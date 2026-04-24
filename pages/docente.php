<?php
// ============================================================================
// docente.php — CRUD para Docentes
// Ubicacion: pages/docente.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Maneja TODO lo relacionado con docentes: listar, ver detalle, crear, editar, eliminar.
//
// TRES VISTAS:
//   ?vista=listar           -> Tabla con todos los docentes
//   ?vista=ver&id=UUID      -> Detalle de un docente (solo lectura)
//   ?vista=formulario       -> Crear docente nuevo
//   ?vista=formulario&editar=UUID -> Editar docente existente
// ============================================================================

// Variables que header.php necesita para el sidebar y el titulo
$paginaActual = 'docente';
$tituloPagina = 'Docentes';
require __DIR__ . '/../includes/header.php';       // HTML comun (sidebar, mensajes flash)
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

    // Normalizar campos opcionales antes de enviar a la API.
    $fechaNacimiento = trim($_POST['fecha_nacimiento'] ?? '');
    $fechaNacimiento = ($fechaNacimiento === '') ? null : $fechaNacimiento;

    $lineaInvestigacion = trim($_POST['linea_investigacion_principal'] ?? '');
    $lineaInvestigacion = ($lineaInvestigacion === '') ? null : $lineaInvestigacion;

    // Validar y normalizar URL CVLAC para crear/actualizar.
    $urlCvlacNormalizada = '';
    if (in_array($accionPost, ['crear', 'actualizar'], true)) {
        $urlCvlacInput = trim($_POST['url_cvlac'] ?? '');
        $urlSinProtocolo = preg_replace('~^https?://~i', '', $urlCvlacInput);
        $urlSinProtocolo = rtrim($urlSinProtocolo ?? '', '/');

        $regexUrlCvlac = '~^www\.[a-z0-9-]+(\.[a-z0-9-]+)*\.[a-z]{2,24}$~i';
        $urlCvlacValida = ($urlSinProtocolo !== '') && preg_match($regexUrlCvlac, $urlSinProtocolo);

        if (!$urlCvlacValida) {
            $_SESSION['mensaje'] = 'La URL CVLAC debe iniciar con www y terminar con una extension valida.';
            $_SESSION['tipo'] = 'danger';

            $redir = 'docente.php?vista=formulario';
            if ($accionPost === 'actualizar') {
                $id = $_POST['id'] ?? '';
                if ($id !== '') {
                    $redir .= '&editar=' . urlencode($id);
                }
            }

            header('Location: ' . $redir);
            exit;
        }

        // Guardar siempre con protocolo https://
        $urlCvlacNormalizada = 'https://' . $urlSinProtocolo;
    }

    // ── CREAR DOCENTE ───────────────────────────────────────────────
    if ($accionPost === 'crear') {
        $datos = [
            'cedula'                        => $_POST['cedula'] ?? '',
            'nombres'                       => $_POST['nombres'] ?? '',
            'apellidos'                     => $_POST['apellidos'] ?? '',
            'genero'                        => $_POST['genero'] ?? '',
            'cargo'                         => $_POST['cargo'] ?? '',
            'fecha_nacimiento'              => $fechaNacimiento,
            'correo'                        => $_POST['correo'] ?? '',
            'telefono'                      => $_POST['telefono'] ?? '',
            'url_cvlac'                     => $urlCvlacNormalizada,
            'escalafon'                     => $_POST['escalafon'] ?? '',
            'perfil'                        => $_POST['perfil'] ?? '',
            'cat_minciencia'                => $_POST['cat_minciencia'] ?? '',
            'conv_minciencia'               => $_POST['conv_minciencia'] ?? '',
            'nacionalidad'                  => $_POST['nacionalidad'] ?? '',
            'linea_investigacion_principal' => $lineaInvestigacion
        ];

        $resultado = $api->crear('docente', $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: docente.php');
        exit;
    }

    // ── ACTUALIZAR DOCENTE ───────────────────────────────────────────
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'cedula'                        => $_POST['cedula'] ?? '',
            'nombres'                       => $_POST['nombres'] ?? '',
            'apellidos'                     => $_POST['apellidos'] ?? '',
            'genero'                        => $_POST['genero'] ?? '',
            'cargo'                         => $_POST['cargo'] ?? '',
            'fecha_nacimiento'              => $fechaNacimiento,
            'correo'                        => $_POST['correo'] ?? '',
            'telefono'                      => $_POST['telefono'] ?? '',
            'url_cvlac'                     => $urlCvlacNormalizada,
            'escalafon'                     => $_POST['escalafon'] ?? '',
            'perfil'                        => $_POST['perfil'] ?? '',
            'cat_minciencia'                => $_POST['cat_minciencia'] ?? '',
            'conv_minciencia'               => $_POST['conv_minciencia'] ?? '',
            'nacionalidad'                  => $_POST['nacionalidad'] ?? '',
            'linea_investigacion_principal' => $lineaInvestigacion
        ];

        $resultado = $api->actualizar('docente', 'id', $id, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: docente.php');
        exit;
    }

    // ── ELIMINAR DOCENTE ─────────────────────────────────────────────
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('docente', 'id', $id);
        $_SESSION['mensaje'] = $resultado['exito'] ? 'Docente eliminado exitosamente.' : 'Error: ' . $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: docente.php');
        exit;
    }
}

