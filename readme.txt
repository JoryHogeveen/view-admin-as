=== View Admin As ===
Contributors: keraweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: admin, view, roles, users, switch, user switching, role switching, capabilities, caps, screen settings, defaults, visitor
Requires at least: 4.1
Tested up to: 4.9
Requires PHP: 5.2.4
Stable tag: 1.7.5

View the WordPress admin as a different role or visitor, switch between users, temporarily change your capabilities, set screen settings for roles.

== Description ==

= The ultimate User switcher and Role manager =

This plugin will add a menu item to your admin bar where you can change your view in the WordPress admin without the need to login with a user that has this role!

If you've selected a user, you can also change this user's preferences; like screen settings on various admin pages. You can also switch to a role or temporarily change your own capabilities.

With the "Role defaults" module you can set default screen settings for roles and apply them on users through various bulk actions.

It also features a "Role manager" module to add, edit or remove roles and grant or deny them capabilities.

= Overview / Features =

*	Switch between user accounts
	*	Edit this user's screen preferences and settings
*	Switch between roles
*	Temporarily change your own capabilities (non-destructively)
*	View your site as an unregistered visitor
*	Switch language/locale on backend and frontend
*	Easily switch back anytime
*	Completely secure (see *Security* below)
*	Do all the above without logging out!

= Module: Role defaults (screen settings) =

*	Set default screen settings for roles
*	Apply defaults to a user
*	Apply defaults to all users of a role
*	Apply defaults when registering a new user (in a multisite this is done when a user is added to its first blog)
*	Copy defaults from one role to another (or multiple)
*	Import/Export role defaults, can also download (and upload) setting files
*	Disable the "screen settings" option and/or lock the meta boxes for all users that don't have access to this plugin

= Module: Role manager (role editor) =

*Note: Changes made with the Role Manager are permanent!*

*	Add, edit or delete roles
*	Grant and/or add capabilities to roles
*	Rename roles
*	Clone roles
*	Import/Export roles, can also download (and upload) setting files
*	Update role capabilities from current view

= Compatibility & Integrations =

This plugin will work with most other plugins but these are tested:

