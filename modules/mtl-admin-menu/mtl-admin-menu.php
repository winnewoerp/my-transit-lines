<?php
/**
 * My Transit Lines
 * Dashboard admin section module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */

/**
 * include the class created for this module
 */
include( get_template_directory() . '/modules/mtl-admin-menu/mtl-admin-menu.class.php'); // MTL dashboard admin section module class

/**
 * include scripts needed for the admin section
 */
function mtl_admin_scripts( ) {
	global $post;
	// preparing the media uploader
	wp_enqueue_media();
	
	// preparing the color picker
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker-script', plugins_url('script.js', __FILE__ ), array( 'wp-color-picker' ), false, true );
	
	// load main js file for the theme including l10n script
	if(!$post->ID) {
		mtl_localize_script();
		wp_enqueue_script( 'OpenLayers', get_template_directory_uri().'/openlayers/dist/ol.js', array());
		wp_enqueue_script( 'my-transit-lines', get_template_directory_uri() . '/js/my-transit-lines.js', array()); // TODO don't use main js file
	}
	
	// enqueue theme style file to admin pages
	wp_enqueue_style( 'my-transit-lines-style', get_template_directory_uri().'/modules/mtl-admin-menu/style.css',array() );
}
add_action( 'admin_enqueue_scripts', 'mtl_admin_scripts' );


/**
 * include the WP Admin Backend additions for this theme to the dashboard
 */
if( is_admin() ) $mtl_settings_page = new MtlSettingsPage();


?>