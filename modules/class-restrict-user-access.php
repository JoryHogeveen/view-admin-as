<?php
/**
 * View Admin As - Restrict User Access plugin
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Compatibility class for the Restrict User Access plugin.
 *
 * Tested from RUA version: 0.12.4
 * Official RUA compat release: 0.13 (https://github.com/intoxstudio/restrict-user-access/pull/8)
 * Checked version: 0.15
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.4
 * @version 1.7.2
 * @uses    VAA_View_Admin_As_Class_Base Extends class
 */
final class VAA_View_Admin_As_RUA extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6.4
	 * @static
	 * @var    VAA_View_Admin_As_RUA
	 */
	private static $_instance = null;

	/**
	 * The existing access levels.
	 *
	 * @since  1.6.4
	 * @see    restrict-user-access/app.php -> get_levels()
	 * @var    array of WP_Post objects (RUA access level post type)
	 */
	private $levels;

	/**
	 * @since  1.6.4
	 * @var    int  WP_Post ID (RUA access level post type).
	 */
	private $selectedLevel;

	/**
	 * @since  1.6.4
	 * @var    array  The caps set for this level.
	 */
	private $selectedLevelCaps = array();

	/**
	 * @since  1.6.4
	 * @var    object  The post type object of the level types.
	 */
	private $levelPostType;

	/**
	 * @since  1.6.4
	 * @var    string
	 */
	private $viewKey = 'rua_level';

	/**
	 * @since  1.6.4
	 * @var    RUA_App
	 */
	private $ruaApp;

	/**
	 * @since  1.7.2
	 * @var    RUA_Level_Manager
	 */
	private $ruaLevelManager;

	/**
	 * @since  1.6.4
	 * @var    string
	 */
	private $ruaMetaPrefix;

	/**
	 * @since  1.6.4
	 * @var    string
	 */
	private $ruaTypeRestrict;

	/**
	 * Populate the instance and validate RUA plugin is active.
	 *
	 * @since   1.6.4
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( ! $this->vaa->is_enabled() ) {
			return;
		}

		if ( ! is_callable( array( 'RUA_App', 'instance' ) ) ) {
			return;
		}

		$this->ruaApp = RUA_App::instance();
		if ( isset( $this->ruaApp->level_manager ) ) {
			$this->ruaLevelManager = $this->ruaApp->level_manager;
		}

		$access_cap            = ( defined( RUA_App::CAPABILITY ) ) ? RUA_App::CAPABILITY : 'edit_users';
		$this->ruaMetaPrefix   = ( defined( RUA_App::META_PREFIX ) ) ? RUA_App::META_PREFIX : '_ca_';
		$this->ruaTypeRestrict = ( defined( RUA_App::TYPE_RESTRICT ) ) ? RUA_App::TYPE_RESTRICT : 'restriction';

		if ( current_user_can( $access_cap ) && ! is_network_admin() ) {

			$this->vaa->register_module( array(
				'id'       => $this->viewKey,
				'instance' => self::$_instance,
			) );

			$this->store_levels();

			add_filter( 'view_admin_as_view_types', array( $this, 'add_view_type' ) );

			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), 40, 2 );
			add_action( 'vaa_admin_bar_roles_after', array( $this, 'admin_bar_roles_after' ), 10, 2 );

			add_filter( 'view_admin_as_validate_view_data_' . $this->viewKey, array( $this, 'validate_view_data' ), 10, 2 );
			add_filter( 'view_admin_as_update_view_' . $this->viewKey, array( $this, 'update_view' ), 10, 3 );
		}

		add_action( 'vaa_view_admin_as_do_view', array( $this, 'do_view' ) );
	}

	/**
	 * Initialize the RUA module.
	 *
	 * @since   1.6.4
	 * @access  public
	 */
	public function do_view() {

		if ( $this->get_levels( $this->store->get_view( $this->viewKey ) ) ) {

			$this->selectedLevel     = $this->store->get_view( $this->viewKey );
			$this->selectedLevelCaps = $this->get_level_caps( $this->selectedLevel, true );

			add_filter( 'vaa_admin_bar_viewing_as_title', array( $this, 'vaa_viewing_as_title' ) );

			$this->vaa->view()->init_user_modifications();
			add_action( 'vaa_view_admin_as_modify_user', array( $this, 'modify_user' ), 10, 2 );

			add_filter( 'get_user_metadata', array( $this, 'get_user_metadata' ), 10, 3 );

			// Administrators can see all restricted content in RUA.
			if ( $this->store->get_view() && ! $this->store->get_selectedCaps( 'administrator' ) ) {
				// Not a view with administrator capability == no global access.
				add_filter( 'rua/user/global-access', '__return_false' );
			}
		}

		if ( VAA_API::is_user_modified() && isset( $this->ruaLevelManager ) ) {

			if ( is_callable( array( $this->ruaLevelManager, 'reset_user_levels_caps' ) ) ) {
				/**
				 * Reset the user levels caps.
				 * @since  1.7.2
				 * @link   https://github.com/JoryHogeveen/view-admin-as/issues/56#issuecomment-299077527
				 * @link   https://github.com/intoxstudio/restrict-user-access/pull/11
				 * @see    RUA_Level_Manager::add_filters()
				 */
				$this->ruaLevelManager->reset_user_levels_caps( $this->store->get_selectedUser()->ID );
			}

			if ( $this->store->get_view( 'caps' ) ) {
				/**
				 * Remove the whole filter when the caps view is selected.
				 * @since  1.7.2
				 * @link   https://github.com/JoryHogeveen/view-admin-as/issues/56#issuecomment-299077527
				 * @see    RUA_Level_Manager::add_filters()
				 */
				remove_filter( 'user_has_cap', array( $this->ruaLevelManager, 'user_level_has_cap' ), 9 );
			}
		}
	}

	/**
	 * Update the current user's WP_User instance with the current view data.
	 *
	 * @since   1.6.4
	 * @param   WP_User  $user        User object.
	 * @param   bool     $accessible  Are the WP_User properties accessible?
	 */
	public function modify_user( $user, $accessible ) {

		$caps = (array) $this->selectedLevelCaps;

		if ( $this->store->get_view( 'role' ) || ! $accessible ) {
			// Merge the caps with the current selected caps, overwrite existing.
			// Also do the same when WP_User parameters aren't accessible.
			$caps = array_merge( $this->store->get_selectedCaps(), $caps );
		} else {
			$caps = array_merge( $user->allcaps, $caps );
		}

		$this->store->set_selectedCaps( $caps );

		if ( $accessible ) {
			// Merge the caps with the current user caps, overwrite existing.
			$user->allcaps = array_merge( $user->caps, $caps );
		}
	}

	/**
	 * Filter the return metadata for the RUA levels.
	 *
	 * @since   1.6.4
	 * @param   null    $null      The value get_metadata() should return.
	 * @param   int     $user_id   User/Object ID.
	 * @param   string  $meta_key  Meta key.
	 * @return  array
	 */
	public function get_user_metadata( $null, $user_id, $meta_key ) {
		if ( (int) $user_id === (int) $this->store->get_selectedUser()->ID
		     && $this->get_levels( $this->selectedLevel )
		) {
			// @todo Check for future API updates in RUA plugin
			if ( $this->ruaMetaPrefix . 'level' === $meta_key ) {
				return array( $this->selectedLevel );
			}
			if ( $this->ruaMetaPrefix . 'level_' . $this->selectedLevel === $meta_key ) {
				// Return current time + 120 seconds to make sure this level won't be set as expired.
				return array( time() + 120 );
			}
		}
		return $null;
	}

	/**
	 * Add view type.
	 *
	 * @since   1.6.4
	 * @param   array  $types  Existing view types.
	 * @return  array
	 */
	public function add_view_type( $types ) {
		$types[] = $this->viewKey;
		return $types;
	}

	/**
	 * Validate data for this view type
	 *
	 * @since   1.7
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	public function validate_view_data( $null, $data ) {
		if ( is_numeric( $data ) && $this->get_levels( (int) $data ) ) {
			return $data;
		}
		return $null;
	}

	/**
	 * View update handler (Ajax probably), called from main handler.
	 *
	 * @since   1.6.4
	 * @since   1.7    Renamed from `ajax_handler`
	 * @access  public
	 * @param   null    $null    Null.
	 * @param   array   $data    The ajax data for this module.
	 * @param   string  $type    The view type.
	 * @return  bool
	 */
	public function update_view( $null, $data, $type ) {

		if ( ! $this->is_valid_ajax() || $type !== $this->viewKey ) {
			return $null;
		}

		if ( is_numeric( $data ) && $this->get_levels( (int) $data ) ) {
			$this->store->set_view( (int) $data, $this->viewKey, true );
			return true;
		}
		return false;
	}

	/**
	 * Change the VAA admin bar menu title.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @param   string  $title  The current title.
	 * @return  string
	 */
	public function vaa_viewing_as_title( $title ) {

		if ( $this->get_levels( $this->selectedLevel ) ) {

			$view_label = 'Access Level';
			$this->levelPostType = get_post_type_object( $this->ruaTypeRestrict );
			if ( ! empty( $this->levelPostType->labels->singular_name ) ) {
				$view_label = $this->levelPostType->labels->singular_name;
			} elseif ( isset( $this->levelPostType->labels->name ) ) {
				$view_label = $this->levelPostType->labels->name;
			}

			// Translators: %s stands for the view type label.
			$title = sprintf( __( 'Viewing as %s', VIEW_ADMIN_AS_DOMAIN ), $view_label ) . ': ';
			$title .= $this->get_levels( $this->selectedLevel )->post_title;
			// Is there also a role selected?
			if ( $this->store->get_view( 'role' ) && $this->store->get_roles( $this->store->get_view( 'role' ) ) ) {
				$title .= ' <span class="user-role">('
				          . $this->store->get_rolenames( $this->store->get_view( 'role' ) )
				          . ')</span>';
			}
		}
		return $title;
	}

	/**
	 * Add the RUA admin bar items.
	 *
	 * Disable some PHPMD checks for this method.
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 * @todo Refactor to enable above checks?
	 *
	 * @since   1.6.4
	 * @access  public
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item.
	 * @param   string        $role       (optional) Role name.
	 * @param   WP_Role       $role_obj   (optional) Role object.
	 */
	public function admin_bar_menu( $admin_bar, $root, $role = null, $role_obj = null ) {
		$view_name = 'Access Levels';
		$this->levelPostType = get_post_type_object( $this->ruaTypeRestrict );
		if ( isset( $this->levelPostType->labels->name ) ) {
			$view_name = $this->levelPostType->labels->name;
		}

		if ( ! $this->get_levels() || ! count( $this->get_levels() ) ) {
			return;
		}

		if ( ! $role ) {

			$admin_bar->add_group( array(
				'id'     => $root . '-rua-levels',
				'parent' => $root,
				'meta'   => array(
					'class' => 'ab-sub-secondary',
				),
			) );

			$root = $root . '-rua-levels';

			$admin_bar->add_node( array(
				'id'     => $root . '-title',
				'parent' => $root,
				'title'  => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-admin-network' ) . $view_name,
				'href'   => false,
				'meta'   => array(
					'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
					'tabindex' => '0',
				),
			) );

		} else {

			$admin_bar->add_node( array(
				'id'     => $root . '-rua-levels',
				'parent' => $root,
				'title'  => VAA_View_Admin_As_Admin_Bar::do_icon( 'dashicons-admin-network' ) . $view_name,
				'href'   => false,
				'meta'   => array(
					'class'    => 'vaa-has-icon',
					'tabindex' => '0',
				),
			) );

			$root = $root . '-rua-levels';

		} // End if().

		/**
		 * Add items at the beginning of the rua group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string        $root        The current root item.
		 */
		do_action( 'vaa_admin_bar_rua_levels_before', $admin_bar, $root );

		// Add the levels.
		foreach ( $this->get_levels() as $level ) {
			$view_value = $level->ID;
			$view_data  = array( $this->viewKey => $view_value );
			if ( $role ) {
				$view_data['role'] = $role;
			}
			$href  = VAA_API::get_vaa_action_link( $view_data, $this->store->get_nonce( true ) );
			$class = 'vaa-' . $this->viewKey . '-item';
			$title = VAA_View_Admin_As_Form::do_view_title( $level->post_title, $this->viewKey, ( $role ) ? wp_json_encode( $view_data ) : $view_value );
			// Check if this level is the current view.
			if ( $this->store->get_view( $this->viewKey ) ) {
				if ( VAA_API::is_current_view( $view_value, $this->viewKey ) ) {
					// @todo Use is_current_view() from vaa controller?
					if ( 1 === count( $this->store->get_view() ) && empty( $role ) ) {
						$class .= ' current';
						$href = false;
					} elseif ( ! empty( $role ) && $role === $this->store->get_view( 'role' ) ) {
						$class .= ' current';
						$href = false;
					}
				} else {
					$selected = $this->get_levels( $this->selectedLevel );
					if ( $selected && (int) $selected->post_parent === (int) $view_value ) {
						$class .= ' current-parent';
					}
				}
			}
			$parent = $root;
			if ( ! empty( $level->post_parent ) ) {
				$parent = $root . '-' . $this->viewKey . '-' . (int) $level->post_parent;
			}
			$admin_bar->add_node( array(
				'id'        => $root . '-' . $this->viewKey . '-' . $view_value,
				'parent'    => $parent,
				'title'     => $title,
				'href'      => $href,
				'meta'      => array(
					// Translators: %s stands for the view type name.
					'title'     => sprintf( __( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $level->post_title )
					               . ( ( $role ) ? ' (' . $this->store->get_rolenames( $role_obj->name ) . ')' : '' ),
					'class'     => $class,
					'rel'       => ( $role ) ? wp_json_encode( $view_data ) : $view_value,
				),
			) );
		} // End foreach().

		/**
		 * Add items at the end of the rua group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string        $root        The current root item.
		 */
		do_action( 'vaa_admin_bar_rua_levels_after', $admin_bar, $root );
	}

	/**
	 * Add levels under roles.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @param   WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string        $root       The root item.
	 */
	public function admin_bar_roles_after( $admin_bar, $root ) {

		$roles = $this->store->get_roles();
		if ( ! $roles ) {
			return;
		}

		foreach ( $roles as $role_key => $role ) {

			// Admins always have full access in RUA.
			if ( 'administrator' === $role_key ) {
				continue;
			}

			$role_root = $root . '-role-' . $role_key;

			$this->admin_bar_menu( $admin_bar, $role_root, $role_key, $role );

		}
	}

	/**
	 * Store the available groups.
	 *
	 * @since   1.6.4
	 * @access  private
	 */
	private function store_levels() {
		if ( is_callable( array( $this->ruaApp, 'get_levels' ) ) ) {
			$levels = $this->ruaApp->get_levels();
		} else {
			// Fallback @todo Keep this updated on changes in RUA plugin.
			$levels = get_posts( array(
				'numberposts' => -1,
				'post_type'   => $this->ruaTypeRestrict,
				'post_status' => array( 'publish', 'private', 'future' ),
			) );
		}

		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level ) {
				$this->levels[ $level->ID ] = $level;
			}
		}
	}

	/**
	 * Get a group by ID.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @param   string  $key  (optional) The level key.
	 * @return  mixed
	 */
	public function get_levels( $key = '-1' ) {
		if ( ! is_numeric( $key ) ) {
			return false;
		}
		if ( '-1' === $key ) {
			$key = null;
		}
		return VAA_API::get_array_data( $this->levels, $key );
	}

	/**
	 * Get all caps of a level.
	 * Also able to get all caps based on level hierarchy (default).
	 *
	 * @since   1.6.4
	 * @param   int   $level_id      The level ID.
	 * @param   bool  $hierarchical  Add parent level caps?
	 * @return  array
	 */
	public function get_level_caps( $level_id, $hierarchical = true ) {

		// @see https://github.com/intoxstudio/restrict-user-access/pull/8.
		if ( function_exists( 'rua_get_level_caps' ) ) {
			return (array) rua_get_level_caps( $level_id, $hierarchical );
		}

		$levels = array( $level_id );
		if ( $hierarchical ) {
			$levels = array_merge( $levels, get_post_ancestors( (int) $level_id ) );
			$levels = array_reverse( (array) $levels );
		}

		$caps = array();
		foreach ( $levels as $level ) {
			// Just use the regular get_post_meta to prevent any errors in future or old versions of RUA.
			// @todo Check for future API updates in RUA plugin.
			// $level_caps = $this->ruaApp->level_manager->metadata()->get( "caps" )->get_data( $level );
			$level_caps = get_post_meta( $level, $this->ruaMetaPrefix . 'caps', true );
			if ( ! empty( $level_caps ) && is_array( $level_caps ) ) {
				foreach ( $level_caps as $key => $level_cap ) {
					$caps[ $key ] = (bool) $level_cap;
				}
			}
		}

		return $caps;
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  VAA_View_Admin_As_RUA
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_RUA.
