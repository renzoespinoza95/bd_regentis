<?php
Flight::route('GET /rubroxneg/listar', function(){
    $neg_id = intval(Flight::request()->query['neg_id']);
    $rows = DB::query("
        SELECT rn.rubroxneg_id, r.rubro_id, r.nombre
        FROM deux_rubroxneg rn
        INNER JOIN deux_rubro r ON r.rubro_id = rn.rubro_id
        WHERE rn.neg_id=%i
    ",$neg_id);
    Flight::json(['status'=>'ok','data'=>$rows]);
});

Flight::route('POST /rubroxneg/crear', function(){
    $data = Flight::request()->data->getData();
    DB::insert('deux_rubroxneg',[
        'neg_id'=>$data['neg_id'],
        'rubro_id'=>$data['rubro_id']
    ]);
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /rubroxneg/eliminar', function(){
    $data = Flight::request()->data->getData();
    DB::delete('deux_rubroxneg',"rubroxneg_id=%i",$data['rubroxneg_id']);
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /neg/activo', function(){
    autentificar_administrador();
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        $is_activo = intval($data['is_activo'] ?? -1);

        if($neg_id <= 0){ Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }
        if($is_activo !== 0 && $is_activo !== 1){ Flight::json(['status'=>'error','msg'=>'is_activo inválido'],400); return; }

        DB::update('reg_neg', ['is_activo' => $is_activo], "neg_id=%i", $neg_id);
        Flight::json(['status'=>'ok']);
    } catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /QQvN/neg/logo/defecto', function(){
    autentificar_administrador();
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        if($neg_id <= 0){ Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

        $logo = 'https://barsi-img.b-cdn.net/recursos/sg3f.png';
        DB::update('reg_neg', ['img_logo' => $logo], "neg_id=%i", $neg_id);
        Flight::json(['status'=>'ok', 'img_logo'=>$logo]);
    } catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /QQvN/neg/logo/random', function(){
    autentificar_administrador();
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        if($neg_id <= 0){ Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

        $img = DB::queryFirstField("SELECT url FROM tt_imagen ORDER BY RAND() LIMIT 1");
        if(!$img){ Flight::json(['status'=>'error','msg'=>'No existen imágenes'],404); return; }

        DB::update('reg_neg', ['img_logo' => $img], "neg_id=%i", $neg_id);
        Flight::json(['status'=>'ok', 'img_logo'=>$img]);
    } catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /QQvN/buscarUsuarioCodUsu', function(){
    autentificar_administrador();
    DB::query("SET NAMES 'utf8mb4'");
    $data = Flight::request()->data->getData();
    $cod_usu = trim($data['cod_usu'] ?? '');
    if(!$cod_usu){ Flight::json(['status'=>'error','msg'=>'cod_usu requerido'],400); return; }

    $usu = DB::queryFirstRow("
        SELECT usu_id, cod_usu, nombres_apellidos, sobrenombre, celular, dni, img_perfil, email, tipoxusu_id
        FROM reg_usu
        WHERE cod_usu = %s AND (borrado_el IS NULL)
        LIMIT 1
    ", $cod_usu);

    if(!$usu){ Flight::json(['status'=>'error','msg'=>'Usuario no encontrado'],404); return; }
    Flight::json(['status'=>'ok', 'usuario'=>$usu]);
});

Flight::route('POST /QQvN/neg/propietarios', function(){
    autentificar_administrador();
    DB::query("SET NAMES 'utf8mb4'");
    $data = Flight::request()->data->getData();
    $neg_id = intval($data['neg_id'] ?? 0);

    $rows = DB::query("
        SELECT nx.negxusu_id, nx.neg_id, nx.usu_id, nx.is_activo, nx.fecha_creacion, u.cod_usu, u.nombres_apellidos, u.sobrenombre, u.celular, u.email, u.img_perfil, u.tipoxusu_id
        FROM reg_negxusu nx
        INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
        WHERE nx.neg_id = %i AND nx.borrado_el IS NULL
        ORDER BY nx.negxusu_id DESC
    ", $neg_id);
    Flight::json(['status'=>'ok', 'rows'=>$rows]);
});

Flight::route('POST /QQvN/neg/propietario/eliminar', function(){
    autentificar_administrador();
    DB::query("SET NAMES 'utf8mb4'");
    $data = Flight::request()->data->getData();
    $negxusu_id = intval($data['negxusu_id'] ?? 0);
    if(!$negxusu_id){ Flight::json(['status'=>'error','msg'=>'negxusu_id requerido'],400); return; }

    $row = DB::queryFirstRow("SELECT negxusu_id, usu_id FROM reg_negxusu WHERE negxusu_id = %i", $negxusu_id);
    if(!$row){ Flight::json(['status'=>'error','msg'=>'Asignación no encontrada'],404); return; }

    DB::update('reg_negxusu', ['borrado_el' => date('Y-m-d H:i:s')], "negxusu_id=%i", $negxusu_id);
    DB::update('reg_usu', ['tipoxusu_id' => 1], "usu_id=%i", $row['usu_id']);
    Flight::json(['status'=>'ok']);
});

Flight::route('GET /WEwr/catmercado/listar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $rows = DB::query("
            SELECT cat_mercado_id, nombre, is_visible, is_activo
            FROM reg_cat_mercado
            WHERE borrado_el IS NULL
            ORDER BY cat_mercado_id DESC
        ");
        Flight::json(['status'=>'ok', 'data'=>$rows]);
    }catch(Exception $e){
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WEwr/catmercado/crear', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $nombre = trim($data['nombre'] ?? '');
        $is_visible = intval($data['is_visible'] ?? 1);
        $is_activo = intval($data['is_activo'] ?? 1);

        if($nombre === ''){ Flight::json(['status'=>'error', 'msg'=>'Nombre requerido'],400); return; }

        DB::insert('reg_cat_mercado', [
            'nombre'=>$nombre,
            'is_visible'=>$is_visible,
            'is_activo'=>$is_activo
        ]);
        Flight::json(['status'=>'ok', 'cat_mercado_id'=>DB::insertId()]);
    }catch(Exception $e){
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WEwr/catmercado/editar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $cat_mercado_id = intval($data['cat_mercado_id'] ?? 0);
        $nombre = trim($data['nombre'] ?? '');
        $is_visible = intval($data['is_visible'] ?? 1);
        $is_activo = intval($data['is_activo'] ?? 1);

        if($cat_mercado_id <= 0){ Flight::json(['status'=>'error', 'msg'=>'cat_mercado_id inválido'],400); return; }
        if($nombre === ''){ Flight::json(['status'=>'error', 'msg'=>'Nombre requerido'],400); return; }

        DB::update('reg_cat_mercado', [
            'nombre'=>$nombre,
            'is_visible'=>$is_visible,
            'is_activo'=>$is_activo
        ], "cat_mercado_id=%i", $cat_mercado_id);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WEwr/catmercado/eliminar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $cat_mercado_id = intval($data['cat_mercado_id'] ?? 0);
        if($cat_mercado_id <= 0){ Flight::json(['status'=>'error', 'msg'=>'cat_mercado_id inválido'],400); return; }

        DB::update('reg_cat_mercado', ['borrado_el'=>date('Y-m-d H:i:s')], "cat_mercado_id=%i", $cat_mercado_id);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WEwr/catmercado/activo', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $cat_mercado_id = intval($data['cat_mercado_id'] ?? 0);
        $is_activo = intval($data['is_activo'] ?? -1);

        if($cat_mercado_id <= 0){ Flight::json(['status'=>'error', 'msg'=>'cat_mercado_id inválido'],400); return; }
        if($is_activo !== 0 && $is_activo !== 1){ Flight::json(['status'=>'error', 'msg'=>'is_activo inválido'],400); return; }

        DB::update('reg_cat_mercado', ['is_activo'=>$is_activo], "cat_mercado_id=%i", $cat_mercado_id);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WEwr/catmercado/visible', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $cat_mercado_id = intval($data['cat_mercado_id'] ?? 0);
        $is_visible = intval($data['is_visible'] ?? -1);

        if($cat_mercado_id <= 0){ Flight::json(['status'=>'error', 'msg'=>'cat_mercado_id inválido'],400); return; }
        if($is_visible !== 0 && $is_visible !== 1){ Flight::json(['status'=>'error', 'msg'=>'is_visible inválido'],400); return; }

        DB::update('reg_cat_mercado', ['is_visible'=>$is_visible], "cat_mercado_id=%i", $cat_mercado_id);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /neg_pago/listar', function(){
    autentificar_administrador();
    DB::query("SET NAMES 'utf8mb4'");
    $data = Flight::request()->data->getData();
    $neg_id = intval($data['neg_id'] ?? 0);

    $rows = DB::query("
        SELECT neg_pago_id, motivo, monto, fecha_inicio_premium, fecha_fin_premium, is_aprobado, yaplin_id
        FROM reg_neg_pago
        WHERE neg_id = %i AND borrado_el IS NULL
        ORDER BY neg_pago_id DESC
    ", $neg_id);
    Flight::json(['status' => 'ok', 'rows' => $rows]);
});

Flight::route('POST /neg_pago/crear', function(){
    autentificar_administrador();
    DB::query("SET NAMES 'utf8mb4'");
    $data = Flight::request()->data->getData();
    $neg_id = intval($data['neg_id'] ?? 0);
    $motivo = trim($data['motivo'] ?? '');
    $monto = floatval($data['monto'] ?? 0);
    $fecha_inicio_premium = trim($data['fecha_inicio_premium'] ?? '');
    $fecha_fin_premium = trim($data['fecha_fin_premium'] ?? '');
    $is_aprobado = intval($data['is_aprobado'] ?? 1);

    if($neg_id <= 0){ Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

    DB::insert('reg_neg_pago', [
        'neg_id' => $neg_id,
        'motivo' => $motivo,
        'monto' => $monto,
        'fecha_inicio_premium' => $fecha_inicio_premium,
        'fecha_fin_premium' => $fecha_fin_premium,
        'is_aprobado' => $is_aprobado
    ]);
    Flight::json(['status'=>'ok', 'neg_pago_id'=>DB::insertId()]);
});

Flight::route('POST /neg_pago/eliminar', function(){
    autentificar_administrador();
    $data = Flight::request()->data->getData();
    $neg_pago_id = intval($data['neg_pago_id'] ?? 0);
    if($neg_pago_id <= 0){ Flight::json(['status'=>'error'],400); return; }

    DB::update('reg_neg_pago', ['borrado_el'=>date('Y-m-d H:i:s')], "neg_pago_id=%i", $neg_pago_id);
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /neg_pago/toggle_aprobado', function(){
    autentificar_administrador();
    $data = Flight::request()->data->getData();
    $neg_pago_id = intval($data['neg_pago_id'] ?? 0);
    $row = DB::queryFirstRow("SELECT is_aprobado FROM reg_neg_pago WHERE neg_pago_id = %i", $neg_pago_id);
    if(!$row){ Flight::json(['status'=>'error'],404); return; }

    DB::update('reg_neg_pago', ['is_aprobado'=> $row['is_aprobado'] ? 0 : 1], 'neg_pago_id=%i', $neg_pago_id);
    Flight::json(['status'=>'ok']);
});