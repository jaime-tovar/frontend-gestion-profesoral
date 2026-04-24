<?php
// ============================================================================
// ruta.php — CRUD para la tabla Ruta
// Ubicacion: pages/ruta.php
// Sigue el mismo patron que producto.php (ver comentarios detallados ahi).
// ============================================================================
//
// Campos: id (PK, auto), ruta, descripcion

require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$tabla = 'ruta';
$clave = 'id';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accionPost = $_POST['accion_post'] ?? '';

    if ($accionPost === 'crear') {
        $datos = [
            'ruta'        => $_POST['ruta'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? ''
        ];
        $resultado = $api->crear($tabla, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    if ($accionPost === 'actualizar') {
        $valor = $_POST['id'] ?? '';
        $datos = [
            'ruta'        => $_POST['ruta'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? ''
        ];
        $resultado = $api->actualizar($tabla, $clave, $valor, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    if ($accionPost === 'eliminar') {
        $valor = $_POST['id'] ?? '';
        $resultado = $api->eliminar($tabla, $clave, $valor);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    header('Location: ruta.php');
    exit;
}

$paginaActual = 'ruta';
$tituloPagina = 'Rutas';
require __DIR__ . '/../includes/header.php';

$accion = $_GET['accion'] ?? '';
$valorClave = $_GET['clave'] ?? '';

$registros = $api->listar($tabla);
$mostrarFormulario = in_array($accion, ['nuevo', 'editar']);
$editando = $accion === 'editar';

$registro = null;
if ($editando && $valorClave) {
    foreach ($registros as $r) {
        if (($r[$clave] ?? '') == $valorClave) { $registro = $r; break; }
    }
}
?>

<div class="container mt-4">
    <h3>Rutas</h3>

    <?php if (!$mostrarFormulario): ?>
        <a href="ruta.php?accion=nuevo" class="btn btn-primary mb-3">Nueva Ruta</a>
    <?php endif; ?>

    <?php if ($mostrarFormulario): ?>
        <div class="card mb-3">
            <div class="card-header"><?= $editando ? "Editar Ruta" : "Nueva Ruta" ?></div>
            <div class="card-body">
                <form method="POST" action="ruta.php"
                      onsubmit="<?= $editando ? "return confirm('¿Está seguro de actualizar la Ruta?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>
                    <div class="row">
                        <?php if ($editando): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">ID</label>
                            <input class="form-control" value="<?= $registro['id'] ?? '' ?>" readonly />
                        </div>
                        <?php endif; ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Ruta</label>
                            <input class="form-control" name="ruta" value="<?= $registro['ruta'] ?? '' ?>" />
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Descripcion</label>
                            <input class="form-control" name="descripcion" value="<?= $registro['descripcion'] ?? '' ?>" />
                        </div>
                    </div>
                    <button class="btn btn-success me-2" type="submit">Guardar</button>
                    <a href="ruta.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($registros)): ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark"><tr><th>ID</th><th>Ruta</th><th>Descripcion</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?= $reg['id'] ?></td>
                    <td><?= $reg['ruta'] ?></td>
                    <td><?= $reg['descripcion'] ?></td>
                    <td>
                        <a href="ruta.php?accion=editar&clave=<?= $reg['id'] ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                        <form method="POST" action="ruta.php" style="display:inline" onsubmit="return confirm('¿Eliminar Ruta #<?= $reg['id'] ?>?')">
                            <input type="hidden" name="accion_post" value="eliminar" />
                            <input type="hidden" name="id" value="<?= $reg['id'] ?>" />
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No se encontraron registros en la tabla ruta.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
