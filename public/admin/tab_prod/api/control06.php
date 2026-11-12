<?php
// control06.php - Endpoints de imágenes de producto (FlightPHP / MeekroDB)
// Agrega y elimina imágenes de la galería del producto validando neg_id.

Flight::route('POST /product/imagen_agregar', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $product_id = intval($d['product_id'] ?? 0);
    $img = trim($d['img'] ?? '');

    if (!$product_id || $img === '') {
        Flight::json(['status' => 'error', 'msg' => 'Datos incompletos para agregar la imagen'], 400);
        return;
    }

    try {
        // Validar que el producto pertenezca al negocio actual y no esté borrado
        $prod = DB::queryFirstField(
            "SELECT product_id FROM pos_product WHERE product_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $product_id, $neg_id
        );
        if (!$prod) {
            Flight::json(['status' => 'error', 'msg' => 'Producto no encontrado']);
            return;
        }

        // Obtener el orden máximo actual para esta imagen
        $ordenMax = intval(DB::queryFirstField(
            "SELECT IFNULL(MAX(orden), 0) FROM pos_product_image WHERE product_id=%i AND borrado_el IS NULL",
            $product_id
        ));

        DB::insert('pos_product_image', [
            'product_id'     => $product_id,
            'cod_producto'   => '0',
            'img'            => $img,
            'orden'          => $ordenMax + 1,
            'is_visible'     => 1,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ]);

        Flight::json(['status' => 'ok', 'product_image_id' => DB::insertId()]);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('POST /product/imagen_eliminar', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $product_image_id = intval($d['product_image_id'] ?? 0);
    if (!$product_image_id) {
        Flight::json(['status' => 'error', 'msg' => 'ID de imagen no proporcionado'], 400);
        return;
    }

    try {
        // Validar que la imagen pertenezca a un producto del negocio actual
        $imgRow = DB::queryFirstRow(
            "SELECT pi.product_image_id, pi.product_id 
               FROM pos_product_image pi
              INNER JOIN pos_product p ON p.product_id = pi.product_id
              WHERE pi.product_image_id = %i
                AND p.neg_id = %i
                AND pi.borrado_el IS NULL
                AND p.borrado_el IS NULL",
            $product_image_id, $neg_id
        );

        if (!$imgRow) {
            Flight::json(['status' => 'error', 'msg' => 'Imagen no encontrada']);
            return;
        }

        DB::update('pos_product_image', [
            'borrado_el' => date('Y-m-d H:i:s')
        ], "product_image_id=%i", $product_image_id);

        Flight::json(['status' => 'ok']);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});