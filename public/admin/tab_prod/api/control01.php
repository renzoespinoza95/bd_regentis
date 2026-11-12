<?php
// control01.php - Controlador de productos (FlightPHP / MeekroDB)
// Todas las consultas filtran por neg_id y borrado_el IS NULL.
// El stock se lee de pos_inventario.stock_actual.
// Las rutas POST /product/crear y /product/editar están en control02.php
// Todo borrado es lógico (UPDATE borrado_el), nunca DELETE.

// Guarda las categorías de un producto (solo las que pertenecen al negocio).
// pos_product_category usa soft delete: los vínculos quitados se marcan con
// borrado_el y los que vuelven a marcarse se reactivan (índice único product/category).
if (!function_exists('prod_guardar_categorias')) {
    function prod_guardar_categorias($product_id, $categorias, $neg_id){
        $ids = [];
        if (is_array($categorias)) {
            foreach ($categorias as $cat) {
                $id = is_array($cat) ? intval($cat['category_id'] ?? 0) : intval($cat);
                if ($id > 0) $ids[$id] = $id;
            }
        }

        $now = date('Y-m-d H:i:s');

        $validos = [];
        if ($ids) {
            $validos = array_map('intval', DB::queryFirstColumn(
                "SELECT category_id FROM pos_category WHERE neg_id=%i AND borrado_el IS NULL AND category_id IN %li",
                $neg_id, array_values($ids)
            ));
        }

        // Soft delete de los vínculos que ya no están seleccionados
        if ($validos) {
            DB::update('pos_product_category', [
                'borrado_el' => $now
            ], "product_id=%i AND borrado_el IS NULL AND category_id NOT IN %li", $product_id, $validos);
        } else {
            DB::update('pos_product_category', [
                'borrado_el' => $now
            ], "product_id=%i AND borrado_el IS NULL", $product_id);
        }

        foreach ($validos as $cid) {
            $existe = intval(DB::queryFirstField(
                "SELECT COUNT(*) FROM pos_product_category WHERE product_id=%i AND category_id=%i",
                $product_id, $cid
            ));
            if ($existe > 0) {
                // Reactiva el vínculo (si estaba borrado lógicamente)
                DB::update('pos_product_category', [
                    'borrado_el' => null,
                    'neg_id'     => $neg_id
                ], "product_id=%i AND category_id=%i", $product_id, $cid);
            } else {
                DB::insert('pos_product_category', [
                    'product_id'  => $product_id,
                    'category_id' => $cid,
                    'neg_id'      => $neg_id
                ]);
            }
        }
    }
}

// Soft delete de una lista de productos junto con sus imágenes y vínculos a categorías.
// Devuelve la cantidad de productos que se marcaron como borrados.
if (!function_exists('prod_soft_delete_productos')) {
    function prod_soft_delete_productos($ids, $neg_id, $now){
        $ids = array_values(array_unique(array_map('intval', (array)$ids)));
        if (!$ids) return 0;

        DB::update('pos_product', [
            'borrado_el' => $now
        ], "product_id IN %li AND neg_id=%i AND borrado_el IS NULL", $ids, $neg_id);
        $n = DB::affectedRows();

        DB::update('pos_product_image', [
            'borrado_el' => $now
        ], "product_id IN %li AND borrado_el IS NULL", $ids);

        DB::update('pos_product_category', [
            'borrado_el' => $now
        ], "product_id IN %li AND borrado_el IS NULL", $ids);

        return $n;
    }
}

