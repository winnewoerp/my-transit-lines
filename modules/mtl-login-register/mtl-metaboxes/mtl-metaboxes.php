<?php
/**
 * My Transit Lines
 * Proposal meta boxes
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2015-04-22 */

/* TO DO: create extra function for MTL map including editing toolbar to avoid redundancy with frontend post form */

// meta boxes
add_action( 'load-post.php', 'mtl_post_meta_boxes_setup' );
add_action( 'load-post-new.php', 'mtl_post_meta_boxes_setup' );

function mtl_post_meta_boxes_setup() {
	add_action( 'add_meta_boxes', 'mtl_add_post_meta_boxes' );
}

function mtl_add_post_meta_boxes() {
	global $post;
	
	$relevant_posttypes = array('mtlproposal');
	foreach($relevant_posttypes as $relevant_posttype) {
		add_meta_box(
			'mtl-post-class',
			esc_html__(__('Metadata for this proposal','my-transit-lines'),__('Metadata for this proposal','my-transit-lines')),
			'mtl_post_class_meta_box',
			$relevant_posttype,
			'normal',
			'high'
		);
	}
	
	// creating rating meta box in rating phase
	$mtl_options2 = get_option('mtl-option-name2');
	if($mtl_options2['mtl-current-project-phase']=='rate' && get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-phase') {
		add_meta_box(
			'mtl-post-class-rating',
			esc_html__(__('Editor\'s rating of this proposal','my-transit-lines'),__('Editor\'s rating of this proposal','my-transit-lines')),
			'mtl_post_class_rating_meta_box',
			'mtlproposal',
			'normal',
			'high'
		);
	}
}

