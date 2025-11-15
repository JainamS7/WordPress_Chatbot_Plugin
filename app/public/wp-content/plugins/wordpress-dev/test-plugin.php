<?php
/**
 * ZeroEntropy Sync Plugin Test Script
 * 
 * This script tests the plugin functionality without requiring WordPress.
 * Run this to verify the plugin logic works correctly.
 */

// Mock WordPress functions for testing
if (!function_exists('__')) {
    function __($text, $domain = 'zeroentropy-sync') {
        return $text;
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        $options = array(
            'zeroentropy_api_key' => 'test-api-key',
            'zeroentropy_base_url' => 'https://api.zeroentropy.dev/v1',
            'zeroentropy_collection_name' => 'wordpress_posts',
            'zeroentropy_auto_sync' => false,
            'zeroentropy_last_sync' => time() - 3600
        );
        return isset($options[$option]) ? $options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        echo "Updated option: $option = $value\n";
        return true;
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = array()) {
        echo "POST request to: $url\n";
        echo "Headers: " . json_encode($args['headers'] ?? array()) . "\n";
        echo "Body: " . ($args['body'] ?? '') . "\n";
        
        // Mock successful response
        return array(
            'response' => array('code' => 200),
            'body' => json_encode(array(
                'num_documents' => 5,
                'num_indexed_documents' => 5,
                'num_parsing_documents' => 0,
                'num_indexing_documents' => 0,
                'num_failed_documents' => 0
            ))
        );
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response) {
        return $response['response']['code'] ?? 200;
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'] ?? '';
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($response) {
        return false;
    }
}

if (!function_exists('get_posts')) {
    function get_posts($args) {
        return array(
            (object) array(
                'ID' => 1,
                'post_title' => 'Test Post 1',
                'post_content' => 'This is test content for post 1.',
                'post_excerpt' => 'Test excerpt 1',
                'post_date' => '2024-01-01 00:00:00',
                'post_author' => 1
            ),
            (object) array(
                'ID' => 2,
                'post_title' => 'Test Post 2',
                'post_content' => 'This is test content for post 2.',
                'post_excerpt' => 'Test excerpt 2',
                'post_date' => '2024-01-02 00:00:00',
                'post_author' => 1
            )
        );
    }
}

if (!function_exists('get_the_author_meta')) {
    function get_the_author_meta($field, $user_id) {
        return 'Test Author';
    }
}

if (!function_exists('wp_get_post_categories')) {
    function wp_get_post_categories($post_id, $args = array()) {
        return array('Technology', 'WordPress');
    }
}

if (!function_exists('wp_get_post_tags')) {
    function wp_get_post_tags($post_id, $args = array()) {
        return array('test', 'plugin');
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post_id) {
        return "https://example.com/post-$post_id";
    }
}

if (!function_exists('error_log')) {
    function error_log($message) {
        echo "ERROR: $message\n";
    }
}

/**
 * Test the plugin functionality
 */
class ZeroEntropySyncTest {
    
    private $plugin;
    
    public function __construct() {
        // Include the plugin file
        require_once __DIR__ . '/zeroentropy-sync.php';
        $this->plugin = ZeroEntropySync::get_instance();
    }
    
    public function test_connection() {
        echo "=== Testing Connection ===\n";
        $result = $this->plugin->test_connection();
        echo "Result: " . json_encode($result) . "\n\n";
        return $result['success'];
    }
    
    public function test_sync_posts() {
        echo "=== Testing Post Sync ===\n";
        $result = $this->plugin->sync_posts(2);
        echo "Result: " . json_encode($result) . "\n\n";
        return $result['success'];
    }
    
    public function run_all_tests() {
        echo "ZeroEntropy Sync Plugin Test\n";
        echo str_repeat("=", 40) . "\n\n";
        
        $tests_passed = 0;
        $total_tests = 2;
        
        if ($this->test_connection()) {
            $tests_passed++;
        }
        
        if ($this->test_sync_posts()) {
            $tests_passed++;
        }
        
        echo "=== Test Results ===\n";
        echo "Passed: $tests_passed/$total_tests\n";
        
        if ($tests_passed === $total_tests) {
            echo "✓ All tests passed!\n";
            return true;
        } else {
            echo "✗ Some tests failed.\n";
            return false;
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $tester = new ZeroEntropySyncTest();
    $success = $tester->run_all_tests();
    exit($success ? 0 : 1);
}
