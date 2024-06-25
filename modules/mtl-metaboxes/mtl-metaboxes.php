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
}

function mtl_post_class_meta_box($post) {
	// nonce field for meta boxes
	wp_nonce_field( basename( __FILE__ ), 'mtl_post_class_nonce' );
	
	// get the mtl options
	$mtl_options = get_option('mtl-option-name');
	
	$mtl_feature_data =  get_post_meta($post->ID,'mtl-feature-data',true);
	$mtl_feature_labels_data =  get_post_meta($post->ID,'mtl-feature-labels-data',true);
	$mtl_features =  get_post_meta($post->ID,'mtl-features',true);
	
	$output = '<div id="mtl-box">';
	$output .= '<p style="clear:both"><label for="mtl-manual-proposal-data"><strong><input type="checkbox" name="mtl-manual-proposal-data" id="mtl-manual-proposal-data" /> '.__('Check this box if you want standard fields like category box or custom field section to overwrite changes within this meta box','my-transit-lines').'</strong></label></p>';

	// get the current category
	$current_category = get_the_category($post->ID);
	
	// load JS stuff (copied from mtl-proposal module)
	$output .= '<script type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = ["'.$mtl_feature_data.'"]; var vectorLabelsData = ["'.$mtl_feature_labels_data.'"]; var vectorFeatures = ["'.$mtl_features.'"]; var vectorCategoriesData = [undefined]; var editMode = true; </script>'."\r\n";
	$all_categories = get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' );
	
	// save category style data to JS array
	$output .= get_transport_mode_style_data();
	
	$output_later = '';
	foreach($all_categories as $single_category) {
		if(str_replace('other','',$single_category->slug)!=$single_category->slug) $output_later = 'defaultCategory = "'.$single_category->cat_ID.'";';
	}

	$output .= '<p><strong>'.__('If necessary, change transportation mode for this proposal.<br /><span style="font-weight:normal">Please change transport mode here and do not use the default WP category selector.</span>','my-transit-lines').'</strong><br /><span id="mtl-category-select"><span class="transport-mode-select">'."\r\n";
	
	// getting all categories for selected as transit mode categories, set the given category option to checked
	foreach($all_categories as $single_category) {
		if ($mtl_options['mtl-use-cat'.$single_category->cat_ID] == true) {
			$checked='';

			if($single_category->cat_ID == $current_category[0]->term_id) $checked=' checked="checked"';
			
			$output .= '<label class="mtl-category"><input'.$checked.' class="cat-select" onclick="redraw()" type="radio" name="cat" value="'.$single_category->cat_ID.'" id="cat-'.$single_category->slug.'" /> '.$single_category->name.'</label>'."\r\n";
		}
	}
	$output .= '</span></span></p>';
	
	$output .= '<p><strong>'.__('Map data of this proposal','my-transit-lines').'</strong></p>';
	$output .= '<div id="mtl-map-box">'."\r\n";
	$output .= '<div id="mtl-map" style="height:400px;"></div>'."\r\n";
	$output .= '<div class="feature-textinput-box"><label for="feature-textinput">'.__('Station name (optional)','my-transit-lines').': <br /><input type="text" name="feature-textinput" id="feature-textinput" onkeydown="var k=event.keyCode || event.which; if(k==13) { event.preventDefault(); }" /></label><br /><span class="set-name">'.__('Set new name', 'my-transit-line').'</span></div>'."\r\n";
	$output .= '</div>'."\r\n";
	$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="toggleMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="toggleMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span>'."\r\n";
	$output .= '<span id="zoomtofeatures" class="alignright"><a href="javascript:zoomToFeatures()">'.__('Fit proposition to map','my-transit-lines').'</a></span>';
	$output .= '<span class="alignright" id="mtl-toggle-labels" style="padding-right:5px;"><label style="text-align: right;"><input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></span></p>'."\r\n";
	$output .= '</div>';
	
	$output .= '<link rel="stylesheet" href="'.get_template_directory_uri().'/openlayers/ol.css">'."\r\n";
	$output .= '<link rel="stylesheet" href="'.get_template_directory_uri().'/modules/mtl-proposal-form/mtl-proposal-form.css">'."\r\n";
	$output .= '<script type="text/javascript"> '.$output_later.' var centerLon = "'.$mtl_options['mtl-center-lon'].'"; var centerLat = "'.$mtl_options['mtl-center-lat'].'"; </script>'."\r\n";
	$output .= mtl_localize_script(true);
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/js/util.js?ver='.wp_get_theme()->version.'"></script>';
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/openlayers/dist/ol.js?ver='.wp_get_theme()->version.'"></script>';
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/js/my-transit-lines.js?ver='.wp_get_theme()->version.'"></script>';
	$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/modules/mtl-proposal-form/mtl-proposal-form.js?ver='.wp_get_theme()->version.'"></script>';
	$output .= '<script type="text/javascript"> $(\'#post\').submit(function() { warningMessage = \'\'; }); </script>';
	
	// hidden input fields to save feature data
	$output .= '<input type="hidden" id="mtl-feature-data" value="'.$mtl_feature_data.'" name="mtl-feature-data" />'."\r\n";
	$output .= '<input type="hidden" id="mtl-feature-labels-data" value="'.htmlspecialchars($mtl_feature_labels_data).'" name="mtl-feature-labels-data" />'."\r\n";
	$output .= '<input type="hidden" id="mtl-features" value="'.$mtl_features.'" name="mtl-features" />'."\r\n";
	
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
	
	// enqueue theme style file to admin pages
	wp_enqueue_style( 'mtl-admin-style-metaboxes', get_template_directory_uri().'/modules/mtl-metaboxes/style.css', array() );
}
add_action( 'admin_enqueue_scripts', 'mtl_admin_scripts_metaboxes' );

// save post from backend
function mtl_post_save($post_id) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( !isset( $_POST['mtl_post_class_nonce']) || !wp_verify_nonce( $_POST['mtl_post_class_nonce'], basename( __FILE__ ) ) ) return;
	if ( !current_user_can( 'edit_post', $post_id ) ) return;
	
	// saving custom fields
	if(!isset($_POST['mtl-manual-proposal-data']) || $_POST['mtl-manual-proposal-data'] != 'on') {
		$save_custom_fields = array('mtl-feature-data','mtl-feature-labels-data','mtl-features','mtl-count-stations','mtl-line-length');
		foreach($save_custom_fields as $save_custom_field) if($_POST[$save_custom_field] != get_post_meta($post_id,$save_custom_field,true)) update_post_meta($post_id,$save_custom_field,$_POST[$save_custom_field]);
		
		if($_POST['cat']) {
			remove_action( 'save_post', 'mtl_post_save' ); // remove save action to avoid infinite loop
			$post = array(
				'ID' => $post_id,
				'post_category'	=> array($_POST['cat']),
			);
			wp_update_post($post);
			add_action( 'save_post', 'mtl_post_save' ); // re-add save action
		}
	}
	
	
}
add_action('save_post','mtl_post_save');

?>