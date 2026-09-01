<?php
/**
 * My Transit Lines
 * Flextiles module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-07 */

/**
 * load the styles and scripts
 */
function mtl_flextiles_init()
{
	wp_enqueue_style('flextiles', get_template_directory_uri() . '/modules/mtl-flextiles/flextiles.css',array());
	wp_enqueue_script('flextiles-js',  get_template_directory_uri() . '/modules/mtl-flextiles/flextiles.js',array( 'jquery' ) );
}
add_action('wp_enqueue_scripts', 'mtl_flextiles_init');

 /**
 * shortcode [mtl-flextiles]
 */
function mtl_flextiles_output( $atts ){
	extract( shortcode_atts( array(
		'type' => 'menu',
		'menu_class' => '',
		'menu_name' => '',
		'width' => 250,
		'height' => 250,
		'space' => 10,
		'paddingtop' => 0,
		'paddingbottom' => 0,
		'bgcolors' => '',
		'colors' => '',
		'linkstyles' => '',
		'images' => ''
	), $atts ) );
	$a_style = $linkstyles;
	$bgcolors = explode(',',$bgcolors);
	$colors = explode(',',$colors);
	$images = explode(',',$images);
	$nav_style = '';
	$li_style = '';
	if($width) $li_style .= 'width:'.$width.'px;';
	if($height) $li_style .= 'height:'.$height.'px;';
	if($space) {
		$li_style .= 'margin:0 0 '.$space.'px '.$space.'px;';
		$nav_style .= 'margin-left:-'.$space.'px;';
	}
	if($paddingtop) $nav_style .= 'padding-top:'.$paddingtop.'px;';
	if($paddingbottom) $nav_style .= 'padding-bottom:'.$paddingbottom.'px;';
	if($menu_name) {
		$menu_object = wp_get_nav_menu_object($menu_name);
		$menu_id = $menu_object->term_id;
		$menu_slug = $menu_object->slug;
		$menu_content = wp_get_nav_menu_items($menu_id);
		$output = '<div class="flextiles-list"><nav class="flex-navigation '.$menu_class.'" style="'.$nav_style.'">';
		$output .= '<ul id="flexmenu-' . $menu_slug . '">';
		$countNavItems = 0;
		foreach ( (array) $menu_content as $key => $menu_item ) {
			$this_li_style = '';
			$this_a_style = '';
			if($bgcolors[$countNavItems]) $this_li_style .= 'background-color:'.$bgcolors[$countNavItems].';';
			if($images[$countNavItems]) {
				$image = wp_get_attachment_image_src($images[$countNavItems],'full');
				$src= $image[0];
				$this_li_style .= 'background-image:url('.$src.');';
			}
			if($colors[$countNavItems]) $this_a_style .= 'color:'.$colors[$countNavItems].';';
			$title = $menu_item->title;
			$url = $menu_item->url;
			$output .= '<li style="'.$li_style.$this_li_style.'"><a style="'.$this_a_style.$a_style.'" href="' . $url . '">' . $title . '</a></li>';
			$countNavItems++;
		}
		$output .= '</ul>';
		$output .= '<div style="clear:both"></div>';
		$output .= '</nav></div>';
		if($width) $output .= '<script type="text/javascript"> flextilesWidth = '.$width.'; </script>';
		if($space) $output .= '<script type="text/javascript"> flextilesSpace = '.$space.'; </script>';
	}
	return $output;
}
add_shortcode( 'mtl-flextiles', 'mtl_flextiles_output' );
?>