<?php
/**
 * View Admin As - Class API
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

/**
 * API class that holds general functions
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.6.4
 */
final class VAA_API
{
	/**
	 * Check if the original current user is a super admin
	 *
	 * @since   1.6.3
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   int  $user_id  (optional) Default: current user.
	 * @return  bool
	 */
	public static function is_super_admin( $user_id = null ) {
		return VAA_View_Admin_As_Store::is_super_admin( $user_id );
	}

	/**
	 * Check if the user is a superior admin
	 *
	 * @since   1.5.3
	 * @since   1.6    Moved to this class from main class
	 * @since   1.6.3  Improve is_super_admin() check
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   int  $user_id  (optional) Default: current user.
	 * @return  bool
	 */
	public static function is_superior_admin( $user_id = null ) {

		// If it's the current user or null, don't pass the user ID to make sure we check the original user status.
		$is_super_admin = self::is_super_admin(
			( null !== $user_id && (int) get_current_user_id() === (int) $user_id ) ? null : $user_id
		);

		// Is it a super admin and is it one of the manually configured superior admins?
		return (bool) ( true === $is_super_admin && in_array( (int) $user_id, self::get_superior_admins(), true ) );
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
	 * Is any toolbar showing?
	 * Do not use this before the `init` hook.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function is_toolbar_showing() {

		if ( is_admin_bar_showing() || self::is_vaa_toolbar_showing() ) {
			return true;
		}
		return false;
	}

	/**
	 * Is our custom toolbar showing?
	 * Do not use this before the `init` hook.
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
	 * For WP < 4.0!
	 *
	 * @since   1.6.2
	 * @see     https://developer.wordpress.org/reference/functions/is_customize_preview/
	 * @global  WP_Customize_Manager  $wp_customize
	 * @return  bool
	 */
	public static function is_customize_preview() {

		if ( function_exists( 'is_customize_preview' ) ) {
			return is_customize_preview();
		}

		global $wp_customize;
		return ( $wp_customize instanceof WP_Customize_Manager ) && $wp_customize->is_preview();
	}

	/**
	 * Appends the "reset-view" parameter to the current URL.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $url  (optional) Use a defined url create the reset link.
	 * @param   bool    $all  (optional) Reset all views link?
	 * @return  string
	 */
	public static function get_reset_link( $url = '', $all = false ) {

		if ( empty( $url ) ) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			// Check protocol.
			$url = ( is_ssl() ? 'https://' : 'http://' ) . $url;
		}

		// Check for existing query vars.
		$url_comp = parse_url( $url );

		$reset = 'reset-view';
		if ( $all ) {
			$reset = 'reset-all-views';
		}

		return esc_url( $url . ( ( isset( $url_comp['query'] ) ) ? '&' : '?' ) . $reset, array( 'http', 'https' ) );
	}

	/**
	 * Removes the "reset-view" or "reset-all-views" parameter to the current URL.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $url  (optional) Use a defined url to remove the reset link.
	 * @return  string
	 */
	public static function remove_reset_link( $url = '' ) {

		if ( empty( $url ) ) {
			$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			// Check protocol
			$url = ( ( is_ssl() ) ? 'https://' : 'http://' ) . $url;
		}

		if ( false !== strpos( $url, '?' ) ) {
			$url = explode( '?', $url );

			if ( ! empty( $url[1] ) ) {

				$url[1] = explode( '&', $url[1] );
				foreach ( $url[1] as $key => $val ) {
					if ( in_array( $val, array( 'reset-view', 'reset-all-views' ), true ) ) {
						unset( $url[1][ $key ] );
					}
				}
				$url[1] = implode( '&', $url[1] );

			}
			$url = implode( '?', $url );
		}

		return esc_url( $url, array( 'http', 'https' ) );
	}

	/**
	 * Get full array or array key.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $array  The requested array.
	 * @param   string  $key    (optional) Return only a key of the requested array.
	 * @return  mixed
	 */
	public static function get_array_data( $array, $key = null ) {
		if ( null !== $key ) {
			if ( isset( $array[ $key ] ) ) {
				return $array[ $key ];
			}
			return null; // return null if key is not found
		}
		return $array;
	}

