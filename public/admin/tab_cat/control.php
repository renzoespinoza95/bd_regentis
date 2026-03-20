<?php
// este es mi backend usando php8.2, flightphp y meekrodb2

/* ============================================================
   VISTA PRINCIPAL
   ============================================================ */
Flight::route('GET /cat', function () {

    include DEFINITION;
    autentificar_administrador();

    global $path_public;

    include $path_public . '/admin/tab_cat/inicio.php';

});


/* ============================================================
   LISTAR CATEGORÍAS
   SOLO DEL NEGOCIO DEL ADMINISTRADOR
   ============================================================ */
Flight::route('GET /category/listar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    $neg_id = intval($administrador_actual['neg_id']);

    $rows = DB::query("
        SELECT
            category_id AS id,
            category_id,
            neg_id,
            name,
            icon,
            brief,
            color,
            priority,
            created_at,
            last_update
        FROM pos_category
        WHERE neg_id = %i
        ORDER BY category_id DESC
    ", $neg_id);

    Flight::json($rows);

});


/* ============================================================
   CREAR CATEGORÍA
   ============================================================ */
Flight::route('POST /category/crear', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    $now = time()*1000;

    $neg_id = intval($administrador_actual['neg_id']);

    DB::insert('pos_category', [

        'neg_id'      => $neg_id,
        'name'        => $d['name'],
        'icon'        => "icon_cat.jpg",
        'brief'       => $d['brief'],
        'color'       => $d['color'],
        'priority'    => 0,
        'created_at'  => $now,
        'last_update' => $now

    ]);

    Flight::json([
        'status' => 'ok'
    ]);

});


/* ============================================================
   EDITAR CATEGORÍA
   SOLO SI PERTENECE AL NEGOCIO
   ============================================================ */
Flight::route('POST /category/editar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    $now = time()*1000;

    $neg_id = intval($administrador_actual['neg_id']);

    DB::update(
        'pos_category',
        [
            'name'        => $d['name'],
            'brief'       => $d['brief'],
            'color'       => $d['color'],
            'last_update' => $now
        ],
        "category_id=%i AND neg_id=%i",
        $d['category_id'],
        $neg_id
    );

    Flight::json([
        'status'=>'ok'
    ]);

});


/* ============================================================
   ELIMINAR CATEGORÍA
   SOLO SI PERTENECE AL NEGOCIO
   ============================================================ */
Flight::route('POST /category/eliminar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    $neg_id = intval($administrador_actual['neg_id']);

    DB::delete(
        'pos_category',
        "category_id=%i AND neg_id=%i",
        $d['category_id'],
        $neg_id
    );

    Flight::json([
        'status'=>'ok'
    ]);

});