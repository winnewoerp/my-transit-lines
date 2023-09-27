<?php
/**
 * My Transit Lines
 * Proposal tile list
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-07 */

/* ### STILL TO DO ###
 * any suggestions?
 */

/**
 * create the thumb maps
 */
global $count_thumblist_maps;
$count_thumblist_maps = 0;
function mtl_thumblist_map() {

	// get the mtl options
	$mtl_options = get_option('mtl-option-name');
	$mtl_options2 = get_option('mtl-option-name2');
	$mtl_options3 = get_option('mtl-option-name3');
	$output = '';
	global $post;
	$output .= '<div id="thumblist-map'.$post->ID.'" class="mtl-thumblist-map'.($mtl_options2['mtl-current-project-phase']=='rate' ? ' rating' : '').'"></div>';
	$output .= '<script type="text/javascript"> createThumbMap('.$post->ID.'); </script>';
	return $output;
}

/**
 * shortcode [mtl-tile-list]
 */
function mtl_tile_list_output($atts) {
	global $post;
	$output = '';
	extract( shortcode_atts( array(
		'type' => 'mtlproposal',
		'hidethumbs' => false,
	), $atts ) );
	
	// get the mtl options
	$mtl_options = get_option('mtl-option-name');
	$mtl_options2 = get_option('mtl-option-name2');
	$mtl_options3 = get_option('mtl-option-name3');
	
	// get categories from parameter or mtl theme options
	$query_cats = '';
	$mtl_all_catids = '';
	$categories = get_categories();
	foreach($categories as $category) if($mtl_options['mtl-use-cat'.$category->cat_ID] == true) $mtl_all_catids .= $category->cat_ID.',';
		
	if(isset($_GET['mtl-catid']) && $_GET['mtl-catid'] != 'all') {
		$single_catid = intval($_GET['mtl-catid']);
		$get_cats = $single_catid;
	}
	else $get_cats = $mtl_all_catids;

	$status_tax_query = array();
	if(isset($_GET['mtl-statusid']) && $_GET['mtl-statusid'] != 'all') {
		$single_statusid = intval($_GET['mtl-statusid']);

		$status_tax_query = array( array (
			'taxonomy' => 'sorting-phase-status',
			'terms' => $single_statusid,
		),);

		// if default status is searched for also find proposals without status
		if (get_term_by('id', $single_statusid, 'sorting-phase-status')->slug == get_taxonomy('sorting-phase-status')->default_term['slug']) {
			$status_tax_query = array( array (
				'taxonomy' => 'sorting-phase-status',
				'terms' => other_term_ids('sorting-phase-status', $single_statusid),
				'operator' => 'NOT IN',
			));
		}
	}
	
	if($single_catid || $mtl_all_catids) {
		
		// get tags from parameter
		$get_tag = '';
		if(isset($_GET['mtl-tag'])) $get_tag = intval($_GET['mtl-tag']);
		if($get_tag) $tag_data = get_tag($get_tag);
		
		// get userid from parameter
		$get_userid = '';
		if(isset($_GET['mtl-userid']))$get_userid = intval($_GET['mtl-userid']);

		// get sort criteria from parameter
		$order_by = 'date';
		if($_GET['orderby']=='date' || $_GET['orderby']=='comment_count' || $_GET['orderby']=='rand') $order_by=$_GET['orderby'];
		if($_GET['orderby']=='rating') {
			$order_by = 'meta_value';
			$meta_key = 'mtl-rating-average';
		}
		if($_GET['orderby']=='rating_count') {
			$order_by = 'meta_value';
			$meta_key = 'mtl-rating-count';
		}
		$order = 'desc';
		if($_GET['order']=='asc' || $_GET['order']=='desc') $order=$_GET['order'];
		
		// proposals per page, one less for standard list, as there's the "add post box" as first tile
		$posts_per_page = 25;
		if(isset($_GET['num'])) $posts_per_page = intval($_GET['num']);
		$paged = '';
		$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
		
		$status = array('publish');
		if(isset($_GET['show-drafts']) && $_GET['show-drafts'] == 'true' && isset($_GET['mtl-userid']) && $_GET['mtl-userid'] == get_current_user_id()) {
			$status[] = 'draft';
		}

		$search = $_GET['search'];
		
		$query_string = array(
			'posts_per_page' => max(($posts_per_page - (($posts_per_page + 1) % 4)), -1),
			'post_type' => $type,
			'cat' => $get_cats,
			'tag_id' => $get_tag,
			'author' => $get_userid,
			'orderby' => $order_by,
			'meta_key' => $meta_key,
			'order' => $order,
			's' => $search,
			'tax_query' => $status_tax_query,
		);

		if ($order_by!='rand') {
			$query_string['paged'] = $paged;
			$query_string['post_status'] = $status;

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
		}
		
		$second_query = new WP_Query($query_string);
		$mtl_options = get_option('mtl-option-name');
		$all_categories = get_categories('include='.$mtl_all_catids);
		$all_statuses = get_terms( array(
			'taxonomy' => 'sorting-phase-status',
			'hide_empty' => false,
		));

		// remove query arg "page" for form action link
		$form_post_link = get_permalink($post->ID);
		
		// filter start
		$output .= '<div id="mtl-list-filter"><form name="mtl-filter-form" id="mtl-filter-form" method="get" action="'.$form_post_link.'"><p><strong>'.__('Filter:','my-transit-lines').'</strong> ';
		
		// transit mode selector
		$output .= '<select name="mtl-catid">'."\r\n";
		$output .= '<option value="all">'.__('All transit modes','my-transit-lines').' </option>';
		foreach($all_categories as $single_category) {
			$catid = $single_category->cat_ID;
			if($mtl_options['mtl-use-cat'.$catid] == true) {
				$output .= '<option value="'.$catid.'"'.($catid==$single_catid ? ' selected="selected"' : '').'>'.$single_category->name.' </option>'."\r\n";
			}
		}
		$output .= '</select>';
		
		// user selector
		$output .= '<select name="mtl-userid">'."\r\n";
		
		$count_posts = count_filtered_posts($type,$get_userid,$get_cats,$get_tag);
		$output .= '<option value="all">'.__('All users (incl. unregistered)','my-transit-lines').' </option>';
		$blogusers = get_users('orderby=display_name');
		foreach($blogusers as $bloguser) {
			//$count_userposts = count_filtered_posts($type,$bloguser->ID,$get_cats,$get_tag);
			//if($count_userposts>0)
			$output .= '<option value="'.$bloguser->ID.'"'.($bloguser->ID==$get_userid ? ' selected="selected"' : '').'>'.$bloguser->display_name.' </option>'."\r\n";
		}
		$output .= '</select>';
		
		// tag selector (administrative divisions) - only if checkbox set within theme options
		if($mtl_options3['mtl-show-districts']) {
			$tags = get_tags();
			if($type == 'mtlproposal') {
				$output .= '<select name="mtl-tag">';
				$count_posts = count_filtered_posts($type,$get_userid,$get_cats,'');
				$output .= '<option value="all">'.__('All regions','my-transit-lines').' </option>';
				foreach ( $tags as $current_tag ) {
					$count_posts = count_filtered_posts($type,$get_userid,$get_cats,$current_tag->term_id );
					$tag_link = add_query_arg( array('mtl-tag' => $current_tag->term_id), get_permalink($mtl_options['mtl-postlist-page']));
					$selected = '';
					if($get_tag == $current_tag->term_id) $selected = ' selected="selected"';
					if($count_posts>0) $output .= "<option".$selected." value='{$current_tag->term_id}'>{$current_tag->name} </option>";
				}
				$output .= '</select>';
			}
		}
		$output .= '</p>';

		// Sorting phase status selector
		$output .= '<p><strong>'.__('Sorting Phase Status:','my-transit-lines').'</strong>';
		$output .= '<select name="mtl-statusid">'."\r\n";
		$output .= '<option value="all">'.__('All statuses','my-transit-lines').' </option>';
		foreach($all_statuses as $single_status) {
			$statusid = $single_status->term_id;
			$output .= '<option value="'.$statusid.'"'.($statusid==$single_statusid ? ' selected="selected"' : '').'>'.$single_status->name.' </option>'."\r\n";
		}
		$output .= '</select></p>';
		
		$output .= '<p><strong>'.__('Sort:','my-transit-lines').'</strong><select name="orderby">';
		$output .= '<option'.($order_by=='date' ? ' selected="selected"' : '').' value="date">'.__('Date','my-transit-lines').'</option>';
		$output .= '<option'.($order_by=='comment_count' ? ' selected="selected"' : '').' value="comment_count">'.__('Number of comments','my-transit-lines').'</option>';
		if($mtl_options2['mtl-current-project-phase']=='rate') {
			$output .= '<option'.($_GET['orderby']=='rating' ? ' selected="selected"' : '').' value="rating">'.__('Rating','my-transit-lines').'</option>';
			$output .= '<option'.($_GET['orderby']=='rating_count' ? ' selected="selected"' : '').' value="rating_count">'.__('Number of ratings','my-transit-lines').'</option>';
		}
		$output .= '<option'.($order_by=='rand' ? ' selected="selected"' : '').' value="rand">'.__('Random','my-transit-lines').'</option>';
		$output .= '</select><select name="order"><option'.($order=='desc' ? ' selected="selected"' : '').' value="desc">'.__('Descendent','my-transit-lines').'</option><option'.($order=='asc' ? ' selected="selected"' : '').' value="asc">'.__('Ascendent','my-transit-lines').'</option></select>';
		
		// Selector for amount of proposals shown
		$amounts = [25,50,100,250,500];
		$output .= '<strong>'.__('Amount:','my-transit-lines').'</strong>';
		$output .= '<select name="num">';
		if (!in_array($posts_per_page, $amounts)) {
			$output .= '<option selected="selected" value="'.$posts_per_page.'">'.($posts_per_page > 2 ? $posts_per_page : 'all').'</option>';
		}
		foreach ($amounts as $amount) {
			$output .= '<option '.($posts_per_page == $amount ? ' selected="selected"' : '').' value="'.$amount.'">'.$amount.'</option>';
		}
		$output .= ' </select></p>';

		$output .= '<p><strong>'.__('Search:','my-transit-lines').'</strong><input type="search" name="search" value="'.$search.'">';

		$output .= '<button type="submit">'.__('Filter/sort','my-transit-lines').'</button></p></form></div>'."\r\n";
		
		// paginate links
		$big = 999999999; // need an unlikely integer
		$mtl_paginate_links = '<div class="mtl-paginate-links">';
		if($order_by!='rand') $mtl_paginate_links .= paginate_links( array(
			'base' => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
			'format' => '?paged=%#%',
			'current' => max( 1, get_query_var('paged') ),
			'total' => $second_query->max_num_pages,
			'prev_text' => '',
			'next_text' => ''
		) );
		$mtl_paginate_links .= '</div>';
		$output .=  $mtl_paginate_links;
		
		// start the tile list
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/modules/mtl-tile-list/mtl-tile-list.js"></script>';
		$output .= '<div class="mtl-posttiles-list">';
		
		// load the text translations
		$output .= mtl_localize_script(true);
		
		// load the necessary scripts and set some JS variables
		if(!$hidethumbs) $output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/openlayers/OpenLayers.js"></script>'."\r\n";
		$output .= '<script type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = [""]; var vectorLabelsData = [""]; var vectorCategoriesData = [undefined]; var editMode = false; </script>'."\r\n";
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>';
		if(!$hidethumbs) $output .= '<script type="text/javascript"> var mtlCenterLon = "'.$mtl_options['mtl-center-lon'].'"; var mtlCenterLat = "'.$mtl_options['mtl-center-lat'].'"; </script>'."\r\n";
		$output .= '<script type="text/javascript"> ';
		$output .= ' var loadingNewProposalsText = "'.__('Loading new set of proposals...','my-transit-lines').'";';
		$output .= ' var tilePageUrl = "'.get_permalink().'"; var initMap =false;';
		if(!$hidethumbs) {
			$output .= ' var transportModeStyleData = {';
			$count_cats = 0;
			foreach($all_categories as $single_category) {
				$catid = $single_category->cat_ID;
				if($mtl_options['mtl-use-cat'.$catid] == true) {
					if($count_cats) $output .= ',';
					$output .= $catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"]';
					$count_cats++;
				}
			}
			$output .= '};';
		}
		$output .= '</script>'."\r\n";
		
		// output the add post tile (first tile of the list, shown in most cases)
		if($type == 'mtlproposal' && $mtl_options['mtl-addpost-page'] && (!$get_catid_addpost || $get_catid=='all')) $output .= '<div class="mtl-post-tile add-post"><div class="entry-thumbnail placeholder"></div><h1><a href="'.get_permalink($mtl_options['mtl-addpost-page']).'">'.__('Add a new proposal with map and description','my-transit-lines').'</a></h1><div class="entry-meta">'.__('Contribute to the collection!','my-transit-lines').'</div></div>';
		
		// loop through the tiles
		while($second_query->have_posts()) : $second_query->the_post(); global $post;
		
		$hide_proposal = (bool)(get_post_meta($post->ID,'author-name',true) && $get_userid);
		
		if(!$hide_proposal && $mtl_options['mtl-use-cat'.$catid] == true) {
			$category = get_the_category($post->ID);
			$post_category = $category[0]->slug;
			$catid = $category[0]->cat_ID;
			$bgcolor = $mtl_options['mtl-color-cat'.$catid];
			
			$output .= '<div class="mtl-post-tile" style="background-color:'.$bgcolor.'" >';
			
			if(!$hidethumbs) {
				// Removing line breaks that can be caused by WordPress import/export
				$output .= '<script type="text/javascript"> var currentCat = '.$catid.'; var pluginsUrl = "'. plugins_url('', __FILE__) .'"; var vectorData = ["'.str_replace("\n", "", get_post_meta($post->ID,'mtl-feature-data',true)).'"]; var vectorLabelsData = ["'.str_replace("\n", "", get_post_meta($post->ID,'mtl-feature-labels-data',true)).'"]; var vectorCategoriesData = [undefined]; var editMode = false; </script>'."\r\n";
				$output .= mtl_thumblist_map();
			}
			$output .= mtl_load_template_part( 'content', get_post_format() );

			if(current_user_can('manage_options') && strlen(get_post_meta($post->ID,'mtl-editors-hints',true))>10)
				$output .= 'hints text ready';
			
			$output .= '</strong></p>';
			$output .= '</div>';
		}
		endwhile;

		$output .= '<div class="clear"></div>';

		$output .= '</div>';
		
		$output .= $mtl_paginate_links;
		wp_reset_postdata();

		$output .= '<script type="text/javascript"> var post_map_url = "'.get_permalink(get_option('mtl-option-name')['mtl-postmap-page']).'"; </script><p class="alignleft"> <a id="mtl-post-map-link">'.__('Proposal map page','my-transit-lines').'</a> </p>';
	}
	
	return $output;
}
add_shortcode( 'mtl-tile-list', 'mtl_tile_list_output' );

// special count posts function for filtered posts
function count_filtered_posts($type,$get_userid,$get_cats,$get_tag) {
	/*$count1 = count(get_posts(array('post_type' => $type,'nopaging' =>true,'author'=>$get_userid,'cat'=>$get_cats,'tag_id'=>$get_tag)));
	$count2 = count(get_posts(array('post_type' => $type,'nopaging' =>true,'author'=>$get_userid,'meta_key'=>'author-name','cat'=>$get_cats,'tag_id'=>$get_tag)));
	if($get_userid) $count_posts = $count1-$count2;
	else $count_posts =  $count1;
	return $count_posts;*/
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