<?php
$paginaActual = 'reconocimiento';
$tituloPagina = 'Reconocimientos';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$vista = $_GET['vista'] ?? 'listar';

// =============================
// PROCESAR POST
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accionPost = $_POST['accion_post'] ?? '';

    $tipo = $_POST['tipo'] ?? '';
    $nombre = $_POST['nombre'] ?? '';
    $institucion = trim($_POST['institucion'] ?? '');
    $ambito = trim($_POST['ambito'] ?? '');
    $fecha = trim($_POST['fecha'] ?? '');
    $docenteId = $_POST['docente'] ?? '';

    $institucion = ($institucion === '') ? null : $institucion;
    $ambito = ($ambito === '') ? null : $ambito;
    $fecha = ($fecha === '') ? null : $fecha;

    if ($tipo === '' || $nombre === '' || $docenteId === '') {
        $_SESSION['mensaje'] = 'Tipo, nombre y docente son obligatorios.';
        $_SESSION['tipo'] = 'danger';
        header('Location: reconocimiento.php?vista=formulario');
        exit;
    }

    // ── CREAR ──
    if ($accionPost === 'crear') {
        $datos = [
            'tipo'        => $tipo,
            'nombre'      => $nombre,
            'institucion' => $institucion,
            'ambito'      => $ambito,
            'fecha'       => $fecha,
            'docente'     => $docenteId
        ];

        $resultado = $api->crear('reconocimiento', $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: reconocimiento.php');
        exit;
    }

    // ── ACTUALIZAR ──
    if ($accionPost === 'actualizar') {
        $id = $_POST['id'] ?? '';

        $datos = [
            'tipo'        => $tipo,
            'nombre'      => $nombre,
            'institucion' => $institucion,
            'ambito'      => $ambito,
            'fecha'       => $fecha,
            'docente'     => $docenteId
        ];

        $resultado = $api->actualizar('reconocimiento', 'id', $id, $datos);

        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: reconocimiento.php');
        exit;
    }

    // ── ELIMINAR ──
    if ($accionPost === 'eliminar') {
        $id = $_POST['id'] ?? '';

        $resultado = $api->eliminar('reconocimiento', 'id', $id);

        $_SESSION['mensaje'] = $resultado['exito']
            ? 'Reconocimiento eliminado.'
            : 'Error: ' . $resultado['mensaje'];

        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';

        header('Location: reconocimiento.php');
        exit;
    }
}

// =============================
// CARGA DE DATOS
// =============================
$reconocimientos = [];
$reconocimiento = null;
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
    $raw = $api->listar('reconocimiento');

    foreach ($raw as $r) {
        $r['nombre_docente'] = $mapaDocentes[$r['docente']] ?? '';
        $reconocimientos[] = $r;
    }
}

// ── VER ──
if ($vista === 'ver' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $arr = $api->obtenerPorClave('reconocimiento', 'id', $id);

    if (!empty($arr)) {
        $reconocimiento = $arr[0];
        $reconocimiento['nombre_docente'] = $mapaDocentes[$reconocimiento['docente']] ?? '';
    }
}

// ── FORMULARIO ──
if ($vista === 'formulario') {
    $docentes = $docentesRaw;

    if (isset($_GET['editar'])) {
        $editando = true;
        $id = $_GET['editar'];
        $arr = $api->obtenerPorClave('reconocimiento', 'id', $id);

        if (!empty($arr)) {
            $reconocimiento = $arr[0];
        }
    }
}
?>

<div class="container mt-4">
    <h3>Reconocimientos</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="reconocimiento.php?vista=formulario" class="btn btn-primary mb-3">
            Nuevo Reconocimiento
        </a>

        <?php if (!empty($reconocimientos)): ?>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Docente</th>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Institución</th>
                        <th>Ámbito</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reconocimientos as $r): ?>
                        <tr>
                            <td><?= $r['nombre_docente'] ?></td>
                            <td><?= $r['tipo'] ?></td>
                            <td><?= $r['nombre'] ?></td>
                            <td><?= $r['institucion'] ?></td>
                            <td><?= $r['ambito'] ?></td>
                            <td><?= $r['fecha'] ?></td>
                            <td>
                                <a href="reconocimiento.php?vista=ver&id=<?= $r['id'] ?>" class="btn btn-info btn-sm">Ver</a>
                                <a href="reconocimiento.php?vista=formulario&editar=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Editar</a>

                                <form method="POST" style="display:inline"
                                      onsubmit="return confirm('¿Eliminar reconocimiento?')">
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

        <a href="reconocimiento.php" class="btn btn-secondary mb-3">Volver</a>

        <?php if ($reconocimiento): ?>
            <div class="card">
                <div class="card-body">
                    <p><strong>Docente:</strong> <?= $reconocimiento['nombre_docente'] ?></p>
                    <p><strong>Tipo:</strong> <?= $reconocimiento['tipo'] ?></p>
                    <p><strong>Nombre:</strong> <?= $reconocimiento['nombre'] ?></p>
                    <p><strong>Institución:</strong> <?= $reconocimiento['institucion'] ?></p>
                    <p><strong>Ámbito:</strong> <?= $reconocimiento['ambito'] ?></p>
                    <p><strong>Fecha:</strong> <?= $reconocimiento['fecha'] ?></p>
                    <p><strong>Fecha Creación:</strong> <?= $reconocimiento['fecha_creacion'] ?></p>
                </div>
            </div>
        <?php endif; ?>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="reconocimiento.php" class="btn btn-secondary mb-3">Volver</a>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>">
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $reconocimiento['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Docente</label>
                        <select name="docente" class="form-select" required>
                            <option value="">-- Seleccionar --</option>
                            <?php foreach ($docentes as $d): ?>
                                <option value="<?= $d['id'] ?>"
                                    <?= ($reconocimiento && $reconocimiento['docente'] == $d['id']) ? 'selected' : '' ?>>
                                    <?= $d['nombres'] . ' ' . $d['apellidos'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Tipo</label>
                        <input name="tipo" class="form-control"
                               value="<?= $reconocimiento['tipo'] ?? '' ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Nombre</label>
                        <input name="nombre" class="form-control"
                               value="<?= $reconocimiento['nombre'] ?? '' ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Institución</label>
                        <input name="institucion" class="form-control"
                               value="<?= $reconocimiento['institucion'] ?? '' ?>">
                    </div>

                    <div class="mb-3">
                        <label>Ámbito</label>
                        <input name="ambito" class="form-control"
                               value="<?= $reconocimiento['ambito'] ?? '' ?>">
                    </div>

                    <div class="mb-3">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control"
                               value="<?= $reconocimiento['fecha'] ?? '' ?>">
                    </div>

                    <button class="btn btn-success">Guardar</button>
                </form>
            </div>
        </div>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>