*	**Advanced Access Manager** *(Pro version not verified)*
*	**bbPress**
*	**BuddyPress**
*	**Genesis Framework** *(and probably other theme frameworks)*
*	**Gravity Forms**
*	**Groups 2.1+** *(Custom integration: adds a view type for groups. Pro version not tested)*
*	**Pods Framework 2.0+**
*	**Members**
*	**Restrict User Access 0.13+** *(Custom integration: adds a view type for access levels)*
*	**User Roles and Capabilities**
*	**User Role Editor** *(Pro version not verified)*
*	**User Switching** *(Not sure why you'd want this but yes, switch-ception is possible!)*
*	**WPFront User Role Editor**
*	**WP Admin UI Customize 1.5.11+**
*	**Yoast SEO**

Full list of tested plugins and details: [Compatibility & Integrations](https://github.com/JoryHogeveen/view-admin-as/wiki/Compatibility-&-Integrations)

= I can't switch back! =

See item **3** at [FAQ](https://wordpress.org/plugins/view-admin-as/faq/).

= It's not working! / I found a bug! =

Please let me know through the support and add a plugins and themes list! :)

= Security =

This plugin is completely safe and will keep your users, passwords and data secure.  
For more info see item **7** at [FAQ](https://wordpress.org/plugins/view-admin-as/faq/)!

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

= Install as a must-use plugin =
Move the `view-admin-as.php` file into the root of your mu-plugins directory, not in the `view-admin-as` subdirectory.  
This is a limitation of WordPress and probably won't change soon.  

**Example:**  
All files dir: `/wp-content/mu-plugins/view-admin-as/...`  
Main file dir: `/wp-content/mu-plugins/view-admin-as.php`  

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
You have nothing to worry about.  
All the plugin functionality is only run if the user is logged in AND is allowed to use this plugin (website admin or custom capabilities).  
**This plugin will do absolutely nothing if the above requirements are not met.**

* Your view is stored separately so your user will keep the normal roles and capabilities.
* All settings, views, capabilities, etc. are verified before applied.
* Passwords are not (and cannot be) revealed.
* Fully written with the WordPress coding and security standards.
* Full support for SSL (https).

So basically if your admin users are safe, this plugin will be safe.
Note: if your admin users aren't safe, this plugin is the last one to worry about ;)

= 8. Does this plugin work as a must-use plugin (mu-plugin)? =
Yes, see *Install as a must-use plugin* on the *Installation* tab.

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

= 1.7.5 =

*	**Feature:** Language switcher. [#81](https://github.com/JoryHogeveen/view-admin-as/issues/81)
*	**Enhancement:** Store options network wide instead of per blog/site if network/multisite is enabled.
*	**Enhancement:** Action links (no-JS) on frontend.
*	**Enhancement:** Add filter `view_admin_as_freeze_locale` to overwrite user setting.
*	**Fix:** JavaScript init on frontend.
*	**UI:** Various minor enhancements.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/82)

= 1.7.4 =

*	**Feature:** Role defaults / Role manager: download export data as file + import from file. [#73](https://github.com/JoryHogeveen/view-admin-as/issues/73)
*	**Enhancement:** Refresh page instead of redirect to home when switching to a site visitor on the frontend. [#76](https://github.com/JoryHogeveen/view-admin-as/issues/76)
*	**Enhancement:** Role Manager: Refresh the page if a role is updated while active in the current view.
*	**Enhancement:** jQuery selector performance.
*	**Compatibility:** [WP 4.9 capabilities](https://make.wordpress.org/core/2017/10/15/improvements-for-roles-and-capabilities-in-4-9/).
*	**Compatibility:** WP Admin UI Customize admin bar editor. [#40](https://github.com/JoryHogeveen/view-admin-as/issues/40) & [WAUC/#1](https://github.com/gqevu6bsiz/WP-Admin-UI-Customize-test/pull/1) & [WAUC/#2](https://github.com/gqevu6bsiz/WP-Admin-UI-Customize-test/pull/2)
*	**Compatibility:** Fix issue with Restrict User Access. [RUA/#15](https://github.com/intoxstudio/restrict-user-access/issues/15)
*	**Compatibility:** Fetch all capabilities from Yoast SEO (5.5+).
*	**Compatibility:** Must-use plugin loader scripts.
*	**UI:** The almighty View Admin As loader icon.
*	**UI:** Full opacity when semi-transparent group nodes are opened.
*	**UI:** Admin page links for Groups and Restrict User Access modules.
*	**UI:** Resizable checkbox wrappers.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/75)

= 1.7.3 =

*	**Feature:** Role Manager: Import/Export roles. [#51](https://github.com/JoryHogeveen/view-admin-as/issues/51) & [PR #62](https://github.com/JoryHogeveen/view-admin-as/pull/62)
*	**Feature/Enhancement:** Option to disable super admin status when a view is active and modifies the current user. [#53](https://github.com/JoryHogeveen/view-admin-as/issues/53) & [PR #61](https://github.com/JoryHogeveen/view-admin-as/pull/61)
*	**Enhancement:** Role Manager: Show custom capabilities that are not yet stored but used in an active caps view. [#70](https://github.com/JoryHogeveen/view-admin-as/issues/70)
*	**Enhancement:** Prevent duplicate names when fetching capabilities from WP objects.
*	**Enhancement:** Allow this plugin to be installed as a must-use plugin. [#71](https://github.com/JoryHogeveen/view-admin-as/issues/71)
	*	More info: [Docs: Install as a must-use plugin](https://github.com/JoryHogeveen/view-admin-as#install-as-a-must-use-plugin) & [WP codex: mu-plugins](https://codex.wordpress.org/Must_Use_Plugins)
*	**Enhancement:** Role Defaults: Enhance meta key comparison.
*	**UI:** Option tooltips. [#67](https://github.com/JoryHogeveen/view-admin-as/issues/67)
*	**UI:** Enhance full popup caps view.
*	**Refactoring:** Fix base class name.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/68)

= 1.7.2 =

*	**Feature:** Integration with the "Groups" plugin. Introduces a new view type `groups` when this plugin is activated. [#11](https://github.com/JoryHogeveen/view-admin-as/issues/11)
*	**Fix:** auto max height didn't work on frontend. [#55](https://github.com/JoryHogeveen/view-admin-as/issues/55)
*	**Fix:** Role Manager used `boolval()` which is only available in PHP 5.5+. [#63](https://github.com/JoryHogeveen/view-admin-as/issues/63)
*	**Fix:** `view_admin_as_superior_admins` filter was not working for single installations. [#65](https://github.com/JoryHogeveen/view-admin-as/issues/65)
*	**Compatibility:** Allow other plugins to overwrite our `user_has_cap` filter by setting it's priority as first (large negative number). [#56](https://github.com/JoryHogeveen/view-admin-as/issues/56). Thanks to [@pbiron](https://github.com/pbiron) for the report.
*	**Compatibility:** Run the `user_has_cap` filter in your `map_meta_cap` filter. [#56](https://github.com/JoryHogeveen/view-admin-as/issues/56)
*	**Compatibility:** Add new network capabilities (WP 4.8) to the list. [#64](https://github.com/JoryHogeveen/view-admin-as/issues/64)
*	**Enhancement:** Automatic JS handling for simple and more advanced options. [#60](https://github.com/JoryHogeveen/view-admin-as/issues/60)
*	**Enhancement:** Role defaults: Rename `all` wildcard to `__all__` to prevent a possible conflict with custom roles.
*	**Enhancement:** Role defaults: Add recording indicator icon to the top level node when a role view is active.
*	**Refactoring:** Move form logic to separate class and extend it (admin bar)

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/54) & [Groups integration PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/59)

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
