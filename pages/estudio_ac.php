<?php
$paginaActual = 'estudio_ac';
$tituloPagina = 'Estudio - Área de Conocimiento';
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
    $areaId = $_POST['area_conocimiento'] ?? '';

    if ($estudioId === '' || $areaId === '') {
        $_SESSION['mensaje'] = 'Estudio y área son obligatorios.';
        $_SESSION['tipo'] = 'danger';
        header('Location: estudio_ac.php?vista=formulario');
        exit;
    }

    if ($accionPost === 'crear') {
        $resultado = $api->crear('estudio_ac', [
            'estudio' => $estudioId,
            'area_conocimiento' => $areaId
        ]);
    }

    if ($accionPost === 'actualizar') {
        $resultado = $api->actualizar('estudio_ac', 'id', $_POST['id'], [
            'estudio' => $estudioId,
            'area_conocimiento' => $areaId
        ]);
    }

    if ($accionPost === 'eliminar') {
        $resultado = $api->eliminar('estudio_ac', 'id', $_POST['id']);
    }

    // 🔥 Manejo del UNIQUE
    if (!$resultado['exito'] && str_contains(strtolower($resultado['mensaje']), 'duplicate')) {
        $_SESSION['mensaje'] = 'Ya existe esa relación entre estudio y área.';
        $_SESSION['tipo'] = 'warning';
    } else {
        $_SESSION['mensaje'] = $resultado['mensaje'] ?? '';
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    header('Location: estudio_ac.php');
    exit;
}

// =============================
// CARGA DE DATOS
// =============================
$registros = [];
$registro = null;
$estudios = [];
$areas = [];
$mapaEstudios = [];
$mapaAreas = [];
$editando = false;

// 🔹 ESTUDIOS → usar titulo
$estudiosRaw = $api->listar('estudios_realizados');
foreach ($estudiosRaw as $e) {
    $mapaEstudios[$e['id']] = $e['titulo'] ?? $e['id'];
}

// 🔹 AREAS → label completo
$areasRaw = $api->listar('area_conocimiento');
foreach ($areasRaw as $a) {
    $mapaAreas[$a['id']] =
        ($a['gran_area'] ?? '') . ' > ' .
        ($a['area'] ?? '') . ' > ' .
        ($a['disciplina'] ?? '');
}

// ── LISTAR ──
if ($vista === 'listar') {
    $raw = $api->listar('estudio_ac');

    foreach ($raw as $r) {
        $r['nombre_estudio'] = $mapaEstudios[$r['estudio']] ?? '';
        $r['nombre_area'] = $mapaAreas[$r['area_conocimiento']] ?? '';
        $registros[] = $r;
    }
}

// ── VER ──
if ($vista === 'ver' && isset($_GET['id'])) {
    $arr = $api->obtenerPorClave('estudio_ac', 'id', $_GET['id']);

    if (!empty($arr)) {
        $registro = $arr[0];
        $registro['nombre_estudio'] = $mapaEstudios[$registro['estudio']] ?? '';
        $registro['nombre_area'] = $mapaAreas[$registro['area_conocimiento']] ?? '';
    }
}

// ── FORMULARIO ──
if ($vista === 'formulario') {
    $estudios = $estudiosRaw;
    $areas = $areasRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $arr = $api->obtenerPorClave('estudio_ac', 'id', $_GET['editar']);

        if (!empty($arr)) {
            $registro = $arr[0];
        }
    }
}
?>

<div class="container mt-4">
    <h3>Estudio - Área de Conocimiento</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="estudio_ac.php?vista=formulario" class="btn btn-primary mb-3">
            Nueva Relación
        </a>

        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Estudio</th>
                    <th>Área</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nombre_estudio']) ?></td>
                        <td><?= htmlspecialchars($r['nombre_area']) ?></td>
                        <td>
                            <a href="estudio_ac.php?vista=ver&id=<?= $r['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                            <a href="estudio_ac.php?vista=formulario&editar=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Editar</a>

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

    <!-- VER -->
    <?php elseif ($vista === 'ver'): ?>

        <a href="estudio_ac.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if ($registro): ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Estudio:</strong> <?= htmlspecialchars($registro['nombre_estudio']) ?></p>
                    <p><strong>Área:</strong> <?= htmlspecialchars($registro['nombre_area']) ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="estudio_ac.php" class="btn btn-secondary mb-3">Volver</a>

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
                <label>Área de Conocimiento</label>
                <select name="area_conocimiento" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($areas as $a): ?>
                        <option value="<?= $a['id'] ?>"
                            <?= ($registro && $registro['area_conocimiento'] == $a['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($a['gran_area'] . ' > ' . $a['area'] . ' > ' . $a['disciplina']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn btn-success">Guardar</button>
        </form>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>