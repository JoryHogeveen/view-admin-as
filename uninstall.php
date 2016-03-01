<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();


if ( ! is_multisite() ) {
	vaa_uninstall();
} else {
    $blogs = wp_get_sites(); // Sadly does not work for large networks -> return false
	if ($blogs) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( intval( $blog['blog_id'] ) );
			vaa_uninstall();
		}
		restore_current_blog();
	}
}

function vaa_uninstall() {
	
	// Delete all View Admin As options
	$option_keys = array( 'vaa_view_admin_as', 'vaa_role_defaults' );
	foreach ( $option_keys as $option_key ) {
		delete_option( $option_key );
	}
	
	// Delete all View Admin As user metadata
	$user_meta_keys = array( 'view-admin-as', 'vaa-view-admin-as' );
	$users = get_users();
	foreach ( $users as $user ) {
		foreach ( $user_meta_keys as $user_meta_key ) {
			delete_user_meta( $user->ID, $user_meta_key );
		}
	}
	
}
