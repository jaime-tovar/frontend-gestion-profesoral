<?php
// ============================================================================
// intereses_futuros.php — CRUD para Intereses Futuros
// Ubicacion: pages/intereses_futuros.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Maneja TODO lo relacionado con intereses_futuros: listar, ver detalle,
//   crear, editar, eliminar.
//
// TRES VISTAS:
//   ?vista=listar           -> Tabla con todos los registros
//   ?vista=ver&id=UUID      -> Detalle de un registro (solo lectura)
//   ?vista=formulario       -> Crear registro nuevo
//   ?vista=formulario&editar=UUID -> Editar registro existente
// ============================================================================

// Variables que header.php necesita para el sidebar y el titulo
$paginaActual = 'intereses_futuros';
$tituloPagina = 'Intereses Futuros';
require __DIR__ . '/../includes/header.php';         // HTML comun (sidebar, mensajes flash)
require_once __DIR__ . '/../services/ApiService.php'; // Para llamar a la API

// Crear una instancia del servicio API
$api = new ApiService();

// Leer la vista actual del query string.
// Si no se especifica (?vista=...), por defecto es 'listar'.
$vista = $_GET['vista'] ?? 'listar';

// ============================================================================
// PROCESAR ACCIONES POST (cuando el usuario envia un formulario)
// ============================================================================
// Esta seccion se ejecuta SOLO cuando llega un POST (submit de formulario).
// Patron PRG: despues de procesar, redirige con header('Location:...').

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // $_POST['accion_post'] viene de un <input type="hidden" name="accion_post" value="crear">
    // Le dice a esta pagina QUE hacer: 'crear', 'actualizar', o 'eliminar'.
    $accionPost = $_POST['accion_post'] ?? '';

    // Validaciones obligatorias para crear/actualizar.
    if (in_array($accionPost, ['crear', 'actualizar'], true)) {
        $docenteId = trim($_POST['docente'] ?? '');
        $terminoId = trim($_POST['termino_clave'] ?? '');

        // Validar campos NOT NULL de la tabla (docente, termino_clave).
        $mensajeValidacion = null;
        if ($docenteId === '') {
            $mensajeValidacion = 'El docente es obligatorio.';
        } elseif ($terminoId === '') {
            $mensajeValidacion = 'El termino clave es obligatorio.';
        }

        // Si hay error, redirigir al formulario correspondiente con mensaje.
        if ($mensajeValidacion !== null) {
            $_SESSION['mensaje'] = $mensajeValidacion;
            $_SESSION['tipo'] = 'danger';

            $redir = 'intereses_futuros.php?vista=formulario';
            if ($accionPost === 'actualizar') {
                $id = $_POST['id'] ?? '';
                if ($id !== '') {
                    $redir .= '&editar=' . urlencode($id);
                }
            }

            header('Location: ' . $redir);
            exit;
        }
    }

    // ── CREAR REGISTRO ───────────────────────────────────────────────
    if ($accionPost === 'crear') {
        $datos = [
            'docente'       => trim($_POST['docente'] ?? ''),
            'termino_clave' => trim($_POST['termino_clave'] ?? '')
        ];

        $resultado = $api->crear('intereses_futuros', $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: intereses_futuros.php');
        exit;
    }

    // ── ACTUALIZAR REGISTRO ───────────────────────────────────────────
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'docente'       => trim($_POST['docente'] ?? ''),
            'termino_clave' => trim($_POST['termino_clave'] ?? '')
        ];

        $resultado = $api->actualizar('intereses_futuros', 'id', $id, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: intereses_futuros.php');
        exit;
    }

    // ── ELIMINAR REGISTRO ─────────────────────────────────────────────
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('intereses_futuros', 'id', $id);
        $_SESSION['mensaje'] = $resultado['exito'] ? 'Registro eliminado exitosamente.' : 'Error: ' . $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: intereses_futuros.php');
        exit;
    }
}

// ============================================================================
// CARGAR DATOS SEGUN LA VISTA (se ejecuta en peticiones GET)
// ============================================================================
// Aca se preparan los datos que el HTML de abajo va a mostrar.

$registros = [];             // Lista de registros (para vista listar)
$registro = null;            // Un registro especifico (para vista ver/formulario)
$docentes = [];              // Lista de docentes (para dropdown)
$terminos = [];              // Lista de terminos clave (para dropdown)
$editando = false;           // true = editando registro existente, false = creando nuevo

// Mapa de docentes: id_docente => "Nombres Apellidos (Cedula)"
$docentesRaw = $api->listar('docente');
$mapaDocentes = [];
foreach ($docentesRaw as $doc) {
    $nombre = trim(($doc['nombres'] ?? '') . ' ' . ($doc['apellidos'] ?? ''));
    $cedula = trim($doc['cedula'] ?? '');
    $etiqueta = $nombre !== '' ? $nombre : ($cedula !== '' ? $cedula : ($doc['id'] ?? ''));
    if ($cedula !== '' && $nombre !== '') {
        $etiqueta .= ' (' . $cedula . ')';
    }
    $mapaDocentes[$doc['id'] ?? ''] = $etiqueta;
}

// Mapa de terminos: id_termino => "termino (termino_ingles)"
$terminosRaw = $api->listar('termino_clave');
$mapaTerminos = [];
foreach ($terminosRaw as $t) {
    $termino = trim($t['termino'] ?? '');
    $terminoIngles = trim($t['termino_ingles'] ?? '');
    $etiqueta = $termino !== '' ? $termino : ($t['id'] ?? '');
    if ($termino !== '' && $terminoIngles !== '') {
        $etiqueta .= ' (' . $terminoIngles . ')';
    }
    $mapaTerminos[$t['id'] ?? ''] = $etiqueta;
}

