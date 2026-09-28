<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

require_once __DIR__ . '/includes/class-tpw-square-gateway-updater.php';
require_once __DIR__ . '/includes/class-tpw-square-gateway-admin.php';
require_once __DIR__ . '/includes/class-tpw-square-gateway-lifecycle.php';

TPW_Square_Gateway_Lifecycle::uninstall();