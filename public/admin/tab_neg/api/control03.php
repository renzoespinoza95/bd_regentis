<?php
Flight::route('GET /negxusu/obtener', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $neg_id = isset(Flight::request()->query['neg_id']) ? intval(Flight::request()->query['neg_id']) : 0;
        if ($neg_id <= 0) { Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

        $row = DB::queryFirstRow("
            SELECT nx.negxusu_id, nx.neg_id, nx.usu_id, u.dni, u.nombres_apellidos, u.is_activo
            FROM reg_negxusu nx
            INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
            WHERE nx.neg_id = %i AND nx.is_activo = 1
            ORDER BY nx.negxusu_id DESC
            LIMIT 1
        ",$neg_id);
        Flight::json(['status'=>'ok','data'=>$row ? $row : null]);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /negxusu/eliminar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $negxusu_id = intval($data['negxusu_id'] ?? 0);
        if ($negxusu_id <= 0) { Flight::json(['status'=>'error','msg'=>'negxusu_id inválido'],400); return; }

        DB::delete('reg_negxusu',"negxusu_id=%i",$negxusu_id);
        Flight::json(['status'=>'ok']);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /usu/buscar-dni', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $dni = trim($data['dni'] ?? '');
        $neg_id = intval($data['neg_id'] ?? 0);

        if ($dni === '') { Flight::json(['status'=>'error','msg'=>'DNI requerido'],400); return; }
        if ($neg_id <= 0) { Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

        $asig = DB::queryFirstRow("
            SELECT nx.negxusu_id, nx.neg_id, nx.usu_id, u.dni, u.nombres_apellidos, u.is_activo
            FROM reg_negxusu nx
            INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
            WHERE nx.neg_id = %i AND nx.is_activo = 1
            ORDER BY nx.negxusu_id DESC
            LIMIT 1
        ",$neg_id);

        if ($asig) { Flight::json(['status'=>'ok','data'=>$asig]); return; }

        $u = DB::queryFirstRow("
            SELECT usu_id, dni, nombres_apellidos, is_activo
            FROM reg_usu
            WHERE dni = %s
            LIMIT 1
        ",$dni);

        if (!$u) { Flight::json(['status'=>'ok','data'=>null]); return; }
        $u['negxusu_id'] = 0;
        Flight::json(['status'=>'ok','data'=>$u]);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /negxusu/asignar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        $usu_id = intval($data['usu_id'] ?? 0);

        if($neg_id <= 0){ Flight::json(['status' => 'error', 'msg' => 'neg_id inválido']); return; }
        if($usu_id <= 0){ Flight::json(['status' => 'error', 'msg' => 'usu_id inválido']); return; }

        $negocio = DB::queryFirstRow("SELECT * FROM reg_neg WHERE neg_id = %i AND borrado_el IS NULL LIMIT 1", $neg_id);
        if(!$negocio){ Flight::json(['status' => 'error', 'msg' => 'Negocio no existe']); return; }

        $usuario = DB::queryFirstRow("SELECT * FROM reg_usu WHERE usu_id = %i AND borrado_el IS NULL LIMIT 1", $usu_id);
        if(!$usuario){ Flight::json(['status' => 'error', 'msg' => 'Usuario no existe']); return; }

        $relacion = DB::queryFirstRow("SELECT negxusu_id FROM reg_negxusu WHERE neg_id = %i AND usu_id = %i AND borrado_el IS NULL LIMIT 1", $neg_id, $usu_id);
        if($relacion){ Flight::json(['status' => 'error', 'msg' => 'El usuario ya está asociado a este negocio']); return; }

        DB::startTransaction();
        try {
            DB::insert('reg_negxusu', [
                'neg_id' => $neg_id,
                'usu_id' => $usu_id,
                'is_activo' => 1,
                'fecha_creacion' => date('Y-m-d H:i:s')
            ]);
            $negxusu_id = DB::insertId();

            DB::update('reg_usu', ['tipoxusu_id' => 2], 'usu_id = %i', $usu_id);            
            DB::commit();
        } catch(Exception $e){
            DB::rollback();
            throw $e;
        }

        Flight::json([
            'status' => 'ok',
            'negxusu_id' => $negxusu_id,
            'neg_id' => $neg_id,
            'usu_id' => $usu_id
        ]);
    } catch(Exception $e){
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('GET /rubro/listar', function(){
    DB::query("SET NAMES 'utf8mb4'");
    $rows = DB::query("
        SELECT rubro_id, nombre, icono, is_activo
        FROM reg_rubro
        WHERE is_activo = 1
        ORDER BY nombre ASC
    ");
    Flight::json(['status'=>'ok', 'data'=>$rows]);
});