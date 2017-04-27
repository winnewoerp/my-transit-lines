<?php
/**
 * My Transit Lines
 * Star rating module
 *
 * @package My Transit Lines
 */
 
/* created by Johannes Bouchain, 2015-04-03 */

/* MTL rating database table setup */

// TO DO: make separate module for mail actions

global $mtl_options, $mtl_options2;
$mtl_options = get_option('mtl-option-name');
$mtl_options2 = get_option('mtl-option-name2');

// setup database table for MTL ratings
function mtl_rating_database_setup() {
	global $wpdb;
	$table_name_no_prefix =  'mtl_ratings';
	$table_name = $wpdb->prefix . $table_name_no_prefix;
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name_no_prefix) {
		global $wpdb;
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			rating_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			rating_user_id tinytext NOT NULL,
			rating_post_id mediumint NOT NULL,
			rating_cat1 int NOT NULL,
			rating_cat2 int NOT NULL,
			rating_cat3 int NOT NULL,
			rating_cat4 int NOT NULL,
			rating_cat5 int  NOT NULL,
			UNIQUE KEY id (id)
		);";
	
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
add_action('after_setup_theme','mtl_rating_database_setup');


/* create star rating section above content of single posts */
function mtl_star_rating($content) {
	global $post, $mtl_options, $mtl_options2, $wpdb;
	$output = $content;
	
	// get custom rating table name
	$table_name_no_prefix = 'mtl_ratings';
	$table_name = $wpdb->prefix . $table_name_no_prefix;
	
	// rating insert and success message or error message
	$message = '';
	if($_SERVER['REQUEST_METHOD'] === 'POST') {
		$error_message = '<div class="rating-message error">'.__('An error occurred when trying to submit your rating. Please try again.','my-transit-lines').'</div>';
		if(intval($_POST['rating_cat1']) && intval($_POST['rating_cat2']) && intval($_POST['rating_cat3']) && intval($_POST['rating_userid'])==get_current_user_id()) {
			$rating_date = date_i18n('Y-m-d H:i:s', false, false);
			$data = array(
				'rating_date' => $rating_date,
				'rating_user_id' => get_current_user_id(),
				'rating_post_id' => $post->ID,
				'rating_cat1' => $_POST['rating_cat1'],
				'rating_cat2' => $_POST['rating_cat2'],
				'rating_cat3' => $_POST['rating_cat3']
			);
			$inserted = $wpdb->insert( $table_name, $data);
			if($inserted) {
				$message .= '<div class="rating-message success">'.__('Thank you! Your rating for this proposal has been submitted successfully.','my-transit-lines').'</div>';
				
				// rating notification e-mail for admin
				// basic e-mail data
				$admin_email = get_settings('admin_email');
				$user_id=get_current_user_id();
				$user_email = get_the_author_meta('user_email',$user_id);
				$user_name = get_the_author_meta('display_name',$user_id);
				$headers = 'From: '.get_settings('blogname').' <noreply@'.mtl_maildomain().'>' . "\r\n";
				
				// send mail to admin if proposal is ready for rating
				$subject = '['.get_settings('blogname').'] '.__('A proposal has been rated','my-transit-lines');
				$mail_message = '';
				$mail_message .= sprintf(__('The proposal "%s" has been rated by a user.','my-transit-lines'),$post->post_title)."\n\n";
				$mail_message .= __('Rating result:','my-transit-lines')."\r\n";
				$mail_message .= __('Conceptual rating','my-transit-lines').': '.$_POST['rating_cat1']."\r\n";
				$mail_message .= __('Technical rating','my-transit-lines').': '.$_POST['rating_cat2']."\r\n";
				$mail_message .= __('Financial rating','my-transit-lines').': '.$_POST['rating_cat3']."\n\n";
				$mail_message .= __('User name','my-transit-lines').': ' . $user_name . "\r\n";
				$mail_message .= __('User e-mail','my-transit-lines').': ' . $user_email . "\r\n\n";
				$mail_message .= __('View the proposal here:','my-transit-lines')."\n";
				$mail_message .= get_permalink($post->ID);
				wp_mail($admin_email,$subject,$mail_message,$headers);
			}
			else $message .= $error_message;
		}
		else $message .= $error_message;
	}
	
	// get user rating values using custom function (see below)
	$rating_values = get_user_rating_values(get_current_user_id(),$post->ID);
	$has_rated = $rating_values[0]; $user_rating_count = $rating_values[1]; $user_rating[0] = $rating_values[2]; $user_rating[1] = $rating_values[3]; $user_rating[2] = $rating_values[4]; $user_average_rating = $rating_values[5];
	
	// get editor rating data from post meta
	$editor_rating = array();
	for($i = 0;$i<=2;$i++) if(get_post_meta($post->ID,'mtl-editor-rating'.($i+1),true)) $editor_rating[$i] = get_post_meta($post->ID,'mtl-editor-rating'.($i+1),true);
	if(floatval($editor_rating[0]) && floatval($editor_rating[1]) && floatval($editor_rating[2])) $editor_average_rating = round((floatval($editor_rating[0])+floatval($editor_rating[1])+floatval($editor_rating[2]))/3,1);
	
	// calculate rating average 
	if($editor_average_rating && $user_average_rating) $average_rating = round(($editor_average_rating+$user_average_rating)/2,1);
	else {
		if($editor_average_rating) $average_rating = $editor_average_rating;
		elseif($user_average_rating) $average_rating = $user_average_rating;
	}
	
	// if rated, insert redundant post rating fields
	if($inserted) {	
		update_post_meta($post->ID,'mtl-rating-average',$average_rating);
		update_post_meta($post->ID,'mtl-rating-count',$user_rating_count);
	}
	
	$rated = false;
	if($editor_rating || $user_rating) $rated = true;
	
	// output rating box
	$output .= '<h2 id="rating"><span id="rating-add"></span>'.__('Rating of this proposal','my-transit-lines').'</h2>';
	if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-phase') {
		$output .= '<div class="mtl-rating-section">';
		
		// inactive average rating
		$output .= '<div class="mtl-rating-subsection average-readonly">';
		if($rated) $output .= '<span class="mtl-rating-box readonly" data-score="'.$average_rating.'" data-count="20" data-score-editors="'.$average_rating.'"></span> '.$user_rating_count.' '.($user_rating_count == 1 ? __('user rating','my-transit-lines') : __('user ratings','my-transit-lines')).($editor_rating ? __(' and editor rating','my-transit-lines') : '').' '.($user_rating_count ? '('.$average_rating.')' : '');
		else $output .= __('No rating so far','my-transit-lines');
		$output .= '</div>';
		
		// output message if user has voted
		if($message) $output .= $message;
		
		// inactive detailed rating
		$output .= '<div class="mtl-rating-subsection readonly">';
			if($rated) {
			$output .= '<div class="mtl-rating-title"><strong>'.__('Average user rating','my-transit-lines').':</strong></div>';
			if($user_rating) { 
				$output .= '<div><span id="mtl-rating-box1-readonly" class="mtl-rating-box readonly" data-count="20" data-score="'.$user_rating[0].'"></span> <span class="text">'.__('Conceptual rating','my-transit-lines').' ('.round($user_rating[0],1).')</span></div>';
				$output .= '<div><span id="mtl-rating-box2-readonly" class="mtl-rating-box readonly" data-count="20" data-score="'.$user_rating[1].'"></span> <span class="text">'.__('Technical rating','my-transit-lines').' ('.round($user_rating[1],1).')</span></div>';
				$output .= '<div><span id="mtl-rating-box3-readonly" class="mtl-rating-box readonly" data-count="20" data-score="'.$user_rating[2].'"></span> <span class="text">'.__('Financial rating','my-transit-lines').' ('.round($user_rating[2],1).')</span></div>';
			}
			else $output .= __('No users\' rating so far','my-transit-lines');
			$output .= '</div>';
			$output .= '<div class="mtl-rating-subsection readonly">';
			$output .= '<div class="mtl-rating-title"><strong>'.__('Editor rating','my-transit-lines').':</strong></div>';
			if($editor_rating) { 
				$output .= '<div><span id="mtl-rating-editors1-readonly" class="mtl-rating-box readonly" data-count="1" data-score="'.$editor_rating[0].'"></span> <span class="text">'.__('Conceptual rating','my-transit-lines').' ('.$editor_rating[0].')</span></div>';
				$output .= '<div><span id="mtl-rating-editors2-readonly" class="mtl-rating-box readonly" data-count="1" data-score="'.$editor_rating[1].'"></span> <span class="text">'.__('Technical rating','my-transit-lines').' ('.$editor_rating[1].')</span></div>';
				$output .= '<div><span id="mtl-rating-editors3-readonly" class="mtl-rating-box readonly" data-count="1" data-score="'.$editor_rating[2].'"></span> <span class="text">'.__('Financial rating','my-transit-lines').' ('.$editor_rating[2].')</span></div>';
			}
			else $output .= __('No editor\'s rating so far','my-transit-lines');
			$term = get_term( get_post_meta($post->ID,'mtl-implementation-horizon',true), 'horizon' );
			$horizon = $term->name;
			$output .= '<p class="implementation-horizon"><strong>'.__('Implementation Horizon','my-transit-lines').'</strong>: '.$horizon.'</p>';
		}
		$output .= '</div>';
		
		// active rating
		if(!$has_rated && is_user_logged_in()) {
			$term = get_term( get_post_meta($post->ID,'mtl-implementation-horizon',true), 'horizon' );
			$horizon = $term->name;
			$output .= '<div class="mtl-rating-subsection active">';
			$output .= '<div class="mtl-rating-title">'.__('How many stars do you want to give to the three rating categories of this proposal? Click on the respective position.','my-transit-lines').'<br />'.($horizon ? sprintf(__('The implementation of this proposal is %s. Please keep this in mind for your rating.','my-transit-lines'),'<strong>'.$horizon.'</strong>') : '').'</div>';
			$output .= '<div><span id="mtl-rating-box1" class="mtl-rating-box active"></span> <span class="text">'.__('Conceptual rating','my-transit-lines').'</span></div>';
			$output .= '<div><span id="mtl-rating-box2" class="mtl-rating-box active"></span> <span class="text">'.__('Technical rating','my-transit-lines').'</span></div>';
			$output .= '<div><span id="mtl-rating-box3" class="mtl-rating-box active"></span> <span class="text">'.__('Financial rating','my-transit-lines').'</span></div>';
			$output .= '</div>';
		}
		
		// rating button
		if(is_user_logged_in()) {
			if(!$has_rated) $output .= '<a class="mtl-button add-rating" href="#"><strong class="show-text">'.__('Rate this proposal','my-transit-lines').'</strong><span class="hide-text">'.__('Cancel rating','my-transit-lines').'</span></a><br />';
			else $output .= '<div class="has-rated">'.__('You already rated this proposal.','my-transit-lines').'</div>';
		}
		else $output .= '<a class="mtl-button" href="'.wp_login_url(get_permalink().'#rating-add').'"><strong>'.__('Log in to rate this proposal','my-transit-lines').'</strong></a><br />';
		
		
		// output rating detail box
		if(get_post_meta($post->ID,'mtl-editor-rating-motivation',true)) {
			$output .= '<div class="mtl-rating-details"><h3>'.__('Editor\'s rating motivation','my-transit-lines').'</h3>';
			$output .= '<p>'.get_post_meta($post->ID,'mtl-editor-rating-motivation',true).'</p>';
			$output .= '</div>';
		}
		
		// detail box button
		if($rated) {
			$output .= '<a class="mtl-button-rating-details" href="#"><span class="show-text">'.__('Show rating details','my-transit-lines').'</span>';
			$output .= '<span class="hide-text">'.__('Hide rating details','my-transit-lines').'</span></a>';
		}
		
		$output .= rating_js_vars();
		$output .= '</div>';		
	}
	else {
		if(get_post_type($post->ID) == 'mtlproposal') {
			if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-ready-phase') $output .= '<div class="mtl-rating-section"><em>'.__('This proposal can\'t yet be rated. The editors of the proposal must enable rating first.','my-transit-lines').'</em></div>';
			else $output .= '<div class="mtl-rating-section"><em>'.__('This proposal can\'t yet be rated. The editors and the author of the proposal must enable rating first.','my-transit-lines').'</em></div>';
		}
		if(get_post_type($post->ID) == 'mtlproposal2') $output .= '<div class="mtl-rating-section"><em>'.__('This is an external proposal and thus can\'t be rated. External proposals must be integrated into the respective regional platform before they can be prepared for rating. The respective platform doesn\'t yet exists? Contact us if you\'re interested in creating it.','my-transit-lines').'</em></div>';
	}
	
	if(is_single() && $mtl_options2['mtl-current-project-phase']=='rate') return $output;
	else return $content;
}
add_filter( 'the_content', 'mtl_star_rating' );

