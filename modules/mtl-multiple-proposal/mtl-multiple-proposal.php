<?php
/**
 * My Transit Lines
 * Multiple proposal view module
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2023-07-23 */

/**
 * map and meta data for multiple proposals
 */
function mtl_multiple_proposal_output( $atts ) {
	extract( shortcode_atts( array(
		'type' => 'mtlproposal',
		'statusid_query' => 0,
	), $atts ) );

	$output = '';

    // get the mtl options
	$mtl_options = get_option('mtl-option-name');

	if (!$statusid_query) {
		$the_query = get_query($type, 1);

		$output .= mtl_search_bar_output($the_query);
	} else {
		$the_query = new WP_Query(array(
			'posts_per_page' => -1,
			'post_type' => $type,
			'tax_query' => array(array(
				'taxonomy' => 'sorting-phase-status',
				'terms' => $statusid_query,
			)),
		));
	}
	
	// load the text translations
	$output .= mtl_localize_script(true);

	$mtl_all_catids = '';
	foreach(get_categories() as $category) {
        if($mtl_options['mtl-use-cat'.$category->cat_ID])
            $mtl_all_catids .= $category->cat_ID.',';
    }

    $all_selectable_categories = get_categories('include='.$mtl_all_catids);

	// load relevant scripts and set some JS variables
	$output .= "\r".'<div id="mtl-box">'."\r\n".'<script type="text/javascript"> var transportModeStyleData = {';
	foreach($all_selectable_categories as $single_category) {
		$catid = $single_category->cat_ID;
		$output .= $catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"],';
	}
	$output .= '}; </script>';

	$vector_data = "";
	$vector_labels_data = "";
	$vector_categories_data = "";
	$vector_proposal_data = "";

	while ($the_query->have_posts()) : $the_query->the_post(); global $post;

	$hide_proposal = (bool)(get_post_meta($post->ID, 'author-name', true) && $the_query->query_vars['author']);
	
	if(!$hide_proposal) {
		$category = get_the_category($post->ID);
		$catid = $category[0]->cat_ID;

		$vector_categories_data .= "\r\n".'"'.$catid.'",';
		// Removing line breaks that can be caused by WordPress import/export
		$vector_data .= "\r\n".'"'.str_replace(array("\n", "\r"), "", get_post_meta($post->ID, 'mtl-feature-data', true)).'",';
		$vector_labels_data .= "\r\n".'"'.str_replace(array("\n", "\r"), "", get_post_meta($post->ID, 'mtl-feature-labels-data', true)).'",';
		$vector_proposal_data .= '{author: "'.get_the_author_meta( 'display_name' ).'", title: "'.get_the_title().'", date: "'.get_the_date( 'd.m.Y' ).'", link: "'.get_permalink().'"},'."\r\n";
	}

	endwhile;
	wp_reset_postdata();
	
	// output the map box
	$output .= '<div id="mtl-map-box">'."\r\n";
	$output .= '<div id="mtl-map"></div>'."\r\n";
	$output .= '</div>';

	$output .= '<div id="popup" class="ol-popup">'."\r\n";
	$output .= '<a href="#" id="popup-closer" class="ol-popup-closer"></a>'."\r\n";
	$output .= '<div id="popup-content" class="ol-popup-content"><a id="popup-content-link" href=""><b id="popup-content-title"></b></a><br>';
	$output .= '<span>'.__('By', 'my-transit-lines').' <span id="popup-content-author"></span> ';
	$output .= __('on', 'my-transit-lines').' <span id="popup-content-date"></span></span></div>'."\r\n";
	$output .= '</div>'."\r\n";

	// output proposal data
	$output .= '<script id="mtl-multiple-proposal-data-script" type="text/javascript"> var multipleMode = true; var editMode = false; var themeUrl = "'. get_template_directory_uri() .'";';
	$output .= 'var vectorData = ['.$vector_data.'];'."\r\n";
	$output .= 'var vectorLabelsData = ['.$vector_labels_data.'];'."\r\n";
	$output .= 'var vectorCategoriesData = ['.$vector_categories_data.'];'."\r\n";
	$output .= 'var vectorProposalData = ['.$vector_proposal_data.']; </script>'."\r\n";

	// output relevant scripts
	$output .= '<link rel="stylesheet" href="'.get_template_directory_uri().'/openlayers/ol.css">'."\r\n";
	$output .= '<link rel="stylesheet" href="'.get_template_directory_uri().'/modules/mtl-multiple-proposal/mtl-multiple-proposal.css">'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/openlayers/dist/ol.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/modules/mtl-multiple-proposal/mtl-multiple-proposal.js"></script>'."\r\n";
	$output .= '<script type="text/javascript"> var loadingNewProposalsText = "'.__('Loading new set of proposals...','my-transit-lines').'";
				var multiple_proposal_page_url = "'.get_permalink().'"; </script>'."\r\n";

	// output opacity change button, map fullscreen link and toggle label checkbox
	$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="toggleMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="toggleMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span></p>'."\r\n";
	$output .= '<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'.__('Fit proposition to map','my-transit-lines').'</a></p>';
	$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:toggleFullscreen()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
	$output .= '<p class="alignright" id="mtl-toggle-labels"><label><input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></p>'."\r\n";
	$output .= '</div>'."\r\n";

	$output .= '<script type="text/javascript"> $(document).ready(function(){ document.getElementById("mtl-toggle-labels-link").checked = false; toggleLabels();}); var post_list_url = "'.get_permalink(get_option('mtl-option-name')['mtl-postlist-page']).'"; </script>'."\r\n";

	if (!$statusid_query)
		$output .= '<p class="alignleft"> <a id="mtl-post-list-link">'.__('Proposal list page','my-transit-lines').'</a> </p>';

    return $output;
}
add_shortcode( 'mtl-multiple-proposal', 'mtl_multiple_proposal_output' );

?>