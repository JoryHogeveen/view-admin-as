;/**
 * View Admin As
 * https://wordpress.org/plugins/view-admin-as/
 *
 * @author  Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @since   0.1
 * @version 1.6.3
 * @preserve
 */

if ( 'undefined' === typeof VAA_View_Admin_As ) {
	var VAA_View_Admin_As = {
		ajaxurl: null,
		siteurl: '',
		view_as: false,
		view_types: [ 'user', 'role', 'caps', 'visitor' ],
		_debug: false,
		_vaa_nonce: '',
		__no_users_found: 'No users found.',
		__success: 'Success',
		__confirm: 'Are you sure?'
	};
}

( function( $ ) {

	VAA_View_Admin_As.prefix = '#wpadminbar #wp-admin-bar-vaa ';
	VAA_View_Admin_As.root = '#wp-admin-bar-vaa';

	if ( 'undefined' === typeof VAA_View_Admin_As._debug ) {
		VAA_View_Admin_As._debug = 0;
	} else {
		VAA_View_Admin_As._debug = parseInt( VAA_View_Admin_As._debug, 10 );
	}

	if ( 'undefined' === typeof VAA_View_Admin_As.ajaxurl && 'undefined' !== typeof ajaxurl ) {
		VAA_View_Admin_As.ajaxurl = ajaxurl;
	}

	// @since  1.6.1  Prevent swipe events to be seen as a click (bug in some browsers)
	VAA_View_Admin_As._touchmove = false;
	$( document ).on( 'touchmove', function() {
		VAA_View_Admin_As._touchmove = true;
	} );
	$( document ).on( 'touchstart', function() {
		VAA_View_Admin_As._touchmove = false;
	} );

	/**
	 * BASE INIT
	**/
	VAA_View_Admin_As.init = function() {

		VAA_View_Admin_As.init_caps();
		VAA_View_Admin_As.init_users();
		VAA_View_Admin_As.init_settings();
		VAA_View_Admin_As.init_module_role_defaults();

		// Functionality that require the document to be fully loaded
		$(window).on("load", function() {

			// Toggle content with title
			$(VAA_View_Admin_As.prefix+'.ab-vaa-toggle').each( function() {
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
				});

				// @since  1.6.1  Keyboard a11y
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
					if ( $(this).hasClass('active') && ( key === 13 || key === 32 || key === 38 ) ) {
						toggleContent.slideUp('fast');
						$(this).removeClass('active');
					} else if ( key === 13 || key === 32 || key === 40 ) {
						toggleContent.slideDown('fast');
						$(this).addClass('active');
					}
				});
			});

			// @since  1.6.3  Toggle items on hover
			$(VAA_View_Admin_As.prefix+'.ab-vaa-showhide[data-showhide]').each( function() {
				$( $(this).attr('data-showhide') ).hide();
				$(this).on('mouseenter', function() {
					$( $(this).attr('data-showhide') ).slideDown('fast');
				}).on('mouseleave', function() {
					$( $(this).attr('data-showhide') ).slideUp('fast');
				});
			});

		}); // End window.load

		// Process reset
		$(document).on('click touchend', VAA_View_Admin_As.prefix+'.vaa-reset-item > .ab-item', function( e ) {
			e.preventDefault();
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			if ( $('button', this).attr('name') === 'vaa_reload' ) {
				window.location.reload();
			} else {
				VAA_View_Admin_As.ajax( { reset : true }, true );
				return false;
			}
		});

		// @since  1.6.2  Process basic views
		$.each( VAA_View_Admin_As.view_types, function( index, type ) {
			$(document).on('click touchend', VAA_View_Admin_As.prefix+'.vaa-'+type+'-item > a.ab-item', function( e ) {
				if ( true === VAA_View_Admin_As._touchmove ) {
					return;
				}
				e.preventDefault();
				if ( ! $(this).parent().hasClass('not-a-view') ) {
					var viewAs = {};
					viewAs[ type ] = String( $(this).attr('rel') );
					VAA_View_Admin_As.ajax( viewAs, true );
					return false;
				}
			});
		} );

		// @since  1.6.3  Removable items
		$(document).on('click touchend', VAA_View_Admin_As.prefix+'.ab-item > .remove', function( e ) {
			e.preventDefault();
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			$(this).parent('.ab-item').slideUp('fast', function() { $(this).remove(); });
		});
	};


	/**
	 * Apply the selected view
	 * viewAs format: { VIEW_TYPE : VIEW_DATA }
	 *
	 * @params  {object}   viewAs
	 * @params  {boolean}  reload
	 */
	VAA_View_Admin_As.ajax = function( viewAs, reload ) {

		var body = $('body');

		$('.vaa-notice', '#wpadminbar').remove();
		// @todo dashicon loader?
		body.append('<div id="vaa-overlay"><span class="vaa-loader-icon" style="background: transparent url('+VAA_View_Admin_As.siteurl+'/wp-includes/images/spinner-2x.gif) center center no-repeat; background-size: contain;"></span></div>');
		$('body #vaa-overlay').fadeIn('fast');

		var fullPopup = false;
		if ( $(VAA_View_Admin_As.prefix).hasClass('fullPopupActive') ) {
			fullPopup = true;
			$(VAA_View_Admin_As.prefix).removeClass('fullPopupActive');
		}

		var data = {
			'action': 'view_admin_as',
			'_vaa_nonce': VAA_View_Admin_As._vaa_nonce,
			// @since  1.6.2  Use JSON data
			'view_admin_as': JSON.stringify( viewAs )
		};

		var isView = false;
		$.each( VAA_View_Admin_As.view_types, function( index, type ) {
			if ( typeof viewAs[ type ] !== 'undefined' ) {
				isView = true;
				return true;
			}
		});

		/**
		 *  @since  1.5  Check view mode
		 *  @todo   Improve form creation
 		 */
		if ( $(VAA_View_Admin_As.prefix+'#vaa-settings-view-mode-single').is(':checked') && isView ) {

			body.append('<form id="vaa_single_mode_form" style="display:none;" method="post"></form>');
			var form = $('#vaa_single_mode_form');
			form.append('<input type="hidden" name="action" value="' + data.action + '">');
			form.append('<input type="hidden" name="_vaa_nonce" value="' + data._vaa_nonce + '">');
			form.append('<input id="data" type="hidden" name="view_admin_as">');
			form.find('#data').val( data.view_admin_as );
			form.submit();

		} else {

			$.post( VAA_View_Admin_As.ajaxurl, data, function(response) {
				if ( VAA_View_Admin_As._debug === 1 ) { console.log(response); }
				if ( typeof response.success !== 'undefined' && true === response.success ) {
					if ( false === reload ) {
						// Check if we have more detailed data to show
						if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.content ) {
							if ( typeof response.data.type === 'undefined' ) {
								response.data.type = 'default';
							}
							if ( 'object' !== typeof response.data.content ) {
								response.data.content = String( response.data.content );
							}
							VAA_View_Admin_As.overlay( response.data.content, String( response.data.type ) );
						} else {
							$('body #vaa-overlay').addClass('success').fadeOut( 'fast', function() { $(this).remove(); } );
							VAA_View_Admin_As.notice( VAA_View_Admin_As.__success, 'success' );
						}
					} else {
						/**
						 * Reload the page
						 * Currently I use "replace" since no history seems necessary. Other option would be "assign" which enables history.
						 * @since  1.6.1  Fix issue with anchors
						 */
						window.location.hash = '';
						window.location.replace(
							window.location.href.replace('#', '').replace('?reset-view', '').replace('&reset-view', '').replace('?reset-all-views', '').replace('&reset-all-views', '')
						);
					}
				} else {
					$('body #vaa-overlay').addClass('error').fadeOut( 'fast', function() { $(this).remove(); } );
					if ( true === fullPopup ) {
						$(VAA_View_Admin_As.prefix).addClass('fullPopupActive');
					}
					if ( 'undefined' !== typeof response.data ) {
						// Check if we have more detailed data to show
						if ( 'undefined' !== typeof response.data.content ) {
							if ( 'undefined' === typeof response.data.type ) {
								response.data.type = 'error';
							}
							VAA_View_Admin_As.notice( response.data.content, response.data.type );
						} else {
							VAA_View_Admin_As.notice( response.data, 'error' );
						}
					}
				}
			});
		}
	};


	/**
	 * Show notice in the admin bar
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {object}  notice
	 * @param  {string}  type
	 */
	VAA_View_Admin_As.notice = function( notice, type ) {
		var root = '#wpadminbar .vaa-notice';
		$('#wp-admin-bar-top-secondary').append('<li class="vaa-notice vaa-' + type + '"><span class="remove ab-icon dashicons dashicons-dismiss" style="top: 2px;"></span>' + notice + '</li>');
		$(root+' .remove').click( function() { $(this).parent().remove(); } );
		// Remove it after 5 seconds
		setTimeout( function(){ $(root).fadeOut('fast', function() { $(this).remove(); } ); }, 5000 );
	};


	/**
	 * Show popup with return content
	 * @see    VAA_View_Admin_As.ajax
	 * @param  {object}  data
	 * @param  {string}  type
	 */
	VAA_View_Admin_As.overlay = function( data, type ) {

		var root = 'body #vaa-overlay';

		$( root ).html('<div class="vaa-overlay-container"><span class="remove dashicons dashicons-dismiss"></span><div class="vaa-response-data"></div></div>');
		if ( 'undefined' !== typeof data.text ) {
			$(root+' .vaa-response-data').append('<p>' + data.text + '</p>');
		}
		if ( 'textarea' === type ) {
			if ( 'undefined' !== typeof data.textareacontent ) {
				$(root+' .vaa-response-data').append('<textarea style="width: 100%;" readonly>'+data.textareacontent+'</textarea>');
				// Auto height
				/*$('body #vaa-overlay .vaa-response-data textarea').each(function(){
					var maxTextareaHeight = $('body #vaa-overlay .vaa-response-data').height();
					var fullTextareaHeight = this.scrollHeight;
					$(this).css({'height': 'auto', 'max-height': maxTextareaHeight}).height( fullTextareaHeight );
				});*/
				// Select full text on click
				$(root+' .vaa-response-data textarea').click( function() { $(this).select(); } );
			}
		} else if ( 'errorlist' === type ) {
			if ( 'undefined' !== typeof data.errors ) {
				$(root+' .vaa-response-data').append('<ul class="errorlist"></ul>');
				data.errors.forEach(function(error) {
					$(root+' .vaa-response-data .errorlist').append('<li>'+error+'</li>');
				});
			}
		} else {
			$(root+' .vaa-response-data').append('<div>'+data+'</div>');
		}
		$(root+' .vaa-overlay-container .remove').click( function() {
			$( root ).fadeOut( 'fast', function() { $(this).remove(); } );
		});

		// Remove overlay on click outside of container
		$(document).mouseup( function( e ){
			$(root+' .vaa-overlay-container').each( function(){
				if ( ! $(this).is(e.target) && 0 === $(this).has(e.target).length ) {
					$( root ).fadeOut( 'fast', function() { $(this).remove(); } );
				}
			});
		});
	};


	/**
	 * SETTINGS
	 * @since  1.5
	 */
	VAA_View_Admin_As.init_settings = function() {

		var root = VAA_View_Admin_As.root + '-settings',
			prefix = 'vaa-settings';

		// @since  1.5  Location
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-admin-menu-location select#' + prefix + '-admin-menu-location', function( e ) {
			e.preventDefault();
			var val = $(this).val();
			if ( val && '' !== val ) {
				var viewAs = { user_setting : { admin_menu_location : val } };
				VAA_View_Admin_As.ajax( viewAs, true );
			}
		});

		// @since  1.5  View mode
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-view-mode input.radio.' + prefix + '-view-mode', function( e ) {
			e.preventDefault();
			var val = $(this).val();
			if ( val && '' !== val ) {
				var viewAs = { user_setting : { view_mode : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
		});

		// @since  1.5.2  Force group users
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-force-group-users input#' + prefix + '-force-group-users', function( e ) {
			e.preventDefault();
			var viewAs = { user_setting : { force_group_users : "no" } };
			if ( this.checked ) {
				viewAs = { user_setting : { force_group_users : "yes" } };
			}
			VAA_View_Admin_As.ajax( viewAs, true );
		});

		// @since  1.6  Enable hide front
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-hide-front input#' + prefix + '-hide-front', function( e ) {
			e.preventDefault();
			var viewAs = { user_setting : { hide_front : "no" } };
			if ( this.checked ) {
				viewAs = { user_setting : { hide_front : "yes" } };
			}
			VAA_View_Admin_As.ajax( viewAs, false );
		});

		// @since  1.6.1  Enable freeze locale
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-freeze-locale input#' + prefix + '-freeze-locale', function( e ) {
			e.preventDefault();
			var viewAs = { user_setting : { freeze_locale : "no" } };
			if ( this.checked ) {
				viewAs = { user_setting : { freeze_locale : "yes" } };
			}
			var reload = false;
			if ( typeof VAA_View_Admin_As.view_as === 'object' && typeof VAA_View_Admin_As.view_as.user !== 'undefined' ) {
				reload = true;
			}
			VAA_View_Admin_As.ajax( viewAs, reload );
		});
	};


	/**
	 * USERS
	 * Extra functions for user views
	 * @since  1.2
	**/
	VAA_View_Admin_As.init_users = function() {

		var root = VAA_View_Admin_As.root + '-users';

		// Search users
		$(document).on('keyup', VAA_View_Admin_As.prefix+root+' .ab-vaa-search.search-users input', function() {
			$(VAA_View_Admin_As.prefix+' .ab-vaa-search #vaa-searchuser-results').empty();
			if ( 1 <= $(this).val().length ) {
				var inputText = $(this).val();
				$(VAA_View_Admin_As.prefix+'.vaa-user-item').each( function() {
					var name = $('.ab-item', this).text();
					if ( -1 < name.toLowerCase().indexOf( inputText.toLowerCase() ) ) {
						var exists = false;
						$(VAA_View_Admin_As.prefix+'.ab-vaa-search #vaa-searchuser-results .vaa-user-item .ab-item').each(function() {
							if ($(this).text().indexOf(name) > -1) {
								exists = $(this);
							}
						});
						var role = $(this).parents('.vaa-role-item').children('.ab-item').attr('rel');
						if ( false !== exists && exists.length ) {
							exists.find('.user-role').text( exists.find('.user-role').text().replace(')', ', ' + role + ')') );
						} else {
							$(this).clone().appendTo(VAA_View_Admin_As.prefix+'.ab-vaa-search #vaa-searchuser-results').children('.ab-item').append(' &nbsp; <span class="user-role">(' + role + ')</span>');
						}
					}
				});
				if ( '' === $.trim( $(VAA_View_Admin_As.prefix+'.ab-vaa-search #vaa-searchuser-results').html() ) ) {
					$(VAA_View_Admin_As.prefix+'.ab-vaa-search #vaa-searchuser-results').append('<div class="ab-item ab-empty-item vaa-not-found">'+VAA_View_Admin_As.__no_users_found+'</div>');
				}
			}
		});
	};


	/**
	 * CAPABILITIES
	 * @since  1.3
	**/
	VAA_View_Admin_As.init_caps = function() {

		var root = VAA_View_Admin_As.root + '-caps';

		VAA_View_Admin_As.caps_filter_settings = {
			selectedRole : 'default',
			selectedRoleCaps : {},
			selectedRoleReverse : false,
			filterString : ''
		};

		// Filter capability handler
		VAA_View_Admin_As.filter_capabilities = function() {
			//VAA_View_Admin_As.caps_filter_settings;
			$(VAA_View_Admin_As.prefix+root+'-quickselect-options .vaa-cap-item').each( function() {
				var name;
				if ( true === VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse ) {
					$(this).hide();
					if ( 1 <= VAA_View_Admin_As.caps_filter_settings.filterString.length ) {
						name = $(this).text();//$('.ab-item', this).text();
						if ( -1 < name.toLowerCase().indexOf( VAA_View_Admin_As.caps_filter_settings.filterString.toLowerCase() ) ) {
							$(this).show();
						}
					} else {
						$(this).show();
					}
					if ( ( VAA_View_Admin_As.caps_filter_settings.selectedRole !== 'default' ) && ( $('input', this).attr('value') in VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps ) ) {
						$(this).hide();
					}
				} else {
					$(this).hide();
					if ( ( VAA_View_Admin_As.caps_filter_settings.selectedRole === 'default' ) || ( $('input', this).attr('value') in VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps ) ) {
						if ( 1 <= VAA_View_Admin_As.caps_filter_settings.filterString.length ) {
							name = $(this).text();//$('.ab-item', this).text();
							if ( -1 < name.toLowerCase().indexOf( VAA_View_Admin_As.caps_filter_settings.filterString.toLowerCase() ) ) {
								$(this).show();
							}
						} else {
							$(this).show();
						}
					}
				}
			});
		};

		// Set max height of the caps submenu
		$(document).on('mouseenter', VAA_View_Admin_As.prefix+root+'-quickselect', function() {
			$(VAA_View_Admin_As.prefix+root+'-quickselect-options').css( { 'max-height': ( $(window).height() - 350 )+'px' } );
		});
		// Enlarge caps
		$(document).on('click', VAA_View_Admin_As.prefix+root+' #open-caps-popup', function() {
			$(VAA_View_Admin_As.prefix).addClass('fullPopupActive');
			$(VAA_View_Admin_As.prefix+root+'-quickselect > .ab-sub-wrapper').addClass('fullPopup');
		});
		// Undo enlarge caps
		$(document).on('click', VAA_View_Admin_As.prefix+root+' #close-caps-popup', function() {
			$(VAA_View_Admin_As.prefix).removeClass('fullPopupActive');
			$(VAA_View_Admin_As.prefix+root+'-quickselect > .ab-sub-wrapper').removeClass('fullPopup');
		});

		// Select role capabilities
		$(document).on('change', VAA_View_Admin_As.prefix+root+' .ab-vaa-select.select-role-caps select', function() {
			VAA_View_Admin_As.caps_filter_settings.selectedRole = $(this).val();

			if ( VAA_View_Admin_As.caps_filter_settings.selectedRole == 'default' ) {
				 VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = {};
				 VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = false;
			} else {
				var selectedRoleElement = $(VAA_View_Admin_As.prefix+root+'-selectrolecaps #vaa-caps-selectrolecaps option[value="' + VAA_View_Admin_As.caps_filter_settings.selectedRole + '"]');
				VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = JSON.parse( selectedRoleElement.attr('data-caps') );
				VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = ( 1 === parseInt( selectedRoleElement.attr('data-reverse'), 10 ) );
			}
			VAA_View_Admin_As.filter_capabilities();
		});

		// Filter capabilities with text input
		$(document).on('keyup', VAA_View_Admin_As.prefix+root+' .ab-vaa-filter input', function() {
			if ( 1 <= $(this).val().length ) {
				VAA_View_Admin_As.caps_filter_settings.filterString = $(this).val();
			} else {
				VAA_View_Admin_As.caps_filter_settings.filterString = '';
			}
			VAA_View_Admin_As.filter_capabilities();
		});


		// Select all capabilities
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+' button#select-all-caps', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			$(VAA_View_Admin_As.prefix+root+'-quickselect-options .vaa-cap-item').each( function() {
				if ( $(this).is(':visible') ) {
					$('input', this).prop( "checked", true );
				}
			});
			return false;
		});
		// Deselect all capabilities
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+' button#deselect-all-caps', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			$(VAA_View_Admin_As.prefix+root+'-quickselect-options .vaa-cap-item').each( function() {
				if ( $(this).is(':visible') ) {
					$('input', this).prop( "checked", false );
				}
			});
			return false;
		});

		// Process view: capabilities
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+' button#apply-caps-view', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var newCaps = {};
			$(VAA_View_Admin_As.prefix+root+'-quickselect-options .vaa-cap-item input').each( function() {
				if ( $(this).is(':checked') ) {
					newCaps[ $(this).attr('value') ] = 1;
				} else {
					newCaps[ $(this).attr('value') ] = 0;
				}
			});
			VAA_View_Admin_As.ajax( { caps : newCaps }, true );
			return false;
		});
	};


	/**
	 * MODULE: Role Defaults
	 * @since  1.4
	 */
	VAA_View_Admin_As.init_module_role_defaults = function() {

		var root = VAA_View_Admin_As.root + '-settings',
			prefix = 'vaa-settings';

		// Enable module
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-role-defaults-enable input#' + prefix + '-role-defaults-enable', function( e ) {
			e.preventDefault();
			var viewAs = { role_defaults : { enable : 0 } };
			if ( this.checked ) {
				viewAs = { role_defaults : { enable : true } };
			}
			VAA_View_Admin_As.ajax( viewAs, true );
		});

		root = VAA_View_Admin_As.root + '-role-defaults';
		prefix = 'vaa-role-defaults';

		// @since  1.4  Enable apply defaults on register
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-setting-register-enable input#' + prefix + '-setting-register-enable', function( e ) {
			e.preventDefault();
			var viewAs = { role_defaults : { apply_defaults_on_register : 0 } };
			if ( this.checked ) {
				viewAs = { role_defaults : { apply_defaults_on_register : true } };
			}
			VAA_View_Admin_As.ajax( viewAs, false );
		});

		// @since  1.5.3  Disable screen settings for users who can't access this plugin
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-setting-disable-user-screen-options input#' + prefix + '-setting-disable-user-screen-options', function( e ) {
			e.preventDefault();
			var viewAs = { role_defaults : { disable_user_screen_options : 0 } };
			if ( this.checked ) {
				viewAs = { role_defaults : { disable_user_screen_options : true } };
			}
			VAA_View_Admin_As.ajax( viewAs, false );
		});

		// @since  1.6  Lock meta box order and locations for users who can't access this plugin
		$(document).on('change', VAA_View_Admin_As.prefix+root+'-setting-lock-meta-boxes input#' + prefix + '-setting-lock-meta-boxes', function( e ) {
			e.preventDefault();
			var viewAs = { role_defaults : { lock_meta_boxes : 0 } };
			if ( this.checked ) {
				viewAs = { role_defaults : { lock_meta_boxes : true } };
			}
			VAA_View_Admin_As.ajax( viewAs, false );
		});

		// @since  1.6.3  Add new meta
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+'-meta-add button#' + prefix + '-meta-add', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+root+'-meta-add input#' + prefix + '-meta-new').val();
			var item = $(VAA_View_Admin_As.prefix+root+'-meta-add #' + prefix + '-meta-template').html().toString();
			item = item.replace( /vaa_new_item/g, val.replace( / /g, '_' ) );
			$(VAA_View_Admin_As.prefix+root+'-meta-select > .ab-item').prepend( item );
		});

		// @since  1.6.3  Update meta
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+'-meta-apply button#' + prefix + '-meta-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = {};
			$(VAA_View_Admin_As.prefix+root+'-meta-select .ab-item.vaa-item input').each( function() {
				val[ $(this).val() ] = ( $(this).is(':checked') );
			});
			if ( val ) {
				var viewAs = { role_defaults : { update_meta : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});

		// @since  1.4  Filter users
		$(document).on('keyup', VAA_View_Admin_As.prefix+root+'-bulk-users-filter input#' + prefix + '-bulk-users-filter', function( e ) {
			e.preventDefault();
			if ( $(this).val().length >= 1 ) {
				var inputText = $(this).val();
				$(VAA_View_Admin_As.prefix+root+'-bulk-users-select .ab-item.vaa-item').each( function() {
					var name = $('.user-name', this).text();
					if ( name.toLowerCase().indexOf( inputText.toLowerCase() ) > -1 ) {
						$(this).show();
					} else {
						$(this).hide();
					}
				});
			} else {
				$(VAA_View_Admin_As.prefix+root+'-bulk-users-select .ab-item.vaa-item').each( function() {
					$(this).show();
				});
			}
		});

		// @since  1.4  Apply defaults to users
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+'-bulk-users-apply button#' + prefix + '-bulk-users-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = [];
			$(VAA_View_Admin_As.prefix+root+'-bulk-users-select .ab-item.vaa-item input').each( function() {
				if ( $(this).is(':checked') ) {
					val.push( $(this).val() );
				}
			});
			if ( val ) {
				var viewAs = { role_defaults : { apply_defaults_to_users : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});

		// @since  1.4  Apply defaults to users by role
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+'-bulk-roles-apply button#' + prefix + '-bulk-roles-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+root+'-bulk-roles-select select#' + prefix + '-bulk-roles-select').val();
			if ( val && '' !== val ) {
				var viewAs = { role_defaults : { apply_defaults_to_users_by_role : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});

		// @since  1.4  Clear role defaults
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+'-clear-roles-apply button#' + prefix + '-clear-roles-apply', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+root+'-clear-roles-select select#' + prefix + '-clear-roles-select').val();
			if ( val && '' !== val ) {
				var viewAs = { role_defaults : { clear_role_defaults : val } };
				if ( confirm( VAA_View_Admin_As.__confirm ) ) {
					VAA_View_Admin_As.ajax( viewAs, false );
				}
			}
			return false;
		});

		// @since  1.5  Export role defaults
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+'-export-roles-export button#' + prefix + '-export-roles-export', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+root+'-export-roles-select select#' + prefix + '-export-roles-select').val();
			if ( val && '' !== val ) {
				var viewAs = { role_defaults : { export_role_defaults : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});

		// @since  1.5  Import role defaults
		$(document).on('click touchend', VAA_View_Admin_As.prefix+root+'-import-roles-import button.vaa-import-role-defaults', function( e ) {
			if ( true === VAA_View_Admin_As._touchmove ) {
				return;
			}
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+root+'-import-roles-input textarea#' + prefix + '-import-roles-input').val();
			if ( val && '' !== val ) {
				try {
					val = JSON.parse( val );
					var viewAs = { role_defaults : { import_role_defaults : val } };
					if ( $(this).attr('data-method') ) {
						viewAs.role_defaults.import_role_defaults_method = String( $(this).attr('data-method') );
					}
					VAA_View_Admin_As.ajax( viewAs, false );
				} catch ( err ) {
					// @todo Improve error message
					alert( err );
				}
			}
			return false;
		});
	};

	// We require a nonce to use this plugin
	if ( 'undefined' !== typeof VAA_View_Admin_As._vaa_nonce ) {
		VAA_View_Admin_As.init();
	}

} )( jQuery );