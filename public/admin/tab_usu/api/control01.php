<?php
// Endpoints de Listado, Detalle y Búsqueda general

Flight::route('GET /usuario/listar', function(){
    include DEFINITION;
    try{
        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("
            SELECT 
                u.usu_id,
                u.cod_usu,
                u.img_perfil,
                u.sobrenombre,
                u.nombres_apellidos,
                u.fecha_nacimiento,
                u.celular,
                u.clavel,
                u.provincia,
                u.is_fantasma,
                u.fecha_creacion,
                u.tipoxusu_id,
                u.dni,
                u.google_uid,
                u.pin_code,
                u.pin_code_fecha_fin,
                r.nombre AS rol_nombre,
                u.is_activo,
                n.neg_id, 
                IFNULL(NULLIF(n.nombre,''), '—') AS negocio_nombre
            FROM reg_usu u
            LEFT JOIN reg_rol r ON r.rol_id = u.rol_id
            LEFT JOIN reg_negxusu nxu ON nxu.usu_id = u.usu_id AND nxu.is_activo = 1 AND nxu.borrado_el IS NULL
            LEFT JOIN reg_neg n ON n.neg_id = nxu.neg_id
            WHERE u.borrado_el IS NULL    
            ORDER BY u.usu_id DESC
        ");

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        exit;
    }catch(Exception $e){
        if (ob_get_length()) ob_clean();
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

Flight::route('GET /tipoxusu/listar', function(){
    include DEFINITION;
    $rows = DB::query('SELECT * FROM reg_tipoxusu ORDER BY 1 DESC');
    Flight::json($rows);
});

Flight::route('POST /usuario/buscar', function () {
    $d = json_decode(Flight::request()->getBody(), true);
    $texto = trim($d['sobrenombre'] ?? $d['nombre'] ?? '');
    if ($texto === '') {
        Flight::json([]);
        return;
    }
    DB::query("SET NAMES 'utf8'");
    $like = '%' . $texto . '%';
    $rows = DB::query(
        'SELECT usu_id AS usuario_id, cod_usu AS cod_usuario, sobrenombre, celular, provincia, tipoxusu_id 
         FROM reg_usu 
         WHERE sobrenombre LIKE %s AND borrado_el IS NULL 
         ORDER BY usu_id DESC',
        $like
    );
    Flight::json($rows);
});

Flight::route('POST /usuario/liquidar', function () {
    DB::query("SET NAMES 'utf8mb4'");
    $d = json_decode(Flight::request()->getBody(), true) ?: [];
    $usu_id = intval($d['usu_id'] ?? 0);

    if(!$usu_id){
        Flight::json(['success' => false, 'msg' => 'usu_id requerido'], 400);
        return;
    }

    DB::startTransaction();
    try{
        DB::update('reg_usu', ['borrado_el' => date('Y-m-d H:i:s')], 'usu_id=%i', $usu_id);
        DB::update('reg_negxusu', ['is_activo' => 0, 'borrado_el' => date('Y-m-d H:i:s')], 'usu_id=%i', $usu_id);
        DB::commit();
        Flight::json(['success' => true, 'usu_id' => $usu_id, 'msg' => 'Usuario liquidado']);
    }catch(Exception $e){
        DB::rollback();
        Flight::json(['success' => false, 'msg' => $e->getMessage()], 500);
    }
});

Flight::route('GET /xico/negocios/validados', function() {
    try {
        $rows = DB::query("SELECT neg_id, nombre FROM reg_neg WHERE is_validado = 1 ORDER BY nombre ASC");
        Flight::json($rows);
    } catch (Exception $e) {
        Flight::json(['success' => false, 'msg' => $e->getMessage()], 500);
    }
});