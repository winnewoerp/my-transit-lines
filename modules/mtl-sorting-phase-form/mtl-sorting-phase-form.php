<?php
/**
 * My Transit Lines
 * Sorting phase form module
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2025-01-03 */

/**
 * shortcode [mtl-sorting-phase-form]
 */
function mtl_sorting_phase_form_output( $atts ) {
	global $post;

	$atts = shortcode_atts( [
		'name_placeholder' => '',
		'email_placeholder' => '',
		'title_placeholder' => '',
		'url_placeholder' => '',
		'justification_placeholder' => '',
		'further_questions_text' => '',
		'service_placeholder' => '',
		'profits_placeholder' => '',
		'troubles_placeholder' => '',
		'alternatives_placeholder' => '',
		'submitted_term' => '',
		'search_tag' => '',
		'require_login' => false,
		'minimum_age_days' => 2 * 365,
		'admin_email' => get_option( 'admin_url' ),
	], $atts );

	$get_atts = shortcode_atts( [
		'submit_proposal' => '',
	], $_GET );

	$post_atts = shortcode_atts( [
		'submitter' => [],
		'proposal' => [],
	], $_POST );
	$post_atts['submitter'] = shortcode_atts( [
		'name' => '',
		'email' => '',
	], $post_atts['submitter'] );
	$post_atts['proposal'] = shortcode_atts( [
		'title' => '',
		'url' => '',
		'id' => '',
		'justification' => 'no',
		'drawing' => 'no',
		'submitted_justification' => '',
		'current_service' => '',
		'profits' => '',
		'troubles' => '',
		'alternatives' => '',
	], $post_atts['proposal'] );

	$prepend = '';
	if ($_POST) {
		$result = mtl_sorting_phase_form_post( $atts, $post_atts );

		if ( isset($result['return']) )
			return $result['return'];
		else
			$prepend = $result['prepend'];
	}

	$proposal = get_post( intval( $get_atts['submit_proposal'] ) );
	if ( mtl_proposal_submission_errors( $atts, $proposal ) ) {
		$proposal = null;
	}

	wp_localize_script( 'mtl-sorting-phase-form', 'form_variables', [
		'rest_base' => get_rest_url(),
		'search_tag' => $atts['search_tag'],
		'minimum_age_days' => $atts['minimum_age_days'],
	] );

	$user = wp_get_current_user();
	
	return $prepend.
	'<form id="sorting-phase-submission" action="" method="post">
		<p>
			<label>'.
				__('Your name or username', 'my-transit-lines').
				'<br>
				<input type="text" name="submitter[name]" size=40 placeholder="'. $atts['name_placeholder'] .'"'. (($user && $user->exists()) ? ' readonly value="'. $user->display_name .'"' : ' required value="'. $post_atts['submitter']['name'] .'"') .'>
			</label>
		</p>
		<p>
			<label>'.
				__('Your email address (for questions and feedback)', 'my-transit-lines').
				'<br>
				<input type="email" name="submitter[email]" size=40 placeholder="'. $atts['email_placeholder'] .'"'. (($user && $user->exists()) ? ' readonly value="'. $user->user_email .'"' : ' required value="'. $post_atts['submitter']['name'] .'"') .'>
			</label>
		</p>
		<p>
			<label>'.
				__('Name of the proposal', 'my-transit-lines').
				'<br>
				<input type="text" name="proposal[title]" id="proposal-title-input" list="proposal-name-list" size=40 required placeholder="'. $atts['title_placeholder'] .'" value="'. ($proposal ? $proposal->post_title : $post_atts['proposal']['title']) .'">
				<input type="number" style="display: none;" name="proposal[id]" id="proposal-id-input" required value="'. ($proposal ? $proposal->ID : $post_atts['proposal']['id']) .'">
			</label>
			<datalist id="proposal-name-list"></datalist>
		</p>
		<p>
			<label>'.
				__('Link to the proposal', 'my-transit-lines').
				'<br>
				<span id="url-input-wrapper">
					<input type="url" name="proposal[url]" id="proposal-url-input" list="proposal-name-list" size=40 placeholder="'. $atts['url_placeholder'] .'" readonly value="'. ($proposal ? get_permalink( $proposal ) : $post_atts['proposal']['url']) .'">
					<a id="proposal-url-link" href="'. ($proposal ? get_permalink( $proposal ) : $post_atts['proposal']['url']) .'"></a>
				</span>
			</label>
		</p>
		<p>
			<label>'.
				__('Does the proposal have good justification?', 'my-transit-lines').
				'<br>
				<label><input type="radio" name="proposal[justification]" value="somewhat"'. ($post_atts['proposal']['justification'] == 'somewhat' ? ' checked' : '') .'><span>'. __('Somewhat', 'my-transit-lines') .'</span></label>
				<label><input type="radio" name="proposal[justification]" value="yes"'. ($post_atts['proposal']['justification'] == 'yes' ? ' checked' : '') .'><span>'. __('Yes', 'my-transit-lines') .'</span></label>
				<label><input type="radio" name="proposal[justification]" value="no"'. ($post_atts['proposal']['justification'] != 'somewhat' && $post_atts['proposal']['justification'] != 'yes' ? ' checked' : '') .'><span>'. __('No', 'my-transit-lines') .'</span></label>
			</label>
		</p>
		<p>
			<label>'.
				__('Are the map contents drawn well?', 'my-transit-lines').
				'<br>
				<label><input type="radio" name="proposal[drawing]" value="somewhat"'. ($post_atts['proposal']['drawing'] == 'somewhat' ? ' checked' : '') .'> '. __('Somewhat', 'my-transit-lines') .' </label>
				<label><input type="radio" name="proposal[drawing]" value="yes"'. ($post_atts['proposal']['drawing'] == 'yes' ? ' checked' : '') .'> '. __('Yes', 'my-transit-lines') .' </label>
				<label><input type="radio" name="proposal[drawing]" value="no"'. ($post_atts['proposal']['drawing'] != 'somewhat' && $post_atts['proposal']['drawing'] != 'yes' ? ' checked' : '') .'> '. __('No', 'my-transit-lines') .' </label>
			</label>
		</p>
		<p>
			<label>'.
				__('Why should we present this proposal?', 'my-transit-lines').
				'<br>
				<textarea name="proposal[submitted_justification]" cols=40 rows=10 required placeholder="'. $atts['justification_placeholder'] .'">'. $post_atts['proposal']['submitted_justification'] .'</textarea>
			</label>
		</p>
		<p>'.
			$atts['further_questions_text'].
		'</p>
		<p>
			<label>'.
				__('What is the current transit service?', 'my-transit-lines').
				'<br>
				<textarea name="proposal[current_service]" cols=40 rows=10 placeholder="'. $atts['service_placeholder'] .'">'. $post_atts['proposal']['current_service'] .'</textarea>
			</label>
		</p>
		<p>
			<label>'.
				__('Who profits from the proposal and by how much?', 'my-transit-lines').
				'<br>
				<textarea name="proposal[profits]" cols=40 rows=10 placeholder="'. $atts['profits_placeholder'] .'">'. $post_atts['proposal']['profits'] .'</textarea>
			</label>
		</p>
		<p>
			<label>'.
				__('Which troubles or negative impacts could there be?', 'my-transit-lines').
				'<br>
				<textarea name="proposal[troubles]" cols=40 rows=10 placeholder="'. $atts['troubles_placeholder'] .'">'. $post_atts['proposal']['troubles'] .'</textarea>
			</label>
		</p>
		<p>
			<label>'.
				__('What alternatives are there, and why are they not as good?', 'my-transit-lines').
				'<br>
				<textarea name="proposal[alternatives]" cols=40 rows=10 placeholder="'. $atts['alternatives_placeholder'] .'">'. $post_atts['proposal']['alternatives'] .'</textarea>
			</label>
		</p>
		<p>
			<input type="submit" value="'. __('Submit to the sorting phase', 'my-transit-lines') .'">
		</p>
	</form>';
}
add_shortcode( 'mtl-sorting-phase-form', 'mtl_sorting_phase_form_output' );

