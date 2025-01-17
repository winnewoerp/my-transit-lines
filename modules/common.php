<?php
/**
 * My Transit Lines
 * Common functions for all modules
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2024-06-25 */

// Implement own array_any if using PHP < 8.4
if ( !function_exists('array_any') ) {
	function array_any( $array, $callback ) {
		foreach ($array as $key => $value) {
			if ($callback($value, $key))
				return true;
		}
		return false;
	}
}

// Implement own array_all if using PHP < 8.4
if ( !function_exists('array_all') ) {
	function array_all( $array, $callback ) {
		foreach ($array as $key => $value) {
			if (!$callback($value, $key))
				return false;
		}
		return true;
	}
}

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
 * @param int $revision the meta revision of the proposal
 * @return string the JSON data or the empty string if the given post_id was invalid
 */
function get_proposal_data_json($post_id, $revision = -1) {
	if (!is_numeric($post_id) || !get_post_status($post_id))
		return '';

	if (!is_numeric($revision) || $revision < 0 || $revision >= get_post_meta( $post_id, 'mtl-meta-revision', true ) || !current_user_can('administrator')) {
		$features = str_replace(["\n", "\r", "\\"], "", get_post_meta($post_id, 'mtl-features', true));
		$revision = 'current';
	} else {
		$features = str_replace(["\n", "\r", "\\"], "", get_post_meta($post_id, '_'.$revision.'mtl-features', true));
	}

	$author = get_the_author_meta('display_name', get_post_field ('post_author', $post_id));
	$title = get_the_title($post_id);
	$date = get_the_date('d.m.Y', $post_id);
	$link = get_permalink_or_edit($post_id);
	$catid = get_the_category($post_id)[0]->term_id;
	$status = get_post_status($post_id);

	return '{"id":'.$post_id.',"author":"'.$author.'","title":"'.$title.'","date":"'.$date.'","link":"'.$link.'","category":'.$catid.',"features":"'.$features.'","status":"'.$status.'","revision":"'.$revision.'"}';
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

/**
 * Returns the title of the post with a draft/pending flag added if necessary
 */
function get_the_title_flags($post_id = -1) {
	if ($post_id === -1) {
		global $post;
		$post_id = $post->ID;
	}

	$post_title = get_the_title($post_id);

	if (get_post_status($post_id) == 'draft' && get_post_type($post_id) == 'mtlproposal') {
		return '<span class="flag">'.esc_html__('Draft','my-transit-lines').'</span> '.$post_title;
	}
	if (get_post_status($post_id) == 'pending' && get_post_type($post_id) == 'mtlproposal') {
		return '<span class="flag">'.esc_html__('Pending','my-transit-lines').'</span> '.$post_title;
	}
	return $post_title;
}

/**
 * Returns the permalink for the post, or if a user is accessing his draft, an edit link
 */
function get_permalink_or_edit($post_id = -1) {
	if ($post_id == -1) {
		global $post;
		$post_id = $post->ID;
	}

	if (get_post_status($post_id) === 'draft' && get_post_field('post_author', $post_id) == get_current_user_id()) {
		return get_permalink(pll_get_post(get_option('mtl-option-name')['mtl-addpost-page'])).'?edit_proposal='.$post_id;
	} else {
		return get_permalink();
	}
}

/**
 * Returns true iff the current user should be able to see districts
 */
function districts_enabled() {
	switch (get_option('mtl-option-name3')['mtl-show-districts']) {
		case 'all':
			return true;
		case 'admin':
			return current_user_can('administrator');
		default:
			return false;
	}
}

define("CUSTOM_TRIM_DEFAULT_REMOVE", [" ", "\n", "\r", "\t", "\v", "\x00"]);
/**
 * Returns the given haystack with all instances of $needles removed from the start and end.
 * If any needle is the prefix of any other needle this might not work correctly.
 */
function custom_trim(string $haystack, array $needles = CUSTOM_TRIM_DEFAULT_REMOVE): string {
	$changed = true;
	while ($changed) {
		$changed = false;
		foreach ($needles as $needle) {
			if (str_starts_with($haystack, $needle)) {
				$haystack = substr($haystack, strlen($needle));
				$changed = true;
			}
			if (str_ends_with($haystack, $needle)) {
				$haystack = substr($haystack, 0, -strlen($needle));
				$changed = true;
			}
		}
	}
	return $haystack;
}
