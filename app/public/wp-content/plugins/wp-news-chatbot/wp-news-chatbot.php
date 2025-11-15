<?php
/*
Plugin Name: WP News Chatbot
Plugin URI:  https://example.com
Description: A visually appealing floating chatbot for news/blog websites. Sends questions to a backend REST endpoint; default reply is configurable. Easy to integrate with your own answering engine later.
Version:     1.0.0
Author:      Your Name
License:     GPLv2 or later
Text Domain: wp-news-chatbot
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPNC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPNC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPNC_OPTION_NAME', 'wpnc_options' );
define( 'WPNC_ZEROENTROPY_API_URL', 'https://api.zeroentropy.dev/v1' );

/**
 * Activation: set defaults if not present
 */
function wpnc_activate() {
    $defaults = array(
        'default_response' => 'Thanks — we received your question. We will answer shortly (default reply).',
        'use_remote'       => 0,
        'remote_url'       => '',
        'display_site'     => 1,
        'use_zeroentropy'  => 1,
        'zeroentropy_api_key' => '',
        'zeroentropy_collection' => 'wordpress_posts',
    );
    if ( false === get_option( WPNC_OPTION_NAME ) ) {
        add_option( WPNC_OPTION_NAME, $defaults );
    } else {
        // merge missing keys
        $opts = get_option( WPNC_OPTION_NAME, array() );
        $opts = wp_parse_args( $opts, $defaults );
        update_option( WPNC_OPTION_NAME, $opts );
    }
}
register_activation_hook( __FILE__, 'wpnc_activate' );

/**
 * Enqueue front-end scripts & styles
 */
function wpnc_enqueue_assets() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    // style
    wp_enqueue_style( 'wpnc-style', WPNC_PLUGIN_URL . 'assets/css/wpnc-style.css', array(), '1.0.0' );
    // script
    wp_enqueue_script( 'wpnc-script', WPNC_PLUGIN_URL . 'assets/js/wpnc-script.js', array(), '1.0.0', true );

    // localize data for JS
    $rest_url = esc_url_raw( rest_url( 'wpnewschatbot/v1/message' ) );
    $nonce = wp_create_nonce( 'wp_rest' );
    wp_localize_script( 'wpnc-script', 'wpncData', array(
        'rest_url' => $rest_url,
        'nonce'    => $nonce,
        'botAvatar'=> WPNC_PLUGIN_URL . 'assets/img/avatar-bot.png',
    ) );
}
add_action( 'wp_enqueue_scripts', 'wpnc_enqueue_assets' );

/**
 * Provide shortcode to output HTML for the widget
 */
function wpnc_render_widget() {
    ob_start();
    // Get site name
    $site_name = get_bloginfo('name');
    // Get site icon (favicon)
    $site_icon = get_site_icon_url(32);
    ?>
    <div id="wpnc-chatbot" class="wpnc-hidden" aria-hidden="true">
      <div id="wpnc-overlay" class="wpnc-overlay"></div>
      
      <button id="wpnc-toggle" aria-label="Open chat" class="wpnc-bubble" title="Chat with us">
        <span class="wpnc-bubble-icon">💬</span>
      </button>

      <div id="wpnc-window" class="wpnc-window" role="dialog" aria-modal="false" aria-label="Site chatbot">
        <div class="wpnc-header">
          <div class="wpnc-title-wrapper">
            <?php if ($site_icon): ?>
              <img src="<?php echo esc_url($site_icon); ?>" alt="<?php echo esc_attr($site_name); ?>" class="wpnc-site-icon" />
            <?php endif; ?>
            <span class="wpnc-title"><?php echo esc_html($site_name); ?> Assistant</span>
          </div>
          <button id="wpnc-close" aria-label="Close chat" class="wpnc-close">✕</button>
        </div>

        <div id="wpnc-messages" class="wpnc-messages" role="log" aria-live="polite"></div>

        <form id="wpnc-form" class="wpnc-form" action="#" onsubmit="return false;">
          <input id="wpnc-input" class="wpnc-input" type="text" autocomplete="off" placeholder="Ask me about this article or site..." aria-label="Your question" />
          <button id="wpnc-send" class="wpnc-send" type="submit" aria-label="Send">Send</button>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'wp_news_chatbot', 'wpnc_render_widget' );

/**
 * Optionally auto-insert to frontend footer if enabled in settings
 */
function wpnc_maybe_inject() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    if ( ! empty( $opts['display_site'] ) ) {
        echo wpnc_render_widget();
    }
}
add_action( 'wp_footer', 'wpnc_maybe_inject' );

