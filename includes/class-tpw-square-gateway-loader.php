<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TPW_Square_Gateway_Loader {

    /**
     * @var TPW_Square_Gateway_Loader|null
     */
    protected static $instance = null;

    /**
     * @var TPW_Square_Gateway_Admin
     */
    protected $admin;

    /**
     * @var TPW_Square_Gateway_Settings|null
     */
    protected $settings;

    /**
     * @var TPW_Square_Gateway_Core_Integration|null
     */
    protected $core_integration;

    /**
     * @var TPW_Square_Gateway_Compatibility_Loader|null
     */
    protected $compatibility_loader;

    /**
     * @var TPW_Square_Gateway_Webhook_Controller|null
     */
    protected $webhook_controller;

    public static function bootstrap(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->init();
        }

        return self::$instance;
    }

    public static function activate(): void {
        if ( ! self::is_core_available() ) {
            TPW_Square_Gateway_Admin::mark_core_missing_notice();
        }
    }

    public static function deactivate(): void {
        delete_transient( TPW_Square_Gateway_Admin::CORE_MISSING_NOTICE_TRANSIENT );
    }

    public static function is_core_available(): bool {
        if ( function_exists( 'tpw_core_loaded_marker' ) ) {
            return true;
        }

        return defined( 'TPW_CORE_VERSION' );
    }

    protected function init(): void {
        $core_available = self::is_core_available();

        $this->admin = new TPW_Square_Gateway_Admin( $core_available );
        $this->admin->register_hooks();

        if ( ! $core_available ) {
            return;
        }

        $this->settings = new TPW_Square_Gateway_Settings();
        $this->settings->register_hooks();

        $this->core_integration = new TPW_Square_Gateway_Core_Integration();
        $this->core_integration->register_hooks();

        $this->compatibility_loader = new TPW_Square_Gateway_Compatibility_Loader();
        $this->compatibility_loader->register_hooks();

        $this->webhook_controller = new TPW_Square_Gateway_Webhook_Controller();
        $this->webhook_controller->register_hooks();
    }
}