<?php
// ============================================================================
// programa.php — CRUD para la tabla Programa
// Ubicacion: pages/programa.php
//
// PARA QUE SIRVE: Listar, crear, editar y eliminar areas de conocimiento.
// Campos: id (PK UUID), gran_area, area, disciplina
//
// PATRON QUE SIGUEN TODAS LAS PAGINAS CRUD SIMPLES:
//   1. Definir variables: $paginaActual, $tituloPagina, $tabla, $clave
//   2. Incluir header.php y ApiService.php
//   3. Si es POST: procesar la accion (crear/actualizar/eliminar)
//      -> Guardar mensaje en $_SESSION -> Redirigir (PRG)
//   4. Si es GET: cargar datos con $api->listar()
//      -> Si ?accion=nuevo: mostrar formulario vacio
//      -> Si ?accion=editar&clave=X: mostrar formulario con datos
//      -> Si sin accion: mostrar tabla con todos los registros
//   5. Incluir footer.php
//
// Las paginas persona, usuario, empresa, rol, ruta siguen ESTE MISMO PATRON.
// Solo cambian: $tabla, $clave, y los campos del formulario/tabla HTML.
//
// ESTRUCTURA DE ESTE ARCHIVO:
//   Lineas 35-77:   Seccion PHP - procesar POST (crear/actualizar/eliminar)
//   Lineas 79-103:  Seccion PHP - leer parametros GET y cargar datos
//   Lineas 110-206: Seccion HTML - interfaz visual (formulario + tabla)
// ============================================================================

// ── PASO 1: CARGAR SERVICIO API Y DEFINIR TABLA/CLAVE ─────────────────
// Se cargan ANTES del POST para que el bloque POST pueda usarlos
// sin necesidad de incluir header.php (que genera HTML).
require_once __DIR__ . '/../services/ApiService.php';

// $api = el objeto que usamos para hablar con la API
$api = new ApiService();

// $tabla = nombre de la tabla en la BD. Se usa en TODOS los metodos de ApiService.
// $clave = nombre de la columna clave primaria. Se usa para editar y eliminar.
// Si esta pagina fuera para "persona", seria: $tabla='persona', $clave='codigo'
// Si fuera para "rol", seria: $tabla='rol', $clave='id'
$tabla = 'programa';
$clave = 'id';

