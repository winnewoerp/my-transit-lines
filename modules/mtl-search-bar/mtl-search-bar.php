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
	$mtl_options = get_option('mtl-option-name');

	$mtl_all_catids = '';
	foreach(get_categories() as $category) {
        if($mtl_options['mtl-use-cat'.$category->cat_ID])
            $mtl_all_catids .= $category->cat_ID.',';
    }
		
	// filter start
	$output .= '<div id="mtl-list-filter"><form name="mtl-filter-form" id="mtl-filter-form" method="get" action="'.get_permalink().'"><p><strong>'.__('Filter:','my-transit-lines').'</strong> ';

	// transit mode selector
	$output .= '<select name="mtl-catid">'."\r\n".'<option value="all">'.__('All transit modes','my-transit-lines').' </option>';
	foreach(get_categories('include='.$mtl_all_catids) as $single_category) {
		$catid = $single_category->cat_ID;
		$output .= '<option value="'.$catid.'"'.($catid === get_query_cats() ? ' selected="selected"' : '').'>'.$single_category->name.' </option>'."\r\n";
	}
	$output .= '</select>';
	
	// user selector
	$output .= '<select name="mtl-userid">'."\r\n".'<option value="all">'.__('All users (incl. unregistered)','my-transit-lines').' </option>';
	foreach(get_users('orderby=display_name') as $bloguser) {
		$output .= '<option value="'.$bloguser->ID.'"'.($bloguser->ID == get_userid() ? ' selected="selected"' : '').'>'.$bloguser->display_name.' </option>'."\r\n";
	}
	$output .= '</select></p>';

	// Sorting phase status selector
	$output .= '<p><strong>'.__('Sorting Phase Status:','my-transit-lines').'</strong>';
	$output .= '<select name="mtl-statusid">'."\r\n";
	$output .= '<option value="all">'.__('All statuses','my-transit-lines').' </option>';
	foreach( get_terms(array('taxonomy' => 'sorting-phase-status', 'hide_empty' => false)) as $single_status) {
		$statusid = $single_status->term_id;
		$output .= '<option value="'.$statusid.'"'.($statusid === get_query_statusid() ? ' selected="selected"' : '').'>'.$single_status->name.' </option>'."\r\n";
	}
	$output .= '</select></p>';

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
		$output .= '<option selected="selected" value="'.$posts_per_page.'">'.($posts_per_page > -1 ? $posts_per_page : 'all').'</option>';
	}
	foreach ($amounts as $amount) {
		$output .= '<option '.($posts_per_page == $amount ? ' selected="selected"' : '').' value="'.$amount.'">'.$amount.'</option>';
	}
	$output .= ' </select></p>';

	$output .= '<p><strong>'.__('Search:','my-transit-lines').'</strong><input type="search" name="search" value="'.get_search_term().'">';

	$output .= '<button type="submit">'.__('Filter/sort','my-transit-lines').'</button></p></form></div>'."\r\n";

	$output .= get_paginate_links($query->max_num_pages);

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
	return ('<div class="mtl-paginate-links">'.
	paginate_links( array(
		'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
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
        'cat' => get_query_cats(),
        'author' => get_userid(),
        's' => get_search_term(),
        'post_status' => get_status(),
        'orderby' => get_orderby(),
        'order' => get_order(),
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        'tax_query' => get_taxonomy_status(),
		'post__in' => get_post_in(),
		'tag__in' => get_tag_in(),
    );

    if(!show_drafts()) {
        $query_string['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key'     => 'mtl-proposal-phase',
                'value'   => 'elaboration-phase',
                'compare' => '!='
            ),
            array(
                'key'     => 'mtl-proposal-phase',
                'compare' => 'NOT EXISTS'
            ),
        );
    }

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
 * Returns the categories to query for
 *
 * @return string
 */
function get_query_cats() {
	if(isset($_GET['mtl-catid']) && $_GET['mtl-catid'] != 'all')
		return $_GET['mtl-catid'];
	
	// get categories from mtl theme options
	$query_cats = '';
	foreach(get_categories() as $category) {
        if(get_option('mtl-option-name')['mtl-use-cat'.$category->cat_ID])
            $query_cats .= $category->cat_ID.',';
    }
	
	return $query_cats;
}

/**
 * Returns the user id to query for
 *
 * @return int|string
 */
function get_userid() {
	if(isset($_GET['mtl-userid']))
		return intval($_GET['mtl-userid']);

	return '';
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
	if(isset($_GET['mtl-statusid']) && $_GET['mtl-statusid'] != 'all') {
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
	if(isset($_GET['mtl-statusid']) && $_GET['mtl-statusid'] != 'all')
		return intval($_GET['mtl-statusid']);
	
	return '';
}

/**
 * Returns the list of ids to search in
 *
 * @return array|null
 */
function get_post_in() {
	if(isset($_GET['mtl-post-ids']) && $_GET['mtl-post-ids'] != 'all')
		return explode(",", $_GET['mtl-post-ids']);

	return null;
}

/**
 * Returns the list of tags to search in
 *
 * @return array|null
 */
function get_tag_in() {
	if(isset($_GET['mtl-tag-ids']) && $_GET['mtl-tag-ids'] != 'all')
		return explode(",", $_GET['mtl-tag-ids']);

	return null;
}

?>