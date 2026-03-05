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
        WHERE email = %s
        AND clavel = %s
        AND is_activo = 1
    ", $usuario, $clavel);

    if ($info_admin) {

        global $nombre_app;

        $valor_key = $nombre_app . vari("KEY");

        $email = perso::preparar_para_encriptar($usuario);
        $enc_email = perso::encrypt($usuario, $valor_key);

        $info_admin['usu_id'] = perso::preparar_para_encriptar($info_admin['usu_id']);
        $enc_info_admin_id = perso::encrypt($info_admin['usu_id'], $valor_key);

        setcookie("sesion_admin_administrador_email_" . $nombre_app, $enc_email, 0, "/");
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

    setcookie("sesion_admin_administrador_email_" . $nombre_app, '', time() - 3600, "/");
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
        WHERE email = %s
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

    if (!isset($data['rol_id'], $data['email'], $data['clavel'])) {

        echo json_encode([
            "respuesta" => [
                "tipo" => "ERROR",
                "descripcion" => "Datos incompletos"
            ]
        ]);

        return;
    }

    $rol_id = (int)$data['rol_id'];
    $email  = $data['email'];
    $clavel = $data['clavel'];

    $resultado = DB::queryFirstRow("
        SELECT
            u.usu_id,
            u.nombres_apellidos,
            u.email,
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
        AND u.email LIKE %s
        AND u.clavel LIKE %s
        AND u.is_activo = 1
    ", $rol_id, $email, $clavel);

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

    $mercados = DB::query("
        SELECT mercado_id,nombre
        FROM reg_mercado
        WHERE is_activo=1
        ORDER BY nombre
    ");

    echo json_encode($mercados);

});

Flight::route('GET /loginVault/negocios/@mercado_id', function($mercado_id){

    include DEFINITION;

    $negocios = DB::query("
        SELECT neg_id,nombre
        FROM reg_neg
        WHERE mercado_id=%i
        AND is_activo=1
        ORDER BY nombre
    ",$mercado_id);

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
    global $nombre_app;

    if(!$administrador_actual){
        echo json_encode(["ok"=>false]);
        return;
    }

    // negocio seleccionado en login
    $neg_id = $_COOKIE["sesion_admin_neg_id_" . $nombre_app] ?? 0;
    $neg_id = intval($neg_id);

    $datos = DB::queryFirstRow("
        SELECT
            r.nombre AS rol,
            m.nombre AS mercado,
            n.nombre AS negocio
        FROM reg_usu u
        LEFT JOIN reg_rol r
            ON u.rol_id = r.rol_id
        LEFT JOIN reg_neg n
            ON n.neg_id = %i
        LEFT JOIN reg_mercado m
            ON m.mercado_id = n.mercado_id
        WHERE u.usu_id = %i
        LIMIT 1
    ", $neg_id, $administrador_actual['usu_id']);

    echo json_encode([
        "ok"   => true,
        "datos"=> $datos
    ]);

});