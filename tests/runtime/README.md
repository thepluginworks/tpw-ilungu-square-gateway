# Minimum Runtime Checks

These checks run only through the local `tpw-minimum.local` WordPress installation. They require two existing isolated test users: one with `manage_options` and one without it. They never accept production URLs, arbitrary WordPress paths, or an installed plugin that is not this workspace. The runner invokes Local's configured PHP 8.3 binary and WP-CLI phar directly, then verifies the PHP version before WordPress loads.

Copy `.env.example` to `.env.local`, set only the isolated test-account logins, and run:

```sh
tests/runtime/run-minimum-runtime.sh
```

The default PHP suite verifies PHP and WordPress minimum versions, the active workspace plugin mount, direct settings-route capability access, non-administrator denial, Settings API nonce validity and rejection of a tampered nonce, a disposable surcharge save/load with restoration, and sandbox/production Square SDK registration. It never renders the settings form, reads or changes Square credential options, submits a payment, or calls Square.

The harness restores its surcharge and sandbox-mode values in both `finally` and shutdown restoration paths, then asserts that both values and their existence match the pre-test snapshot. The route and nonce checks are direct WordPress/PHP checks; they do not prove an authenticated HTTP form submission or browser rendering. Do not run it if site identity, workspace mount, account isolation, or restoration safety cannot be established.

The opt-in HTTP suite runs with `TPW_SQUARE_GATEWAY_TEST_SUITE=http`. It generates short-lived in-memory WordPress sessions for the configured isolated accounts and sends requests only to `https://tpw-minimum.local/wp-admin/admin.php?page=tpw-square-settings` and `options.php`. It discards ordinary response bodies, retains no cookies, and verifies only status codes and local disposable-option state. Before using a synthetic access-token canary, it queries only credential option names and refuses execution if any existing Square credential option is present. The canary response is streamed and discarded while checking only whether the synthetic value appears; it is never printed, saved, or logged. The HTTP suite is disabled until independent safety review.
