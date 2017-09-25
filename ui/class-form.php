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
 * @version 1.7.4
 * @uses    VAA_View_Admin_As_Base Extends class
 */
class VAA_View_Admin_As_Form extends VAA_View_Admin_As_Base
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
	 *
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
	 *
	 * @param   array  $args {
	 *     (required) An array of field arguments.
	 *     @type  string  $name     (required)
	 *     @type  string  $id       (optional) Will be generated from $name if empty.
	 *     @type  string  $label    (optional)
	 *     @type  string  $class    (optional)
	 *     @type  string  $element  (optional)
	 *     @type  array   $attr     (optional)
	 *     @type  array   $auto_js  (optional) See VAA_View_Admin_As_Form::enable_auto_js().
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
		if ( ! empty( $args['auto_js'] ) && empty( $args['auto_js']['event'] ) ) {
			$args['auto_js']['event'] = 'click';
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
	 *
	 * @param   array  $args {
	 *     (required) An array of field arguments.
	 *     @type  string  $name           (required)
	 *     @type  string  $id             (optional) Will be generated from $name if empty.
	 *     @type  string  $placeholder    (optional)
	 *     @type  string  $default        (optional)
	 *     @type  string  $value          (optional)
	 *     @type  string  $label          (optional)
	 *     @type  string  $description    (optional)
	 *     @type  string  $help           (optional)
	 *     @type  string  $class          (optional)
	 *     @type  array   $type           (optional) Optional input type attribute.
	 *     @type  array   $attr           (optional)
	 *     @type  array   $auto_js        (optional) See VAA_View_Admin_As_Form::enable_auto_js().
	 *     @type  bool    $auto_showhide  (optional) Pass `true` or int for auto show/hide description. Integer stands for the delay (default: 200).
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

		$args['attr']['type'] = ( ! empty( $args['type'] ) ) ? $args['type'] : 'text';
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
		self::enable_auto_showhide( $id . '-desc', $label_attr, $desc_attr, $args );

		$html .= self::do_help( $args, array(), array(), $label_attr );
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
	 *
	 * @param   array  $args {
	 *     (required) An array of field arguments.
	 *     @type  string  $name            (required)
	 *     @type  string  $id              (optional) Will be generated from $name if empty.
	 *     @type  string  $compare         (optional)
	 *     @type  string  $value           (optional)
	 *     @type  string  $checkbox_value  (optional) Default: 1.
	 *     @type  string  $label           (optional)
	 *     @type  string  $description     (optional)
	 *     @type  string  $help            (optional)
	 *     @type  string  $class           (optional)
	 *     @type  array   $attr            (optional)
	 *     @type  array   $auto_js         (optional) See VAA_View_Admin_As_Form::enable_auto_js().
	 *     @type  bool    $auto_showhide   (optional) Pass `true` or int for auto show/hide description. Integer stands for the delay (default: 200).
	 *     @type  bool    $removable       (optional)
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
		self::enable_auto_showhide( $id . '-desc', $label_attr, $desc_attr, $args );

		$html .= self::do_help( $args, array(), array(), $label_attr );
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
	 *
	 * @param   array  $args {
	 *     (required) An array of arrays with field arguments.
	 *     @type  string  $name           (required)
	 *     @type  string  $id             (optional) Will be generated from $name if empty.
	 *     @type  string  $value          (optional)
	 *     @type  string  $description    (optional)
	 *     @type  array   $auto_js        (optional) See VAA_View_Admin_As_Form::enable_auto_js().
	 *     @type  bool    $auto_showhide  (optional) Pass `true` or int for auto show/hide description. Integer stands for the delay (default: 200).
	 *     @type  array   $values {
	 *         Array of radio options data.
	 *         @type  array {
	 *             @type  string  $compare        (required)
	 *             @type  string  $label          (optional)
	 *             @type  string  $description    (optional)
	 *             @type  string  $help           (optional)
	 *             @type  string  $class          (optional)
	 *             @type  array   $attr           (optional)
	 *             @type  bool    $auto_showhide  (optional) Overwrite $data.
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
				if ( ( ! empty( $val['auto_showhide'] ) ) ||
					 ( ! isset( $val['auto_showhide'] ) && ! empty( $args['auto_showhide'] ) )
				) {
					self::enable_auto_showhide( $id . '-desc', $label_attr, $desc_attr );
				}

				$html .= '<div class="vaa-radio-wrapper">';
				$html .= self::do_help( $val, array(), array(), $label_attr );
				$html .= '<input ' . $attr . ' ' . $checked . '/>';
				$html .= self::do_label( $val, $id, $label_attr );
				$html .= '<br>';
				$html .= self::do_description( $val, $desc_attr );
				$html .= '</div>';

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
	 *
	 * @param   array  $args {
	 *     (required) An array of arrays with field arguments.
	 *     @type  string  $name           (required)
	 *     @type  string  $id             (optional) Will be generated from $name if empty.
	 *     @type  string  $value          (optional)
	 *     @type  string  $label          (optional)
	 *     @type  string  $description    (optional)
	 *     @type  string  $help           (optional)
	 *     @type  string  $class          (optional)
	 *     @type  array   $attr           (optional)
	 *     @type  array   $auto_js        (optional) See VAA_View_Admin_As_Form::enable_auto_js().
	 *     @type  bool    $auto_showhide  (optional) Pass `true` or int for auto show/hide description. Integer stands for the delay (default: 200).
	 *     @type  array   $values {
	 *         Arrays of selectbox value data.
	 *         @type  array {
	 *             @type  string  $compare  (required)
	 *             @type  string  $value    (optional) Alias for compare.
	 *             @type  string  $label    (optional)
	 *             @type  string  $class    (optional)
	 *             @type  array   $attr     (optional)
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
			self::enable_auto_showhide( $id . '-desc', $label_attr, $desc_attr, $args );

			$html .= self::do_help( $args, array(), array(), $label_attr );
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
	 * @since   1.7.3  Added third $content parameter.
	 * @static
	 *
	 * @param   string  $icon     The icon class.
	 * @param   array   $attr     (optional) Extra attributes.
	 * @param   string  $content  (optional) Icon content.
	 * @return  string
	 */
	public static function do_icon( $icon, $attr = array(), $content = '' ) {
		if ( ! empty( $attr['class'] ) ) {
			$icon .= ' ' . (string) $attr['class'];
		}
		$attr['class'] = 'ab-icon dashicons ' . $icon;
		$attr['aria-hidden'] = 'true';
		$attr = self::parse_to_html_attr( $attr );
		return '<span ' . $attr . '>' . $content . '</span>';
	}

	/**
	 * Returns label html for WP admin bar.
	 *
	 * @since   1.6.1
	 * @since   1.6.3  Added third $attr parameter.
	 * @since   1.7.2  Moved to this class from admin bar class.
	 * @static
	 *
	 * @param   string|array  $label  The label. Also accepts an array with a `label` key.
	 * @param   string        $for    (optional) Add `for` attribute.
	 * @param   array         $attr   (optional) Extra attributes.
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
	 *
	 * @param   string|array  $text  The description text. Also accepts an array with a `description` key.
	 * @param   array         $attr  (optional) Extra attributes.
	 * @return  string
	 */
	public static function do_description( $text, $attr = array() ) {
		if ( is_array( $text ) ) {
			if ( empty( $text['description'] ) ) {
				return '';
			}
			$text = $text['description'];
		} elseif ( ! is_string( $text ) ) {
			return '';
		}
		$attr['class'] = 'ab-item description' . ( ( ! empty( $attr['class'] ) ) ? ' ' . $attr['class'] : '');
		$attr = self::parse_to_html_attr( $attr );
		return '<p ' . $attr . '>' . $text . '</p>';
	}

	/**
	 * Returns help tooltip html for WP admin bar.
	 * It will also change auto show/hide trigger to the help icon if the help text is a boolean true instead of a string.
	 * @todo document this properly.
	 *
	 * @since   1.7.3
	 * @static
	 *
	 * @param   string|array  $text           The help text. Also accepts an array with a `help` key.
	 * @param   array         $help_attr      (optional) Extra help icon attributes.
	 * @param   array         $tooltip_attr   (optional) Extra tooltip attributes.
	 * @param   array         $showhide_attr  (optional) Overwrite existing show/hide attributes.
	 * @return  string
	 */
	public static function do_help( $text, $help_attr = array(), $tooltip_attr = array(), &$showhide_attr = array() ) {
		if ( is_array( $text ) ) {
			if ( empty( $text['help'] ) ) {
				return '';
			}
			$text = $text['help'];
		} elseif ( ! $text ) {
			return '';
		}

		// Reset auto show/hide settings is $test is true. Disables show/hide on the label and sets it on the help icon.
		$help_attr = self::merge_attr( $help_attr, array(
			'class' => 'vaa-help',
		) );
		if ( true === $text ) {
			// Do nothing is auto show/hide isn't enabled.
			if ( ! isset( $showhide_attr['vaa-showhide'] ) ) {
				return '';
			}
			$help_attr['class'] .= ' ab-vaa-showhide';
			$help_attr['vaa-showhide'] = $showhide_attr['vaa-showhide'];
			unset( $showhide_attr['vaa-showhide'] );
		}

		if ( is_string( $text ) ) {
			// ab-sub-wrapper for background, ab-item for text color.
			$tooltip_attr = self::merge_attr( array(
				'class' => 'ab-item ab-sub-wrapper vaa-tooltip',
			), $tooltip_attr );
			$tooltip_attr = self::parse_to_html_attr( $tooltip_attr );
			$text = '<span ' . $tooltip_attr . '>' . $text . '</span>';
		} else {
			$text = '';
		}

		return self::do_icon( 'dashicons-editor-help', $help_attr, $text );
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
	 *     The form element args. Below parameters should be in the `auto_js` key.
	 *
	 *     @type  string  $setting  (required) The setting key.
	 *     @type  string  $confirm  (optional) Let JS generate a confirm box before running ajax?
	 *     @type  string  $refresh  (optional) Refresh after ajax return?
	 *     @type  string  $key      (optional, if values exists). The option key.
	 *     @type  array   $values {
	 *         The array of options. Alias: `value`.
	 *         All options need to be key => value pairs. See type documentation.
	 *         Recursive arrays supported (values in values).
	 *         If a key parameter exists this array will be added as the values of that key.
	 *
	 *         @type  bool    $required  (optional) Whether this option is required or not (default: true).
	 *         @type  string  $element   (optional) The HTML element to use as selector (overwrites current element).
	 *         @type  string  $attr      (optional) Get an attribute value instead of using .val()?
	 *         @type  bool    $json      (optional) Parse value as JSON? (Default parser only).
	 *         @type  string  $parser    (optional) The value processor.
	 *                                   `default` or empty : Normal handling.
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
		if ( ! empty( $args['auto_js'] ) ) {

			// Auto-generate values array based upon key and value keys.
			if ( ! empty( $args['auto_js']['key'] ) ) {
				if ( empty( $args['auto_js']['values'] ) ) {
					// Single value data.
					$value = null;
					if ( ! empty( $args['auto_js']['value'] ) ) {
						$value = $args['auto_js']['value'];
					}
				} else {
					// Set the values as the values of the supplied key.
					$value = array( 'values' => $args['auto_js']['values'] );
				}
				$values = array( $args['auto_js']['key'] => $value );
				$args['auto_js']['values'] = $values;
			}
			unset( $args['auto_js']['key'] );
			unset( $args['auto_js']['value'] );

			$attr['vaa-auto-js'] = wp_json_encode( $args['auto_js'] );
		}
		return $attr;
	}

	/**
	 * Update auto show/hide trigger and target attributes to enable auto show/hide functionality.
	 *
	 * @since   1.7
	 * @since   1.7.2   Moved to this class from admin bar class.
	 * @since   1.7.3   Renamed from `enable_auto_showhide_desc` + allow multiple values for trigger.
	 * @static
	 *
	 * @param   string  $target        The target element.
	 * @param   array   $trigger_attr  Trigger element attributes.
	 * @param   array   $target_attr   (optional) Target element attributes.
	 * @param   array   $args  {
	 *     (optional)Pass the full arguments array for auto_showhide key validation.
	 *
	 *     @type  bool|int|array  $auto_showhide {
	 *         Pass `true` for default handling of the first function parameter target.
	 *         Pass an integer to just set the delay for the first function parameter target.
	 *         Pass an array for full target data (multiple allowed), see parameters below. This will overwrite the first function parameter.
	 *
	 *         @type array {
	 *             @type  string  $target  The selector string for jQuery.
	 *             @type  int     $delay   (optional) Set the delay in milliseconds.
	 *         }
	 *     }
	 * }
	 * @return  void
	 */
	public static function enable_auto_showhide( $target, &$trigger_attr = array(), &$target_attr = array(), $args = array() ) {
		if ( ! empty( $args ) && empty( $args['auto_showhide'] ) ) {
			return;
		}

		$trigger_target = '.' . $target;
		if ( ! empty( $args['auto_showhide'] ) && ! is_bool( $args['auto_showhide'] ) ) {
			// Just the delay, keep the target value.
			if ( is_numeric( $args['auto_showhide'] ) ) {
				$trigger_target = wp_json_encode( array(
					'target' => $trigger_target,
					'delay' => $args['auto_showhide'],
				) );
			}
			// Full data. Multiple targets allowed,
			elseif ( is_array( $args['auto_showhide'] ) ) {
				$trigger_target = wp_json_encode( $args['auto_showhide'] );
			}
		}

		$trigger_attr = self::merge_attr( $trigger_attr, array(
			'class' => 'ab-vaa-showhide',
			'vaa-showhide' => $trigger_target,
		) );

		// @todo Find a way to auto create multiple targets.
		if ( ! empty( $target ) ) {
			$target_attr = self::merge_attr( $target_attr, array(
				'class' => $target,
			) );
		}
	}

	/**
	 * Merge two arrays of attributes into one, combining values.
	 * It currently doesn't convert variable types.
	 *
	 * @since   1.7.3
	 * @static
	 *
	 * @param   array  $attr  The current attributes.
	 * @param   array  $new   The new attributes. Attribute names as key.
	 * @return  string[]
	 */
	public static function merge_attr( $attr, $new ) {
		foreach ( $new as $key => $value ) {
			if ( empty( $attr[ $key ] ) ) {
				$attr[ $key ] = $value;
				continue;
			}
			if ( is_array( $attr[ $key ] ) ) {
				$attr[ $key ] = array_merge( $attr[ $key ], (array) $value );
				continue;
			}
			if ( is_array( $value ) ) {
				$value = implode( ' ', $value );
			}
			$attr[ $key ] .= ( ! empty( $value ) ) ? ' ' . $value : '';
		}
		return $attr;
	}

	/**
	 * Converts an array of attributes to a HTML string format starting with a space.
	 *
	 * @since   1.6.1
	 * @since   1.7     Renamed from `parse_attr_to_html`
	 * @since   1.7.2   Support array values. (Example: CSS classes). Moved to this class from admin bar class.
	 * @static
	 *
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
	 *
	 * @param   VAA_View_Admin_As  $caller  The referrer class
	 * @return  $this  VAA_View_Admin_As_Form
	 */
	public static function get_instance( $caller = null ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $caller );
		}
		return self::$_instance;
	}

} // End class VAA_View_Admin_As_Form.
