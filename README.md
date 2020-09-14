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

### 0.4.0 ###

* Update social share component. #259
* Add fragment property to post permalink component config. #258
* Add support for a fallback image in the customizer. #257
* Add initial data layer to GTM integrations. #255
* Add banned usernames field for Coral. #256
* Add allowed tiers input for Pico/Coral SSO to Irving Integrations settings page. #254
* Allow user to set custom username through Pico/Coral integration. #241
* Add image performance enhancements in irving/post-featured-image. #251
* Add `post_ids_to_skip` config to the `irving/post-list` component. #250
* Implement local WP username storage for Coral users. #249
* Support item overrides in irving/post-list components. #247
* Hydrate WordPress data for images. #244
* Improvements to <head> management, and block library support. #246
* Ensure post previews do not use the homepage template. #248
* Enqueue block library styles. #243
* Add share links config to social sharing component. #242
* Remove unnecessary second param for VIP cache purge filter. #240
* Add support to the pagination component for post type archives. #239
* Define component for Coral to be included in template JSON. #222
* Allow use_context in templates. #223

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
* Add: Social links component
* Fix: Safe Redirect Manager integration

### 0.2.0 ###
This update includes major improvements to the way developers can build layouts for Irving sites using WordPress.

[See release notes](https://github.com/alleyinteractive/wp-irving/releases/tag/0.2.0)

### 0.1.0 ###
First stable release of the WP-Irving plugin.
