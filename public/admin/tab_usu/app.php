<?php

Flight::route('POST /Cuvg/usu/crear', function () {
    try {
        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

        $d = json_decode(Flight::request()->getBody(), true) ?: [];

        // 🔥 INSERT USUARIO
        DB::insert('reg_usu', [
          'cod_usu'            => generarCodigoUnico(),
          'nombres_apellidos'  => $d['nombres_apellidos'] ?? null,
          'dni'                => $d['dni'] ?? null,
          'google_uid'         => $d['google_uid'] ?? generarCodigoUnico(),
          'img_perfil'         => $d['img_perfil'] ?? null,
          'sobrenombre'        => $d['sobrenombre'] ?? null,
          'celular'            => $d['celular'] ?? null,
          'provincia'          => $d['provincia'] ?? null,
          'fecha_nacimiento'   => $d['fecha_nacimiento'] ?? null,
          'tipoxusu_id'        => $d['tipoxusu_id'] ?? null,
          'is_activo'          => isset($d['is_activo']) ? (int)$d['is_activo'] : 1,
          'is_premium'         => isset($d['is_premium']) ? (int)$d['is_premium'] : 0,
          'fecha_fin_premium'  => $d['fecha_fin_premium'] ?? null,
          'clavel'             => $d['clavel'] ?? null,
          'fecha_creacion'     => date('Y-m-d H:i:s')
        ]);

        $usu_id = DB::insertId();

        // 🔥 INSERT RELACIÓN USUARIO - NEGOCIO
        if (!empty($d['neg_id'])) {

            DB::insert('reg_negxusu', [
                'usu_id'          => $usu_id,
                'neg_id'          => $d['neg_id'],
                'is_activo'       => 1,
                'fecha_creacion'  => date('Y-m-d H:i:s')
            ]);
        }

        Flight::json([
            'success'=>true,
            'usu_id'=>$usu_id
        ]);

    } catch(Exception $e){
        Flight::json(['success'=>false,'msg'=>$e->getMessage()],500);
    }
});

