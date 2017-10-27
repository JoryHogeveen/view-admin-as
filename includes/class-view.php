<?php
/**
 * View Admin As - Class View
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * View handler class.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6
 * @since   1.7  Class got split up: data handling/updating is now in VAA_View_Admin_As_Controller.
 * @version 1.7.4
 * @uses    VAA_View_Admin_As_Base Extends class
 */
final class VAA_View_Admin_As_View extends VAA_View_Admin_As_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6
	 * @static
	 * @var    VAA_View_Admin_As_View
	 */
	private static $_instance = null;

	/**
	 * Is the current user modified?
	 *
	 * @since  1.7.2
	 * @var    bool
	 */
	private $is_user_modified = false;

	/**
	 * VAA_View_Admin_As_View constructor.
	 *
	 * @since   1.6
	 * @since   1.6.1  $vaa param.
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );
	}

	/**
	 * Initializes after VAA is enabled.
	 *
	 * @since   1.6
	 * @access  public
	 * @return  void
	 */
	public function init() {
		if ( $this->store->get_view() ) {
			$this->do_view();
		}
	}

	/**
	 * Apply view data.
	 *
	 * @since   1.6.3    Put logic in it's own function.
	 * @access  private
	 * @return  void
	 */
	private function do_view() {

		// @since  1.6.4  Set the current user as the selected user by default.
		$this->store->set_selectedUser( $this->store->get_curUser() );

		/**
		 * USER & VISITOR.
		 * Current user object views (switches current user).
		 *
		 * @since  0.1    User view.
		 * @since  1.6.2  Visitor view.
		 */
		if ( $this->store->get_view( 'user' ) || $this->store->get_view( 'visitor' ) ) {

			/**
			 * Change current user object so changes can be made on various screen settings.
			 * wp_set_current_user() returns the new user object.
			 *
			 * If it is a visitor view it will convert the false return from 'user' to 0.
			 */
			$this->store->set_selectedUser( wp_set_current_user( (int) $this->store->get_view( 'user' ) ) );

			// @since  1.6.2  Set the caps for this view (user view).
			if ( isset( $this->store->get_selectedUser()->allcaps ) ) {
				$this->store->set_selectedCaps( $this->store->get_selectedUser()->allcaps );
			}
		}

		/**
		 * ROLES & CAPS.
		 * Capability based views (modifies current user).
		 *
		 * @since  0.1  Role view
		 * @since  1.3  Caps view
		 */
		if ( $this->store->get_view( 'role' ) || $this->store->get_view( 'caps' ) ) {
			$this->init_user_modifications();
		}

		/**
		 * View data is set, apply the view.
		 * This hook can be used by other modules to enable a view.
		 *
		 * Temporary modifications to the current user are set on priority 99.
		 * This functionality has a separate action: `vaa_view_admin_as_modify_current_user`.
		 *
		 * @since  1.6.3
		 * @param  array
		 */
		do_action( 'vaa_view_admin_as_do_view', $this->store->get_view() );

		/**
		 * Force own locale on view.
		 * @since  1.6.1
		 */
		if ( $this->store->get_userSettings( 'freeze_locale' )
			&& (int) $this->store->get_curUser()->ID !== (int) $this->store->get_selectedUser()->ID
		) {
			add_action( 'init', array( $this, 'freeze_locale' ), 0 );
		}
	}

	/**
	 * Adds the actions and filters to modify the current user object.
	 * Can only be run once.
	 *
	 * @since   1.6.3
	 * @access  public
	 * @return  void
	 */
	public function init_user_modifications() {
		static $done;
		if ( $done ) return;

		$this->is_user_modified = true;

		add_action( 'vaa_view_admin_as_do_view', array( $this, 'modify_user' ), 99 );

		/**
		 * Make sure the $current_user view data isn't overwritten again by switch_blog functions.
		 * @see    This filter is documented in wp-includes/ms-blogs.php
		 * @since  1.6.3
		 */
		add_action( 'switch_blog', array( $this, 'modify_user' ) );

		/**
		 * Prevent some meta updates for the current user while in modification to the current user are active.
		 * @since  1.6.3
		 */
		add_filter( 'update_user_metadata' , array( $this, 'filter_prevent_update_user_metadata' ), 999999999, 3 );

		/**
		 * Get capabilities and user level from current user view object instead of database.
		 * @since  1.6.4
		 */
		add_filter( 'get_user_metadata' , array( $this, 'filter_overrule_get_user_metadata' ), 999999999, 3 );

		// `user_has_cap` priority.
		$priority = -999999999;
		if ( $this->store->get_view( 'caps' ) ) {
			// Overwrite everything when the capability view is active.
			remove_all_filters( 'user_has_cap' );
			$priority = 999999999;
		}
		/**
		 * The priority value of the VAA `user_has_cap` filter.
		 * Runs as first by default.
		 *
		 * @since   1.7.2
		 * @param   int  $priority
		 * @return  int
		 */
		$priority = (int) apply_filters( 'view_admin_as_user_has_cap_priority', $priority );

		/**
		 * Change the capabilities.
		 *
		 * @since  1.7.1
		 * @since  1.7.2  Changed priority to set is at the beginning instead of as last
		 *                to allow other plugins to filter based on the modified user.
		 */
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), $priority, 4 );

		/**
		 * Map the capabilities (map_meta_cap is used for compatibility with network admins).
		 * Filter as last to check other plugin changes as well.
		 *
		 * @since  0.1
		 */
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 999999999, 4 );

		/**
		 * Disable super admin status for the current user.
		 * @since  1.7.3
		 */
		if ( ! is_network_admin() &&
		     VAA_API::is_super_admin( $this->store->get_selectedUser()->ID ) &&
		     $this->store->get_userSettings( 'disable_super_admin' )
		) {
			$this->disable_super_admin();
		}

		$done = true;
	}

	/**
	 * Update the current user's WP_User instance with the current view capabilities.
	 *
	 * @since   1.6.3
	 * @access  public
	 * @return  void
	 */
	public function modify_user() {

		// Can be the current or selected WP_User object (depending on the user view).
		$user = $this->store->get_selectedUser();

		/**
		 * Validate if the WP_User properties are still accessible.
		 * Currently everything is public but this could possibly change.
		 *
		 * @since  1.6.3
		 */
		$accessible = false;
		$public_props = get_object_vars( $user );
		if ( array_key_exists( 'caps', $public_props ) &&
		     array_key_exists( 'allcaps', $public_props ) &&
			 is_callable( array( $user, 'get_role_caps' ) )
		) {
			$accessible = true;
		}

		/**
		 * Role view.
		 *
		 * @since  0.1
		 */
		if ( $this->store->get_roles( $this->store->get_view( 'role' ) ) instanceof WP_Role ) {
			if ( ! $accessible ) {
				// @since  1.6.2  Set the caps for this view here instead of in the mapper function.
				$this->store->set_selectedCaps(
					$this->store->get_roles( $this->store->get_view( 'role' ) )->capabilities
				);
			} else {
				// @since  1.6.3  Set the current user's role to the current view.
				$user->caps = array( $this->store->get_view( 'role' ) => 1 );
				// Sets the `allcaps` and `roles` properties correct.
				$user->get_role_caps();
			}
		}

		/**
		 * Caps view.
		 *
		 * @since  1.3
		 */
		if ( is_array( $this->store->get_view( 'caps' ) ) ) {
			if ( ! $accessible ) {
				$this->store->set_selectedCaps( $this->store->get_view( 'caps' ) );
			} else {
				// @since  1.6.3  Set the current user's caps (roles) to the current view.
				$user->allcaps = array_merge(
					(array) array_filter( $this->store->get_view( 'caps' ) ),
					(array) $user->caps // Contains the current user roles.
				);
			}
		}

		if ( $accessible ) {
			$this->store->set_selectedCaps( $user->allcaps );
		}

		/**
		 * Allow other modules to hook after the initial changes to the current user.
		 *
		 * @since  1.6.3
		 * @since  1.6.4     Changed name (was: `vaa_view_admin_as_modify_current_user`).
		 * @param  \WP_User  $user        The modified user object.
		 * @param  bool      $accessible  Are the needed WP_User properties and methods accessible?
		 */
		do_action( 'vaa_view_admin_as_modify_user', $user, $accessible );
	}

	/**
	 * Prevent some updates to the current user like roles and capabilities.
	 * to prevent problems when making changes within a view.
	 *
	 * IMPORTANT! This filter should ONLY be used when a view is selected!
	 *
	 * @since   1.6.3
	 * @access  public
	 * @see     init_current_user_modifications()
	 *
	 * @see     'update_user_metadata' filter
	 * @link    https://codex.wordpress.org/Plugin_API/Filter_Reference/update_(meta_type)_metadata
	 * @link    http://hookr.io/filters/update_user_metadata/
	 *
	 * @global  wpdb    $wpdb
	 * @param   null    $null       Whether to allow updating metadata for the given type.
	 * @param   int     $object_id  Object ID.
	 * @param   string  $meta_key   Meta key.
	 * @return  mixed
	 */
	public function filter_prevent_update_user_metadata( $null, $object_id, $meta_key ) {
		global $wpdb;
		$user = $this->store->get_selectedUser();

		// Check if the object being updated is the current user.
		if ( (int) $user->ID === (int) $object_id ) {

			// Capabilities meta key check.
			if ( empty( $user->cap_key ) ) {
				$user->cap_key = $wpdb->get_blog_prefix() . 'capabilities';
			}

			// Do not update the current user capabilities or user level while in a view.
			if ( in_array( $meta_key, array(
				$user->cap_key,
				$wpdb->get_blog_prefix() . 'capabilities',
				$wpdb->get_blog_prefix() . 'user_level',
			), true ) ) {
				return false;
			}
		}
		return $null;
	}

	/**
	 * Return view roles when getting the current user data to prevent reloading current user data within a view.
	 *
	 * IMPORTANT! This filter should ONLY be used when a view is selected!
	 *
	 * @since   1.6.4
	 * @access  public
	 * @see     init_current_user_modifications()
	 *
	 * @see     'get_user_metadata' filter
	 * @link    https://codex.wordpress.org/Plugin_API/Filter_Reference/get_(meta_type)_metadata
	 *
	 * @global  wpdb    $wpdb
	 * @param   null    $null       The value update_metadata() should return.
	 * @param   int     $object_id  Object ID.
	 * @param   string  $meta_key   Meta key.
	 * @return  mixed
	 */
	public function filter_overrule_get_user_metadata( $null, $object_id, $meta_key ) {
		global $wpdb;
		$user = $this->store->get_selectedUser();

		// Check if the object being updated is the current user.
		if ( (int) $user->ID === (int) $object_id ) {

			// Return the current user capabilities or user level while in a view.
			// Always return an array to fix $single usage.

			// Current user cap key should be equal to the meta_key for capabilities.
			if ( ! empty( $user->cap_key ) && $meta_key === $user->cap_key ) {
				return array( $user->caps );
			}
			// Fallback if cap_key doesn't exists.
			if ( $meta_key === $wpdb->get_blog_prefix() . 'capabilities' ) {
				return array( $user->caps );
			}
			if ( $meta_key === $wpdb->get_blog_prefix() . 'user_level' ) {
				if ( ! isset( $user->user_level ) ) {
					// Make sure the key exists. Result will be filtered in `filter_prevent_update_user_metadata()`.
					$user->update_user_level_from_caps();
				}
				return array( $user->user_level );
			}
		}
		return $null;
	}

	/**
	 * Change capabilities when the user has selected a view.
	 * If the capability isn't in the chosen view, then make the value for this capability empty and add "do_not_allow".
	 *
	 * @since   0.1
	 * @since   1.5     Changed function name to map_meta_cap (was change_caps).
	 * @since   1.6     Moved to this class from main class.
	 * @since   1.6.2   Use logic from current_view_can().
	 * @since   1.6.3   Prefix function name with `filter_`.
	 * @since   1.7.2   Use the `user_has_cap` filter for compatibility enhancements.
	 * @access  public
	 *
	 * @param   array   $caps     The actual (mapped) cap names, if the caps are not mapped this returns the requested cap.
	 * @param   string  $cap      The capability that was requested.
	 * @param   int     $user_id  The ID of the user.
	 * @param   array   $args     Adds the context to the cap. Typically the object ID (not used).
	 * @return  array   $caps
	 */
	public function filter_map_meta_cap( $caps, $cap, $user_id, $args = array() ) {

		if ( (int) $this->store->get_selectedUser()->ID !== (int) $user_id ) {
			return $caps;
		}

		$filter_caps = (array) $this->store->get_selectedCaps();

		if ( ! $this->store->get_view( 'caps' ) ) {
			/**
			 * Apply user_has_cap filters to make sure we are compatible with modifications from other plugins.
			 *
			 * Issues found:
			 * - Restrict User Access - Overwrites our filtered capabilities. (fixed since RUA 0.15.x).
			 * - Groups - Overwrites our filtered capabilities. (fixed in Groups module).
			 *
			 * @since  1.7.2
			 * @see    \WP_User::has_cap()
			 */
			$filter_caps = apply_filters(
				'user_has_cap',
				$filter_caps,
				$caps,
				// Replicate arguments for `user_has_cap`.
				array_merge( array( $cap, $user_id ), (array) $args ),
				$this->store->get_selectedUser()
			);
		}

		foreach ( (array) $caps as $actual_cap ) {
			if ( ! $this->current_view_can( $actual_cap, $filter_caps ) ) {
				// Regular users. Assuming this capability never exists..
				$caps['vaa_do_not_allow'] = 'vaa_do_not_allow';
				// Network admins.
				$caps['do_not_allow'] = 'do_not_allow';
			}
		}

		return $caps;
	}

	/**
	 * Overwrite the user's capabilities.
	 *
	 * @since   1.6.3
	 * @access  public
	 *
	 * @param   array     $allcaps  All the capabilities of the user.
	 * @param   array     $caps     Actual capabilities for meta capability.
	 * @param   array     $args     [0] Requested capability.
	 *                              [1] User ID.
	 *                              [2] Associated object ID.
	 * @param   \WP_User  $user     (WP 3.7+) The user object.
	 * @return  array
	 */
	public function filter_user_has_cap( $allcaps, $caps, $args, $user = null ) {
		$user_id = ( $user ) ? $user->ID : $args[1];
		if ( is_numeric( $user_id ) && (int) $user_id === (int) $this->store->get_selectedUser()->ID ) {
			return (array) $this->store->get_selectedCaps();
		}
		return $allcaps;
	}

	/**
	 * Remove the current user from the list of super admins.
	 * This sets/changes the global $super_admins variable which overwrites the site option.
	 *
	 * @since   1.7.3
	 * @access  public
	 * @see     grant_super_admin()  >> wp-includes/capabilities.php
	 * @see     revoke_super_admin() >> wp-includes/capabilities.php
	 * @see     get_super_admins()   >> wp-includes/capabilities.php
	 * @see     is_super_admin()     >> wp-includes/capabilities.php
	 * @link    https://developer.wordpress.org/reference/functions/is_super_admin/
	 *
	 * @global  array  $super_admins
	 * @param   \WP_User|int|string  $user   (optional) A user to remove. Both a user object or a user field is accepted.
	 * @param   string               $field  (optional) A user field key to get the user data by.
	 */
	public function disable_super_admin( $user = null, $field = 'id' ) {
		global $super_admins;

		if ( ! isset( $super_admins ) ) {
			$super_admins = get_super_admins();
		}

		$user = ( null !== $user ) ? $user : $this->store->get_selectedUser();
		if ( ! $user instanceof WP_User ) {
			$user = get_user_by( $field, $user );
		}

		// Remove current user from the super admins array.
		// Effectively disables functions grant_super_admin() and revoke_super_admin().
		if ( ! empty( $user->user_login ) && is_array( $super_admins ) ) {
			$key = array_search( $user->user_login, $super_admins, true );
			if ( false !== $key ) {
				unset( $super_admins[ $key ] );
				$GLOBALS['super_admins'] = $super_admins;
			}
		}
	}

	/**
	 * Similar function to current_user_can().
	 *
	 * @since   1.6.2
	 * @access  public
	 *
	 * @param   string  $cap   The capability.
	 * @param   array   $caps  (optional) Capabilities to compare to.
	 *                         Defaults to the selected caps for the current view.
	 * @return  bool
	 */
	public function current_view_can( $cap, $caps = array() ) {

		if ( empty( $caps ) ) {
			$caps = $this->store->get_selectedCaps();
		}

		if ( is_array( $caps ) &&
		     array_key_exists( $cap, $caps ) &&
		     1 === (int) $caps[ $cap ] &&
		     'do_not_allow' !== $cap &&
		     'do_not_allow' !== $caps[ $cap ]
		) {
			return true;
		}
		return false;
	}

	/**
	 * Is the current user modified?
	 *
	 * @since   1.7.2
	 * @access  public
	 * @return  bool
	 */
	public function is_user_modified() {
		return (bool) $this->is_user_modified;
	}

	/**
	 * Set the locale for the current view.
	 *
	 * @since   1.6.1
	 * @access  public
	 * @return  bool  Will return false when used with older WP versions.
	 */
	public function freeze_locale() {
		if ( function_exists( 'get_user_locale' ) && function_exists( 'switch_to_locale' ) ) {
			$locale = get_user_locale( $this->store->get_curUser()->ID );
			if ( get_locale() !== $locale ) {
				switch_to_locale( $locale );
			}
			return true;
		}
		return false;
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  $this  VAA_View_Admin_As_View
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_View.
