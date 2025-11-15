/**
 * WordPress Post Importer Admin JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    var selectedPosts = [];
    var isProcessing = false;
    
    // Test connection button
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        
        if (isProcessing) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        var sourceUrl = $('#source_url').val();
        
        if (!sourceUrl) {
            showNotice('error', 'Please enter a source URL');
            return;
        }
        
        isProcessing = true;
        $button.prop('disabled', true).text('Testing...');
        
        $.ajax({
            url: wpPostImporter.ajax_url,
            type: 'POST',
            timeout: 30000,
            data: {
                action: 'wp_post_importer_test_connection',
                nonce: wpPostImporter.nonce,
                source_url: sourceUrl
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    showConnectionStatus('success', response.data.message);
                } else {
                    showNotice('error', response.data.message || wpPostImporter.strings.test_error);
                    showConnectionStatus('error', response.data.message || wpPostImporter.strings.test_error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Request failed';
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                showNotice('error', errorMessage);
                showConnectionStatus('error', errorMessage);
            },
            complete: function() {
                isProcessing = false;
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Get posts button
    $('#get-posts').on('click', function(e) {
        e.preventDefault();
        
        if (isProcessing) {
            return;
        }
        
        var $button = $(this);
        var originalText = $button.text();
        var sourceUrl = $('#source_url').val();
        var numPosts = $('#num_posts').val();
        
        if (!sourceUrl) {
            showNotice('error', 'Please enter a source URL');
            return;
        }
        
        isProcessing = true;
        $button.prop('disabled', true).text('Loading...');
        $('#posts-list').hide();
        $('#import-posts').hide();
        $('#connection-status').hide();
        
        $.ajax({
            url: wpPostImporter.ajax_url,
            type: 'POST',
            timeout: 60000,
            data: {
                action: 'wp_post_importer_get_posts',
                nonce: wpPostImporter.nonce,
                source_url: sourceUrl,
                num_posts: numPosts
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.posts && response.data.posts.length > 0) {
                        showPostsList(response.data.posts);
                        $('#import-posts').show();
                    } else {
                        showNotice('error', 'No posts found on the source website.');
                    }
                } else {
                    showNotice('error', response.data.message || wpPostImporter.strings.import_error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Request failed';
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. The source website may be slow or unreachable.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                showNotice('error', errorMessage);
            },
            complete: function() {
                isProcessing = false;
                $button.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Import posts form
    $('#wp-post-importer-form').on('submit', function(e) {
        e.preventDefault();
        
        if (isProcessing) {
            return;
        }
        
        if (selectedPosts.length === 0) {
            showNotice('error', 'Please select at least one post to import');
            return;
        }
        
        if (!confirm(wpPostImporter.strings.confirm_import)) {
            return;
        }
        
        var $form = $(this);
        var $importButton = $('#import-posts');
        var $progress = $('#import-progress');
        var $results = $('#import-results');
        var sourceUrl = $('#source_url').val();
        
        isProcessing = true;
        $importButton.prop('disabled', true).text('Importing...');
        $progress.show();
        $results.hide();
        
        $.ajax({
            url: wpPostImporter.ajax_url,
            type: 'POST',
            timeout: 120000,
            data: {
                action: 'wp_post_importer_import_posts',
                nonce: wpPostImporter.nonce,
                source_url: sourceUrl,
                num_posts: $('#num_posts').val(),
                selected_posts: selectedPosts
            },
            success: function(response) {
                if (response.success) {
                    showNotice('success', response.data.message);
                    showImportResults(response.data);
                    selectedPosts = [];
                    updatePostCheckboxes();
                } else {
                    showNotice('error', response.data.message || wpPostImporter.strings.import_error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = 'Request failed';
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                    errorMessage = xhr.responseJSON.data.message;
                }
                showNotice('error', errorMessage);
            },
            complete: function() {
                isProcessing = false;
                $importButton.prop('disabled', false).text('Import Selected Posts');
                $progress.hide();
            }
        });
    });
    
    // Show posts list
    function showPostsList(posts) {
        var $container = $('#posts-list');
        var html = '<h3>Available Posts</h3>';
        html += '<div class="posts-grid">';
        
        posts.forEach(function(post, index) {
            html += '<div class="post-item">';
            html += '<label class="post-checkbox">';
            html += '<input type="checkbox" class="post-select" value="' + post.id + '" />';
            html += '<span class="checkmark"></span>';
            html += '</label>';
            html += '<div class="post-content">';
            html += '<h4>' + escapeHtml(post.title) + '</h4>';
            html += '<p class="post-meta">';
            html += '<strong>Author:</strong> ' + escapeHtml(post.author) + ' | ';
            html += '<strong>Date:</strong> ' + formatDate(post.date) + ' | ';
            html += '<strong>Categories:</strong> ' + escapeHtml(post.categories.join(', ')) + ' | ';
            html += '<strong>Tags:</strong> ' + escapeHtml(post.tags.join(', '));
            html += '</p>';
            if (post.excerpt) {
                html += '<p class="post-excerpt">' + escapeHtml(post.excerpt.substring(0, 200)) + (post.excerpt.length > 200 ? '...' : '') + '</p>';
            }
            html += '<p class="post-actions">';
            html += '<a href="' + escapeHtml(post.link) + '" target="_blank" class="button button-small">View Original</a>';
            html += '</p>';
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        html += '<div class="posts-actions">';
        html += '<button type="button" id="select-all" class="button">Select All</button>';
        html += '<button type="button" id="select-none" class="button">Select None</button>';
        html += '</div>';
        
        $container.html(html).show();
        
        // Bind checkbox events
        $('.post-select').on('change', function() {
            updateSelectedPosts();
        });
        
        $('#select-all').on('click', function() {
            $('.post-select').prop('checked', true);
            updateSelectedPosts();
        });
        
        $('#select-none').on('click', function() {
            $('.post-select').prop('checked', false);
            updateSelectedPosts();
        });
    }
    
    // Update selected posts
    function updateSelectedPosts() {
        selectedPosts = [];
        $('.post-select:checked').each(function() {
            selectedPosts.push(parseInt($(this).val()));
        });
        
        var count = selectedPosts.length;
        if (count > 0) {
            $('#import-posts').text('Import ' + count + ' Selected Posts');
        } else {
            $('#import-posts').text('Import Selected Posts');
        }
    }
    
    // Update post checkboxes
    function updatePostCheckboxes() {
        $('.post-select').prop('checked', false);
        updateSelectedPosts();
    }
    
    // Show connection status
    function showConnectionStatus(type, message) {
        var $status = $('#connection-status');
        var className = type === 'success' ? 'status-success' : 'status-error';
        $status.removeClass('status-success status-error').addClass(className).text(message).show();
    }
    
    // Show import results
    function showImportResults(data) {
        var $results = $('#import-results');
        var html = '<div class="import-results-content">';
        
        html += '<h4>Import Results</h4>';
        html += '<p><strong>Imported:</strong> ' + data.imported + ' / ' + data.total + ' posts</p>';
        
        if (data.errors && data.errors.length > 0) {
            html += '<div class="import-errors">';
            html += '<h5>Errors:</h5>';
            html += '<ul>';
            data.errors.forEach(function(error) {
                html += '<li>' + escapeHtml(error) + '</li>';
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
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + escapeHtml(message) + '</p></div>');
        
        $('.wrap h1').after(notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            notice.fadeOut();
        }, 5000);
    }
    
    // Utility functions
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    function formatDate(dateString) {
        var date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    // Dismiss notices
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
    
    // Form validation
    $('#wp-post-importer-form').on('submit', function(e) {
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