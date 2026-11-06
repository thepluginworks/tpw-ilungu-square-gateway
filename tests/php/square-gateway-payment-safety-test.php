<?php

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_PLUGIN_DIR', __DIR__ );

$options = array(
    'tpw_square_access_token' => 'test-access-token',
    'tpw_square_location_id'  => 'test-location',
    'tpw_square_sandbox_mode' => '1',
);
$post_requests = array();
$get_requests  = array();
$post_queue    = array();
$get_queue     = array();

class WP_Error {
    private $code;
    private $message;
    private $data;

    public function __construct( $code, $message, $data = array() ) {
        $this->code    = $code;
        $this->message = $message;
        $this->data    = $data;
    }

    public function get_error_code() {
        return $this->code;
    }

    public function get_error_message() {
        return $this->message;
    }

    public function get_error_data() {
        return $this->data;
    }
}

function get_option( $name, $default = false ) {
    global $options;

    return array_key_exists( $name, $options ) ? $options[ $name ] : $default;
}

function is_wp_error( $value ) {
    return $value instanceof WP_Error;
}

function wp_json_encode( $value ) {
    return json_encode( $value );
}

function wp_generate_uuid4() {
    static $counter = 0;

    $counter++;

    return sprintf( '00000000-0000-4000-8000-%012d', $counter );
}

function wp_remote_post( $url, $args ) {
    global $post_requests, $post_queue;

    $post_requests[] = array(
        'url'  => $url,
        'args' => $args,
    );

    return array_shift( $post_queue );
}

function wp_remote_get( $url, $args ) {
    global $get_requests, $get_queue;

    $get_requests[] = array(
        'url'  => $url,
        'args' => $args,
    );

    return array_shift( $get_queue );
}

function wp_remote_retrieve_response_code( $response ) {
    return $response['status'];
}

function wp_remote_retrieve_body( $response ) {
    return $response['body'];
}

function assert_same( $expected, $actual, $label ) {
    if ( $expected !== $actual ) {
        fwrite( STDERR, $label . "\n" );
        exit( 1 );
    }
}

function payment_response( $status = 'COMPLETED', $id = 'square-payment-1' ) {
    return array(
        'status' => 200,
        'body'   => json_encode(
            array(
                'payment' => array(
                    'id'           => $id,
                    'status'       => $status,
                    'reference_id' => 'safe-reference',
                ),
            )
        ),
    );
}

require dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-direct-http-client.php';
require dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-legacy-bridge.php';

$post_queue[] = payment_response();
$legacy_response = TPW_Square_Gateway_Direct_HTTP_Client::process_payment(
    array(
        'amount' => 12.34,
        'nonce'  => 'test-nonce',
    )
);
assert_same( true, $legacy_response instanceof TPW_Square_Gateway_Create_Payment_Response, 'Legacy payment response type changed.' );
$legacy_body = json_decode( $post_requests[0]['args']['body'], true );
assert_same( 'sq_00000000-0000-4000-8000-000000000001', $legacy_body['idempotency_key'], 'Omitted idempotency key must retain generated-key behavior.' );

$stable_args = array(
    'amount'          => 12.34,
    'nonce'           => 'test-nonce',
    'idempotency_key' => 'tickets-reservation-123',
);
$post_queue[] = payment_response( 'COMPLETED', 'square-payment-stable' );
$post_queue[] = payment_response( 'COMPLETED', 'square-payment-stable' );
$first_retry_response  = TPW_Square_Gateway_Direct_HTTP_Client::process_payment( $stable_args );
$second_retry_response = TPW_Square_Gateway_Direct_HTTP_Client::process_payment( $stable_args );
$first_retry_body      = json_decode( $post_requests[1]['args']['body'], true );
$second_retry_body     = json_decode( $post_requests[2]['args']['body'], true );
assert_same( 'tickets-reservation-123', $first_retry_body['idempotency_key'], 'Caller key was not passed unchanged.' );
assert_same( $first_retry_body, $second_retry_body, 'Same-key retry did not preserve the request body.' );
assert_same( 'square-payment-stable', $first_retry_response->getPayment()->getId(), 'Stable-key payment identity changed.' );
assert_same( 'square-payment-stable', $second_retry_response->getPayment()->getId(), 'Same-key retry did not return the original payment identity.' );