/* create JS vars, mainly for urls and translations */
function rating_js_vars() {
	global $post, $mtl_options, $mtl_options2;
	$output .= '<script type="text/javascript"> ';
	$output .= 'if(typeof(templateUrl) == "undefined") var templateUrl = "'.get_bloginfo('template_directory').'"; ';
	$output .= 'var mtlStarHint = ["'.__('bad','my-transit-lines').'","'.__('poor','my-transit-lines').'","'.__('regular','my-transit-lines').'","'.__('good','my-transit-lines').'","'.__('gorgeous','my-transit-lines').'"];';
	$output .= 'var mtlCancelHint = "'.__('Cancel this Rating!','my-transit-lines').'"; ';
	$output .= 'var notRatedMessage = "'.__('Not rated yet!','my-transit-lines').'";';
	$output .= 'var ratingForm = "<form id=\"rating-form\" method=\"post\" action=\"#rating\"><input type=\"hidden\" id=\"rating_cat1\" name=\"rating_cat1\" value=\"\" /><input type=\"hidden\" id=\"rating_cat2\" name=\"rating_cat2\" value=\"\" /><input type=\"hidden\" id=\"rating_cat3\" name=\"rating_cat3\" value=\"\" /><input type=\"hidden\" id=\"rating_userid\" name=\"rating_userid\" value=\"'.get_current_user_id().'\" /><button type=\"submit\">'.__('Submit rating','my-transit-lines').'</button></form>";';
	$output .= 'var ratingFormEditors = "<input type=\"hidden\" id=\"rating_cat1\" name=\"mtl-editor-rating1\" value=\"\" /><input type=\"hidden\" id=\"rating_cat2\" name=\"mtl-editor-rating2\" value=\"\" /><input type=\"hidden\" id=\"rating_cat3\" name=\"mtl-editor-rating3\" value=\"\" /><input type=\"hidden\" id=\"rating_userid\" name=\"rating_userid\" value=\"'.get_current_user_id().'\" />";';
	$output .= '</script>';
	if($mtl_options2['mtl-current-project-phase']=='rate') return $output;
}

