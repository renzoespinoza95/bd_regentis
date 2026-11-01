<?php

// 5. Actualizar visibilidad de Ítem
Flight::route('POST /xoxo/reg_item/actualizarVisible', function(){
    $d = json_decode(Flight::request()->getBody(), true);
    $item_id    = $d['item_pag_web_id'] ?? null;
    $is_visible = isset($d['is_visible']) ? (int)$d['is_visible'] : 0;

    if (!$item_id) {
        Flight::json(['status'=>'error','msg'=>'ID requerido']);
        return;
    }

    DB::update('reg_item_pag_web', [
        'is_visible' => $is_visible
    ], "item_pag_web_id=%i", $item_id);

    Flight::json(['status'=>'ok']);
});

// 6. Eliminar Ítem
Flight::route('POST /xoxo/reg_item/eliminar', function(){
    try{
        $d = Flight::request()->data->getData();
        DB::delete('reg_item_pag_web', "item_pag_web_id=%i", $d['item_pag_web_id']);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// 7. Listar Categorías (Filtrado por el neg_id del usuario actual)
Flight::route('GET /xoxo/reg_cat/listar', function(){
    include DEFINITION;
    global $administrador_actual;
    $neg_id = $administrador_actual['neg_id'] ?? 0;

    $rows = DB::query("
        SELECT 
            cat_pag_web_id,
            titulo,
            clave_txt,
            url_img,
            is_visible,
            texto01,
            texto02,
            neg_id
        FROM reg_cat_pag_web
        WHERE neg_id = %i
        ORDER BY orden ASC, cat_pag_web_id DESC
    ", $neg_id);
    Flight::json(['status'=>'ok','data'=>$rows]);
});

// 8. Crear Categoría (Asociada al neg_id actual)
Flight::route('POST /xoxo/reg_cat/crear', function(){
    include DEFINITION;
    global $administrador_actual;
    $neg_id = $administrador_actual['neg_id'] ?? null;

    $d = Flight::request()->data->getData();

    DB::insert('reg_cat_pag_web',[
        'titulo'     => $d['titulo'],
        'clave_txt'  => $d['clave_txt'] ?? '',
        'url_img'    => $d['url_img'] ?? '',
        'is_visible' => isset($d['is_visible']) ? (int)$d['is_visible'] : 1,
        'texto01'    => $d['texto01'] ?? '',
        'texto02'    => $d['texto02'] ?? '',
        'neg_id'     => $neg_id
    ]);

    Flight::json(['status'=>'ok']);
});

// 9. Editar Categoría (Mantiene y asegura el neg_id actual)
Flight::route('POST /xoxo/reg_cat/editar', function(){
    include DEFINITION;
    global $administrador_actual;
    $neg_id = $administrador_actual['neg_id'] ?? null;
    
    $d = Flight::request()->data->getData();

    DB::update('reg_cat_pag_web',[
        'titulo'     => $d['titulo'],
        'clave_txt'  => $d['clave_txt'] ?? '',
        'url_img'    => $d['url_img'] ?? '',
        'is_visible' => isset($d['is_visible']) ? (int)$d['is_visible'] : 1,
        'texto01'    => $d['texto01'] ?? '',
        'texto02'    => $d['texto02'] ?? '',
        'neg_id'     => $neg_id
    ],"cat_pag_web_id=%i AND neg_id=%i", $d['cat_pag_web_id'], $neg_id);

    Flight::json(['status'=>'ok']);
});

// Actualizar imagen principal de Categoría
Flight::route('POST /xoxo/reg_cat/actualizar_url_img', function () {
    include DEFINITION;
    autentificar_administrador();

    $data = json_decode(Flight::request()->getBody(), true) ?: Flight::request()->data->getData();
    $cat_id  = $data['cat_pag_web_id'] ?? null;
    $url_img = $data['url_img'] ?? '';

    if (!$cat_id) {
        Flight::json(['success' => false, 'error' => 'ID requerido'], 400);
        return;
    }

    try {
        DB::update('reg_cat_pag_web', [
            'url_img' => $url_img
        ], "cat_pag_web_id=%i", $cat_id);

        Flight::json(['success' => true]);
    } catch (Exception $e) {
        Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Flight::route('GET /xoxo/reg_cat_img/listar', function(){
    $id = $_GET['id'];
    $row = DB::queryFirstRow("SELECT cat_pag_web_id, url_img, is_visible, orden FROM reg_cat_pag_web WHERE cat_pag_web_id=%i", $id);
    $rows = ($row && !empty($row['url_img'])) ? [[
        'pag_item_img_id' => $row['cat_pag_web_id'],
        'url_img' => $row['url_img'],
        'is_visible' => $row['is_visible'],
        'orden' => $row['orden']
    ]] : [];
    Flight::json(['status'=>'ok','data'=>$rows]);
});

Flight::route('POST /xoxo/reg_cat_img/crear_desde_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $data = json_decode(Flight::request()->getBody(), true);
    
    $cat_id = $data['cat_pag_web_id'] ?? null;
    $url_img = $data['url_img'] ?? null;

    if ($cat_id && $url_img) {
        DB::update('reg_cat_pag_web', ['url_img' => $url_img], "cat_pag_web_id=%i", $cat_id);
    }
    Flight::json(['success' => true]);
});

Flight::route('POST /xoxo/reg_cat_img/crear_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    
    $cat_id = $d['cat'] ?? null;
    $url = trim($d['url'] ?? '');

    if ($cat_id) {
        DB::update('reg_cat_pag_web', ['url_img' => $url], "cat_pag_web_id=%i", $cat_id);
    }
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_cat_img/ordenar', function(){
    include DEFINITION;
    autentificar_administrador();
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_cat_img/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    $id = $d['id'] ?? null;
    
    if ($id) {
        $row = DB::queryFirstRow("SELECT url_img FROM reg_cat_pag_web WHERE cat_pag_web_id=%i", $id);
        if ($row && !empty($row['url_img'])) {
            bunnyDelete($row['url_img']);
        }
        DB::update('reg_cat_pag_web', ['url_img' => ''], "cat_pag_web_id=%i", $id);
    }
    Flight::json(['status'=>'ok']);
});