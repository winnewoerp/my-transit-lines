<?php
/**
 * My Transit Lines
 * Proposal form module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */

/**
 * shortcode [mtl-proposal-form]
 */
function mtl_proposal_form_output( $atts ){
	global $post;
	
	// get the mtl options
	$mtl_options = get_option('mtl-option-name');
	$mtl_options2 = get_option('mtl-option-name2');
	$mtl_options3 = get_option('mtl-option-name3');
	
	// get the posttype from url parameter or set to default
	$postType = 'mtlproposal';
	if(isset($_GET['posttype'])) $postType = $_GET['posttype'];
	$posttype_object = get_post_type_object($postType);
	$posttype_singular_name = $posttype_object->labels->singular_name;

	$editId = get_editId();
	$editType = 'add';
	if ($editId) $editType = 'update';
	
	// only if form allowed
	if(is_user_logged_in() && is_form_allowed() && !isset($_POST['delete-draft']) && !isset($_POST['really-delete-draft'])) {

		// create all necessary strings for this page containing either post type or edit type 
		$mtl_string['logged-out-notice']['mtlproposal'] = sprintf(__('<strong>Important notice:</strong> You are about to write a proposal without being logged in. You won\'t be able to edit your proposal after publishing. Please <a href="%s">login here</a> to have full access to your proposal after publishing it.','my-transit-lines'),wp_login_url().'?redirect_to='.urlencode(add_query_arg(array('posttype'=>$postType),get_permalink())));
		$mtl_string['posttype-selector']['mtlproposal'] = __('Add a single proposal','my-transit-lines');
		$mtl_string['mail-subject']['mtlproposal']['add'] = __('New proposal','my-transit-lines');
		$mtl_string['mail-subject']['mtlproposal']['update'] = __('Updated proposal','my-transit-lines');
		$mtl_string['mail-text']['mtlproposal']['add'] = sprintf(__('A new proposal has been added to your site "%s".','my-transit-lines'),get_settings('blogname'));
		$mtl_string['mail-text']['mtlproposal']['update'] = sprintf(__('A proposal has been updated at your site "%s".','my-transit-lines'),get_settings('blogname'));
		$mtl_string['view-text']['mtlproposal'] = __('View proposal','my-transit-lines');
		$mtl_string['view-here-text']['mtlproposal'] = __('See your proposal here','my-transit-lines');
		$mtl_string['edit-text']['mtlproposal'] = __('Edit proposal','my-transit-lines');
		$mtl_string['check-content']['mtlproposal']['add'] = __('The proposal has already been published. Please check if everything\'s alright with it.','my-transit-lines');
		$mtl_string['check-content']['mtlproposal']['update'] = __('Please have a look at the updated proposal to see if everything\'s alright with it.','my-transit-lines');
		$mtl_string['success-notice']['mtlproposal']['add'] = __( 'Thank you! Your proposal has been added successfully.', 'my-transit-lines' );
		$mtl_string['success-notice']['mtlproposal']['update'] = __( 'Thank you! Your proposal has been updated successfully.', 'my-transit-lines' );
		$mtl_string['success-save-only-notice']['mtlproposal'] = sprintf(__( 'Thank you! Your proposal has saved, but is not visible for the public. You can edit it again via the %1$s"My proposals" menu%2$s.', 'my-transit-lines' ),'<a href="'.get_permalink($mtl_options3['mtl-proposal-page-id']).'?mtl-userid='.get_current_user_id().'&show-drafts=true">','</a>');
		$mtl_string['failure-notice']['mtlproposal']['add'] = __( 'Your proposal couldn\'t be added.', 'my-transit-lines' );
		$mtl_string['form-title']['mtlproposal'] = __( 'Title of your proposal', 'my-transit-lines' );
		$mtl_string['form-description']['mtlproposal'] = __( 'Description of your proposal', 'my-transit-lines' );
		$mtl_string['form-submit']['mtlproposal']['add'] = __( 'Add your proposal', 'my-transit-lines' );
		$mtl_string['form-submit']['mtlproposal']['update'] = __( 'Update your proposal', 'my-transit-lines' );
		$mtl_string['form-submit-save-only']['mtlproposal'] = __( 'Save proposal as draft', 'my-transit-lines' );
		$mtl_string['add-new']['mtlproposal']  = __( 'Add new proposal', 'my-transit-lines' );

		$output = '';
		$action = '';
		if(isset($_POST['action'])) $action = $_POST['action'];
		
		// output a notice for not logged in users
		if(!is_user_logged_in() &&  'POST' != $_SERVER['REQUEST_METHOD'] && empty( $action )) {
			$output .= '<p class="important-notice">'.$mtl_string['logged-out-notice'][$postType].'</p>';
		}
		
		// start output of post form
		$output .= '<div id="mtl-post-form">'."\r\n";
			
		$err = false;
		
		$edit_post = get_post($editId);
		$old_status = $edit_post->post_status;
		
		$status = 'draft';
		if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $action )) {
			if (strlen(trim($_POST['title']))<=2) $err['title']=true;
			if (!is_user_logged_in()) {
				if (strlen(trim($_POST['authname']))<=2) $err['authname']=true;
				if (!$_POST['authemail']) $err['authemail']=true;
				if (strlen(trim($_POST['authemail']))>0 && !ereg("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$",$_POST['authemail'])) $err['authemail_valid']=true;
			}
			if ($postType == 'mtlproposal') {
				if(!isset($_POST['cat'])) $err['cat']=true;
			}
			
			if (strlen(trim($_POST['description']))<=2) $err['description']=true;
			if (!is_user_logged_in()) {
				if (!$_POST['dataprivacy']) $err['dataprivacy']=true;
				if ($_POST['code'] != $_SESSION['rand_code']) $err['captcha']=true;
			}
			if($err) $_POST['errorcheck'] = true;
			$status = 'publish';
			
			if(!$err) {
				
				// Add the content of the form to $post as an array
				if($editId) $this_posttype = get_post_type($editId);
				else $this_posttype = $postType;
				$post = array(
					'ID' => $editId,
					'post_title'	=> $_POST['title'],
					'post_content'	=> $_POST['description'],
					'post_category'	=> array($_POST['cat']),
					'post_status'	=> $status,
					'post_type'	=> $this_posttype
				);
				
				if($old_status == 'draft') {
					$local_time  = current_datetime();
					$current_date = wp_date('Y-m-d H:i',$local_time->getTimestamp());
					$post['post_date'] = $current_date;
				}
								
				// insert/update the current post
				if($editId) $current_post_id = wp_update_post($post);
				else $current_post_id = wp_insert_post($post);
				
				// dear user, your IP please :)
				if (isSet($_SERVER)) {
					if (isSet($_SERVER["HTTP_X_FORWARDED_FOR"])) $realip = $_SERVER["HTTP_X_FORWARDED_FOR"];
					elseif (isSet($_SERVER["HTTP_CLIENT_IP"])) $realip = $_SERVER["HTTP_CLIENT_IP"];
					else $realip = $_SERVER["REMOTE_ADDR"];
				}
				else {
					if (getenv('HTTP_X_FORWARDED_FOR')) $realip = getenv('HTTP_X_FORWARDED_FOR');
					elseif (getenv('HTTP_CLIENT_IP')) $realip = getenv('HTTP_CLIENT_IP');
					else $realip = getenv('REMOTE_ADDR');
				}
				
				// update/add personal data of unregistered users
				if (!is_user_logged_in()) {
					update_post_meta($current_post_id, 'author-name', $_POST['authname']);
					update_post_meta($current_post_id, 'author-email', $_POST['authemail']);
				}

				// update/add all other needed post meta
				update_post_meta($current_post_id, 'mtl-author-ip', $realip);
				update_post_meta($current_post_id, 'mtl-feature-data', $_POST['mtl-feature-data']);
				update_post_meta($current_post_id, 'mtl-feature-labels-data', $_POST['mtl-feature-labels-data']);
				update_post_meta($current_post_id, 'mtl-count-stations', $_POST['mtl-count-stations']);
				update_post_meta($current_post_id, 'mtl-line-length', $_POST['mtl-line-length']);
				delete_post_meta($current_post_id, 'mtl-proposal-phase');

				// delete this for future versions
				if($_POST['mtl-proposal-phase'] != 'elaboration-phase') {
					delete_post_meta($current_post_id,'mtl-under-construction','on');
					delete_post_meta($current_post_id,'mtl-under-construction','');
				}
				
				// enable/disable contact button for current user
				if(is_user_logged_in()) {
					$userid = get_current_user_id();
					update_user_meta($userid,'enable-contact-button',$_POST['enable-contact-button']);
				}
				
				// preparing user info for mail notification			
				global $current_user;
				get_currentuserinfo();
				$author_name = $current_user->user_login;
				if(!$author_name) $author_name = get_post_meta($current_post_id,'author-name',true);
				$author_email = $current_user->user_email;
				if(!$author_email) $author_email = get_post_meta($current_post_id,'author-email',true);
				
				// mail data
				$to = get_settings('admin_email');
				$headers = 'From: '.get_settings('blogname').' <noreply@'.mtl_maildomain().'>' . "\r\n";
				$subject = '['.get_settings('blogname').'] '.$mtl_string['mail-subject'][$postType][$editType];
				$message = $mtl_string['mail-text'][$postType][$editType]."\r\n\r\n";
				$message .= __('Author name','my-transit-lines').': ' . $author_name . "\r\n";
				$message .= __('Author e-mail','my-transit-lines').': ' . $author_email . "\r\n";
				$message .= __('Title','my-transit-lines').': ' . $_POST['title'] . "\r\n";
				$message .= __('Description','my-transit-lines').': ' . $_POST['description'] . "\r\n\r\n";
				$message .= $mtl_string['view-text'][$postType].':'."\r\n".get_permalink($current_post_id)."\r\n\r\n";
				$message .= $mtl_string['edit-text'][$postType].':'."\r\n".get_admin_url().'post.php?post='.$current_post_id.'&action=edit'."\r\n\r\n";
				$message .= $mtl_string['check-content'][$postType][$editType];
				
				wp_mail($to,$subject,$message,$headers);
				$output .= '<div class="success-message-block">'."\r\n";
				if(!isset($_POST['submit-save-only'])) {
					$output .= '<strong>'.$mtl_string['success-notice'][$postType][$editType].'</strong><br />'."\r\n";
					$output .= '<a href="'.get_permalink($current_post_id).'" title="'.$mtl_string['view-text'][$postType].'">'.$mtl_string['view-here-text'][$postType].'</a>'."\r\n";
				}
				elseif(isset($_POST['submit-save-only'])) {
					$output .= '<strong>'.$mtl_string['success-save-only-notice'][$postType].'</strong><br />'."\r\n";
				}
				$output .= '</div>'."\r\n";
				if($editId) unset($editId);
			}
		} // end IF
		$form_token = uniqid();
		$_SESSION['form_token'] = $form_token;	// Do the wp_insert_post action to insert it
		do_action('wp_insert_post', 'wp_insert_post');
		
		if($err) {
			$output .= '<div class="error-message-block">'."\r\n";
			$output .= '<strong>'.$mtl_string['failure-notice'][$postType][$editType].'</strong><br />'."\r\n";
			$output .= __( 'Please verify the following errors:', 'my-transit-lines' ).'<br />'."\r\n";
			$output .= '<ul>'."\r\n";
			if(isset($err['authname'])) $output .= '<li>'.__( 'Please insert your name or nickname', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['cat'])) $output .= '<li>'.__( 'Please select a category', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['authemail']) && !$err['authemail_valid']) $output .= '<li>'.__('Please insert your e-mail address','my-transit-lines').'</li>'."\r\n";
			if(isset($err['authemail_valid'])) $output .= '<li>'.__('Please insert a valid e-mail address', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['title'])) $output .= '<li>'.__('Please insert a title', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['description'])) $output .= '<li>'.__( 'Please insert a description', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['dataprivacy'])) $output .= '<li>'.__( 'Please check that you read our data privacy conditions', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['captcha'])) $output .= '<li>'.__( 'You didn\'t enter the right captcha code', 'my-transit-lines' ).'</li>'."\r\n";
			$output .= '</ul>'."\r\n";
			$output .= __( 'Please fill out/correct the data and press the send button again.', 'my-transit-lines' )."\r\n";
			$output .= '</div>'."\r\n";
		}
		
		if((!$action || $err) && !$hideform) {
			$output .= '<form id="new_post" name="new_post" method="post" action="" enctype="multipart/form-data" onsubmit=" warningMessage = \'\' ">'."\r\n";
			$output .= '<p><label for="title"><strong>'.$mtl_string['form-title'][$postType].'</strong><br />'."\r\n";
			
			// input field title with value set to title from post variables, if existing
			$currentTitle = '';
			if(isset($_POST['title']) && $err) $currentTitle = $_POST['title'];
			elseif($editId && get_the_title($editId) && !$err) $currentTitle = get_the_title($editId); 
			$output .= '<input type="text" id="title" value="'.($currentTitle ? $currentTitle : '').'" name="title" /></label></p>'."\r\n";
		
			// start mtl editor map
			$output .= "\r";
			$output .= '<div id="mtl-box">'."\r\n";
			$mtl_feature_data = '';
			$mtl_feature_labels_data = '';
			if($editId && !$err) {
				$mtl_feature_data =  get_post_meta($editId,'mtl-feature-data',true);
				$mtl_feature_labels_data =  get_post_meta($editId,'mtl-feature-labels-data',true);
			}
			elseif($err && $_POST['mtl-feature-data']) {
				$mtl_feature_data = $_POST['mtl-feature-data'];
				$mtl_feature_labels_data = $_POST['mtl-feature-labels-data'];
			}
			
			$output .= '<script type="text/javascript"> var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = ["'.$mtl_feature_data.'"]; var vectorLabelsData = ["'.$mtl_feature_labels_data.'"]; var vectorCategoriesData = [undefined]; var editMode = true; </script>'."\r\n";
			$all_categories=get_categories( 'show_option_none=Category&hide_empty=0&tab_index=4&taxonomy=category&orderby=slug' );
			
			// get the current category
			$current_category = get_the_category($editId);
		
			// save category style data to JS array
			$output .= '<script type="text/javascript"> var transportModeStyleData = {';
			
			$count_cats = 0;
			$output_later = '';
			foreach($all_categories as $single_category) {
				$catid = $single_category->cat_ID;
				if(str_replace('other','',$single_category->slug)!=$single_category->slug) $output_later .= 'strokeColor = "'.$mtl_options['mtl-color-cat'.$catid].'"; fillColor = "'.$mtl_options['mtl-color-cat'.$catid].'"; externalGraphicUrl = "'.$mtl_options['mtl-image-cat'.$catid].'"; externalGraphicUrlSelected = "'.$mtl_options['mtl-image-selected-cat'.$catid].'"; defaultCategory = "'.$catid.'";';
				if($mtl_options['mtl-use-cat'.$catid] == true) {
					if($count_cats) $output .= ',';
					$output .= $catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"]';
					$count_cats++;
				}
			}
			$output .= '}; </script>'."\r\n";
			$output .= '<script type="text/javascript"> '.$output_later.' var centerLon = "'.$mtl_options['mtl-center-lon'].'"; var centerLat = "'.$mtl_options['mtl-center-lat'].'"; var standardZoom = '.$mtl_options['mtl-standard-zoom'].'; </script>'."\r\n";
		
			// select transit mode and add map data for post type "mtlproposal"
			if($postType == 'mtlproposal') {
				$output .= '<p class="alignleft"><strong>'.__('Please select a transportation mode','my-transit-lines').'</strong><br /><span id="mtl-category-select"><span class="transport-mode-select">'."\r\n";
		
				$checkedAlready = false;

				// getting all categories for selected as transit mode categories, set the given category option to checked
				foreach($all_categories as $single_category) {
					if($mtl_options['mtl-use-cat'.$single_category->cat_ID] == true) {
						$checked='';

						if (($err && isset($_POST['cat']) && $single_category->cat_ID == $_POST['cat']) ||
							(!$err && $editId && $single_category->cat_ID == $current_category[0]->term_id) ||
							(str_contains($single_category->slug, 'other') && !$checkedAlready)) {
								$checked = ' checked="checked"';
								$checkedAlready = true;
						}
						
						$output .= '<label class="mtl-category"><input'.$checked.' class="cat-select" onclick="redraw()" type="radio" name="cat" value="'.$single_category->cat_ID.'" id="cat-'.$single_category->slug.'" /> '.$single_category->name.'</label>'."\r\n";
					}
				}
				
				$output .= '</span><span class="transport-mode-select-inactive">'.__('Please select another tool<br /> to change the transport mode','my-transit-lines').'</span></span></p>'."\r\n";
				$output .= '<p class="alignleft no-bottom-margin"><strong>'.__('Please draw the line and/or the stations into the map','my-transit-lines').'</strong></p>'."\r\n";
				$output .= '<p class="alignleft no-bottom-margin symbol-texts">'.__('Hints for the usage of the respective tool are shown below the map.','my-transit-lines').'</p>'."\r\n";
				$output .= '<link rel="stylesheet" href="'.get_template_directory_uri().'/openlayers/ol.css">'."\r\n";
				$output .= '<div id="mtl-map-box">'."\r\n";
				$output .= '<div id="mtl-map"></div>'."\r\n";
				$output .= '<div class="feature-textinput-box"><label for="feature-textinput">'.__('Station name (optional)','my-transit-lines').': <br /><input type="text" name="feature-textinput" id="feature-textinput" onkeydown="var k=event.keyCode || event.which; if(k==13) { event.preventDefault(); }" /></label><br /><span class="set-name">'.__('Set new name', 'my-transit-line').'</span></div>'."\r\n";
				$output .= '</div>';
				$output .= mtl_localize_script(true);
				wp_enqueue_script('mtl-proposal-form', get_template_directory_uri().'/modules/mtl-proposal-form/mtl-proposal-form.js', array('my-transit-lines'), wp_get_theme()->version, true);
				$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="toggleMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="toggleMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span></p>'."\r\n";
				$output .= '<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'.__('Fit proposition to map','my-transit-lines').'</a></p>';
				$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:toggleFullscreen()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
				$output .= '<p class="alignright" id="mtl-toggle-labels"><label style="text-align: right;"><input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></p>'."\r\n";
				$output .= '<p class="alignleft"><strong>'.__('Tool usage hints','my-transit-lines').'</strong>: ';
				$output .= '<span class="mtl-tool-hint none">'.__('Please use the tools at the top left corner of the map.<br /> Use the point symbol (top) to draw the stations and the line symbol (second from top) to draw the line.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Point">'.__('Click on the map to add stations.<br /> You can then add names to the station by using the select tool (third from bottom).','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint LineString">'.__('Click on the map to add a line.<br /> Every click adds a new point to the line. Doubleclick to finish drawing the line.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Polygon">'.__('Click on the map to add a polygon.<br /> Every click adds a new point to the polygon. Click the first point to finish drawing the polygon.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Circle">'.__('Click on the map to add a circle.<br /> <strong> Circles will not be saved! </strong> The first click sets the center, the second one sets the radius.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Modify">'.__('Click on features to edit them.<br /> You can move or modify the feature (move points, add points) with this tool.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Select">'.__('Use the tool to select features.<br /> Select a feature to add a name. With the delete tool (second from bottom) you can delete a selected feature.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Navigate">'.__('Use the selected tool to navigate the map.<br /> With this tool, no modification of the objects is possible.','my-transit-lines').'</span>';
				$output .= '</p>'."\r\n";
				$output .= '</div>'."\r\n";
				
				// GeoJSON import
				$import_hints = __('Please note: Only point and linestring features will be imported. Labels will be set if included as name property for the respective feature. The imported features will be appended to the existing features in your proposal. The coordinate system of the import file must be WGS84/EPSG:4326 (the standard projection of OpenStreetMap tools).','my-transit-lines');
				if(trim($mtl_options3['mtl-geojson-import-hints'])) $import_hints = $mtl_options3['mtl-geojson-import-hints'];
				
				
				$output .= '<p><label for="mtl-import-geojson"><strong>'.__('Import GeoJSON file','my-transit-lines').'</strong><br>
							<input type="file" name="mtl-import-geojson" id="mtl-import-geojson" accept=".geojson,.json" multiple="true">
							<script type="text/javascript"> document.querySelector("#mtl-import-geojson").addEventListener("change", function() { importJSONFiles(document.querySelector("#mtl-import-geojson")); }); </script>
							</label>
							</p>
							<p style="text-align:left"><small>'.$import_hints.'</small></p>';

				// editor's hints
				if(is_user_author() && $mtl_options2['mtl-current-project-phase']=='rate' && get_post_type($editId)=='mtlproposal') {
					$output .= '<div class="editors-hints-box">';
					$output .= '<h4>'.__('Editor\'s hints for this proposal','my-transit-lines').'</h4>';
					if(get_post_meta($editId,'mtl-editors-hints',true)) $output .= '<p>'.get_post_meta($editId,'mtl-editors-hints',true).'</p>';
					else $output .= '<p><em>'.__('No editor\'s hints yet','my-transit-lines').'</em></p>';
					$output .= '</div>';
				}
			
				// hidden input fields to save feature data
				$output .= '<input type="hidden" id="mtl-feature-data" value="'.$mtl_feature_data.'" name="mtl-feature-data" />'."\r\n";
				$output .= '<input type="hidden" id="mtl-feature-labels-data" value="'.$mtl_feature_labels_data.'" name="mtl-feature-labels-data" />'."\r\n";
			
				// hidden input field for station count
				$mtl_count_stations = '';
				if($editId && !$err) $mtl_count_stations =  get_post_meta($editId,'mtl-count-stations',true);
				elseif($err && $_POST['mtl-count-stations']) $mtl_count_stations = $_POST['mtl-count-stations'];
				$output .= '<input type="hidden" id="mtl-count-stations" value="'.$mtl_count_stations.'"  name="mtl-count-stations" />'."\r\n";
				
				// hidden input field for line length
				$mtl_line_length = '';
				if($editId && !$err) $mtl_line_length =  get_post_meta($editId,'mtl-line-length',true);
				elseif($err && $_POST['mtl-line-length']) $mtl_line_length = $_POST['mtl-line-length'];	
				$output .= '<input type="hidden" id="mtl-line-length" value="'.$mtl_line_length.'"  name="mtl-line-length" />'."\r\n";
			} // end if $postType == 'mtlproposal'
			
			// continue form: description textbox, filled with text from post variable, if existing
			$current_description = '';
			$output .= '<p class="alignleft"><label for="description"><strong>'.$mtl_string['form-description'][$postType].'</strong></label>'."\r\n";
			if($err && $_POST['description']) $current_description = $_POST['description'];
			elseif($editId && !$err) $current_description = get_post($editId)->post_content;
			$settings = array( 'media_buttons' => false,  'textarea_name' => 'description','teeny'=>true);
			$output .= mtl_load_wp_editor($current_description,'description',$settings);
			
			if (!is_user_logged_in()) {
				$output .= '<h3>'.__( 'Your personal data', 'my-transit-lines' ).'</h3>'."\r\n";
				$output .= '<p><label for="authname"><strong>'.__( 'Your name or nickname', 'my-transit-lines' ).'</strong></label>'."\r\n";
				$output .= '<input type="text" id="authname" value="'.$_POST['authname'].'" name="authname" />'."\r\n";
				$output .= '<p><label for="authmail"><strong>'.__( 'Your e-mail address', 'my-transit-lines' ).'</strong></label>'."\r\n";
				$output .= '<input type="text" id="authmail" value="'.$_POST['authemail'].'" name="authemail" /></p>'."\r\n";
		
				// captcha
				$output .= '<p class="alignleft"><img src="'.get_template_directory_uri().'/modules/mtl-proposal-form/captcha.php"/><br />'."\r\n";
				$output .= '<label for="mtl_input_code" class="lapkarte-code"><strong>'.__("Please enter the captcha code (case-sensitive)",'my-transit-lines').'</strong></label>'."\r\n";
				$output .= '<input type="text" name="code" id="mtl_input_code" autocomplete="off" /></p>'."\r\n";
				$output .= '<p><label class="dataprivacy" for="dataprivacy"><strong>'.sprintf( __( 'I have read and accepted the <a href="%s">data privacy statement</a>:','my-transit-lines' ), esc_url(get_permalink(111)) ).' &nbsp; <input type="checkbox" name="dataprivacy"'.($_POST['dataprivacy'] ? ' checked="ckecked"' : '').' /></strong></label></p>'."\r\n";
			}
			else {
				$userid = get_current_user_id();
				$enable_checked = false;
				if(get_user_meta($userid,'enable-contact-button',true)) $enable_checked = true;
				if(isset($_POST['enable-contact-button'])) {
					if($_POST['enable-contact-button']) $enable_checked = true;
					else $enable_checked = false;
				}
				else {
					if('POST' == $_SERVER['REQUEST_METHOD']) $enable_checked = false;
				}
				$output .= '<p class="alignleft">&nbsp;<br /><label for="enable-contact-button"><input type="checkbox" id="enable-contact-button" '.($enable_checked ? 'checked="checked"' : '').'name="enable-contact-button" /> <strong>'.esc_html__('Enable contact button for my finished proposals','my-transit-lines').'</strong></label>
				<small>'.esc_html__('This enables a contact button within your proposals linked to a contact form where interested people can contact you. On submit, an email with the form data is being sent to you (and in copy to the admin team). Your email address is not visible to the respective person until you reply to her/him. The button is not visible as long as your prposal is still in elaboration phase.','my-transit-lines').' <strong>'.esc_html__('Important: This global option is being set for all of your finished proposals, not only for this one.','my-transit-lines').'<strong></small></p>'."\r\n";
			}
			// send post
			$submit_editType = $editType;
			$edit_post = get_post($editId);
			if($edit_post->post_status == 'draft' || get_post_meta($editId,'mtl-proposal-phase',true) == 'elaboration-phase') $submit_editType = 'add';
			
			$output .= '<p id="submit-box">&#160;<br />'.(!$editId || $edit_post->post_status == 'draft' || get_post_meta($editId,'mtl-proposal-phase',true) == 'elaboration-phase' ? '<input type="submit" class="save-only" value="'.$mtl_string['form-submit-save-only'][$postType].'" tabindex="6" id="submit-save-only" name="submit-save-only" /> ' : '').'<input type="submit" value="'.$mtl_string['form-submit'][$postType][$submit_editType].'" tabindex="6" id="submit" name="submit" /></p>'."\r\n";
			if($editId && ($edit_post->post_status == 'draft' || get_post_meta($editId,'mtl-proposal-phase',true) == 'elaboration-phase')) $output .= '<p><input type="submit" class="delete-draft" value="'.esc_html__('Delete this draft','my-transit-lines').'" tabindex="7" id="delete-draft" name="delete-draft" /></p>'."\r\n";
			if($editType=='update') $output .= '<a href="'.get_permalink($editId).'">'.__('Cancel update','my-transit-lines').'</a>';
			$output .= '<input type="hidden" name="action" value="post" />'."\r\n";
			$output .= '<input type="hidden" name="form_token" value="'.$form_token.'" />'."\r\n";
			$output .= '<input type="hidden" name="delete_id" value="'.$editId.'" />'."\r\n";
			wp_nonce_field( 'new-post' );
			$output .= '</form>'."\r\n";
		}
		else if(!$hideform) $output .= '<a href="'.get_permalink($mtl_options['mtl-addpost-page']).'">'.$mtl_string['add-new'][$postType].'</a>'."\r\n";
		
		$output .= '</div>'."\r\n";
		$output .= '<br>';
		if($editId) $output .= '<script type="text/javascript"> setTitle("'.$mtl_string['edit-text'][$postType].'"); </script>';
		
		$output .= '<script type="text/javascript"> var suggestUrl = "'.get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=ajax-tag-search&amp;tax=mtl-tag"; </script>';
		
		if(is_user_logged_in()) return $output;
		
	}
	else {
		if(!is_user_logged_in()) {
			if(!intval($_GET['edit_proposal'])) return '<p><strong>'.sprintf(__('You must be logged in to add a new proposal. If you already have an account at "%1$s" or want to login using Facebook, Google, OpenID or Twitter, you can <a href="%2$s">login here</a>. Otherwise <a href="%3$s">create your "%1$s" account here</a>.','my-transit-lines'),get_bloginfo('name'),wp_login_url(curPageURL()), wp_registration_url()).'</strong></p>';
			else return '<p><strong>'.sprintf(__('You must <a href="%2$s">login here</a> to edit your proposal.','my-transit-lines'),get_bloginfo('name'),wp_login_url(curPageURL()), wp_registration_url()).'</strong></p>';
		}
		else {
			if(!get_more_drafts_allowed()) {
				$list_posts = '<ul>';
				for($i = 0;$i<=1;$i++) {
					if($i==0) $query_name = get_drafts_query();
					else $query_name = get_elaboration_phase_query();
					while($query_name->have_posts()) {
						$query_name->the_post();
						global $post;
						$list_posts .= '<li><a href="'.add_query_arg('edit_proposal',$post->ID,get_permalink($mtl_options['mtl-addpost-page'])).'">'.get_the_title().'</a></li>';
					}
				}
				$list_posts .= '<ul>';
				wp_reset_postdata();
				return '<div class="error-message-block"><p>'.esc_html__('You have reached your limit of drafts. Please publish or delete at least one of your drafts to start a new proposal. These are your current drafts:','my-transit-lines').'</p>'.$list_posts.'</div>';
			}
			elseif(isset($_POST['delete-draft'])) {
				$delete_id = intval($_POST['delete_id']);
				$output = '<div class="success-message-block">'."\r\n";
				$output .= '<strong>'.sprintf(esc_html__('Are your sure you want to delete the draft of your proposal "%s"? There is no going back.','my-transit-lines'),get_the_title($delete_id)).'</strong><br />'."\r\n";
				$output .= '<br /><form id="delete_post" name="delete_post" method="post" action="" enctype="multipart/form-data"><input type="hidden" name="deleteid" value="'.$delete_id.'" /><input type="submit" name="really-delete-draft" value="'.esc_html__('Yes, definetily delete this draft','my-transit-lines').'"></form><br />';
				$output .= '<br /><a href="'.add_query_arg('edit_proposal',$delete_id,get_permalink($mtl_options['mtl-addpost-page'])).'">'.esc_html__('No, I do not want to delete the draft','my-transit-lines').'</a><br />';
				$output .= '</div>'."\r\n";
				return $output;
			}
			elseif(isset($_POST['really-delete-draft'])) {
				$delete_id = intval($_POST['deleteid']);
				$delete_post = get_post($delete_id);
				if($delete_post->post_status == 'draft' && wp_get_current_user()->ID == $delete_post->post_author) {
					wp_delete_post($delete_id);
					$output = '<div class="success-message-block">'."\r\n";
					$output .= '<strong>'.esc_html__('Draft successfully deleted!','my-transit-lines').'</strong><br />'."\r\n";
					$output .= '</div>'."\r\n";
					return $output;
				} else {
					$output = '<div class="error-message-block"><p>'.esc_html__('Couldn\'t delete the proposal. Is it a draft and are you logged in and the author?','my-transit-lines').'</p></div>';
					return $output;
				};
			}
		}		
	}
}
add_shortcode( 'mtl-proposal-form', 'mtl_proposal_form_output' );

/**
 * Returns the amount of drafts the current user has already created
 *
 * @return int
 */
function get_drafts_count() {
	return get_drafts_query()->post_count + get_elaboration_phase_query()->post_count;
}

/**
 * Returns a WP_Query of all the drafts the current user has created
 *
 * @return WP_Query
 */
function get_drafts_query() {
	$drafts_query_string =  array(
	'posts_per_page' => -1,
	'post_type' => 'mtlproposal',
	'author' => get_current_user_id(),
	'post_status' => 'draft',
	);
	return new WP_Query($drafts_query_string);
}

/**
 * Returns a WP_Query of all the elabortion phase proposals the current user has created
 * 
 * @return WP_Query
 */
function get_elaboration_phase_query() {
	$elaboration_phase_query_string =  array(
		'posts_per_page' => -1,
		'post_type' => 'mtlproposal',
		'author' => get_current_user_id(),
		'meta_key' => 'mtl-proposal-phase',
		'meta_value' => 'elaboration-phase',
	);
	return new WP_Query($elaboration_phase_query_string);
}

/**
 * Returns the editId for the proposal to edit. Might be the id of a proposal or an empty string for a new proposal
 *
 * @return int|string
 */
function get_editId() {
	// editId to find out, defaults to an empty string
	$editId = '';

	// only if form allowed
	if(is_user_logged_in() && is_form_allowed() && !isset($_POST['delete-draft']) && !isset($_POST['really-delete-draft']) && is_user_author() && !is_rating_possible()) {
		$editId = get_id();
	}
	return $editId;
}

function is_rating_possible() {
	return is_edit() && get_option('mtl-option-name2')['mtl-current-project-phase'] == 'rate' && get_post_meta(get_id(), 'mtl-proposal-rateable');
}

/**
 * Returns true iff the current user is the author of the post to edit
 *
 * @return boolean
 */
function is_user_author() {
	if(is_edit()) {
		$get_id = get_id();
		$current_post = get_post($get_id);
		$current_user = wp_get_current_user();
		$author_id	  = $current_post->post_author;
		return ($author_id > 0 && $author_id == $current_user->ID);
	}
	return false;
}

/**
 * Returns true iff the user is allowed to have more drafts or is editing an existing proposal
 *
 * @return bool
 */
function get_more_drafts_allowed() {
	return (is_edit() || get_drafts_count() < get_option('mtl-option-name2')['mtl-allowed-drafts']);
}

/**
 * Returns true iff trying to edit an existing proposal
 * Does not check whether it's the right user or even any user
 *
 * @return bool
 */
function is_edit() {
	// prepare existing post data extraction for post editing
	$get_id = get_id();

	return (bool)$get_id && (bool)get_post_status($get_id);
}

/**
 * Returns the id of the proposal the user wants to edit (passed by get request URL parameter) or if none, an empty string
 *
 * @return int|string
 */
function get_id() {
	if(isset($_GET['edit_proposal'])) return intval($_GET['edit_proposal']);
	else return '';
}

/**
 * Returns true if the user is either logged in and editing an existing proposal or if new proposals and more drafts are allowed
 *
 * @return boolean
 */
function is_form_allowed() {
	return (is_user_logged_in() && is_edit()) || (get_option('mtl-option-name2')['mtl-prevent-new-proposals']!='on' && get_more_drafts_allowed());
}

 /**
 * shortcode [hide-if-editmode]
 */
function hide_if_editmode_output( $atts, $content ){
	$edit_proposal = '';
	if(isset($_POST['action'])) $action = $_POST['action'];
	if(isset($_GET['edit_proposal']) || !empty( $action )) $hideThis = true;
	if(!$hideThis) return $content;
	else return;
}
add_shortcode( 'hide-if-editmode', 'hide_if_editmode_output' );

 /**
 * shortcode [hide-if-not-editmode]
 */
function hide_if_not_editmode_output( $atts, $content ){
	$edit_proposal = '';
	if(isset($_POST['action'])) $action = $_POST['action'];
	if(!isset($_GET['edit_proposal']) || !empty( $action )) $hideThis = true;
	if(!$hideThis) return $content;
	else return;
}
add_shortcode( 'hide-if-not-editmode', 'hide_if_not_editmode_output' );

 /**
 * shortcode [hide-if-not-logged-in]
 */
 function hide_if_not_logged_in( $atts, $content ){
	if(is_user_logged_in()) return do_shortcode($content);
}
add_shortcode( 'hide-if-not-logged-in', 'hide_if_not_logged_in' );

?>