<?php
/**
 * ZeroEntropy Sync Plugin Installation Script
 * 
 * This script helps with the installation and setup of the ZeroEntropy Sync plugin.
 * Run this script to check system requirements and create necessary directories.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // If not in WordPress context, define basic constants
    if (!defined('ABSPATH')) {
        define('ABSPATH', dirname(__FILE__) . '/../../');
    }
}

/**
 * Installation checker class
 */
class ZeroEntropySyncInstaller {
    
    private $errors = array();
    private $warnings = array();
    
    /**
     * Check system requirements
     */
    public function check_requirements() {
        echo "Checking system requirements...\n\n";
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $this->errors[] = "PHP 7.4 or higher is required. Current version: " . PHP_VERSION;
        } else {
            echo "✓ PHP version: " . PHP_VERSION . " (OK)\n";
        }
        
        // Check cURL extension
        if (!extension_loaded('curl')) {
            $this->errors[] = "cURL extension is required but not installed.";
        } else {
            echo "✓ cURL extension: Available\n";
        }
        
        // Check JSON extension
        if (!extension_loaded('json')) {
            $this->errors[] = "JSON extension is required but not installed.";
        } else {
            echo "✓ JSON extension: Available\n";
        }
        
        // Check if we're in WordPress context
        if (!function_exists('wp_remote_post')) {
            $this->warnings[] = "Not running in WordPress context. Some features may not work.";
        } else {
            echo "✓ WordPress context: Available\n";
        }
        
        // Check file permissions
        $plugin_dir = dirname(__FILE__);
        if (!is_writable($plugin_dir)) {
            $this->warnings[] = "Plugin directory is not writable: " . $plugin_dir;
        } else {
            echo "✓ Plugin directory: Writable\n";
        }
        
        return empty($this->errors);
    }
    
    /**
     * Create necessary directories
     */
    public function create_directories() {
        echo "\nCreating directories...\n";
        
        $directories = array(
            'templates',
            'assets',
            'languages'
        );
        
        foreach ($directories as $dir) {
            $path = dirname(__FILE__) . '/' . $dir;
            if (!is_dir($path)) {
                if (mkdir($path, 0755, true)) {
                    echo "✓ Created directory: " . $dir . "\n";
                } else {
                    $this->errors[] = "Failed to create directory: " . $dir;
                }
            } else {
                echo "✓ Directory exists: " . $dir . "\n";
            }
        }
    }
    
    /**
     * Validate plugin files
     */
    public function validate_files() {
        echo "\nValidating plugin files...\n";
        
        $required_files = array(
            'zeroentropy-sync.php',
            'templates/admin-page.php',
            'templates/settings-page.php',
            'assets/admin.js',
            'assets/admin.css'
        );
        
        foreach ($required_files as $file) {
            $path = dirname(__FILE__) . '/' . $file;
            if (file_exists($path)) {
                echo "✓ File exists: " . $file . "\n";
            } else {
                $this->errors[] = "Required file missing: " . $file;
            }
        }
    }
    
    /**
     * Display installation summary
     */
    public function display_summary() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "INSTALLATION SUMMARY\n";
        echo str_repeat("=", 50) . "\n";
        
        if (empty($this->errors)) {
            echo "✓ Installation successful!\n\n";
            echo "Next steps:\n";
            echo "1. Upload the plugin to your WordPress site\n";
            echo "2. Activate the plugin in WordPress admin\n";
            echo "3. Configure your ZeroEntropy API key\n";
            echo "4. Start syncing your posts!\n";
        } else {
            echo "✗ Installation failed!\n\n";
            echo "Errors:\n";
            foreach ($this->errors as $error) {
                echo "- " . $error . "\n";
            }
        }
        
        if (!empty($this->warnings)) {
            echo "\nWarnings:\n";
            foreach ($this->warnings as $warning) {
                echo "- " . $warning . "\n";
            }
        }
        
        echo "\n" . str_repeat("=", 50) . "\n";
    }
    
    /**
     * Run installation
     */
    public function install() {
        echo "ZeroEntropy Sync Plugin Installer\n";
        echo str_repeat("=", 40) . "\n\n";
        
        $this->check_requirements();
        $this->create_directories();
        $this->validate_files();
        $this->display_summary();
        
        return empty($this->errors);
    }
}

// Run installer if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $installer = new ZeroEntropySyncInstaller();
    $success = $installer->install();
    exit($success ? 0 : 1);
}
