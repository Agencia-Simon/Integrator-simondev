<?php
/**
 * Plugin Name: Integrator Simon.dev
 * Description: Un plugin integrador de Simon.dev
 * Version: 1.0.4
 * Author: Agencia Simon.dev
 * License: GPL2
 */

// Evitar el acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb;
$table_name_logs = $wpdb->prefix . 'simondev_integratorlogs';
$table_name_fails = $wpdb->prefix . 'simondev_integratorfails';

// Crear un menú en el panel de administración
function isd_add_menu() {
    add_menu_page(
        'Integrator Simon.dev',
        'Integrator Simon.dev',
        'manage_options',
        'isd_dashboard',
        'isd_dashboard_page',
        'dashicons-admin-generic'
    );

    add_submenu_page(
        'isd_dashboard',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'isd_dashboard',
        'isd_dashboard_page'
    );

    add_submenu_page(
        'isd_dashboard',
        'Settings',
        'Settings',
        'manage_options',
        'isd_settings',
        'isd_settings_page'
    );
}
add_action( 'admin_menu', 'isd_add_menu' );

// Función para mostrar la página del dashboard
function isd_dashboard_page() {
    include plugin_dir_path( __FILE__ ) . 'includes/class-isd-dashboard.php';
    isd_dashboard_page_content();
}

// Función para mostrar la página de configuración
function isd_settings_page() {
    include plugin_dir_path( __FILE__ ) . 'includes/class-isd-settings.php';
    isd_settings_page_content();
}

function isd_register_sync_page() {
    include plugin_dir_path( __FILE__ ) . 'includes/isd_manual_sync_page_content.php';
    add_submenu_page(
        null, // No se mostrará en el menú
        'Sincronización Manual', // Título de la página
        'Sincronización Manual', // Título del menú
        'manage_options', // Capacidad requerida
        'isd_manual_sync', // Slug de la página
        'isd_manual_sync_page_content' // Función que mostrará el contenido
    );
}
add_action('admin_menu', 'isd_register_sync_page');

// Encolar estilos y scripts
function isd_enqueue_scripts($hook) {
    // Verificar si estamos en la página del plugin
    if (strpos($hook, 'isd_') === false) {
        return;
    }

    // Encolar los estilos de Bootstrap
    wp_enqueue_style('bootstrap-css', 'https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css');

    // Encolar el script de Chart.js para el gráfico
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
}
add_action('admin_enqueue_scripts', 'isd_enqueue_scripts');

function isd_manual_sync() {
    $api_url = get_option('isd_api_url'); // Obtener la URL del API desde settings
    $api_token = get_option('isd_api_token'); // Obtener el token desde settings
    
    $response = wp_remote_get(
        $api_url . '/api/custom-window',
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_token,
                'Content-Type'  => 'application/json'
            )
        )
    );

    if (is_wp_error($response)) {
        wp_send_json_error('Error en la solicitud: ' . $response->get_error_message());
    } else {
        wp_send_json_success(wp_remote_retrieve_body($response));
    }
}
add_action('wp_ajax_isd_manual_sync', 'isd_manual_sync');

// Función para agregar intervalos personalizados para el cron
function isd_cron_products($schedules) {
    $custom_interval = get_option('isd_interval', 1); // Obtener el intervalo configurado en minutos
    $schedules['isd_product_interval'] = array(
        'interval' => $custom_interval * 60, // Convertir a segundos
        'display'  => __('Intervalo de sincronización de productos (Minutos)')
    );
    return $schedules;
}
add_filter('cron_schedules', 'isd_cron_products');

// Programar o desprogramar el cron en base a la configuración
function isd_manage_cron_task() {
    $is_automate_products = get_option('isd_automate_products', 0); // Verificar si está activada la automatización
    $timestamp = wp_next_scheduled('isd_cron_task');

    if ($is_automate_products) {
        // Si está activado y no hay cron programado, programar uno
        if (!$timestamp) {
            wp_schedule_event(time(), 'isd_product_interval', 'isd_cron_task');
        }
    } else {
        // Si está desactivado y hay un cron programado, desprogramarlo
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'isd_cron_task');
        }
    }
}

