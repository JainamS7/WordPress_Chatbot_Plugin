# ZeroEntropy Sync WordPress Plugin

A WordPress plugin that automatically syncs your WordPress posts to ZeroEntropy for enhanced search capabilities.

## Features

- **Automatic Post Sync**: Automatically sync posts to ZeroEntropy when published or updated
- **Manual Sync**: Manually sync posts with configurable batch sizes
- **Connection Testing**: Test your ZeroEntropy API connection
- **Admin Interface**: Clean, user-friendly admin interface
- **Settings Management**: Easy configuration of API keys and settings
- **Error Handling**: Comprehensive error handling and user feedback

## Installation

### Method 1: Manual Installation

1. Download the plugin files
2. Upload the `zeroentropy-sync` folder to your WordPress `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to 'ZeroEntropy Sync' in your WordPress admin menu
5. Configure your API settings

### Method 2: WordPress Admin Upload

1. Go to your WordPress admin dashboard
2. Navigate to Plugins > Add New
3. Click "Upload Plugin"
4. Choose the plugin zip file
5. Click "Install Now" and then "Activate"

## Configuration

### Getting Your API Key

1. Visit [api.zeroentropy.dev](https://api.zeroentropy.dev)
2. Sign up for a free account
3. Get your API key from the dashboard

### Plugin Settings

1. Go to **ZeroEntropy Sync > Settings** in your WordPress admin
2. Enter your ZeroEntropy API key
3. Configure the collection name (default: `wordpress_posts`)
4. Enable auto-sync if desired
5. Save your settings

## Usage

### Manual Sync

1. Go to **ZeroEntropy Sync** in your WordPress admin
2. Enter the number of posts you want to sync (1-100)
3. Click "Test Connection" to verify your API key
4. Click "Sync Posts" to start the sync process

### Auto Sync

When auto-sync is enabled, posts will be automatically synced to ZeroEntropy when:
- A new post is published
- An existing post is updated
- A post status changes to "published"

## Plugin Structure

```
zeroentropy-sync/
├── zeroentropy-sync.php          # Main plugin file
├── templates/
│   ├── admin-page.php           # Main admin page template
│   └── settings-page.php        # Settings page template
├── assets/
│   ├── admin.js                 # Admin JavaScript
│   └── admin.css                # Admin styles
└── README.md                    # This file
```

## API Integration

The plugin integrates with the ZeroEntropy API to:

- Create collections for organizing posts
- Add documents with post content and metadata
- Handle authentication with API keys
- Provide error handling and status reporting

### Post Content Structure

Each synced post includes:

- **Title**: Post title
- **Author**: Post author name
- **Date**: Publication date
- **Categories**: Post categories (comma-separated)
- **Tags**: Post tags (comma-separated)
- **Link**: Post permalink
- **Content**: Full post content (HTML stripped)

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- ZeroEntropy API key
- cURL extension enabled

## Troubleshooting

### Common Issues

1. **API Key Not Working**
   - Verify your API key is correct
   - Check that your ZeroEntropy account is active
   - Test the connection using the "Test Connection" button

2. **Sync Failures**
   - Check your internet connection
   - Verify the ZeroEntropy API is accessible
   - Check the error messages in the admin interface

3. **Posts Not Syncing**
   - Ensure auto-sync is enabled in settings
   - Check that posts are published (not drafts)
   - Verify your API key has the correct permissions

### Debug Mode

To enable debug logging:

1. Add this to your `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. Check the debug log at `/wp-content/debug.log`

## Support

For support and questions:

- Check the [ZeroEntropy API Documentation](https://api.zeroentropy.dev/docs)
- Review the plugin settings and error messages
- Contact support through the ZeroEntropy website

## Changelog

### Version 1.0.0
- Initial release
- Manual and automatic post syncing
- Admin interface and settings
- Connection testing
- Error handling and user feedback

## License

This plugin is licensed under the GPL v2 or later.

## Contributing

Contributions are welcome! Please feel free to submit pull requests or open issues for bugs and feature requests.
