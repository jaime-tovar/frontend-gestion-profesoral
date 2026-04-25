<?php
$paginaActual = 'rol_usuario';
$tituloPagina = 'Roles por Usuario';
require __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/ApiService.php';

$api = new ApiService();
$vista = $_GET['vista'] ?? 'listar';

// =============================
// FUNCION NOMBRE USUARIO (AJUSTADA)
// =============================
function nombreUsuario($u) {
    return $u['nombre_completo']
        ?? $u['username']
        ?? $u['email']
        ?? $u['id'];
}

// =============================
// PROCESAR POST
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accionPost = $_POST['accion_post'] ?? '';
    $usuarioId = $_POST['usuario_id'] ?? '';
    $rolId = $_POST['rol_id'] ?? '';

    if ($usuarioId === '' || $rolId === '') {
        $_SESSION['mensaje'] = 'Usuario y rol son obligatorios.';
        $_SESSION['tipo'] = 'danger';
        header('Location: rol_usuario.php?vista=formulario');
        exit;
    }

    // ── CREAR ──
    if ($accionPost === 'crear') {
        $resultado = $api->crear('rol_usuario', [
            'usuario_id' => $usuarioId,
            'rol_id'     => $rolId
        ]);
    }

    // ── ELIMINAR (PK COMPUESTA) ──
    if ($accionPost === 'eliminar') {
        $resultado = $api->queryPersonalizado(
            "DELETE FROM rol_usuario WHERE usuario_id = ? AND rol_id = ?",
            [$usuarioId, $rolId]
        );
    }

    // Manejo de duplicados
    if (!$resultado['exito'] && str_contains(strtolower($resultado['mensaje']), 'duplicate')) {
        $_SESSION['mensaje'] = 'Ese usuario ya tiene ese rol.';
        $_SESSION['tipo'] = 'warning';
    } else {
        $_SESSION['mensaje'] = $resultado['mensaje'] ?? '';
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    header('Location: rol_usuario.php');
    exit;
}

// =============================
// CARGA DE DATOS
// =============================
$registros = [];
$usuarios = [];
$roles = [];
$mapaUsuarios = [];
$mapaRoles = [];

// 🔹 Usuarios (FILTRAR ACTIVOS opcional)
$usuariosRaw = $api->listar('usuario');

foreach ($usuariosRaw as $u) {
    // 👇 opcional: solo activos
    if (isset($u['activo']) && !$u['activo']) continue;

    $mapaUsuarios[$u['id']] = nombreUsuario($u);
}

// 🔹 Roles
$rolesRaw = $api->listar('rol');
foreach ($rolesRaw as $r) {
    $mapaRoles[$r['id']] = $r['nombre'] ?? $r['id'];
}

// ── LISTAR ──
if ($vista === 'listar') {
    $raw = $api->listar('rol_usuario');

    foreach ($raw as $r) {
        $r['nombre_usuario'] = $mapaUsuarios[$r['usuario_id']] ?? 'Usuario eliminado';
        $r['nombre_rol'] = $mapaRoles[$r['rol_id']] ?? 'Rol eliminado';
        $registros[] = $r;
    }
}

// ── FORMULARIO ──
if ($vista === 'formulario') {
    $usuarios = $usuariosRaw;
    $roles = $rolesRaw;
}
?>

<div class="container mt-4">
    <h3>Roles por Usuario</h3>

    <!-- LISTAR -->
    <?php if ($vista === 'listar'): ?>

        <a href="rol_usuario.php?vista=formulario" class="btn btn-primary mb-3">
            Asignar Rol
        </a>

        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($registros as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['nombre_usuario']) ?></td>
                        <td><?= htmlspecialchars($r['nombre_rol']) ?></td>
                        <td>
                            <form method="POST" style="display:inline"
                                  onsubmit="return confirm('¿Quitar rol a este usuario?')">
                                <input type="hidden" name="accion_post" value="eliminar">
                                <input type="hidden" name="usuario_id" value="<?= $r['usuario_id'] ?>">
                                <input type="hidden" name="rol_id" value="<?= $r['rol_id'] ?>">
                                <button class="btn btn-danger btn-sm">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <!-- FORM -->
    <?php elseif ($vista === 'formulario'): ?>

        <a href="rol_usuario.php" class="btn btn-secondary mb-3">Volver</a>

        <form method="POST">
            <input type="hidden" name="accion_post" value="crear">

            <div class="mb-3">
                <label>Usuario</label>
                <select name="usuario_id" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($usuarios as $u): ?>
                        <?php if (isset($u['activo']) && !$u['activo']) continue; ?>
                        <option value="<?= $u['id'] ?>">
                            <?= htmlspecialchars(nombreUsuario($u)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label>Rol</label>
                <select name="rol_id" class="form-select" required>
                    <option value="">-- Seleccionar --</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= $r['id'] ?>">
                            <?= htmlspecialchars($r['nombre'] ?? $r['id']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn btn-success">Asignar Rol</button>
        </form>

    <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>