/**
 * Register admin settings page
 */
function wpnc_admin_menu() {
    add_options_page( 'WP News Chatbot', 'WP News Chatbot', 'manage_options', 'wpnc-options', 'wpnc_options_page' );
}
add_action( 'admin_menu', 'wpnc_admin_menu' );

function wpnc_admin_init() {
    register_setting( 'wpnc_options_group', WPNC_OPTION_NAME, 'wpnc_sanitize_options' );
    add_settings_section( 'wpnc_main_section', 'Chatbot Settings', null, 'wpnc-options' );

    add_settings_field( 'wpnc_default_response', 'Default response', 'wpnc_field_default_response', 'wpnc-options', 'wpnc_main_section' );
    add_settings_field( 'wpnc_use_remote', 'Use remote answer engine', 'wpnc_field_use_remote', 'wpnc-options', 'wpnc_main_section' );
    add_settings_field( 'wpnc_remote_url', 'Remote engine URL', 'wpnc_field_remote_url', 'wpnc-options', 'wpnc_main_section' );
    add_settings_field( 'wpnc_display_site', 'Display site-wide', 'wpnc_field_display_site', 'wpnc-options', 'wpnc_main_section' );
    
    add_settings_field( 'wpnc_use_zeroentropy', 'Use Zero Entropy', 'wpnc_field_use_zeroentropy', 'wpnc-options', 'wpnc_main_section' );
    add_settings_field( 'wpnc_zeroentropy_api_key', 'Zero Entropy API Key', 'wpnc_field_zeroentropy_api_key', 'wpnc-options', 'wpnc_main_section' );
    add_settings_field( 'wpnc_zeroentropy_collection', 'Zero Entropy Collection', 'wpnc_field_zeroentropy_collection', 'wpnc-options', 'wpnc_main_section' );
}
add_action( 'admin_init', 'wpnc_admin_init' );

function wpnc_field_default_response() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    $val = isset( $opts['default_response'] ) ? esc_textarea( $opts['default_response'] ) : '';
    echo "<textarea name='".WPNC_OPTION_NAME."[default_response]' rows='4' cols='60'>{$val}</textarea>";
    echo "<p class='description'>Reply the chatbot will give by default (visible now until you plug in your engine).</p>";
}

function wpnc_field_use_remote() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    $checked = ! empty( $opts['use_remote'] ) ? 'checked' : '';
    echo "<input type='checkbox' name='".WPNC_OPTION_NAME."[use_remote]' value='1' {$checked} /> Enable forwarding to remote engine (if URL set).";
}

function wpnc_field_remote_url() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    $val = isset( $opts['remote_url'] ) ? esc_attr( $opts['remote_url'] ) : '';
    echo "<input type='url' name='".WPNC_OPTION_NAME."[remote_url]' value='{$val}' size='60' />";
    echo "<p class='description'>If enabled, plugin will forward POST to this URL and return its response (should return JSON `{ \"answer\": \"...\" }`).</p>";
}

function wpnc_field_display_site() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    $checked = isset( $opts['display_site'] ) && $opts['display_site'] ? 'checked' : '';
    echo "<input type='checkbox' name='".WPNC_OPTION_NAME."[display_site]' value='1' {$checked} /> Automatically add widget to every page (or use the shortcode where you want).";
}

function wpnc_field_use_zeroentropy() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    $checked = ! empty( $opts['use_zeroentropy'] ) ? 'checked' : '';
    echo "<input type='checkbox' name='".WPNC_OPTION_NAME."[use_zeroentropy]' value='1' {$checked} /> Enable Zero Entropy API for intelligent answers.";
}

function wpnc_field_zeroentropy_api_key() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    $val = isset( $opts['zeroentropy_api_key'] ) ? esc_attr( $opts['zeroentropy_api_key'] ) : '';
    echo "<input type='text' name='".WPNC_OPTION_NAME."[zeroentropy_api_key]' value='{$val}' size='60' />";
    echo "<p class='description'>Your Zero Entropy API key.</p>";
}

function wpnc_field_zeroentropy_collection() {
    $opts = get_option( WPNC_OPTION_NAME, array() );
    $val = isset( $opts['zeroentropy_collection'] ) ? esc_attr( $opts['zeroentropy_collection'] ) : 'wordpress_posts';
    echo "<input type='text' name='".WPNC_OPTION_NAME."[zeroentropy_collection]' value='{$val}' size='40' />";
    echo "<p class='description'>Collection name in Zero Entropy (default: wordpress_posts).</p>";
}

