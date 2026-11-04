=== iLungu Square Gateway ===
Contributors: thepluginworks
Tags: square, payments, tpw
Requires at least: 6.5
Requires PHP: 8.3
Stable tag: 1.1.5
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Add Square payment processing to iLungu Club with direct HTTP payments and compatibility for existing TPW checkout flows.

== Description ==

iLungu Square Gateway is the Square payments add-on for iLungu Club. It keeps Square configuration and checkout support in a dedicated plugin while preserving the existing TPW payment flow used by live sites.

Use this plugin when your iLungu Club-powered site needs Square payments without changing the established TPW checkout experience.

== License ==

iLungu Square Gateway is licensed under the GNU General Public License, version 3 or later (GPL-3.0-or-later). See `LICENSE` for the complete licence text.

== Requirements ==

* WordPress 6.5 or higher
* PHP 8.3 or higher
* iLungu Club installed and active
* A Square application ID, access token, and location ID
* Square enabled as an active TPW payment method

== Installation ==

1. Install and activate iLungu Club.
2. Upload the `tpw-ilungu-square-gateway` plugin folder to your WordPress plugins directory, or install the packaged ZIP from the plugin update flow.
3. Activate iLungu Square Gateway.
4. Sign in as a WordPress administrator, then open the Square settings page from iLungu Club payment settings.
5. Enter the application ID, access token, and location ID issued for the Square account that will process the site's payments.
6. Enable sandbox mode only for a Square sandbox account in a test environment; disable it before using production Square credentials.
7. Save the settings and confirm Square is enabled as an active TPW payment method.

== FAQ ==

= Does this plugin work without iLungu Club? =

No. iLungu Square Gateway extends iLungu Club and should be used alongside an active iLungu Club installation.

= What Square credentials do I need? =

You need a Square application ID, access token, and location ID for the account you want to process payments with.

= Who owns the Square credentials? =

The Square account owner supplies and controls the credentials. Do not send access tokens in support requests, screenshots, source code, or repository issues. Square settings are available only from the administrator-protected iLungu Club settings route. Existing `tpw_square_*` settings are retained on uninstall for iLungu Club compatibility.

= Where do I get setup support? =

Use the established iLungu Club support channel for site-specific setup help. Do not share production credentials when requesting support.

= What happens when I deactivate or uninstall the plugin? =

Deactivation is non-destructive. Uninstall preserves data by default. An administrator can enable Delete all plugin data on uninstall from the Square settings page; that opt-in removes only Square Gateway-owned local runtime state. Existing Square settings, iLungu Club payment records, and remote Square/provider records are always preserved.

== Changelog ==

= 1.1.5 =

* Raised the declared compatibility floor to PHP 8.3 and WordPress 6.5.
* Improved administrator-only Square settings safety, including preventing configured access tokens from appearing in settings HTML while preserving a stored token when the field is left blank.
* Added GPL-3.0-or-later licensing declarations and the complete repository licence text.
* Clarified customer setup, credential-ownership, sandbox, uninstall, and support guidance.

= 1.1.4 =

* Renamed the public product to iLungu Square Gateway and migrated the release package identity to `tpw-ilungu-square-gateway`.
* Added preserve-by-default uninstall guidance and removed sensitive payment request diagnostics from logging.

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