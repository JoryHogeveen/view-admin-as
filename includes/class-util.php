<?php
/**
 * View Admin As - Class Utility
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Utility class that holds general functions.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.8.5
 * @version 1.8.7
 */
abstract class VAA_Util
{
	/**
	 * Get full array or array key(s).
	 *
	 * @since   1.5.0
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.7.5  Option to pass an array of keys. Will always return an array (even if not found) + third require_all option.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array         $array        The requested array.
	 * @param   string|array  $key          (optional) Return only a key of the requested array.
	 * @param   bool          $require_all  (optional) In case of an array of keys, return `null` if not all keys are present?
	 * @return  mixed
	 */
	public static function get_array_data( $array, $key = null, $require_all = false ) {
		$return = $array;
		if ( null !== $key ) {
			$return = null;
			if ( ! is_array( $array ) ) {
				return $return; // Key's not available in non-arrays.
			}
			// @since  1.7.5  Search for multiple keys.
			if ( is_array( $key ) ) {
				$return = array();
				foreach ( $key as $k ) {
					if ( isset( $array[ $k ] ) ) {
						$return[ $k ] = $array[ $k ];
					}
				}
				if ( $require_all && array_diff_key( array_flip( $key ), $return ) ) {
					$return = null; // Not all keys found.
				}
			} elseif ( isset( $array[ $key ] ) ) {
				$return = $array[ $key ]; // Key found.
			}
		}
		return $return;
	}

	/**
	 * Set full array or array key.
	 *
	 * @since   1.5.0
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
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
			_doing_it_wrong(
				__METHOD__,
				'View Admin As: Key <code>' . esc_html( (string) $key ) . '</code> does not exist',
				null
			);

			// return no changes if key is not found or appending is not allowed.
			return $array;
		}
		return $var;
	}

	/**
	 * Check if two arrays are the same.
	 * Does NOT support recursive arrays!
	 *
	 * @since   1.7.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array  $array1     Array one.
	 * @param   array  $array2     Array two.
	 * @param   bool   $recursive  (optional) Compare recursively.
	 * @param   bool   $strict     (optional) Strict comparison? Only available when comparing recursive.
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
	 * @since   1.7.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $array
	 * @param   string  $key
	 * @param   array   $args {
	 *     Optional array of match arguments.
	 *     @type  mixed     $compare     A value to compare against (NOTE: strict comparison!).
	 *     @type  callable  $validation  A variable function check, example: 'is_int' or 'MyClass::check'.
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
			// Don't accept unavailable validation methods.
			if ( is_callable( $args['validation'] ) ) {
				return (bool) call_user_func( $args['validation'], $value );
			}
		}
		return false;
	}

	/**
	 * Does a string starts with a given string?
	 *
	 * @since   1.4.0
	 * @since   1.7.0  Moved from `VAA_View_Admin_As_Role_Defaults`.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $haystack  The string to search in.
	 * @param   string  $needle    The string to search for.
	 * @return  bool
	 */
	public static function starts_with( $haystack, $needle ) {
		// Search backwards starting from haystack length characters from the end.
		return '' === $needle || 0 === strpos( $haystack, $needle );
	}

	/**
	 * Does a string ends with a given string?
	 *
	 * @since   1.4.0
	 * @since   1.7.0  Moved from `VAA_View_Admin_As_Role_Defaults`.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $haystack  The string to search in.
	 * @param   string  $needle    The string to search for.
	 * @return  bool
	 */
	public static function ends_with( $haystack, $needle ) {
		// Search forward starting from end minus needle length characters.
		return '' === $needle || ( strlen( $haystack ) - strlen( $needle ) === strrpos( $haystack, $needle ) );
	}

	/**
	 * Enhancement for is_callable(), also check for class_exists() or method_exists() when an array is passed.
	 * Prevents incorrect `true` when a class has a __call() method.
	 * Can also handle error notices.
	 *
	 * @since   1.7.4
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   callable|array  $callable     The callable data.
	 * @param   bool|string     $do_notice    Add an error notice when it isn't?
	 *                                        Pass `debug` to only show notice when WP_DEBUG is enabled.
	 * @param   bool            $syntax_only  See is_callable() docs.
	 * @return  bool
	 */
	public static function exists_callable( $callable, $do_notice = false, $syntax_only = false ) {
		$pass = is_callable( $callable, $syntax_only );
		if ( $pass && is_array( $callable ) ) {
			if ( 1 === count( $callable ) ) {
				$pass = class_exists( $callable[0] );
			} else {
				$pass = method_exists( $callable[0], $callable[1] );
			}
		}
		if ( ! $pass && $do_notice ) {
			if ( 'debug' === $do_notice ) {
				$do_notice = ( defined( 'WP_DEBUG' ) && WP_DEBUG );
			}
			if ( ! is_string( $do_notice ) ) {
				$callable  = self::callable_to_string( $callable );
				$do_notice = sprintf(
					// Translators: %s stands for the requested class, method or function.
					__( '%s does not exist or is not callable.', VIEW_ADMIN_AS_DOMAIN ),
					'<code>' . $callable . '</code>'
				);
			}
			view_admin_as()->add_error_notice( $callable, $do_notice );
		}
		return (bool) $pass;
	}

