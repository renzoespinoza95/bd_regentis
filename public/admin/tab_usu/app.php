<?php

function crear_usuario_negocio(
    $nombres_apellidos,
    $neg_id
){

    $nombres_apellidos = trim(
        $nombres_apellidos
    );

    $neg_id = intval(
        $neg_id
    );

    if($nombres_apellidos === ''){

        return [

            'ok' => false,

            'msg' => 'nombres_apellidos requerido'

        ];

    }

    if($neg_id <= 0){

        return [

            'ok' => false,

            'msg' => 'neg_id requerido'

        ];

    }

    /* =====================================
       NEGOCIO
    ====================================== */

    $negocio = DB::queryFirstRow("

        SELECT

            neg_id

        FROM reg_neg

        WHERE neg_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    if(!$negocio){

        return [

            'ok' => false,

            'msg' => 'Negocio no encontrado'

        ];

    }

    /* =====================================
       IMAGEN ALEATORIA
    ====================================== */

    $imagen = DB::queryFirstRow("

        SELECT

            imagen_id,
            url

        FROM tt_imagen

        ORDER BY RAND()

        LIMIT 1

    ");

    $img_perfil =
        $imagen
        ?
        $imagen['url']
        :
        '7pyi.jpg';

    /* =====================================
       NICK ALEATORIO
    ====================================== */

    $nick = DB::queryFirstRow("

        SELECT

            nick_id,
            nick

        FROM tt_nick

        ORDER BY RAND()

        LIMIT 1

    ");

    $sobrenombre =
        $nick
        ?
        $nick['nick']
        :
        null;

    /* =====================================
       INSERT USUARIO
    ====================================== */

    DB::insert(

        'reg_usu',

        [

            'cod_usu' =>
                generarCodigoUnico(),

            'google_uid' =>
                generarCodigoUnico(),

            'nombres_apellidos' =>
                $nombres_apellidos,

            'sobrenombre' =>
                $sobrenombre,

            'img_perfil' =>
                $img_perfil,

            'tipoxusu_id' =>
                2,

            'rol_id' =>
                1,

            'clavel' =>
                '12qw12',

            'is_activo' =>
                1,

            'is_fantasma' =>
                1,

            'fecha_creacion' =>
                date(
                    'Y-m-d H:i:s'
                )

        ]

    );

    $usu_id = DB::insertId();

    /* =====================================
       RELACION NEGOCIO
    ====================================== */

    DB::insert(

        'reg_negxusu',

        [

            'usu_id' =>
                $usu_id,

            'neg_id' =>
                $neg_id,

            'is_activo' =>
                1,

            'fecha_creacion' =>
                date(
                    'Y-m-d H:i:s'
                )

        ]

    );

    /* =====================================
       CREAR PIN
    ====================================== */

    $pin = crear_pin_code(
        $usu_id
    );

    if(!$pin['ok']){

        return [

            'ok' => false,

            'msg' => 'No se pudo crear el PIN'

        ];

    }

    /* =====================================
       EXPIRACION 24 HORAS
    ====================================== */

    $pin_code_fecha_fin = date(

        'Y-m-d H:i:s',

        strtotime(
            '+24 hours'
        )

    );

    DB::update(

        'reg_usu',

        [

            'pin_code_fecha_fin' =>
                $pin_code_fecha_fin

        ],

        'usu_id=%i',

        $usu_id

    );

    /* =====================================
       LOGIN COMPLETO
    ====================================== */

    $login = login_by_id(
        $usu_id
    );

    if(!$login['ok']){

        return [

            'ok' => false,

            'msg' => $login['msg']

        ];

    }

    /* =====================================
       AGREGAR PIN AL RESPONSE
    ====================================== */

    $login['data']['pin_code'] =
        $pin['pin_code'];

    $login['data']['pin_code_fecha_fin'] =
        $pin_code_fecha_fin;

    /* =====================================
       RESPONSE
    ====================================== */

    return [

        'ok' => true,

        'data' =>
            $login['data'],

        'rubros' =>
            $login['rubros'],

        'screens' =>
            $login['screens']

    ];

}


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

                'tipoxusu_id' => 2

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

function crear_pin_code($usu_id)
{

    $usu_id = intval($usu_id);

    if($usu_id <= 0){

        return [

            'ok' => false,

            'msg' => 'usu_id inválido'

        ];

    }

    do{

        $pin_code = str_pad(

            mt_rand(0, 999999),

            6,

            '0',

            STR_PAD_LEFT

        );

        $existe = DB::queryFirstField("

            SELECT COUNT(*)

            FROM reg_usu

            WHERE pin_code = %s

            AND pin_code_fecha_fin >= NOW()

            AND borrado_el IS NULL

        ", $pin_code);

    }
    while($existe > 0);

    $pin_code_fecha_fin = date(

        'Y-m-d H:i:s',

        strtotime('+4 minutes')

    );

    DB::update(

        'reg_usu',

        [

            'pin_code' => $pin_code,

            'pin_code_fecha_fin' => $pin_code_fecha_fin

        ],

        'usu_id=%i',

        $usu_id

    );

    return [

        'ok' => true,

        'pin_code' => $pin_code,

        'pin_code_fecha_fin' => $pin_code_fecha_fin

    ];

}

function veri_pin_code($pin_code)
{

    $pin_code = trim($pin_code);

    if($pin_code === ''){

        return [

            'ok' => false,

            'msg' => 'pin_code requerido'

        ];

    }

    $usuario = DB::queryFirstRow("

            SELECT *

            FROM reg_usu

            WHERE pin_code = %s

            AND pin_code_fecha_fin >= NOW()

            AND borrado_el IS NULL

            LIMIT 1

        ", $pin_code);

        if(!$usuario){

            return [

                'ok' => false,

                'msg' => 'PIN inválido o expirado'

            ];

        }

        /* =====================================
           INVALIDAR PIN
        ===================================== */

        DB::update(

            'reg_usu',

            [

                'pin_code' => null,

                'pin_code_fecha_fin' => null

            ],

            'usu_id=%i',

            $usuario['usu_id']

        );

        /* =====================================
           RESPONSE
        ===================================== */

        return [

            'ok' => true,

            'usuario' => $usuario

        ];

}

Flight::route('POST /K5jX/ingresarConPinCode', function(){

    try{

        include DEFINITION;

        DB::query("SET NAMES 'utf8mb4'");

        $d = json_decode(

            Flight::request()->getBody(),

            true

        ) ?: [];

        /* ======================================
           PIN CODE
        ====================================== */

        $pin_code = trim(

            $d['pin_code'] ?? ''

        );

        if($pin_code === ''){

            Flight::json([

                'status' => 'error',

                'msg' => 'pin_code requerido'

            ], 400);

            return;

        }

        /* ======================================
           VALIDAR PIN
        ====================================== */

        $rPin = veri_pin_code(
            $pin_code
        );

        if(!$rPin['ok']){

            Flight::json([

                'status' => 'error',

                'msg' => $rPin['msg']

            ], 401);

            return;

        }

        /* ======================================
           LOGIN POR ID
        ====================================== */

        $login = login_by_id(

            $rPin['usuario']['usu_id']

        );

        if(!$login['ok']){

            Flight::json([

                'status' => 'error',

                'msg' => $login['msg']

            ], 401);

            return;

        }

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status'  => 'ok',

            'data'    => $login['data'],

            'rubros'  => $login['rubros'],

            'screens' => $login['screens']

        ], 200, JSON_UNESCAPED_UNICODE);

    }
    catch(Throwable $e){

        Flight::json([

            'status' => 'error',

            'msg'    => $e->getMessage(),

            'line'   => $e->getLine(),

            'file'   => $e->getFile()

        ], 500);

    }

});

Flight::route('POST /Sp07/crearPinCode', function(){

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
       USUARIO
    ====================================== */

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    if($usu_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'usu_id requerido'

        ], 400);

        return;

    }

    $usuario = DB::queryFirstRow("

        SELECT

            usu_id,
            nombres_apellidos,
            celular,
            email

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
       CREAR PIN
    ====================================== */

    $r = crear_pin_code(
        $usu_id
    );

    if(!$r['ok']){

        Flight::json([

            'status' => 'error',

            'msg' => $r['msg']

        ], 400);

        return;

    }

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status' => 'ok',

        'usuario' => $usuario,

        'pin_code' => $r['pin_code'],

        'pin_code_fecha_fin' =>
            $r['pin_code_fecha_fin']

    ]);

});


Flight::route('POST /Vke6/crearTrabajadorDelivery', function(){

    try{

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
           PARAMETROS
        ====================================== */

        $nombres_apellidos = trim(

            $d['nombres_apellidos'] ?? ''

        );

        $neg_id = intval(

            $d['neg_id'] ?? 0

        );

        if($nombres_apellidos === ''){

            Flight::json([

                'status' => 'error',

                'msg' => 'nombres_apellidos requerido'

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
           CREAR USUARIO
        ====================================== */

        $r = crear_usuario_negocio(

            $nombres_apellidos,

            $neg_id

        );

        if(!$r['ok']){

            Flight::json([

                'status' => 'error',

                'msg' => $r['msg']

            ], 400);

            return;

        }

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'data' => $r['data'],

            'rubros' => $r['rubros'],

            'screens' => $r['screens']

        ], 200, JSON_UNESCAPED_UNICODE);

    }
    catch(Throwable $e){

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage(),

            'line' => $e->getLine(),

            'file' => $e->getFile()

        ], 500);

    }

});