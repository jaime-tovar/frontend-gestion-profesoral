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
    
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h3 class="mb-1"><i class="bi bi-person-gear me-2"></i>Usuarios</h3>
            <p class="text-muted mb-0">Gestionar los usuarios del sistema</p>
        </div>
        <?php if (!$mostrarFormulario): ?>
            <a href="usuario.php?accion=nuevo" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Usuario
            </a>
        <?php endif; ?>
    </div>

    <?php if ($mostrarFormulario): ?>
        <div class="card mb-4">
            <div class="card-header d-flex align-items-center">
                <i class="bi bi-<?= $editando ? 'pencil-square' : 'plus-circle' ?> me-2"></i>
                <?= $editando ? "Editar Usuario" : "Nuevo Usuario" ?>
            </div>
            <div class="card-body">
                <form method="POST" action="usuario.php"
                      onsubmit="<?= $editando ? "return confirm('Esta seguro de actualizar el Usuario?')" : '' ?>">
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />
                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Username <span class="text-danger">*</span></label>
                            <input class="form-control" name="username" placeholder="Nombre de usuario"
                                   value="<?= $registro['username'] ?? '' ?>"
                                   <?= $editando ? 'disabled' : '' ?> />
                            <?php if ($editando): ?>
                                <input type="hidden" name="username" value="<?= $registro['username'] ?? '' ?>" />
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input class="form-control" type="email" name="email" placeholder="correo@ejemplo.com"
                                   value="<?= $registro['email'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password <span class="text-danger">*</span></label>
                            <input class="form-control" type="password" name="password" placeholder="Minimo 6 caracteres"
                                   value="<?= $registro['password'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre Completo <span class="text-danger">*</span></label>
                            <input class="form-control" name="nombre_completo" placeholder="Nombre y apellidos"
                                   value="<?= $registro['nombre_completo'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Estado</label>
                            <?php $activoValor = (string)($registro['activo'] ?? '1'); ?>
                            <select class="form-select" name="activo">
                                <option value="1" <?= $activoValor === '1' ? 'selected' : '' ?>>Activo</option>
                                <option value="0" <?= $activoValor === '0' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-2">
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-check-lg me-1"></i> Guardar
                        </button>
                        <a href="usuario.php" class="btn btn-secondary">
                            <i class="bi bi-x-lg me-1"></i> Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($registros)): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-table me-2"></i>Lista de Usuarios</span>
                <span class="badge" style="background-color: var(--primary-800);"><?= count($registros) ?> registros</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Nombre Completo</th>
                                <th style="width: 120px;">Estado</th>
                                <th style="width: 150px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registros as $reg): ?>
                            <tr>
                                <td class="fw-medium"><?= $reg['username'] ?? '' ?></td>
                                <td>
                                    <a href="mailto:<?= $reg['email'] ?? '' ?>" class="text-decoration-none" style="color: var(--accent-600);">
                                        <?= $reg['email'] ?? '' ?>
                                    </a>
                                </td>
                                <td><?= $reg['nombre_completo'] ?? '' ?></td>
                                <td>
                                    <?php if ((int)($reg['activo'] ?? 0) === 1): ?>
                                        <span class="badge badge-active">
                                            <i class="bi bi-check-circle me-1"></i>Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">
                                            <i class="bi bi-x-circle me-1"></i>Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="usuario.php?accion=editar&clave=<?= $reg['id'] ?? '' ?>"
                                           class="btn btn-warning btn-sm" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="usuario.php"
                                              onsubmit="return confirm('Esta seguro de eliminar el Usuario \'<?= $reg['username'] ?? '' ?>\'?')">
                                            <input type="hidden" name="accion_post" value="eliminar" />
                                            <input type="hidden" name="id" value="<?= $reg['id'] ?? '' ?>" />
                                            <button class="btn btn-danger btn-sm" type="submit" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-people" style="font-size: 3rem;"></i>
            </div>
            <div class="empty-state-title">No hay usuarios registrados</div>
            <div class="empty-state-description">Comience creando un nuevo usuario.</div>
            <a href="usuario.php?accion=nuevo" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nuevo Usuario
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
