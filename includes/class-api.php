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
 * API class that is also extends utility functions.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.0
 * @version 1.8.6
 * @uses    \VAA_Util Extends class
 */
final class VAA_API extends VAA_Util
{
	/**
	 * Check if a user has full access to this plugin.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   \WP_User|int  $user  The user to check.
	 * @return  bool
	 */
	public static function user_has_full_access( $user ) {
		if ( ! $user instanceof WP_User ) {
			$user = get_user_by( 'ID', $user );
			if ( ! $user ) {
				return false;
			}
		}

		if ( is_multisite() ) {
			return is_super_admin( $user->ID );
		}

		/**
		 * For single installations is_super_admin() isn't enough since it only checks for `delete_users`.
		 * @since  1.7.6
		 * @link   https://wordpress.org/support/topic/required-capabilities-2/
		 */
		$caps = array(
			'edit_users',
			'delete_plugins',
		);

		/**
		 * Filter the capabilities required to gain full access to this plugin.
		 * Note: Single site only!
		 * Note: is_super_admin() is always checked!
		 *
		 * @since  1.8.0
		 * @param  array     $caps  The default capabilities.
		 * @param  \WP_User  $user  The user that is being validated.
		 * @return array
		 */
		$caps = apply_filters( 'view_admin_as_full_access_capabilities', $caps, $user );

		foreach ( $caps as $cap ) {
			if ( ! $user->has_cap( $cap ) ) {
				return false;
			}
		}

		return is_super_admin( $user->ID );
	}

	/**
	 * Check if the user is a super admin.
	 * This check is more strict for single installations since it checks VAA_API::user_has_full_access.
	 * It will validate the original user while in a view and no parameter is passed.
	 *
	 * @see  \VAA_API::user_has_full_access()
	 * @see  \VAA_View_Admin_As_Store::cur_user_has_full_access()
	 *
	 * @since   1.6.3
	 * @since   1.8.0  Check full access.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   int|\WP_User  $user  (optional) Default: current user.
	 * @return  bool
	 */
	public static function is_super_admin( $user = null ) {
		if ( null === $user || view_admin_as()->store()->is_curUser( $user ) ) {
			return view_admin_as()->store()->cur_user_has_full_access();
		}

		return self::user_has_full_access( $user );
	}

	/**
	 * Check if the user is a superior admin.
	 * It will validate the original user while in a view and no parameter is passed.
	 *
	 * @since   1.5.3
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @since   1.6.3  Improve is_super_admin() check
	 * @since   1.8.0  Enhance code to reflect VAA_API::is_super_admin() changes.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   int|\WP_User  $user_id  (optional) Default: current user.
	 * @return  bool
	 */
	public static function is_superior_admin( $user_id = null ) {

		// If it's the current user or null, don't pass the user ID to make sure we check the original user status.
		$is_super_admin = self::is_super_admin(
			( null !== $user_id && view_admin_as()->store()->is_curUser( $user_id ) ) ? null : $user_id
		);

		// Full access is required.
		if ( ! $is_super_admin ) {
			return false;
		}

		if ( null === $user_id ) {
			$user_id = view_admin_as()->store()->get_originalUserData( 'ID' );
		} elseif ( $user_id instanceof WP_User ) {
			$user_id = $user_id->ID;
		}

		// Is it one of the manually configured superior admins?
		return (bool) ( in_array( (int) $user_id, self::get_superior_admins(), true ) );
	}

	/**
	 * Get the superior admin ID's (filter since 1.5.2).
	 *
	 * @since   1.5.3
	 * @since   1.6.0  Moved from `VAA_View_Admin_As`.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return int[]
	 */
	public static function get_superior_admins() {
		static $superior_admins;
		if ( ! is_null( $superior_admins ) ) return $superior_admins;

		/**
		 * Grant admins the capability to view other admins. There is no UI for this!
		 *
		 * @since  1.5.2
		 * @param  array
		 * @return int[] Requires a returned array of user ID's
		 */
		$superior_admins = (array) apply_filters( 'view_admin_as_superior_admins', array() );

		// Only allow unique  numeric values (user id's).
		$superior_admins = array_unique( array_map( 'absint', array_filter( $superior_admins, 'is_numeric' ) ) );

		return $superior_admins;
	}

	/**
	 * Get the current active view. Returns `null` if no view (type) is active.
	 *
	 * @see  \VAA_View_Admin_As_Store::get_view()
	 *
	 * @since   1.8.3
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $type  (optional) A view type. Will return `null` if this view type is not active.
	 * @return  mixed
	 */
	public static function get_current_view( $type = null ) {
		$store = view_admin_as()->store();
		if ( $store ) {
			return $store->get_view( $type );
		}
		return null;
	}

	/**
	 * Is the current user in an active view.
	 *
	 * @since   1.8.4
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param  string  $type  (optional) Check for a single view type.
	 * @return bool
	 */
	public static function is_view_active( $type = null ) {
		return (bool) self::get_current_view( $type );
	}

