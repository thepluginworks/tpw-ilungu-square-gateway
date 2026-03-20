<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( function_exists( 'tpw_core_render_settings_header' ) ) {
    tpw_core_render_settings_header( 'Square Payment Settings' );
}

$currency_symbol = '£';
if ( function_exists( 'tpw_core_get_currency_symbol' ) ) {
    $currency_symbol = tpw_core_get_currency_symbol();
} else {
    $currency_symbol = get_option( 'tpw_currency_symbol', '£' );
}

global $wpdb;
$payment_methods_table = $wpdb->prefix . 'tpw_payment_methods';
$current_label = $wpdb->get_var(
    $wpdb->prepare( "SELECT name FROM $payment_methods_table WHERE slug = %s", 'square' )
);

if ( ! is_string( $current_label ) || '' == $current_label ) {
    $current_label = 'Pay by Card (via Square)';
}
?>
<div class="tpw-admin-ui"><div class="wrap">
    <p><a href="<?php echo esc_url( tpw_core_get_payment_methods_settings_url() ); ?>" class="button"><?php esc_html_e( 'Back to Payment Methods', 'tpw-square-gateway' ); ?></a></p>
    <?php if ( ! empty( $_GET['settings-updated'] ) ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Square settings updated successfully.', 'tpw-square-gateway' ); ?></p></div>
    <?php endif; ?>
    <form method="post" action="options.php">
        <?php settings_fields( 'tpw_payment_settings' ); ?>
        <?php do_settings_sections( 'tpw_payment_settings' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="tpw_label_square"><?php esc_html_e( 'Label', 'tpw-square-gateway' ); ?></label></th>
                <td>
                    <input type="text" name="tpw_label_square" id="tpw_label_square" value="<?php echo esc_attr( $current_label ); ?>" class="regular-text" />
                    <p class="description"><?php esc_html_e( 'Shown on checkout.', 'tpw-square-gateway' ); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="tpw_square_app_id"><?php esc_html_e( 'Application ID', 'tpw-square-gateway' ); ?></label></th>
                <td><input type="text" name="tpw_square_app_id" id="tpw_square_app_id" value="<?php echo esc_attr( get_option( 'tpw_square_app_id' ) ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="tpw_square_access_token"><?php esc_html_e( 'Access Token', 'tpw-square-gateway' ); ?></label></th>
                <td><input type="password" name="tpw_square_access_token" id="tpw_square_access_token" value="<?php echo esc_attr( get_option( 'tpw_square_access_token' ) ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="tpw_square_location_id"><?php esc_html_e( 'Location ID', 'tpw-square-gateway' ); ?></label></th>
                <td><input type="text" name="tpw_square_location_id" id="tpw_square_location_id" value="<?php echo esc_attr( get_option( 'tpw_square_location_id' ) ); ?>" class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="tpw_square_sandbox_mode"><?php esc_html_e( 'Sandbox Mode', 'tpw-square-gateway' ); ?></label></th>
                <td>
                    <input type="checkbox" name="tpw_square_sandbox_mode" id="tpw_square_sandbox_mode" value="1" <?php checked( '1', (string) get_option( 'tpw_square_sandbox_mode' ), true ); ?> />
                    <label for="tpw_square_sandbox_mode"><?php esc_html_e( 'Use Square Sandbox for testing', 'tpw-square-gateway' ); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="tpw_surcharge_square_percent"><?php esc_html_e( 'Surcharge (%)', 'tpw-square-gateway' ); ?></label></th>
                <td>
                    <input type="number" name="tpw_surcharge_square_percent" id="tpw_surcharge_square_percent" step="0.01" min="0" value="<?php echo esc_attr( get_option( 'tpw_surcharge_square_percent', 0 ) ); ?>" />
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="tpw_surcharge_square_fixed"><?php printf( esc_html__( 'Fixed (%s)', 'tpw-square-gateway' ), esc_html( $currency_symbol ) ); ?></label></th>
                <td>
                    <input type="number" name="tpw_surcharge_square_fixed" id="tpw_surcharge_square_fixed" step="0.01" min="0" value="<?php echo esc_attr( get_option( 'tpw_surcharge_square_fixed', 0 ) ); ?>" />
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div></div>