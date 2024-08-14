<?php
/**
 * My Transit Lines
 * Common functions for all modules
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2024-06-25 */

const STYLE_DATA_KEYS = array(
	['mtl-color-cat', 'color'],
	['mtl-image-cat', 'image'],
	['mtl-image-selected-cat', 'image-selected'],
	['mtl-costs-cat', 'costs'],
	['mtl-allow-others-cat', 'allow-others'],
);

/**
 * Returns the <script> tag containing the transport mode style data
 *
 * @return string
 */
function get_transport_mode_style_data() {
	// get the mtl options
	$mtl_options = get_option( 'mtl-option-name' );

	$output = '<script type="text/javascript"> let transportModeStyleData = {';

	foreach(get_map_categories() as $cat) {
		$catid = $cat->term_id;

		$output .= "\"$catid\":{";
		foreach(STYLE_DATA_KEYS as $style_data) {
				$output .= "\"{$style_data[1]}\":\"{$mtl_options[$style_data[0].$catid]}\",";
		}
		$output .= "\"name\":\"{__($cat->name,'my-transit-lines'}\",";
		$output .= "},";
	}
	$output .= "}; </script>\r\n";

	return $output;
}

/**
 * Get the categories that were activated in the admin menu
 * @return array
 */
function get_active_categories() {
	$mtl_options = get_option('mtl-option-name');

	return array_filter(get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' ), function($cat) use ($mtl_options) {
		return $mtl_options['mtl-cat-use'.$cat->cat_ID] == 'use';
	});
}

function get_map_categories() {
	$mtl_options = get_option('mtl-option-name');

	return array_filter(get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' ), function($cat) use ($mtl_options) {
		return in_array($mtl_options['mtl-cat-use'.$cat->term_id], ['use', 'only-in-map']);
	});
}

/**
 * Get the categories that were activated to be searched for in the admin menu
 * @return array
 */
function get_searchable_categories() {
	$mtl_options = get_option('mtl-option-name');

	return array_filter(get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' ), function($cat) use ($mtl_options) {
		return in_array($mtl_options['mtl-cat-use'.$cat->term_id], ['use', 'only-in-search']);
	});
}