function mtl_post_class_meta_box($post) {
	// nonce field for meta boxes
	wp_nonce_field( basename( __FILE__ ), 'mtl_post_class_nonce' );
	
	// get the mtl options
	$mtl_options = get_option('mtl-option-name');
	$mtl_options2 = get_option('mtl-option-name2');
	
	$mtl_feature_data =  get_post_meta($post->ID,'mtl-feature-data',true);
	$mtl_feature_labels_data =  get_post_meta($post->ID,'mtl-feature-labels-data',true);
	
	$output .= '<div id="mtl-box">';
	$output .= '<p style="clear:both"><label for="mtl-manual-proposal-data"><strong><input type="checkbox" name="mtl-manual-proposal-data" id="mtl-manual-proposal-data" /> '.__('Check this box if you want standard fields like category box or custom field section to overwrite changes within this meta box','my-transit-lines').'</strong></label></p>';

	
	// get the current category
	$current_category = get_the_category($post->ID);
	
	// load JS stuff (copied from mtl-proposal module)
	$output .= '<script type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = "'.$mtl_feature_data.'"; var vectorLabelsData = "'.$mtl_feature_labels_data.'"; var editMode = true; </script>'."\r\n";
	$all_categories=get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' );
	
	// save category style data to JS array
	$output .= '<script type="text/javascript"> var transportModeStyleData = {';
	
	$count_cats = 0;
	$output_later = '';
	foreach($all_categories as $single_category) {
		$catid = $single_category->cat_ID;
		if(str_replace('other','',$single_category->slug)!=$single_category->slug) $output_later .= 'strokeColor = "'.$mtl_options['mtl-color-cat'.$catid].'"; fillColor = "'.$mtl_options['mtl-color-cat'.$catid].'"; externalGraphicUrl = "'.$mtl_options['mtl-image-cat'.$catid].'"; externalGraphicUrlSelected = "'.$mtl_options['mtl-image-selected-cat'.$catid].'"; bahnTyp = "'.$single_category->name.'";';
		if($mtl_options['mtl-use-cat'.$catid] == true) {
			if($count_cats) $output .= ',';
			$output .= $catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"]';
			$count_cats++;
		}
	}
	$output .= '}; </script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/openlayers/OpenLayers.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/Editor/Lang/de.js"></script>'."\r\n";
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/ole/lib/loader.js"></script>'."\r\n";
	$output .= '<script type="text/javascript"> '.$output_later.' var mtlCenterLon = "'.$mtl_options['mtl-center-lon'].'"; var mtlCenterLat = "'.$mtl_options['mtl-center-lat'].'"; </script>'."\r\n";
	$output .= mtl_localize_script(true);
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>'."\r\n";

	$output .= '<p><strong>'.__('If necessary, change transportation mode for this proposal.<br /><span style="font-weight:normal">Please change transport mode here and do not use the default WP category selector.</span>','my-transit-lines').'</strong><br /><span id="mtl-category-select"><span class="transport-mode-select">'."\r\n";
	
	// getting all categories for selected as transit mode categories, set the given category option to checked
	foreach($all_categories as $single_category) {
		$checked='';
		$post_cat = '';
		if(isset($_POST['cat'])) $post_cat = $_POST['cat'];
		if($single_category->cat_ID == $current_category[0]->term_id) $checked=' checked="checked"';
		if($mtl_options['mtl-use-cat'.$single_category->cat_ID] == true) $output .= '<label class="mtl-category"><input'.$checked.' class="cat-select" onclick="changeLinetype()" type="radio" name="cat" value="'.$single_category->cat_ID.'" id="cat-'.$single_category->slug.'" /> '.$single_category->name.'</label>'."\r\n";
	}
	$output .= '</span></span></p>';
	
	// editor box for editor's hints field
	if($mtl_options2['mtl-current-project-phase']=='rate') {
		$output .= '<p><strong>'.__('Input the editor\'s hints here','my-transit-lines').'</strong><br />';
		$output .= __('A text is needed to show the author what to change to make the proposal better and to enable "proposal ready status" selection for the author. The text must have at least 10 characters, so write something like "This is a good proposal" if it\'s already good.','my-transit-lines').'</p>';
		$output .= '<div id="mtl-editors-hints-box">';
		ob_start();
		wp_editor(get_post_meta($post->ID, 'mtl-editors-hints', true ),'mtl-editors-hints',array('textarea_name' => 'mtl-editors-hints'));
		$output_editor = ob_get_clean();
		ob_end_flush();
		$output .= $output_editor;
		$output .= '</div>';
		$output .= '<p><label for="minor-changes"><input type="checkbox" name="minor-changes" id="minor-changes" /> '.__('Only minor changes within editor\'s hints text. Do not send update notification e-mail to user.','my-transit-lines').'</label></p>';
		$output .= '<p><strong><label for="mtl-proposal-status-nok"><input type="checkbox" name="mtl-proposal-status-nok" id="mtl-proposal-status-nok"'.(get_post_meta($post->ID,'mtl-proposal-status-nok',true)=='on' ? ' checked="checked"' : '' ).' /> '.__('Check this box if proposal is not ok yet (the "under construction" flag will appear for the proposal).','my-transit-lines').'</label></strong></p>';

	}
	
	if(get_post_meta($post->ID,'author-name',true)) $output .= '<p><strong>'.__('This proposal was created by an unregistered user and thus it can\'t enter the rating phase.','my-transit-lines').'</strong></p>';

	$output .= '<p>&nbsp;<br /><label class="mtl-proposal-phase" for="mtl-proposal-phase"><strong>'.__('Please select the current phase of this proposal','my-transit-lines').'</strong> ('.__('default: revision phase','my-transit-lines').')<br />';
	$output .= '<select name="mtl-proposal-phase" id="mtl-proposal-phase">';
	$output .= '<option value="elaboration-phase"'.(get_post_meta($post->ID,'mtl-proposal-phase',true) == 'elaboration-phase' || get_post_meta($post->ID,'mtl-under-construction',true) == 'on' ? ' selected="selected"' : '').'>'.__('Elaboration phase','my-transit-lines').'</option>';
	$output .= '<option value="revision-phase"'.(get_post_meta($post->ID,'mtl-proposal-phase',true) == 'revision-phase' || !get_post_meta($post->ID,'mtl-proposal-phase',true) ? ' selected="selected"' : '').'>'.__('Revision phase','my-transit-lines').'</option>';
		if(!get_post_meta($post->ID,'author-name',true)) {
		if($mtl_options2['mtl-current-project-phase']=='rate') {
			if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-ready-phase') $output .= '<option value="rating-ready-phase" selected="selected">'.__('Ready for rating','my-transit-lines').'</option>';
			if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-ready-phase' || get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-phase') {
				$output .= '<option value="rating-phase"'.(get_post_meta($post->ID,'mtl-proposal-phase',true) == 'rating-phase' ? ' selected="selected"' : '').'>'.__('Rating phase','my-transit-lines').'</option>';
			}
			if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-ready-phase') $output .= '<option value="rating-phase-refused"'.(get_post_meta($post->ID,'mtl-proposal-phase',true) == 'rating-phase-refused' ? ' selected="selected"' : '').'>'.__('Refuse rating phase','my-transit-lines').'</option>';
		}
	}
	$output .= '</select><br />';
	$output .= __('<strong>Please note:</strong> Select "Elaboration phase" if you want the flag for unfinished proposals to appear within the proposals list and in the single proposal view. "Revision phase" will only be set if you didn\'t set the proposal to "not yet ok" status (see above).','my-transit-lines');
	if($mtl_options2['mtl-current-project-phase']=='rate') {
		if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-ready-phase') $output .= __(' Choose "Rating phase" to enable rating for this proposal or "Refuse rating phase" if you don\'t want to allow rating yet. In both cases, the user will be informed by e-mail.','my-transit-lines');
	}
	$output .= '</p>'; 
	
	$output .= '<p><strong>'.__('Map data of this proposal','my-transit-lines').'</strong></p>';
	$output .= '<div id="mtl-map-box">'."\r\n";
	$output .= '<div id="mtl-map" style="height:400px;"></div>'."\r\n";
	$output .= '<div class="feature-textinput-box"><label for="feature-textinput">'.__('Station name (optional)','my-transit-lines').': <br /><input type="text" name="feature-textinput" id="feature-textinput" /></label><br /><span class="set-name">Neuen Namen setzen</span></div>'."\r\n";
	$output .= '</div>'."\r\n";
	$output .= '<p id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="setMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></p>'."\r\n";
	$output .= '</div>';
	
	// hidden input fields to save feature data
	$output .= '<input type="hidden" id="mtl-feature-data" value="'.$mtl_feature_data.'" name="mtl-feature-data" />'."\r\n";
	$output .= '<input type="hidden" id="mtl-feature-labels-data" value="'.$mtl_feature_labels_data.'" name="mtl-feature-labels-data" />'."\r\n";
	
	// hidden input field for station count
	$mtl_count_stations =  get_post_meta($post->ID,'mtl-count-stations',true);
	$output .= '<input type="hidden" id="mtl-count-stations" value="'.$mtl_count_stations.'"  name="mtl-count-stations" />'."\r\n";
	
	// hidden input field for line length
	$mtl_line_length =  get_post_meta($post->ID,'mtl-line-length',true);
	$output .= '<input type="hidden" id="mtl-line-length" value="'.$mtl_line_length.'"  name="mtl-line-length" />'."\r\n";
	
	echo $output;
}

