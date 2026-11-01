<?php

// 15. Actualizar visibilidad de Subcategoría
Flight::route('POST /xoxo/reg_subcat/actualizarVisible', function(){
    $d = json_decode(Flight::request()->getBody(), true);
    $subcat_id  = $d['subcat_pag_web_id'] ?? null;
    $is_visible = isset($d['is_visible']) ? (int)$d['is_visible'] : 0;

    if (!$subcat_id) {
        Flight::json(['status'=>'error','msg'=>'ID requerido']);
        return;
    }

    DB::update('reg_subcat_pag_web', [
        'is_visible' => $is_visible
    ], "subcat_pag_web_id=%i", $subcat_id);

    Flight::json(['status'=>'ok']);
});

// 16. Eliminar Subcategoría
Flight::route('POST /xoxo/reg_subcat/eliminar', function(){
    $d = Flight::request()->data->getData();
    DB::delete('reg_subcat_pag_web', "subcat_pag_web_id=%i", $d['subcat_pag_web_id']);
    Flight::json(['status'=>'ok']);
});

// 17. Listar Imágenes
Flight::route('GET /xoxo/reg_img/listar', function(){
    $id = $_GET['id'];
    $rows = DB::query("
        SELECT *
        FROM reg_pag_item_img
        WHERE item_pag_web_id=%i
        ORDER BY orden ASC
    ", $id);
    Flight::json(['status'=>'ok','data'=>$rows]);
});

// 18. Crear Imagen (Subida a Bunny CDN)
Flight::route('POST /xoxo/reg_img/crear', function(){
    include DEFINITION;
    autentificar_administrador();

    $item = $_POST['item'] ?? null;

    if (!$item) {
        Flight::json(['status'=>'error','msg'=>'item requerido']);
        return;
    }

    if (empty($_FILES['file']['tmp_name'])) {
        Flight::json(['status'=>'error','msg'=>'archivo requerido']);
        return;
    }

    try {
        $jpgPath = resizeTo800Jpg($_FILES['file']['tmp_name']);

        if (!file_exists($jpgPath)) {
            throw new Exception('Error al generar imagen');
        }

        $filename = 'img_' . date('Ymd_His') . '_' . rand(1000,9999) . '.jpg';

        if (!bunnyUpload($jpgPath, $filename)) {
            throw new Exception('Error al subir a Bunny');
        }

        $url = rtrim(BUNNY_CDN_BASE, '/') . '/' . SLIDER_DIR . '/' . $filename;

        DB::insert('reg_pag_item_img', [
            'item_pag_web_id' => $item,
            'url_img'         => $url
        ]);

        Flight::json(['status'=>'ok','url'=>$url]);
    } catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// 19. Crear Imagen mediante URL directa pegada
Flight::route('POST /xoxo/reg_img/crear_url', function(){
    include DEFINITION;
    autentificar_administrador();

    $d = Flight::request()->data->getData();

    if(empty($d['url']) || empty($d['item'])){
        Flight::json(['status'=>'error','msg'=>'datos incompletos']);
        return;
    }

    DB::insert('reg_pag_item_img', [
        'item_pag_web_id' => $d['item'],
        'url_img'         => trim($d['url'])
    ]);

    Flight::json(['status'=>'ok']);
});

// Actualizar imagen principal de Subcategoría
Flight::route('POST /xoxo/reg_subcat/actualizar_url_img', function () {
    include DEFINITION;
    autentificar_administrador();

    $data = json_decode(Flight::request()->getBody(), true) ?: Flight::request()->data->getData();
    $subcat_id = $data['subcat_pag_web_id'] ?? null;
    $url_img   = $data['url_img'] ?? '';

    if (!$subcat_id) {
        Flight::json(['success' => false, 'error' => 'ID requerido'], 400);
        return;
    }

    try {
        DB::update('reg_subcat_pag_web', [
            'url_img' => $url_img
        ], "subcat_pag_web_id=%i", $subcat_id);

        Flight::json(['success' => true]);
    } catch (Exception $e) {
        Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Flight::route('GET /xoxo/reg_subcat_img/listar', function(){
    $id = $_GET['id'];
    $row = DB::queryFirstRow("SELECT subcat_pag_web_id, url_img, is_visible, orden FROM reg_subcat_pag_web WHERE subcat_pag_web_id=%i", $id);
    $rows = ($row && !empty($row['url_img'])) ? [[
        'pag_item_img_id' => $row['subcat_pag_web_id'],
        'url_img' => $row['url_img'],
        'is_visible' => $row['is_visible'],
        'orden' => $row['orden']
    ]] : [];
    Flight::json(['status'=>'ok','data'=>$rows]);
});

Flight::route('POST /xoxo/reg_subcat_img/crear_desde_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $data = json_decode(Flight::request()->getBody(), true);
    
    $subcat_id = $data['subcat_pag_web_id'] ?? null;
    $url_img = $data['url_img'] ?? null;

    if ($subcat_id && $url_img) {
        DB::update('reg_subcat_pag_web', ['url_img' => $url_img], "subcat_pag_web_id=%i", $subcat_id);
    }
    Flight::json(['success' => true]);
});

Flight::route('POST /xoxo/reg_subcat_img/crear_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    
    $subcat_id = $d['subcat'] ?? null;
    $url = trim($d['url'] ?? '');

    if ($subcat_id) {
        DB::update('reg_subcat_pag_web', ['url_img' => $url], "subcat_pag_web_id=%i", $subcat_id);
    }
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_subcat_img/ordenar', function(){
    include DEFINITION;
    autentificar_administrador();
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_subcat_img/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    $id = $d['id'] ?? null;
    
    if ($id) {
        $row = DB::queryFirstRow("SELECT url_img FROM reg_subcat_pag_web WHERE subcat_pag_web_id=%i", $id);
        if ($row && !empty($row['url_img'])) {
            bunnyDelete($row['url_img']);
        }
        DB::update('reg_subcat_pag_web', ['url_img' => ''], "subcat_pag_web_id=%i", $id);
    }
    Flight::json(['status'=>'ok']);
});