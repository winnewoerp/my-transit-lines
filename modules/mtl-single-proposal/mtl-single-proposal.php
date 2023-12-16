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
		$lineLength = max(get_post_meta($post->ID,'mtl-line-length',true), 0);
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
		$category_name = $category[0]->name;
		
		// load relevant scripts and set some JS variables
		$output .= "\r".'<link rel="stylesheet" href="'.get_template_directory_uri().'/openlayers/ol.css">'."\r\n";
		$output .= '<div id="mtl-box">'."\r\n";
		$output .= '<script type="text/javascript"> var transportModeStyleData = {'.$catid.' : ["'.$mtl_options['mtl-color-cat'.$catid].'","'.$mtl_options['mtl-image-cat'.$catid].'","'.$mtl_options['mtl-image-selected-cat'.$catid].'"]}; </script>';
		// Removing line breaks that can be caused by WordPress import/export
		$output .= '<script type="text/javascript"> var editMode = false; var themeUrl = "'. get_template_directory_uri() .'"; var vectorData = ["'.str_replace("\n", "", get_post_meta($post->ID,'mtl-feature-data',true)).'"]; var vectorLabelsData = ["'.str_replace("\n", "", get_post_meta($post->ID,'mtl-feature-labels-data',true)).'"]; var vectorCategoriesData = [undefined]; var editMode = false; </script>'."\r\n";
		
		// output the map box
		$output .= '<div id="mtl-map-box">'."\r\n";
		$output .= '<div id="mtl-map"></div>'."\r\n";
		$output .= '</div>';
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri().'/openlayers/dist/ol.js"></script>'."\r\n";
		$output .= mtl_localize_script(true);
		$output .= '<script type="text/javascript" src="'.get_template_directory_uri() . '/js/my-transit-lines.js"></script>'."\r\n";
		
		// output opacity change button, map fullscreen link and toggle label checkbox
		$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="setMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="setMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span></p>'."\r\n";
		$output .= '<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'.__('Fit proposition to map','my-transit-lines').'</a></p>';
		$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:toggleFullscreen()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
		$output .= '<p class="alignright" id="mtl-toggle-labels"><label><input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></p>'."\r\n";
		$output .= '</div>'."\r\n";
		
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
		
		// check for reCAPTCHA keys
		$use_recaptcha = false;
		if(trim($mtl_options3['mtl-recaptcha-website-key']) && trim($mtl_options3['mtl-recaptcha-secret-key'])) $use_recaptcha = true;

		// show proposal author contact form if user has enabled this and proposal is not under construction
		if(get_post_meta($post->ID,'mtl-proposal-phase',true) != 'elaboration-phase') {
			$authorid = get_the_author_ID();
			if(get_user_meta($authorid,'enable-contact-button',true)) {
				$output2 .= '
				<div class="proposal-author-contact-form" id="proposal-author-contact-form">
					'.($mtl_options3['mtl-proposal-contact-form-title'] ? '<h2>'.$mtl_options3['mtl-proposal-contact-form-title'].'</h2>' : '').'
					'.($mtl_options3['mtl-proposal-contact-form-intro'] ? '<p class="intro">'.$mtl_options3['mtl-proposal-contact-form-intro'].'</p>' : '');
					
					// handle the sent form data
					$err = false;
					if(isset($_POST['pacf-sent']) && $_POST['pacf-sent']) {
						
						// recaptcha stuff
						if(!$use_recaptcha || (isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response']))) {
							if($use_recaptcha) {
								$secret = $mtl_options3['mtl-recaptcha-secret-key'];
								$verifyResponse = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $secret . '&response=' . $_POST['g-recaptcha-response']);
								$responseData = json_decode($verifyResponse);
								if(!$responseData->success) {
									$output2 .= '
								<div class="error-message-block">
									<p><strong>'.esc_html__('Captcha validation failed.','my-transit-lines').'</strong></p>
								</div>';
									$err = true;
								}
							}
							
							// send message
							if(!$use_recaptcha || $responseData->success) {
								//  mail data
								global $current_user;
								get_currentuserinfo();
								$to = $current_user->user_email;
								$headers = 'From: '.sanitize_text_field(wp_unslash($_POST['pacf-first-name'])).' '.sanitize_text_field(wp_unslash($_POST['pacf-last-name'])).' <'.sanitize_text_field(wp_unslash($_POST['pacf-email-address'])).'>' . "\r\n";
								if($_POST['pacf-privacy-admin-mail']) {
									$headers .= 'CC: Linie Plus Admin Team <'.get_bloginfo('admin_email').'>'."\r\n";
								}
								$subject = '['.get_settings('blogname').'] '.sprintf(esc_html__('Your proposal %s','my-transit-lines'),'"'.get_the_title().'"').' – '.esc_html__('Message via proposal contact form:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-subject']));
								$message = sprintf(esc_html__('Dear %s!','my-transit-lines'),$current_user->user_nicename)."\r\n\r\n";
								$message .= sprintf(esc_html__('A message to you has been sent via the contact form of your proposal %s. Just reply to this email to get in touch with the respective person.','my-transit-lines'),'"'.get_the_title().'"')."\r\n\r\n";
								$message .= esc_html__('Link to proposal:','my-transit-lines')."\r\n";
								$message .= get_permalink()."\r\n\r\n";
								$message .= esc_html__('The following data has been submitted:','my-transit-lines')."\r\n\r\n";
								if(isset($_POST['pacf-gender']) && $_POST['pacf-gender']) $message .= esc_html__('Gender:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-gender']))."\r\n";
								if(isset($_POST['pacf-title']) && $_POST['pacf-title']) $message .= esc_html__('Title:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-title']))."\r\n";
								$message .= esc_html__('First name:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-first-name']))."\r\n";
								$message .= esc_html__('Last name:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-last-name']))."\r\n";
								if(isset($_POST['pacf-institution']) && $_POST['pacf-institution']) $message .= esc_html__('Institution:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-institution']))."\r\n";
								$message .= esc_html__('Email address:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-email-address']))."\r\n";
								if(isset($_POST['pacf-phone-number']) && $_POST['pacf-phone-number']) $message .= esc_html__('Phone number:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-phone-number']))."\r\n\r\n";
								$message .= esc_html__('Subject:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-subject']))."\r\n\r\n";
								$message .= esc_html__('Message:','my-transit-lines')."\r\n";
								$message .= wp_unslash(strip_tags($_POST['pacf-text']))."\r\n";
								wp_mail($to,$subject,$message,$headers);
								
								if(!$_POST['pacf-privacy-admin-mail']) {
									$to = get_bloginfo('admin_email');
									$headers = 'From: wordpress@'.mtl_maildomain();
									$subject = '['.get_settings('blogname').'] '.sprintf(esc_html__('Proposal %s:','my-transit-lines'),'"'.get_the_title().'"').' '.esc_html__('Message via proposal contact form','my-transit-lines');
									$message = sprintf(esc_html__('A message to the author has been sent via the contact form of proposal %s.','my-transit-lines'),'"'.get_the_title().'"')."\r\n\r\n";
									$message .= esc_html__('Link to proposal:','my-transit-lines')."\r\n";
									$message .= get_permalink();
									wp_mail($to,$subject,$message,$headers);
								}
								
								if(!$_POST['pacf-privacy-admin-mail']) {
									$output2 .= '
									<div class="success-message-block">
										<p><strong>'.esc_html__('Thank you! Your message has been sent to the author of this proposal. A notice without any of your data has been sent to the admin team for statistical reasons.','my-transit-lines').'</strong></p>
									</div>';
								}
								
								else {
									$output2 .= '
									<div class="success-message-block">
										<p><strong>'.esc_html__('Thank you! Your message has been sent to the author of this proposal. A copy of your message has been sent to the admin team.','my-transit-lines').'</strong></p>
									</div>';
								}
							}
						}
					}
					
					// output the proposal author contact form
					if((!isset($_POST['pacf-sent']) && !$_POST['pacf-sent']) || $err) {
						$output2 .= '
					<p><button><a href="#" class="pacf-toggle"><i class="dashicons dashicons-email"></i> '.esc_html__('Contact the author of this proposal','my-transit-lines').'</a></button></p>
					'.($use_recaptcha ? '<script src="https://www.google.com/recaptcha/api.js"></script>
					<script>
					   function onSubmit(token) {
							var valid = document.getElementById("pacf-form-element").checkValidity();
							if(valid) document.getElementById("pacf-form-element").submit();
							else document.getElementById("pacf-form-element").reportValidity();
					   }
					 </script>' : '').'
					<form method="post" action="#proposal-author-contact-form" id="pacf-form-element">
						<p>
							<label for="pacf-gender">'.esc_html__('Please select your gender (optional)','my-transit-lines').'
								<select name="pacf-gender" id="pacf-gender">
									<option disabled selected value>'.esc_html__('Please select an option','my-transit-lines').'</option>
									<option>'.esc_html__('Mrs.','my-transit-lines').'</option>
									<option>'.esc_html__('Mr.','my-transit-lines').'</option>
								</select>
							</label>
						</p>
						<p>
							<label for="pacf-title">'.esc_html__('Your title (optional)','my-transit-lines').'
								<input type="text" name="pacf-title" id="pacf-title" value="" />
							</label>
						</p>
						<p>
							<label for="pacf-first-name">'.esc_html__('Your first name','my-transit-lines').'
								<input type="text" name="pacf-first-name" id="pacf-first-name" value="" minlength="2" required />
							</label>
						</p>
						<p>
							<label for="pacf-last-name">'.esc_html__('Your last name','my-transit-lines').'
								<input type="text" name="pacf-last-name" id="pacf-last-name" value="" minlength="2" required />
							</label>
						</p>
						<p>
							<label for="pacf-institution">'.esc_html__('Your institution (optional)','my-transit-lines').'
								<input type="text" name="pacf-institution" id="pacf-institution" value="" />
							</label>
						</p>
						<p>
							<label for="pacf-email-address">'.esc_html__('Your email address','my-transit-lines').'
								<input type="email" name="pacf-email-address" id="pacf-email-address" value="" required />
							</label>
						</p>
						<p>
							<label for="pacf-phone-number">'.esc_html__('Your phone number (optional)','my-transit-lines').'
								<input type="phone" name="pacf-phone-number" id="pacf-phone-number" value="" />
							</label>
						</p>
						<p>
							<label for="pacf-subject">'.esc_html__('Subject of your message','my-transit-lines').'
								<input type="text" name="pacf-subject" id="pacf-subject" value="" minlength="5" required />
							</label>
						</p>
						<p>
							<label for="pacf-text">'.esc_html__('Your message','my-transit-lines').'
								<textarea name="pacf-text" id="pacf-text" minlength="20" required></textarea>
							</label>
						</p>
						<p>
							<label for="pacf-privacy-policy-accepted"><input type="checkbox" name="pacf-privacy-policy-accepted" id="pacf-privacy-policy-accepted" required /> '.sprintf(esc_html__('I have read and accept the %1$sData privacy policy%2$s.','my-transit-lines'),'<a href="'.get_permalink(get_option('wp_page_for_privacy_policy')).'" target="_blank">','</a>').'</label>
						</p>
						<p>
							<label for="pacf-privacy-admin-mail"><input type="checkbox" name="pacf-privacy-admin-mail" id="pacf-privacy-admin-mail" /> '.esc_html__('The admin team would like to be informed, too! So please check this box if you accept that a full copy of your data is being sent to the Linie Plus admin team in CC. If you do not accept this, the admin team will only be informed about the usage of the contact form for this proposal without any of your personal data.','my-transit-lines').'</label>
						</p>
						<p>
							<button 
							type="submit" '.($use_recaptcha ? '
							class="g-recaptcha" 
							data-sitekey="'.$mtl_options3['mtl-recaptcha-website-key'].'" 
							data-callback="onSubmit" 
							data-action="submit"' : '').'>'.esc_html__('Send message','my-transit-lines').'</button>

							<input type="hidden" name="pacf-sent" value="1" />
						</p>
					</form>';
					}
				$output2 .= '
				</div>';
			}
		}

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