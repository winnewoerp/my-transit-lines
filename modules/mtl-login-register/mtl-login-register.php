<?php
/**
 * My Transit Lines
 * Login/Register module functions and definitions
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */

/**
 * create Login/Register widget showing login/register when not logged in, greeting+username/logout/change password when logged in
 */
function mtl_login_register_widget($args) {
   extract($args);
   $mtl_options = get_option('mtl-option-name');
   echo $before_widget;
    // print some HTML for the widget to display here
	if(!is_user_logged_in()) {
		$widget_content = '';
		echo $before_title . __('Login/Register','my-transit-lines') . $after_title;
		echo $after_widget;
		$widget_content .= '<li><a href="'.wp_login_url().'">'.__('Login','my-transit-lines').'</a></li>';
		if (get_option('users_can_register')) $widget_content .= '<li><a href="'.wp_registration_url().'">'.__('Register','my-transit-lines').'</a></li></li>';
		else $widget_content .= '</li>';
		echo $widget_content;
	}
	else {
		global $user_login;
		$widget_content = '';
		wp_get_current_user();
		echo $before_title . __('Hello','my-transit-lines').' <strong>'.$user_login.'</strong>!' . $after_title;
		echo $after_widget;
		$widget_content .= '<li><a href="'.get_permalink(pll_get_post($mtl_options['mtl-postlist-page'])).'?mtl-userid='.get_current_user_id().'&show-drafts=true">'.esc_html__('My proposals','my-transit-lines').'</a></li>';
		$widget_content .= '<li><a href="'.get_permalink(pll_get_post($mtl_options['mtl-settings-page'])).'">'.esc_html__('Settings','my-transit-lines').'</a></li>';
		$widget_content .= '<li><a href="'.wp_logout_url().'">'.__('Logout','my-transit-lines').'</a></li>';
		$widget_content .= '<li><a href="'.wp_lostpassword_url().'">'.__('Change password','my-transit-lines').'</a></li></li>';
		echo $widget_content;
		
	}
}


/**
 * register the widget for use in dashboard widget section
 */
function mtl_register_sidebar_widget() {
	wp_register_sidebar_widget(
		'mtl_login_register', // unique widget id
		__('MTL Login/Register','my-transit-lines'), // widget name
		'mtl_login_register_widget',  // callback function
		array( // options
			'description' => 'Showing login/register when not logged in, greeting+username/logout/change password when logged in'
		)
	);
}
add_action('init', 'mtl_register_sidebar_widget');

/**
 * populate sidebar2 in top menu bar with the Login/Register widget by default
 */
function mtl_default_widgets_sidebar2() {
	add_option("sidebars_widgets",
		array("sidebar-2" => array('mtl_login_register_widget', "tag_cloud"))
	);	
}
add_action('populate_options', 'mtl_default_widgets_sidebar2');

/**
 * redirect users to front page after login
 */
function redirect_to_front_page() {
global $redirect_to;
if (!isset($_GET['redirect_to'])) {
$redirect_to = get_option('siteurl');
}
}
add_action('login_form', 'redirect_to_front_page');

/**
 * disable admin bar on the frontend for subscribers.
 */
function themeblvd_disable_admin_bar() { 
	if( ! current_user_can('edit_posts') )
		add_filter('show_admin_bar', '__return_false');	
}
add_action( 'after_setup_theme', 'themeblvd_disable_admin_bar' );
 
/**
 * redirect back to homepage and not allow access to WP admin for subscribers.
 */
function themeblvd_redirect_admin(){
	if ( ! current_user_can( 'edit_posts' ) ){
		wp_redirect( site_url() );
		exit;		
	}
}
add_action( 'admin_init', 'themeblvd_redirect_admin' );

?>