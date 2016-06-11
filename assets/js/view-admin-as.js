/**
 * View Admin As
 * https://wordpress.org/plugins/view-admin-as/
 * 
 * @author Jory Hogeveen <info@keraweb.nl>
 * @package view-admin-as
 * @version 1.5.2.1
 */

;(function($) {
	
	if ( 'undefined' == typeof VAA_View_Admin_As ) {
		VAA_View_Admin_As = {};
	}

	VAA_View_Admin_As.prefix = '#wpadminbar #wp-admin-bar-view-as ';

	if ( typeof VAA_View_Admin_As._debug == 'undefined' ) {
		VAA_View_Admin_As._debug = 0;
	} else {
		VAA_View_Admin_As._debug = parseInt( VAA_View_Admin_As._debug );
	}

	if ( typeof VAA_View_Admin_As.ajaxurl == 'undefined' && typeof ajaxurl != 'undefined' ) {
		VAA_View_Admin_As.ajaxurl = ajaxurl;
	}


	/**
	 * BASE INIT
	**/
	VAA_View_Admin_As.init = function() {

		VAA_View_Admin_As.init_caps();
		VAA_View_Admin_As.init_roles();
		VAA_View_Admin_As.init_users();
		VAA_View_Admin_As.init_settings();
		VAA_View_Admin_As.init_module_role_defaults();

		// Toggle content with title
		$(window).load(function() {
			$(VAA_View_Admin_As.prefix+'.ab-vaa-toggle').each( function() {
				var toggleContent = $(this).parent().children().not('.ab-vaa-toggle');
				if ( ! $(this).hasClass('active') ) {
					toggleContent.hide();
				}
				$(this).click( function(e) {
					e.preventDefault();
					if ( $(this).hasClass('active') ) {
						toggleContent.slideUp('fast');
						$(this).removeClass('active');
					} else {
						toggleContent.slideDown('fast');
						$(this).addClass('active');
					}
				});
			});
		});

		// Process reset
		$(document).on('click', VAA_View_Admin_As.prefix+'.vaa-reset-item > .ab-item', function(e) {
			e.preventDefault();
			if ( $('button', this).attr('name') == 'reload' ) {
				window.location.reload();
			} else {
				viewAs = { reset : true };
				VAA_View_Admin_As.ajax( viewAs, true );
				return false;
			}
		});
	};

	
	/**
	 * Apply the selected view
	 * viewAs format: { VIEWTYPE : VIEWDATA }
	 *
	 * @params  object  viewAs
	 * @params  boolean reload
	 */
	VAA_View_Admin_As.ajax = function( viewAs, reload ) {
		//VAA_View_Admin_As.ajaxurl;
		
		$('#wpadminbar .vaa-update').remove();
		$('body').append('<div id="vaa-overlay"><span class="vaa-loader-icon" style="background: transparent url(\'' + VAA_View_Admin_As.siteurl + '/wp-includes/images/spinner-2x.gif\') center center no-repeat; background-size: contain;"></span></div>');
		$('body #vaa-overlay').fadeIn('fast');
		
		var fullPopup = false;
		if ( $(VAA_View_Admin_As.prefix).hasClass('fullPopupActive') ) {
			fullPopup = true;
			$(VAA_View_Admin_As.prefix).removeClass('fullPopupActive');
		}
		
		var data = {
			'action': 'view_admin_as',
			'_vaa_nonce': VAA_View_Admin_As._vaa_nonce,
			'view_admin_as': viewAs
		};

		if ( $(VAA_View_Admin_As.prefix+'#vaa_settings_view_mode_single').is(':checked') && ( typeof viewAs.caps !== 'undefined' || typeof viewAs.role !== 'undefined' || typeof viewAs.user !== 'undefined' ) ) {

			$('body').append('<form id="vaa_single_mode_form" style="display:none;" method="post"></form>');
			$('#vaa_single_mode_form').append('<input type="hidden" name="action" value="' + data.action + '">');
			$('#vaa_single_mode_form').append('<input type="hidden" name="_vaa_nonce" value="' + data._vaa_nonce + '">');
			$('#vaa_single_mode_form').append('<input id="data" type="hidden" name="view_admin_as">');
			$('#vaa_single_mode_form #data').val( JSON.stringify( data.view_admin_as ) );
			$('#vaa_single_mode_form').submit();

		} else {

			$.post( VAA_View_Admin_As.ajaxurl, data, function(response) {
				if ( VAA_View_Admin_As._debug === 1 ) { console.log(response); }
				if ( typeof response.success != 'undefined' && true === response.success ) {
					if ( false === reload ) {
						// Check if we have more detailed data to show
						if ( typeof response.data != 'undefined' && typeof response.data.content != 'undefined' ) {
							if ( typeof response.data.type == 'undefined' ) { 
								response.data.type = 'default'; 
							}
							if ( typeof response.data.content != 'object' ) { 
								response.data.content = String( response.data.content ); 
							}                           
							VAA_View_Admin_As.overlay( response.data.content, String( response.data.type ) );
						} else {
							$('body #vaa-overlay').addClass('success').fadeOut( 'fast', function() { $(this).remove(); } );
							VAA_View_Admin_As.notice( VAA_View_Admin_As.__success, 'success' );
						}
					} else {
						// Reload the page
						window.location = window.location.href.replace('?reset-view', '').replace('&reset-view', '');
						// Force reload
						window.location.reload();
					}
				} else {
					$('body #vaa-overlay').addClass('error').fadeOut( 'fast', function() { $(this).remove(); } );
					if ( true === fullPopup ) {
						$(VAA_View_Admin_As.prefix).addClass('fullPopupActive');
					}
					if ( typeof response.data != 'undefined' ) {
						// Check if we have more detailed data to show
						if ( typeof response.data.content != 'undefined' ) {
							if ( typeof response.data.type == 'undefined' ) {
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
	 * @param  object  notice
	 * @param  string  type
	 */
	VAA_View_Admin_As.notice = function( notice, type ) {
		$('#wp-admin-bar-top-secondary').append('<li class="vaa-update vaa-' + type + '"><span class="remove ab-icon dashicons dashicons-dismiss" style="top: 2px;"></span>' + notice + '</li>');
		$('#wpadminbar .vaa-update .remove').click( function() { $(this).parent().remove(); } );
		// Remove it after 5 seconds
		setTimeout( function(){ $('#wpadminbar .vaa-update').fadeOut('fast', function() { $(this).remove(); } ); }, 5000 );
	};


	/**
	 * Show popup with return content
	 * @see    VAA_View_Admin_As.ajax
	 * @param  object  data
	 * @param  string  type
	 */
	VAA_View_Admin_As.overlay = function( data, type ) {
		$('body #vaa-overlay').html('<div class="vaa-overlay-container"><span class="remove dashicons dashicons-dismiss"></span><div class="vaa-response-data"></div></div>');
		if ( type == 'textarea' ) {
			if ( typeof data.text != 'undefined' ) {
				$('body #vaa-overlay .vaa-response-data').append('<p>' + data.text + '</p>');
			}
			if ( typeof data.textareacontent != 'undefined' ) {
				$('body #vaa-overlay .vaa-response-data').append('<textarea style="width: 100%;" readonly>'+data.textareacontent+'</textarea>');
				// Auto height
				/*$('body #vaa-overlay .vaa-response-data textarea').each(function(){ 
					var maxTextareaHeight = $('body #vaa-overlay .vaa-response-data').height();
					var fullTextareaHeight = this.scrollHeight;
					$(this).css({'height': 'auto', 'max-height': maxTextareaHeight}).height( fullTextareaHeight ); 
				});*/
				// Select full text on click
				$('body #vaa-overlay .vaa-response-data textarea').click( function() { $(this).select(); } );
			}
		} else if ( type == 'errorlist' ) {
			if ( typeof data.text != 'undefined' ) {
				$('body #vaa-overlay .vaa-response-data').append('<p>'+data.text+'</p>');
			}
			if ( typeof data.errors != 'undefined' ) {
				$('body #vaa-overlay .vaa-response-data').append('<ul class="errorlist"></ul>');
				data.errors.forEach(function(error) {
					$('body #vaa-overlay .vaa-response-data .errorlist').append('<li>'+error+'</li>');
				});
			}
		} else {
			$('body #vaa-overlay .vaa-response-data').append('<div>'+data+'</div>');
		}
		$('#vaa-overlay .vaa-overlay-container .remove').click( function() {
			$('body #vaa-overlay').fadeOut( 'fast', function() { $(this).remove(); } );
		});

		// Remove overlay on click outsite of container
		$(document).mouseup( function(e){
			$('body #vaa-overlay .vaa-overlay-container').each( function(){
				if ( ! $(this).is(e.target) && 0 === $(this).has(e.target).length ) {
					$('body #vaa-overlay').fadeOut( 'fast', function() { $(this).remove(); } );
				}
			});
		});
	};


	/**
	 * SETTINGS
	**/
	VAA_View_Admin_As.init_settings = function() {

		// Location
		$(document).on('change', VAA_View_Admin_As.prefix+'#wp-admin-bar-settings-admin-menu-location select#vaa_settings_admin_menu_location', function(e) {
			e.preventDefault();
			var val = $(this).val();
			if ( val && '' !== val ) {
				var viewAs = { user_setting : { admin_menu_location : val } };
				VAA_View_Admin_As.ajax( viewAs, true );
			}
		});

		// View mode
		$(document).on('change', VAA_View_Admin_As.prefix+'#wp-admin-bar-settings-view-mode input.radio.vaa_settings_view_mode', function(e) {
			e.preventDefault();
			var val = $(this).val();
			if ( val && '' !== val ) {
				var viewAs = { user_setting : { view_mode : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
		});

		// Force group users
		$(document).on('change', VAA_View_Admin_As.prefix+'#wp-admin-bar-settings-force-group-users input#vaa_settings_force_group_users', function(e) {
			e.preventDefault();
			var viewAs = { user_setting : { force_group_users : "no" } };
			if ( this.checked ) {
				viewAs = { user_setting : { force_group_users : "yes" } };
			}
			VAA_View_Admin_As.ajax( viewAs, true );
		});
	};
	

	/**
	 * ROLES
	**/
	VAA_View_Admin_As.init_roles = function() {

		// Process role views
		$(document).on('click', VAA_View_Admin_As.prefix+'.vaa-role-item > a.ab-item', function(e) {
			e.preventDefault();
			if ( ! $(this).parent().hasClass('not-a-view') ) {
				viewAs = { role : String( $(this).attr('rel') ) };
				VAA_View_Admin_As.ajax( viewAs, true );
				return false;
			}
		}); 
	};


	/**
	 * USERS
	**/
	VAA_View_Admin_As.init_users = function() {

		// Process user views
		$(document).on('click', VAA_View_Admin_As.prefix+'.vaa-user-item > a.ab-item', function(e) {
			e.preventDefault();
			if ( ! $(this).parent().hasClass('not-a-view') ) {
				viewAs = { user : parseInt( $(this).attr('rel') ) };
				VAA_View_Admin_As.ajax( viewAs, true );
				return false;
			}
		}); 

		// Search users
		$(document).on('keyup', VAA_View_Admin_As.prefix+'#wp-admin-bar-users .ab-vaa-search.search-users input', function(e) {
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
	**/
	VAA_View_Admin_As.init_caps = function() {

		VAA_View_Admin_As.caps_filter_settings = { 
			selectedRole : 'default', 
			selectedRoleCaps : {}, 
			selectedRoleReverse : false, 
			filterString : '', 
		};

		// Filter capability handler
		VAA_View_Admin_As.filter_capabilities = function() {
			//VAA_View_Admin_As.caps_filter_settings;
			$(VAA_View_Admin_As.prefix+' #wp-admin-bar-caps-quickselect-options .vaa-cap-item').each( function() {
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
					if ( ( VAA_View_Admin_As.caps_filter_settings.selectedRole != 'default' ) && ( $('input', this).attr('value') in VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps ) ) {
						$(this).hide();
					}
				} else {
					$(this).hide();
					if ( ( VAA_View_Admin_As.caps_filter_settings.selectedRole == 'default' ) || ( $('input', this).attr('value') in VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps ) ) {
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
		$(document).on('mouseenter', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps-quickselect', function() {
			$(VAA_View_Admin_As.prefix+'#wp-admin-bar-caps-quickselect-options').css( { 'max-height': ( $(window).height() - 350 )+'px' } );
		});
		// Enlarge caps
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps #open-caps-popup', function() {
			$(VAA_View_Admin_As.prefix).addClass('fullPopupActive');
			$(VAA_View_Admin_As.prefix+'#wp-admin-bar-caps-quickselect > .ab-sub-wrapper').addClass('fullPopup');
		});
		// Undo enlarge caps
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps #close-caps-popup', function() {
			$(VAA_View_Admin_As.prefix).removeClass('fullPopupActive');
			$(VAA_View_Admin_As.prefix+'#wp-admin-bar-caps-quickselect > .ab-sub-wrapper').removeClass('fullPopup');
		});

		// Select role capabilities
		$(document).on('change', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps .ab-vaa-select.select-role-caps select', function() {
			VAA_View_Admin_As.caps_filter_settings.selectedRole = $(this).val();

			if ( VAA_View_Admin_As.caps_filter_settings.selectedRole == 'default' ) {
				 VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = {};
				 VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = false;
			} else {
				var selectedRoleElement = $(VAA_View_Admin_As.prefix+' #wp-admin-bar-selectrolecaps #select-role-caps option[value="' + VAA_View_Admin_As.caps_filter_settings.selectedRole + '"]');
				VAA_View_Admin_As.caps_filter_settings.selectedRoleCaps = JSON.parse( selectedRoleElement.attr('data-caps') );
				if ( '1' == selectedRoleElement.attr('data-reverse') ) {
					VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = true;
				} else {
					VAA_View_Admin_As.caps_filter_settings.selectedRoleReverse = false;
				}
			}
			VAA_View_Admin_As.filter_capabilities();
		});
		
		// Filter capabilities with text input
		$(document).on('keyup', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps .ab-vaa-filter input', function(e) {
			//VAA_View_Admin_As.caps_filter_settings;
			if ( 1 <= $(this).val().length ) {
				VAA_View_Admin_As.caps_filter_settings.filterString = $(this).val();
			} else {
				VAA_View_Admin_As.caps_filter_settings.filterString = '';
			}
			VAA_View_Admin_As.filter_capabilities();
		});
		
		
		// Select all capabilities
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps button#select-all-caps', function(e) {
			$(VAA_View_Admin_As.prefix+'#wp-admin-bar-caps-quickselect-options .vaa-cap-item').each( function() {
				if ( $(this).is(':visible') ){
					$('input', this).prop( "checked", true );
				}
			});
			return false;
		});
		// Deselect all capabilities
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps button#deselect-all-caps', function(e) {
			$(VAA_View_Admin_As.prefix+'#wp-admin-bar-caps-quickselect-options .vaa-cap-item').each( function() {
				if ( $(this).is(':visible') ){
					$('input', this).prop( "checked", false );
				}
			});
			return false;
		});
		
		// Process view: capabilities
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-caps button#apply-caps-view', function(e) {
			var newCaps = '';
			$(VAA_View_Admin_As.prefix+'#wp-admin-bar-caps-quickselect-options .vaa-cap-item input').each( function() {
				if ( $(this).is(':checked') ) {
					newCaps += $(this).attr('value') + ':' + 1 + ',';
				} else {
					newCaps += $(this).attr('value') + ':' + 0 + ',';
				}
			});
			VAA_View_Admin_As.ajax( { caps : newCaps }, true );
			return false;
		});
	};
	

	/**
	 * MODULE: Role Defaults
	 */
	VAA_View_Admin_As.init_module_role_defaults = function() {

		// Enable module
		$(document).on('change', VAA_View_Admin_As.prefix+'#wp-admin-bar-settings-role-defaults-enable input#vaa_role_defaults_enable', function(e) {
			e.preventDefault();
			var viewAs = { role_defaults : { enable : 0 } };
			if ( this.checked ) {
				viewAs = { role_defaults : { enable : true } };
			}
			VAA_View_Admin_As.ajax( viewAs, true );
		});
		
		// Enable apply defaults on register
		$(document).on('change', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-setting-register-enable input#vaa_role_defaults_register_enable', function(e) {
			e.preventDefault();
			var viewAs = { role_defaults : { apply_defaults_on_register : 0 } };
			if ( this.checked ) {
				viewAs = { role_defaults : { apply_defaults_on_register : true } };
			}
			VAA_View_Admin_As.ajax( viewAs, false );
		});
		
		// Disable screen settings for users who can't access this plugin
		$(document).on('change', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-setting-disable-user-screen-options input#vaa_role_defaults_disable_user_screen_options', function(e) {
			e.preventDefault();
			var viewAs = { role_defaults : { disable_user_screen_options : 0 } };
			if ( this.checked ) {
				viewAs = { role_defaults : { disable_user_screen_options : true } };
			}
			VAA_View_Admin_As.ajax( viewAs, false );
		});

		// Filter users
		$(document).on('keyup', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-bulk-users-filter input#role-defaults-bulk-users-filter', function(e) {
			e.preventDefault();
			if ( $(this).val().length >= 1 ) {
				var inputText = $(this).val();
				$(VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-bulk-users-select .ab-item.vaa-item').each( function() {
					var name = $('.user-name', this).text();
					if ( name.toLowerCase().indexOf( inputText.toLowerCase() ) > -1 ) {
						$(this).show();
					} else {
						$(this).hide();
					}
				});
			} else {
				$(VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-bulk-users-select .ab-item.vaa-item').each( function() {
					$(this).show();
				});
			}
		});
		
		// Apply defaults to users
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-bulk-users-apply button#role-defaults-bulk-users-apply', function(e) {
			e.preventDefault();
			var val = [];
			$(VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-bulk-users-select .ab-item.vaa-item input').each( function() {
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
		
		// Apply defaults to users by role
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-bulk-roles-apply button#role-defaults-bulk-roles-apply', function(e) {
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-bulk-roles-select select#role-defaults-bulk-roles-select').val();
			if ( val && '' !== val ) {
				var viewAs = { role_defaults : { apply_defaults_to_users_by_role : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});
		
		// Clear role defaults
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-clear-roles-apply button#role-defaults-clear-roles-apply', function(e) {
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-clear-roles-select select#role-defaults-clear-roles-select').val();
			if ( val && '' !== val ) {
				var viewAs = { role_defaults : { clear_role_defaults : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});

		// Export role defaults
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-export-roles-export button#role-defaults-export-roles-export', function(e) {
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-export-roles-select select#role-defaults-export-roles-select').val();
			if ( val && '' !== val ) {
				var viewAs = { role_defaults : { export_role_defaults : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});

		// Import role defaults
		$(document).on('click', VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-import-roles-import button#role-defaults-import-roles-import', function(e) {
			e.preventDefault();
			var val = $(VAA_View_Admin_As.prefix+'#wp-admin-bar-role-defaults-import-roles-input textarea#role-defaults-import-roles-input').val();
			if ( val && '' !== val ) {
				var viewAs = { role_defaults : { import_role_defaults : val } };
				VAA_View_Admin_As.ajax( viewAs, false );
			}
			return false;
		});
	};


	// We require a nonce to use this plugin
	if ( 'undefined' != typeof VAA_View_Admin_As._vaa_nonce ) {
		VAA_View_Admin_As.init();
	}


})( jQuery );