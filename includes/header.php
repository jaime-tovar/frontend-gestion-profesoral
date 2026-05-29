<?php
// ============================================================================
// header.php — Inicio del HTML, sidebar y mensajes de sesion
// Ubicacion: includes/header.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Se incluye al INICIO de CADA pagina con: require_once '../includes/header.php';
//   Genera el HTML comun a todas las paginas:
//     - El <head> con Bootstrap CSS y el titulo
//     - El sidebar de navegacion (Home, Producto, Persona, etc.)
//     - Los mensajes flash de sesion (exito/error despues de una operacion)
//
//   Asi no hay que repetir el HTML del sidebar en cada pagina.
//   Cada pagina solo necesita definir su contenido especifico.
//
// COMO SE USA (en cada pagina):
//   $paginaActual = 'producto';           // Para resaltar en el sidebar
//   $tituloPagina = 'Productos';          // Para el <title> del navegador
//   require_once '../includes/header.php'; // Incluir ESTE archivo
//   // ... contenido de la pagina ...
//   require_once '../includes/footer.php'; // Cerrar el HTML
// ============================================================================

// ob_start() = Output Buffering Start (empezar a acumular la salida).
//
// PROBLEMA QUE RESUELVE:
//   En HTTP, los headers (como 'Location: pagina.php') van ANTES del body (HTML).
//   Si PHP ya envio HTML al navegador, no puede enviar mas headers.
//   Entonces header('Location: ...') falla con: "headers already sent".
//
//   ob_start() le dice a PHP: "no envies nada al navegador todavia,
//   acumula todo en un buffer (memoria). Yo te digo cuando enviar."
//   Asi podemos hacer header('Location: ...') en cualquier momento,
//   incluso despues de "generar" HTML.
//
//   El buffer se envia al final con ob_end_flush() en footer.php.
ob_start();

// session_start() = iniciar el sistema de sesiones de PHP.
//
// Las sesiones permiten guardar datos entre peticiones HTTP.
// HTTP es "stateless" (sin estado): cada peticion es independiente.
// Sin sesiones, PHP no recuerda nada de la peticion anterior.
//
// Usamos sesiones para los mensajes flash:
//   Peticion 1 (POST crear producto):
//     $_SESSION['mensaje'] = 'Producto creado';
//     header('Location: producto.php');  // Redirige (nueva peticion)
//
//   Peticion 2 (GET producto.php):
//     echo $_SESSION['mensaje'];  // 'Producto creado' (se acordo!)
//     unset($_SESSION['mensaje']); // Borrar (solo se muestra una vez)
//
// session_status() === PHP_SESSION_NONE: verificar que no haya una sesion ya iniciada.
//   Si ya hay una sesion activa, no iniciar otra (daria un warning).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Calcular la URL base del proyecto para que los links funcionen
// sin importar donde este instalado.
//
// $_SERVER['SCRIPT_NAME'] = la ruta del archivo actual.
//   Ejemplo: '/FrontPhp_AppiGenericaPhp/pages/producto.php'
//
// dirname() = obtener la carpeta del archivo (sin el nombre del archivo).
//   dirname('/FrontPhp_AppiGenericaPhp/pages/producto.php')
//   -> '/FrontPhp_AppiGenericaPhp/pages'
//
// str_replace('\\', '/') = cambiar barras invertidas por barras normales (Windows).
// str_replace('/pages', '') = quitar '/pages' para obtener la raiz del proyecto.
//   '/FrontPhp_AppiGenericaPhp/pages' -> '/FrontPhp_AppiGenericaPhp'
//
// rtrim(..., '/') = quitar la barra final si existe.
//
// Resultado: $baseUrl = '/FrontPhp_AppiGenericaPhp'
// Se usa en los links del sidebar para armar las rutas completas.
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = rtrim(str_replace('/pages', '', $scriptDir), '/');

// Si la pagina no definio $paginaActual, ponerla en '' (vacio).
// $paginaActual se usa para resaltar el link activo en el sidebar.
if (!isset($paginaActual)) $paginaActual = '';

// Usar el nombre del archivo actual para marcar el menu activo.
$currentPage = basename($_SERVER['SCRIPT_NAME']);

