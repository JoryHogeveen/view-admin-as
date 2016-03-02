/**
 * Plugin Name: View Admin As
 * Description: View the WordPress admin as a specific role, switch between users and non-destructively change your capabilities.
 * Plugin URI:  https://wordpress.org/plugins/view-admin-as/
 * Version:     1.4
 * Author:      Jory Hogeveen
 * Author URI:  http://www.keraweb.nl
 * Text Domain: view-admin-as
 * Domain Path: /languages/
 * License: 	GPLv2
 */

(function($) {
	
	var vaa_bar = '#wpadminbar #wp-admin-bar-view-as ';
	var caps_filter = {'selectedRoleReverse' : false, 'selectedRole' : 'default', 'selectedRoleCaps' : {}, 'filterString' : ''};
			
	var ajax_url = '';
	if (typeof VAA_View_Admin_As.ajaxurl != 'undefined') {
		ajax_url = VAA_View_Admin_As.ajaxurl;
	} else {
		ajax_url = ajaxurl;
	}

	// Set max height of the caps submenu
	$(document).on('mouseenter', vaa_bar+'#wp-admin-bar-caps-quickselect', function() {
		$(vaa_bar+'#wp-admin-bar-caps-quickselect-options').css({'max-height': ($(window).height() - 300)+'px'});
	});
	// Enlarge caps
	$(document).on('click', vaa_bar+'#wp-admin-bar-caps #open-caps-popup', function() {
		$(vaa_bar).addClass('fullPopupActive');
		$(vaa_bar+'#wp-admin-bar-caps-quickselect > .ab-sub-wrapper').addClass('fullPopup');
	});
	// Undo enlarge caps
	$(document).on('click', vaa_bar+'#wp-admin-bar-caps #close-caps-popup', function() {
		$(vaa_bar).removeClass('fullPopupActive');
		$(vaa_bar+'#wp-admin-bar-caps-quickselect > .ab-sub-wrapper').removeClass('fullPopup');
	});
	
	// Search users
	$(document).on('keyup', vaa_bar+'#wp-admin-bar-users .ab-vaa-search.search-users input', function(e) {
		$(vaa_bar+' .ab-vaa-search #vaa-searchuser-results').empty();
		if ( $(this).val().length >= 1 ) {
			var inputText = $(this).val();
			$(vaa_bar+'.vaa-user-item').each( function() {
				var name = $('.ab-item', this).text();
				if ( name.toLowerCase().indexOf( inputText.toLowerCase() ) > -1 ) {
					var exists = false;
					$(vaa_bar+'.ab-vaa-search #vaa-searchuser-results .vaa-user-item .ab-item').each(function(){
						if ($(this).text().indexOf(name) > -1) {
							exists = $(this);
						}
					});
					var role = $(this).parents('.vaa-role-item').children('.ab-item').attr('rel');
					if (exists != false) {
						exists.find('.user-role').text(exists.find('.user-role').text().replace(')', ', '+role+')'));
					} else {
						$(this).clone().appendTo(vaa_bar+'.ab-vaa-search #vaa-searchuser-results').children('.ab-item').append(' &nbsp; <span class="user-role">('+role+')</span>');
					}
				}
			});
			if ( $.trim( $(vaa_bar+'.ab-vaa-search #vaa-searchuser-results').html()) == '' ) {
				$(vaa_bar+'.ab-vaa-search #vaa-searchuser-results').append('<div class="ab-item ab-empty-item vaa-not-found">'+VAA_View_Admin_As.__no_users_found+'</div>');
			}
		}
	});
	
	// Select role capabilities
	$(document).on('change', vaa_bar+'#wp-admin-bar-caps .ab-vaa-select.select-role-caps select', function() {
		caps_filter;
		caps_filter.selectedRole = $(this).val();
		if (caps_filter.selectedRole == 'default') {
			caps_filter.selectedRoleCaps = {};
			caps_filter.selectedRoleReverse = false;
		} else {
			var selectedRoleElement = $(vaa_bar+' #wp-admin-bar-selectrolecaps #select-role-caps option[value="'+caps_filter.selectedRole+'"]');
			caps_filter.selectedRoleCaps = JSON.parse(selectedRoleElement.attr('data-caps'));
			if (selectedRoleElement.attr('data-reverse') == '1') {
				caps_filter.selectedRoleReverse = true;
			} else {
				caps_filter.selectedRoleReverse = false;
			}
		}
		filter_capabilities();
	});
	
	// Filter capabilities with text input
	$(document).on('keyup', vaa_bar+'#wp-admin-bar-caps .ab-vaa-filter input', function(e) {
		caps_filter;
		if ( $(this).val().length >= 1 ) {
			caps_filter.filterString = $(this).val();
		} else {
			caps_filter.filterString = '';
		}
		filter_capabilities();
		
	});
	
	// Filter capability handler
	function filter_capabilities() {
		caps_filter;
		$(vaa_bar+' #wp-admin-bar-caps-quickselect-options .vaa-cap-item').each( function() {
			if (caps_filter.selectedRoleReverse == true) {
				$(this).hide();
				if ( caps_filter.filterString.length >= 1 ) {
					var name = $(this).text();//$('.ab-item', this).text();
					if ( name.toLowerCase().indexOf( caps_filter.filterString.toLowerCase() ) > -1 ) {
						$(this).show();
					}
				} else {
					$(this).show();
				}
				if ( ( caps_filter.selectedRole != 'default' ) && ( $('input', this).attr('value') in caps_filter.selectedRoleCaps ) ) {
					$(this).hide();
				}
			} else {
				$(this).hide();
				if ( ( caps_filter.selectedRole == 'default' ) || ( $('input', this).attr('value') in caps_filter.selectedRoleCaps ) ) {
					if ( caps_filter.filterString.length >= 1 ) {
						var name = $(this).text();//$('.ab-item', this).text();
						if ( name.toLowerCase().indexOf( caps_filter.filterString.toLowerCase() ) > -1 ) {
							$(this).show();
						}
					} else {
						$(this).show();
					}
				}
			}
		});
	}
	
	// Select all capabilities
	$(document).on('click', vaa_bar+'#wp-admin-bar-caps button#select-all-caps', function(e) {
		$(vaa_bar+'#wp-admin-bar-caps-quickselect-options .vaa-cap-item').each( function() {
			if ($(this).is(':visible')){
				$('input', this).prop( "checked", true );
			}
		});
	});
	// Deselect all capabilities
	$(document).on('click', vaa_bar+'#wp-admin-bar-caps button#deselect-all-caps', function(e) {
		$(vaa_bar+'#wp-admin-bar-caps-quickselect-options .vaa-cap-item').each( function() {
			if ($(this).is(':visible')){
				$('input', this).prop( "checked", false );
			}
		});
	});
	
	// Process view: capabilities
	$(document).on('click', vaa_bar+'#wp-admin-bar-caps button#apply-caps-view', function(e) {
		var newCaps = '';
		$(vaa_bar+'#wp-admin-bar-caps-quickselect-options .vaa-cap-item input').each( function() {
			if ($(this).is(':checked')) {
				newCaps += $(this).attr('value')+':'+1+',';
			} else {
				newCaps += $(this).attr('value')+':'+0+',';
			}
		});
		vaa_apply_view( { caps : newCaps }, true );
	});
	
	// Process views: reset, roles and users
	$(document).on('click', vaa_bar+'.ab-sub-wrapper a.ab-item', function(e) {
		e.preventDefault();
		if ( ! $(this).parent().hasClass('not-a-view') ) {
			var viewAs = $(this).parent().attr('id').replace('wp-admin-bar-', '').split("-");
			switch (viewAs[0]) {
				case 'reset': viewAs = { reset : true }; break;
				case 'role': viewAs = { role : String( viewAs[1] ) }; break;
				case 'user': viewAs = { user : parseInt( viewAs[1] ) }; break;
			}
			vaa_apply_view(viewAs, true);
		}
	});
	
	/**
	 * Apply the selected view
	 * viewAs format: { VIEWTYPE : VIEWDATA }
	 *
	 * @params	object	viewAs
	 */
	function vaa_apply_view(viewAs, reload) {
		ajax_url;
		
		$('#wpadminbar .vaa-update-error').remove();
		$('body').append('<div id="vaa-loading"><span class="vaa-loader-icon" style="background: transparent url(\'' + VAA_View_Admin_As.siteurl + '/wp-includes/images/spinner-2x.gif\') center center no-repeat; background-size: contain;"></span></div>');
		$('body #vaa-loading').fadeIn('fast');
		
		var fullPopup = false;
		if ($(vaa_bar).hasClass('fullPopupActive')) {
			fullPopup = true;
			$(vaa_bar).removeClass('fullPopupActive');
		}
		
		var data = {
			'action': 'update_view_as',
			'view_as': viewAs
		};
		$.post(ajax_url, data, function(response) {
			if (response.success == true) {
				if (reload == false) {
					$('body #vaa-loading').addClass('success').fadeOut('fast', function(){ $(this).remove(); });
					vaa_add_notice('Success', 'success');
				} else {
					window.location = window.location.href.replace('?reset-view', '').replace('&reset-view', '');
				}
			} else {
				$('body #vaa-loading').addClass('error').fadeOut('fast', function(){ $(this).remove(); });
				if (fullPopup == true) {
					$(vaa_bar).addClass('fullPopupActive');
				}
				vaa_add_notice(response.data, 'error');
			}
		});
	}
	
	// Show notice in case of errors
	function vaa_add_notice(notice, type) {
		$(vaa_bar).after('<li class="vaa-update vaa-' + type + '"><span class="remove ab-icon dashicons dashicons-dismiss"></span>' + notice + '</li>');
		$('#wpadminbar .vaa-update .remove').click(function(){ $(this).parent().remove(); });
		setTimeout(function(){ $('#wpadminbar .vaa-update').fadeOut('fast'); }, 3000);
	}
	
	
	
	/**
	 * MODULE: Role Defaults
	 */
	
	// Enable module
	$(document).on('change', vaa_bar+'#wp-admin-bar-role-defaults-enable input#vaa_role_defaults_enable', function(e) {
		e.preventDefault();
		if ( this.checked ) {
			var viewAs = { role_defaults : { enable : true } };
		} else {
			var viewAs = { role_defaults : { disable : true } };
		}
		vaa_apply_view(viewAs, true);
	});
	
	// Enable apply defaults on register
	$(document).on('change', vaa_bar+'#wp-admin-bar-role-defaults-register-enable input#vaa_role_defaults_register_enable', function(e) {
		e.preventDefault();
		if ( this.checked ) {
			var viewAs = { role_defaults : { apply_defaults_on_register : true } };
		} else {
			var viewAs = { role_defaults : { disable_apply_defaults_on_register : true } };
		}
		vaa_apply_view(viewAs, false);
	});
	
	// Apply defaults to users
	$(document).on('click', vaa_bar+'#wp-admin-bar-role-defaults-bulk-users-apply button#role-defaults-bulk-users-apply', function(e) {
		e.preventDefault();
		var val = [];
		$(vaa_bar+'#wp-admin-bar-role-defaults-bulk-users-select .ab-item.vaa-item input').each( function() {
			if ($(this).is(':checked')) {
				val.push($(this).val());
			}
		});
		if (val) {
			var viewAs = { role_defaults : { apply_defaults_to_users : val } };
			vaa_apply_view(viewAs, false);
		}
	});
	
	// Apply defaults to users by role
	$(document).on('click', vaa_bar+'#wp-admin-bar-role-defaults-bulk-roles-apply button#role-defaults-bulk-roles-apply', function(e) {
		e.preventDefault();
		var val = $(vaa_bar+'#wp-admin-bar-role-defaults-bulk-roles-select select#role-defaults-bulk-roles-select').val();
		if (val && val != '') {
			var viewAs = { role_defaults : { apply_defaults_to_users_by_role : val } };
			vaa_apply_view(viewAs, false);
		}
	});
	
	// Clear role defaults
	$(document).on('click', vaa_bar+'#wp-admin-bar-role-defaults-clear-roles-apply button#role-defaults-clear-roles-apply', function(e) {
		e.preventDefault();
		var val = $(vaa_bar+'#wp-admin-bar-role-defaults-clear-roles-select select#role-defaults-clear-roles-select').val();
		if (val && val != '') {
			var viewAs = { role_defaults : { clear_role_defaults : val } };
			vaa_apply_view(viewAs, false);
		}
	});

	// Filter users
	$(document).on('keyup', vaa_bar+'#wp-admin-bar-role-defaults-bulk-users-filter input#role-defaults-bulk-users-filter', function(e) {
		e.preventDefault();
		if ( $(this).val().length >= 1 ) {
			var inputText = $(this).val();
			$(vaa_bar+'#wp-admin-bar-role-defaults-bulk-users-select .ab-item.vaa-item').each( function() {
				var name = $('.user-name', this).text();
				if ( name.toLowerCase().indexOf( inputText.toLowerCase() ) > -1 ) {
					$(this).show();
				} else {
					$(this).hide();
				}
			});
		} else {
			$(vaa_bar+'#wp-admin-bar-role-defaults-bulk-users-select .ab-item.vaa-item').each( function() {
				$(this).show();
			});
		}
		
	});


})( jQuery );