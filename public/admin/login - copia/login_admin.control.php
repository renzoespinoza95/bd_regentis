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

    if(empty($usuario) || empty($clavel)){
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

    if($info_admin){

        global $nombre_app;

        $valor_key = $nombre_app . vari("KEY");

        $email = perso::preparar_para_encriptar($usuario);
        $enc_email = perso::encrypt($usuario, $valor_key);

        $info_admin['usu_id'] = perso::preparar_para_encriptar($info_admin['usu_id']);
        $enc_info_admin_id = perso::encrypt($info_admin['usu_id'], $valor_key);

        setcookie("sesion_admin_administrador_email_" . $nombre_app, $enc_email, 0, "/");
        setcookie("sesion_admin_administrador_id_" . $nombre_app, $enc_info_admin_id, 0, "/");

        echo perso::ok();

    }else{

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