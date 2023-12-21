<?php
/**
 * My Transit Lines
 * Proposal tile list
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-07 */

/* ### STILL TO DO ###
 * any suggestions?
 */

/**
 * create the thumb maps
 */
global $count_thumblist_maps;
$count_thumblist_maps = 0;
function mtl_thumblist_map() {
	// get the mtl options
	$mtl_options2 = get_option('mtl-option-name2');
	$output = '';
	global $post;
	$output .= '<div id="thumblist-map'.$post->ID.'" class="mtl-thumblist-map'.($mtl_options2['mtl-current-project-phase']=='rate' ? ' rating' : '').'"></div>';
	$output .= '<script type="text/javascript"> createThumbMap('.$post->ID.'); </script>';
	return $output;
}

/**
 * shortcode [mtl-tile-list]
 */
function mtl_tile_list_output($atts) {
	global $post;
	$output = '';
	extract( shortcode_atts( array(
		'type' => 'mtlproposal',
		'hidethumbs' => false,
	), $atts ) );
	
	$output = '';

    // get the mtl options
	$mtl_options = get_option('mtl-option-name');

	$the_query = get_query($type, 4);

	$output .= mtl_search_bar_output($the_query);
		
	// start the tile list
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/modules/mtl-tile-list/mtl-tile-list.js"></script>';
	$output .= '<div class="mtl-posttiles-list">';
	
	// load the text translations
	$output .= mtl_localize_script(true);
	
	// load the necessary scripts and set some JS variables
	if(!$hidethumbs) $output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/openlayers/dist/ol.js"></script>'."\r\n";
	$output .= '<script type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = [""]; var vectorLabelsData = [""]; var vectorCategoriesData = [undefined]; var editMode = false; </script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>';
	if(!$hidethumbs) $output .= '<script type="text/javascript"> var centerLon = "'.$mtl_options['mtl-center-lon'].'"; var centerLat = "'.$mtl_options['mtl-center-lat'].'"; </script>'."\r\n";
	$output .= '<script type="text/javascript"> ';
	$output .= ' var loadingNewProposalsText = "'.__('Loading new set of proposals...','my-transit-lines').'";';
	$output .= ' var tilePageUrl = "'.get_permalink().'"; var initMap = false;';
	if(!$hidethumbs) {
		$output .= ' var transportModeStyleData = {';
		foreach(get_categories('include='.$mtl_all_catids) as $single_category) {
			$catid = $single_category->cat_ID;
			$output .= $catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"],';
		}
		$output .= '};';
	}
	$output .= '</script>'."\r\n";
	
	// output the add post tile (first tile of the list, shown in most cases)
	if($type == 'mtlproposal' && $mtl_options['mtl-addpost-page']) $output .= '<div class="mtl-post-tile add-post"><div class="entry-thumbnail placeholder"></div><h1><a href="'.get_permalink($mtl_options['mtl-addpost-page']).'">'.__('Add a new proposal with map and description','my-transit-lines').'</a></h1><div class="entry-meta">'.__('Contribute to the collection!','my-transit-lines').'</div></div>';
	
	// loop through the tiles
	while($the_query->have_posts()) : $the_query->the_post(); global $post;
	
	$hide_proposal = (bool)(get_post_meta($post->ID, 'author-name', true) && $get_userid);
	
	$catid = get_the_category($post->ID)[0]->cat_ID;
	
	if(!$hide_proposal && $mtl_options['mtl-use-cat'.$catid] == true) {
		$bgcolor = $mtl_options['mtl-color-cat'.$catid];
		
		$output .= '<div class="mtl-post-tile" style="background-color:'.$bgcolor.'" >';
		
		if(!$hidethumbs) {
			// Removing line breaks that can be caused by WordPress import/export
			$output .= '<script type="text/javascript"> var currentCat = '.$catid.'; var pluginsUrl = "'. plugins_url('', __FILE__) .'"; var vectorData = ["'.str_replace(array("\n", "\r"), "", get_post_meta($post->ID,'mtl-feature-data',true)).'"]; var vectorLabelsData = ["'.str_replace(array("\n", "\r"), "", get_post_meta($post->ID,'mtl-feature-labels-data',true)).'"]; var vectorCategoriesData = [undefined]; var editMode = false; </script>'."\r\n";
			$output .= mtl_thumblist_map();
		}
		$output .= mtl_load_template_part('content', get_post_format());

		if(current_user_can('manage_options') && strlen(get_post_meta($post->ID, 'mtl-editors-hints', true)) > 10)
			$output .= 'hints text ready';
		
		$output .= '</p></div>';
	}
	endwhile;
	wp_reset_postdata();

	$output .= '<div class="clear"></div></div>';
	
	$output .= get_paginate_links($the_query->max_num_pages);

	$output .= '<script type="text/javascript"> var post_map_url = "'.get_permalink(get_option('mtl-option-name')['mtl-postmap-page']).'"; </script><p class="alignleft"> <a id="mtl-post-map-link">'.__('Proposal map page','my-transit-lines').'</a> </p>';
	
	return $output;
}
add_shortcode( 'mtl-tile-list', 'mtl_tile_list_output' );
