<?php
// ============================================================================
// estudios_realizados.php — CRUD para Estudios Realizados
// Ubicacion: pages/estudios_realizados.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Maneja TODO lo relacionado con estudios realizados: listar, ver detalle,
//   crear, editar, eliminar.
//
// TRES VISTAS:
//   ?vista=listar           -> Tabla con todos los estudios
//   ?vista=ver&id=UUID      -> Detalle de un estudio (solo lectura)
//   ?vista=formulario       -> Crear estudio nuevo
//   ?vista=formulario&editar=UUID -> Editar estudio existente
// ============================================================================

// Variables que header.php necesita para el sidebar y el titulo
$tituloPagina = 'Estudios Realizados';
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
    $fecha = trim($_POST['fecha'] ?? '');
    $fecha = ($fecha === '') ? null : $fecha;

    $tipo = trim($_POST['tipo'] ?? '');
    $tipo = ($tipo === '') ? null : $tipo;

    $ciudad = trim($_POST['ciudad'] ?? '');
    $ciudad = ($ciudad === '') ? null : $ciudad;

    $pais = trim($_POST['pais'] ?? '');
    $pais = ($pais === '') ? null : $pais;

    $metodologia = trim($_POST['metodologia'] ?? '');
    $metodologia = ($metodologia === '') ? null : $metodologia;

    $perfilEgresado = trim($_POST['perfil_egresado'] ?? '');
    $perfilEgresado = ($perfilEgresado === '') ? null : $perfilEgresado;

    // Normalizar el booleano de institucion acreditada.
    $insAcreditadaRaw = $_POST['ins_acreditada'] ?? '';
    $insAcreditada = ($insAcreditadaRaw === '') ? null : (int)$insAcreditadaRaw;

    // Validaciones obligatorias y de formato para crear/actualizar.
    if (in_array($accionPost, ['crear', 'actualizar'], true)) {
        $docenteId = trim($_POST['docente'] ?? '');
        $titulo = trim($_POST['titulo'] ?? '');
        $universidad = trim($_POST['universidad'] ?? '');

        // Validar campos NOT NULL de la tabla (docente, titulo, universidad).
        $mensajeValidacion = null;
        if ($docenteId === '') {
            $mensajeValidacion = 'El docente es obligatorio.';
        } elseif ($titulo === '') {
            $mensajeValidacion = 'El titulo es obligatorio.';
        } elseif ($universidad === '') {
            $mensajeValidacion = 'La universidad es obligatoria.';
        }

        // Validaciones adicionales: campos marcados como obligatorios en la vista.
        if ($mensajeValidacion === null && $fecha === null) {
            $mensajeValidacion = 'La fecha es obligatoria.';
        } elseif ($mensajeValidacion === null && $tipo === null) {
            $mensajeValidacion = 'El tipo es obligatorio.';
        } elseif ($mensajeValidacion === null && $ciudad === null) {
            $mensajeValidacion = 'La ciudad es obligatoria.';
        } elseif ($mensajeValidacion === null && $pais === null) {
            $mensajeValidacion = 'El pais es obligatorio.';
        } elseif ($mensajeValidacion === null && $insAcreditada === null) {
            $mensajeValidacion = 'La institucion acreditada es obligatoria.';
        } elseif ($mensajeValidacion === null && $metodologia === null) {
            $mensajeValidacion = 'La metodologia es obligatoria.';
        } elseif ($mensajeValidacion === null && $perfilEgresado === null) {
            $mensajeValidacion = 'El perfil del egresado es obligatorio.';
        }

        // Validar formato de fecha cuando el campo se usa.
        if ($mensajeValidacion === null && $fecha !== null) {
            $fechaObj = DateTime::createFromFormat('Y-m-d', $fecha);
            $fechaValida = $fechaObj && $fechaObj->format('Y-m-d') === $fecha;
            if (!$fechaValida) {
                $mensajeValidacion = 'La fecha no es valida. Use AAAA-MM-DD.';
            }
        }

        // Validar que el booleano solo acepte 0 o 1 si esta presente.
        if ($mensajeValidacion === null && $insAcreditada !== null && !in_array($insAcreditada, [0, 1], true)) {
            $mensajeValidacion = 'La institucion acreditada debe ser Si o No.';
        }

        // Si hay error, redirigir al formulario correspondiente con mensaje.
        if ($mensajeValidacion !== null) {
            $_SESSION['mensaje'] = $mensajeValidacion;
            $_SESSION['tipo'] = 'danger';

            $redir = 'estudios_realizados.php?vista=formulario';
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

    // ── CREAR ESTUDIO ───────────────────────────────────────────────
    if ($accionPost === 'crear') {
        $datos = [
            'docente'        => trim($_POST['docente'] ?? ''),
            'titulo'         => trim($_POST['titulo'] ?? ''),
            'universidad'    => trim($_POST['universidad'] ?? ''),
            'fecha'          => $fecha,
            'tipo'           => $tipo,
            'ciudad'         => $ciudad,
            'pais'           => $pais,
            'ins_acreditada' => $insAcreditada,
            'metodologia'    => $metodologia,
            'perfil_egresado'=> $perfilEgresado
        ];

        $resultado = $api->crear('estudios_realizados', $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: estudios_realizados.php');
        exit;
    }

    // ── ACTUALIZAR ESTUDIO ───────────────────────────────────────────
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'docente'        => trim($_POST['docente'] ?? ''),
            'titulo'         => trim($_POST['titulo'] ?? ''),
            'universidad'    => trim($_POST['universidad'] ?? ''),
            'fecha'          => $fecha,
            'tipo'           => $tipo,
            'ciudad'         => $ciudad,
            'pais'           => $pais,
            'ins_acreditada' => $insAcreditada,
            'metodologia'    => $metodologia,
            'perfil_egresado'=> $perfilEgresado
        ];

        $resultado = $api->actualizar('estudios_realizados', 'id', $id, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: estudios_realizados.php');
        exit;
    }

    // ── ELIMINAR ESTUDIO ─────────────────────────────────────────────
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('estudios_realizados', 'id', $id);
        $_SESSION['mensaje'] = $resultado['exito'] ? 'Estudio eliminado exitosamente.' : 'Error: ' . $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: estudios_realizados.php');
        exit;
    }
}

