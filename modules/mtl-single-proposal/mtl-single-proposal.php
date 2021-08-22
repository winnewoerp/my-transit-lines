<?php
/**
 * My Transit Lines
 * Single proposal view module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */

/* ### STILL TO DO ###
 * I don't know, is there anything? Other custom stuff needed to be added to the single view?
 */

 /**
 * map and meta data for single proposal
 */
function mtl_proposal_map($content) {
	if(get_post_type()=='mtlproposal') {
	
		global $post;
		$output = '';
		$output2 = '';
		
		// get the mtl options
		$mtl_options = get_option('mtl-option-name');
		$mtl_options2 = get_option('mtl-option-name2');
		$mtl_options3 = get_option('mtl-option-name3');
		
		// do the meta data calculations
		$countStations = get_post_meta($post->ID,'mtl-count-stations',true);
		$lineLength = get_post_meta($post->ID,'mtl-line-length',true);
		if($lineLength>=1000) $lineLengthOutput = str_replace('.',',',round($lineLength/1000,3)).' km';
		else $lineLengthOutput = str_replace('.',',',round($lineLength,1)).' m';
		if($countStations > 1 && $lineLength) {
			$averageDistance = $lineLength/($countStations-1);
			if($averageDistance>=1000) $averageDistanceOutput = str_replace('.',',',round($averageDistance/1000,3)).' km';
			else $averageDistanceOutput = str_replace('.',',',round($averageDistance,1)).' m';
		}
		
		// get data of current category
		$category = get_the_category($post->ID);
		$catid = $category[0]->cat_ID;
		$category_slug = $category[0]->slug;
		$category_name = $category[0]->name;
		
		// load relevant scripts and set some JS variables
		$output .= "\r";
		$output .= '<div id="mtl-box">'."\r\n";
		$output .= '<script type="text/javascript"> var transportModeStyleData = {'.$catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"]}; </script>';
		$output .= '<script type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = "'.get_post_meta($post->ID,'mtl-feature-data',true).'"; var vectorLabelsData = "'.get_post_meta($post->ID,'mtl-feature-labels-data',true).'"; var editMode = false; </script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/openlayers/OpenLayers.js"></script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/Editor/Lang/de.js"></script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/loader.js"></script>'."\r\n";
		$output .= mtl_localize_script(true);
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>'."\r\n";
		
		// output the map box
		$output .= '<div id="mtl-map-box">'."\r\n";
		$output .= '<div id="mtl-map"></div>'."\r\n";
		$output .= '</div>';
		
		// output opacity change button and map fullscreen link
		$output .= '<p id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="setMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></p>'."\r\n";
		$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:mtlFullscreenMap()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
		$output .= '</div>'."\r\n";
		
		// show "under construction" text if proposal is set as unfinished
		if(get_post_meta($post->ID,'mtl-under-construction',true)=='on' || get_post_meta($post->ID,'mtl-proposal-phase',true)=='elaboration-phase') $output .= '<p style="background:'.$mtl_options['mtl-color-cat'.$catid].';padding:5px;"><strong style="color:white">'.__('This proposal has not been finished yet, but the author will complete it soon.','my-transit-lines').'</strong></p>';
		
		// output the meta data
		$output .= '<h2>'.__('Description of this proposal','my-transit-lines').'</h2>';
		$output2 .= '<h2>'.__('Metadata for this proposal','my-transit-lines').'</h2>'."\r\n";
		$output2 .= '<p class="mtl-metadata">';
		$output2 .= __('Transport mode','my-transit-lines').': '.$category_name.'<br />';
		if($lineLength) $output2 .= __('Line length','my-transit-lines').': '.$lineLengthOutput.'<br />';
		if($countStations) $output2 .= __('Number of stations','my-transit-lines').': '.$countStations.'<br />';
		if($averageDistance) $output2 .= __('Average station distance','my-transit-lines').': '.$averageDistanceOutput.'<br /><small>'.__('Attention: average station distance calculation is currently only correct when there is one contiguous line with the first and the last station on the respective end of line.','my-transit-lines').'</small><br />';
		$output2 .= '</p>'."\r\n";
		if($mtl_options3['mtl-show-districts']) $output2 .= mtl_taglist(true);
		
		/*$authorid = get_the_author_ID();
		if(get_user_meta($authorid,'enable-contact-button',true)) $output2 .= '
			<p><button><a href="'.add_query_arg(array('proposal-id' => $post->ID,'author-id' => $authorid),get_permalink(60459)).'">[IN ARBEIT]'.esc_html('Contact the author of this proposal','my-transit-lines').'</a></button></p>';*/

		$output2 .= '<script type="text/javascript"> currentCat = "'.$catid .'"; </script>';
		
		// show edit proposal button if current user equals author and proposal is not rateable
		$current_user = wp_get_current_user();
		$author_id=$post->post_author;
		if($mtl_options2['mtl-current-project-phase']=='rate' && (get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-phase' || get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-ready-phase')) $rating_possible = true;
		if($mtl_options['mtl-addpost-page']) {
			if($author_id > 0 && $author_id == $current_user->ID) {
				if(!$rating_possible) $output2 .= '<p class="mtl-edit-proposal"><a href="'.get_permalink($mtl_options['mtl-addpost-page']).'?edit_proposal='.$post->ID.'">'.__('Edit my proposal','my-transit-lines').'</a></p>';
				else {
					if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-ready-phase') $output2 .= '<p><em>'.__('<strong>Note:</strong> This proposal is already waiting for rating activation by the editors and therefore it can\'t be edited anymore.','my-transit-lines').'</em></p>';
					else $output2 .= '<p><em>'.__('<strong>Note:</strong> This proposal is already rateable and therefore it can\'t be edited anymore.','my-transit-lines').'</em></p>';
				}
			}
		}
		
		$content = $output.$content.$output2;
	}
	return $content;
}
add_filter( 'the_content', 'mtl_proposal_map' );

/**
 * get the taglist for districts/municipalities
 */
function mtl_taglist($single) {
	global $post;
	$output = '';
	
	$mtl_options = get_option('mtl-option-name');
	$tags = get_the_tags();
	$form_post_link = get_permalink($post->ID);
	if($tags) {
		$output .= '<div class="post-tags">';
		$output .= '<h3>'.__('All administrative subdivisons of this proposal:','my-transit-lines').'</h3>';
		$output .= '<ul>';
		foreach ( $tags as $current_tag ) {
			$tag_link = add_query_arg( array('mtl-tag' => $current_tag->term_id), get_permalink($mtl_options['mtl-postlist-page']));
			$selected = '';
			$get_tag = '';
			if(isset($_GET['mtl-tag'])) $get_tag = intval($_GET['mtl-tag']);
			if($get_tag == $current_tag->term_id) $selected = ' selected="selected"';
			$output .= "<li><a href='{$tag_link}' title='{$current_tag->name}'>{$current_tag->name}</a></li> ";
		}
		$output .= '</ul>';
		$output .= '</div>';
	}
	return $output;
}
?>