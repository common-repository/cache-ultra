=== Cache Ultra ===

Contributors: limelightdept
Plugin Name: Cache Ultra
Plugin URI: https://limelighttheme.com
Tags: caching, optimize, minify, performance, pagespeed, performance, seo, speed
Author URI: https://limelighttheme.com
Author: Limelight Department
Tested up to: 5.2.3
Stable tag: "1.1.11"
Version 1.1.11
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Requires PHP: 5.6
Donate link:

Optimize page load times with full page caching. Improves Google PageSpeed Insights by an average of +30%.

== Description ==

Optimize page load times with full page caching. Improves Google PageSpeed Insights by an average of +30%.

Cache Ultra recompiles your javascript and css, then "snapshots" your pages into fully cached static html files. Automatically renews your snapshots every 8 hours to handle WordPress form tokens and such.

Also provides lazy loading site-wide or page-by-page.
You'll have full control of page resources and how they're loaded on a per page or post type basis.

Create custom cache to handle the unique scripting needs for your site.

This plugin leverages our powerful caching API.

For more information visit http://www.limelighttheme.com

== Installation ==

== Screenshots ==
1. Google page speed insights before
2. Google page speed insights after
3. Snapshot sidebar before snapshot
4. Snapshot sidebar after snapshot
5. Snapshot configuration popup
6. Snapshot resource configuration popup
7. Snapshot settings page

== Upgrade Notice ==

== Changelog ==

1.1.11
- Added tags to the cache resources lists.

1.1.10
- Fixed invalid sql notice in activation code.

1.1.8
- Added dom indexes to the resources table in order to better track settings between versions and token refreshes.

1.1.7
- Optimized how resource files are being loaded from html cache.

1.1.6
- Fixed issue with mismatching md5 hashes for tmp files.

1.1.5
- Set default lazyload option to be FALSE.

1.1.4
- Modified the activation code to match current DB specs.

1.1.3
- Fixed issues with decoding certain base64 resource data.

1.1.2
- Fixed issued with POST request being overloaded.

1.1.1
- Changed how referrer header was being sent to cache cloud.

1.1.0
- Fixed a couple of issues with the performance scoring. Added the ability to modify custom cache's by what the url contains rather than an exact match.

1.0.8
- Added before and after performance scores.

1.0.6
- Added auto resource generation on new caches.

1.0.5
- Updated caching headers.

1.0.4
- Added page caches list to admin options page.

1.0.3
- Added exception in cache loader for ajax.

1.0.2
- Added a cron that will refresh enabled caches every 8 hours.

1.0.1
- Fixed php warning within the cache model.

1.0.0
- First relase of Cache Ultra.



== Frequently Asked Questions ==
