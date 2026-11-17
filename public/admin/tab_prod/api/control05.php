<?php
// control05.php - Subcategorías (FlightPHP / MeekroDB)
// Una subcategoría pertenece a una categoría (pos_category) del negocio.
// Todas las consultas filtran por neg_id (vía la categoría) y borrado_el IS NULL.
// La eliminación es lógica (UPDATE borrado_el).

if (!defined('IMG_SUBCATEGORIA_DEFECTO')) {
    define('IMG_SUBCATEGORIA_DEFECTO', 'https://barsi-img.b-cdn.net/regx/4GjSVb.jpg');
}
Flight::route('GET /product/listar_subcategorias', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    try {
        // Se listan activas e inactivas (las inactivas se muestran en gris y cursiva)
        $subs = DB::query(
            "SELECT s.subcategoria_id,
                    s.category_id,
                    s.nombre,
                    s.img,
                    s.orden,
                    s.is_visible,
                    IFNULL(s.is_activo, 1) AS is_activo,
                    (SELECT COUNT(*)
                       FROM pos_product p
                      WHERE p.subcategoria_id = s.subcategoria_id
                        AND p.neg_id = %i
                        AND p.borrado_el IS NULL) AS total_productos
               FROM pos_subcategoria s
              INNER JOIN pos_category c ON c.category_id = s.category_id
              WHERE c.neg_id = %i
                AND c.borrado_el IS NULL
                AND s.borrado_el IS NULL
              ORDER BY s.category_id ASC, s.orden ASC, s.subcategoria_id ASC",
            $neg_id, $neg_id
        );
        Flight::json($subs);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('POST /product/subcategoria_crear', function () {
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
    $nombre      = mb_substr(trim($d['nombre'] ?? ''), 0, 200);
    $is_activo   = array_key_exists('is_activo', $d)
        ? (filter_var($d['is_activo'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0)
        : 1;

    if (!$category_id || $nombre === '') {
        Flight::json(['status' => 'error', 'msg' => 'Datos incompletos'], 400);
        return;
    }

    try {
        $cat = DB::queryFirstField(
            "SELECT category_id FROM pos_category WHERE category_id=%i AND neg_id=%i AND borrado_el IS NULL",
            $category_id, $neg_id
        );
        if (!$cat) {
            Flight::json(['status' => 'error', 'msg' => 'La categoría no existe']);
            return;
        }

        $dup = DB::queryFirstField(
            "SELECT subcategoria_id FROM pos_subcategoria
              WHERE category_id=%i AND LOWER(nombre)=LOWER(%s) AND borrado_el IS NULL",
            $category_id, $nombre
        );
        if ($dup) {
            Flight::json(['status' => 'error', 'msg' => 'Ya existe una subcategoría con ese nombre en esta categoría']);
            return;
        }

        $orden = intval(DB::queryFirstField(
            "SELECT IFNULL(MAX(orden), 0) FROM pos_subcategoria WHERE category_id=%i AND borrado_el IS NULL",
            $category_id
        )) + 1;

        $img = trim($d['img'] ?? '');
        if ($img === '' || !preg_match('#^https?://#i', $img)) {
            $img = IMG_SUBCATEGORIA_DEFECTO;
        }

        DB::insert('pos_subcategoria', [
            'category_id' => $category_id,
            'nombre'      => $nombre,
            'is_activo'   => $is_activo,
            'orden'       => $orden,
            'img'         => mb_substr($img, 0, 200),
            'is_visible'  => 1
        ]);

        Flight::json(['status' => 'ok', 'subcategoria_id' => DB::insertId()]);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('POST /product/subcategoria_editar', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $subcategoria_id = intval($d['subcategoria_id'] ?? 0);
    $nombre          = mb_substr(trim($d['nombre'] ?? ''), 0, 200);
    $is_activo       = array_key_exists('is_activo', $d)
        ? (filter_var($d['is_activo'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0)
        : 1;

    if (!$subcategoria_id || $nombre === '') {
        Flight::json(['status' => 'error', 'msg' => 'Datos incompletos'], 400);
        return;
    }

    try {
        // La subcategoría debe pertenecer a una categoría del negocio
        $sub = DB::queryFirstRow(
            "SELECT s.subcategoria_id, s.category_id
               FROM pos_subcategoria s
              INNER JOIN pos_category c ON c.category_id = s.category_id
              WHERE s.subcategoria_id = %i
                AND c.neg_id = %i
                AND c.borrado_el IS NULL
                AND s.borrado_el IS NULL",
            $subcategoria_id, $neg_id
        );
        if (!$sub) {
            Flight::json(['status' => 'error', 'msg' => 'Subcategoría no encontrada']);
            return;
        }

        $dup = DB::queryFirstField(
            "SELECT subcategoria_id FROM pos_subcategoria
              WHERE category_id=%i AND LOWER(nombre)=LOWER(%s)
                AND subcategoria_id <> %i AND borrado_el IS NULL",
            intval($sub['category_id']), $nombre, $subcategoria_id
        );
        if ($dup) {
            Flight::json(['status' => 'error', 'msg' => 'Ya existe una subcategoría con ese nombre en esta categoría']);
            return;
        }

        $upd = [
            'nombre'    => $nombre,
            'is_activo' => $is_activo
        ];

        // La imagen es opcional: solo se actualiza si llega una URL http(s) válida
        $img = trim($d['img'] ?? '');
        if ($img !== '' && preg_match('#^https?://#i', $img)) {
            $upd['img'] = mb_substr($img, 0, 200);
        }

        DB::update('pos_subcategoria', $upd, "subcategoria_id=%i", $subcategoria_id);

        Flight::json(['status' => 'ok']);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

// Actualiza solo pos_subcategoria.img (botón "Usar URL" del modal Editar Subcategoría)
Flight::route('POST /product/subcategoria_img', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $subcategoria_id = intval($d['subcategoria_id'] ?? 0);
    $img             = trim($d['img'] ?? '');

    if (!$subcategoria_id || !preg_match('#^https?://#i', $img)) {
        Flight::json(['status' => 'error', 'msg' => 'Datos incompletos o URL inválida'], 400);
        return;
    }

    if (mb_strlen($img) > 200) {
        Flight::json(['status' => 'error', 'msg' => 'La URL es demasiado larga (máximo 200 caracteres)']);
        return;
    }

    try {
        // La subcategoría debe pertenecer a una categoría del negocio
        $sub = DB::queryFirstField(
            "SELECT s.subcategoria_id
               FROM pos_subcategoria s
              INNER JOIN pos_category c ON c.category_id = s.category_id
              WHERE s.subcategoria_id = %i
                AND c.neg_id = %i
                AND c.borrado_el IS NULL
                AND s.borrado_el IS NULL",
            $subcategoria_id, $neg_id
        );
        if (!$sub) {
            Flight::json(['status' => 'error', 'msg' => 'Subcategoría no encontrada']);
            return;
        }

        DB::update('pos_subcategoria', [
            'img' => $img
        ], "subcategoria_id=%i", $subcategoria_id);

        Flight::json(['status' => 'ok', 'img' => $img]);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

// Soft delete de una subcategoría y de los productos que cuelgan de ella
// (con sus imágenes y vínculos a categorías).
Flight::route('POST /product/subcategoria_eliminar', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    $request = Flight::request();
    $d = json_decode($request->getBody(), true);
    if (!is_array($d)) {
        $d = $request->data->getData();
    }

    $subcategoria_id = intval($d['subcategoria_id'] ?? 0);
    if (!$subcategoria_id) {
        Flight::json(['status' => 'error', 'msg' => 'ID no proporcionado'], 400);
        return;
    }

    try {
        $sub = DB::queryFirstRow(
            "SELECT s.subcategoria_id
               FROM pos_subcategoria s
              INNER JOIN pos_category c ON c.category_id = s.category_id
              WHERE s.subcategoria_id = %i
                AND c.neg_id = %i
                AND c.borrado_el IS NULL
                AND s.borrado_el IS NULL",
            $subcategoria_id, $neg_id
        );
        if (!$sub) {
            Flight::json(['status' => 'error', 'msg' => 'Subcategoría no encontrada']);
            return;
        }

        $now = date('Y-m-d H:i:s');

        DB::startTransaction();

        $prodIds = array_map('intval', DB::queryFirstColumn(
            "SELECT product_id FROM pos_product
              WHERE subcategoria_id = %i AND neg_id = %i AND borrado_el IS NULL",
            $subcategoria_id, $neg_id
        ));

        $nProductos = prod_soft_delete_productos($prodIds, $neg_id, $now);

        DB::update('pos_subcategoria', [
            'borrado_el' => $now
        ], "subcategoria_id=%i", $subcategoria_id);

        DB::commit();
        Flight::json(['status' => 'ok', 'productos' => $nProductos]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});