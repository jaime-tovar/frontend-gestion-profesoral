<?php
$paginaActual = 'red_docente';
$tituloPagina = 'Red Docente';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$vista = $_GET['vista'] ?? 'listar';

// =============================
// PROCESAR POST
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accionPost = $_POST['accion_post'] ?? '';

    $redId = $_POST['red'] ?? '';
    $docenteId = $_POST['docente'] ?? '';
    $fechaInicio = trim($_POST['fecha_inicio'] ?? '');
    $fechaFin = trim($_POST['fecha_fin'] ?? '');
    $actDestacadas = trim($_POST['act_destacadas'] ?? '');

    $fechaInicio = ($fechaInicio === '') ? null : $fechaInicio;
    $fechaFin = ($fechaFin === '') ? null : $fechaFin;
    $actDestacadas = ($actDestacadas === '') ? null : $actDestacadas;

    if ($redId === '' || $docenteId === '') {
        $_SESSION['mensaje'] = 'Red y docente son obligatorios.';
        $_SESSION['tipo'] = 'danger';
        header('Location: red_docente.php?vista=formulario');
        exit;
    }

    // Validación lógica de fechas
    if ($fechaInicio && $fechaFin && $fechaFin < $fechaInicio) {
        $_SESSION['mensaje'] = 'La fecha fin no puede ser menor que la fecha inicio.';
        $_SESSION['tipo'] = 'danger';
        header('Location: red_docente.php?vista=formulario');
        exit;
    }

    // ── CREAR ──
    if ($accionPost === 'crear') {
        $datos = [
            'red'             => $redId,
            'docente'         => $docenteId,
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => $fechaFin,
            'act_destacadas'  => $actDestacadas
        ];

        $resultado = $api->crear('red_docente', $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: red_docente.php');
        exit;
    }

    // ── ACTUALIZAR ──
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'red'             => $redId,
            'docente'         => $docenteId,
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => $fechaFin,
            'act_destacadas'  => $actDestacadas
        ];

        $resultado = $api->actualizar('red_docente', 'id', $id, $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: red_docente.php');
        exit;
    }

    // ── ELIMINAR ──
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('red_docente', 'id', $id);

        $_SESSION['mensaje'] = $resultado['exito']
            ? 'Relación eliminada.'
            : 'Error: ' . $resultado['mensaje'];

        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: red_docente.php');
        exit;
    }
}

// =============================
// CARGA DE DATOS
// =============================
$registros = [];
$registro = null;
$docentes = [];
$redes = [];
$mapaDocentes = [];
$mapaRedes = [];
$editando = false;

// Docentes
$docentesRaw = $api->listar('docente');
foreach ($docentesRaw as $d) {
    $mapaDocentes[$d['id']] = $d['nombres'] . ' ' . $d['apellidos'];
}

// Redes
$redesRaw = $api->listar('red');
foreach ($redesRaw as $r) {
    $mapaRedes[$r['id']] = $r['nombre'] ?? $r['id'];
}

// ── LISTAR ──
if ($vista === 'listar') {
    $raw = $api->listar('red_docente');

    foreach ($raw as $r) {
        $r['nombre_docente'] = $mapaDocentes[$r['docente']] ?? '';
        $r['nombre_red'] = $mapaRedes[$r['red']] ?? '';
        $registros[] = $r;
    }
}

// ── VER ──
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $arr = $api->obtenerPorClave('red_docente', 'id', $id);

    if (!empty($arr)) {
        $registro = $arr[0];
        $registro['nombre_docente'] = $mapaDocentes[$registro['docente']] ?? '';
        $registro['nombre_red'] = $mapaRedes[$registro['red']] ?? '';
    }
}

// ── FORMULARIO ──
if ($vista === 'formulario') {
    $docentes = $docentesRaw;
    $redes = $redesRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $arr = $api->obtenerPorClave('red_docente', 'id', $id);

        if (!empty($arr)) {
            $registro = $arr[0];
        }
    }
}
?>

<div class="container mt-4">
    <h3>Red Docente</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="red_docente.php?vista=formulario" class="btn btn-primary mb-3">
            Nueva Relación
        </a>

        <?php if (!empty($registros)): ?>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Docente</th>
                        <th>Red</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registros as $r): ?>
                        <tr>
                            <td><?= $r['nombre_docente'] ?></td>
                            <td><?= $r['nombre_red'] ?></td>
                            <td><?= $r['fecha_inicio'] ?></td>
                            <td><?= $r['fecha_fin'] ?></td>
                            <td>
                                <a href="red_docente.php?vista=ver&id=<?= $r['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                                <a href="red_docente.php?vista=formulario&editar=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Editar</a>

                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar relación?')">
                                    <input type="hidden" name="accion_post" value="eliminar">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
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

        <a href="red_docente.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if ($registro): ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Docente:</strong> <?= $registro['nombre_docente'] ?></p>
                    <p><strong>Red:</strong> <?= $registro['nombre_red'] ?></p>
                    <p><strong>Fecha Inicio:</strong> <?= $registro['fecha_inicio'] ?></p>
                    <p><strong>Fecha Fin:</strong> <?= $registro['fecha_fin'] ?></p>
                    <p><strong>Actividades:</strong> <?= $registro['act_destacadas'] ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="red_docente.php" class="btn btn-secondary mb-3">Volver</a>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Docente</label>
                        <select name="docente" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= ($registro && $registro['docente'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= $d['nombres'] . ' ' . $d['apellidos'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Red</label>
                        <select name="red" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($redes as $r): ?>
                                <option value="<?= $r['id'] ?>"
                                    <?= ($registro && $registro['red'] == $r['id']) ? 'selected' : '' ?>>
                                    <?= $r['nombre'] ?? $r['id'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" class="form-control"
                               value="<?= $registro['fecha_inicio'] ?? '' ?>">
                    </div>

                    <div class="mb-3">
                        <label>Fecha Fin</label>
                        <input type="date" name="fecha_fin" class="form-control"
                               value="<?= $registro['fecha_fin'] ?? '' ?>">
                    </div>

                    <div class="mb-3">
                        <label>Actividades Destacadas</label>
                        <textarea name="act_destacadas" class="form-control"><?= $registro['act_destacadas'] ?? '' ?></textarea>
                    </div>

                    <button class="btn btn-success">Guardar</button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>