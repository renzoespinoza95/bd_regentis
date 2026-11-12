<?php
// control02.php - Crear / Editar producto
// Todas las consultas filtran por neg_id.
// El stock se guarda en pos_inventario.stock_actual.
// La subcategoría (pos_product.subcategoria_id) es opcional y única por producto.
// Al crear un producto desde el modal se le asigna una imagen por defecto.

if (!defined('IMG_PRODUCTO_DEFECTO')) {
    define('IMG_PRODUCTO_DEFECTO', 'https://barsi-img.b-cdn.net/regx/Wirr77.jpg');
}

// Valida que la subcategoría exista y pertenezca a una categoría del negocio.
// Devuelve la fila (subcategoria_id, category_id) o null.
if (!function_exists('prod_validar_subcategoria')) {
    function prod_validar_subcategoria($subcategoria_id, $neg_id){
        $subcategoria_id = intval($subcategoria_id);
        if ($subcategoria_id <= 0) return null;

        $row = DB::queryFirstRow(
            "SELECT s.subcategoria_id, s.category_id
               FROM pos_subcategoria s
              INNER JOIN pos_category c ON c.category_id = s.category_id
              WHERE s.subcategoria_id = %i
                AND c.neg_id = %i
                AND c.borrado_el IS NULL
                AND s.borrado_el IS NULL",
            $subcategoria_id, $neg_id
        );
        return $row ?: null;
    }
}

Flight::route('POST /product/crear', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $name        = trim($d['name'] ?? '');
    $price       = max(0, floatval($d['price'] ?? 0));
    $stock       = max(0, intval($d['stock'] ?? 0));
    $description = trim($d['description'] ?? '');
    $contenido01 = trim($d['contenido01'] ?? '');
    $contenido02 = trim($d['contenido02'] ?? '');
    $categorias  = $d['categorias'] ?? [];
    if (!is_array($categorias)) $categorias = [];

    if ($name === '') {
        Flight::json(['status' => 'error', 'msg' => 'El nombre es obligatorio'], 400);
        return;
    }

    try {
        // Subcategoría (opcional): si es válida, su categoría padre se asigna también
        $sub = prod_validar_subcategoria($d['subcategoria_id'] ?? 0, $neg_id);
        if ($sub) {
            $categorias[] = intval($sub['category_id']);
        }

        DB::startTransaction();

        DB::insert('pos_product', [
            'name'              => $name,
            'price'             => $price,
            'cantidad_comprada' => $stock,
            'description'       => $description,
            'contenido01'       => $contenido01,
            'contenido02'       => $contenido02,
            'neg_id'            => $neg_id,
            'subcategoria_id'   => $sub ? intval($sub['subcategoria_id']) : null,
            'tipo_producto'     => 'ABARROTES',
            'marca_des'         => 'GENERICA',
            'is_visible'        => 1,
            'fecha_creacion'    => date('Y-m-d H:i:s')
        ]);

        $product_id = DB::insertId();

        DB::insert('pos_inventario', [
            'product_id'   => $product_id,
            'stock_actual' => $stock,
            'neg_id'       => $neg_id
        ]);

        // Imagen por defecto del producto nuevo
        DB::insert('pos_product_image', [
            'product_id'     => $product_id,
            'img'            => IMG_PRODUCTO_DEFECTO,
            'orden'          => 0,
            'is_visible'     => 1,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ]);

        prod_guardar_categorias($product_id, $categorias, $neg_id);

        DB::commit();
        Flight::json(['status' => 'ok', 'product_id' => $product_id]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('POST /product/editar', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $product_id  = intval($d['product_id'] ?? 0);
    $name        = trim($d['name'] ?? '');
    $price       = max(0, floatval($d['price'] ?? 0));
    $stock       = max(0, intval($d['stock'] ?? 0));
    $description = trim($d['description'] ?? '');
    $contenido01 = trim($d['contenido01'] ?? '');
    $contenido02 = trim($d['contenido02'] ?? '');
    $categorias  = $d['categorias'] ?? [];
    if (!is_array($categorias)) $categorias = [];

    if (!$product_id || $name === '') {
        Flight::json(['status' => 'error', 'msg' => 'Datos incompletos'], 400);
        return;
    }

    try {
        $existe = DB::queryFirstField(
            "SELECT product_id FROM pos_product WHERE product_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $product_id, $neg_id
        );
        if (!$existe) {
            Flight::json(['status' => 'error', 'msg' => 'Producto no encontrado']);
            return;
        }

        // Subcategoría (opcional): si es válida, su categoría padre se asigna también
        $sub = prod_validar_subcategoria($d['subcategoria_id'] ?? 0, $neg_id);
        if ($sub) {
            $categorias[] = intval($sub['category_id']);
        }

        DB::startTransaction();

        DB::update('pos_product', [
            'name'               => $name,
            'price'              => $price,
            'description'        => $description,
            'contenido01'        => $contenido01,
            'contenido02'        => $contenido02,
            'subcategoria_id'    => $sub ? intval($sub['subcategoria_id']) : null,
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ], "product_id=%i AND neg_id=%i", $product_id, $neg_id);

        // Stock en pos_inventario (se crea el registro si no existe)
        $tieneInv = DB::queryFirstField(
            "SELECT COUNT(*) FROM pos_inventario WHERE product_id=%i",
            $product_id
        );
        if ($tieneInv > 0) {
            DB::update('pos_inventario', [
                'stock_actual' => $stock,
                'neg_id'       => $neg_id
            ], "product_id=%i", $product_id);
        } else {
            DB::insert('pos_inventario', [
                'product_id'   => $product_id,
                'stock_actual' => $stock,
                'neg_id'       => $neg_id
            ]);
        }

        prod_guardar_categorias($product_id, $categorias, $neg_id);

        DB::commit();
        Flight::json(['status' => 'ok']);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});