// Requerir login para todas las paginas excepto login.php.
$publicPages = ['login.php'];
if (empty($_SESSION['usuario_email']) && !in_array($currentPage, $publicPages, true)) {
    header('Location: ' . $baseUrl . '/pages/login.php');
    exit;
}

// Helper simple para comparar archivos activos.
function isActivePage(string $currentPage, array $files): bool {
    return in_array($currentPage, $files, true);
}

// Grupos del menu para el acordeon.
$grupoCatalogos = ['facultad.php', 'programa.php', 'area_conocimiento.php', 'linea_investigacion.php', 'termino_clave.php', 'red.php'];
$grupoDocentes = ['docente.php', 'intereses_futuros.php'];
$grupoFormacion = ['estudios_realizados.php'];
$grupoEstructura = ['docente_departamento.php'];
$grupoRedesAcademicas = [];
$grupoSeguridad = ['usuario.php', 'rol.php'];
?>
<!-- A partir de aca es HTML puro (con algo de PHP mezclado) -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- tituloPagina viene de la pagina que incluyo este header (ej: 'Productos').
         Si no esta definida, usa 'Menú Gestion Profesoral' como default (operador ??). -->
    <title><?= $tituloPagina ?? 'Menú Gestion Profesoral' ?></title>

    <!-- Google Fonts - Inter for modern typography -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS desde CDN (Content Delivery Network) — no necesita instalacion -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet" />

    <!-- CSS custom del proyecto (sidebar, layout responsive, etc.) -->
    <link href="<?= $baseUrl ?>/assets/css/app.css" rel="stylesheet" />
