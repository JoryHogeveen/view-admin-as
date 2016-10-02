<?php
/**
 * View Admin As - Uninstaller
 *
 * Remove plugin data from the database
 *
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @version 1.5.x
 */

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}


vaa_uninstall();

if ( is_multisite() ) {
	global $wp_version;
	if ( version_compare( $wp_version, '4.5.999', '<' ) ) {
		// Sadly does not work for large networks -> return false
		$blogs = wp_get_sites();
	} else {
		$blogs = get_sites();
	}
	if ( $blogs ) {
		foreach ( $blogs as $blog ) {
			$blog = (array) $blog;
			vaa_uninstall( intval( $blog['blog_id'] ) );
		}
		vaa_uninstall( 'site' );
	}
}
function vaa_uninstall( $blog_id = false ) {

	// Delete all View Admin As options
	$option_keys = array( 'vaa_view_admin_as', 'vaa_role_defaults' );

	if ( $blog_id ) {

		if ( $blog_id == 'site' ) {
			foreach ( $option_keys as $option_key ) {
				delete_site_option( $option_key );
			}
		} else {
			foreach ( $option_keys as $option_key ) {
				delete_blog_option( $blog_id, $option_key );
			}
		}

	} else {

		foreach ( $option_keys as $option_key ) {
			delete_option( $option_key );
		}

		// Delete all View Admin As user metadata
		$user_meta_keys = array( 'vaa-view-admin-as' );
		// Older (not used anymore) keys
		$user_meta_keys[] = 'view-admin-as';

		global $wpdb;
		$all_users = $wpdb->get_results("SELECT ID FROM $wpdb->users");
		foreach ( $all_users as $user ) {
			foreach ( $user_meta_keys as $user_meta_key ) {
				delete_user_meta( $user->ID, $user_meta_key );
			}
		}
	}
}
