<?php
/**
 * Admin page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('ZeroEntropy Sync', 'zeroentropy-sync'); ?></h1>
    
    <div class="zeroentropy-admin-container">
        <div class="zeroentropy-main-content">
            <div class="zeroentropy-card">
                <h2><?php _e('Sync Status', 'zeroentropy-sync'); ?></h2>
                
                <div class="zeroentropy-status-grid">
                    <div class="status-item">
                        <span class="status-label"><?php _e('Total Posts:', 'zeroentropy-sync'); ?></span>
                        <span class="status-value"><?php echo number_format($published_posts); ?></span>
                    </div>
                    
                    <div class="status-item">
                        <span class="status-label"><?php _e('API Key:', 'zeroentropy-sync'); ?></span>
                        <span class="status-value <?php echo empty($api_key) ? 'status-error' : 'status-success'; ?>">
                            <?php echo empty($api_key) ? __('Not configured', 'zeroentropy-sync') : __('Configured', 'zeroentropy-sync'); ?>
                        </span>
                    </div>
                    
                    <div class="status-item">
                        <span class="status-label"><?php _e('Collection:', 'zeroentropy-sync'); ?></span>
                        <span class="status-value"><?php echo esc_html($collection_name); ?></span>
                    </div>
                    
                    <div class="status-item">
                        <span class="status-label"><?php _e('Auto Sync:', 'zeroentropy-sync'); ?></span>
                        <span class="status-value <?php echo $auto_sync ? 'status-success' : 'status-warning'; ?>">
                            <?php echo $auto_sync ? __('Enabled', 'zeroentropy-sync') : __('Disabled', 'zeroentropy-sync'); ?>
                        </span>
                    </div>
                    
                    <div class="status-item">
                        <span class="status-label"><?php _e('API Base URL:', 'zeroentropy-sync'); ?></span>
                        <span class="status-value"><?php echo esc_html($base_url); ?></span>
                    </div>
                    
                    <div class="status-item">
                        <span class="status-label"><?php _e('Last Sync:', 'zeroentropy-sync'); ?></span>
                        <span class="status-value"><?php 
                            $last_sync = get_option('zeroentropy_last_sync', '');
                            echo $last_sync ? date('Y-m-d H:i:s', $last_sync) : __('Never', 'zeroentropy-sync');
                        ?></span>
                    </div>
                </div>
            </div>
            
            <div class="zeroentropy-card">
                <h2><?php _e('Manual Sync', 'zeroentropy-sync'); ?></h2>
                <p><?php _e('Manually sync your WordPress posts to ZeroEntropy for enhanced search capabilities.', 'zeroentropy-sync'); ?></p>
                
                <form id="zeroentropy-sync-form">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="num_posts"><?php _e('Number of Posts', 'zeroentropy-sync'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="num_posts" name="num_posts" value="10" min="1" max="100" class="regular-text" />
                                <p class="description"><?php _e('Maximum number of recent posts to sync (1-100)', 'zeroentropy-sync'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <button type="button" id="test-connection" class="button button-secondary">
                            <?php _e('Test Connection', 'zeroentropy-sync'); ?>
                        </button>
                        <button type="submit" id="sync-posts" class="button button-primary">
                            <?php _e('Sync Posts', 'zeroentropy-sync'); ?>
                        </button>
                    </p>
                </form>
                
                <div id="sync-progress" class="zeroentropy-progress" style="display: none;">
                    <div class="progress-bar">
                        <div class="progress-fill"></div>
                    </div>
                    <p class="progress-text"><?php _e('Syncing posts...', 'zeroentropy-sync'); ?></p>
                </div>
                
                <div id="sync-results" class="zeroentropy-results" style="display: none;"></div>
            </div>
        </div>
        
        <div class="zeroentropy-sidebar">
            <div class="zeroentropy-card">
                <h3><?php _e('Quick Actions', 'zeroentropy-sync'); ?></h3>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=zeroentropy-sync-settings'); ?>" class="button button-secondary">
                        <?php _e('Settings', 'zeroentropy-sync'); ?>
                    </a>
                </p>
                <p>
                    <a href="https://api.zeroentropy.dev/docs" target="_blank" class="button button-secondary">
                        <?php _e('API Documentation', 'zeroentropy-sync'); ?>
                    </a>
                </p>
            </div>
            
            <div class="zeroentropy-card">
                <h3><?php _e('About ZeroEntropy', 'zeroentropy-sync'); ?></h3>
                <p><?php _e('ZeroEntropy provides advanced search capabilities for your WordPress content. Sync your posts to enable semantic search and better content discovery.', 'zeroentropy-sync'); ?></p>
            </div>
        </div>
    </div>
</div>
