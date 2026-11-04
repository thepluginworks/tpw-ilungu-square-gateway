<?php

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "This test must run through WordPress.\n" );
	exit( 1 );
}

function tpw_square_gateway_http_fail( string $message ): void {
	throw new RuntimeException( $message );
}

function tpw_square_gateway_http_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		tpw_square_gateway_http_fail( $message );
	}
}

function tpw_square_gateway_http_env( string $name ): string {
	$value = getenv( $name );

	if ( false === $value || '' === $value ) {
		tpw_square_gateway_http_fail( "Missing required environment variable: {$name}" );
	}

	return $value;
}

function tpw_square_gateway_http_snapshot_option( string $name ): array {
	global $wpdb;

	$row = $wpdb->get_row(
		$wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name )
	);

	return [
		'exists' => null !== $row,
		'value'  => null !== $row ? $row->option_value : null,
	];
}

function tpw_square_gateway_http_refresh_option_cache( string $name ): void {
	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
	wp_cache_delete( $name, 'options' );
}

function tpw_square_gateway_http_restore_option( string $name, array $snapshot ): void {
	global $wpdb;

	if ( $snapshot['exists'] ) {
		$wpdb->update( $wpdb->options, [ 'option_value' => $snapshot['value'] ], [ 'option_name' => $name ] );
	} else {
		$wpdb->delete( $wpdb->options, [ 'option_name' => $name ] );
	}

	tpw_square_gateway_http_refresh_option_cache( $name );
}

function tpw_square_gateway_http_assert_option_restored( string $name, array $snapshot ): void {
	$actual = tpw_square_gateway_http_snapshot_option( $name );

	tpw_square_gateway_http_assert( $snapshot['exists'] === $actual['exists'], "Option restoration changed existence for {$name}." );
	tpw_square_gateway_http_assert( $snapshot['value'] === $actual['value'], "Option restoration changed value for {$name}." );
}

function tpw_square_gateway_http_session( WP_User $user ): array {
	$expiration = time() + MINUTE_IN_SECONDS;
	$manager    = WP_Session_Tokens::get_instance( $user->ID );
	$token      = $manager->create( $expiration );

	$auth_cookie      = wp_generate_auth_cookie( $user->ID, $expiration, 'secure_auth', $token );
	$logged_in_cookie = wp_generate_auth_cookie( $user->ID, $expiration, 'logged_in', $token );

	$_COOKIE[ LOGGED_IN_COOKIE ] = $logged_in_cookie;
	wp_set_current_user( $user->ID );

	return [
		'cookie'  => SECURE_AUTH_COOKIE . '=' . $auth_cookie . '; ' . LOGGED_IN_COOKIE . '=' . $logged_in_cookie,
		'manager' => $manager,
		'nonce'   => wp_create_nonce( 'tpw_payment_settings-options' ),
		'token'   => $token,
	];
}

function tpw_square_gateway_http_status( string $url, string $cookie, array $args = [] ): int {
	$response = wp_remote_request(
		$url,
		array_replace_recursive(
			[
				'redirection'         => 0,
				'timeout'             => 15,
				'sslverify'           => false,
				'limit_response_size' => 0,
				'headers'             => [
					'Cookie' => $cookie,
				],
			],
			$args
		)
	);

	tpw_square_gateway_http_assert( ! is_wp_error( $response ), 'Settings HTTP request failed.' );

	return (int) wp_remote_retrieve_response_code( $response );
}

function tpw_square_gateway_http_response_contains_canary( string $url, string $cookie, string $canary ): array {
	$found = false;
	$carry = '';
	$curl  = curl_init( $url );

	tpw_square_gateway_http_assert( false !== $curl, 'Unable to initialise the Local-only HTTP client.' );

	curl_setopt_array(
		$curl,
		[
			CURLOPT_FOLLOWLOCATION => false,
			CURLOPT_HTTPHEADER     => [ 'Cookie: ' . $cookie ],
			CURLOPT_RETURNTRANSFER => false,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_TIMEOUT        => 15,
			CURLOPT_WRITEFUNCTION  => static function ( $handle, string $chunk ) use ( &$found, &$carry, $canary ): int {
				$found = $found || false !== strpos( $carry . $chunk, $canary );
				$carry = substr( $carry . $chunk, - strlen( $canary ) );

				return strlen( $chunk );
			},
		]
	);

	$success = curl_exec( $curl );
	$status  = (int) curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
	curl_close( $curl );

	tpw_square_gateway_http_assert( true === $success, 'Credential non-disclosure HTTP request failed.' );

	return [
		'contains_canary' => $found,
		'status'          => $status,
	];
}

