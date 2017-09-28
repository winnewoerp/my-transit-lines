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
		$featureData = get_post_meta($post->ID,'mtl-feature-data',true);
		if($featureData) {
			$featureLabelsData = get_post_meta($post->ID,'mtl-feature-labels-data',true);
			$output = '';
			$output .= '
		<script type="text/javascript">
			var feature_data_for_geojson = "'.$featureData.'";
			var feature_labels_data_for_geojson = "'.$featureLabelsData.'";
			var title_for_geojson = "'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes(get_the_title()))).'";
			var content_for_geojson = "'.str_replace("\r","\\r",str_replace("\n","\\n",addslashes(get_the_content()))).'";
			var author_for_geojson = "'.get_the_author().'";
			var date_for_geojson = "'.get_the_date().'";
			var website_for_geojson = "'.get_bloginfo('url').'";';
	$category = get_the_category($post->ID);
			$output .= '
			var category_for_geojson = "'.$category[0]->name.'";
			var license_link_for_geojson = "https://creativecommons.org/licenses/by-nc-sa/3.0/de/";
		</script>
		<script type="text/javascript" src="'.get_template_directory_uri().'/modules/mtl-download-geojson/mtl-download-geojson.js"></script>
		<p><a href="" download="'.$post->ID.'-'.$post->post_name.'.geojson" id="mtl-geojson-download">Streckendaten als GeoJSON-Datei herunterladen (Beta)</a></p>';
			$content .= $output;
		}
	}
	return $content;
}
add_filter('the_content','download_GeoJSON');