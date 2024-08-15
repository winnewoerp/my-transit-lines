<?php
/**
 * My Transit Lines
 * Proposal search bar
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2023-10-14 */

/**
 * Outputs the search bar using the WP_Query passed.
 * If null or nothing is passed the query from get_query() is used
 *
 * @param WP_Query $query
 * @return string
 */
function mtl_search_bar_output($query = null) {
    if ($query == null) {
        $query = get_query();
    }

	$output = '';

    // get the mtl options
	$mtl_options3 = get_option('mtl-option-name3');

	wp_enqueue_script('mtl-search-bar', get_template_directory_uri().'/modules/mtl-search-bar/mtl-search-bar.js', array(), wp_get_theme()->version);

	// filter start
	$output .= '<div id="mtl-list-filter"><details id="mtl-filter-details">
	<summary>' . __('Search options','my-transit-lines') . '</summary>
	<form name="mtl-filter-form" id="mtl-filter-form" method="get" action="'.get_permalink().'">
	<input type="checkbox" id="mtl-filter-multiple">
	<label for="mtl-filter-multiple">' . __('Select multiple values','my-transit-lines') . '</label><hr>
	<p class="mtl-filter-section"><strong>'.__('Filter:','my-transit-lines').'</strong> ';

	$output .= multi_selector_output(get_query_cats(), array_map(function($cat) {
		return [
			'ID' => $cat->term_id,
			'name' => $cat->name,
		];
	}, get_searchable_categories()), 'mtl-catid', __('All transit modes','my-transit-lines'));

	$output .= multi_selector_output($query->query['author__in'], array_map(function($user) {
		return [
			'ID' => $user->ID,
			'name' => $user->display_name,
		];
	}, get_users('oderby=display_name')), 'mtl-userid', __('All users (incl. unregistered)','my-transit-lines'));

	// only show tag selector when enabled or for admins
	if ($mtl_options3['mtl-show-districts'] || current_user_can('administrator')) {
		$output .= multi_selector_output($query->query['tag__in'], array_map(function($tag) {
			return [
				'ID' => $tag->term_id,
				'name' => $tag->name,
			];
		}, get_tags()), 'mtl-tag-ids', __('All regions','my-transit-lines'));
	}

	if(current_user_can('administrator')) {
		$is_checked = in_array("draft", get_status());
		$output .= '
		<input id="mtl-show-drafts" name="show-drafts" value="'.($is_checked ? 'true' : 'false').'" autocomplete="off" type="checkbox" '.($is_checked ? 'checked' : '').' onchange="event.target.value = event.target.checked;">
		<label for="mtl-show-drafts">'.__('Show drafts', 'my-transit-lines').'</label>';
	}
	$output .= '</p>';

	$statuses = get_terms(array('taxonomy' => 'sorting-phase-status', 'hide_empty' => false));
	if (count($statuses) > 1) {
		// Sorting phase status selector
		$output .= '<p><strong>'.__('Sorting Phase Status:','my-transit-lines').'</strong>';
		$output .= '<select name="mtl-statusid">'."\r\n";
		$output .= '<option value="">'.__('All statuses','my-transit-lines').' </option>';
		foreach( $statuses as $single_status) {
			$statusid = $single_status->term_id;
			$output .= '<option value="'.$statusid.'"'.($statusid === get_query_statusid() ? ' selected="selected"' : '').'>'.$single_status->name.' </option>'."\r\n";
		}
		$output .= '</select></p>';
	}

    $order_by = get_orderby();
    $order = get_order();

	$output .= '<p><strong>'.__('Sort:','my-transit-lines').'</strong><select name="orderby">';
	$output .= '<option'.($order_by=='date' ? ' selected="selected"' : '').' value="date">'.__('Date','my-transit-lines').'</option>';
	$output .= '<option'.($order_by=='comment_count' ? ' selected="selected"' : '').' value="comment_count">'.__('Number of comments','my-transit-lines').'</option>';
	$output .= '<option'.($order_by=='rand' ? ' selected="selected"' : '').' value="rand">'.__('Random','my-transit-lines').'</option>';
	$output .= '</select><select name="order"><option'.($order=='DESC' ? ' selected="selected"' : '').' value="DESC">'.__('Descendent','my-transit-lines').'</option><option'.($order == 'ASC' ? ' selected="selected"' : '').' value="ASC">'.__('Ascendent','my-transit-lines').'</option></select>';

    $posts_per_page = get_posts_per_page();

	// Selector for amount of proposals shown
	$amounts = [25, 50, 100, 250];
	$output .= '<strong>'.__('Amount:','my-transit-lines').'</strong>';
	$output .= '<select name="num">';
	if (!in_array($posts_per_page, $amounts)) {
		$output .= '<option selected="selected" value="'.$posts_per_page.'">'.($posts_per_page > -1 ? $posts_per_page : __('all', 'my-transit-lines')).'</option>';
	}
	foreach ($amounts as $amount) {
		$output .= '<option '.($posts_per_page == $amount ? ' selected="selected"' : '').' value="'.$amount.'">'.$amount.'</option>';
	}
	$output .= ' </select></p>';

	$output .= '<p><strong>'.__('Search:','my-transit-lines').'</strong><input type="search" name="search" value="'.get_search_term().'">';

	$output .= '<button type="submit">'.__('Filter/sort','my-transit-lines').'</button></p></form></details></div>'."\r\n";

	$output .= get_paginate_links($query->max_num_pages);

    return $output;
}

