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
	$output .= get_transport_mode_style_data();

	// output proposal data
	$output .= '
	<script data-mtl-data-script data-mtl-replace-with="#mtl-multiple-proposal-data-script" id="mtl-multiple-proposal-data-script" type="application/json">
		{"proposalList":'.get_all_proposal_data_json($the_query).'}
	</script>';

	$output .= '
	<script type="text/javascript">
		var showLabels = false;
		var multipleMode = true;
		var editMode = false;
		var themeUrl = "'. get_template_directory_uri() .'";
	</script>';

	$output .= the_map_output();

	$output .= '<div id="popup" class="ol-popup" style="display:none;">'."\r\n";
	$output .= '<a href="#" id="popup-closer" class="ol-popup-closer"></a>'."\r\n";
	$output .= '<div id="popup-content" class="ol-popup-content"><a id="popup-content-link" href=""><b id="popup-content-title"></b></a><br>';
	$output .= '<span>'.__('By', 'my-transit-lines').' <span id="popup-content-author"></span> ';
	$output .= __('on', 'my-transit-lines').' <span id="popup-content-date"></span></span></div>'."\r\n";
	$output .= '</div>'."\r\n";

	// output relevant scripts
	wp_enqueue_script('mtl-multiple-proposal', get_template_directory_uri() . '/modules/mtl-multiple-proposal/mtl-multiple-proposal.js', array('my-transit-lines'), wp_get_theme()->version, true);

	if (!$statusid_query)
		$output .= '<p class="alignleft"><a data-mtl-search-link href="' . get_permalink(pll_get_post($mtl_options['mtl-postlist-page'])) . '">'.__('Proposal list page','my-transit-lines').'</a></p>';

    return $output;
}
add_shortcode( 'mtl-multiple-proposal', 'mtl_multiple_proposal_output' );
