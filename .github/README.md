# View Admin As
View the WordPress admin as a different role or visitor, switch between users, temporarily change your capabilities, set default screen settings for roles.

[![WordPress Plugin version](https://img.shields.io/wordpress/plugin/v/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![WordPress Plugin WP tested version](https://img.shields.io/wordpress/v/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![WordPress Plugin downloads](https://img.shields.io/wordpress/plugin/dt/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![WordPress Plugin rating](https://img.shields.io/wordpress/plugin/r/view-admin-as.svg?style=flat)](https://wordpress.org/plugins/view-admin-as/)
[![Travis](https://travis-ci.com/JoryHogeveen/view-admin-as.svg?branch=master)](http://travis-ci.com/JoryHogeveen/view-admin-as)
[![Code Climate](https://codeclimate.com/github/JoryHogeveen/view-admin-as/badges/gpa.svg)](https://codeclimate.com/github/JoryHogeveen/view-admin-as)  
[![License](https://img.shields.io/badge/license-GPL--2.0%2B-blue.svg)](https://github.com/JoryHogeveen/view-admin-as/blob/master/license.txt)
[![Donate](https://img.shields.io/badge/Donate-PayPal-blue.svg)](https://www.keraweb.nl/donate.php?for=view-admin-as)
[![CII Best Practices](https://bestpractices.coreinfrastructure.org/projects/1047/badge)](https://bestpractices.coreinfrastructure.org/projects/1047)
[![Project Stats](https://www.openhub.net/p/view-admin-as/widgets/project_thin_badge.gif)](https://www.openhub.net/p/view-admin-as)

![View Admin As](https://raw.githubusercontent.com/JoryHogeveen/view-admin-as/master/.github/assets/banner-1544x500.jpg)  

## Description
**The ultimate User switcher and Role manager**

This plugin will add a menu item to your admin bar where you can change your view in the WordPress admin.  
Switch to other users without the need to login as that user or even switch roles and temporarily change your own capabilities.

When you're viewing as a different user, you can also change this user's preferences; like screen settings on various admin pages.

With the "Role defaults" module you can set default screen settings and metabox locations for roles and apply them to users through various bulk actions.

It also features a "Role manager" module to add, edit or remove roles and grant or deny them capabilities.

### Overview / Features

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

### Module: Role defaults (screen settings)

*	Set default screen settings for roles
*	Apply defaults to a user
*	Apply defaults to all users of a role
*	Apply defaults when registering a new user (in a multisite this is done when a user is added to its first blog)
*	Copy defaults from one role to another (or multiple)
*	Import/Export role defaults, can also download (and upload) setting files
*	Disable the "screen settings" option and/or lock the meta boxes for all users that don't have access to this plugin

[Click here for Role Defaults documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Role-Defaults)

### Module: Role manager (role editor)

*Note: Changes made with the Role Manager are permanent!*

*	Add, edit or delete roles
*	Grant and/or add capabilities to roles
*	Rename roles
*	Clone roles
*	Import/Export roles, can also download (and upload) setting files
*	Update role capabilities from current view
*	Automatically migrate users to another role after deleting a role

[Click here for Role Manager documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Role-Manager)

## Compatibility & Integrations

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

## Translations
Please help translating this plugin on [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/view-admin-as)!

## Actions & Filters
*	[Click here for Action documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Actions)
*	[Click here for Filter documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Filters)

## Plugin capabilities
[Click here for documentation](https://github.com/JoryHogeveen/view-admin-as/wiki/Custom-capabilities)

## Ideas?
Please let me know by creating a new [issue](https://github.com/JoryHogeveen/view-admin-as/issues/new) and describe your idea.  
Pull Requests are very welcome!

## I can't switch back!
When a view is selected there is a reset button available on the dropdown.
If you get a 403 page of WordPress you can return with the link that this plugin will add to those pages.
And if even that doesn't work just add "?reset-view" in the address bar and you're good to go! This will work on all pages as long as you are logged in.

Example: http://www.your.domain/wp-admin/?reset-view

## It's not working! / I found a bug!
Please let me know through the support and add a plugins and themes list! :)
https://wordpress.org/support/plugin/view-admin-as

## Security
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

## Installation
Installation of this plugin works like any other plugin out there. Either:

1. Upload the zip file to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress

Or search for "View Admin As" via your plugins menu.

### Install as a must-use plugin
Move the `view-admin-as.php` file into the root of your mu-plugins directory, not in the `view-admin-as` subdirectory.  
This is a limitation of WordPress and probably won't change soon.  

**Example:**  
All files dir: `/wp-content/mu-plugins/view-admin-as/...`  
Main file dir: `/wp-content/mu-plugins/view-admin-as.php`  

### Minimum Requirements
* WordPress 4.1 or greater (Though I always recommend to update to the latest version!)

## Developer notes
This plugin will only be useful for admins (network super admins or regular admins). It will not add functionalities for other roles unless you specifically apply custom capabilities for those users.  
Also keep in mind that switching to users that have equal roles is disabled. (regular admins to regular admins + super admins to super admins)

I've created this at first for myself since I'm a developer and often need to see the outcome on roles which my clients use.

So, when you are developing a plugin or theme that does anything with roles or capabilities you can use this plugin to easily check if everything works.
No more hassle of creating test users and constantly logging out and in anymore!

This plugin is also useful to support your clients and/or users. For example; make screen display presets of the edit and overview pages before you let them log in.

### Other Notes
You can find me here:

*	[Keraweb](http://www.keraweb.nl/ "Keraweb")
*	[Keraweb @ Slack](https://keraweb.slack.com/ "Keraweb") (User `keraweb`)
*	[LinkedIn](https://nl.linkedin.com/in/joryhogeveen "LinkedIn profile")
