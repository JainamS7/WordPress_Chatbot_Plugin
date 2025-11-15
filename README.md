# WordPress Chatbot Plugin

A modern, visually appealing chatbot plugin for WordPress news/blog websites with ZeroEntropy and OpenAI integration.

## Features

- Modern, responsive chatbot UI
- ZeroEntropy API integration for document retrieval
- OpenAI API integration for intelligent summarization
- WordPress post integration
- Markdown rendering support
- Fully customizable appearance

## Security Notice

**IMPORTANT**: API keys are NOT stored in the plugin code. They must be configured in your `wp-config.php` file.

## Installation

1. Upload the plugin to `/wp-content/plugins/wp-news-chatbot/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure API keys in `wp-config.php` (see Configuration section below)

## Configuration

### API Keys Setup

Add the following constants to your `wp-config.php` file (before the "stop editing" comment):

```php
// WP News Chatbot API Keys
define( 'WPNC_ZEROENTROPY_API_KEY', 'your_zeroentropy_api_key_here' );
define( 'WPNC_OPENAI_API_KEY', 'your_openai_api_key_here' );
```

**Security Best Practices:**

1. **Never commit `wp-config.php`** - This file is already in `.gitignore`
2. **Rotate API keys immediately** if they were ever exposed in version control
3. **Use environment variables** in production environments
4. **Restrict API key permissions** to only what's necessary
5. **Monitor API usage** regularly for unauthorized access

### Plugin Settings

Configure the plugin through WordPress Admin:
- Go to Settings → WP News Chatbot
- Set Zero Entropy collection name
- Configure default responses
- Customize display options

## How It Works

1. User asks a question in the chatbot
2. Plugin queries Zero Entropy API to find top 2 relevant documents
3. Plugin fetches WordPress post data using document IDs
4. Plugin sends post content to OpenAI for summarization
5. Plugin displays formatted response with article titles, summaries, and links

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Zero Entropy API account
- OpenAI API account

## Security

- All API keys are stored in `wp-config.php` (not in plugin files)
- `wp-config.php` is excluded from version control via `.gitignore`
- API keys are never logged or exposed in error messages
- Plugin uses WordPress REST API for secure communication

## Support

For issues and questions, please open an issue on GitHub.

## License

GPLv2 or later

