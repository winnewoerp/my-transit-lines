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
		'statusid_query' => 0,
		'proposal_ids' => '',
	), $atts ) );

	$output = '';

    // get the mtl options
	$mtl_options = get_option('mtl-option-name');

	if ($statusid_query) {
		$the_query = new WP_Query(array(
			'posts_per_page' => -1,
			'post_type' => 'mtlproposal',
			'tax_query' => array(array(
				'taxonomy' => 'sorting-phase-status',
				'terms' => $statusid_query,
			)),
		));
	} else if ($proposal_ids) {
		$the_query = new WP_Query(array(
			'posts_per_page' => -1,
			'post_type' => 'mtlproposal',
			'post__in' => explode(',', $proposal_ids),
		));
	} else {
		$the_query = get_query();

		$output .= mtl_search_bar_output($the_query);
	}
	
	// load the text translations
	$output .= mtl_localize_script(true);

	// load relevant scripts and set some JS variables
	$output .= "\r".'<div id="mtl-box">'."\r\n";
	$output .= get_transport_mode_style_data();

	$proposal_data = "";

	while ($the_query->have_posts()) : $the_query->the_post(); global $post;

	$comma = isset($comma) ? ",\r\n" : "";

	$proposal_data .= $comma.get_proposal_data_json($post->ID);

	endwhile;
	wp_reset_postdata();
	
	// output the map box
	$output .= '<div id="mtl-map-box">'."\r\n";

	// Add the list-loader notification
	$newProposalText = __('Loading new set of proposals...','my-transit-lines');
	$output .= "<div style=\"display:none;\" class=\"mtl-list-loader\">$newProposalText</div>";
	$output .= "<div style=\"display:none;\" class=\"mtl-list-loader bottom\">$newProposalText</div>";

	$output .= '<div id="mtl-map"></div>'."\r\n";
	$output .= '</div>';

	$output .= '<div id="popup" class="ol-popup" style="display:none;">'."\r\n";
	$output .= '<a href="#" id="popup-closer" class="ol-popup-closer"></a>'."\r\n";
	$output .= '<div id="popup-content" class="ol-popup-content"><a id="popup-content-link" href=""><b id="popup-content-title"></b></a><br>';
	$output .= '<span>'.__('By', 'my-transit-lines').' <span id="popup-content-author"></span> ';
	$output .= __('on', 'my-transit-lines').' <span id="popup-content-date"></span></span></div>'."\r\n";
	$output .= '</div>'."\r\n";

	// output proposal data
	$output .= '
	<script data-mtl-data-script data-mtl-replace-with="#mtl-multiple-proposal-data-script" id="mtl-multiple-proposal-data-script" type="application/json">
		{"proposalList":['.$proposal_data.']}
	</script>';

	$output .= '<script type="text/javascript"> var showLabels = false; var multipleMode = true; var editMode = false; var themeUrl = "'. get_template_directory_uri() .'";</script>';

	// output relevant scripts
	wp_enqueue_script('mtl-multiple-proposal', get_template_directory_uri() . '/modules/mtl-multiple-proposal/mtl-multiple-proposal.js', array('my-transit-lines'), wp_get_theme()->version, true);

	// output opacity change button, map fullscreen link and toggle label checkbox
	$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="toggleMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="toggleMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span></p>'."\r\n";
	$output .= '<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'.__('Fit proposition to map','my-transit-lines').'</a></p>';
	$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:toggleFullscreen()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
	$output .= '<p class="alignright" id="mtl-toggle-labels"><label><input type="checkbox" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></p>'."\r\n";
	$output .= '</div>'."\r\n";

	if (!$statusid_query)
		$output .= '<p class="alignleft"><a data-mtl-search-link href="' . get_permalink(pll_get_post($mtl_options['mtl-postlist-page'])) . '">'.__('Proposal list page','my-transit-lines').'</a></p>';

    return $output;
}
add_shortcode( 'mtl-multiple-proposal', 'mtl_multiple_proposal_output' );

?>