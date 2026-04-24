<?php
// ============================================================================
// index.php — Punto de entrada del frontend
// Ubicacion: FrontPhp_AppiGenericaPhp/index.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Cuando alguien abre http://localhost/FrontPhp_AppiGenericaPhp/
//   (sin especificar una pagina), Apache busca index.php por defecto.
//   Este archivo simplemente REDIRIGE al usuario a la pagina Home.
//
// COMO FUNCIONA LA REDIRECCION:
//   header('Location: ...') le dice al navegador:
//   "No te quedes aca, anda a esta otra pagina."
//   El navegador cambia la URL automaticamente.
//   Es como un cartel que dice "la entrada esta por alla →".
//
//   exit detiene la ejecucion de PHP. Sin exit, PHP podria seguir
//   ejecutando codigo despues del header (lo cual no tiene sentido
//   porque el navegador ya se fue a otra pagina).
// ============================================================================

header('Location: pages/home.php'); // Redirigir al navegador a la pagina Home
exit;                                // Detener la ejecucion de PHP
?>
