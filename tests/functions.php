<?php
/**
 * View Admin As - Unit tests installation
 *
 * Functions and pluggable function overwrites.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

/**
 * Retrieve user info by a given field.
 *
 * @see get_user_by() >> wp-includes/pluggable.php
 *
 * @param string     $field The field to retrieve the user with. id | ID | slug | email | login.
 * @param int|string $value A value for $field. A user ID, slug, email address, or login name.
 * @return WP_User|false WP_User object on success, false on failure.
 */
function get_user_by( $field, $value ) {

	if ( class_exists( 'VAA_UnitTest_Factory' ) ) {
		$user = VAA_UnitTest_Factory::get_user( $value, ( $field ) ? $field : 'ID' );
		if ( $user ) {
			return $user;
		}
	}

	// User doesn't exists in VAA factory, load it like normal.
	// @todo Keep this synced with WP Core get_user_by()

	$userdata = WP_User::get_data_by( $field, $value );

	if ( !$userdata )
		return false;

	$user = new WP_User;
	$user->init( $userdata );

	return $user;
}