// Ejecutar la lógica al activar el plugin
function isd_schedule_cron() {
    isd_manage_cron_task(); // Revisar la configuración y programar/desprogramar el cron
}
register_activation_hook(__FILE__, 'isd_schedule_cron');

// Desactivar cron al desactivar el plugin
function isd_unschedule_cron() {
    $timestamp = wp_next_scheduled('isd_cron_task');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'isd_cron_task');
    }
}
register_deactivation_hook(__FILE__, 'isd_unschedule_cron');

// Función de la tarea cron
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

function isd_cron_task_callback() {
    isd_write_log('Cron iniciado');
    $result = SyncProducts();
    $status_code = $result[1];
    if ($status_code == 200) {
        isd_write_log('Sincronización realizada [mensaje]- '.$result[0]['message']);
    } else {
        isd_write_log('Error en la sincronización [mensaje]- '.$result[0]);
    }
}
add_action('isd_cron_task', 'isd_cron_task_callback');

// Verificar cambios en la configuración y reprogramar el cron si es necesario
function isd_save_settings() {
    if ( isset( $_POST['isd_save_settings'] ) ) {
        check_admin_referer( 'isd_save_settings_nonce', 'isd_save_settings_nonce_field' );

        // Guardar la configuración
        update_option( 'isd_api_url', sanitize_text_field( $_POST['isd_api_url'] ) );
        update_option( 'isd_api_token', sanitize_text_field( $_POST['isd_api_token'] ) );
        update_option( 'isd_interval', sanitize_text_field( $_POST['isd_interval'] ) );
        
        // Guardar nuevas variables de automatización
        update_option( 'isd_automate_products', isset( $_POST['isd_automate_products'] ) ? 1 : 0 );
        update_option( 'isd_automate_clients', isset( $_POST['isd_automate_clients'] ) ? 1 : 0 );

        // Revisar la configuración para programar o desprogramar el cron
        isd_manage_cron_task();

        echo '<div class="updated"><p>Ajustes guardados.</p></div>';
    }
}
add_action('admin_init', 'isd_save_settings');

// Función para guardar logs en un archivo txt en el directorio del plugin
function isd_write_log($message) {
    // Obtener el directorio del plugin
    $plugin_dir = plugin_dir_path( __FILE__ );
    
    // Definir la ruta del archivo log
    $log_file = $plugin_dir . 'logs.txt';

    // Preparar el mensaje a escribir
    $log_message = date("Y-m-d H:i:s") . " >> " . $message . PHP_EOL;

    // Escribir el mensaje en el archivo (crea el archivo si no existe)
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

// Crear tablas al cargar el plugin si no existen
function isd_create_tables_if_not_exists() {
    global $wpdb;
    global $table_name_logs, $table_name_fails;
    
    // Nombres de las tablas (especifica correctamente los prefijos si es necesario)
    $table_name_logs = $wpdb->prefix . 'isd_logs';
    $table_name_fails = $wpdb->prefix . 'isd_fails';

    $charset_collate = $wpdb->get_charset_collate();

    // Verificar si la tabla de logs ya existe
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name_logs}'") != $table_name_logs) {
        // SQL para crear la tabla de logs
        $sql_logs = "CREATE TABLE $table_name_logs (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type ENUM('product', 'client', 'invoice') NOT NULL,
            error BOOLEAN NOT NULL,
            message TEXT,
            execution_time FLOAT NOT NULL,
            created_count INT DEFAULT 0,
            updated_count INT DEFAULT 0,
            failed_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_logs);
    }

    // Verificar si la tabla de fallos ya existe
    if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name_fails}'") != $table_name_fails) {
        // SQL para crear la tabla de fallos
        $sql_fails = "CREATE TABLE $table_name_fails (
            id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            log_id BIGINT(20) UNSIGNED NOT NULL,
            sku VARCHAR(255) NOT NULL,
            message TEXT,
            datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (log_id) REFERENCES $table_name_logs(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_fails);
    }
}

// Hook para verificar y crear tablas al cargar el plugin
add_action('plugins_loaded', 'isd_create_tables_if_not_exists');

