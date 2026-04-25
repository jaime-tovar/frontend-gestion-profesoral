<?php
// ============================================================================
// docente_departamento.php — CRUD para Docente Departamento
// Ubicacion: pages/docente_departamento.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Maneja TODO lo relacionado con docente_departamento: listar, ver detalle,
//   crear, editar, eliminar.
//
// TRES VISTAS:
//   ?vista=listar           -> Tabla con todos los registros
//   ?vista=ver&id=UUID      -> Detalle de un registro (solo lectura)
//   ?vista=formulario       -> Crear registro nuevo
//   ?vista=formulario&editar=UUID -> Editar registro existente
// ============================================================================

// Variables que header.php necesita para el sidebar y el titulo
$paginaActual = 'docente_departamento';
$tituloPagina = 'Docente Departamento';
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

    // Normalizar campos antes de enviar a la API.
    $dedicacion = trim($_POST['dedicacion'] ?? '');
    $dedicacion = ($dedicacion === '') ? null : $dedicacion;

    $modalidad = trim($_POST['modalidad'] ?? '');
    $modalidad = ($modalidad === '') ? null : $modalidad;

    $fechaIngreso = trim($_POST['fecha_ingreso'] ?? '');
    $fechaIngreso = ($fechaIngreso === '') ? null : $fechaIngreso;

    $fechaSalida = trim($_POST['fecha_salida'] ?? '');
    $fechaSalida = ($fechaSalida === '') ? null : $fechaSalida;

    // Validaciones obligatorias y de formato para crear/actualizar.
    if (in_array($accionPost, ['crear', 'actualizar'], true)) {
        $docenteId = trim($_POST['docente'] ?? '');
        $programaId = trim($_POST['programa'] ?? '');

        // Validar campos NOT NULL de la tabla (docente, programa).
        $mensajeValidacion = null;
        if ($docenteId === '') {
            $mensajeValidacion = 'El docente es obligatorio.';
        } elseif ($programaId === '') {
            $mensajeValidacion = 'El programa es obligatorio.';
        }

        // Validar formato de fecha cuando el campo se usa.
        if ($mensajeValidacion === null && $fechaIngreso !== null) {
            $fechaIngresoObj = DateTime::createFromFormat('Y-m-d', $fechaIngreso);
            $fechaIngresoValida = $fechaIngresoObj && $fechaIngresoObj->format('Y-m-d') === $fechaIngreso;
            if (!$fechaIngresoValida) {
                $mensajeValidacion = 'La fecha de ingreso no es valida. Use AAAA-MM-DD.';
            }
        }

        if ($mensajeValidacion === null && $fechaSalida !== null) {
            $fechaSalidaObj = DateTime::createFromFormat('Y-m-d', $fechaSalida);
            $fechaSalidaValida = $fechaSalidaObj && $fechaSalidaObj->format('Y-m-d') === $fechaSalida;
            if (!$fechaSalidaValida) {
                $mensajeValidacion = 'La fecha de salida no es valida. Use AAAA-MM-DD.';
            }
        }

        // Validar modalidad cuando se usa (lista controlada).
        if ($mensajeValidacion === null && $modalidad !== null
            && !in_array($modalidad, ['virtual', 'presencial'], true)) {
            $mensajeValidacion = 'La modalidad debe ser virtual o presencial.';
        }

        // Si hay error, redirigir al formulario correspondiente con mensaje.
        if ($mensajeValidacion !== null) {
            $_SESSION['mensaje'] = $mensajeValidacion;
            $_SESSION['tipo'] = 'danger';

            $redir = 'docente_departamento.php?vista=formulario';
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
            'programa'      => trim($_POST['programa'] ?? ''),
            'dedicacion'    => $dedicacion,
            'modalidad'     => $modalidad,
            'fecha_ingreso' => $fechaIngreso,
            'fecha_salida'  => $fechaSalida
        ];

        $resultado = $api->crear('docente_departamento', $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: docente_departamento.php');
        exit;
    }

    // ── ACTUALIZAR REGISTRO ───────────────────────────────────────────
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'docente'       => trim($_POST['docente'] ?? ''),
            'programa'      => trim($_POST['programa'] ?? ''),
            'dedicacion'    => $dedicacion,
            'modalidad'     => $modalidad,
            'fecha_ingreso' => $fechaIngreso,
            'fecha_salida'  => $fechaSalida
        ];

        $resultado = $api->actualizar('docente_departamento', 'id', $id, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: docente_departamento.php');
        exit;
    }

    // ── ELIMINAR REGISTRO ─────────────────────────────────────────────
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('docente_departamento', 'id', $id);
        $_SESSION['mensaje'] = $resultado['exito'] ? 'Registro eliminado exitosamente.' : 'Error: ' . $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: docente_departamento.php');
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
$programas = [];             // Lista de programas (para dropdown)
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

// Mapa de programas: id_programa => nombre
$programasRaw = $api->listar('programa');
$mapaProgramas = [];
foreach ($programasRaw as $prog) {
    $nombrePrograma = trim($prog['nombre'] ?? '');
    $mapaProgramas[$prog['id'] ?? ''] = ($nombrePrograma !== '') ? $nombrePrograma : ($prog['id'] ?? '');
}

