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
	['mtl-only-in-map-cat', 'only-in-map'],
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

	foreach(get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' ) as $single_category) {
		$catid = $single_category->cat_ID;
		if($mtl_options['mtl-use-cat'.$catid] == true) {
			$output .= '"'.$catid.'":{';
			foreach(STYLE_DATA_KEYS as $style_data) {
				$output .= '"'.$style_data[1].'":"'.$mtl_options[$style_data[0].$catid].'",';
			}
			$output .= '"name":"'.__($single_category->name, 'my-transit-lines').'",';
			$output .= '},';
		}
	}
	$output .= '}; </script>'."\r\n";

	return $output;
}

/**
 * Get the categories that were activated in the admin menu
 * @return array
 */
function get_active_categories() {
	$mtl_options = get_option('mtl-option-name');

	return array_filter(get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' ), function($category) use ($mtl_options) {
		return $mtl_options['mtl-use-cat'.$category->cat_ID] && !$mtl_options['mtl-only-in-map-cat'.$category->cat_ID];
	});
}
