# Contribute to View Admin As

Community made patches, localizations, bug reports, and contributions are always welcome and are crucial to ensure that this plugin remains alive and strong.

When contributing please ensure you follow the guidelines below so that we can keep on top of things.

## Getting Started

* Submit a ticket for your issue, assuming one does not already exist.
  * https://github.com/JoryHogeveen/view-admin-as/issues
  * Clearly describe the issue including steps to reproduce the bug.
  * Make sure you fill in the earliest version that you know has the issue as well as the version of WordPress you're using.

## Making Changes

* Fork the repository on GitHub
* Make the changes to your forked repository's code
  * Ensure you stick to the [WordPress Coding Standards](http://codex.wordpress.org/WordPress_Coding_Standards)
  * This repository contains all files needed to properly configure your IDE with the correct coding standards and code style configuration.
    * [PHP MD](https://github.com/JoryHogeveen/view-admin-as/blob/master/tests/phpmd.xml)
    * [PHP CS](https://github.com/JoryHogeveen/view-admin-as/blob/master/tests/phpcs.xml)
    * [ESLint](https://github.com/JoryHogeveen/view-admin-as/blob/master/tests/.eslintrc)
    * [CSSLint](https://github.com/JoryHogeveen/view-admin-as/blob/master/tests/.csslintrc)
* Create a new branch, named with a issue prefix (if present) and some keywords on what is changed.
  * Example: `#123-what-is-changed`.
* When committing, reference your issue (if present) and include a note about the fix.
* Push the changes to the branch you created and submit a pull request for the `dev` branch.

At this point you're waiting on us to merge your pull request. We'll review all pull requests, and make suggestions and changes if necessary.

# Additional Resources
* [General GitHub documentation](http://help.github.com/)
* [GitHub pull request documentation](http://help.github.com/send-pull-requests/)
