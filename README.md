# TPW Square Gateway

Current version: 1.1.3

TPW Square Gateway is the production Square payments add-on for FlexiClub. It moves Square ownership out of the base platform while preserving the existing TPW checkout flow, settings keys, frontend payment boot process, and legacy `TPW_Square_Gateway` compatibility surface used by existing TPW integrations.

## Features

- Direct HTTP payment processing against Square's Payments API without bundling the legacy Square PHP SDK in the add-on.
- Compatibility bridge for legacy TPW consumers that still call `TPW_Square_Gateway::process_payment()`.
- Response adapters and class aliases that preserve expected `Square\\Types\\CreatePaymentResponse` style contracts for existing consumers.
- Add-on ownership of Square settings rendering and settings registration while keeping existing option keys and the `tpw-square-settings` route.
- Conditional frontend ownership of the Square Web Payments SDK so `TPW_Core_Payments.boot()` continues to mount the Square UI and emit `tpw_square_ready` on payment pages.
- Safe behavior when FlexiClub is unavailable.

## Requirements

- WordPress with FlexiClub installed and active.
- A Square application ID, access token, and location ID.
- Square enabled as an active TPW payment method.

## Installation

1. Install and activate FlexiClub.
2. Install and activate TPW Square Gateway.
3. Go to the FlexiClub payment settings and open the Square settings page.
4. Enter the Square application ID, access token, and location ID.
5. Enable sandbox mode only for test environments.
6. Confirm Square is enabled as an active TPW payment method.

## Version History

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Updates

TPW Square Gateway is distributed through GitHub releases.

- Production install packages are published as `tpw-square-gateway.zip` with the correct `tpw-square-gateway/` archive root.
- WordPress update checks read the public manifest at `https://thepluginworks.github.io/tpw-square-gateway/tpw-square-gateway.json`.
- One-click updates use the exact tagged GitHub release asset URL published in that manifest.