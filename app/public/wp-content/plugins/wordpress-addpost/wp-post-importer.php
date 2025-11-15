<?php
/**
 * Plugin Name: WordPress Post Importer
 * Plugin URI: https://github.com/your-repo/wp-post-importer
 * Description: Import posts from external WordPress websites using the WordPress REST API.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: wp-post-importer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_POST_IMPORTER_VERSION', '1.0.0');
define('WP_POST_IMPORTER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_POST_IMPORTER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main WordPress Post Importer Plugin Class
 */
class WordPressPostImporter {
    
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
        
        // Use unique action names to prevent conflicts
        add_action('wp_ajax_wp_post_importer_test_connection', array($this, 'ajax_test_connection'));
        add_action('wp_ajax_wp_post_importer_get_posts', array($this, 'ajax_get_posts'));
        add_action('wp_ajax_wp_post_importer_import_posts', array($this, 'ajax_import_posts'));
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('wp-post-importer', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Post Importer', 'wp-post-importer'),
            __('Post Importer', 'wp-post-importer'),
            'manage_options',
            'wp-post-importer',
            array($this, 'admin_page'),
            'dashicons-download',
            30
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-post-importer') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'wp-post-importer-admin',
            WP_POST_IMPORTER_PLUGIN_URL . 'assets/admin.js',
            array('jquery'),
            WP_POST_IMPORTER_VERSION,
            true
        );
        
