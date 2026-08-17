<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Admin {

    public const CORE_MISSING_NOTICE_TRANSIENT = 'tpw_square_gateway_core_missing_notice';
    public const SQUARE_SETTINGS_PAGE_SLUG = 'tpw-square-settings';

    /**
     * @var bool
     */
    protected $core_available;

    public function __construct( bool $core_available ) {
        $this->core_available = $core_available;
    }

    public function register_hooks(): void {
        add_action( 'admin_notices', [ $this, 'maybe_render_core_notice' ] );
        add_action( 'network_admin_notices', [ $this, 'maybe_render_core_notice' ] );
        add_action( 'tpw_core/square_settings_route', [ $this, 'render_settings_route' ], 10, 1 );
    }

    public static function mark_core_missing_notice(): void {
        set_transient( self::CORE_MISSING_NOTICE_TRANSIENT, 1, MINUTE_IN_SECONDS * 10 );
    }

    public function maybe_render_core_notice(): void {
        if ( ! current_user_can( 'activate_plugins' ) ) {
            return;
        }

        if ( $this->core_available ) {
            delete_transient( self::CORE_MISSING_NOTICE_TRANSIENT );
            return;
        }

        echo '<div class="notice notice-warning"><p>';
        echo esc_html__( 'iLungu Square Gateway is installed, but iLungu Club is not active. The add-on remains inactive until iLungu Club is available.', 'tpw-square-gateway' );
        echo '</p></div>';
    }

    public function owns_settings(): bool {
        return $this->core_available;
    }

    public function register_settings_placeholder(): void {
        // Settings ownership will be implemented in a later phase.
    }

    public function render_settings_route( $route_slug ): void {
        if ( ! $this->can_render_settings_route( $route_slug ) ) {
            return;
        }

        include TPW_SQUARE_GATEWAY_PATH . 'views/square-settings-page.php';
    }

    protected function can_render_settings_route( $route_slug ): bool {
        if ( ! $this->core_available ) {
            return false;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        return self::SQUARE_SETTINGS_PAGE_SLUG === (string) $route_slug;
    }
}