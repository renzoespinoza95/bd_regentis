<?php
// Backend PHP 8.1 + FlightPHP + MeekroDB2

/* =========================================================
   INICIO
========================================================= */
Flight::route('GET /variables/inicio', function () {
    include DEFINITION;
    autentificar_administrador();
    include VARPATH . '/public/admin/tab_vari/inicio.php';
});


/* =========================================================
   LISTAR
========================================================= */
Flight::route('GET /variables/listar', function() {

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("
        SELECT 
            vari_id,
            nombre,
            valor
        FROM reg_vari
        ORDER BY vari_id DESC
    ");

    Flight::json([
        'status' => 'ok',
        'data' => $rows
    ]);

});


/* =========================================================
   OBTENER POR ID
========================================================= */
Flight::route('GET /variables/obtener/@id', function($id) {

    DB::query("SET NAMES 'utf8mb4'");

    $row = DB::queryFirstRow(
        "SELECT 
            vari_id,
            nombre,
            valor
         FROM reg_vari 
         WHERE vari_id = %i",
        $id
    );

    if(!$row){
        Flight::json([
            'status' => 'error',
            'msg' => 'Registro no encontrado'
        ], 404);
        return;
    }

    Flight::json([
        'status' => 'ok',
        'data' => $row
    ]);

});


/* =========================================================
   EDITAR
========================================================= */
Flight::route('POST /variables/editar', function() {

    include DEFINITION;

    $data = Flight::request()->data->getData();

    if(empty($data['vari_id'])){
        Flight::json([
            'status' => 'error',
            'msg' => 'vari_id requerido'
        ], 400);
        return;
    }

    DB::update(
        'reg_vari',
        [
            'nombre' => $data['nombre'],
            'valor'  => $data['valor']
        ],
        "vari_id=%i",
        $data['vari_id']
    );

    Flight::json([
        'status' => 'ok'
    ]);

});


/* =========================================================
   ELIMINAR
========================================================= */
Flight::route('POST /variables/eliminar', function() {

    include DEFINITION;

    $data = Flight::request()->data->getData();

    if(empty($data['vari_id'])){
        Flight::json([
            'status' => 'error',
            'msg' => 'vari_id requerido'
        ], 400);
        return;
    }

    DB::delete(
        'reg_vari',
        "vari_id=%i",
        $data['vari_id']
    );

    Flight::json([
        'status' => 'ok'
    ]);

});


/* =========================================================
   YUTU - LISTA CATEGORIAS
========================================================= */
Flight::route('GET /yutu/listaCategorias', function () {
    
    include DEFINITION;

    // 🔥 usar base secundaria
    $indie->query("USE bd_indie");

    try {

        $data = $indie->query("
            SELECT 
                cat_pag_web_id,
                clave_txt,
                titulo,
                metatag01,
                metatag02,
                url_img,
                is_visible,
                orden,
                neg_id
            FROM reg_cat_pag_web
            ORDER BY orden ASC, cat_pag_web_id ASC
        ");

        Flight::json([
            'success' => true,
            'data' => $data
        ]);

    } catch (Exception $e) {

        Flight::json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);

    }

});


/* =========================================================
   CREAR
========================================================= */
Flight::route('POST /variables/crear', function() {

    include DEFINITION;

    $data = Flight::request()->data->getData();

    if(empty($data['nombre'])){
        Flight::json([
            'status' => 'error',
            'msg' => 'nombre requerido'
        ], 400);
        return;
    }

    DB::insert('reg_vari', [
        'nombre' => $data['nombre'],
        'valor'  => $data['valor'] ?? ''
    ]);

    Flight::json([
        'status' => 'ok',
        'vari_id' => DB::insertId()
    ]);

});