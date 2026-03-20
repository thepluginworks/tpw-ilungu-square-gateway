<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Direct_HTTP_Client {

    private const API_VERSION = '2026-01-22';
    private const SANDBOX_API_BASE = 'https://connect.squareupsandbox.com';
    private const PRODUCTION_API_BASE = 'https://connect.squareup.com';

    public static function is_enabled(): bool {
        $methods = get_option( 'tpw_active_payment_methods', [] );

        return in_array( 'square', $methods, true );
    }

    public static function label(): string {
        return 'Pay by Card (via Square)';
    }

    public static function process_payment( array $args ) {
        $access_token = get_option( 'tpw_square_access_token' );
        $is_sandbox   = '1' === (string) get_option( 'tpw_square_sandbox_mode' );
        $location_id  = get_option( 'tpw_square_location_id' );

        error_log( '[TPW DEBUG] Square environment: ' . ( $is_sandbox ? 'sandbox' : 'production' ) );
        error_log( '[TPW DEBUG] location_id: ' . $location_id );
        error_log( '[TPW DEBUG] access_token passed to direct HTTP client: ' . substr( (string) ( $access_token ?? 'null' ), 0, 10 ) . '...' );

        if ( empty( $access_token ) ) {
            error_log( '[TPW ERROR] Square access token is missing.' );

            return new WP_Error(
                'square_payment_error',
                'Payment service error. Please try again.',
                [ 'detail' => 'Square access token is missing.' ]
            );
        }

        $request_body = self::build_request_body( $args, (string) $location_id );

        error_log( '[TPW DEBUG] args: ' . print_r( $args, true ) );
        error_log( '[TPW DEBUG] Nonce: ' . ( $args['nonce'] ?? 'null' ) );
        error_log( '[TPW DEBUG] Token Length: ' . strlen( (string) $access_token ) );
        error_log( '[TPW DEBUG] Location ID: ' . $location_id );
        error_log( '[TPW DEBUG] Payment Request Body: ' . print_r( $request_body, true ) );

        $response = wp_remote_post(
            self::get_api_url( $is_sandbox ),
            [
                'method'  => 'POST',
                'timeout' => 45,
                'headers' => [
                    'Authorization'  => 'Bearer ' . $access_token,
                    'Content-Type'   => 'application/json',
                    'Accept'         => 'application/json',
                    'Square-Version' => self::API_VERSION,
                ],
                'body'    => wp_json_encode( $request_body ),
            ]
        );

        if ( is_wp_error( $response ) ) {
            error_log( '[TPW ERROR] HTTP payment error: ' . $response->get_error_message() );

            return new WP_Error(
                'square_payment_error',
                'Payment service error. Please try again.',
                [ 'detail' => $response->get_error_message() ]
            );
        }

        $status_code = (int) wp_remote_retrieve_response_code( $response );
        $raw_body    = wp_remote_retrieve_body( $response );
        $payload     = json_decode( $raw_body, true );

        if ( ! is_array( $payload ) ) {
            error_log( '[TPW ERROR] Invalid Square response body: ' . $raw_body );

            return new WP_Error(
                'square_payment_error',
                'Unexpected payment error. Please try again.',
                [
                    'status' => $status_code,
                    'detail' => 'Invalid JSON response from Square.',
                ]
            );
        }

        if ( $status_code >= 400 || ! empty( $payload['errors'] ) ) {
            $normalized = self::normalize_square_errors( $payload['errors'] ?? [] );
            $friendly   = self::friendly_message_for_errors( $normalized );

            error_log( '[TPW ERROR] Square API error: status=' . $status_code . ' codes=' . implode( ',', array_map( static function ( $item ) {
                return $item['code'];
            }, $normalized ) ) . ' category=' . ( $normalized[0]['category'] ?? '' ) );

            return new WP_Error(
                self::wp_error_code_for_errors( $normalized ),
                $friendly,
                [
                    'status'            => $status_code,
                    'category'          => $normalized[0]['category'] ?? null,
                    'codes'             => array_map( static function ( $item ) {
                        return $item['code'];
                    }, $normalized ),
                    'details'           => array_map( static function ( $item ) {
                        return $item['detail'];
                    }, $normalized ),
                    'require_new_nonce' => self::require_new_nonce( $normalized ),
                    'raw_errors'        => $normalized,
                ]
            );
        }

        if ( empty( $payload['payment'] ) || ! is_array( $payload['payment'] ) ) {
            error_log( '[TPW ERROR] Square response missing payment payload: ' . print_r( $payload, true ) );

            return new WP_Error(
                'square_payment_error',
                'Unexpected payment error. Please try again.',
                [
                    'status' => $status_code,
                    'detail' => 'Square response did not include a payment payload.',
                ]
            );
        }

        return new TPW_Square_Gateway_Create_Payment_Response( $payload, $status_code );
    }

    private static function build_request_body( array $args, string $location_id ): array {
        if ( class_exists( 'TPW_Core_Payments' ) ) {
            $calc = TPW_Core_Payments::tpw_core_calculate_payable_total( (float) ( $args['amount'] ?? 0 ), 'square' );
            $charge_amount = (float) $calc['total_with_surcharge'];
        } else {
            $charge_amount = (float) ( $args['amount'] ?? 0 );
        }

        return [
            'source_id'       => (string) ( $args['nonce'] ?? '' ),
            'idempotency_key' => self::generate_idempotency_key(),
            'amount_money'    => [
                'amount'   => (int) round( $charge_amount * 100 ),
                'currency' => 'GBP',
            ],
            'autocomplete'    => true,
            'location_id'     => $location_id,
            'reference_id'    => ! empty( $args['reference_id'] )
                ? (string) $args['reference_id']
                : 'RSVP-' . (string) ( $args['submission_id'] ?? '' ),
            'note'            => self::build_note( $args ),
        ];
    }

    private static function build_note( array $args ): string {
        $note_parts = [];

        if ( ! empty( $args['member_name'] ) ) {
            $note_parts[] = 'Member: ' . $args['member_name'];
        }

        if ( ! empty( $args['payment_id'] ) ) {
            $note_parts[] = '| TPW Payment ID: ' . $args['payment_id'];
        }

        $note_parts[] = '— RSVP payment';

        return trim( implode( ' ', $note_parts ) );
    }

    private static function generate_idempotency_key(): string {
        if ( function_exists( 'wp_generate_uuid4' ) ) {
            return 'sq_' . wp_generate_uuid4();
        }

        return uniqid( 'sq_' );
    }

    private static function get_api_url( bool $is_sandbox ): string {
        $base = $is_sandbox ? self::SANDBOX_API_BASE : self::PRODUCTION_API_BASE;

        return $base . '/v2/payments';
    }

    private static function normalize_square_errors( array $errors ): array {
        $normalized = [];

        foreach ( $errors as $error ) {
            if ( ! is_array( $error ) ) {
                continue;
            }

            $normalized[] = [
                'category' => isset( $error['category'] ) ? strtoupper( (string) $error['category'] ) : 'API_ERROR',
                'code'     => isset( $error['code'] ) ? strtoupper( (string) $error['code'] ) : 'UNKNOWN',
                'detail'   => isset( $error['detail'] ) ? (string) $error['detail'] : null,
                'field'    => isset( $error['field'] ) ? (string) $error['field'] : null,
            ];
        }

        if ( empty( $normalized ) ) {
            $normalized[] = [
                'category' => 'API_ERROR',
                'code'     => 'UNKNOWN',
                'detail'   => null,
                'field'    => null,
            ];
        }

        return $normalized;
    }

    private static function friendly_message_for_errors( array $errors ): string {
        $codes    = array_map( static function ( $error ) {
            return strtoupper( (string) ( $error['code'] ?? '' ) );
        }, $errors );
        $primary  = $codes[0] ?? '';
        $category = strtoupper( (string) ( $errors[0]['category'] ?? '' ) );

        if ( 'API_ERROR' === $category ) {
            return 'There was a temporary issue processing your payment. Please try again.';
        }

        switch ( $primary ) {
            case 'GENERIC_DECLINE':
            case 'CARD_DECLINED':
                return 'Your bank has declined this payment. Please try another card or contact your bank.';
            case 'INSUFFICIENT_FUNDS':
                return 'This card does not have enough funds to complete the payment. Please try another card.';
            case 'CARD_DECLINED_VERIFICATION_REQUIRED':
                return 'Your bank requires additional security verification for this payment. Please use a different card or contact your bank.';
            case 'CVV_FAILURE':
            case 'VERIFY_CVV_FAILURE':
                return 'The security code (CVV) was incorrect. Please check the number and try again.';
            case 'AVS_FAILURE':
            case 'ADDRESS_VERIFICATION_FAILURE':
            case 'VERIFY_AVS_FAILURE':
                return 'The billing postcode or address does not match the card.';
            case 'CARD_EXPIRED':
                return 'This card has expired. Please try a different card.';
            case 'INVALID_CARD':
            case 'INVALID_CARD_DATA':
            case 'INVALID_EXPIRATION':
            case 'INVALID_EXPIRATION_DATE':
            case 'BAD_EXPIRATION':
            case 'EXPIRATION_FAILURE':
                return 'The card details entered are not valid. Please check and try again.';
            case 'CARD_NOT_SUPPORTED':
            case 'UNSUPPORTED_CARD_BRAND':
                return 'This card type is not supported. Please try a different card.';
            case 'CARD_TOKEN_USED':
            case 'SOURCE_USED':
            case 'CARD_TOKEN_EXPIRED':
            case 'SOURCE_EXPIRED':
                return 'Your payment session expired. Please re-enter your card details.';
            case 'UNAUTHORIZED':
            case 'ACCESS_TOKEN_EXPIRED':
            case 'RATE_LIMITED':
                return 'There was a temporary issue processing your payment. Please try again.';
            default:
                $detail = $errors[0]['detail'] ?? null;

                return $detail ? (string) $detail : 'We couldn’t process your payment. Please try again or use a different card.';
        }
    }

    private static function wp_error_code_for_errors( array $errors ): string {
        $category = strtoupper( (string) ( $errors[0]['category'] ?? '' ) );

        switch ( $category ) {
            case 'PAYMENT_METHOD_ERROR':
                return 'square_payment_declined';
            case 'INVALID_REQUEST_ERROR':
                return 'square_invalid_request';
            case 'AUTHENTICATION_ERROR':
                return 'square_auth_error';
            case 'RATE_LIMIT_ERROR':
                return 'square_rate_limited';
            default:
                return 'square_payment_error';
        }
    }

    private static function require_new_nonce( array $errors ): bool {
        foreach ( $errors as $error ) {
            $code = strtoupper( (string) ( $error['code'] ?? '' ) );

            if ( in_array( $code, [ 'CARD_TOKEN_USED', 'SOURCE_USED', 'CARD_TOKEN_EXPIRED', 'SOURCE_EXPIRED' ], true ) ) {
                return true;
            }
        }

        return false;
    }
}

