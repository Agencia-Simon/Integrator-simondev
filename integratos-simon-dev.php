<?php
/**
 * Plugin Name: Integrator Simon.dev
 * Description: Un plugin integrador de Simon.dev
 * Version: 1.0.3
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

// Crear tablas al activar el plugin
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