/**
 * Handles [mtl-sorting-phase-form] shortcode submissions, returning a response
 */
function mtl_sorting_phase_form_post( $atts, $post_atts ) {
	$err = [];

	$user = wp_get_current_user();
	if ( $user->exists() ) {

		if ( !isset( $_POST['submitter'] ) ) {
			$err[] = 'submitter';
		} else {
			if ( !isset( $_POST['submitter']['name'] ) || $_POST['submitter']['name'] !== $user->display_name ) {
				$err[] = 'name';
			}

			if ( !isset( $_POST['submitter']['email'] ) || $_POST['submitter']['email'] !== $user->user_email ) {
				$err[] = 'email';
			}
		}
	} else if ( $atts['require_login'] ) {
		$err[] = 'login';
	}

	global $post;
	if ( !isset( $_POST['proposal'] ) || !isset( $_POST['proposal']['id'] ) )  {
		$err[] = 'proposal';
	} else {
		$proposal = get_post( $_POST['proposal']['id'] );

		$err = array_merge( $err, mtl_proposal_submission_errors( $atts, $proposal ) );

		if ( !isset( $_POST['proposal']['url'] ) || $_POST['proposal']['url'] !== get_permalink( $proposal ) )
			$err[] = 'url';

		if ( !isset( $_POST['proposal']['title'] ) || $_POST['proposal']['title'] !== $proposal->post_title )
			$err[] = 'title';

		if ( !isset( $_POST['proposal']['justification'] ) || !in_array( $_POST['proposal']['justification'], ['yes', 'no', 'somewhat'] ) )
			$err[] = 'justification';

		if ( !isset( $_POST['proposal']['drawing'] ) || !in_array( $_POST['proposal']['drawing'], ['yes', 'no', 'somewhat'] ) )
			$err[] = 'drawing';

		if ( !isset( $_POST['proposal']['submitted_justification'] ) || empty( $_POST['proposal']['submitted_justification'] ) )
			$err[] = 'submitted_justification';

		if ( !isset( $_POST['proposal']['current_service'] ) )
			$err[] = 'current_service';

		if ( !isset( $_POST['proposal']['profits'] ) )
			$err[] = 'profits';

		if ( !isset( $_POST['proposal']['troubles'] ) )
			$err[] = 'troubles';

		if ( !isset( $_POST['proposal']['alternatives'] ) )
			$err[] = 'alternatives';
	}

	if ($err) {
		$err_string = '
		<p>
			<h3>'.
				__('Form submission returned the following errors:').
			'</h3>
			<ul id="sorting-phase-form-errors">';
		foreach ($err as $error) {
			$err_string .= '
				<li class="'. $error .'">';
			
			switch ($error) {
				case 'submitter':
					$err_string .= __('You need to submit your name and email.', 'my-transit-lines');
					break;
				case 'name':
					$err_string .= __('You need to submit a name, or if you\'re logged in your username.', 'my-transit-lines');
					break;
				case 'email':
					$err_string .= __('You need to submit an email, or if you\'re logged in your user\'s email.', 'my-transit-lines');
					break;
				case 'login':
					$err_string .= __('You need to be logged in to submit this form.', 'my-transit-lines');
					break;
				case 'type':
				case 'proposal':
				case 'id':
				case 'url':
				case 'title':
					$err_string .= __('You need to submit an existing proposal.', 'my-transit-lines');
					break;
				case 'status':
					$err_string .= __('You need to submit a published proposal.', 'my-transit-lines');
					break;
				case 'age':
					$err_string .= sprintf(__('You need to submit a proposal that\'s at least %s days old.', 'my-transit-lines'), $atts['minimum_age_days']);
					break;
				case 'region':
					$err_string .= __('You need to submit a proposal from an unlocked region.', 'my-transit-lines');
					break;
				case 'sorting-phase':
					$err_string .= __('You need to submit a proposal that wasn\'t submitted to the sorting phase already.', 'my-transit-lines');
					break;
				case 'justification':
					$err_string .= __('You need to submit whether the proposal has good justification.', 'my-transit-lines');
					break;
				case 'drawing':
					$err_string .= __('You need to submit whether the proposal is well drawn.', 'my-transit-lines');
					break;
				case 'submitted_justification':
					$err_string .= __('You need to submit your own justification for the proposal.', 'my-transit-lines');
					break;
				case 'current_service':
					$err_string .= __('You need to submit what the service without the proposal is like.', 'my-transit-lines');
					break;
				case 'profits':
					$err_string .= __('You need to submit who profits from the proposal.', 'my-transit-lines');
					break;
				case 'troubles':
					$err_string .= __('You need to submit the troubles the proposal creates.', 'my-transit-lines');
					break;
				case 'alternatives':
					$err_string .= __('You need to submit alternatives to the proposal.', 'my-transit-lines');
					break;
				default:
					$err_string .= __('An unknown error occured!', 'my-transit-lines');
					break;
			}
			$err_string .= '
				</li>';
		}
		$err_string .= '
			</ul>
		</p>';

		return ['prepend' => $err_string];
	}

	if ( intval( $atts['submitted_term'] ) ) {
		wp_set_post_terms( $proposal->ID, intval( $atts['submitted_term'] ), 'sorting-phase-status' );
	}

	$to = $post_atts['submitter']['email'];
	$subject = sprintf( __( 'Sorting phase submission for "%s"', 'my-transit-lines' ), $proposal->post_title );
	$headers = ['From: '.sprintf( __('%s via sorting phase form', 'my-transit-lines'), $post_atts['submitter']['name'] ).'<'.get_option( 'admin_email' ).'>' ];

	$body = sprintf( __( 'Hello %s!', 'my-transit-lines' ), $post_atts['submitter']['name'] )."\n\n";
	$body .= sprintf( __( 'You requested the proposal "%1$s" (%2$s) to be presented specially on our website %3$s (%4$s). You justified this as follows:', 'my-transit-lines' ), $post_atts['proposal']['title'], $post_atts['proposal']['url'], get_option( 'blogname' ), get_bloginfo( 'url' ) )."\n";
	$body .= '"'.$post_atts['proposal']['submitted_justification'].'"'."\n\n";
	$body .= __('What is the current transit service?', 'my-transit-lines')."\n";
	$body .= '"'.$post_atts['proposal']['current_service'].'"'."\n";
	$body .= __('Who profits from the proposal and by how much?', 'my-transit-lines')."\n";
	$body .= '"'.$post_atts['proposal']['profits'].'"'."\n";
	$body .= __('Which troubles or negative impacts could there be?', 'my-transit-lines')."\n";
	$body .= '"'.$post_atts['proposal']['troubles'].'"'."\n";
	$body .= __('What alternatives are there, and why are they not as good?', 'my-transit-lines')."\n";
	$body .= '"'.$post_atts['proposal']['alternatives'].'"'."\n\n";
	$body .= __( 'We will examine the proposal and decide whether it matches our criteria.', 'my-transit-lines' )."\n\n";
	$body .= sprintf( __( 'Thank you for your support to make %s and maybe public transit in the future a bit better.', 'my-transit-lines' ), get_option( 'blogname' ) )."\n\n";
	$body .= sprintf( __( 'This mail was sent by a form from %1$s (%2$s).', 'my-transit-lines' ), get_option( 'blogname' ), get_bloginfo( 'url' ) );

	$admin_body = sprintf( __( 'By: %1$s <%2$s>', 'my-transit-lines' ), $post_atts['submitter']['name'], $post_atts['submitter']['email'] )."\n";
	$admin_body .= sprintf( __( 'Proposal: %s', 'my-transit-lines' ), $post_atts['proposal']['title'] )."\n";
	$admin_body .= sprintf( __( 'URL: %s', 'my-transit-lines' ), $post_atts['proposal']['url'] )."\n\n";
	$admin_body .= __( 'Submitted justification:', 'my-transit-lines' )."\n";
	$admin_body .= '"'.$post_atts['proposal']['submitted_justification'].'"'."\n\n";
	$admin_body .= __( 'Current service:', 'my-transit-lines' )."\n";
	$admin_body .= '"'.$post_atts['proposal']['current_service'].'"'."\n\n";
	$admin_body .= __( 'Profits:', 'my-transit-lines' )."\n";
	$admin_body .= '"'.$post_atts['proposal']['profits'].'"'."\n\n";
	$admin_body .= __( 'Troubles:', 'my-transit-lines' )."\n";
	$admin_body .= '"'.$post_atts['proposal']['troubles'].'"'."\n\n";
	$admin_body .= __( 'Alternatives:', 'my-transit-lines' )."\n";
	$admin_body .= '"'.$post_atts['proposal']['alternatives'].'"'."\n\n";
	$admin_body .= "——————————————————\n\n";
	$admin_body .= sprintf( __( 'Good justification: %s', 'my-transit-lines' ), $post_atts['proposal']['justification'] )."\n\n";
	$admin_body .= sprintf( __( 'Good drawing: %s', 'my-transit-lines' ), $post_atts['proposal']['drawing'] )."\n\n";
	$admin_body .= "——————————————————\n\n";
	$admin_body .= sprintf( __( 'This mail was sent by a form from %1$s (%2$s).', 'my-transit-lines' ), get_option( 'blogname' ), get_bloginfo( 'url' ) );

	// Send confirmation mail to the user
	wp_mail( $to, $subject, $body, array_merge( $headers, ['Reply-To: '.$atts['admin_email']] ) );

	// Send a copy of the mail to the admins
	wp_mail( $atts['admin_email'], $subject, $admin_body, array_merge( $headers, ['Reply-To: '.$post_atts['submitter']['email']] ) );

	return ['return' => '<p>'.__( 'Thank your for your submission. The data was submitted correctly and you should receive a confirmation email shortly.', 'my-transit-lines' ).'</p>'];
}

