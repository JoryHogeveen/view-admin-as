/* eslint-disable no-extra-semi */
;/**
 * View Admin As
 * https://wordpress.org/plugins/view-admin-as/
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   0.1
 * @version 1.6.x
 * @preserve
 */
/* eslint-enable no-extra-semi */

if ( 'undefined' === typeof VAA_View_Admin_As ) {
	var VAA_View_Admin_As = {
		siteurl: '',
		view: false,
		view_types: [ 'user', 'role', 'caps', 'visitor' ],
		_debug: false,
		_vaa_nonce: '',
		__no_users_found: 'No users found.',
		__success: 'Success',
		__confirm: 'Are you sure?'
	};
}

( function( $ ) {

	var $document = $( document ),
		$window = $( window );

	VAA_View_Admin_As.prefix = '#wpadminbar #wp-admin-bar-vaa ';
	VAA_View_Admin_As.root = '#wp-admin-bar-vaa';
	VAA_View_Admin_As.maxHeightListenerElements = $( VAA_View_Admin_As.prefix + ' .max-height-listener' );

	if ( ! VAA_View_Admin_As.hasOwnProperty( '_debug' ) ) {
		VAA_View_Admin_As._debug = 0;
	}
	VAA_View_Admin_As._debug = parseInt( VAA_View_Admin_As._debug, 10 );

	if ( ! VAA_View_Admin_As.hasOwnProperty( 'ajaxurl' ) && 'undefined' !== typeof ajaxurl ) {
		VAA_View_Admin_As.ajaxurl = ajaxurl;
	}

	// @since  1.6.1  Prevent swipe events to be seen as a click (bug in some browsers).
	VAA_View_Admin_As._touchmove = false;
	$document.on( 'touchmove', function() {
		VAA_View_Admin_As._touchmove = true;
	} );
	$document.on( 'touchstart', function() {
		VAA_View_Admin_As._touchmove = false;
	} );

	/**
	 * BASE INIT.
	 * @return  {null}  nothing
	**/
	VAA_View_Admin_As.init = function() {

		VAA_View_Admin_As.init_caps();
		VAA_View_Admin_As.init_users();
		VAA_View_Admin_As.init_settings();
		VAA_View_Admin_As.init_module_role_defaults();

		// Functionality that require the document to be fully loaded.
		$window.on( "load", function() {

			// Toggle content with title.
			$( VAA_View_Admin_As.prefix + '.ab-vaa-toggle' ).each( function() {
				var toggleContent = $(this).parent().children().not('.ab-vaa-toggle');
				if ( ! $(this).hasClass('active') ) {
					toggleContent.hide();
				}

				$(this).on( 'click touchend', function( e ) {
					e.preventDefault();
					e.stopPropagation();
					if ( true === VAA_View_Admin_As._touchmove ) {
						return;
					}
					if ( $(this).hasClass('active') ) {
						toggleContent.slideUp('fast');
						$(this).removeClass('active');
					} else {
						toggleContent.slideDown('fast');
						$(this).addClass('active');
					}
					VAA_View_Admin_As.autoMaxHeight();
				} );

				// @since  1.6.1  Keyboard a11y.
				$(this).on( 'keyup', function( e ) {
					e.preventDefault();
					/**
					 * @see  https://api.jquery.com/keyup/
					 * 13 = enter
					 * 32 = space
					 * 38 = arrow up
					 * 40 = arrow down
					 */
					var key = parseInt( e.which, 10 );
					if ( $(this).hasClass('active') && ( 13 === key || 32 === key || 38 === key ) ) {
						toggleContent.slideUp('fast');
						$(this).removeClass('active');
					} else if ( 13 === key || 32 === key || 40 === key ) {
						toggleContent.slideDown('fast');
						$(this).addClass('active');
					}
					VAA_View_Admin_As.autoMaxHeight();
				} );
			} );

			// @since  1.6.3  Toggle items on hover.
			$( VAA_View_Admin_As.prefix + '.ab-vaa-showhide[data-showhide]' ).each( function() {
				$( $(this).attr('data-showhide') ).hide();
				$(this).on( 'mouseenter', function() {
					$( $(this).attr('data-showhide') ).slideDown('fast');
				}).on( 'mouseleave', function() {
					$( $(this).attr('data-showhide') ).slideUp('fast');
				} );
			} );

			// @since  1.6.x  Conditional items.
			$( VAA_View_Admin_As.prefix + '.ab-vaa-conditional[data-condition-target]' ).each( function() {
				var $this    = $( this ),
					$target  = $( $this.attr( 'data-condition-target' ) ),
					$compare = $this.attr( 'data-condition' );
				$this.hide();
				$target.on( 'change', function() {
					if ( $compare === $target.val() ) {
						$this.slideDown('fast');
					} else {
						$this.slideUp('fast');
					}
					VAA_View_Admin_As.autoMaxHeight();
				} );
			} );

		} ); // End window.load.

		// Process reset.
		$document.on( 'click touchend', VAA_View_Admin_As.prefix + '.vaa-reset-item > .ab-item', function( e ) {
			e.preventDefault();
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			if ( 'vaa_reload' === $( 'button', this ).attr('name') ) {
				window.location.reload();
			} else {
				VAA_View_Admin_As.ajax( { reset : true }, true );
				return false;
			}
		} );

		// @since  1.6.2  Process basic views.
		$.each( VAA_View_Admin_As.view_types, function( index, type ) {
			$document.on( 'click touchend', VAA_View_Admin_As.prefix + '.vaa-'+type+'-item > a.ab-item', function( e ) {
				if ( true === VAA_View_Admin_As._touchmove ) {
					return;
				}
				e.preventDefault();
				if ( ! $(this).parent().hasClass('not-a-view') ) {
					var view_data = {};
					view_data[ type ] = String( $(this).attr('rel') );
					VAA_View_Admin_As.ajax( view_data, true );
					return false;
				}
			} );
		} );

		// @since  1.6.3  Removable items.
		$document.on( 'click touchend', VAA_View_Admin_As.prefix + '.ab-item > .remove', function( e ) {
			e.preventDefault();
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			$(this).parent('.ab-item').slideUp('fast', function() { $(this).remove(); } );
		} );
	};


	/**
	 * Apply the selected view.
	 *
	 * @param   {object}   data     The data to send, view format: { VIEW_TYPE : VIEW_TYPE_DATA }
	 * @param   {boolean}  refresh  Reload/redirect the page?
	 * @return  {null}     nothing
	 */
	VAA_View_Admin_As.ajax = function( data, refresh ) {

		var body = $('body');

		$( '.vaa-notice', '#wpadminbar' ).remove();
		// @todo dashicon loader?
		body.append('<div id="vaa-overlay"><span class="vaa-loader-icon" style="background: transparent url('+VAA_View_Admin_As.siteurl+'/wp-includes/images/spinner-2x.gif) center center no-repeat; background-size: contain;"></span></div>');
		$('body #vaa-overlay').fadeIn('fast');

		var post_data = {
			'action': 'view_admin_as',
			'_vaa_nonce': VAA_View_Admin_As._vaa_nonce,
			// @since  1.6.2  Use JSON data.
			'view_admin_as': JSON.stringify( data )
		};

		var isView = false;
		$.each( VAA_View_Admin_As.view_types, function( index, type ) {
			if ( 'undefined' !== typeof data[ type ] ) {
				isView = true;
				return true;
			}
		} );

		/**
		 *  @since  1.5  Check view mode.
		 *  @todo   Improve form creation.
 		 */
		if ( $( VAA_View_Admin_As.prefix + '#vaa-settings-view-mode-single' ).is(':checked') && isView ) {

			body.append('<form id="vaa_single_mode_form" style="display:none;" method="post"></form>');
			var form = $('#vaa_single_mode_form');
			form.append('<input type="hidden" name="action" value="' + post_data.action + '">');
			form.append('<input type="hidden" name="_vaa_nonce" value="' + post_data._vaa_nonce + '">');
			form.append('<input id="data" type="hidden" name="view_admin_as">');
			form.find('#data').val( post_data.view_admin_as );
			form.submit();

		} else {

			$.post( VAA_View_Admin_As.ajaxurl, post_data, function( response ) {
				var success = ( response.hasOwnProperty( 'success' ) && true === response.success ),
					data = {},
					display = false;

				if ( 1 === VAA_View_Admin_As._debug ) {
					// Show debug info in console.
					console.log( response );
				}

				if ( response.hasOwnProperty( 'data' ) ) {
					if ( 'object' === typeof response.data ) {
						data = response.data;
					}
					display = ( data.hasOwnProperty( 'display' ) ) ? data.display : display;
				}

				if ( success ) {
					if ( refresh ) {
						VAA_View_Admin_As.refresh( data );
						return;
					} else {
						if ( ! data.hasOwnProperty( 'text' ) ) {
							data.text = VAA_View_Admin_As.__success;
						}
					}
				}

				if ( ! data.hasOwnProperty( 'type' ) ) {
					if ( success ) {
						data.type = 'success';
					} else {
						data.type = 'error';
					}
				}

				if ( 'popup' === display ) {
					VAA_View_Admin_As.popup( data, data.type );
				} else {
					if ( ! data.hasOwnProperty( 'text' ) ) {
						data.text = response.data;
					}
					VAA_View_Admin_As.notice( String( data.text ), data.type );

					$('body #vaa-overlay').addClass( data.type ).fadeOut( 'fast', function() { $(this).remove(); } );
				}
			} );
		}
	};

	/**
	 * Reload the page or optionally redirect the user
	 * @since  1.6.x
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {object}  data  Info for the redirect: { redirect: URL }
	 * @return {null}  Nothing
	 */
	VAA_View_Admin_As.refresh = function( data ) {
		if ( data.hasOwnProperty( 'redirect' ) ) {
			/**
			 * Optional redirect.
			 * Currently I use "replace" since no history seems necessary. Other option would be "assign" which enables history.
			 * @since  1.6.4
			 */
			window.location.replace( String( data.redirect ) );
		} else {
			/**
			 * Reload the page.
			 * @since  1.6.1  Fix issue with anchors.
			 */
			window.location.hash = '';
			window.location.reload();
		}
	};

	/**
	 * Show notice in the admin bar.
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {string}  notice  The notice text
	 * @param  {string}  type    The notice type (error, notice, etc)
	 * @return {null}  Nothing
	 */
	VAA_View_Admin_As.notice = function( notice, type ) {
		var root = '#wpadminbar .vaa-notice';
		$('#wp-admin-bar-top-secondary').append(
			'<li class="vaa-notice vaa-' + type + '"><span class="remove ab-icon dashicons dashicons-dismiss" style="top: 2px;"></span>' + notice + '</li>'
		);
		$( root + ' .remove' ).click( function() { $(this).parent().remove(); } );
		// Remove it after 5 seconds
		setTimeout( function(){ $(root).fadeOut('fast', function() { $(this).remove(); } ); }, 5000 );
	};


	/**
	 * Show popup with return content.
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {object}  data  Data to use
	 * @param  {string}  type  The notice/overlay type (error, notice, etc)
	 * @return {null}  Nothing
	 */
	VAA_View_Admin_As.popup = function( data, type ) {

		var root = 'body #vaa-overlay';

		$( root ).html(
			'<div class="vaa-overlay-container vaa-' + type + '"><span class="remove dashicons dashicons-dismiss"></span><div class="vaa-response-data"></div></div>'
		);

		if ( 'object' !== typeof data ) {
			data = { text: data };
		}
		if ( data.hasOwnProperty( 'text' ) ) {
			$( root + ' .vaa-response-data' ).append('<p>' + String( data.text ) + '</p>');
		}

		if ( data.hasOwnProperty( 'list' ) ) {
			$( root + ' .vaa-response-data' ).append('<ul></ul>');
			data.list.forEach( function( item ) {
				$( root + ' .vaa-response-data ul' ).append('<li>' + String( item ) + '</li>');
			} );
		}

		if ( data.hasOwnProperty( 'textarea' ) ) {
			$( root + ' .vaa-response-data' ).append('<textarea style="width: 100%;" readonly>' + String( data.textarea ) + '</textarea>');
			// Auto height.
			/*$('body #vaa-overlay .vaa-response-data textarea').each(function(){
				var maxTextareaHeight = $('body #vaa-overlay .vaa-response-data').height();
				var fullTextareaHeight = this.scrollHeight;
				$(this).css({'height': 'auto', 'max-height': maxTextareaHeight}).height( fullTextareaHeight );
			} );*/
			// Select full text on click.
			$( root + ' .vaa-response-data textarea' ).click( function() { $(this).select(); } );
		}

		$( root + ' .vaa-overlay-container .remove' ).click( function() {
			$( root ).fadeOut( 'fast', function() { $(this).remove(); } );
		} );

		// Remove overlay on click outside of container.
		$document.mouseup( function( e ){
			$( root + ' .vaa-overlay-container' ).each( function(){
				if ( ! $(this).is( e.target ) && 0 === $(this).has( e.target ).length ) {
					$( root ).fadeOut( 'fast', function() { $(this).remove(); } );
				}
			} );
		} );
	};


	/**
	 * SETTINGS.
	 * @since  1.5
	 * @return {null}  nothing
	 */
	VAA_View_Admin_As.init_settings = function() {

		var root = VAA_View_Admin_As.root + '-settings',
			prefix = 'vaa-settings',
			root_prefix = VAA_View_Admin_As.prefix + root;

		// @since  1.5  Location.
		$document.on( 'change', root_prefix + '-admin-menu-location select#' + prefix + '-admin-menu-location', function( e ) {
			e.preventDefault();
			var val = $(this).val();
			if ( val && '' !== val ) {
				var view_data = { user_setting : { admin_menu_location : val } };
				VAA_View_Admin_As.ajax( view_data, true );
			}
		} );

		// @since  1.5  View mode.
		$document.on( 'change', root_prefix + '-view-mode input.radio.' + prefix + '-view-mode', function( e ) {
			e.preventDefault();
			var val = $(this).val();
			if ( val && '' !== val ) {
				var view_data = { user_setting : { view_mode : val } };
				VAA_View_Admin_As.ajax( view_data, false );
			}
		} );

		// @since  1.5.2  Force group users.
		$document.on( 'change', root_prefix + '-force-group-users input#' + prefix + '-force-group-users', function( e ) {
			e.preventDefault();
			var view_data = { user_setting : { force_group_users : "no" } };
			if ( this.checked ) {
				view_data = { user_setting : { force_group_users : "yes" } };
			}
			VAA_View_Admin_As.ajax( view_data, true );
		} );

		// @since  1.6  Enable hide front.
		$document.on( 'change', root_prefix + '-hide-front input#' + prefix + '-hide-front', function( e ) {
			e.preventDefault();
			var view_data = { user_setting : { hide_front : "no" } };
			if ( this.checked ) {
				view_data = { user_setting : { hide_front : "yes" } };
			}
			VAA_View_Admin_As.ajax( view_data, false );
		} );

		// @since  1.6.1  Enable freeze locale.
		$document.on( 'change', root_prefix + '-freeze-locale input#' + prefix + '-freeze-locale', function( e ) {
			e.preventDefault();
			var view_data = { user_setting : { freeze_locale : "no" } };
			if ( this.checked ) {
				view_data = { user_setting : { freeze_locale : "yes" } };
			}
			var reload = false;
			if ( 'object' === typeof VAA_View_Admin_As.view && 'undefined' !== typeof VAA_View_Admin_As.view.user ) {
				reload = true;
			}
			VAA_View_Admin_As.ajax( view_data, reload );
		} );
	};


	/**
	 * USERS.
	 * Extra functions for user views.
	 * @since  1.2
	 * @return {null}  nothing
	**/
	VAA_View_Admin_As.init_users = function() {

		var root = VAA_View_Admin_As.root + '-users',
			root_prefix = VAA_View_Admin_As.prefix + root;

		// Search users.
		$document.on( 'keyup', root_prefix + ' .ab-vaa-search.search-users input', function() {
			$( VAA_View_Admin_As.prefix + ' .ab-vaa-search #vaa-searchuser-results' ).empty();
			if ( 1 <= $(this).val().length ) {
				var inputText = $(this).val();
				$( VAA_View_Admin_As.prefix + '.vaa-user-item' ).each( function() {
					var name = $('.ab-item', this).text();
					if ( -1 < name.toLowerCase().indexOf( inputText.toLowerCase() ) ) {
						var exists = false;
						$( VAA_View_Admin_As.prefix + '.ab-vaa-search #vaa-searchuser-results .vaa-user-item .ab-item' ).each(function() {
							if ( -1 < $(this).text().indexOf(name) ) {
								exists = $(this);
							}
						} );
						var role = $(this).parents('.vaa-role-item').children('.ab-item').attr('rel');
						if ( false !== exists && exists.length ) {
							exists.find('.user-role').text( exists.find('.user-role').text().replace(')', ', ' + role + ')') );
						} else {
							$(this).clone().appendTo( VAA_View_Admin_As.prefix + '.ab-vaa-search #vaa-searchuser-results' ).children('.ab-item').append(' &nbsp; <span class="user-role">(' + role + ')</span>');
						}
					}
				} );
				if ( '' === $.trim( $( VAA_View_Admin_As.prefix + '.ab-vaa-search #vaa-searchuser-results' ).html() ) ) {
					$( VAA_View_Admin_As.prefix + '.ab-vaa-search #vaa-searchuser-results' ).append('<div class="ab-item ab-empty-item vaa-not-found">'+VAA_View_Admin_As.__no_users_found+'</div>');
				}
			}
		} );
	};


	/**
	 * CAPABILITIES.
	 * @since  1.3
	 * @return {null}  nothing
	**/
	VAA_View_Admin_As.init_caps = function() {

		var root = VAA_View_Admin_As.root + '-caps',
			root_prefix = VAA_View_Admin_As.prefix + root;

		VAA_View_Admin_As.caps_filter_settings = {
			selectedRole : 'default',
			selectedRoleCaps : {},
			selectedRoleReverse : false,
			filterString : ''
		};

		// Filter capability handler.
		VAA_View_Admin_As.filter_capabilities = function() {
			var reverse = ( true === VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse ),
				isDefault = ( 'default' === VAA_View_Admin_As.caps_filter_settings.selectedRole ),
				filterString = VAA_View_Admin_As.caps_filter_settings.filterString;

			$( root_prefix + '-select-options .vaa-cap-item' ).each( function() {
				var $this = $(this),
					exists = ( $( 'input', this ).attr('value') in VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps ),
					name;

				$this.hide();
				if ( reverse || exists || isDefault ) {
					if ( 1 <= filterString.length ) {
						name = $this.text(); // $( '.ab-item', this ).text();
						if ( -1 < name.toLowerCase().indexOf( filterString.toLowerCase() ) ) {
							$this.show();
						}
					} else {
						$this.show();
					}
					if ( reverse && exists && ! isDefault ) {
						$this.hide();
					}
				}
			} );
		};

		// Since  1.6.x  Get the selected capabilities
		VAA_View_Admin_As.get_selected_capabilities = function() {
			var capabilities = {};
			$( root_prefix + '-select-options .vaa-cap-item input' ).each( function() {
				if ( $(this).is(':checked') ) {
					capabilities[ $(this).attr('value') ] = 1;
				} else {
					capabilities[ $(this).attr('value') ] = 0;
				}
			} );
			return capabilities;
		};

		// Since  1.6.x  Set the selected capabilities
		VAA_View_Admin_As.set_selected_capabilities = function( capabilities ) {
			$( root_prefix + '-select-options .vaa-cap-item input' ).each( function() {
				if ( capabilities.hasOwnProperty( $(this).attr('value') ) ) {
					if ( capabilities[ $(this).attr('value') ] ) {
						$( this ).attr('checked','checked');
					} else {
						$( this ).attr( 'checked', false );
					}
				} else {
					$( this ).attr( 'checked', false );
				}
			} );
		};

		// Set max height of the caps submenu.
		$document.on( 'mouseenter', root_prefix + '-manager', function() {
			VAA_View_Admin_As.autoMaxHeight();
		} );
		// Enlarge caps.
		$document.on( 'click', root_prefix + ' #open-caps-popup', function() {
			$( VAA_View_Admin_As.prefix ).addClass('fullPopupActive');
			$( root_prefix + '-manager > .ab-sub-wrapper').addClass('fullPopup');
			VAA_View_Admin_As.autoMaxHeight();
		} );
		// Undo enlarge caps.
		$document.on( 'click', root_prefix + ' #close-caps-popup', function() {
			$( VAA_View_Admin_As.prefix ).removeClass('fullPopupActive');
			$( root_prefix + '-manager > .ab-sub-wrapper').removeClass('fullPopup');
			VAA_View_Admin_As.autoMaxHeight();
		} );

		// Select role capabilities.
		$document.on( 'change', root_prefix + ' .ab-vaa-select.select-role-caps select', function() {
			VAA_View_Admin_As.caps_filter_settings.selectedRole = $(this).val();

			if ( 'default' === VAA_View_Admin_As.caps_filter_settings.selectedRole ) {
				 VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = {};
				 VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = false;
			} else {
				var selectedRoleElement = $( root_prefix + '-selectrolecaps #vaa-caps-selectrolecaps option[value="' + VAA_View_Admin_As.caps_filter_settings.selectedRole + '"]' );
				VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = JSON.parse( selectedRoleElement.attr('data-caps') );
				VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = ( 1 === parseInt( selectedRoleElement.attr('data-reverse'), 10 ) );
			}
			VAA_View_Admin_As.filter_capabilities();
		} );

		// Filter capabilities with text input.
		$document.on( 'keyup', root_prefix + ' .ab-vaa-filter input', function() {
			VAA_View_Admin_As.caps_filter_settings.filterString = $(this).val();
			VAA_View_Admin_As.filter_capabilities();
		} );


		// Select all capabilities.
		$document.on( 'click touchend', root_prefix + ' button#select-all-caps', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			$( root_prefix + '-select-options .vaa-cap-item' ).each( function() {
				if ( $(this).is(':visible') ) {
					$('input', this).prop( "checked", true );
				}
			} );
			return false;
		} );
		// Deselect all capabilities.
		$document.on( 'click touchend', root_prefix + ' button#deselect-all-caps', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			$( root_prefix + '-select-options .vaa-cap-item' ).each( function() {
				if ( $(this).is(':visible') ) {
					$('input', this).prop( "checked", false );
				}
			} );
			return false;
		} );

		// Process view: capabilities.
		$document.on( 'click touchend', root_prefix + ' button#apply-caps-view', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var newCaps = VAA_View_Admin_As.get_selected_capabilities();
			VAA_View_Admin_As.ajax( { caps : newCaps }, true );
			return false;
		} );
	};

	/**
	 * MODULE: Role Defaults.
	 * @since  1.4
	 * @return {null}  nothing
	 */
	VAA_View_Admin_As.init_module_role_defaults = function() {

		var root = VAA_View_Admin_As.root + '-settings',
			prefix = 'vaa-settings',
			root_prefix = VAA_View_Admin_As.prefix + root;

		// Enable module.
		$document.on( 'change', root_prefix + '-role-defaults-enable input#' + prefix + '-role-defaults-enable', function( e ) {
			e.preventDefault();
			var view_data = { role_defaults : { enable : 0 } };
			if ( this.checked ) {
				view_data = { role_defaults : { enable : true } };
			}
			VAA_View_Admin_As.ajax( view_data, true );
		} );

		root = VAA_View_Admin_As.root + '-role-defaults';
		prefix = 'vaa-role-defaults';
		root_prefix = VAA_View_Admin_As.prefix + root;

		// @since  1.4  Enable apply defaults on register.
		$document.on( 'change', root_prefix + '-setting-register-enable input#' + prefix + '-setting-register-enable', function( e ) {
			e.preventDefault();
			var view_data = { role_defaults : { apply_defaults_on_register : 0 } };
			if ( this.checked ) {
				view_data = { role_defaults : { apply_defaults_on_register : true } };
			}
			VAA_View_Admin_As.ajax( view_data, false );
		} );

		// @since  1.5.3  Disable screen settings for users who can't access this plugin.
		$document.on( 'change', root_prefix + '-setting-disable-user-screen-options input#' + prefix + '-setting-disable-user-screen-options', function( e ) {
			e.preventDefault();
			var view_data = { role_defaults : { disable_user_screen_options : 0 } };
			if ( this.checked ) {
				view_data = { role_defaults : { disable_user_screen_options : true } };
			}
			VAA_View_Admin_As.ajax( view_data, false );
		} );

		// @since  1.6  Lock meta box order and locations for users who can't access this plugin.
		$document.on( 'change', root_prefix + '-setting-lock-meta-boxes input#' + prefix + '-setting-lock-meta-boxes', function( e ) {
			e.preventDefault();
			var view_data = { role_defaults : { lock_meta_boxes : 0 } };
			if ( this.checked ) {
				view_data = { role_defaults : { lock_meta_boxes : true } };
			}
			VAA_View_Admin_As.ajax( view_data, false );
		} );

		// @since  1.6.3  Add new meta.
		$document.on( 'click touchend', root_prefix + '-meta-add button#' + prefix + '-meta-add', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $( root_prefix + '-meta-add input#' + prefix + '-meta-new' ).val();
			var item = $( root_prefix + '-meta-add #' + prefix + '-meta-template' ).html().toString();
			item = item.replace( /vaa_new_item/g, val.replace( / /g, '_' ) );
			$( root_prefix + '-meta-select > .ab-item' ).prepend( item );
		} );

		// @since  1.6.3  Update meta.
		$document.on( 'click touchend', root_prefix + '-meta-apply button#' + prefix + '-meta-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = {};
			$( root_prefix + '-meta-select .ab-item.vaa-item input' ).each( function() {
				val[ $(this).val() ] = ( $(this).is(':checked') );
			} );
			if ( val ) {
				var view_data = { role_defaults : { update_meta : val } };
				VAA_View_Admin_As.ajax( view_data, false );
			}
			return false;
		} );

		// @since  1.4  Filter users.
		$document.on( 'keyup', root_prefix + '-bulk-users-filter input#' + prefix + '-bulk-users-filter', function( e ) {
			e.preventDefault();
			if ( 1 <= $(this).val().length ) {
				var inputText = $(this).val();
				$( root_prefix + '-bulk-users-select .ab-item.vaa-item' ).each( function() {
					var name = $('.user-name', this).text();
					if ( -1 < name.toLowerCase().indexOf( inputText.toLowerCase() ) ) {
						$(this).show();
					} else {
						$(this).hide();
					}
				} );
			} else {
				$( root_prefix + '-bulk-users-select .ab-item.vaa-item' ).each( function() {
					$(this).show();
				} );
			}
		} );

		// @since  1.4  Apply defaults to users.
		$document.on( 'click touchend', root_prefix + '-bulk-users-apply button#' + prefix + '-bulk-users-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = [];
			$( root_prefix + '-bulk-users-select .ab-item.vaa-item input' ).each( function() {
				if ( $(this).is(':checked') ) {
					val.push( $(this).val() );
				}
			} );
			if ( val ) {
				var view_data = { role_defaults : { apply_defaults_to_users : val } };
				VAA_View_Admin_As.ajax( view_data, false );
			}
			return false;
		} );

		// @since  1.4  Apply defaults to users by role.
		$document.on( 'click touchend', root_prefix + '-bulk-roles-apply button#' + prefix + '-bulk-roles-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $( root_prefix + '-bulk-roles-select select#' + prefix + '-bulk-roles-select' ).val();
			if ( val && '' !== val ) {
				var view_data = { role_defaults : { apply_defaults_to_users_by_role : val } };
				VAA_View_Admin_As.ajax( view_data, false );
			}
			return false;
		} );

		// @since  1.4  Clear role defaults.
		$document.on( 'click touchend', root_prefix + '-clear-roles-apply button#' + prefix + '-clear-roles-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $( root_prefix + '-clear-roles-select select#' + prefix + '-clear-roles-select' ).val();
			if ( val && '' !== val ) {
				var view_data = { role_defaults : { clear_role_defaults : val } };
				if ( confirm( VAA_View_Admin_As.__confirm ) ) {
					VAA_View_Admin_As.ajax( view_data, false );
				}
			}
			return false;
		} );

		// @since  1.5  Export role defaults.
		$document.on( 'click touchend', root_prefix + '-export-roles-export button#' + prefix + '-export-roles-export', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $( root_prefix + '-export-roles-select select#' + prefix + '-export-roles-select' ).val();
			if ( val && '' !== val ) {
				var view_data = { role_defaults : { export_role_defaults : val } };
				VAA_View_Admin_As.ajax( view_data, false );
			}
			return false;
		} );

		// @since  1.5  Import role defaults.
		$document.on( 'click touchend', root_prefix + '-import-roles-import button.vaa-import-role-defaults', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $( root_prefix + '-import-roles-input textarea#' + prefix + '-import-roles-input' ).val();
			if ( val && '' !== val ) {
				try {
					val = JSON.parse( val );
					var view_data = { role_defaults : { import_role_defaults : val } };
					if ( $(this).attr('data-method') ) {
						view_data.role_defaults.import_role_defaults_method = String( $(this).attr('data-method') );
					}
					VAA_View_Admin_As.ajax( view_data, false );
				} catch ( err ) {
					// @todo Improve error message.
					alert( err );
				}
			}
			return false;
		} );
	};

	/**
	 * Auto resize max height elements
	 * @since  1.6.x
	 * @return {null}  nothing
	 */
	VAA_View_Admin_As.autoMaxHeight = function() {
		setTimeout( function() {
			VAA_View_Admin_As.maxHeightListenerElements.each( function() {
				var element = $( this ),
					count = 0,
					wait = setInterval( function() {
						var offset = element.offset();
						if ( element.is(':visible') && 0 < offset.top ) {
							clearInterval( wait );
							var maxHeight = $window.height() - offset.top - 100;
							maxHeight = ( 100 < maxHeight ) ? maxHeight : 100;
							element.css( { 'max-height': maxHeight + 'px' } );
						}
						count++;
						if ( 5 < count ) {
							clearInterval( wait );
						}
					}, 100 );
			} );
		}, 100 );
	};
	$document.on( 'resize', VAA_View_Admin_As.autoMaxHeight );

	// We require a nonce to use this plugin.
	if ( 'undefined' !== typeof VAA_View_Admin_As._vaa_nonce ) {
		VAA_View_Admin_As.init();
	}

} ( jQuery ) );