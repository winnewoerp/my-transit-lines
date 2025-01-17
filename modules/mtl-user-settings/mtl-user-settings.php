<?php
/**
 * My Transit Lines
 * User settings
 *
 * @package My Transit Lines
 */
 
/* created by Jan Garloff, 2025-01-14 */

/**
 * The field on the editing screens.
 *
 * @param $user WP_User user object
 */
function mtl_custom_user_settings_fields( $user ) {
	?>
	<h3><?= __('My Transit Lines Settings','my-transit-lines') ?></h3>
	<table class="form-table">
		<tr>
			<th>
				<?= __('Proposal Publishing','my-transit-lines') ?>
			</th>
			<td>
				<input type="checkbox"
					id="mtl-dont-publish"
					name="mtl-dont-publish"
					<?= get_user_meta( $user->ID, 'mtl-dont-publish', true ) ? 'checked' : '' ?>
				>
				<label for="mtl-dont-publish">
					<?php _e('Never publish the posts of this user immediately','my-transit-lines'); ?>
				</label>
			</td>
		</tr>
	</table>
	<?php
}
add_action( 'show_user_profile', 'mtl_custom_user_settings_fields' );
add_action( 'edit_user_profile', 'mtl_custom_user_settings_fields' );

/**
 * The save action.
 *
 * @param $user_id int the ID of the current user.
 *
 * @return bool Meta ID if the key didn't exist, true on successful update, false on failure.
 */
function mtl_custom_user_settings_update( $user_id ) {
    // check that the current user have the capability to edit the $user_id
    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
 
    // create/update user meta for the $user_id
    return update_user_meta( $user_id, 'mtl-dont-publish', isset( $_POST['mtl-dont-publish'] ) );
}
add_action( 'personal_options_update', 'mtl_custom_user_settings_update' );
add_action( 'edit_user_profile_update', 'mtl_custom_user_settings_update' );

/**
 * [mtl-user-settings] shortcode
 */
function mtl_user_settings_output( $atts ) {
	if ( !is_user_logged_in() ) {
		return 
		'<p>'.
			__('You need to be logged in to access your personal settings!','my-transit-lines').
		'</p>';
	}

	$submit_message = '';
	if ( !empty( $_POST ) ) {
		$result = mtl_user_settings_post( $atts );

		$submit_message = 
		'<ul id="user-settings-form-success">
			<li>'.
				__('Your changes were saved successfully!','my-transit-lines').
			'</li>
		</ul>';
	}

	global $user_login;

	return $submit_message.
	'<form id="mtl-user-settings-form" method="post" action="">
		<input type="hidden" value=0 name="mtl-arbitrary-payload">
		<h2>'.
			__('Profile settings','my-transit-lines').
		'</h2>
		<table class="form-table" role="presentation"><tbody>
			<tr>
				<th scope="row">'.
					__('Profile name','my-transit-lines').
				'</th>
				<td>
					<p>'.
						$user_login.
						' <small>'.
							__('Your username cannot be changed.','my-transit-lines').
						'</small>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row">'.
					__('Profile picture','my-transit-lines').
				'</th>
				<td>'.
					get_avatar( get_current_user_id() ).
					'<p>
						<a href="https://gravatar.com">'.
							__('You can change your profile picture using Gravatar', 'my-transit-lines').
						'</a>
					</p>
				</td>
			</tr>
		</tbody></table>
		<h2>'.
			__('Contact settings','my-transit-lines').
		'</h2>
		<table class="form-table" role="presentation"><tbody>
			<tr>
				<th scope="row">'.
					__('Contact button','my-transit-lines').
				'</th>
				<td>
					<label for="enable-contact-button">
						<input type="checkbox" id="enable-contact-button" '.(get_user_meta( get_current_user_id(), 'enable-contact-button', true ) ? 'checked' : '').' name="enable-contact-button"> '.
						esc_html__('Enable contact button for my finished proposals','my-transit-lines').
					'</label>
					<br>
					<small>'.
						esc_html__('This enables a contact button within your proposals linked to a contact form where interested people can contact you. On submit, an email with the form data is being sent to you (and in copy to the admin team). Your email address is not visible to the respective person until you reply to her/him.','my-transit-lines').
					'</small>
				</td>
			</tr>
		</tbody></table>

		<p class="aligncenter">
			<input type="submit" value="'.__('Save changes','my-transit-lines').'">
		</p>
	</form>';
}
add_shortcode( 'mtl-user-settings', 'mtl_user_settings_output' );

/**
 * Updates the user meta according to the $_POST and $atts variables
 * 
 * Returns an associative array of which updates were successful or not, in the form of meta_key => bool
 */
function mtl_user_settings_post( $atts ) {
	update_user_meta( get_current_user_id(), 'enable-contact-button', isset( $_POST['enable-contact-button'] ) );
}
