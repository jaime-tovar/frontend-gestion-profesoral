<?php
// ============================================================================
// config.php — Configuracion del frontend
// Ubicacion: FrontPhp_AppiGenericaPhp/config.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Define la URL donde esta corriendo la API (el backend).
//   Todas las paginas del frontend usan esta URL para hacer llamadas cURL
//   a la API y obtener datos.
//
//   Es el UNICO archivo de configuracion del frontend.
//   Si la API cambia de puerto o de servidor, se cambia SOLO ACA.
//
// CUANDO SE USA:
//   Cada pagina hace: require_once '../config.php';
//   Despues ApiService.php usa API_BASE_URL para armar las URLs:
//     API_BASE_URL . '/api/producto'  ->  'http://localhost:8000/api/producto'
//
// QUE ES define():
//   define() crea una CONSTANTE: un valor que no cambia nunca durante la ejecucion.
//   A diferencia de una variable ($variable), una constante:
//     - No lleva $ adelante
//     - No se puede cambiar despues de definirla
//     - Esta disponible en TODO el codigo (es global)
//   Es como un "readonly" en C# o un "const" en JavaScript.
// ============================================================================

// La URL donde corre la API. El frontend le hace peticiones HTTP a esta URL.
// Si la API esta en otro puerto: cambiar 8000 por el puerto correcto.
// Si la API esta en otra maquina: cambiar localhost por la IP (ej: 'http://192.168.1.100:8000')
define('API_BASE_URL', 'http://api-gestion-profesoral.infinityfree.me');
?>
