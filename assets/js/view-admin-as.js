/* eslint-disable no-extra-semi */
;/**
 * View Admin As
 * https://wordpress.org/plugins/view-admin-as/
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   0.1
 * @version 1.7.2
 * @preserve
 */
/* eslint-enable no-extra-semi */

if ( 'undefined' === typeof VAA_View_Admin_As ) {
	/**
	 * Property reference to script localization from plugin.
	 * Only the properties from script localization are documented here.
	 *
	 * @see  VAA_View_Admin_As_UI::enqueue_scripts()
	 *
	 * @property  {string}   ajaxurl        Current site/blog ajax callback (/wp-admin/admin-ajax.php).
	 * @property  {string}   siteurl        Current site/blog url.
	 * @property  {array}    settings       The global settings.
	 * @property  {array}    settings_user  The user settings.
	 * @property  {array}    view           The current view (empty if no view is active).
	 * @property  {array}    view_types     The available view types.
	 * @property  {string}   _vaa_nonce
	 * @property  {boolean}  _debug
	 * @property  {string}   __no_users_found      'No users found.'.
	 * @property  {string}   __key_already_exists: 'Key already exists.'.
	 * @property  {string}   __success             'Success'.
	 * @property  {string}   __confirm             'Are you sure?'.
	 */
	var VAA_View_Admin_As = {};
}

