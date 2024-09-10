<?php

function isd_manual_sync_page_content() {
    // Obtener la URL y el token de la API desde las opciones
    $apiUrl = esc_url(get_option('isd_api_url'));
    $apiToken = esc_attr(get_option('isd_api_token'));

    // Asegurar que el endpoint esté correctamente formateado
    $postUrl = 'api/custom-window';
    if (substr($apiUrl, -1) === '/') {
        $apiUrl .= $postUrl;
    } else {
        $apiUrl .= '/' . $postUrl;
    }

    // Configurar la solicitud HTTP
    $args = array(
        'method'    => 'GET',
        'timeout' => 60,
        'headers'   => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $apiToken,
        ),
    );

    // Realizar la solicitud a la API
    $response = wp_remote_get($apiUrl, $args);

    // Procesar la respuesta
    if (is_wp_error($response)) {
        $result = 'Error al realizar la solicitud: ' . $response->get_error_message();
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code == 200) {
            $body = wp_remote_retrieve_body($response);
            $result = json_encode(json_decode($body), JSON_PRETTY_PRINT);
        } else {
            $result = 'Error al sincronizar: ' . $status_code . ' ' . wp_remote_retrieve_response_message($response);
        }
    }
    ?>
    <div class="wrap">
        <h1>Proceso de Sincronización Manual</h1>
        <div id="sync-container">
            <p>Sincronización completada.</p>
            <pre id="sync-result"><?php echo esc_html($result); ?></pre>
            <a href="<?php echo admin_url('admin.php?page=isd_dashboard'); ?>" class="btn btn-info mt-4">Regresar al Dashboard</a>
        </div>
    </div>
    <?php
}