// ── VISTA LISTAR ───────────────────────────────────────────────────
if ($vista === 'listar') {
    $registrosRaw = $api->listar('intereses_futuros');
    foreach ($registrosRaw as $r) {
        $r['nombre_docente'] = $mapaDocentes[$r['docente'] ?? ''] ?? '';
        $r['nombre_termino'] = $mapaTerminos[$r['termino_clave'] ?? ''] ?? '';
        $registros[] = $r;
    }
}

// ── VISTA VER (detalle de un registro) ───────────────────────────────
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $registroArr = $api->obtenerPorClave('intereses_futuros', 'id', $id);
    if (!empty($registroArr)) {
        $registro = $registroArr[0];
        $registro['nombre_docente'] = $mapaDocentes[$registro['docente'] ?? ''] ?? '';
        $registro['nombre_termino'] = $mapaTerminos[$registro['termino_clave'] ?? ''] ?? '';
    }
}

// ── VISTA FORMULARIO (crear o editar) ───────────────────────────────
if ($vista === 'formulario') {
    $docentes = $docentesRaw;
    $terminos = $terminosRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $registroArr = $api->obtenerPorClave('intereses_futuros', 'id', $id);
        if (!empty($registroArr)) {
            $registro = $registroArr[0];
        }
    }
}
?>

<!-- ======================================================================
     HTML — Lo que ve el usuario en el navegador
     A partir de aca es HTML con PHP mezclado para mostrar datos dinamicos.
     ====================================================================== -->

<div class="container mt-4">
    <h3>Intereses Futuros</h3>

    <!-- =============================================================== -->
    <!-- VISTA: LISTAR REGISTROS (tabla)                                 -->
    <!-- =============================================================== -->
    <?php if ($vista === 'listar'): ?>

        <a href="intereses_futuros.php?vista=formulario" class="btn btn-primary mb-3">Nuevo Registro</a>

        <?php if (!empty($registros)): ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Docente</th>
                        <th>Termino Clave</th>
                        <th>Fecha Creacion</th>
                        <th>Fecha Actualizacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= $r['nombre_docente'] ?? '' ?></td>
                        <td><?= $r['nombre_termino'] ?? '' ?></td>
                        <td><?= $r['fecha_creacion'] ?? '' ?></td>
                        <td><?= $r['fecha_actualizacion'] ?? '' ?></td>
                        <td>
                            <a href="intereses_futuros.php?vista=ver&id=<?= $r['id'] ?? '' ?>" class="btn btn-info btn-sm me-1">Ver</a>
                            <a href="intereses_futuros.php?vista=formulario&editar=<?= $r['id'] ?? '' ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                            <form method="POST" action="intereses_futuros.php" style="display:inline"
                                  onsubmit="return confirm('Eliminar registro?')">
                                <input type="hidden" name="accion_post" value="eliminar" />
                                <input type="hidden" name="id" value="<?= $r['id'] ?? '' ?>" />
                                <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No se encontraron registros.</div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: VER DETALLE (solo lectura)                               -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'ver'): ?>

        <a href="intereses_futuros.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <?php if ($registro): ?>
            <div class="card mb-3">
                <div class="card-header"><strong>Interes Futuro</strong></div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Docente:</strong> <?= $registro['nombre_docente'] ?? '' ?></div>
                        <div class="col-md-6"><strong>Termino Clave:</strong> <?= $registro['nombre_termino'] ?? '' ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><strong>Fecha Creacion:</strong> <?= $registro['fecha_creacion'] ?? '' ?></div>
                        <div class="col-md-6"><strong>Fecha Actualizacion:</strong> <?= $registro['fecha_actualizacion'] ?? '' ?></div>
                    </div>
                </div>
            </div>

            <a href="intereses_futuros.php?vista=formulario&editar=<?= $registro['id'] ?? '' ?>" class="btn btn-warning me-2">Editar</a>
            <form method="POST" action="intereses_futuros.php" style="display:inline"
                  onsubmit="return confirm('Eliminar registro?')">
                <input type="hidden" name="accion_post" value="eliminar" />
                <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">Registro no encontrado.</div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: FORMULARIO (CREAR / EDITAR)                              -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="intereses_futuros.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <div class="card mb-3">
            <div class="card-header">
                <?= $editando ? "Editar Registro" : "Nuevo Registro" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="intereses_futuros.php"
                      onsubmit="<?= $editando ? "return confirm('Esta seguro de actualizar este Registro?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Docente</label>
                            <select class="form-select" name="docente" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($docentes as $doc): ?>
                                    <option value="<?= $doc['id'] ?? '' ?>"
                                        <?= ($editando && $registro && ($registro['docente'] ?? '') == ($doc['id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= $mapaDocentes[$doc['id'] ?? ''] ?? '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Termino Clave</label>
                            <select class="form-select" name="termino_clave" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($terminos as $t): ?>
                                    <option value="<?= $t['id'] ?? '' ?>"
                                        <?= ($editando && $registro && ($registro['termino_clave'] ?? '') == ($t['id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= $mapaTerminos[$t['id'] ?? ''] ?? '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-success me-2" type="submit">Guardar</button>
                        <a href="intereses_futuros.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
