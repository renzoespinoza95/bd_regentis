<?php
// este es mi backend usando php8.1, flightphp y meekrodb2 
Flight::route('GET /variables/inicio', function () {
    include DEFINITION;
    autentificar_administrador();
    global $path_public;
    include $path_public . '/admin/tab_vari/inicio.php';
});

/* ============================
   LISTAR
============================ */
Flight::route('GET /variables/listar', function() {
    DB::query("SET NAMES 'utf8'");
    $rows = DB::query("SELECT * FROM reg_vari ORDER BY variables_sistema_id DESC");

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});

/* ============================
   OBTENER POR ID
============================ */
Flight::route('GET /variables/obtener/@id', function($id) {

    DB::query("SET NAMES 'utf8'");
    $row = DB::queryFirstRow(
        "SELECT * FROM reg_vari WHERE variables_sistema_id = %i",
        $id
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($row, JSON_UNESCAPED_UNICODE);
});

/* ============================
   EDITAR
============================ */
Flight::route('POST /variables/editar', function() {

    $data = Flight::request()->data->getData();

    DB::update(
        'variables_sistema',
        [
            'nombre_variable' => $data['nombre_variable'],
            'valor' => $data['valor']
        ],
        "variables_sistema_id=%i",
        $data['variables_sistema_id']
    );

    Flight::json(['status'=>'ok']);
});

/* ============================
   ELIMINAR
============================ */
Flight::route('POST /variables/eliminar', function() {

    $data = Flight::request()->data->getData();

    DB::delete(
        'variables_sistema',
        "variables_sistema_id=%i",
        $data['variables_sistema_id']
    );

    Flight::json(['status'=>'ok']);
});