try {
	$expected_url         = tpw_square_gateway_http_env( 'TPW_SQUARE_GATEWAY_TEST_URL' );
	$expected_plugin_path = tpw_square_gateway_http_env( 'TPW_SQUARE_GATEWAY_TEST_PLUGIN_PATH' );
	$admin_login          = tpw_square_gateway_http_env( 'TPW_SQUARE_GATEWAY_TEST_ADMIN_LOGIN' );
	$nonadmin_login       = tpw_square_gateway_http_env( 'TPW_SQUARE_GATEWAY_TEST_NONADMIN_LOGIN' );
	$expected_host        = wp_parse_url( $expected_url, PHP_URL_HOST );

	tpw_square_gateway_http_assert( 'local' === wp_get_environment_type(), 'Refusing a non-local WordPress environment.' );
	tpw_square_gateway_http_assert( 'tpw-minimum.local' === $expected_host, 'Unexpected configured test host.' );
	tpw_square_gateway_http_assert( $expected_host === wp_parse_url( home_url(), PHP_URL_HOST ), 'WordPress home URL does not match the test host.' );
	tpw_square_gateway_http_assert( PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 3, 'PHP 8.3.x is required.' );
	tpw_square_gateway_http_assert( 0 === strpos( $GLOBALS['wp_version'], '6.5.' ), 'WordPress 6.5.x is required.' );
	tpw_square_gateway_http_assert( function_exists( 'curl_init' ), 'The Local PHP cURL extension is required.' );

	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$plugin_directory = WP_PLUGIN_DIR . '/tpw-ilungu-square-gateway';
	tpw_square_gateway_http_assert( realpath( $expected_plugin_path ) === realpath( $plugin_directory ), 'The installed Square Gateway plugin is not this workspace.' );
	tpw_square_gateway_http_assert( is_plugin_active( 'tpw-ilungu-square-gateway/ilungu-square-gateway.php' ), 'Square Gateway is not active.' );
	tpw_square_gateway_http_assert( 'addon' === tpw_core_get_square_settings_route_owner(), 'Square Gateway does not own the Club settings route.' );

	$admin_user    = get_user_by( 'login', $admin_login );
	$nonadmin_user = get_user_by( 'login', $nonadmin_login );

	tpw_square_gateway_http_assert( $admin_user instanceof WP_User && user_can( $admin_user, 'manage_options' ), 'Configured isolated administrator account cannot manage options.' );
	tpw_square_gateway_http_assert( $nonadmin_user instanceof WP_User && ! user_can( $nonadmin_user, 'manage_options' ), 'Configured isolated non-administrator account can manage options.' );

	global $wpdb;
	$credential_options = [
		'tpw_square_app_id',
		'tpw_square_access_token',
		'tpw_square_location_id',
	];
	$placeholders       = implode( ',', array_fill( 0, count( $credential_options ), '%s' ) );
	$query              = $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name IN ({$placeholders})", ...$credential_options );

	tpw_square_gateway_http_assert( [] === $wpdb->get_col( $query ), 'Refusing to test with existing Square credential options.' );

	$surcharge_option   = 'tpw_surcharge_square_percent';
	$surcharge_snapshot = tpw_square_gateway_http_snapshot_option( $surcharge_option );
	$settings_url       = $expected_url . '/wp-admin/admin.php?page=tpw-square-settings';
	$options_url        = $expected_url . '/wp-admin/options.php';
	$admin_session      = tpw_square_gateway_http_session( $admin_user );
	$nonadmin_session   = tpw_square_gateway_http_session( $nonadmin_user );

	try {
		tpw_square_gateway_http_assert( 200 === tpw_square_gateway_http_status( $settings_url, $admin_session['cookie'] ), 'Administrator cannot access the Square settings route over HTTP.' );
		tpw_square_gateway_http_assert( 403 === tpw_square_gateway_http_status( $settings_url, $nonadmin_session['cookie'] ), 'Non-administrator was not denied the Square settings route over HTTP.' );

		$nonce = $admin_session['nonce'];
		tpw_square_gateway_http_assert(
			302 === tpw_square_gateway_http_status(
				$options_url,
				$admin_session['cookie'],
				[
					'method' => 'POST',
					'body'   => [
						'action'                       => 'update',
						'option_page'                  => 'tpw_payment_settings',
						'_wpnonce'                     => $nonce,
						'tpw_surcharge_square_percent' => '3.256',
					],
				]
			),
			'Valid Square settings submission was not redirected.'
		);
		tpw_square_gateway_http_refresh_option_cache( $surcharge_option );
		$surcharge_after_request = tpw_square_gateway_http_snapshot_option( $surcharge_option );
		tpw_square_gateway_http_assert( $surcharge_after_request['exists'] && 3.26 === (float) $surcharge_after_request['value'], 'HTTP settings submission did not save the sanitised surcharge.' );

		foreach ( [ '', $nonce . 'invalid' ] as $invalid_nonce ) {
			tpw_square_gateway_http_assert(
				403 === tpw_square_gateway_http_status(
					$options_url,
					$admin_session['cookie'],
					[
						'method' => 'POST',
						'body'   => [
							'action'                       => 'update',
							'option_page'                  => 'tpw_payment_settings',
							'_wpnonce'                     => $invalid_nonce,
							'tpw_surcharge_square_percent' => '3.256',
						],
					]
				),
				'Missing or tampered settings nonce was not rejected over HTTP.'
			);
		}

		$canary = 'tpw-square-runtime-canary-' . bin2hex( random_bytes( 16 ) );
		update_option( 'tpw_square_access_token', $canary );

		$canary_result = tpw_square_gateway_http_response_contains_canary( $settings_url, $admin_session['cookie'], $canary );
		tpw_square_gateway_http_assert( 200 === $canary_result['status'], 'Credential non-disclosure request did not reach the Square settings route.' );
		tpw_square_gateway_http_assert( ! $canary_result['contains_canary'], 'Square settings response exposed the synthetic credential canary.' );
	} finally {
		tpw_square_gateway_http_restore_option( $surcharge_option, $surcharge_snapshot );
		foreach ( $credential_options as $credential_option ) {
			tpw_square_gateway_http_restore_option( $credential_option, [ 'exists' => false, 'value' => null ] );
		}
		$admin_session['manager']->destroy( $admin_session['token'] );
		$nonadmin_session['manager']->destroy( $nonadmin_session['token'] );
		tpw_square_gateway_http_assert_option_restored( $surcharge_option, $surcharge_snapshot );
	}

	wp_set_current_user( 0 );

	echo "square gateway settings HTTP checks passed\n";
} catch ( Throwable $error ) {
	wp_set_current_user( 0 );
	fwrite( STDERR, "square gateway settings HTTP checks failed: {$error->getMessage()}\n" );
	exit( 1 );
}
