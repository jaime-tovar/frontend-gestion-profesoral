<?php
if (session_status() === PHP_SESSION_NONE) {
	session_start();
}

require_once __DIR__ . '/../services/ApiService.php';

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$baseUrl = rtrim(str_replace('/pages', '', $scriptDir), '/');
$homeUrl = $baseUrl . '/pages/home.php';

$error = '';
$email = '';

if (!empty($_SESSION['usuario_email'])) {
	header('Location: ' . $homeUrl);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['email']) || isset($_GET['password']))) {
	$email = trim($_GET['email'] ?? '');
	$password = trim($_GET['password'] ?? '');

	if ($email === '' || $password === '') {
		$error = 'Email y contrasena son obligatorios.';
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error = 'El email no es valido.';
	} else {
		$api = new ApiService();
		$usuarios = $api->obtenerPorClave('usuario', 'email', rawurlencode($email));
		$usuario = $usuarios[0] ?? null;

		if (!$usuario) {
			$usuarios = $api->listar('usuario');
			foreach ($usuarios as $u) {
				if (strcasecmp(trim((string)($u['email'] ?? '')), $email) === 0) {
					$usuario = $u;
					break;
				}
			}
		}

		if (!$usuario) {
			$error = 'Credenciales invalidas.';
		} elseif (isset($usuario['activo']) && (int)$usuario['activo'] !== 1) {
			$error = 'El usuario esta inactivo.';
		} elseif (!empty($usuario['fecha_borrado'])) {
			$error = 'El usuario fue eliminado.';
		} else {
			$storedPassword = (string)($usuario['password'] ?? '');
			$validPassword = $storedPassword !== '' && $storedPassword === $password;

			if (!$validPassword) {
				$error = 'Credenciales invalidas.';
			} else {
				$_SESSION['usuario_id'] = $usuario['id'] ?? '';
				$_SESSION['usuario_email'] = $usuario['email'] ?? $email;
				$_SESSION['usuario_username'] = $usuario['username'] ?? '';
				$_SESSION['usuario_nombre'] = $usuario['nombre_completo'] ?? '';
				$_SESSION['mensaje'] = 'Bienvenido. Sesion iniciada.';
				$_SESSION['tipo'] = 'success';

				header('Location: ' . $homeUrl);
				exit;
			}
		}
	}
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title>Login - Gestion Profesoral</title>

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
	<link href="<?= $baseUrl ?>/assets/css/app.css" rel="stylesheet" />
</head>
<body>
	<div class="container min-vh-100 d-flex align-items-center justify-content-center py-5">
		<div class="card shadow-lg border-0" style="max-width: 420px; width: 100%;">
			<div class="card-header bg-white border-0 text-center pt-4">
				<h1 class="h4 mb-1">Iniciar sesion</h1>
				<p class="text-muted small mb-0">Sistema de Gestion Profesoral</p>
			</div>
			<div class="card-body px-4 pb-4">
				<?php if ($error !== ''): ?>
					<div class="alert alert-danger" role="alert">
						<?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
					</div>
				<?php endif; ?>

				<form method="get" action="">
					<div class="mb-3">
						<label for="email" class="form-label">Email</label>
						<input
							type="email"
							class="form-control"
							id="email"
							name="email"
							value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
							required
							autocomplete="username"
						/>
					</div>
					<div class="mb-3">
						<label for="password" class="form-label">Contrasena</label>
						<input
							type="password"
							class="form-control"
							id="password"
							name="password"
							required
							autocomplete="current-password"
						/>
					</div>
					<button type="submit" class="btn btn-primary w-100">Ingresar</button>
				</form>

				<div class="mt-3 small text-muted">
					Usar las siguientes credenciales:<br>
					Email: docente@uni.edu<br>
					Contrasena: docente123
				</div>
			</div>
		</div>
	</div>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
