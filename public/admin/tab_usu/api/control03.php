<?php
// Funciones avanzadas, PIN codes, Automáticos y Toggles

Flight::route('POST /NTol/usuarioAutomatico', function(){
    include DEFINITION;
    DB::query("SET NAMES 'utf8mb4'");
    DB::startTransaction();
    try{
        $nombre = DB::queryFirstField("SELECT nombre FROM tt_nombre ORDER BY RAND() LIMIT 1");
        $apellido = DB::queryFirstField("SELECT apellido FROM tt_apellido ORDER BY RAND() LIMIT 1");
        $nick = DB::queryFirstField("SELECT nick FROM tt_nick ORDER BY RAND() LIMIT 1");

        $nombre_completo = trim($nombre . ' ' . $apellido);
        $dni = str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $celular = '9' . rand(10000000, 99999999);
        $cod_usu = 'ADM' . rand(1000,9999);
        $google_uid = strtoupper(substr(md5(uniqid('',true)), 0, 10));
        $fecha_actual = date('Y-m-d H:i:s');

        DB::insert('reg_usu', [
            'cod_usu' => $cod_usu,
            'google_uid' => $google_uid,
            'img_perfil' => 'https://barsi-img.b-cdn.net/recursos/logo-regentis.png',
            'sobrenombre' => $nick,
            'nombres_apellidos' => $nombre_completo,
            'celular' => $celular,
            'dni' => $dni,
            'is_activo' => 1,
            'fecha_creacion' => $fecha_actual,
            'tipoxusu_id' => 1,
            'rol_id' => 1,
            'is_fantasma' => 1,
            'is_acepto_terminos' => 0,
            'clavel' => '12qw12'
        ]);

        $usu_id = DB::insertId();
        DB::commit();

        Flight::json([
            'success' => true,
            'usu_id' => $usu_id,
            'usuario' => [
                'usu_id' => $usu_id,
                'cod_usu' => $cod_usu,
                'google_uid' => $google_uid,
                'img_perfil' => 'https://barsi-img.b-cdn.net/recursos/logo-regentis.png',
                'sobrenombre' => $nick,
                'nombres_apellidos' => $nombre_completo,
                'celular' => $celular,
                'dni' => $dni,
                'fecha_creacion' => $fecha_actual,
                'tipoxusu_id' => 1,
                'rol_nombre' => '—',
                'negocio_nombre' => '—',
                'is_fantasma' => 1
            ]
        ]);
    }catch(Exception $e){
        DB::rollback();
        Flight::json(['success' => false, 'msg' => $e->getMessage()], 500);
    }
});

Flight::route('POST /IS54/toggleIsFantasma', function () {
    include DEFINITION;
    DB::query("SET NAMES 'utf8mb4'");
    $data = json_decode(Flight::request()->getBody(), true);
    $usu_id = intval($data['usu_id'] ?? 0);

    if(!$usu_id){
        Flight::json(['res' => 'error', 'msg' => 'usu_id inválido'], 400);
        return;
    }

    $usuario = DB::queryFirstRow("SELECT usu_id, is_fantasma FROM reg_usu WHERE usu_id = %i AND borrado_el IS NULL LIMIT 1", $usu_id);
    if(!$usuario){
        Flight::json(['res' => 'error', 'msg' => 'Usuario no encontrado'], 404);
        return;
    }

    $nuevo_estado = intval(!$usuario['is_fantasma']);
    DB::update('reg_usu', ['is_fantasma' => $nuevo_estado], 'usu_id=%i', $usu_id);

    Flight::json([
        'res' => 'ok',
        'msg' => $nuevo_estado ? 'Usuario convertido a fantasma' : 'Usuario restaurado',
        'is_fantasma' => $nuevo_estado
    ]);
});

Flight::route('POST /KdB0/crearPin', function () {
    include DEFINITION;
    autentificar_administrador();
    DB::query("SET NAMES 'utf8mb4'");

    $data = json_decode(Flight::request()->getBody(), true) ?: Flight::request()->data->getData();
    $usu_id = intval($data['usu_id'] ?? 0);

    if ($usu_id <= 0) {
        Flight::json(['status' => 'error', 'msg' => 'ID de usuario inválido'], 400);
        return;
    }

    $usuario = DB::queryFirstRow("SELECT cod_usu FROM reg_usu WHERE usu_id = %i", $usu_id);

    if (!$usuario) {
        Flight::json(['status' => 'error', 'msg' => 'Usuario no encontrado'], 404);
        return;
    }

    $cod_usu = $usuario['cod_usu'];
    $pin_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

    $fecha_actual = new DateTime();
    $fecha_actual->modify('+1 year');
    $pin_code_fecha_fin = $fecha_actual->format('Y-m-d H:i:s');

    DB::update('reg_usu', [
        'pin_code' => $pin_code,
        'pin_code_fecha_fin' => $pin_code_fecha_fin
    ], 'usu_id=%i', $usu_id);

    Flight::json([
        'status'   => 'ok',
        'cod_usu'  => $cod_usu,
        'pin_code' => $pin_code
    ]);
});

Flight::route('POST /usuario/reiniciar', function(){
    include DEFINITION;
    DB::query("SET NAMES 'utf8mb4'");
    $d = json_decode(Flight::request()->getBody(), true) ?: [];
    $usu_id = intval($d['usu_id'] ?? 0);
    if(!$usu_id){
        Flight::json(['status' => 'error', 'msg' => 'usu_id requerido'], 400);
        return;
    }

    DB::startTransaction();
    try {
        $nombre = DB::queryFirstField("SELECT nombre FROM tt_nombre ORDER BY RAND() LIMIT 1");
        $apellido = DB::queryFirstField("SELECT apellido FROM tt_apellido ORDER BY RAND() LIMIT 1");
        $nick = DB::queryFirstField("SELECT nick FROM tt_nick ORDER BY RAND() LIMIT 1");

        $nombre_completo = trim($nombre . ' ' . $apellido);
        $dni = str_pad(rand(1, 99999999), 8, '0', STR_PAD_LEFT);
        $celular = '9' . rand(10000000, 99999999);

        DB::delete('reg_negxusu', "usu_id=%i", $usu_id);
        DB::update('reg_usu', [
            'nombres_apellidos' => $nombre_completo,
            'sobrenombre' => $nick,
            'google_uid' => strtoupper(substr(md5(uniqid('', true)), 0, 10)),    
            'celular' => $celular,
            'dni' => $dni,
            'tipoxusu_id' => 1,
            'rol_id' => 1,
            'is_acepto_terminos' => 0,
            'clavel' => '12qw12',
            'img_perfil' => 'https://barsi-img.b-cdn.net/recursos/logo-regentis.png'
        ], "usu_id=%i", $usu_id);

        DB::commit();
        Flight::json(['status' => 'ok', 'msg' => 'Usuario reiniciado']);
    } catch(Exception $e){
        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
});