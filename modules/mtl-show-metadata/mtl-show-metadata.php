<?php
/**
 * My Transit Lines
 * Proposal search bar
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2023-10-14 */

function mtl_show_metadata_output($atts) {
	$mtl_options3 = get_option('mtl-option-name3');

	$a = shortcode_atts( 
		array(
			'id' => '',
			'format_string' => $mtl_options3['mtl-proposal-metadata-contents'],
		),
		$atts
	);

	if (!$a['id'])
		return;

	$id = $a['id'];

	// output the meta data
	$output = '<h2>'.__('Metadata for this proposal','my-transit-lines').'</h2>'."\r\n";
	$output .= '<p class="mtl-metadata">';
	$output .= str_replace(array('[post-category]', '[post-length]', '[post-station-count]', '[post-station-distance]', '[post-costs]'), array(get_category_name($id), get_line_length($id), get_count_stations($id), get_average_distance($id), get_cost($id)), $a['format_string']);
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

	return str_replace('.',',', format_unit_prefix($lineLength, false).'m');
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

	return str_replace('.',',', format_unit_prefix($average_distance, false).'m');
}

function get_cost($id) {
	$costs = max(get_post_meta($id,'mtl-costs',true), 0);

	return str_replace('.',',',  format_unit_prefix($costs * 1E6, true).get_option('mtl-option-name3')['mtl-currency-symbol']);
}

/**
 * Format the number with its unit prefix
 * Examples: 	format_unit_prefix(1250) == '1.25k'
 * 				format_unit_prefix(1.25E6, true) == '1.25 million ';
 * 
 * @param [number] $number the number to format
 * @param [bool] $word_prefix whether to use words (million, billion, ...) or short prefixes (k, M, G) for numbers larger than 1000
 * @return string
 */
function format_unit_prefix($number, $word_prefix) {
	if ($number >= 1E9) {
		return round($number/1E9,3).($word_prefix ? __(' billion ', 'my-transit-lines') : ' G');
	}
	if ($number >= 1E6) {
		return round($number/1E6,3).($word_prefix ? __(' million ', 'my-transit-lines') : ' M');
	}
	if ($number >= 1E3) {
		return round($number/1E3,3).($word_prefix ? __(' thousand ', 'my-transit-lines') : ' k');
	}
	if ($number == 0) {
		return $number.' ';
	}
	if ($number < 1E-6) {
		return round($number*1E9,3).' n';
	}
	if ($number < 1E-3) {
		return round($number*1E6,3).' µ';
	}
	if ($number < 1) {
		return round($number*1E3,3).' m';
	}
	return round($number,3).' ';
}
