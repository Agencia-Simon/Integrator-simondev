<?php
global $wpdb;

function isd_dashboard_page_content() {
    // Datos de ejemplo para el gráfico
    $product_data = [
        'created' => [10, 20, 15, 25, 30],
        'updated' => [5, 15, 20, 10, 25],
    ];

    global $wpdb;

    // Nombre de las tablas
    $table_logs = $wpdb->prefix . 'isd_logs';

    // Consultar el total de logs registrados
    $total_logs = $wpdb->get_var( "SELECT COUNT(*) FROM $table_logs" );

    // Consultar el promedio de execution_time para los logs con error = false
    // Suponiendo que execution_time está en un formato adecuado para convertir a DECIMAL
    $avg_execution_time = $wpdb->get_var( "
        SELECT AVG(CAST(SUBSTRING_INDEX(execution_time, ' ', 1) AS DECIMAL(10,2))) 
        FROM $table_logs 
        WHERE error = false
    " );
    $avg_execution_time = round($avg_execution_time, 2);

    ?>
    <style>
        #spinner {
            display: none;
            border: 2px solid #343a40;
            border-radius: 50%;
            border-top: 2px solid #f3f3f3;
            width: 15px;
            height: 15px;
            animation: spin 1s linear infinite;
            margin-left: 10px; /* Espaciado entre el texto y el spinner */
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <div class="wrap">
        <h1>Integrator by <a style="color: #007bff; text-decoration: none;" href="https://agenciasimon.com" target="_blank">Simon.dev</a></h1>
        <div class="container mt-4">
            <div class="col-md-12 mt-3 mb-3">
                <a href="<?php echo admin_url('admin.php?page=isd_settings'); ?>" class="btn btn-info">Configurar Integrador</a>
            </div>

            <h3 class="">Integración Activa <span class="badge badge-dark ml-2">DOBRA ➞ WOOCOMMERCE</span></h3>
            <div style="display: none;" id="sync-alert" class="alert alert-warning" role="alert">
                ¡Aviso! La sincronización está ejecutandose, por favor no cierre ni recargue esta ventana.
            </div>

            <div class="col-md-12 mt-3 mb-3">
                    <a id="sync-button" href="#" class="btn btn-secondary">Sincronización Manual <span id="spinner"></span></a>
                    <button class="btn btn-secondary">Programar Sincronizaciones</button>
                </div>
            
            <hr>

            <div class="row">
                        
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="badge badge-primary">Tareas Ejecutadas</h3>
                            <h2 class=""><?php echo $total_logs; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="badge badge-success">Tareas en Proceso</h3>
                            <h2 class=""><?php echo "0"; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h3 class="badge badge-warning">Promedio de Velocidad</h3>
                            <h2 class=""><?php echo $avg_execution_time; ?> Segundos</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 mt-2">
                    <h3 class="">Sincronizaciones de Productos</h3>
                    <canvas id="productChart"></canvas>
                </div>

                <div class="col-md-12 mb-3 mt-3">
                    <button class="btn btn-primary">Reintentar Sincronizaciones</button>
                </div>

                <div class="col-md-12 mb-3">
                    <h5 class="card-title">Productos No Sincronizados</h5>
                    <div class="table-responsive" style="max-height: 900px;">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre del Producto</th>
                                    <th>SKU del Producto</th>
                                    <th>Mensaje de Error</th>
                                    <th>Fecha Registrada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Aquí se llenarán los datos dinámicamente -->
                                <?php
                                // Ejemplo de datos de productos no sincronizados
                                $productos_no_sincronizados = [
                                    ['nombre' => 'Producto A', 'sku' => 'SKU123', 'error' => 'Error de conexión', 'fecha' => '2024-09-05'],
                                    ['nombre' => 'Producto B', 'sku' => 'SKU124', 'error' => 'API no responde', 'fecha' => '2024-09-06'],
                                    ['nombre' => 'Producto C', 'sku' => 'SKU125', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto D', 'sku' => 'SKU126', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto E', 'sku' => 'SKU127', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto F', 'sku' => 'SKU128', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto G', 'sku' => 'SKU129', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto H', 'sku' => 'SKU130', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto I', 'sku' => 'SKU131', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto J', 'sku' => 'SKU132', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto K', 'sku' => 'SKU133', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto L', 'sku' => 'SKU134', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    ['nombre' => 'Producto M', 'sku' => 'SKU135', 'error' => 'Formato incorrecto', 'fecha' => '2024-09-07'],
                                    // Agregar más productos según sea necesario
                                ];

                                foreach ($productos_no_sincronizados as $producto) {
                                    echo '<tr>';
                                    echo '<td>' . esc_html($producto['nombre']) . '</td>';
                                    echo '<td>' . esc_html($producto['sku']) . '</td>';
                                    echo '<td>' . esc_html($producto['error']) . '</td>';
                                    echo '<td>' . esc_html($producto['fecha']) . '</td>';
                                    echo '</tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var spinner = document.getElementById('spinner');
            var syncButton = document.getElementById('sync-button');
            var syncAlert = document.getElementById('sync-alert');
            syncButton.addEventListener('click', function(event) {
                event.preventDefault(); // Prevenir el comportamiento por defecto del enlace

                // Mostrar el spinner
                spinner.style.display = 'inline-block';
                syncAlert.style.display = 'block';
                // Redirigir después de mostrar el spinner
                setTimeout(function() {
                    window.location.href = '<?php echo admin_url('admin.php?page=isd_manual_sync'); ?>';
                }, 500); // Esperar un poco para que el spinner sea visible antes de redirigir
            });
        });
    </script>
    <script>
        var ctx = document.getElementById('productChart').getContext('2d');
        var productChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo'],
                datasets: [{
                    label: 'Productos Creados',
                    data: <?php echo json_encode($product_data['created']); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: false,
                }, {
                    label: 'Productos Actualizados',
                    data: <?php echo json_encode($product_data['updated']); ?>,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    backgroundColor: 'rgba(153, 102, 255, 0.2)',
                    fill: false,
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        beginAtZero: true
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
    <?php
}