// ============================================================================
// CARGAR DATOS SEGUN LA VISTA (se ejecuta en peticiones GET)
// ============================================================================
// Aca se preparan los datos que el HTML de abajo va a mostrar.

$estudios = [];              // Lista de estudios (para vista listar)
$estudio = null;             // Un estudio especifico (para vista ver/formulario)
$docentes = [];              // Lista de docentes (para dropdown)
$editando = false;           // true = editando estudio existente, false = creando nuevo

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

// ── VISTA LISTAR ───────────────────────────────────────────────────
if ($vista === 'listar') {
    $estudiosRaw = $api->listar('estudios_realizados');
    foreach ($estudiosRaw as $e) {
        $e['nombre_docente'] = $mapaDocentes[$e['docente'] ?? ''] ?? '';
        $estudios[] = $e;
    }
}

// ── VISTA VER (detalle de un estudio) ───────────────────────────────
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $estudioArr = $api->obtenerPorClave('estudios_realizados', 'id', $id);
    if (!empty($estudioArr)) {
        $estudio = $estudioArr[0];
        $estudio['nombre_docente'] = $mapaDocentes[$estudio['docente'] ?? ''] ?? '';
    }
}

// ── VISTA FORMULARIO (crear o editar) ───────────────────────────────
if ($vista === 'formulario') {
    $docentes = $docentesRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $estudioArr = $api->obtenerPorClave('estudios_realizados', 'id', $id);
        if (!empty($estudioArr)) {
            $estudio = $estudioArr[0];
        }
    }
}
?>

<!-- ======================================================================
     HTML — Lo que ve el usuario en el navegador
     A partir de aca es HTML con PHP mezclado para mostrar datos dinamicos.
     ====================================================================== -->

