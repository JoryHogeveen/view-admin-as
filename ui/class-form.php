<?php
/**
 * View Admin As - Form UI
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

/**
 * Form UI for View Admin As.
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   1.7.2
 * @version 1.7.2
 * @uses    VAA_View_Admin_As_Class_Base Extends class
 */
class VAA_View_Admin_As_Form extends VAA_View_Admin_As_Class_Base
{
	/**
	 * The single instance of the class.
	 *
	 * @since  1.7.2
	 * @static
	 * @var    VAA_View_Admin_As_Form
	 */
	private static $_instance = null;

	/**
	 * Generate a view type title and it's view related data.
	 * The data is used in javascript to switch a view.
	 *
	 * @since   1.7
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @access  public
	 * @static
	 * @param   string  $title  The title content.
	 * @param   string  $type   The view type.
	 * @param   string  $value  The view value.
	 * @param   array   $attr   (optional) Array of other attributes.
	 * @return  string
	 */
	public static function do_view_title( $title, $type, $value, $attr = array() ) {
		$attr = (array) $attr;
		$class = ( ! empty( $attr['class'] ) ) ? ' ' . $attr['class'] : '';
		$attr['class'] = 'vaa-view-data' . $class;
		$attr['data-view-type'] = $type;
		$attr['data-view-value'] = $value;
		$attr = self::parse_to_html_attr( $attr );
		return '<span ' . $attr . '>' . $title . '</span>';
	}

	/**
	 * Generate button HTML for node.
	 *
	 * @since   1.6.1
	 * @since   1.6.2  Added $element option.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @access  public
	 * @static
	 * @param   array  $args {
	 *     Required. An array of field arguments.
	 *     @type  string  $name     Required.
	 *     @type  string  $id       Optional (Will be generated from $name if empty).
	 *     @type  string  $label    Optional.
	 *     @type  string  $class    Optional.
	 *     @type  string  $element  Optional.
	 *     @type  array   $attr     Optional.
	 * }
	 * @return  string
	 */
	public static function do_button( $args ) {
		$id = esc_attr( ( ! empty( $args['id'] ) ) ? $args['id'] : $args['name'] );
		$name = str_replace( '-', '_', esc_attr( $args['name'] ) );
		$elem = ( ! empty( $args['element'] ) ) ? $args['element'] : 'button';
		$label = ( ! empty( $args['label'] ) ) ? $args['label'] : '';
		$class = ( ( ! empty( $args['class'] ) ) ? ' ' . $args['class'] : '' );

		$args['attr']['id'] = $id;
		$args['attr']['name'] = $name;
		$args['attr']['class'] = 'button' . $class;

		$attr = $args['attr'];
		if ( ! empty( $args['auto-js'] ) && empty( $args['auto-js']['event'] ) ) {
			$args['auto-js']['event'] = 'click';
		}
		$attr = self::enable_auto_js( $attr, $args );
		$attr = self::parse_to_html_attr( $attr );

		return '<' . $elem . ' ' . $attr . '>' . $label . '</' . $elem . '>';
	}

	/**
	 * Generate text input HTML for node.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Automatic show/hide description option.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @access  public
	 * @static
	 * @param   array  $args {
	 *     Required. An array of field arguments.
	 *     @type  string  $name         Required.
	 *     @type  string  $id           Optional (Will be generated from $name if empty).
	 *     @type  string  $placeholder  Optional.
	 *     @type  string  $default      Optional.
	 *     @type  string  $value        Optional.
	 *     @type  string  $label        Optional.
	 *     @type  string  $description  Optional.
	 *     @type  string  $class        Optional.
	 *     @type  array   $attr         Optional.
	 *     @type  bool    $auto_showhide_desc  Optional.
	 * }
	 * @return  string
	 */
	public static function do_input( $args ) {
		$html = '';

		$id = esc_attr( ( ! empty( $args['id'] ) ) ? $args['id'] : $args['name'] );
		$name = str_replace( '-', '_', esc_attr( $args['name'] ) );
		$default = ( ! empty( $args['default'] ) ) ? $args['default'] : '';
		$placeholder = ( ! empty( $args['placeholder'] ) ) ? $args['placeholder'] : '';
		$class = ( ! empty( $args['class'] ) ) ? $args['class'] : '';

		$args['attr']['type'] = 'text';
		$args['attr']['id'] = $id;
		$args['attr']['name'] = $name;
		$args['attr']['placeholder'] = $placeholder;
		$args['attr']['value'] = ( ! empty( $args['value'] ) ) ? $args['value'] : $default;
		$args['attr']['class'] = $class;

		$attr = $args['attr'];
		$attr = self::enable_auto_js( $attr, $args );
		$attr = self::parse_to_html_attr( $attr );

		$label_attr = array();
		$desc_attr = array();
		self::enable_auto_showhide_desc( $id . '-desc', $label_attr, $desc_attr, $args );

		$html .= self::do_label( $args, $id, $label_attr );
		$html .= '<input ' . $attr . '/>';
		$html .= self::do_description( $args, $desc_attr );
		return $html;
	}

