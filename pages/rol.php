<?php
// ============================================================================
// rol.php — CRUD para la tabla Rol
// Ubicacion: pages/rol.php
// Sigue el mismo patron que producto.php (ver comentarios detallados ahi).
// ============================================================================
//
// Campos: id (PK UUID), nombre, descripcion, activo

require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$tabla = 'rol';
$clave = 'id';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accionPost = $_POST['accion_post'] ?? '';

    if ($accionPost === 'crear') {
        $datos = [
            'nombre'      => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'activo'      => isset($_POST['activo']) ? (int)$_POST['activo'] : 1
        ];
        $resultado = $api->crear($tabla, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    if ($accionPost === 'actualizar') {
        $valor = $_POST['id'] ?? '';
        $datos = [
            'nombre'      => $_POST['nombre'] ?? '',
            'descripcion' => $_POST['descripcion'] ?? '',
            'activo'      => isset($_POST['activo']) ? (int)$_POST['activo'] : 1
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

    header('Location: rol.php');
    exit;
}

$paginaActual = 'rol';
$tituloPagina = 'Roles';
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
    <h3>Roles</h3>

    <?php if (!$mostrarFormulario): ?>
        <a href="rol.php?accion=nuevo" class="btn btn-primary mb-3">Nuevo Rol</a>
    <?php endif; ?>

    <?php if ($mostrarFormulario): ?>
        <div class="card mb-3">
            <div class="card-header"><?= $editando ? "Editar Rol" : "Nuevo Rol" ?></div>
            <div class="card-body">
                <form method="POST" action="rol.php"
                      onsubmit="<?= $editando ? "return confirm('¿Está seguro de actualizar el Rol?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" name="nombre" value="<?= $registro['nombre'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Activo</label>
                            <?php $activoValor = (string)($registro['activo'] ?? '1'); ?>
                            <select class="form-select" name="activo">
                                <option value="1" <?= $activoValor === '1' ? 'selected' : '' ?>>Si</option>
                                <option value="0" <?= $activoValor === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Descripcion</label>
                            <textarea class="form-control" name="descripcion" rows="3"><?= $registro['descripcion'] ?? '' ?></textarea>
                        </div>
                    </div>
                    <button class="btn btn-success me-2" type="submit">Guardar</button>
                    <a href="rol.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($registros)): ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Descripcion</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?= $reg['nombre'] ?? '' ?></td>
                    <td><?= $reg['descripcion'] ?? '' ?></td>
                    <td><?= (int)($reg['activo'] ?? 0) === 1 ? 'Si' : 'No' ?></td>
                    <td>
                        <a href="rol.php?accion=editar&clave=<?= $reg['id'] ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                        <form method="POST" action="rol.php" style="display:inline" onsubmit="return confirm('¿Eliminar Rol <?= $reg['nombre'] ?? '' ?>?')">
                            <input type="hidden" name="accion_post" value="eliminar" />
                            <input type="hidden" name="id" value="<?= $reg['id'] ?? '' ?>" />
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="alert alert-warning">No se encontraron registros en la tabla rol.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
