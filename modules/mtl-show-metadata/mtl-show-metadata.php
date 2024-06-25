<?php
/**
 * My Transit Lines
 * Proposal search bar
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
	$output .= '<p class="mtl-metadata">';
	$output .= str_replace($search, $replace, $a['format_string']);
	$output .= '</p>'."\r\n";
	if($mtl_options3['mtl-show-districts'] || current_user_can('administrator')) $output .= mtl_taglist();

	return $output;
}
add_shortcode( 'mtl-show-metadata', 'mtl_show_metadata_output' );

function get_category_name($id) {
	return get_the_category($id)[0]->name;
}

function get_line_length($id) {
	$lineLength = max(get_post_meta($id,'mtl-line-length',true), 0);

	return format_unit_prefix($lineLength, false).'m';
}

function get_count_stations($id) {
	return max(get_post_meta($id,'mtl-count-stations',true), 0);
}

function get_average_distance($id) {
	$line_length = max(get_post_meta($id,'mtl-line-length',true), 0);
	$count_stations = max(get_post_meta($id,'mtl-count-stations',true), 0);

	if (!$line_length || $count_stations < 2)
		return '0 m';

	$average_distance = $line_length / ($count_stations - 1);

	return format_unit_prefix($average_distance, false).'m';
}

function get_cost($id) {
	$costs = max(get_post_meta($id,'mtl-costs',true), 0);

	return format_unit_prefix($costs * 1E6, true).get_option('mtl-option-name3')['mtl-currency-symbol'];
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
