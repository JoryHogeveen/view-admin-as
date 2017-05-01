<?php
/**
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   0.1
 * @version 1.7.1
 * @licence GPL-2.0+
 * @link    https://github.com/JoryHogeveen/view-admin-as
 *
 * @wordpress-plugin
 * Plugin Name:       View Admin As
 * Plugin URI:        https://wordpress.org/plugins/view-admin-as/
 * Description:       View the WordPress admin as a different role or visitor, switch between users, temporarily change your capabilities, set default screen settings for roles.
 * Version:           1.7.1
 * Author:            Jory Hogeveen
 * Author URI:        https://www.keraweb.nl
 * Text Domain:       view-admin-as
 * Domain Path:       /languages/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/JoryHogeveen/view-admin-as
 *
 * @copyright 2015-2017 Jory Hogeveen
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

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'VAA_View_Admin_As' ) ) {

	define( 'VIEW_ADMIN_AS_VERSION',    '1.7.1' );
	define( 'VIEW_ADMIN_AS_DB_VERSION', '1.7' );
	define( 'VIEW_ADMIN_AS_FILE',       __FILE__ );
	define( 'VIEW_ADMIN_AS_BASENAME',   plugin_basename( VIEW_ADMIN_AS_FILE ) );
	define( 'VIEW_ADMIN_AS_DIR',        plugin_dir_path( VIEW_ADMIN_AS_FILE ) );
	define( 'VIEW_ADMIN_AS_URL',        plugin_dir_url( VIEW_ADMIN_AS_FILE ) );
	define( 'VIEW_ADMIN_AS_DOMAIN',     'view-admin-as' );

	// Include main init class file.
	require_once( VIEW_ADMIN_AS_DIR . 'includes/class-vaa.php' );

	/**
	 * Main instance of View Admin As.
	 * Returns the main instance of VAA_View_Admin_As to prevent the need to use globals.
	 *
	 * @since   1.4.1
	 * @since   1.6.4   Changed to lowercase (style fix).
	 * @return  VAA_View_Admin_As
	 */
	function view_admin_as() {
		return VAA_View_Admin_As::get_instance();
	}

	// Instantiate View Admin As.
	view_admin_as();

// end if class_exists.
} else {

	// @since  1.5.1  added notice on class name conflict.
	add_action( 'admin_notices', 'view_admin_as_conflict_admin_notice' );
	function view_admin_as_conflict_admin_notice() {
		echo '<div class="notice-error notice is-dismissible"><p><strong>' . esc_html__( 'View Admin As', 'view-admin-as' ) . ':</strong> '
			. esc_html__( 'Plugin not activated because of a conflict with an other plugin or theme', 'view-admin-as' )
		    // Translators: %s stands for the class name.
			. ' <code>(' . sprintf( esc_html__( 'Class %s already exists', 'view-admin-as' ), 'VAA_View_Admin_As' ) . ')</code></p></div>';
	}
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	deactivate_plugins( plugin_basename( __FILE__ ) );

} // End if().