class TPW_Square_Gateway_Create_Payment_Response implements JsonSerializable {

    /**
     * @var mixed
     */
    public $payment;

    /**
     * @var array<int,mixed>
     */
    public $errors;

    /**
     * @var array<string,mixed>
     */
    protected $payload;

    /**
     * @var int
     */
    protected $status_code;

    public function __construct( array $payload, int $status_code ) {
        $this->payload      = $payload;
        $this->status_code  = $status_code;
        $this->payment      = TPW_Square_Gateway_Data_Object::wrap( $payload['payment'] ?? null );
        $this->errors       = is_array( $payload['errors'] ?? null ) ? $payload['errors'] : [];
    }

    public function isSuccess(): bool {
        return $this->status_code >= 200
            && $this->status_code < 300
            && ! empty( $this->payload['payment']['id'] )
            && empty( $this->payload['errors'] );
    }

    public function getPayment() {
        return $this->payment;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function getStatusCode(): int {
        return $this->status_code;
    }

    public function getBody(): array {
        return $this->payload;
    }

    public function getResult() {
        return $this->getPayment();
    }

    public function toArray(): array {
        return $this->payload;
    }

    public function jsonSerialize(): array {
        return $this->payload;
    }

    public function __get( string $name ) {
        return TPW_Square_Gateway_Data_Object::wrap( $this->payload[ $name ] ?? null );
    }

    public function __isset( string $name ): bool {
        return array_key_exists( $name, $this->payload );
    }

    public function __call( string $name, array $arguments ) {
        if ( 0 === strpos( $name, 'get' ) ) {
            $suffix = substr( $name, 3 );

            if ( '' !== $suffix ) {
                $key = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $suffix ) );

                if ( array_key_exists( $key, $this->payload ) ) {
                    return TPW_Square_Gateway_Data_Object::wrap( $this->payload[ $key ] );
                }
            }
        }

