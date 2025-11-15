<?php
/**
 * Settings page template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('ZeroEntropy Sync Settings', 'zeroentropy-sync'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('zeroentropy_sync_settings', 'zeroentropy_sync_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="api_key"><?php _e('ZeroEntropy API Key', 'zeroentropy-sync'); ?></label>
                </th>
                <td>
                    <input type="password" id="api_key" name="api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" required />
                    <p class="description">
                        <?php _e('Your ZeroEntropy API key. Get one from', 'zeroentropy-sync'); ?> 
                        <a href="https://api.zeroentropy.dev" target="_blank">api.zeroentropy.dev</a>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="base_url"><?php _e('API Base URL', 'zeroentropy-sync'); ?></label>
                </th>
                <td>
                    <input type="url" id="base_url" name="base_url" value="<?php echo esc_attr($base_url); ?>" class="regular-text" required />
                    <p class="description"><?php _e('The base URL for the ZeroEntropy API', 'zeroentropy-sync'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="collection_name"><?php _e('Collection Name', 'zeroentropy-sync'); ?></label>
                </th>
                <td>
                    <input type="text" id="collection_name" name="collection_name" value="<?php echo esc_attr($collection_name); ?>" class="regular-text" required />
                    <p class="description"><?php _e('Name of the collection to store your posts in', 'zeroentropy-sync'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="auto_sync"><?php _e('Auto Sync', 'zeroentropy-sync'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" id="auto_sync" name="auto_sync" value="1" <?php checked($auto_sync); ?> />
                        <?php _e('Automatically sync posts when they are published or updated', 'zeroentropy-sync'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, posts will be automatically synced to ZeroEntropy when published or updated', 'zeroentropy-sync'); ?></p>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" class="button button-primary" value="<?php _e('Save Settings', 'zeroentropy-sync'); ?>" />
        </p>
    </form>
    
    <div class="zeroentropy-settings-info">
        <h3><?php _e('Configuration Help', 'zeroentropy-sync'); ?></h3>
        
        <div class="zeroentropy-info-grid">
            <div class="info-item">
                <h4><?php _e('API Key', 'zeroentropy-sync'); ?></h4>
                <p><?php _e('You need a ZeroEntropy API key to use this plugin. Sign up at api.zeroentropy.dev to get your free API key.', 'zeroentropy-sync'); ?></p>
            </div>
            
            <div class="info-item">
                <h4><?php _e('Collection Name', 'zeroentropy-sync'); ?></h4>
                <p><?php _e('Choose a unique name for your collection. This will be used to organize your WordPress posts in ZeroEntropy.', 'zeroentropy-sync'); ?></p>
            </div>
            
            <div class="info-item">
                <h4><?php _e('Auto Sync', 'zeroentropy-sync'); ?></h4>
                <p><?php _e('When enabled, new posts and updates will be automatically synced to ZeroEntropy. This ensures your search index stays up to date.', 'zeroentropy-sync'); ?></p>
            </div>
        </div>
    </div>
</div>
