/**
 * ZeroEntropy Sync Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Test connection button
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var originalText = $button.text();
        
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: zeroentropySync.ajax_url,
            type: 'POST',
            data: {
                action: 'zeroentropy_test_connection',
                nonce: zeroentropySync.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message || zeroentropySync.strings.test_error);
                }
            },
            error: function() {
                showNotice('error', zeroentropySync.strings.test_error);
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Sync posts form
    $('#zeroentropy-sync-form').on('submit', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $syncButton = $('#sync-posts');
        var $progress = $('#sync-progress');
        var $results = $('#sync-results');
        var numPosts = $('#num_posts').val();
        
        if (!confirm(zeroentropySync.strings.confirm_sync)) {
            return;
        }
        
        $syncButton.prop('disabled', true).text('Syncing...');
        $progress.show();
        $results.hide();
        
        $.ajax({
            url: zeroentropySync.ajax_url,
            type: 'POST',
            data: {
                action: 'zeroentropy_sync_posts',
                nonce: zeroentropySync.nonce,
                num_posts: numPosts
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    showSyncResults(response.data);
                } else {
                    showNotice('error', response.data.message || zeroentropySync.strings.sync_error);
                }
            },
            error: function() {
                showNotice('error', zeroentropySync.strings.sync_error);
            },
            complete: function() {
                $syncButton.prop('disabled', false).text('Sync Posts');
                $progress.hide();
            }
        });
    });
    
    // Show sync results
    function showSyncResults(data) {
        var $results = $('#sync-results');
        var html = '<div class="zeroentropy-results-content">';
        
        html += '<h4>Sync Results</h4>';
        html += '<p><strong>Synced:</strong> ' + data.synced + ' / ' + data.total + ' posts</p>';
        
        if (data.errors && data.errors.length > 0) {
            html += '<div class="zeroentropy-errors">';
            html += '<h5>Errors:</h5>';
            html += '<ul>';
            data.errors.forEach(function(error) {
                html += '<li>' + error + '</li>';
            });
            html += '</ul>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $results.html(html).show();
    }
    
    // Show admin notices
    function showNotice(type, message) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    // Dismiss notices
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
    
    // Form validation
    $('#zeroentropy-sync-form').on('submit', function(e) {
        var numPosts = parseInt($('#num_posts').val());
        
        if (isNaN(numPosts) || numPosts < 1 || numPosts > 100) {
            e.preventDefault();
            showNotice('error', 'Please enter a valid number of posts (1-100)');
            return false;
        }
    });
    
    // Real-time form validation
    $('#num_posts').on('input', function() {
        var $input = $(this);
        var value = parseInt($input.val());
        
        if (isNaN(value) || value < 1 || value > 100) {
            $input.addClass('error');
        } else {
            $input.removeClass('error');
        }
    });
});
