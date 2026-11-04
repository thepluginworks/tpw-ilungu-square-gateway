<?php

if ( ! defined( 'ABSPATH' ) ) {
	fwrite( STDERR, "This test must run through WordPress.\n" );
	exit( 1 );
}

function tpw_square_gateway_runtime_fail( string $message ): void {
	throw new RuntimeException( $message );
}

function tpw_square_gateway_runtime_assert( bool $condition, string $message ): void {
	if ( ! $condition ) {
		tpw_square_gateway_runtime_fail( $message );
	}
}

function tpw_square_gateway_runtime_env( string $name ): string {
	$value = getenv( $name );

	if ( false === $value || '' === $value ) {
		tpw_square_gateway_runtime_fail( "Missing required environment variable: {$name}" );
	}

	return $value;
}

function tpw_square_gateway_runtime_snapshot_option( string $name ): array {
	$missing = new stdClass();
	$value   = get_option( $name, $missing );

	return [
		'exists' => $value !== $missing,
		'value'  => $value !== $missing ? $value : null,
	];
}

function tpw_square_gateway_runtime_restore_option( string $name, array $snapshot ): void {
	if ( $snapshot['exists'] ) {
		update_option( $name, $snapshot['value'] );
		return;
	}

	delete_option( $name );
}

function tpw_square_gateway_runtime_assert_option_restored( string $name, array $snapshot ): void {
	$actual = tpw_square_gateway_runtime_snapshot_option( $name );

	tpw_square_gateway_runtime_assert( $snapshot['exists'] === $actual['exists'], "Option restoration changed existence for {$name}." );
	tpw_square_gateway_runtime_assert( $snapshot['value'] === $actual['value'], "Option restoration changed value for {$name}." );
}

