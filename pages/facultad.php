<?php
// ============================================================================
// facultad.php — CRUD para la tabla Facultad
// Ubicacion: pages/facultad.php
//
// PARA QUE SIRVE: Listar, crear, editar y eliminar facultades.
// Campos: id (PK UUID), nombre, activo
//
// PATRON QUE SIGUEN TODAS LAS PAGINAS CRUD SIMPLES:
//   1. Definir variables: $paginaActual, $tituloPagina, $tabla, $clave
//   2. Incluir header.php y ApiService.php
//   3. Si es POST: procesar la accion (crear/actualizar/eliminar)
//      -> Guardar mensaje en $_SESSION -> Redirigir (PRG)
//   4. Si es GET: cargar datos con $api->listar()
//      -> Si ?accion=nuevo: mostrar formulario vacio
//      -> Si ?accion=editar&clave=X: mostrar formulario con datos
//      -> Si sin accion: mostrar tabla con todos los registros
//   5. Incluir footer.php
//
// Las paginas persona, usuario, empresa, rol, ruta siguen ESTE MISMO PATRON.
// Solo cambian: $tabla, $clave, y los campos del formulario/tabla HTML.
//
// ESTRUCTURA DE ESTE ARCHIVO:
//   Lineas 35-77:   Seccion PHP - procesar POST (crear/actualizar/eliminar)
//   Lineas 79-103:  Seccion PHP - leer parametros GET y cargar datos
//   Lineas 110-206: Seccion HTML - interfaz visual (formulario + tabla)
// ============================================================================

// ── PASO 1: CARGAR SERVICIO API Y DEFINIR TABLA/CLAVE ─────────────────
// Se cargan ANTES del POST para que el bloque POST pueda usarlos
// sin necesidad de incluir header.php (que genera HTML).
require_once __DIR__ . '/../services/ApiService.php';

// $api = el objeto que usamos para hablar con la API
$api = new ApiService();

// $tabla = nombre de la tabla en la BD. Se usa en TODOS los metodos de ApiService.
// $clave = nombre de la columna clave primaria. Se usa para editar y eliminar.
// Si esta pagina fuera para "persona", seria: $tabla='persona', $clave='codigo'
// Si fuera para "rol", seria: $tabla='rol', $clave='id'
$tabla = 'facultad';
$clave = 'id';

