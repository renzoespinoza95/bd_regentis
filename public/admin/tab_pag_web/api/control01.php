<?php

function generar_url_amigable($texto){
    $texto = strtolower($texto);
    $texto = strtr($texto, [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u',
        'ñ'=>'n'
    ]);
    $texto = preg_replace('/[^a-z0-9\s-]/', '', $texto);
    $texto = preg_replace('/[\s-]+/', '-', $texto);
    $texto = trim($texto, '-');
    return $texto;
}

// 1. Ruta de la vista principal
Flight::route('GET /pagweb/inicio', function () {
    include DEFINITION;
    autentificar_administrador();

    ob_start();
    perso::vuejs2();
    perso::vue_select();
    perso::start($version);
    perso::favicon();
    perso::font_awesome();
    perso::jquery2();
    perso::jquery_ui();
    perso::js();
    perso::apprise();
    perso::block_ui();
    perso::perso($version);
    perso::global_env($apphost, $varhost);
    perso::datatables();
    perso::summernote();
    $assets_head = ob_get_clean();

    ob_start();
    include VARPATH . "/public/admin/menu.php";
    $menu_html = ob_get_clean();

    ob_start();
    include VARPATH . "/public/admin/footer.php";
    $footer_html = ob_get_clean();

    $data = [
        'title'       => 'Administración Web - Items',
        'varhost'     => $varhost,
        'apphost'     => $apphost,
        'assets_head' => $assets_head,
        'menu_html'   => $menu_html,
        'footer_html' => $footer_html
    ];

    $basePath = VARPATH . '/public/admin/tab_pag_web/comp_pag_web';

    $partials = [
        'head'                => file_get_contents($basePath . '/head.html'),
        'navbar_items'        => file_get_contents($basePath . '/navbar_items.html'),
        'modal_item'          => file_get_contents($basePath . '/modal_item.html'),
        'modal_categorias'    => file_get_contents($basePath . '/modal_categorias.html'),
        'modal_subcategorias' => file_get_contents($basePath . '/modal_subcategorias.html'),
        'modal_imagenes'      => file_get_contents($basePath . '/modal_imagenes.html'),
        'modal_videos'        => file_get_contents($basePath . '/modal_videos.html'),
        'scripts'             => file_get_contents($basePath . '/scripts.html'),
    ];

    echo (new Mustache)->render(
        file_get_contents(VARPATH . '/public/admin/tab_pag_web/pag_web.html'),
        $data,
        $partials
    );
});

// 2. Listar Ítems
Flight::route('GET /xoxo/reg_item/listar', function(){
    try{
        include DEFINITION;
        autentificar_administrador();

        $rows = DB::query("
          SELECT 
            i.*,
            s.titulo AS subcat_titulo,
            c.titulo AS cat_titulo
          FROM reg_item_pag_web i
          INNER JOIN reg_subcat_pag_web s 
            ON s.subcat_pag_web_id = i.subcat_pag_web_id
          INNER JOIN reg_cat_pag_web c 
            ON c.cat_pag_web_id = s.cat_pag_web_id
          ORDER BY i.item_pag_web_id DESC
        ");

        Flight::json(['status'=>'ok','data'=>$rows]);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});

// 3. Crear Ítem
Flight::route('POST /xoxo/reg_item/crear', function(){
    try{
        include DEFINITION;
        autentificar_administrador();

        $data = Flight::request()->data->getData();
        $url_amigable = generar_url_amigable($data['titulo']);

        DB::insert('reg_item_pag_web',[
            'titulo'            => $data['titulo'],
            'url_amigable'      => $url_amigable,
            'contenido'         => $data['contenido'],
            'subtitulo_detalle' => $data['subtitulo_detalle'] ?? '',
            'html01'            => $data['html01'] ?? '',
            'html02'            => $data['html02'] ?? '',
            'is_visible'        => isset($data['is_visible']) ? (int)$data['is_visible'] : 1,
            'precio'            => $data['precio'],
            'clave_txt'         => $data['clave_txt'],
            'stock'             => $data['stock'],
            'subcat_pag_web_id' => $data['subcat_id']
        ]);

        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// 4. Editar Ítem
Flight::route('POST /xoxo/reg_item/editar', function(){
    try{
        include DEFINITION;
        autentificar_administrador();

        $d = Flight::request()->data->getData();

        if(empty($d['item_pag_web_id'])){
            Flight::json(['status'=>'error','msg'=>'item_pag_web_id requerido']);
            return;
        }

        $url_amigable = generar_url_amigable($d['titulo']);

        DB::update('reg_item_pag_web',[
            'titulo'            => $d['titulo'],
            'url_amigable'      => $url_amigable,
            'contenido'         => $d['contenido'],
            'subtitulo_detalle' => $d['subtitulo_detalle'] ?? '',
            'html01'            => $d['html01'] ?? '',
            'html02'            => $d['html02'] ?? '',
            'is_visible'        => isset($d['is_visible']) ? (int)$d['is_visible'] : 1,
            'precio'            => $d['precio'],
            'clave_txt'         => $d['clave_txt'],
            'stock'             => $d['stock'],
            'subcat_pag_web_id' => $d['subcat_id']
        ],"item_pag_web_id=%i", $d['item_pag_web_id']);

        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()]);
    }
});

// NUEVO: Actualización rápida de clave_txt de Ítem
Flight::route('POST /xoxo/reg_item/actualizarClave', function(){
    try{
        include DEFINITION;
        autentificar_administrador();

        $d = json_decode(Flight::request()->getBody(), true) ?: Flight::request()->data->getData();
        $item_id   = $d['item_pag_web_id'] ?? null;
        $clave_txt = $d['clave_txt'] ?? '';

        if(!$item_id){
            Flight::json(['status'=>'error','msg'=>'ID requerido']);
            return;
        }

        DB::update('reg_item_pag_web', [
            'clave_txt' => $clave_txt
        ], "item_pag_web_id=%i", $item_id);

        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()]);
    }
});