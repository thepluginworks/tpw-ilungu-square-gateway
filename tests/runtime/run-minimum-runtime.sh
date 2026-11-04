#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
CONFIG_PATH="${TPW_SQUARE_GATEWAY_TEST_CONFIG:-$ROOT_DIR/tests/runtime/.env.local}"
TEST_SUITE="${TPW_SQUARE_GATEWAY_TEST_SUITE:-php}"
LOCAL_PHP="/Users/stuart/Library/Application Support/Local/lightning-services/php-8.3.36+0/bin/darwin-arm64/bin/php"
LOCAL_WP_CLI="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/posix/wp"
LOCAL_PHP_CONFIG="/Users/stuart/Library/Application Support/Local/run/oEdOdls2T/conf/php"
LOCAL_WP_CLI_CONFIG="/Applications/Local.app/Contents/Resources/extraResources/bin/wp-cli/config.yaml"

if [[ ! -f "$CONFIG_PATH" ]]; then
	echo "Missing isolated test configuration: $CONFIG_PATH" >&2
	exit 1
fi

set -a
# shellcheck disable=SC1090
source "$CONFIG_PATH"
set +a

required_variables=(
	TPW_SQUARE_GATEWAY_TEST_URL
	TPW_SQUARE_GATEWAY_TEST_WP_PATH
	TPW_SQUARE_GATEWAY_TEST_PLUGIN_PATH
	TPW_SQUARE_GATEWAY_TEST_ADMIN_LOGIN
	TPW_SQUARE_GATEWAY_TEST_NONADMIN_LOGIN
)

for variable in "${required_variables[@]}"; do
	if [[ -z "${!variable:-}" ]]; then
		echo "Missing required test configuration: $variable" >&2
		exit 1
	fi
done

if [[ "$TPW_SQUARE_GATEWAY_TEST_URL" != "https://tpw-minimum.local" ]]; then
	echo "Refusing to run outside https://tpw-minimum.local." >&2
	exit 1
fi

if [[ "$(realpath "$TPW_SQUARE_GATEWAY_TEST_WP_PATH")" != "/Volumes/Work/LocalSites/tpw-minimum/app/public" ]]; then
	echo "Refusing an unexpected WordPress path." >&2
	exit 1
fi

if [[ "$(realpath "$TPW_SQUARE_GATEWAY_TEST_PLUGIN_PATH")" != "$ROOT_DIR" ]]; then
	echo "Refusing an unexpected Square Gateway workspace path." >&2
	exit 1
fi

if [[ ! -x "$LOCAL_PHP" || ! -f "$LOCAL_WP_CLI" || ! -d "$LOCAL_PHP_CONFIG" || ! -f "$LOCAL_WP_CLI_CONFIG" ]]; then
	echo "Local PHP 8.3 or WP-CLI runtime files are unavailable." >&2
	exit 1
fi

LOCAL_PHP_VERSION="$(PHPRC="$LOCAL_PHP_CONFIG" "$LOCAL_PHP" -r 'echo PHP_VERSION;')"

if [[ ! "$LOCAL_PHP_VERSION" =~ ^8\.3\. ]]; then
	echo "Refusing Local PHP version $LOCAL_PHP_VERSION; expected PHP 8.3.x." >&2
	exit 1
fi

case "$TEST_SUITE" in
	php)
		TEST_SCRIPT="$ROOT_DIR/tests/runtime/square-gateway-minimum-runtime.php"
		;;
	http)
		TEST_SCRIPT="$ROOT_DIR/tests/runtime/square-gateway-settings-http.php"
		;;
	*)
		echo "Unknown minimum-runtime test suite: $TEST_SUITE" >&2
		exit 1
		;;
esac

PHPRC="$LOCAL_PHP_CONFIG" \
WP_CLI_CONFIG_PATH="$LOCAL_WP_CLI_CONFIG" \
WP_CLI_DISABLE_AUTO_CHECK_UPDATE=1 \
"$LOCAL_PHP" "$LOCAL_WP_CLI" --path="$TPW_SQUARE_GATEWAY_TEST_WP_PATH" eval-file \
	"$TEST_SCRIPT"
