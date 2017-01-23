=== View Admin As ===
Contributors: keraweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: admin, view, roles, users, switch, user switching, role switching, capabilities, caps, screen settings, defaults, visitor
Requires at least: 3.5
Tested up to: 4.7
Stable tag: 1.6.4

View the WordPress admin as a different role or visitor, switch between users, temporarily change your capabilities, set screen settings for roles.

== Description ==

This plugin will add a menu item to your admin bar where you can change your view in the WordPress admin without the need to login with a user that has this role!

If you've selected a user, you can also change this user's preferences; like screen settings on various admin pages.

You can also switch to a role or temporarily change your own capabilities.

Through the "Role defaults" module you can set default screen settings for roles and apply them on users through various bulk actions.

= Overview / Features =

*	Switch to the view of a user to see their capabilities and settings (admins are filtered!)
	*	Edit this user's screen preferences and settings
*	Switch to a default view of a role
*	Temporarily change your own capabilities (non-destructively)
*	View your site as an unregistered visitor
*	Do this all without logging out and easily go back to your own (default) user view!

= Module: Role defaults (screen settings) =

*	Set default screen settings for roles
*	Apply defaults to a user
*	Apply defaults to all users of a role
*	Apply defaults when registering a new user (in a multisite this is done when a user is added to its first blog)
*	Import/Export role defaults
*	Disable the "screen settings" option and/or lock the meta boxes for all users that don't have access to this plugin

= Compatibility / Integrations =

*   **Members & User Role Editor**
	- Support for multiple roles per user. (since 1.2.2)
	- Add view-admin-as capabilities to the list (since 1.6)
*   **Genesis Framework** *(and probably other theme frameworks)* - Changed "init" hook to "plugins_loaded" for theme support (since 1.3.3)
*   **Pods Framework 2.0+** - has its own capability management to determine if the current user is an admin or not. I've used the build in capabilities from Pods to determine whether to show the Pods menu when you are in an other view.
*   **Restrict User Access 0.13+** - Integrated with the access levels as a new view type (since 1.6.4)
*   **Advanced Access Manager** - (checked in 1.6.3, Pro version not verified)

This plugin will work with most other plugins.

= I can't switch back! =

When a view is selected there is a reset button available on the dropdown.
If you get a 403 page of WordPress you can return with the link that this plugin will add to those pages.
And if even that doesn't work just add "?reset-view" in the address bar and you're good to go! This will work on all pages as long as you are logged in.

Example: http://www.your.domain/wp-admin/?reset-view

= It's not working! / I found a bug! =

Please let me know through the support and add a plugins and themes list! :)

= Security =

This plugin will keep your users and data secure, see item 7 at FAQ for more info!

= Developer notes =

This plugin will only be useful for admins (network super admins or regular admins). It will not add functionalities for other roles unless you specifically apply custom capabilities for those users.

Also keep in mind that switching to users that have equal roles is disabled. (regular admins to regular admins + super admins to super admins)

== Installation ==

Installation of this plugin works like any other plugin out there. Either:

1. Upload the zip file to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Or search for "View Admin As" via your plugins menu.

= Recommended Requirements =

* WordPress 4.0 or greater (Though I always recommend to update to the latest version!)

= Minimum Requirements =

