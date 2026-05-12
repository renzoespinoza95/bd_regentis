<?php
/* -------------------------------
 // este es mi backend usando php8.2, flightphp y meekrodb2
 * ------------------------------- */

Flight::route('GET /administradores', function () {

    include DEFINITION;

    autentificar_administrador();

    require_once VARPATH . '/public/admin/tab_adm/inicio.php';

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
        'sobrenombre'       => $d['sobrenombre'], // 👈 NUEVO
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
        'sobrenombre'       => $d['sobrenombre'],
        'is_activo'         => intval($d['is_activo']),
        'rol_id'            => intval($d['rol_id']),
        'tipoxusu_id'       => intval($d['tipoxusu_id']) // 🔥 nuevo
    ];

    if (!empty($d['clavel'])) {
        $data['clavel'] = $d['clavel'];
    }

    DB::update('reg_usu', $data, "usu_id=%i", intval($d['usu_id']));

    // 🔥 actualizar negocio
    DB::delete('reg_negxusu', "usu_id=%i", intval($d['usu_id']));

    if (!empty($d['neg_id'])) {
        DB::insert('reg_negxusu', [
            'usu_id' => intval($d['usu_id']),
            'neg_id' => intval($d['neg_id']),
            'is_activo' => 1,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ]);
    }

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
Flight::route('GET /JXEc/admin/listar', function () {

    $rows = DB::query("
        SELECT 
            u.usu_id,
            u.nombres_apellidos,
            u.email,
            u.clavel,
            u.sobrenombre,
            u.is_activo,
            u.rol_id,
            IFNULL(r.nombre,'—') AS rol,

            u.tipoxusu_id,
            tu.descripcion AS tipoxusu,

            n.neg_id,
            n.nombre AS negocio

        FROM reg_usu u

        LEFT JOIN reg_rol r 
            ON r.rol_id = u.rol_id

        LEFT JOIN reg_tipoxusu tu
            ON tu.tipoxusu_id = u.tipoxusu_id

        LEFT JOIN reg_negxusu nx
            ON nx.usu_id = u.usu_id
            AND nx.is_activo = 1

        LEFT JOIN reg_neg n
            ON n.neg_id = nx.neg_id

        ORDER BY u.usu_id DESC
    ");

    Flight::json($rows);

});

Flight::route('GET /JXEc/tipoxusu/listar', function () {

    $rows = DB::query("
        SELECT tipoxusu_id, descripcion
        FROM reg_tipoxusu
        ORDER BY descripcion
    ");

    Flight::json($rows);

});

Flight::route('GET /JXEc/neg/listar', function () {

    $rows = DB::query("
        SELECT neg_id, nombre
        FROM reg_neg
        WHERE is_activo = 1
        ORDER BY nombre
    ");

    Flight::json($rows);

});