// ── VISTA LISTAR ───────────────────────────────────────────────────
if ($vista === 'listar') {
    $registrosRaw = $api->listar('docente_departamento');
    foreach ($registrosRaw as $r) {
        $r['nombre_docente'] = $mapaDocentes[$r['docente'] ?? ''] ?? '';
        $r['nombre_programa'] = $mapaProgramas[$r['programa'] ?? ''] ?? '';
        $registros[] = $r;
    }
}

// ── VISTA VER (detalle de un registro) ───────────────────────────────
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $registroArr = $api->obtenerPorClave('docente_departamento', 'id', $id);
    if (!empty($registroArr)) {
        $registro = $registroArr[0];
        $registro['nombre_docente'] = $mapaDocentes[$registro['docente'] ?? ''] ?? '';
        $registro['nombre_programa'] = $mapaProgramas[$registro['programa'] ?? ''] ?? '';
    }
}

// ── VISTA FORMULARIO (crear o editar) ───────────────────────────────
if ($vista === 'formulario') {
    $docentes = $docentesRaw;
    $programas = $programasRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $registroArr = $api->obtenerPorClave('docente_departamento', 'id', $id);
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
    <h3>Docente Departamento</h3>

    <!-- =============================================================== -->
    <!-- VISTA: LISTAR REGISTROS (tabla)                                 -->
    <!-- =============================================================== -->
    <?php if ($vista === 'listar'): ?>

        <a href="docente_departamento.php?vista=formulario" class="btn btn-primary mb-3">Nuevo Registro</a>

        <?php if (!empty($registros)): ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Docente</th>
                        <th>Programa</th>
                        <th>Dedicacion</th>
                        <th>Modalidad</th>
                        <th>Fecha Ingreso</th>
                        <th>Fecha Salida</th>
                        <th>Fecha Creacion</th>
                        <th>Fecha Actualizacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= $r['nombre_docente'] ?? '' ?></td>
                        <td><?= $r['nombre_programa'] ?? '' ?></td>
                        <td><?= $r['dedicacion'] ?? '' ?></td>
                        <td><?= $r['modalidad'] ?? '' ?></td>
                        <td><?= $r['fecha_ingreso'] ?? '' ?></td>
                        <td><?= $r['fecha_salida'] ?? '' ?></td>
                        <td><?= $r['fecha_creacion'] ?? '' ?></td>
                        <td><?= $r['fecha_actualizacion'] ?? '' ?></td>
                        <td>
                            <a href="docente_departamento.php?vista=ver&id=<?= $r['id'] ?? '' ?>" class="btn btn-info btn-sm me-1">Ver</a>
                            <a href="docente_departamento.php?vista=formulario&editar=<?= $r['id'] ?? '' ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                            <form method="POST" action="docente_departamento.php" style="display:inline"
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

        <a href="docente_departamento.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <?php if ($registro): ?>
            <div class="card mb-3">
                <div class="card-header"><strong>Docente Departamento</strong></div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Docente:</strong> <?= $registro['nombre_docente'] ?? '' ?></div>
                        <div class="col-md-6"><strong>Programa:</strong> <?= $registro['nombre_programa'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Dedicacion:</strong> <?= $registro['dedicacion'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Modalidad:</strong> <?= $registro['modalidad'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Fecha Ingreso:</strong> <?= $registro['fecha_ingreso'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Fecha Salida:</strong> <?= $registro['fecha_salida'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Fecha Creacion:</strong> <?= $registro['fecha_creacion'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Fecha Actualizacion:</strong> <?= $registro['fecha_actualizacion'] ?? '' ?></div>
                    </div>
                </div>
            </div>

            <a href="docente_departamento.php?vista=formulario&editar=<?= $registro['id'] ?? '' ?>" class="btn btn-warning me-2">Editar</a>
            <form method="POST" action="docente_departamento.php" style="display:inline"
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

        <a href="docente_departamento.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <div class="card mb-3">
            <div class="card-header">
                <?= $editando ? "Editar Registro" : "Nuevo Registro" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="docente_departamento.php"
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
                            <label class="form-label">Programa</label>
                            <select class="form-select" name="programa" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($programas as $prog): ?>
                                    <option value="<?= $prog['id'] ?? '' ?>"
                                        <?= ($editando && $registro && ($registro['programa'] ?? '') == ($prog['id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= $mapaProgramas[$prog['id'] ?? ''] ?? '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Dedicacion</label><span class="text-danger">*</span>
                            <input class="form-control" name="dedicacion" value="<?= $registro['dedicacion'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Modalidad</label><span class="text-danger">*</span>
                            <?php $modalidadValor = (string)($registro['modalidad'] ?? ''); ?>
                            <!-- Lista controlada para modalidad (virtual/presencial) -->
                            <select class="form-select" name="modalidad" required><span class="text-danger">*</span>
                                <option value="" <?= $modalidadValor === '' ? 'selected' : '' ?>>-- Seleccionar --</option>
                                <option value="virtual" <?= $modalidadValor === 'virtual' ? 'selected' : '' ?>>virtual</option>
                                <option value="presencial" <?= $modalidadValor === 'presencial' ? 'selected' : '' ?>>presencial</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Ingreso</label> <span class="text-danger">*</span>
                            <input class="form-control" type="date" name="fecha_ingreso"
                                   value="<?= $registro['fecha_ingreso'] ?? '' ?>" required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Fecha Salida</label>
                            <input class="form-control" type="date" name="fecha_salida"
                                   value="<?= $registro['fecha_salida'] ?? '' ?>" />
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-success me-2" type="submit">Guardar</button>
                        <a href="docente_departamento.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
