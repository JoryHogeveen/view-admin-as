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
 * @version 1.7.5
 * @uses    VAA_View_Admin_As_Base Extends class
 */
final class VAA_View_Admin_As_Languages extends VAA_View_Admin_As_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.7.5
	 * @static
	 * @var    VAA_View_Admin_As_Languages
	 */
	private static $_instance = null;

	/**
	 * The available languages.
	 *
	 * @since  1.7.5
	 * @var    array
	 */
	private $languages;

	/**
	 * Selected language locale.
	 *
	 * @since  1.7.5
	 * @var    string
	 */
	private $selectedLanguage;

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
	private $viewKey = 'locale';

	/**
	 * Populate the instance.
	 *
	 * @since   1.7.5
	 * @access  protected
	 * @param   VAA_View_Admin_As  $vaa  The main VAA object.
	 */
	protected function __construct( $vaa ) {
		self::$_instance = $this;
		parent::__construct( $vaa );

		if ( ! $this->vaa->is_enabled() ) {
			return;
		}

		$this->init();
	}

	/**
	 * Setup module and hooks.
	 *
	 * @since   1.7.5
	 * @access  private
	 */
	private function init() {

		$this->vaa->register_module( array(
			'id'       => $this->viewKey,
			'instance' => self::$_instance,
		) );

		$this->store_languages();

		if ( ! $this->get_languages() ) {
			// Only one language installed.
			return;
		}

		add_filter( 'view_admin_as_view_types', array( $this, 'add_view_type' ) );

		add_action( 'vaa_admin_bar_menu', array( $this, 'admin_bar_menu' ), 3, 2 );

		add_filter( 'view_admin_as_validate_view_data_' . $this->viewKey, array( $this, 'validate_view_data' ), 10, 2 );
		add_filter( 'view_admin_as_update_view_' . $this->viewKey, array( $this, 'update_view' ), 10, 3 );

		add_action( 'vaa_view_admin_as_do_view', array( $this, 'do_view' ) );
	}

	/**
	 * Apply the language view.
	 *
	 * @since   1.7.5
	 * @access  public
	 */
	public function do_view() {

		if ( $this->get_languages( $this->store->get_view( $this->viewKey ) ) ) {

			$this->selectedLanguage = $this->store->get_view( $this->viewKey );

			add_filter( 'vaa_admin_bar_view_titles', array( $this, 'vaa_admin_bar_view_titles' ) );

			add_filter( 'locale', array( $this, 'filter_locale' ) );
			add_action( 'after_setup_theme', array( $this, 'action_switch_to_locale' ), 0 );

			// Overwrite user setting for freeze locale.
			add_filter( 'view_admin_as_freeze_locale', '__return_false', 99 );
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
		return $this->selectedLanguage;
	}

	/**
	 * Change the site language.
	 *
	 * @since   1.7.5
	 * @access  public
	 */
	public function action_switch_to_locale() {
		if ( function_exists( 'switch_to_locale' ) ) {
			switch_to_locale( $this->selectedLanguage );
		}
	}

	/**
	 * Add view type.
	 *
	 * @since   1.7.5
	 * @param   string[]  $types  Existing view types.
	 * @return  string[]
	 */
	public function add_view_type( $types ) {
		$types[] = $this->viewKey;
		return $types;
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
		if ( is_string( $data ) && $this->get_languages( $data ) ) {
			return $data;
		}
		return $null;
	}

	/**
	 * View update handler (Ajax probably), called from main handler.
	 *
	 * @since   1.7.5   Renamed from `ajax_handler`
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

		if ( is_string( $data ) && $this->get_languages( $data ) ) {
			$this->store->set_view( $data, $this->viewKey, true );
			return true;
		}
		return false;
	}

	/**
	 * Change the VAA admin bar menu title.
	 *
	 * @since   1.7.5
	 * @access  public
	 * @param   array  $title  The current title(s).
	 * @return  array
	 */
	public function vaa_admin_bar_view_titles( $title = array() ) {
		$language = $this->get_languages( $this->selectedLanguage );
		if ( $language ) {
			$title[] = $language;
		}
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
		$root = $main_root . '-locale';

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
			'title'  => VAA_View_Admin_As_Form::do_icon( 'dashicons-translation' ) . __( 'Languages', VIEW_ADMIN_AS_DOMAIN ),
			'href'   => false,
			'meta'   => array(
				'class'    => 'vaa-has-icon ab-vaa-title' . ( ( $this->store->get_view( $this->viewKey ) ) ? ' current' : '' ),
				'tabindex' => '0',
			),
		) );

		$admin_bar->add_group( array(
			'id' => $root . '-languages',
			'parent' => $root . '-title',
			'meta'   => array(
				'class' => 'vaa-auto-max-height',
			),
		) );

		$parent = $root . '-languages';

		/**
		 * Add items at the beginning of the rua group.
		 *
		 * @see     'admin_bar_menu' action
		 * @link    https://codex.wordpress.org/Class_Reference/WP_Admin_Bar
		 * @param   \WP_Admin_Bar  $admin_bar   The toolbar object.
		 * @param   string         $root        The current root item.
		 */
		do_action( 'vaa_admin_bar_languages_before', $admin_bar, $root );

		// Add the levels.
		foreach ( $this->get_languages() as $locale => $language ) {
			$view_value = $locale;
			$view_data  = array( $this->viewKey => $view_value );
			$href  = VAA_API::get_vaa_action_link( $view_data, $this->store->get_nonce( true ) );
			$class = 'vaa-' . $this->viewKey . '-item';
			$title = ( $locale !== $language ) ? '<code>' . $locale . '</code> | ' . $language : $locale;
			$title = VAA_View_Admin_As_Form::do_view_title( $title, $this->viewKey, $view_value );
			// Check if this level is the current view.
			if ( $this->store->get_view( $this->viewKey ) ) {
				if ( VAA_API::is_current_view( $view_value, $this->viewKey ) ) {
					$class .= ' current';
					$href = false;
				}
			}
			//$parent = $root;
			$admin_bar->add_node( array(
				'id'        => $root . '-' . $this->viewKey . '-' . $view_value,
				'parent'    => $parent,
				'title'     => $title,
				'href'      => $href,
				'meta'      => array(
					// Translators: %s stands for the language name.
					'title'     => sprintf( __( 'View in %s', VIEW_ADMIN_AS_DOMAIN ), $language ),
					'class'     => $class,
					'rel'       => $view_value,
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
		do_action( 'vaa_admin_bar_languages_after', $admin_bar, $root );
	}

	/**
	 * Store the available languages.
	 *
	 * @since   1.7.5
	 * @access  private
	 */
	private function store_languages() {

		$installed = get_available_languages();

		if ( 1 === count( $installed ) ) {
			return;
		}

		$existing = (array) $this->store->get_optionData( $this->optionKey );
		$languages = $existing;

		if ( array_diff_key( array_flip( $installed ), $existing ) ) {
			// New languages detected. Call the WP API to get language info.
			$languages = $this->get_wp_languages( $languages );
		}

		$this->languages['en_US'] = 'English';

		// Same order as WordPress.
		sort( $installed );

		foreach ( $installed as $locale ) {
			if ( array_key_exists( $locale, $languages ) ) {
				$this->languages[ $locale ] = $languages[ $locale ];
			}
		}

		if ( $languages !== $existing ) {
			$this->store->update_optionData( $this->languages, $this->optionKey, true );
		}
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
	 * Get a language by locale.
	 *
	 * @since   1.7.5
	 * @access  public
	 * @param   string  $key  (optional) The language locale.
	 * @return  mixed
	 */
	public function get_languages( $key = '-1' ) {
		if ( ! is_string( $key ) ) {
			return false;
		}
		if ( '-1' === $key ) {
			$key = null;
		}
		return VAA_API::get_array_data( $this->languages, $key );
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.7.5
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class.
	 * @return  $this  VAA_View_Admin_As_Languages
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Languages.
