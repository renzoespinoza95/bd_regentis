<?php

//==============================
// Vista login
//==============================
Flight::route('GET /', function() {

    include DEFINITION;

    $mBase = $varhost . "/public/admin/login/";
    // include VARPATH . "/public/admin/login/inicio.php";
    echo "Pagina de inicio de regentis";

});


//==============================
// Dashboard administrador
//==============================
Flight::route('GET /admin/dash', function() {

    include DEFINITION;

    global $nombre_app, $apphost, $ssa_id;

    if(empty($ssa_id)){
        Flight::redirect($apphost . "/loginVault");
        exit;
    }

    $valor_key = $nombre_app . vari("KEY");

    $usu_id = perso::decrypt($ssa_id, $valor_key);
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
    include VARPATH . "/public/admin/login/inicio.php";

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

        setcookie("ssa_sobrenombre_" . $nombre_app, $enc_sobrenombre, 0, "/");
        setcookie("ssa_id_" . $nombre_app, $enc_info_admin_id, 0, "/");

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

    setcookie("ssa_sobrenombre_" . $nombre_app, '', time() - 3600, "/");
    setcookie("ssa_id_" . $nombre_app, '', time() - 3600, "/");

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

Flight::route('GET /loginVault/mercados', function(){

    include DEFINITION;

    global $nombre_app;

    $cookie_admin = $_COOKIE["ssa_id_" . $nombre_app] ?? '';

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

    $cookie_admin = $_COOKIE["ssa_id_" . $nombre_app] ?? '';

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