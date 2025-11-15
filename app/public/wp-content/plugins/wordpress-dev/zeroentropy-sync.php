<?php
/**
 * Plugin Name: ZeroEntropy Sync
 * Plugin URI: https://github.com/your-repo/zeroentropy-sync
 * Description: Automatically sync WordPress posts to ZeroEntropy for enhanced search capabilities.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: zeroentropy-sync
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ZEROENTROPY_SYNC_VERSION', '1.0.0');
define('ZEROENTROPY_SYNC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ZEROENTROPY_SYNC_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main ZeroEntropy Sync Plugin Class
 */
class ZeroEntropySync {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_zeroentropy_sync_posts', array($this, 'ajax_sync_posts'));
        add_action('wp_ajax_zeroentropy_test_connection', array($this, 'ajax_test_connection'));
        add_action('save_post', array($this, 'auto_sync_post'), 10, 2);
        add_action('delete_post', array($this, 'handle_post_deletion'));
        add_action('zeroentropy_sync_single_post', array($this, 'sync_single_post_scheduled'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('zeroentropy-sync', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('ZeroEntropy Sync', 'zeroentropy-sync'),
            __('ZeroEntropy Sync', 'zeroentropy-sync'),
            'manage_options',
            'zeroentropy-sync',
            array($this, 'admin_page'),
            'dashicons-search',
            30
        );
        
        add_submenu_page(
            'zeroentropy-sync',
            __('Settings', 'zeroentropy-sync'),
            __('Settings', 'zeroentropy-sync'),
            'manage_options',
            'zeroentropy-sync-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'zeroentropy-sync') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'zeroentropy-sync-admin',
            ZEROENTROPY_SYNC_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            ZEROENTROPY_SYNC_VERSION,
            true
        );
        
        wp_localize_script('zeroentropy-sync-admin', 'zeroentropySync', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('zeroentropy_sync_nonce'),
            'strings' => array(
                'sync_success' => __('Posts synced successfully!', 'zeroentropy-sync'),
                'sync_error' => __('Error syncing posts. Please check your settings.', 'zeroentropy-sync'),
                'test_success' => __('Connection test successful!', 'zeroentropy-sync'),
                'test_error' => __('Connection test failed. Please check your API key.', 'zeroentropy-sync'),
                'confirm_sync' => __('Are you sure you want to sync all posts? This may take a while.', 'zeroentropy-sync')
            )
        ));
        
        wp_enqueue_style(
            'zeroentropy-sync-admin',
            ZEROENTROPY_SYNC_PLUGIN_URL . 'assets/admin.css',
            array(),
            ZEROENTROPY_SYNC_VERSION
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        $api_key = get_option('zeroentropy_api_key', '');
        $base_url = get_option('zeroentropy_base_url', 'https://api.zeroentropy.dev/v1');
        $collection_name = get_option('zeroentropy_collection_name', 'wordpress_posts');
        $auto_sync = get_option('zeroentropy_auto_sync', false);
        
        $total_posts = wp_count_posts('post');
        $published_posts = $total_posts->publish;
        
        include ZEROENTROPY_SYNC_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $api_key = get_option('zeroentropy_api_key', '');
        $base_url = get_option('zeroentropy_base_url', 'https://api.zeroentropy.dev/v1');
        $collection_name = get_option('zeroentropy_collection_name', 'wordpress_posts');
        $auto_sync = get_option('zeroentropy_auto_sync', false);
        
        include ZEROENTROPY_SYNC_PLUGIN_DIR . 'templates/settings-page.php';
    }
    
    /**
     * Save plugin settings
     */
    private function save_settings() {
        if (!wp_verify_nonce($_POST['zeroentropy_sync_nonce'], 'zeroentropy_sync_settings')) {
            wp_die(__('Security check failed', 'zeroentropy-sync'));
        }
        
        update_option('zeroentropy_api_key', sanitize_text_field($_POST['api_key']));
        update_option('zeroentropy_base_url', esc_url_raw($_POST['base_url']));
        update_option('zeroentropy_collection_name', sanitize_text_field($_POST['collection_name']));
        update_option('zeroentropy_auto_sync', isset($_POST['auto_sync']));
        
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'zeroentropy-sync') . '</p></div>';
        });
    }
    
    /**
     * AJAX handler for syncing posts
     */
    public function ajax_sync_posts() {
        check_ajax_referer('zeroentropy_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zeroentropy-sync'));
        }
        
        $num_posts = intval($_POST['num_posts'] ?? 10);
        $result = $this->sync_posts($num_posts);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        check_ajax_referer('zeroentropy_sync_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'zeroentropy-sync'));
        }
        
        $result = $this->test_connection();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Sync posts to ZeroEntropy
     */
    public function sync_posts($num_posts = 10) {
        $api_key = get_option('zeroentropy_api_key');
        $base_url = get_option('zeroentropy_base_url', 'https://api.zeroentropy.dev/v1');
        $collection_name = get_option('zeroentropy_collection_name', 'wordpress_posts');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('API key not configured', 'zeroentropy-sync')
            );
        }
        
        try {
            // Create collection first
            $this->create_collection($api_key, $base_url, $collection_name);
            
            // Get posts
            $posts = get_posts(array(
                'numberposts' => $num_posts,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC'
            ));
            
            $success_count = 0;
            $errors = array();
            
            foreach ($posts as $post) {
                $result = $this->sync_single_post($post, $api_key, $base_url, $collection_name);
                if ($result['success']) {
                    $success_count++;
                } else {
                    $errors[] = $result['message'];
                }
            }
            
            // Update last sync time
            update_option('zeroentropy_last_sync', time());
            
            return array(
                'success' => true,
                'message' => sprintf(__('Successfully synced %d out of %d posts', 'zeroentropy-sync'), $success_count, count($posts)),
                'synced' => $success_count,
                'total' => count($posts),
                'errors' => $errors
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get collection list from ZeroEntropy
     */
    private function get_collection_list($api_key, $base_url) {
        $response = wp_remote_post($base_url . '/collections/get-collection-list', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(new stdClass()),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code === 200) {
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            return $data['collection_names'] ?? array();
        } else {
            $body = wp_remote_retrieve_body($response);
            throw new Exception('Failed to get collection list: HTTP ' . $status_code . ': ' . $body);
        }
    }
    
    /**
     * Sync a single post to ZeroEntropy
     */
    private function sync_single_post($post, $api_key, $base_url, $collection_name) {
        try {
            // Prepare content
            $content = $this->prepare_post_content($post);
            $metadata = $this->prepare_post_metadata($post);
            
            // Send to ZeroEntropy
            $response = wp_remote_post($base_url . '/documents/add-document', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'collection_name' => $collection_name,
                    'path' => 'post_' . $post->ID,
                    'content' => array(
                        'type' => 'text',
                        'text' => $content
                    ),
                    'metadata' => $metadata
                )),
                'timeout' => 30
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message()
                );
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code >= 200 && $status_code < 300) {
                return array('success' => true);
            } else {
                $body = wp_remote_retrieve_body($response);
                return array(
                    'success' => false,
                    'message' => 'HTTP ' . $status_code . ': ' . $body
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Prepare post content for ZeroEntropy
     */
    private function prepare_post_content($post) {
        $author = get_the_author_meta('display_name', $post->post_author);
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        
        $content = "Title: " . $post->post_title . "\n\n";
        $content .= "Author: " . $author . "\n";
        $content .= "Date: " . $post->post_date . "\n";
        $content .= "Categories: " . implode(', ', $categories) . "\n";
        $content .= "Tags: " . implode(', ', $tags) . "\n\n";
        $content .= "Excerpt: " . $post->post_excerpt . "\n\n";
        $content .= "Content: " . strip_tags($post->post_content);
        
        return $content;
    }
    
    /**
     * Prepare post metadata for ZeroEntropy
     */
    private function prepare_post_metadata($post) {
        $author = get_the_author_meta('display_name', $post->post_author);
        $categories = wp_get_post_categories($post->ID, array('fields' => 'names'));
        $tags = wp_get_post_tags($post->ID, array('fields' => 'names'));
        
        return array(
            'title' => $post->post_title,
            'author' => $author,
            'date' => $post->post_date,
            'categories' => implode(', ', $categories),
            'tags' => implode(', ', $tags),
            'link' => get_permalink($post->ID),
            'post_id' => (string) $post->ID
        );
    }
    
    /**
     * Create collection in ZeroEntropy
     */
    private function create_collection($api_key, $base_url, $collection_name) {
        $response = wp_remote_post($base_url . '/collections/add-collection', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'collection_name' => $collection_name
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 201 && $status_code !== 409) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception('Failed to create collection: HTTP ' . $status_code . ': ' . $body);
        }
    }
    
    /**
     * Test connection to ZeroEntropy
     */
    public function test_connection() {
        $api_key = get_option('zeroentropy_api_key');
        $base_url = get_option('zeroentropy_base_url', 'https://api.zeroentropy.dev/v1');
        $collection_name = get_option('zeroentropy_collection_name', 'wordpress_posts');
        
        if (empty($api_key)) {
            return array(
                'success' => false,
                'message' => __('API key not configured', 'zeroentropy-sync')
            );
        }
        
        try {
            // First test general connection
            $response = wp_remote_post($base_url . '/status/get-status', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(new stdClass()),
                'timeout' => 10
            ));
            
            if (is_wp_error($response)) {
                return array(
                    'success' => false,
                    'message' => $response->get_error_message()
                );
            }
            
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code === 200) {
                $body = wp_remote_retrieve_body($response);
                $data = json_decode($body, true);
                
                // Try to get collection-specific status
                $collection_response = wp_remote_post($base_url . '/status/get-status', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                        'Content-Type' => 'application/json'
                    ),
                    'body' => json_encode(array('collection_name' => $collection_name)),
                    'timeout' => 10
                ));
                
                $message = __('Connection successful!', 'zeroentropy-sync');
                if (!is_wp_error($collection_response) && wp_remote_retrieve_response_code($collection_response) === 200) {
                    $collection_body = wp_remote_retrieve_body($collection_response);
                    $collection_data = json_decode($collection_body, true);
                    if ($collection_data && isset($collection_data['num_documents'])) {
                        $message = sprintf(__('Connection successful! Collection "%s" has %d documents.', 'zeroentropy-sync'), $collection_name, $collection_data['num_documents']);
                    }
                } elseif ($data && isset($data['num_documents'])) {
                    $message = sprintf(__('Connection successful! Found %d documents across all collections.', 'zeroentropy-sync'), $data['num_documents']);
                }
                
                return array(
                    'success' => true,
                    'message' => $message
                );
            } else {
                $body = wp_remote_retrieve_body($response);
                return array(
                    'success' => false,
                    'message' => 'HTTP ' . $status_code . ': ' . $body
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Auto-sync post when saved
     */
    public function auto_sync_post($post_id, $post) {
        $auto_sync = get_option('zeroentropy_auto_sync', false);
        
        if (!$auto_sync || $post->post_status !== 'publish') {
            return;
        }
        
        // Prevent infinite loops
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }
        
        // Sync in background
        wp_schedule_single_event(time(), 'zeroentropy_sync_single_post', array($post_id));
    }
    
    /**
     * Handle scheduled single post sync
     */
    public function sync_single_post_scheduled($post_id) {
        $api_key = get_option('zeroentropy_api_key');
        $base_url = get_option('zeroentropy_base_url', 'https://api.zeroentropy.dev/v1');
        $collection_name = get_option('zeroentropy_collection_name', 'wordpress_posts');
        
        if (empty($api_key)) {
            return;
        }
        
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        
        try {
            $this->create_collection($api_key, $base_url, $collection_name);
            $result = $this->sync_single_post($post, $api_key, $base_url, $collection_name);
            
            if ($result['success']) {
                update_option('zeroentropy_last_sync', time());
            }
        } catch (Exception $e) {
            error_log('ZeroEntropy Sync: Failed to sync post ' . $post_id . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Handle post deletion
     */
    public function handle_post_deletion($post_id) {
        $api_key = get_option('zeroentropy_api_key');
        $base_url = get_option('zeroentropy_base_url', 'https://api.zeroentropy.dev/v1');
        $collection_name = get_option('zeroentropy_collection_name', 'wordpress_posts');
        
        if (empty($api_key)) {
            return;
        }
        
        try {
            $this->delete_document($api_key, $base_url, $collection_name, 'post_' . $post_id);
        } catch (Exception $e) {
            // Log error but don't fail the post deletion
            error_log('ZeroEntropy Sync: Failed to delete document for post ' . $post_id . ': ' . $e->getMessage());
        }
    }
    
    /**
     * Delete document from ZeroEntropy
     */
    private function delete_document($api_key, $base_url, $collection_name, $path) {
        $response = wp_remote_post($base_url . '/documents/delete-document', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'collection_name' => $collection_name,
                'path' => $path
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            throw new Exception('Failed to delete document: HTTP ' . $status_code . ': ' . $body);
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create default options
        add_option('zeroentropy_api_key', '');
        add_option('zeroentropy_base_url', 'https://api.zeroentropy.dev/v1');
        add_option('zeroentropy_collection_name', 'wordpress_posts');
        add_option('zeroentropy_auto_sync', false);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('zeroentropy_sync_single_post');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
ZeroEntropySync::get_instance();
