<?php

/**
 * My Transit Lines
 * Tag adding module
 *
 * @package My Transit Lines
 */

/* created by Jan Garloff, 2024-04-22 */

/**
 * shortcode [mtl-tag-adding]
 */
function mtl_tag_adding_output($atts) {
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

	$output = '';

	if (!$the_query->have_posts())
		return $output;

	$the_query->the_post();
	global $post;

	$output .= '<script id="mtl-data-script" type="text/javascript"> var data = "' . str_replace(array("\r", "\n"), "", get_post_meta($post->ID, 'mtl-feature-data', true)) . '";</script>';

	if (!isset($_POST['no-reload']) || !$_POST['no-reload']) {
		$output .= '<script id="mtl-source-script" type="text/javascript"> var countrySource = \'' . str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-country-source'])) . '\';' . "\r\n";
		$output .= 'var stateSource = \'' . str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-state-source'])) . '\';' . "\r\n";
		$output .= 'var districtSource = \'' . str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-district-source'])) . '\'; </script>' . "\r\n";
	
		wp_enqueue_script('mtl-tag-adding', get_template_directory_uri().'/modules/mtl-tag-adding/mtl-tag-adding.js', array('openlayers'), wp_get_theme()->version, true);
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
add_shortcode( 'mtl-tag-adding', 'mtl_tag_adding_output' );