/* get user rating values */
function get_user_rating_values($user_id,$post_id) {
	global $wpdb;
	$table_name_no_prefix =  'mtl_ratings';
	$table_name = $wpdb->prefix . $table_name_no_prefix;
	$has_rated = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE rating_user_id = '$user_id' AND rating_post_id = '$post_id'" );
	$user_rating_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE rating_post_id = '$post_id'" );
	
	// get rating category average user rating values
	if($user_rating_count) {
		$user_rating[0] = $wpdb->get_var( "SELECT AVG(rating_cat1) FROM $table_name WHERE rating_post_id = '$post_id'" );
		$user_rating[1] = $wpdb->get_var( "SELECT AVG(rating_cat2) FROM $table_name WHERE rating_post_id = '$post_id'" );
		$user_rating[2] = $wpdb->get_var( "SELECT AVG(rating_cat3) FROM $table_name WHERE rating_post_id = '$post_id'" );
		$user_average_rating = round(($user_rating[0]+$user_rating[1]+$user_rating[2])/3,1);
	}
	return array($has_rated,$user_rating_count,$user_rating[0],$user_rating[1],$user_rating[2],$user_average_rating);
}

/* enqueue the necessary scripts and stylesheets */
function mtl_star_rating_scripts() {
	global $post, $mtl_options, $mtl_options2;
	if($mtl_options2['mtl-current-project-phase']=='rate') {
		wp_enqueue_style( 'mtl-raty-css', get_template_directory_uri() . '/modules/mtl-star-rating/raty/jquery.raty.css', array(), '20150403' );
		wp_enqueue_script( 'mtl-raty-js', get_template_directory_uri() . '/modules/mtl-star-rating/raty/jquery.raty.js', array(), '20150403', true );
		wp_enqueue_script( 'mtl-star-rating-js', get_template_directory_uri() . '/modules/mtl-star-rating/mtl-star-rating.js', array(), '20150403', true );
	}
}
add_action( 'wp_enqueue_scripts', 'mtl_star_rating_scripts' );
add_action( 'admin_enqueue_scripts', 'mtl_star_rating_scripts' );

