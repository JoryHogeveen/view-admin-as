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
 * Required since v1.7.2: 0.15.1 (https://github.com/intoxstudio/restrict-user-access/pull/11)
 * Checked version: 1.0
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.6.4
 * @version 1.8.4
 * @uses    \VAA_View_Admin_As_Type Extends class
 */
final class VAA_View_Admin_As_RUA extends VAA_View_Admin_As_Type
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.6.4
	 * @static
	 * @var    \VAA_View_Admin_As_RUA
	 */
	private static $_instance = null;

	/**
	 * @since  1.6.4
	 * @since  1.8.0  Renamed from `$viewKey`.
	 * @var    string
	 */
	protected $type = 'rua_level';

	/**
	 * The view icon.
	 *
	 * @since  1.7.6
	 * @var    string
	 */
	protected $icon = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCIgdmlld0JveD0iMCAwIDIwIDIwIj48ZyBmaWxsPSIjYTBhNWFhIj48cGF0aCBkPSJNMTAuMDEyIDE0LjYyNUw1Ljc4IDEyLjI3Yy0xLjkwNi42NjQtMy42MDUgMS43Ni00Ljk4IDMuMTc4IDIuMTA1IDIuNzcgNS40MzYgNC41NiA5LjE4NSA0LjU2IDMuNzY2IDAgNy4xMTItMS44MDIgOS4yMTUtNC41OTMtMS4zOC0xLjQwNC0zLjA3LTIuNDk2LTQuOTctMy4xNTRsLTQuMjE4IDIuMzY3em0tLjAwNS0xNC42M0M3LjQxMi0uMDA1IDUuMzEgMS45MSA1LjMxIDQuMjhoOS4zOTNjMC0yLjM3LTIuMS00LjI4Ni00LjY5Ni00LjI4NnptNi4xMjYgMTAuNzFjLjE1OC0uMDMyLjY0LS4yMzIuNjMtLjMzMy0uMDI1LS4yNC0uNjg2LTUuNTg0LS42ODYtNS41ODRzLS40MjItLjI3LS42ODYtLjI5M2MuMDI0LjIxLjY5IDUuNzYuNzQ1IDYuMjF6bS0xMi4yNTMgMGMtLjE1OC0uMDMyLS42NC0uMjMyLS42My0uMzMzLjAyNS0uMjQuNjg2LTUuNTg0LjY4Ni01LjU4NHMuNDItLjI3LjY4Ni0uMjkzYy0uMDIuMjEtLjY5IDUuNzYtLjc0MiA2LjIxeiIvPjxwYXRoIGQ9Ik0xMCAxMy45NjdoLjAyM2wuOTc1LS41NXYtNC4yMWMuNzgtLjM3NyAxLjMxNC0xLjE3MyAxLjMxNC0yLjA5NyAwLTEuMjg1LTEuMDM1LTIuMzIzLTIuMzItMi4zMjNTNy42NyA1LjgyNSA3LjY3IDcuMTFjMCAuOTIzLjUzNSAxLjcyIDEuMzE1IDIuMDkzVjEzLjRsMS4wMTYuNTY3em0tMS43NjQtLjk4NXYtLjAzNWMwLTMuNjEtMS4zNS02LjU4My0zLjA4My02Ljk2bC0uMDMuMy0uNTIgNC42NyAzLjYzMyAyLjAyNXptMy41Ni0uMDM1YzAgLjAxNCAwIC4wMTguMDAzLjAyM2wzLjYxLTIuMDI1LS41My00LjY4LS4wMjgtLjI3M2MtMS43MjMuNC0zLjA1NyAzLjM2Mi0zLjA1NyA2Ljk1NXoiLz48L2c+PC9zdmc+';

	/**
	 * @since  1.6.4
	 * @since  1.8.0  Renamed from `$selectedLevel`.
	 * @var    int  WP_Post ID (RUA access level post type).
	 */
	protected $selected;

	/**
	 * @since  1.6.4
	 * @since  1.8.0  Renamed from `$selectedLevelCaps`.
	 * @var    array  The caps set for this level.
	 */
	protected $selectedCaps = array();

	/**
	 * @since  1.6.4
	 * @var    \WP_Post_Type  The post type object of the level types.
	 */
	protected $levelPostType;

	/**
	 * @since  1.6.4
	 * @var    \RUA_App
	 */
	protected $ruaApp;

	/**
	 * @since  1.7.2
	 * @var    \RUA_Level_Manager
	 */
	protected $ruaLevelManager;

	/**
	 * @since  1.6.4
	 * @var    string
	 */
	protected $ruaMetaPrefix;

	/**
	 * @since  1.6.4
	 * @var    string
	 */
	protected $ruaTypeRestrict;

	/**
	 * @since  1.7.4
	 * @var    string
	 */
	protected $ruaScreen;

	/**
	 * Populate the instance and validate RUA plugin is active.
	 *
	 * @since   1.6.4
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;

		if ( is_network_admin() || ! VAA_API::exists_callable( array( 'RUA_App', 'instance' ), 'debug' ) ) {
			return;
		}
		$this->ruaApp = RUA_App::instance();

		if ( ! is_object( $this->ruaApp->level_manager ) ) {
			return;
		}
		$this->ruaLevelManager = $this->ruaApp->level_manager;

		$this->ruaMetaPrefix   = ( defined( 'RUA_App::META_PREFIX' ) ) ? RUA_App::META_PREFIX : '_ca_';
		$this->ruaTypeRestrict = ( defined( 'RUA_App::TYPE_RESTRICT' ) ) ? RUA_App::TYPE_RESTRICT : 'restriction';
		$this->ruaScreen       = ( defined( 'RUA_App::BASE_SCREEN' ) ) ? RUA_App::BASE_SCREEN : 'wprua';
		$this->cap             = ( defined( 'RUA_App::CAPABILITY' ) ) ? RUA_App::CAPABILITY : 'manage_options';

		parent::__construct( $vaa );

		if ( ! $this->has_access() ) {
			return;
		}

		$this->priorities['toolbar'] = 40;

		$this->label          = 'Access Levels';
		$this->label_singular = 'Access Level';
		$this->description    = __( 'Plugin' ) . ': ' . $this->translate_remote( 'Restrict User Access' );

		$this->add_action( 'init', array( $this, 'set_labels' ), 99999 );
	}

	/**
	 * Sets the type labels.
	 * @since   1.8.0
	 */
	public function set_labels() {
		$this->levelPostType = get_post_type_object( $this->ruaTypeRestrict );
		if ( ! empty( $this->levelPostType->labels->singular_name ) ) {
			$this->label_singular = $this->levelPostType->labels->singular_name;
		}
		if ( isset( $this->levelPostType->label ) ) {
			$this->label = $this->levelPostType->label;
		}
	}

	/**
	 * Setup module and hooks.
	 *
	 * @since   1.7.4
	 * @access  private
	 */
	public function init() {

		if ( parent::init() ) {
			$this->add_action( 'vaa_admin_bar_roles_after', array( $this, 'admin_bar_roles_after' ), 10, 2 );
		} else {
			// Add this anyway to reset user level caps.
			$this->add_action( 'vaa_view_admin_as_do_view', array( $this, 'do_view' ) );
		}
	}

	/**
	 * Initialize the RUA module.
	 *
	 * @since   1.6.4
	 * @access  public
	 */
	public function do_view() {

		if ( parent::do_view() ) {

			if ( ! VAA_API::exists_callable( array( 'WPCALoader', 'load' ), 'debug' ) ) {
				return;
			}
			WPCALoader::load();

			//$this->selected     = $this->store->get_view( $this->type );
			$this->selectedCaps = $this->get_level_caps( $this->selected, true );

			$this->add_action( 'vaa_view_admin_as_modify_user', array( $this, 'modify_user' ), 10, 2 );
			$this->init_user_modifications();

			$this->add_filter( 'get_user_metadata', array( $this, 'get_user_metadata' ), 10, 3 );

			// Administrators can see all restricted content in RUA.
			if ( VAA_API::is_view_active() && ! $this->store->get_selectedCaps( 'administrator' ) ) {
				// Not a view with administrator capability == no global access.
				$this->add_filter( 'rua/user/global-access', '__return_false' );
			}
		}

		if ( VAA_API::is_user_modified() && is_object( $this->ruaLevelManager ) ) {

			if ( is_callable( array( $this->ruaLevelManager, 'reset_user_levels_caps' ) ) ) {
				/**
				 * Reset the user levels caps.
				 * @since  1.7.2
				 * @link   https://github.com/JoryHogeveen/view-admin-as/issues/56#issuecomment-299077527
				 * @link   https://github.com/intoxstudio/restrict-user-access/pull/11
				 * @see    \RUA_Level_Manager::add_filters()
				 */
				$this->ruaLevelManager->reset_user_levels_caps( $this->store->get_selectedUser()->ID );
			}

			if ( $this->store->get_view( 'caps' ) ) {
				/**
				 * Remove the whole filter when the caps view is selected.
				 * @since  1.7.2
				 * @link   https://github.com/JoryHogeveen/view-admin-as/issues/56#issuecomment-299077527
				 * @see    \RUA_Level_Manager::add_filters()
				 */
				remove_filter( 'user_has_cap', array( $this->ruaLevelManager, 'user_level_has_cap' ), 9 );
			}
		}
	}

	/**
	 * Update the current user's WP_User instance with the current view data.
	 *
	 * @since   1.6.4
	 * @param   \WP_User  $user        User object.
	 */
	public function modify_user( $user ) {

		$caps = (array) $this->selectedCaps;

		// Merge the caps with the current selected caps, overwrite existing.
		$caps = array_merge( $this->store->get_selectedCaps(), $caps );

		$this->store->set_selectedCaps( $caps );

		// Merge the caps with the current user caps, overwrite existing.
		$user->allcaps = array_merge( $user->caps, $caps );
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
		if (
			(int) $user_id === (int) $this->store->get_selectedUser()->ID
			&& $this->get_levels( $this->selected )
		) {
			// @todo Check for future API updates in RUA plugin
			if ( $this->ruaMetaPrefix . 'level' === $meta_key ) {
				return array( $this->selected );
			}
			if ( $this->ruaMetaPrefix . 'level_' . $this->selected === $meta_key ) {
				// Return current time + 120 seconds to make sure this level won't be set as expired.
				return array( time() + 120 );
			}
		}
		return $null;
	}

	/**
	 * Validate data for this view type
	 *
	 * @since   1.7.0
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	public function validate_view_data( $null, $data = null ) {
		if ( is_numeric( $data ) && $this->get_levels( (int) $data ) ) {
			return (int) $data;
		}
		return $null;
	}

	/**
	 * Get the view title.
	 *
	 * @since   1.8.x
	 * @param   string  $key  The data key.
	 * @return  string
	 */
	public function get_view_title( $key ) {
		$title = $key;
		$item  = $this->get_levels( $key );
		if ( $item ) {
			$title = $item->post_title;
		}

		/**
		 * Change the display title for view type nodes.
		 *
		 * @since  1.8.0
		 * @param  string  $title  Level title.
		 * @param  string  $key    Level key.
		 * @return string
		 */
		$title = apply_filters( 'vaa_admin_bar_view_title_' . $this->type, $title, $key );

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
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item.
	 * @param   string         $role       (optional) Role name.
	 * @param   \WP_Role       $role_obj   (optional) Role object.
	 */
	public function admin_bar_menu( $admin_bar, $root, $role = null, $role_obj = null ) {

		if ( ! $this->get_levels() ) {
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
				'title'  => VAA_View_Admin_As_Form::do_icon( $this->icon ) . $this->label,
				'href'   => false,
				'meta'   => array(
					'class'    => 'vaa-has-icon ab-vaa-title ab-vaa-toggle active',
					'tabindex' => '0',
				),
			) );

			$admin_bar->add_node( array(
				'id'     => $root . '-admin',
				'parent' => $root,
				'title'  => VAA_View_Admin_As_Form::do_description(
					VAA_View_Admin_As_Form::do_icon( 'dashicons-admin-links' )
					. __( 'Plugin' ) . ': ' . $this->translate_remote( 'Restrict User Access' )
				),
				'href'   => menu_page_url( $this->ruaScreen, false ),
				'meta'   => array(
					'class' => 'auto-height',
				),
			) );

		} else {

			$admin_bar->add_node( array(
				'id'     => $root . '-rua-levels',
				'parent' => $root,
				'title'  => VAA_View_Admin_As_Form::do_icon( $this->icon ) . $this->label,
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
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 */
		do_action( 'vaa_admin_bar_rua_levels_before', $admin_bar, $root );

		// Add the levels.
		foreach ( $this->get_levels() as $level ) {
			$view_value = $level->ID;
			$view_data  = array( $this->type => $view_value );
			if ( $role ) {
				$view_data['role'] = $role;
			}
			$href  = VAA_API::get_vaa_action_link( $view_data );
			$class = 'vaa-' . $this->type . '-item';
			$title = VAA_View_Admin_As_Form::do_view_title( $level->post_title, $this, ( $role ) ? wp_json_encode( $view_data ) : $view_value );
			// Check if this level is the current view.
			if ( $this->store->get_view( $this->type ) ) {
				if ( VAA_API::is_current_view( $view_value, $this->type ) ) {
					$class .= ' current';
					if ( 1 === count( $this->store->get_view() ) && empty( $role ) ) {
						// The node item is the only view and is not related to a role.
						$href = false;
					} elseif ( ! empty( $role ) && $role === $this->store->get_view( 'role' ) ) {
						// The node item is related to a role and that role is the current view.
						$href = false;
					}
				} else {
					$selected = $this->get_levels( $this->selected );
					if ( $selected && (int) $selected->post_parent === (int) $view_value ) {
						$class .= ' current-parent';
					}
				}
			}
			$parent = $root;
			if ( ! empty( $level->post_parent ) ) {
				$parent = $root . '-' . $this->type . '-' . (int) $level->post_parent;
			}
			$admin_bar->add_node( array(
				'id'     => $root . '-' . $this->type . '-' . $view_value,
				'parent' => $parent,
				'title'  => $title,
				'href'   => $href,
				'meta'   => array(
					// Translators: %s stands for the view type name.
					'title' => sprintf( __( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $level->post_title )
					           . ( ( $role ) ? ' (' . $this->store->get_rolenames( $role_obj->name ) . ')' : '' ),
					'class' => $class,
				),
			) );
		} // End foreach().

		/**
		 * Add items at the end of the rua group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 */
		do_action( 'vaa_admin_bar_rua_levels_after', $admin_bar, $root );
	}

	/**
	 * Add levels under roles.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item.
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
	 * Store the available access levels.
	 *
	 * @since   1.6.4
	 * @since   1.8.0  Renamed from `store_levels()`.
	 * @access  private
	 */
	public function store_data() {
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

		$data = array();
		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level ) {
				$data[ $level->ID ] = $level;
			}
		}
		$this->set_data( $data );
	}

	/**
	 * Get an access level by ID.
	 *
	 * @since   1.6.4
	 * @see     \RUA_App::get_levels()
	 * @access  public
	 * @param   string  $key  (optional) The level key.
	 * @return  \WP_Post[]|\WP_Post  Array of WP_Post objects (RUA access level post type)
	 */
	public function get_levels( $key = '-1' ) {
		if ( ! is_numeric( $key ) ) {
			return null;
		}
		if ( '-1' === $key ) {
			$key = null;
		}
		return $this->get_data( $key );
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
	 * Translate with another domain.
	 *
	 * @since   1.7.4
	 * @param   string  $string  The string.
	 * @return  string
	 */
	public function translate_remote( $string ) {
		$domain = ( defined( 'RUA_App::DOMAIN' ) ) ? RUA_App::DOMAIN : 'restrict-user-access';
		// @codingStandardsIgnoreLine >> Prevent groups translation from getting parsed by translate.wordpress.org
		return __( $string, $domain );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.6.4
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class.
	 * @return  \VAA_View_Admin_As_RUA  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_RUA.
