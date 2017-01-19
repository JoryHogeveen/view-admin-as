<?php
/**
 * View Admin As - Restrict User Access plugin
 *
 * Compatibility class for the Restrict User Access plugin
 *
 * Tested RUA version: 0.12.4
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   1.7
 * @version 1.7
 */

! defined( 'VIEW_ADMIN_AS_DIR' ) and die( 'You shall not pass!' );

if ( ! class_exists( 'VAA_View_Admin_As_RUA' ) ) {

add_action( 'vaa_view_admin_as_modules_loaded', array( 'VAA_View_Admin_As_RUA', 'get_instance' ) );

final class VAA_View_Admin_As_RUA extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.7
	 * @static
	 * @var    VAA_View_Admin_As_RUA
	 */
	private static $_instance = null;

	/**
	 * The existing access levels
	 *
	 * @since  1.7
	 * @see    restrict-user-access/app.php -> get_levels()
	 * @var    array of WP_Post objects (RUA access level post type)
	 */
	private $levels;

	/**
	 * @since  1.7
	 * @var    int  WP_Post ID (RUA access level post type)
	 */
	private $selectedLevel;

	/**
	 * @since  1.7
	 * @var    array  The caps set for this level
	 */
	private $selectedLevelCaps = array();

	/**
	 * @since  1.7
	 * @var    object  The post type object of the level types
	 */
	private $levelPostType;

	/**
	 * @since  1.7
	 * @var    string
	 */
	private $viewKey = 'rua_level';

	/**
	 * @since  1.7
	 * @var    RUA_App
	 */
	private $ruaApp;

	/**
	 * @since  1.7
	 * @var    string
	 */
	private $ruaMetaPrefix;

	/**
	 * @since  1.7
	 * @var    string
	 */
	private $ruaTypeRestrict;

	/**
	 * Populate the instance and validate RUA plugin is active
	 *
	 * @since   1.7
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa
	 */
	protected function __construct( $vaa ) {

		if ( ! is_callable( array( 'RUA_App', 'instance' ) ) ) {
			return;
		}

		self::$_instance = $this;
		$this->ruaApp = RUA_App::instance();

		$access_cap            = ( defined( RUA_App::CAPABILITY ) ) ? RUA_App::CAPABILITY : 'edit_users';
		$this->ruaMetaPrefix   = ( defined( RUA_App::META_PREFIX ) ) ? RUA_App::META_PREFIX : '_ca_';
		$this->ruaTypeRestrict = ( defined( RUA_App::TYPE_RESTRICT ) ) ? RUA_App::TYPE_RESTRICT : 'restriction';

		if ( $vaa->is_enabled() && current_user_can( $access_cap ) && ! is_network_admin() ) {
			parent::__construct( $vaa );

			$this->vaa->register_module( array(
				'id'       => $this->viewKey,
				'instance' => self::$_instance
			) );

			$this->store_levels();

			add_filter( 'view_admin_as_view_types', array( $this, 'add_view_type' ) );

			add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), 40, 2 );
			add_action( 'vaa_admin_bar_roles_after', array( $this, 'admin_bar_roles_after' ), 10, 2 );

			add_action( 'vaa_view_admin_as_do_view', array( $this, 'do_view' ) );
		}
	}

	/**
	 * Initialize the RUA module
	 *
	 * @since   1.7
	 * @access  public
	 */
	public function do_view() {

		if ( $this->get_levels( $this->get_viewAs( $this->viewKey ) ) ) {

			$this->selectedLevel     = $this->get_viewAs( $this->viewKey );
			$this->selectedLevelCaps = $this->get_level_caps( $this->selectedLevel, true );

			add_filter( 'vaa_admin_bar_viewing_as_title', array( $this, 'vaa_viewing_as_title' ) );

			$this->vaa->view()->init_user_modifications();
			add_action( 'vaa_view_admin_as_modify_user', array( $this, 'modify_user' ), 10, 2 );

			add_filter( 'get_user_metadata', array( $this, 'get_user_metadata' ), 10, 3 );

			// Administrators can see all restricted content in RUA
			if ( $this->get_viewAs() && ! $this->get_selectedCaps('administrator') ) {
				// Not a view with administrator capability == no global access
				add_filter( 'rua/user/global-access', '__return_false' );
			}
		}
	}

	/**
	 * Update the current user's WP_User instance with the current view data
	 *
	 * @since   1.7
	 * @param   WP_User  $user
	 * @param   bool     $accessible
	 */
	public function modify_user( $user, $accessible ) {

		$caps = (array) $this->selectedLevelCaps;

		if ( $this->get_viewAs('role') || ! $accessible ) {
			// Merge the caps with the current selected caps, overwrite existing
			// Also do the same when WP_User parameters aren't accessible
			$caps = array_merge( $this->store->get_selectedCaps(), $caps );
		} else {
			$caps = array_merge( $user->allcaps, $caps );
		}

		$this->store->set_selectedCaps( $caps );

		if ( $accessible ) {
			// Merge the caps with the current user caps, overwrite existing
			$user->allcaps = array_merge( $user->caps, $caps );
		}
	}

	/**
	 * Filter the return metadata for the RUA levels
	 *
	 * @since   1.7
	 * @param   null    $null
	 * @param   int     $user_id
	 * @param   string  $meta_key
	 * @return  array
	 */
	public function get_user_metadata( $null, $user_id, $meta_key ) {
		if ( $user_id == $this->get_curUser()->ID
		     && $this->get_levels( $this->selectedLevel )
		) {
			// @todo Check for future API updates in RUA plugin
			if ( $meta_key == $this->ruaMetaPrefix . 'level' ) {
				return array( $this->selectedLevel );
			}
			if ( $meta_key == $this->ruaMetaPrefix . 'level_' . $this->selectedLevel ) {
				// Return current time + 120 seconds to make sure this level won't be set as expired
				return array( time() + 120 );
			}
		}
		return $null;
	}

	/**
	 * Add groups view type
	 *
	 * @since   1.7
	 * @param   array  $types
	 * @return  array
	 */
	public function add_view_type( $types ) {
		$types[] =  $this->viewKey;
		return $types;
	}

	/**
	 * Ajax handler, called from main ajax handler
	 *
	 * @since   1.7
	 * @access  public
	 * @param   $data
	 * @return  bool
	 */
	public function ajax_handler( $data ) {

		if ( ! defined('VAA_DOING_AJAX')
		  || ! VAA_DOING_AJAX
		  || ! $this->is_vaa_enabled()
		) {
			return false;
		}

		$level = $data;

		if ( is_string( $data ) && strpos( $data, '|' ) !== false ) {
			$data = explode( '|', $data );
			$level = (int) $data[0];
			if ( ! empty( $data[1] ) ) {
				$role = (string) $data[1];
			}
		}

		if ( is_numeric( $level ) && $this->get_levels( (int) $level ) ) {
			$view = array(
				$this->viewKey  => (int) $level
			);
			if ( ! empty( $role ) && $this->store->get_roles( $role ) ) {
				$view['role'] = $role;
			}
			$this->vaa->view()->update_view( $view );
			return true;
		}
		return false;
	}

	/**
	 * Change the VAA admin bar menu title
	 *
	 * @since   1.7
	 * @access  public
	 * @param   string  $title
	 * @return  string
	 */
	public function vaa_viewing_as_title( $title ) {
		$view_label = 'Access Level';
		$this->levelPostType = get_post_type_object( $this->ruaTypeRestrict );
		if ( ! empty( $this->levelPostType->labels->singular_name ) ) {
			$view_label = $this->levelPostType->labels->singular_name;
		} elseif ( isset( $this->levelPostType->labels->name ) ) {
			$view_label = $this->levelPostType->labels->name;
		}

		if ( $this->get_levels( $this->selectedLevel ) ) {
			$title = sprintf( __( 'Viewing as %s', VIEW_ADMIN_AS_DOMAIN ), $view_label ) . ': ';
			$title .= $this->get_levels( $this->selectedLevel )->post_title;
			// Is there also a role selected?
			if ( $this->get_viewAs('role') && $this->get_roles( $this->get_viewAs('role') ) ) {
				$title .= ' <span class="user-role">('
				          . translate_user_role( $this->get_roles( $this->get_viewAs('role') )->name )
				          . ')</span>';
			}
		}
		return $title;
	}

	/**
	 * Add the RUA admin bar items
	 *
	 * @since   1.7
	 * @access  public
	 * @param   WP_Admin_Bar  $admin_bar
	 * @param   string        $root
	 * @param   mixed         $role
	 * @param   mixed         $role_obj
	 */
	public function admin_bar_menu( $admin_bar, $root, $role = false, $role_obj = null ) {
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
					'tabindex' => '0'
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
					'tabindex' => '0'
				),
			) );

			$root = $root . '-rua-levels';

		}

		/**
		 * Add items at the beginning of the rua group
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_rua_levels_before', $admin_bar );

		// Add the levels
		foreach ( $this->get_levels() as $level_key => $level ) {
			$href = '#';
			$class = 'vaa-' . $this->viewKey . '-item';
			$title = $level->post_title;
			// Check if this level is the current view
			if ( $this->get_viewAs( $this->viewKey ) ) {
				if ( $this->get_viewAs( $this->viewKey ) == $level->ID ) {
					$class .= ' current';
					if ( 1 === count( $this->get_viewAs() ) ) {
						$href = false;
					}
				}
				elseif ( $current_parent = $this->get_levels( $this->selectedLevel ) ) {
					if ( $current_parent->post_parent == $level->ID ) {
						$class .= ' current-parent';
					}
				}
			}
			$parent = $root;
			if ( ! empty( $level->post_parent ) ) {
				$parent = $root .'-' . $this->viewKey . '-' . $level->post_parent;
			}
			$admin_bar->add_node( array(
				'id'        => $root . '-' . $this->viewKey . '-' . $level->ID,
				'parent'    => $parent,
				'title'     => $title,
				'href'      => $href,
				'meta'      => array(
					'title'     => sprintf( esc_attr__( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $level->post_title )
					               . ( ( $role ) ? ' (' . translate_user_role( $role_obj->name ) . ')' : '' ),
					'class'     => $class,
					'rel'       => $level->ID . ( ( $role ) ? '|' . $role : '' ),
				),
			) );
		}

		/**
		 * Add items at the end of the rua group
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 */
		do_action( 'vaa_admin_bar_rua_levels_after', $admin_bar );
	}

	/**
	 * Add levels under roles
	 *
	 * @since   1.7
	 * @access  public
	 * @param   $admin_bar
	 * @param   $root
	 */
	public function admin_bar_roles_after( $admin_bar, $root ) {

		if ( ! $this->store->get_roles() ) {
			return;
		}

		foreach ( $this->store->get_roles() as $role_key => $role ) {

			// Admins always have full access in RUA
			if ( 'administrator' == $role_key ) {
				continue;
			}

			$role_root = $root . '-role-' . $role_key;

			$this->admin_bar_menu( $admin_bar, $role_root, $role_key, $role );

		}
	}

	/**
	 * Store the available groups
	 *
	 * @since   1.7
	 * @access  private
	 */
	private function store_levels() {
		if ( is_callable( array( $this->ruaApp, 'get_levels' ) ) ) {
			$levels = $this->ruaApp->get_levels();
		} else {
			// Fallback @todo Keep this updated on changes in RUA plugin
			$levels = get_posts( array(
				'numberposts' => -1,
				'post_type'   => $this->ruaTypeRestrict,
				'post_status' => array( 'publish', 'private', 'future' )
			) );
		}

		if ( ! empty( $levels ) ) {
			foreach ( $levels as $level ) {
				$this->levels[ $level->ID ] = $level;
			}
		}
	}

	/**
	 * Get a group by ID
	 *
	 * @since   1.7
	 * @access  public
	 * @param   string  $key
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
	 * Get all caps of a level
	 * Also able to get all caps based on level hierarchy (default)
	 *
	 * @since   1.7
	 * @param   int   $level_id
	 * @param   bool  $hierarchical
	 * @return  array
	 */
	public function get_level_caps( $level_id, $hierarchical = true ) {

		// @see https://github.com/intoxstudio/restrict-user-access/pull/8
		if ( function_exists('rua_get_level_caps') ) {
			return (array) rua_get_level_caps( $level_id, $hierarchical );
		}

		$levels = array( $level_id );
		if ( $hierarchical ) {
			$levels = array_merge( $levels, get_post_ancestors( (int) $level_id ) );
			$levels = array_reverse( (array) $levels );
		}

		$caps = array();
		foreach ( $levels as $level ) {
			// Just use the regular get_post_meta to prevent any errors in future or old versions of RUA
			// @todo Check for future API updates in RUA plugin
			// $level_caps = $this->ruaApp->level_manager->metadata()->get( "caps" )->get_data( $level );
			$level_caps = get_post_meta( $level, $this->ruaMetaPrefix . 'caps', true );
			if( ! empty( $level_caps ) && is_array( $level_caps ) ) {
				foreach ( $level_caps as $key => $level_cap ) {
					$caps[$key] = !!$level_cap;
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
	 * @since   1.7
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class
	 * @return  VAA_View_Admin_As_RUA
	 */
	public static function get_instance( $caller = null ) {
		if ( is_object( $caller ) && 'VAA_View_Admin_As' == get_class( $caller ) ) {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self( $caller );
			}
			return self::$_instance;
		}
		return null;
	}

} // end class

} // end if class_exists