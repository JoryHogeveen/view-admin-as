<?php
/**
 * View Admin As - View Type
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * View Type class base.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.8
 * @version 1.8
 * @uses    \VAA_View_Admin_As_Base Extends class
 */
abstract class VAA_View_Admin_As_Type extends VAA_View_Admin_As_Base
{
	/**
	 * View type settings.
	 *
	 * @since  1.8
	 * @var    array
	 */
	private $settings = array(
		'enabled' => true,
	);

	/**
	 * The view type.
	 *
	 * @since  1.8
	 * @var    string
	 */
	protected $type = '';

	/**
	 * The view type label.
	 *
	 * @since  1.8
	 * @var    string
	 */
	protected $label = '';

	/**
	 * The view type singular label.
	 *
	 * @since  1.8
	 * @var    string
	 */
	protected $label_singular = '';

	/**
	 * The icon for this view type.
	 *
	 * @since  1.8
	 * @var    string
	 */
	protected $icon = '';

	/**
	 * Selected view.
	 *
	 * @since  1.8
	 * @var    mixed
	 */
	protected $selected = null;

	/**
	 * The hook priorities for this type.
	 *
	 * @since  1.8
	 * @var    int[]
	 */
	protected $priorities = array(
		'toolbar'            => 10,
		'view_title'         => 10,
		'validate_view_data' => 10,
		'update_view'        => 10,
		'do_view'            => 10,
	);

	/**
	 * The capability required for this view type.
	 *
	 * @since  1.8
	 * @var    string
	 */
	protected $cap = 'view_admin_as';

	/**
	 * Populate the instance.
	 *
	 * @since   1.8
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		parent::__construct( $vaa );

		$this->vaa->register_view_type( array(
			'id'       => $this->type,
			'instance' => $this,
		) );

		// @todo After init??
		if ( ! $this->is_vaa_enabled() || ! $this->current_user_can( $this->cap ) ) {
			return;
		}

		$this->add_action( 'vaa_view_admin_as_pre_init', array( $this, 'init' ) );
	}

	/**
	 * Setup module and hooks.
	 *
	 * @since   1.8
	 * @access  protected
	 * @return  bool  Successful init?
	 */
	public function init() {

		$this->store_data();

		if ( ! $this->get_data() ) {
			// No view data available.
			return false;
		}

		$this->init_hooks();

		return true;
	}

	/**
	 * Setup hooks.
	 *
	 * @since   1.8
	 * @access  protected
	 */
	protected function init_hooks() {

		$this->add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), $this->priorities['toolbar'], 2 );

		$this->add_filter( 'view_admin_as_view_types', array( $this, 'add_view_type' ) );

		$this->add_filter( 'view_admin_as_validate_view_data_' . $this->type, array( $this, 'validate_view_data' ), $this->priorities['validate_view_data'], 3 );
		$this->add_filter( 'view_admin_as_update_view_' . $this->type, array( $this, 'update_view' ), $this->priorities['update_view'], 3 );

		$this->add_action( 'vaa_view_admin_as_do_view', array( $this, 'do_view' ), $this->priorities['do_view'] );
	}

	/**
	 * Apply this view type if active.
	 *
	 * @since   1.8
	 * @access  public
	 * @return  bool  Is this view type active?
	 */
	public function do_view() {

		$this->selected = $this->store->get_view( $this->type );

		if ( $this->selected ) {

			$this->add_filter( 'vaa_admin_bar_view_titles', array( $this, 'view_title' ), $this->priorities['view_title'] );
			return true;
		}
		return false;
	}

	/**
	 * Helper method for the view object.
	 * Adds the actions and filters to modify the current user object.
	 * Can only be run once.
	 *
	 * @since   1.8
	 * @access  public
	 * @return  void
	 */
	public function init_user_modifications() {
		$this->vaa->view()->init_user_modifications();
	}

	/**
	 * Add view type.
	 *
	 * @since   1.8
	 * @param   string[]  $types  Existing view types.
	 * @return  string[]
	 */
	public function add_view_type( $types ) {
		$types[] = $this->type;
		return $types;
	}

	/**
	 * View update handler (Ajax probably), called from main handler.
	 *
	 * @since   1.8   Renamed from `ajax_handler`
	 * @access  public
	 * @param   null    $null    Null.
	 * @param   array   $data    The ajax data for this module.
	 * @param   string  $type    The view type.
	 * @return  bool
	 */
	public function update_view( $null, $data, $type = null ) {

		if ( $type !== $this->type ) {
			return $null;
		}

		if ( $this->get_data( $data ) ) {
			$this->store->set_view( $data, $this->type, true );
			return true;
		}
		return false;
	}

	/**
	 * Validate data for this view type
	 *
	 * @since   1.8
	 * @access  public
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	abstract public function validate_view_data( $null, $data = null );

	/**
	 * Change the VAA admin bar menu title.
	 *
	 * @since   1.8
	 * @access  public
	 * @param   array  $titles  The current title(s).
	 * @return  array
	 */
	abstract public function view_title( $titles = array() );

	/**
	 * Add the admin bar items.
	 *
	 * @since   1.8
	 * @access  public
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item.
	 */
	abstract public function admin_bar_menu( $admin_bar, $root );

	/**
	 * Store the available languages.
	 *
	 * @since   1.8
	 * @access  private
	 */
	abstract public function store_data();

	/**
	 * Set the view type data.
	 *
	 * @since   1.8
	 * @access  public
	 * @param   mixed   $val
	 * @param   string  $key     (optional) The data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 */
	public function set_data( $val, $key = null, $append = true ) {
		$this->store->set_data( $this->type, $val, $key, $append );
	}

	/**
	 * Get the view type data.
	 *
	 * @since   1.8
	 * @access  public
	 * @param   string  $key  (optional) The data key.
	 * @return  mixed
	 */
	public function get_data( $key = null ) {
		return $this->store->get_data( $this->type, $key );
	}

	/**
	 * Get the view type id.
	 *
	 * @since   1.8
	 * @access  public
	 * @return  string
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Get the view type label.
	 *
	 * @since   1.8
	 * @access  public
	 * @return  string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Get the view type singular label.
	 *
	 * @since   1.8
	 * @access  public
	 * @return  string
	 */
	public function get_label_singular() {
		return $this->label_singular;
	}

	/**
	 * Get the view type settings.
	 *
	 * @since   1.8
	 * @param   string  $key  Key in the setting array.
	 * @return  mixed
	 */
	final public function get_settings( $key = null ) {
		return VAA_API::get_array_data( $this->settings, $key );
	}

	/**
	 * Set the view type settings.
	 *
	 * @since   1.8
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  void
	 */
	final public function set_settings( $val, $key = null, $append = false ) {
		$this->settings = VAA_API::set_array_data( $this->settings, $val, $key, $append );

		$view_types = (array) $this->store->get_settings( 'view_types' );
		$view_types[ $this->type ] = $this->get_settings();
		$settings = array(
			'view_types' => $view_types,
		);
		$this->store->set_settings( $settings );
	}


	/**
	 * Set the view type settings.
	 * Also sets the settings within this instance and VAA store.
	 *
	 * @since   1.8
	 * @param   mixed   $val     Settings.
	 * @param   string  $key     (optional) Setting key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 * @return  bool
	 */
	final public function update_settings( $val, $key = null, $append = false ) {
		$this->set_settings( $val, $key, $append ); // Also updates store.
		return $this->store->update_optionData( $this->store->get_optionData() );
	}

} // End class VAA_View_Admin_As_Type.