/**
 * Returns the output for a multi-selector
 * @param array $queried_options array containing all searched for ids
 * @param array $all_options array containing associative arrays with ID and name fields
 * @param string $selector_name name for the selector. Must be a valid html name
 * @param string $all_selected_option text of the all_selected option
 * @return string
 */
function multi_selector_output($queried_options, $all_options, $selector_name, $all_selected_option) {
	$output = '';
	if (count($all_options) > 1) {
		$all_selected = empty($queried_options) || $queried_options == array_map(function($option) {
			return $option['ID'];
		}, $all_options);
	
		$multiple = count($queried_options) > 1 ? " multiple" : "";
		$selected = $all_selected ? " selected" : "";

		// selector
		$output .= "<select name=\"$selector_name\" class=\"allowsMultiple\" $multiple>\r\n
					<option value=\"\"$selected>$all_selected_option</option>\r\n";
		foreach($all_options as $option) {
			$id = $option['ID'];

			$selected = !$all_selected && in_array($id, $queried_options) ? " selected" : "";

			$output .= "<option value=\"$id\"$selected>{$option['name']}</option>\r\n";
		}
		$output .= "</select>";
	}
	return $output;
}

/**
 * Returns the paginate links
 *
 * @param int $max_num_pages
 * @return string
 */
function get_paginate_links($max_num_pages) {
	$big = 999999999; // need an unlikely integer
	return ('<div data-mtl-replace-with=".mtl-paginate-links" class="mtl-paginate-links">'.
	paginate_links( array(
		'base' => str_replace( $big, '%#%', get_pagenum_link( $big ) ),
		'format' => '?paged=%#%',
		'current' => max( 1, get_query_var('paged') ),
		'total' => $max_num_pages,
		'prev_text' => '',
		'next_text' => ''
	) ).'</div>');
}

/**
 * Returns the WP_Query determined by the $_GET arguments
 * 
 * @param string which post type will be queried
 * @param int which number the posts per page has to be a multiple of
 *
 * @return WP_Query
 */
function get_query($type = 'mtlproposal', $per_page_multiple = 1) {

    $posts_per_page = get_posts_per_page();
    
    $query_string = array(
        'posts_per_page' => max(($posts_per_page - (($posts_per_page + 1) % $per_page_multiple)), -1),
        'post_type' => $type,
        'author__in' => get_query_users(),
        'category__in' => get_query_cats_children(),
		'tag__in' => get_query_tags(),
        's' => get_search_term(),
        'post_status' => get_status(),
        'orderby' => get_orderby(),
        'order' => get_order(),
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        'tax_query' => get_taxonomy_status(),
		'post__in' => get_post_in(),
    );

    return new WP_Query($query_string);
}

/**
 * Returns all term ids of the given taxonomy except for the excluded one
 *
 * @param string $taxonomy
 * @param int $exclude_id
 * @return int[]
 */
