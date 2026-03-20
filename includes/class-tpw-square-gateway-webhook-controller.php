<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Webhook_Controller {

    public function register_hooks(): void {
        // Webhook routes and verification are intentionally deferred.
    }

    public function is_enabled(): bool {
        return false;
    }

    public function get_route_namespace(): string {
        return 'tpw-square-gateway/v1';
    }
}