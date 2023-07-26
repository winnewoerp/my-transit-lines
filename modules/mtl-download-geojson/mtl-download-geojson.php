<?php
/**
 * My Transit Lines
 * Download GeoJSON module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2017-09-16 */

/* ### STILL TO DO ###
 * any suggestions?
 */
 
function download_GeoJSON($content) {
	global $post;

	if(is_single()) {
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
	<script type="text/javascript">
		var title_for_geojson = "'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes(get_the_title($postId)))).'";
		var content_for_geojson = "'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes(get_the_content(null, false, $postId)))).'";
		var author_for_geojson = "'.get_the_author($postId).'";
		var date_for_geojson = "'.get_the_date('', $postId).'";
		var website_for_geojson = "'.get_bloginfo('url').'";
		var category_for_geojson = "'.$category[0]->name.'";
		var license_link_for_geojson = "https://creativecommons.org/licenses/by-nc-sa/3.0/de/";
		</script>
		<p><a href="" download="'.$postId.'-'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes(get_the_title($postId)))).'.geojson" id="mtl-geojson-download">'.__('Download proposal map data as GeoJSON','my-transit-lines').'</a></p>
		<script type="text/javascript" src="'.get_template_directory_uri().'/modules/mtl-download-geojson/mtl-download-geojson.js"></script>';
	}

	return $output;
}