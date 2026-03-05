<?php
// este es mi backend usando php8.2, flightphp y meekrodb2
Flight::route('GET /prod', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;                          // asegúrate de tener esta var en tu bootstrap
    include $path_public . '/admin/tab_prod/inicio.php';
});

/* 🔵 1) LISTAR PRODUCTOS (con nombres y IDs de categorías) */
Flight::route('GET /product/listar', function () {

    $rows = DB::query("
        SELECT p.*,
               GROUP_CONCAT(pc.category_id)        AS categories_ids,
               GROUP_CONCAT(c.name SEPARATOR ', ') AS categories_names,
               IFNULL(MAX(i.stock_actual), 0)      AS stock
        FROM product p
        LEFT JOIN product_category pc ON pc.product_id = p.product_id
        LEFT JOIN category c ON c.id = pc.category_id
        LEFT JOIN inventario i ON i.product_id = p.product_id
        GROUP BY p.product_id
        ORDER BY p.product_id DESC
    ");

    // Convertir a arrays
    foreach ($rows as &$r) {
        $r['categories_ids'] = $r['categories_ids'] ? array_map('intval', explode(',', $r['categories_ids'])) : [];
    }

    Flight::json($rows);
});


/* 🔵 2) LISTAR CATEGORÍAS PARA EL COMBO (Vue-Select) */
Flight::route('GET /product/listar_categorias', function () {

    $rows = DB::query("
        SELECT id AS category_id, name AS descripcion
        FROM category
        ORDER BY descripcion ASC
    ");

    Flight::json($rows);
});


/* 🔵 3) CREAR PRODUCTO (y sus categorías + imagen por defecto) */
Flight::route('POST /product/crear', function () {

    $d = Flight::request()->data->getData();

    $now_unix = time() * 1000;
    $now_dt   = date("Y-m-d H:i:s");

    DB::startTransaction();

    try {

        // ==========================
        // INSERT PRODUCTO
        // ==========================
        DB::insert('product', [
            'name'               => $d['name'],
            'price'              => $d['price'],
            'price_discount'     => 0,
            'description'        => $d['description'],
            'created_at'         => $now_unix,
            'last_update'        => $now_unix,
            'fecha_creacion'     => $now_dt,
            'fecha_modificacion' => $now_dt
        ]);

        $product_id = DB::insertId();

        // ==========================
        // INSERT CATEGORÍAS
        // ==========================
        if (!empty($d['categorias'])) {
            foreach ($d['categorias'] as $cat) {

                $category_id = is_array($cat)
                    ? intval($cat['category_id'])
                    : intval($cat);

                DB::insert('product_category', [
                    'product_id'  => $product_id,
                    'category_id' => $category_id
                ]);
            }
        }

        // ==========================
        // INSERT IMAGEN POR DEFECTO
        // ==========================
        DB::insert('product_image', [
            'product_id' => $product_id,
            'name'       => 'sin_foto.jpg'
        ]);

        DB::commit();

        Flight::json(['status' => 'ok']);

    } catch (Exception $e) {

        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
});



/* 🔵 4) EDITAR PRODUCTO (actualiza categorías) */
Flight::route('POST /product/editar', function () {

    $d = Flight::request()->data->getData();

    if (!isset($d['product_id'])) {
        Flight::json(['status'=>'error','msg'=>'product_id requerido'], 400);
        return;
    }

    $now_unix = time() * 1000;
    $now_dt   = date("Y-m-d H:i:s");

    DB::startTransaction();

    try {

        DB::update('product', [
            'name'               => $d['name'],
            'price'              => $d['price'],
            'description'        => $d['description'],
            'last_update'        => $now_unix,
            'fecha_modificacion' => $now_dt
        ], "product_id=%i", $d['product_id']);

        // LIMPIAR CATEGORÍAS ANTERIORES
        DB::delete('product_category', "product_id=%i", $d['product_id']);

        // AGREGAR NUEVAS
        if (!empty($d['categorias'])) {
            foreach ($d['categorias'] as $cat) {
              DB::insert('product_category', [
                'product_id'  => $d['product_id'],
                'category_id' => intval($cat['category_id'])
              ]);
            }
        }

        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch (Exception $e){

        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});


/* 🔵 5) DETALLE DEL PRODUCTO (categorías + imágenes) */
Flight::route('GET /product/detalle/@product_id', function ($product_id) {
    include DEFINITION;
    $p = DB::queryFirstRow("
        SELECT 
          p.*,
          IFNULL(i.stock_actual,0) AS stock
        FROM product p
        LEFT JOIN inventario i ON i.product_id = p.product_id
        WHERE p.product_id=%i
    ", $product_id);


    if (!$p) {
        Flight::json(['status'=>'error','msg'=>'No existe'], 404);
        return;
    }

    // Categorías
    $cats = DB::query("
        SELECT c.id AS category_id, c.name AS descripcion
        FROM product_category pc
        JOIN category c ON c.id = pc.category_id
        WHERE pc.product_id=%i
    ", $product_id);

    // Imágenes
    $imgs = DB::query("
    SELECT name
    FROM product_image
    WHERE product_id=%i
    ", $product_id);

    // armar ruta completa
    $base = $varhost . vari('IMG_PRODUCTO');

    foreach ($imgs as &$img) {
        $img['image'] = $base . $img['name'];
    }

    $p['categories'] = $cats;
    $p['images']     = $imgs;

    Flight::json($p);
});


/* 🔵 6) ELIMINAR PRODUCTO */
Flight::route('POST /product/eliminar', function () {

    $d = Flight::request()->data->getData();

    if (!isset($d['product_id'])) {
        Flight::json(['status'=>'error','msg'=>'product_id requerido'], 400);
        return;
    }

    DB::delete('product', "product_id=%i", $d['product_id']);

    Flight::json(['status'=>'ok']);
});


/* ============================================================
   CREAR CATEGORIA - USADA POR EL MODAL DEL FRONTEND
   ============================================================ */

Flight::route('POST /product/categoria_crear', function () {

    $d = Flight::request()->data->getData();

    if (!isset($d['name']) || trim($d['name']) == '') {
        Flight::json(['status'=>'error','msg'=>'Nombre requerido'], 400);
        return;
    }

    $now_unix = time() * 1000;

    try {

        DB::insert('category', [
            'name'        => $d['name'],
            'icon'        => $d['icon'],
            'draft'       => 0,
            'brief'       => $d['brief'],
            'color'       => $d['color'],
            'priority'    => 0,
            'created_at'  => $now_unix,
            'last_update' => $now_unix
        ]);

        Flight::json(['status'=>'ok']);

    } catch (Exception $e){

        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});

Flight::route('GET /imp_lista_prod', function () {

    include DEFINITION;
    login_admin::autentificar_administrador();
    // ===============================
    // DATOS DE CABECERA
    // ===============================
    $template_data = [];

    // ===============================
    // LISTADO DE PRODUCTOS + CATEGORÍAS
    // ===============================
    $rows = DB::query("
        SELECT 
            p.product_id,
            p.name AS producto,
            p.price,
            IFNULL(MAX(i.stock_actual),0) AS stock,
            GROUP_CONCAT(c.name SEPARATOR ', ') AS categorias
        FROM product p
        LEFT JOIN product_category pc ON pc.product_id = p.product_id
        LEFT JOIN category c ON c.id = pc.category_id
        LEFT JOIN inventario i ON i.product_id = p.product_id
        GROUP BY p.product_id
        ORDER BY p.name
    ");

    $i = 1;
    foreach ($rows as &$r) {
        $r['indice'] = $i++;
    }

    $template_data['listado'] = $rows;

        $template_data['informacion'] = [
        [
            'razon_social'  => 'CLUB SOCIAL LIMA NORTE S.A.C',
            'ruc'           => '20202020',
            'titulo_reporte'=> 'LISTADO GENERAL DE PRODUCTOS',
            'fecha'         => date('d/m/Y H:i'),
            'total_items'   => count($template_data['listado'])
        ]
    ];

    // ===============================
    // RENDER MUSTACHE
    // ===============================
    $mustache = new Mustache;

    $ruta_html = VARPATH . '/public/reportes/reporte_html/imp_lista_prod.html';

    $html = $mustache->render(
        file_get_contents($ruta_html),
        $template_data
    );

    // ===============================
    // GENERAR PDF
    // ===============================
    $nombre_pdf = 'lista_productos_' . time() . '.pdf';
    $ruta_pdf  = VARPATH . '/public/reportes/archivos_temporales/' . $nombre_pdf;

    $wkh_pdf->addPage($html);
    $comando = $wkh_pdf->getCommand($ruta_pdf);

    try {
        exec($comando);
        Flight::redirect($varhost . '/public/reportes/archivos_temporales/' . $nombre_pdf);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
});

Flight::route('GET /producto/reporteCategoriaProducto', function () {

    include DEFINITION;
    login_admin::autentificar_administrador();

    // ===============================
    // CATEGORÍAS SELECCIONADAS
    // ===============================
    $cats = json_decode($_GET['categorias'] ?? '[]', true);

    $where = '';
    if (!empty($cats) && !in_array('ALL', $cats)) {
        $where = "WHERE c.id IN %ls";
    }

    // ===============================
    // QUERY PRODUCTOS POR CATEGORÍA
    // ===============================
    if ($where) {
        $rows = DB::query("
            SELECT
              c.name AS categoria,
              p.name AS producto,
              p.price,
              IFNULL(MAX(i.stock_actual),0) AS stock
            FROM category c
            INNER JOIN product_category pc ON pc.category_id = c.id
            INNER JOIN product p ON p.product_id = pc.product_id
            LEFT JOIN inventario i ON i.product_id = p.product_id
            $where
            GROUP BY c.name, p.product_id
            ORDER BY c.name, p.name
        ", array_map('intval', $cats));
    } else {
        // TODAS LAS CATEGORÍAS
        $rows = DB::query("
            SELECT
              c.name AS categoria,
              p.name AS producto,
              p.price,
              IFNULL(MAX(i.stock_actual),0) AS stock
            FROM category c
            INNER JOIN product_category pc ON pc.category_id = c.id
            INNER JOIN product p ON p.product_id = pc.product_id
            LEFT JOIN inventario i ON i.product_id = p.product_id
            GROUP BY c.name, p.product_id
            ORDER BY c.name, p.name
        ");
    }

    // ===============================
    // AGRUPAR POR CATEGORÍA
    // ===============================
    $agrupado = [];
    foreach ($rows as $r) {
        $agrupado[$r['categoria']][] = $r;
    }

    // ===============================
    // TEMPLATE DATA
    // ===============================
    $template_data = [];

    $template_data['informacion'] = [
        [
            'razon_social'   => 'CLUB SOCIAL LIMA NORTE S.A.C',
            'ruc'            => '20202020',
            'titulo_reporte' => 'REPORTE DE PRODUCTOS POR CATEGORÍA',
            'fecha'          => date('d/m/Y H:i'),
            'total_items'    => count($rows)
        ]
    ];

    $template_data['categorias'] = [];

    foreach ($agrupado as $nombre => $items) {
        $template_data['categorias'][] = [
            'nombre' => $nombre,
            'items'  => $items
        ];
    }

    // ===============================
    // RENDER MUSTACHE
    // ===============================
    $mustache = new Mustache;

    $ruta_tpl = VARPATH . '/public/reportes/reporte_html/reporte_categoria_producto.html';

    $html = $mustache->render(
        file_get_contents($ruta_tpl),
        $template_data
    );

    // ===============================
    // HTML TEMPORAL (FIX WINDOWS)
    // ===============================
    $tmp_html = VARPATH . '/public/reportes/archivos_temporales/tmp_categoria.html';
    file_put_contents($tmp_html, $html);

    // ===============================
    // GENERAR PDF (REUSANDO $wkh_pdf)
    // ===============================
    $nombre_pdf = 'reporte_categoria_' . time() . '.pdf';
    $ruta_pdf   = VARPATH . '/public/reportes/archivos_temporales/' . $nombre_pdf;

    $wkh_pdf->addPage($tmp_html);
    $comando = $wkh_pdf->getCommand($ruta_pdf);

    exec($comando, $out, $ret);

    if ($ret !== 0 || !file_exists($ruta_pdf)) {
        echo "<pre>";
        echo "NO SE CREÓ EL PDF\n\n";
        echo "Comando:\n$comando\n\n";
        echo "Return code: $ret\n";
        echo "</pre>";
        exit;
    }

    // ===============================
    // REDIRECT FINAL
    // ===============================
    Flight::redirect(
        $varhost . '/public/reportes/archivos_temporales/' . $nombre_pdf
    );
});
