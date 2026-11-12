<?php
// control03.php - Productos fantasma (FlightPHP / MeekroDB)
// Crea 5 productos con texto aleatorio (clase Lorem) y valor 1 en los numéricos,
// asignados a la categoría que viene seleccionada desde el frontend.
// Cada producto tiene 1 imagen por defecto.
// Todas las consultas filtran por neg_id. El stock se guarda en pos_inventario.

Flight::route('POST /OO09/productosFantasma', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $img_fantasma = 'https://barsi-img.b-cdn.net/recursos/6qz5.png';

    if (!class_exists('Lorem')) {
        Flight::json(['status' => 'error', 'msg' => 'La clase Lorem no está cargada'], 500);
        return;
    }

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $category_id = intval($d['category_id'] ?? 0);
    if (!$category_id) {
        Flight::json(['status' => 'error', 'msg' => 'Selecciona una categoría antes de crear productos fantasma']);
        return;
    }

    $cat = DB::queryFirstRow(
        "SELECT category_id FROM pos_category WHERE category_id = %i AND neg_id = %i AND borrado_el IS NULL",
        $category_id, $neg_id
    );
    if (!$cat) {
        Flight::json(['status' => 'error', 'msg' => 'La categoría seleccionada no existe']);
        return;
    }

    $ucfirst = function ($txt) {
        return mb_strtoupper(mb_substr($txt, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($txt, 1, null, 'UTF-8');
    };

    try {
        DB::startTransaction();

        $ids = [];
        for ($i = 0; $i < 5; $i++) {
            DB::insert('pos_product', [
                'name'              => $ucfirst(Lorem::ipsum(3)),
                'price'             => 1,
                'cantidad_comprada' => 1,
                'description'       => $ucfirst(Lorem::ipsum(15)) . '.',
                'contenido01'       => '<p>' . $ucfirst(Lorem::ipsum(30)) . '.</p>',
                'contenido02'       => '<p>' . $ucfirst(Lorem::ipsum(30)) . '.</p>',
                'neg_id'            => $neg_id,
                'tipo_producto'     => 'ABARROTES',
                'marca_des'         => 'GENERICA',
                'is_visible'        => 1,
                'fecha_creacion'    => date('Y-m-d H:i:s')
            ]);

            $product_id = DB::insertId();
            $ids[] = $product_id;

            DB::insert('pos_inventario', [
                'product_id'   => $product_id,
                'stock_actual' => 1,
                'neg_id'       => $neg_id
            ]);

            DB::insert('pos_product_category', [
                'product_id'  => $product_id,
                'category_id' => $category_id,
                'neg_id'      => $neg_id
            ]);

            DB::insert('pos_product_image', [
                'product_id'     => $product_id,
                'img'            => $img_fantasma,
                'orden'          => 0,
                'is_visible'     => 1,
                'fecha_creacion' => date('Y-m-d H:i:s')
            ]);
        }

        DB::commit();
        Flight::json(['status' => 'ok', 'creados' => count($ids), 'ids' => $ids]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});