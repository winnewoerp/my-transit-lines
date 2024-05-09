<?php

/**
 * My Transit Lines
 * Updating old proposals module
 *
 * @package My Transit Lines
 */

/* created by Jan Garloff, 2024-04-22 */

/**
 * shortcode [mtl-update-old-proposals]
 */
function mtl_update_old_proposals_output($atts) {

	$mtl_options3 = get_option('mtl-option-name3');

	if (current_user_can('administrator') && isset($_POST['mtl-id']) && isset($_POST['tags']) && get_post_type($_POST['mtl-id']) == 'mtlproposal' && !get_the_tags($_POST['mtl-id'])) {

		wp_add_post_tags($_POST['mtl-id'], $_POST['tags']);
	}

	// Query for proposals without tags
	$tags = get_terms('post_tag', array('fields' => 'ids'));
	$args = array(
		'post_type' => 'mtlproposal',
		'posts_per_page' => -1,
		'tax_query' => array(
			array(
				'taxonomy' => 'post_tag',
				'field' => 'id',
				'terms' => $tags,
				'operator' => 'NOT IN'
			)
		)
	);
	$the_query = new WP_Query($args);

	if (!$the_query->have_posts())
		return mtl_update_old_proposals_serverside();

	$the_query->the_post();
	global $post;

	$output = '<script id="mtl-data-script" type="text/javascript"> var data = "' . str_replace(array("\r", "\n"), "", get_post_meta($post->ID, 'mtl-feature-data', true)) . '";</script>';

	if (!isset($_POST['no-reload']) || !$_POST['no-reload']) {
		$output .= mtl_update_old_proposals_serverside();

		$output .= '<script id="mtl-source-script" type="text/javascript"> var countrySource = \'' . str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-country-source'])) . '\';' . "\r\n";
		$output .= 'var stateSource = \'' . str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-state-source'])) . '\';' . "\r\n";
		$output .= 'var districtSource = \'' . str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-district-source'])) . '\'; </script>' . "\r\n";
	
		wp_enqueue_script('mtl-tag-adding', get_template_directory_uri().'/modules/mtl-update-old-proposals/mtl-update-old-proposals.js', array('openlayers'), wp_get_theme()->version, true);
	}

	$output .= '<strong><a id="post-title" href="'.get_permalink($post->ID).'">'.$post->post_title.'</a></strong>';
	$output .= '<div class="post-tags">';
	$output .= '<h3>'.__('All administrative subdivisons of this proposal:','my-transit-lines').'</h3>';
	$output .= '<ul id="list-start">';
	$output .= '</ul>';
	$output .= '</div>';

	$output .= '<form id="add_tags" name="add_tags" method="post" action="" enctype="multipart/form-data"><input type="hidden" id="id_input" name="mtl-id" value="'.$post->ID.'" /><input type="hidden" name="no-reload" id="no-reload" value="" /><input type="hidden" name="tags" id="tags_input" /><input type="submit" id="form-submit" value="Add tags" /></form>';

	return $output;
}
add_shortcode( 'mtl-update-old-proposals', 'mtl_update_old_proposals_output' );

/**
 * Updates all old proposals that only need serverside updating
 * 
 * @return string html containing how many proposals were updated
 */
function mtl_update_old_proposals_serverside() {
	$count = 0;

	$count += mtl_modify_meta_key('mtlproposal', 1000, 'mtl-under-construction', 'mtl_under_construction_deletion_callback');

	$count += mtl_modify_meta_key('mtlproposal', 1000, 'mtl-proposal-phase', 'mtl_proposal_phase_deletion_callback');

	return '<p>'.$count.' proposal(s) were updated server-side</p>';
}

function mtl_under_construction_deletion_callback($post, $key) {
	wp_update_post( array(
		'ID' => $post->ID,
		'post_status' => 'draft',
	) );

	delete_post_meta($post->ID, $key);
}

function mtl_proposal_phase_deletion_callback($post, $key) {
	if (get_post_meta($post->ID, $key, true) == 'elaboration-phase')
		wp_update_post( array(
			'ID' => $post->ID,
			'post_status' => 'draft',
		) );
	
	delete_post_meta($post->ID, $key);
}

/**
 * Modify the specified meta key from all posts of the specified type
 *
 * @param string $post_type which post type to target
 * @param int $max_num how many posts to target max. -1 for no limit
 * @param string $key which key to modify
 * @param callable $callback will be called on every post with that meta key
 * 
 * @return int how many posts were modified
 */
function mtl_modify_meta_key($post_type, $max_num, $key, $callback = null) {
	$count = 0;

	$args = array(
		'post_type' => $post_type,
		'posts_per_page' => $max_num,
		'meta_key' => $key
	);
	$the_query = new WP_Query($args);

	while($the_query->have_posts()) : $the_query->the_post(); global $post;
		if ($callback)
			call_user_func($callback, $post, $key);

		$count++;
	endwhile;
	wp_reset_postdata();

	return $count;
}
