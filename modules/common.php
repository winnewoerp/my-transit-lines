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
		$catname = __($cat->name,'my-transit-lines');
		$output .= "\"name\":\"$catname\",";
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

/**
 * Returns a JSON string with all the relevant data of the specified proposal
 * @param int $post_id the id of the proposal
 * @return string the JSON data
 */
function get_proposal_data_json($post_id) {
	$author = get_the_author_meta('display_name', get_post_field ('post_author', $post_id));
	$title = get_the_title($post_id);
	$date = get_the_date('d.m.Y', $post_id);
	$link = get_permalink($post_id);
	$catid = get_the_category($post_id)[0]->term_id;
	$features = str_replace(["\n", "\r", "\\"], "", get_post_meta($post_id, 'mtl-features', true));

	return '{"id":'.$post_id.',"author":"'.$author.'","title":"'.$title.'","date":"'.$date.'","link":"'.$link.'","category":'.$catid.',"features":"'.$features.'"}';
}

/**
 * Returns a JSON list string with all the relevant data of all proposals within the query
 * @param WP_Query $the_query
 * @return string the JSON list
 */
function get_all_proposal_data_json($the_query) {
	$proposalData = '';

	while($the_query->have_posts()) : $the_query->the_post(); global $post;
		$comma = isset($comma) ? ",\r\n" : "";

		$proposalData .= $comma.get_proposal_data_json($post->ID);
	endwhile;
	wp_reset_postdata();
	$the_query->rewind_posts();

	return "[$proposalData]";
}
