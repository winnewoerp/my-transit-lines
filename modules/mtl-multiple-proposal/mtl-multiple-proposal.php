<?php
/**
 * My Transit Lines
 * Multiple proposal view module
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2023-07-23 */

/* ### STILL TO DO ###
 * I don't know, is there anything? Other custom stuff needed to be added to the multiple view?
 */

 /**
 * map and meta data for multiple proposals
 */
function mtl_multiple_proposal_output( $atts ) {
	$output = '';

    // get the mtl options
	$mtl_options = get_option('mtl-option-name');

	$the_query = get_query();

	$output .= mtl_search_bar_output($the_query);
	
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

	while ($the_query->have_posts()) : $the_query->the_post(); global $post;

	$hide_proposal = (bool)(get_post_meta($post->ID, 'author-name', true) && $the_query->query_vars['author']);
	
	if(!$hide_proposal) {
		$category = get_the_category($post->ID);
		$catid = $category[0]->cat_ID;

		$vector_categories_data .= "\r\n".'"'.$catid.'",';
		// Removing line breaks that can be caused by WordPress import/export
		$vector_data .= "\r\n".'"'.str_replace("\n", "", get_post_meta($post->ID, 'mtl-feature-data', true)).'",';
		$vector_labels_data .= "\r\n".'"'.str_replace("\n", "", get_post_meta($post->ID, 'mtl-feature-labels-data', true)).'",';
	}

	endwhile;
	wp_reset_postdata();

	$output .= '<script id="mtl-multiple-proposal-data-script" type="text/javascript"> var editMode = false; var themeUrl = "'. get_template_directory_uri() .'";';
	$output .= 'var vectorData = ['.$vector_data.'];'."\r\n";
	$output .= 'var vectorLabelsData = ['.$vector_labels_data.'];'."\r\n";
	$output .= 'var vectorCategoriesData = ['.$vector_categories_data.']; </script>'."\r\n";

	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/openlayers/OpenLayers.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/Editor/Lang/de.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/loader.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/modules/mtl-multiple-proposal/mtl-multiple-proposal.js"></script>'."\r\n";
	$output .= '<script type="text/javascript"> var loadingNewProposalsText = "'.__('Loading new set of proposals...','my-transit-lines').'";
				var multiple_proposal_page_url = "'.get_permalink().'"; </script>'."\r\n";
	
	// output the map box
	$output .= '<div id="mtl-map-box">'."\r\n";
	$output .= '<div id="mtl-map"></div>'."\r\n";
	$output .= '</div>';

	// output opacity change button, map fullscreen link and toggle label checkbox
	$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="setMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="setMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span></p>'."\r\n";
	$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:mtlFullscreenMap()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
	$output .= '<p class="alignright" id="mtl-toggle-labels"><label><input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></p>'."\r\n";
	$output .= '</div>'."\r\n";

	$output .= '<script type="text/javascript"> $(document).ready(function(){ document.getElementById("mtl-toggle-labels-link").checked = false; toggleLabels();}); </script>'."\r\n";

	$output .= '<script type="text/javascript"> var post_list_url = "'.get_permalink(get_option('mtl-option-name')['mtl-postlist-page']).'"; </script><p class="alignleft"> <a id="mtl-post-list-link">'.__('Proposal list page','my-transit-lines').'</a> </p>';

    return $output;
}
add_shortcode( 'mtl-multiple-proposal', 'mtl_multiple_proposal_output' );

?>