=== View Admin As ===
Contributors: keraweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: admin, view, roles, users, switch, user switching, role switching, capabilities, caps, screen settings, defaults, visitor
Requires at least: 4.1
Tested up to: 4.8
Stable tag: 1.7.1

View the WordPress admin as a different role or visitor, switch between users, temporarily change your capabilities, set screen settings for roles.

== Description ==

This plugin will add a menu item to your admin bar where you can change your view in the WordPress admin without the need to login with a user that has this role!

If you've selected a user, you can also change this user's preferences; like screen settings on various admin pages. You can also switch to a role or temporarily change your own capabilities.

With the "Role defaults" module you can set default screen settings for roles and apply them on users through various bulk actions.

It also features a "Role manager" module to add, edit or remove roles and grant or deny them capabilities.

= Overview / Features =

*	Switch between user accounts
	*	Edit this user's screen preferences and settings
*	Switch to a role view
*	Temporarily change your own capabilities (non-destructively)
*	View your site as an unregistered visitor
*	Easily switch back anytime
*	Do this all without logging out and easily go back to your own (default) user view!

= Module: Role defaults (screen settings) =

*	Set default screen settings for roles
*	Apply defaults to a user
*	Apply defaults to all users of a role
*	Apply defaults when registering a new user (in a multisite this is done when a user is added to its first blog)
*	Import/Export role defaults
*	Disable the "screen settings" option and/or lock the meta boxes for all users that don't have access to this plugin

= Module: Role manager (role editor) =

*Note: Changes made with the Role Manager are permanent!*

*	Add, edit or delete roles
*	Grant and/or add capabilities to roles
*	Rename roles
*	Clone roles
*	Update role capabilities from current view

= Compatibility & Integrations =

This plugin will work with most other plugins but these are tested:

