<?php

// 20. Ordenar Imágenes (Drag and Drop)
Flight::route('POST /xoxo/reg_img/ordenar', function(){
    include DEFINITION;
    autentificar_administrador();

    $data = json_decode(Flight::request()->getBody(), true);

    if (!isset($data['orden'])) {
        Flight::json(['status'=>'error']);
        return;
    }

    DB::startTransaction();
    try {
        foreach($data['orden'] as $item){
            DB::query(
                "UPDATE reg_pag_item_img 
                 SET orden = %i 
                 WHERE pag_item_img_id = %i",
                (int)$item['orden'],
                (int)$item['id']
            );
        }
        DB::commit();
        Flight::json(['status'=>'ok']);
    } catch(Exception $e) {
        DB::rollback();
        Flight::json(['status'=>'error']);
    }
});

// 21. Eliminar Imagen
Flight::route('POST /xoxo/reg_img/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();

    $d = Flight::request()->data->getData();
    $id = $d['id'] ?? null;

    if (!$id) {
        Flight::json(['status'=>'error']);
        return;
    }

    $row = DB::queryFirstRow(
        "SELECT url_img FROM reg_pag_item_img WHERE pag_item_img_id=%i",
        $id
    );

    if ($row && !empty($row['url_img'])) {
        bunnyDelete($row['url_img']);
    }

    DB::delete('reg_pag_item_img', "pag_item_img_id=%i", $id);
    Flight::json(['status'=>'ok']);
});

// 22. Listar Videos
Flight::route('GET /xoxo/reg_vid/listar', function(){
    $id = $_GET['id'];
    $rows = DB::query("
        SELECT *
        FROM reg_pag_item_vid
        WHERE item_pag_web_id=%i
    ", $id);
    Flight::json(['status'=>'ok','data'=>$rows]);
});

// 23. Crear Video (Vimeo OEmbed)
Flight::route('POST /xoxo/reg_vid/crear', function(){
    $d = Flight::request()->data->getData();
    $url = trim($d['codigo']);

    preg_match('/vimeo\.com\/(\d+)/', $url, $m);
    $video_id = $m[1] ?? null;

    if(!$video_id){
        Flight::json(['status'=>'error','msg'=>'URL Vimeo inválida']);
        return;
    }

    $embed = "https://player.vimeo.com/video/".$video_id;
    $thumb = null;

    try{
        $json = file_get_contents("https://vimeo.com/api/oembed.json?url=".$url);
        $data = json_decode($json, true);
        $thumb = $data['thumbnail_url'] ?? null;
    }catch(Exception $e){}

    DB::insert('reg_pag_item_vid',[
        'item_pag_web_id' => $d['item'],
        'titulo'          => $d['titulo'],
        'codigo_web'      => $embed,
        'url_img'         => $thumb
    ]);

    Flight::json(['status'=>'ok']);
});

// 24. Eliminar / Editar Video
Flight::route('POST /xoxo/reg_vid/eliminar', function(){
    $d = Flight::request()->data->getData();
    DB::delete('reg_pag_item_vid', "pag_item_vid_id=%i", $d['id']);
    Flight::json(['status'=>'ok']);
});

Flight::route('POST /xoxo/reg_vid/editar', function(){
    $d = Flight::request()->data->getData();
    DB::update('reg_pag_item_vid',[
        'titulo' => $d['titulo']
    ],"pag_item_vid_id=%i", $d['id']);
    Flight::json(['status'=>'ok']);
});