        wp_localize_script('wp-post-importer-admin', 'wpPostImporter', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_post_importer_nonce'),
            'strings' => array(
                'test_success' => __('Connection test successful!', 'wp-post-importer'),
                'test_error' => __('Connection test failed. Please check the URL.', 'wp-post-importer'),
                'import_success' => __('Posts imported successfully!', 'wp-post-importer'),
                'import_error' => __('Error importing posts. Please try again.', 'wp-post-importer'),
                'confirm_import' => __('Are you sure you want to import these posts?', 'wp-post-importer'),
                'loading' => __('Loading...', 'wp-post-importer')
            )
        ));
        
        wp_enqueue_style(
            'wp-post-importer-admin',
            WP_POST_IMPORTER_PLUGIN_URL . 'assets/admin.css',
            array(),
            WP_POST_IMPORTER_VERSION
        );
    }
    
    /**
     * Main admin page
     */
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('WordPress Post Importer', 'wp-post-importer'); ?></h1>
            
            <div class="wp-post-importer-container">
                <div class="wp-post-importer-main-content">
                    <div class="wp-post-importer-card">
                        <h2><?php _e('Import Posts', 'wp-post-importer'); ?></h2>
                        <p><?php _e('Import posts from an external WordPress website using the WordPress REST API.', 'wp-post-importer'); ?></p>
                        
                        <form id="wp-post-importer-form">
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="source_url"><?php _e('Source Website URL', 'wp-post-importer'); ?></label>
                                    </th>
                                    <td>
                                        <input type="url" id="source_url" name="source_url" class="regular-text" placeholder="https://example.com" required />
                                        <p class="description"><?php _e('Enter the URL of the WordPress website you want to import posts from', 'wp-post-importer'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="num_posts"><?php _e('Number of Posts', 'wp-post-importer'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" id="num_posts" name="num_posts" value="10" min="1" max="100" class="regular-text" />
                                        <p class="description"><?php _e('Maximum number of recent posts to fetch (1-100)', 'wp-post-importer'); ?></p>
                                    </td>
                                </tr>
                            </table>
                            
                            <p class="submit">
                                <button type="button" id="test-connection" class="button button-secondary">
                                    <?php _e('Test Connection', 'wp-post-importer'); ?>
                                </button>
                                <button type="button" id="get-posts" class="button button-secondary">
                                    <?php _e('Get Posts', 'wp-post-importer'); ?>
                                </button>
                                <button type="submit" id="import-posts" class="button button-primary" style="display: none;">
                                    <?php _e('Import Selected Posts', 'wp-post-importer'); ?>
                                </button>
                            </p>
                        </form>
                        
                        <div id="connection-status" class="wp-post-importer-status" style="display: none;"></div>
                        <div id="posts-list" class="wp-post-importer-posts" style="display: none;"></div>
                        <div id="import-progress" class="wp-post-importer-progress" style="display: none;">
                            <div class="progress-bar">
                                <div class="progress-fill"></div>
                            </div>
                            <p class="progress-text"><?php _e('Importing posts...', 'wp-post-importer'); ?></p>
                        </div>
                        <div id="import-results" class="wp-post-importer-results" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for testing connection
     */
    public function ajax_test_connection() {
        // Prevent infinite loops
        if (defined('WP_POST_IMPORTER_PROCESSING')) {
            wp_die('Request already in progress');
        }
        
        define('WP_POST_IMPORTER_PROCESSING', true);
        
        check_ajax_referer('wp_post_importer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-post-importer'));
        }
        
        $source_url = sanitize_url($_POST['source_url']);
        $result = $this->test_connection($source_url);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for getting posts
     */
    public function ajax_get_posts() {
        // Prevent infinite loops
        if (defined('WP_POST_IMPORTER_PROCESSING')) {
            wp_die('Request already in progress');
        }
        
        define('WP_POST_IMPORTER_PROCESSING', true);
        
        check_ajax_referer('wp_post_importer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-post-importer'));
        }
        
        $source_url = sanitize_url($_POST['source_url']);
        $num_posts = intval($_POST['num_posts']);
        
        // Additional validation
        if (empty($source_url)) {
            wp_send_json_error(array(
                'message' => __('Source URL is required', 'wp-post-importer')
            ));
        }
        
        if ($num_posts < 1 || $num_posts > 100) {
            wp_send_json_error(array(
                'message' => __('Number of posts must be between 1 and 100', 'wp-post-importer')
            ));
        }
        
        $result = $this->get_posts($source_url, $num_posts);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * AJAX handler for importing posts
     */
    public function ajax_import_posts() {
        // Prevent infinite loops
        if (defined('WP_POST_IMPORTER_PROCESSING')) {
            wp_die('Request already in progress');
        }
        
        define('WP_POST_IMPORTER_PROCESSING', true);
        
        check_ajax_referer('wp_post_importer_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'wp-post-importer'));
        }
        
        $source_url = sanitize_url($_POST['source_url']);
        $num_posts = intval($_POST['num_posts']);
        $selected_posts = isset($_POST['selected_posts']) ? array_map('intval', $_POST['selected_posts']) : array();
        
        $result = $this->import_posts($source_url, $num_posts, $selected_posts);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Test connection to external WordPress site - Simple like CLI
     */
    public function test_connection($source_url) {
        if (empty($source_url)) {
            return array(
                'success' => false,
                'message' => __('Source URL is required', 'wp-post-importer')
            );
        }
        
        // Prevent self-referencing
        $current_site_url = home_url();
        $source_url_clean = rtrim($source_url, '/');
        $current_site_url_clean = rtrim($current_site_url, '/');
        
        if ($source_url_clean === $current_site_url_clean) {
            return array(
                'success' => false,
                'message' => __('Cannot import from the same site. Please use a different WordPress website URL.', 'wp-post-importer')
            );
        }
        
        try {
            // Simple test like CLI tool
            $api_url = $source_url_clean . '/wp-json/wp/v2/posts?per_page=1';
            $response = wp_remote_get($api_url, array('timeout' => 10));
            
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
                
                if (is_array($data)) {
                    return array(
                        'success' => true,
                        'message' => sprintf(__('Connection successful! Found WordPress REST API at %s', 'wp-post-importer'), $source_url)
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => __('Invalid response format', 'wp-post-importer')
                    );
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                return array(
                    'success' => false,
                    'message' => 'HTTP ' . $status_code . ': ' . substr($body, 0, 200)
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
     * Get posts from external WordPress site - Using exact CLI logic
     */
    public function get_posts($source_url, $num_posts = 10) {
        if (empty($source_url)) {
            return array(
                'success' => false,
                'message' => __('Source URL is required', 'wp-post-importer')
            );
        }
        
        // Prevent self-referencing
        $current_site_url = home_url();
        $source_url_clean = rtrim($source_url, '/');
        $current_site_url_clean = rtrim($current_site_url, '/');
        
        if ($source_url_clean === $current_site_url_clean) {
            return array(
                'success' => false,
                'message' => __('Cannot import from the same site. Please use a different WordPress website URL.', 'wp-post-importer')
            );
        }
        
        try {
            // Use exact same logic as CLI tool
            $api_base = $source_url_clean . '/wp-json/wp/v2';
            $url = $api_base . '/posts';
            $params = array(
                'per_page' => min($num_posts, 100),
                'status' => 'publish'
            );
            
            $full_url = add_query_arg($params, $url);
            
            // Simple request like CLI tool
            $response = wp_remote_get($full_url, array(
                'timeout' => 30
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
                $posts_data = json_decode($body, true);
                
                if (is_array($posts_data)) {
                    $processed_posts = array();
                    
                    // Process each post exactly like CLI tool
                    foreach ($posts_data as $post_data) {
                        $post_id = $post_data['id'] ?? 0;
                        
                        // Fetch detailed post content (like CLI)
                        $detailed_content = $this->fetch_post_content($api_base, $post_id);
                        
                        // Extract author information (like CLI)
                        $author_name = 'Unknown';
                        if (isset($post_data['author'])) {
                            $author_id = $post_data['author'];
                            $author_name = $this->fetch_author_name($api_base, $author_id);
                        }
                        
                        // Extract categories (like CLI)
                        $categories = array();
                        if (isset($post_data['categories'])) {
                            $category_ids = $post_data['categories'];
                            $categories = $this->fetch_categories($api_base, $category_ids);
                        }
                        
                        // Extract tags (like CLI)
                        $tags = array();
                        if (isset($post_data['tags'])) {
                            $tag_ids = $post_data['tags'];
                            $tags = $this->fetch_tags($api_base, $tag_ids);
                        }
                        
                        // Use raw HTML content without modification
                        $content = $detailed_content['content']['rendered'] ?? '';
                        $excerpt = $post_data['excerpt']['rendered'] ?? '';
                        
                        $processed_posts[] = array(
                            'id' => $post_id,
                            'title' => $post_data['title']['rendered'] ?? '',
                            'content' => $content,
                            'excerpt' => $excerpt,
                            'link' => $post_data['link'] ?? '',
                            'date' => $post_data['date'] ?? '',
                            'author' => $author_name,
                            'categories' => $categories,
                            'tags' => $tags,
                            'source_url' => $source_url
                        );
                    }
                    
                    return array(
                        'success' => true,
                        'posts' => $processed_posts,
                        'count' => count($processed_posts)
                    );
                } else {
                    return array(
                        'success' => false,
                        'message' => __('Invalid response format', 'wp-post-importer')
                    );
                }
            } else {
                $body = wp_remote_retrieve_body($response);
                return array(
                    'success' => false,
                    'message' => 'HTTP ' . $status_code . ': ' . substr($body, 0, 200)
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
     * Fetch detailed post content - Exact CLI logic
     */
    private function fetch_post_content($api_base, $post_id) {
        try {
            $url = $api_base . '/posts/' . $post_id;
            $response = wp_remote_get($url, array('timeout' => 30));
            
            if (is_wp_error($response)) {
                return array();
            }
            
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        } catch (Exception $e) {
            return array();
        }
    }
    
    /**
     * Fetch author name by ID - Exact CLI logic
     */
    private function fetch_author_name($api_base, $author_id) {
        try {
            $url = $api_base . '/users/' . $author_id;
            $response = wp_remote_get($url, array('timeout' => 30));
            
            if (is_wp_error($response)) {
                return 'Author ' . $author_id;
            }
            
            $body = wp_remote_retrieve_body($response);
            $author_data = json_decode($body, true);
            return $author_data['name'] ?? 'Author ' . $author_id;
        } catch (Exception $e) {
            return 'Author ' . $author_id;
        }
    }
    
    /**
     * Fetch categories by IDs - Exact CLI logic
     */
    private function fetch_categories($api_base, $category_ids) {
        $categories = array();
        foreach ($category_ids as $cat_id) {
            try {
                $url = $api_base . '/categories/' . $cat_id;
                $response = wp_remote_get($url, array('timeout' => 30));
                
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $cat_data = json_decode($body, true);
                    $categories[] = $cat_data['name'] ?? '';
                }
            } catch (Exception $e) {
                // Continue with next category
            }
        }
        return $categories;
    }
    
    /**
     * Fetch tags by IDs - Exact CLI logic
     */
    private function fetch_tags($api_base, $tag_ids) {
        $tags = array();
        foreach ($tag_ids as $tag_id) {
            try {
                $url = $api_base . '/tags/' . $tag_id;
                $response = wp_remote_get($url, array('timeout' => 30));
                
                if (!is_wp_error($response)) {
                    $body = wp_remote_retrieve_body($response);
                    $tag_data = json_decode($body, true);
                    $tags[] = $tag_data['name'] ?? '';
                }
            } catch (Exception $e) {
                // Continue with next tag
            }
        }
        return $tags;
    }
    
    /**
     * Clean HTML content using lynx-style approach - DISABLED
     * Now using raw HTML content without modification
     */
    /*
    private function clean_html_with_lynx_style($html_content) {
        // Decode HTML entities first
        $text = html_entity_decode($html_content, ENT_QUOTES, 'UTF-8');
        
        // Remove script and style elements completely
        $text = preg_replace('/<(script|style)[^>]*>.*?<\/\1>/is', '', $text);
        
        // Convert common HTML elements to text equivalents
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        $text = preg_replace('/<\/div>/i', "\n", $text);
        $text = preg_replace('/<\/h[1-6]>/i', "\n\n", $text);
        $text = preg_replace('/<\/li>/i', "\n", $text);
        
        // Remove all remaining HTML tags
        $text = preg_replace('/<[^>]+>/', '', $text);
        
        // Clean up whitespace
        $text = preg_replace('/\n\s*\n/', "\n\n", $text);  // Multiple newlines to double newline
        $text = preg_replace('/[ \t]+/', ' ', $text);      // Multiple spaces/tabs to single space
        $text = preg_replace('/\n /', "\n", $text);       // Remove leading spaces from lines
        
        return trim($text);
    }
    */
    
    /**
     * Import posts to WordPress
     */
    public function import_posts($source_url, $num_posts = 10, $selected_posts = array()) {
        $posts_result = $this->get_posts($source_url, $num_posts);
        
        if (!$posts_result['success']) {
            return $posts_result;
        }
        
        $posts = $posts_result['posts'];
        $imported_count = 0;
        $errors = array();
        
        foreach ($posts as $post) {
            // Skip if not selected (when specific posts are chosen)
            if (!empty($selected_posts) && !in_array($post['id'], $selected_posts)) {
                continue;
            }
            
            $result = $this->import_single_post($post);
            if ($result['success']) {
                $imported_count++;
            } else {
                $errors[] = $result['message'];
            }
        }
        
        return array(
            'success' => true,
            'message' => sprintf(__('Successfully imported %d out of %d posts', 'wp-post-importer'), $imported_count, count($posts)),
            'imported' => $imported_count,
            'total' => count($posts),
            'errors' => $errors
        );
    }
    
    /**
     * Import single post
     */
    private function import_single_post($post) {
        try {
            // Check if post already exists
            $existing_posts = get_posts(array(
                'meta_key' => '_imported_from_id',
                'meta_value' => $post['id'],
                'post_type' => 'post',
                'posts_per_page' => 1
            ));
            
            if (!empty($existing_posts)) {
                return array(
                    'success' => false,
                    'message' => 'Post already imported'
                );
            }
            
            // Create the post
            $new_post = array(
                'post_title' => $post['title'],
                'post_content' => $post['content'],
                'post_excerpt' => $post['excerpt'],
                'post_status' => 'draft',
                'post_author' => 1,
                'post_date' => $post['date'],
                'post_type' => 'post',
                'meta_input' => array(
                    '_imported_from_id' => $post['id'],
                    '_imported_from_url' => $post['source_url'],
                    '_imported_original_link' => $post['link'],
                    '_imported_original_author' => $post['author']
                )
            );
            
            $new_post_id = wp_insert_post($new_post);
            
            if (is_wp_error($new_post_id)) {
                return array(
                    'success' => false,
                    'message' => $new_post_id->get_error_message()
                );
            }
            
            // Add tags
            if (!empty($post['tags'])) {
                wp_set_post_tags($new_post_id, $post['tags']);
            }
            
            return array(
                'success' => true,
                'message' => 'Post imported successfully',
                'post_id' => $new_post_id
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Initialize the plugin
WordPressPostImporter::get_instance();