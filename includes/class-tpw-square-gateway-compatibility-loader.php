<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Compatibility_Loader {

    public function register_hooks(): void {
        add_action( 'plugins_loaded', [ $this, 'maybe_boot_legacy_bridge' ], 30 );
    }

    public function should_claim_legacy_surface(): bool {
        if ( function_exists( 'tpw_core_get_square_gateway_legacy_owner' ) ) {
            return 'addon' === tpw_core_get_square_gateway_legacy_owner();
        }

        return false;
    }

    public function get_legacy_owner_strategy(): string {
        return $this->should_claim_legacy_surface() ? 'addon' : 'core';
    }

    public function get_bridge_target_class(): string {
        return 'TPW_Square_Gateway';
    }

    public function can_boot_bridge(): bool {
        if ( ! $this->should_claim_legacy_surface() ) {
            return false;
        }

        if ( class_exists( $this->get_bridge_target_class(), false ) ) {
            return false;
        }

        return class_exists( 'TPW_Square_Gateway_Legacy_Bridge', false );
    }

    public function maybe_boot_legacy_bridge(): void {
        if ( ! $this->can_boot_bridge() ) {
            return;
        }

        TPW_Square_Gateway_Legacy_Bridge::bootstrap();
    }
}