<?php
/**
 * My Transit Lines
 * Download GeoJSON module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2017-09-16 */

function download_GeoJSON($content) {
	global $post;

	if(is_single() && get_post_type() == "mtlproposal") {
		$content .= get_download_button($post->ID);
	} else if (is_page(get_option('mtl-option-name')['mtl-addpost-page'])) {
		$content .= get_download_button(get_editId());
	}

	return $content;
}
add_filter('the_content','download_GeoJSON');

function get_download_button($postId) {
	$output = '';

	$category = get_the_category($postId);
	if($category) {
		$output .= '
	<script data-mtl-data-script data-mtl-replace-with="#mtl-geojson-data" id="mtl-geojson-data" type="application/json">
		{"title_for_geojson": "'.str_replace(["[", "]"], ["[[", "]]"], addcslashes(get_the_title($postId), "\"\\\n\r\t")).'",
		"content_for_geojson": "'.str_replace(["[", "]"], ["[[", "]]"], addcslashes(get_the_content(null, false, $postId), "\"\\\n\r\t")).'",
		"author_for_geojson": "'.get_post_field( 'post_author', $postId ).'",
		"date_for_geojson": "'.get_the_date('', $postId).'",
		"website_for_geojson": "'.get_bloginfo('url').'",
		"category_for_geojson": "'.__($category[0]->name, 'my-transit-lines').'",
		"license_link_for_geojson": "https://creativecommons.org/licenses/by-nc-sa/3.0/de/"}
	</script>
	<script type="text/javascript" src="'.get_template_directory_uri().'/modules/mtl-download-geojson/mtl-download-geojson.js?ver='.wp_get_theme()->version.'" defer></script>'.
	'<p><a href="" download="'.$postId.'-'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes(get_the_title($postId)))).'.geojson" id="mtl-geojson-download">'.__('Download proposal map data as GeoJSON','my-transit-lines').'</a></p>';
	}

	return $output;
}