try {
	$expected_url         = tpw_square_gateway_runtime_env( 'TPW_SQUARE_GATEWAY_TEST_URL' );
	$expected_plugin_path = tpw_square_gateway_runtime_env( 'TPW_SQUARE_GATEWAY_TEST_PLUGIN_PATH' );
	$admin_login          = tpw_square_gateway_runtime_env( 'TPW_SQUARE_GATEWAY_TEST_ADMIN_LOGIN' );
	$nonadmin_login       = tpw_square_gateway_runtime_env( 'TPW_SQUARE_GATEWAY_TEST_NONADMIN_LOGIN' );
	$expected_host        = wp_parse_url( $expected_url, PHP_URL_HOST );

	tpw_square_gateway_runtime_assert( 'local' === wp_get_environment_type(), 'Refusing a non-local WordPress environment.' );
	tpw_square_gateway_runtime_assert( 'tpw-minimum.local' === $expected_host, 'Unexpected configured test host.' );
	tpw_square_gateway_runtime_assert( $expected_host === wp_parse_url( home_url(), PHP_URL_HOST ), 'WordPress home URL does not match the test host.' );
	tpw_square_gateway_runtime_assert( PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION === 3, 'PHP 8.3.x is required.' );
	tpw_square_gateway_runtime_assert( 0 === strpos( $GLOBALS['wp_version'], '6.5.' ), 'WordPress 6.5.x is required.' );

	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$plugin_directory = WP_PLUGIN_DIR . '/tpw-ilungu-square-gateway';
	tpw_square_gateway_runtime_assert( realpath( $expected_plugin_path ) === realpath( $plugin_directory ), 'The installed Square Gateway plugin is not this workspace.' );
	tpw_square_gateway_runtime_assert( is_plugin_active( 'tpw-ilungu-square-gateway/ilungu-square-gateway.php' ), 'Square Gateway is not active.' );
	tpw_square_gateway_runtime_assert( class_exists( 'TPW_Square_Gateway_Admin' ), 'Square Gateway did not bootstrap.' );
	tpw_square_gateway_runtime_assert( class_exists( 'TPW_Square_Gateway_Core_Integration' ), 'Square Gateway Core integration did not bootstrap.' );

	$admin_user    = get_user_by( 'login', $admin_login );
	$nonadmin_user = get_user_by( 'login', $nonadmin_login );

	tpw_square_gateway_runtime_assert( $admin_user instanceof WP_User, 'Configured isolated administrator account was not found.' );
	tpw_square_gateway_runtime_assert( $nonadmin_user instanceof WP_User, 'Configured isolated non-administrator account was not found.' );
	tpw_square_gateway_runtime_assert( user_can( $admin_user, 'manage_options' ), 'Configured administrator account cannot manage options.' );
	tpw_square_gateway_runtime_assert( ! user_can( $nonadmin_user, 'manage_options' ), 'Configured non-administrator account can manage options.' );

	$admin        = new TPW_Square_Gateway_Admin( true );
	$route_access = new ReflectionMethod( $admin, 'can_render_settings_route' );
	$route_access->setAccessible( true );

	wp_set_current_user( $admin_user->ID );
	tpw_square_gateway_runtime_assert( true === $route_access->invoke( $admin, 'tpw-square-settings' ), 'Administrator cannot access the Square settings route.' );
	tpw_square_gateway_runtime_assert( false === $route_access->invoke( $admin, 'unexpected-route' ), 'Administrator can access an unexpected Square settings route.' );

	$nonce = wp_create_nonce( 'tpw_payment_settings-options' );
	tpw_square_gateway_runtime_assert( false !== wp_verify_nonce( $nonce, 'tpw_payment_settings-options' ), 'Settings API nonce is invalid.' );
	tpw_square_gateway_runtime_assert( false === wp_verify_nonce( $nonce . 'invalid', 'tpw_payment_settings-options' ), 'Tampered Settings API nonce was accepted.' );

	wp_set_current_user( $nonadmin_user->ID );
	tpw_square_gateway_runtime_assert( false === $route_access->invoke( $admin, 'tpw-square-settings' ), 'Non-administrator can access the Square settings route.' );

	$surcharge_option   = 'tpw_surcharge_square_percent';
	$sandbox_option     = 'tpw_square_sandbox_mode';
	$surcharge_snapshot = tpw_square_gateway_runtime_snapshot_option( $surcharge_option );
	$sandbox_snapshot   = tpw_square_gateway_runtime_snapshot_option( $sandbox_option );

	register_shutdown_function(
		static function () use ( $surcharge_option, $surcharge_snapshot, $sandbox_option, $sandbox_snapshot ): void {
			tpw_square_gateway_runtime_restore_option( $surcharge_option, $surcharge_snapshot );
			tpw_square_gateway_runtime_restore_option( $sandbox_option, $sandbox_snapshot );
		}
	);

	try {
		$settings          = new TPW_Square_Gateway_Settings();
		$disposable_value  = $settings->sanitize_surcharge_value( '3.256' );

		tpw_square_gateway_runtime_assert( 3.26 === $disposable_value, 'Disposable surcharge value was not sanitised.' );
		update_option( $surcharge_option, $disposable_value );
		tpw_square_gateway_runtime_assert( 3.26 === (float) get_option( $surcharge_option ), 'Disposable surcharge setting did not persist.' );

		$integration = new TPW_Square_Gateway_Core_Integration();
		$config      = [
			'activeMethods' => [ 'square' ],
		];

		update_option( $sandbox_option, '1' );
		$integration->enqueue_square_sdk_for_payments_page( $config, [] );
		$scripts = wp_scripts();
		tpw_square_gateway_runtime_assert( isset( $scripts->registered['square-web-payments'] ), 'Square SDK was not registered for sandbox mode.' );
		tpw_square_gateway_runtime_assert( 'https://sandbox.web.squarecdn.com/v1/square.js' === $scripts->registered['square-web-payments']->src, 'Sandbox Square SDK URL is incorrect.' );

		wp_dequeue_script( 'square-web-payments' );
		wp_deregister_script( 'square-web-payments' );
		update_option( $sandbox_option, '0' );
		$integration->enqueue_square_sdk_for_payments_page( $config, [] );
		$scripts = wp_scripts();
		tpw_square_gateway_runtime_assert( isset( $scripts->registered['square-web-payments'] ), 'Square SDK was not registered for production mode.' );
		tpw_square_gateway_runtime_assert( 'https://web.squarecdn.com/v1/square.js' === $scripts->registered['square-web-payments']->src, 'Production Square SDK URL is incorrect.' );
	} finally {
		tpw_square_gateway_runtime_restore_option( $surcharge_option, $surcharge_snapshot );
		tpw_square_gateway_runtime_restore_option( $sandbox_option, $sandbox_snapshot );
		tpw_square_gateway_runtime_assert_option_restored( $surcharge_option, $surcharge_snapshot );
		tpw_square_gateway_runtime_assert_option_restored( $sandbox_option, $sandbox_snapshot );
	}

	wp_set_current_user( 0 );

	echo "square gateway minimum runtime checks passed\n";
} catch ( Throwable $error ) {
	wp_set_current_user( 0 );
	fwrite( STDERR, "square gateway minimum runtime checks failed: {$error->getMessage()}\n" );
	exit( 1 );
}
