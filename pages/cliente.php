<?php
// ============================================================================
// cliente.php — CRUD para la tabla Cliente
// Ubicacion: pages/cliente.php
// Sigue el mismo patron que producto.php (ver comentarios detallados ahi).
// NOTA: Tiene FK a persona y empresa. Los dropdowns muestran nombres resueltos
//       con join manual en PHP (mapa de personas, igual que en factura.php).
// ============================================================================
//
// Campos: id (PK, auto), credito, fkcodpersona (FK), fkcodempresa (FK, opcional)

require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$tabla = 'cliente';
$clave = 'id';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accionPost = $_POST['accion_post'] ?? '';

    if ($accionPost === 'crear') {
        $datos = [
            'credito'      => $_POST['credito'] ?? '0',
            'fkcodpersona' => $_POST['fkcodpersona'] ?? '',
            'fkcodempresa' => !empty($_POST['fkcodempresa']) ? $_POST['fkcodempresa'] : null
        ];
        $resultado = $api->crear($tabla, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    if ($accionPost === 'actualizar') {
        $valor = $_POST['id'] ?? '';
        $datos = [
            'credito'      => $_POST['credito'] ?? '0',
            'fkcodpersona' => $_POST['fkcodpersona'] ?? '',
            'fkcodempresa' => !empty($_POST['fkcodempresa']) ? $_POST['fkcodempresa'] : null
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

    header('Location: cliente.php');
    exit;
}

$paginaActual = 'cliente';
$tituloPagina = 'Clientes';
require __DIR__ . '/../includes/header.php';

$accion = $_GET['accion'] ?? '';
$valorClave = $_GET['clave'] ?? '';

$registros = $api->listar($tabla);
$personas = $api->listar('persona');
$empresas = $api->listar('empresa');

$mostrarFormulario = in_array($accion, ['nuevo', 'editar']);
$editando = $accion === 'editar';

$registro = null;
if ($editando && $valorClave) {
    foreach ($registros as $r) {
        if (($r[$clave] ?? '') == $valorClave) { $registro = $r; break; }
    }
}

// Mapas para mostrar nombres en la tabla
$mapaPersonas = [];
foreach ($personas as $p) { $mapaPersonas[$p['codigo'] ?? ''] = $p['nombre'] ?? 'Sin nombre'; }
$mapaEmpresas = [];
foreach ($empresas as $e) { $mapaEmpresas[$e['codigo'] ?? ''] = $e['nombre'] ?? 'Sin nombre'; }
?>

<div class="container mt-4">
    <h3>Clientes</h3>

    <?php if (!$mostrarFormulario): ?>
        <a href="cliente.php?accion=nuevo" class="btn btn-primary mb-3">Nuevo Cliente</a>
    <?php endif; ?>

    <?php if ($mostrarFormulario): ?>
        <div class="card mb-3">
            <div class="card-header"><?= $editando ? "Editar Cliente" : "Nuevo Cliente" ?></div>
            <div class="card-body">
                <form method="POST" action="cliente.php"
                      onsubmit="<?= $editando ? "return confirm('¿Está seguro de actualizar el Cliente?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>
                    <div class="row">
                        <?php if ($editando): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ID</label>
                            <input class="form-control" value="<?= $registro['id'] ?? '' ?>" readonly />
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Credito</label>
                            <input class="form-control" type="number" step="0.01" name="credito" value="<?= $registro['credito'] ?? '0' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Persona</label>
                            <select class="form-select" name="fkcodpersona" required>
                                <option value="">-- Seleccione Persona --</option>
                                <?php foreach ($personas as $p): ?>
                                    <option value="<?= $p['codigo'] ?>" <?= ($registro && ($registro['fkcodpersona'] ?? '') == $p['codigo']) ? 'selected' : '' ?>>
                                        [<?= $p['codigo'] ?>] <?= $p['nombre'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Empresa (opcional)</label>
                            <select class="form-select" name="fkcodempresa">
                                <option value="">-- Sin Empresa --</option>
                                <?php foreach ($empresas as $e): ?>
                                    <option value="<?= $e['codigo'] ?>" <?= ($registro && ($registro['fkcodempresa'] ?? '') == $e['codigo']) ? 'selected' : '' ?>>
                                        [<?= $e['codigo'] ?>] <?= $e['nombre'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-success me-2" type="submit">Guardar</button>
                    <a href="cliente.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($registros)): ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark"><tr><th>ID</th><th>Persona</th><th>Empresa</th><th>Credito</th><th>Acciones</th></tr></thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?= $reg['id'] ?></td>
                    <td><?= $mapaPersonas[$reg['fkcodpersona'] ?? ''] ?? $reg['fkcodpersona'] ?></td>
                    <td><?= !empty($reg['fkcodempresa']) ? ($mapaEmpresas[$reg['fkcodempresa']] ?? $reg['fkcodempresa']) : '-' ?></td>
                    <td><?= $reg['credito'] ?></td>
                    <td>
                        <a href="cliente.php?accion=editar&clave=<?= $reg['id'] ?>" class="btn btn-warning btn-sm me-1">Editar</a>
                        <form method="POST" action="cliente.php" style="display:inline" onsubmit="return confirm('¿Eliminar Cliente #<?= $reg['id'] ?>?')">
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
        <div class="alert alert-warning">No se encontraron registros en la tabla cliente.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
