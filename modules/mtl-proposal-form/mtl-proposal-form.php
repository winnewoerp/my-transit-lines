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
	$mtl_options3 = get_option('mtl-option-name3');
				
	// GeoJSON import
	$import_hints = __('Please note: Only point and linestring features will be imported. Labels will be set if included as name property for the respective feature. The imported features will be appended to the existing features in your proposal. The coordinate system of the import file must be WGS84/EPSG:4326 (the standard projection of OpenStreetMap tools).','my-transit-lines');
	if(trim($mtl_options3['mtl-geojson-import-hints'])) $import_hints = $mtl_options3['mtl-geojson-import-hints'];

	extract( shortcode_atts( [
		'center_lon' => $mtl_options['mtl-center-lon'],
		'center_lat' => $mtl_options['mtl-center-lat'],
		'standard_zoom' => $mtl_options['mtl-standard-zoom'],
		'import_hints' => $import_hints,
	], $atts ) );
	
	// get the posttype from url parameter or set to default
	$postType = 'mtlproposal';
	if(isset($_GET['posttype'])) $postType = $_GET['posttype'];

	$editId = get_editId();
	$editType = 'add';
	if ($editId) $editType = 'update';
			
	$err = [];

	$addpost_page_link = get_permalink(pll_get_post($mtl_options['mtl-addpost-page']));
	
	// only if form allowed
	if(is_form_allowed() && !isset($_POST['delete-draft']) && !isset($_POST['really-delete-draft'])) {

		// create all necessary strings for this page containing either post type or edit type 
		$mtl_string['logged-out-notice']['mtlproposal'] = sprintf(__('<strong>Important notice:</strong> You are about to write a proposal without being logged in. You won\'t be able to edit your proposal after publishing. Please <a href="%s">login here</a> to have full access to your proposal after publishing it.','my-transit-lines'),wp_login_url().'?redirect_to='.urlencode(add_query_arg(array('posttype'=>$postType),get_permalink())));
		$mtl_string['posttype-selector']['mtlproposal'] = __('Add a single proposal','my-transit-lines');
		$mtl_string['mail-subject']['mtlproposal']['add'] = __('New proposal','my-transit-lines');
		$mtl_string['mail-subject']['mtlproposal']['update'] = __('Updated proposal','my-transit-lines');
		$mtl_string['mail-text']['mtlproposal']['add'] = sprintf(__('A new proposal has been added to your site "%s".','my-transit-lines'),get_option('blogname'));
		$mtl_string['mail-text']['mtlproposal']['update'] = sprintf(__('A proposal has been updated at your site "%s".','my-transit-lines'),get_option('blogname'));
		$mtl_string['view-text']['mtlproposal'] = __('View proposal','my-transit-lines');
		$mtl_string['view-here-text']['mtlproposal'] = __('See your proposal here','my-transit-lines');
		$mtl_string['edit-text']['mtlproposal'] = __('Edit proposal','my-transit-lines');
		$mtl_string['check-content']['mtlproposal']['add'] = __('The proposal has already been published. Please check if everything\'s alright with it.','my-transit-lines');
		$mtl_string['check-content']['mtlproposal']['update'] = __('Please have a look at the updated proposal to see if everything\'s alright with it.','my-transit-lines');
		$mtl_string['success-notice']['mtlproposal']['add'] = __( 'Thank you! Your proposal has been added successfully.', 'my-transit-lines' );
		$mtl_string['success-notice']['mtlproposal']['update'] = __( 'Thank you! Your proposal has been updated successfully.', 'my-transit-lines' );
		$mtl_string['success-save-only-notice']['mtlproposal'] = sprintf(__( 'Thank you! Your proposal has saved, but is not visible for the public. You can edit it again via the %1$s"My proposals" menu%2$s.', 'my-transit-lines' ),'<a href="'.get_permalink(pll_get_post($mtl_options['mtl-postlist-page'])).'?mtl-userid='.get_current_user_id().'&show-drafts=true">','</a>');
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
		
		// start output of post form
		$output .= '<div id="mtl-post-form">'."\r\n";
		
		$edit_post = get_post($editId);
		$old_status = $edit_post->post_status;
		
		$status = 'draft';
		if( 'POST' == $_SERVER['REQUEST_METHOD'] && !empty( $action )) {
			if (!isset($_POST['title']) || strlen(trim($_POST['title']))<=2) $err['title']=true;
			if ($postType == 'mtlproposal') {
				if(!isset($_POST['cat'])) $err['cat']=true;
			}
			
			if (!isset($_POST['description']) || strlen(trim($_POST['description']))<=2) $err['description']=true;
			if($err) $_POST['errorcheck'] = true;

			if(!isset($_POST['submit-save-only'])) {
				$status = 'publish';
			}
			
			if(!$err) {
				// Add the content of the form to $post as an array
				if($editId) $this_posttype = get_post_type($editId);
				else $this_posttype = $postType;
				$post = array(
					'ID' => $editId,
					'post_title'	=> esc_html($_POST['title']),
					'post_content'	=> $_POST['description'],
					'post_category'	=> array($_POST['cat']),
					'post_status'	=> $status,
					'post_type'		=> $this_posttype,
				);

				$post['tags_input'] = explode(',', $_POST['mtl-tags']);
				
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

				// update/add all other needed post meta
				update_post_meta($current_post_id, 'mtl-author-ip', $realip);
				update_post_meta($current_post_id, 'mtl-count-stations', $_POST['mtl-count-stations']);
				update_post_meta($current_post_id, 'mtl-line-length', $_POST['mtl-line-length']);
				update_post_meta($current_post_id, 'mtl-features', $_POST['mtl-features']);
				update_post_meta($current_post_id, 'mtl-costs', $_POST['mtl-costs']);

				delete_post_meta($current_post_id, 'mtl-feature-data');
				delete_post_meta($current_post_id, 'mtl-feature-labels-data');
				
				do_action('wp_insert_post', $current_post_id, get_post($current_post_id), true);
				
				// enable/disable contact button for current user
				$userid = get_current_user_id();
				update_user_meta($userid,'enable-contact-button',$_POST['enable-contact-button']);
				
				// preparing user info for mail notification			
				$current_user = wp_get_current_user();
				$author_name = $current_user->user_login;
				if(!$author_name) $author_name = get_post_meta($current_post_id,'author-name',true);
				$author_email = $current_user->user_email;
				if(!$author_email) $author_email = get_post_meta($current_post_id,'author-email',true);
				
				// mail data
				$to = get_option('admin_email');
				$headers = 'From: '.get_option('blogname').' <noreply@'.mtl_maildomain().'>' . "\r\n";
				$subject = '['.get_option('blogname').'] '.$mtl_string['mail-subject'][$postType][$editType];
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
				} else {
					$output .= '<strong>'.$mtl_string['success-save-only-notice'][$postType].'</strong><br />'."\r\n";
				}
				$output .= '</div>'."\r\n";
				if($editId) unset($editId);
			}
		} // end IF
		$form_token = uniqid();
		$_SESSION['form_token'] = $form_token;
		
		if($err) {
			$output .= '<div class="error-message-block">'."\r\n";
			$output .= '<strong>'.$mtl_string['failure-notice'][$postType][$editType].'</strong><br />'."\r\n";
			$output .= __( 'Please verify the following errors:', 'my-transit-lines' ).'<br />'."\r\n";
			$output .= '<ul>'."\r\n";
			if(isset($err['cat'])) $output .= '<li>'.__( 'Please select a category', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['title'])) $output .= '<li>'.__('Please insert a title', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['description'])) $output .= '<li>'.__( 'Please insert a description', 'my-transit-lines' ).'</li>'."\r\n";
			if(isset($err['mtl-tags'])) $output .= '<li>'.__( 'Please insert location tags', 'my-transit-lines').'</li>'."\r\n";
			$output .= '</ul>'."\r\n";
			$output .= __( 'Please fill out/correct the data and press the send button again.', 'my-transit-lines' )."\r\n";
			$output .= '</div>'."\r\n";
		}
		
		if(!$action || $err) {
			$output .= '<form id="new_post" name="new_post" method="post" action="" enctype="multipart/form-data" onsubmit=" warningMessage = \'\' ">'."\r\n";
			$output .= '<p class="alignleft"><label for="title"><strong>'.$mtl_string['form-title'][$postType].'</strong><br />'."\r\n";
			
			// input field title with value set to title from post variables, if existing
			$currentTitle = '';
			if(isset($_POST['title']) && $err) $currentTitle = $_POST['title'];
			elseif($editId && get_the_title($editId) && !$err) $currentTitle = get_the_title($editId); 
			$output .= '<input type="text" id="title" value="'.($currentTitle ? $currentTitle : '').'" name="title" /></label></p>'."\r\n";
		
			// start mtl editor map
			$output .= "\r";
			$mtl_features = '';
			if($editId && !$err) {
				$mtl_features = get_post_meta($editId, 'mtl-features', true);
			}
			elseif($err && $_POST['mtl-features']) {
				$mtl_features = $_POST['mtl-features'];
			}

			$output .= '<script type="text/javascript">var editMode = true; var themeUrl = "'.get_template_directory_uri().'"; var proposalList = ['.get_proposal_data_json($editId).']</script>'."\r\n";
			$active_categories = get_active_categories();

			// get the current category
			$current_category = get_the_category($editId);

			// save category style data to JS array
			$output .= get_transport_mode_style_data();

			$output .= '<script type="text/javascript"> ';

			foreach($active_categories as $single_category) {
				if(str_contains($single_category->slug, 'other')) $output .= 'defaultCategory = "'.$single_category->cat_ID.'";';
			}

			$output .= ' var centerLon = "'.$center_lon.'"; var centerLat = "'.$center_lat.'"; var standardZoom = '.$standard_zoom.'; </script>'."\r\n";

			if(trim($mtl_options3['mtl-country-source']))
				$output .= '<script type="text/javascript"> var countrySource = \''.str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-country-source'])).'\';'."\r\n";
			if(trim($mtl_options3['mtl-state-source']))
				$output .= 'var stateSource = \''.str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-state-source'])).'\';'."\r\n";
			if(trim($mtl_options3['mtl-district-source']))
				$output .= 'var districtSource = \''.str_replace(array("\r", "\n"), "", file_get_contents($mtl_options3['mtl-district-source'])).'\'; </script>'."\r\n";
		
			// select transit mode and add map data for post type "mtlproposal"
			if($postType == 'mtlproposal') {
				if (count($active_categories) == 1) {
					$single_category = $active_categories[0];
					$output .= '<input checked="checked" style="display:none;" class="cat-select" onclick="redraw()" type="radio" name="cat" value="'.$single_category->cat_ID.'" id="cat-'.$single_category->slug.'" />'."\r\n";
				} else {
					$output .= '<p class="alignleft"><strong>'.__('Please select a transportation mode','my-transit-lines').'</strong><br /><span id="mtl-category-select" data-mtl-toggle-fullscreen><span class="transport-mode-select">'."\r\n";

					$checkedAlready = false;
					// getting all categories for selected as transit mode categories, set the given category option to checked
					foreach ($active_categories as $single_category) {
						$single_catid = $single_category->cat_ID;
						$single_catname = __($single_category->name, 'my-transit-lines');
						$single_catslug = $single_category->slug;

						$checked = '';

						if (($err && isset($_POST['cat']) && $single_catid == $_POST['cat']) ||
							(!$err && $editId && $single_catid == $current_category[0]->term_id) ||
							(str_contains($single_catslug, 'other') && !$checkedAlready)) {
								$checked = ' checked="checked"';
								$checkedAlready = true;
						}

						$output .= '<label class="mtl-category"><input'.$checked.' class="cat-select" onclick="redraw()" type="radio" name="cat" value="'.$single_catid.'" id="cat-'.$single_catslug.'" /> '.$single_catname.'</label>'."\r\n";
					}
				}

				$output .= '</span></span></p>'."\r\n";
				$output .= '<p class="alignleft no-bottom-margin"><strong>'.__('Please draw the line and/or the stations into the map','my-transit-lines').'</strong></p>'."\r\n";
				$output .= '<p class="alignleft no-bottom-margin symbol-texts">'.__('Hints for the usage of the respective tool are shown below the map.','my-transit-lines').'</p>'."\r\n";
				$output .= '<div style="position:relative;">';
				$output .= the_map_output();
				$output .= '<div class="feature-textinput-box"><label for="feature-textinput">'.__('Station name (optional)','my-transit-lines').': <br /><input type="text" name="feature-textinput" id="feature-textinput" onkeydown="var k=event.keyCode || event.which; if(k==13) { event.preventDefault(); }" /></label><span class="set-name">'.__('Set new name', 'my-transit-lines').'</span></div>'."\r\n";
				$output .= '</div>';
				$output .= mtl_localize_script(true);
				wp_enqueue_script('mtl-proposal-form', get_template_directory_uri().'/modules/mtl-proposal-form/mtl-proposal-form.js', array('my-transit-lines'), wp_get_theme()->version, true);
				$output .= '<p class="alignleft"><strong>'.__('Tool usage hints','my-transit-lines').'</strong>: ';
				$output .= '<span class="mtl-tool-hint none">'.__('Please use the tools at the top left corner of the map.<br /> Use the point symbol (top) to draw the stations and the line symbol (second from top) to draw the line.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Point">'.__('Click on the map to add stations.<br /> You can then add names to the station by using the select tool (third from bottom).','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint LineString">'.__('Click on the map to add a line.<br /> Every click adds a new point to the line. Doubleclick to finish drawing the line.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Polygon">'.__('Click on the map to add a polygon.<br /> Every click adds a new point to the polygon. Click the first point to finish drawing the polygon.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Circle">'.__('Click on the map to add a circle.<br /> The first click sets the center, the second one sets the radius.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Modify">'.__('Click on features to edit them.<br /> You can move or modify the feature (move points, add points) with this tool.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Select">'.__('Use the tool to select features.<br /> Select a feature to add a name. With the delete tool (second from bottom) you can delete a selected feature.','my-transit-lines').'</span>';
				$output .= '<span class="mtl-tool-hint Navigate">'.__('Use the selected tool to navigate the map.<br /> With this tool, no modification of the objects is possible.','my-transit-lines').'</span>';
				$output .= '</p>'."\r\n";

				$output .= '<p class="alignleft"><label for="mtl-import-geojson"><strong>'.__('Import GeoJSON file','my-transit-lines').'</strong><br>
							<input type="file" name="mtl-import-geojson" id="mtl-import-geojson" accept=".geojson,.json" multiple="true">
							<script type="text/javascript"> document.querySelector("#mtl-import-geojson").addEventListener("change", function() { importJSONFiles(document.querySelector("#mtl-import-geojson")); }); </script>
							</label>
							</p>
							<p style="text-align:left"><small>'.$import_hints.'</small></p>';
			
				// hidden input field to save features
				$output .= '<input type="hidden" id="mtl-features" value="'.$mtl_features.'" name="mtl-features" />'."\r\n";
			
				// hidden input field for station count
				$mtl_count_stations = '';
				if($editId && !$err) $mtl_count_stations = get_post_meta($editId,'mtl-count-stations',true);
				elseif($err && $_POST['mtl-count-stations']) $mtl_count_stations = $_POST['mtl-count-stations'];
				$output .= '<input type="hidden" id="mtl-count-stations" value="'.$mtl_count_stations.'"  name="mtl-count-stations" />'."\r\n";
				
				// hidden input field for line length
				$mtl_line_length = '';
				if($editId && !$err) $mtl_line_length = get_post_meta($editId,'mtl-line-length',true);
				elseif($err && $_POST['mtl-line-length']) $mtl_line_length = $_POST['mtl-line-length'];	
				$output .= '<input type="hidden" id="mtl-line-length" value="'.$mtl_line_length.'"  name="mtl-line-length" />'."\r\n";

				// hidden input field for tags
				$mtl_tags = '';
				if($editId && !$err) {
					$posttags = get_the_tags($editId);
					if ($posttags) {
						foreach($posttags as $tag) {
							$mtl_tags .= $tag->name . ','; 
						}
					}
				}
				elseif($err && $_POST['mtl-tags']) $mtl_tags = $_POST['mtl-tags'];
				$output .= '<input type="hidden" id="mtl-tags" value="'.$mtl_tags.'" name="mtl-tags" />'."\r\n";

				// hidden input field for costs
				$mtl_costs = '0';
				if($editId && !$err) $mtl_costs = get_post_meta($editId,'mtl-costs',true);
				elseif($err && $_POST['mtl-costs']) $mtl_costs = $_POST['mtl-costs'];
				$output .= '<input type="hidden" id="mtl-costs" value="'.$mtl_costs.'" name="mtl-costs" />'."\r\n";
			} // end if $postType == 'mtlproposal'
			
			// continue form: description textbox, filled with text from post variable, if existing
			$current_description = '';
			$output .= '<p class="alignleft"><label for="description"><strong>'.$mtl_string['form-description'][$postType].'</strong></label>'."\r\n";
			if($err && $_POST['description']) $current_description = $_POST['description'];
			elseif($editId && !$err) $current_description = get_post($editId)->post_content;
			$settings = array( 'media_buttons' => false,  'textarea_name' => 'description','teeny'=>true);
			$output .= mtl_get_output(function() use ($current_description, $settings) {
				wp_editor($current_description, 'description', $settings);
			});
			
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

			// send post
			$submit_editType = $editType;
			$edit_post = get_post($editId);
			if($edit_post->post_status == 'draft') $submit_editType = 'add';
			
			$output .= '<p id="submit-box">&#160;<br />'.(!$editId || $edit_post->post_status == 'draft' ? '<input type="submit" class="save-only" value="'.$mtl_string['form-submit-save-only'][$postType].'" tabindex="6" id="submit-save-only" name="submit-save-only" /> ' : '').'<input type="submit" value="'.$mtl_string['form-submit'][$postType][$submit_editType].'" tabindex="6" id="submit" name="submit" /></p>'."\r\n";
			if($editId && $edit_post->post_status == 'draft') $output .= '<p><input type="submit" class="delete-draft" value="'.esc_html__('Delete this draft','my-transit-lines').'" tabindex="7" id="delete-draft" name="delete-draft" /></p>'."\r\n";
			if($editType=='update') $output .= '<a href="'.get_permalink($editId).'">'.__('Cancel update','my-transit-lines').'</a>';
			$output .= '<input type="hidden" name="action" value="post" />'."\r\n";
			$output .= '<input type="hidden" name="form_token" value="'.$form_token.'" />'."\r\n";
			$output .= '<input type="hidden" name="delete_id" value="'.$editId.'" />'."\r\n";
			if (!is_admin() && !defined('REST_REQUEST'))
				wp_nonce_field( 'new-post' );
			$output .= '</form>'."\r\n";
		}
		else $output .= '<a href="'.$addpost_page_link.'">'.$mtl_string['add-new'][$postType].'</a>'."\r\n";
		
		$output .= '</div>'."\r\n";
		$output .= '<br>';
		if(isset($editId) && $editId) $output .= '<script type="text/javascript"> setTitle("'.$mtl_string['edit-text'][$postType].'"); </script>';
		
		$output .= '<script type="text/javascript"> var suggestUrl = "'.get_bloginfo('wpurl').'/wp-admin/admin-ajax.php?action=ajax-tag-search&amp;tax=mtl-tag"; </script>';
		
		return $output;
	}
	else {
		if(!is_user_logged_in()) {
			if(!intval($_GET['edit_proposal'])) return '<p><strong>'.sprintf(__('You must be logged in to add a new proposal. If you already have an account at "%1$s" or want to login using Facebook, Google, OpenID or Twitter, you can <a href="%2$s">login here</a>. Otherwise <a href="%3$s">create your "%1$s" account here</a>.','my-transit-lines'),get_bloginfo('name'),wp_login_url(curPageURL()), wp_registration_url()).'</strong></p>';
			else return '<p><strong>'.sprintf(__('You must <a href="%2$s">login here</a> to edit your proposal.','my-transit-lines'),get_bloginfo('name'),wp_login_url(curPageURL()), wp_registration_url()).'</strong></p>';
		}
		else {
			if(!get_more_drafts_allowed()) {
				$list_posts = '<ul>';
				$query_name = get_drafts_query();
				while($query_name->have_posts()) {
					$query_name->the_post();
					global $post;
					$list_posts .= '<li><a href="'.add_query_arg('edit_proposal',$post->ID,$addpost_page_link).'">'.get_the_title().'</a></li>';
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
				$output .= '<br /><a href="'.add_query_arg('edit_proposal',$delete_id,$addpost_page_link).'">'.esc_html__('No, I do not want to delete the draft','my-transit-lines').'</a><br />';
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
 * Returns a WP_Query of all the drafts the current user has created
 *
 * @return WP_Query
 */
function get_drafts_query() {
	$drafts_query_string = array(
		'posts_per_page' => -1,
		'post_type' => 'mtlproposal',
		'author' => get_current_user_id(),
		'post_status' => 'draft',
	);
	return new WP_Query($drafts_query_string);
}

/**
 * Returns the editId for the proposal to edit. Might be the id of a proposal or an empty string for a new proposal
 *
 * @return int|string
 */
function get_editId() {
	// only if form allowed
	if(is_form_allowed() && !isset($_POST['delete-draft']) && !isset($_POST['really-delete-draft']) && is_user_author())
		return get_id();

	return '';
}

/**
 * Returns true iff the current user is the author of the post to edit
 *
 * @return boolean
 */
function is_user_author() {
	if (!is_edit())
		return false;

	$get_id = get_id();
	$current_post = get_post($get_id);
	$current_user = wp_get_current_user();
	$author_id	  = $current_post->post_author;
	return ($author_id > 0 && $author_id == $current_user->ID);
}

/**
 * Returns true iff the user is allowed to have more drafts or is editing an existing proposal
 *
 * @return bool
 */
function get_more_drafts_allowed() {
	return (is_edit() || get_drafts_query()->post_count < get_option('mtl-option-name3')['mtl-allowed-drafts']);
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
 * Returns true if the user is logged in and either editing an existing proposal or if new proposals and more drafts are allowed
 *
 * @return boolean
 */
function is_form_allowed() {
	return is_user_logged_in() && (is_edit() || get_more_drafts_allowed());
}

 /**
 * shortcode [hide-if-editmode]
 */
function hide_if_editmode_output( $atts, $content ) {
	if(isset($_GET['edit_proposal']) || !empty( $_POST['action']))
		return;

	return $content;
}
add_shortcode( 'hide-if-editmode', 'hide_if_editmode_output' );

 /**
 * shortcode [hide-if-not-editmode]
 */
function hide_if_not_editmode_output( $atts, $content ) {
	if (!isset($_GET['edit_proposal']) || !empty( $_POST['action']))
		return;

	return $content;
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