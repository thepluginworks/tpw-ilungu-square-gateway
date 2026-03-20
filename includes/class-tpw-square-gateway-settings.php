<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Settings {

    public function register_hooks(): void {
        add_action( 'admin_init', [ $this, 'register_settings' ], 20 );
    }

    public function register_settings(): void {
        register_setting( 'tpw_payment_settings', 'tpw_square_app_id' );
        register_setting( 'tpw_payment_settings', 'tpw_square_access_token' );
        register_setting( 'tpw_payment_settings', 'tpw_square_location_id' );
        register_setting( 'tpw_payment_settings', 'tpw_square_sandbox_mode' );
        register_setting(
            'tpw_payment_settings',
            'tpw_label_square',
            [
                'sanitize_callback' => [ $this, 'save_method_label_square' ],
            ]
        );
        register_setting(
            'tpw_payment_settings',
            'tpw_surcharge_square_percent',
            [
                'sanitize_callback' => [ $this, 'sanitize_surcharge_value' ],
            ]
        );
        register_setting(
            'tpw_payment_settings',
            'tpw_surcharge_square_fixed',
            [
                'sanitize_callback' => [ $this, 'sanitize_surcharge_value' ],
            ]
        );
    }

    public function sanitize_surcharge_value( $value ) {
        $normalized = (float) $value;

        if ( $normalized < 0 ) {
            $normalized = 0;
        }

        return round( $normalized, 2 );
    }

    public function save_method_label_square( $value ): string {
        $label = sanitize_text_field( (string) $value );

        global $wpdb;
        $table = $wpdb->prefix . 'tpw_payment_methods';
        $wpdb->update( $table, [ 'name' => $label ], [ 'slug' => 'square' ] );

        return $label;
    }
}