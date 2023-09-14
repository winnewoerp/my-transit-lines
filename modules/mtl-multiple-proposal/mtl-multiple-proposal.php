<?php
/**
 * My Transit Lines
 * Multiple proposal view module
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2023-07-23 */

/* ### STILL TO DO ###
 * I don't know, is there anything? Other custom stuff needed to be added to the multiple view?
 */

 /**
 * map and meta data for multiple proposals
 */
function mtl_multiple_proposal_output( $atts ) {
	global $post;
	$output = '';
	extract( shortcode_atts( array(
		'type' => 'mtlproposal',
	), $atts ) );
	
	// get the mtl options
	$mtl_options = get_option('mtl-option-name');
	
	// get categories from parameter or mtl theme options
	$query_cats = '';
	$mtl_all_catids = '';
	foreach(get_categories() as $category) {
        if($mtl_options['mtl-use-cat'.$category->cat_ID])
            $mtl_all_catids .= $category->cat_ID.',';
    }
		
	if(isset($_GET['mtl-catid']) && $_GET['mtl-catid'] != 'all') {
		$single_catid = intval($_GET['mtl-catid']);
		$query_cats = $single_catid;
	}
	else $query_cats = $mtl_all_catids;
	
	if($query_cats) {
		// get userid from parameter
		$get_userid = '';
		if(isset($_GET['mtl-userid']))
            $get_userid = intval($_GET['mtl-userid']);

        // get sort criteria from parameter
		$order_by = 'date';
		if($_GET['orderby']=='date' || $_GET['orderby']=='comment_count' || $_GET['orderby']=='rand') $order_by=$_GET['orderby'];
		$order = 'desc';
		if($_GET['order']=='asc' || $_GET['order']=='desc') $order=$_GET['order'];
		
		$posts_per_page = 23;
		if(isset($_GET['num'])) $posts_per_page = intval($_GET['num']);
		$paged = '';
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		
		$status = array('publish');
		if(isset($_GET['show-drafts']) && $_GET['show-drafts'] == 'true' && isset($_GET['mtl-userid']) && $_GET['mtl-userid'] == get_current_user_id()) {
			$status[] = 'draft';
		}

		$search = $_GET['search'];
		
		$query_string = array(
			'posts_per_page' => $posts_per_page,
			'post_type' => $type,
			'cat' => $query_cats,
			'author' => $get_userid,
			's' => $search,
            'post_status' => $status,
			'orderby' => $order_by,
			'order' => $order,
			'paged' => $paged,
		);

        if(!in_array('draft', $status)) {
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

		$the_query = new WP_Query($query_string);

		$vector_data = [];
		$vector_labels_data = [];
		$vector_categories_data = [];

		while ($the_query->have_posts()) : $the_query->the_post(); global $post;

		$hide_proposal = (bool)(get_post_meta($post->ID,'author-name',true) && $get_userid);
		
		if(!$hide_proposal) {
			$category = get_the_category($post->ID);
			$catid = $category[0]->cat_ID;

			array_push($vector_categories_data, $catid);
			array_push($vector_data, get_post_meta($post->ID,'mtl-feature-data',true));
			array_push($vector_labels_data, get_post_meta($post->ID,'mtl-feature-labels-data',true));
		}

		endwhile;
		wp_reset_postdata();

		$all_selectable_categories = get_categories('include='.$mtl_all_catids);
			
		// remove query arg "page" for form action link
		$form_post_link = get_permalink($post->ID);
		
		// filter start
		$output .= '<div id="mtl-list-filter"><form name="mtl-filter-form" id="mtl-filter-form" method="get" action="'.$form_post_link.'"><p><strong>'.__('Filter:','my-transit-lines').'</strong> ';

        // transit mode selector
		$output .= '<select name="mtl-catid">'."\r\n".'<option value="all">'.__('All transit modes','my-transit-lines').' </option>';
		foreach($all_selectable_categories as $single_category) {
			$catid = $single_category->cat_ID;
            $output .= '<option value="'.$catid.'"'.($catid==$single_catid ? ' selected="selected"' : '').'>'.$single_category->name.' </option>'."\r\n";
		}
		$output .= '</select>';
		
		// user selector
		$output .= '<select name="mtl-userid">'."\r\n".'<option value="all">'.__('All users (incl. unregistered)','my-transit-lines').' </option>';
		$blogusers = get_users('orderby=display_name');
		foreach($blogusers as $bloguser) {
			$output .= '<option value="'.$bloguser->ID.'"'.($bloguser->ID==$get_userid ? ' selected="selected"' : '').'>'.$bloguser->display_name.' </option>'."\r\n";
		}
		$output .= '</select>';

		$output .= '<p><strong>'.__('Sort:','my-transit-lines').'</strong><select name="orderby">';
		$output .= '<option'.($order_by=='date' ? ' selected="selected"' : '').' value="date">'.__('Date','my-transit-lines').'</option>';
		$output .= '<option'.($order_by=='comment_count' ? ' selected="selected"' : '').' value="comment_count">'.__('Number of comments','my-transit-lines').'</option>';
		$output .= '<option'.($order_by=='rand' ? ' selected="selected"' : '').' value="rand">'.__('Random','my-transit-lines').'</option>';
		$output .= '</select><select name="order"><option'.($order=='desc' ? ' selected="selected"' : '').' value="desc">'.__('Descendent','my-transit-lines').'</option><option'.($order=='asc' ? ' selected="selected"' : '').' value="asc">'.__('Ascendent','my-transit-lines').'</option></select></p>';

		$output .= '<p><strong>'.__('Search:','my-transit-lines').'</strong><input type="search" name="search" value="'.$search.'">';

		$output .= '<button type="submit">'.__('Filter/sort','my-transit-lines').'</button></p></form></div>'."\r\n";

		// paginate links
		$big = 999999999; // need an unlikely integer
		$mtl_paginate_links = '<div class="mtl-paginate-links">';
		if($order_by!='rand') $mtl_paginate_links .= paginate_links( array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '?paged=%#%',
			'current' => max( 1, get_query_var('paged') ),
			'total' => $the_query->max_num_pages,
			'prev_text' => '',
			'next_text' => ''
		) );
		$mtl_paginate_links .= '</div>';
		$output .= $mtl_paginate_links;
		
		// load the text translations
		$output .= mtl_localize_script(true);

		// load relevant scripts and set some JS variables
		$output .= "\r".'<div id="mtl-box">'."\r\n".'<script type="text/javascript"> var transportModeStyleData = {';
		foreach($all_selectable_categories as $single_category) {
			$catid = $single_category->cat_ID;
			$output .= $catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"],';
		}
		$output .= '}; </script>';
		$output .= '<script id="mtl-multiple-proposal-data-script" type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = [';
		foreach ($vector_data as $value) {
			$output .= '"'.$value.'",';
		}
		$output .= ']; var vectorLabelsData = [';
		foreach ($vector_labels_data as $value) {
			$output .= '"'.$value.'",';
		}
		$output .= ']; var vectorCategoriesData = [';
		foreach ($vector_categories_data as $value) {
			$output .= '"'.$value.'",';
		}
		$output .= ']; var editMode = false;</script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/openlayers/OpenLayers.js"></script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/Editor/Lang/de.js"></script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/loader.js"></script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/modules/mtl-multiple-proposal/mtl-multiple-proposal.js"></script>'."\r\n";
		$output .= '<script type="text/javascript"> var loadingNewProposalsText = "'.__('Loading new set of proposals...','my-transit-lines').'";
					var multiple_proposal_page_url = "'.get_permalink().'"; </script>'."\r\n";
		
		// output the map box
		$output .= '<div id="mtl-map-box">'."\r\n";
		$output .= '<div id="mtl-map"></div>'."\r\n";
		$output .= '</div>';

		// output opacity change button, map fullscreen link and toggle label checkbox
		$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="setMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="setMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span></p>'."\r\n";
		$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:mtlFullscreenMap()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
		$output .= '<p class="alignright" id="mtl-toggle-labels"><label><input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></p>'."\r\n";
		$output .= '</div>'."\r\n";

		$output .= '<script type="text/javascript"> $(document).ready(function(){ document.getElementById("mtl-toggle-labels-link").checked = false; toggleLabels();}); </script>'."\r\n";

		$output .= '<script type="text/javascript"> var post_list_url = "'.get_permalink(get_option('mtl-option-name')['mtl-postlist-page']).'"; </script><p class="alignleft"> <a id="mtl-post-list-link">'.__('Proposal list page','my-transit-lines').'</a> </p>';
    }

    return $output;
}
add_shortcode( 'mtl-multiple-proposal', 'mtl_multiple_proposal_output' );

?>