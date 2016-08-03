# [View Admin As](https://viewadminas.wordpress.com/) #
View the WordPress admin as a different role, switch between users, temporarily change your capabilities, set default screen settings for roles.

[![WordPress Plugin version](https://img.shields.io/wordpress/plugin/v/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![WordPress Plugin WP tested version](https://img.shields.io/wordpress/v/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![WordPress Plugin downloads](https://img.shields.io/wordpress/plugin/dt/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![WordPress Plugin rating](https://img.shields.io/wordpress/plugin/r/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![Travis](https://secure.travis-ci.org/JoryHogeveen/view-admin-as.png?branch=master)](http://travis-ci.org/JoryHogeveen/view-admin-as)
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)](https://github.com/JoryHogeveen/view-admin-as/blob/master/license.txt)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=YGPLMLU7XQ9E8&lc=US&item_name=View%20Admin%20As&item_number=JWPP%2dVAA&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest)

## Description
This plugin will add a menu item to your admin bar where you can change your view in the WordPress admin without the need to login with a user that has this role!

If you've selected a user, you can also change this user's preferences; like screen settings on various admin pages.

You can also see the defaults for a role and/or temporarily change your own capabilities.

Through the new "Role defaults" module you can set default screen settings for roles and apply them on users through various bulk actions.

### Overview / Features
*	Switch to the view of a user to see their capabilities and settings (admins are filtered!)
*	Edit this user's screen preferences and settings
*	Switch to a default view of a role
*	Temporarily change your own capabilities (non-destructively)
*	Do this all without loggin out and easily go back to your own (default) user view!

### Module: Role defaults (screen settings)
*	Set default screen settings for roles
*	Apply defaults to a user
*	Apply defaults to all users of a role
*	Apply defaults when registering a new user (in a multisite this is done when a user is added to its first blog)
*	Import/Export role defaults
*	Disable the "screen settings" option for all users that don't have access to this plugin

## Compatibility
WordPress 3.5+ and PHP 5.3+

I think this plugin will work with most other plugins.

Fixed compatibility issues:

*   **WooCommerce** - removes the admin bar for the roles "customer" and "subscriber". This functionality will stay the same, but when you switch to a view in the admin it will override this setting. (You need the admin bar to switch back to default)
*   **Pods** - has its own capability management to determine if the current user is an admin or not. I've used the build in capabilities from Pods to determine wether to show the Pods menu when you are in an other view.
*   **User Role Editor / Members** - Support for multiple roles per user. (since 1.2.2)
*   **Genesis Framework** *(and probably other theme frameworks)* - Changed "init" hook to "plugins_loaded" for theme support (since 1.3.3)

## Translations
Please help translating this plugin on https://translate.wordpress.org/projects/wp-plugins/view-admin-as!

## Actions and Filters
[Click here for documentation](https://viewadminas.wordpress.com/documentation/actions-filters/ "Click here for documentation")

### Plugin capabilities
[Click here for documentation](https://viewadminas.wordpress.com/documentation/capabilities/ "Click here for documentation")

## Ideas?
Please let me know through the support page!
https://wordpress.org/support/plugin/view-admin-as

## I can't switch back!
When a view is selected there is a reset button available on the dropdown.
If you get a 403 page of WordPress you can return with the link that this plugin will add to those pages.
And if even that doesn't work just add "?reset-view" in the address bar and you're good to go! This will work on all pages as long as you are logged in.

Example: http://www.your.domain/wp-admin/?reset-view

## It's not working! / I found a bug!
Please let me know through the support and add a plugins and themes list! :)
https://wordpress.org/support/plugin/view-admin-as

## Security
You have nothing to worry about. All the plugin functionality is only run if a user is logged in AND is an administrator.
Only if the above requirements are OK will this plugin do anything.
Your view is stored separately so your user will keep the normal roles and capabilities.
All settings, views, capabilities, etc. are checked before applied.

So basically if your admin users are safe, this plugin will be safe.
Note: if your admin users aren't safe, this plugin is the last one to worry about ;)

## Installation

Installation of this plugin works like any other plugin out there. Either:

1. Upload the zip file to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Or search for "View Admin As" via your plugins menu.

### Recommended Requirements

* WordPress 4.0 or greater (Though I always recommend to update to the latest version!)
* PHP version 5.6 or greater

### Minimum Requirements

* WordPress 3.5 or greater (3.8+ recommended because of design, this plugin doesn't incorporate all styles of versions prior to WP 3.8)
* PHP version 5.3 or greater

## Developer notes
This plugin will only be usefull for admins (network super admins or regular admins). It will not add functionalities for other roles.

Also keep in mind that switching to users that have equal roles is disabled. (regular admins to regular admins + super admins to super admins)

### Other Notes

You can find me here:

*	[Keraweb](http://www.keraweb.nl/ "Keraweb")
*	[LinkedIn](https://nl.linkedin.com/in/joryhogeveen "LinkedIn profile")
*	[Plugin page](https://viewadminas.wordpress.com/ "Plugin page")
