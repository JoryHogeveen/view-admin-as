=== View Admin As ===
Contributors: keraweb
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest
Tags: admin, view, roles, users, switch, capabilities, caps, screen settings
Requires at least: 3.8
Tested up to: 4.5
Stable tag: 1.3.4

View the WordPress admin as a specific role, switch between users and temporarily change your capabilities.

== Description ==

This plugin will add a menu item to your admin bar where you can change your view in the WordPress admin without the need to login with a user that has this role!

If you've selected a user, you can also change this user's preferences; like screen settings on various admin pages.

You can also see the defaults for a role and/or temporarily change your own capabilities.

= Overview / Features =

*	Switch to the view of a user to see their capabilities and settings (admins are filtered!)
*	Edit this user's screen preferences and settings
*	Switch to a default view of a role
*	Temporarily change your own capabilities (non-destructively)
*	Do this all without loggin out and easily go back to your own (default) user view!

= Compatibility =

I think this plugin will work with most other plugins though I've found two allready that have their own capability management or have some kind of actions that influence the admin.

Fixed compatibility issues:

*	WooCommerce - removes the admin bar for the roles "customer" and "subscriber". This functionality will stay the same, but when you switch to a view in the admin it will override this setting. (You need the admin bar to switch back to default)

*	Pods - has its own capability management to determine if the current user is an admin or not. I've used the build in capabilities from Pods to determine wether to show the Pods menu when you are in an other view.

*	User Role Editor - Support for multiple roles per user. (since 1.2.2)

*	Genesis Framework (and probably other theme frameworks) - Changed "init" hook to "plugins_loaded" for theme support (since 1.3.3)

= I can't switch back! =

Just add "?reset-view" in the address bar and you're good to go! This will work on all pages as long as you are logged in.

Example: http://www.your.domain/wp-admin/?reset-view

= It's not working! / I found a bug! =

Please let me know through the support and add a plugins and themes list! :)

= Security =

You have nothing to worry about. All the plugin functionality is only run if a user is logged in AND is an administrator.
Only if the above requirements are OK will this plugin do anything.
Your view is stored separately so your user will keep the normal roles and capabilities.
All settings, views, capabilities, etc. are checked before applied.

So basically if your admin users are safe, this plugin will be safe.
Note: if your admin users aren't safe, this plugin is the last one to worry about ;)

= Developer notes =

This plugin will only be usefull for admins (network super admins or regular admins). It will do NOTHING for other roles.

Also keep in mind that switching to users that have equal roles is disabled. (regular admins to regular admins + super admins to super admins)

== Installation ==

Installation of this plugin works like any other plugin out there. Either:

1. Upload the zip file to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Or search for "View Admin As" via your plugins menu.

== Frequently Asked Questions ==

= 1. How do I switch to a user? =
Just click on the name!
If the amount of users is more than 10 you can find them under their roles or you can search for them.

= 2. How do I switch to a role? =
Just click the role :)

= 3. How does the capability system work? =
Only the capabilities enabled for your user are shown.
You can deselect the capabilities by clicking on them. When you would like to see the results just click the shiny button on the upper left.

You can also filter the roles by name or select/deselect all capabilities.
Note: When you select/deselect capabilities while you've filtered them only the capabilities shown by your filter are affected!

When you disable a capability that prevents you from viewing a screen, you can reset the view, see item 4.

= 4. I can't switch back! =

Just add "?reset-view" in the address bar and you're good to go! This will work on all pages as long as you are logged in.

Example: http://www.your.domain/wp-admin/?reset-view

= 5. I can't find a user! =
Could it be that this user is an equal user to your's? Example: you are both Admins? 
If so, these are filtered. Viewing Admins can only be done when you are a Super Admin within a network installation.

Why? To protect your fellow admin! You have no power over equal users..

If this is not the case, please make sure you aren't overlooking something.
If that is not the case, please contact me! See item 6.

= 6. It's not working! / I found a bug! =

Please let me know through the support and add a plugins and themes list! :)

= 7. Is this plugin safe? Even for production websites? =

You have nothing to worry about. All the plugin functionality is only run if a user is logged in AND is an administrator.
Only if the above requirements are OK will this plugin do anything.
Your view is stored separately so your user will keep the normal roles and capabilities.
All settings, views, capabilities, etc. are checked before applied.

So basically if your admin users are safe, this plugin will be safe.
Note: if your admin users aren't safe, this plugin is the last one to worry about ;)

= 8. Why this plugin? =

I've created this at first for myself since I'm a developer and often need to see the outcome on roles which my clients use.

So, when you are developing a plugin or theme that does anything with roles or capabilities you can use this plugin to easally check if everything works.
No more hassle of creating test users and constantly logging out and in anymore!

This plugin is also usefull to support your clients and/or users. For example; make screen display presets of the edit and overview pages before you let them log in.

== Screenshots ==

1. Admin Default view with the View Selector (view mode: off)
2. Admin Role view (role: Editor)
3. Admin User view (user: Mr. Random, role: Author)
4. Default dropdown
5. Dropdown when with 10+ users
6. Search users
7. Quickly deselect capabilities
8. Large popup for better overview of capabilities

== Changelog ==

= 1.3.4 =

*	Improvement: View settings are saved separately for each browser login so you can set different views at the same time if you use different browsers. (incognito also works!)
*	Improvement: View settings are saved for 24 hours. After that they are cleared automatically. (login triggers cleanup)
*	Improvement: Better Ajax handlers
*	Improvement: Better storage handlers
*	Improvement: uninstall.php added for cleanup all data. (Sadly does not work for large networks of 10000+ sites or users).
*	Tested with WordPress 4.5-Beta1

= 1.3.3 =

*	Improvement: Changed "init" hook to "plugins_loaded" for theme support (found some issues with the Genesis Framework, this solved it).

= 1.3.2 =

*	Feature: Added the ability to filter capabilities by the role defaults (normal and reversed)

= 1.3.1 =

*	Capability filter improved
*	Fix: constructor for PHP7
*	Fix: Stop loading css and scripts on frontend when no view is selected and the adminbar is disabled
*	Added version tag to css and scripts

= 1.3 =

*	Feature: Added the ability to (non-destructively) change your own capabilities
*	Feature: Added reset link on the "access denied" page when a view is selected
*	Remove 'reset-view' from address bar when selecting a new view
*	Added capability screenshots
*	Added a FAQ
*	Tested with WordPress 4.4-Beta4

= 1.2.2 =

*	Added support for users with multiple roles
*	Enabled switching to admin users for multisites (switching to super admins is allways disabled!)

= 1.2.1 =

*	Warning fixed

= 1.2 =

*	Support i18n functionality (currently only English and Dutch, translators are welcome!!) - Note: I use default WordPress strings aswell
*	When grouped (10+ users): show number of users per role
*	When grouped (10+ users): ability to search for users by their username
*	Disable forcing the admin bar when in default view (Off)
*	Some extra code and style improvements

= 1.1 =

*	Sort users by their role
*	Group users under their roles when there are more than 10 users
* 	Make current user or role bold in dropdown + add eye icon
*	Improve compatibility with Pods Framework
*	Added css for style improvements
*	Some extra code improvements

= 1.0 =

Created from nothingness just to be one of the cool kids. Yay!

== Other Notes ==

You can find me here:

*	[Keraweb](http://www.keraweb.nl/ "Keraweb")
*	[LinkedIn](https://nl.linkedin.com/in/joryhogeveen "LinkedIn profile")

= Ideas? =

Please let me know through the support page!