function wpnc_sanitize_options( $input ) {
    $out = array();
    $out['default_response'] = isset( $input['default_response'] ) ? sanitize_textarea_field( $input['default_response'] ) : '';
    $out['use_remote'] = ! empty( $input['use_remote'] ) ? 1 : 0;
    $out['remote_url'] = ! empty( $input['remote_url'] ) ? esc_url_raw( $input['remote_url'] ) : '';
    $out['display_site'] = ! empty( $input['display_site'] ) ? 1 : 0;
    $out['use_zeroentropy'] = ! empty( $input['use_zeroentropy'] ) ? 1 : 0;
    $out['zeroentropy_api_key'] = isset( $input['zeroentropy_api_key'] ) ? sanitize_text_field( $input['zeroentropy_api_key'] ) : '';
    $out['zeroentropy_collection'] = isset( $input['zeroentropy_collection'] ) ? sanitize_text_field( $input['zeroentropy_collection'] ) : 'wordpress_posts';
    return $out;
}

function wpnc_options_page() {
    ?>
    <div class="wrap">
      <h1>WP News Chatbot</h1>
      <form method="post" action="options.php">
        <?php
          settings_fields( 'wpnc_options_group' );
          do_settings_sections( 'wpnc-options' );
          submit_button();
        ?>
      </form>
      <h2>Usage</h2>
      <p>Use shortcode <code>[wp_news_chatbot]</code> in posts, pages, or templates. If "Display site-wide" is checked the widget is auto-inserted into the footer of your theme.</p>
      <h2>How to integrate your engine later</h2>
      <ol>
        <li>Set "Use remote answer engine" and provide your engine URL. The plugin forwards the question JSON to your URL: <code>{ "question": "..." }</code> and expects back JSON: <code>{ "answer": "..." }</code>.</li>
        <li>Or modify the REST callback in <code>wp-news-chatbot.php</code> to call your own internal library or model.</li>
      </ol>
    </div>
    <?php
}

/**
 * REST endpoint to receive message and return an answer
 */
add_action( 'rest_api_init', function () {
    register_rest_route( 'wpnewschatbot/v1', '/message', array(
        'methods'  => 'POST',
        'callback' => 'wpnc_rest_handle_message',
        'permission_callback' => '__return_true',
    ) );
} );