/* separately enqueue module stylesheet for admin */
function mtl_admin_star_rating_styles() {
	wp_enqueue_style( 'mtl-star-rating-css', get_template_directory_uri() . '/modules/mtl-star-rating/mtl-star-rating.css', array(), '20150403' );
}
add_action( 'admin_enqueue_scripts', 'mtl_admin_star_rating_styles' );

/* hook rating display to tiles */
function mtl_tiles_rating_output($content) {
	global $post, $mtl_options, $mtl_options2, $wpdb;
	
	// get rating data-count
	// get editor rating data from post meta
	$editor_rating = array();
	for($i = 0;$i<=2;$i++) if(get_post_meta($post->ID,'mtl-editor-rating'.($i+1),true)) $editor_rating[$i] = get_post_meta($post->ID,'mtl-editor-rating'.($i+1),true);
	if(floatval($editor_rating[0]) && floatval($editor_rating[1]) && floatval($editor_rating[2])) $editor_average_rating = round((floatval($editor_rating[0])+floatval($editor_rating[1])+floatval($editor_rating[2]))/3,1);
	
	// get user rating data
	$rating_values = get_user_rating_values(get_current_user_id(),$post->ID);
	$user_average_rating = $rating_values[5];
	
	// calculate rating average 
	if($editor_average_rating && $user_average_rating) $average_rating = round(($editor_average_rating+$user_average_rating)/2,1);
	else {
		if($editor_average_rating) $average_rating = $editor_average_rating;
		elseif($user_average_rating) $average_rating = $user_average_rating;
	}
	
	$rated = false;
	if($editor_rating || $user_average_rating) $rated = true;
	
	$post_id = $post->ID;
	$table_name_no_prefix =  'mtl_ratings';
	$table_name = $wpdb->prefix . $table_name_no_prefix;
	$has_rated = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE rating_user_id = '".get_current_user_id()."' AND rating_post_id = '$post_id'" );
	
	if($has_rated && get_current_user_id()) $has_rated_output = '<span class="has-rated" title="'.__('You already rated this proposal','my-transit-lines').'"></span>&nbsp; ';
	
	// output rating or rating link within post tiles 
	$output = '';
	if((get_post_type($post->ID)=='mtlproposal' || get_post_type($post->ID)=='mtlproposal2') && !is_single()) {
		if($mtl_options2['mtl-current-project-phase']=='rate') {
			$editor_rating = get_post_meta($post->ID,'mtl-editor-rating',true) ? get_post_meta($post->ID,'mtl-editor-rating',true) : 'undefined';
			if(get_post_meta($post->ID,'mtl-proposal-phase',true)=='rating-phase') {
				if(is_user_logged_in()) $rating_url = get_permalink($post->ID).'#rating'.($rated ? '' : '-add');
				else $rating_url = wp_login_url(get_permalink().'#rating-add');
				$output .= '<a href="'.$rating_url.'" class="tile-rating-section'.($rated ? '' : ' not-rated').'">';
				if($rated) $output .= '<span class="mtl-tile-rating" data-score="'.$average_rating.'" data-count="20"></span> '.($average_rating? '<span class="text">'.$has_rated_output.'<strong title="'.__('Rating','my-transit-lines').'">'.$average_rating.'</strong> <span title="'.__('Number of ratings','my-transit-lines').'">('.get_post_meta($post->ID,'mtl-rating-count',true).')</span></span>' : '');
				else {
					if(is_user_logged_in()) $output .= '<span><em><strong>'.__('Rate this proposal','my-transit-lines').'</strong></em></span>';
					else $output .= '<span><em><strong>'.__('Log in and rate','my-transit-lines').'</strong></em></span>';
				}
				$output .= '</a>';
				$output .= rating_js_vars();
			}
			else {
				if(get_post_type($post->ID)=='mtlproposal') $output .= '<span class="tile-rating-section"><em>'.__('Rating not yet possible','my-transit-lines').'</em></span>';
				else $output .= '<span class="tile-rating-section"><em>'.__('Rating not possible','my-transit-lines').'</em></span>';
			}
		}
		return $output;
	}
	else return $content;
}
add_action('the_content','mtl_tiles_rating_output');

