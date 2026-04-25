<?php
// ============================================================================
// red.php — CRUD para la tabla Red
// Ubicacion: pages/red.php
//
// PARA QUE SIRVE: Listar, crear, editar y eliminar registros de red.
// Campos: id (PK UUID), nombre, url, pais
// ============================================================================

require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$tabla = 'red';
$clave = 'id';
$accionPost = '';
$validacionFallida = false;
$formularioDatos = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accionPost = $_POST['accion_post'] ?? '';

    $normalizarUrl = function (string $url): array {
        $url = trim($url);
        if ($url === '') {
            return ['valida' => false, 'mensaje' => 'La URL es obligatoria.'];
        }

        // Asegurar que se guarde siempre con https://
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        } else {
            $url = preg_replace('~^https?://~i', 'https://', $url);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return ['valida' => false, 'mensaje' => 'La URL no es valida.'];
        }

        return ['valida' => true, 'url' => $url];
    };

    $urlNormalizada = null;
    if (in_array($accionPost, ['crear', 'actualizar'], true)) {
        $formularioDatos = [
            'id'     => $_POST['id'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'url'    => $_POST['url'] ?? '',
            'pais'   => $_POST['pais'] ?? ''
        ];

        $validacionUrl = $normalizarUrl($_POST['url'] ?? '');
        if (!$validacionUrl['valida']) {
            $_SESSION['mensaje'] = $validacionUrl['mensaje'];
            $_SESSION['tipo'] = 'danger';
            $validacionFallida = true;
        } else {
            $urlNormalizada = $validacionUrl['url'];
        }
    }

    if ($accionPost === 'crear' && !$validacionFallida) {
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'url'    => $urlNormalizada ?? '',
            'pais'   => $_POST['pais'] ?? ''
        ];
        $resultado = $api->crear($tabla, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    if ($accionPost === 'actualizar' && !$validacionFallida) {
        $valor = $_POST['id'] ?? '';
        $datos = [
            'nombre' => $_POST['nombre'] ?? '',
            'url'    => $urlNormalizada ?? '',
            'pais'   => $_POST['pais'] ?? ''
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

    if (!$validacionFallida) {
        header('Location: red.php');
        exit;
    }
}

$paginaActual = 'red';
$tituloPagina = 'Red';
require __DIR__ . '/../includes/header.php';

$accion = $_GET['accion'] ?? '';
$valorClave = $_GET['clave'] ?? '';

$registros = $api->listar($tabla);
$mostrarFormulario = in_array($accion, ['nuevo', 'editar']);
$editando = $accion === 'editar';

$registro = null;
if ($editando && $valorClave) {
    foreach ($registros as $r) {
        if (($r[$clave] ?? '') == $valorClave) {
            $registro = $r;
            break;
        }
    }
}

if ($validacionFallida) {
    $mostrarFormulario = true;
    $editando = $accionPost === 'actualizar';
    $registro = $formularioDatos;
}
?>

<div class="container mt-4">
    <h3>Red</h3>

    <?php if (!$mostrarFormulario): ?>
        <a href="red.php?accion=nuevo" class="btn btn-primary mb-3">Nueva Red</a>
    <?php endif; ?>

    <?php if ($mostrarFormulario): ?>
        <div class="card mb-3">
            <div class="card-header"><?= $editando ? "Editar Red" : "Nueva Red" ?></div>
            <div class="card-body">
                <form method="POST" action="red.php"
                      onsubmit="<?= $editando ? "return confirm('Esta seguro de actualizar la red?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />

                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" name="nombre"
                                   value="<?= $registro['nombre'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">URL</label>
                            <input class="form-control" name="url"
                                   value="<?= $registro['url'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pais</label>
                            <input class="form-control" name="pais"
                                   value="<?= $registro['pais'] ?? '' ?>" />
                        </div>
                    </div>
                    <button class="btn btn-success me-2" type="submit">Guardar</button>
                    <a href="red.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($registros)): ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>URL</th>
                    <th>Pais</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?= $reg['nombre'] ?? '' ?></td>
                    <td><?= $reg['url'] ?? '' ?></td>
                    <td><?= $reg['pais'] ?? '' ?></td>
                    <td>
                        <a href="red.php?accion=editar&clave=<?= $reg['id'] ?>"
                           class="btn btn-warning btn-sm me-1">Editar</a>
                        <form method="POST" action="red.php" style="display:inline"
                            onsubmit="return confirm('Esta seguro de eliminar la red \'<?= $reg['nombre'] ?? '' ?>\'?')">
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
        <div class="alert alert-warning">No se encontraron registros en la tabla red.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