	/**
	 * Convert callable variable to string for display.
	 *
	 * @since   1.7.4
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   callable|array  $callable
	 * @return  string
	 */
	public static function callable_to_string( $callable ) {
		if ( is_string( $callable ) ) {
			return $callable;
		}
		if ( is_object( $callable ) ) {
			$callable = array( $callable, '' );
		}
		if ( is_array( $callable ) ) {
			if ( is_object( $callable[0] ) ) {
				$callable[0] = get_class( $callable[0] );
				$callable    = implode( '->', $callable );
			} else {
				$callable = implode( '::', $callable );
			}
		}
		return (string) $callable;
	}

	/**
	 * AJAX request validator. Verifies caller and nonce.
	 * Returns the requested data.
	 *
	 * @since   1.7.0
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
		if ( self::doing_ajax() ) {
			return self::get_request( $nonce, $key, $type );
		}
		return null;
	}

	/**
	 * Normal request validator. Verifies caller and nonce.
	 * Returns the requested data.
	 *
	 * @since   1.7.0
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
		if ( ! self::doing_ajax() ) {
			return self::get_request( $nonce, $key, $type );
		}
		return null;
	}

	/**
	 * Request validator. Verifies caller and nonce.
	 * Returns the requested data.
	 *
	 * @since   1.7.0
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
			$request = self::maybe_json_decode( $request, true, true );
			return $request;
		}
		return null;
	}

	/**
	 * JSON request check.
	 *
	 * @since   1.8.7
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_json_request( $key = null, $type = 'post' ) {
		if ( self::doing_json() ) {
			return self::is_request( $key, $type );
		}
		return false;
	}

	/**
	 * AJAX request check.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_ajax_request( $key = null, $type = 'post' ) {
		if ( self::doing_ajax() ) {
			return self::is_request( $key, $type );
		}
		return false;
	}

	/**
	 * Normal request check.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $key    The key to fetch.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_normal_request( $key = null, $type = 'post' ) {
		if ( ! self::doing_ajax() ) {
			return self::is_request( $key, $type );
		}
		return false;
	}

	/**
	 * Check if there is a request made.
	 *
	 * @since   1.7.0
	 * @since   1.8.8  Support for any request type.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $key    The key to check.
	 * @param   string  $type   The type of request.
	 * @return  bool
	 */
	public static function is_request( $key = null, $type = 'post' ) {
		if ( ! $key && ! $type ) {
			// Any request.
			return true;
		}
		
		// @codingStandardsIgnoreLine >> Ignore $_GET and $_POST issues.
		$data = ( 'get' === strtolower( (string) $type ) ) ? $_GET : $_POST;
		if ( isset( $data[ $key ] ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the current request is for JSON/REST.
	 * Also check WP 5.0 function wp_is_json_request().
	 *
	 * @see wp_is_json_request()
	 *
	 * @since   1.8.8
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function doing_json() {
		if ( function_exists( 'wp_is_json_request' ) ) {
			return wp_is_json_request();
		}
		// Fallback to referer.
		return ( false !== strpos( (string) wp_get_referer(), '/wp-json/' ) );
	}

	/**
	 * Check if the current request is AJAX.
	 * Also check WP 4.7 function wp_doing_ajax().
	 *
	 * @see wp_doing_ajax()
	 *
	 * @since   1.8.5
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function doing_ajax() {
		// @todo VAA_DOING_AJAX handler
		if ( function_exists( 'wp_doing_ajax' ) ) {
			return wp_doing_ajax();
		}
		return ! defined( 'DOING_AJAX' ) || ! DOING_AJAX;
	}

	/**
	 * Check if the value contains JSON.
	 * It the value is an array it will be parsed recursively.
	 *
	 * @link https://stackoverflow.com/questions/6041741/fastest-way-to-check-if-a-string-is-json-in-php
	 *
	 * @since   1.7.5
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   mixed  $value   The value to be checked for JSON data.
	 * @param   bool   $assoc   See json_decode().
	 * @param   bool   $decode  Decode with html_entity_decode() and stripcslashes()?
	 * @return  mixed
	 */
	public static function maybe_json_decode( $value, $assoc = true, $decode = false ) {
		if ( ! is_string( $value ) ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $key => $val ) {
					$value[ $key ] = self::maybe_json_decode( $val, $assoc, $decode );
				}
			}
			return $value;
		}
		if ( 0 !== strpos( $value, '[' ) && 0 !== strpos( $value, '{' ) ) {
			return $value;
		}
		if ( $decode ) {
			$value = stripcslashes( html_entity_decode( $value ) );
		}
		$var = json_decode( $value, $assoc );
		if ( null !== $var ) {
			return $var;
		}
		return $value;
	}

	/**
	 * Check if debug is enabled.
	 *
	 * @since   1.8.7
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return bool
	 */
	public static function debug() {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

} // End class VAA_Util.
