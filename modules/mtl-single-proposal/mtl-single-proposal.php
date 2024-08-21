<?php
/**
 * My Transit Lines
 * Single proposal view module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2014-09-06 */

/**
 * map and meta data for single proposal
 */
function mtl_proposal_map($content) {
	if ($content === "")
		return "";

	if (!(get_post_type() == 'mtlproposal'))
		return $content;
	
	global $post;
	$output = '';
	$output2 = '';
	
	// get the mtl options
	$mtl_options = get_option('mtl-option-name');
	$mtl_options3 = get_option('mtl-option-name3');
	
	// load relevant scripts and set some JS variables
	$output .= '<div id="mtl-box">'."\r\n";

	// save category style data to JS array
	$output .= get_transport_mode_style_data();

	// Add data for JS scripts
	$output .= '<script type="text/javascript">var editMode = false; var themeUrl = "'.get_template_directory_uri().'"; var proposalList = ['.get_proposal_data_json($post->ID).']</script>';
	
	// output the map box
	$output .= '<div id="mtl-map-box">'."\r\n";
	$output .= '<div id="mtl-map"></div>'."\r\n";
	$output .= '</div>';
	$output .= mtl_localize_script(true);
	wp_enqueue_script('mtl-single-proposal', get_template_directory_uri() . '/modules/mtl-single-proposal/mtl-single-proposal.js', array('my-transit-lines'), wp_get_theme()->version, true);
	
	// output opacity change button, map fullscreen link and toggle label checkbox
	$output .= '<p id="map-color-opacity"><span id="mtl-colored-map-box"><label for="mtl-colored-map"><input type="checkbox" checked="checked" id="mtl-colored-map" name="colored-map" onclick="toggleMapColors()" /> '.__('colored map','my-transit-lines').'</label></span> &nbsp; <span id="mtl-opacity-low-box"><label for="mtl-opacity-low"><input type="checkbox" checked="checked" id="mtl-opacity-low" name="opacity-low" onclick="toggleMapOpacity()" /> '.__('brightened map','my-transit-lines').'</label></span></p>'."\r\n";
	$output .= '<p id="zoomtofeatures" class="alignright" style="margin-top:-12px"><a href="javascript:zoomToFeatures()">'.__('Fit proposition to map','my-transit-lines').'</a></p>';
	$output .= '<p class="alignright"><a id="mtl-fullscreen-link" href="javascript:toggleFullscreen()"><span class="fullscreen-closed">'.__('Fullscreen view','my-transit-lines').'</span><span class="fullscreen-open">'.__('Close fullscreen view','my-transit-lines').'</span></a></p>'."\r\n";
	$output .= '<p class="alignright" id="mtl-toggle-labels"><label><input type="checkbox" checked="checked" id="mtl-toggle-labels-link" onclick="toggleLabels()" /> '.__('Show labels','my-transit-lines').'</label></p>'."\r\n";
	$output .= '<p class="alignright" id="mtl-toggle-sizes"><label><input type="checkbox" autocomplete="off" id="mtl-toggle-sizes-link" onclick="toggleSizes()" /> '.__('Show feature sizes','my-transit-lines').'</label></p>'."\r\n";
	$output .= '</div>'."\r\n";

	// output the meta data
	$output .= '<h2>'.__('Description of this proposal','my-transit-lines').'</h2>';
	$output2 .= mtl_show_metadata_output(array(
		'id' => $post->ID,
	));
	
	// check for reCAPTCHA keys
	$use_recaptcha = false;
	if(trim($mtl_options3['mtl-recaptcha-website-key']) && trim($mtl_options3['mtl-recaptcha-secret-key'])) $use_recaptcha = true;

	// show proposal author contact form if author has enabled it
	$authorid = get_the_author_meta('ID');
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
						$current_user = wp_get_current_user();
						$to = $current_user->user_email;
						$headers = 'From: '.sanitize_text_field(wp_unslash($_POST['pacf-first-name'])).' '.sanitize_text_field(wp_unslash($_POST['pacf-last-name'])).' <'.sanitize_text_field(wp_unslash($_POST['pacf-email-address'])).'>' . "\r\n";
						if($_POST['pacf-privacy-admin-mail']) {
							$headers .= 'CC: Linie Plus Admin Team <'.get_bloginfo('admin_email').'>'."\r\n";
						}
						$subject = '['.get_option('blogname').'] '.sprintf(esc_html__('Your proposal %s','my-transit-lines'),'"'.get_the_title().'"').' – '.esc_html__('Message via proposal contact form:','my-transit-lines').' '.sanitize_text_field(wp_unslash($_POST['pacf-subject']));
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
							$subject = '['.get_option('blogname').'] '.sprintf(esc_html__('Proposal %s:','my-transit-lines'),'"'.get_the_title().'"').' '.esc_html__('Message via proposal contact form','my-transit-lines');
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
			if((!isset($_POST['pacf-sent']) || !$_POST['pacf-sent']) || $err) {
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
					<label for="pacf-privacy-policy-accepted"><input type="checkbox" name="pacf-privacy-policy-accepted" id="pacf-privacy-policy-accepted" required /> '.sprintf(esc_html__('I have read and accept the %1$sData privacy policy%2$s.','my-transit-lines'),'<a href="'.get_privacy_policy_url().'" target="_blank">','</a>').'</label>
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

	$output2 .= '<script type="text/javascript"> defaultCategory = "'.get_the_category($post->ID)[0]->cat_ID .'"; </script>';
	
	// show edit proposal button iff current user equals author
	$current_user = wp_get_current_user();
	$author_id=$post->post_author;
	if($mtl_options['mtl-addpost-page']) {
		if($author_id > 0 && $author_id == $current_user->ID) {
			$output2 .= '<p class="mtl-edit-proposal"><a href="'.get_permalink(pll_get_post($mtl_options['mtl-addpost-page'])).'?edit_proposal='.$post->ID.'">'.__('Edit my proposal','my-transit-lines').'</a></p>';
		}
	}
	
	return $output.$content.$output2;
}
add_filter( 'the_content', 'mtl_proposal_map' );

/**
 * get the taglist for districts/municipalities
 */
function mtl_taglist() {
	$output = '';
	
	$mtl_options = get_option('mtl-option-name');
	$tags = get_the_tags();
	if($tags) {
		$output .= '<div class="post-tags">';
		$output .= '<h3>'.__('All administrative subdivisons of this proposal:','my-transit-lines').'</h3>';
		$output .= '<ul>';
		foreach ( $tags as $current_tag ) {
			$tag_link = str_replace("?", "#!?", add_query_arg( array('mtl-tag-ids' => $current_tag->term_id), get_permalink(pll_get_post($mtl_options['mtl-postlist-page']))));

			$output .= "<li><a href='{$tag_link}' title='{$current_tag->name}'>{$current_tag->name}</a></li>"."\r\n";
		}
		$output .= '</ul>';
		$output .= '</div>';
	}
	return $output;
}
