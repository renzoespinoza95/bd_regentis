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
            u.nombres_apellidos,
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
        WHERE u.is_fantasma = 1    
        ORDER BY u.usu_id ASC
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

    /* ======================================
       FIRMA
    ====================================== */

    $xin  = $data['xin'] ?? '';
    $yuan = $data['yuan'] ?? '';

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       PAYLOAD
    ====================================== */

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

    /* ======================================
       VALIDAR
    ====================================== */

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

    /* ======================================
       VALIDAR USUARIO
    ====================================== */

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

    /* ======================================
       VALIDAR SOBRENOMBRE REPETIDO
    ====================================== */

    $existeNick = DB::queryFirstField("

        SELECT usu_id

        FROM reg_usu

        WHERE LOWER(sobrenombre) = LOWER(%s)

        AND usu_id <> %i

        LIMIT 1

    ",
        $sobrenombre,
        $usu_id
    );

    if($existeNick){

        Flight::json([

            'ok' => false,

            'msg' => 'El sobrenombre ya existe'

        ], 409);

        return;
    }

    /* ======================================
       IMAGEN RANDOM
    ====================================== */

    $img_perfil = DB::queryFirstField("

        SELECT url

        FROM tt_imagen

        WHERE url IS NOT NULL
        AND url != ''

        ORDER BY RAND()

        LIMIT 1

    ");

    if(!$img_perfil){

        $img_perfil =
            'https://barsi-img.b-cdn.net/recursos/ffc1.png';

    }

    /* ======================================
       UPDATE
    ====================================== */

    DB::update(

        'reg_usu',

        [

            'sobrenombre' =>
                $sobrenombre,

            'celular' =>
                $celular,

            'nombres_apellidos' =>
                $nombres_apellidos,

            'img_perfil' =>
                $img_perfil

        ],

        "usu_id=%i",

        $usu_id

    );

    enviar_auto_msg(

        $usu_id,

        'TXT_REGISTRO'

    );

    /* ======================================
       RESPUESTA
    ====================================== */

    Flight::json([

        'ok' => true,

        'msg' => 'Registro básico actualizado',

        'usuario' => [

            'usu_id' =>
                $usu_id,

            'sobrenombre' =>
                $sobrenombre,

            'nombres_apellidos' =>
                $nombres_apellidos,

            'img_perfil' =>
                $img_perfil

        ]

    ]);

});

Flight::route('POST /GbaX/buscarCodigo', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $data = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       FIRMA
    ====================================== */

    $xin = trim(
        $data['xin'] ?? ''
    );

    $yuan = trim(
        $data['yuan'] ?? ''
    );

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       COD_USU
    ====================================== */

    $cod_usu = trim(
        $data['cod_usu'] ?? ''
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$cod_usu){

        Flight::json([

            'ok' => false,

            'msg' => 'cod_usu requerido'

        ], 400);

        return;
    }

    /* ======================================
       CONSULTA
    ====================================== */

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

                NULLIF(
                    n.nombre,
                    ''
                ),

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

        WHERE u.cod_usu = %s
        AND u.borrado_el IS NULL

        LIMIT 1

    ", $cod_usu);

    /* ======================================
       NO ENCONTRADO
    ====================================== */

    if(!$row){

        Flight::json([

            'ok' => false,

            'msg' => 'Usuario no encontrado'

        ], 404);

        return;
    }

    /* ======================================
       RESPUESTA
    ====================================== */

    Flight::json([

        'ok' => true,

        'usuario' => $row

    ]);

});