	/**
	 * Generate checkbox HTML for node.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Automatic show/hide description option + removable option.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @access  public
	 * @static
	 * @param   array  $args {
	 *     Required. An array of field arguments.
	 *     @type  string  $name            Required.
	 *     @type  string  $id              Optional (Will be generated from $name if empty).
	 *     @type  string  $compare         Optional.
	 *     @type  string  $value           Optional.
	 *     @type  string  $checkbox_value  Optional  (default: 1).
	 *     @type  string  $label           Optional.
	 *     @type  string  $description     Optional.
	 *     @type  string  $class           Optional.
	 *     @type  array   $attr            Optional.
	 *     @type  bool    $auto_showhide_desc   Optional.
	 *     @type  bool    $removable       Optional.
	 * }
	 * @return  string
	 */
	public static function do_checkbox( $args ) {
		$html = '';

		$id = esc_attr( ( ! empty( $args['id'] ) ) ? $args['id'] : $args['name'] );
		$name = str_replace( '-', '_', esc_attr( $args['name'] ) );

		if ( empty( $args['value'] ) ) {
			$args['value'] = null;
		}
		if ( empty( $args['compare'] ) ) {
			$args['compare'] = 1;
		}
		$checked = checked( $args['value'], $args['compare'], false );
		$class = ( ! empty( $args['class'] ) ) ? ' ' . $args['class'] : '';

		$args['attr']['type'] = 'checkbox';
		$args['attr']['id'] = $id;
		$args['attr']['name'] = $name;
		$args['attr']['value'] = ( ! empty( $args['checkbox_value'] ) ) ? $args['checkbox_value'] : '1';
		$args['attr']['class'] = 'checkbox' . $class;

		$attr = $args['attr'];
		$attr = self::enable_auto_js( $attr, $args );
		$attr = self::parse_to_html_attr( $attr );

		$label_attr = array();
		$desc_attr = array();
		self::enable_auto_showhide_desc( $id . '-desc', $label_attr, $desc_attr, $args );

		$html .= '<input ' . $attr . ' ' . $checked . '/>';
		$html .= self::do_label( $args, $id, $label_attr );

		if ( ! empty( $args['removable'] ) ) {
			$html .= self::do_icon( 'dashicons-dismiss remove', array( 'title' => __( 'Remove', VIEW_ADMIN_AS_DOMAIN ) ) );
		}

		$html .= self::do_description( $args, $desc_attr );
		return $html;
	}

