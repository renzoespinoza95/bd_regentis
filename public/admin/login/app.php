<?php

Flight::route('POST /coral/login', function () {

    try {

        $data = Flight::request()->data->getData();

        if (!isset($data['sobrenombre']) || !isset($data['clavel'])) {
            Flight::json([
                'status' => 'error',
                'msg'    => 'Payload incompleto'
            ], 400);
            return;
        }

        // 🔐 1️⃣ Desencriptar
        try {
            $sobrenombre = trim(des_barsi($data['sobrenombre']));
            $clavel      = trim(des_barsi($data['clavel']));
        } catch (Exception $e) {
            Flight::json([
                'status' => 'error',
                'msg'    => 'Error al desencriptar credenciales'
            ], 400);
            return;
        }

        if ($sobrenombre === '' || $clavel === '') {
            Flight::json([
                'status' => 'error',
                'msg'    => 'Credenciales vacías'
            ], 400);
            return;
        }

        // 🔎 2️⃣ Validar usuario (solo para login)
        $usuario = DB::queryFirstRow("
            SELECT usu_id
            FROM reg_usu
            WHERE sobrenombre = %s
            AND clavel = %s
            AND (is_activo = 1 OR is_activo IS NULL)
            LIMIT 1
        ", $sobrenombre, $clavel);

        if (!$usuario) {
            Flight::json([
                'status' => 'error',
                'msg'    => 'Credenciales incorrectas'
            ], 401);
            return;
        }

        $usu_id = $usuario['usu_id'];

        // 🧠 3️⃣ Traer TODO (igual que tu query administrador_actual)
        $row = DB::queryFirstRow("
            SELECT
                u.usu_id,
                u.nombres_apellidos,
                u.email,
                u.sobrenombre,
                u.cod_usu,
                u.img_perfil,
                u.rol_id,
                r.nombre AS rol_nombre,
                r.submenu_inicio,

                -- 🔥 NUEVO (TIPO USUARIO)
                tu.tipoxusu_id AS tipoxusu_id,
                tu.clave_txt AS tipoxusu_clave,
                tu.descripcion AS tipoxusu_descripcion,

                nxu.negxusu_id,
                nxu.neg_id,
                nxu.is_activo AS negxusu_activo,
                nxu.fecha_creacion AS negxusu_fecha_creacion,

                n.cod_neg,
                n.nombre AS negocio_nombre,
                n.puesto,
                n.direccion AS negocio_direccion,
                n.ciudad AS negocio_ciudad,
                n.provincia AS negocio_provincia,
                n.departamento AS negocio_departamento,
                n.map_lat AS negocio_lat,
                n.map_lng AS negocio_lng,
                n.img_logo,
                n.is_validado,

                m.mercado_id,
                m.nombre AS mercado_nombre,
                m.direccion AS mercado_direccion,
                m.ciudad AS mercado_ciudad,
                m.provincia AS mercado_provincia,
                m.departamento AS mercado_departamento,
                m.map_lat AS mercado_lat,
                m.map_lng AS mercado_lng

            FROM reg_usu u

            LEFT JOIN reg_rol r
                ON u.rol_id = r.rol_id

            -- 🔥 JOIN NUEVO
            LEFT JOIN reg_tipoxusu tu
                ON u.tipoxusu_id = tu.tipoxusu_id

            LEFT JOIN reg_negxusu nxu
                ON u.usu_id = nxu.usu_id
                AND nxu.is_activo = 1

            LEFT JOIN reg_neg n
                ON nxu.neg_id = n.neg_id

            LEFT JOIN reg_mercado m
                ON n.mercado_id = m.mercado_id

            WHERE u.usu_id = %i
            AND (u.is_activo = 1 OR u.is_activo IS NULL)
            LIMIT 1
        ", $usu_id);

        // 🔥 SCREENS (AHORA CON FUNCIÓN)
        $screens = obtenerScreens(
            $row['tipoxusu_id'],
            $row['neg_id']
        );

        // 🕒 4️⃣ Último acceso
        DB::update(
            'reg_usu',
            ['fecha_ultimo_acceso' => date('Y-m-d H:i:s')],
            'usu_id=%i',
            $usu_id
        );

        Flight::json([
            'status' => 'ok',
            'data'   => $row,
            'screens' => $screens
        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => 'Error interno del servidor'
        ], 500);
    }
});

Flight::route('POST /coral/loginById', function () {

    try {

        include DEFINITION;

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        if (!isset($data['usu_id'])) {

            Flight::json([
                'status' => 'error',
                'msg'    => 'Payload incompleto'
            ], 400);

            return;
        }

        $usu_id = intval($data['usu_id']);

        if ($usu_id <= 0) {

            Flight::json([
                'status' => 'error',
                'msg'    => 'Usuario inválido'
            ], 400);

            return;
        }

        /* =========================================
           VALIDAR EXISTENCIA
        ========================================= */

        $usuario = DB::queryFirstRow("

            SELECT usu_id

            FROM reg_usu

            WHERE usu_id = %i
            AND (is_activo = 1 OR is_activo IS NULL)

            LIMIT 1

        ", $usu_id);

        if (!$usuario) {

            Flight::json([
                'status' => 'error',
                'msg'    => 'Usuario no existe'
            ], 401);

            return;
        }

        /* =========================================
           TRAER DATA COMPLETA
        ========================================= */

        $row = DB::queryFirstRow("

            SELECT

                u.usu_id,
                u.nombres_apellidos,
                u.email,
                u.sobrenombre,
                u.cod_usu,
                u.img_perfil,
                r.nombre AS rol_nombre,
                r.submenu_inicio,

                tu.tipoxusu_id,
                tu.clave_txt AS tipoxusu_clave,
                tu.descripcion AS tipoxusu_descripcion,

                nxu.negxusu_id,
                nxu.neg_id,
                nxu.is_activo AS negxusu_activo,
                nxu.fecha_creacion AS negxusu_fecha_creacion,

                n.cod_neg,
                n.nombre AS negocio_nombre,
                n.puesto,
                n.direccion AS negocio_direccion,
                n.ciudad AS negocio_ciudad,
                n.provincia AS negocio_provincia,
                n.departamento AS negocio_departamento,
                n.map_lat AS negocio_lat,
                n.map_lng AS negocio_lng,
                n.img_logo,
                n.is_validado,
                n.descripcion AS negocio_descripcion,

                m.mercado_id,
                m.nombre AS mercado_nombre,
                m.direccion AS mercado_direccion,
                m.ciudad AS mercado_ciudad,
                m.provincia AS mercado_provincia,
                m.departamento AS mercado_departamento,
                m.map_lat AS mercado_lat,
                m.map_lng AS mercado_lng,
                m.logo AS mercado_logo,
                m.topnavbar_color,
                m.patron_fondo

            FROM reg_usu u

            LEFT JOIN reg_rol r
                ON u.rol_id = r.rol_id

            LEFT JOIN reg_tipoxusu tu
                ON u.tipoxusu_id = tu.tipoxusu_id

            LEFT JOIN reg_negxusu nxu
                ON u.usu_id = nxu.usu_id
                AND nxu.is_activo = 1

            LEFT JOIN reg_neg n
                ON nxu.neg_id = n.neg_id

            LEFT JOIN reg_mercado m
                ON n.mercado_id = m.mercado_id

            WHERE u.usu_id = %i
            AND (u.is_activo = 1 OR u.is_activo IS NULL)

            LIMIT 1

        ", $usu_id);

        /* =========================================
           RUBROS DEL NEGOCIO
        ========================================= */

        $rubros = [];

        if (!empty($row['neg_id'])) {

            $rubros = DB::query("

                SELECT

                    rxn.rubroxneg_id,
                    rxn.neg_id,
                    rxn.rubro_id,
                    rxn.is_activo,

                    rr.nombre,
                    rr.icono

                FROM reg_rubroxneg rxn

                INNER JOIN reg_rubro rr
                    ON rr.rubro_id = rxn.rubro_id

                WHERE rxn.neg_id = %i
                AND rxn.is_activo = 1
                AND rr.is_activo = 1

                ORDER BY rr.nombre ASC

            ", $row['neg_id']);

        }

        /* =========================================
           SCREENS
        ========================================= */

        $screens = [];

        if(
            !empty($row['tipoxusu_id'])
            &&
            !empty($row['neg_id'])
        ){

            $screens = DB::query("

                SELECT DISTINCT

                    s.screen_id,
                    s.nombre,
                    s.titulo,
                    s.explicacion,
                    s.vue_route,
                    s.tipoxusu_id

                FROM deux_screen s

                INNER JOIN deux_screenxrubro sxr
                    ON sxr.screen_id = s.screen_id

                INNER JOIN reg_rubroxneg rxn
                    ON rxn.rubro_id = sxr.rubro_id
                    AND rxn.neg_id = %i
                    AND rxn.is_activo = 1

                WHERE s.tipoxusu_id = %i

                ORDER BY s.screen_id ASC

            ",
                $row['neg_id'],
                $row['tipoxusu_id']
            );

            /* =========================================
               RUBROS DE CADA SCREEN
            ========================================= */

            foreach($screens as &$s){

                $s['rubros'] = DB::query("

                    SELECT

                        r.rubro_id,
                        r.nombre,
                        r.icono

                    FROM deux_screenxrubro sxr

                    INNER JOIN reg_rubro r
                        ON r.rubro_id = sxr.rubro_id

                    WHERE sxr.screen_id = %i
                    AND r.is_activo = 1

                    ORDER BY r.nombre ASC

                ", $s['screen_id']);

            }

        }

        enviar_auto_msg(
            $usu_id,
            'TXT_REGISTRO'
        );

        /* =========================================
           ÚLTIMO ACCESO
        ========================================= */

        DB::update(
            'reg_usu',
            [
                'fecha_ultimo_acceso' => date('Y-m-d H:i:s')
            ],
            'usu_id=%i',
            $usu_id
        );

        /* =========================================
           RESPONSE
        ========================================= */

        Flight::json([

            'status'  => 'ok',

            'data'    => $row,

            'rubros'  => $rubros,

            'screens' => $screens

        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {

        Flight::json([

            'status' => 'error',

            'msg'    => $e->getMessage(),

            'line'   => $e->getLine(),

            'file'   => $e->getFile()

        ], 500);
    }
});

Flight::route('POST /coral/crearUsuarioFirebase', function () {

    try {

        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

        $d = json_decode(Flight::request()->getBody(), true) ?: [];

        $token = trim($d['token'] ?? '');

        if (!$token) {
            Flight::json(['status'=>'error','msg'=>'Token requerido'],400);
            return;
        }

        // 🔥 VALIDAR TOKEN GOOGLE
        $data = firebase_decode_jwt($token);

        if (empty($data['sub'])) {
            Flight::json(['status'=>'error','msg'=>'Token inválido'],401);
            return;
        }

        $google_uid = $data['sub'];
        $email      = $data['email'] ?? null;
        $nombre     = $data['name'] ?? null;
        $foto       = "https://barsi-img.b-cdn.net/recursos/logo-regentis.png";

        // 🔥 ¿YA EXISTE?
        $existe = DB::queryFirstRow(

            "SELECT *
             FROM reg_usu
             WHERE google_uid = %s
             AND borrado_el IS NULL",

            $google_uid

        );

        if ($existe) {

            // 🔥 actualizar último acceso
            DB::update('reg_usu', [
                'fecha_ultimo_acceso' => date('Y-m-d H:i:s')
            ], "usu_id=%i", $existe['usu_id']);

            Flight::json([
                'status' => 'ok',
                'data'   => $existe
            ]);

            return;
        }



        $sobrenombre = generarSobrenombre();
        $clavel      = generarClavel();
        $cod_usu     = generarCodUsu();

        // 🔥 INSERT
        DB::insert('reg_usu', [
            'cod_usu'           => $cod_usu,
            'nombres_apellidos' => $nombre,
            'google_uid'        => $google_uid,
            'email'             => $email,
            'img_perfil'        => $foto,
            'sobrenombre'       => $sobrenombre,
            'celular'           => null,
            'dni'               => null,
            'rol_id'            => 1,
            'is_fantasma'       => 0,
            'is_activo'         => 1,
            'fecha_nacimiento'  => null,
            'provincia'         => null,
            'fecha_creacion'    => date('Y-m-d H:i:s'),
            'tipoxusu_id'       => 1,
            'clavel'            => $clavel,
            'fecha_ultimo_acceso'=> date('Y-m-d H:i:s')
        ]);

        $usu_id = DB::insertId();

        // 🔥 RESPUESTA
        $user = DB::queryFirstRow("SELECT * FROM reg_usu WHERE usu_id=%i", $usu_id);

        Flight::json([
            'status' => 'ok',
            'data'   => $user
        ]);

    } catch(Exception $e) {
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }

});

// 🔥 GENERAR SOBRENOMBRE (3 letras + 3 números)
function generarSobrenombre() {
    $letras = 'abcdefghijklmnopqrstuvwxyz';
    $num = '';
    $let = '';

    for ($i=0;$i<3;$i++) {
        $let .= $letras[random_int(0,25)];
        $num .= random_int(0,9);
    }

    return $let.$num;
}

// 🔥 GENERAR CLAVE (6 números)
function generarClavel() {
    return str_pad((string)random_int(0,999999),6,'0',STR_PAD_LEFT);
}

// 🔥 GENERAR COD_USU
function generarCodUsu() {
    do {
        $codigo = str_pad((string)random_int(0,99999),5,'0',STR_PAD_LEFT)
                . chr(random_int(65,90));
        $existe = DB::queryFirstField("SELECT 1 FROM reg_usu WHERE cod_usu=%s", $codigo);
    } while ($existe);

    return $codigo;
}

function firebase_decode_jwt($jwt) {
    $parts = explode('.', $jwt);

    if (count($parts) !== 3) return [];

    $payload = $parts[1];
    $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $payload));

    return json_decode($payload, true);
}

Flight::route('POST /WAsQ/usuario/screens', function () {

    try {

        include DEFINITION;

        DB::query("SET NAMES 'utf8mb4'");

        $data = json_decode(
            Flight::request()->getBody(),
            true
        ) ?: [];

        /* =========================================
           FIRMA
        ========================================= */

        $xin  = $data['xin'] ?? '';
        $yuan = $data['yuan'] ?? '';

        firma($xin, $yuan);

        /* =========================================
           usu_id
        ========================================= */

        if (!isset($data['usu_id'])) {

            Flight::json([
                'status' => 'error',
                'msg'    => 'Payload incompleto'
            ], 400);

            return;
        }

        $usu_id = intval(
            $data['usu_id']
        );

        if ($usu_id <= 0) {

            Flight::json([
                'status' => 'error',
                'msg'    => 'Usuario inválido'
            ], 400);

            return;
        }

        /* =========================================
           TRAER USUARIO
        ========================================= */

        $row = DB::queryFirstRow("

            SELECT

                u.tipoxusu_id,

                nxu.neg_id

            FROM reg_usu u

            LEFT JOIN reg_negxusu nxu
                ON u.usu_id = nxu.usu_id
                AND nxu.is_activo = 1
                AND nxu.borrado_el IS NULL

            WHERE u.usu_id = %i

            LIMIT 1

        ", $usu_id);

        if (!$row) {

            Flight::json([
                'status' => 'error',
                'msg'    => 'Usuario no encontrado'
            ], 404);

            return;
        }

        /* =========================================
           SCREENS
        ========================================= */

        $screens = [];

        if(
            !empty($row['tipoxusu_id'])
            &&
            !empty($row['neg_id'])
        ){

            $screens = DB::query("

                SELECT DISTINCT

                    s.screen_id,
                    s.nombre,
                    s.titulo,
                    s.explicacion,
                    s.vue_route,
                    s.tipoxusu_id

                FROM deux_screen s

                INNER JOIN deux_screenxrubro sxr
                    ON sxr.screen_id = s.screen_id

                INNER JOIN reg_rubroxneg rxn
                    ON rxn.rubro_id = sxr.rubro_id
                    AND rxn.neg_id = %i
                    AND rxn.is_activo = 1
                    AND rxn.borrado_el IS NULL

                WHERE s.tipoxusu_id = %i
                AND s.is_visible = 1

                ORDER BY s.screen_id ASC

            ",
                $row['neg_id'],
                $row['tipoxusu_id']
            );

            /* =========================================
               RUBROS DE CADA SCREEN
            ========================================= */

            foreach($screens as &$s){

                $s['rubros'] = DB::query("

                    SELECT

                        r.rubro_id,
                        r.nombre,
                        r.icono

                    FROM deux_screenxrubro sxr

                    INNER JOIN reg_rubro r
                        ON r.rubro_id = sxr.rubro_id

                    WHERE sxr.screen_id = %i
                    AND r.is_activo = 1
                    AND r.borrado_el IS NULL

                    ORDER BY r.nombre ASC

                ", $s['screen_id']);

            }

        }

        /* =========================================
           MEMBRESIA
        ========================================= */

        $membresia = veri_membresia(
            $usu_id
        );

        /* =========================================
           RESPONSE
        ========================================= */

        Flight::json([

            'status'  => 'ok',

            'membresia' => $membresia,

            'screens' => $screens

        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {

        Flight::json([

            'status' => 'error',

            'msg'    => $e->getMessage(),

            'line'   => $e->getLine(),

            'file'   => $e->getFile()

        ], 500);

    }

});  

function obtenerScreens($tipoxusu_id, $neg_id) {

    return DB::query("
        SELECT
            s.screen_id,
            s.nombre,
            s.titulo,
            s.vue_route,
            s.rubro_id,
            s.tipoxusu_id,
            r.nombre AS rubro_nombre,

            IFNULL(noti.badge, 0) AS badge

        FROM deux_screen s

        INNER JOIN deux_rubroxneg rxn
            ON rxn.rubro_id = s.rubro_id

        LEFT JOIN deux_rubro r
            ON r.rubro_id = s.rubro_id

        LEFT JOIN reg_noti noti
            ON noti.screen_id = s.screen_id
            AND noti.neg_id = %i

        WHERE 
            s.tipoxusu_id = %i
            AND rxn.neg_id = %i

        ORDER BY s.screen_id ASC
    ", 
        $neg_id,        // join noti
        $tipoxusu_id,   // filtro tipo usuario
        $neg_id         // filtro negocio
    );
}

Flight::route('GET /flask', function () {
    echo perso::ok();
});


Flight::route('POST /H0YY/usuFotoRandom', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $nombre_id = trim(
        $d['nombre_id'] ?? ''
    );

    $valor_id = intval(
        $d['valor_id'] ?? 0
    );

    $img_url = trim(
        $d['img_url'] ?? ''
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($nombre_id == ''){

        Flight::json([
            'success' => false,
            'msg' => 'nombre_id requerido'
        ], 400);

        return;
    }

    if($valor_id <= 0){

        Flight::json([
            'success' => false,
            'msg' => 'valor_id requerido'
        ], 400);

        return;
    }

    if($img_url == ''){

        Flight::json([
            'success' => false,
            'msg' => 'img_url requerido'
        ], 400);

        return;
    }

    /* ======================================
       SOLO PERMITIR usu_id
    ====================================== */

    if($nombre_id !== 'usu_id'){

        Flight::json([
            'success' => false,
            'msg' => 'nombre_id inválido'
        ], 400);

        return;
    }

    /* ======================================
       VALIDAR USUARIO
    ====================================== */

    $usuario = DB::queryFirstRow("
        SELECT usu_id
        FROM reg_usu
        WHERE usu_id = %i
        LIMIT 1
    ", $valor_id);

    if(!$usuario){

        Flight::json([
            'success' => false,
            'msg' => 'Usuario no encontrado'
        ], 404);

        return;
    }

    /* ======================================
       UPDATE
    ====================================== */

    DB::update(
        'reg_usu',
        [
            'img_perfil' => $img_url
        ],
        'usu_id = %i',
        $valor_id
    );

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'success' => true,

        'usu_id' => $valor_id,

        'img_perfil' => $img_url

    ]);

});

Flight::route('POST /xin_yuan', function(){

    try {

        include DEFINITION;

        DB::query("SET NAMES 'utf8mb4'");

        $data = json_decode(
            Flight::request()->getBody(),
            true
        ) ?: [];

        $xin    = $data['xin'] ?? '';
        $yuan   = $data['yuan'] ?? '';
        $usu_id = intval($data['usu_id'] ?? 0);

        /* =========================================
           VALIDAR FIRMA
        ========================================= */

        firma($xin, $yuan);

        /* =========================================
           VALIDAR usu_id
        ========================================= */

        if($usu_id <= 0){

            Flight::json([
                'status' => 'error',
                'msg'    => 'usu_id inválido'
            ], 400);

            return;
        }

        /* =========================================
           BUSCAR USUARIO
        ========================================= */

        $usuario = DB::queryFirstRow("

            SELECT

                usu_id,
                cod_usu,
                nombres_apellidos,
                sobrenombre,
                email,
                celular,
                img_perfil,
                is_activo

            FROM reg_usu

            WHERE usu_id = %i

            LIMIT 1

        ", $usu_id);

        /* =========================================
           NO EXISTE
        ========================================= */

        if(!$usuario){

            Flight::json([
                'status' => 'error',
                'msg'    => 'Usuario no encontrado'
            ], 404);

            return;
        }

        /* =========================================
           RESPONSE
        ========================================= */

        Flight::json([

            'status' => 'ok',

            'msg'    => 'Usuario válido',

            'data'   => $usuario

        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {

        Flight::json([

            'status' => 'error',

            'msg'    => $e->getMessage(),

            'line'   => $e->getLine(),

            'file'   => $e->getFile()

        ], 500);

    }

});


Flight::route('POST /YfrW/loginNeg', function() {

    include DEFINITION;
    autentificar_administrador();

    $json = Flight::request()->getBody();

    $data = json_decode($json);

    $usu_id = intval(
        $data->usu_id ?? 0
    );

    /* ======================================
       VALIDAR PAYLOAD
    ====================================== */

    if($usu_id <= 0){

        echo json_encode([

            "res" => "error",

            "msg" => "usu_id inválido"

        ]);

        return;
    }

    /* ======================================
       VALIDAR USUARIO
    ====================================== */

    $info_admin = DB::queryFirstRow("

        SELECT *

        FROM reg_usu

        WHERE usu_id = %i
        AND is_activo = 1

        LIMIT 1

    ", $usu_id);

    /* ======================================
       LOGIN OK
    ====================================== */

    if($info_admin){

        global $nombre_app;

        $valor_key = $nombre_app . vari("KEY");

        $sobrenombre = perso::preparar_para_encriptar(
            $info_admin['sobrenombre']
        );

        $enc_sobrenombre = perso::encrypt(
            $info_admin['sobrenombre'],
            $valor_key
        );

        $info_admin['usu_id'] =
            perso::preparar_para_encriptar(
                $info_admin['usu_id']
            );

        $enc_info_admin_id = perso::encrypt(
            $info_admin['usu_id'],
            $valor_key
        );

        setcookie(
            "ssa_sobrenombre_" . $nombre_app,
            $enc_sobrenombre,
            0,
            "/"
        );

        setcookie(
            "ssa_id_" . $nombre_app,
            $enc_info_admin_id,
            0,
            "/"
        );

        echo json_encode([

            "res"    => "ok",

            "rol_id" => intval(
                $info_admin['rol_id']
            )

        ]);

    } else {

        /* ======================================
           ERROR
        ====================================== */

        echo json_encode([

            "res" => "error",

            "msg" => "Usuario no encontrado o inactivo"

        ]);

    }

});



Flight::route('POST /EiwA/tiendaAutomatico', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       VALIDAR JSON
    ====================================== */
    if(empty($d['negocio'])){

        Flight::json([
            'status' => 'error',
            'msg' => 'negocio requerido'
        ], 400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           NEGOCIO
        ====================================== */
        try {

            $negocio = $d['negocio'];

            /* ======================================
               GENERAR COD_NEG
            ====================================== */

            do {

                $cod_neg = 'NEG' . str_pad(
                    rand(1, 9999),
                    4,
                    '0',
                    STR_PAD_LEFT
                );

                $existe_cod = DB::queryFirstField("

                    SELECT neg_id

                    FROM reg_neg

                    WHERE cod_neg = %s

                    LIMIT 1

                ", $cod_neg);

            } while($existe_cod);

            /* ======================================
               PRIMER CELULAR YAPE
            ====================================== */

            $lista_yape = $negocio['lista_yape'] ?? [];

            $celular_principal = null;

            if(
                is_array($lista_yape)
                &&
                !empty($lista_yape[0])
            ){
                $celular_principal = trim(
                    $lista_yape[0]
                );
            }

            DB::insert('reg_neg',[

                'cod_neg' => $cod_neg,

                'nombre' => $negocio['nombre'] ?? null,

                'descripcion' => $negocio['descripcion'] ?? null,

                'mercado_id' => intval(
                    $negocio['mercado_id'] ?? 0
                ),

                'puesto' => $negocio['puesto'] ?? null,

                'ciudad' => $negocio['ciudad'] ?? null,

                'provincia' => $negocio['provincia'] ?? null,

                'departamento' => $negocio['departamento'] ?? null,

                'direccion' => $negocio['direccion'] ?? null,

                'celular_informes' => $celular_principal,

                'img_logo' => 'https://barsi-img.b-cdn.net/recursos/sg3f.png',

                'lista_yape' => json_encode(
                    $negocio['lista_yape'] ?? [],
                    JSON_UNESCAPED_UNICODE
                ),

                'is_activo' => intval(
                    $negocio['is_activo'] ?? 1
                ),

                'is_validado' => intval(
                    $negocio['is_validado'] ?? 1
                ),

                'fecha_creacion' => $now

            ]);

            $neg_id = DB::insertId();

        } catch(Exception $e){

            throw new Exception(
                'ERROR REG_NEG: '
                . $e->getMessage()
            );
        }

        veri_publico_general($neg_id);

        /* ======================================
           RUBRO X NEGOCIO
        ====================================== */
        try {

            $rubro_id = intval(
                $negocio['rubro_id'] ?? 0
            );

            if($rubro_id > 0){

                DB::insert(
                    'reg_rubroxneg',
                    [

                        'neg_id' => $neg_id,

                        'rubro_id' => $rubro_id,

                        'is_activo' => 1
                    ]
                );
            }

        } catch(Exception $e){

            throw new Exception(
                'ERROR REG_RUBROXNEG: '
                . $e->getMessage()
            );
        }

        /* ======================================
           SLIDERS
        ====================================== */
        try {

            if(!empty($d['sliders'])){

                foreach($d['sliders'] as $s){

                    DB::insert('reg_slider',[

                        'neg_id' => $neg_id,

                        'img' => 'https://barsi-img.b-cdn.net/recursos/71ye.png',

                        'descripcion' => $s['descripcion'] ?? null,

                        'grupo' => $s['grupo'] ?? 'A',

                        'orden' => intval(
                            $s['orden'] ?? 1
                        ),

                        'is_visible' => intval(
                            $s['is_visible'] ?? 1
                        ),

                        'fecha_creacion' => $s['fecha_creacion'] ?? $now,

                        'fecha_fin' => $s['fecha_fin'] ?? null

                    ]);
                }
            }

        } catch(Exception $e){

            throw new Exception(
                'ERROR REG_SLIDER: '
                . $e->getMessage()
            );
        }

        /* ======================================
           CATEGORIAS
        ====================================== */
        $mapCategorias = [];

        try {

            if(!empty($d['categorias'])){

                foreach($d['categorias'] as $c){

                    DB::insert('pos_category',[

                        'neg_id' => $neg_id,

                        'name' => $c['nombre'] ?? null,

                        'icon' => $c['icon'] ?? '🛒',

                        'color' => $c['color'] ?? '#FD7635',

                        'img' => 'https://barsi-img.b-cdn.net/recursos/ffc1.png',

                        'priority' => intval(
                            $c['priority'] ?? 0
                        ),

                        'brief' => $c['descripcion'] ?? null,

                        'clave_txt' => $c['clave_txt']
                            ?? strtolower(
                                str_replace(
                                    ' ',
                                    '-',
                                    trim($c['nombre'] ?? '')
                                )
                            ),

                        'is_activo' => 1

                    ]);

                    $category_id = DB::insertId();

                    $mapCategorias[
                        $c['categoria_id']
                    ] = $category_id;

                }
            }

        } catch(Exception $e){

            throw new Exception(
                'ERROR POS_CATEGORY: '
                . $e->getMessage()
            );
        }

        /* ======================================
           PRODUCTOS
        ====================================== */
        try {

            if(!empty($d['productos'])){

                foreach($d['productos'] as $p){

                    /* ==========================
                       CATEGORY ID REAL
                    ========================== */

                    $new_category_id =
                        $mapCategorias[
                            $p['categoria_id']
                        ] ?? 0;

                    /* ==========================
                       PRODUCTO
                    ========================== */

                    DB::insert('pos_product',[

                        'cod_producto' => 'AUTO_' . uniqid(),

                        'name' => $p['name'] ?? null,

                        'tipo_producto' => 'ABARROTES',

                        'marca_des' => 'GENERICO',

                        'price' => floatval(
                            $p['price'] ?? 0
                        ),

                        'description' => $p['descripcion'] ?? null,

                        'fecha_creacion' => $now,

                        'fecha_modificacion' => $now,

                        'neg_id' => $neg_id,

                        'is_visible' => 1

                    ]);

                    $product_id = DB::insertId();

                    /* ==========================
                       PRODUCT CATEGORY
                    ========================== */

                    DB::insert(
                        'pos_product_category',
                        [

                            'product_id' => $product_id,

                            'category_id' => $new_category_id,

                            'is_visible' => 1,

                            'neg_id' => $neg_id
                        ]
                    );

                    /* ==========================
                       IMAGEN
                    ========================== */

                    DB::insert(
                        'pos_product_image',
                        [

                            'product_id' => $product_id,

                            'img' => 'https://barsi-img.b-cdn.net/recursos/6qz5.png',

                            'orden' => 1,

                            'is_visible' => 1
                        ]
                    );

                    /* ==========================
                       INVENTARIO
                    ========================== */

                    $stock = intval(
                        $p['stock'] ?? 0
                    );

                    DB::insert(
                        'pos_inventario',
                        [

                            'product_id' => $product_id,

                            'stock_actual' => $stock,

                            'neg_id' => $neg_id
                        ]
                    );

                    /* ==========================
                       MOVIMIENTO INVENTARIO
                    ========================== */

                    DB::insert(
                        'pos_inventario_movimiento',
                        [

                            'product_id' => $product_id,

                            'tipo' => 'AJUSTE',

                            'origen' => 'AJUSTE',

                            'cantidad' => $stock,

                            'precio_unitario' => floatval(
                                $p['price'] ?? 0
                            ),

                            'fecha' => $now,

                            'stock_resultante' => $stock,

                            'neg_id' => $neg_id
                        ]
                    );

                }

            }

        } catch(Exception $e){

            throw new Exception(
                'ERROR PRODUCTOS: '
                . $e->getMessage()
            );
        }

        /* ======================================
           CATEGORIA MAS-VENDIDOS
        ====================================== */

        try {

            /* ==========================
               VERIFICAR EXISTENCIA
            ========================== */

            $catMasVendidos = DB::queryFirstRow("

                SELECT

                    category_id

                FROM pos_category

                WHERE neg_id = %i
                AND clave_txt = 'mas-vendidos'

                LIMIT 1

            ", $neg_id);

            /* ==========================
               SI NO EXISTE → CREAR
            ========================== */

            if(!$catMasVendidos){

                DB::insert(
                    'pos_category',
                    [

                        'neg_id' => $neg_id,

                        'name' => 'Más vendidos',

                        'icon' => '🔥',

                        'color' => '#FF4D4F',

                        'img' =>
                            'https://barsi-img.b-cdn.net/recursos/ffc1.png',

                        'priority' => 999,

                        'brief' =>
                            'Productos más solicitados',

                        'clave_txt' =>
                            'mas-vendidos',

                        'is_activo' => 1

                    ]
                );

                $mas_category_id = DB::insertId();

                /* ==========================
                   2 PRODUCTOS RANDOM
                ========================== */

                $productos = DB::query("

                    SELECT product_id

                    FROM pos_product

                    WHERE neg_id = %i

                    ORDER BY RAND()

                    LIMIT 2

                ", $neg_id);

                foreach($productos as $pr){

                    $existeRelacion =
                        DB::queryFirstField("

                            SELECT 1

                            FROM pos_product_category

                            WHERE product_id = %i
                            AND category_id = %i

                            LIMIT 1

                        ",
                            $pr['product_id'],
                            $mas_category_id
                        );

                    if(!$existeRelacion){

                        DB::insert(
                            'pos_product_category',
                            [

                                'product_id' =>
                                    $pr['product_id'],

                                'category_id' =>
                                    $mas_category_id,

                                'is_visible' => 1,

                                'neg_id' => $neg_id

                            ]
                        );

                    }

                }

            }

        } catch(Exception $e){

            throw new Exception(
                'ERROR MAS-VENDIDOS: '
                . $e->getMessage()
            );
        }        

        /* ======================================
           NEGXUSU
        ====================================== */
        try {

            if(!empty($d['negxusu'])){

                foreach($d['negxusu'] as $nxu){

                    DB::insert(
                        'reg_negxusu',
                        [

                            'neg_id' => $neg_id,

                            'usu_id' => intval(
                                $nxu['usu_id']
                            ),

                            'is_activo' => intval(
                                $nxu['is_activo'] ?? 1
                            )
                        ]
                    );
                }
            }

        } catch(Exception $e){

            throw new Exception(
                'ERROR REG_NEGXUSU: '
                . $e->getMessage()
            );
        }

         /* ======================================
            PROPIETARIO
         ====================================== */

        try {

            if(!empty($d['propietario'])){

                $usu_id = intval(
                    $d['propietario']['usu_id']
                );

                /* ==============================
                   BUSCAR TIPO PROPIETARIO
                ============================== */

                $tipoxusu_id =
                    DB::queryFirstField("

                        SELECT tipoxusu_id

                        FROM reg_tipoxusu

                        WHERE LOWER(clave_txt)
                        LIKE '%propietario%'

                        LIMIT 1

                    ");

                if(!$tipoxusu_id){

                    $tipoxusu_id = 2;
                }

                $fecha_inicio_premium =
                    date('Y-m-d H:i:s');

                $fecha_fin_premium =
                    date(
                        'Y-m-d H:i:s',
                        strtotime('+15 days')
                    );

                DB::update(
                    'reg_usu',
                    [

                        'tipoxusu_id' =>
                            $tipoxusu_id

                    ],
                    "usu_id=%i",
                    $usu_id
                );

                DB::insert(

                    'reg_neg_pago',

                    [

                        'neg_id' => $neg_id,

                        'usu_id_yapeo' => null,

                        'motivo' => 'FREE',

                        'monto' => 0,

                        'fecha_inicio_premium' =>
                            $fecha_inicio_premium,

                        'fecha_fin_premium' =>
                            $fecha_fin_premium,

                        'yaplin_id' => null,

                        'is_aprobado' => 1,

                        'borrado_el' => null

                    ]

                );

                /* ==============================
                   INSERTAR NEGXUSU
                ============================== */

                $existe_negxusu = DB::queryFirstField("

                    SELECT negxusu_id

                    FROM reg_negxusu

                    WHERE usu_id = %i
                    AND neg_id = %i

                    LIMIT 1

                ",
                    $usu_id,
                    $neg_id
                );

                if(!$existe_negxusu){

                    DB::insert(
                        'reg_negxusu',
                        [

                            'usu_id' => $usu_id,

                            'neg_id' => $neg_id,

                            'is_activo' => 1,

                            'fecha_creacion' => $now

                        ]
                    );

                }

            }

        } catch(Exception $e){

            throw new Exception(
                'ERROR PROPIETARIO: '
                . $e->getMessage()
            );

        }

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */
        Flight::json([

            'status' => 'ok',

            'msg' => 'Tienda creada correctamente',

            'neg_id' => $neg_id

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});
