<?php
include_once BCA_JARVIS_PATH . '/includes/openai/config.php';
include_once BCA_JARVIS_PATH . '/includes/openai/openai.php';

function bca_jarvis_rest_api_init()
{
    register_rest_route('openai/v1', '/create_completion', array(
        'methods' => 'POST',
        'callback' => 'bca_jarvis_write',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('jarvis/v1', '/crawler', array(
        'methods' => 'GET',
        'callback' => 'bca_jarvis_crawler',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('jarvis/v1', '/feed', array(
        'methods' => 'GET',
        'callback' => 'bca_jarvis_feed',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('jarvis/v1', '/write', array(
        'methods' => 'POST',
        'callback' => 'bca_jarvis_write',
        'permission_callback' => '__return_true'
    ));
    register_rest_route('jarvis/v1', '/seo', array(
        'methods' => 'POST',
        'callback' => 'bca_jarvis_seo',
        'permission_callback' => '__return_true'
    ));
}
add_action('rest_api_init', 'bca_jarvis_rest_api_init', 10);

function bca_jarvis_seo($request)
{
    $response = array();
    $attribute = $request->get_param('attribute');
    $value = $request->get_param('value');
    $value2 = $request->get_param('value2');
    $value = $value ? $value : '';
    $value2 = $value2 ? $value2 : false;

    if (trim($value) === '') {
        $response['error'] = array(
            'message' => "No value was provided",
        );
        return $response;
    }


    $prompt = openai_rewrite_seo_prompt($attribute, $value, $value2);
    $max_tokens = _prompt_max_token_length($prompt);
    
    // $response['keywords'] = _get_mock_content('keywords.txt');
    // return $response;
    $openai = openai_init();

    if (!$openai) {
        $response['error'] = array(
            'message' => "OpenAI API key not configured, please follow instructions in README.md",
        );
        return $response;
    }
    
    try {
        $completion = $openai->create_completion(array(
            'model' => "text-davinci-003",
            'prompt' => $prompt,
            'temperature' => 1,
            'max_tokens' => $max_tokens,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.0
        ));
        if (isset( $completion['choices'][0]['text'] )) {
            $response['results'] = _transform_string_to_list($completion['choices'][0]['text']);
        }
    } catch (Exception $error) {
        // Consider adjusting the error handling logic for your use case
        if ($error->response) {
            error_log($error->response->status . ' ' . $error->response->data);
            $response['error'] = $error->response->data;
        } else {
            error_log('Error with OpenAI API request: ' . $error->message);
            $response['error'] = array(
                'message' => 'An error occurred during your request.',
            );
        }
    }

    return $response;
}
function bca_jarvis_write($request)
{
    $response = array();
    $content = $request->get_param('content');
    $content = $content ? $content : '';
    
    if (trim($content) === '') {
        $response['error'] = array(
            'message' => "No content/title was provided",
        );
        return $response;
    }


    $prompt = openai_generate_article_prompt($content);

    $body = [
        'prompt'  => $prompt
    ];
    
    $token_response = wp_remote_post('https://web-crawler.herokuapp.com/tokens', array(
        'body' => $body,
    ));

    if (is_wp_error($token_response)) {
        return $token_response->get_error_message();
    }
    $token_response = wp_remote_retrieve_body($token_response);
    $token_response = json_decode($token_response, true);
    
    $max_tokens = 2688;

    if (is_array($token_response)) {
        $prompt_content_token_count = count( $token_response );
        $max_tokens = 4001 - $prompt_content_token_count;
    }

    $options = get_option( 'jarvis_options' );
    $api_key = _get_object_property( $options, 'api_key', '' );
    $configuration = new Configuration(array(
        'apiKey' => sanitize_text_field( $api_key ),
    ));
    $openai = new OpenAI($configuration);

    if (!$configuration->apiKey) {
        $response['error'] = array(
            'message' => "OpenAI API key not configured, please follow instructions in README.md",
        );
        return $response;
    }

    try {
        $completion = $openai->create_completion(array(
            'model' => "text-davinci-003",
            'prompt' => $prompt,
            'temperature' => 0.7,
            'max_tokens' => $max_tokens,
            'top_p' => 1,
            'frequency_penalty' => 0.5,
            'presence_penalty' => 0.0
        ));
        if (isset( $completion['choices'][0]['text'] )) {
            $response['result'] = _prepare_content($completion['choices'][0]['text']);
            http_response_code(200);
        }
    } catch (Exception $error) {
        // Consider adjusting the error handling logic for your use case
        if ($error->response) {
            error_log($error->response->status . ' ' . $error->response->data);
            $response['error'] = $error->response->data;
        } else {
            error_log('Error with OpenAI API request: ' . $error->message);
            $response['error'] = array(
                'message' => 'An error occurred during your request.',
            );
        }
    }

    return $response;
}
function openai_rewrite_seo_prompt($attribute, $value, $value2 = false)
{
    $value = ucfirst(strtolower($value));
    if ($attribute=='keywords') {
        $prompt = openai_rewrite_keywords_prompt($value);
    } elseif ($attribute=='title'&&$value2) {
        $prompt = openai_rewrite_title_prompt($value, $value2);
    } elseif ($attribute=='description'&&$value2) {
        $prompt = openai_rewrite_description_prompt($value, $value2);
    } elseif ($attribute=='url'&&$value2) {
        $prompt = openai_rewrite_url_prompt($value, $value2);
    } else {
        $prompt = false;
    }
    return $prompt;
}
function openai_rewrite_keywords_prompt($title)
{
    $title = ucfirst(strtolower($title));
    $prompt = "Based on this article title create a list 5 focus keywords for SEO. Do not number the list.\n\Example:News\nCrypto News\nBitcoin News\nBusiness\n\nTitle:$title";
    return $prompt;
}
function openai_rewrite_title_prompt($title, $keyword)
{
    $title = ucfirst(strtolower($title));
    $keyword = ucfirst(strtolower($keyword));
    $prompt = "List 5 different versions of this title. It must include 4 power words and the SEO keyword `$keyword`. Do not number the list.\n\nExample list:News title one\nNews title two\nNews title three\n\nTitle:$title";
    return $prompt;
}
function openai_rewrite_description_prompt($title, $keyword)
{
    $title = ucfirst(strtolower($title));
    $keyword = ucfirst(strtolower($keyword));
    $prompt = "Based on this article title create 5 meta description for google search results. Include the SEO keyword `$keyword`. The description should be a maximum of 160 characters long. Do not number the list.\n\ntitle:$title";
    return $prompt;
}
function openai_rewrite_url_prompt($url, $keyword)
{
    $keyword = ucfirst(strtolower($keyword));
    $prompt = "Rewrite this slug. Create 5 versions. It must be lowercase. It must include the SEO keyword `$keyword`. Do not number the list. It should be shorter than 75 characters.\n\nExample slug:super-bowl-lvi-ads-expect-less-crypto-this-year\n\nslug:$url";
    return $prompt;
}
function openai_generate_article_prompt($content)
{
    $content = ucfirst(strtolower($content));
    $prompt = "Rewrite this article in the tone of a New York Times journalist. The article should be engaging. This article should contain headings wrapped in `h2` tags, create them. The article should be a minimum of 650 words long.\n\n###$content###\n\n";
    return $prompt;
}
function openai_summarize_prompt($content)
{
    $content = ucfirst(strtolower($content));
    $prompt = "Please summarize the following text:$content";
    return $prompt;
}

function openai_init()
{
    $options = get_option( 'jarvis_options' );
    $api_key = _get_object_property( $options, 'api_key', '' );
    $configuration = new Configuration(array(
        'apiKey' => sanitize_text_field( $api_key ),
    ));
    $openai = new OpenAI($configuration);

    if (!$configuration->apiKey) {
        return false;
    }
    
    return $openai;
}

function bca_jarvis_crawler($request)
{
    $url = $request->get_param('url');
    if (!$url) {
        $response['error'] = array(
            'message' => "Invalid url was given...",
        );
        http_response_code(500);
        echo json_encode($response);
        return;
    }

    $response = wp_remote_get('https://web-crawler.herokuapp.com/crawl?url=' . $url);

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }
    $response = wp_remote_retrieve_body($response);
    $response = json_decode($response, true);

    if (!isset( $response['article']['html'] ) || empty($response['article']['html']) ) {
        $response['error'] = array(
            'message' => "Content is either missing or malformed.",
            'article' => $response
        );
        return $response;
    }
    
    $html = $response['article']['html'];
    $allowed_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'strong', 'em', 'b', 'cite', 'span', 'blockquote');
    $stripped_html = _strip_tags_except($html, $allowed_tags);
    $stripped_html = bca_jarvis_html_remove_class_attr($stripped_html);
    $response['article']['html'] = $stripped_html;
    $results['result'] = $response['article'];
    return $results;
}
function bca_jarvis_feed( $request )
{
    $url = $request->get_param('url');
    if (!$url) {
        $response['error'] = array(
            'message' => "Invalid url was given...",
        );
        http_response_code(500);
        echo json_encode($response);
        return;
    }
    
    $response = wp_remote_get('https://web-crawler.herokuapp.com/feed?url=' . $url);

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }
    $response = wp_remote_retrieve_body($response);
    $response = json_decode($response, true);
    return $response;
}
function bca_jarvis_html_remove_class_attr($html)
{
    return preg_replace('/(<[^>]+) class=".*?"/i', '$1', $html);
}

if (!function_exists('bca_jarvis_register_cpt')) {

    // Register Custom Post Type
    function bca_jarvis_register_cpt()
    {

        $labels = array(
            'name'                  => _x('News Crawlers', 'Post Type General Name', 'bca'),
            'singular_name'         => _x('News Crawler', 'Post Type Singular Name', 'bca'),
            'menu_name'             => __('News Crawlers', 'bca'),
            'name_admin_bar'        => __('News Crawler', 'bca'),
            'archives'              => __('Item News Crawler', 'bca'),
            'attributes'            => __('Item Attributes', 'bca'),
            'parent_item_colon'     => __('Parent News Crawler:', 'bca'),
            'all_items'             => __('All News Crawlers', 'bca'),
            'add_new_item'          => __('Add New News Crawler', 'bca'),
            'add_new'               => __('Add New', 'bca'),
            'new_item'              => __('New News Crawler', 'bca'),
            'edit_item'             => __('Edit News Crawler', 'bca'),
            'update_item'           => __('Update News Crawler', 'bca'),
            'view_item'             => __('View News Crawler', 'bca'),
            'view_items'            => __('View News Crawlers', 'bca'),
            'search_items'          => __('Search News Crawler', 'bca'),
            'not_found'             => __('Not found', 'bca'),
            'not_found_in_trash'    => __('Not found in Trash', 'bca'),
            'featured_image'        => __('Featured Image', 'bca'),
            'set_featured_image'    => __('Set featured image', 'bca'),
            'remove_featured_image' => __('Remove featured image', 'bca'),
            'use_featured_image'    => __('Use as featured image', 'bca'),
            'insert_into_item'      => __('Insert into News Crawler', 'bca'),
            'uploaded_to_this_item' => __('Uploaded to this News Crawler', 'bca'),
            'items_list'            => __('Items list', 'bca'),
            'items_list_navigation' => __('Items list navigation', 'bca'),
            'filter_items_list'     => __('Filter News Crawlers list', 'bca'),
        );
        $args = array(
            'label'                 => __('News Crawler', 'bca'),
            'description'           => __('Post Type Description', 'bca'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'revisions', 'custom-fields'),
            'taxonomies'            => array(),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-admin-site-alt2',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        register_post_type('news-crawlers', $args);
        
        register_post_meta( 'news-crawlers', 'site_url', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
        ) );
        
    }
    add_action('init', 'bca_jarvis_register_cpt', 0);
}

if (!function_exists('bca_jarvis_register_taxonomy')) {

    // Register Custom Taxonomy
    function bca_jarvis_register_taxonomy()
    {

        $labels = array(
            'name'                       => _x('Crawled Contents', 'Taxonomy General Name', 'bca'),
            'singular_name'              => _x('Crawled Content', 'Taxonomy Singular Name', 'bca'),
            'menu_name'                  => __('Crawled Content', 'bca'),
            'all_items'                  => __('All Crawled Contents', 'bca'),
            'parent_item'                => __('Parent Crawled Content', 'bca'),
            'parent_item_colon'          => __('Parent Crawled Content:', 'bca'),
            'new_item_name'              => __('New Crawled Content Name', 'bca'),
            'add_new_item'               => __('Add New Crawled Content', 'bca'),
            'edit_item'                  => __('Edit Crawled Content', 'bca'),
            'update_item'                => __('Update Crawled Content', 'bca'),
            'view_item'                  => __('View Crawled Content', 'bca'),
            'separate_items_with_commas' => __('Separate Crawled Contents with commas', 'bca'),
            'add_or_remove_items'        => __('Add or remove Crawled Contents', 'bca'),
            'choose_from_most_used'      => __('Choose from the most used', 'bca'),
            'popular_items'              => __('Popular Crawled Contents', 'bca'),
            'search_items'               => __('Search Crawled Contents', 'bca'),
            'not_found'                  => __('Not Found', 'bca'),
            'no_terms'                   => __('No Crawled Contents', 'bca'),
            'items_list'                 => __('Crawled Contents list', 'bca'),
            'items_list_navigation'      => __('Crawled Contents list navigation', 'bca'),
        );
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
        );
        register_taxonomy('crawled-content', array('post'), $args);
    }
    add_action('init', 'bca_jarvis_register_taxonomy', 0);
}

function _get_mock_content( $filename = null )
{
    if ($filename == null) {
        $filename = BCA_JARVIS_PATH . '/src/test/test.txt';
    } else {
        $filename = BCA_JARVIS_PATH . '/src/test/' . $filename;
    }

    $contents = file_get_contents($filename);
    if ($contents === false) {
      throw new Exception("Could not read file '$filename'.");
    }
    return _prepare_content($contents);
}
function _prepare_content( $content )
{
    return trim( wpautop(_transform_headings($content)) );
}
function _transform_headings($text) {
    $patterns = array('/##\s*(.*)\s*/', '/#\s*(.*)\s*/');
    $replacements = array('<h2>$1</h2>', '<h2>$1</h2>');
    return preg_replace($patterns, $replacements, $text);
}
function _transform_string_to_list($str) {
    // Split the string into an array, using "\n" as the delimiter
    $arr = explode("\n", $str);
    
    // Remove any numbers from each array element
    foreach ($arr as &$element) {
      $element = trim(preg_replace('/\d+\.|\d+\. /', '', $element));
    }
    
    // Remove any empty elements
    $arr = array_filter($arr);
    
    // Reset the array keys
    $arr = array_values($arr);
    
    return $arr;
}
function _prompt_max_token_length($prompt)
{
    $body = [
        'prompt'  => $prompt
    ];
    
    $token_response = wp_remote_post('https://web-crawler.herokuapp.com/tokens', array(
        'body' => $body,
    ));

    if (is_wp_error($token_response)) {
        return $token_response->get_error_message();
    }
    $token_response = wp_remote_retrieve_body($token_response);
    $token_response = json_decode($token_response, true);
    
    $max_tokens = 2688;

    if (is_array($token_response)) {
        $prompt_content_token_count = count( $token_response );
        $max_tokens = 4001 - $prompt_content_token_count;
    }
    
    return $max_tokens;
}