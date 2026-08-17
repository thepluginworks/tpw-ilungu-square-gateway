=== iLungu Square Gateway ===
Contributors: thepluginworks
Tags: square, payments, tpw
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 1.1.4

Add Square payment processing to iLungu Club with direct HTTP payments and compatibility for existing TPW checkout flows.

== Description ==

iLungu Square Gateway is the Square payments add-on for iLungu Club. It keeps Square configuration and checkout support in a dedicated plugin while preserving the existing TPW payment flow used by live sites.

Use this plugin when your iLungu Club-powered site needs Square payments without changing the established TPW checkout experience.

== Requirements ==

* WordPress 6.0 or higher
* PHP 7.4 or higher
* iLungu Club installed and active
* A Square application ID, access token, and location ID
* Square enabled as an active TPW payment method

== Installation ==

1. Install and activate iLungu Club.
2. Upload the `tpw-ilungu-square-gateway` plugin folder to your WordPress plugins directory, or install the packaged ZIP from the plugin update flow.
3. Activate iLungu Square Gateway.
4. Open the iLungu Club payment settings and go to the Square settings page.
5. Enter your Square application ID, access token, and location ID.
6. Enable sandbox mode only for test environments, then save your settings.

== FAQ ==

= Does this plugin work without iLungu Club? =

No. iLungu Square Gateway extends iLungu Club and should be used alongside an active iLungu Club installation.

= What Square credentials do I need? =

You need a Square application ID, access token, and location ID for the account you want to process payments with.

== Changelog ==

= 1.1.3 =

* Updated the legacy Square bridge to prefer the shared Core path contract and support both `tpw-flexiclub` and `tpw-ilungu-club` plugin folder locations.

= 1.1.2 =

* Updated plugin copy and admin messaging to refer to FlexiClub instead of TPW Core.
* Updated the legacy bridge path so compatibility loading targets the FlexiClub plugin location.

= 1.1.1 =

* Added a public-facing plugin readme with setup steps, requirements, and common questions for site admins.

= 1.1.0 =

* Added in-dashboard update support for GitHub-distributed plugin releases.

= 1.0.0 =

* First stable release.