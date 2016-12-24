<?php
/**
 * View Admin As - Class API
 *
 * API class that holds general functions
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.6
 * @version 1.6.2
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

final class VAA_API
{
	/**
	 * Check if the user is a superior admin
	 *
	 * @since   1.5.3
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   int  $user_id
	 * @return  bool
	 */
	public static function is_superior_admin( $user_id ) {
		// Is it a super admin and is it one of the manually configured superior admins?
		return (bool) ( true === is_super_admin( $user_id ) && in_array( $user_id, self::get_superior_admins() ) );
	}

	/**
	 * Get the superior admin ID's (filter since 1.5.2)
	 *
	 * @since   1.5.3
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return array
	 */
	public static function get_superior_admins() {

		/**
		 * Grant admins the capability to view other admins. There is no UI for this!
		 *
		 * @since  1.5.2
		 * @param  array
		 * @return array requires a returned array of user ID's
		 */
		return array_filter(
			(array) apply_filters( 'view_admin_as_superior_admins', array() ),
			'is_numeric'  // Only allow numeric values (user id's)
		);
	}

	/**
	 * Get full array or array key
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $array  The requested array
	 * @param   string  $key    Return only a key of the requested array (optional)
	 * @return  mixed
	 */
	final public static function get_array_data( $array, $key = null ) {
		if ( null !== $key ) {
			if ( isset( $array[ $key ] ) ) {
				return $array[ $key ];
			}
			return false; // return false if key is not found
		}
		return $array;
	}

	/**
	 * Set full array or array key
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $array   Original array
	 * @param   mixed   $var     The new value
	 * @param   string  $key     The array key for the value (optional)
	 * @param   bool    $append  If the key doesn't exist in the original array, append it (optional)
	 * @return  mixed
	 */
	final public static function set_array_data( $array, $var, $key = null, $append = false ) {
		if ( null !== $key ) {
			if ( true === $append && ! is_array( $array ) ) {
				$array = array();
			}
			if ( true === $append || isset( $array[ $key ] ) ) {
				$array[ $key ] = $var;
				return $array;
			}

			// Notify user if in debug mode
			if ( defined('WP_DEBUG') && true === WP_DEBUG ) {
				trigger_error('View Admin As: Key does not exist', E_USER_NOTICE);
				if ( ! defined('WP_DEBUG_DISPLAY') || ( defined('WP_DEBUG_DISPLAY') && true === WP_DEBUG_DISPLAY ) ) {
					debug_print_backtrace();
				}
			}

			return $array; // return no changes if key is not found or appending is not allowed
		}
		return $var;
	}

	/**
	 * Is our custom toolbar showing?
	 * Do not use this before the `init` hook
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function is_vaa_toolbar_showing() {

		if ( class_exists( 'VAA_View_Admin_As_Toolbar' ) && VAA_View_Admin_As_Toolbar::$showing ) {
			return true;
		}
		return false;
	}

	/**
	 * Whether the site is being previewed in the Customizer.
	 * For WP < 4.0
	 *
	 * @since   1.6.2
	 * @see     https://developer.wordpress.org/reference/functions/is_customize_preview/
	 * @global  WP_Customize_Manager  $wp_customize
	 * @return  bool
	 */
	public static function is_customize_preview() {

		if ( function_exists('is_customize_preview') ) {
			return is_customize_preview();
		}

		global $wp_customize;
		return ( $wp_customize instanceof WP_Customize_Manager ) && $wp_customize->is_preview();
	}

	/**
	 * Appends the "reset-view" parameter to the current URL
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $url  (optional) Use a defined url create the reset link
	 * @param   bool    $all  (optional) Reset all views link?
	 * @return  string
	 */
	public static function get_reset_link( $url = '', $all = false ) {

		if ( empty( $url ) ) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			// Check protocol
			$url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $url;
		}

		// Check for existing query vars
		$url_comp = parse_url( $url );

		$reset = 'reset-view';
		if ( $all ) {
			$reset = 'reset-all-views';
		}

		return $url . ( ( isset ( $url_comp['query'] ) ) ? '&' : '?' ) . $reset;
	}

	/**
	 * Removes the "reset-view" or "reset-all-views" parameter to the current URL
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $url  (optional) Use a defined url to remove the reset link
	 * @return  string
	 */
	public static function remove_reset_link( $url = '' ) {

		if ( empty( $url ) ) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			// Check protocol
			$url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $url;
		}

		if ( strpos( $url, '?' ) !== false ) {
			$url = explode( '?', $url );

			if ( ! empty( $url[1] ) ) {

				$url[1] = explode( '&', $url[1] );
				foreach ( $url[1] as $key => $val ) {
					if ( in_array( $val, array( 'reset-view', 'reset-all-views' ) ) ) {
						unset( $url[1][ $key ] );
					}
				}
				$url[1] = implode( '&', $url[1] );

			}
			$url = implode( '?', $url );
		}

		return $url;
	}

} // end class