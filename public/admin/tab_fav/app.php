<?php
Flight::route('POST /DzCy/agregarFavorito', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $usu_id     = intval($d['usu_id'] ?? 0);
    $product_id = intval($d['product_id'] ?? 0);

    if(!$usu_id || !$product_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'usu_id y product_id requeridos'
        ], 400);
        return;
    }

    try {

        // 🔥 evitar duplicados
        $existe = DB::queryFirstField("
            SELECT fav_id 
            FROM reg_fav 
            WHERE usu_id=%i AND product_id=%i
        ", $usu_id, $product_id);

        if($existe){
            Flight::json([
                'status' => 'ok',
                'msg' => 'Ya existe',
                'fav_id' => intval($existe)
            ]);
            return;
        }

        DB::insert('reg_fav', [
            'usu_id' => $usu_id,
            'product_id' => $product_id
        ]);

        Flight::json([
            'status' => 'ok',
            'fav_id' => DB::insertId()
        ]);

    } catch(Exception $e){
        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

Flight::route('POST /DzCy/eliminarFavoritos', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $fav_id = intval($d['fav_id'] ?? 0);

    if(!$fav_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'fav_id requerido'
        ], 400);
        return;
    }

    try {

        DB::delete('reg_fav', 'fav_id=%i', $fav_id);

        Flight::json([
            'status' => 'ok'
        ]);

    } catch(Exception $e){
        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

}); 

Flight::route('POST /DzCy/misFavoritos', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    if(!$usu_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'usu_id requerido'

        ], 400);

        return;
    }

    try {

        $rows = DB::query("

            SELECT

                f.fav_id,
                f.product_id,
                f.fecha_creacion,

                p.name,
                p.price,
                p.neg_id,

                /* ======================================
                   NEGOCIO
                ====================================== */

                n.nombre AS neg_nombre,

                /* ======================================
                   STOCK
                ====================================== */

                IFNULL(

                    MAX(
                        i.stock_actual
                    ),

                    0

                ) AS stock,

                /* ======================================
                   IMAGEN PRINCIPAL
                ====================================== */

                img.img AS img

            FROM reg_fav f

            INNER JOIN pos_product p

                ON p.product_id = f.product_id

                AND p.borrado_el IS NULL

                AND p.is_visible = 1

            /* ======================================
               NEGOCIO
            ====================================== */

            LEFT JOIN reg_neg n

                ON n.neg_id = p.neg_id

            /* ======================================
               INVENTARIO
            ====================================== */

            LEFT JOIN pos_inventario i

                ON i.product_id = p.product_id

            /* ======================================
               IMAGEN DE MENOR ORDEN
            ====================================== */

            LEFT JOIN pos_product_image img

                ON img.product_image_id = (

                    SELECT ppi.product_image_id

                    FROM pos_product_image ppi

                    WHERE ppi.product_id = p.product_id

                    AND ppi.is_visible = 1

                    AND ppi.borrado_el IS NULL

                    ORDER BY ppi.orden ASC

                    LIMIT 1

                )

            WHERE f.usu_id = %i

            GROUP BY

                f.fav_id,
                f.product_id,
                f.fecha_creacion,

                p.name,
                p.price,
                p.neg_id,

                n.nombre,

                img.img

            ORDER BY f.fav_id DESC

        ", $usu_id);

        /* ======================================
           NORMALIZAR
        ====================================== */

        foreach($rows as &$r){

            $r['product_id'] = intval(
                $r['product_id']
            );

            $r['fav_id'] = intval(
                $r['fav_id']
            );

            $r['price'] = floatval(
                $r['price']
            );

            $r['stock'] = intval(
                $r['stock']
            );

            if(empty($r['img'])){

                $r['img'] =

                    'https://picsum.photos/300?random=' .

                    $r['product_id'];
            }
        }

        Flight::json([

            'status' => 'ok',

            'data'   => $rows

        ]);

    } catch(Exception $e){

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});