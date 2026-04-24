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

    <!-- Bootstrap CSS desde CDN (Content Delivery Network) — no necesita instalacion -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

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
                        <a class="nav-link <?= $paginaActual === 'home' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/home.php">
                            <span class="bi bi-house-door-fill-nav-menu"></span> Home
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'area_conocimiento' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/area_conocimiento.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Área de Conocimiento
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'termino_clave' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/termino_clave.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Termino Clave
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'linea_investigacion' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/linea_investigacion.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Linea de Investigación
                        </a>
                    </div>
                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'facultad' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/facultad.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Facultad
                        </a>
                    </div>
                
                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'red' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/red.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Red
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'programa' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/programa.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Programa
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'rol' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/rol.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Rol
                        </a>
                    </div>
                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'usuario' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/usuario.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Usuario
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'ruta' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/ruta.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Ruta
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'cliente' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/cliente.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Cliente
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'vendedor' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/vendedor.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Vendedor
                        </a>
                    </div>

                    <div class="nav-item px-3">
                        <a class="nav-link <?= $paginaActual === 'docente' ? 'active' : '' ?>"
                           href="<?= $baseUrl ?>/pages/docente.php">
                            <span class="bi bi-list-nested-nav-menu"></span> Docente
                        </a>
                    </div>

                </nav>
            </div>
        </div>

        <!-- ───────── CONTENIDO PRINCIPAL ───────── -->
        <main>
            <div class="top-row px-4">
                <span>Gestión Profesoral</span>
            </div>

            <article class="content px-4">

                <!-- ───────── MENSAJES FLASH DE SESION ───────── -->
                <?php
                // Mensajes flash: notificaciones que se muestran UNA SOLA VEZ.
                // Despues de crear/editar/eliminar, la pagina guarda un mensaje en $_SESSION
                // y redirige. Aca se muestra y se borra con unset().
                ?>
                <?php if (isset($_SESSION['mensaje'])): ?>
                    <div class="alert alert-<?= $_SESSION['tipo'] ?? 'info' ?> alert-dismissible fade show mt-3">
                        <?= $_SESSION['mensaje'] ?>
                        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
                    </div>
                    <?php unset($_SESSION['mensaje'], $_SESSION['tipo']); ?>
                <?php endif; ?>