// ══════════════════════════════════════════════════════════════════════════
// PASO 2: PROCESAR ACCIONES POST (crear, actualizar, eliminar)
// ══════════════════════════════════════════════════════════════════════════
// Esta seccion SOLO se ejecuta cuando el usuario envio un formulario (POST).
// Cuando simplemente abre la pagina (GET), esta seccion se salta.
//
// $_SERVER['REQUEST_METHOD'] contiene el verbo HTTP: 'GET' o 'POST'.
// 'GET'  = el usuario abrio la pagina (clic en link, o escribio la URL)
// 'POST' = el usuario envio un formulario (clic en "Guardar" o "Eliminar")

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();

    // $_POST es un array superglobal con todos los datos del formulario.
    // 'accion_post' viene de un <input type="hidden" name="accion_post" value="crear">
    // que esta en el formulario HTML (mas abajo). Le dice a PHP QUE hacer.
    $accionPost = $_POST['accion_post'] ?? '';

    // ── CREAR ──────────────────────────────────────────────────────────
    if ($accionPost === 'crear') {
        // Armar el array de datos que se va a enviar a la API.
        // Los campos vienen del formulario: gran_area, area y disciplina.
        $datos = [
            'cant_graduados'  => $_POST['cant_graduados'] ?? '',
            'ciudad'       => $_POST['ciudad'] ?? '',
            'facultad' => $_POST['facultad'] ?? '',
            'fecha_cierre' => $_POST['fecha_cierre'] ?? '',
            'nivel' => $_POST['nivel'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'numero_cohortes' => $_POST['numero_cohortes'] ?? '',
        ];

        // Llamar a la API: POST http://localhost:8000/api/programa con los datos
        // $resultado = ['exito' => true/false, 'mensaje' => 'Registro creado exitosamente.']
        $resultado = $api->crear($tabla, $datos);

        // Guardar el resultado en la sesion para mostrarlo despues del redirect.
        // $_SESSION['mensaje'] = el texto que aparece en la alerta (verde o roja)
        // $_SESSION['tipo'] = 'success' (verde) si fue exitoso, 'danger' (rojo) si fallo
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    // ── ACTUALIZAR ─────────────────────────────────────────────────────
    if ($accionPost === 'actualizar') {
        // $valor = el id del registro a actualizar (viene del formulario)
        $valor = $_POST['id'] ?? '';

        // Solo enviar los campos que se pueden cambiar (no el id, que es la PK)
        $datos = [
            'cant_graduados'  => $_POST['cant_graduados'] ?? '',
            'ciudad'       => $_POST['ciudad'] ?? '',
            'facultad' => $_POST['facultad'] ?? '',
            'fecha_cierre' => $_POST['fecha_cierre'] ?? '',
            'nivel' => $_POST['nivel'] ?? '',
            'nombre' => $_POST['nombre'] ?? '',
            'numero_cohortes' => $_POST['numero_cohortes'] ?? ''
        ];

        // Llamar a la API: PUT http://localhost:8000/api/programa/id/{UUID}
        $resultado = $api->actualizar($tabla, $clave, $valor, $datos);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    // ── ELIMINAR ───────────────────────────────────────────────────────
    if ($accionPost === 'eliminar') {
        $valor = $_POST['id'] ?? '';

        // Llamar a la API: DELETE http://localhost:8000/api/programa/id/{UUID}
        $resultado = $api->eliminar($tabla, $clave, $valor);
        $_SESSION['mensaje'] = $resultado['mensaje'];
        $_SESSION['tipo'] = $resultado['exito'] ? 'success' : 'danger';
    }

    // ── REDIRIGIR (patron PRG: Post/Redirect/Get) ──────────────────────
    // Despues de procesar el POST, redirigimos a la misma pagina con GET.
    // Esto evita que al refrescar (F5), el navegador reenvie el formulario.
    //
    // Sin PRG: F5 -> "¿Desea reenviar el formulario?" -> duplica la accion
    // Con PRG: F5 -> simplemente recarga la pagina normalmente
    header('Location: programa.php');
    exit;  // IMPORTANTE: sin exit, PHP sigue ejecutando el codigo de abajo
}

// ── PASO 3: CONFIGURAR LA PAGINA ──────────────────────────────────────
// Estas variables las usa header.php para el sidebar y el titulo.
$paginaActual = 'Programa';   // Resalta "Gran Area" en el sidebar
$tituloPagina = 'Programa';  // Aparece en la pestana del navegador (<title>)

// Incluir el header (sidebar, Bootstrap, sesion, mensajes flash)
require __DIR__ . '/../includes/header.php';

// ══════════════════════════════════════════════════════════════════════════
// PASO 4: LEER PARAMETROS GET Y CARGAR DATOS
// ══════════════════════════════════════════════════════════════════════════
// Esta seccion se ejecuta en TODAS las peticiones (GET y POST, pero POST
// ya salio con exit arriba, asi que en la practica solo se ejecuta en GET).

// Leer parametros del query string (?accion=editar&clave={UUID})
// $_GET es un array superglobal con los parametros de la URL.
$accion = $_GET['accion'] ?? '';        // ?accion=editar -> 'editar', si no viene -> ''
$valorClave = $_GET['clave'] ?? '';     // ?clave=PR001 -> 'PR001'

// Llamar a la API para obtener TODOS los registros.
// GET http://localhost:8000/api/programa
// $registros = [['id'=>'...','gran_area'=>'...','area'=>'...'], ...]
$registros = $api->listar($tabla);

// Determinar si debemos mostrar el formulario o la tabla.
// in_array('editar', ['nuevo', 'editar']) -> true (esta en la lista)
// in_array('', ['nuevo', 'editar']) -> false (no esta)
$mostrarFormulario = in_array($accion, ['nuevo', 'editar']);

// ¿Estamos editando un registro existente?
$editando = $accion === 'editar';

// Si estamos editando, buscar el registro en el array de registros.
// No hacemos otra llamada a la API — buscamos en los datos que ya tenemos.
$registro = null;
if ($editando && $valorClave) {
    // Recorrer todos los registros hasta encontrar el que tiene el id buscado
    foreach ($registros as $r) {
        // $r[$clave] = $r['id'] (porque $clave = 'id')
        if (($r[$clave] ?? '') == $valorClave) {
            $registro = $r;  // Encontrado, guardar el registro
            break;           // Dejar de buscar (ya lo encontro)
        }
    }
}
// Despues de esto:
//   $registro = ['id'=>'...','gran_area'=>'...','area'=>'...','disciplina'=>'...']
//   O $registro = null si no se encontro

// ══════════════════════════════════════════════════════════════════════════
// A PARTIR DE ACA: HTML
// La etiqueta de cierre PHP de abajo termina la seccion de codigo.
// Todo lo que sigue es HTML con pedazos de PHP para imprimir valores.
// ══════════════════════════════════════════════════════════════════════════
?>

<!-- ══════════════════════════════════════════════ -->
<!-- HTML: INTERFAZ DE USUARIO                      -->
<!-- ══════════════════════════════════════════════ -->

<!-- container = clase Bootstrap que centra el contenido con margenes laterales -->
<!-- mt-4 = margin-top 4 (espacio arriba) -->
<div class="container mt-4">
    <h3>Programa</h3>

    <!-- ───────── BOTON "NUEVA GRAN AREA" ───────── -->
    <!-- Solo se muestra si NO estamos en el formulario (para no tener dos formularios) -->
    <!-- La sintaxis "if(): ... endif;" es la forma alternativa de PHP para if(){...}.
         Se usa cuando hay bloques grandes de HTML en el medio.
         Es mas legible que abrir y cerrar llaves con HTML mezclado. -->
    <?php if (!$mostrarFormulario): ?>
        <!-- Este link lleva a programa.php?accion=nuevo, que activa el formulario vacio -->
        <!-- btn btn-primary = boton azul de Bootstrap -->
        <!-- mb-3 = margin-bottom 3 (espacio abajo) -->
        <a href="programa.php?accion=nuevo" class="btn btn-primary mb-3">Nueva Área de Conocimiento</a>
    <?php endif; ?>

    <!-- ───────── FORMULARIO (CREAR / EDITAR) ───────── -->
    <!-- Se muestra SOLO si $mostrarFormulario es true (accion=nuevo o accion=editar) -->
    <?php if ($mostrarFormulario): ?>
        <!-- card = componente Bootstrap tipo "tarjeta" con borde, cabecera y cuerpo -->
        <div class="card mb-3">
            <div class="card-header">
                <!-- Operador ternario: condicion ? valor_si_true : valor_si_false
                     Si estamos editando muestra "Editar Programa", si no "Nueva Programa" -->
                <?= $editando ? "Editar Programa" : "Nueva Programa" ?>
            </div>
            <div class="card-body">
                <!-- method="POST" = los datos van en el body HTTP (no visibles en la URL) -->
                <!-- action="programa.php" = a donde se envia el formulario (esta misma pagina) -->
                <!-- onsubmit: si estamos editando, muestra dialogo de confirmacion.
                     confirm() devuelve true (Aceptar) o false (Cancelar).
                     "return false" cancela el envio del formulario. -->
                <form method="POST" action="programa.php"
                      onsubmit="<?= $editando ? "return confirm('¿Está seguro de actualizar el Programa?')" : '' ?>">

                    <!-- Input hidden: dato que se envia pero NO se ve en pantalla.
                         Le dice a la seccion POST de arriba QUE hacer: 'crear' o 'actualizar'.
                         name="accion_post" = la clave en $_POST['accion_post']
                         value="crear" o "actualizar" = el valor que recibe PHP -->
                    <input type="hidden" name="accion_post" value="<?= $editando ? 'actualizar' : 'crear' ?>" />

                    <?php if ($editando): ?>
                        <input type="hidden" name="id" value="<?= $registro['id'] ?? '' ?>" />
                    <?php endif; ?>

                    <!-- row + col-md-6 = grid de Bootstrap.
                         row = fila contenedora.
                         col-md-6 = ocupa 6 de 12 columnas en pantallas medianas (50%).
                         En desktop: 2 campos por fila. En movil: 1 campo por fila. -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nombre</label>
                            <input class="form-control" name="nombre"
                                value="<?= $registro['nombre'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Facultad</label>
                            <input class="form-control" name="facultad"
                                value="<?= $registro['facultad'] ?? '' ?>" />
                        </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nivel</label>
                                <input class="form-control" name="nivel"
                                    value="<?= $registro['nivel'] ?? '' ?>" />
                            </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ciudad</label>
                            <input class="form-control" name="ciudad"
                                value="<?= $registro['ciudad'] ?? '' ?>" />
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label">Cantidad de Graduados</label>
                            <input class="form-control" name="cant_graduados"
                                value="<?= $registro['cant_graduados'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Número de Cohortes</label>
                            <input class="form-control" name="numero_cohortes"
                                value="<?= $registro['numero_cohortes'] ?? '' ?>" />
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fecha de Cierre</label>
                            <input class="form-control" type="date" name="fecha_cierre"
                                value="<?= $registro['fecha_cierre'] ?? '' ?>" />
                        </div>

                    </div>
                    <!-- Boton Guardar: type="submit" = al hacer clic, envia el formulario (POST) -->
                    <!-- btn-success = boton verde de Bootstrap -->
                    <button class="btn btn-success me-2" type="submit">Guardar</button>
                    <!-- Boton Cancelar: es un <a> (link), NO un boton de submit.
                         No envia nada, simplemente vuelve a programa.php (la lista). -->
                    <!-- btn-secondary = boton gris de Bootstrap -->
                    <a href="programa.php" class="btn btn-secondary">Cancelar</a>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ───────── TABLA DE REGISTROS ───────── -->
    <!-- Se muestra siempre (tanto si el formulario esta visible como si no) -->
    <!-- !empty($registros) = verificar que hay datos para mostrar -->
    <?php if (!empty($registros)): ?>
        <!-- table = tabla HTML con estilos Bootstrap -->
        <!-- table-striped = filas alternando color (gris/blanco) para facilitar lectura -->
        <!-- table-hover = la fila se resalta cuando pasas el mouse encima -->
        <table class="table table-striped table-hover">
            <!-- thead = cabecera de la tabla (fila de titulos) -->
            <!-- table-dark = fondo oscuro para la cabecera -->
            <thead class="table-dark">
                <tr>
                    <th>Nombre</th>
                    <th>Nivel</th>
                    <th>Ciudad</th>
                    <th>Facultad</th>
                    <th>Cant. Graduados</th>
                    <th>Número de Cohortes</th>
                    <th>Fecha de Cierre</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <!-- tbody = cuerpo de la tabla (las filas de datos) -->
            <tbody>
                <!-- foreach recorre cada registro y genera una fila <tr> por cada Programa.
                     $reg = un Programa: ['id'=>'...','gran_area'=>'...','area'=>'...','disciplina'=>'...']
                     Se genera tanto HTML como areas de conocimiento haya en el array. -->
                <?php foreach ($registros as $reg): ?>
                <tr>
                    <!-- <?= $reg['id'] ?> imprime el valor de la columna 'id' de este registro -->
                    <td><?= $reg['nombre'] ?? '' ?></td>
                    <td><?= $reg['nivel'] ?? '' ?></td>
                    <td><?= $reg['ciudad'] ?? '' ?></td>
                    <td><?= $reg['facultad'] ?? '' ?></td>
                    <td><?= $reg['cant_graduados'] ?? '' ?></td>
                    <td><?= $reg['num_cohortes'] ?? '' ?></td>
                    <td><?= $reg['fecha_cierre'] ?? '' ?></td>
                    <td>
                        <!-- Boton Editar: es un link GET que abre el formulario con datos pre-llenados.
                             La URL lleva ?accion=editar&clave=PR001 para que la seccion PHP
                             de arriba sepa que mostrar el formulario con datos de PR001. -->
                        <!-- btn-warning = boton amarillo. btn-sm = boton pequeno -->
                        <!-- me-1 = margin-end 1 (espacio entre este boton y el siguiente) -->
                                <a href="programa.php?accion=editar&clave=<?= $reg['id'] ?>"
                           class="btn btn-warning btn-sm me-1">Editar</a>

                        <!-- Boton Eliminar: es un formulario POST (no un link GET).
                             Se usa POST porque eliminar es una accion que MODIFICA datos.
                             Los links GET no deberian modificar datos (convencion HTTP). -->
                        <!-- style="display:inline" = que el form quede al lado del boton Editar,
                             no en una linea nueva (por defecto <form> es display:block). -->
                        <!-- onsubmit="return confirm(...)" = dialogo de confirmacion:
                             confirm() muestra un popup con Aceptar/Cancelar.
                             Si clic "Aceptar" -> confirm() devuelve true -> el form se envia.
                             Si clic "Cancelar" -> confirm() devuelve false -> return false -> NO se envia. -->
                        <form method="POST" action="programa.php" style="display:inline"
                              onsubmit="return confirm('¿Está seguro de eliminar el Programa\'<?= $reg['gran_area'] ?? '' ?>\'?')">
                            <!-- Estos inputs hidden le dicen a la seccion POST:
                                 accion_post='eliminar' = que hacer
                                 codigo='PR001' = que registro eliminar -->
                            <input type="hidden" name="accion_post" value="eliminar" />
                            <input type="hidden" name="id" value="<?= $reg['id'] ?? '' ?>" />
                            <!-- btn-danger = boton rojo de Bootstrap -->
                            <button class="btn btn-danger btn-sm" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <!-- Si no hay registros, mostrar un mensaje de advertencia -->
        <!-- alert alert-warning = caja amarilla de Bootstrap -->
        <div class="alert alert-warning">No se encontraron registros en la tabla programa.</div>
    <?php endif; ?>

</div>

<!-- Incluir el footer: cierra los tags HTML que abrio header.php y ejecuta ob_end_flush() -->
<?php require __DIR__ . '/../includes/footer.php'; ?>