	/**
	 * Check if the provided data is the same as the current view.
	 *
	 * @see  \VAA_View_Admin_As_Controller::is_current_view()
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
	 * Is the current user modified?
	 * Returns true if the currently active user's capabilities or roles are changed by the selected view.
	 *
	 * @see  \VAA_View_Admin_As_View::current_view_can()
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
	 * Similar function to current_user_can() but applies to the currently active view.
	 *
	 * @see  \VAA_View_Admin_As_View::current_view_can()
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
	 * Set the current view.
	 *
	 * @see  \VAA_View_Admin_As_Controller::update()
	 * @see  \VAA_View_Admin_As_Controller::update_view()
	 *
	 * @since   1.8.3
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array  $view  The view.
	 * @return  bool
	 */
	public static function update_view( $view ) {
		$controller = view_admin_as()->controller();
		if ( $controller ) {
			$view    = array_intersect_key( $view, array_flip( $controller->get_view_types() ) );
			$success = $controller->update( $view );
			return ( true === $success );
		}
		return false;
	}

	/**
	 * Check if a view type is enabled. Pass an array to check multiple view types.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string|array  $type  The view type key.
	 * @return  bool
	 */
	public static function is_view_type_enabled( $type ) {
		$type = view_admin_as()->get_view_types( $type );
		if ( is_array( $type ) ) {
			foreach ( $type as $view_type ) {
				if ( ! $view_type instanceof VAA_View_Admin_As_Type || ! $view_type->is_enabled() ) {
					return false;
				}
			}
			return true;
		}
		if ( $type instanceof VAA_View_Admin_As_Type ) {
			return $type->is_enabled();
		}
		return false;
	}

	/**
	 * Generate a VAA action link.
	 *
	 * @since   1.7.0
	 * @since   1.8.2  Switched $nonce and $url parameters order.
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   array   $data   View type data.
	 * @param   string  $url    (optional) A URL. Of not passed it will generate a link from the current URL.
	 * @param   string  $nonce  (optional) Use a different nonce. Pass `false` to omit nonce.
	 * @return  string
	 */
	public static function get_vaa_action_link( $data, $url = null, $nonce = null ) {

		$params = array(
			'action'        => 'view_admin_as',
			'view_admin_as' => $data, // wp_json_encode( array( $type, $data ) ),
		);

		if ( null === $nonce ) {
			$nonce = view_admin_as()->store()->get_nonce( true );
		}
		if ( $nonce ) {
			$params['_vaa_nonce'] = (string) $nonce;
		}

		// @todo fix WP referrer/nonce checks and allow switching on any page without ajax.
		// @see https://codex.wordpress.org/Function_Reference/check_admin_referer
		if ( null === $url ) {
			if ( is_admin() ) {
				$url = is_network_admin() ? network_admin_url() : admin_url();
			} else {
				// Since  1.7.5  Frontend url.
				$url = get_site_url();
			}
		}

		$url = add_query_arg( $params, ( $url ) ? $url : false );

		return esc_url( $url, array( 'http', 'https' ) );
	}

	/**
	 * Appends the "reset-view" parameter to the current URL.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $url  (optional) Supply the URL to create the reset link.
	 * @param   bool    $all  (optional) Reset all views link?
	 * @return  string
	 */
	public static function get_reset_link( $url = '', $all = false ) {
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
	 * @since   1.6.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @param   string  $url  (optional) Supply the URL to remove the reset link.
	 * @return  string
	 */
	public static function remove_reset_link( $url = '' ) {
		$url = remove_query_arg( array( 'reset-view', 'reset-all-views' ), ( $url ) ? $url : false );
		return esc_url( $url, array( 'http', 'https' ) );
	}

	/**
	 * Is any toolbar showing?
	 * Do not use this before the `init` hook.
	 *
	 * @since   1.7.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function is_toolbar_showing() {
		return (bool) ( is_admin_bar_showing() || self::is_vaa_toolbar_showing() );
	}

	/**
	 * Is our custom toolbar showing?
	 * Do not use this before the `init` hook.
	 *
	 * @since   1.6.0
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function is_vaa_toolbar_showing() {

		if ( class_exists( 'VAA_View_Admin_As_Toolbar' ) ) {
			return (bool) VAA_View_Admin_As_Toolbar::$showing;
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
		if ( ! VAA_Util::doing_ajax() ) {
			return is_admin();
		}
		// It's an ajax call, is_admin() would always return `true`. Compare the referrer url with the admin url.
		return ( false !== strpos( (string) wp_get_referer(), admin_url() ) );
	}

	/**
	 * Is the customizer admin container currently rendering?
	 *
	 * @since   1.7.6
	 * @access  public
	 * @static
	 * @api
	 *
	 * @return  bool
	 */
	public static function is_customizer_admin() {
		return (bool) ( is_customize_preview() && is_admin() );
	}

	/**
	 * Backwards compat method for apply_shortcodes() since WP 5.4.
	 * @todo  deprecate when 5.4 is the minimum version of WP.
	 *
	 * @since  1.8.6
	 * @param  string  $content
	 * @param  bool    $ignore_html
	 *
	 * @return string
	 */
	public static function apply_shortcodes( $content, $ignore_html = false ) {
		if ( function_exists( 'apply_shortcodes' ) ) {
			return apply_shortcodes( $content, $ignore_html );
		}
		return do_shortcode( $content, $ignore_html );
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

} // End class VAA_API.
