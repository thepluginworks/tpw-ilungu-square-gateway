=== TPW Square Gateway ===
Contributors: thepluginworks
Tags: square, payments, tpw
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.1.1

Add Square payment processing to TPW Core with direct HTTP payments and compatibility for existing TPW checkout flows.

== Description ==

TPW Square Gateway is the Square payments add-on for TPW Core. It keeps Square configuration and checkout support in a dedicated plugin while preserving the existing TPW payment flow used by live sites.

Use this plugin when your TPW-powered site needs Square payments without changing the established TPW checkout experience.

== Requirements ==

* WordPress 6.0 or higher
* PHP 7.4 or higher
* TPW Core installed and active
* A Square application ID, access token, and location ID
* Square enabled as an active TPW payment method

== Installation ==

1. Install and activate TPW Core.
2. Upload the `tpw-square-gateway` plugin folder to your WordPress plugins directory, or install the packaged ZIP from the plugin update flow.
3. Activate TPW Square Gateway.
4. Open the TPW payment settings and go to the Square settings page.
5. Enter your Square application ID, access token, and location ID.
6. Enable sandbox mode only for test environments, then save your settings.

== FAQ ==

= Does this plugin work without TPW Core? =

No. TPW Square Gateway extends TPW Core and should be used alongside an active TPW Core installation.

= What Square credentials do I need? =

You need a Square application ID, access token, and location ID for the account you want to process payments with.

== Changelog ==

= 1.1.1 =

* Added a public-facing plugin readme with setup steps, requirements, and common questions for site admins.

= 1.1.0 =

* Added in-dashboard update support for GitHub-distributed plugin releases.

= 1.0.0 =

* First stable release.