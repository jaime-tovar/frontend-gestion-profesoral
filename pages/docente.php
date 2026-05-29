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
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h3 class="mb-1"><i class="bi bi-person-badge me-2"></i>Docentes</h3>
            <p class="text-muted mb-0">Gestionar el registro de docentes de la institucion</p>
        </div>
        <?php if ($vista === 'listar'): ?>
            <a href="docente.php?vista=formulario" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Docente
            </a>
        <?php endif; ?>
    </div>

    <!-- =============================================================== -->
    <!-- VISTA: LISTAR DOCENTES (tabla)                                  -->
    <!-- =============================================================== -->
    <?php if ($vista === 'listar'): ?>

        <?php if (!empty($docentes)): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-table me-2"></i>Lista de Docentes</span>
                    <span class="badge" style="background-color: var(--primary-800);"><?= count($docentes) ?> registros</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Cedula</th>
                                    <th>Nombre Completo</th>
                                    <th>Correo</th>
                                    <th>Telefono</th>
                                    <th>Linea de Investigacion</th>
                                    <th style="width: 180px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($docentes as $doc): ?>
                                <tr>
                                    <td class="fw-medium"><?= $doc['cedula'] ?? '' ?></td>
                                    <td><?= ($doc['nombres'] ?? '') . ' ' . ($doc['apellidos'] ?? '') ?></td>
                                    <td>
                                        <a href="mailto:<?= $doc['correo'] ?? '' ?>" class="text-decoration-none" style="color: var(--accent-600);">
                                            <?= $doc['correo'] ?? '' ?>
                                        </a>
                                    </td>
                                    <td><?= $doc['telefono'] ?? '' ?></td>
                                    <td><span class="badge" style="background-color: var(--secondary-200); color: var(--primary-700);"><?= $doc['nombre_linea'] ?? '-' ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="docente.php?vista=ver&id=<?= $doc['id'] ?? '' ?>" class="btn btn-info btn-sm" title="Ver detalle">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="docente.php?vista=formulario&editar=<?= $doc['id'] ?? '' ?>" class="btn btn-warning btn-sm" title="Editar">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form method="POST" action="docente.php"
                                                  onsubmit="return confirm('Eliminar docente con cedula <?= $doc['cedula'] ?? '' ?>?')">
                                                <input type="hidden" name="accion_post" value="eliminar" />
                                                <input type="hidden" name="id" value="<?= $doc['id'] ?? '' ?>" />
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
                    <i class="bi bi-people" style="font-size: 3rem;"></i>
                </div>
                <div class="empty-state-title">No hay docentes registrados</div>
                <div class="empty-state-description">Comience agregando un nuevo docente al sistema.</div>
                <a href="docente.php?vista=formulario" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i> Nuevo Docente
                </a>
            </div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: VER DETALLE DE DOCENTE (solo lectura)                     -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'ver'): ?>

        <a href="docente.php" class="btn btn-secondary mb-4">
            <i class="bi bi-arrow-left me-1"></i> Volver al listado
        </a>

        <?php if ($docente): ?>
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center">
                    <i class="bi bi-person-circle me-2"></i>
                    <span>Informacion del Docente</span>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="detail-label">Cedula</div>
                            <div class="detail-value"><?= $docente['cedula'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Nombres</div>
                            <div class="detail-value"><?= $docente['nombres'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Apellidos</div>
                            <div class="detail-value"><?= $docente['apellidos'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Genero</div>
                            <div class="detail-value"><?= $docente['genero'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Cargo</div>
                            <div class="detail-value"><?= $docente['cargo'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Fecha de Nacimiento</div>
                            <div class="detail-value"><?= $docente['fecha_nacimiento'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Correo Electronico</div>
                            <div class="detail-value">
                                <a href="mailto:<?= $docente['correo'] ?? '' ?>" class="text-decoration-none" style="color: var(--accent-600);">
                                    <?= $docente['correo'] ?? '' ?>
                                </a>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Telefono</div>
                            <div class="detail-value"><?= $docente['telefono'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Nacionalidad</div>
                            <div class="detail-value"><?= $docente['nacionalidad'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Escalafon</div>
                            <div class="detail-value"><?= $docente['escalafon'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Categoria Minciencia</div>
                            <div class="detail-value"><?= $docente['cat_minciencia'] ?? '' ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="detail-label">Convocatoria Minciencia</div>
                            <div class="detail-value"><?= $docente['conv_minciencia'] ?? '' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Linea de Investigacion</div>
                            <div class="detail-value">
                                <span class="badge" style="background-color: var(--accent-100); color: var(--accent-600);">
                                    <?= $docente['nombre_linea'] ?? '-' ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">URL CVLAC</div>
                            <div class="detail-value">
                                <?php if (!empty($docente['url_cvlac'])): ?>
                                    <a href="<?= $docente['url_cvlac'] ?>" target="_blank" class="text-decoration-none" style="color: var(--accent-600);">
                                        <?= $docente['url_cvlac'] ?>
                                        <i class="bi bi-box-arrow-up-right ms-1"></i>
                                    </a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Perfil</div>
                            <div class="detail-value"><?= $docente['perfil'] ?? '-' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Fecha de Creacion</div>
                            <div class="detail-value text-muted"><?= $docente['fecha_creacion'] ?? '' ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-label">Ultima Actualizacion</div>
                            <div class="detail-value text-muted"><?= $docente['fecha_actualizacion'] ?? '' ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <a href="docente.php?vista=formulario&editar=<?= $docente['id'] ?? '' ?>" class="btn btn-warning">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
                <form method="POST" action="docente.php"
                      onsubmit="return confirm('Eliminar docente con cedula <?= $docente['cedula'] ?? '' ?>?')">
                    <input type="hidden" name="accion_post" value="eliminar" />
                    <input type="hidden" name="id" value="<?= $docente['id'] ?? '' ?>" />
                    <button class="btn btn-danger" type="submit">
                        <i class="bi bi-trash me-1"></i> Eliminar
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Docente no encontrado.
            </div>
        <?php endif; ?>

    <!-- =============================================================== -->
    <!-- VISTA: FORMULARIO (CREAR / EDITAR)                              -->
    <!-- =============================================================== -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="docente.php" class="btn btn-secondary mb-4">
            <i class="bi bi-arrow-left me-1"></i> Volver al listado
        </a>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-<?= $editando ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
                <?= $editando ? "Editar Docente" : "Nuevo Docente" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="docente.php"
                      onsubmit="<?= $editando ? "return confirm('Esta seguro de actualizar este Docente?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $docente['id'] ?? '' ?>" />
                    <?php endif; ?>

                    <!-- Seccion: Datos Personales -->
                    <h6 class="text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-person me-2"></i>Datos Personales
                    </h6>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cedula <span class="text-danger">*</span></label>
                            <input class="form-control" name="cedula" value="<?= $docente['cedula'] ?? '' ?>" placeholder="Numero de cedula" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input class="form-control" name="nombres" value="<?= $docente['nombres'] ?? '' ?>" placeholder="Nombres completos" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input class="form-control" name="apellidos" value="<?= $docente['apellidos'] ?? '' ?>" placeholder="Apellidos completos" required />
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Genero <span class="text-danger">*</span></label>
                            <select class="form-select" name="genero" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="Masculino" <?= ($docente && ($docente['genero'] ?? '') === 'Masculino') ? 'selected' : '' ?>>Masculino</option>
                                <option value="Femenino" <?= ($docente && ($docente['genero'] ?? '') === 'Femenino') ? 'selected' : '' ?>>Femenino</option>
                                <option value="Otro" <?= ($docente && ($docente['genero'] ?? '') === 'Otro') ? 'selected' : '' ?>>Otro</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha de Nacimiento <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" name="fecha_nacimiento"
                                   value="<?= $docente['fecha_nacimiento'] ?? '' ?>" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nacionalidad <span class="text-danger">*</span></label>
                            <select class="form-select" name="nacionalidad" required>
                                <option value="">-- Seleccionar --</option>
                                <option value="Colombiana" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Colombiana') ? 'selected' : '' ?>>Colombiana</option>
                                <option value="Mexicana" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Mexicana') ? 'selected' : '' ?>>Mexicana</option>
                                <option value="Española" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Española') ? 'selected' : '' ?>>Española</option>
                                <option value="Alemania" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Alemania') ? 'selected' : '' ?>>Alemana</option>
                                <option value="Otra" <?= ($docente && ($docente['nacionalidad'] ?? '') === 'Otra') ? 'selected' : '' ?>>Otra</option>
                            </select>
                        </div>
                    </div>

                    <!-- Seccion: Contacto -->
                    <h6 class="text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-envelope me-2"></i>Informacion de Contacto
                    </h6>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Correo Electronico <span class="text-danger">*</span></label>
                            <input class="form-control" type="email" name="correo" value="<?= $docente['correo'] ?? '' ?>" placeholder="correo@ejemplo.com" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Telefono</label>
                            <input class="form-control" name="telefono" value="<?= $docente['telefono'] ?? '' ?>" placeholder="Numero de telefono" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">URL CVLAC <span class="text-danger">*</span></label>
                            <input class="form-control" type="text" name="url_cvlac"
                                   value="<?= $docente['url_cvlac'] ?? '' ?>"
                                   pattern="^(https?://)?www\.[A-Za-z0-9-]+(\.[A-Za-z0-9-]+)*\.[A-Za-z]{2,24}$"
                                   title="Use www.ejemplo.com"
                                   placeholder="www.cvlac.scienti.gov.co/..."
                                   required />
                        </div>
                    </div>

                    <!-- Seccion: Informacion Academica -->
                    <h6 class="text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-mortarboard me-2"></i>Informacion Academica
                    </h6>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Cargo <span class="text-danger">*</span></label>
                            <input class="form-control" name="cargo" value="<?= $docente['cargo'] ?? '' ?>" placeholder="Cargo actual" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Escalafon <span class="text-danger">*</span></label>
                            <input class="form-control" name="escalafon" value="<?= $docente['escalafon'] ?? '' ?>" placeholder="Nivel de escalafon" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Linea de Investigacion <span class="text-danger">*</span></label>
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
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Categoria Minciencia <span class="text-danger">*</span></label>
                            <input class="form-control" name="cat_minciencia" value="<?= $docente['cat_minciencia'] ?? '' ?>" placeholder="Categoria" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Convocatoria Minciencia <span class="text-danger">*</span></label>
                            <input class="form-control" name="conv_minciencia" value="<?= $docente['conv_minciencia'] ?? '' ?>" placeholder="Numero de convocatoria" required />
                        </div>
                    </div>

                    <!-- Seccion: Perfil -->
                    <h6 class="text-muted mb-3 border-bottom pb-2">
                        <i class="bi bi-file-text me-2"></i>Perfil Profesional
                    </h6>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label">Descripcion del Perfil</label>
                            <textarea class="form-control" name="perfil" rows="4" placeholder="Descripcion breve del perfil profesional del docente..."><?= $docente['perfil'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-check-lg me-1"></i> Guardar
                        </button>
                        <a href="docente.php" class="btn btn-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
