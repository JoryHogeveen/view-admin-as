=== View Admin As ===
Contributors: keraweb
Donate link: https://www.keraweb.nl/donate.php?for=view-admin-as
Tags: admin, view, roles, users, switch, user switching, role switching, capabilities, caps, screen settings, defaults, visitor
Requires at least: 4.1
Tested up to: 5.0
Requires PHP: 5.2.4
Stable tag: 1.8.3

View the WordPress admin as a different role or visitor, switch between users, temporarily change your capabilities, set screen settings for roles.

== Description ==

= The ultimate User switcher and Role manager =

This plugin will add a menu item to your admin bar where you can change your view in the WordPress admin.  
Switch to other users without the need to login as that user or even switch roles and temporarily change your own capabilities.

When you're viewing as a different user, you can also change this user's preferences; like screen settings on various admin pages.

With the "Role defaults" module you can set default screen settings and metabox locations for roles and apply them to users through various bulk actions.

It also features a "Role manager" module to add, edit or remove roles and grant or deny them capabilities.

= Overview / Features =

*	Switch between user accounts
	*	Edit this user's screen preferences and settings
*	Switch between roles
*	Temporarily change your own capabilities (non-destructively)
*	View your site as an unregistered visitor
*	Switch language/locale on backend and frontend
*	Make combinations of the above view types
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

[Click here for Role Defaults documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Role-Defaults)

= Module: Role manager (role editor) =

*Note: Changes made with the Role Manager are permanent!*

*	Add, edit or delete roles
*	Grant and/or add capabilities to roles
*	Rename roles
*	Clone roles
*	Import/Export roles, can also download (and upload) setting files
*	Update role capabilities from current view
*	Automatically migrate users to another role after deleting a role

[Click here for Role Manager documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Role-Manager)

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
Just click on the link in the toolbar!

If the amount of users and roles combined is more than 15 you can find the users under their roles or you can search for them.
  
If the amount of users is more than 100 the plugin will switch to AJAX search and won't load users in advance for performance.  
This limit can be changed through the filter: [`view_admin_as_user_query_limit`](https://github.com/JoryHogeveen/view-admin-as/wiki/Filters#view_admin_as_user_query_limit)

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

= 4. How do the Role Defaults and Role Manager modules work? =
* [Click here for Role Defaults documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Role-Defaults)
* [Click here for Role Manager documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Role-Manager)

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
11. View combinations
12. Access levels taken from the "Restrict User Access" plugin

== Changelog ==

= 1.8.3 =

*	**Compatibility:** Users always have the exists capability. [Go to issue](https://wordpress.org/support/topic/compatibility-with-view-admin-as-2/)
*	**Compatibility:** WordPress 4.9.6 privacy capabilities.
*	**API:** Added several API methods & enhancements.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/102)

= 1.8.2 =

*	**Enhancement/Fix:** Support AJAX search in the Role Defaults module. [#100](https://github.com/JoryHogeveen/view-admin-as/issues/100)
*	**Enhancement:** Add support for `X-Redirect-By` header since WordPress 5.0. [#42313](https://core.trac.wordpress.org/ticket/42313)
*	**Enhancement:** Improve uninstall script.
*	**Enhancement:** Use latest WPCS v1.1 update and fix code standard notices.
*	**UI:** Change the default top level node text to "View As" (same as Facebook uses).
*	**Updated:** Screenshots

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/98)

= 1.8.1 =

*	**Feature:** Support searching users by multiple user columns like email, url, etc. [#95](https://github.com/JoryHogeveen/view-admin-as/issues/95)
*	**Feature:** User setting to force AJAX search for users. [#96](https://github.com/JoryHogeveen/view-admin-as/issues/96)
*	**Feature:** New filter: `view_admin_as_user_ajax_search` to force AJAX search for user at all times.
*	**Fix:** Prevent "form changed" popup which showed in various pages. [#93](https://github.com/JoryHogeveen/view-admin-as/issues/93)
*	**Enhancement:** Improve Pods Framework compatibility when in a view.
*	**Enhancement:** Improve getting view data on load.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/94)

= 1.8 =

*	**Feature:** View combinations UI. [#18](https://github.com/JoryHogeveen/view-admin-as/issues/18)
*	**Feature/Enhancement:** Limit user query to max 100 users for performance. Switch to AJAX search if there are more users than this limit. [#19](https://github.com/JoryHogeveen/view-admin-as/issues/19)
*	**Accessibility:** New filter: `view_admin_as_user_query_limit` to change the limit used to query users.
*	**Accessibility:** New filters: `vaa_admin_bar_view_title_role` & `vaa_admin_bar_view_title_user` & `vaa_admin_bar_view_title_locale` to change the titles for role, users and languages.
*	**Accessibility:** New filter: `vaa_admin_bar_view_title_user_show_roles` to remove the roles from user nodes.
*	**Accessibility:** New filter: `view_admin_as_full_access_capabilities` for single site installations to change the capabilities required to gain full access to this plugin.
*	**Enhancement:** Use a class autoloader.
*	**Enhancement:** Stop using the `rel` attribute for view type data.
*	**Enhancement:** Access validation logic.
*	**Compatibility:** Patch Yoast SEO compatibility. [Yoast SEO #9365](https://github.com/Yoast/wordpress-seo/pull/9365)
*	**Refactoring:** Action/Filter hook manager class. [#77](https://github.com/JoryHogeveen/view-admin-as/issues/77) 
*	**Refactoring:** Refactor all view types as separate modules. [#84](https://github.com/JoryHogeveen/view-admin-as/issues/84)
*	**Fix:** Use `prop` instead of `attr` for `checked` attributes in checkbox inputs.
*	**Updated/Added:** Screenshots.

Detailed info: [PR on GitHub](https://github.com/JoryHogeveen/view-admin-as/pull/78)

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
