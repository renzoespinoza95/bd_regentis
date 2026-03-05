<?php
/* -------------------------------
 * Vista /tab3
 * ------------------------------- */
Flight::route('GET /prov', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;                          // asegúrate de tener esta var en tu bootstrap
    include $path_public . '/admin/tab_proveedores/inicio.php';
});

/* ============================================
 * VISTA PRINCIPAL /proveedores
 * ============================================ */
Flight::route('GET /proveedores', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();

    global $path_public;
    include $path_public . '/admin/tab_proveedores/inicio.php';
});


/* ============================================
 * GET /proveedor/listar
 * ============================================ */
Flight::route('GET /proveedor/listar', function () {
    $rows = DB::query("
        SELECT proveedor_id, nombre, ruc, direccion, telefono, email, is_activo
        FROM proveedor
        ORDER BY proveedor_id DESC
    ");
    Flight::json($rows);
});


/* ============================================
 * POST /proveedor/crear
 * ============================================ */
Flight::route('POST /proveedor/crear', function () {

    $d = Flight::request()->data;

    DB::insert("proveedor", [
        'nombre'    => $d['nombre'],
        'ruc'       => $d['ruc'],
        'direccion' => $d['direccion'],
        'telefono'  => $d['telefono'],
        'email'     => $d['email'],
        'is_activo' => 1
    ]);

    Flight::json(['status'=>'ok']);
});


/* ============================================
 * GET /proveedor/detalle/@id
 * ============================================ */
Flight::route('GET /proveedor/detalle/@id', function ($id) {

    $item = DB::queryFirstRow("
        SELECT *
        FROM proveedor
        WHERE proveedor_id=%i
    ", $id);

    Flight::json($item);
});


/* ============================================
 * POST /proveedor/editar
 * ============================================ */
Flight::route('POST /proveedor/editar', function () {

    $d = Flight::request()->data;

    DB::update("proveedor", [
        'nombre'    => $d['nombre'],
        'ruc'       => $d['ruc'],
        'direccion' => $d['direccion'],
        'telefono'  => $d['telefono'],
        'email'     => $d['email'],
        'is_activo' => $d['is_activo']
    ], "proveedor_id=%i", $d['proveedor_id']);

    Flight::json(['status'=>'ok']);
});


/* ============================================
 * POST /proveedor/eliminar
 * ============================================ */
Flight::route('POST /proveedor/eliminar', function () {

    $id = intval(Flight::request()->data->proveedor_id);

    DB::delete("proveedor", "proveedor_id=%i", $id);

    Flight::json(['status'=>'ok']);
});

