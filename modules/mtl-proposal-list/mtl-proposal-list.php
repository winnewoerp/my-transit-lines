<?php
/**
 * My Transit Lines
 * Proposal list, featuring several tabs displaying the proposals in different ways
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2024-08-16 */

/**
 * shortcode [mtl-proposal-list]
 */
function mtl_proposal_list_output($atts) {
	// use transient caching when requesting the list without parameters
	if (!$_GET && !$atts && !current_user_can( 'administrator' )) {
		$output = get_transient( 'mtl-proposal-list-cached' );

		if ($output)
			return $output;
		
		unset( $output );
	}

    // get the mtl options
	$mtl_options = get_option('mtl-option-name');

	extract( shortcode_atts( [
		'show_tabs' => 'tiles,list,map',
		'hide_search_bar' => false,
		'statusid_query' => 0,
		'posts_per_page' => 23,
		'proposal_ids' => '',
		'center_lon' => $mtl_options['mtl-center-lon'],
		'center_lat' => $mtl_options['mtl-center-lat'],
		'standard_zoom' => $mtl_options['mtl-standard-zoom'],
	], $atts ) );

	$show_tabs = explode(',', $show_tabs);

	if ($statusid_query) {
		$the_query = new WP_Query(array(
			'posts_per_page' => $posts_per_page,
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
		$the_query = get_query(4);
	}

	$selected_tab = get_selected_tab($show_tabs);

	$output = '';

	if (!$hide_search_bar) {
		$output .= mtl_search_bar_output($the_query);
	}
	$output .= get_paginate_links($the_query->max_num_pages);

	$newProposalText = __('Loading new set of proposals...','my-transit-lines');

	if (count($show_tabs) > 1) {
		// Start the tab switcher
		$output .= '
		<div id="mtl-tab-selector" class="mtl-tab-selector">'.
			(in_array('tiles', $show_tabs) ?
			'<button title="'.__('tiles','my-transit-lines').'" id="mtl-tab-selector-tiles" class="mtl-tab-selector-option'.($selected_tab == "tiles" ? '' : ' unselected').'">
				<img src="'.get_template_directory_uri().'/images/tiles.svg" height="40" width="40" alt="'.__('tiles','my-transit-lines').'">
			</button>' : '').
			(in_array('list', $show_tabs) ?
			'<button title="'.__('list','my-transit-lines').'" id="mtl-tab-selector-list" class="mtl-tab-selector-option'.($selected_tab == "list" ? '' : ' unselected').'">
				<img src="'.get_template_directory_uri().'/images/list.svg" height="40" width="40" alt="'.__('list','my-transit-lines').'">
			</button>' : '').
			(in_array('map', $show_tabs) ?
			'<button title="'.__('map','my-transit-lines').'" id="mtl-tab-selector-map" class="mtl-tab-selector-option'.($selected_tab == "map" ? '' : ' unselected').'">
				<img src="'.get_template_directory_uri().'/images/map.svg" height="40" width="40" alt="'.__('map','my-transit-lines').'">
			</button>' : '').
		'</div>';
	}

	$output .= '<div id="mtl-tab-box" class="mtl-tab-box">
		<div style="display:none" class="mtl-list-loader">'.$newProposalText.'</div>
		<div style="display:none" class="mtl-list-loader bottom">'.$newProposalText.'</div>

		<script type="text/javascript">
			var themeUrl = "'. get_template_directory_uri() .'";
			var centerLon = "'.$center_lon.'";
			var centerLat = "'.$center_lat.'";
			var standardZoom = "'.$standard_zoom.'";
			var multipleMode = true;
			var editMode = false;
			var showLabels = false;
		</script>'.
		get_transport_mode_style_data().
		mtl_localize_script(true).
		'<script data-mtl-replace-with="#mtl-tile-list-data-script" data-mtl-data-script id="mtl-tile-list-data-script" type="application/json">
			{"proposalList":'.get_all_proposal_data_json($the_query).'}
		</script>'.
		
		(in_array('tiles',$show_tabs) ? '<div id="mtl-tab-tiles" class="mtl-tab'.($selected_tab == "tiles" ? '' : ' unselected').'">
			<div data-mtl-replace-with="#mtl-posttiles-list" id="mtl-posttiles-list" class="mtl-posttiles-list">'.
				tiles_output($the_query).
			'</div>
		</div>' : '').
		(in_array('list',$show_tabs) ? '<div id="mtl-tab-list" class="mtl-tab'.($selected_tab == "list" ? '' : ' unselected').'">
			<div data-mtl-replace-with="#mtl-postlist" id="mtl-postlist" class="mtl-postlist">'.
				list_output($the_query).
			'</div>
		</div>' : '').
		(in_array('map',$show_tabs) ? '<div id="mtl-tab-map" class="mtl-tab'.($selected_tab == "map" ? '' : ' unselected').'">'.
			map_output().
		'</div>' : '').
	'</div>'.
	get_paginate_links($the_query->max_num_pages).
	(count($show_tabs) > 1 ? '<p>
		Uicons by <a href="https://www.flaticon.com/uicons">Flaticon</a>
	</p>' : '');

	// Cache proposal list when requesting the list without parameters
	if (!$_GET && !$atts && !current_user_can( 'administrator' )) {
		set_transient( 'mtl-proposal-list-cached', $output, 60 * 60 );
	}

	return $output;
}
add_shortcode( 'mtl-proposal-list', 'mtl_proposal_list_output' );

function mtl_save_post_remove_cache($post_id, $post) {
	if ($post->post_status === 'publish' && $post->post_type === 'mtlproposal') {
		delete_transient( 'mtl-proposal-list-cached' );
	}
}
add_action('save_post_mtlproposal', 'mtl_save_post_remove_cache', 10, 2);
add_action('delete_post', 'mtl_save_post_remove_cache', 10, 2);

function mtl_save_comment_remove_cache($comment_id, $comment) {
	$post = get_post($comment->comment_post_ID);

	if ($post && $post->post_status === 'publish' && $post->post_type === 'mtlproposal' && $comment->comment_approved === '1') {
		delete_transient( 'mtl-proposal-list-cached' );
	}
}
add_action('wp_insert_comment', 'mtl_save_comment_remove_cache', 10, 2);
add_action('delete_comment', 'mtl_save_comment_remove_cache', 10, 2);

function mtl_publish_comment_remove_cache($new_status, $old_status, $comment) {
	$post = get_post($comment->comment_post_ID);

	if ($post && $post->post_status === 'publish' && $post->post_type === 'mtlproposal' && $comment->comment_approved === '1') {
		delete_transient( 'mtl-proposal-list-cached' );
	}
}
add_action('transition_comment_status', 'mtl_publish_comment_remove_cache', 10, 3);

function tiles_output($the_query) {
	$tiles = add_proposal_tile();

	// loop through the tiles
	while($the_query->have_posts()) : $the_query->the_post();
		$tiles .= proposal_tile();
	endwhile;
	wp_reset_postdata();
	$the_query->rewind_posts();

	return $tiles;
}

/**
 * Returns the tile for the addpost page
 * @return string
 */
function add_proposal_tile() {
	$mtl_options = get_option('mtl-option-name');

	if (!$mtl_options['mtl-addpost-page'])
		return '';

	return '
	<div class="mtl-post-tile add-post">
		<div class="entry-thumbnail placeholder"></div>
		<h1>
			<a href="'.get_permalink(pll_get_post($mtl_options['mtl-addpost-page'])).'">'.
				__('Add a new proposal with map and description','my-transit-lines').
			'</a>
		</h1>
		<div class="entry-meta">'.
			__('Contribute to the collection!','my-transit-lines').
		'</div>
	</div>';
}

/**
 * Returns the tile for the $post global. Needs to be used in the loop
 * @return string
 */
function proposal_tile() {
	global $post;

	$color = get_option('mtl-option-name')['mtl-color-cat'.get_the_category($post->ID)[0]->term_id];

	return '
	<div class="mtl-post-tile" style="background-color:'.$color.'">
		<div id="tiles-map'.$post->ID.'" class="mtl-thumblist-map"></div>
		<article>
			<div class="entry-thumbnail placeholder"></div>
			<header>
				<h1 class="entry-title">
					<a href="'.get_permalink_or_edit().'" rel="bookmark" title="'.get_the_title().'">'.get_the_title_flags().'</a>
				</h1>
			</header>
			<footer class="entry-footer">
				<div class="entry-meta">'.
					mtl_posted_on_list(true).
				'</div>
				<span class="comments-link">
					<strong>'.
					mtl_get_output(function() {
						comments_popup_link( __( 'Leave a comment', 'my-transit-lines' ), __( '1 Comment', 'my-transit-lines' ), __( '% Comments', 'my-transit-lines' ) );
					}).
					'</strong>
				</span>'.
				get_edit_post_link_checked(' class="edit-link"').
			'</footer>
		</article>
	</div>';
}

function list_output($the_query) {
	$list_items = add_proposal_list_item();

	// loop through the tiles
	while($the_query->have_posts()) : $the_query->the_post();
		$list_items .= proposal_list_item();
	endwhile;
	wp_reset_postdata();
	$the_query->rewind_posts();

	return $list_items;
}

/**
 * Returns the list item for the addpost page
 * @return string
 */
function add_proposal_list_item() {
	$mtl_options = get_option('mtl-option-name');

	if (!$mtl_options['mtl-addpost-page'])
		return '';

	return '
	<article>
		<a href="'.get_permalink(pll_get_post($mtl_options['mtl-addpost-page'])).'" rel="bookmark" title="'.get_the_title(pll_get_post($mtl_options['mtl-addpost-page'])).'" class="mtl-postlist-item add-post">
			<img src="'.get_template_directory_uri().'/images/add-proposal.png" height="200" width="200">
			<div class="mtl-list-content">
				<h1>'
					.__('Add a new proposal with map and description','my-transit-lines').
				'</h1>'.
				__('Contribute to the collection!','my-transit-lines').
			'</div>
		</a>
	</article>';
}

/**
 * Returns the list item for the $post global. Needs to be used in the loop
 * @return string
 */
function proposal_list_item() {
	global $post;

	$color = get_option('mtl-option-name')['mtl-color-cat'.get_the_category($post->ID)[0]->term_id];

	return '
	<article class="mtl-postlist-item" style="background-color:'.$color.'">
		<a href="'.get_permalink_or_edit().'" title="'.get_the_title().'" rel="bookmark" class="mtl-list-map-link">
			<div id="list-map'.$post->ID.'" class="mtl-list-map"></div>
		</a>
		<div id="list-content"'.$post->ID.'" class="mtl-list-content">
			<header>
				<a href="'.get_permalink_or_edit().'" title="'.get_the_title().'" rel="bookmark" class="mtl-list-content-link"></a>
				<h1>'.
					get_the_title_flags().
				'</h1>
			</header>
			<div class="the_content">'.
			get_the_content('<br>'.__("Read more",'my-transit-lines'), true).
			'</div>
			<footer>'.
				mtl_posted_on_list(true).
				'<br>
				<strong>'.
					mtl_get_output(function() {
						comments_popup_link( __( 'Leave a comment', 'my-transit-lines' ), __( '1 Comment', 'my-transit-lines' ), __( '% Comments', 'my-transit-lines' ) );
					}).
				'</strong>'.
				get_edit_post_link_checked("", " - ").
			'</footer>
		</div>
	</article>';
}

function map_output() {
	return the_map_output().
	'<div id="popup" class="ol-popup" style="display:none;">
		<a href="#" id="popup-closer" class="ol-popup-closer"></a>
		<div id="popup-content" class="ol-popup-content">
			<a id="popup-content-link" href="">
				<span id="popup-content-draft-flag" class="flag" style="display:none;">'.esc_html__('Draft','my-transit-lines').'</span>
				<span id="popup-content-pending-flag" class="flag" style="display:none;">'.esc_html__('Pending','my-transit-lines').'</span>
				<b id="popup-content-title"></b>
			</a>
			<br>
			<span>'.
				__('By', 'my-transit-lines').
				' <span id="popup-content-author"></span>'.
				__('on', 'my-transit-lines').
				' <span id="popup-content-date"></span>
			</span>
		</div>
	</div>';
}

/**
 * Returns the output link in a <span> container iff the user is an admin
 */
function get_edit_post_link_checked($container_attributes = "", $pre_link_text = "") {
	global $post;

	if (!current_user_can('administrator'))
		return '';

	return '
	<span'.$container_attributes.'>
		'.$pre_link_text.'<a class="post-edit-link" href="'.get_edit_post_link().'">'.__('Edit','my-transit-lines').'</a>
	</span>';
}

/**
 * Returns the tab selected by the user, or the first specified, shown tab
 */
function get_selected_tab($shown_tabs = ['tiles','list','map']) {
	if (isset($_GET['mtl-tab']) && $_GET['mtl-tab'] != "")
		return $_GET['mtl-tab'];

	return $shown_tabs[0];
}
