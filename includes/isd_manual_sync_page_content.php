<?php

function SyncProducts(){
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
    $status_code = 0;
    // Procesar la respuesta
    if (is_wp_error($response)) {
        $result = 'Error al realizar la solicitud: Servicio temporalmente fuera de servicio.<br><a href="mailto:dev@agenciasimon.com">Contactar soporte</a>';
    } else {
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code == 200) {
            $body = wp_remote_retrieve_body($response);
            $api_response = json_encode(json_decode($body), JSON_PRETTY_PRINT);
            $result = json_decode($body, true);
            $product_register = isd_register_log($result, 'product');
            $log_id = $product_register['log_id'];
            // Si hay fallos, registrar los detalles de los fallos
            if ($result['Fails_sync'] > 0) {
                isd_register_fails($log_id, $result['Fails_data']);
            }
        } else {
            $result = 'Error de sincronización: Puede reintentar la tarea.<br><a href="mailto:dev@agenciasimon.com">Contactar soporte</a>';
        }
    }
    return [$result, $status_code];
}

function isd_manual_sync_page_content()
{
    $response = SyncProducts();
    $result = $response[0];
    $status_code = $response[1];
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
                        <strong><?php echo strval($status_code == 200? $result['message']: $result); ?></strong>
                        <br>
                        <strong>Tiempo de ejecución: </strong><?php echo esc_html($status_code == 200? $result['execution_time']: 0); ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Productos Sincronizados</div>
                    <div class="card-body">
                        <strong><?php echo esc_html($status_code == 200? $result['created_products'] : 0); ?></strong>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Productos Actualizados</div>
                    <div class="card-body">
                        <strong><?php echo esc_html($status_code == 200? $result['updated_Count'] : 0); ?></strong>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Fallos de Sincronización</div>
                    <div class="card-body">
                        <strong><?php echo esc_html($status_code == 200? $result['Fails_sync'] : 0); ?></strong>
                    </div>
                </div>

            </div>
            <a href="<?php echo admin_url('admin.php?page=isd_dashboard'); ?>" class="btn btn-info mt-4">Regresar al Dashboard</a>
            <?php
}

// Registrar un nuevo log en la tabla de logs
function isd_register_log($api_response, $type)
{
    global $wpdb;
    $table_name_logs = $wpdb->prefix . 'isd_logs';
    $data = array(
        'type' => $type, // o 'client', 'invoice', según corresponda
        'error' => $api_response['error'] ? 0 : 1,
        'message' => $api_response['message'],
        'execution_time' => (float) $api_response['execution_time'],
        'created_count' => (int) $api_response['created_products'],
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
    $table_name_fails = $wpdb->prefix . 'isd_logs';

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
