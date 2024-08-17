<?php
/**
 * My Transit Lines
 * Proposal tile list
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-07 */

/**
 * create the thumb maps
 */
function mtl_thumblist_map() {
	global $post;
	$output = '<div id="thumblist-map'.$post->ID.'" class="mtl-thumblist-map"></div>';
	return $output;
}

/**
 * shortcode [mtl-tile-list]
 */
function mtl_tile_list_output($atts) {
	global $post;
	$output = '';
	extract( shortcode_atts( array(
		'hidethumbs' => false,
	), $atts ) );
	
	$output = '';

    // get the mtl options
	$mtl_options = get_option('mtl-option-name');

	$the_query = get_query(4);

	$output .= mtl_search_bar_output($the_query);
		
	// start the tile list
	$output .= '<div data-mtl-replace-with="#mtl-posttiles-list" id="mtl-posttiles-list" class="mtl-posttiles-list">';

	// Add the list-loader notification
	$newProposalText = __('Loading new set of proposals...','my-transit-lines');
	$output .= "<div style=\"display:none;\" class=\"mtl-list-loader\">$newProposalText</div>";
	$output .= "<div style=\"display:none;\" class=\"mtl-list-loader bottom\">$newProposalText</div>";
	
	// load the text translations
	$output .= mtl_localize_script(true);
	
	// load the necessary scripts and set some JS variables
	$output .= '<script type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'";</script>'."\r\n";
	wp_enqueue_script('mtl-tile-list', get_template_directory_uri().'/modules/mtl-tile-list/mtl-tile-list.js', array('my-transit-lines'), wp_get_theme()->version);
	if(!$hidethumbs) $output .= '<script type="text/javascript"> var centerLon = "'.$mtl_options['mtl-center-lon'].'"; var centerLat = "'.$mtl_options['mtl-center-lat'].'"; var standardZoom = "'.$mtl_options['mtl-standard-zoom'].'"; </script>'."\r\n";
	if(!$hidethumbs) {
		$output .= get_transport_mode_style_data();
	}

	if($mtl_options['mtl-addpost-page']) $output .= '<div class="mtl-post-tile add-post"><div class="entry-thumbnail placeholder"></div><h1><a href="'.get_permalink(pll_get_post($mtl_options['mtl-addpost-page'])).'">'.__('Add a new proposal with map and description','my-transit-lines').'</a></h1><div class="entry-meta">'.__('Contribute to the collection!','my-transit-lines').'</div></div>';

	$proposalDataList = '';

	// loop through the tiles
	while($the_query->have_posts()) : $the_query->the_post(); global $post;
	
	$catid = get_the_category($post->ID)[0]->cat_ID;
	
	if(!in_array($mtl_options['mtl-cat-use'.$catid], ['no','only-in-search'])) {
		$bgcolor = $mtl_options['mtl-color-cat'.$catid];
		
		$output .= '<div class="mtl-post-tile" style="background-color:'.$bgcolor.'" >';
		
		if(!$hidethumbs) {
			$comma = isset($comma) ? ",\r\n" : "";

			$proposalDataList .= $comma.get_proposal_data_json($post->ID);

			$output .= mtl_thumblist_map();
		}
		$output .= mtl_load_template_part('content', get_post_format());

		$output .= '</div>';
	}
	endwhile;
	wp_reset_postdata();

	$output .= '<script data-mtl-data-script id="mtl-tile-list-data-script" type="application/json">{"proposalList":['.$proposalDataList.']}</script>';

	$output .= '<div class="clear"></div></div>';
	
	$output .= get_paginate_links($the_query->max_num_pages);

	$output .= '<p class="alignleft"><a data-mtl-search-link href="'.get_permalink(pll_get_post($mtl_options['mtl-postmap-page'])).'">'.__('Proposal map page','my-transit-lines').'</a></p>';
	
	return $output;
}
add_shortcode( 'mtl-tile-list', 'mtl_tile_list_output' );
