# Security Notice

## ⚠️ CRITICAL: API Keys Were Exposed

**Your API keys were exposed in the initial commit to this repository.**

### Immediate Actions Required

1. **Rotate your Zero Entropy API key immediately**
   - Log into your Zero Entropy account
   - Generate a new API key
   - Revoke the old key that was previously committed

2. **Rotate your OpenAI API key immediately**
   - Log into your OpenAI account (https://platform.openai.com/api-keys)
   - Generate a new API key
   - Revoke the old key that was exposed

3. **Update your local `wp-config.php`** with the new keys

### What Was Fixed

- ✅ Removed all hardcoded API keys from plugin code
- ✅ API keys now use WordPress constants from `wp-config.php`
- ✅ `wp-config.php` is excluded from version control (`.gitignore`)
- ✅ Added security documentation

### Current Security Status

- ✅ No API keys in current code
- ✅ API keys stored securely in `wp-config.php` (not in repo)
- ⚠️ Old keys still visible in git history (see below)

### Removing Keys from Git History (Optional)

If you want to completely remove the exposed keys from git history:

```bash
# WARNING: This rewrites git history and requires force push
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch app/public/wp-content/plugins/wp-news-chatbot/wp-news-chatbot.php" \
  --prune-empty --tag-name-filter cat -- --all

git push origin --force --all
```

**Note**: This is a destructive operation. Only do this if you understand the implications.

### Best Practices Going Forward

1. **Never commit API keys** - Always use environment variables or config files in `.gitignore`
2. **Use separate keys for development and production**
3. **Rotate keys regularly**
4. **Monitor API usage** for unauthorized access
5. **Use least privilege** - Only grant necessary permissions to API keys

### Current Configuration

API keys are now configured in `wp-config.php`:

```php
define( 'WPNC_ZEROENTROPY_API_KEY', 'your_key_here' );
define( 'WPNC_OPENAI_API_KEY', 'your_key_here' );
```

This file is excluded from version control, so your keys remain secure.

