<?php

define( 'ABSPATH', __DIR__ . '/' );

$options = array();
$site_transients = array();
$transients = array();

function get_option( $name, $default = false ) {
    global $options;

    return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
}

function delete_option( $name ) {
    global $options;

    unset( $options[ $name ] );
}

function delete_site_transient( $name ) {
    global $site_transients;

    unset( $site_transients[ $name ] );
}

function delete_transient( $name ) {
    global $transients;

    unset( $transients[ $name ] );
}

class TPW_Square_Gateway_Updater {
    const CACHE_KEY = 'tpw_square_gateway_update_manifest';
}

class TPW_Square_Gateway_Admin {
    const CORE_MISSING_NOTICE_TRANSIENT = 'tpw_square_gateway_core_missing_notice';
}

require dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-lifecycle.php';

function assert_same( $expected, $actual, $label ) {
    if ( $expected !== $actual ) {
        fwrite( STDERR, $label . "\n" );
        exit( 1 );
    }
}

assert_same( '1', TPW_Square_Gateway_Lifecycle::sanitize_delete_data_on_uninstall( '1' ), 'Exact opt-in must be retained.' );
assert_same( '0', TPW_Square_Gateway_Lifecycle::sanitize_delete_data_on_uninstall( 1 ), 'Integer opt-in must be rejected.' );
assert_same( '0', TPW_Square_Gateway_Lifecycle::sanitize_delete_data_on_uninstall( 'yes' ), 'Non-canonical opt-in must be rejected.' );

function seed_state( $opt_in ) {
    global $options, $site_transients, $transients;

    $options = array(
        'tpw_square_gateway_delete_data_on_uninstall' => $opt_in,
        'tpw_square_app_id' => 'app-id',
        'tpw_square_access_token' => 'access-token',
        'tpw_square_location_id' => 'location-id',
        'tpw_square_sandbox_mode' => '1',
        'tpw_label_square' => 'Square',
        'tpw_surcharge_square_percent' => '2.50',
        'tpw_surcharge_square_fixed' => '0.30',
        'tpw_square_requested_active' => '1',
        'tpw_square_enabled' => '1',
        'tpw_active_payment_methods' => array( 'square' ),
        'tpw_currency_symbol' => 'GBP',
    );
    $site_transients = array( 'tpw_square_gateway_update_manifest' => array( 'version' => '1.1.4' ) );
    $transients = array( 'tpw_square_gateway_core_missing_notice' => 1 );
}

function assert_preserved_state( $label ) {
    global $options, $site_transients, $transients;

    assert_same( 'app-id', $options['tpw_square_app_id'], $label . ': app ID must survive.' );
    assert_same( 'access-token', $options['tpw_square_access_token'], $label . ': access token must survive.' );
    assert_same( 'location-id', $options['tpw_square_location_id'], $label . ': location ID must survive.' );
    assert_same( '1', $options['tpw_square_sandbox_mode'], $label . ': sandbox mode must survive.' );
    assert_same( 'Square', $options['tpw_label_square'], $label . ': label must survive.' );
    assert_same( '2.50', $options['tpw_surcharge_square_percent'], $label . ': percentage surcharge must survive.' );
    assert_same( '0.30', $options['tpw_surcharge_square_fixed'], $label . ': fixed surcharge must survive.' );
    assert_same( '1', $options['tpw_square_requested_active'], $label . ': Club requested state must survive.' );
    assert_same( '1', $options['tpw_square_enabled'], $label . ': Club enabled state must survive.' );
    assert_same( array( 'square' ), $options['tpw_active_payment_methods'], $label . ': shared active methods must survive.' );
    assert_same( 'GBP', $options['tpw_currency_symbol'], $label . ': shared currency must survive.' );
    assert_same( true, isset( $site_transients['tpw_square_gateway_update_manifest'] ), $label . ': updater cache must survive.' );
    assert_same( true, isset( $transients['tpw_square_gateway_core_missing_notice'] ), $label . ': notice transient must survive.' );
}

foreach ( array( null, '0', 'yes', 1 ) as $invalid_value ) {
    seed_state( $invalid_value );
    assert_same( false, TPW_Square_Gateway_Lifecycle::uninstall(), 'Only exact string opt-in may clean up.' );
    assert_preserved_state( 'Invalid opt-in' );
}

seed_state( '1' );
assert_same( true, TPW_Square_Gateway_Lifecycle::uninstall(), 'Exact opt-in must clean up.' );
assert_same( false, isset( $options['tpw_square_gateway_delete_data_on_uninstall'] ), 'Consent option must be deleted.' );
assert_same( false, isset( $site_transients['tpw_square_gateway_update_manifest'] ), 'Updater cache must be deleted.' );
assert_same( false, isset( $transients['tpw_square_gateway_core_missing_notice'] ), 'Notice transient must be deleted.' );

assert_same( 'app-id', $options['tpw_square_app_id'], 'Opted-in cleanup must preserve compatibility options.' );
assert_same( '1', $options['tpw_square_requested_active'], 'Opted-in cleanup must preserve Club state.' );
assert_same( '1', $options['tpw_square_enabled'], 'Opted-in cleanup must preserve Club state.' );
assert_same( array( 'square' ), $options['tpw_active_payment_methods'], 'Opted-in cleanup must preserve shared state.' );

$loader_source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-loader.php' );
$updater_source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-updater.php' );
$lifecycle_source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-lifecycle.php' );

assert_same( false, false !== strpos( $loader_source, 'delete_option(' ), 'Activation and deactivation must not delete durable options.' );
assert_same( false, false !== strpos( $updater_source, 'TPW_Square_Gateway_Lifecycle::uninstall' ), 'Plugin updates must not invoke uninstall cleanup.' );
assert_same( false, false !== strpos( $lifecycle_source, 'wp_remote_' ), 'Uninstall must not invoke a remote Square API operation.' );

echo "square gateway lifecycle tests passed\n";