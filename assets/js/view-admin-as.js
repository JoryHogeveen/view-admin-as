/* eslint-disable no-extra-semi */
;/**
 * View Admin As
 * https://wordpress.org/plugins/view-admin-as/
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package View_Admin_As
 * @since   0.1
 * @version 1.7.4
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
	 * @property  {string}   _loader_icon   The loader icon URL.
	 * @property  {string}   _vaa_nonce
	 * @property  {boolean}  _debug
	 * @property  {string}   __no_users_found      'No users found.'.
	 * @property  {string}   __key_already_exists  'Key already exists.'.
	 * @property  {string}   __success             'Success'.
	 * @property  {string}   __confirm             'Are you sure?'.
	 * @property  {string}   __download            'Download'.
	 */
	var VAA_View_Admin_As = {};
}

( function( $ ) {

	VAA_View_Admin_As.prefix = '#wpadminbar #wp-admin-bar-vaa ';
	VAA_View_Admin_As.root = '#wp-admin-bar-vaa';

	var $document = $( document ),
		$window = $( window ),
		$body = $('body'),
		$vaa = $( VAA_View_Admin_As.prefix );

	VAA_View_Admin_As.maxHeightListenerElements = null;
	VAA_View_Admin_As._mobile = false;

	if ( ! VAA_View_Admin_As.hasOwnProperty( '_debug' ) ) {
		VAA_View_Admin_As._debug = false;
	}

	if ( ! VAA_View_Admin_As.hasOwnProperty( 'ajaxurl' ) ) {
		if ( 'undefined' === typeof ajaxurl ) {
			// Does not work with websites in sub-folders.
			var ajaxurl = window.location.origin + '/wp-admin/admin-ajax.php';
		}
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
	 * @param   {string}  val  The string to decode.
	 * @return  {string|object}  Parsed JSON object or original string.
	 */
	VAA_View_Admin_As.json_decode = function( val ) {
		if ( 0 === val.indexOf("{") || 0 === val.indexOf("[") ) {
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
	 * @return  {void}  Nothing.
	 */
	VAA_View_Admin_As.init = function() {

		VAA_View_Admin_As.init_caps();
		VAA_View_Admin_As.init_users();
		VAA_View_Admin_As.init_module_role_defaults();
		VAA_View_Admin_As.init_module_role_manager();

		// Functionality that require the document to be fully loaded.
		$window.on( 'load', function() {

			// Preload loader icon.
			if ( VAA_View_Admin_As._loader_icon ) {
				var loader_icon = new Image();
				loader_icon.src = VAA_View_Admin_As._loader_icon;
			}

			VAA_View_Admin_As.init_auto_js();

			// Load autoMaxHeight elements.
			VAA_View_Admin_As.maxHeightListenerElements = $( VAA_View_Admin_As.prefix + '.vaa-auto-max-height' );

			// Toggle content with title.
			$( VAA_View_Admin_As.prefix + '.ab-vaa-toggle' ).each( function() {
				var $this   = $(this),
					$toggle = $this.parent().children().not('.ab-vaa-toggle');
				if ( ! $this.hasClass('active') ) {
					$toggle.hide();
				}

				$this.on( 'click touchend', function( e ) {
					e.preventDefault();
					e.stopPropagation();
					if ( true === VAA_View_Admin_As._touchmove ) {
						return;
					}
					if ( $(this).hasClass('active') ) {
						$toggle.slideUp('fast');
						$(this).removeClass('active');
					} else {
						$toggle.slideDown('fast');
						$(this).addClass('active');
					}
					VAA_View_Admin_As.autoMaxHeight();
				} );

				// @since  1.6.1  Keyboard a11y.
				$this.on( 'keyup', function( e ) {
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
						$toggle.slideUp('fast');
						$(this).removeClass('active');
					} else if ( 13 === key || 32 === key || 40 === key ) {
						$toggle.slideDown('fast');
						$(this).addClass('active');
					}
					VAA_View_Admin_As.autoMaxHeight();
				} );
			} );

			/**
			 * @since  1.6.3  Toggle items on hover.
			 * @since  1.7.3  Allow multiple targets + add delay option.
 			 */
			$( VAA_View_Admin_As.prefix + '[vaa-showhide]' ).each( function() {
				var $this = $( this ),
					args = VAA_View_Admin_As.json_decode( $this.attr('vaa-showhide') ),
					delay = 200;
				if ( 'object' !== typeof args ) {
					args = { 0: { target: args, delay: delay } };
				}
				$.each( args, function( key, data ) {
					var timeout = null;
					// Don't validate target property. It's mandatory so let the console notify the developer.
					if ( ! data.hasOwnProperty( 'delay' ) ) {
						data.delay = delay;
					}
					var $target = $( data.target );
					$target.hide();
					$this.on( 'mouseenter', function() {
						timeout = setTimeout( function() {
							$target.slideDown('fast');
						}, data.delay );
					}).on( 'mouseleave', function() {
						if ( timeout ) {
							clearTimeout( timeout );
						}
						$target.slideUp('fast');
					} );
				} );
			} );

			// @since  1.7  Conditional items.
			$( VAA_View_Admin_As.prefix + '[vaa-condition-target]' ).each( function() {
				var $this    = $(this),
					$target  = $( $this.attr( 'vaa-condition-target' ) ),
					checkbox = ( 'checkbox' === $target.attr('type') ),
					compare  = $this.attr( 'vaa-condition' );
				if ( checkbox ) {
					if ( 'undefined' !== typeof compare ) {
						compare = Boolean( compare );
					} else {
						compare = true;
					}
				}
				$this.hide();
				$target.on( 'change', function() {

					if ( checkbox && $target.is(':checked') ) {
						if ( compare ) {
							$this.slideDown('fast');
						} else {
							$this.slideUp('fast');
						}
					} else if ( ! checkbox && compare === $target.val() ) {
						$this.slideDown('fast');
					} else {
						$this.slideUp('fast');
					}

					VAA_View_Admin_As.autoMaxHeight();

				} ).trigger('change'); // Trigger on load.
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

			// @since  1.7.4  Auto resizable.
			$( VAA_View_Admin_As.prefix + '.vaa-resizable' ).each( function() {
				var $this = $( this ),
					height = $this.css( 'max-height' );
				$this.css( {
					'max-height': 'none',
					'height': height,
					'resize': 'vertical'
				} );
			} );

		} ); // End window.load.

		// Process reset.
		$vaa.on( 'click touchend', '.vaa-reset-item > .ab-item', function( e ) {
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
			$vaa.on( 'click touchend', '.vaa-' + type + '-item > a.ab-item', function( e ) {
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
		$vaa.on( 'click touchend', '.remove', function( e ) {
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
	 * @return  {void}  Nothing.
	 */
	VAA_View_Admin_As.mobile = function() {
		var $root = $( '.vaa-mobile ' + VAA_View_Admin_As.prefix );

		// @since  1.7  Fix for clicking within sub secondary elements. Overwrites WP core 'hover' functionality.
		$root.on( 'click touchend', ' > .ab-sub-wrapper .ab-item', function( e ) {
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
		$root.on( 'click touchend', 'input, textarea, select', function( e ) {
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
		$root.on( 'click touchend', 'label', function( e ) {
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
	 * @since   1.7
	 * @param   {string|boolean}  html  The content to show in the overlay. Pass `false` to remove the overlay.
	 * @return  {void}  Nothing.
	 */
	VAA_View_Admin_As.overlay = function( html ) {
		var $overlay = $( '#vaa-overlay' );
		if ( false === html ) {
			$overlay.fadeOut( 'fast', function() { $(this).remove(); } );
			$document.off( 'mouseup.vaa_overlay' );
			return;
		}
		if ( ! $overlay.length ) {
			html = '<div id="vaa-overlay">' + html + '</div>';
			$body.append( html );
			$overlay = $( 'body #vaa-overlay' );
		} else if ( html.length ) {
			$overlay.html( html );
		}
		$overlay.fadeIn('fast');

		// Remove overlay.
		$( '.remove', $overlay ).click( function() {
			VAA_View_Admin_As.overlay( false );
		} );

		// Remove overlay on click outside of container.
		$document.on( 'mouseup.vaa_overlay', function( e ) {
			$( '.vaa-overlay-container', $overlay ).each( function() {
				if ( ! $(this).is( e.target ) && 0 === $(this).has( e.target ).length ) {
					VAA_View_Admin_As.overlay( false );
					return false;
				}
			} );
		} );
	};

	/**
	 * Apply the selected view.
	 *
	 * @param   {object}   data     The data to send, view format: { VIEW_TYPE : VIEW_TYPE_DATA }
	 * @param   {boolean}  refresh  Reload/redirect the page?
	 * @return  {void}  Nothing.
	 */
	VAA_View_Admin_As.ajax = function( data, refresh ) {

		$( '.vaa-notice', '#wpadminbar' ).remove();
		// @todo dashicon loader?
		var loader_icon = '';
		if ( VAA_View_Admin_As._loader_icon ) {
			loader_icon = ' style="background-image: url(' + VAA_View_Admin_As._loader_icon + ')"';
		}
		VAA_View_Admin_As.overlay( '<span class="vaa-loader-icon"'+loader_icon+'></span>' );

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
			var $form = $('#vaa_single_mode_form');
			$form.append('<input type="hidden" name="action" value="' + post_data.action + '">');
			$form.append('<input type="hidden" name="_vaa_nonce" value="' + post_data._vaa_nonce + '">');
			$form.append('<input id="data" type="hidden" name="view_admin_as">');
			$form.find('#data').val( post_data.view_admin_as );
			$form.submit();

		} else {

			$.post( VAA_View_Admin_As.ajaxurl, post_data, function( response ) {
				var success = ( response.hasOwnProperty( 'success' ) && true === response.success ),
					data = {},
					display = false;

				// Maybe show debug info in console.
				VAA_View_Admin_As.debug( response );

				if ( response.hasOwnProperty( 'data' ) ) {
					if ( 'object' === typeof response.data ) {
						data = response.data;
						if ( data.hasOwnProperty( 'display' ) ) {
							display = data.display;
						}
					}
				}

				if ( success ) {
					// @todo Enhance download handler.
					if ( 'download' === refresh ) {
						VAA_View_Admin_As.download( data );
						VAA_View_Admin_As.overlay( false );
						return;
					} else if ( refresh ) {
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
	 * @see    VAA_View_Admin_As.ajax()
	 * @param  {object}  data  Info for the redirect: { redirect: URL }
	 * @return {void}  Nothing.
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
	 * @see    VAA_View_Admin_As.ajax()
	 * @param  {string}  notice   The notice text.
	 * @param  {string}  type     The notice type (notice, error, message, warning, success).
	 * @param  {int}     timeout  Time to wait before auto-remove notice (milliseconds), pass `false` or `0` to prevent auto-removal.
	 * @return {void}  Nothing.
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
				setTimeout( function () { $( root ).slideUp( 'fast', function () { $(this).remove(); } ); }, timeout );
			}
		} else {
			// Notice in top level admin bar.
			html = '<li class="vaa-notice vaa-' + type + '">' + html + '</li>';
			$('#wp-admin-bar-top-secondary').append( html );
			$( root + ' .remove' ).click( function() { $(this).parent().remove(); } );
			// Remove it after # seconds.
			if ( timeout ) {
				setTimeout( function () { $( root ).fadeOut( 'fast', function () { $(this).remove(); } ); }, timeout );
			}
		}
	};

	/**
	 * Show notice for an item node.
	 * @since  1.7
	 * @see    VAA_View_Admin_As.ajax()
	 * @param  {string}  parent   The HTML element selector to add the notice to (selector or jQuery object).
	 * @param  {string}  notice   The notice text.
	 * @param  {string}  type     The notice type (notice, error, message, warning, success).
	 * @param  {int}     timeout  Time to wait before auto-remove notice (milliseconds), pass `false` or `0` to prevent auto-removal.
	 * @return {void}  Nothing.
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
	 * @since  1.7  Renamed from VAA_View_Admin_As.overlay()
	 * @see    VAA_View_Admin_As.ajax()
	 * @param  {object}  data  Data to use.
	 * @param  {string}  type  The notice/overlay type (notice, error, message, warning, success).
	 * @return {void}  Nothing.
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
		var root = '#vaa-overlay',
			$overlay = $( root ),
			$overlay_container = $( root + ' .vaa-overlay-container' ),
			$popup_response = $( root + ' .vaa-response-data' );

		var textarea = $( 'textarea', $popup_response );
		if ( textarea.length ) {
			// Select full text on click.
			textarea.on( 'click', function() { $(this).select(); } );
		}

		var popupMaxHeight = function() {
			if ( textarea.length ) {
				textarea.each( function() {
					$(this).css( { 'height': 'auto', 'overflow-y': 'hidden' } ).height( this.scrollHeight );
				});
			}
			// 80% of screen height - padding + border;
			var max_height = ( $overlay.height() * .8 ) - 24;
			$overlay_container.css( 'max-height', max_height );
			$popup_response.css( 'max-height', max_height );
		};
		popupMaxHeight();
		$window.on( 'resize', function() {
			popupMaxHeight();
		});
	};

	/**
	 * Download text content as a file.
	 * @since  1.7.4
	 * @see    VAA_View_Admin_As.ajax()
	 * @param  {object|string}  data  Data to use.
	 * @return {void}  Nothing.
	 */
	VAA_View_Admin_As.download = function( data ) {
		var content = '',
			filename = '';
		if ( 'string' === typeof data ) {
			content = data;
		} else {
			if ( data.hasOwnProperty( 'download' ) ) {
				content = String( data.download );
			} else if ( data.hasOwnProperty( 'textarea' ) ) {
				content = String( data.textarea );
			} else if ( data.hasOwnProperty( 'content' ) ) {
				content = String( data.content );
			}
		}

		// Maybe format JSON data.
		content = VAA_View_Admin_As.json_decode( content );
		if ( 'object' === typeof content ) {
			content = JSON.stringify( content, null, '\t' );
		}

		if ( ! content ) {
			return; //@todo Notice.
		}

		if ( data.hasOwnProperty( 'filename' ) ) {
			filename = data.filename;
		}

		// https://stackoverflow.com/questions/3665115/create-a-file-in-memory-for-user-to-download-not-through-server
		var link = 'data:application/octet-stream;charset=utf-8,' + encodeURIComponent( content );

		$body.append( '<a id="vaa_temp_download" href="' + link + '" download="' + String( filename ) + '"></a>' );
		document.getElementById('vaa_temp_download').click();
		$( 'a#vaa_temp_download' ).remove();
	};

	/**
	 * Automatic option handling.
	 * @since  1.7.2
	 * @return {void}  Nothing.
	 */
	VAA_View_Admin_As.init_auto_js = function() {

		$( VAA_View_Admin_As.root + ' [vaa-auto-js]' ).each( function() {
			var $this = $(this),
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
				if ( 'change' !== data.event && true === VAA_View_Admin_As._touchmove ) {
					return;
				}
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
		 *         @type  {string}   parser     The value parser.
		 *         @type  {string}   attr       Get an attribute value instead of using .val()?
		 *         OR
		 *         @type  {object}   values     An object of multiple values as option_key => data (see above parameters).
		 *     }
		 * }
		 * @param  {mixed}  elem  The element (runs through $() function).
		 * @return {void} Nothing.
		 */
		VAA_View_Admin_As.do_auto_js = function( data, elem ) {
			if ( 'object' !== typeof data ) {
				return;
			}
			var $elem    = $( elem ),
				setting  = ( data.hasOwnProperty( 'setting' ) ) ? String( data.setting ) : null,
				confirm  = ( data.hasOwnProperty( 'confirm' ) ) ? Boolean( data.confirm ) : false,
				refresh  = ( data.hasOwnProperty( 'refresh' ) ) ? Boolean( data.refresh ) : false;

			// Callback overwrite.
			if ( data.hasOwnProperty('callback') ) {
				VAA_View_Admin_As[ data.callback ]( data );
				return;
			}

			var val = VAA_View_Admin_As.get_auto_js_values_recursive( data, elem );

			if ( null !== val ) {

				if ( ! setting ) {
					return;
				}

				var view_data = {};
				view_data[ setting ] = val;

				// @todo Enhance download handler.
				if ( data.hasOwnProperty( 'download' ) && data.download ) {
					refresh = 'download';
				}

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
		 *     @type  {boolean}  required  Whether this option is required or not (default: true).
		 *     @type  {mixed}    element   The option element (overwrites the second elem parameter).
		 *     @type  {string}   parser    The value parser.
		 *     @type  {string}   attr      Get an attribute value instead of using .val()?
		 *     OR
		 *     @type  {object}   values    An object of multiple values as option_key => data (see above parameters).
		 * }
		 * @param  {mixed}  elem  The element (runs through $() function).
		 * @return {object} Value data.
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

					if ( null !== val_val ) {
						val[ val_key ] = val_val;
					} else if ( auto_js.required ) {
						val = null;
						stop = true;
						return false;
					}
				} );

				if ( stop ) {
					return null;
				}

			} else {
				val = VAA_View_Admin_As.parse_auto_js_value( data, elem );
			}
			return val;
		};

		/**
		 * Get the value of an option through various parsers.
		 * @since  1.7.2
		 * @param  {object} data {
		 *     The option data.
		 *     @type  {mixed}    element  The option element (overwrites the second elem parameter).
		 *     @type  {string}   parser   The value parser.
		 *     @type  {string}   attr     Get an attribute value instead of using .val()?
		 *     @type  {boolean}  attr     Parse as JSON?
		 * }
		 * @param  {mixed}  elem  The element (runs through $() function).
		 * @return {*} Value.
		 */
		VAA_View_Admin_As.parse_auto_js_value = function( data, elem ) {
			if ( 'object' !== typeof data ) {
				return null;
			}
			var $elem  = ( data.hasOwnProperty( 'element' ) ) ? $( data.element ) : $( elem ),
				parser = ( data.hasOwnProperty( 'parser' ) ) ? String( data.parser ) : '',
				val    = null;

			switch ( parser ) {

				case 'multiple':
				case 'multi':
					val = {};
					$elem.each( function() {
						var $this = $(this),
							value;
						if ( 'checkbox' === $this.attr( 'type' ) ) {
							// JSON not supported and always a boolean value.
							value = ( data.hasOwnProperty( 'attr' ) ) ? $this.attr( data.attr ) : $this.val();
							val[ value ] = this.checked;
						} else {
							value = VAA_View_Admin_As.get_auto_js_value( this, data );
							val[ $this.attr('name') ] = value;
						}
					} );
					break;

				case 'selected':
					val = [];
					$elem.each( function() {
						var $this = $(this),
							value;
						if ( 'checkbox' === $this.attr( 'type' ) ) {
							// JSON not supported.
							value = ( data.hasOwnProperty( 'attr' ) ) ? $this.attr( data.attr ) : $this.val();
							if ( this.checked && value ) {
								val.push( value );
							}
						} else {
							value = VAA_View_Admin_As.get_auto_js_value( this, data );
							if ( value ) {
								val.push( value );
							}
						}
					} );
					break;

				default:
					val = VAA_View_Admin_As.get_auto_js_value( $elem, data );
					break;

			}

			return val;
		};

		/**
		 * Get the value of an option through various parsers.
		 * @since  1.7.2
		 * @param  {mixed}  elem  Required. The element (runs through $() function).
		 * @param  {object} data {
		 *     The option data.
		 *     @type  {string}   attr  Optional. Get an attribute value instead of using .val()?
		 *     @type  {boolean}  json  Optional. Parse as JSON?
		 * }
		 * @return {*} Value.
		 */
		VAA_View_Admin_As.get_auto_js_value = function( elem, data ) {
			if ( 'object' !== typeof data ) {
				data = {};
			}
			var $elem = $( elem ),
				val = null,
				attr = ( data.hasOwnProperty( 'attr' ) ) ? String( data.attr ) : false,
				json = ( data.hasOwnProperty( 'json' ) ) ? Boolean( data.json ) : false,
				value = ( attr ) ? $elem.attr( attr ) : $elem.val();

			if ( 'checkbox' === $elem.attr( 'type' ) ) {
				var checked = $elem.is(':checked');
				if ( attr ) {
					if ( checked && value ) {
						val = value;
					}
				} else {
					val = checked;
				}
			} else {
				if ( value ) {
					val = value;
				}
			}

			if ( json ) {
				try {
					val = JSON.parse( val );
				} catch ( err ) {
					val = null;
					// @todo Improve error message.
					VAA_View_Admin_As.popup( '<pre>' + err + '</pre>', 'error' );
				}
			}

			return val;
		}
	};

	/**
	 * USERS.
	 * Extra functions for user views.
	 * @since  1.2
	 * @return {void}  Nothing.
	**/
	VAA_View_Admin_As.init_users = function() {

		var root = VAA_View_Admin_As.root + '-users',
			root_prefix = VAA_View_Admin_As.prefix + root,
			$root = $( root_prefix );

		// Search users.
		$root.on( 'keyup', '.ab-vaa-search.search-users input', function() {
			$( VAA_View_Admin_As.prefix + ' .ab-vaa-search .ab-vaa-results' ).empty();
			var input_text = $(this).val();
			if ( 1 <= input_text.length ) {
				$( VAA_View_Admin_As.prefix + '.vaa-user-item' ).each( function() {
					var name = $( '.ab-item', this ).text();
					if ( -1 < name.toLowerCase().indexOf( input_text.toLowerCase() ) ) {
						var exists = false;
						$( VAA_View_Admin_As.prefix + '.ab-vaa-search .ab-vaa-results .vaa-user-item .ab-item' ).each(function() {
							if ( -1 < $(this).text().indexOf( name ) ) {
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
	 * @return {void}  Nothing.
	**/
	VAA_View_Admin_As.init_caps = function() {

		var root = VAA_View_Admin_As.root + '-caps',
			root_prefix = VAA_View_Admin_As.prefix + root,
			$root = $( root_prefix );

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
					capabilities[ val ] = $(this).is(':checked');
				}
			} );
			return capabilities;
		};

		// Since  1.7  Set the selected capabilities.
		VAA_View_Admin_As.set_selected_capabilities = function( capabilities ) {
			$( root_prefix + '-select-options .vaa-cap-item input' ).each( function() {
				var $this = $(this);
				if ( capabilities.hasOwnProperty( $this.attr('value') ) ) {
					if ( capabilities[ $this.attr('value') ] ) {
						$this.attr( 'checked', 'checked' );
					} else {
						$this.attr( 'checked', false );
					}
				} else {
					$this.attr( 'checked', false );
				}
			} );
		};

		// Enlarge caps.
		$root.on( 'click', '#open-caps-popup', function() {
			$( VAA_View_Admin_As.prefix ).addClass('fullPopupActive');
			$( root_prefix + '-manager > .ab-sub-wrapper' ).addClass('fullPopup');
			VAA_View_Admin_As.autoMaxHeight();
		} );
		// Undo enlarge caps.
		$root.on( 'click', '#close-caps-popup', function() {
			$( VAA_View_Admin_As.prefix ).removeClass('fullPopupActive');
			$( root_prefix + '-manager > .ab-sub-wrapper' ).removeClass('fullPopup');
			VAA_View_Admin_As.autoMaxHeight();
		} );

		// Select role capabilities.
		$root.on( 'change', '.ab-vaa-select.select-role-caps select', function() {
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
		$root.on( 'keyup', '.ab-vaa-filter input', function() {
			VAA_View_Admin_As.caps_filter_settings.filterString = $(this).val();
			VAA_View_Admin_As.filter_capabilities();
		} );

		// Select all capabilities.
		$root.on( 'click touchend', 'button#select-all-caps', function( e ) {
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
		$root.on( 'click touchend', 'button#deselect-all-caps', function( e ) {
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
		$root.on( 'click touchend', 'button#apply-caps-view', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var new_caps = VAA_View_Admin_As.get_selected_capabilities();
			VAA_View_Admin_As.ajax( { caps : new_caps }, true );
			return false;
		} );
	};

	/**
	 * MODULE: Role Defaults.
	 * @since  1.4
	 * @return {void}  Nothing.
	 */
	VAA_View_Admin_As.init_module_role_defaults = function() {

		var root = VAA_View_Admin_As.root + '-role-defaults',
			prefix = 'vaa-role-defaults',
			root_prefix = VAA_View_Admin_As.prefix + root,
			$root = $( root_prefix );

		// @since  1.6.3  Add new meta.
		$root.on( 'click touchend', root + '-meta-add button#' + prefix + '-meta-add', function( e ) {
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
		$root.on( 'keyup', root + '-bulk-users-filter input#' + prefix + '-bulk-users-filter', function( e ) {
			e.preventDefault();
			if ( 1 <= $(this).val().length ) {
				var input_text = $(this).val();
				$( root_prefix + '-bulk-users-select .ab-item.vaa-item' ).each( function() {
					var name = $('.user-name', this).text();
					if ( -1 < name.toLowerCase().indexOf( input_text.toLowerCase() ) ) {
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
	 * @return {void}  Nothing.
	 */
	VAA_View_Admin_As.init_module_role_manager = function() {

		/**
		 * Capability functions.
		 */
		var root = VAA_View_Admin_As.root + '-caps-manager-role-manager',
			prefix = 'vaa-caps-manager-role-manager',
			root_prefix = VAA_View_Admin_As.prefix + root,
			$root = $( root_prefix );

		// @since  1.7  Update capabilities when selecting a role.
		$root.on( 'change', 'select#' + prefix + '-edit-role', function() {
			var $this = $(this),
				role  = $this.val(),
				caps  = {},
				$selected_role = $( root_prefix + ' select#' + prefix + '-edit-role option[value="' + role + '"]' );
			if ( $selected_role.attr('data-caps') ) {
				caps = JSON.parse( $selected_role.attr('data-caps') );
			}

			// Reset role filters.
			VAA_View_Admin_As.caps_filter_settings.selectedRole = 'default';
			VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = {};
			VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = false;
			VAA_View_Admin_As.filter_capabilities();

			VAA_View_Admin_As.set_selected_capabilities( caps );
		} );

		// @since  1.7  Add/Modify roles.
		$root.on( 'click touchend', 'button#' + prefix + '-save-role', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var role = $( root_prefix + ' select#' + prefix + '-edit-role' ).val(),
				refresh = ( VAA_View_Admin_As.view.hasOwnProperty( 'role' ) && role === VAA_View_Admin_As.view.role );
			if ( ! role ) {
				return false;
			}
			if ( '__new__' === role ) {
				role = $( root_prefix + ' input#' + prefix + '-new-role' ).val();
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
		$root.on( 'click touchend', root + '-new-cap button#' + prefix + '-add-cap', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var existing = VAA_View_Admin_As.get_selected_capabilities();
			var val = $( root_prefix + '-new-cap input#' + prefix + '-new-cap' ).val();
			var item = $( root_prefix + '-new-cap #' + prefix + '-cap-template' ).html().toString();
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
	 * Read file content and place it in a target value.
	 * https://developer.mozilla.org/en-US/docs/Web/API/FileReader
	 * http://www.javascripture.com/FileReader
	 *
	 * @todo Is this the correct method name?
	 *
	 * @since  1.7.4
	 * @param  {object}  data  The auto_js data.
	 * @return {void}  Nothing.
	 */
	VAA_View_Admin_As.assign_file_content = function( data ) {
		if ( 'function' !== typeof FileReader ) {
			// @todo Remove file element on load if the browser doesn't support FileReader.
			return;
		}
		var param    = ( data.hasOwnProperty('param') ) ? data.param : {},
			$target  = ( param.hasOwnProperty('target') ) ? $( param.target ) : null,
			$element = ( param.hasOwnProperty('element') ) ? $( param.element )  : null,
			wait     = true;

		if ( ! $target || ! $element ) {
			return;
		}

		var files  = $element[0].files,
			length = files.length,
			val    = '';

		if ( length ) {
			$.each( files, function( key, file ) {
				var reader = new FileReader();
				reader.onload = function() { //progressEvent
					var content = VAA_View_Admin_As.json_decode( this.result );
					if ( 'object' === typeof content ) {
						// Remove JSON format.
						content = JSON.stringify( content );
					}
					val += content;
					length--;
					if ( ! length ) {
						wait = false;
					}
				};
				reader.readAsText( file );
			} );
		}

		var areWeThereYet = setInterval( function() {
			if ( ! wait ) {
				$target.val( val );
				clearInterval( areWeThereYet );
			}
		}, 100 );
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
			var scroll_top = ( 'undefined' !== typeof window.pageYOffset ) ? window.pageYOffset : ( document.documentElement || document.body.parentNode || document.body ).scrollTop;

			VAA_View_Admin_As.maxHeightListenerElements.each( function() {
			var $element = $(this),
				count    = 0,
				wait     = setInterval( function() {
					var offset     = $element.offset(),
						offset_top = ( offset.top - scroll_top );
					if ( $element.is(':visible') && 0 < offset_top ) {
						clearInterval( wait );
						var max_height = $window.height() - offset_top - 100;
						max_height = ( 100 < max_height ) ? max_height : 100;
						$element.css( { 'max-height': max_height + 'px' } );
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

	/**
	 * Maybe show a debug message.
	 * @since  1.7.4
	 * @param  {mixed} message The data to debug.
	 * @return {null}  Nothing.
	 */
	VAA_View_Admin_As.debug = function( message ) {
		if ( true === VAA_View_Admin_As._debug ) {
			// Show debug info in console.
			console.log( message );
		}
	};

	// We require a nonce to use this plugin.
	if ( VAA_View_Admin_As.hasOwnProperty( '_vaa_nonce' ) ) {
		VAA_View_Admin_As.init();
	}

} ( jQuery ) );
