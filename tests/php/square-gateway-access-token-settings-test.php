<?php

define( 'ABSPATH', __DIR__ . '/' );

$options = [
	'tpw_square_access_token' => 'synthetic-existing-token',
];

function get_option( $name, $default = false ) {
	global $options;

	return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
}

require_once dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-settings.php';

$settings = new TPW_Square_Gateway_Settings();

if ( 'synthetic-existing-token' !== $settings->save_access_token( '' ) ) {
	fwrite( STDERR, "Blank access token did not preserve the stored value.\n" );
	exit( 1 );
}

if ( 'synthetic-replacement-token' !== $settings->save_access_token( ' synthetic-replacement-token ' ) ) {
	fwrite( STDERR, "Replacement access token was not accepted.\n" );
	exit( 1 );
}

$view = file_get_contents( dirname( __DIR__, 2 ) . '/views/square-settings-page.php' );

if ( ! is_string( $view ) || false !== strpos( $view, "get_option( 'tpw_square_access_token' )" ) || false === strpos( $view, 'value=""' ) ) {
	fwrite( STDERR, "Settings view can disclose the stored access token.\n" );
	exit( 1 );
}

echo "square gateway access token settings tests passed\n";
