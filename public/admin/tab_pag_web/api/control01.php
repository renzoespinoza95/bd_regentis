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

// ---------------------------------------------------------------------------
// Utilidades para exportar / importar el árbol de contenido (endpoints 5 y 6)
// ---------------------------------------------------------------------------

// Convierte a int / float las columnas indicadas (MySQL suele devolver todo como string)
function arbol_cast($row, $ints = array(), $floats = array()){
    foreach ($ints as $c) {
        if (array_key_exists($c, $row) && $row[$c] !== null && $row[$c] !== '') {
            $row[$c] = (int)$row[$c];
        }
    }
    foreach ($floats as $c) {
        if (array_key_exists($c, $row) && $row[$c] !== null && $row[$c] !== '') {
            $row[$c] = (float)$row[$c];
        }
    }
    return $row;
}

// Devuelve solo las columnas pedidas que existan en el JSON y no sean null
// (así un null en el JSON nunca borra un dato ya guardado en la BD)
function imp_campos($j, $cols){
    $out = array();
    foreach ($cols as $c) {
        if (array_key_exists($c, $j) && $j[$c] !== null) {
            $out[$c] = $j[$c];
        }
    }
    return $out;
}

function imp_vacio($v){
    return $v === null || $v === '';
}

// 🌟 NUEVO ENDPOINT PÚBLICO VIA AJAX: NjjztP/regentisRss/{neg_id}
Flight::route('GET /NjjztP/regentisRss/@neg_id', function ($neg_id_param) {
    try {
        include DEFINITION;
        $neg_id = (int)$neg_id_param;

        if ($neg_id <= 0) {
            Flight::json(['status' => 'error', 'msg' => 'neg_id inválido'], 400);
            return;
        }

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
    include VARPATH . "/public/admin/footer.php";
    $footer_html = ob_get_clean();

    $data = [
        'title'       => 'Administración Web - Items',
        'varhost'     => $varhost,
        'apphost'     => $apphost,
        'assets_head' => $assets_head,
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
        'modal_arbol_contenido' => file_get_contents($basePath . '/modal_arbol_contenido.html'),
        'scripts'             => file_get_contents($basePath . '/scripts.html'),
        'menu_flot'           => file_get_contents(VARPATH . '/public/admin/menu_flot.html'),
    ];

    echo (new Mustache)->render(
        file_get_contents(VARPATH . '/public/admin/tab_pag_web/pag_web.html'),
        $data,
        $partials
    );
});

// 2. Listar Ítems (incluye las imágenes de cada ítem para mostrar miniaturas)
Flight::route('GET /xoxo/reg_item/listar', function(){
    try{
        include DEFINITION;
        autentificar_administrador();

        // Ya no se filtra por visibilidad de subcategoría / categoría:
        // las ocultas se siguen mostrando (en gris) en el panel.
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
          WHERE (i.is_visible = 1 OR i.is_visible IS NULL)
          ORDER BY i.item_pag_web_id DESC
        ");

        // Imágenes de todos los ítems en una sola consulta
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int)$r['item_pag_web_id'];
        }

        $imgsPorItem = [];
        if (count($ids) > 0) {
            $imgs = DB::query("
              SELECT pag_item_img_id, item_pag_web_id, url_img
              FROM reg_pag_item_img
              WHERE item_pag_web_id IN %li
              ORDER BY orden ASC, pag_item_img_id ASC
            ", $ids);

            foreach ($imgs as $im) {
                $imgsPorItem[(int)$im['item_pag_web_id']][] = [
                    'pag_item_img_id' => (int)$im['pag_item_img_id'],
                    'url_img'         => $im['url_img']
                ];
            }
        }

        foreach ($rows as $k => $r) {
            $rows[$k]['imagenes'] = $imgsPorItem[(int)$r['item_pag_web_id']] ?? [];
        }

        Flight::json(['status'=>'ok','data'=>$rows]);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});