<?php
// AJAX test module - will be removed later

function register_ajaxLoop_script() {
    wp_register_script('ajaxLoop', get_template_directory_uri().'module/mtl-ajax-test/js/ajaxLoop.js', array('jquery'));
    wp_enqueue_script('ajaxLoop');
}
add_action('wp_enqueue_scripts', 'register_ajaxLoop_script');
?>