// ============================================================================
// CARGAR DATOS SEGUN LA VISTA (se ejecuta en peticiones GET)
// ============================================================================
// Aca se preparan los datos que el HTML de abajo va a mostrar.

$docentes = [];               // Lista de docentes (para vista listar)
$docente = null;              // Un docente especifico (para vista ver/formulario)
$lineas = [];                 // Lista de lineas de investigacion (para dropdown)
$editando = false;            // true = editando docente existente, false = creando nuevo

// Mapa de lineas: id_linea => nombre
$lineasRaw = $api->listar('linea_investigacion');
$mapaLineas = [];
foreach ($lineasRaw as $li) { $mapaLineas[$li['id'] ?? ''] = $li['nombre'] ?? ''; }

// ── VISTA LISTAR ───────────────────────────────────────────────────
if ($vista === 'listar') {
    $docentesRaw = $api->listar('docente');
    foreach ($docentesRaw as $d) {
        $d['nombre_linea'] = $mapaLineas[$d['linea_investigacion_principal'] ?? ''] ?? '';
        $docentes[] = $d;
    }
}

// ── VISTA VER (detalle de un docente) ───────────────────────────────
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $docenteArr = $api->obtenerPorClave('docente', 'id', $id);
    if (!empty($docenteArr)) {
        $docente = $docenteArr[0];
        $docente['nombre_linea'] = $mapaLineas[$docente['linea_investigacion_principal'] ?? ''] ?? '';
    }
}

// ── VISTA FORMULARIO (crear o editar) ───────────────────────────────
if ($vista === 'formulario') {
    $lineas = $lineasRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $docenteArr = $api->obtenerPorClave('docente', 'id', $id);
        if (!empty($docenteArr)) {
            $docente = $docenteArr[0];
        }
    }
}
?>

<!-- ======================================================================
     HTML — Lo que ve el usuario en el navegador
     A partir de aca es HTML con PHP mezclado para mostrar datos dinamicos.
     ====================================================================== -->

