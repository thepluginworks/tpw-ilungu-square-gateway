<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Lifecycle {

    public const DELETE_DATA_OPTION = 'tpw_square_gateway_delete_data_on_uninstall';

    public static function is_delete_data_on_uninstall_enabled(): bool {
        return '1' === get_option( self::DELETE_DATA_OPTION, '0' );
    }

    public static function sanitize_delete_data_on_uninstall( $value ): string {
        return '1' === $value ? '1' : '0';
    }

    public static function uninstall(): bool {
        if ( ! self::is_delete_data_on_uninstall_enabled() ) {
            return false;
        }

        delete_site_transient( TPW_Square_Gateway_Updater::CACHE_KEY );
        delete_transient( TPW_Square_Gateway_Admin::CORE_MISSING_NOTICE_TRANSIENT );
        delete_option( self::DELETE_DATA_OPTION );

        return true;
    }
}