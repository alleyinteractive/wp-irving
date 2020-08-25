# WP Irving #
**Contributors:** [alleyinteractive](https://profiles.wordpress.org/alleyinteractive/)  
**Tags:** irving, headless  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html  

WP Irving contains everything needed to power an Irving headless app with WordPress.

## Description ##

WP Irving is the companion WordPress plugin to [Irving](https://github.com/alleyinteractive/irving). Included are various APIs, plugin integrations, and other customizations/helpers to streamline Irving development when using WordPress as the CMS. It registers the API endpoints that Irving Core expects, which you can build upon to pass your data from WordPress to the frontend.

While WP Irving contains a few basic plugin integrations (and we hope to support more in the plugin in the future), overall the plugin is rather lightweight. It provides the tools you need to structure your API data appropriately for Irving, but it is up to you to build out the responses.

## Installation ##

1. Upload `/wp-irving/` folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

## Changelog ##

### 0.3.0 ###
* Fix: Cache clearing redirect
* Fix: Page title in the head component
* Fix: Menu URL
* Add: Integrations Manager
* Fix: Add permissions callbacks to REST API endpoints for WP 5.5
* Add: Yoast schema set through integrations manager
* Fix: Duplicates in post list component.
* Add: Pico integration
* Add: Coral component
* Add: Post meta component
* Fix: Yoast integration data formatting
* Add: Interstitial support to the post-list component
* Add: Term helper components
* Add: Set base URL automatically in query-pagination component on term archive pages
* Add: Jetpack site stats integration
* Add: Google Tag Manager integration
* Add: Back-end for handling SSO between Pico and Coral
* Add: Coral comment count component
* Fix: Byline component improvements

### 0.2.0 ###
This update includes major improvements to the way developers can build layouts for Irving sites using WordPress.

[See release notes](https://github.com/alleyinteractive/wp-irving/releases/tag/0.2.0)

### 0.1.0 ###
First stable release of the WP-Irving plugin.
