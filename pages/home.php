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
$tituloPagina = 'CRUD Gestion Profesoral';     // Titulo en la pestana del navegador
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
    
    <!-- Page Header -->
    <div class="page-header mb-4">
        <div>
            <h1 class="mb-1">Panel de Control</h1>
            <p class="text-muted mb-0">Sistema de Gestion Profesoral - Base de Datos Academica</p>
        </div>
    </div>

    <!-- Stats Cards Row -->
    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card card-hover-lift">
                <div class="stat-icon primary">
                    <i class="bi bi-folder fs-4"></i>
                </div>
                <div class="stat-value">6</div>
                <div class="stat-label">Catalogos</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card card-hover-lift">
                <div class="stat-icon success">
                    <i class="bi bi-people fs-4"></i>
                </div>
                <div class="stat-value">6</div>
                <div class="stat-label">Modulos Docentes</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card card-hover-lift">
                <div class="stat-icon warning">
                    <i class="bi bi-mortarboard fs-4"></i>
                </div>
                <div class="stat-value">4</div>
                <div class="stat-label">Formacion Academica</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card card-hover-lift">
                <div class="stat-icon info">
                    <i class="bi bi-shield-lock fs-4"></i>
                </div>
                <div class="stat-value">3</div>
                <div class="stat-label">Seguridad</div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4">
        
        <!-- Quick Access Card -->
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-lightning-charge me-2"></i>
                    <span>Acceso Rapido</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="docente.php" class="d-block p-3 rounded text-decoration-none" style="background-color: var(--secondary-100); transition: var(--transition);" onmouseover="this.style.backgroundColor='var(--accent-100)'" onmouseout="this.style.backgroundColor='var(--secondary-100)'">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-2 me-3" style="background-color: var(--accent-100);">
                                        <i class="bi bi-person-badge fs-5" style="color: var(--accent-600);"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="color: var(--primary-800);">Docentes</div>
                                        <small class="text-muted">Gestionar docentes</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="facultad.php" class="d-block p-3 rounded text-decoration-none" style="background-color: var(--secondary-100); transition: var(--transition);" onmouseover="this.style.backgroundColor='var(--success-100)'" onmouseout="this.style.backgroundColor='var(--secondary-100)'">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-2 me-3" style="background-color: var(--success-100);">
                                        <i class="bi bi-building fs-5" style="color: var(--success-600);"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="color: var(--primary-800);">Facultades</div>
                                        <small class="text-muted">Administrar facultades</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="programa.php" class="d-block p-3 rounded text-decoration-none" style="background-color: var(--secondary-100); transition: var(--transition);" onmouseover="this.style.backgroundColor='var(--warning-100)'" onmouseout="this.style.backgroundColor='var(--secondary-100)'">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-2 me-3" style="background-color: var(--warning-100);">
                                        <i class="bi bi-journal-bookmark fs-5" style="color: var(--warning-600);"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="color: var(--primary-800);">Programas</div>
                                        <small class="text-muted">Ver programas academicos</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="usuario.php" class="d-block p-3 rounded text-decoration-none" style="background-color: var(--secondary-100); transition: var(--transition);" onmouseover="this.style.backgroundColor='var(--info-100)'" onmouseout="this.style.backgroundColor='var(--secondary-100)'">
                                <div class="d-flex align-items-center">
                                    <div class="rounded p-2 me-3" style="background-color: var(--info-100);">
                                        <i class="bi bi-person-gear fs-5" style="color: var(--info-600);"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="color: var(--primary-800);">Usuarios</div>
                                        <small class="text-muted">Gestionar accesos</small>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Status Card -->
        <div class="col-lg-4">
            <?php if ($apiInfo): ?>
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center" style="background: linear-gradient(135deg, var(--success-600) 0%, var(--success-500) 100%); color: white; border: none;">
                        <i class="bi bi-check-circle me-2"></i>
                        <span>API Conectada</span>
                    </div>
                    <div class="card-body">
                        <div class="detail-row">
                            <div class="detail-label">API</div>
                            <div class="detail-value"><?= $apiInfo['Mensaje'] ?? 'ApiGenericaPhp' ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Version</div>
                            <div class="detail-value"><?= $apiInfo['Version'] ?? '?' ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">URL Base</div>
                            <div class="detail-value">
                                <a href="<?= API_BASE_URL ?>" target="_blank" class="text-decoration-none" style="color: var(--accent-600);">
                                    <?= API_BASE_URL ?>
                                </a>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Documentacion</div>
                            <div class="detail-value">
                                <a href="<?= API_BASE_URL ?>/docs" target="_blank" class="btn btn-sm btn-primary mt-1">
                                    <i class="bi bi-book me-1"></i> Ver Swagger
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center" style="background: linear-gradient(135deg, var(--danger-600) 0%, var(--danger-500) 100%); color: white; border: none;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <span>API No Disponible</span>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger mb-3">
                            <i class="bi bi-x-circle me-2"></i>
                            No se pudo conectar con la API.
                        </div>
                        <p class="text-muted small mb-2">
                            <strong>URL esperada:</strong><br>
                            <code><?= API_BASE_URL ?></code>
                        </p>
                        <p class="text-muted small mb-0">
                            <strong>Como iniciar:</strong><br>
                            <code>php -S localhost:8000 -t public</code><br>
                            en la carpeta ApiGenericaPhp
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Info Cards Row -->
    <div class="row g-4 mt-2">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-info-circle me-2"></i>
                    <span>Acerca del Sistema</span>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        Frontend PHP que consume la API generica
                        <strong>ApiGenericaPhp</strong> (PHP vanilla + MySQL/MariaDB).
                    </p>
                    <p class="text-muted small mb-0">
                        Utilice el menu lateral para navegar a cada modulo del sistema.
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-gear me-2"></i>
                    <span>Nota Tecnica</span>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Sin Stored Procedures:</strong> Este frontend usa solo CRUD generico (GET, POST, PUT, DELETE).
                    </p>
                    <p class="text-muted small mb-0">
                        Los <strong>triggers de MariaDB</strong> calculan automaticamente subtotales, stock y totales.
                    </p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
