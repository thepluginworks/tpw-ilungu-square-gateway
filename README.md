# iLungu Square Gateway

Current version: 1.1.5

iLungu Square Gateway is the production Square payments add-on for iLungu Club. It moves Square ownership out of the base platform while preserving the existing TPW checkout flow, settings keys, frontend payment boot process, and legacy `TPW_Square_Gateway` compatibility surface used by existing TPW integrations.

It is a free supporting payment integration for the iLungu ecosystem, distributed through GitHub under GPL-3.0-or-later.

## Features

- Direct HTTP payment processing against Square's Payments API without bundling the legacy Square PHP SDK in the add-on.
- Compatibility bridge for legacy TPW consumers that still call `TPW_Square_Gateway::process_payment()`.
- Response adapters and class aliases that preserve expected `Square\\Types\\CreatePaymentResponse` style contracts for existing consumers.
- Add-on ownership of Square settings rendering and settings registration while keeping existing option keys and the `tpw-square-settings` route.
- Conditional frontend ownership of the Square Web Payments SDK so `TPW_Core_Payments.boot()` continues to mount the Square UI and emit `tpw_square_ready` on payment pages.
- Safe behavior when iLungu Club is unavailable.

## Requirements

- WordPress 6.5 or later with iLungu Club installed and active.
- PHP 8.3 or later.
- A Square application ID, access token, and location ID.
- Square enabled as an active TPW payment method.

## Installation

1. Install and activate iLungu Club.
2. Install and activate iLungu Square Gateway.
3. Sign in as a WordPress administrator and go to the iLungu Club payment settings.
4. Open the Square settings page and enter the application ID, access token, and location ID issued for the Square account that will process the site's payments.
5. Enable sandbox mode only for a Square sandbox account in a test environment; disable it before using production Square credentials.
6. Save the settings and confirm Square is enabled as an active TPW payment method.

## Credential ownership and support

- The Square account owner supplies and controls the application ID, access token, and location ID. Do not send access tokens in support requests, screenshots, source code, or repository issues.
- Square settings are available only from the administrator-protected iLungu Club settings route. The add-on uses the existing `tpw_square_*` option keys for compatibility with iLungu Club; they are retained on uninstall.
- This add-on does not own iLungu Club payment records or remote Square data. See **Data Retention** for the exact local cleanup boundary.
- Use the established iLungu Club support channel for site-specific setup help. Do not share production credentials when requesting support.

## Payment recovery API

The legacy `TPW_Square_Gateway::process_payment( array $args )` contract remains compatible. Consumers that omit `idempotency_key` continue to receive an automatically generated Square key.

Payment-owning integrations that need safe retry and reconciliation can supply a stable, non-empty string of up to 192 characters in `idempotency_key`. The gateway passes it unchanged to Square. The caller must persist that key and replay the identical payment request with the same key after an uncertain transport result; it must not create a replacement key for that retry.

Successful payment responses retain the existing `Square\Types\CreatePaymentResponse`-compatible adapter. Persist `getPayment()->getId()` and reconcile it through the additive `TPW_Square_Gateway::get_payment( string $payment_id )` API. It uses Square's authenticated payment lookup and returns the same response adapter or a `WP_Error`.

`WP_Error` codes and response types are unchanged. Gateway payment errors now include additive `outcome` metadata: `failed` means Square returned a terminal/request failure, while `uncertain` means that no payment outcome can safely be inferred. For an uncertain create result, `retry_with_same_idempotency_key` is `true`. Do not release capacity, issue fulfilment, or submit a new payment while a result remains uncertain.

Square does not provide payment lookup by idempotency key. If a payment ID was not received, recovery is limited to promptly replaying the identical create request with the same key. After Square's retry/idempotency period, a key alone cannot prove the final provider outcome.

## Version History

See [CHANGELOG.md](CHANGELOG.md) for release history.

## Updates

iLungu Square Gateway is distributed through GitHub releases.

- Production install packages are published as `tpw-ilungu-square-gateway.zip` with the correct `tpw-ilungu-square-gateway/` archive root.
- WordPress update checks read the public manifest at `https://thepluginworks.github.io/tpw-ilungu-square-gateway/tpw-ilungu-square-gateway.json`.
- One-click updates use the exact tagged GitHub release asset URL published in that manifest.

## License

iLungu Square Gateway is licensed under the GNU General Public License, version 3 or later (GPL-3.0-or-later). See [LICENSE](LICENSE) for the complete licence text.

## Data Retention

- Deactivation is non-destructive and uninstall preserves all data by default.
- Administrators can enable **Delete all plugin data on uninstall** from the Square settings page. This opt-in removes only the Square Gateway updater cache and Core-missing notice transient.
- Existing Square configuration options are retained because they are compatibility state that can pre-date this add-on and is shared with iLungu Club.
- iLungu Club payment methods, RSVP payments, payment logs, submissions, and other shared records are never removed by this plugin.
- Local uninstall never deletes remote Square payments, accounts, locations, credentials, customers, orders, refunds, webhooks, or provider records.