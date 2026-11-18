<?php
// control04.php - Crear / Editar / Eliminar categoría (FlightPHP / MeekroDB)
// Todas las consultas filtran por neg_id y borrado_el IS NULL.
// La eliminación es lógica (UPDATE borrado_el).

if (!defined('IMG_CATEGORIA_DEFECTO')) {
    define('IMG_CATEGORIA_DEFECTO', 'https://barsi-img.b-cdn.net/regx/QthzRv.jpg');
}

if (!function_exists('cat_normalizar')) {
    function cat_normalizar($d){
        $name      = trim($d['name'] ?? '');
        $icon      = trim($d['icon'] ?? '');
        $brief     = trim($d['brief'] ?? '');
        $clave_txt = trim($d['clave_txt'] ?? '');
        $color     = trim($d['color'] ?? '');
        $img       = trim($d['img'] ?? '');

        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#999999';
        }
        if ($img !== '' && !preg_match('#^https?://#i', $img)) {
            $img = '';
        }

        // Si no se envía is_activo se asume activa
        $is_activo = array_key_exists('is_activo', $d)
            ? (filter_var($d['is_activo'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0)
            : 1;

        return [
            'name'      => mb_substr($name, 0, 50),
            'icon'      => $icon !== ''      ? mb_substr($icon, 0, 400)      : null,
            'brief'     => $brief !== ''     ? mb_substr($brief, 0, 100)     : null,
            'clave_txt' => $clave_txt !== '' ? mb_substr($clave_txt, 0, 100) : null,
            'color'     => $color,
            'img'       => $img !== ''       ? mb_substr($img, 0, 300)       : null,
            'is_activo' => $is_activo
        ];
    }
}

Flight::route('POST /product/categoria_crear', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $c = cat_normalizar($d);
    if ($c['name'] === '') {
        Flight::json(['status' => 'error', 'msg' => 'El nombre es obligatorio'], 400);
        return;
    }

    // Si no se eligió imagen, se usa la imagen por defecto
    if ($c['img'] === null) {
        $c['img'] = IMG_CATEGORIA_DEFECTO;
    }

    try {
        DB::insert('pos_category', [
            'name'      => $c['name'],
            'icon'      => $c['icon'],
            'brief'     => $c['brief'],
            'clave_txt' => $c['clave_txt'],
            'color'     => $c['color'],
            'img'       => $c['img'],
            'is_activo' => $c['is_activo'],
            'neg_id'    => $neg_id
        ]);

        Flight::json(['status' => 'ok', 'category_id' => DB::insertId()]);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('POST /product/categoria_editar', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $category_id = intval($d['category_id'] ?? 0);
    $c = cat_normalizar($d);

    if (!$category_id || $c['name'] === '') {
        Flight::json(['status' => 'error', 'msg' => 'Datos incompletos'], 400);
        return;
    }

    try {
        $existe = DB::queryFirstField(
            "SELECT category_id FROM pos_category WHERE category_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $category_id, $neg_id
        );
        if (!$existe) {
            Flight::json(['status' => 'error', 'msg' => 'Categoría no encontrada']);
            return;
        }

        DB::update('pos_category', [
            'name'      => $c['name'],
            'icon'      => $c['icon'],
            'brief'     => $c['brief'],
            'clave_txt' => $c['clave_txt'],
            'color'     => $c['color'],
            'img'       => $c['img'],
            'is_activo' => $c['is_activo']
        ], "category_id=%i AND neg_id=%i", $category_id, $neg_id);

        Flight::json(['status' => 'ok']);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

// Actualiza solo pos_category.img (botón "Usar URL" del modal Editar Categoría)
Flight::route('POST /product/categoria_img', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $category_id = intval($d['category_id'] ?? 0);
    $img         = trim($d['img'] ?? '');

    if (!$category_id || !preg_match('#^https?://#i', $img)) {
        Flight::json(['status' => 'error', 'msg' => 'Datos incompletos o URL inválida'], 400);
        return;
    }

    if (mb_strlen($img) > 300) {
        Flight::json(['status' => 'error', 'msg' => 'La URL es demasiado larga (máximo 300 caracteres)']);
        return;
    }

    try {
        $existe = DB::queryFirstField(
            "SELECT category_id FROM pos_category WHERE category_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $category_id, $neg_id
        );
        if (!$existe) {
            Flight::json(['status' => 'error', 'msg' => 'Categoría no encontrada']);
            return;
        }

        DB::update('pos_category', [
            'img' => $img
        ], "category_id=%i AND neg_id=%i", $category_id, $neg_id);

        Flight::json(['status' => 'ok', 'img' => $img]);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

// Soft delete de una categoría y de todo lo que cuelga de ella:
//  - sus subcategorías y los productos de esas subcategorías (con imágenes y vínculos)
//  - los productos vinculados directamente que NO tengan otra categoría activa
//  - los vínculos restantes (pos_product_category) de la categoría
Flight::route('POST /product/categoria_eliminar', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $category_id = intval($d['category_id'] ?? 0);
    if (!$category_id) {
        Flight::json(['status' => 'error', 'msg' => 'ID no proporcionado'], 400);
        return;
    }

    try {
        $existe = DB::queryFirstField(
            "SELECT category_id FROM pos_category WHERE category_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $category_id, $neg_id
        );
        if (!$existe) {
            Flight::json(['status' => 'error', 'msg' => 'Categoría no encontrada']);
            return;
        }

        $now = date('Y-m-d H:i:s');

        DB::startTransaction();

        // 1. Subcategorías de la categoría y sus productos
        $subIds = array_map('intval', DB::queryFirstColumn(
            "SELECT subcategoria_id FROM pos_subcategoria WHERE category_id=%i AND borrado_el IS NULL",
            $category_id
        ));

        $prodIds = [];
        if ($subIds) {
            $prodIds = array_map('intval', DB::queryFirstColumn(
                "SELECT product_id FROM pos_product
                  WHERE neg_id=%i AND borrado_el IS NULL AND subcategoria_id IN %li",
                $neg_id, $subIds
            ));
        }

        // 2. Productos vinculados directamente: solo se eliminan si no tienen otra categoría activa
        $directos = array_map('intval', DB::queryFirstColumn(
            "SELECT pc.product_id
               FROM pos_product_category pc
              INNER JOIN pos_product p ON p.product_id = pc.product_id
              WHERE pc.category_id = %i
                AND pc.borrado_el IS NULL
                AND p.neg_id = %i
                AND p.borrado_el IS NULL",
            $category_id, $neg_id
        ));
        foreach ($directos as $pid) {
            $otras = intval(DB::queryFirstField(
                "SELECT COUNT(*)
                   FROM pos_product_category pc
                  INNER JOIN pos_category c ON c.category_id = pc.category_id
                                           AND c.borrado_el IS NULL
                                           AND c.neg_id = %i
                  WHERE pc.product_id = %i
                    AND pc.category_id <> %i
                    AND pc.borrado_el IS NULL",
                $neg_id, $pid, $category_id
            ));
            if ($otras == 0) {
                $prodIds[] = $pid;
            }
        }

        // 3. Productos (con imágenes y vínculos)
        $nProductos = prod_soft_delete_productos($prodIds, $neg_id, $now);

        // 4. Vínculos restantes de esta categoría (productos que sobreviven en otras categorías)
        DB::update('pos_product_category', [
            'borrado_el' => $now
        ], "category_id=%i AND borrado_el IS NULL", $category_id);

        // 5. Subcategorías
        if ($subIds) {
            DB::update('pos_subcategoria', [
                'borrado_el' => $now
            ], "subcategoria_id IN %li AND borrado_el IS NULL", $subIds);
        }

        // 6. La categoría
        DB::update('pos_category', [
            'borrado_el' => $now
        ], "category_id=%i AND neg_id=%i", $category_id, $neg_id);

        DB::commit();
        Flight::json([
            'status'        => 'ok',
            'subcategorias' => count($subIds),
            'productos'     => $nProductos
        ]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});