// ══════════════════════════════════════════════════════════════════════════
// PASO 2: PROCESAR ACCIONES POST (crear, actualizar, eliminar)
// ══════════════════════════════════════════════════════════════════════════
// Esta seccion SOLO se ejecuta cuando el usuario envio un formulario (POST).
// Cuando simplemente abre la pagina (GET), esta seccion se salta.
//
// $_SERVER['REQUEST_METHOD'] contiene el verbo HTTP: 'GET' o 'POST'.
// 'GET'  = el usuario abrio la pagina (clic en link, o escribio la URL)
// 'POST' = el usuario envio un formulario (clic en "Guardar" o "Eliminar")

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    // $_POST es un array superglobal con todos los datos del formulario.
    // 'accion_post' viene de un <input type="hidden" name="accion_post" value="crear">
    // que esta en el formulario HTML (mas abajo). Le dice a PHP QUE hacer.
    $accionPost = $_POST['accion_post'] ?? '';

    // ── CREAR ──────────────────────────────────────────────────────────
    if ($accionPost === 'crear') {
        // Armar el array de datos que se va a enviar a la API.
        // Los campos vienen del formulario: nombre y activo.
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'activo' => isset($_POST['activo']) ? (int)$_POST['activo'] : 1
        ];

        // Llamar a la API: POST http://localhost:8000/api/facultad con los datos
        // $resultado = ['exito' => true/false, 'mensaje' => 'Registro creado exitosamente.']
        $resultado = $api->crear($tabla, $datos);

        // Guardar el resultado en la sesion para mostrarlo despues del redirect.
        // $_SESSION['mensaje'] = el texto que aparece en la alerta (verde o roja)
        // $_SESSION['tipo'] = 'success' (verde) si fue exitoso, 'danger' (rojo) si fallo
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    // ── ACTUALIZAR ─────────────────────────────────────────────────────
    if ($accionPost === 'actualizar') {
        // $valor = el id del registro a actualizar (viene del formulario)
        $valor = $_POST['id'] ?? '';

        // Solo enviar los campos que se pueden cambiar (no el id, que es la PK)
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'activo' => isset($_POST['activo']) ? (int)$_POST['activo'] : 1
        ];

        // Llamar a la API: PUT http://localhost:8000/api/facultad/id/{UUID}
        $resultado = $api->actualizar($tabla, $clave, $valor, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    // ── ELIMINAR ───────────────────────────────────────────────────────
    if ($accionPost === 'eliminar') {
        $valor = $_POST['id'] ?? '';

        // Llamar a la API: DELETE http://localhost:8000/api/facultad/id/{UUID}
        $resultado = $api->eliminar($tabla, $clave, $valor);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    // ── REDIRIGIR (patron PRG: Post/Redirect/Get) ──────────────────────
    // Despues de procesar el POST, redirigimos a la misma pagina con GET.
    // Esto evita que al refrescar (F5), el navegador reenvie el formulario.
    //
    // Sin PRG: F5 -> "¿Desea reenviar el formulario?" -> duplica la accion
    // Con PRG: F5 -> simplemente recarga la pagina normalmente
    header('Location: facultad.php');
    exit;  // IMPORTANTE: sin exit, PHP sigue ejecutando el codigo de abajo
}

// ── PASO 3: CONFIGURAR LA PAGINA ──────────────────────────────────────
// Estas variables las usa header.php para el sidebar y el titulo.
$paginaActual = 'Facultad';   // Resalta "Facultad" en el sidebar
$tituloPagina = 'Facultad';  // Aparece en la pestana del navegador (<title>)

// Incluir el header (sidebar, Bootstrap, sesion, mensajes flash)
require __DIR__ . '/../includes/header.php';

// ══════════════════════════════════════════════════════════════════════════
// PASO 4: LEER PARAMETROS GET Y CARGAR DATOS
// ══════════════════════════════════════════════════════════════════════════
// Esta seccion se ejecuta en TODAS las peticiones (GET y POST, pero POST
// ya salio con exit arriba, asi que en la practica solo se ejecuta en GET).

// Leer parametros del query string (?accion=editar&clave={UUID})
// $_GET es un array superglobal con los parametros de la URL.
$accion = $_GET['accion'] ?? '';        // ?accion=editar -> 'editar', si no viene -> ''
$valorClave = $_GET['clave'] ?? '';     // ?clave=PR001 -> 'PR001'

// Llamar a la API para obtener TODOS los registros.
// GET http://localhost:8000/api/facultad
// $registros = [['id'=>'...','nombre'=>'...','activo'=>1], ...]
$registros = $api->listar($tabla);

// Determinar si debemos mostrar el formulario o la tabla.
// in_array('editar', ['nuevo', 'editar']) -> true (esta en la lista)
// in_array('', ['nuevo', 'editar']) -> false (no esta)
$mostrarFormulario = in_array($accion, ['nuevo', 'editar']);

// ¿Estamos editando un registro existente?
$editando = $accion === 'editar';

// Si estamos editando, buscar el registro en el array de registros.
// No hacemos otra llamada a la API — buscamos en los datos que ya tenemos.
$registro = null;
if ($editando && $valorClave) {
    // Recorrer todos los registros hasta encontrar el que tiene el id buscado
    foreach ($registros as $r) {
        // $r[$clave] = $r['id'] (porque $clave = 'id')
        if (($r[$clave] ?? '') == $valorClave) {
            $registro = $r;  // Encontrado, guardar el registro
            break;           // Dejar de buscar (ya lo encontro)
        }
    }
}
// Despues de esto:
//   $registro = ['id'=>'...','nombre'=>'...','activo'=>1]
//   O $registro = null si no se encontro

// ══════════════════════════════════════════════════════════════════════════
// A PARTIR DE ACA: HTML
// La etiqueta de cierre PHP de abajo termina la seccion de codigo.
// Todo lo que sigue es HTML con pedazos de PHP para imprimir valores.
// ══════════════════════════════════════════════════════════════════════════
?>

<!-- ══════════════════════════════════════════════ -->
<!-- HTML: INTERFAZ DE USUARIO                      -->
<!-- ══════════════════════════════════════════════ -->

<!-- container = clase Bootstrap que centra el contenido con margenes laterales -->
<!-- mt-4 = margin-top 4 (espacio arriba) -->
<div class="container mt-4">
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h3 class="mb-1"><i class="bi bi-building me-2"></i>Facultades</h3>
            <p class="text-muted mb-0">Gestionar las facultades de la institucion</p>
        </div>
        <?php if (!$mostrarFormulario): ?>
            <a href="facultad.php?accion=nuevo" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nueva Facultad
            </a>
        <?php endif; ?>
    </div>

    <!-- ───────── FORMULARIO (CREAR / EDITAR) ───────── -->
    <!-- Se muestra SOLO si $mostrarFormulario es true (accion=nuevo o accion=editar) -->
    <?php if ($mostrarFormulario): ?>
        <!-- card = componente Bootstrap tipo "tarjeta" con borde, cabecera y cuerpo -->
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-<?= $editando ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
                <?= $editando ? "Editar Facultad" : "Nueva Facultad" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="facultad.php"
                      onsubmit="<?= $editando ? "return confirm('Esta seguro de actualizar la facultad?')" : '' ?>">

                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />

                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input class="form-control" name="nombre" placeholder="Ingrese el nombre de la facultad"
                                value="<?= $registro['nombre'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <?php $activoValor = (string)($registro['activo'] ?? '1'); ?>
                            <select class="form-select" name="activo">
                                <option value="1" <?= $activoValor === '1' ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= $activoValor === '0' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-check-lg me-1"></i> Guardar
                        </button>
                        <a href="facultad.php" class="btn btn-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ───────── TABLA DE REGISTROS ───────── -->
    <?php if (!empty($registros)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-table me-2"></i>Lista de Facultades</span>
                <span class="badge" style="background-color: var(--primary-800);"><?= count($registros) ?> registros</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th style="width: 120px;">Estado</th>
                                <th style="width: 180px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $reg): ?>
                            <tr>
                                <td class="fw-medium"><?= $reg['nombre'] ?? '' ?></td>
                                <td>
                                    <?php if ((int)($reg['activo'] ?? 0) === 1): ?>
                                        <span class="badge badge-active">
                                            <i class="bi bi-check-circle me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">
                                            <i class="bi bi-x-circle me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="facultad.php?accion=editar&clave=<?= $reg['id'] ?>"
                                           class="btn btn-warning btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="facultad.php"
                                            onsubmit="return confirm('Esta seguro de eliminar la facultad \'<?= $reg['nombre'] ?? '' ?>\'?')">
                                            <input type="hidden" name="accion_post" value="eliminar" />
                                            <input type="hidden" name="id" value="<?= $reg['id'] ?? '' ?>" />
                                            <button class="btn btn-danger btn-sm" type="submit" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
            </div>
            <div class="empty-state-title">No hay facultades registradas</div>
            <div class="empty-state-description">Comience creando una nueva facultad.</div>
            <a href="facultad.php?accion=nuevo" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nueva Facultad
            </a>
        </div>
    <?php endif; ?>

</div>

<!-- Incluir el footer: cierra los tags HTML que abrio header.php y ejecuta ob_end_flush() -->
<?php require __DIR__ . '/../includes/footer.php'; ?>
