# Changelog

All notable changes to TPW Square Gateway are documented in this file.

## 1.1.1 - 2026-03-25

- Added a public `readme.txt` in standard WordPress plugin format with concise setup guidance, requirements, and FAQ content for site admins.

## 1.1.0 - 2026-03-25

- Added a GitHub release packaging flow that builds a filtered `tpw-square-gateway.zip` archive using `.distignore` and preserves the correct `tpw-square-gateway/` root folder.
- Added a GitHub Actions release workflow that publishes the release asset, generates `tpw-square-gateway.json`, and deploys the manifest through GitHub Pages.
- Added a WordPress-native GitHub updater that reads the public manifest, handles context-aware cache refresh behaviour, injects update metadata into plugin update transients, and provides plugin details for the update modal.

## 1.0.0 - 2026-03-20

First stable production release.

- Added the standalone TPW Square Gateway add-on bootstrap, loader, admin, settings, compatibility loader, and webhook controller structure.
- Added add-on ownership of the Square settings page, Square settings registration, and the legacy `TPW_Square_Gateway` surface used by existing TPW integrations.
- Added direct HTTP payment processing against Square's Payments API, including request building, surcharge-aware totals, idempotency keys, and normalized payment error handling.
- Added legacy response adapters and Square class aliases so existing TPW consumers continue to work without requiring consumer-side code changes.
- Added conditional frontend enqueue ownership for the Square Web Payments SDK so payment pages continue to work with `TPW_Core_Payments.boot()` and `tpw_square_ready`.
- Preserved existing Square option keys, route slug, and checkout integration points while moving live Square ownership into the add-on.