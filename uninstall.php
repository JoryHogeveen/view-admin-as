<?php

//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) 
    exit();


if ( ! is_multisite() ) {
    vaa_uninstall_delete_user_meta();
} else {
    $blogs = wp_get_sites(); // Sadly does not work for large networks -> return false
	if ($blogs) {
		foreach ( $blogs as $blog ) {
			switch_to_blog( intval( $blog['blog_id'] ) );
			vaa_uninstall_delete_user_meta();
		}
		restore_current_blog();
	}
}

// Delete all View Admin As user metadata
function vaa_uninstall_delete_user_meta() {
	$user_meta = array('vaa-view-admin-as');
	$users = get_users();
	foreach ($users as $user) {
		foreach ($user_meta as $meta) {
			delete_user_meta($user->ID, $meta);
		}
	}
}