// add rating to post meta box (see metaboxes module)
function mtl_star_rating_dashboard($post) {
	global $post;
	
	// nonce field for rating meta boxes
	wp_nonce_field( basename( __FILE__ ), 'mtl_post_rating_class_nonce' );
	
	// get editor rating data from post meta
	$editor_rating = array();
	for($i = 0;$i<=2;$i++) if(get_post_meta($post->ID,'mtl-editor-rating'.($i+1),true)) $editor_rating[$i] = get_post_meta($post->ID,'mtl-editor-rating'.($i+1),true);
	if(floatval($editor_rating[0]) && floatval($editor_rating[1]) && floatval($editor_rating[2])) $editor_average_rating = round((floatval($editor_rating[0])+floatval($editor_rating[1])+floatval($editor_rating[2]))/3,1);
	
	// star rating for editors
	$output .= '<div class="mtl-rating-section editors">';
	$output .= '<div class="mtl-rating-subsection">';
	$output .= '<div class="mtl-rating-title">'.__('How many stars do you want to give to the three rating categories of this proposal? Click on the respective position','my-transit-lines').':</div>';
	$output .= '<div><span id="mtl-rating-box1" class="mtl-rating-box active" data-score="'.$editor_rating[0].'"></span> <span class="text">'.__('Conceptual rating','my-transit-lines').'</span></div>';
	$output .= '<div><span id="mtl-rating-box2" class="mtl-rating-box active" data-score="'.$editor_rating[1].'"></span> <span class="text">'.__('Technical rating','my-transit-lines').'</span></div>';
	$output .= '<div><span id="mtl-rating-box3" class="mtl-rating-box active" data-score="'.$editor_rating[2].'"></span> <span class="text">'.__('Financial rating','my-transit-lines').'</span></div>';
	$output .= '</div>';
	$output .= '</div>';
	$output .= rating_js_vars();
	
	// editor's rating motivation
	$output .= '<p><strong>'.__('Editor\'s rating motivation','my-transit-lines').'</strong></p>';
	$output .= '<div id="mtl-editor-rating-motivation-box">';
	ob_start();
	wp_editor(get_post_meta($post->ID, 'mtl-editor-rating-motivation', true ),'mtl-editor-rating-motivation',array('textarea_name' => 'mtl-editor-rating-motivation'));
	$output_editor = ob_get_clean();
	ob_end_flush();
	$output .= $output_editor;
	$output .= '</div>';
	
	// getting all categories for selected as transit mode categories, set the given category option to checked
	$current_term = get_the_terms( $post->ID, 'horizon');
	$output .= $current_term[0]->name;
	
	$output .= '<div class="horizon-select"><p><strong>'.__('Implementation horizon of this proposal','my-transit-lines').'</strong><br />';
	$taxonomy_elements=get_categories( 'hide_empty=0&tab_index=4&taxonomy=horizon&orderby=slug' );
	foreach($taxonomy_elements as $taxonomy_element) {
		$checked='';
		$post_cat = '';
		if(isset($_POST['cat'])) $post_cat = $_POST['cat'];
		if($taxonomy_element->cat_ID == $current_term[0]->term_id) $checked=' checked="checked"';
		$output .= '<label class="mtl-horizon"><input'.$checked.' class="horizon-select" type="radio" name="mtl-implementation-horizon" value="'.$taxonomy_element->cat_ID.'" id="horizon-'.$taxonomy_element->slug.'" /> '.$taxonomy_element->name.'</label>'."\r\n";
	}
	$output .= '</p>';
	$output .= '</div>';
		
	echo $output;
}

