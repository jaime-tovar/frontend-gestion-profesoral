<?php
$paginaActual = 'experiencia';
$tituloPagina = 'Experiencia';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$vista = $_GET['vista'] ?? 'listar';

// =============================
// PROCESAR POST
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accionPost = $_POST['accion_post'] ?? '';

    $nombreCargo = $_POST['nombre_cargo'] ?? '';
    $institucion = $_POST['institucion'] ?? '';
    $tipo = trim($_POST['tipo'] ?? '');
    $fechaInicio = $_POST['fecha_inicio'] ?? '';
    $fechaFin = trim($_POST['fecha_fin'] ?? '');
    $docenteId = $_POST['docente'] ?? '';

    $tipo = ($tipo === '') ? null : $tipo;
    $fechaFin = ($fechaFin === '') ? null : $fechaFin;

    // Validación
    if ($nombreCargo === '' || $institucion === '' || $fechaInicio === '' || $docenteId === '') {
        $_SESSION['mensaje'] = 'Todos los campos obligatorios deben ser llenados.';
        $_SESSION['tipo'] = 'danger';
        header('Location: experiencia.php?vista=formulario');
        exit;
    }

    // ── CREAR ──
    if ($accionPost === 'crear') {
        $datos = [
            'nombre_cargo' => $nombreCargo,
            'institucion'  => $institucion,
            'tipo'         => $tipo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'docente'      => $docenteId
        ];

        $resultado = $api->crear('experiencia', $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: experiencia.php');
        exit;
    }

    // ── ACTUALIZAR ──
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'nombre_cargo' => $nombreCargo,
            'institucion'  => $institucion,
            'tipo'         => $tipo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'docente'      => $docenteId
        ];

        $resultado = $api->actualizar('experiencia', 'id', $id, $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: experiencia.php');
        exit;
    }

    // ── ELIMINAR ──
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('experiencia', 'id', $id);

        $_SESSION['mensaje'] = $resultado['exito']
            ? 'Experiencia eliminada.'
            : 'Error: ' . $resultado['mensaje'];

        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: experiencia.php');
        exit;
    }
}

// =============================
// CARGA DE DATOS
// =============================
$experiencias = [];
$experiencia = null;
$docentes = [];
$mapaDocentes = [];
$editando = false;

// Docentes (FK)
$docentesRaw = $api->listar('docente');
foreach ($docentesRaw as $d) {
    $mapaDocentes[$d['id']] = $d['nombres'] . ' ' . $d['apellidos'];
}

// ── LISTAR ──
if ($vista === 'listar') {
    $raw = $api->listar('experiencia');

    foreach ($raw as $e) {
        $e['nombre_docente'] = $mapaDocentes[$e['docente']] ?? '';
        $experiencias[] = $e;
    }
}

// ── VER ──
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $arr = $api->obtenerPorClave('experiencia', 'id', $id);

    if (!empty($arr)) {
        $experiencia = $arr[0];
        $experiencia['nombre_docente'] = $mapaDocentes[$experiencia['docente']] ?? '';
    }
}

// ── FORMULARIO ──
if ($vista === 'formulario') {
    $docentes = $docentesRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $arr = $api->obtenerPorClave('experiencia', 'id', $id);

        if (!empty($arr)) {
            $experiencia = $arr[0];
        }
    }
}
?>

<div class="container mt-4">
    <h3>Experiencia</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="experiencia.php?vista=formulario" class="btn btn-primary mb-3">
            Nueva Experiencia
        </a>

        <?php if (!empty($experiencias)): ?>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Docente</th>
                        <th>Cargo</th>
                        <th>Institución</th>
                        <th>Tipo</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($experiencias as $e): ?>
                        <tr>
                            <td><?= $e['nombre_docente'] ?></td>
                            <td><?= $e['nombre_cargo'] ?></td>
                            <td><?= $e['institucion'] ?></td>
                            <td><?= $e['tipo'] ?></td>
                            <td><?= $e['fecha_inicio'] ?></td>
                            <td><?= $e['fecha_fin'] ?></td>
                            <td>
                                <a href="experiencia.php?vista=ver&id=<?= $e['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                                <a href="experiencia.php?vista=formulario&editar=<?= $e['id'] ?>" class="btn btn-warning btn-sm">Editar</a>

                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar experiencia?')">
                                    <input type="hidden" name="accion_post" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button class="btn btn-danger btn-sm">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <!-- VER -->
    <?php elseif ($vista === 'ver'): ?>

        <a href="experiencia.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if ($experiencia): ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Docente:</strong> <?= $experiencia['nombre_docente'] ?></p>
                    <p><strong>Cargo:</strong> <?= $experiencia['nombre_cargo'] ?></p>
                    <p><strong>Institución:</strong> <?= $experiencia['institucion'] ?></p>
                    <p><strong>Tipo:</strong> <?= $experiencia['tipo'] ?></p>
                    <p><strong>Fecha Inicio:</strong> <?= $experiencia['fecha_inicio'] ?></p>
                    <p><strong>Fecha Fin:</strong> <?= $experiencia['fecha_fin'] ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="experiencia.php" class="btn btn-secondary mb-3">Volver</a>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $experiencia['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Docente</label>
                        <select name="docente" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= ($experiencia && $experiencia['docente'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= $d['nombres'] . ' ' . $d['apellidos'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Cargo</label>
                        <input name="nombre_cargo" class="form-control"
                               value="<?= $experiencia['nombre_cargo'] ?? '' ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Institución</label>
                        <input name="institucion" class="form-control"
                               value="<?= $experiencia['institucion'] ?? '' ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Tipo</label>
                        <input name="tipo" class="form-control"
                               value="<?= $experiencia['tipo'] ?? '' ?>">
                    </div>

                    <div class="mb-3">
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control"
                               value="<?= $experiencia['fecha_inicio'] ?? '' ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control"
                               value="<?= $experiencia['fecha_fin'] ?? '' ?>">
                    </div>

                    <button class="btn btn-success">Guardar</button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>