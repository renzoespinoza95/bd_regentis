<?php

// Nuevo endpoint para obtener info del negocio actual
Flight::route('GET /xoxo/negocio/info', function(){
    header('Content-Type: application/json; charset=utf-8');
    try {
        include DEFINITION;
        autentificar_administrador();
        global $administrador_actual;
        $neg_id = $administrador_actual['neg_id'] ?? 0;
        $nombre = '';
        if ($neg_id) {
            $neg = DB::queryFirstRow("SELECT nombre FROM reg_neg WHERE neg_id = %i", $neg_id);
            if ($neg) {
                $nombre = $neg['nombre'];
            }
        }
        echo json_encode(['status' => 'ok', 'neg_id' => $neg_id, 'nombre' => $nombre]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

// 5. Actualizar visibilidad de Ítem
Flight::route('POST /xoxo/reg_item/actualizarVisible', function(){
    header('Content-Type: application/json; charset=utf-8');
    $d = json_decode(Flight::request()->getBody(), true);
    $item_id    = $d['item_pag_web_id'] ?? null;
    $is_visible = isset($d['is_visible']) ? (int)$d['is_visible'] : 0;

    if (!$item_id) {
        echo json_encode(['status'=>'error','msg'=>'ID requerido']);
        return;
    }

    DB::update('reg_item_pag_web', [
        'is_visible' => $is_visible
    ], "item_pag_web_id=%i", $item_id);

    echo json_encode(['status'=>'ok']);
});

// 6. Eliminar Ítem
Flight::route('POST /xoxo/reg_item/eliminar', function(){
    header('Content-Type: application/json; charset=utf-8');
    try{
        $d = Flight::request()->data->getData();
        DB::delete('reg_item_pag_web', "item_pag_web_id=%i", $d['item_pag_web_id']);
        echo json_encode(['status'=>'ok']);
    }catch(Exception $e){
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// 7. Listar Categorías (Filtrado por el neg_id del usuario actual)
Flight::route('GET /xoxo/reg_cat/listar', function(){
    header('Content-Type: application/json; charset=utf-8');
    try {
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
        echo json_encode(['status'=>'ok','data'=>$rows]);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// 8. Crear Categoría (Asociada al neg_id actual)
Flight::route('POST /xoxo/reg_cat/crear', function(){
    header('Content-Type: application/json; charset=utf-8');
    try {
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

        echo json_encode(['status'=>'ok']);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// 9. Editar Categoría (Mantiene y asegura el neg_id actual)
Flight::route('POST /xoxo/reg_cat/editar', function(){
    header('Content-Type: application/json; charset=utf-8');
    try {
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

        echo json_encode(['status'=>'ok']);
    } catch (Exception $e) {
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// Actualizar imagen principal de Categoría
Flight::route('POST /xoxo/reg_cat/actualizar_url_img', function () {
    header('Content-Type: application/json; charset=utf-8');
    include DEFINITION;
    autentificar_administrador();

    $data = json_decode(Flight::request()->getBody(), true) ?: Flight::request()->data->getData();
    $cat_id  = $data['cat_pag_web_id'] ?? null;
    $url_img = $data['url_img'] ?? '';

    if (!$cat_id) {
        echo json_encode(['success' => false, 'error' => 'ID requerido']);
        return;
    }

    try {
        DB::update('reg_cat_pag_web', [
            'url_img' => $url_img
        ], "cat_pag_web_id=%i", $cat_id);

        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
});

Flight::route('GET /xoxo/reg_cat_img/listar', function(){
    header('Content-Type: application/json; charset=utf-8');
    $id = $_GET['id'];
    $row = DB::queryFirstRow("SELECT cat_pag_web_id, url_img, is_visible, orden FROM reg_cat_pag_web WHERE cat_pag_web_id=%i", $id);
    $rows = ($row && !empty($row['url_img'])) ? [[
        'pag_item_img_id' => $row['cat_pag_web_id'],
        'url_img' => $row['url_img'],
        'is_visible' => $row['is_visible'],
        'orden' => $row['orden']
    ]] : [];
    echo json_encode(['status'=>'ok','data'=>$rows]);
});

Flight::route('POST /xoxo/reg_cat_img/crear_desde_url', function(){
    header('Content-Type: application/json; charset=utf-8');
    include DEFINITION;
    autentificar_administrador();
    $data = json_decode(Flight::request()->getBody(), true);
    
    $cat_id = $data['cat_pag_web_id'] ?? null;
    $url_img = $data['url_img'] ?? null;

    if ($cat_id && $url_img) {
        DB::update('reg_cat_pag_web', ['url_img' => $url_img], "cat_pag_web_id=%i", $cat_id);
    }
    echo json_encode(['success' => true]);
});

Flight::route('POST /xoxo/reg_cat_img/crear_url', function(){
    header('Content-Type: application/json; charset=utf-8');
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    
    $cat_id = $d['cat'] ?? null;
    $url = trim($d['url'] ?? '');

    if ($cat_id) {
        DB::update('reg_cat_pag_web', ['url_img' => $url], "cat_pag_web_id=%i", $cat_id);
    }
    echo json_encode(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_cat_img/ordenar', function(){
    header('Content-Type: application/json; charset=utf-8');
    include DEFINITION;
    autentificar_administrador();
    echo json_encode(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_cat_img/eliminar', function(){
    header('Content-Type: application/json; charset=utf-8');
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
    echo json_encode(['status'=>'ok']);
});