Flight::route('POST /Ko3d/agregarTrabajador', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       FIRMA
    ====================================== */

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       CAMPOS
    ====================================== */

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($usu_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'usu_id requerido'

        ], 400);

        return;
    }

    if($neg_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ], 400);

        return;
    }

    /* ======================================
       USUARIO
    ====================================== */

    $usuario = DB::queryFirstRow("

        SELECT
            usu_id,
            nombres_apellidos,
            tipoxusu_id

        FROM reg_usu

        WHERE usu_id = %i
        AND borrado_el IS NULL

        LIMIT 1

    ", $usu_id);

    if(!$usuario){

        Flight::json([

            'status' => 'error',

            'msg' => 'Usuario no encontrado'

        ], 404);

        return;
    }

    /* ======================================
       VALIDAR TIPO USUARIO
    ====================================== */

    if(
        intval(
            $usuario['tipoxusu_id']
        ) !== 1
    ){

        Flight::json([

            'status' => 'error',

            'msg' =>
                'El usuario ya pertenece a otro tipo de cuenta'

        ], 400);

        return;
    }

    /* ======================================
       NEGOCIO
    ====================================== */

    $negocio = DB::queryFirstRow("

        SELECT
            neg_id,
            nombre

        FROM reg_neg

        WHERE neg_id = %i
        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    if(!$negocio){

        Flight::json([

            'status' => 'error',

            'msg' => 'Negocio no encontrado'

        ], 404);

        return;
    }

    DB::startTransaction();

    try {

        /* ======================================
           DESACTIVAR RELACIONES ANTERIORES
        ====================================== */

        DB::update(

            'reg_negxusu',

            [

                'is_activo' => 0

            ],

            "usu_id=%i",

            $usu_id

        );

        /* ======================================
           INSERTAR NUEVA RELACIÓN
        ====================================== */

        DB::insert(

            'reg_negxusu',

            [

                'usu_id' =>
                    $usu_id,

                'neg_id' =>
                    $neg_id,

                'is_activo' => 1,

                'fecha_creacion' =>
                    date('Y-m-d H:i:s')

            ]

        );

        $negxusu_id = DB::insertId();

        /* ======================================
           ACTUALIZAR TIPO USUARIO
        ====================================== */

        DB::update(

            'reg_usu',

            [

                'tipoxusu_id' => 4

            ],

            "usu_id=%i",

            $usu_id

        );

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Trabajador agregado correctamente',

            'negxusu_id' =>
                $negxusu_id,

            'tipoxusu_id' => 4

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ], 500);

    }

});


Flight::route('POST /RhM4/editarCuentaUsu', function(){

    include DEFINITION;

    $json = Flight::request()->getBody();

    $data = json_decode($json);

    /* ======================================
       FIRMA
    ====================================== */

    $xin = $data->xin ?? null;

    $yuan = $data->yuan ?? null;

    firma($xin, $yuan);

    /* ======================================
       PAYLOAD
    ====================================== */

    $usu_id = intval(
        $data->usu_id ?? 0
    );

    $nombres_apellidos = trim(
        $data->nombres_apellidos ?? ''
    );

    $email = trim(
        $data->email ?? ''
    );

    $celular = trim(
        $data->celular ?? ''
    );

    $fecha_nacimiento = trim(
        $data->fecha_nacimiento ?? ''
    );

    $img_perfil = trim(
        $data->img_perfil ?? ''
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($usu_id <= 0){

        echo json_encode([

            "res" => "error",

            "msg" => "usu_id inválido"

        ]);

        return;
    }

    /* ======================================
       USUARIO
    ====================================== */

    $info_usuario = DB::queryFirstRow("

        SELECT *

        FROM reg_usu

        WHERE usu_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $usu_id);

    if(!$info_usuario){

        echo json_encode([

            "res" => "error",

            "msg" => "Usuario no encontrado"

        ]);

        return;
    }

    /* ======================================
       ARMAR UPDATE DINAMICO
    ====================================== */

    $update = [];

    if(
        $nombres_apellidos !== ''
        &&
        $nombres_apellidos !=
        $info_usuario['nombres_apellidos']
    ){

        $update['nombres_apellidos']
            = $nombres_apellidos;

    }

    if(
        $email !=
        $info_usuario['email']
    ){

        $update['email']
            = $email;

    }

    if(
        $celular !=
        $info_usuario['celular']
    ){

        $update['celular']
            = $celular;

    }

    if(
        $fecha_nacimiento !=
        $info_usuario['fecha_nacimiento']
    ){

        $update['fecha_nacimiento']
            = $fecha_nacimiento;
    }

    if(
        $img_perfil !== ''
        &&
        $img_perfil !=
        $info_usuario['img_perfil']
    ){

        $update['img_perfil']
            = $img_perfil;

    }

    /* ======================================
       SIN CAMBIOS
    ====================================== */

    if(
        empty($update)
    ){

        echo json_encode([

            "res" => "ok",

            "msg" => "Sin cambios"

        ]);

        return;
    }

    /* ======================================
       UPDATE
    ====================================== */

    DB::update(

        'reg_usu',

        $update,

        'usu_id=%i',

        $usu_id

    );

    /* ======================================
       RESPONSE
    ====================================== */

    echo json_encode([

        "res" => "ok",

        "msg" => "Cuenta actualizada"

    ]);

});