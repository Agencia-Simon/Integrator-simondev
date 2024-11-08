<?php

function isd_logs_page_content() {
    // Ruta al archivo de logs dentro de la carpeta del plugin
    $log_file_path = plugin_dir_path(__FILE__) . '../logs.txt';

    // Lee el contenido del archivo si existe
    $log_content = '';
    if (file_exists($log_file_path)) {
        $log_content = file_get_contents($log_file_path);
    } else {
        $log_content = 'No hay logs disponibles.';
    }
    $log_file_url = plugins_url('../logs.txt', __FILE__);

?>
    <div class="wrap">
        <h1>Integrator Simon.dev Logs</h1>
        <div class="col-md-12 mt-3 mb-3">
            <a href="<?php echo admin_url('admin.php?page=isd_dashboard'); ?>" class="btn btn-secondary">Dashboard</a>
            <a href="<?php echo esc_url($log_file_url); ?>" class="btn btn-primary" download="logs.txt">Descargar Logs</a>
        </div>
        <div class="log-content" style="background-color: #f7f7f7; padding: 15px; border: 1px solid #ccc; white-space: pre-wrap; max-height: 400px; overflow-y: scroll;">
            <?php echo esc_html($log_content); ?>
        </div>

        <h4 class="mt-4">Soporte:</h4>
        <a href="mailto:dev@agenciasimon.com" class="btn btn-success">Enviar un correo electrónico</a>
    </div>
    <?php
}
