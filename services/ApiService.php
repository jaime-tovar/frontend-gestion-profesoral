<?php
// ============================================================================
// ApiService.php — Servicio que comunica el frontend con la API via cURL
// Ubicacion: services/ApiService.php
//
// PARA QUE SIRVE ESTE ARCHIVO:
//   Es la UNICA clase del frontend. Encapsula TODAS las llamadas HTTP a la API.
//   Las paginas (producto.php, factura.php, etc.) NUNCA hacen cURL directamente.
//   Siempre llaman a los metodos de esta clase.
//
//   Tiene 5 metodos, uno para cada operacion CRUD:
//     listar()         -> GET    /api/{tabla}              (traer todos)
//     obtenerPorClave()-> GET    /api/{tabla}/{clave}/{val} (traer uno)
//     crear()          -> POST   /api/{tabla}              (insertar nuevo)
//     actualizar()     -> PUT    /api/{tabla}/{clave}/{val} (modificar)
//     eliminar()       -> DELETE /api/{tabla}/{clave}/{val} (borrar)
//
// QUE ES cURL:
//   cURL es una libreria de PHP para hacer peticiones HTTP.
//   Es como un "navegador invisible": hace pedidos a una URL y recibe respuestas,
//   pero sin interfaz visual. Es la forma en que PHP habla con la API.
//
//   Flujo:
//     1. El usuario hace clic en "Guardar" en el formulario de producto
//     2. El navegador envia un POST a producto.php (el frontend PHP)
//     3. producto.php crea un ApiService y llama a $api->crear('producto', $datos)
//     4. ApiService hace un cURL POST a http://localhost:8000/api/producto
//     5. La API recibe el pedido, ejecuta SQL, y responde JSON
//     6. ApiService recibe el JSON y lo devuelve a producto.php
//     7. producto.php muestra el mensaje de exito/error al usuario
//
// CUANDO SE USA:
//   Cada pagina CRUD hace: require_once '../services/ApiService.php';
//   Y luego: $api = new ApiService();
//            $productos = $api->listar('producto');
//
// EQUIVALENTE EN OTROS LENGUAJES:
//   - JavaScript: fetch() o axios
//   - C#:         HttpClient
//   - Python:     requests.get(), requests.post()
//   - Java:       HttpURLConnection o RestTemplate
// ============================================================================

// Cargar la configuracion para tener acceso a API_BASE_URL
// __DIR__ = la carpeta donde esta ESTE archivo (services/)
// '/../config.php' = subir un nivel y buscar config.php
require_once __DIR__ . '/../config.php';

class ApiService {

    // La URL base de la API. Se llena en el constructor con API_BASE_URL de config.php
    // Ejemplo: 'http://localhost:8000'
    private $baseUrl;

    // Constructor: se ejecuta al hacer new ApiService()
    // Guarda la URL base de la API para usarla en todos los metodos
    public function __construct() {
        $this->baseUrl = API_BASE_URL; // API_BASE_URL viene de config.php (constante)
    }

    // =======================================================================
    // listar() — GET /api/{tabla} — Traer todos los registros
    // =======================================================================
    // Ejemplo: $api->listar('producto', 50)
    //   -> Hace GET http://localhost:8000/api/producto?limite=50
    //   -> Retorna: [['codigo'=>'PR001','nombre'=>'Laptop',...], ...]
    //
    // $tabla = nombre de la tabla a consultar
    // $limite = maximo de registros (opcional, por defecto null = todos)
    public function listar($tabla, $limite = null) {
        // Armar la URL: 'http://localhost:8000' + '/api/' + 'producto'
        $url = $this->baseUrl . "/api/" . $tabla;

        // Si se paso un limite, agregarlo como query string
        // 'http://localhost:8000/api/producto' + '?limite=50'
        if ($limite) {
            $url .= "?limite=" . $limite;
        }

        // --- HACER LA PETICION HTTP CON cURL ---

        // curl_init($url) = crear una sesion cURL apuntando a esa URL.
        // $ch = "cURL handle" (el identificador de la sesion). Se usa para configurarla.
        $ch = curl_init($url);

        // curl_setopt() = configurar una opcion de cURL.
        // CURLOPT_RETURNTRANSFER = true: que curl_exec() RETORNE la respuesta como string
        //   en vez de imprimirla directamente en la pantalla.
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // CURLOPT_TIMEOUT = 5: si la API no responde en 5 segundos, cancelar.
        //   Evita que la pagina se quede colgada si la API esta caida.
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        // curl_exec() = EJECUTAR la peticion HTTP. Envia el GET y espera la respuesta.
        //   $respuesta = el JSON que devolvio la API (como string).
        //   Si falla (API caida, timeout), devuelve false.
        $respuesta = curl_exec($ch);

        // curl_close() = cerrar la sesion cURL (liberar recursos).
        curl_close($ch);

        // Si la respuesta es false (fallo la conexion), devolver array vacio
        if (!$respuesta) return [];

        // json_decode() = convertir el string JSON a un array PHP.
        //   '{"datos":[{"codigo":"PR001",...}]}' -> ['datos' => [['codigo'=>'PR001',...]]]
        //   El "true" significa: convertir a array asociativo (no a objeto).
        $json = json_decode($respuesta, true);

        // La API devuelve los datos dentro de la clave 'datos'.
        //   $json['datos'] = el array de registros.
        //   ?? [] = si no existe la clave 'datos', devolver array vacio.
        return $json['datos'] ?? [];
    }

