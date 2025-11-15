# ZeroEntropy Sync WordPress Plugin - Changelog

## Version 1.0.0 - Fixed API Integration

### 🔧 Bug Fixes
- **Fixed HTTP 405 Error**: Updated all API calls to use correct HTTP methods as per OpenAPI specification
- **Status Endpoint**: Changed from GET to POST for `/status/get-status` endpoint
- **Collection Management**: Ensured proper POST requests for all collection operations
- **Document Operations**: Verified correct HTTP methods for document add/update/delete operations

### ✨ New Features
- **Enhanced Connection Testing**: Now tests both general API connection and collection-specific status
- **Document Deletion**: Added support for deleting documents when WordPress posts are deleted
- **Scheduled Sync**: Implemented background sync for individual posts
- **Last Sync Tracking**: Added timestamp tracking for last successful sync
- **Collection List Support**: Added method to retrieve and verify collection existence

### 🎯 API Integration Improvements
- **Correct HTTP Methods**: All endpoints now use the proper HTTP methods as defined in the OpenAPI spec
- **Proper Request Bodies**: All POST requests include appropriate JSON bodies
- **Enhanced Error Handling**: Better error messages and status code handling
- **Collection Status**: Added support for checking collection-specific document counts

### 📊 Admin Interface Enhancements
- **Extended Status Display**: Added API Base URL and Last Sync timestamp to admin dashboard
- **Better Error Reporting**: Improved error messages and user feedback
- **Connection Details**: More detailed connection test results showing document counts

### 🔄 Sync Process Improvements
- **Background Processing**: Individual post syncs now run in background to avoid blocking
- **Auto-sync Optimization**: Better handling of post status changes and revisions
- **Error Logging**: Comprehensive error logging for debugging sync issues
- **Status Tracking**: Last sync time is updated after successful operations

### 🛠️ Technical Improvements
- **OpenAPI Compliance**: Full compliance with ZeroEntropy API specification
- **WordPress Standards**: Follows WordPress coding standards and best practices
- **Security**: Proper nonce verification and capability checks
- **Performance**: Optimized API calls and reduced unnecessary requests

### 📋 Installation & Usage
1. Upload plugin to `/wp-content/plugins/`
2. Activate in WordPress admin
3. Configure API key in ZeroEntropy Sync settings
4. Test connection to verify setup
5. Sync posts manually or enable auto-sync

### 🧪 Testing
- Added comprehensive test script (`test-plugin.php`)
- Mock WordPress functions for standalone testing
- Connection and sync functionality verification
- Error handling and edge case testing

### 📚 Documentation
- Complete README with installation instructions
- API integration documentation
- Troubleshooting guide
- Configuration examples

---

## Previous Versions

### Version 0.9.0 - Initial Release
- Basic plugin structure
- WordPress integration
- Admin interface
- Manual and automatic sync
- Initial API integration (had HTTP method issues)

---

## Known Issues Fixed
- ✅ HTTP 405 Method Not Allowed errors
- ✅ Incorrect API endpoint usage
- ✅ Missing request bodies for POST requests
- ✅ Inadequate error handling
- ✅ No document deletion support

## Future Enhancements
- [ ] Bulk sync with progress indicators
- [ ] Sync status dashboard
- [ ] Advanced filtering options
- [ ] Webhook support for real-time updates
- [ ] Multi-site support
- [ ] Advanced search integration
