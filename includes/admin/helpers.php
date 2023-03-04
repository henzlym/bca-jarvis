<?php
function _get_object_property($obj, $prop, $defaultValue = null) {
    return $obj[$prop] ?? $defaultValue;
}
  
function _strip_tags_except($string, $allowed_tags) {
    // Remove all HTML tags except for the allowed tags
    $string = strip_tags($string, '<' . implode('><', $allowed_tags) . '>');
    
    // Remove nested tags
    $string = _remove_img_tags($string);
    $string = _clean_signatures( $string );
    
    return $string;
}

function _remove_img_tags($string) {
    $pattern = '/<img\s[^>]*>/i';
    $replacement = '';
    $string = preg_replace($pattern, $replacement, $string);
    return $string;
}

function _remove_html_tags_with_pattern($string, $pattern) {
    $pattern = '/<[^>]*>(' . $pattern . '.*?)<\/[^>]*>/i';
    $replacement = '';
    $string = preg_replace($pattern, $replacement, $string);
    return $string;
}

function _clean_signatures( $string )
{
    $string = _remove_html_tags_with_pattern( $string, 'HodlX Guest PostSubmit Your Post');
    $string = _remove_html_tags_with_pattern( $string, 'Check Latest Headlines on');
    $string = _remove_html_tags_with_pattern( $string, "Don't Miss a Beat – Subscribe");
    $string = _remove_html_tags_with_pattern( $string, 'Follow Us on Twitter Facebook Telegram');
    $string = _remove_html_tags_with_pattern( $string, 'Follow us on Twitter Facebook and Telegram');
    $string = _remove_html_tags_with_pattern( $string, 'Check out the Latest Industry Announcements');
    $string = _remove_html_tags_with_pattern( $string, 'Check Latest News Headlines');
    $string = _remove_html_tags_with_pattern( $string, 'Disclaimer: Opinions expressed');
    $string = _remove_html_tags_with_pattern( $string, 'Featured Image: Shutterstock');
    $string = _remove_html_tags_with_pattern( $string, 'Generated Image: ');

    return $string;
}