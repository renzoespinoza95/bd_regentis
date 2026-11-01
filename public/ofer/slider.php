<?php

/* =========================================================
   SLIDER (LISTAR)
========================================================= */

Flight::route('POST /Ceve/slider/listar', function(){

    try {

        $d = Flight::request()->data->getData();

        $xin  = isset($d['xin']) ? $d['xin'] : null;
        $yuan = isset($d['yuan']) ? $d['yuan'] : null;

        firma($xin, $yuan);

        DB::query("SET NAMES 'utf8mb4'");

        $neg_id = isset($d['neg_id']) ? intval($d['neg_id']) : 0;

        // En MeekroDB, los placeholders como %i protegen contra SQL Injection
        $rows = DB::query("
            SELECT
                slider_id,
                img,
                orden,
                is_visible,
                fecha_creacion,
                titulo_superior
            FROM reg_slider
            WHERE neg_id=%i
            AND borrado_el IS NULL
            ORDER BY
                orden ASC,
                slider_id DESC
        ", $neg_id);

        Flight::json([
            'status' => 'ok',
            'data'   => $rows
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);

    }

});


/* =========================================================
   CREAR
========================================================= */

Flight::route('POST /Ceve/slider/crear', function(){

    try {

        $d = Flight::request()->data->getData();

        $xin  = isset($d['xin']) ? $d['xin'] : null;
        $yuan = isset($d['yuan']) ? $d['yuan'] : null;

        firma($xin, $yuan);

        DB::query("SET NAMES 'utf8mb4'");

        $neg_id = isset($d['neg_id']) ? intval($d['neg_id']) : 0;

        $img = isset($d['img']) ? trim($d['img']) : '';
        $orden = isset($d['orden']) ? intval($d['orden']) : 0;
        $is_visible = isset($d['is_visible']) ? intval($d['is_visible']) : 1;
        $titulo_superior = isset($d['titulo_superior']) ? trim($d['titulo_superior']) : '';

        // Sintaxis estándar de inserción asociativa para MeekroDB
        DB::insert('reg_slider', [
            'img'             => $img,
            'orden'           => $orden,
            'is_visible'      => $is_visible,
            'fecha_creacion'  => date('Y-m-d H:i:s'),
            'neg_id'          => $neg_id,
            'titulo_superior' => $titulo_superior,
            'borrado_el'      => null
        ]);

        Flight::json([
            'status'    => 'ok',
            'slider_id' => DB::insertId()
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);

    }

});

/* =========================================================
   EDITAR
========================================================= */

Flight::route('POST /Ceve/slider/editar', function(){

    try {

        $d = Flight::request()->data->getData();

        $xin  = isset($d['xin']) ? $d['xin'] : null;
        $yuan = isset($d['yuan']) ? $d['yuan'] : null;

        firma($xin, $yuan);

        DB::query("SET NAMES 'utf8mb4'");

        $neg_id    = isset($d['neg_id']) ? intval($d['neg_id']) : 0;
        $slider_id = isset($d['slider_id']) ? intval($d['slider_id']) : 0;

        $img             = isset($d['img']) ? trim($d['img']) : '';
        $orden           = isset($d['orden']) ? intval($d['orden']) : 0;
        $is_visible      = isset($d['is_visible']) ? intval($d['is_visible']) : 1;
        $titulo_superior = isset($d['titulo_superior']) ? trim($d['titulo_superior']) : '';

        // Estructura nativa de actualización en MeekroDB: DB::update(tabla, datos_a_cambiar, where, ...valores_where)
        DB::update(
            'reg_slider',
            [
                'img'             => $img,
                'orden'           => $orden,
                'is_visible'      => $is_visible,
                'titulo_superior' => $titulo_superior
            ],
            "slider_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $slider_id,
            $neg_id
        );

        Flight::json([
            'status' => 'ok'
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);

    }

});

/* =========================================================
   VISIBLE
========================================================= */

Flight::route('POST /Ceve/slider/visible', function(){

    try {

        $d = Flight::request()->data->getData();

        $xin  = isset($d['xin']) ? $d['xin'] : null;
        $yuan = isset($d['yuan']) ? $d['yuan'] : null;

        firma($xin, $yuan);

        DB::query("SET NAMES 'utf8mb4'");

        $neg_id    = isset($d['neg_id']) ? intval($d['neg_id']) : 0;
        $slider_id = isset($d['slider_id']) ? intval($d['slider_id']) : 0;

        // Método oficial de MeekroDB para traer una sola fila asociativa
        $slider = DB::queryFirstRow("
            SELECT slider_id, is_visible 
            FROM reg_slider 
            WHERE slider_id=%i AND neg_id=%i AND borrado_el IS NULL 
            LIMIT 1
        ", $slider_id, $neg_id);

        if (!$slider) {
            Flight::json([
                'status' => 'error',
                'msg'    => 'Slider no encontrado'
            ], 404);
            return;
        }

        $estado_actual = intval($slider['is_visible']);
        $nuevo_estado  = ($estado_actual === 1) ? 0 : 1;

        DB::update(
            'reg_slider',
            [
                'is_visible' => $nuevo_estado
            ],
            "slider_id=%i AND neg_id=%i",
            $slider_id,
            $neg_id
        );

        Flight::json([
            'status'     => 'ok',
            'is_visible' => $nuevo_estado
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);

    }

});

/* =========================================================
   ELIMINAR FOTO SLIDER (SOFT DELETE)
========================================================= */

Flight::route('POST /M4LT/eliminarFotoSlider', function(){

    try {

        $d = Flight::request()->data->getData();

        $xin  = isset($d['xin']) ? $d['xin'] : null;
        $yuan = isset($d['yuan']) ? $d['yuan'] : null;

        firma($xin, $yuan);

        DB::query("SET NAMES 'utf8mb4'");

        $slider_id = isset($d['slider_image_id']) ? intval($d['slider_image_id']) : 0;

        if ($slider_id <= 0) {
            Flight::json([
                'status' => 'error',
                'msg'    => 'ID de slider inválido o ausente'
            ], 400);
            return;
        }

        DB::update(
            'reg_slider',
            [
                'borrado_el' => date('Y-m-d H:i:s')
            ],
            "slider_id=%i AND borrado_el IS NULL",
            $slider_id
        );

        Flight::json([
            'res'    => 'ok',
            'status' => 'ok',
            'msg'    => 'Foto eliminada correctamente'
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);

    }

});

/* =========================================================
   ORDENAR FOTOS DEL SLIDER
========================================================= */

Flight::route('POST /M4LT/ordenarFotoSlider', function(){

    try {

        $d = Flight::request()->data->getData();

        $xin  = isset($d['xin']) ? $d['xin'] : null;
        $yuan = isset($d['yuan']) ? $d['yuan'] : null;

        firma($xin, $yuan);

        DB::query("SET NAMES 'utf8mb4'");

        $lista = isset($d['lista']) ? $d['lista'] : null;

        if (!is_array($lista) || empty($lista)) {
            Flight::json([
                'status' => 'error',
                'msg'    => 'Listado de ordenamiento vacío o inválido'
            ], 400);
            return;
        }

        foreach ($lista as $item) {
            
            $slider_id   = isset($item['slider_image_id']) ? intval($item['slider_image_id']) : 0;
            $nuevo_orden = isset($item['orden']) ? intval($item['orden']) : 0;

            if ($slider_id > 0) {
                DB::update(
                    'reg_slider',
                    [
                        'orden' => $nuevo_orden
                    ],
                    "slider_id=%i AND borrado_el IS NULL",
                    $slider_id
                );
            }
        }

        Flight::json([
            'res'    => 'ok',
            'status' => 'ok',
            'msg'    => 'Orden actualizado correctamente'
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);

    }

});