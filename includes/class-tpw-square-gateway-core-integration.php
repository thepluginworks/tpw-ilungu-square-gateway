<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Core_Integration {

    public function register_hooks(): void {
        add_filter( 'tpw_core/square_gateway_addon_active', [ $this, 'declare_addon_active' ], 100 );
        add_filter( 'tpw_core/square_settings_route_owner', [ $this, 'claim_square_settings_owner' ], 100 );
        add_filter( 'tpw_core/square_settings_registration_owner', [ $this, 'claim_square_settings_registration_owner' ], 100 );
        add_filter( 'tpw_core/square_gateway_legacy_owner', [ $this, 'claim_legacy_owner' ], 100 );
        add_filter( 'tpw_core/payments_page_config_localized', [ $this, 'enqueue_square_sdk_for_payments_page' ], 20, 2 );
    }

    public function declare_addon_active( $active ): bool {
        return true;
    }

    public function claim_square_settings_owner( $owner ): string {
        return 'addon';
    }

    public function claim_square_settings_registration_owner( $owner ): string {
        return 'addon';
    }

    public function claim_legacy_owner( $owner ): string {
        return 'addon';
    }

    public function enqueue_square_sdk_for_payments_page( array $config, array $context ): array {
        if ( ! $this->should_enqueue_square_sdk( $config ) ) {
            return $config;
        }

        $sdk_url = $this->get_square_sdk_url();

        if ( ! wp_script_is( 'square-web-payments', 'registered' ) ) {
            wp_register_script( 'square-web-payments', $sdk_url, array(), null, true );
        }

        if ( ! wp_script_is( 'square-web-payments', 'enqueued' ) ) {
            wp_enqueue_script( 'square-web-payments' );
        }

        return $config;
    }

    protected function should_enqueue_square_sdk( array $config ): bool {
        return $this->config_contains_square_method( $config ) || ! empty( $config['square'] );
    }

    protected function config_contains_square_method( array $config ): bool {
        if ( empty( $config['activeMethods'] ) || ! is_array( $config['activeMethods'] ) ) {
            return false;
        }

        foreach ( $config['activeMethods'] as $method ) {
            if ( is_object( $method ) && isset( $method->slug ) ) {
                if ( 'square' === sanitize_key( (string) $method->slug ) ) {
                    return true;
                }

                continue;
            }

            if ( is_array( $method ) && isset( $method['slug'] ) ) {
                if ( 'square' === sanitize_key( (string) $method['slug'] ) ) {
                    return true;
                }

                continue;
            }

            if ( is_string( $method ) && 'square' === sanitize_key( $method ) ) {
                return true;
            }
        }

        return false;
    }

    protected function get_square_sdk_url(): string {
        $is_sandbox = '1' === (string) get_option( 'tpw_square_sandbox_mode' );

        return $is_sandbox
            ? 'https://sandbox.web.squarecdn.com/v1/square.js'
            : 'https://web.squarecdn.com/v1/square.js';
    }

    public function get_phase(): string {
        return 'settings-registration-and-legacy-bridge';
    }
}