/**
 * Returns an array of error descriptors for the given $proposal.
 * An empty array is returned iff the proposal is valid
 */
function mtl_proposal_submission_errors( $atts, $proposal ) {
	global $post;

	$err = [];

	// Check existence
	if ( !$proposal || $proposal === $post ) {
		return ['proposal'];
	}

	// Check type
	if ( $proposal->post_type !== 'mtlproposal' )
		$err[] = 'type';

	// Check status
	if ( $proposal->post_status !== 'publish' )
		$err[] = 'status';

	// Check age
	$age = get_post_datetime( $proposal, 'date', 'gmt' )->diff( new DateTimeImmutable( "now", new DateTimeZone( "UTC" ) ) );
	if ( $age->d + $age->y * 365 < $atts['minimum_age_days'] ) {
		$err[] = 'age';
	}

	// Check region
	if ( $atts['search_tag'] && intval( $atts['search_tag'] ) && !array_any( wp_get_post_terms( $proposal->ID, 'post_tag' ), function( $term ) use ( $atts ) {
		return $term->term_id === intval( $atts['search_tag'] );
	} ) ) {
		$err[] = 'region';
	}

	// Check sorting phase status
	if ( array_any( wp_get_post_terms( $proposal->ID, 'sorting-phase-status' ), function( $term ) {
		return $term->slug !== 'not-submitted';
	} ) ) {
		$err[] = 'sorting-phase';
	}

	return $err;
}