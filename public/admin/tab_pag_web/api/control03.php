<?php

// 10. Actualizar visibilidad de Categoría
Flight::route('POST /xoxo/reg_cat/actualizarVisible', function(){
    include DEFINITION;
    global $administrador_actual;
    $neg_id = $administrador_actual['neg_id'] ?? null;

    $d = json_decode(Flight::request()->getBody(), true);
    $cat_id     = $d['cat_pag_web_id'] ?? null;
    $is_visible = isset($d['is_visible']) ? (int)$d['is_visible'] : 0;

    if (!$cat_id) {
        Flight::json(['status'=>'error','msg'=>'ID requerido']);
        return;
    }

    DB::update('reg_cat_pag_web', [
        'is_visible' => $is_visible
    ], "cat_pag_web_id=%i AND neg_id=%i", $cat_id, $neg_id);

    Flight::json(['status'=>'ok']);
});

// 11. Eliminar Categoría (Validando propiedad del neg_id)
Flight::route('POST /xoxo/reg_cat/eliminar', function(){
    include DEFINITION;
    global $administrador_actual;
    $neg_id = $administrador_actual['neg_id'] ?? null;

    $d = Flight::request()->data->getData();
    DB::delete('reg_cat_pag_web', "cat_pag_web_id=%i AND neg_id=%i", $d['cat_pag_web_id'], $neg_id);
    Flight::json(['status'=>'ok']);
});

// 12. Listar Subcategorías
Flight::route('GET /xoxo/reg_subcat/listar', function(){
    include DEFINITION;
    autentificar_administrador();

    $rows = DB::query("
        SELECT 
            s.*,
            c.titulo AS categoria
        FROM reg_subcat_pag_web s
        LEFT JOIN reg_cat_pag_web c 
            ON c.cat_pag_web_id = s.cat_pag_web_id
        ORDER BY s.subcat_pag_web_id DESC
    ");

    Flight::json(['status'=>'ok','data'=>$rows]);
});

// 13. Crear Subcategoría
Flight::route('POST /xoxo/reg_subcat/crear', function(){
    $d = Flight::request()->data->getData();

    DB::insert('reg_subcat_pag_web',[
        'titulo'         => $d['titulo'],
        'clave_txt'      => $d['clave_txt'] ?? '',
        'url_img'        => $d['url_img'] ?? '',
        'is_visible'     => isset($d['is_visible']) ? (int)$d['is_visible'] : 1,
        'texto01'        => $d['texto01'] ?? '',
        'texto02'        => $d['texto02'] ?? '',
        'cat_pag_web_id' => $d['cat_id']
    ]);

    Flight::json(['status'=>'ok']);
});

// 14. Editar Subcategoría
Flight::route('POST /xoxo/reg_subcat/editar', function(){
    $d = Flight::request()->data->getData();

    DB::update('reg_subcat_pag_web',[
        'titulo'         => $d['titulo'],
        'cat_pag_web_id' => $d['cat_id'],
        'clave_txt'      => $d['clave_txt'] ?? '',
        'url_img'        => $d['url_img'] ?? '',
        'is_visible'     => isset($d['is_visible']) ? (int)$d['is_visible'] : 1,
        'texto01'        => $d['texto01'] ?? '',
        'texto02'        => $d['texto02'] ?? ''
    ],"subcat_pag_web_id=%i", $d['subcat_pag_web_id']);

    Flight::json(['status'=>'ok']);
});