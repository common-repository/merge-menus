=== Merge Menus ===
Contributors: wphelpdeskuk, watchthedot
Tags: merge, menu, combine
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Quickly add the elements of 1 menu on to another

== Description ==
Ever had to combine 2 menus using the default WordPress Menu editor. Manually it can take a while to copy over all the details from 1 menu to another.

This does all the leg work for you by copied all the elements of the menu into the menu you are currently editing.

== Installation ==
1. Search for Merge Menus in the WordPress Plugin Repo
2. Install the plugin
3. Activate the plugin

== Changelog ==
We use the Semantic Versioning system of defining versions (https://semver.org/).
This means that version 1.10 is a minor update for the version 1.x branch and version 2.0 is a MAJOR update.
We will not wrap version numbers of double digits.

= 1.1.3 =
* feat: publish language files
* feat: add documentation link to plugin meta

= 1.1.2 =
* fix: disable dropdown menu when site has no nav menus defined

= 1.1.1 =
* fix: missing composer directory breaking plugin load

= 1.1.0 =
* chore: update branding to Watch The Dot / support.watchthedot.com
* chore: remove symfony/polyfill-* from vendor
* fix: add namespace to plugin file
  therefore using WatchTheDot\Plugins instead of global namespace
* fix: use static functions when $this is not referenced
  This fixes a memory leak standard to using anonymous functions in classes
* reactor: remove Plugin::__ helper method and instead use __ directly
* chore: tested up to WP 6.4

= 1.0.0 =
* First version released to WP Repo

For more information, see [the plugin page](https://support.watchthedot.com/our-plugins/merge-menus)
