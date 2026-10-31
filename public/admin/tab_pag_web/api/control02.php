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