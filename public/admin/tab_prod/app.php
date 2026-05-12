<?php

Flight::route('GET /MpIH/product/listar', function () {

    include DEFINITION;

    // 🔥 RECIBIR DESDE QUERY (usuarioService)
    $neg_id = intval(Flight::request()->query['neg_id'] ?? 0);
    $usu_id = intval(Flight::request()->query['usu_id'] ?? 0);

    if(!$neg_id){
        Flight::json([
            'status'=>'error',
            'msg'=>'neg_id requerido'
        ],400);
        return;
    }

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("
        SELECT p.*,
               GROUP_CONCAT(pc.category_id) AS categories_ids,
               GROUP_CONCAT(c.name SEPARATOR ', ') AS categories_names,
               IFNULL(MAX(i.stock_actual),0) AS stock
        FROM pos_product p
        LEFT JOIN pos_product_category pc ON pc.product_id = p.product_id
        LEFT JOIN pos_category c ON c.category_id = pc.category_id
        LEFT JOIN pos_inventario i ON i.product_id = p.product_id
        WHERE p.neg_id=%i
        GROUP BY p.product_id
        ORDER BY p.product_id DESC
    ", $neg_id);

    foreach ($rows as &$r) {
        $r['categories_ids'] = $r['categories_ids']
            ? array_map('intval', explode(',', $r['categories_ids']))
            : [];
    }

    Flight::json([
        'status'=>'ok',
        'data'=>$rows,
        'neg_id'=>$neg_id,
        'usu_id'=>$usu_id
    ]);
});

Flight::route('POST /MpIH/buscarProducto', function () {

    include DEFINITION;

    $d = Flight::request()->data->getData();

    $neg_id = intval($d['neg_id'] ?? 0);
    $usu_id = intval($d['usu_id'] ?? 0);
    $texto  = trim($d['texto'] ?? '');

    if(!$neg_id || !$texto){
        Flight::json([
            'status'=>'error',
            'msg'=>'neg_id y texto son requeridos'
        ],400);
        return;
    }

    // 🔥 mínimo 2 caracteres
    if(strlen($texto) < 2){
        Flight::json([
            'status'=>'ok',
            'data'=>[]
        ]);
        return;
    }

    // 🔥 tipo Google: +palabra*
    $words = explode(' ', $texto);
    $search = '';

    foreach($words as $w){
        $search .= '+' . $w . '* ';
    }

    $limit = 20;

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            IFNULL(MAX(i.stock_actual),0) AS stock
        FROM pos_product p
        LEFT JOIN pos_inventario i ON i.product_id = p.product_id
        WHERE p.neg_id=%i
        AND MATCH(p.name) AGAINST (%s IN BOOLEAN MODE)
        GROUP BY p.product_id
        ORDER BY p.product_id DESC
        LIMIT %i
    ", $neg_id, trim($search), $limit);

    // 🔥 normalizar tipos
    foreach($rows as &$r){
        $r['price'] = floatval($r['price']);
        $r['stock'] = intval($r['stock']);
    }

    Flight::json([
        'status'=>'ok',
        'data'=>$rows,
        'neg_id'=>$neg_id,
        'usu_id'=>$usu_id
    ]);
});

Flight::route('GET /MylW/listar_categorias/@neg_id', function ($neg_id) {

    include DEFINITION;
    $neg_id = intval($neg_id);

    if(!$neg_id){
        Flight::json([
            'status'=>'error',
            'msg'=>'neg_id requerido'
        ],400);
        return;
    }

    // 🔥 1. CATEGORÍAS
    $categorias = DB::query("
        SELECT 
            c.category_id,
            c.name AS descripcion
        FROM pos_category c
        WHERE c.neg_id=%i
        ORDER BY c.name ASC
    ", $neg_id);


    // 🔥 2. PRODUCTOS + STOCK
    $productos = DB::query("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            pc.category_id,
            IFNULL(MAX(i.stock_actual),0) AS stock
        FROM pos_product p
        JOIN pos_product_category pc 
            ON pc.product_id = p.product_id
        LEFT JOIN pos_inventario i 
            ON i.product_id = p.product_id
        WHERE p.neg_id=%i
        GROUP BY p.product_id, pc.category_id
    ", $neg_id);


    // 🔥 3. MAPEAR POR CATEGORÍA
    $map = [];

    foreach($productos as $p){
        $cid = intval($p['category_id']);

        if(!isset($map[$cid])){
            $map[$cid] = [];
        }

        $map[$cid][] = [
            'product_id' => intval($p['product_id']),
            'name'       => $p['name'],
            'price'      => floatval($p['price']),
            'stock'      => intval($p['stock'])
        ];
    }


    // 🔥 4. ASIGNAR PRODUCTOS
    foreach($categorias as &$c){
        $cid = intval($c['category_id']);
        $c['productos'] = $map[$cid] ?? [];
    }


    Flight::json([
        'status'=>'ok',
        'neg_id'=>$neg_id,
        'data'=>$categorias
    ]);
});

Flight::route('GET /ArWL/tienda/@neg_id', function ($neg_id) {

    include DEFINITION;

    $neg_id = intval($neg_id);

    if (!$neg_id) {

        Flight::json([
            'status' => 'error',
            'msg' => 'neg_id requerido'
        ], 400);

        return;
    }

    DB::query("SET NAMES 'utf8mb4'");

    /* ============================================================
       🔥 USUARIO ACTUAL
    ============================================================ */
    global $usuario_actual;

    $usu_id = intval($usuario_actual['usu_id'] ?? 0);

    /* ============================================================
       0. NEGOCIO
    ============================================================ */
    $negocio = DB::queryFirstRow("
        SELECT *
        FROM reg_neg
        WHERE neg_id = %i
    ", $neg_id);


    /* ============================================================
       1. SLIDERS
    ============================================================ */
    $sliders = DB::query("
        SELECT *
        FROM reg_slider
        WHERE neg_id = %i
        AND is_visible = 1
        ORDER BY orden ASC
    ", $neg_id);


    /* ============================================================
       2. CATEGORÍAS
    ============================================================ */
    $categorias = DB::query("
        SELECT 
            c.category_id,
            c.name,
            c.icon,
            c.color,
            c.img,
            c.priority,
            c.clave_txt

        FROM pos_category c

        WHERE c.neg_id = %i
        AND c.is_activo = 1

        ORDER BY c.priority ASC, c.name ASC
    ", $neg_id);


    /* ============================================================
       3. PRODUCTOS + IMAGEN
    ============================================================ */
    $productos = DB::query("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            p.neg_id,
            pc.category_id,

            IFNULL(MAX(i.stock_actual),0) AS stock,

            SUBSTRING_INDEX(
                GROUP_CONCAT(pi.img ORDER BY pi.orden ASC),
                ',', 1
            ) AS img

        FROM pos_product p

        INNER JOIN pos_product_category pc 
            ON pc.product_id = p.product_id

        LEFT JOIN pos_inventario i 
            ON i.product_id = p.product_id

        LEFT JOIN pos_product_image pi
            ON pi.product_id = p.product_id
            AND pi.is_visible = 1

        WHERE p.neg_id = %i
        AND p.is_visible = 1

        GROUP BY 
            p.product_id,
            pc.category_id

        ORDER BY p.name ASC
    ", $neg_id);


    /* ============================================================
       4. MAPEAR PRODUCTOS
    ============================================================ */
    $map = [];

    foreach ($productos as $p) {

        $cid = intval($p['category_id']);

        if (!isset($map[$cid])) {
            $map[$cid] = [];
        }

        $map[$cid][] = [
            'product_id' => intval($p['product_id']),
            'name'       => $p['name'],
            'price'      => floatval($p['price']),
            'stock'      => intval($p['stock']),
            'neg_id'      => intval($p['neg_id']),
            'img'        => $p['img'] ?: null
        ];
    }


    /* ============================================================
       5. FILTRAR CATEGORÍAS VACÍAS
    ============================================================ */
    $categorias_filtradas = [];

    foreach ($categorias as $c) {

        $cid = intval($c['category_id']);

        $productos_categoria = $map[$cid] ?? [];

        if (count($productos_categoria) <= 0) {
            continue;
        }

        $c['productos'] = $productos_categoria;

        $categorias_filtradas[] = $c;
    }

    $categorias = $categorias_filtradas;


    /* ============================================================
       6. MÁS VENDIDOS
    ============================================================ */
    $mas_vendidos = [];

    $catMasVendidos = array_filter($categorias, function ($c) {

        $clave = strtolower(trim($c['clave_txt']));
        $clave = str_replace('-', '_', $clave);

        return $clave === 'mas_vendidos';
    });

    if ($catMasVendidos) {

        $catMasVendidos = array_values($catMasVendidos)[0];

        $mas_vendidos = $catMasVendidos['productos'] ?? [];
    }   


    /* ============================================================
       9. RESPONSE
    ============================================================ */
    Flight::json([
        'status' => 'ok',

        'neg_id' => $neg_id,

        'data' => [

            'negocio' => $negocio,

            'sliders' => $sliders,

            'categorias' => $categorias,

            'mas_vendidos' => $mas_vendidos
        ]
    ]);

});