Flight::route('GET /product/listar', function(){
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    try {
        $productos = DB::query(
            "SELECT p.*, IFNULL(MAX(i.stock_actual), 0) AS stock
               FROM pos_product p
               LEFT JOIN pos_inventario i ON i.product_id = p.product_id
              WHERE p.neg_id = %i
                AND p.borrado_el IS NULL
              GROUP BY p.product_id
              ORDER BY p.product_id DESC",
            $neg_id
        );

        $ids = array_map(function($p){ return intval($p['product_id']); }, $productos);
        $catsPorProducto = [];
        $imgsPorProducto = [];

        if ($ids) {
            $cats = DB::query(
                "SELECT pc.product_id, c.category_id, c.name AS descripcion
                   FROM pos_product_category pc
                  INNER JOIN pos_category c ON c.category_id = pc.category_id
                  WHERE pc.product_id IN %li
                    AND pc.borrado_el IS NULL
                    AND c.neg_id = %i
                    AND c.borrado_el IS NULL",
                $ids, $neg_id
            );
            foreach ($cats as $c) {
                $catsPorProducto[$c['product_id']][] = [
                    'category_id' => intval($c['category_id']),
                    'descripcion' => $c['descripcion']
                ];
            }

            $imgs = DB::query(
                "SELECT * FROM pos_product_image
                  WHERE product_id IN %li AND borrado_el IS NULL
                  ORDER BY orden ASC, product_image_id ASC",
                $ids
            );
            foreach ($imgs as $im) {
                $imgsPorProducto[$im['product_id']][] = $im;
            }
        }

        foreach ($productos as &$p) {
            $pid = $p['product_id'];
            $cats = $catsPorProducto[$pid] ?? [];
            $p['categories']     = $cats;
            $p['categories_ids'] = array_map(function($c){ return $c['category_id']; }, $cats);
            $p['imagenes']       = $imgsPorProducto[$pid] ?? [];
        }
        unset($p);

        Flight::json($productos);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('GET /product/listar_categorias', function(){
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    try {
        // Se listan activas e inactivas (las inactivas se muestran en gris y cursiva)
        $categorias = DB::query(
            "SELECT c.category_id,
                    c.name,
                    c.name AS descripcion,
                    c.brief,
                    c.icon,
                    c.color,
                    c.img,
                    IFNULL(c.is_activo, 1) AS is_activo,
                    (SELECT COUNT(*)
                       FROM pos_product_category pc
                      INNER JOIN pos_product p ON p.product_id = pc.product_id
                      WHERE pc.category_id = c.category_id
                        AND pc.borrado_el IS NULL
                        AND p.neg_id = %i
                        AND p.borrado_el IS NULL) AS total_productos
               FROM pos_category c
              WHERE c.neg_id = %i
                AND c.borrado_el IS NULL
              ORDER BY c.name ASC",
            $neg_id, $neg_id
        );
        Flight::json($categorias);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('GET /product/detalle/@id', function($id){
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    try {
        $producto = DB::queryFirstRow(
            "SELECT p.*,
                    (SELECT IFNULL(MAX(i.stock_actual), 0)
                       FROM pos_inventario i
                      WHERE i.product_id = p.product_id) AS stock
               FROM pos_product p
              WHERE p.product_id = %i
                AND p.neg_id = %i
                AND p.borrado_el IS NULL",
            intval($id), $neg_id
        );

        if (!$producto) {
            Flight::json(['status' => 'error', 'msg' => 'Producto no encontrado']);
            return;
        }

        $producto['categories'] = DB::query(
            "SELECT c.category_id, c.name AS descripcion
               FROM pos_category c
              INNER JOIN pos_product_category pc ON pc.category_id = c.category_id
              WHERE pc.product_id = %i
                AND pc.borrado_el IS NULL
                AND c.neg_id = %i
                AND c.borrado_el IS NULL",
            intval($id), $neg_id
        );

        $producto['images'] = DB::query(
            "SELECT * FROM pos_product_image
              WHERE product_id = %i AND borrado_el IS NULL
              ORDER BY orden ASC, product_image_id ASC",
            intval($id)
        );

        Flight::json($producto);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

// Cambia is_visible (0 / 1) de un producto
Flight::route('POST /product/visible', function(){
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $data = json_decode($request->getBody(), true);
    if (!is_array($data)) {
        $data = $request->data->getData();
    }
    $productId = intval($data['product_id'] ?? 0);
    $isVisible = filter_var($data['is_visible'] ?? 0, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    if (!$productId) {
        Flight::json(['status' => 'error', 'msg' => 'ID no proporcionado']);
        return;
    }

    try {
        $existe = DB::queryFirstField(
            "SELECT product_id FROM pos_product WHERE product_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $productId, $neg_id
        );
        if (!$existe) {
            Flight::json(['status' => 'error', 'msg' => 'Producto no encontrado']);
            return;
        }

        DB::update('pos_product', [
            'is_visible'         => $isVisible,
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ], "product_id=%i AND neg_id=%i", $productId, $neg_id);

        Flight::json(['status' => 'ok', 'is_visible' => $isVisible]);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('POST /product/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $data = json_decode($request->getBody(), true);
    if (!is_array($data)) {
        $data = $request->data->getData();
    }
    $productId = intval($data['product_id'] ?? 0);

    if (!$productId) {
        Flight::json(['status' => 'error', 'msg' => 'ID no proporcionado']);
        return;
    }

    try {
        $existe = DB::queryFirstField(
            "SELECT product_id FROM pos_product WHERE product_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $productId, $neg_id
        );
        if (!$existe) {
            Flight::json(['status' => 'error', 'msg' => 'Producto no encontrado']);
            return;
        }

        // Borrado lógico (pos_inventario tiene FK hacia pos_product)
        DB::update('pos_product', [
            'borrado_el' => date('Y-m-d H:i:s')
        ], "product_id=%i AND neg_id=%i", $productId, $neg_id);

        Flight::json(['status' => 'ok']);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});