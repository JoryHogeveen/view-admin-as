<?php
/**
 * Plugin Name: View Admin As
 * Description: View the WordPress admin as a different role or visitor, switch between users, temporarily change your capabilities, set default screen settings for roles.
 * Plugin URI:  https://wordpress.org/plugins/view-admin-as/
 * Version:     1.6.2
 * Author:      Jory Hogeveen
 * Author URI:  https://www.keraweb.nl
 * Text Domain: view-admin-as
 * Domain Path: /languages/
 * License:     GPLv2
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   0.1
 * @version 1.6.2
 */

/*
 * Copyright 2015-2016 Jory Hogeveen
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * ( at your option ) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

! defined( 'ABSPATH' ) and die( 'You shall not pass!' );

if ( ! class_exists( 'VAA_View_Admin_As' ) ) {

	define( 'VIEW_ADMIN_AS_VERSION',    '1.6.2' );
	define( 'VIEW_ADMIN_AS_DB_VERSION', '1.6' );
	define( 'VIEW_ADMIN_AS_FILE',       __FILE__ );
	define( 'VIEW_ADMIN_AS_BASENAME',   plugin_basename( VIEW_ADMIN_AS_FILE ) );
	define( 'VIEW_ADMIN_AS_DIR',        plugin_dir_path( VIEW_ADMIN_AS_FILE ) );
	define( 'VIEW_ADMIN_AS_URL',        plugin_dir_url( VIEW_ADMIN_AS_FILE ) );

	// Include main init class file
	require_once( VIEW_ADMIN_AS_DIR . 'includes/class-vaa.php' );

	/**
	 * Main instance of View Admin As.
	 *
	 * Returns the main instance of VAA_View_Admin_As to prevent the need to use globals.
	 * Only for internal use. If the $caller parameter passes an unknown object it will return null.
	 *
	 * @since   1.4.1
	 * @since   1.6     $caller parameter
	 * @param   object  $caller
	 * @return  VAA_View_Admin_As
	 */
	function View_Admin_As( $caller ) {
		return VAA_View_Admin_As::get_instance( $caller );
	}

	// Instantiate View Admin As
	VAA_View_Admin_As::instantiate();

// end if class_exists
} else {

	// @since  1.5.1  added notice on class name conflict
	add_action( 'admin_notices', 'view_admin_as_conflict_admin_notice' );
	function view_admin_as_conflict_admin_notice() {
		echo '<div class="notice-error notice is-dismissible"><p><strong>' . __( 'View Admin As', 'view-admin-as' ) . ':</strong> '
			. __('Plugin not activated because of a conflict with an other plugin or theme', 'view-admin-as')
			. ' <code>(' . sprintf( __( 'Class %s already exists', 'view-admin-as' ), 'VAA_View_Admin_As' ) . ')' . '</code></p></div>';
	}
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( plugin_basename( __FILE__ ) );

}