*	**Advanced Access Manager** *(Pro version not verified)*
*	**bbPress**
*	**BuddyPress**
*	**Genesis Framework** *(and probably other theme frameworks)*
*	**Gravity Forms**
*	**Pods Framework 2.0+**
*	**Members**
*	**Restrict User Access 0.13+** *(Custom integration: adds a view type for access levels)*
*	**User Roles and Capabilities**
*	**User Role Editor** *(Pro version not verified)*
*	**User Switching** *(Not sure why you'd want this but yes, switch-ception is possible!)*
*	**WPFront User Role Editor**

Full list of tested plugins and details: [Compatibility & Integrations](https://github.com/JoryHogeveen/view-admin-as/wiki/Compatibility-&-Integrations)

= I can't switch back! =

See item **3** at [FAQ](https://wordpress.org/plugins/view-admin-as/faq/).

= It's not working! / I found a bug! =

Please let me know through the support and add a plugins and themes list! :)

= Security =

This plugin will keep your users and data secure, see item **7** at [FAQ](https://wordpress.org/plugins/view-admin-as/faq/) for more info!

= Developer notes =

This plugin will only be useful for admins (network super admins or regular admins). It will not add functionalities for other roles unless you specifically apply custom capabilities for those users.  
Also keep in mind that switching to users that have equal roles is disabled. (regular admins to regular admins + super admins to super admins)

I've created this at first for myself since I'm a developer and often need to see the outcome on roles which my clients use.

So, when you are developing a plugin or theme that does anything with roles or capabilities you can use this plugin to easily check if everything works.
No more hassle of creating test users and constantly logging out and in anymore!

This plugin is also useful to support your clients and/or users. For example; make screen display presets of the edit and overview pages before you let them log in.

== Installation ==

Installation of this plugin works like any other plugin out there. Either:

1. Upload and unpack the zip file to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Or search for "View Admin As" via your plugins menu.

= Minimum Requirements =

* WordPress 4.1 or greater (Though I always recommend to update to the latest version!)

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
Please see the `view_admin_as_role_defaults_meta` filter at [Wiki: Filters](https://github.com/JoryHogeveen/view-admin-as/wiki/Filters#view_admin_as_role_defaults_meta)!

The meta manager (since 1.6.3) provides a UI to edit the meta keys.
Please follow these guidelines:

* `%%` stands for a wildcard which could be anything.
* Avoid special characters. Spaces, quotes etc. are forbidden.
* Default meta keys cannot be removed, only disabled.

= 5. I can't find a user! =
Could it be that this user is an equal user to your's? Example: you are both Admins?
If so, these are filtered. Viewing Admins can only be done when you are a Super Admin within a network installation.

Why? To protect your fellow admin! You have no power over equal users..
*Unless you are a superior admin... [Read more](https://github.com/JoryHogeveen/view-admin-as/wiki/Filters#view_admin_as_superior_admins)*

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

== Screenshots ==

1. Default dropdown
2. Dropdown with grouped users
3. Search users
4. Quickly (de)select capabilities
5. Large popup for better overview of capabilities
6. Admin bar when a view is selected + the reset button location
7. Settings window
8. Module Role defaults window (tabs are normally closed)
9. Module Role manager main window (tabs are normally closed)
10. Module Role manager capability window (tabs is normally closed)
11. Access levels taken from the "Restrict User Access" plugin

== Changelog ==

= 1.7.1 =

*	**Feature:** Module Role Manager: Rename roles. [#47](https://github.com/JoryHogeveen/view-admin-as/issues/47)
*	**Enhancement:** Improve fetching available capabilities for a super admin. It now also checks for registered custom post type and taxonomy capabilities and more other plugins.
*	**Compatibility:** Also use the `user_has_cap` filter besides `map_meta_cap` to further improve capability and role view compatibility.
*	**UI:** Add submenu scrollbar when there are too much users under a role. [#49](https://github.com/JoryHogeveen/view-admin-as/issues/49)
*	**UI:** Module Role Manager: Show original role name for reference.
*	**Accessibility:** Fix tabindex for some nodes that have form elements.
*	**Updated:** Screenshots.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/48)

= 1.7 =

*	**Feature:** New module Role manager. Add, edit and/or remove roles and grant or deny them capabilities. [#43](https://github.com/JoryHogeveen/view-admin-as/issues/43)
*	**Feature:** Module Role Defaults: Added the option to copy defaults from one role to another (or multiple). [#44](https://github.com/JoryHogeveen/view-admin-as/issues/44)
*	**Enhancement/UI:** Enable and Improve responsive styles/a11y. [#16](https://github.com/JoryHogeveen/view-admin-as/issues/16)
*	**Enhancement/UI:** Improved the autoHeight calculation (submenu and popup).
*	**Enhancement:** View combinations now working in code (No UI). [#18](https://github.com/JoryHogeveen/view-admin-as/issues/18)
*	**Enhancement:** Major code refactoring for better standards en easier development.
*	**Maintenance:** Validated compatibility with "Restrict User Access" (RUA) plugin v0.14. [#31](https://github.com/JoryHogeveen/view-admin-as/issues/31)
*	**Compatibility:** Tested with WordPress 4.8 (alpha) and requires WordPress 4.1 or higher (was 3.5).
*	**Fix:** Fixed all major [CodeClimate](https://codeclimate.com/github/JoryHogeveen/view-admin-as) issues. All green now!
*	**Updated:** [Wiki (documentation)](https://github.com/JoryHogeveen/view-admin-as/wiki).
*	**Updated/Added:** Screenshots & Banners.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/42)

= 1.6.4 =

*	**Feature:** Integration with the "Restrict User Access" (RUA) plugin. Introduces a new view type "access levels" when this plugin is activated. [#31](https://github.com/JoryHogeveen/view-admin-as/issues/31)
*	**Enhancement:** Improve compatibility with plugins that use the current user object. Related: [#32](https://github.com/JoryHogeveen/view-admin-as/issues/32)
*	**Enhancement:** Improve compatibility with plugins that use the role objects.
*	**Enhancement:** Redirect to homepage when selecting the visitor view.
*	**Enhancement:** Integrate with the capability groups in plugin "User Role Editor".
*	**Fix:** Started to use CodeClimate for style checks + applied fixes. [#37](https://github.com/JoryHogeveen/view-admin-as/issues/37)

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/36) & [RUA integration PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/34)

= 1.6.3 =

*	**Feature:** Meta sync manager UI for the role defaults module [#28](https://github.com/JoryHogeveen/view-admin-as/issues/28)
*	**Feature:** Multiple import methods for the role defaults module [#27](https://github.com/JoryHogeveen/view-admin-as/issues/27)
*	**Enhancement:** Also update the current user object's capabilities and roles to improve support for other plugins [#32](https://github.com/JoryHogeveen/view-admin-as/issues/32)
*	Other minor improvements

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/29)

= 1.6.2 =

*	**Feature:** A new view! You can now see your site as an unregistered visitor (no need to switch browsers) [#14](https://github.com/JoryHogeveen/view-admin-as/issues/14)
*	**Enhancement:** Reduced queries for getting the available users to **1**! *Performance improvement to the native WP function `get_users()` (with fallback if needed)* [#24](https://github.com/JoryHogeveen/view-admin-as/issues/24)
*	**Enhancement:** Add all existing roles that have defaults to the clear list even if they have been removed from WP [#22](https://github.com/JoryHogeveen/view-admin-as/issues/22)
*	**Enhancement:** Enable the current view as a capability filter
*	**Enhancement:** Highlight the view capabilities in the capability menu
*	**Enhancement:** Pass view data as JSON *(enhances compatibility with weird capability identifiers since WP doesn't escape these so it could contain special characters)*
*	**Enhancement/Fix:** Compatibility with the `editable_roles` filter for non super admins
*	**Fix:** Hide our toolbar in the customizer preview. Switching in the WP Customizer not possible (yet)
*	**Fix:** Improve capability view handling
*	Other minor improvements

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/23)

= 1.6.1 =

*	**Feature:** Freeze locale, force your own locale setting over that of a selected view. (Requires WP 4.7) [#21](https://github.com/JoryHogeveen/view-admin-as/issues/21)
*	**Enhancement:** Added a11y keyboard tab indexes
*	**Fix:** Reloading when anchor tags are set in the url [#17](https://github.com/JoryHogeveen/view-admin-as/issues/17)
*	Other minor fixes

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/20)

= 1.6 =

*	**Feature:** Lock meta boxes [#9](https://github.com/JoryHogeveen/view-admin-as/issues/9)
*	**Feature:** View as links in user management page [#12](https://github.com/JoryHogeveen/view-admin-as/issues/12)
*	**Enhancement:** Better admin bar handling when set to hidden by user [#4](https://github.com/JoryHogeveen/view-admin-as/issues/4)
	*	Also adds an option to hide/show our toolbar when no view is selected and the admin bar is not shown.
*	**Enhancement:** Better handling for permission errors [#10](https://github.com/JoryHogeveen/view-admin-as/issues/10)
*	**Compatibility:** Show our custom capabilities on role manage plugins like "Members"
*	**Compatibility:** PHP 5.2 (WP minimum)
*	**Fix:** occasional issues with enabling the Role Defaults module
*	**Refactor:** Multiple classes for more flexibility in future development

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/8)

= Older versions =

[Complete changelog](https://github.com/JoryHogeveen/view-admin-as/wiki/Changelog)

== Other Notes ==

= You can find me here: =

*	[Keraweb](http://www.keraweb.nl/)
*	[GitHub](https://github.com/JoryHogeveen/view-admin-as/)
*	[LinkedIn](https://nl.linkedin.com/in/joryhogeveen)

= Translations =

Please help translating this plugin on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/view-admin-as)!

= Actions & Filters =

*	[Click here for Action documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Actions)
*	[Click here for Filter documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Filters)

= Plugin capabilities =

[Click here for documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Custom-capabilities)

= Ideas? =

Please let me know on [GitHub](https://github.com/JoryHogeveen/view-admin-as/issues/new)!

== Upgrade Notice ==

= 1.7 =
Version 1.7 introduces some radical code changes to the plugin and requires WordPress 4.1 or higher. Please clear your cache after updating.

= 1.5 =
Version 1.5 introduces some radical code changes to the plugin. Please clear your cache after updating.