    // =======================================================================
    // obtenerPorClave() — GET /api/{tabla}/{clave}/{valor} — Traer uno
    // =======================================================================
    // Ejemplo: $api->obtenerPorClave('producto', 'codigo', 'PR001')
    //   -> Hace GET http://localhost:8000/api/producto/codigo/PR001
    //   -> Retorna: [['codigo'=>'PR001','nombre'=>'Laptop','stock'=>15,...]]
    //
    // Se usa en factura.php para traer los detalles de una factura especifica.
    public function obtenerPorClave($tabla, $nombreClave, $valorClave) {
        // URL: 'http://localhost:8000/api/producto/codigo/PR001'
        $url = $this->baseUrl . "/api/" . $tabla . "/" . $nombreClave . "/" . $valorClave;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $respuesta = curl_exec($ch);
        curl_close($ch);

        if (!$respuesta) return [];

        $json = json_decode($respuesta, true);
        return $json['datos'] ?? [];
    }

    // =======================================================================
    // crear() — POST /api/{tabla} — Insertar un registro nuevo
    // =======================================================================
    // Ejemplo: $api->crear('producto', ['codigo'=>'PR099','nombre'=>'Test','stock'=>5,'valorunitario'=>1000])
    //   -> Hace POST http://localhost:8000/api/producto con body JSON
    //   -> Retorna: ['exito' => true, 'mensaje' => 'Registro creado exitosamente.']
    //
    // $datos = array asociativo con los campos y valores a insertar.
    //   json_encode() lo convierte a JSON para enviarlo en el body de la peticion.
    public function crear($tabla, $datos) {
        $url = $this->baseUrl . "/api/" . $tabla;

        $ch = curl_init($url);

        // CURLOPT_POST = true: hacer una peticion POST (no GET).
        curl_setopt($ch, CURLOPT_POST, true);

        // CURLOPT_POSTFIELDS = el cuerpo (body) de la peticion.
        // json_encode($datos) convierte el array PHP a un string JSON:
        //   ['codigo'=>'PR099','nombre'=>'Test'] -> '{"codigo":"PR099","nombre":"Test"}'
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos));

        // CURLOPT_HTTPHEADER = los headers HTTP a enviar.
        // 'Content-Type: application/json' le dice a la API:
        //   "El body que te estoy mandando esta en formato JSON."
        //   Sin esto, la API no sabe como interpretar el body.
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta = curl_exec($ch);

        // curl_getinfo() = obtener informacion de la peticion que se hizo.
        // CURLINFO_HTTP_CODE = el codigo HTTP que devolvio la API (200, 400, 500, etc.)
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Parsear la respuesta JSON de la API
        $json = json_decode($respuesta, true);

        // Armar el mensaje de respuesta
        $mensaje = $json['mensaje'] ?? 'Operacion completada.';
        // Si la API devolvio un detalle extra, agregarlo al mensaje
        if (!empty($json['detalle'])) {
            $mensaje .= ' ' . $json['detalle'];  // Concatenar con un espacio
        }

        // Determinar si fue exitoso: codigos 200-299 son exito
        //   200 = OK, 201 = Created, etc.
        //   400 = error de datos, 500 = error de servidor -> NO exito
        $exito = $httpCode >= 200 && $httpCode < 300;

        // Retornar un array con el resultado para que la pagina sepa que paso
        return ['exito' => $exito, 'mensaje' => $mensaje];
    }

    // =======================================================================
    // actualizar() — PUT /api/{tabla}/{clave}/{valor} — Modificar registro
    // =======================================================================
    // Ejemplo: $api->actualizar('producto', 'codigo', 'PR001', ['stock'=>99])
    //   -> Hace PUT http://localhost:8000/api/producto/codigo/PR001 con body JSON
    //   -> Retorna: ['exito' => true, 'mensaje' => 'Registro actualizado exitosamente.']
    public function actualizar($tabla, $nombreClave, $valorClave, $datos) {
        $url = $this->baseUrl . "/api/" . $tabla . "/" . $nombreClave . "/" . $valorClave;

        $ch = curl_init($url);

        // CURLOPT_CUSTOMREQUEST = 'PUT': cURL no tiene una opcion directa para PUT
        // (como si tiene CURLOPT_POST para POST), asi que se usa CUSTOMREQUEST.
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($datos)); // Body con los datos nuevos
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($respuesta, true);
        $mensaje = $json['mensaje'] ?? 'Operacion completada.';
        if (!empty($json['detalle'])) {
            $mensaje .= ' ' . $json['detalle'];
        }
        $exito = $httpCode >= 200 && $httpCode < 300;

        return ['exito' => $exito, 'mensaje' => $mensaje];
    }

    // =======================================================================
    // eliminar() — DELETE /api/{tabla}/{clave}/{valor} — Borrar registro
    // =======================================================================
    // Ejemplo: $api->eliminar('producto', 'codigo', 'PR099')
    //   -> Hace DELETE http://localhost:8000/api/producto/codigo/PR099
    //   -> Retorna: ['exito' => true, 'mensaje' => 'Registro eliminado exitosamente.']
    //
    // DELETE no envia body (no necesita datos, solo saber QUE borrar via la URL).
    public function eliminar($tabla, $nombreClave, $valorClave) {
        $url = $this->baseUrl . "/api/" . $tabla . "/" . $nombreClave . "/" . $valorClave;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE'); // Peticion DELETE
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respuesta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($respuesta, true);
        $mensaje = $json['mensaje'] ?? 'Operacion completada.';
        if (!empty($json['detalle'])) {
            $mensaje .= ' ' . $json['detalle'];
        }
        $exito = $httpCode >= 200 && $httpCode < 300;

        return ['exito' => $exito, 'mensaje' => $mensaje];
    }
}
?>
