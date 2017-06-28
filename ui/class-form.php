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
	 * @param   string  $elem   (optional) HTML element type.
	 * @return  string
	 */
	public static function do_view_title( $title, $type, $value, $attr = array(), $elem = 'span' ) {
		$attr = (array) $attr;
		$class = ( ! empty( $attr['class'] ) ) ? ' ' . $attr['class'] : '';
		$attr['class'] = 'vaa-view-data' . $class;
		$attr['vaa-view-type'] = $type;
		$attr['vaa-view-value'] = $value;
		$attr = self::parse_to_html_attr( $attr );
		return '<' . $elem . ' ' . $attr . '>' . $title . '</' . $elem . '>';
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
	 *     @type  array   $auto_js  Optional. See VAA_View_Admin_As_Form::enable_auto_js().
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
	 *     @type  array   $auto_js      Optional. See VAA_View_Admin_As_Form::enable_auto_js().
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
	 *     @type  array   $auto_js         Optional. See VAA_View_Admin_As_Form::enable_auto_js().
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
	 * @param   array  $args {
	 *     Required. An array of arrays with field arguments.
	 *     @type  string  $name         Required.
	 *     @type  string  $id           Optional (Will be generated from $name if empty).
	 *     @type  string  $value        Optional.
	 *     @type  string  $description  Optional.
	 *     @type  array   $auto_js      Optional. See VAA_View_Admin_As_Form::enable_auto_js().
	 *     @type  bool    $auto_showhide_desc   Optional.
	 *     @type  array   $values {
	 *         Array of radio options data.
	 *         @type  array {
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
	public static function do_radio( $args ) {
		$html = '';

		if ( ! empty( $args['values'] ) ) {
			foreach ( $args['values'] as $val ) {

				$id = esc_attr( ( ( ! empty( $args['id'] ) ) ? $args['id'] : $args['name'] ) . '-' . $val['compare'] );
				$name = str_replace( '-', '_', esc_attr( $args['name'] ) );

				if ( empty( $args['value'] ) ) {
					$args['value'] = null;
				}
				$checked = checked( $args['value'], $val['compare'], false );
				$class = ( ! empty( $val['class'] ) ) ? ' ' . $val['class'] : '';
				$class .= ' ' . esc_attr( $args['name'] );

				$val['attr']['type'] = 'radio';
				$val['attr']['id'] = $id;
				$val['attr']['name'] = $name;
				$val['attr']['value'] = $val['compare'];
				$val['attr']['class'] = 'radio' . $class;

				$attr = $val['attr'];
				$attr = self::enable_auto_js( $attr, $args );
				$attr = self::parse_to_html_attr( $attr );

				$label_attr = array();
				$desc_attr = array();
				// Custom validation required.
				if ( ( ! empty( $val['auto_showhide_desc'] ) ) ||
					 ( ! isset( $val['auto_showhide_desc'] ) && ! empty( $args['auto_showhide_desc'] ) )
				) {
					self::enable_auto_showhide_desc( $id . '-desc', $label_attr, $desc_attr );
				}

				$html .= '<input ' . $attr . ' ' . $checked . '/>';
				$html .= self::do_label( $val, $id, $label_attr );
				$html .= '<br>';
				$html .= self::do_description( $val, $desc_attr );

			} // End foreach().

			$html .= self::do_description( $args );

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
	 * @param   array  $args {
	 *     Required. An array of arrays with field arguments.
	 *     @type  string  $name         Required.
	 *     @type  string  $id           Optional (Will be generated from $name if empty).
	 *     @type  string  $value        Optional.
	 *     @type  string  $label        Optional.
	 *     @type  string  $description  Optional.
	 *     @type  string  $class        Optional.
	 *     @type  array   $attr         Optional.
	 *     @type  array   $auto_js      Optional. See VAA_View_Admin_As_Form::enable_auto_js().
	 *     @type  bool    $auto_showhide_desc   Optional.
	 *     @type  array   $values {
	 *         Arrays of selectbox value data.
	 *         @type  array {
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
	public static function do_select( $args ) {
		$html = '';

		if ( ! empty( $args['values'] ) ) {
			$id = esc_attr( ( ! empty( $args['id'] ) ) ? $args['id'] : $args['name'] );
			$name = str_replace( '-', '_', esc_attr( $args['name'] ) );

			$label_attr = array();
			$desc_attr = array();
			self::enable_auto_showhide_desc( $id . '-desc', $label_attr, $desc_attr, $args );

			$html .= self::do_label( $args, $id, $label_attr );

			if ( empty( $args['value'] ) ) {
				$args['value'] = null;
			}

			$class = ( ! empty( $args['class'] ) ) ? ' ' . $args['class'] : '';

			$args['attr']['id'] = $id;
			$args['attr']['name'] = $name;
			$args['attr']['class'] = 'selectbox' . $class;

			$attr = $args['attr'];
			$attr = self::enable_auto_js( $attr, $args );
			$attr = self::parse_to_html_attr( $attr );

			$html .= '<select ' . $attr . '>';

			foreach ( $args['values'] as $val ) {

				if ( empty( $val['compare'] ) ) {
					$val['compare'] = ( ! empty( $val['value'] ) ) ? $val['value'] : false;
				}
				$label = ( ! empty( $val['label'] ) ) ? $val['label'] : $val['compare'];
				$selected = selected( $args['value'], $val['compare'], false );

				$val['attr']['value'] = $val['compare'];
				$attr = self::parse_to_html_attr( $val['attr'] );

				$html .= '<option ' . $attr . ' ' . $selected . '>' . $label . '</option>';

			}
			$html .= '</select>';

			$html .= self::do_description( $args, $desc_attr );

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
	 *
	 * @internal  Please do not use this yet since it's in development and subject to changes.
	 *
	 * @since   1.7.2
	 * @static
	 *
	 * @param   array  $attr  The attributes array to append to.
	 * @param   array  $args  {
	 *     The form element args.
	 *
	 *     @type  string  $setting  Required. The setting key.
	 *     @type  string  $confirm  Optional. Let JS generate a confirm box before running ajax?
	 *     @type  string  $refresh  Optional. Refresh after ajax return?
	 *     @type  string  $key      Optional (if values exists). The option key.
	 *     @type  array   $values {
	 *         The array of options. Alias: `value`.
	 *         All options need to be key => value pairs. See type documentation.
	 *         Recursive arrays supported (values in values).
	 *         If a key parameter exists this array will be added as the values of that key.
	 *
	 *         @type  bool    $required  Whether this option is required or not (default: true).
	 *         @type  string  $element   Optional. The HTML element to use as selector (overwrites current element).
	 *         @type  string  $attr      Get an attribute value instead of using .val()?
	 *         @type  bool    $json      Parse value as JSON? (Default parser only).
	 *         @type  string  $parser    Optional. The value processor.
	 *                                   `default` or empty : normal handling
	 *                                           (single checkbox or input/textarea value)
	 *                                   `multiple` or `multi` : Get multiple values.
	 *                                           (default: name => value | checkbox: value => checked)
	 *                                   `selected` : Get selected values only.
	 *                                           (default: non empty values | checkbox: values of checked elements)
	 *     }
	 * }
	 * @return  array
	 */
	public static function enable_auto_js( $attr, $args ) {
		if ( ! empty( $args['auto-js'] ) ) {

			// Auto-generate values array based upon key and value keys.
			if ( ! empty( $args['auto-js']['key'] ) ) {
				if ( empty( $args['auto-js']['values'] ) ) {
					// Single value data.
					$value = null;
					if ( ! empty( $args['auto-js']['value'] ) ) {
						$value = $args['auto-js']['value'];
					}
				} else {
					// Set the values as the values of the supplied key.
					$value = array( 'values' => $args['auto-js']['values'] );
				}
				$values = array( $args['auto-js']['key'] => $value );
				$args['auto-js']['values'] = $values;
			}
			unset( $args['auto-js']['key'] );
			unset( $args['auto-js']['value'] );

			$attr['vaa-auto-js'] = wp_json_encode( $args['auto-js'] );
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
		if ( ! empty( $args ) && empty( $args['auto_showhide_desc'] ) ) {
			return;
		}
		$label_attr = array(
			'class' => 'ab-vaa-showhide',
			'vaa-showhide' => '.' . $target,
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