function wpnc_rest_handle_message( WP_REST_Request $request ) {
    $params = $request->get_json_params();
    $question = isset( $params['question'] ) ? sanitize_text_field( $params['question'] ) : '';

    $opts = get_option( WPNC_OPTION_NAME, array() );

    // If configured to use Zero Entropy API, attempt to query
    // Try Zero Entropy if API key and collection are set (ignore checkbox for now)
    // Get API key from options or WordPress constant (defined in wp-config.php)
    $api_key = ! empty( $opts['zeroentropy_api_key'] ) ? $opts['zeroentropy_api_key'] : ( defined( 'WPNC_ZEROENTROPY_API_KEY' ) ? WPNC_ZEROENTROPY_API_KEY : '' );
    $collection = ! empty( $opts['zeroentropy_collection'] ) ? $opts['zeroentropy_collection'] : 'wordpress_posts';
    
    // Debug (API key intentionally not logged for security)
    error_log( 'WPNC Question: ' . $question );
    error_log( 'WPNC Collection: ' . $collection );
    
    if ( ! empty( $api_key ) && ! empty( $collection ) ) {
        $api_url = WPNC_ZEROENTROPY_API_URL . '/queries/top-documents';
        
        $request_body = array(
            'collection_name' => $collection,
            'query' => $question,
            'k' => 2,
            'include_metadata' => false
        );
        
        error_log( 'WPNC Calling Zero Entropy API: ' . $api_url );
        error_log( 'WPNC Request Body: ' . wp_json_encode( $request_body ) );
        
        $response = wp_remote_post( $api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => wp_json_encode( $request_body ),
            'timeout' => 30,
        ) );
        
        if ( is_wp_error( $response ) ) {
            error_log( 'WPNC API Error: ' . $response->get_error_message() );
        } else {
            $status_code = wp_remote_retrieve_response_code( $response );
            error_log( 'WPNC API Status Code: ' . $status_code );
        }
        
        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
            $body = wp_remote_retrieve_body( $response );
            error_log( 'WPNC API Response: ' . $body );
            $data = json_decode( $body, true );
            
            if ( is_array( $data ) && isset( $data['results'] ) && is_array( $data['results'] ) && ! empty( $data['results'] ) ) {
                error_log( 'WPNC: Processing results, count: ' . count( $data['results'] ) );
                
                // Get WordPress post data for each document
                $posts_data = array();
                
                foreach ( $data['results'] as $index => $result ) {
                    $doc_path = isset( $result['path'] ) ? $result['path'] : 'Unknown';
                    
                    // Extract post ID from path (e.g., "post_784" -> 784)
                    if ( preg_match( '/post_(\d+)/', $doc_path, $matches ) ) {
                        $post_id = intval( $matches[1] );
                        
                        // Get WordPress post
                        $post = get_post( $post_id );
                        if ( $post ) {
                            $posts_data[] = array(
                                'id' => $post_id,
                                'title' => $post->post_title,
                                'link' => get_permalink( $post_id ),
                                'content' => $post->post_content
                            );
                        }
                    }
                }
                
                error_log( 'WPNC: Total posts found: ' . count( $posts_data ) );
                
                // Send to OpenAI for summarization
                if ( ! empty( $posts_data ) ) {
                    error_log( 'WPNC: Calling OpenAI with ' . count( $posts_data ) . ' posts' );
                    $openai_response = call_openai_for_summary( $question, $posts_data );
                    if ( $openai_response ) {
                        error_log( 'WPNC: OpenAI response received' );
                        return rest_ensure_response( array( 'answer' => $openai_response ) );
                    } else {
                        error_log( 'WPNC: OpenAI response is empty' );
                    }
                } else {
                    error_log( 'WPNC: No posts data to send to OpenAI' );
                }
                
                // Fallback if OpenAI fails
                $answer = "Found " . count( $data['results'] ) . " relevant document(s):\n\n";
                foreach ( $data['results'] as $index => $result ) {
                    $answer .= "Document " . ($index + 1) . ": " . esc_html( $result['path'] ) . "\n\n";
                }
                return rest_ensure_response( array( 'answer' => wp_kses_post( $answer ) ) );
            } else {
                error_log( 'WPNC: No results in response data' );
            }
        }
        // If Zero Entropy fails, fall through to default answer
    }

    // If configured to use remote engine, attempt to forward
    if ( ! empty( $opts['use_remote'] ) && ! empty( $opts['remote_url'] ) ) {
        $remote = wp_remote_post( $opts['remote_url'], array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => wp_json_encode( array( 'question' => $question ) ),
            'timeout' => 15,
        ) );
        if ( ! is_wp_error( $remote ) && 200 === wp_remote_retrieve_response_code( $remote ) ) {
            $body = wp_remote_retrieve_body( $remote );
            $data = json_decode( $body, true );
            if ( is_array( $data ) && isset( $data['answer'] ) ) {
                return rest_ensure_response( array( 'answer' => sanitize_text_field( $data['answer'] ) ) );
            }
            // fallback to default below if remote doesn't provide answer key
        }
        // if remote fails, continue to default answer
    }

    // Default reply (safe fallback)
    $default = isset( $opts['default_response'] ) ? $opts['default_response'] : 'Thanks — we received your question.';
    return rest_ensure_response( array( 'answer' => $default ) );
}

/**
 * Call OpenAI API for document summarization
 */
