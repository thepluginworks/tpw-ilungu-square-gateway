<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Legacy_Bridge {

    /**
     * @var bool
     */
    protected static $booted = false;

    public static function bootstrap(): void {
        if ( self::$booted ) {
            return;
        }

        if ( class_exists( self::target_class(), false ) ) {
            self::$booted = true;
            return;
        }

        class_alias( 'TPW_Square_Gateway_Legacy_Bridge_Proxy', self::target_class() );

        self::$booted = true;
    }

    public static function is_booted(): bool {
        return self::$booted;
    }

    public static function target_class(): string {
        return 'TPW_Square_Gateway';
    }

    public static function implementation_class(): string {
        return 'TPW_Core_Square_Gateway_Legacy';
    }

    public static function load_core_implementation(): void {
        $path = WP_PLUGIN_DIR . '/tpw-core/modules/payments/gateways/class-tpw-square-gateway.php';

        if ( file_exists( $path ) && ! class_exists( self::implementation_class(), false ) ) {
            require_once $path;
        }
    }
}

class TPW_Square_Gateway_Legacy_Bridge_Proxy {

    public static function is_enabled(): bool {
        return TPW_Square_Gateway_Direct_HTTP_Client::is_enabled();
    }

    public static function label(): string {
        return TPW_Square_Gateway_Direct_HTTP_Client::label();
    }

    public static function process_payment( array $args ) {
        return TPW_Square_Gateway_Direct_HTTP_Client::process_payment( $args );
    }

    public static function __callStatic( string $name, array $arguments ) {
        if ( is_callable( [ 'TPW_Square_Gateway_Direct_HTTP_Client', $name ] ) ) {
            return call_user_func_array( [ 'TPW_Square_Gateway_Direct_HTTP_Client', $name ], $arguments );
        }

        TPW_Square_Gateway_Legacy_Bridge::load_core_implementation();

        if ( ! is_callable( [ TPW_Square_Gateway_Legacy_Bridge::implementation_class(), $name ] ) ) {
            throw new BadMethodCallException( 'Unknown method ' . $name );
        }

        return call_user_func_array( [ TPW_Square_Gateway_Legacy_Bridge::implementation_class(), $name ], $arguments );
    }
}