<?php
/**
 * View Admin As - Class API
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * API class that holds general functions.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @version 1.7.3
 */
final class VAA_API
{
	/**
	 * Check if the user is a super admin.
	 * It will validate the original user while in a view and no parameter is passed.
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
		$store = view_admin_as()->store();
		if ( $store ) {
			return $store->is_super_admin( $user_id );
		}
		return is_super_admin( $user_id );
	}

	/**
	 * Check if the user is a superior admin.
	 * It will validate the original user while in a view and no parameter is passed.
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

		if ( null === $user_id ) {
			$store = view_admin_as()->store();
			if ( $store ) {
				$user_id = $store->get_originalUserData( 'ID' );
			}
		}

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
		static $superior_admins;
		if ( ! is_null( $superior_admins ) ) return $superior_admins;

		/**
		 * Grant admins the capability to view other admins. There is no UI for this!
		 *
		 * @since  1.5.2
		 * @param  array
		 * @return array requires a returned array of user ID's
		 */
		$superior_admins = array_unique( array_map( 'absint', array_filter(
			(array) apply_filters( 'view_admin_as_superior_admins', array() ),
			'is_numeric'  // Only allow numeric values (user id's)
		) ) );

		return $superior_admins;
	}

	/**
	 * Check if the provided data is the same as the current view.
	 *
	 * @since   1.7.1
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   mixed  $data
	 * @param   bool   $type  Only compare a single view type instead of all view data?
	 *                        If set, the data value should be the single view type data.
	 *                        If data is `null` then it will return true if that view type is active.
	 *                        If data is `false` then it will return true if this is the only active view type.
	 * @return  bool
	 */
	public static function is_current_view( $data, $type = null ) {
		$controller = view_admin_as()->controller();
		if ( $controller ) {
			return $controller->is_current_view( $data, $type );
		}
		return false;
	}

	/**
	 * Similar function to current_user_can().
	 *
	 * @since   1.7.2
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $cap   The capability.
	 * @param   array   $caps  (optional) Capabilities to compare to.
	 *                         Defaults to the selected caps for the current view.
	 * @return  bool
	 */
	public static function current_view_can( $cap, $caps = array() ) {
		$view = view_admin_as()->view();
		if ( $view ) {
			return $view->current_view_can( $cap, $caps );
		}
		return false;
	}

	/**
	 * Is the current user modified?
	 *
	 * @since   1.7.2
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function is_user_modified() {
		$view = view_admin_as()->view();
		if ( $view ) {
			return $view->is_user_modified();
		}
		return false;
	}

	/**
	 * Is any toolbar showing?
	 * Do not use this before the `init` hook.
	 *
	 * @since   1.7
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
	 * Generate a VAA action link.
	 *
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $data   View type data.
	 * @param   string  $nonce  The nonce.
	 * @param   string  $url    (optional) A URL. Of not passed it will generate a link from the current URL.
	 * @return  string
	 */
	public static function get_vaa_action_link( $data, $nonce, $url = null ) {

		$params = array(
			'action'        => 'view_admin_as',
			'view_admin_as' => $data, // wp_json_encode( array( $type, $data ) ),
			'_vaa_nonce'    => (string) $nonce,
		);

		// @todo fix WP referrer/nonce checks and allow switching on any page without ajax.
		// @see https://codex.wordpress.org/Function_Reference/check_admin_referer
		if ( empty( $url ) ) {
			$url = is_network_admin() ? network_admin_url() : admin_url();
		}

		$url = add_query_arg( $params, ( $url ) ? $url : false );

		return esc_url( $url, array( 'http', 'https' ) );
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
	public static function get_reset_link( $url = null, $all = false ) {
		$params = 'reset-view';
		if ( $all ) {
			$params = 'reset-all-views';
		}
		$url = add_query_arg( $params, '', ( $url ) ? $url : false );
		return esc_url( $url, array( 'http', 'https' ) );
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
		$url = remove_query_arg( array( 'reset-view', 'reset-all-views' ), ( $url ) ? $url : false );
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
			if ( is_array( $array ) && isset( $array[ $key ] ) ) {
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
			if ( is_array( $array ) && ( true === $append || isset( $array[ $key ] ) ) ) {
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
	 * Does NOT support recursive arrays!
	 *
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array  $array1     Array one.
	 * @param   array  $array2     Array two.
	 * @param   bool   $recursive  Compare recursively.
	 * @param   bool   $strict     Strict comparison? Only available when comparing recursive.
	 * @return  bool
	 */
	public static function array_equal( $array1, $array2, $recursive = true, $strict = false ) {
		if ( ! is_array( $array1 ) || ! is_array( $array2 ) ) {
			return false;
		}
		if ( $recursive ) {
			return (
				self::array_diff_assoc_recursive( $array1, $array2, $strict ) === self::array_diff_assoc_recursive( $array2, $array1, $strict )
			);
		}
		// Check for recursive arrays.
		$arr1 = array_filter( $array1, 'is_scalar' );
		$arr2 = array_filter( $array2, 'is_scalar' );
		if ( $array1 !== $arr1 || $array2 !== $arr2 ) {
			return false;
		}
		return (
			count( $arr1 ) === count( $arr2 ) &&
			array_diff_assoc( $arr1, $arr2 ) === array_diff_assoc( $arr2, $arr1 )
		);
	}

	/**
	 * Recursive version of `array_diff_assoc()`.
	 *
	 * @since   1.7.3
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array  $array1  Array one.
	 * @param   array  $array2  Array two.
	 * @param   bool   $strict  Strict comparison?
	 * @return  array
	 */
	public static function array_diff_assoc_recursive( $array1, $array2, $strict = false ) {
		$return = array();

		foreach ( $array1 as $key => $value ) {
			if ( array_key_exists( $key, $array2 ) ) {
				if ( is_array( $value ) ) {
					if ( is_array( $array2[ $key ] ) ) {
						$diff = self::array_diff_assoc_recursive( $value, $array2[ $key ], $strict );
						if ( $diff ) {
							$return[ $key ] = $diff;
						}
					} else {
						$return[ $key ] = $value;
					}
				} else {
					if ( $strict ) {
						if ( $value !== $array2[ $key ] ) {
							$return[ $key ] = $value;
						}
					} else {
						if ( (string) $value !== (string) $array2[ $key ] ) {
							$return[ $key ] = $value;
						}
					}
				}
			} else {
				$return[ $key ] = $value;
			}
		}

		return $return;
	}

	/**
	 * Check if an array has a key and optional compare or validate the value.
	 *
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $array
	 * @param   string  $key
	 * @param   array   $args {
	 *     Optional array of match arguments.
	 *     @type  mixed         $compare     A value to compare against (NOTE: strict comparison!).
	 *     @type  string|array  $validation  A variable function check, example: 'is_int' or 'MyClass::check'.
	 * }
	 * @return bool
	 */
	public static function array_has( $array, $key, $args = array() ) {
		$isset = ( isset( $array[ $key ] ) );
		if ( empty( $args ) || ! $isset ) {
			return $isset;
		}
		$value = $array[ $key ];
		if ( isset( $args['compare'] ) ) {
			return ( $args['compare'] === $value );
		}
		if ( ! empty( $args['validation'] ) ) {
			$validation = $args['validation'];
			// Don't accept unavailable validation methods.
			if ( ! is_callable( $validation ) ) {
				return false;
			}
			if ( is_array( $validation ) ) {
				return (bool) call_user_func( $validation, $value );
			}
			return (bool) $validation( $value );
		}
		return false;
	}

	/**
	 * Does a string starts with a given string?
	 *
	 * @since   1.4
	 * @since   1.7  Moved from VAA_View_Admin_As_Role_Defaults
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $haystack  The string to search in.
	 * @param   string  $needle    The string to search for.
	 * @return  bool
	 */
	public static function starts_with( $haystack, $needle ) {
		// search backwards starting from haystack length characters from the end.
		return '' === $needle || strrpos( $haystack, $needle, -strlen( $haystack ) ) !== false;
	}

	/**
	 * Does a string ends with a given string?
	 *
	 * @since   1.4
	 * @since   1.7  Moved from VAA_View_Admin_As_Role_Defaults
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $haystack  The string to search in.
	 * @param   string  $needle    The string to search for.
	 * @return  bool
	 */
	public static function ends_with( $haystack, $needle ) {
		// search forward starting from end minus needle length characters.
		// @codingStandardsIgnoreLine >> yeah yeah, I know...
		return '' === $needle || ( ( $temp = strlen( $haystack ) - strlen( $needle ) ) >= 0 && strpos( $haystack, $needle, $temp ) !== false);
	}

	/**
	 * Compare with the current WordPress version.
	 * Returns true when it's the provided version or newer.
	 *
	 * @since   1.6.4
	 * @since   1.7.2  Only check full version numbers by default.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @global  string      $wp_version          WordPress version.
	 * @param   int|string  $version             The WP version to check.
	 * @param   bool        $only_full_versions  Only validate full versions without dev notes (RC1, dev, etc).
	 * @return  bool
	 */
	public static function validate_wp_version( $version, $only_full_versions = true ) {
		global $wp_version;
		$version = strtolower( $version );
		$compare = strtolower( $wp_version );
		if ( $only_full_versions ) {
			// Only leave the version numbers.
			$version = explode( '-', $version );
			$version = $version[0];
			$compare = explode( '-', $compare );
			$compare = $compare[0];
		}
		return (bool) version_compare( $version, $compare, '<=' );
	}

	/**
	 * AJAX Request validator. Verifies caller and nonce.
	 * Returns the requested data.
	 *
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
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
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
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
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $nonce  The nonce to validate
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  mixed
	 */
	public static function get_request( $nonce, $key = null, $type = 'post' ) {
		// @codingStandardsIgnoreLine >> Ignore $_GET and $_POST issues.
		$data = ( 'get' === strtolower( (string) $type ) ) ? $_GET : $_POST;
		if ( isset( $data[ $key ] ) && isset( $data['_vaa_nonce'] ) && wp_verify_nonce( $data['_vaa_nonce'], $nonce ) ) {
			$request = self::get_array_data( $data, $key );
			if ( is_string( $request ) ) {
				$request = json_decode( stripcslashes( html_entity_decode( $request ) ), true );
			}
			return $request;
		}
		return null;
	}

	/**
	 * AJAX Request check.
	 *
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
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
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
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
	 * @since   1.7
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $key    The key to check.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_request( $key = null, $type = 'post' ) {
		// @codingStandardsIgnoreLine >> Ignore $_GET and $_POST issues.
		$data = ( 'get' === strtolower( (string) $type ) ) ? $_GET : $_POST;
		if ( isset( $data[ $key ] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Enhanced is_admin() function with AJAX support.
	 *
	 * @see is_admin()
	 *
	 * @since   1.7.4
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function is_admin() {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return is_admin();
		}
		// It's an ajax call, is_admin() would always return `true`. Compare the referrer url with the admin url.
		return ( false !== strpos( (string) wp_get_referer(), admin_url() ) );
	}

} // End class VAA_API.