// save redundant post fields on each post save
function save_redundant_rating_post_fields($post_id) {
	$post = get_post($post_id);
	
	// get editor rating data from post meta - this is redundant (see above), maybe make a function?
	$editor_rating = array();
	for($i = 0;$i<=2;$i++) if(get_post_meta($post_id,'mtl-editor-rating'.($i+1),true)) $editor_rating[$i] = get_post_meta($post_id,'mtl-editor-rating'.($i+1),true);
	if(floatval($editor_rating[0]) && floatval($editor_rating[1]) && floatval($editor_rating[2])) $editor_average_rating = round((floatval($editor_rating[0])+floatval($editor_rating[1])+floatval($editor_rating[2]))/3,1);
	
	// get user rating data
	$rating_values = get_user_rating_values(get_current_user_id(),$post_id);
	$user_average_rating = $rating_values[5];
	$user_rating_count = $rating_values[1];

	// calculate rating average 
	if($editor_average_rating && $user_average_rating) $average_rating = round(($editor_average_rating+$user_average_rating)/2,1);
	else {
		if($editor_average_rating) $average_rating = $editor_average_rating;
		elseif($user_average_rating) $average_rating = $user_average_rating;
	}
	
	// update meta fields for rating -- TODO: Remove this redundancy and do better SQL queries for getting rating data for each post out of rating table
	if($_POST['mtl-manual-proposal-data'] != 'on' && $average_rating && $average_rating) {
		update_post_meta($post_id,'mtl-rating-average',$average_rating);
		update_post_meta($post_id,'mtl-rating-count',$user_rating_count);
	}
	else {
		delete_post_meta($post_id,'mtl-rating-average',$average_rating);
		delete_post_meta($post_id,'mtl-rating-count',$user_rating_count);
	}
}
add_action( 'save_post', 'save_redundant_rating_post_fields');