	/**
	 * Generate radio HTML for node.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Automatic show/hide description option.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @access  public
	 * @static
	 * @param   array  $data {
	 *     Required. An array of arrays with field arguments.
	 *     @type  string  $name         Required.
	 *     @type  string  $id           Optional (Will be generated from $name if empty).
	 *     @type  string  $value        Optional.
	 *     @type  string  $description  Optional.
	 *     @type  bool    $auto_showhide_desc   Optional.
	 *     @type  array   $values {
	 *         Array of radio options data.
	 *         @type  array  $args {
	 *             @type  string  $compare      Required.
	 *             @type  string  $label        Optional.
	 *             @type  string  $description  Optional.
	 *             @type  string  $class        Optional.
	 *             @type  array   $attr         Optional.
	 *             @type  bool    $auto_showhide_desc   Optional  (overwrite $data).
	 *         }
	 *     }
	 * }
	 * @return  string
	 */
	public static function do_radio( $data ) {
		$html = '';

		if ( ! empty( $data['values'] ) ) {
			foreach ( $data['values'] as $args ) {

				$id = esc_attr( ( ( ! empty( $data['id'] ) ) ? $data['id'] : $data['name'] ) . '-' . $args['compare'] );
				$name = str_replace( '-', '_', esc_attr( $data['name'] ) );

				if ( empty( $data['value'] ) ) {
					$data['value'] = null;
				}
				$checked = checked( $data['value'], $args['compare'], false );
				$class = ( ! empty( $args['class'] ) ) ? ' ' . $args['class'] : '';
				$class .= ' ' . esc_attr( $data['name'] );

				$args['attr']['type'] = 'radio';
				$args['attr']['id'] = $id;
				$args['attr']['name'] = $name;
				$args['attr']['value'] = $args['compare'];
				$args['attr']['class'] = 'radio' . $class;

				$attr = $args['attr'];
				$attr = self::enable_auto_js( $attr, $args );
				$attr = self::parse_to_html_attr( $attr );

				$label_attr = array();
				$desc_attr = array();
				// Custom validation required.
				if ( ( ! empty( $args['auto_showhide_desc'] ) ) ||
					 ( ! isset( $args['auto_showhide_desc'] ) && ! empty( $data['auto_showhide_desc'] ) )
				) {
					self::enable_auto_showhide_desc( $id . '-desc', $label_attr, $desc_attr );
				}

				$html .= '<input ' . $attr . ' ' . $checked . '/>';
				$html .= self::do_label( $args, $id, $label_attr );
				$html .= '<br>';
				$html .= self::do_description( $args, $desc_attr );

			} // End foreach().

			$html .= self::do_description( $data );

		} // End if().
		return $html;
	}

	/**
	 * Generate selectbox HTML for node.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Automatic show/hide description option.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @access  public
	 * @static
	 * @param   array  $data {
	 *     Required. An array of arrays with field arguments.
	 *     @type  string  $name         Required.
	 *     @type  string  $id           Optional (Will be generated from $name if empty).
	 *     @type  string  $value        Optional.
	 *     @type  string  $label        Optional.
	 *     @type  string  $description  Optional.
	 *     @type  string  $class        Optional.
	 *     @type  array   $attr         Optional.
	 *     @type  bool    $auto_showhide_desc   Optional.
	 *     @type  array   $values {
	 *         Arrays of selectbox value data.
	 *         @type  array  $args {
	 *             @type  string  $compare  Required.
	 *             @type  string  $value    Optional  (Alias for compare).
	 *             @type  string  $label    Optional.
	 *             @type  string  $class    Optional.
	 *             @type  array   $attr     Optional.
	 *         }
	 *     }
	 * }
	 * @return  string
	 */
	public static function do_select( $data ) {
		$html = '';

		if ( ! empty( $data['values'] ) ) {
			$id = esc_attr( ( ! empty( $data['id'] ) ) ? $data['id'] : $data['name'] );
			$name = str_replace( '-', '_', esc_attr( $data['name'] ) );

			$label_attr = array();
			$desc_attr = array();
			self::enable_auto_showhide_desc( $id . '-desc', $label_attr, $desc_attr, $data );

			$html .= self::do_label( $data, $id, $label_attr );

			if ( empty( $data['value'] ) ) {
				$data['value'] = null;
			}

			$class = ( ! empty( $data['class'] ) ) ? ' ' . $data['class'] : '';

			$data['attr']['id'] = $id;
			$data['attr']['name'] = $name;
			$data['attr']['class'] = 'selectbox' . $class;

			$attr = $data['attr'];
			$attr = self::enable_auto_js( $attr, $data );
			$attr = self::parse_to_html_attr( $attr );

			$html .= '<select ' . $attr . '>';

			foreach ( $data['values'] as $args ) {

				if ( empty( $args['compare'] ) ) {
					$args['compare'] = ( ! empty( $args['value'] ) ) ? $args['value'] : false;
				}
				$label = ( ! empty( $args['label'] ) ) ? $args['label'] : $args['compare'];
				$selected = selected( $data['value'], $args['compare'], false );

				$args['attr']['value'] = $args['compare'];
				$attr = self::parse_to_html_attr( $args['attr'] );

				$html .= '<option ' . $attr . ' ' . $selected . '>' . $label . '</option>';

			}
			$html .= '</select>';

			$html .= self::do_description( $data, $desc_attr );

		} // End if().
		return $html;
	}

