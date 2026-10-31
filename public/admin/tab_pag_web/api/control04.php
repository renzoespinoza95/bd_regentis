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

/* ==================================
   🟢 CREAR IMAGEN DESDE URL (FLASK WORKER)
   ================================== */
Flight::route('POST /xoxo/reg_img/crear_desde_url', function () {

    include DEFINITION;
    autentificar_administrador();

    $data = json_decode(Flight::request()->getBody(), true);

    $item_pag_web_id = $data['item_pag_web_id'] ?? null;
    $url_img         = $data['url_img'] ?? null;
    $neg_id          = $administrador_actual['neg_id']; // Seguridad multi-tenant

    if (!$item_pag_web_id || !$url_img) {
        Flight::json(['success' => false, 'error' => 'Faltan datos requeridos'], 400);
        return;
    }

    try {
        // 🔥 PHP inserta formalmente en la base de datos usando MeekroDB
        DB::insert('reg_pag_item_img', [
            'item_pag_web_id' => $item_pag_web_id,
            'url_img'         => $url_img,
            'is_visible'      => 1,
            'orden'           => 0 // O puedes calcular el max orden si lo requieres
        ]);

        Flight::json([
            'success'   => true,
            'img_id'    => DB::insertId(),
            'url_img'   => $url_img
        ]);

    } catch (Exception $e) {
        Flight::json([
            'success' => false,
            'error'   => $e->getMessage()
        ], 500);
    }
});

/* ==========================================
   🟢 RUTAS DE IMÁGENES PARA CATEGORÍAS
   ========================================== */
Flight::route('GET /xoxo/reg_cat_img/listar', function(){
    $id = $_GET['id'];
    $rows = DB::query("SELECT * FROM reg_cat_pag_img WHERE cat_pag_web_id=%i ORDER BY orden ASC", $id);
    Flight::json(['status'=>'ok','data'=>$rows]);
});

Flight::route('POST /xoxo/reg_cat_img/crear_desde_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $data = json_decode(Flight::request()->getBody(), true);
    
    DB::insert('reg_cat_pag_img', [
        'cat_pag_web_id' => $data['cat_pag_web_id'],
        'url_img'        => $data['url_img'],
        'is_visible'     => 1,
        'orden'          => 0
    ]);
    Flight::json(['success' => true]);
});

Flight::route('POST /xoxo/reg_cat_img/crear_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    
    DB::insert('reg_cat_pag_img', [
        'cat_pag_web_id' => $d['cat'],
        'url_img'        => trim($d['url'])
    ]);
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_cat_img/ordenar', function(){
    include DEFINITION;
    autentificar_administrador();
    $data = json_decode(Flight::request()->getBody(), true);
    foreach($data['orden'] as $item){
        DB::update('reg_cat_pag_img', ['orden' => (int)$item['orden']], "cat_pag_img_id=%i", (int)$item['id']);
    }
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_cat_img/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    $id = $d['id'] ?? null;
    
    $row = DB::queryFirstRow("SELECT url_img FROM reg_cat_pag_img WHERE cat_pag_img_id=%i", $id);
    if ($row) bunnyDelete($row['url_img']);
    
    DB::delete('reg_cat_pag_img', "cat_pag_img_id=%i", $id);
    Flight::json(['status'=>'ok']);
});


/* ==========================================
   🟢 RUTAS DE IMÁGENES PARA SUBCATEGORÍAS
   ========================================== */
Flight::route('GET /xoxo/reg_subcat_img/listar', function(){
    $id = $_GET['id'];
    $rows = DB::query("SELECT * FROM reg_subcat_pag_img WHERE subcat_pag_web_id=%i ORDER BY orden ASC", $id);
    Flight::json(['status'=>'ok','data'=>$rows]);
});

Flight::route('POST /xoxo/reg_subcat_img/crear_desde_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $data = json_decode(Flight::request()->getBody(), true);
    
    DB::insert('reg_subcat_pag_img', [
        'subcat_pag_web_id' => $data['subcat_pag_web_id'],
        'url_img'          => $data['url_img'],
        'is_visible'       => 1,
        'orden'            => 0
    ]);
    Flight::json(['success' => true]);
});

Flight::route('POST /xoxo/reg_subcat_img/crear_url', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    
    DB::insert('reg_subcat_pag_img', [
        'subcat_pag_web_id' => $d['subcat'],
        'url_img'           => trim($d['url'])
    ]);
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_subcat_img/ordenar', function(){
    include DEFINITION;
    autentificar_administrador();
    $data = json_decode(Flight::request()->getBody(), true);
    foreach($data['orden'] as $item){
        DB::update('reg_subcat_pag_img', ['orden' => (int)$item['orden']], "subcat_pag_img_id=%i", (int)$item['id']);
    }
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_subcat_img/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();
    $d = Flight::request()->data->getData();
    $id = $d['id'] ?? null;
    
    $row = DB::queryFirstRow("SELECT url_img FROM reg_subcat_pag_img WHERE subcat_pag_img_id=%i", $id);
    if ($row) bunnyDelete($row['url_img']);
    
    DB::delete('reg_subcat_pag_img', "subcat_pag_img_id=%i", $id);
    Flight::json(['status'=>'ok']);
});