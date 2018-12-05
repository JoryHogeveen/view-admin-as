<?php
/**
 * View Admin As - Groups plugin
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Compatibility class for the Groups plugin
 *
 * Tested from Groups version: 2.1.2
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.7.2
 * @version 1.8.3
 * @uses    \VAA_View_Admin_As_Type Extends class
 */
final class VAA_View_Admin_As_Groups extends VAA_View_Admin_As_Type
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.7.2
	 * @static
	 * @var    \VAA_View_Admin_As_Groups
	 */
	private static $_instance = null;

	/**
	 * @since  1.7.2
	 * @since  1.8.0  Renamed from `$viewKey`.
	 * @var    string
	 */
	protected $type = 'groups';

	/**
	 * The view icon.
	 *
	 * @todo Check for `dashicons-itthinx-groups`: https://github.com/itthinx/groups/pull/61
	 *
	 * @since  1.7.6
	 * @var    string
	 */
	protected $icon = 'dashicons-image-filter';

	/**
	 * @since  1.7.2
	 * @since  1.8.0  Renamed from `$selectedGroup`.
	 * @see    \Groups_Group >> groups/lib/core/class-groups-group.php
	 * @var    \Groups_Group
	 */
	protected $selected;

	/**
	 * @since  1.7.4
	 * @var    string
	 */
	protected $groupsScreen = 'groups-admin';

	/**
	 * Populate the instance and validate Groups plugin.
	 *
	 * @since   1.7.2
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;

		if ( is_network_admin() || ! VAA_API::exists_callable( array( 'Groups_Group', 'get_groups' ), 'debug' ) ) {
			return;
		}

		$this->cap = ( defined( 'GROUPS_ADMINISTER_GROUPS' ) ) ? GROUPS_ADMINISTER_GROUPS : 'manage_options';

		parent::__construct( $vaa );

		if ( ! $this->has_access() ) {
			return;
		}

		$this->priorities['toolbar'] = 40;

		$this->label          = $this->translate_remote( 'Groups' );
		$this->label_singular = $this->translate_remote( 'Group' );
		$this->description    = __( 'Plugin' ) . ': ' . $this->label;

		// Add groups capabilities.
		$this->capabilities[] = $this->cap;
		if ( defined( 'GROUPS_ACCESS_GROUPS' ) ) {
			$this->capabilities[] = GROUPS_ACCESS_GROUPS;
		}
		if ( defined( 'GROUPS_ADMINISTER_OPTIONS' ) ) {
			$this->capabilities[] = GROUPS_ADMINISTER_OPTIONS;
		}
		if ( defined( 'GROUPS_RESTRICT_ACCESS' ) ) {
			$this->capabilities[] = GROUPS_RESTRICT_ACCESS;
		}
		// Do not add to VAA capabilities.
		$this->add_filter( 'members_get_capabilities', array( $this, 'add_capabilities' ) );
	}

	/**
	 * Setup module and hooks.
	 *
	 * @since   1.7.4
	 * @access  private
	 */
	public function init() {

		if ( parent::init() ) {

			if ( defined( 'GROUPS_PLUGIN_URL' ) ) {
				$this->icon = GROUPS_PLUGIN_URL . '/images/groups.png';
			}
		} else {
			// Add this anyway.
			$this->add_action( 'vaa_view_admin_as_do_view', array( $this, 'do_view' ) );
		}
	}

	/**
	 * Initialize the Groups module.
	 * @since   1.7.2
	 * @access  public
	 */
	public function do_view() {

		if ( parent::do_view() ) {

			$this->selected = new Groups_Group( $this->store->get_view( $this->type ) );

			$this->reset_groups_user();

			$this->add_action( 'vaa_view_admin_as_modify_user', array( $this, 'modify_user' ), 10, 2 );
			$this->init_user_modifications();

			$this->add_filter( 'groups_post_access_user_can_read_post', array( $this, 'groups_post_access_user_can_read_post' ), 99, 3 );

			/**
			 * Replicate 404 page when the selected user has no access to read.
			 * I use this since I can't hook into the `posts_where` filter from Groups.
			 * @see VAA_View_Admin_As_Groups::groups_post_access_user_can_read_post()
			 */
			$this->add_action( 'wp', array( $this, 'post_access_404' ) );
			//$this->add_filter( 'groups_post_access_posts_where_apply', '__return_false' );

			remove_shortcode( 'groups_member' );
			remove_shortcode( 'groups_non_member' );
			add_shortcode( 'groups_member', array( $this, 'shortcode_groups_member' ) );
			add_shortcode( 'groups_non_member', array( $this, 'shortcode_groups_non_member' ) );

			// Filter user-group relationships.
			//$this->add_filter( 'groups_user_is_member', array( $this, 'groups_user_is_member' ), 20, 3 );
		}

		// Filter group capabilities.
		if ( VAA_API::is_user_modified() ) {
			$this->add_filter( 'groups_group_can', array( $this, 'groups_group_can' ), 20, 3 );
			$this->add_filter( 'groups_user_can', array( $this, 'groups_user_can' ), 20, 3 );
		}
	}

	/**
	 * Reset Groups_User data for the selected user.
	 *
	 * @see  \Groups_Cache
	 * @see  \Groups_User
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   int  $user_id
	 */
	public function reset_groups_user( $user_id = null ) {
		if ( ! VAA_API::exists_callable( array( 'Groups_User', 'clear_cache' ), 'debug' ) ) {
			return;
		}

		if ( ! $user_id ) {
			$user_id = $this->store->get_selectedUser()->ID;
		}

		try {

			Groups_User::clear_cache( $user_id );

			$capabilities_base   = array();
			$capability_ids_base = array();
			$groups_ids_base     = array( $this->selected->group_id );
			$groups_base         = array( $this->selected );
			$capabilities        = null;
			$capability_ids      = null;
			$groups_ids          = null;
			$groups              = null;

			Groups_Cache::set( Groups_User::CAPABILITIES_BASE . $user_id, $capabilities_base, Groups_User::CACHE_GROUP );
			Groups_Cache::set( Groups_User::CAPABILITY_IDS_BASE . $user_id, $capability_ids_base, Groups_User::CACHE_GROUP );
			Groups_Cache::set( Groups_User::GROUP_IDS_BASE . $user_id, $groups_ids_base, Groups_User::CACHE_GROUP );
			Groups_Cache::set( Groups_User::GROUPS_BASE . $user_id, $groups_base, Groups_User::CACHE_GROUP );
			//Groups_Cache::set( Groups_User::CAPABILITIES . $user_id, $capabilities, Groups_User::CACHE_GROUP );
			//Groups_Cache::set( Groups_User::CAPABILITY_IDS . $user_id, $capability_ids, Groups_User::CACHE_GROUP );
			//Groups_Cache::set( Groups_User::GROUP_IDS . $user_id, $groups_ids, Groups_User::CACHE_GROUP );
			//Groups_Cache::set( Groups_User::GROUPS . $user_id, $groups, Groups_User::CACHE_GROUP );

		} catch ( Exception $e ) {

			$this->vaa->add_error_notice( __METHOD__, array(
			    'message' => $e->getMessage(),
			) );

		} // End try().
	}

	/**
	 * Update the current user's WP_User instance with the current view data.
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   \WP_User  $user        User object.
	 */
	public function modify_user( $user ) {

		$caps = array();
		if ( $this->selected ) {

			// Merge the caps with the current selected caps, overwrite existing.
			$group_caps = (array) $this->selected->capabilities_deep;
			foreach ( $group_caps as $group_cap ) {
				/**
				 * @see    \Groups_Capability::create()
				 * @see    \Groups_Capability::__get()
				 * @param  int     $capability_id
				 * @param  string  $capability
				 * @param  string  $class
				 * @param  string  $object
				 * @param  string  $name
				 * @param  string  $description
				 * @param  array   $group_ids
				 */
				if ( isset( $group_cap->capability ) && is_string( $group_cap->capability ) ) {
					$caps[ $group_cap->capability ] = 1;
				} elseif ( isset( $group_cap->capability->capability ) ) {
					$caps[ $group_cap->capability->capability ] = 1;
				}
			}
		}

		$caps = array_merge( $this->store->get_selectedCaps(), $caps );

		$this->store->set_selectedCaps( $caps );

		// Merge the caps with the current user caps, overwrite existing.
		$user->allcaps = array_merge( $user->caps, $caps );
	}

	/**
	 * Filter the user-group relation.
	 *
	 * @todo https://github.com/itthinx/groups/pull/59
	 * @see  \Groups_User_Group::read() >> groups/lib/core/class-groups-user-group.php
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   bool  $result    Current result.
	 * @param   int   $user_id   User ID.
	 * @param   int   $group_id  Group ID.
	 * @return  bool|object
	 */
	public function groups_user_is_member( $result, $user_id, $group_id ) {
		if (
			(int) $user_id === (int) $this->store->get_curUser()->ID
			&& $this->selected
			&& (int) $group_id === (int) $this->selected->group->group_id
		) {
			$result = $this->selected->group;
		}
		return $result;
	}

	/**
	 * Filter for the current view.
	 *
	 * @see  \Groups_User::can() >> groups/lib/core/class-groups-user.php
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   bool           $result  Current result.
	 * @param   \Groups_Group  $object  (not used) Group object.
	 * @param   string         $cap     Capability.
	 * @return  bool
	 */
	public function groups_user_can( $result, $object = null, $cap = '' ) {

		/**
		 * Fallback PHP < 5.4 due to apply_filters_ref_array
		 * @see https://codex.wordpress.org/Function_Reference/apply_filters_ref_array
		 */
		if ( is_array( $result ) ) {
			$cap = $result[2];
			//$object = $result[1];
			$result = $result[0];
		}

		if ( ! $this->store->get_view() ) {
			return $result;
		}

		if (
			$this->selected
			&& is_callable( array( $this->selected, 'can' ) )
			&& ! $this->selected->can( $cap )
		) {
			$result = false;
		} else {
			// For other view types.
			$result = VAA_API::current_view_can( $cap );
		}
		return $result;
	}

	/**
	 * Filter for the current view.
	 *
	 * @see  \Groups_Group::can() >> groups/lib/core/class-groups-group.php
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   bool           $result  Current result.
	 * @param   \Groups_Group  $object  Group object.
	 * @param   string         $cap     Capability.
	 * @return  bool
	 */
	public function groups_group_can( $result, $object = null, $cap = '' ) {
		// Prevent loop on `groups_user_can` filter.
		if ( $this->selected && $this->selected->group_id === $object->group_id ) {
			return $result;
		}
		return $this->groups_user_can( $result, $object, $cap );
	}

	/**
	 * Filter whether the user can do something with a post.
	 *
	 * @see  \Groups_Post_Access::user_can_read_post()
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   bool  $result
	 * @param   int   $post_id
	 * @param   int   $user_id
	 * @return  bool
	 */
	public function groups_post_access_user_can_read_post( $result, $post_id, $user_id ) {
		if ( $this->store->get_selectedUser()->ID !== $user_id || ! $this->selected ) {
			return $result;
		}
		if ( ! VAA_API::exists_callable( array( 'Groups_Post_Access', 'get_read_group_ids' ), 'debug' ) ) {
			return $result;
		}

		$post_access = Groups_Post_Access::get_read_group_ids( $post_id );

		$result = true;
		if ( ! empty( $post_access ) && ! in_array( $this->selected->group_id, $post_access, true ) ) {
			$result = false;
		}
		return $result;
	}

	/**
	 * Replicate 404 page when the selected user has no access to read.
	 * I use this since I can't hook into the `posts_where` filter from Groups.
	 *
	 * @hook    `wp`
	 * @see     \VAA_View_Admin_As_Groups::groups_post_access_user_can_read_post()
	 *
	 * @since   1.7.2
	 * @access  public
	 */
	public function post_access_404() {
		global $post;
		if ( isset( $post->ID ) && ! $this->groups_post_access_user_can_read_post( true, $post->ID, $this->store->get_selectedUser()->ID ) ) {
			global $wp_query;
			$wp_query->set_404();
		}
	}

	/**
	 * Our own implementation for the groups_member shortcode.
	 *
	 * @see  \Groups_Access_Shortcodes::groups_member()
	 *
	 * @since   1.7.2
	 * @param   array   $atts
	 * @param   string  $content
	 * @return  string
	 */
	public function shortcode_groups_member( $atts, $content ) {
		return $this->shortcode_member( $atts, $content, false );
	}

	/**
	 * Our own implementation for the groups_non_member shortcode.
	 *
	 * @see  \Groups_Access_Shortcodes::groups_non_member()
	 *
	 * @since   1.7.2
	 * @param   array   $atts
	 * @param   string  $content
	 * @return  string
	 */
	public function shortcode_groups_non_member( $atts, $content ) {
		return ! $this->shortcode_member( $atts, $content, true );
	}

	/**
	 * Our own implementation for the Groups member shortcodes.
	 *
	 * @see  \VAA_View_Admin_As_Groups::shortcode_groups_member()
	 * @see  \VAA_View_Admin_As_Groups::shortcode_groups_non_member()
	 *
	 * @since   1.7.2
	 * @param   array   $atts
	 * @param   string  $content
	 * @param   bool    $reverse
	 * @return  string
	 */
	public function shortcode_member( $atts, $content, $reverse = false ) {
		$output       = '';
		$shortcode    = ( $reverse ) ? 'groups_non_member' : 'groups_member';
		$options      = shortcode_atts( array( 'group' => '' ), $atts ); //, $shortcode
		$show_content = false;
		if ( null !== $content ) {
			$groups = explode( ',', $options['group'] );
			foreach ( $groups as $group ) {
				$group          = trim( $group );
				$selected_group = $this->selected;
				$current_group  = Groups_Group::read( $group );
				if ( ! $current_group ) {
					$current_group = Groups_Group::read_by_name( $group );
				}
				if ( $current_group && $current_group->group_id === $selected_group->group_id ) {
					$show_content = ! $reverse;
					break;
				}
			}
			if ( $show_content ) {
				remove_shortcode( $shortcode );
				$content = do_shortcode( $content );
				add_shortcode( $shortcode, array( $this, 'shortcode_' . $shortcode ) );
				$output = $content;
			}
		}
		return $output;
	}

	/**
	 * Validate data for this view type
	 *
	 * @since   1.7.2
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	public function validate_view_data( $null, $data = null ) {
		if ( is_numeric( $data ) && $this->get_groups( (int) $data ) ) {
			return (int) $data;
		}
		return $null;
	}

	/**
	 * Change the VAA admin bar menu title.
	 *
	 * @since   1.7.2
	 * @since   1.7.5  Renamed from `vaa_viewing_as_title()`.
	 * @access  public
	 * @param   array  $titles  The current title(s).
	 * @return  array
	 */
	public function view_title( $titles = array() ) {
		if ( $this->selected ) {
			$titles[ $this->label_singular ] = $this->selected->name;
		}
		return $titles;
	}

	/**
	 * Add the Groups admin bar items.
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item.
	 */
	public function admin_bar_menu( $admin_bar, $root ) {

		if ( ! $this->get_groups() || ! count( $this->get_groups() ) ) {
			return;
		}

		$admin_bar->add_group( array(
			'id'     => $root . '-groups',
			'parent' => $root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$root = $root . '-groups';

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
				. __( 'Plugin' ) . ': ' . $this->label
			),
			'href'   => menu_page_url( $this->groupsScreen, false ),
			'meta'   => array(
				'class' => 'auto-height',
			),
		) );

		/**
		 * Add items at the beginning of the groups group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 */
		do_action( 'vaa_admin_bar_groups_before', $admin_bar, $root );

		// Add the groups.
		foreach ( $this->get_groups() as $group_key => $group ) {
			$view_value = $group->group_id;
			$view_data  = array( $this->type => $view_value );
			$href       = VAA_API::get_vaa_action_link( $view_data );
			$class      = 'vaa-' . $this->type . '-item';
			$title      = VAA_View_Admin_As_Form::do_view_title( $group->name, $this, $view_value );
			// Check if this group is the current view.
			if ( $this->store->get_view( $this->type ) ) {
				if ( (int) $this->store->get_view( $this->type ) === (int) $group->group_id ) {
					$class .= ' current';
					$href   = false;
				} else {
					$selected = $this->get_groups( $this->store->get_view( $this->type ) );
					if ( (int) $selected->parent_id === (int) $group->group_id ) {
						$class .= ' current-parent';
					}
				}
			}
			$parent = $root;
			if ( ! empty( $group->parent_id ) ) {
				$parent = $root . '-' . $this->type . '-' . (int) $group->parent_id;
			}
			$admin_bar->add_node( array(
				'id'     => $root . '-' . $this->type . '-' . (int) $group->group_id,
				'parent' => $parent,
				'title'  => $title,
				'href'   => $href,
				'meta'   => array(
					// Translators: %s stands for the view type name.
					'title' => sprintf( __( 'View as %s', VIEW_ADMIN_AS_DOMAIN ), $group->name ),
					'class' => $class,
				),
			) );
		}

		/**
		 * Add items at the end of the groups group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 */
		do_action( 'vaa_admin_bar_groups_after', $admin_bar, $root );
	}

	/**
	 * Store the available groups.
	 * @since   1.7.2
	 * @since   1.8.0  Renamed from `store_groups()`.
	 * @access  private
	 */
	public function store_data() {
		$groups = Groups_Group::get_groups();

		$data = array();
		if ( ! empty( $groups ) ) {
			foreach ( $groups as $group ) {
				$data[ $group->group_id ] = $group;
			}
		}
		$this->set_data( $data );
	}

	/**
	 * Get a group by ID.
	 *
	 * @since   1.7.2
	 * @access  public
	 * @param   string  $key  The group key.
	 * @return  \Groups_Group[]|\Groups_Group|bool
	 */
	public function get_groups( $key = '-1' ) {
		if ( ! is_numeric( $key ) ) {
			return false;
		}
		if ( '-1' === $key ) {
			$key = null;
		}
		return $this->get_data( $key );
	}

	/**
	 * Translate with another domain.
	 *
	 * @since   1.7.2
	 * @param   string  $string  The string.
	 * @return  string
	 */
	public function translate_remote( $string ) {
		$domain = ( defined( 'GROUPS_PLUGIN_DOMAIN' ) ) ? GROUPS_PLUGIN_DOMAIN : 'groups';
		// @codingStandardsIgnoreLine >> Prevent groups translation from getting parsed by translate.wordpress.org
		return __( $string, $domain );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.7.2
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class.
	 * @return  \VAA_View_Admin_As_Groups  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Groups.
