<?php
// ============================================================================
// home.php — Pagina de inicio (dashboard)
// Ubicacion: pages/home.php
//
// PARA QUE SIRVE:
//   Es la primera pagina que ve el usuario. Muestra:
//   - Descripcion del proyecto
//   - Lista de tablas disponibles
//   - Estado de conexion con la API (verde si conecta, rojo si no)
//
// COMO VERIFICA LA CONEXION:
//   Hace un cURL GET a la raiz de la API (http://localhost:8000/).
//   Si la API responde, muestra la info (version, URL, link a Swagger).
//   Si no responde (timeout de 3 seg), muestra error y como solucionarlo.
// ============================================================================

$paginaActual = 'home';              // Resalta "Home" en el sidebar
$tituloPagina = 'CRUD Facturas';     // Titulo en la pestana del navegador
require __DIR__ . '/../includes/header.php';       // HTML comun (sidebar, mensajes)
require __DIR__ . '/../services/ApiService.php';   // Para tener API_BASE_URL disponible

// Verificar conexion a la API haciendo un GET a la raiz
$apiInfo = null;
$ch = curl_init(API_BASE_URL . '/');               // http://localhost:8000/
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 3);              // 3 segundos maximo de espera
$respuesta = curl_exec($ch);                        // Ejecutar la peticion
curl_close($ch);
if ($respuesta) {
    $apiInfo = json_decode($respuesta, true);       // Si respondio, parsear el JSON
}
// Si $apiInfo es null, la API no responde. El HTML de abajo muestra el error.
?>

<div class="container mt-4">

    <h1>CRUD - Base de Datos Facturas</h1>

    <p class="lead">
        Frontend PHP que consume la API generica
        <strong>ApiGenericaPhp</strong> (PHP vanilla + MySQL/MariaDB).
    </p>

    <div class="alert alert-info">
        <strong>Tablas disponibles:</strong> Producto, Persona, Usuario, Empresa, Rol, Ruta, Cliente, Vendedor, Factura.
        <br />
        Use el menu lateral para navegar a cada tabla.
    </div>

    <div class="alert alert-warning">
        <strong>Sin Stored Procedures:</strong> Este frontend usa solo CRUD generico (GET, POST, PUT, DELETE).
        Las facturas se crean con multiples llamadas al EntidadesController.
        Los <strong>triggers de MariaDB</strong> calculan automaticamente subtotales, stock y totales.
    </div>

    <?php if ($apiInfo): ?>
        <div class="card mt-4 border-secondary">
            <div class="card-header bg-secondary bg-opacity-10 text-muted py-2">
                <small><strong>API conectada</strong></small>
            </div>
            <div class="card-body py-2">
                <table class="table table-sm table-borderless mb-0" style="font-size: 0.85rem;">
                    <tbody>
                        <tr>
                            <td class="text-muted" style="width:160px">API</td>
                            <td><strong><?= $apiInfo['Mensaje'] ?? 'ApiGenericaPhp' ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Version</td>
                            <td><?= $apiInfo['Version'] ?? '?' ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">URL</td>
                            <td><a href="<?= API_BASE_URL ?>/docs" target="_blank"><?= API_BASE_URL ?></a></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Swagger</td>
                            <td><a href="<?= API_BASE_URL ?>/docs" target="_blank"><?= API_BASE_URL ?>/docs</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-danger mt-4">
            <strong>API no disponible.</strong> Verifique que ApiGenericaPhp este corriendo en
            <code><?= API_BASE_URL ?></code>
            <br />
            <small>Ejecutar: <code>php -S localhost:8000 -t public</code> en la carpeta ApiGenericaPhp</small>
        </div>
    <?php endif; ?>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
