<?php
/**
 * Plugin Name: Integrator Simon.dev
 * Description: Un plugin integrador de Simon.dev
 * Version: 1.0.2
 * Author: Agencia Simon.dev
 * License: GPL2
 */

// Evitar el acceso directo al archivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

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


function custom_cors_headers() {
    header('Access-Control-Allow-Origin: http://localhost:8002'); // Cambia al origen que necesites
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}
add_action('init', 'custom_cors_headers');
