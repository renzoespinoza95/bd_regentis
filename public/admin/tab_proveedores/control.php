<?php
/* -------------------------------
 * Vista /prov
 * ------------------------------- */
Flight::route('GET /prov', function () {

    include DEFINITION;
    autentificar_administrador();

    require_once VARPATH . '/public/admin/tab_proveedores/inicio.php';

});

/* ============================================
 * GET /pos_proveedor/listar
 * ============================================ */
Flight::route('GET /proveedor/listar', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $rows = DB::query("
        SELECT 
            proveedor_id,
            nombre,
            ruc,
            direccion,
            telefono,
            email,
            is_activo
        FROM pos_proveedor
        WHERE neg_id=%i
        ORDER BY proveedor_id DESC
    ",$neg_id);

    Flight::json($rows);

});


/* ============================================
 * POST /pos_proveedor/crear
 * ============================================ */
Flight::route('POST /proveedor/crear', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data;

    DB::insert("pos_proveedor", [

        'neg_id'     => $neg_id,
        'nombre'     => $d['nombre'],
        'ruc'        => $d['ruc'],
        'direccion'  => $d['direccion'],
        'telefono'   => $d['telefono'],
        'email'      => $d['email'],
        'is_activo'  => 1

    ]);

    Flight::json(['status'=>'ok']);

});


/* ============================================
 * GET /pos_proveedor/detalle/@id
 * ============================================ */
Flight::route('GET /proveedor/detalle/@id', function ($id) {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $item = DB::queryFirstRow("

        SELECT *
        FROM pos_proveedor
        WHERE proveedor_id=%i
        AND neg_id=%i

    ",$id,$neg_id);

    Flight::json($item);

});


/* ============================================
 * POST /pos_proveedor/editar
 * ============================================ */
Flight::route('POST /proveedor/editar', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data;

    DB::update("pos_proveedor",[

        'nombre'     => $d['nombre'],
        'ruc'        => $d['ruc'],
        'direccion'  => $d['direccion'],
        'telefono'   => $d['telefono'],
        'email'      => $d['email'],
        'is_activo'  => $d['is_activo']

    ],"proveedor_id=%i AND neg_id=%i",$d['proveedor_id'],$neg_id);

    Flight::json(['status'=>'ok']);

});


/* ============================================
 * POST /pos_proveedor/eliminar
 * ============================================ */
Flight::route('POST /proveedor/eliminar', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $id = intval(Flight::request()->data->proveedor_id);

    DB::delete(
        "pos_proveedor",
        "proveedor_id=%i AND neg_id=%i",
        $id,
        $neg_id
    );

    Flight::json(['status'=>'ok']);

});