	/**
	 * Set full array or array key.
	 *
	 * @since   1.5
	 * @since   1.6    Moved to this class from main class.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $array   Original array.
	 * @param   mixed   $var     The new value.
	 * @param   string  $key     (optional) The array key for the value.
	 * @param   bool    $append  (optional) If the key doesn't exist in the original array, append it.
	 * @return  mixed
	 */
	public static function set_array_data( $array, $var, $key = null, $append = false ) {
		if ( null !== $key ) {
			if ( true === $append && ! is_array( $array ) ) {
				$array = array();
			}
			if ( true === $append || isset( $array[ $key ] ) ) {
				$array[ $key ] = $var;
				return $array;
			}

			// Notify user if in debug mode
			_doing_it_wrong( __METHOD__, 'View Admin As: Key <code>' . (string) $key . '</code> does not exist', null );

			// return no changes if key is not found or appending is not allowed.
			return $array;
		}
		return $var;
	}

	/**
	 * Check if two arrays are the same.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array  $array1  Array one
	 * @param   array  $array2  Array two
	 * @return  bool
	 */
	public static function array_equal( $array1, $array2 ) {
		return (
			is_array( $array1 ) && is_array( $array2 ) &&
			count( $array1 ) === count( $array2 ) &&
			array_diff_assoc( $array1, $array2 ) === array_diff_assoc( $array2, $array1 )
		);
	}

	/**
	 * Compare with the current WordPress version.
	 * Returns true when it's the provided version or newer.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @static
	 * @api
	 *
	 * @global  string      $wp_version  WordPress version.
	 * @param   int|string  $version     The WP version to check.
	 * @return  bool
	 */
	public static function validate_wp_version( $version ) {
		global $wp_version;
		if ( version_compare( $wp_version, $version, '<' ) ) {
			return false;
		}
		return true;
	}

	/**
	 * AJAX Request validator. Verifies caller and nonce.
	 * Returns the requested data.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $nonce  The nonce to validate
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  mixed
	 */
	public static function get_ajax_request( $nonce, $key = null, $type = 'post' ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return self::get_request( $nonce, $key, $type );
		}
		return null;
	}

	/**
	 * AJAX Request validator. Verifies caller and nonce.
	 * Returns the requested data.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $nonce  The nonce to validate
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  mixed
	 */
	public static function get_normal_request( $nonce, $key = null, $type = 'post' ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return self::get_request( $nonce, $key, $type );
		}
		return null;
	}

	/**
	 * Request validator. Verifies caller and nonce.
	 * Returns the requested data.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $nonce  The nonce to validate
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  mixed
	 */
	public static function get_request( $nonce, $key = null, $type = 'post' ) {
		// @codingStandardsIgnoreStart
		$data = ( 'get' === $type ) ? $_GET : $_POST;
		// @codingStandardsIgnoreEnd
		if ( isset( $data[ $key ] ) && isset( $data['_vaa_nonce'] ) && wp_verify_nonce( $data['_vaa_nonce'], $nonce ) ) {
			return VAA_API::get_array_data( $data, $key );
		}
		return null;
	}

	/**
	 * AJAX Request check.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_ajax_request( $key = null, $type = 'post' ) {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return self::is_request( $key, $type );
		}
		return false;
	}

	/**
	 * Normal Request check.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_normal_request( $key = null, $type = 'post' ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return self::is_request( $key, $type );
		}
		return false;
	}

	/**
	 * Check if there is a request made.
	 *
	 * @since   1.6.x
	 * @access  public
	 * @param   string  $key    The key to check.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_request( $key = null, $type = 'post' ) {
		// @codingStandardsIgnoreStart
		$data = ( 'get' === $type ) ? $_GET : $_POST;
		// @codingStandardsIgnoreEnd
		if ( isset( $data[ $key ] ) ) {
			return true;
		}
		return false;
	}

} // end class.