        throw new BadMethodCallException( 'Unknown method ' . $name );
    }
}

class TPW_Square_Gateway_Data_Object implements JsonSerializable {

    /**
     * @var array<string,mixed>
     */
    protected $data;

    public function __construct( array $data ) {
        foreach ( $data as $key => $value ) {
            $this->data[ $key ] = self::wrap( $value );
        }
    }

    public static function wrap( $value ) {
        if ( is_array( $value ) ) {
            if ( self::is_list( $value ) ) {
                return array_map( [ __CLASS__, 'wrap' ], $value );
            }

            return new self( $value );
        }

        return $value;
    }

    public function toArray(): array {
        return $this->unwrap( $this->data );
    }

    public function jsonSerialize(): array {
        return $this->toArray();
    }

    public function __get( string $name ) {
        return self::wrap( $this->data[ $name ] ?? null );
    }

    public function __isset( string $name ): bool {
        return array_key_exists( $name, $this->data );
    }

    public function __call( string $name, array $arguments ) {
        if ( 0 === strpos( $name, 'get' ) ) {
            $suffix = substr( $name, 3 );

            if ( '' !== $suffix ) {
                $key = strtolower( preg_replace( '/(?<!^)[A-Z]/', '_$0', $suffix ) );

                if ( array_key_exists( $key, $this->data ) ) {
                    return self::wrap( $this->data[ $key ] );
                }
            }
        }

        throw new BadMethodCallException( 'Unknown method ' . $name );
    }

