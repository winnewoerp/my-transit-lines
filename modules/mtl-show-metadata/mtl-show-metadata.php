<?php
/**
 * My Transit Lines
 * Proposal metadata display
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2023-10-14 */

function mtl_show_metadata_output($atts) {
	global $post;
	$mtl_options3 = get_option('mtl-option-name3');

	$a = shortcode_atts( 
		array(
			'id' => $post->ID,
			'format_string' => $mtl_options3['mtl-proposal-metadata-contents'],
		),
		$atts
	);

	if (!$a['id'])
		return;

	$id = $a['id'];

	$search = array('[post-category]', '[post-length]', '[post-station-count]', '[post-station-distance]', '[post-costs]');
	$replace = array(get_category_name($id), get_line_length($id), get_count_stations($id), get_average_distance($id), get_cost($id));

	// output the meta data
	$output = '<h2>'.__('Metadata for this proposal','my-transit-lines').'</h2>'."\r\n";
	$output .= '<div class="mtl-metadata" id="mtl-metadata">';
	$output .= str_replace($search, $replace, $a['format_string']);
	$output .= '</div>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/modules/mtl-show-metadata/mtl-show-metadata.js?ver='.wp_get_theme()->version.'"></script>'."\r\n";
	if($mtl_options3['mtl-show-districts'] || current_user_can('administrator')) $output .= mtl_taglist();

	return $output;
}
add_shortcode( 'mtl-show-metadata', 'mtl_show_metadata_output' );

function get_category_name($id) {
	$output  = '<details id="mtl-metadata-category-name">';
	$output .= '<summary>'.__('Transit mode', 'my-transit-lines').': '.__(get_the_category($id)[0]->name, 'my-transit-lines').'</summary>';
	$output .= '<div></div>';
	$output .= '</details>';

	return $output;
}

function get_line_length($id) {
	$lineLength = format_unit_prefix(max(get_post_meta($id,'mtl-line-length',true), 0), false).'m';

	$output  = '<details id="mtl-metadata-line-length">';
	$output .= '<summary>'.__('Line length', 'my-transit-lines').': '.$lineLength.'</summary>';
	$output .= '<div></div>';
	$output .= '</details>';

	return $output;
}

function get_count_stations($id) {
	$countStations = max(get_post_meta($id,'mtl-count-stations',true), 0);

	$output  = '<details id="mtl-metadata-count-stations">';
	$output .= '<summary>'.__('Station count', 'my-transit-lines').': '.$countStations.'</summary>';
	$output .= '<div></div>';
	$output .= '</details>';

	return $output;
}

function get_average_distance($id) {
	$line_length = max(get_post_meta($id,'mtl-line-length',true), 0);
	$count_stations = max(get_post_meta($id,'mtl-count-stations',true), 0);

	if (!$line_length || $count_stations < 2)
		return __('Average distance', 'my-transit-lines').': 0 m<br>';

	$average_distance = $line_length / ($count_stations - 1);

	return __('Average distance', 'my-transit-lines').': '.format_unit_prefix($average_distance, false).'m<br>';
}

function get_cost($id) {
	$costs = format_unit_prefix(max(get_post_meta($id,'mtl-costs',true), 0) * 1E6, true).get_option('mtl-option-name3')['mtl-currency-symbol'];

	$output  = '<details id="mtl-metadata-costs">';
	$output .= '<summary>'.__('Costs', 'my-transit-lines').': '.$costs.'</summary>';
	$output .= '<div></div>';
	$output .= '</details>';

	return $output;
}

/**
 * Format the number with its unit prefix
 * Examples: 	format_unit_prefix(1250) == '1.25k'
 * 				format_unit_prefix(1.25E6, true) == '1.25 million ';
 * 
 * @param [number] $number the number to format
 * @param [bool] $word_prefix whether to use words (million, billion, ...) or short prefixes (k, M, G) for numbers larger than 1000
 * @param [number] $step the step size to use instead of 1000, for example for squared or cubed units
 * @return string
 */
function format_unit_prefix($number, $word_prefix, $step=1E3) {
	if ($number >= $step**3) {
		return str_replace('.',_x('.', 'decimal separator', 'my-transit-lines'), round($number/$step**3,3)).($word_prefix ? ' '.__('billion', 'my-transit-lines').' ' : ' G');
	}
	if ($number >= $step**2) {
		return str_replace('.',_x('.', 'decimal separator', 'my-transit-lines'), round($number/$step**2,3)).($word_prefix ? ' '.__('million', 'my-transit-lines').' ' : ' M');
	}
	if ($number >= $step**1) {
		return str_replace('.',_x('.', 'decimal separator', 'my-transit-lines'), round($number/$step**1,3)).($word_prefix ? ' '.__('thousand', 'my-transit-lines').' ' : ' k');
	}
	if ($number == 0) {
		return '0 ';
	}
	if ($number < $step**-2) {
		return str_replace('.',_x('.', 'decimal separator', 'my-transit-lines'), round($number/$step**-2,3)).' n';
	}
	if ($number < $step**-1) {
		return str_replace('.',_x('.', 'decimal separator', 'my-transit-lines'), round($number/$step**-1,3)).' µ';
	}
	if ($number < $step**0) {
		return str_replace('.',_x('.', 'decimal separator', 'my-transit-lines'), round($number/$step**0,3)).' m';
	}
	return str_replace('.',_x('.', 'decimal separator', 'my-transit-lines'), round($number,3)).' ';
}