( function( $ ) {

	var $document = $( document ),
		$window = $( window ),
		$body = $('body');

	VAA_View_Admin_As.prefix = '#wpadminbar #wp-admin-bar-vaa ';
	VAA_View_Admin_As.root = '#wp-admin-bar-vaa';
	VAA_View_Admin_As.maxHeightListenerElements = null;
	VAA_View_Admin_As._mobile = false;

	if ( ! VAA_View_Admin_As.hasOwnProperty( '_debug' ) ) {
		VAA_View_Admin_As._debug = false;
	}
	VAA_View_Admin_As._debug = Boolean( parseInt( VAA_View_Admin_As._debug, 10 ) );

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
	 * Safely try to parse as JSON. If it isn't JSON it will return the original string.
	 * @since   1.7
	 * @param   {string}  val  The string the decode.
	 * @return  {string|object}  Parsed JSON object or original string.
	 */
	VAA_View_Admin_As.json_decode = function( val ) {
		if ( 0 === val.indexOf("{") ) {
			try {
				val = JSON.parse( val );
			} catch ( err ) {
				// No JSON data.
			}
		}
		return val;
	};

	/**
	 * BASE INIT.
	 * @since   1.5.1
	 * @return  {null}  Nothing.
	 */
	VAA_View_Admin_As.init = function() {

		VAA_View_Admin_As.init_caps();
		VAA_View_Admin_As.init_users();
		VAA_View_Admin_As.init_module_role_defaults();
		VAA_View_Admin_As.init_module_role_manager();

		// Functionality that require the document to be fully loaded.
		$window.on( 'load', function() {

			VAA_View_Admin_As.init_auto_js();

			// Load autoMaxHeight elements.
			VAA_View_Admin_As.maxHeightListenerElements = $( VAA_View_Admin_As.prefix + '.vaa-auto-max-height' );

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

			// @since  1.7  Conditional items.
			$( VAA_View_Admin_As.prefix + '.ab-vaa-conditional[data-condition-target]' ).each( function() {
				var $this    = $( this ),
					$target  = $( $this.attr( 'data-condition-target' ) ),
					compare  = $this.attr( 'data-condition' ),
					checkbox = $target.is(':checkbox');
				$this.hide();
				$target.on( 'change', function() {
					if ( checkbox && $target.is(':checked') ) {
						$this.slideDown('fast');
					} else if ( ! checkbox && compare === $target.val() ) {
						$this.slideDown('fast');
					} else {
						$this.slideUp('fast');
					}
					VAA_View_Admin_As.autoMaxHeight();
				} );
			} );

			// @since  1.7  Init mobile fixes.
			if ( $body.hasClass('mobile') || 783 > $body.innerWidth() ) {
				$body.addClass('vaa-mobile');
				VAA_View_Admin_As._mobile = true;
				VAA_View_Admin_As.mobile();
			}

			// @since  1.7.1  Auto max height trigger.
			VAA_View_Admin_As.maxHeightListenerElements.each( function() {
				$(this).parents('.menupop').on( 'mouseenter', function() {
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
			$document.on( 'click touchend', VAA_View_Admin_As.prefix + '.vaa-' + type + '-item > a.ab-item', function( e ) {
				if ( true === VAA_View_Admin_As._touchmove ) {
					return;
				}
				e.preventDefault();
				var $this = $(this);
				// Fix for responsive views (first click triggers show child items).
				if ( VAA_View_Admin_As._mobile && $this.parent().hasClass('menupop') && ! $this.next().is(':visible') ) {
					$this.next().show().parent().addClass('active');
					return;
				}
				if ( ! $this.parent().hasClass('not-a-view') ) {
					var view_data = {},
						val = $this.attr('rel');
					if ( ! val ) {
						val = $this.find('.vaa-view-data').attr('vaa-view-value');
					}
					view_data[ type ] = VAA_View_Admin_As.json_decode( val );
					view_data = ( 'object' === typeof view_data[ type ] ) ? view_data[ type ] : view_data;
					VAA_View_Admin_As.ajax( view_data, true );
					return false;
				}
			} );
		} );

		// @since  1.6.3  Removable items.
		$document.on( 'click touchend', VAA_View_Admin_As.prefix + '.remove', function( e ) {
			e.preventDefault();
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			$(this).parent().slideUp( 'fast', function() { $(this).remove(); } );
		} );
	};

	/**
	 * MOBILE INIT.
	 * @since   1.7
	 * @return  {null}  Nothing.
	 */
	VAA_View_Admin_As.mobile = function() {
		var prefix = '.vaa-mobile ' + VAA_View_Admin_As.prefix;

		// @since  1.7  Fix for clicking within sub secondary elements. Overwrites WP core 'hover' functionality.
		$document.on( 'click touchend', prefix + ' > .ab-sub-wrapper .ab-item', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			var $sub = $(this).parent('.menupop').children('.ab-sub-wrapper');
			if ( $sub.length ) {
				if ( $sub.hasClass('active') ) {
					$sub.slideUp('fast');
					$sub.removeClass('active');
				} else {
					$sub.slideDown('fast');
					$sub.addClass('active');
				}
			}
		} );

		/**
		 * @since  1.7  Mimic default form handling because this gets overwritten by WP core.
		 */
		// Form elements.
		$document.on( 'click touchend', prefix + 'input, ' + prefix + 'textarea, ' + prefix + 'select', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.stopPropagation();
			var $this = $(this);
			if ( $this.is('[type="checkbox"]') ) {
				// Checkboxes.
				e.preventDefault();
				if ( $this.is(':checked') ) {
					$this.prop( 'checked', false );
				} else {
					$this.prop( 'checked', true );
				}
				$this.trigger('change');
				return false;
			} else if ( $this.is('[type="radio"]') ) {
				// Radio.
				e.preventDefault();
				$('input[name="' + $this.attr['name'] + '"]').removeAttr('checked');
				$this.prop( 'checked', true );
				$this.trigger('change');
				return false;
			}
			return true;
		} );
		// Labels.
		$document.on( 'click touchend', prefix + 'label', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			e.stopPropagation();
			$( '#' + $(this).attr( 'for' ) ).trigger( e.type );
			return false;
		} );
	};

	/**
	 * Add an overlay.
	 *
	 * @param   {string}  html  The content to show in the overlay.
	 * @return  {null}  Nothing.
	 */
	VAA_View_Admin_As.overlay = function( html ) {
		var $overlay = $( '#vaa-overlay' );
		if ( ! $overlay.length ) {
			html = '<div id="vaa-overlay">' + html + '</div>';
			$body.append( html );
			$overlay = $( 'body #vaa-overlay' );
		} else if ( html.length ) {
			$overlay.html( html );
		}
		$overlay.fadeIn('fast');
	};

	/**
	 * Apply the selected view.
	 *
	 * @param   {object}   data     The data to send, view format: { VIEW_TYPE : VIEW_TYPE_DATA }
	 * @param   {boolean}  refresh  Reload/redirect the page?
	 * @return  {null}  Nothing.
	 */
	VAA_View_Admin_As.ajax = function( data, refresh ) {

		$( '.vaa-notice', '#wpadminbar' ).remove();
		// @todo dashicon loader?
		var loader_icon = VAA_View_Admin_As.siteurl + '/wp-includes/images/spinner-2x.gif';
		VAA_View_Admin_As.overlay( '<span class="vaa-loader-icon" style="background-image: url('+loader_icon+')"></span>' );

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

			$body.append('<form id="vaa_single_mode_form" style="display:none;" method="post"></form>');
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

				if ( true === VAA_View_Admin_As._debug ) {
					// Show debug info in console.
					console.log( response );
				}

				if ( response.hasOwnProperty( 'data' ) ) {
					if ( 'object' === typeof response.data ) {
						data = response.data;
						if ( data.hasOwnProperty( 'display' ) ) {
							display = data.display;
						}
					}
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
					data.type = ( success ) ? 'success' : 'error';
				}

				if ( 'popup' === display ) {
					VAA_View_Admin_As.popup( data, data.type );
				} else {
					if ( ! data.hasOwnProperty( 'text' ) ) {
						data.text = response.data;
					}
					VAA_View_Admin_As.notice( String( data.text ), data.type, 5000 );

					$('body #vaa-overlay').addClass( data.type ).fadeOut( 'fast', function() { $(this).remove(); } );
				}
			} );
		}
	};

	/**
	 * Reload the page or optionally redirect the user.
	 * @since  1.7
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {object}  data  Info for the redirect: { redirect: URL }
	 * @return {null}  Nothing.
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
	 * Show global notice.
	 * @since  1.0
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {string}  notice   The notice text.
	 * @param  {string}  type     The notice type (notice, error, message, warning, success).
	 * @param  {int}     timeout  Time to wait before auto-remove notice (milliseconds), pass `false` or `0` to prevent auto-removal.
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.notice = function( notice, type, timeout ) {
		var root = '#wpadminbar .vaa-notice',
			html = notice + '<span class="remove ab-icon dashicons dashicons-dismiss"></span>';

		type    = ( 'undefined' === typeof type ) ? 'notice' : type;
		timeout = ( 'undefined' === typeof timeout ) ? 5000 : timeout;

		if ( VAA_View_Admin_As._mobile ) {
			// Notice in VAA bar.
			html = '<div class="vaa-notice vaa-' + type + '" style="display: none;">' + html + '</div>';
			$( VAA_View_Admin_As.prefix + '> .ab-sub-wrapper').prepend( html ).children('.vaa-notice').slideDown( 'fast' );
			$( 'html, body' ).animate( { scrollTop: '0' } );
			// Remove it after # seconds.
			if ( timeout ) {
				setTimeout( function () { $( root ).slideUp( 'fast', function () { $( this ).remove(); } ); }, timeout );
			}
		} else {
			// Notice in top level admin bar.
			html = '<li class="vaa-notice vaa-' + type + '">' + html + '</li>';
			$('#wp-admin-bar-top-secondary').append( html );
			$( root + ' .remove' ).click( function() { $(this).parent().remove(); } );
			// Remove it after # seconds.
			if ( timeout ) {
				setTimeout( function () { $( root ).fadeOut( 'fast', function () { $( this ).remove(); } ); }, timeout );
			}
		}
	};

	/**
	 * Show notice for an item node.
	 * @since  1.7
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {string}  parent   The HTML element selector to add the notice to (selector or jQuery object).
	 * @param  {string}  notice   The notice text.
	 * @param  {string}  type     The notice type (notice, error, message, warning, success).
	 * @param  {int}     timeout  Time to wait before auto-remove notice (milliseconds), pass `false` or `0` to prevent auto-removal.
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.item_notice = function( parent, notice, type, timeout ) {
		var root = '.vaa-notice',
			html = notice + '<span class="remove ab-icon dashicons dashicons-dismiss"></span>',
			$element = $( parent );

		type    = ( 'undefined' === typeof type ) ? 'notice' : type;
		timeout = ( 'undefined' === typeof timeout ) ? 5000 : timeout;

		html = '<div class="vaa-notice vaa-' + type + '" style="display: none;">' + html + '</div>';
		$element.append( html ).children('.vaa-notice').slideDown( 'fast' );

		// Remove it after # seconds.
		if ( timeout ) {
			setTimeout( function(){ $( root, $element ).slideUp( 'fast', function() { $(this).remove(); } ); }, timeout );
		}
	};

	/**
	 * Show confirm warning.
	 * Returns a jQuery confirm element selector to add your own confirm actions.
	 * @since  1.7
	 * @param  {string}  parent  The HTML element selector to add the notice to (selector or jQuery object).
	 * @param  {string}  text    The confirm text.
	 * @return {object}  jQuery confirm element.
	 */
	VAA_View_Admin_As.item_confirm = function( parent, text ) {
		$( parent ).find( '.vaa-notice' ).slideUp( 'fast', function() { $(this).remove(); } );
		text = '<button class="vaa-confirm button"><span class="ab-icon dashicons dashicons-warning"></span>' + text + '</button>';
		VAA_View_Admin_As.item_notice( parent, text, 'warning', 0 );
		return $( parent ).find( '.vaa-confirm' );
	};

	/**
	 * Show popup with return content.
	 * @since  1.5
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {object}  data  Data to use.
	 * @param  {string}  type  The notice/overlay type (notice, error, message, warning, success).
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.popup = function( data, type ) {
		type = ( 'undefined' === typeof type ) ? 'notice' : type;

		/*
		 * Build overlay HTML.
		 */
		var html = '';

		html += '<div class="vaa-overlay-container vaa-' + type + '">';
		html += '<span class="remove dashicons dashicons-dismiss"></span>';
		html += '<div class="vaa-response-data">';

		// If it's not an object assume it's a string and convert it to the proper object form.
		if ( 'object' !== typeof data ) {
			data = { text: data };
		}

		// Simple text.
		if ( data.hasOwnProperty( 'text' ) ) {
			html += '<p>' + String( data.text ) + '</p>';
		}
		// List items.
		if ( data.hasOwnProperty( 'list' ) ) {
			html +=  '<ul>';
			data.list.forEach( function( item ) {
				html +=  '<li>' + String( item ) + '</li>';
			} );
			html +=  '</ul>';
		}
		// Textarea.
		if ( data.hasOwnProperty( 'textarea' ) ) {
			html += '<textarea style="width: 100%;" readonly>' + String( data.textarea ) + '</textarea>';
		}

		// End: .vaa-response-data & .vaa-overlay-container
		html += '</div></div>';

		// Trigger the overlay.
		VAA_View_Admin_As.overlay( html );

		/*
		 * Overlay handlers.
		 */
		var root = 'body #vaa-overlay',
			$overlay = $( root ),
			$overlayContainer = $( root + ' .vaa-overlay-container' ),
			$popupResponse = $( root + ' .vaa-response-data' );

		// Remove overlay.
		$( root + ' .vaa-overlay-container .remove' ).click( function() {
			$overlay.fadeOut( 'fast', function() { $(this).remove(); } );
		} );

		// Remove overlay on click outside of container.
		$document.on( 'mouseup', function( e ){
			$( root + ' .vaa-overlay-container' ).each( function(){
				if ( ! $(this).is( e.target ) && 0 === $(this).has( e.target ).length ) {
					$overlay.fadeOut( 'fast', function() { $(this).remove(); } );
				}
			} );
		} );

		var textarea = $( 'textarea', $popupResponse );
		if ( textarea.length ) {
			// Select full text on click.
			textarea.on( 'click', function() { $(this).select(); } );
		}

		var popupMaxHeight = function() {
			if ( textarea.length ) {
				textarea.each( function() {
					$( this ).css( { 'height': 'auto', 'overflow-y': 'hidden' } ).height( this.scrollHeight );
				});
			}
			// 80% of screen height - padding + border;
			var maxHeight = ( $overlay.height() * .8 ) - 24;
			$overlayContainer.css( 'max-height', maxHeight );
			$popupResponse.css( 'max-height', maxHeight );
		};
		popupMaxHeight();
		$window.on( 'resize', function() {
			popupMaxHeight();
		});
	};

	/**
	 * Automatic option handling.
	 * @since  1.7.2
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.init_auto_js = function() {

		$( VAA_View_Admin_As.root + ' [vaa-auto-js]' ).each( function() {
			var $this = $( this ),
				data = VAA_View_Admin_As.json_decode( $this.attr('vaa-auto-js') );
			if ( 'object' !== typeof data ) {
				return;
			}
			if ( ! data.hasOwnProperty('event') ) {
				data.event = 'change';
			}
			if ( 'click' === data.event ) {
				data.event = 'click touchend';
			}
			$this.on( data.event, function( e ) {
				e.preventDefault();
				VAA_View_Admin_As.do_auto_js( data, this );
				return false;
			} );
		} );

		/**
		 * Get the values of the option data.
		 * @since  1.7.2
		 * @param  {object} data {
		 *     The option data.
		 *     @type  {string}   setting  The setting key.
		 *     @type  {boolean}  confirm  Confirm before sending ajax?
		 *     @type  {boolean}  refresh  Refresh after sending ajax?
		 *     @type  {object}   values {
		 *         A object of multiple values as option_key => data (see below parameters).
		 *         Can also contain another values parameter to build the option data recursive.
		 *         @type  {boolean}  required   Whether this option is required or not (default: true).
		 *         @type  {mixed}    element    The option element (overwrites the second elem parameter).
		 *         @type  {string}   processor  The value processor.
		 *         @type  {string}   attr       Get an attribute value instead of using .val()?
		 *         OR
		 *         @type  {object}   values     An object of multiple values as option_key => data (see above parameters).
		 *     }
		 * }
		 * @param  {mixed}  elem  The element (runs through $() function).
		 * @return {object}
		 */
		VAA_View_Admin_As.do_auto_js = function( data, elem ) {
			if ( 'object' !== typeof data ) {
				return;
			}
			var $elem    = $( elem ),
				setting  = ( data.hasOwnProperty( 'setting' ) ) ? String( data.setting ) : null,
				confirm  = ( data.hasOwnProperty( 'confirm' ) ) ? Boolean( data.confirm ) : false,
				refresh  = ( data.hasOwnProperty( 'refresh' ) ) ? Boolean( data.refresh ) : false;

			var val = VAA_View_Admin_As.get_auto_js_values_recursive( data, elem );

			if ( null !== val ) {
				var view_data = {};
				view_data[ setting ] = val;

				if ( confirm ) {
					confirm = VAA_View_Admin_As.item_confirm( $elem.parent(), VAA_View_Admin_As.__confirm );
					$( confirm ).on( 'click', function() {
						VAA_View_Admin_As.ajax( view_data, refresh );
					} );
				} else {
					VAA_View_Admin_As.ajax( view_data, refresh );
				}
			} else {
				// @todo Notifications etc.
			}
		};

		/**
		 * Get the values of the option data.
		 * @since  1.7.2
		 * @param  {object} data {
		 *     The option data.
		 *     @type  {boolean}  required   Whether this option is required or not (default: true).
		 *     @type  {mixed}    element    The option element (overwrites the second elem parameter).
		 *     @type  {string}   processor  The value processor.
		 *     @type  {string}   attr       Get an attribute value instead of using .val()?
		 *     OR
		 *     @type  {object}   values     An object of multiple values as option_key => data (see above parameters).
		 * }
		 * @param  {mixed}  elem  The element (runs through $() function).
		 * @return {object}
		 */
		VAA_View_Admin_As.get_auto_js_values_recursive = function( data, elem ) {
			if ( 'object' !== typeof data ) {
				return null;
			}
			var stop = false,
				val = null;
			if ( data.hasOwnProperty( 'values' ) ) {
				val = {};
				$.each( data.values, function( val_key, auto_js ) {
					if ( 'object' !== typeof auto_js || null === auto_js ) {
						auto_js = {};
					}
					auto_js.required = ( auto_js.hasOwnProperty( 'required' ) ) ? Boolean( auto_js.required ) : true;

					var val_val = VAA_View_Admin_As.get_auto_js_values_recursive( auto_js, elem );

					if ( null === val_val ) {
						if ( auto_js.required ) {
							val = null;
							stop = true;
							return false;
						}
					} else {
						val[ val_key ] = val_val;
					}
				} );

				if ( stop ) {
					return null;
				}

			} else {
				val = VAA_View_Admin_As.get_auto_js_value( data, elem );
			}
			return val;
		};

		/**
		 * Get the value of an option through various processors.
		 * @since  1.7.2
		 * @param  {object} data {
		 *     The option data.
		 *     @type  {mixed}   element    The option element (overwrites the second elem parameter).
		 *     @type  {string}  processor  The value processor.
		 *     @type  {string}  attr       Get an attribute value instead of using .val()?
		 * }
		 * @param  {mixed}  elem  The element (runs through $() function).
		 * @return {*}
		 */
		VAA_View_Admin_As.get_auto_js_value = function( data, elem ) {
			if ( 'object' !== typeof data ) {
				return null;
			}
			var $elem = ( data.hasOwnProperty( 'element' ) ) ? $( data.element ) : $( elem ),
				val = null;
			if ( ! data.hasOwnProperty( 'processor' ) ) {
				data.processor = ( data.hasOwnProperty( 'attr' ) ) ? 'attr' : '';
			}
			switch ( data.processor ) {
				case 'multi':
					val = {};
					$elem.each( function() {
						var $this = $(this),
						    value = ( data.hasOwnProperty( 'attr' ) ) ? $this.attr( data.attr ) : $this.val();
						if ( 'checkbox' === $this.attr( 'type' ) ) {
							val[ value ] = this.checked;
						} else {
							val[ $this.attr('name') ] = value;
						}
					} );
					break;
				case 'selected':
					val = [];
					$elem.each( function() {
						var $this = $(this),
							value = ( data.hasOwnProperty( 'attr' ) ) ? $this.attr( data.attr ) : $this.val();
						if ( 'checkbox' === $this.attr( 'type' ) ) {
							if ( this.checked ) {
								val.push( value );
							}
						} else {
							val.push( value );
						}
					} );
					break;
				case 'json':
					try {
						val = JSON.parse( ( ( data.hasOwnProperty( 'attr' ) ) ? $elem.attr( data.attr ) : $elem.val() ) );
					} catch ( err ) {
						val = null;
						// @todo Improve error message.
						VAA_View_Admin_As.popup( '<pre>' + err + '</pre>', 'error' );
					}
					break;
				case 'attr':
					var attr = $elem.attr( data.attr );
					if ( attr ) {
						val = attr;
					}
					break;
				default:
					if ( 'checkbox' === $elem.attr( 'type' ) ) {
						val = $elem.is(':checked');
					} else {
						var value = ( data.hasOwnProperty( 'attr' ) ) ? $elem.attr( data.attr ) : $elem.val();
						if ( value ) {
							val = value;
						}
					}
					break;
			}
			return val;
		};
	};

	/**
	 * USERS.
	 * Extra functions for user views.
	 * @since  1.2
	 * @return {null}  Nothing.
	**/
	VAA_View_Admin_As.init_users = function() {

		var root = VAA_View_Admin_As.root + '-users',
			root_prefix = VAA_View_Admin_As.prefix + root;

		// Search users.
		$document.on( 'keyup', root_prefix + ' .ab-vaa-search.search-users input', function() {
			$( VAA_View_Admin_As.prefix + ' .ab-vaa-search .ab-vaa-results' ).empty();
			if ( 1 <= $(this).val().length ) {
				var inputText = $(this).val();
				$( VAA_View_Admin_As.prefix + '.vaa-user-item' ).each( function() {
					var name = $( '.ab-item', this ).text();
					if ( -1 < name.toLowerCase().indexOf( inputText.toLowerCase() ) ) {
						var exists = false;
						$( VAA_View_Admin_As.prefix + '.ab-vaa-search .ab-vaa-results .vaa-user-item .ab-item' ).each(function() {
							if ( -1 < $(this).text().indexOf(name) ) {
								exists = $(this);
							}
						} );
						var role = $(this).parents('.vaa-role-item').children('.ab-item').attr('rel');
						if ( false !== exists && exists.length ) {
							exists.find('.user-role').text( exists.find('.user-role').text().replace(')', ', ' + role + ')') );
						} else {
							$(this).clone()
							       .appendTo( VAA_View_Admin_As.prefix + '.ab-vaa-search .ab-vaa-results' )
							       .children('.ab-item')
							       .append(' &nbsp; <span class="user-role">(' + role + ')</span>');
						}
					}
				} );
				if ( '' === $.trim( $( VAA_View_Admin_As.prefix + '.ab-vaa-search .ab-vaa-results' ).html() ) ) {
					$( VAA_View_Admin_As.prefix + '.ab-vaa-search .ab-vaa-results' )
						.append('<div class="ab-item ab-empty-item vaa-not-found">' + VAA_View_Admin_As.__no_users_found + '</div>');
				}
			}
		} );
	};

	/**
	 * CAPABILITIES.
	 * @since  1.3
	 * @return {null}  Nothing.
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

		// Since  1.7  Get the selected capabilities.
		VAA_View_Admin_As.get_selected_capabilities = function() {
			var capabilities = {};
			$( root_prefix + '-select-options .vaa-cap-item input' ).each( function() {
				var val = $(this).attr('value');
				if ( 'undefined' === typeof capabilities[ val ] ) {
					if ( $(this).is(':checked') ) {
						capabilities[ val ] = 1;
					} else {
						capabilities[ val ] = 0;
					}
				}
			} );
			return capabilities;
		};

		// Since  1.7  Set the selected capabilities.
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
					$( 'input', this ).prop( "checked", true );
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
					$( 'input', this ).prop( "checked", false );
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
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.init_module_role_defaults = function() {

		var root = VAA_View_Admin_As.root + '-role-defaults',
			prefix = 'vaa-role-defaults',
			root_prefix = VAA_View_Admin_As.prefix + root;

		// @since  1.6.3  Add new meta.
		$document.on( 'click touchend', root_prefix + '-meta-add button#' + prefix + '-meta-add', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $( root_prefix + '-meta-add input#' + prefix + '-meta-new' ).val();
			var item = $( root_prefix + '-meta-add #' + prefix + '-meta-template' ).html().toString();
			val = val.replace( / /g, '_' );
			item = item.replace( /vaa_new_item/g, val );
			if ( $( root_prefix + '-meta-select input[value="' + val + '"]' ).length ) {
				VAA_View_Admin_As.item_notice( $(this).parent(), VAA_View_Admin_As.__key_already_exists, 'error', 2000 );
			} else {
				$( root_prefix + '-meta-select > .ab-item' ).prepend( item );
			}
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
	};

	/**
	 * MODULE: Role Manager.
	 * @since  1.7
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.init_module_role_manager = function() {

		var root = VAA_View_Admin_As.root + '-role-manager',
			prefix = 'vaa-role-manager',
			root_prefix = VAA_View_Admin_As.prefix + root;

		// @since  1.7  Apply current view capabilities to role.
		$document.on( 'click touchend', root_prefix + '-apply-view-apply button#' + prefix + '-apply-view-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var role = $( root_prefix + '-apply-view-select select#' + prefix + '-apply-view-select' ).val();
			var capabilities = JSON.parse( $(this).attr('data-view-caps') );
			if ( role && '' !== role && capabilities ) {
				var view_data = { role_manager : { apply_view_to_role : { role: role, capabilities: capabilities } } };
				VAA_View_Admin_As.ajax( view_data, true );
			}
			return false;
		} );

		/**
		 * Capability functions.
		 */
		var caps_root = VAA_View_Admin_As.root + '-caps-manager-role-manager',
			caps_prefix = 'vaa-caps-manager-role-manager',
			caps_root_prefix = VAA_View_Admin_As.prefix + caps_root;

		// @since  1.7  Update capabilities when selecting a role.
		$document.on( 'change', caps_root_prefix + ' select#' + caps_prefix + '-edit-role', function() {
			var $this = $(this),
				role  = $this.val(),
				caps  = {},
				selectedRoleElement = $( caps_root_prefix + ' select#' + caps_prefix + '-edit-role option[value="' + role + '"]' );
			if ( selectedRoleElement.attr('data-caps') ) {
				caps = JSON.parse( selectedRoleElement.attr('data-caps') );
			}

			// Reset role filters.
			VAA_View_Admin_As.caps_filter_settings.selectedRole = 'default';
			VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = {};
			VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = false;
			VAA_View_Admin_As.filter_capabilities();

			VAA_View_Admin_As.set_selected_capabilities( caps );
		} );

		// @since  1.7  Add/Modify roles.
		$document.on( 'click touchend', caps_root_prefix + ' button#' + caps_prefix + '-save-role', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var role = $( caps_root_prefix + ' select#' + caps_prefix + '-edit-role' ).val(),
				refresh = false;
			if ( ! role ) {
				return false;
			}
			if ( '__new__' === role ) {
				role = $( caps_root_prefix + ' input#' + caps_prefix + '-new-role' ).val();
				refresh = true;
			}
			var data = {
				role: role,
				capabilities: VAA_View_Admin_As.get_selected_capabilities()
			};
			VAA_View_Admin_As.ajax( { role_manager : { save_role : data } }, refresh );
			return false;
		} );

		// @since  1.7  Add new capabilities.
		$document.on( 'click touchend', caps_root_prefix + '-new-cap button#' + caps_prefix + '-add-cap', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var existing = VAA_View_Admin_As.get_selected_capabilities();
			var val = $( caps_root_prefix + '-new-cap input#' + caps_prefix + '-new-cap' ).val();
			var item = $( caps_root_prefix + '-new-cap #' + caps_prefix + '-cap-template' ).html().toString();
			val = val.replace( / /g, '_' );
			item = item.replace( /vaa_new_item/g, val );
			if ( 'undefined' !== typeof existing[ val ] ) {
				VAA_View_Admin_As.item_notice( $(this).parent(), VAA_View_Admin_As.__key_already_exists, 'error', 2000 );
			} else {
				$( VAA_View_Admin_As.root + '-caps-select-options > .ab-item' ).prepend( item );
			}
		} );
	};

	/**
	 * Auto resize max height elements.
	 * @since  1.7
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.autoMaxHeight = function() {
		if ( ! VAA_View_Admin_As.maxHeightListenerElements ) {
			return null;
		}
		setTimeout( function() {
			// @link  http://stackoverflow.com/questions/11193453/find-the-vertical-position-of-scrollbar-without-jquery
			var scrollTop = ( 'undefined' !== typeof window.pageYOffset ) ? window.pageYOffset : ( document.documentElement || document.body.parentNode || document.body ).scrollTop;

			VAA_View_Admin_As.maxHeightListenerElements.each( function() {
			var $element = $(this),
				count    = 0,
				wait     = setInterval( function() {
					var offset    = $element.offset(),
						offsetTop = ( offset.top - scrollTop );
					if ( $element.is(':visible') && 0 < offsetTop ) {
						clearInterval( wait );
						var maxHeight = $window.height() - offsetTop - 100;
						maxHeight = ( 100 < maxHeight ) ? maxHeight : 100;
						$element.css( { 'max-height': maxHeight + 'px' } );
					}
					count++;
					if ( 5 < count ) {
						clearInterval( wait );
					}
				}, 100 );
			} );
		}, 100 );
	};
	$window.on( 'resize', VAA_View_Admin_As.autoMaxHeight );

	// We require a nonce to use this plugin.
	if ( VAA_View_Admin_As.hasOwnProperty( '_vaa_nonce' ) ) {
		VAA_View_Admin_As.init();
	}

} ( jQuery ) );