function mtl_admin_scripts_metaboxes( ) {
	global $post;
	// get the style for the Openlayers Editor
	if($post->ID) wp_enqueue_style('ole-style',get_template_directory_uri() .'/ole/theme/geosilk/geosilk.css',array());
	
	// enqueue theme style file to admin pages
	wp_enqueue_style( 'mtl-admin-style-metaboxes', get_template_directory_uri().'/modules/mtl-metaboxes/style.css',array() );
}
add_action( 'admin_enqueue_scripts', 'mtl_admin_scripts_metaboxes' );

function mtl_post_class_rating_meta_box($post) {
	// get contents for this meta box from star rating module
	if(function_exists('mtl_star_rating_dashboard')) mtl_star_rating_dashboard($post);
}

// save post from backend
function mtl_post_save($post_id) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( !wp_verify_nonce( $_POST['mtl_post_class_nonce'], basename( __FILE__ ) ) ) return;
	if ( !current_user_can( 'edit_post', $post_id ) ) return;
	
	// saving custom fields
	if($_POST['mtl-manual-proposal-data'] != 'on') {
		$save_custom_fields = array('mtl-feature-data','mtl-feature-labels-data','mtl-count-stations','mtl-line-length','mtl-proposal-phase','mtl-proposal-status-nok','mtl-editors-hints');
		foreach($save_custom_fields as $save_custom_field) if($_POST[$save_custom_field] != get_post_meta($post_id,$save_custom_field,true)) update_post_meta($post_id,$save_custom_field,$_POST[$save_custom_field]);
		
		if($_POST['mtl-proposal-status-nok']=='on') update_post_meta($post_id,'mtl-proposal-phase','elaboration-phase');
		
		if($_POST['cat']) {
			remove_action( 'save_post', 'mtl_post_save' ); // remove save action to avoid infinite loop
			$post = array(
				'ID' => $post_id,
				'post_category'	=> array($_POST['cat']),
			);
			wp_update_post($post);
			add_action( 'save_post', 'mtl_post_save' ); // re-add save action
		}
		
		// delete this for future versions
		if($_POST['mtl-proposal-phase'] != 'elaboration-phase') {
			delete_post_meta($post_id,'mtl-under-construction','on');
			delete_post_meta($current_post_id,'mtl-under-construction','');
		}
	}
	
	
}
add_action('save_post','mtl_post_save');



?>