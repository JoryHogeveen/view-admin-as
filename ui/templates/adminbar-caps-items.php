<?php
/**
 * Add caps items.
 *
 * @since    1.7
 * @version  1.7.2
 *
 * @var  WP_Admin_Bar  $admin_bar  The toolbar object.
 * @var  string        $root       The current root item.
 * @var  string        $main_root  The main VAA root item.
 */

if ( ! defined( 'VIEW_ADMIN_AS_DIR' ) ) {
	die();
}

if ( isset( $admin_bar ) && $admin_bar instanceof WP_Admin_Bar && isset( $root ) ) {

	if ( ! isset( $main_root ) ) {
		$main_root = $root;
	}
	if ( ! isset( $parent ) ) {
		$parent = $root;
	}

	$caps_items = '';
	foreach ( $this->store->get_caps() as $cap_name => $cap_val ) {
		$class   = 'vaa-cap-item';
		$checked = false;
		// check if we've selected a capability view and we've changed some capabilities.
		$selected_caps = $this->store->get_view( 'caps' );
		if ( isset( $selected_caps[ $cap_name ] ) ) {
			if ( 1 === (int) $selected_caps[ $cap_name ] ) {
				$checked = true;
			}
		} elseif ( 1 === (int) $cap_val ) {
			$checked = true;
		}
		// Check for this capability in any view set.
		if ( $this->vaa->view()->current_view_can( $cap_name ) ) {
			$class .= ' current';
		}
		// The list of capabilities.
		$caps_items .=
			'<div class="ab-item ' . $class . '">'
			. VAA_View_Admin_As_Form::do_checkbox(
				array(
					'name'           => 'vaa_cap_' . esc_attr( $cap_name ),
					'value'          => $checked,
					'compare'        => true,
					'checkbox_value' => esc_attr( $cap_name ),
					'label'          => $cap_name,
				)
			)
			. '</div>';
	}
	$admin_bar->add_node(
		array(
			'id'     => $root . '-select-options',
			'parent' => $parent,
			'title'  => $caps_items,
			'href'   => false,
			'meta'   => array(
				'class' => 'ab-vaa-multipleselect vaa-auto-max-height',
			),
		)
	);

} else {
	_doing_it_wrong( __FILE__, esc_html__( 'No toolbar resources found.', VIEW_ADMIN_AS_DOMAIN ), '1.7' );
} // End if().