function call_openai_for_summary( $question, $posts_data ) {
    // Get OpenAI API key from WordPress constant (defined in wp-config.php)
    $openai_api_key = defined( 'WPNC_OPENAI_API_KEY' ) ? WPNC_OPENAI_API_KEY : '';
    
    error_log( 'WPNC: call_openai_for_summary called' );
    
    if ( empty( $openai_api_key ) ) {
        error_log( 'WPNC: OpenAI API key is empty. Please define WPNC_OPENAI_API_KEY in wp-config.php' );
        return false;
    }
    
    // Build context from posts
    $context = "";
    foreach ( $posts_data as $index => $post ) {
        $context .= "## Article " . ($index + 1) . ": " . $post['title'] . "\n\n";
        $context .= wp_strip_all_tags( wp_trim_words( $post['content'], 200 ) ) . "\n\n";
    }
    
    error_log( 'WPNC: Context built, length: ' . strlen( $context ) );
    
    // Create simple prompt asking only for summaries
    $prompt = "Based on the following articles, provide a brief summary (<30 words) for each article.\n\n";
    $prompt .= "Articles:\n\n" . $context . "\n\n";
    $prompt .= "Format your response in markdown as:\n";
    $prompt .= "## Article Title\nSummary in less than 30 words\n\n";
    
    $openai_request = array(
        'model' => 'gpt-4o-mini',
        'messages' => array(
            array(
                'role' => 'system',
                'content' => 'You are a helpful assistant that provides brief summaries. Always respond in markdown format with clear headings.'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        ),
        'max_tokens' => 300,
        'temperature' => 0.7
    );
    
    error_log( 'WPNC: Calling OpenAI API' );
    
    $openai_response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $openai_api_key
        ),
        'body' => wp_json_encode( $openai_request ),
        'timeout' => 60
    ) );
    
    if ( is_wp_error( $openai_response ) ) {
        error_log( 'WPNC: OpenAI API Error: ' . $openai_response->get_error_message() );
        return false;
    }
    
    $status_code = wp_remote_retrieve_response_code( $openai_response );
    error_log( 'WPNC: OpenAI API Status: ' . $status_code );
    
    if ( $status_code === 200 ) {
        $body = wp_remote_retrieve_body( $openai_response );
        error_log( 'WPNC: OpenAI Response length: ' . strlen( $body ) );
        $data = json_decode( $body, true );
        
        if ( isset( $data['choices'][0]['message']['content'] ) ) {
            $summaries = $data['choices'][0]['message']['content'];
            
            // Add links to each article after summary
            $answer = "";
            $article_index = 0;
            
            // Split summaries by ## headings
            $sections = preg_split( '/##\s*(.+)/', $summaries, -1, PREG_SPLIT_DELIM_CAPTURE );
            
            // First element is empty, start from index 1
            for ( $i = 1; $i < count( $sections ); $i += 2 ) {
                if ( $i + 1 < count( $sections ) ) {
                    $title = trim( $sections[$i] );
                    $summary = trim( $sections[$i + 1] );
                    
                    // Add formatted output
                    $answer .= "## " . $title . "\n\n";
                    $answer .= $summary . "\n\n";
                    
                    // Add link if available
                    if ( isset( $posts_data[$article_index] ) ) {
                        $answer .= "[Read more](" . $posts_data[$article_index]['link'] . ")\n\n";
                    }
                    
                    $article_index++;
                }
            }
            
            // Convert markdown to HTML
            $answer = markdown_to_html( $answer );
            
            error_log( 'WPNC: Returning OpenAI answer' );
            return $answer;
        } else {
            error_log( 'WPNC: No content in OpenAI response' );
            error_log( 'WPNC: Response data: ' . print_r( $data, true ) );
        }
    } else {
        $body = wp_remote_retrieve_body( $openai_response );
        error_log( 'WPNC: OpenAI Error Response: ' . substr( $body, 0, 500 ) );
    }
    
    return false;
}

/**
 * Simple markdown to HTML converter
 */
function markdown_to_html( $markdown ) {
    // Headers
    $markdown = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $markdown);
    $markdown = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $markdown);
    $markdown = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $markdown);
    
    // Bold
    $markdown = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $markdown);
    
    // Italic
    $markdown = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $markdown);
    
    // Code blocks
    $markdown = preg_replace('/`(.*?)`/', '<code>$1</code>', $markdown);
    
    // Links
    $markdown = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $markdown);
    
    // Bullet points
    $markdown = preg_replace('/^\* (.*$)/m', '<li>$1</li>', $markdown);
    $markdown = preg_replace('/^\- (.*$)/m', '<li>$1</li>', $markdown);
    
    // Wrap consecutive <li> tags in <ul>
    $markdown = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $markdown);
    
    // Numbered lists
    $markdown = preg_replace('/^(\d+)\. (.*$)/m', '<li>$2</li>', $markdown);
    
    // Horizontal rule
    $markdown = preg_replace('/^---$/m', '<hr>', $markdown);
    
    // Paragraphs (double newline)
    $markdown = preg_replace('/\n\n/', '</p><p>', $markdown);
    $markdown = '<p>' . $markdown . '</p>';
    
    // Clean up empty paragraphs
    $markdown = preg_replace('/<p>\s*<\/p>/', '', $markdown);
    $markdown = preg_replace('/<p>(<h[1-6]>)/', '$1', $markdown);
    $markdown = preg_replace('/<\/p>(<h[1-6]>)/', '$1', $markdown);
    $markdown = preg_replace('/<p>(<ul>)/', '$1', $markdown);
    $markdown = preg_replace('/<\/ul><\/p>/', '</ul>', $markdown);
    
    return $markdown;
}

/**
 * Clean up on uninstall (optional)
 */
register_uninstall_hook( __FILE__, 'wpnc_uninstall' );
function wpnc_uninstall() {
    delete_option( WPNC_OPTION_NAME );
}