// WP mail actions on meta value change
function rateable_notifyer($meta_id, $object_id, $meta_key, $_meta_value) {
	global $mtl_options;
	$current_post = get_post($object_id);
	
	// basic e-mail data
	$admin_email = get_settings('admin_email');
	$user_id=$current_post->post_author;
	$user_email = get_the_author_meta('user_email',$user_id);
	$user_name = get_the_author_meta('display_name',$user_id);
	$headers = 'From: '.get_settings('blogname').' <noreply@'.mtl_maildomain().'>' . "\r\n";
	
	// send mail to admin if proposal is ready for rating
	$testkey = 'mtl-proposal-phase';
	$testvalue = 'rating-ready-phase';
	if($meta_key==$testkey && get_post_meta($current_post->ID,$testkey,true)==$testvalue) {
		
		$subject = '['.get_settings('blogname').'] '.__('Proposal ready for rating activation','my-transit-lines');
		$message = '';
		$message .= sprintf(__('The proposal "%s" has been set to ready for voting status by its author.','my-transit-lines'),$current_post->post_title)."\n\n";
		$message .= __('Author name','my-transit-lines').': ' . $user_name . "\r\n";
		$message .= __('Author e-mail','my-transit-lines').': ' . $user_email . "\r\n\n";
		$message .= __('Edit the proposal here and activate rating or set status back to editing phase when it doesn\'t seem to be ready yet:','my-transit-lines')."\n";
		$message .=  get_admin_url().'post.php?post='.$current_post->ID.'&action=edit';
		wp_mail($admin_email,$subject,$message,$headers);
	}
	
	// send mail to user if rating activation denied and set phase back to "Revision phase"
	$testkey = 'mtl-proposal-phase';
	$testvalue = 'rating-phase-refused';
	if($meta_key==$testkey && get_post_meta($current_post->ID,$testkey,true)==$testvalue) {
		$subject = '['.get_settings('blogname').'] '.__('Rating activation denied','my-transit-lines');
		$message = '';
		$message .= sprintf(__('The editing team denied rating activation for your proposal "%s".','my-transit-lines'),$current_post->post_title)."\n\n";
		$message .= __('Go to the editing view of your proposal and read the editors\' hints to see why:','my-transit-lines')."\n";
		$message .= get_permalink($mtl_options['mtl-addpost-page']).'?edit_proposal='.$current_post->ID;
		wp_mail($user_email,$subject,$message,$headers);
		
		remove_action('updated_post_meta', 'rateable_notifyer');
		update_post_meta($current_post->ID,'mtl-proposal-phase','revision-phase');
		add_action('updated_post_meta', 'rateable_notifyer', 10, 4);
	}
	
	// send mail to user if rating has been activated for the proposal
	$testkey = 'mtl-proposal-phase';
	$testvalue = 'rating-phase';
	if($meta_key==$testkey && get_post_meta($current_post->ID,$testkey,true)==$testvalue) {
		$subject = '['.get_settings('blogname').'] '.__('Proposal rating activation','my-transit-lines');
		$message = '';
		$message .= sprintf(__('The editing team of "%1$s" activated voting for your proposal "%2$s".','my-transit-lines'),get_settings('blogname'),$current_post->post_title)."\n\n";
		$message .= __('View the proposal here:','my-transit-lines')."\n";
		$message .= get_permalink($current_post->ID)."\n\n";
		$message .= __('Good luck for the voting phase!','my-transit-lines')."\n";
		wp_mail($user_email,$subject,$message,$headers);
	}
	
	// send mail to if editors hints changed
	$testkey = 'mtl-editors-hints';
	$testvalue = '---xxx---';
	if($meta_key==$testkey && get_post_meta($current_post->ID,$testkey,true)!=$testvalue && $_POST['minor-changes']!='on') {
		$subject = '['.get_settings('blogname').'] '.__('Editors hints updated for a proposal','my-transit-lines');
		$message = '';
		$message .= sprintf(__('The editing team of "%1$s" updated the editor\'s hints of your proposal "%2$s".','my-transit-lines'),get_settings('blogname'),$current_post->post_title)."\n\n";
		$message .= __('Go to the editing view of your proposal and read the updated editor\'s hints:','my-transit-lines')."\n";
		$message .= get_permalink($mtl_options['mtl-addpost-page']).'?edit_proposal='.$current_post->ID;
		if($_POST['mtl-proposal-status-nok']!='on') $message .= "\n\n".__('The editors marked the proposal as generally ok to show that it already meets at least the basic requirements. Have a look at the hints and decide, if you already want to enter rating phase with your proposal.','my-transit-lines'); 
		wp_mail($user_email,$subject,$message,$headers);
	}
	
	
}
add_action('updated_post_meta', 'rateable_notifyer', 10, 4);

