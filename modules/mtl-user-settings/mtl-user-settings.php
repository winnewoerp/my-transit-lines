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
    return update_user_meta(
        $user_id,
        'mtl-dont-publish',
        isset( $_POST['mtl-dont-publish'] )
    );
}
add_action( 'personal_options_update', 'mtl_custom_user_settings_update' );
add_action( 'edit_user_profile_update', 'mtl_custom_user_settings_update' );

