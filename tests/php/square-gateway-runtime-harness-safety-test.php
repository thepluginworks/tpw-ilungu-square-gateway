<?php

$root_dir       = dirname( __DIR__, 2 );
$runtime_source = file_get_contents( $root_dir . '/tests/runtime/square-gateway-minimum-runtime.php' );
$http_source    = file_get_contents( $root_dir . '/tests/runtime/square-gateway-settings-http.php' );
$runner_source  = file_get_contents( $root_dir . '/tests/runtime/run-minimum-runtime.sh' );

if ( ! is_string( $runtime_source ) || ! is_string( $http_source ) || ! is_string( $runner_source ) ) {
	fwrite( STDERR, "Unable to read runtime harness source.\n" );
	exit( 1 );
}

foreach ( array(
	'render_settings_route(',
	'tpw_square_access_token',
	'tpw_square_app_id',
	'tpw_square_location_id',
) as $forbidden_fragment ) {
	if ( false !== strpos( $runtime_source, $forbidden_fragment ) ) {
		fwrite( STDERR, "Runtime harness reads credential-bearing settings output: {$forbidden_fragment}\n" );
		exit( 1 );
	}
}

foreach ( array(
	'wp_remote_retrieve_body',
	'CURLOPT_COOKIEJAR',
	'CURLOPT_COOKIEFILE',
	'CURLOPT_RETURNTRANSFER => true',
) as $forbidden_fragment ) {
	if ( false !== strpos( $http_source, $forbidden_fragment ) ) {
		fwrite( STDERR, "HTTP harness retains sensitive response or cookie material: {$forbidden_fragment}\n" );
		exit( 1 );
	}
}

foreach ( array(
	'SELECT option_name',
	'limit_response_size',
	'CURLOPT_WRITEFUNCTION',
	'tpw_square_gateway_http_restore_option( $credential_option',
	"'secure_auth'",
	'SECURE_AUTH_COOKIE',
	"SELECT option_value FROM {\$wpdb->options}",
	"wp_cache_delete( 'alloptions', 'options' )",
	'TPW_SQUARE_GATEWAY_TEST_SUITE',
) as $required_fragment ) {
	if ( false === strpos( $http_source . $runner_source, $required_fragment ) ) {
		fwrite( STDERR, "HTTP harness lacks credential-isolation guard: {$required_fragment}\n" );
		exit( 1 );
	}
}

foreach ( array(
	'LOCAL_PHP=',
	'LOCAL_WP_CLI=',
	'LOCAL_PHP_VERSION=',
	'expected PHP 8.3.x',
	'"$LOCAL_PHP" "$LOCAL_WP_CLI"',
) as $required_fragment ) {
	if ( false === strpos( $runner_source, $required_fragment ) ) {
		fwrite( STDERR, "Runtime runner lacks Local PHP 8.3 guard: {$required_fragment}\n" );
		exit( 1 );
	}
}

echo "square gateway runtime harness safety tests passed\n";