// redundant because of error
function rateable_notifyer2($meta_id, $object_id, $meta_key, $_meta_value) {
	global $mtl_options;
	$current_post = get_post($object_id);
	
	// basic e-mail data
	$admin_email = get_settings('admin_email');
	$user_id=$current_post->post_author;
	$user_email = get_the_author_meta('user_email',$user_id);
	$user_name = get_the_author_meta('display_name',$user_id);
	$headers = 'From: '.get_settings('blogname').' <noreply@'.mtl_maildomain().'>' . "\r\n";
	
	// send mail to if editors hints created
	$testkey = 'mtl-editors-hints';
	$testvalue = '---xxx---';
	if($meta_key==$testkey && get_post_meta($current_post->ID,$testkey,true)!=$testvalue && $_POST['minor-changes']!='on') {
		$subject = '['.get_settings('blogname').'] '.__('Editors hints updated for a proposal','my-transit-lines');
		$message = '';
		$message .= sprintf(__('The editing team of "%1$s" updated the editor\'s hints of your proposal "%2$s".','my-transit-lines'),get_settings('blogname'),$current_post->post_title)."\n\n";
		$message .= __('Go to the editing view of your proposal and read the updated editor\'s hints:','my-transit-lines')."\n";
		$message .= get_permalink($mtl_options['mtl-addpost-page']).'?edit_proposal='.$current_post->ID;
		if($_POST['mtl-proposal-status-nok']!='on') $message .= "\n\n".__('The editors marked the proposal as generally ok to show that it already meets at least the basic requirements. Have a look at the hints and decide, if you already want to enter rating phase with your proposal.','my-transit-lines'); 
		wp_mail($user_email,$subject,$message,$headers);
	}
}
add_action('added_post_meta', 'rateable_notifyer2', 10, 4);

// save post rating from backend
function mtl_post_rating_save($post_id) {
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	if ( !wp_verify_nonce( $_POST['mtl_post_rating_class_nonce'], basename( __FILE__ ) ) ) return;
	if ( !current_user_can( 'edit_post', $post_id ) ) return;
	
	// saving custom rating fields
	if($_POST['mtl-manual-proposal-data'] != 'on') {
	
		//wtf *** can't display custom term content in frotend, so saving redundant custom field "mtl-implementation-horizon"
		$save_custom_fields = array('mtl-editor-rating1','mtl-editor-rating2','mtl-editor-rating3','mtl-editor-rating-motivation','mtl-implementation-horizon');
		foreach($save_custom_fields as $save_custom_field) 	if($_POST[$save_custom_field] && $_POST[$save_custom_field] != get_post_meta($post_id,$save_custom_field,true)) update_post_meta($post_id,$save_custom_field,$_POST[$save_custom_field]);
		if($_POST['mtl-implementation-horizon']) wp_set_post_terms( $post_id, array($_POST['mtl-implementation-horizon']), 'horizon' );
	
	}
	
}
add_action('save_post','mtl_post_rating_save');

?>