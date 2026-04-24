<?php
// ============================================================================
// usuario.php — CRUD para la tabla Usuario
// Ubicacion: pages/usuario.php
// Sigue el mismo patron que producto.php (ver comentarios detallados ahi).
// NOTA: No implementa login/autenticacion, solo CRUD basico de la tabla.
// ============================================================================
//
// Campos: id (PK UUID), username, password, email, nombre_completo, activo

require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$tabla = 'usuario';
$clave = 'id';
$accionPost = '';
$validacionFallida = false;
$formularioDatos = null;

// ══════════════════════════════════════════════
// PROCESAR ACCIONES POST
// ══════════════════════════════════════════════

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    $accionPost = $_POST['accion_post'] ?? '';

    // Validar y normalizar datos solo para crear/actualizar.
    if (in_array($accionPost, ['crear', 'actualizar'], true)) {
        // Normalizar entradas para validar y guardar en el formato correcto.
        $username = strtoupper(trim($_POST['username'] ?? ''));
        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');
        $activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;

        // Guardar los datos para reusar en el formulario si falla la validacion.
        $formularioDatos = [
            'id' => $_POST['id'] ?? '',
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'nombre_completo' => $nombreCompleto,
            'activo' => $activo
        ];

        // Validaciones obligatorias y de formato.
        $mensajeValidacion = null;
        if ($username === '') {
            $mensajeValidacion = 'El username es obligatorio.';
        } elseif ($email === '') {
            $mensajeValidacion = 'El email es obligatorio.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mensajeValidacion = 'El email no es valido.';
        } elseif (strlen($password) < 6) {
            $mensajeValidacion = 'El password debe tener al menos 6 caracteres.';
        } elseif ($nombreCompleto === '') {
            $mensajeValidacion = 'El nombre completo es obligatorio.';
        }

        // Si hay error, marcar fallo y mostrar el mensaje.
        if ($mensajeValidacion !== null) {
            $_SESSION['mensaje'] = $mensajeValidacion;
            $_SESSION['tipo'] = 'danger';
            $validacionFallida = true;
        }
    }

    if ($accionPost === 'crear' && !$validacionFallida) {
        $datos = [
            'username'        => $formularioDatos['username'] ?? '',
            'password'        => $formularioDatos['password'] ?? '',
            'email'           => $formularioDatos['email'] ?? '',
            'nombre_completo' => $formularioDatos['nombre_completo'] ?? '',
            'activo'          => $formularioDatos['activo'] ?? 1
        ];
        $resultado = $api->crear($tabla, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    if ($accionPost === 'actualizar' && !$validacionFallida) {
        $valor = $formularioDatos['id'] ?? '';
        $datos = [
            'username'        => $formularioDatos['username'] ?? '',
            'password'        => $formularioDatos['password'] ?? '',
            'email'           => $formularioDatos['email'] ?? '',
            'nombre_completo' => $formularioDatos['nombre_completo'] ?? '',
            'activo'          => $formularioDatos['activo'] ?? 1
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
        header('Location: usuario.php');
        exit;
    }
}

$paginaActual = 'usuario';
$tituloPagina = 'Usuarios';
require __DIR__ . '/../includes/header.php';

// ══════════════════════════════════════════════
// LEER PARAMETROS GET
// ══════════════════════════════════════════════

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

// Si la validacion falla en POST, mantener el formulario abierto con los datos.
if ($validacionFallida) {
    $mostrarFormulario = true;
    $editando = $accionPost === 'actualizar';
    $registro = $formularioDatos;
}
?>

<div class="container mt-4">
    <h3>Usuarios</h3>

    <?php if (!$mostrarFormulario): ?>
        <a href="usuario.php?accion=nuevo" class="btn btn-primary mb-3">Nuevo Usuario</a>
    <?php endif; ?>

    <?php if ($mostrarFormulario): ?>
        <div class="card mb-3">
            <div class="card-header">
                <?= $editando ? "Editar Usuario" : "Nuevo Usuario" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="usuario.php"
                      onsubmit="<?= $editando ? "return confirm('¿Está seguro de actualizar el Usuario?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username</label>
                            <input class="form-control" name="username"
                                   value="<?= $registro['username'] ?? '' ?>"
                                   <?= $editando ? 'disabled' : '' ?> />
                            <?php if ($editando): ?>
                                <!-- Mantener username en POST cuando el input esta deshabilitado -->
                                <input type="hidden" name="username" value="<?= $registro['username'] ?? '' ?>" />
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email" name="email"
                                   value="<?= $registro['email'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password</label>
                            <input class="form-control" type="password" name="password"
                                   value="<?= $registro['password'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre Completo</label>
                            <input class="form-control" name="nombre_completo"
                                   value="<?= $registro['nombre_completo'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Activo</label>
                            <?php $activoValor = (string)($registro['activo'] ?? '1'); ?>
                            <select class="form-select" name="activo">
                                <option value="1" <?= $activoValor === '1' ? 'selected' : '' ?>>Si</option>
                                <option value="0" <?= $activoValor === '0' ? 'selected' : '' ?>>No</option>
                            </select>
                        </div>
                    </div>
                    <button class="btn btn-success me-2" type="submit">Guardar</button>
                    <a href="usuario.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($registros)): ?>
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Nombre Completo</th>
                    <th>Activo</th>
                    <th>Fecha Creacion</th>
                    <th>Fecha Actualizacion</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <td><?= $reg['username'] ?? '' ?></td>
                    <td><?= $reg['email'] ?? '' ?></td>
                    <td><?= $reg['nombre_completo'] ?? '' ?></td>
                    <td><?= (int)($reg['activo'] ?? 0) === 1 ? 'Si' : 'No' ?></td>
                    <td><?= $reg['fecha_creacion'] ?? '' ?></td>
                    <td><?= $reg['fecha_actualizacion'] ?? '' ?></td>
                    <td>
                        <a href="usuario.php?accion=editar&clave=<?= $reg['id'] ?? '' ?>"
                           class="btn btn-warning btn-sm me-1">Editar</a>
                        <form method="POST" action="usuario.php" style="display:inline"
                              onsubmit="return confirm('¿Está seguro de eliminar el Usuario \'<?= $reg['username'] ?? '' ?>\'?')">
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
        <div class="alert alert-warning">No se encontraron registros en la tabla usuario.</div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
