<?php
$paginaActual = 'evaluacion_docente';
$tituloPagina = 'Evaluación Docente';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$vista = $_GET['vista'] ?? 'listar';

// =============================
// PROCESAR POST
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accionPost = $_POST['accion_post'] ?? '';

    $calificacion = $_POST['calificacion'] ?? '';
    $semestre = $_POST['semestre'] ?? '';
    $docenteId = $_POST['docente'] ?? '';

    // Validación básica
    if ($calificacion === '' || $semestre === '' || $docenteId === '') {
        $_SESSION['mensaje'] = 'Todos los campos son obligatorios.';
        $_SESSION['tipo'] = 'danger';
        header('Location: evaluacion_docente.php?vista=formulario');
        exit;
    }

    // ── CREAR ──
    if ($accionPost === 'crear') {
        $datos = [
            'calificacion' => $calificacion,
            'semestre'     => $semestre,
            'docente'      => $docenteId
        ];

        $resultado = $api->crear('evaluacion_docente', $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: evaluacion_docente.php');
        exit;
    }

    // ── ACTUALIZAR ──
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'calificacion' => $calificacion,
            'semestre'     => $semestre,
            'docente'      => $docenteId
        ];

        $resultado = $api->actualizar('evaluacion_docente', 'id', $id, $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: evaluacion_docente.php');
        exit;
    }

    // ── ELIMINAR ──
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('evaluacion_docente', 'id', $id);

        $_SESSION['mensaje'] = $resultado['exito']
            ? 'Evaluación eliminada.'
            : 'Error: ' . $resultado['mensaje'];

        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: evaluacion_docente.php');
        exit;
    }
}

// =============================
// CARGA DE DATOS
// =============================
$evaluaciones = [];
$evaluacion = null;
$docentes = [];
$mapaDocentes = [];
$editando = false;

// Cargar docentes (para FK)
$docentesRaw = $api->listar('docente');
foreach ($docentesRaw as $d) {
    $mapaDocentes[$d['id']] = $d['nombres'] . ' ' . $d['apellidos'];
}

// ── LISTAR ──
if ($vista === 'listar') {
    $evaluacionesRaw = $api->listar('evaluacion_docente');

    foreach ($evaluacionesRaw as $e) {
        $e['nombre_docente'] = $mapaDocentes[$e['docente']] ?? '';
        $evaluaciones[] = $e;
    }
}

// ── VER ──
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $arr = $api->obtenerPorClave('evaluacion_docente', 'id', $id);

    if (!empty($arr)) {
        $evaluacion = $arr[0];
        $evaluacion['nombre_docente'] = $mapaDocentes[$evaluacion['docente']] ?? '';
    }
}

// ── FORMULARIO ──
if ($vista === 'formulario') {
    $docentes = $docentesRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $arr = $api->obtenerPorClave('evaluacion_docente', 'id', $id);

        if (!empty($arr)) {
            $evaluacion = $arr[0];
        }
    }
}
?>

<div class="container mt-4">
    <h3>Evaluación Docente</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="evaluacion_docente.php?vista=formulario" class="btn btn-primary mb-3">
            Nueva Evaluación
        </a>

        <?php if (!empty($evaluaciones)): ?>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Docente</th>
                        <th>Semestre</th>
                        <th>Calificación</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evaluaciones as $e): ?>
                        <tr>
                            <td><?= $e['nombre_docente'] ?></td>
                            <td><?= $e['semestre'] ?></td>
                            <td><?= $e['calificacion'] ?></td>
                            <td><?= $e['fecha_creacion'] ?></td>
                            <td>
                                <a href="evaluacion_docente.php?vista=ver&id=<?= $e['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                                <a href="evaluacion_docente.php?vista=formulario&editar=<?= $e['id'] ?>" class="btn btn-warning btn-sm">Editar</a>

                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar evaluación?')">
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

        <a href="evaluacion_docente.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if ($evaluacion): ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Docente:</strong> <?= $evaluacion['nombre_docente'] ?></p>
                    <p><strong>Semestre:</strong> <?= $evaluacion['semestre'] ?></p>
                    <p><strong>Calificación:</strong> <?= $evaluacion['calificacion'] ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="evaluacion_docente.php" class="btn btn-secondary mb-3">Volver</a>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $evaluacion['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Docente</label>
                        <select name="docente" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= ($evaluacion && $evaluacion['docente'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= $d['nombres'] . ' ' . $d['apellidos'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Semestre</label>
                        <input name="semestre" class="form-control"
                               value="<?= $evaluacion['semestre'] ?? '' ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Calificación</label>
                        <input type="number" step="0.01" name="calificacion"
                               class="form-control"
                               value="<?= $evaluacion['calificacion'] ?? '' ?>" required>
                    </div>

                    <button class="btn btn-success">Guardar</button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>