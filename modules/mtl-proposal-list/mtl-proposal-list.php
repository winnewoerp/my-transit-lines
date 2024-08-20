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
    // get the mtl options
	$mtl_options = get_option('mtl-option-name');

	extract( shortcode_atts( [
		'type' => 'mtlproposal',
	], $atts ) );

	$the_query = get_query(4);

	$selected_tab = get_selected_tab();

	$output = mtl_search_bar_output($the_query);

	wp_enqueue_script('mtl-proposal-list', get_template_directory_uri().'/modules/mtl-proposal-list/mtl-proposal-list.js', ['my-transit-lines'], wp_get_theme()->version);

	$newProposalText = __('Loading new set of proposals...','my-transit-lines');

	// Start the tab switcher
	$output .= '
	<div id="mtl-tab-selector" class="mtl-tab-selector">
		<button id="mtl-tab-selector-tiles" class="mtl-tab-selector-option'.($selected_tab == "tiles" ? '' : ' unselected').'">
			<img src="'.get_template_directory_uri().'/images/tiles.svg" height="40" width="40" alt="'.__('tiles','my-transit-lines').'">
		</button>
		<button id="mtl-tab-selector-list" class="mtl-tab-selector-option'.($selected_tab == "list" ? '' : ' unselected').'">
			<img src="'.get_template_directory_uri().'/images/list.svg" height="40" width="40" alt="'.__('list','my-transit-lines').'">
		</button>
		<button id="mtl-tab-selector-map" class="mtl-tab-selector-option'.($selected_tab == "map" ? '' : ' unselected').'">
			<img src="'.get_template_directory_uri().'/images/map.svg" height="40" width="40" alt="'.__('map','my-transit-lines').'">
		</button>
	</div>
	<div id="mtl-tab-box" class="mtl-tab-box">
		<div style="display:none" class="mtl-list-loader">'.$newProposalText.'</div>
		<div style="display:none" class="mtl-list-loader bottom">'.$newProposalText.'</div>

		<script type="text/javascript">
			var themeUrl = "'. get_template_directory_uri() .'";
			var centerLon = "'.$mtl_options['mtl-center-lon'].'";
			var centerLat = "'.$mtl_options['mtl-center-lat'].'";
			var standardZoom = "'.$mtl_options['mtl-standard-zoom'].'";
			var multipleMode = true;
			var editMode = false;
			var showLabels = false;
		</script>'.
		get_transport_mode_style_data().
		mtl_localize_script(true).
		'<script data-mtl-replace-with="#mtl-tile-list-data-script" data-mtl-data-script id="mtl-tile-list-data-script" type="application/json">
			{"proposalList":'.get_all_proposal_data_json($the_query).'}
		</script>

		<div id="mtl-tab-tiles" class="mtl-tab">
			<div data-mtl-replace-with="#mtl-posttiles-list" id="mtl-posttiles-list" class="mtl-posttiles-list">'.
				tiles_output($the_query).
			'</div>
		</div>
		<div id="mtl-tab-list" class="mtl-tab">
			<div data-mtl-replace-with="#mtl-postlist" id="mtl-postlist" class="mtl-postlist">'.
				list_output($the_query).
			'</div>
		</div>
		<div id="mtl-tab-map" class="mtl-tab">'.
			map_output().
		'</div>
	</div>'.
	get_paginate_links($the_query->max_num_pages).
	'<p>
		Uicons by <a href="https://www.flaticon.com/uicons">Flaticon</a>
	</p>';

	return $output;
}
add_shortcode( 'mtl-proposal-list', 'mtl_proposal_list_output' );

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
					<a href="'.get_permalink().'" rel="bookmark" title="'.get_the_title().'">'.get_the_title().'</a>
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
				</span>
				<span class="edit-link">
					<a class="post-edit-link" href="'.get_edit_post_link().'">'.__( 'Edit', 'my-transit-lines' ).'</a>
				</span>
			</footer>
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
		<a href="'.get_permalink().'" title="'.get_the_title().'" rel="bookmark" class="mtl-list-map-link">
			<div id="list-map'.$post->ID.'" class="mtl-list-map"></div>
		</a>
		<div id="list-content"'.$post->ID.'" class="mtl-list-content">
			<header>
				<a href="'.get_permalink().'" title="'.get_the_title().'" rel="bookmark" class="mtl-list-content-link"></a>
				<h1>'.
					get_the_title().
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
				'</strong>
				<span>
					 - <a class="post-edit-link" href="'.get_edit_post_link().'">'.__( 'Edit', 'my-transit-lines' ).'</a>
				</span>
			</footer>
		</div>
	</article>';
}

function map_output() {
	return '
	<div id="mtl-box">
		<div id="mtl-map-box">
			<div id="mtl-map"></div>
		</div>
		<div class="mtl-map-controls">
			<p id="map-color-opacity">
				<span id="mtl-colored-map-box">
					<label for="mtl-colored-map">
						<input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="toggleMapColors()"> '.
						__('colored map','my-transit-lines').
					'</label>
				</span>
				&nbsp;
				<span id="mtl-opacity-low-box">
					<label for="mtl-opacity-low">
						<input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="toggleMapOpacity()"> '.
						__('brightened map','my-transit-lines').
					'</label>
				</span>
			</p>
			<p id="zoomtofeatures" class="alignright" style="margin-top:-12px">
				<a href="javascript:zoomToFeatures()">'.
					__('Fit proposition to map','my-transit-lines').
				'</a>
			</p>
			<p class="alignright">
				<a id="mtl-fullscreen-link" href="javascript:toggleFullscreen()">
					<span class="fullscreen-closed">'.
						__('Fullscreen view','my-transit-lines').
					'</span>
					<span class="fullscreen-open">'.
						__('Close fullscreen view','my-transit-lines').
					'</span>
				</a>
			</p>
			<p class="alignright" id="mtl-toggle-labels">
				<label>
					<input type="checkbox" id="mtl-toggle-labels-link" onclick="toggleLabels()"> '.
					__('Show labels','my-transit-lines').
				'</label>
			</p>
		</div>
		<div id="popup" class="ol-popup" style="display:none;">
			<a href="#" id="popup-closer" class="ol-popup-closer"></a>
			<div id="popup-content" class="ol-popup-content">
				<a id="popup-content-link" href="">
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
		</div>
	</div>';
}

function get_selected_tab() {
	if (isset($_GET['mtl-tab']) && $_GET['mtl-tab'] != "")
		return $_GET['mtl-tab'];

	return 'tiles';
}