function other_term_ids($taxonomy, $exclude_id) {
	$all_terms = get_terms( array(
		'taxonomy' => $taxonomy,
		'hide_empty' => false,
	));

	$term_ids = array();
	foreach ($all_terms as $current_term) {
		if ($current_term->term_id != $exclude_id)
			$term_ids[] = $current_term->term_id;
	}

	return $term_ids;
}

/**
 * Returns the amount of posts to be displayed per page
 * @return int
 */
function get_posts_per_page() {
	if (isset($_GET['num']))
		return intval($_GET['num']);

	return 25;
}

/**
 * Returns the categories to query for, including children of passed in categories
 *
 * @return array
 */
function get_query_cats_children() {
	if(isset($_GET['mtl-catid']) && $_GET['mtl-catid'] != '')
		return array_merge(...array_map(function($catid) {
			$ids = get_term_children($catid, 'category');
			$ids[] = $catid;
			return $ids;
		}, explode(',', $_GET['mtl-catid'])));

	return [];
}

/**
 * Returns the categories to query for without children
 * 
 * @return array
 */
function get_query_cats() {
	if(isset($_GET['mtl-catid']) && $_GET['mtl-catid'] != '')
		return explode(',', $_GET['mtl-catid']);

	return [];
}

/**
 * Returns the user id to query for
 *
 * @return array
 */
function get_query_users() {
	if(isset($_GET['mtl-userid']) && $_GET['mtl-userid'] != '')
		return explode(',', $_GET['mtl-userid']);

	return [];
}

/**
 * Returns the search term to query for
 *
 * @return string
 */
function get_search_term() {
    if(isset($_GET['search']))
        return $_GET['search'];
	
		return '';
}

/**
 * Returns the post status to query for
 *
 * @return array
 */
function get_status() {
	$status = array('publish');
    if(show_drafts()) {
        $status[] = 'draft';
    }
	
	return $status;
}

/**
 * Returns if drafts should be shown
 *
 * @return bool
 */
function show_drafts() {
	return isset($_GET['show-drafts']) && $_GET['show-drafts'] == 'true' && ((isset($_GET['mtl-userid']) && $_GET['mtl-userid'] == get_current_user_id()) || ( current_user_can( 'administrator' )));
}

/**
 * Returns the order to query for
 *
 * @return string
 */
function get_order() {
	if (isset($_GET['order']))
		return $_GET['order'];

	return 'DESC';
}

/**
 * Returns the orderby to query for
 *
 * @return string
 */
function get_orderby() {
	if (isset($_GET['orderby']))
		return $_GET['orderby'];

	return 'date';
}

/**
 * Returns the sorting phase status to query for
 *
 * @return array
 */
function get_taxonomy_status() {
	if(isset($_GET['mtl-statusid']) && $_GET['mtl-statusid'] != '') {
		$single_statusid = intval($_GET['mtl-statusid']);

		// if default status is searched for also find proposals without status
		if (get_term_by('id', $single_statusid, 'sorting-phase-status')->slug == get_taxonomy('sorting-phase-status')->default_term['slug']) {
			return array( array (
				'taxonomy' => 'sorting-phase-status',
				'terms' => other_term_ids('sorting-phase-status', $single_statusid),
				'operator' => 'NOT IN',
			));
		}

		return array( array (
			'taxonomy' => 'sorting-phase-status',
			'terms' => $single_statusid,
		),);
	}

	return array();
}

/**
 * Returns the sorting phase status id
 *
 * @return int|string
 */
function get_query_statusid() {
	if(isset($_GET['mtl-statusid']) && $_GET['mtl-statusid'] != '')
		return intval($_GET['mtl-statusid']);
	
	return '';
}

/**
 * Returns the list of ids to search in
 *
 * @return array
 */
function get_post_in() {
	if(isset($_GET['mtl-post-ids']) && $_GET['mtl-post-ids'] != '')
		return explode(",", $_GET['mtl-post-ids']);

	return array();
}

/**
 * Returns the list of tags to search in
 *
 * @return array
 */
function get_query_tags() {
	if(isset($_GET['mtl-tag-ids']) && $_GET['mtl-tag-ids'] != '')
		return explode(",", $_GET['mtl-tag-ids']);

	return [];
}