Flight::route('GET /KT3E/listarUsuarios', function(){

    $rows = DB::query("
        SELECT 
            u.usu_id,
            u.cod_usu,
            u.img_perfil,
            u.sobrenombre,
            u.celular,
            u.dni,
            u.provincia,
            u.fecha_creacion,

            u.tipoxusu_id,
            t.descripcion AS tipoxusu_descripcion,  -- 🔥 NUEVO

            r.nombre AS rol_nombre,

            IFNULL(NULLIF(n.nombre,''), '—') AS negocio_nombre

        FROM reg_usu u

        LEFT JOIN reg_tipoxusu t   -- 🔥 NUEVO
            ON t.tipoxusu_id = u.tipoxusu_id

        LEFT JOIN reg_rol r 
            ON r.rol_id = u.rol_id

        LEFT JOIN reg_negxusu nxu
            ON nxu.usu_id = u.usu_id
            AND nxu.is_activo = 1

        LEFT JOIN reg_neg n
            ON n.neg_id = nxu.neg_id

        ORDER BY u.usu_id ASC
        LIMIT 20
    ");

    Flight::json($rows);

});


Flight::route('POST /ZYhL/detalleUsu', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $data = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval($data['usu_id'] ?? 0);

    // ================================
    // VALIDAR
    // ================================
    if(!$usu_id){

        Flight::json([
            'ok' => false,
            'msg' => 'usu_id requerido'
        ], 400);

        return;
    }

    // ================================
    // CONSULTA
    // ================================
    $row = DB::queryFirstRow("

        SELECT 
            u.usu_id,
            u.cod_usu,
            u.img_perfil,
            u.sobrenombre,
            u.nombres_apellidos,
            u.fecha_nacimiento,
            u.celular,
            u.provincia,
            u.fecha_creacion,
            u.tipoxusu_id,
            u.dni,
            u.google_uid,
            u.email,
            u.descripcion,
            u.is_premium,
            u.fecha_fin_premium,
            u.is_activo,
            u.is_fantasma,
            u.is_acepto_terminos,
            u.map_lat,
            u.map_lng,

            r.rol_id,
            r.nombre AS rol_nombre,

            n.neg_id,
            IFNULL(
                NULLIF(n.nombre, ''),
                '—'
            ) AS negocio_nombre

        FROM reg_usu u

        LEFT JOIN reg_rol r 
            ON r.rol_id = u.rol_id

        LEFT JOIN reg_negxusu nxu
            ON nxu.usu_id = u.usu_id
            AND nxu.is_activo = 1

        LEFT JOIN reg_neg n
            ON n.neg_id = nxu.neg_id

        WHERE u.usu_id = %i

        LIMIT 1

    ", $usu_id);

    // ================================
    // NO ENCONTRADO
    // ================================
    if(!$row){

        Flight::json([
            'ok' => false,
            'msg' => 'Usuario no encontrado'
        ], 404);

        return;
    }

    // ================================
    // RESPUESTA
    // ================================
    Flight::json([
        'ok' => true,
        'usuario' => $row
    ]);

});

Flight::route('POST /EUwe/registroBasico', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $data = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval(
        $data['usu_id'] ?? 0
    );

    $sobrenombre = trim(
        $data['sobrenombre'] ?? ''
    );

    $celular = trim(
        $data['celular'] ?? ''
    );

    $nombres_apellidos = trim(
        $data['nombres_apellidos'] ?? ''
    );

    $img_perfil = trim(
        $data['img_perfil'] ?? ''
    );

    // ======================================
    // VALIDAR
    // ======================================

    if(!$usu_id){

        Flight::json([
            'ok' => false,
            'msg' => 'usu_id requerido'
        ], 400);

        return;
    }

    if($sobrenombre === ''){

        Flight::json([
            'ok' => false,
            'msg' => 'sobrenombre requerido'
        ], 400);

        return;
    }

    if($nombres_apellidos === ''){

        Flight::json([
            'ok' => false,
            'msg' => 'nombres_apellidos requerido'
        ], 400);

        return;
    }

    if($img_perfil === ''){

        Flight::json([
            'ok' => false,
            'msg' => 'img_perfil requerido'
        ], 400);

        return;
    }

    // ======================================
    // VALIDAR USUARIO
    // ======================================

    $usuario = DB::queryFirstRow("

        SELECT 
            usu_id,
            sobrenombre,
            nombres_apellidos,
            img_perfil

        FROM reg_usu

        WHERE usu_id = %i

        LIMIT 1

    ", $usu_id);

    if(!$usuario){

        Flight::json([
            'ok' => false,
            'msg' => 'Usuario no encontrado'
        ], 404);

        return;
    }

    // ======================================
    // VALIDAR SOBRENOMBRE REPETIDO
    // ======================================

    $existeNick = DB::queryFirstField("

        SELECT usu_id

        FROM reg_usu

        WHERE LOWER(sobrenombre) = LOWER(%s)
        AND usu_id <> %i

        LIMIT 1

    ", $sobrenombre, $usu_id);

    if($existeNick){

        Flight::json([
            'ok' => false,
            'msg' => 'El sobrenombre ya existe'
        ], 409);

        return;
    }

    // ======================================
    // UPDATE
    // ======================================

    DB::update(
        'reg_usu',
        [
            'sobrenombre' => $sobrenombre,
            'celular' => $celular,
            'nombres_apellidos' => $nombres_apellidos,
            'img_perfil' => $img_perfil
        ],
        "usu_id=%i",
        $usu_id
    );

    // ======================================
    // RESPUESTA
    // ======================================

    Flight::json([

        'ok' => true,

        'msg' => 'Registro básico actualizado',

        'usuario' => [

            'usu_id' => $usu_id,

            'sobrenombre' => $sobrenombre,

            'nombres_apellidos' => $nombres_apellidos,

            'img_perfil' => $img_perfil
        ]
    ]);

});