<div class="container mt-4">
    <h3>Docentes</h3>

    <!-- =============================================================== -->
    <!-- VISTA: LISTAR DOCENTES (tabla)                                  -->
    <!-- =============================================================== -->
    <?php if ($vista === 'listar'): ?>

        <a href="docente.php?vista=formulario" class="btn btn-primary mb-3">Nuevo Docente</a>

        <?php if (!empty($docentes)): ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Cedula</th>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Correo</th>
                        <th>Telefono</th>
                        <th>Linea</th>
                        <th>Fecha Creacion</th>
                        <th>Fecha Actualizacion</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($docentes as $doc): ?>
                    <tr>
                        <td><?= $doc['cedula'] ?? '' ?></td>
                        <td><?= $doc['nombres'] ?? '' ?></td>
                        <td><?= $doc['apellidos'] ?? '' ?></td>
                        <td><?= $doc['correo'] ?? '' ?></td>
                        <td><?= $doc['telefono'] ?? '' ?></td>
                        <td><?= $doc['nombre_linea'] ?? '' ?></td>
                        <td><?= $doc['fecha_creacion'] ?? '' ?></td>
                        <td><?= $doc['fecha_actualizacion'] ?? '' ?></td>
                        <td>
                            <a href="docente.php?vista=ver&id=<?= $doc['id'] ?? '' ?>" class="btn btn-info btn-sm me-1">Ver</a>
                            <a href="docente.php?vista=formulario&editar=<?= $doc['id'] ?? '' ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                            <form method="POST" action="docente.php" style="display:inline"
                                  onsubmit="return confirm('Eliminar docente con cedula <?= $doc['cedula'] ?? '' ?>?')">
                                <input type="hidden" name="accion_post" value="eliminar" />
                                <input type="hidden" name="id" value="<?= $doc['id'] ?? '' ?>" />
                                <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-warning">No se encontraron docentes.</div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: VER DETALLE DE DOCENTE (solo lectura)                     -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'ver'): ?>

        <a href="docente.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <?php if ($docente): ?>
            <div class="card mb-3">
                <div class="card-header"><strong>Docente</strong></div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Cedula:</strong> <?= $docente['cedula'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Nombres:</strong> <?= $docente['nombres'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Apellidos:</strong> <?= $docente['apellidos'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Genero:</strong> <?= $docente['genero'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Cargo:</strong> <?= $docente['cargo'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Fecha Nacimiento:</strong> <?= $docente['fecha_nacimiento'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Correo:</strong> <?= $docente['correo'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Telefono:</strong> <?= $docente['telefono'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Nacionalidad:</strong> <?= $docente['nacionalidad'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-4"><strong>Escalafon:</strong> <?= $docente['escalafon'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Categoria Minciencia:</strong> <?= $docente['cat_minciencia'] ?? '' ?></div>
                        <div class="col-md-4"><strong>Convocatoria Minciencia:</strong> <?= $docente['conv_minciencia'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-6"><strong>Linea Investigacion:</strong> <?= $docente['nombre_linea'] ?? '' ?></div>
                        <div class="col-md-6"><strong>URL CVLAC:</strong> <?= $docente['url_cvlac'] ?? '' ?></div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-md-12"><strong>Perfil:</strong> <?= $docente['perfil'] ?? '' ?></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><strong>Fecha Creacion:</strong> <?= $docente['fecha_creacion'] ?? '' ?></div>
                        <div class="col-md-6"><strong>Fecha Actualizacion:</strong> <?= $docente['fecha_actualizacion'] ?? '' ?></div>
                    </div>
                </div>
            </div>

            <a href="docente.php?vista=formulario&editar=<?= $docente['id'] ?? '' ?>" class="btn btn-warning me-2">Editar</a>
            <form method="POST" action="docente.php" style="display:inline"
                  onsubmit="return confirm('Eliminar docente con cedula <?= $docente['cedula'] ?? '' ?>?')">
                <input type="hidden" name="accion_post" value="eliminar" />
                <input type="hidden" name="id" value="<?= $docente['id'] ?? '' ?>" />
                <button class="btn btn-danger" type="submit">Eliminar</button>
            </form>
        <?php else: ?>
            <div class="alert alert-danger">Docente no encontrado.</div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: FORMULARIO (CREAR / EDITAR)                              -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="docente.php" class="btn btn-secondary mb-3">Volver al listado</a>

        <div class="card mb-3">
            <div class="card-header">
                <?= $editando ? "Editar Docente" : "Nuevo Docente" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="docente.php"
                      onsubmit="<?= $editando ? "return confirm('Esta seguro de actualizar este Docente?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $docente['id'] ?? '' ?>" />
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cedula</label>
                            <input class="form-control" name="cedula" value="<?= $docente['cedula'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombres</label>
                            <input class="form-control" name="nombres" value="<?= $docente['nombres'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellidos</label>
                            <input class="form-control" name="apellidos" value="<?= $docente['apellidos'] ?? '' ?>" required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Genero</label>
                            <select class="form-select" name="genero" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="Masculino" <?= ($docente && ($docente['genero'] ?? '') === 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                <option value="Femenino" <?= ($docente && ($docente['genero'] ?? '') === 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                                <option value="Otro" <?= ($docente && ($docente['genero'] ?? '') === 'Otro') ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cargo</label>
                            <input class="form-control" name="cargo" value="<?= $docente['cargo'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha Nacimiento</label>
                            <input class="form-control" type="date" name="fecha_nacimiento"
                                   value="<?= $docente['fecha_nacimiento'] ?? '' ?>" required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Correo</label>
                            <input class="form-control" type="email" name="correo" value="<?= $docente['correo'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefono</label>
                            <input class="form-control" name="telefono" value="<?= $docente['telefono'] ?? '' ?>" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad</label>
                            <select class="form-select" name="nacionalidad" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="Colombiana" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Colombiana') ? 'selected' : '' ?>>Colombiana</option>
                                <option value="Mexicana" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Mexicana') ? 'selected' : '' ?>>Méxicana</option>
                                <option value="Española" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Española') ? 'selected' : '' ?>>Española</option>
                                <option value="Alemania" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Alemania') ? 'selected' : '' ?>>Alemana</option>
                                <option value="Otra" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Otra') ? 'selected' : '' ?>>Otra</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Escalafon</label>
                            <input class="form-control" name="escalafon" value="<?= $docente['escalafon'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Categoria Minciencia</label>
                            <input class="form-control" name="cat_minciencia" value="<?= $docente['cat_minciencia'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Convocatoria Minciencia</label>
                            <input class="form-control" name="conv_minciencia" value="<?= $docente['conv_minciencia'] ?? '' ?>" required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Linea Investigacion Principal</label>
                            <select class="form-select" name="linea_investigacion_principal" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($lineas as $li): ?>
                                    <option value="<?= $li['id'] ?? '' ?>"
                                        <?= ($editando && $docente && ($docente['linea_investigacion_principal'] ?? '') == ($li['id'] ?? '')) ? 'selected' : '' ?>>
                                        <?= $li['nombre'] ?? '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">URL CVLAC</label>
                            <input class="form-control" type="text" name="url_cvlac"
                                   value="<?= $docente['url_cvlac'] ?? '' ?>"
                                   pattern="^(https?://)?www\.[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,24}$"
                                   title="Use www.ejemplo.com"
                                   required />
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Perfil</label>
                            <textarea class="form-control" name="perfil" rows="3"><?= $docente['perfil'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-success me-2" type="submit">Guardar</button>
                        <a href="docente.php" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
