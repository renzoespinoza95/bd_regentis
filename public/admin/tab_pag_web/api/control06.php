<?php
// 🌟 6. ENDPOINT PARA IMPORTAR Y SINCRONIZAR EL ÁRBOL DESDE JSON
//    Acepta el formato de json_plantilla (web / pos / sliders).
//    Es una sincronización aditiva: crea lo que no existe y actualiza lo que ya existe
//    (los valores null del JSON no pisan datos guardados). Todo ocurre en una transacción.
Flight::route('POST /xoxo/arbol_contenido/importar', function(){
    $transaccion = false;
    try {
        include DEFINITION;
        autentificar_administrador();
        global $administrador_actual;
        $neg_id = (int)($administrador_actual['neg_id'] ?? 0);

        if ($neg_id <= 0) {
            Flight::json(['status' => 'error', 'msg' => 'No se pudo determinar el negocio (neg_id)']);
            return;
        }

        $data = Flight::request()->data->getData();
        $contenido = $data['contenido'] ?? null;

        // El JSON puede llegar como texto
        if (is_string($contenido)) {
            $dec = json_decode($contenido, true);
            if (is_array($dec)) {
                $contenido = $dec;
            }
        }
        // O directamente con web / pos / sliders en la raíz
        if (!$contenido && (isset($data['web']) || isset($data['pos']) || isset($data['sliders']))) {
            $contenido = $data;
        }

        if (!$contenido || !is_array($contenido)) {
            Flight::json(['status' => 'error', 'msg' => 'JSON inválido o vacío']);
            return;
        }

        // Contadores del resumen
        $n = [];
        $cnt = function($clave) use (&$n) {
            $n[$clave] = ($n[$clave] ?? 0) + 1;
        };

        $tiposProducto = ['ABARROTES', 'CUARTO_HOTEL', 'PLATO', 'ESTACIONAMIENTO', 'ELECTRODOMESTICO'];

        DB::startTransaction();
        $transaccion = true;

        // =====================================================================
        // MÓDULO WEB
        // =====================================================================
        if (isset($contenido['web']) && is_array($contenido['web'])) {
            foreach ($contenido['web'] as $catJson) {
                $tituloCat = $catJson['titulo'] ?? '';
                $claveCat = $catJson['clave_txt'] ?? '';

                $catDb = null;
                if (!empty($claveCat)) {
                    $catDb = DB::queryFirstRow("SELECT * FROM reg_cat_pag_web WHERE neg_id = %i AND clave_txt = %s", $neg_id, $claveCat);
                }
                if (!$catDb && !empty($tituloCat)) {
                    $catDb = DB::queryFirstRow("SELECT * FROM reg_cat_pag_web WHERE neg_id = %i AND titulo = %s", $neg_id, $tituloCat);
                }

                $camposCat = imp_campos($catJson, ['titulo', 'metatag01', 'metatag02', 'url_img', 'is_visible', 'orden', 'texto01', 'texto02']);

                if ($catDb) {
                    if ($camposCat) {
                        DB::update('reg_cat_pag_web', $camposCat, "cat_pag_web_id = %i", $catDb['cat_pag_web_id']);
                    }
                    $catId = $catDb['cat_pag_web_id'];
                    $cnt('web_categorias_actualizadas');
                } else {
                    $camposCat['titulo'] = $tituloCat;
                    $camposCat['clave_txt'] = $claveCat;
                    $camposCat['neg_id'] = $neg_id;
                    if (!isset($camposCat['is_visible'])) {
                        $camposCat['is_visible'] = 1;
                    }
                    DB::insert('reg_cat_pag_web', $camposCat);
                    $catId = DB::insertId();
                    $cnt('web_categorias_nuevas');
                }

                // ---------- Subcategorías Web ----------
                if (isset($catJson['subcategorias']) && is_array($catJson['subcategorias'])) {
                    foreach ($catJson['subcategorias'] as $subJson) {
                        $tituloSub = $subJson['titulo'] ?? '';
                        $claveSub = $subJson['clave_txt'] ?? '';

                        $subDb = null;
                        if (!empty($claveSub)) {
                            $subDb = DB::queryFirstRow("SELECT * FROM reg_subcat_pag_web WHERE cat_pag_web_id = %i AND clave_txt = %s", $catId, $claveSub);
                        }
                        if (!$subDb && !empty($tituloSub)) {
                            $subDb = DB::queryFirstRow("SELECT * FROM reg_subcat_pag_web WHERE cat_pag_web_id = %i AND titulo = %s", $catId, $tituloSub);
                        }

                        $camposSub = imp_campos($subJson, ['titulo', 'descripcion', 'url_img', 'is_visible', 'orden', 'texto01', 'texto02']);

                        if ($subDb) {
                            if ($camposSub) {
                                DB::update('reg_subcat_pag_web', $camposSub, "subcat_pag_web_id = %i", $subDb['subcat_pag_web_id']);
                            }
                            $subId = $subDb['subcat_pag_web_id'];
                            $cnt('web_subcategorias_actualizadas');
                        } else {
                            $camposSub['titulo'] = $tituloSub;
                            $camposSub['clave_txt'] = $claveSub;
                            $camposSub['cat_pag_web_id'] = $catId;
                            if (!isset($camposSub['is_visible'])) {
                                $camposSub['is_visible'] = 1;
                            }
                            DB::insert('reg_subcat_pag_web', $camposSub);
                            $subId = DB::insertId();
                            $cnt('web_subcategorias_nuevas');
                        }

                        // ---------- Ítems Web ----------
                        if (isset($subJson['items']) && is_array($subJson['items'])) {
                            foreach ($subJson['items'] as $itemJson) {
                                $tituloItem = $itemJson['titulo'] ?? '';
                                $claveItem = $itemJson['clave_txt'] ?? '';

                                $itemDb = null;
                                if (!empty($claveItem)) {
                                    $itemDb = DB::queryFirstRow("SELECT * FROM reg_item_pag_web WHERE subcat_pag_web_id = %i AND clave_txt = %s", $subId, $claveItem);
                                }
                                if (!$itemDb && empty($claveItem) && !empty($tituloItem)) {
                                    $itemDb = DB::queryFirstRow("SELECT * FROM reg_item_pag_web WHERE subcat_pag_web_id = %i AND titulo = %s", $subId, $tituloItem);
                                }

                                $camposItem = imp_campos($itemJson, ['titulo', 'contenido', 'precio', 'stock', 'is_visible', 'orden', 'subtitulo_detalle', 'html01', 'html02', 'url_amigable']);

                                if ($itemDb) {
                                    // url_amigable vacío en el JSON: no se toca el existente
                                    if (imp_vacio($camposItem['url_amigable'] ?? null)) {
                                        unset($camposItem['url_amigable']);
                                    }
                                    if ($camposItem) {
                                        DB::update('reg_item_pag_web', $camposItem, "item_pag_web_id = %i", $itemDb['item_pag_web_id']);
                                    }
                                    $itemId = $itemDb['item_pag_web_id'];
                                    $cnt('web_items_actualizados');
                                } else {
                                    $camposItem['titulo'] = $tituloItem;
                                    $camposItem['clave_txt'] = $claveItem;
                                    $camposItem['subcat_pag_web_id'] = $subId;
                                    if (imp_vacio($camposItem['url_amigable'] ?? null)) {
                                        $camposItem['url_amigable'] = generar_url_amigable($tituloItem);
                                    }
                                    if (!isset($camposItem['contenido'])) {
                                        $camposItem['contenido'] = '';
                                    }
                                    if (!isset($camposItem['precio'])) {
                                        $camposItem['precio'] = 0;
                                    }
                                    if (!isset($camposItem['stock'])) {
                                        $camposItem['stock'] = 0;
                                    }
                                    if (!isset($camposItem['is_visible'])) {
                                        $camposItem['is_visible'] = 1;
                                    }
                                    DB::insert('reg_item_pag_web', $camposItem);
                                    $itemId = DB::insertId();
                                    $cnt('web_items_nuevos');
                                }

                                // ---------- Imágenes del ítem ----------
                                if (isset($itemJson['imagenes']) && is_array($itemJson['imagenes'])) {
                                    foreach ($itemJson['imagenes'] as $imgJson) {
                                        $urlImg = $imgJson['url_img'] ?? '';
                                        if (empty($urlImg)) continue;

                                        $imgDb = DB::queryFirstRow("SELECT * FROM reg_pag_item_img WHERE item_pag_web_id = %i AND url_img = %s", $itemId, $urlImg);
                                        if ($imgDb) {
                                            DB::update('reg_pag_item_img', [
                                                'is_visible' => $imgJson['is_visible'] ?? 1,
                                                'orden' => $imgJson['orden'] ?? 0
                                            ], "pag_item_img_id = %i", $imgDb['pag_item_img_id']);
                                        } else {
                                            DB::insert('reg_pag_item_img', [
                                                'item_pag_web_id' => $itemId,
                                                'url_img' => $urlImg,
                                                'is_visible' => $imgJson['is_visible'] ?? 1,
                                                'orden' => $imgJson['orden'] ?? 0
                                            ]);
                                            $cnt('web_imagenes_nuevas');
                                        }
                                    }
                                }

                                // ---------- Vídeos del ítem ----------
                                if (isset($itemJson['videos']) && is_array($itemJson['videos'])) {
                                    foreach ($itemJson['videos'] as $vidJson) {
                                        $codigoWeb = $vidJson['codigo_web'] ?? '';
                                        if (empty($codigoWeb)) continue;

                                        $vidDb = DB::queryFirstRow("SELECT * FROM reg_pag_item_vid WHERE item_pag_web_id = %i AND codigo_web = %s", $itemId, $codigoWeb);
                                        if ($vidDb) {
                                            DB::update('reg_pag_item_vid', [
                                                'titulo' => $vidJson['titulo'] ?? '',
                                                'is_visible' => $vidJson['is_visible'] ?? 1,
                                                'orden' => $vidJson['orden'] ?? 0,
                                                'url_img' => $vidJson['url_img'] ?? null
                                            ], "pag_item_vid_id = %i", $vidDb['pag_item_vid_id']);
                                        } else {
                                            DB::insert('reg_pag_item_vid', [
                                                'item_pag_web_id' => $itemId,
                                                'titulo' => $vidJson['titulo'] ?? '',
                                                'codigo_web' => $codigoWeb,
                                                'is_visible' => $vidJson['is_visible'] ?? 1,
                                                'orden' => $vidJson['orden'] ?? 0,
                                                'url_img' => $vidJson['url_img'] ?? null
                                            ]);
                                            $cnt('web_videos_nuevos');
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // =====================================================================
        // MÓDULO POS
        // =====================================================================
        // Los subcategoria_id del JSON no coinciden con los de la BD: se guarda la equivalencia
        $mapaSubcat = [];

        if (isset($contenido['pos']) && is_array($contenido['pos'])) {
            foreach ($contenido['pos'] as $pcatJson) {
                $nombreCat = $pcatJson['name'] ?? '';
                $claveCat = $pcatJson['clave_txt'] ?? '';

                $pcatDb = null;
                if (!empty($claveCat)) {
                    $pcatDb = DB::queryFirstRow("SELECT * FROM pos_category WHERE neg_id = %i AND clave_txt = %s", $neg_id, $claveCat);
                }
                if (!$pcatDb && !empty($nombreCat)) {
                    $pcatDb = DB::queryFirstRow("SELECT * FROM pos_category WHERE neg_id = %i AND name = %s", $neg_id, $nombreCat);
                }

                $camposPcat = imp_campos($pcatJson, ['name', 'icon', 'brief', 'color', 'priority', 'img', 'is_activo']);

                if ($pcatDb) {
                    $camposPcat['borrado_el'] = null; // si estaba borrada, se reactiva
                    DB::update('pos_category', $camposPcat, "category_id = %i", $pcatDb['category_id']);
                    $pcatId = $pcatDb['category_id'];
                    $cnt('pos_categorias_actualizadas');
                } else {
                    $camposPcat['name'] = $nombreCat;
                    $camposPcat['clave_txt'] = $claveCat;
                    $camposPcat['neg_id'] = $neg_id;
                    if (!isset($camposPcat['is_activo'])) {
                        $camposPcat['is_activo'] = 1;
                    }
                    DB::insert('pos_category', $camposPcat);
                    $pcatId = DB::insertId();
                    $cnt('pos_categorias_nuevas');
                }

                // ---------- Subcategorías POS (pos_subcategoria) ----------
                if (isset($pcatJson['subcategorias']) && is_array($pcatJson['subcategorias'])) {
                    foreach ($pcatJson['subcategorias'] as $spJson) {
                        $nombreSub = $spJson['nombre'] ?? '';
                        if (empty($nombreSub)) continue;

                        $spDb = DB::queryFirstRow("SELECT * FROM pos_subcategoria WHERE category_id = %i AND nombre = %s", $pcatId, $nombreSub);
                        $camposSp = imp_campos($spJson, ['is_activo', 'orden']);

                        if ($spDb) {
                            $camposSp['borrado_el'] = null;
                            DB::update('pos_subcategoria', $camposSp, "subcategoria_id = %i", $spDb['subcategoria_id']);
                            $spId = $spDb['subcategoria_id'];
                            $cnt('pos_subcategorias_actualizadas');
                        } else {
                            $camposSp['category_id'] = $pcatId;
                            $camposSp['nombre'] = $nombreSub;
                            if (!isset($camposSp['is_activo'])) {
                                $camposSp['is_activo'] = 1;
                            }
                            DB::insert('pos_subcategoria', $camposSp);
                            $spId = DB::insertId();
                            $cnt('pos_subcategorias_nuevas');
                        }

                        if (isset($spJson['subcategoria_id'])) {
                            $mapaSubcat[$spJson['subcategoria_id']] = $spId;
                        }
                    }
                }

                // ---------- Productos POS ----------
                if (isset($pcatJson['productos']) && is_array($pcatJson['productos'])) {
                    foreach ($pcatJson['productos'] as $prodJson) {
                        $nombreProd = $prodJson['name'] ?? '';
                        $codProd = $prodJson['cod_producto'] ?? '';

                        $prodDb = null;
                        if (!empty($codProd)) {
                            $prodDb = DB::queryFirstRow("SELECT * FROM pos_product WHERE neg_id = %i AND cod_producto = %s", $neg_id, $codProd);
                        }
                        if (!$prodDb && empty($codProd) && !empty($nombreProd)) {
                            $prodDb = DB::queryFirstRow("SELECT * FROM pos_product WHERE neg_id = %i AND name = %s", $neg_id, $nombreProd);
                        }

                        $camposProd = imp_campos($prodJson, ['name', 'price', 'marca_des', 'description', 'is_visible']);

                        // tipo_producto solo si es un valor válido del ENUM
                        if (isset($prodJson['tipo_producto']) && in_array($prodJson['tipo_producto'], $tiposProducto, true)) {
                            $camposProd['tipo_producto'] = $prodJson['tipo_producto'];
                        }

                        // variantes_json: objeto/array -> texto JSON (la columna exige JSON válido)
                        if (array_key_exists('variantes_json', $prodJson) && $prodJson['variantes_json'] !== null) {
                            $vj = $prodJson['variantes_json'];
                            if (is_array($vj)) {
                                $camposProd['variantes_json'] = json_encode($vj, JSON_UNESCAPED_UNICODE);
                            } else if (is_string($vj) && $vj !== '' && json_decode($vj) !== null) {
                                $camposProd['variantes_json'] = $vj;
                            }
                        }

                        // subcategoria_id del JSON -> id real en pos_subcategoria
                        if (array_key_exists('subcategoria_id', $prodJson)) {
                            $sj = $prodJson['subcategoria_id'];
                            $camposProd['subcategoria_id'] = ($sj !== null && isset($mapaSubcat[$sj])) ? $mapaSubcat[$sj] : null;
                        }

                        $camposProd['fecha_modificacion'] = date('Y-m-d H:i:s');

                        if ($prodDb) {
                            $camposProd['borrado_el'] = null; // si estaba borrado, se reactiva
                            DB::update('pos_product', $camposProd, "product_id = %i", $prodDb['product_id']);
                            $prodId = $prodDb['product_id'];
                            $codFinal = $prodDb['cod_producto'];
                            $cnt('pos_productos_actualizados');
                        } else {
                            $codFinal = !empty($codProd) ? $codProd : 'AUTO_' . uniqid();
                            $camposProd['name'] = $nombreProd;
                            $camposProd['cod_producto'] = $codFinal;
                            $camposProd['neg_id'] = $neg_id;
                            $camposProd['fecha_creacion'] = date('Y-m-d H:i:s');
                            if (!isset($camposProd['price'])) {
                                $camposProd['price'] = 0;
                            }
                            if (!isset($camposProd['marca_des'])) {
                                $camposProd['marca_des'] = 'GENERICO';
                            }
                            if (!isset($camposProd['description'])) {
                                $camposProd['description'] = '';
                            }
                            if (!isset($camposProd['is_visible'])) {
                                $camposProd['is_visible'] = 1;
                            }
                            DB::insert('pos_product', $camposProd);
                            $prodId = DB::insertId();
                            $cnt('pos_productos_nuevos');
                        }

                        // ---------- Relación en pos_product_category ----------
                        $relDb = DB::queryFirstRow("SELECT * FROM pos_product_category WHERE category_id = %i AND product_id = %i", $pcatId, $prodId);
                        if (!$relDb) {
                            DB::insert('pos_product_category', [
                                'category_id' => $pcatId,
                                'product_id' => $prodId,
                                'is_visible' => 1,
                                'neg_id' => $neg_id
                            ]);
                        } else {
                            DB::update('pos_product_category', [
                                'is_visible' => 1,
                                'neg_id' => $neg_id
                            ], "category_id = %i AND product_id = %i", $pcatId, $prodId);
                        }

                        // ---------- Imágenes del producto (pos_product_image) ----------
                        if (isset($prodJson['imagenes']) && is_array($prodJson['imagenes'])) {
                            foreach ($prodJson['imagenes'] as $pimJson) {
                                $urlPim = $pimJson['img'] ?? '';
                                if (empty($urlPim)) continue;

                                $pimDb = DB::queryFirstRow("SELECT * FROM pos_product_image WHERE product_id = %i AND img = %s", $prodId, $urlPim);
                                if ($pimDb) {
                                    DB::update('pos_product_image', [
                                        'orden' => $pimJson['orden'] ?? 0,
                                        'is_visible' => $pimJson['is_visible'] ?? 1,
                                        'borrado_el' => null
                                    ], "product_image_id = %i", $pimDb['product_image_id']);
                                } else {
                                    DB::insert('pos_product_image', [
                                        'product_id' => $prodId,
                                        'cod_producto' => $codFinal,
                                        'img' => $urlPim,
                                        'orden' => $pimJson['orden'] ?? 0,
                                        'is_visible' => $pimJson['is_visible'] ?? 1
                                    ]);
                                    $cnt('pos_imagenes_nuevas');
                                }
                            }
                        }
                    }
                }
            }
        }

        // =====================================================================
        // SLIDERS (reg_slider)
        // =====================================================================
        if (isset($contenido['sliders']) && is_array($contenido['sliders'])) {
            foreach ($contenido['sliders'] as $sliderJson) {
                $tituloSup = $sliderJson['titulo_superior'] ?? '';
                $grupo = $sliderJson['grupo'] ?? null;
                // grupo es un ENUM A-Z: cualquier otro valor se guarda como NULL
                if (!is_string($grupo) || !preg_match('/^[A-Z]$/', $grupo)) {
                    $grupo = null;
                }

                $sliderDb = null;
                if (!empty($tituloSup)) {
                    $sliderDb = DB::queryFirstRow("SELECT * FROM reg_slider WHERE neg_id = %i AND titulo_superior = %s", $neg_id, $tituloSup);
                }

                if ($sliderDb) {
                    DB::update('reg_slider', [
                        'img' => $sliderJson['img'] ?? '',
                        'orden' => $sliderJson['orden'] ?? 0,
                        'is_visible' => $sliderJson['is_visible'] ?? 1,
                        'fecha_fin' => $sliderJson['fecha_fin'] ?? null,
                        'descripcion' => $sliderJson['descripcion'] ?? '',
                        'grupo' => $grupo
                    ], "slider_id = %i", $sliderDb['slider_id']);
                    $cnt('sliders_actualizados');
                } else {
                    DB::insert('reg_slider', [
                        'titulo_superior' => $tituloSup,
                        'img' => $sliderJson['img'] ?? '',
                        'orden' => $sliderJson['orden'] ?? 0,
                        'is_visible' => $sliderJson['is_visible'] ?? 1,
                        'fecha_creacion' => $sliderJson['fecha_creacion'] ?? date('Y-m-d H:i:s'),
                        'fecha_fin' => $sliderJson['fecha_fin'] ?? null,
                        'descripcion' => $sliderJson['descripcion'] ?? '',
                        'grupo' => $grupo,
                        'neg_id' => $neg_id
                    ]);
                    $cnt('sliders_nuevos');
                }
            }
        }

        DB::commit();
        $transaccion = false;

        Flight::json(['status' => 'ok', 'resumen' => $n]);

    } catch (Exception $e) {
        if ($transaccion) {
            try {
                DB::rollback();
            } catch (Exception $e2) {
                // sin acción: se devuelve el error original
            }
        }
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
});