</head>
<body>
    <div class="page">

        <!-- ───────── SIDEBAR (barra lateral de navegacion) ───────── -->
        <!-- El sidebar aparece a la izquierda en desktop y se colapsa en movil -->
        <div class="sidebar">
            <div class="top-row ps-3 navbar navbar-dark">
                <div class="container-fluid">
                    <!-- Logo/titulo del sidebar. Clic aca lleva al Home -->
                    <a class="navbar-brand" href="<?= $baseUrl ?>/pages/home.php">Menú Gestion Profesoral</a>
                </div>
            </div>

            <!-- Boton hamburguesa para movil (implementado con CSS puro, sin JavaScript) -->
            <!-- Es un checkbox invisible: cuando se marca, el CSS muestra el menu -->
            <input type="checkbox" title="Menu de navegacion" class="navbar-toggler" />

            <!-- Lista de links de navegacion -->
            <div class="nav-scrollable">
                <nav class="nav flex-column">

                    <!--
                        Cada link del sidebar tiene esta logica PHP:
                        <?= $paginaActual === 'home' ? 'active' : '' ?>

                        Esto es un OPERADOR TERNARIO (un if en una linea):
                          condicion ? valor_si_true : valor_si_false

                        Si $paginaActual es 'home', agrega la clase CSS 'active' (resalta el link).
                        Si no, no agrega nada (string vacio '').

                        $paginaActual lo define cada pagina al inicio:
                          $paginaActual = 'producto';  (en producto.php)
                          $paginaActual = 'factura';   (en factura.php)
                    -->
                    <div class="nav-item px-3">
                        <a class="nav-link <?= isActivePage($currentPage, ['home.php']) ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/home.php">
                            <i class="bi bi-house-door me-2"></i>
                            Inicio
                        </a>
                    </div>

                    <!-- Menu en acordeon usando <details> para no depender de JavaScript -->
                    <details class="nav-item px-3 nav-accordion" <?= isActivePage($currentPage, $grupoCatalogos) ? 'open' : '' ?>>
                        <summary class="nav-link nav-accordion__summary">
                            <span><i class="bi bi-folder me-2"></i>Catalogos</span>
                        </summary>
                        <div class="nav flex-column ms-3">
                            <a class="nav-link <?= isActivePage($currentPage, ['facultad.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/facultad.php">Facultad</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['programa.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/programa.php">Programa</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['area_conocimiento.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/area_conocimiento.php">Área de Conocimiento</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['linea_investigacion.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/linea_investigacion.php">Línea de Investigación</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['termino_clave.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/termino_clave.php">Términos Clave</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['red.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/red.php">Red</a>
                        </div>
                    </details>

                    <details class="nav-item px-3 nav-accordion" <?= isActivePage($currentPage, $grupoDocentes) ? 'open' : '' ?>>
                        <summary class="nav-link nav-accordion__summary">
                            <span><i class="bi bi-people me-2"></i>Docentes</span>
                        </summary>
                        <div class="nav flex-column ms-3">
                            <a class="nav-link <?= isActivePage($currentPage, ['docente.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/docente.php">Docente</a>
                               <a class="nav-link <?= isActivePage($currentPage, ['experiencia.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/experiencia.php">Experiencia</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['evaluacion_docente.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/evaluacion_docente.php">Evaluación Docente</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['reconocimiento.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/reconocimiento.php">Reconocimientos</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['intereses_futuros.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/intereses_futuros.php">Intereses Futuros</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['red_docente.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/red_docente.php">Red - Docente</a>
                        </div>
                    </details>

                    <details class="nav-item px-3 nav-accordion" <?= isActivePage($currentPage, $grupoFormacion) ? 'open' : '' ?>>
                        <summary class="nav-link nav-accordion__summary">
                            <span><i class="bi bi-mortarboard me-2"></i>Formacion Academica</span>
                        </summary>
                        <div class="nav flex-column ms-3">
                            <a class="nav-link <?= isActivePage($currentPage, ['estudios_realizados.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/estudios_realizados.php">Estudios Realizados</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['estudio_ac.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/estudio_ac.php">Área de Estudio</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['apoyo_profesoral.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/apoyo_profesoral.php">Apoyo Profesoral</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['beca.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/beca.php">Beca</a>
                        </div>
                    </details>

                    <details class="nav-item px-3 nav-accordion" <?= isActivePage($currentPage, $grupoEstructura) ? 'open' : '' ?>>
                        <summary class="nav-link nav-accordion__summary">
                            <span><i class="bi bi-building me-2"></i>Estructura Academica</span>
                        </summary>
                        <div class="nav flex-column ms-3">
                            <a class="nav-link <?= isActivePage($currentPage, ['docente_departamento.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/docente_departamento.php">Docente por Departamento</a>
                        </div>
                    </details>

                    <details class="nav-item px-3 nav-accordion" <?= isActivePage($currentPage, $grupoSeguridad) ? 'open' : '' ?>>
                        <summary class="nav-link nav-accordion__summary">
                            <span><i class="bi bi-shield-lock me-2"></i>Seguridad</span>
                        </summary>
                        <div class="nav flex-column ms-3">
                            <a class="nav-link <?= isActivePage($currentPage, ['usuario.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/usuario.php">Usuario</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['rol.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/rol.php">Rol</a>
                            <a class="nav-link <?= isActivePage($currentPage, ['rol_usuario.php']) ? 'active' : '' ?>"
                               href="<?= $baseUrl ?>/pages/rol_usuario.php">Permisos</a>
                        </div>
                    </details>

                </nav>
            </div>
        </div>

        <!-- ───────── CONTENIDO PRINCIPAL ───────── -->
        <main>
            <div class="top-row px-4">
                <span><i class="bi bi-grid-3x3-gap me-2"></i>Sistema de Gestion Profesoral</span>
                <?php if (!empty($_SESSION['usuario_email'])): ?>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <?php if (!empty($_SESSION['usuario_nombre'])): ?>
                            <span class="text-muted small d-none d-sm-inline">
                                <?= htmlspecialchars($_SESSION['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php endif; ?>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= $baseUrl ?>/pages/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i>Cerrar sesion
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <article class="content px-4">

                <!-- ───────── MENSAJES FLASH DE SESION ───────── -->
                <?php
                // Mensajes flash: notificaciones que se muestran UNA SOLA VEZ.
                // Despues de crear/editar/eliminar, la pagina guarda un mensaje en $_SESSION
                // y redirige. Aca se muestra y se borra con unset().
                ?>
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['tipo'] ?? 'info' ?> alert-dismissible fade show mt-3" role="alert">
                        <i class="bi bi-<?= ($_SESSION['tipo'] ?? 'info') === 'success' ? 'check-circle' : (($_SESSION['tipo'] ?? 'info') === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-2"></i>
                        <?= $_SESSION['mensaje'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                    <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
                <?php endif; ?>
