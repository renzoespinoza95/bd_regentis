<?php
Flight::route('GET /mercado/listar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $rows = DB::query("
            SELECT m.mercado_id, m.cat_mercado_id, m.nombre, m.direccion, m.is_activo, m.logo, m.topnavbar_color, m.patron_fondo, cm.nombre AS categoria
            FROM reg_mercado m
            LEFT JOIN reg_cat_mercado cm ON cm.cat_mercado_id = m.cat_mercado_id
            ORDER BY m.mercado_id DESC
        ");
        Flight::json(['status'=>'ok','data'=>$rows]);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /mercado/crear', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $nombre = trim($data['nombre'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $is_activo = intval($data['is_activo'] ?? 1);

        if ($nombre === '') { Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return; }
        if ($is_activo !== 0 && $is_activo !== 1) { $is_activo = 1; }

        DB::insert('reg_mercado',[
            'nombre'=>$nombre,
            'direccion'=>($direccion === '' ? null : $direccion),
            'fecha_creacion'=>date('Y-m-d H:i:s'),
            'is_activo'=>$is_activo
        ]);
        Flight::json(['status'=>'ok','mercado_id'=>DB::insertId()]);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /mercado/editar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $mercado_id = intval($data['mercado_id'] ?? 0);
        $nombre = trim($data['nombre'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $is_activo = intval($data['is_activo'] ?? 1);
        $cat_mercado_id = intval($data['cat_mercado_id'] ?? 0);

        if ($mercado_id <= 0) { Flight::json(['status'=>'error','msg'=>'mercado_id inválido'],400); return; }
        if ($nombre === '') { Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return; }
        if ($is_activo !== 0 && $is_activo !== 1) { $is_activo = 1; }

        $m = DB::queryFirstRow("SELECT mercado_id FROM reg_mercado WHERE mercado_id=%i",$mercado_id);
        if (!$m) { Flight::json(['status'=>'error','msg'=>'Mercado no existe'],404); return; }

        DB::update('reg_mercado',[
            'nombre'=>$nombre,
            'direccion'=>($direccion === '' ? null : $direccion),
            'is_activo'=>$is_activo,
            'logo'=>($data['logo'] ?: null),
            'topnavbar_color'=>($data['topnavbar_color'] ?: null),
            'cat_mercado_id' => $cat_mercado_id > 0 ? $cat_mercado_id : null,
            'patron_fondo'=>($data['patron_fondo'] ?: null)
        ],"mercado_id=%i",$mercado_id);

        Flight::json(['status'=>'ok']);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /mercado/eliminar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $mercado_id = intval($data['mercado_id'] ?? 0);
        if ($mercado_id <= 0) { Flight::json(['status'=>'error','msg'=>'mercado_id inválido'],400); return; }

        DB::delete('reg_mercado',"mercado_id=%i",$mercado_id);
        Flight::json(['status'=>'ok']);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WEwr/mercado/activo', function(){
    autentificar_administrador();
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $mercado_id = intval($data['mercado_id'] ?? 0);
        $is_activo = intval($data['is_activo'] ?? -1);

        if($mercado_id <= 0){ Flight::json(['status'=>'error','msg'=>'mercado_id inválido'],400); return; }
        if($is_activo !== 0 && $is_activo !== 1){ Flight::json(['status'=>'error','msg'=>'is_activo inválido'],400); return; }

        DB::update('reg_mercado', ['is_activo'=>$is_activo], "mercado_id=%i", $mercado_id);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});