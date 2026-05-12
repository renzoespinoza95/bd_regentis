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
                u.rol_id,

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
            "SELECT * FROM reg_usu WHERE google_uid=%s",
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
            'is_activo'         => 1,
            'fecha_nacimiento'  => null,
            'provincia'         => null,
            'fecha_creacion'    => date('Y-m-d H:i:s'),
            'is_premium'        => 0,
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

        // 🔎 TRAER USUARIO
        $row = DB::queryFirstRow("
            SELECT
                u.tipoxusu_id,
                nxu.neg_id
            FROM reg_usu u

            LEFT JOIN reg_negxusu nxu
                ON u.usu_id = nxu.usu_id
                AND nxu.is_activo = 1

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

        // 🔥 SCREENS (AHORA CON FUNCIÓN)
        $screens = obtenerScreens(
            $row['tipoxusu_id'],
            $row['neg_id']
        );

        Flight::json([
            'status'  => 'ok',
            'screens' => $screens
        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => 'Error interno del servidor'
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