    public function getId() {
        return $this->data['id'] ?? null;
    }

    public function getStatus() {
        return TPW_Square_Gateway_Enum_Value::wrap( $this->data['status'] ?? null );
    }

    public function getReferenceId() {
        return $this->data['reference_id'] ?? null;
    }

    public function getLocationId() {
        return $this->data['location_id'] ?? null;
    }

    public function getOrderId() {
        return $this->data['order_id'] ?? null;
    }

    public function getReceiptUrl() {
        return $this->data['receipt_url'] ?? null;
    }

    public function getNote() {
        return $this->data['note'] ?? null;
    }

    public function getSourceType() {
        return TPW_Square_Gateway_Enum_Value::wrap( $this->data['source_type'] ?? null );
    }

    public function getAmountMoney() {
        return $this->data['amount_money'] ?? null;
    }

    public function getApprovedMoney() {
        return $this->data['approved_money'] ?? null;
    }

    public function getTotalMoney() {
        return $this->data['total_money'] ?? null;
    }

    public function getCardDetails() {
        return $this->data['card_details'] ?? null;
    }

    public function getCreatedAt() {
        return $this->data['created_at'] ?? null;
    }

    public function getUpdatedAt() {
        return $this->data['updated_at'] ?? null;
    }

    private function unwrap( $value ) {
        if ( $value instanceof self ) {
            return $value->toArray();
        }

        if ( $value instanceof TPW_Square_Gateway_Enum_Value ) {
            return $value->value;
        }

        if ( is_array( $value ) ) {
            foreach ( $value as $key => $item ) {
                $value[ $key ] = $this->unwrap( $item );
            }
        }

        return $value;
    }

    private static function is_list( array $value ): bool {
        if ( function_exists( 'array_is_list' ) ) {
            return array_is_list( $value );
        }

        return array_keys( $value ) === range( 0, count( $value ) - 1 );
    }
}

class TPW_Square_Gateway_Enum_Value {

    /**
     * @var string|null
     */
    public $value;

    public function __construct( ?string $value ) {
        $this->value = $value;
    }

    public static function wrap( $value ) {
        if ( null === $value || $value instanceof self ) {
            return $value;
        }

        return new self( is_scalar( $value ) ? (string) $value : null );
    }

    public function __toString(): string {
        return (string) $this->value;
    }
}

if ( ! class_exists( 'Square\\Types\\CreatePaymentResponse', false ) ) {
    class_alias( 'TPW_Square_Gateway_Create_Payment_Response', 'Square\\Types\\CreatePaymentResponse' );
}

if ( ! class_exists( 'Square\\Types\\Payment', false ) ) {
    class_alias( 'TPW_Square_Gateway_Data_Object', 'Square\\Types\\Payment' );
}

if ( ! class_exists( 'Square\\Types\\Money', false ) ) {
    class_alias( 'TPW_Square_Gateway_Data_Object', 'Square\\Types\\Money' );
}

if ( ! class_exists( 'Square\\Types\\CardPaymentDetails', false ) ) {
    class_alias( 'TPW_Square_Gateway_Data_Object', 'Square\\Types\\CardPaymentDetails' );
}

if ( ! class_exists( 'Square\\Types\\Card', false ) ) {
    class_alias( 'TPW_Square_Gateway_Data_Object', 'Square\\Types\\Card' );
}