<?php
function crm_custom_theme_enqueue_scripts() {
    // Enqueue main stylesheet
    wp_enqueue_style('crm-theme-style', get_stylesheet_uri(), array(), '1.0');
    
    // Enqueue custom CSS
    wp_enqueue_style('crm-custom-css', get_template_directory_uri() . '/css/custom.css', array(), time());

    // Enqueue custom JS
    wp_enqueue_script('crm-custom-js', get_template_directory_uri() . '/js/custom.js', array(), time(), true);

    // Localize script for AJAX URL
    wp_localize_script('crm-custom-js', 'crmAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php')
    ));
}
add_action('wp_enqueue_scripts', 'crm_custom_theme_enqueue_scripts');

// Add theme support
function crm_custom_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'crm_custom_theme_setup');

// Require enquiry backend logic
require_once get_template_directory() . '/inc/enquiries.php';

// Require PWA functionality
require_once get_template_directory() . '/inc/pwa.php';


add_filter('admin_footer_text', function () {
    return '';
});

add_filter('update_footer', '__return_empty_string', 11);