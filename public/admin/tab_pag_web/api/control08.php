<?php

function regentis_rss($neg_id_param) {
    try {
        $neg_id = (int)$neg_id_param;

        if ($neg_id <= 0) {
            return ['status' => 'error', 'msg' => 'neg_id inválido'];
        }

        // Consultar información básica del negocio
        $negocio = DB::queryFirstRow("SELECT neg_id, nombre, slug, img_logo, celular_informes FROM reg_neg WHERE neg_id = %i", $neg_id);

        // ---------------- WEB ----------------[cite: 13, 16]
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

        // ---------------- POS ----------------[cite: 13, 16]
        $categoriasPos = DB::query("SELECT * FROM pos_category WHERE neg_id = %i AND borrado_el IS NULL ORDER BY category_id DESC", $neg_id);
        $arbolPos = [];

        foreach ($categoriasPos as $pcat) {
            $pcat = arbol_cast($pcat, ['category_id', 'priority', 'neg_id', 'categoria_global_id', 'is_activo']);

            $subsPos = DB::query("SELECT * FROM pos_subcategoria WHERE category_id = %i AND borrado_el IS NULL ORDER BY orden ASC, subcategoria_id ASC", $pcat['category_id']);
            $subsPosList = [];
            foreach ($subsPos as $sp) {
                $subsPosList[] = arbol_cast($sp, ['subcategoria_id', 'category_id', 'is_activo', 'orden']);
            }

            $relaciones = DB::query("SELECT * FROM pos_product_category WHERE category_id = %i AND is_visible = 1", $pcat['category_id']);
            $productosList = [];

            foreach ($relaciones as $rel) {
                $prod = DB::queryFirstRow("SELECT * FROM pos_product WHERE product_id = %i AND neg_id = %i AND borrado_el IS NULL", $rel['product_id'], $neg_id);
                if (!$prod) {
                    continue;
                }

                $prod = arbol_cast($prod, ['product_id', 'neg_id', 'subcategoria_id', 'is_visible', 'cantidad_comprada'], ['price', 'compra_total']);

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

        // ---------------- SLIDERS ----------------[cite: 13, 16]
        $sliders = DB::query("SELECT * FROM reg_slider WHERE neg_id = %i ORDER BY slider_id DESC", $neg_id);
        foreach ($sliders as $k => $sl) {
            $sliders[$k] = arbol_cast($sl, ['slider_id', 'orden', 'is_visible', 'neg_id']);
        }

        return [
            'status' => 'ok',
            'negocio' => $negocio,
            'web' => $arbolWeb,
            'pos' => $arbolPos,
            'sliders' => $sliders
        ];

    } catch (Exception $e) {
        return ['status' => 'error', 'msg' => $e->getMessage()];
    }
}