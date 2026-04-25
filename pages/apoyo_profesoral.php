<?php
$paginaActual = 'apoyo_profesoral';
$tituloPagina = 'Apoyo Profesoral';
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
    $conApoyo = isset($_POST['con_apoyo']) ? 1 : 0;
    $institucion = trim($_POST['institucion'] ?? '');
    $tipo = trim($_POST['tipo'] ?? '');

    $institucion = ($institucion === '') ? null : $institucion;
    $tipo = ($tipo === '') ? null : $tipo;

    if ($estudioId === '') {
        $_SESSION['mensaje'] = 'El estudio es obligatorio.';
        $_SESSION['tipo'] = 'danger';
        header('Location: apoyo_profesoral.php?vista=formulario');
        exit;
    }

    // ── CREAR ──
    if ($accionPost === 'crear') {
        $resultado = $api->crear('apoyo_profesoral', [
            'estudio'     => $estudioId,
            'con_apoyo'   => $conApoyo,
            'institucion' => $institucion,
            'tipo'        => $tipo
        ]);
    }

    // ── ACTUALIZAR ──
    if ($accionPost === 'actualizar') {
        $resultado = $api->actualizar('apoyo_profesoral', 'id', $_POST['id'], [
            'estudio'     => $estudioId,
            'con_apoyo'   => $conApoyo,
            'institucion' => $institucion,
            'tipo'        => $tipo
        ]);
    }

    // ── ELIMINAR ──
    if ($accionPost === 'eliminar') {
        $resultado = $api->eliminar('apoyo_profesoral', 'id', $_POST['id']);
    }

    $_SESSION['mensaje'] = $resultado['mensaje'] ?? '';
    $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

    header('Location: apoyo_profesoral.php');
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
    $raw = $api->listar('apoyo_profesoral');

    foreach ($raw as $r) {
        $r['nombre_estudio'] = $mapaEstudios[$r['estudio']] ?? '';
        $registros[] = $r;
    }
}

// ── VER ──
if ($vista === 'ver' && isset($_GET['id'])) {
    $arr = $api->obtenerPorClave('apoyo_profesoral', 'id', $_GET['id']);

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
        $arr = $api->obtenerPorClave('apoyo_profesoral', 'id', $_GET['editar']);
        if (!empty($arr)) {
            $registro = $arr[0];
        }
    }
}
?>

<div class="container mt-4">
    <h3>Apoyo Profesoral</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="apoyo_profesoral.php?vista=formulario" class="btn btn-primary mb-3">
            Nuevo Registro
        </a>
        <?php if (!empty($registros)): ?>
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Estudio</th>
                    <th>¿Con Apoyo?</th>
                    <th>Institución</th>
                    <th>Tipo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nombre_estudio']) ?></td>
                        <td><?= $r['con_apoyo'] ? 'Sí' : 'No' ?></td>
                        <td><?= htmlspecialchars($r['institucion']) ?></td>
                        <td><?= htmlspecialchars($r['tipo']) ?></td>
                        <td>
                            <a href="apoyo_profesoral.php?vista=ver&id=<?= $r['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                            <a href="apoyo_profesoral.php?vista=formulario&editar=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Editar</a>

                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Eliminar registro?')">
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

        <a href="apoyo_profesoral.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if ($registro): ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Estudio:</strong> <?= htmlspecialchars($registro['nombre_estudio']) ?></p>
                    <p><strong>¿Con Apoyo?:</strong> <?= $registro['con_apoyo'] ? 'Sí' : 'No' ?></p>
                    <p><strong>Institución:</strong> <?= htmlspecialchars($registro['institucion']) ?></p>
                    <p><strong>Tipo:</strong> <?= htmlspecialchars($registro['tipo']) ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="apoyo_profesoral.php" class="btn btn-secondary mb-3">Volver</a>

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

            <div class="mb-3 form-check">
                <input type="checkbox" name="con_apoyo" class="form-check-input"
                    <?= ($registro && $registro['con_apoyo']) ? 'checked' : '' ?>>
                <label class="form-check-label">¿Cuenta con apoyo?</label>
            </div>

            <div class="mb-3">
                <label>Institución</label>
                <input name="institucion" class="form-control"
                       value="<?= htmlspecialchars($registro['institucion'] ?? '') ?>">
            </div>

            <div class="mb-3">
                <label>Tipo</label>
                <input name="tipo" class="form-control"
                       value="<?= htmlspecialchars($registro['tipo'] ?? '') ?>">
            </div>

            <button class="btn btn-success">Guardar</button>
        </form>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>