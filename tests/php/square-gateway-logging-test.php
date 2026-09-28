<?php

$source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-direct-http-client.php' );

if ( ! is_string( $source ) ) {
    fwrite( STDERR, "Unable to read direct HTTP client source.\n" );
    exit( 1 );
}

foreach ( array(
    'error_log( \'[TPW DEBUG] Square environment:',
    'error_log( \'[TPW DEBUG] location_id:',
    'access_token passed to direct HTTP client',
    'error_log( \'[TPW DEBUG] args:',
    'error_log( \'[TPW DEBUG] Nonce:',
    'error_log( \'[TPW DEBUG] Token Length:',
    'error_log( \'[TPW DEBUG] Payment Request Body:',
    'Invalid Square response body:',
    'Square response missing payment payload:',
) as $unsafe_log_fragment ) {
    if ( false !== strpos( $source, $unsafe_log_fragment ) ) {
        fwrite( STDERR, "Unsafe payment diagnostic remains: {$unsafe_log_fragment}\n" );
        exit( 1 );
    }
}

if ( false === strpos( $source, "Square payment request failed: transport_error." ) ) {
    fwrite( STDERR, "Expected sanitized transport diagnostic is missing.\n" );
    exit( 1 );
}

echo "square gateway logging tests passed\n";