$invalid_key_response = TPW_Square_Gateway_Direct_HTTP_Client::process_payment(
    array(
        'amount'          => 12.34,
        'nonce'           => 'test-nonce',
        'idempotency_key' => '',
    )
);
assert_same( 'square_invalid_request', $invalid_key_response->get_error_code(), 'Invalid idempotency key was not rejected.' );
assert_same( 'failed', $invalid_key_response->get_error_data()['outcome'], 'Invalid idempotency key must be a failed request.' );
assert_same( 3, count( $post_requests ), 'Invalid idempotency key must not reach Square.' );

$post_queue[] = new WP_Error( 'http_request_failed', 'timeout' );
$timeout_response = TPW_Square_Gateway_Direct_HTTP_Client::process_payment( $stable_args );
assert_same( 'square_payment_error', $timeout_response->get_error_code(), 'Transport error code changed.' );
assert_same( 'uncertain', $timeout_response->get_error_data()['outcome'], 'Transport failure must be uncertain.' );
assert_same( true, $timeout_response->get_error_data()['retry_with_same_idempotency_key'], 'Transport failure must require same-key retry.' );

$post_queue[] = array(
    'status' => 200,
    'body'   => '{',
);
$invalid_json_response = TPW_Square_Gateway_Direct_HTTP_Client::process_payment( $stable_args );
assert_same( 'uncertain', $invalid_json_response->get_error_data()['outcome'], 'Invalid payment JSON must be uncertain.' );

$lookup_statuses = array( 'COMPLETED', 'PENDING', 'APPROVED', 'FAILED', 'CANCELED' );
foreach ( $lookup_statuses as $lookup_status ) {
    $get_queue[] = payment_response( $lookup_status, 'square-payment-lookup-' . strtolower( $lookup_status ) );
    $lookup_response = TPW_Square_Gateway_Direct_HTTP_Client::get_payment( 'square-payment-lookup-' . strtolower( $lookup_status ) );
    assert_same( true, $lookup_response instanceof TPW_Square_Gateway_Create_Payment_Response, 'Payment lookup response type changed.' );
    assert_same( $lookup_status, (string) $lookup_response->getPayment()->getStatus(), 'Payment lookup status was not preserved.' );
}
assert_same( 'https://connect.squareupsandbox.com/v2/payments/square-payment-lookup-completed', $get_requests[0]['url'], 'Payment lookup URL is incorrect.' );
assert_same( 'Bearer test-access-token', $get_requests[0]['args']['headers']['Authorization'], 'Payment lookup is not authenticated.' );

$get_queue[] = array(
    'status' => 400,
    'body'   => json_encode(
        array(
            'errors' => array(
                array(
                    'category' => 'INVALID_REQUEST_ERROR',
                    'code'     => 'NOT_FOUND',
                    'detail'   => 'Missing payment.',
                ),
            ),
        )
    ),
);
$not_found_response = TPW_Square_Gateway_Direct_HTTP_Client::get_payment( 'missing-payment' );
assert_same( 'square_invalid_request', $not_found_response->get_error_code(), 'Lookup error code normalization changed.' );
assert_same( 'failed', $not_found_response->get_error_data()['outcome'], 'Known lookup failure must be failed.' );

$get_queue[] = new WP_Error( 'http_request_failed', 'timeout' );
$lookup_timeout = TPW_Square_Gateway_Direct_HTTP_Client::get_payment( 'square-payment-lookup' );
assert_same( 'uncertain', $lookup_timeout->get_error_data()['outcome'], 'Lookup transport failure must be uncertain.' );

TPW_Square_Gateway_Legacy_Bridge::bootstrap();
$get_queue[] = payment_response( 'COMPLETED', 'square-payment-proxy' );
$proxy_response = TPW_Square_Gateway::get_payment( 'square-payment-proxy' );
assert_same( 'square-payment-proxy', $proxy_response->getPayment()->getId(), 'Legacy proxy did not forward get_payment.' );

$client_source = file_get_contents( dirname( __DIR__, 2 ) . '/includes/class-tpw-square-gateway-direct-http-client.php' );
foreach ( array(
    'Square idempotency key:',
    'Payment Request Body:',
    'idempotency_key='
) as $unsafe_log_fragment ) {
    assert_same( false, false !== strpos( $client_source, $unsafe_log_fragment ), 'Sensitive payment diagnostic was added: ' . $unsafe_log_fragment );
}

echo "square gateway payment safety tests passed\n";
