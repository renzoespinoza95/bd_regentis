<?php
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

// 5. Endpoint para obtener el árbol de contenido (Web, POS y Sliders)
//    Devuelve el mismo formato de json_plantilla (web / pos / sliders),
//    con los pos_subcategoria dentro de cada categoría POS.
Flight::route('GET /xoxo/arbol_contenido/obtener', function(){
    try {
        include DEFINITION;
        autentificar_administrador();
        global $administrador_actual;
        $neg_id = (int)($administrador_actual['neg_id'] ?? 0);

        // Consultar información básica del negocio
        $negocio = DB::queryFirstRow("SELECT neg_id, nombre, slug, img_logo, celular_informes FROM reg_neg WHERE neg_id = %i", $neg_id);

        // ---------------- WEB ----------------
        $categoriasWeb = DB::query("SELECT * FROM reg_cat_pag_web WHERE neg_id = %i AND (is_visible = 1 OR is_visible IS NULL) ORDER BY cat_pag_web_id DESC", $neg_id);
        $arbolWeb = [];

        foreach ($categoriasWeb as $cat) {
            $cat = arbol_cast($cat, ['cat_pag_web_id', 'is_visible', 'orden', 'neg_id']);

            $subcats = DB::query("SELECT * FROM reg_subcat_pag_web WHERE cat_pag_web_id = %i AND (is_visible = 1 OR is_visible IS NULL) ORDER BY orden ASC, subcat_pag_web_id ASC", $cat['cat_pag_web_id']);
            $subcatsList = [];

            foreach ($subcats as $sub) {
                $sub = arbol_cast($sub, ['subcat_pag_web_id', 'cat_pag_web_id', 'is_visible', 'orden']);

                $items = DB::query("SELECT * FROM reg_item_pag_web WHERE subcat_pag_web_id = %i AND (is_visible = 1 OR is_visible IS NULL) ORDER BY orden ASC, item_pag_web_id ASC", $sub['subcat_pag_web_id']);
                $itemsList = [];

                foreach ($items as $item) {
                    $item = arbol_cast($item, ['item_pag_web_id', 'subcat_pag_web_id', 'stock', 'orden', 'is_visible'], ['precio']);

                    $imagenes = DB::query("SELECT * FROM reg_pag_item_img WHERE item_pag_web_id = %i ORDER BY orden ASC, pag_item_img_id ASC", $item['item_pag_web_id']);
                    foreach ($imagenes as $k => $im) {
                        $imagenes[$k] = arbol_cast($im, ['pag_item_img_id', 'item_pag_web_id', 'is_visible', 'orden']);
                    }

                    $videos = DB::query("SELECT * FROM reg_pag_item_vid WHERE item_pag_web_id = %i ORDER BY orden ASC, pag_item_vid_id ASC", $item['item_pag_web_id']);
                    foreach ($videos as $k => $vi) {
                        $videos[$k] = arbol_cast($vi, ['pag_item_vid_id', 'item_pag_web_id', 'is_visible', 'orden']);
                    }

                    $item['imagenes'] = $imagenes;
                    $item['videos'] = $videos;
                    $itemsList[] = $item;
                }

                $sub['items'] = $itemsList;
                $subcatsList[] = $sub;
            }

            $cat['subcategorias'] = $subcatsList;
            $arbolWeb[] = $cat;
        }

        // ---------------- POS ----------------
        $categoriasPos = DB::query("SELECT * FROM pos_category WHERE neg_id = %i AND borrado_el IS NULL ORDER BY category_id DESC", $neg_id);
        $arbolPos = [];

        foreach ($categoriasPos as $pcat) {
            $pcat = arbol_cast($pcat, ['category_id', 'priority', 'neg_id', 'categoria_global_id', 'is_activo']);

            // Subcategorías POS de la categoría
            $subsPos = DB::query("SELECT * FROM pos_subcategoria WHERE category_id = %i AND borrado_el IS NULL ORDER BY orden ASC, subcategoria_id ASC", $pcat['category_id']);
            $subsPosList = [];
            foreach ($subsPos as $sp) {
                $subsPosList[] = arbol_cast($sp, ['subcategoria_id', 'category_id', 'is_activo', 'orden']);
            }

            // Productos de la categoría (a través de la tabla puente)
            $relaciones = DB::query("SELECT * FROM pos_product_category WHERE category_id = %i AND is_visible = 1", $pcat['category_id']);
            $productosList = [];

            foreach ($relaciones as $rel) {
                $prod = DB::queryFirstRow("SELECT * FROM pos_product WHERE product_id = %i AND neg_id = %i AND borrado_el IS NULL", $rel['product_id'], $neg_id);
                if (!$prod) {
                    continue;
                }

                $prod = arbol_cast($prod, ['product_id', 'neg_id', 'subcategoria_id', 'is_visible', 'cantidad_comprada'], ['price', 'compra_total']);

                // variantes_json se guarda como texto: se devuelve como objeto JSON real
                if (isset($prod['variantes_json']) && $prod['variantes_json'] !== '') {
                    $dec = json_decode($prod['variantes_json'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $prod['variantes_json'] = $dec;
                    }
                }

                $pimagenes = DB::query("SELECT * FROM pos_product_image WHERE product_id = %i AND borrado_el IS NULL ORDER BY orden ASC, product_image_id ASC", $prod['product_id']);
                foreach ($pimagenes as $k => $pim) {
                    $pimagenes[$k] = arbol_cast($pim, ['product_image_id', 'product_id', 'orden', 'is_visible']);
                }

                $prod['imagenes'] = $pimagenes;
                $productosList[] = $prod;
            }

            $pcat['subcategorias'] = $subsPosList;
            $pcat['productos'] = $productosList;
            $arbolPos[] = $pcat;
        }

        // ---------------- SLIDERS ----------------
        $sliders = DB::query("SELECT * FROM reg_slider WHERE neg_id = %i ORDER BY slider_id DESC", $neg_id);
        foreach ($sliders as $k => $sl) {
            $sliders[$k] = arbol_cast($sl, ['slider_id', 'orden', 'is_visible', 'neg_id']);
        }

        Flight::json([
            'status' => 'ok',
            'negocio' => $negocio,
            'web' => $arbolWeb,
            'pos' => $arbolPos,
            'sliders' => $sliders
        ]);

    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
});

