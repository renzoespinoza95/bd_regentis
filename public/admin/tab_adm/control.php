<?php
/* -------------------------------
 // este es mi backend usando php8.2, flightphp y meekrodb2
 * ------------------------------- */

Flight::route('GET /administradores', function () {

    include DEFINITION;

    // verificar sesión
    global $sesion_admin_administrador_id, $nombre_app, $apphost;

    if(empty($sesion_admin_administrador_id)){
        Flight::redirect($apphost . "/loginVault");
        exit;
    }

    global $path_public;
    include $path_public . '/admin/tab_adm/inicio.php';

});


//=========================
// CREAR ADMIN
//=========================
Flight::route('POST /admin/crear', function () {

    $d = Flight::request()->data->getData();

    DB::insert('reg_usu', [
        'nombres_apellidos' => $d['nombres_apellidos'],
        'email'             => $d['email'],
        'clavel'            => $d['clavel'],
        'fecha_creacion'    => date('Y-m-d H:i:s'),
        'is_activo'         => 1,
        'rol_id'            => intval($d['rol_id'])
    ]);

    Flight::json(['status'=>'ok']);

});


//=========================
// EDITAR ADMIN
//=========================
Flight::route('POST /admin/editar', function () {

    $d = Flight::request()->data->getData();

    $data = [
        'nombres_apellidos' => $d['nombres_apellidos'],
        'email'             => $d['email'],
        'is_activo'         => intval($d['is_activo']),
        'rol_id'            => intval($d['rol_id'])
    ];

    if (!empty($d['clavel'])) {
        $data['clavel'] = $d['clavel'];
    }

    DB::update('reg_usu', $data, "usu_id=%i", intval($d['usu_id']));

    Flight::json(['status'=>'ok']);

});


//=========================
// DESACTIVAR ADMIN
//=========================
Flight::route('POST /admin/eliminar', function () {

    $id = intval(Flight::request()->data->usu_id);

    DB::update('reg_usu', [
        'is_activo' => 0
    ], "usu_id=%i", $id);

    Flight::json(['status'=>'ok']);

});


//=========================
// LISTAR ROLES
//=========================
Flight::route('GET /rol-admin/listar', function () {

    $rows = DB::query("
        SELECT 
            rol_id,
            nombre
        FROM reg_rol
        WHERE is_activo = 1
        ORDER BY nombre
    ");

    Flight::json($rows);

});


//=========================
// LISTAR ADMIN
//=========================
Flight::route('GET /admin/listar', function () {

    $rows = DB::query("
        SELECT 
            u.usu_id,
            u.nombres_apellidos,
            u.email,
            u.clavel,
            u.is_activo,
            u.rol_id,
            r.nombre AS rol
        FROM reg_usu u
        LEFT JOIN reg_rol r
            ON r.rol_id = u.rol_id
        ORDER BY u.usu_id DESC
    ");

    Flight::json($rows);

});