<?php

// Función para mostrar la página de configuración
function isd_settings_page_content() {
    if ( isset( $_POST['isd_save_settings'] ) ) {
        check_admin_referer( 'isd_save_settings_nonce', 'isd_save_settings_nonce_field' );
        update_option( 'isd_api_url', sanitize_text_field( $_POST['isd_api_url'] ) );
        update_option( 'isd_api_token', sanitize_text_field( $_POST['isd_api_token'] ) );
        update_option( 'isd_interval', sanitize_text_field( $_POST['isd_interval'] ) );

        // Guardar los nuevos valores para automatizaciones
        update_option( 'isd_automate_products', isset( $_POST['isd_automate_products'] ) ? 1 : 0 );
        update_option( 'isd_automate_clients', isset( $_POST['isd_automate_clients'] ) ? 1 : 0 );

        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }

    $api_url = get_option( 'isd_api_url', '' );
    $api_token = get_option( 'isd_api_token', '' );
    $interval = get_option( 'isd_interval', 1 );
    // Obtener los valores de automatización
    $automate_products = get_option( 'isd_automate_products', 0 );
    $automate_clients = get_option( 'isd_automate_clients', 0 );

    ?>
    <div class="wrap">
        <h1>Integrator Simon.dev Settings</h1>
        <div class="col-md-12 mt-3 mb-3">
            <a href="<?php echo admin_url('admin.php?page=isd_dashboard'); ?>" class="btn btn-secondary">Dashboard</a>
        </div>
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
            <h2 class="title mt-4">Automatizaciones</h2>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Automatizar Productos</th>
                    <td>
                    <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="isd_automate_products" name="isd_automate_products" <?php checked( 1, $automate_products ); ?>>
                            <label class="custom-control-label" for="isd_automate_products" id="pcstatus"><?php echo $automate_products ? 'Encendido' : 'Apagado'; ?></label>
                        </div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Automatizar Clientes</th>
                    <td>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="isd_automate_clients" name="isd_automate_clients" <?php checked( 1, $automate_clients ); ?>>
                            <label class="custom-control-label" for="isd_automate_clients" id="clientstatus"><?php echo $automate_clients ? 'Encendido' : 'Apagado'; ?></label>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button( 'Save Settings', 'primary', 'isd_save_settings' ); ?>
        </form>
        <style>
            .custom-control-input:checked~.custom-control-label::before {
                background-color: #5bc0be;
                border-color: #5bc0be;
            }
            .custom-control-label::before {
                width: 2.25rem;
            }
            .custom-control-label::after {
                width: 1rem;
            }
        </style>
        <script>
            document.getElementById('isd_automate_products').addEventListener('change', function() {
                var label = document.getElementById('pcstatus');
                if (this.checked) {
                    label.innerHTML = 'Encendido';
                } else {
                    label.innerHTML = 'Apagado';
                }
            });

            document.getElementById('isd_automate_clients').addEventListener('change', function() {
                var label = document.getElementById('clientstatus');
                if (this.checked) {
                    label.innerHTML = 'Encendido';
                } else {
                    label.innerHTML = 'Apagado';
                }
            });
        </script>
    </div>
    <?php
}
