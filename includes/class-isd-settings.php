<?php

// Función para mostrar la página de configuración
function isd_settings_page_content() {
    if ( isset( $_POST['isd_save_settings'] ) ) {
        check_admin_referer( 'isd_save_settings_nonce', 'isd_save_settings_nonce_field' );
        update_option( 'isd_api_url', sanitize_text_field( $_POST['isd_api_url'] ) );
        update_option( 'isd_api_token', sanitize_text_field( $_POST['isd_api_token'] ) );
        update_option( 'isd_interval', sanitize_text_field( $_POST['isd_interval'] ) );
        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }

    $api_url = get_option( 'isd_api_url', '' );
    $api_token = get_option( 'isd_api_token', '' );
    $interval = get_option( 'isd_interval', 0 );
    ?>
    <div class="wrap">
        <h1>Integrator Simon.dev Settings</h1>
        <form method="post" action="">
            <?php wp_nonce_field( 'isd_save_settings_nonce', 'isd_save_settings_nonce_field' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API URL</th>
                    <td><input type="text" name="isd_api_url" value="<?php echo esc_attr( $api_url ); ?>" size="50" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">API Token</th>
                    <td><input type="text" name="isd_api_token" value="<?php echo esc_attr( $api_token ); ?>" size="50" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Intervalo de sincronización automática</th>
                    <td><input type="number" name="isd_interval" value="<?php echo esc_attr( $interval ); ?>" /></td>
                </tr>
            </table>
            <?php submit_button( 'Save Settings', 'primary', 'isd_save_settings' ); ?>
        </form>
    </div>
    <?php
}
