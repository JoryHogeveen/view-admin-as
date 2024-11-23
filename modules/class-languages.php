<?php
/**
 * View Admin As - Language switcher
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Language switcher add-on.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.7.5
 * @version 1.8.7
 * @uses    \VAA_View_Admin_As_Type Extends class
 */
class VAA_View_Admin_As_Languages extends VAA_View_Admin_As_Type
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.7.5
	 * @static
	 * @var    \VAA_View_Admin_As_Languages
	 */
	private static $_instance = null;

	/**
	 * Option key.
	 *
	 * @since  1.7.5
	 * @var    string
	 */
	protected $optionKey = 'languages';

	/**
	 * @since  1.7.5
	 * @var    string
	 */
	protected $type = 'locale';

	/**
	 * The icon for this view type.
	 *
	 * @since  1.8.0
	 * @var    string
	 */
	protected $icon = 'dashicons-translation';

	/**
	 * Populate the instance.
	 *
	 * @since   1.7.5
	 * @access  protected
	 * @param   \VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( ! $this->has_access() ) {
			return;
		}

		$this->priorities = array(
			'toolbar'            => 9,
			'view_title'         => 90,
			'validate_view_data' => 10,
			'update_view'        => 10,
			'do_view'            => 10,
		);
	}

	/**
	 * @inheritDoc
	 */
	public function init() {
		$this->label          = __( 'Languages', VIEW_ADMIN_AS_DOMAIN );
		$this->label_singular = __( 'Language', VIEW_ADMIN_AS_DOMAIN );
		return parent::init();
	}

	/**
	 * Apply the language view.
	 *
	 * @since   1.7.5
	 * @access  public
	 */
	public function do_view() {

		if ( parent::do_view() ) {

			$this->add_filter( 'locale', array( $this, 'filter_locale' ) );
			$this->add_action( 'after_setup_theme', array( $this, 'action_switch_to_locale' ), 0 );

			// Overwrite user setting for freeze locale.
			$this->add_filter( 'view_admin_as_freeze_locale', '__return_false', 99 );
		}
	}

	/**
	 * Change the site language.
	 *
	 * @since   1.7.5
	 * @access  public
	 * param   string  $locale
	 * @return  string
	 */
	public function filter_locale() {
		return $this->selected;
	}

	/**
	 * Change the site language.
	 *
	 * @since   1.7.5
	 * @access  public
	 */
	public function action_switch_to_locale() {
		if ( function_exists( 'switch_to_locale' ) ) {
			switch_to_locale( $this->selected );
		}
	}

	/**
	 * Validate data for this view type
	 *
	 * @since   1.7.5
	 * @param   null   $null  Default return (invalid)
	 * @param   mixed  $data  The view data
	 * @return  mixed
	 */
	public function validate_view_data( $null, $data = null ) {
		if ( is_string( $data ) && $this->get_data( $data ) ) {
			return $data;
		}
		return $null;
	}

	/**
	 * Update the view titles if this view is selected.
	 *
	 * @since   1.7.5
	 * @since   1.8.0  Renamed from `vaa_admin_bar_view_titles()`.
	 * @since   1.8.7  Added second required `$view` param.
	 * @access  public
	 * @param   array  $titles  The current title(s).
	 * @param   array  $view    View data.
	 * @return  array
	 */
	public function view_title( $titles, $view ) {
		if ( isset( $view[ $this->type ] ) ) {
			$title = $this->get_view_title( $view[ $this->type ] );
			if ( $title ) {
				$titles[ /* No need for view type key. */ ] = $title;
			}
		}
		return $titles;
	}

	/**
	 * Get the view title.
	 *
	 * @since   1.8.0
	 * @param   string  $key  The locale.
	 * @return  string
	 */
	public function get_view_title( $key ) {
		$title = $this->get_data( $key );

		/**
		 * Change the display title for language nodes.
		 *
		 * @since  1.8.0
		 * @param  string  $title  Language (native).
		 * @param  string  $key    The locale.
		 * @return string
		 */
		$title = apply_filters( 'vaa_admin_bar_view_title_' . $this->type, $title, $key );

		return $title;
	}

	/**
	 * Add the admin bar items.
	 *
	 * @since   1.7.5
	 * @access  public
	 * @param   \WP_Admin_Bar  $admin_bar  The toolbar object.
	 * @param   string         $root       The root item.
	 */
	public function admin_bar_menu( $admin_bar, $root ) {
		static $done;
		if ( $done ) return;

		$main_root = $root;
		$root      = $main_root . '-locale';

		$admin_bar->add_group( array(
			'id'     => $root,
			'parent' => $main_root,
			'meta'   => array(
				'class' => 'ab-sub-secondary',
			),
		) );

		$admin_bar->add_node( array(
			'id'     => $root . '-title',
			'parent' => $root,
			'title'  => VAA_View_Admin_As_Form::do_icon( $this->icon ) . $this->label,
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title' . ( ( $this->store->get_view( $this->type ) ) ? ' current' : '' ),
				'tabindex' => '0',
			),
		) );

		$admin_bar->add_group( array(
			'id'     => $root . '-languages',
			'parent' => $root . '-title',
			'meta'   => array(
				'class' => 'vaa-auto-max-height',
			),
		) );

		/**
		 * Add items at the beginning of the rua group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 */
		$this->do_action( 'vaa_admin_bar_languages_before', $admin_bar, $root );

		// Add the levels.
		include VIEW_ADMIN_AS_DIR . 'ui/templates/adminbar-language-items.php';

		/**
		 * Add items at the end of the rua group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 */
		$this->do_action( 'vaa_admin_bar_languages_after', $admin_bar, $root );
	}

	/**
	 * Store the available languages.
	 *
	 * @since   1.7.5
	 * @since   1.8.0  Renamed from `store_languages()`.
	 * @access  public
	 */
	public function store_data() {

		$installed = get_available_languages();

		if ( ! $installed || ( 1 === count( $installed ) && 'en_US' === reset( $installed ) ) ) {
			return;
		}

		$existing  = (array) $this->store->get_optionData( $this->optionKey );
		$languages = $existing;

		if ( array_diff_key( array_flip( $installed ), $existing ) ) {
			// New languages detected. Call the WP API to get language info.
			$languages = $this->get_wp_languages( $languages );
		}

		$data_languages['en_US'] = 'English';

		// Same order as WordPress.
		sort( $installed );

		foreach ( $installed as $locale ) {
			if ( array_key_exists( $locale, $languages ) ) {
				$data_languages[ $locale ] = $languages[ $locale ];
			}
		}

		if ( $languages !== $existing ) {
			$this->store->update_optionData( $data_languages, $this->optionKey, true );
		}

		$this->set_data( $data_languages );
	}

	/**
	 * Call the WP API to get language info.
	 *
	 * @since   1.7.5
	 * @param   array  $languages  Existing languages.
	 * @return  array
	 */
	private function get_wp_languages( $languages ) {
		if ( ! file_exists( ABSPATH . 'wp-admin/includes/translation-install.php' ) ) {
			// @todo Notice on debug.
			return $languages;
		}
		require_once ABSPATH . 'wp-admin/includes/translation-install.php';

		if ( ! function_exists( 'wp_get_available_translations' ) ) {
			return $languages;
		}
		$wp_languages = wp_get_available_translations();

		if ( ! $wp_languages ) {
			return $languages;
		}

		foreach ( $wp_languages as $locale => $language_info ) {
			$name = $locale;
			if ( isset( $language_info['native_name'] ) ) {
				$name = $language_info['native_name'];
			}
			$languages[ $locale ] = $name;
		}

		return $languages;
	}

	/**
	 * Set the view type data.
	 *
	 * @since   1.8.0
	 * @access  public
	 * @param   mixed   $val
	 * @param   string  $key     (optional) The data key.
	 * @param   bool    $append  (optional) Append if it doesn't exist?
	 */
	public function set_data( $val, $key = null, $append = true ) {
		$this->store->set_languages( $val, $key, $append );
	}

	/**
	 * Get a language by locale.
	 *
	 * @since   1.7.5
	 * @since   1.8.0  Renamed from `get_languages()`.
	 * @access  public
	 * @param   string  $key  (optional) The language locale.
	 * @return  mixed
	 */
	public function get_data( $key = '-1' ) {
		if ( ! is_string( $key ) ) {
			return false;
		}
		if ( '-1' === $key ) {
			$key = null;
		}
		return $this->store->get_languages( $key );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.7.5
	 * @access  public
	 * @static
	 * @param   \VAA_View_Admin_As  $caller  The referrer class.
	 * @return  \VAA_View_Admin_As_Languages  $this
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Languages.
