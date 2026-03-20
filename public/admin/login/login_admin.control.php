<?php

//==============================
// Vista login
//==============================
Flight::route('GET /', function() {

    include DEFINITION;

    $mBase = $varhost . "/public/admin/login/";
    include $path_public . "/admin/login/inicio.php";

});


//==============================
// Dashboard administrador
//==============================
Flight::route('GET /admin/dash', function() {

    include DEFINITION;

    global $nombre_app, $apphost, $sesion_admin_administrador_id;

    if(empty($sesion_admin_administrador_id)){
        Flight::redirect($apphost . "/loginVault");
        exit;
    }

    $valor_key = $nombre_app . vari("KEY");

    $usu_id = perso::decrypt($sesion_admin_administrador_id, $valor_key);
    $usu_id = str_replace("*","",$usu_id);

    // obtener info usuario + rol
    $info_admin = DB::queryFirstRow("
        SELECT
            u.usu_id,
            u.nombres_apellidos,
            u.email,
            u.sobrenombre,
            u.rol_id,
            r.submenu_inicio,
            s.url
        FROM reg_usu u
        INNER JOIN reg_rol r
            ON u.rol_id = r.rol_id
        INNER JOIN reg_submenu s
            ON r.submenu_inicio = s.submenu_id
        WHERE u.usu_id = %i
        AND u.is_activo = 1
    ", $usu_id);

    $neg_id_cookie = $_COOKIE["sesion_admin_neg_id_" . $nombre_app] ?? 0;

    $administrador_actual = $info_admin;
    $administrador_actual['neg_id'] = $neg_id_cookie;

    if(!$info_admin){
        Flight::redirect($apphost . "/loginVault");
        exit;
    }

    Flight::redirect($apphost . $info_admin['url']);

});


//==============================
// Vista login
//==============================
Flight::route('GET /loginVault', function() {

    include DEFINITION;

    $mBase = $varhost . "/public/admin/login/";
    include $path_public . "/admin/login/inicio.php";

});


//==============================
// Procesar login
//==============================
Flight::route('POST /loginVault', function() {

    include DEFINITION;

    $json = Flight::request()->getBody();
    $datos_usuario = json_decode($json);

    $usuario = $datos_usuario->usuario ?? '';
    $clavel  = $datos_usuario->clavel ?? '';

    if (empty($usuario) || empty($clavel)) {
        echo perso::error();
        return;
    }

    // verificar usuario
    $info_admin = DB::queryFirstRow("
        SELECT *
        FROM reg_usu
        WHERE sobrenombre = %s
        AND clavel = %s
        AND is_activo = 1
    ", $usuario, $clavel);

    if ($info_admin) {

        global $nombre_app;

        $valor_key = $nombre_app . vari("KEY");

        $sobrenombre = perso::preparar_para_encriptar($usuario);
        $enc_sobrenombre = perso::encrypt($usuario, $valor_key);

        $info_admin['usu_id'] = perso::preparar_para_encriptar($info_admin['usu_id']);
        $enc_info_admin_id = perso::encrypt($info_admin['usu_id'], $valor_key);

        setcookie("sesion_admin_administrador_sobrenombre_" . $nombre_app, $enc_sobrenombre, 0, "/");
        setcookie("sesion_admin_administrador_id_" . $nombre_app, $enc_info_admin_id, 0, "/");

        // respuesta con rol
        echo json_encode([
            "res"    => "ok",
            "rol_id" => intval($info_admin['rol_id'])
        ]);

    } else {

        echo perso::error();

    }

});


//==============================
// Cerrar sesión
//==============================
Flight::route('GET /finAdmin', function() {

    include DEFINITION;

    global $nombre_app, $apphost;

    setcookie("sesion_admin_administrador_sobrenombre_" . $nombre_app, '', time() - 3600, "/");
    setcookie("sesion_admin_administrador_id_" . $nombre_app, '', time() - 3600, "/");

    Flight::redirect($apphost . "/loginVault");

});


//==============================
// Ruta test login
//==============================
Flight::route('GET /tt01', function() {

    include DEFINITION;

    $usuario = "renzo";
    $clavel  = "renzo";

    $res = DB::queryFirstRow("
        SELECT COUNT(usu_id) AS cant
        FROM reg_usu
        WHERE sobrenombre = %s
        AND clavel = %s
        AND is_activo = 1
    ", $usuario, $clavel);

    var_dump($res);

});


//==============================
// OPTIONS CORS
//==============================
Flight::route('OPTIONS /api/login_admin/j_login_app', function() {

    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

});


//==============================
// API LOGIN ADMIN
//==============================
Flight::route('POST /api/login_admin/j_login_app', function() {

    include DEFINITION;

    $body = Flight::request()->getBody();
    $data = json_decode($body, true);

    global $file_log, $ruta_log;

    if (!($file_log->load($ruta_log)->write($data))) {
        die("error file_log");
    }

    if (!isset($data['rol_id'], $data['sobrenombre'], $data['clavel'])) {

        echo json_encode([
            "respuesta" => [
                "tipo" => "ERROR",
                "descripcion" => "Datos incompletos"
            ]
        ]);

        return;
    }

    $rol_id = (int)$data['rol_id'];
    $sobrenombre  = $data['sobrenombre'];
    $clavel = $data['clavel'];

    $resultado = DB::queryFirstRow("
        SELECT
            u.usu_id,
            u.nombres_apellidos,
            u.email,
            u.sobrenombre,
            u.clavel,
            u.fecha_creacion,
            u.fecha_ultimo_acceso,
            u.is_activo,
            u.rol_id,
            r.nombre
        FROM reg_usu u
        INNER JOIN reg_rol r
            ON u.rol_id = r.rol_id
        WHERE u.rol_id = %i
        AND u.sobrenombre LIKE %s
        AND u.clavel LIKE %s
        AND u.is_activo = 1
    ", $rol_id, $sobrenombre, $clavel);

    if ($resultado) {

        echo json_encode([
            "respuesta" => [
                "tipo" => "EXITO",
                "descripcion" => "Administrador encontrado",
                "listado" => $resultado
            ]
        ]);

    } else {

        echo json_encode([
            "respuesta" => [
                "tipo" => "ERROR",
                "descripcion" => "No se encontro el administrador"
            ]
        ]);

    }

});


Flight::route('GET /loginVault/mercados', function(){

    include DEFINITION;

    global $nombre_app;

    $cookie_admin = $_COOKIE["sesion_admin_administrador_id_" . $nombre_app] ?? '';

    if(!$cookie_admin){
        echo json_encode([]);
        return;
    }

    $valor_key = $nombre_app . vari("KEY");

    $usu_id = perso::decrypt($cookie_admin, $valor_key);
    $usu_id = str_replace("*","",$usu_id);

    $mercados = DB::query("
        SELECT DISTINCT
            m.mercado_id,
            m.nombre
        FROM reg_mercado m

        INNER JOIN reg_neg n
            ON n.mercado_id = m.mercado_id

        INNER JOIN reg_negxusu nx
            ON nx.neg_id = n.neg_id

        WHERE nx.usu_id = %i
        AND nx.is_activo = 1
        AND n.is_activo = 1
        AND m.is_activo = 1

        ORDER BY m.nombre
    ", $usu_id);

    echo json_encode($mercados);

});

Flight::route('GET /loginVault/negocios/@mercado_id', function($mercado_id){

    include DEFINITION;

    global $nombre_app;

    $cookie_admin = $_COOKIE["sesion_admin_administrador_id_" . $nombre_app] ?? '';

    if(!$cookie_admin){
        echo json_encode([]);
        return;
    }

    $valor_key = $nombre_app . vari("KEY");

    $usu_id = perso::decrypt($cookie_admin, $valor_key);
    $usu_id = str_replace("*","",$usu_id);

    $negocios = DB::query("
        SELECT
            n.neg_id,
            n.nombre
        FROM reg_neg n

        INNER JOIN reg_negxusu nx
            ON nx.neg_id = n.neg_id

        WHERE nx.usu_id = %i
        AND n.mercado_id = %i
        AND nx.is_activo = 1
        AND n.is_activo = 1

        ORDER BY n.nombre
    ", $usu_id, $mercado_id);

    echo json_encode($negocios);

});

Flight::route('POST /loginVault/negocioSeleccionado', function(){

    include DEFINITION;

    $json = Flight::request()->getBody();
    $data = json_decode($json,true);

    $neg_id = $data['neg_id'] ?? 0;

    global $nombre_app;

    setcookie(
        "sesion_admin_neg_id_" . $nombre_app,
        $neg_id,
        0,
        "/"
    );

    echo json_encode(["res"=>"ok"]);

});


Flight::route('GET /api/usuario/mis-datos', function(){

    include DEFINITION;

    global $administrador_actual;

    if(!$administrador_actual){
        echo json_encode(["ok"=>false]);
        return;
    }

    $datos = DB::queryFirstRow("
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

            nxu.negxusu_id,
            nxu.neg_id,
            nxu.is_activo AS negxusu_activo,
            nxu.fecha_creacion AS negxusu_fecha_creacion,

            n.cod_neg,
            n.descripcion,
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

        LEFT JOIN reg_negxusu nxu
            ON u.usu_id = nxu.usu_id
            AND nxu.is_activo = 1

        LEFT JOIN reg_neg n
            ON nxu.neg_id = n.neg_id

        LEFT JOIN reg_mercado m
            ON n.mercado_id = m.mercado_id

        WHERE u.usu_id = %i
        LIMIT 1
    ", $administrador_actual['usu_id']);

    echo json_encode([
        "ok"   => true,
        "datos"=> $datos
    ]);

});


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

        // 🕒 4️⃣ Último acceso
        DB::update(
            'reg_usu',
            ['fecha_ultimo_acceso' => date('Y-m-d H:i:s')],
            'usu_id=%i',
            $usu_id
        );

        Flight::json([
            'status' => 'ok',
            'data'   => $row
        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => 'Error interno del servidor'
        ], 500);
    }
});