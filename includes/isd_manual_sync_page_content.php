<?php

function isd_manual_sync_page_content()
{
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
        'method' => 'GET',
        'timeout' => 60,
        'headers' => array(
            'Content-Type' => 'application/json',
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
            $api_response = json_encode(json_decode($body), JSON_PRETTY_PRINT);
            $product_register = isd_register_log($api_response);
            $log_id = $product_register['log_id'];
            // Si hay fallos, registrar los detalles de los fallos
            if ($api_response['Fails_sync'] > 0) {
                isd_register_fails($log_id, $api_response['Fails_data']);
            }
            $result = json_decode($body, true);
        } else {
            $result = 'Error al sincronizar: ' . $status_code . ' ' . wp_remote_retrieve_response_message($response);
        }
    }
    ?>
    <style>
    .card-container .card {
        margin-left: 50px;
    }
    </style>
    <div class="wrap">
        <h1>Proceso de Sincronización Manual</h1>
        <div id="sync-container">
            <p>Sincronización completada.</p>
            <!-- Productos -->
            <h3 class="mt-3 mb-3">Productos</h3>
            <div class="row card-container">    
                <div class="card">
                    <div class="card-header">Resultados</div>
                    <div class="card-body">
                        <strong><?php echo esc_html($result['message']); ?></strong>
                        <br>
                        <strong>Tiempo de ejecución:</strong><?php echo esc_html($result['execution_time']); ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Productos Sincronizados</div>
                    <div class="card-body">
                        <strong><?php echo esc_html($result['created_products']); ?></strong>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Productos Actualizados</div>
                    <div class="card-body">
                        <strong><?php echo esc_html($result['updated_Count']); ?></strong>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Fallos de Sincronización</div>
                    <div class="card-body">
                        <strong><?php echo esc_html($result['Fails_sync']); ?></strong>
                    </div>
                </div>

            </div>
            <a href="<?php echo admin_url('admin.php?page=isd_dashboard'); ?>" class="btn btn-info mt-4">Regresar al
                Dashboard</a>
            <?php
}

// Registrar un nuevo log en la tabla de logs
function isd_register_log($api_response)
{
    global $wpdb;
    $table_name_logs = $wpdb->prefix . 'simondev_integratorlogs';
    $data = array(
        'type' => 'product', // o 'client', 'invoice', según corresponda
        'error' => $api_response['error'] ? 1 : 0, // 1 para true, 0 para false
        'message' => $api_response['message'],
        'execution_time' => (float) $api_response['execution_time'],
        'created_count' => (int) $api_response['Synchronized_products'],
        'updated_count' => (int) $api_response['updated_Count'],
        'failed_count' => (int) $api_response['Fails_sync']
    );
    // Insertar el registro en la tabla de logs
    $wpdb->insert(
        $table_name_logs,
        $data
    );
    $log_id = $wpdb->insert_id;
    //append the log_id to the data array
    $data['log_id'] = $log_id;
    return $data;
}

// Registrar los fallos si existen
function isd_register_fails($log_id, $fails_data)
{
    global $wpdb;
    $table_name_fails = $wpdb->prefix . 'simondev_integratorfails';

    foreach ($fails_data as $fail) {
        $wpdb->insert(
            $table_name_fails,
            array(
                'log_id' => $log_id,
                'sku' => $fail['Sku'], // Asumiendo que 'SKU' es el campo de la respuesta
                'message' => $fail['message'], // Asumiendo que 'message' es el campo de la respuesta
                'datetime' => current_time('mysql') // Fecha y hora actual de WordPress
            )
        );
    }
}
