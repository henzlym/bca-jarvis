<?php
include_once BCA_JARVIS_PATH . '/includes/openai/config.php';
include_once BCA_JARVIS_PATH . '/includes/openai/openai.php';

function bca_jarvis_rest_api_init()
{
    register_rest_route('openai/v1', '/create_completion', array(
        'methods' => 'POST',
        'callback' => 'openai_create_completion',
    ));

    register_rest_route('jarvis/v1', '/crawler', array(
        'methods' => 'GET',
        'callback' => 'bca_jarvis_crawler',
    ));
    register_rest_route('jarvis/v1', '/feed', array(
        'methods' => 'GET',
        'callback' => 'bca_jarvis_feed',
    ));
}
add_action('rest_api_init', 'bca_jarvis_rest_api_init', 10);

function openai_create_completion($request)
{
    $response = array();
    $content = $request->get_param('content');
    $title = $request->get_param('title');

    $content = $content ? $content : '';
    $title = $title ? $title : '';
    if (trim($content) === '' || trim($title) === '') {
        $response['error'] = array(
            'message' => "No content/title was provided",
        );
        http_response_code(400);
        echo json_encode($response);
        return;
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
        http_response_code(500);
        echo json_encode($response);
        return;
    }

    try {
        $completion = $openai->create_completion(array(
            'model' => "text-davinci-003",
            'prompt' => openai_generate_prompt($content, $title),
            'temperature' => 0.9,
            'max_tokens' => 2688,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0.6
        ));
        error_log(print_r($completion, true));
        $response['result'] = $completion['choices'][0]['text'];
        http_response_code(200);
    } catch (Exception $error) {
        // Consider adjusting the error handling logic for your use case
        if ($error->response) {
            error_log($error->response->status . ' ' . $error->response->data);
            $response['error'] = $error->response->data;
            http_response_code($error->response->status);
        } else {
            error_log('Error with OpenAI API request: ' . $error->message);
            $response['error'] = array(
                'message' => 'An error occurred during your request.',
            );
            http_response_code(500);
        }
    }

    echo json_encode($response);
}
function openai_generate_prompt($content, $title)
{
    $content = ucfirst(strtolower($content));
    $prompt = "Rewrite this text in the writing style of a New York Times journalist.
        ###
        $content
        ###
    ";
    return $prompt;
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
    // $command = "python3 " . BCA_JARVIS_PATH . "crawler-newspaper.py";
    // $safe_cmd = escapeshellcmd($command);
    error_log(print_r('Jarvis start....', true));
    // error_log(print_r( $command,true));
    // exec($safe_cmd, $output, $return_var);    
    // error_log(print_r($output,true));
    $response = wp_remote_get('https://web-crawler.herokuapp.com/crawl?url=' . $url);
    error_log(print_r($response, true));

    if (is_wp_error($response)) {
        return $response->get_error_message();
    }
    $response = wp_remote_retrieve_body($response);
    $response = json_decode($response, true);

    $allowed_tags = '<h1><h2><h3><h4><h5><h6><p><strong><em><b><cite><span>';
    $stripped_html = strip_tags($response['article']['html'], $allowed_tags);
    $stripped_html = bca_jarvis_html_remove_class_attr($stripped_html);
    $response['html'] = $stripped_html;
    $results['result'] = $response['article'];
    error_log(print_r($results, true));
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