* WordPress 3.5 or greater (3.8+ recommended because of design, this plugin doesn't incorporate all styles of versions prior to WP 3.8)

== Frequently Asked Questions ==

= 1. How do I switch to a user, role or visitor? =
Just click on the link!
If the amount of users is more than 10 you can find them under their roles or you can search for them.

= 2. How does the capability system work? =
Only the capabilities that are allowed for your user are shown.
You can deselect the capabilities by clicking on them. When you would like to see the results just click the apply button on the upper left.

*Please note that as an administrator you don't have all capabilities marked as enabled by default. This is because WP overrules some capability checks for super admins. **This does not happen when you are in a view!***

You can also filter the roles by name or select/deselect all capabilities.
Note: When you select/deselect capabilities while you've filtered them only the capabilities shown by your filter are affected!

When you disable a capability that prevents you from viewing a screen, you can reset the view, see next item.

= 3. I can't switch back! =
When a view is selected there is a reset button available on the dropdown.
If you get a 403 page of WordPress you can return with the link that this plugin will add to those pages.
And if even that doesn't work just add "?reset-view" in the address bar and you're good to go! This will work on all pages as long as you are logged in.

Example: http://www.your.domain/wp-admin/?reset-view

= 4. What data is stored for role defaults and how can I change this? =
Please see the `view_admin_as_role_defaults_meta` filter at [Actions & Filters](https://github.com/JoryHogeveen/view-admin-as/wiki/Actions-&-Filters)!

The meta manager (since 1.6.3) provides a UI to edit the meta keys.
Please follow these guidelines:
- `%%` stands for a wildcard which could be anything.
- Avoid special characters. Spaces, quotes etc. are forbidden.
- Default meta keys cannot be removed, only disabled.

= 5. I can't find a user! =
Could it be that this user is an equal user to your's? Example: you are both Admins?
If so, these are filtered. Viewing Admins can only be done when you are a Super Admin within a network installation.

Why? To protect your fellow admin! You have no power over equal users..
*Unless you are a superior admin... [Read more](https://github.com/JoryHogeveen/view-admin-as/wiki/Actions-&-Filters#view_admin_as_superior_admins)*

If this is not the case, please make sure you aren't overlooking something.
If that is not the case, please contact me! See next item.

= 6. It's not working! / I found a bug! =
Please let me know through the support and add a plugins and themes list! :)

= 7. Is this plugin safe? Even for production websites? =
You have nothing to worry about. All the plugin functionality is only run if a user is logged in AND is allowed to use this plugin (website admin or custom capabilities).
Only if the above requirements are OK will this plugin do anything.
Your view is stored separately so your user will keep the normal roles and capabilities.
All settings, views, capabilities, etc. are checked before applied.

So basically if your admin users are safe, this plugin will be safe.
Note: if your admin users aren't safe, this plugin is the last one to worry about ;)

= 8. Why this plugin? =
I've created this at first for myself since I'm a developer and often need to see the outcome on roles which my clients use.

So, when you are developing a plugin or theme that does anything with roles or capabilities you can use this plugin to easily check if everything works.
No more hassle of creating test users and constantly logging out and in anymore!

This plugin is also useful to support your clients and/or users. For example; make screen display presets of the edit and overview pages before you let them log in.

== Screenshots ==

1. Default dropdown
2. Dropdown with grouped users
3. Search users
4. Quickly (de)select capabilities
5. Large popup for better overview of capabilities
6. Module Role defaults window (tabs are normally closed)
7. Settings window
8. Admin bar when a view is selected + the reset button location

== Changelog ==

= 1.6.4 =

*	Feature: Integration with the Restrict User Access (RUA) plugin. Introduces a new view type "access levels" when this plugin is activated. [#31](https://github.com/JoryHogeveen/view-admin-as/issues/31)
*	Enhancement: Improve compatibility with plugins that use the current user object. Related: [#32](https://github.com/JoryHogeveen/view-admin-as/issues/32)
*	Enhancement: Improve compatibility with plugins that use the role objects.
*	Fix: Started to use CodeClimate for style checks + applied fixes.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/36) | [RUA PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/34)

= 1.6.3 =

*	Feature: Meta sync manager UI for the role defaults module [#28](https://github.com/JoryHogeveen/view-admin-as/issues/28)
*	Feature: Multiple import methods for the role defaults module [#27](https://github.com/JoryHogeveen/view-admin-as/issues/27)
*	Enhancement: Also update the current user object's capabilities and roles to improve support for other plugins [#32](https://github.com/JoryHogeveen/view-admin-as/issues/32)
*	Other minor improvements

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/29)

= 1.6.2 =

*	Feature: A new view! You can now see your site as an unregistered visitor (no need to switch browsers) [#14](https://github.com/JoryHogeveen/view-admin-as/issues/14)
*	Enhancement: Reduced queries for getting the available users to **1**! *Performance improvement to the native WP function `get_users()` (with fallback if needed)* [#24](https://github.com/JoryHogeveen/view-admin-as/issues/24)
*	Enhancement: Add all existing roles that have defaults to the clear list even if they have been removed from WP [#22](https://github.com/JoryHogeveen/view-admin-as/issues/22)
*	Enhancement: Enable the current view as a capability filter
*	Enhancement: Highlight the view capabilities in the capability menu
*	Enhancement: Pass view data as JSON *(enhances compatibility with weird capability identifiers since WP doesn't escape these so it could contain special characters)*
*	Enhancement/Fix: Compatibility with the `editable_roles` filter for non super admins
*	Fix: Hide our toolbar in the customizer preview. Switching in the WP Customizer not possible (yet)
*	Fix: Improve capability view handling
*	Other minor improvements

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/23)

= 1.6.1 =

*	Feature: Freeze locale, force your own locale setting over that of a selected view. (Requires WP 4.7) [#21](https://github.com/JoryHogeveen/view-admin-as/issues/21)
*	Enhancement: Added a11y keyboard tab indexes
*	Fix: Reloading when anchor tags are set in the url [#17](https://github.com/JoryHogeveen/view-admin-as/issues/17)
*	Other minor fixes

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/20)

= 1.6 =

*	Feature: Lock meta boxes [#9](https://github.com/JoryHogeveen/view-admin-as/issues/9)
*	Feature: View as links in user management page [#12](https://github.com/JoryHogeveen/view-admin-as/issues/12)
*	Enhancement: Better admin bar handling when set to hidden by user [#4](https://github.com/JoryHogeveen/view-admin-as/issues/4)
	*	Also adds an option to hide/show our toolbar when no view is selected and the admin bar is not shown.
*	Enhancement: Better handling for permission errors [#10](https://github.com/JoryHogeveen/view-admin-as/issues/10)
*	Compatibility: Show our custom capabilities on role manage plugins like Members
*	Compatibility: PHP 5.2 (WP minimum)
*	Fix: occasional issues with enabling the Role Defaults module
*	Refactor whole backend into multiple classes for more flexibility in future development

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/8)

= Older versions =

[Complete changelog](https://github.com/JoryHogeveen/view-admin-as/wiki/Changelog)

== Other Notes ==

You can find me here:

*	[Keraweb](http://www.keraweb.nl/)
*	[GitHub](https://github.com/JoryHogeveen/view-admin-as/)
*	[LinkedIn](https://nl.linkedin.com/in/joryhogeveen)

= Translations =

Please help translating this plugin on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/view-admin-as)!

= Actions and Filters =

[Click here for documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Actions-&-Filters)

= Plugin capabilities =

[Click here for documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Custom-capabilities)

= Ideas? =

Please let me know on [GitHub](https://github.com/JoryHogeveen/view-admin-as/issues/new)!

== Upgrade Notice ==

= 1.5 =
Version 1.5 introduces some radical code changes to the plugin. Please clear your cache after updating