<div class="container mt-4">
    <h3>Estudios Realizados</h3>

    <!-- =============================================================== -->
    <!-- VISTA: LISTAR ESTUDIOS (tabla)                                  -->
    <!-- =============================================================== -->
    <?php if ($vista === 'listar'): ?>

        <a href="estudios_realizados.php?vista=formulario" class="btn btn-primary mb-3">Nuevo Estudio</a>

        <?php if (!empty($estudios)): ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Docente</th>
                        <th>Titulo</th>
                        <th>Universidad</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th>Ciudad</th>
                        <th>Pais</th>
                        <th>Ins. Acreditada</th>
                        <th>Fecha Creacion</th>
                        <th>Fecha Actualizacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($estudios as $e): ?>
                    <tr>
                        <td><?= $e['nombre_docente'] ?? '' ?></td>
                        <td><?= $e['titulo'] ?? '' ?></td>
                        <td><?= $e['universidad'] ?? '' ?></td>
                        <td><?= $e['fecha'] ?? '' ?></td>
                        <td><?= $e['tipo'] ?? '' ?></td>
                        <td><?= $e['ciudad'] ?? '' ?></td>
                        <td><?= $e['pais'] ?? '' ?></td>
                        <td>
                            <?php
                            $insValor = $e['ins_acreditada'] ?? null;
                            echo ($insValor === 1 || $insValor === '1') ? 'Si' : (($insValor === 0 || $insValor === '0') ? 'No' : '');
                            ?>
                        </td>
                        <td><?= $e['fecha_creacion'] ?? '' ?></td>
                        <td><?= $e['fecha_actualizacion'] ?? '' ?></td>
                        <td>
                            <a href="estudios_realizados.php?vista=ver&id=<?= $e['id'] ?? '' ?>" class="btn btn-info btn-sm me-1">Ver</a>
                            <a href="estudios_realizados.php?vista=formulario&editar=<?= $e['id'] ?? '' ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                            <form method="POST" action="estudios_realizados.php" style="display:inline"
                                  onsubmit="return confirm('Eliminar estudio realizado?')">
                                <input type="hidden" name="accion_post" value="eliminar" />
                                <input type="hidden" name="id" value="<?= $e['id'] ?? '' ?>" />
                                <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No se encontraron estudios realizados.</div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: VER DETALLE DE ESTUDIO (solo lectura)                    -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'ver'): ?>

        <a href="estudios_realizados.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <?php if ($estudio): ?>
            <div class="card mb-3">
                <div class="card-header"><strong>Estudio Realizado</strong></div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Docente:</strong> <?= $estudio['nombre_docente'] ?? '' ?></div>
                        <div class="col-md-6"><strong>Titulo:</strong> <?= $estudio['titulo'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Universidad:</strong> <?= $estudio['universidad'] ?? '' ?></div>
                        <div class="col-md-6"><strong>Fecha:</strong> <?= $estudio['fecha'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Tipo:</strong> <?= $estudio['tipo'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Ciudad:</strong> <?= $estudio['ciudad'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Pais:</strong> <?= $estudio['pais'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Ins. Acreditada:</strong>
                            <?php
                            $insValor = $estudio['ins_acreditada'] ?? null;
                            echo ($insValor === 1 || $insValor === '1') ? 'Si' : (($insValor === 0 || $insValor === '0') ? 'No' : '');
                            ?>
                        </div>
                        <div class="col-md-6"><strong>Metodologia:</strong> <?= $estudio['metodologia'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-12"><strong>Perfil Egresado:</strong> <?= $estudio['perfil_egresado'] ?? '' ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><strong>Fecha Creacion:</strong> <?= $estudio['fecha_creacion'] ?? '' ?></div>
                        <div class="col-md-6"><strong>Fecha Actualizacion:</strong> <?= $estudio['fecha_actualizacion'] ?? '' ?></div>
                    </div>
                </div>
            </div>

            <a href="estudios_realizados.php?vista=formulario&editar=<?= $estudio['id'] ?? '' ?>" class="btn btn-warning me-2">Editar</a>
            <form method="POST" action="estudios_realizados.php" style="display:inline"
                  onsubmit="return confirm('Eliminar estudio realizado?')">
                <input type="hidden" name="accion_post" value="eliminar" />
                <input type="hidden" name="id" value="<?= $estudio['id'] ?? '' ?>" />
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">Estudio no encontrado.</div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: FORMULARIO (CREAR / EDITAR)                              -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="estudios_realizados.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <div class="card mb-3">
            <div class="card-header">
                <?= $editando ? "Editar Estudio" : "Nuevo Estudio" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="estudios_realizados.php"
                      onsubmit="<?= $editando ? "return confirm('Esta seguro de actualizar este Estudio?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $estudio['id'] ?? '' ?>" />
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Docente</label>
                            <select class="form-select" name="docente" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($docentes as $doc): ?>
                                    <option value="<?= $doc['id'] ?? '' ?>"
                                        <?= ($editando && $estudio && ($estudio['docente'] ?? '') == ($doc['id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= $mapaDocentes[$doc['id'] ?? ''] ?? '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Titulo</label>
                            <input class="form-control" name="titulo" value="<?= $estudio['titulo'] ?? '' ?>" required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Universidad</label>
                            <input class="form-control" name="universidad" value="<?= $estudio['universidad'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fecha</label>
                            <!-- Campo obligatorio por requerimiento -->
                            <input class="form-control" type="date" name="fecha" value="<?= $estudio['fecha'] ?? '' ?>" required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <!-- Campo obligatorio por requerimiento -->
                            <input class="form-control" name="tipo" value="<?= $estudio['tipo'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ciudad</label>
                            <!-- Campo obligatorio por requerimiento -->
                            <?php $ciudadValor = (string)($estudio['ciudad'] ?? ''); ?>
                            <!-- Lista controlada de ciudades principales de Colombia -->
                            <select class="form-select" name="ciudad" required>
                                <option value="" <?= $ciudadValor === '' ? 'selected' : '' ?>>-- Seleccionar --</option>
                                <option value="Bogota" <?= $ciudadValor === 'Bogota' ? 'selected' : '' ?>>Bogota</option>
                                <option value="Medellin" <?= $ciudadValor === 'Medellin' ? 'selected' : '' ?>>Medellin</option>
                                <option value="Cali" <?= $ciudadValor === 'Cali' ? 'selected' : '' ?>>Cali</option>
                                <option value="Barranquilla" <?= $ciudadValor === 'Barranquilla' ? 'selected' : '' ?>>Barranquilla</option>
                                <option value="Cartagena" <?= $ciudadValor === 'Cartagena' ? 'selected' : '' ?>>Cartagena</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Pais</label>
                            <!-- Campo obligatorio por requerimiento -->
                            <?php $paisValor = (string)($estudio['pais'] ?? ''); ?>
                            <!-- Lista controlada para pais con seleccion consistente -->
                            <select class="form-select" name="pais" required>
                                <option value="" <?= $paisValor === '' ? 'selected' : '' ?>>-- Seleccionar --</option>
                                <option value="Colombia" <?= $paisValor === 'Colombia' ? 'selected' : '' ?>>Colombia</option>
                                <option value="Mexico" <?= $paisValor === 'Mexico' ? 'selected' : '' ?>>Mexico</option>
                                <option value="Espana" <?= $paisValor === 'Espana' ? 'selected' : '' ?>>Espana</option>
                                <option value="Alemania" <?= $paisValor === 'Alemania' ? 'selected' : '' ?>>Alemania</option>
                                <option value="Otra" <?= $paisValor === 'Otra' ? 'selected' : '' ?>>Otra</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Ins. Acreditada</label>
                            <?php $insValor = (string)($estudio['ins_acreditada'] ?? ''); ?>
                            <!-- Campo obligatorio por requerimiento -->
                            <select class="form-select" name="ins_acreditada" required>
                                <option value="" <?= $insValor === '' ? 'selected' : '' ?>>-- Seleccionar --</option>
                                <option value="1" <?= $insValor === '1' ? 'selected' : '' ?>>Si</option>
                                <option value="0" <?= $insValor === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Metodologia</label>
                            <input class="form-control" name="metodologia" value="<?= $estudio['metodologia'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Perfil Egresado</label>
                            <input class="form-control" name="perfil_egresado" value="<?= $estudio['perfil_egresado'] ?? '' ?>" required />
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-success me-2" type="submit">Guardar</button>
                        <a href="estudios_realizados.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
