<?php
$paginaActual = 'beca';
$tituloPagina = 'Becas';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$vista = $_GET['vista'] ?? 'listar';

// =============================
// PROCESAR POST
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accionPost = $_POST['accion_post'] ?? '';

    $estudioId = $_POST['estudio'] ?? '';
    $tipo = trim($_POST['tipo'] ?? '');
    $institucion = trim($_POST['institucion'] ?? '');

    $fechaInicio = $_POST['fecha_inicio'] ?? null;
    $fechaFin = $_POST['fecha_fin'] ?? null;

    $fechaInicio = ($fechaInicio === '') ? null : $fechaInicio;
    $fechaFin = ($fechaFin === '') ? null : $fechaFin;

    if ($estudioId === '' || $tipo === '' || $institucion === '') {
        $_SESSION['mensaje'] = 'Estudio, tipo e institución son obligatorios.';
        $_SESSION['tipo'] = 'danger';
        header('Location: beca.php?vista=formulario');
        exit;
    }

    // 🔥 Validación fechas
    if ($fechaInicio && $fechaFin && $fechaFin < $fechaInicio) {
        $_SESSION['mensaje'] = 'La fecha fin no puede ser menor a la fecha inicio.';
        $_SESSION['tipo'] = 'warning';
        header('Location: beca.php?vista=formulario');
        exit;
    }

    $datos = [
        'estudio'       => $estudioId,
        'tipo'          => $tipo,
        'institucion'   => $institucion,
        'fecha_inicio'  => $fechaInicio,
        'fecha_fin'     => $fechaFin
    ];

    if ($accionPost === 'crear') {
        $resultado = $api->crear('beca', $datos);
    }

    if ($accionPost === 'actualizar') {
        $resultado = $api->actualizar('beca', 'id', $_POST['id'], $datos);
    }

    if ($accionPost === 'eliminar') {
        $resultado = $api->eliminar('beca', 'id', $_POST['id']);
    }

    $_SESSION['mensaje'] = $resultado['mensaje'] ?? '';
    $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

    header('Location: beca.php');
    exit;
}

// =============================
// CARGA DE DATOS
// =============================
$registros = [];
$registro = null;
$estudios = [];
$mapaEstudios = [];
$editando = false;

// 🔹 Estudios → usar titulo
$estudiosRaw = $api->listar('estudios_realizados');
foreach ($estudiosRaw as $e) {
    $mapaEstudios[$e['id']] = $e['titulo'] ?? $e['id'];
}

// ── LISTAR ──
if ($vista === 'listar') {
    $raw = $api->listar('beca');

    foreach ($raw as $r) {
        $r['nombre_estudio'] = $mapaEstudios[$r['estudio']] ?? '';
        $registros[] = $r;
    }
}

// ── VER ──
if ($vista === 'ver' && isset($_GET['id'])) {
    $arr = $api->obtenerPorClave('beca', 'id', $_GET['id']);

    if (!empty($arr)) {
        $registro = $arr[0];
        $registro['nombre_estudio'] = $mapaEstudios[$registro['estudio']] ?? '';
    }
}

// ── FORMULARIO ──
if ($vista === 'formulario') {
    $estudios = $estudiosRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $arr = $api->obtenerPorClave('beca', 'id', $_GET['editar']);
        if (!empty($arr)) {
            $registro = $arr[0];
        }
    }
}
?>

<div class="container mt-4">
    <h3>Becas</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="beca.php?vista=formulario" class="btn btn-primary mb-3">
            Nueva Beca
        </a>
        <?php if (!empty($registros)): ?>
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Estudio</th>
                    <th>Tipo</th>
                    <th>Institución</th>
                    <th>Fecha Inicio</th>
                    <th>Fecha Fin</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nombre_estudio']) ?></td>
                        <td><?= htmlspecialchars($r['tipo']) ?></td>
                        <td><?= htmlspecialchars($r['institucion']) ?></td>
                        <td><?= htmlspecialchars($r['fecha_inicio']) ?></td>
                        <td><?= htmlspecialchars($r['fecha_fin']) ?></td>
                        <td>
                            <a href="beca.php?vista=ver&id=<?= $r['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                            <a href="beca.php?vista=formulario&editar=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Editar</a>

                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar beca?')">
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

        <a href="beca.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if ($registro): ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Estudio:</strong> <?= htmlspecialchars($registro['nombre_estudio']) ?></p>
                    <p><strong>Tipo:</strong> <?= htmlspecialchars($registro['tipo']) ?></p>
                    <p><strong>Institución:</strong> <?= htmlspecialchars($registro['institucion']) ?></p>
                    <p><strong>Fecha Inicio:</strong> <?= htmlspecialchars($registro['fecha_inicio']) ?></p>
                    <p><strong>Fecha Fin:</strong> <?= htmlspecialchars($registro['fecha_fin']) ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="beca.php" class="btn btn-secondary mb-3">Volver</a>

        <form method="POST">
            <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?= $registro['id'] ?>">
            <?php endif; ?>

            <div class="mb-3">
                <label>Estudio</label>
                <select name="estudio" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($estudios as $e): ?>
                        <option value="<?= $e['id'] ?>"
                            <?= ($registro && $registro['estudio'] == $e['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($e['titulo']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Tipo</label>
                <input name="tipo" class="form-control"
                       value="<?= htmlspecialchars($registro['tipo'] ?? '') ?>" required>
            </div>

            <div class="mb-3">
                <label>Institución</label>
                <input name="institucion" class="form-control"
                       value="<?= htmlspecialchars($registro['institucion'] ?? '') ?>" required>
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

            <button class="btn btn-success">Guardar</button>
        </form>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>