	/**
	 * Returns icon html for WP admin bar.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Added second $attr parameter.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @static
	 * @param   string  $icon  The icon class.
	 * @param   array   $attr  Extra attributes.
	 * @return  string
	 */
	public static function do_icon( $icon, $attr = array() ) {
		$attr['class'] = 'ab-icon dashicons ' . $icon;
		$attr['aria-hidden'] = 'true';
		$attr = self::parse_to_html_attr( $attr );
		return '<span ' . $attr . '></span>';
	}

	/**
	 * Returns label html for WP admin bar.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Added third $attr parameter.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @static
	 * @param   string|array  $label  The label. (Also accepts an array with a `label` key)
	 * @param   string        $for    (optional) Add for attribute.
	 * @param   array         $attr   Extra attributes.
	 * @return  string
	 */
	public static function do_label( $label, $for = '', $attr = array() ) {
		if ( is_array( $label ) ) {
			if ( empty( $label['label'] ) ) {
				return '';
			}
			$label = $label['label'];
		}
		$attr['for'] = $for;
		$attr = self::parse_to_html_attr( $attr );
		return '<label ' . $attr . '>' . $label . '</label>';
	}

	/**
	 * Returns description html for WP admin bar.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Added second $attr parameter.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @static
	 * @param   string|array  $text  The description text. (Also accepts an array with a `description` key)
	 * @param   array         $attr  Extra attributes.
	 * @return  string
	 */
	public static function do_description( $text, $attr = array() ) {
		if ( is_array( $text ) ) {
			if ( empty( $text['description'] ) ) {
				return '';
			}
			$text = $text['description'];
		}
		$attr['class'] = 'ab-item description' . ( ( ! empty( $attr['class'] ) ) ? ' ' . $attr['class'] : '');
		$attr = self::parse_to_html_attr( $attr );
		return '<p ' . $attr . '>' . $text . '</p>';
	}

	/**
	 * Auto-generate a JSON attribute for automatic JS handling.
	 * @internal  Please do not use this yet since it's in development and subject to changes.
	 *
	 * @since   1.7.2
	 * @static
	 * @param   array  $args  The form element args.
	 * @param   array  $attr  The attributes array to append to.
	 * @return  array
	 */
	public static function enable_auto_js( $attr, $args, $multi = false ) {
		if ( ! empty( $args['auto-js'] ) ) {
			$key = 'vaa-auto-js';
			if ( $multi ) {
				$key .= '-multi';
			}
			$attr[ $key ] = wp_json_encode( $args['auto-js'] );
		}
		return $attr;
	}

	/**
	 * Update label and description attributes to enable auto show/hide functionality
	 *
	 * @since   1.7
	 * @since   1.7.2   Moved to this class from admin bar class.
	 * @static
	 * @param   string  $target      The target element.
	 * @param   array   $label_attr  Label attributes.
	 * @param   array   $desc_attr   Description attributes.
	 * @param   array   $args        (optional) Pass the full arguments array for auto_show_hide key validation.
	 */
	public static function enable_auto_showhide_desc( $target, &$label_attr = array(), &$desc_attr = array(), $args = array() ) {
		if ( ! empty( $args ) && empty( $data['auto_showhide_desc'] ) ) {
			return;
		}
		$label_attr = array(
			'class' => 'ab-vaa-showhide',
			'data-showhide' => '.' . $target,
		);
		$desc_attr = array( 'class' => $target );
	}

	/**
	 * Converts an array of attributes to a HTML string format starting with a space.
	 *
	 * @static
	 * @since   1.6.1
	 * @since   1.7     Renamed from `parse_attr_to_html`
	 * @since   1.7.2   Support array values. (Example: CSS classes). Moved to this class from admin bar class.
	 * @param   array   $array  Array to parse. (attribute => value pairs)
	 * @return  string
	 */
	public static function parse_to_html_attr( $array ) {
		$str = '';
		if ( is_array( $array ) && ! empty( $array ) ) {
			foreach ( $array as $attr => $value ) {
				if ( is_array( $value ) ) {
					$value = implode( ' ', $value );
				}
				$array[ $attr ] = esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
			}
			$str = implode( ' ', $array );
		}
		return $str;
	}

	/**
	 * Main Instance.
	 *
	 * Ensures only one instance of this class is loaded or can be loaded.
	 *
	 * @since   1.7.2
	 * @access  public
	 * @static
	 * @param   VAA_View_Admin_As  $caller  The referrer class
	 * @return  VAA_View_Admin_As_Form
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Form.
