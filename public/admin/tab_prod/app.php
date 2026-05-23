<?php
Flight::route('POST /MpIH/product/listar', function () {

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $data = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* =========================================
       FIRMA
    ========================================= */

    $xin  = $data['xin'] ?? '';
    $yuan = $data['yuan'] ?? '';

    firma($xin, $yuan);

    /* =========================================
       PAYLOAD
    ========================================= */

    $neg_id = intval($data['neg_id'] ?? 0);

    $usu_id = intval($data['usu_id'] ?? 0);

    /* =========================================
       VALIDAR
    ========================================= */

    if(!$neg_id){

        Flight::json([
            'status'=>'error',
            'msg'=>'neg_id requerido'
        ],400);

        return;
    }

    /* =========================================
       PRODUCTOS
    ========================================= */

    $rows = DB::query("

        SELECT

            p.*,

            GROUP_CONCAT(
                pc.category_id
            ) AS categories_ids,

            GROUP_CONCAT(
                c.name
                SEPARATOR ', '
            ) AS categories_names,

            IFNULL(
                MAX(i.stock_actual),
                0
            ) AS stock

        FROM pos_product p

        LEFT JOIN pos_product_category pc
            ON pc.product_id = p.product_id

        LEFT JOIN pos_category c
            ON c.category_id = pc.category_id

        LEFT JOIN pos_inventario i
            ON i.product_id = p.product_id

        WHERE p.neg_id = %i

        GROUP BY p.product_id

        ORDER BY p.product_id DESC

    ", $neg_id);

    /* =========================================
       NORMALIZAR categories_ids
    ========================================= */

    foreach ($rows as &$r) {

        $r['categories_ids'] = $r['categories_ids']

            ? array_map(
                'intval',
                explode(',', $r['categories_ids'])
            )

            : [];

    }

    /* =========================================
       RESPONSE
    ========================================= */

    Flight::json([

        'status'=>'ok',

        'data'=>$rows,

        'neg_id'=>$neg_id,

        'usu_id'=>$usu_id

    ], 200, JSON_UNESCAPED_UNICODE);

});

Flight::route('POST /MylW/listar_categorias', function () {

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$neg_id){

        Flight::json([

            'status'=>'error',

            'msg'=>'neg_id requerido'

        ],400);

        return;
    }

    /* ======================================
       PUBLICO GENERAL
    ====================================== */

    $publico_general_id = DB::queryFirstField("

        SELECT

            cliente_id

        FROM pos_cliente

        WHERE neg_id = %i

        AND nombres_apellidos = 'PUBLICO_GENERAL'

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    $publico_general_id = intval(
        $publico_general_id
    );

    /* ======================================
       CATEGORÍAS
    ====================================== */

    $categorias = DB::query("

        SELECT 

            c.category_id,

            c.name AS descripcion

        FROM pos_category c

        WHERE c.neg_id = %i

        AND c.borrado_el IS NULL

        ORDER BY c.name ASC

    ", $neg_id);

    /* ======================================
       PRODUCTOS + STOCK
    ====================================== */

    $productos = DB::query("

        SELECT 

            p.product_id,

            p.cod_producto,

            p.name,

            p.price,

            p.description,

            p.is_visible,

            pc.category_id,

            IFNULL(
                MAX(i.stock_actual),
                0
            ) AS stock

        FROM pos_product p

        INNER JOIN pos_product_category pc 
            ON pc.product_id = p.product_id

        LEFT JOIN pos_inventario i 
            ON i.product_id = p.product_id

        WHERE p.neg_id = %i

        AND p.borrado_el IS NULL

        GROUP BY 
            p.product_id,
            pc.category_id

    ", $neg_id);

    /* ======================================
       MAPEAR POR CATEGORÍA
    ====================================== */

    $map = [];

    foreach($productos as $p){

        $cid = intval(
            $p['category_id']
        );

        if(!isset($map[$cid])){

            $map[$cid] = [];

        }

        /* ======================================
           IMAGENES
        ====================================== */

        $imagenes = DB::query("

            SELECT

                product_image_id,

                img,

                orden,

                is_visible

            FROM pos_product_image

            WHERE product_id = %i

            AND borrado_el IS NULL

            ORDER BY orden ASC

        ",
            $p['product_id']
        );

        foreach($imagenes as &$img){

            $img['product_image_id'] = intval(
                $img['product_image_id']
            );

            $img['orden'] = intval(
                $img['orden']
            );

            $img['is_visible'] = intval(
                $img['is_visible']
            );

        }

        $map[$cid][] = [

            'product_id' => intval(
                $p['product_id']
            ),

            'cod_producto' =>
                $p['cod_producto'],

            'is_visible' => intval(
                $p['is_visible']
            ),

            'name' =>
                $p['name'],

            'description' =>
                $p['description'],

            'price' => floatval(
                $p['price']
            ),

            'stock' => intval(
                $p['stock']
            ),

            'imagenes' =>
                $imagenes

        ];
    }

    /* ======================================
       ASIGNAR PRODUCTOS
    ====================================== */

    foreach($categorias as &$c){

        $cid = intval(
            $c['category_id']
        );

        $c['productos'] =
            $map[$cid] ?? [];

    }

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status'=>'ok',

        'neg_id'=>$neg_id,

        'publico_general_id' =>
            $publico_general_id,

        'data'=>$categorias

    ]);

});

Flight::route('POST /ArWL/tienda', function () {

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ============================================================
       CAMPOS
    ============================================================ */

    $neg_id = isset($d['neg_id'])
        ? intval($d['neg_id'])
        : 0;

    $xin = isset($d['xin'])
        ? trim((string)$d['xin'])
        : '';

    $yuan = isset($d['yuan'])
        ? trim((string)$d['yuan'])
        : '';

    /* ============================================================
       VALIDAR FIRMA
    ============================================================ */

    firma(
        $xin,
        $yuan
    );

    /* ============================================================
       VALIDAR NEG_ID
    ============================================================ */

    if (!$neg_id) {

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ], 400);

        return;
    }

    /* ============================================================
       🔥 USUARIO ACTUAL
    ============================================================ */

    global $usuario_actual;

    $usu_id = intval(
        $usuario_actual['usu_id'] ?? 0
    );

    /* ============================================================
       0. NEGOCIO
    ============================================================ */

    $negocio = DB::queryFirstRow("

        SELECT *

        FROM reg_neg

        WHERE neg_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    /* ============================================================
       VALIDAR NEGOCIO
    ============================================================ */

    if(!$negocio){

        Flight::json([

            'status' => 'error',

            'msg' => 'Negocio no encontrado'

        ],404);

        return;
    }

    /* ============================================================
       1. SLIDERS
    ============================================================ */

    $sliders = DB::query("

        SELECT *

        FROM reg_slider

        WHERE neg_id = %i

        AND is_visible = 1

        AND borrado_el IS NULL

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

        AND c.borrado_el IS NULL

        ORDER BY
            c.priority ASC,
            c.name ASC

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
            p.description,
            p.cod_producto,
            p.tipo_producto,
            p.marca_des,
            p.is_visible,

            pc.category_id,

            IFNULL(
                MAX(i.stock_actual),
                0
            ) AS stock,

            SUBSTRING_INDEX(

                GROUP_CONCAT(

                    pi.img

                    ORDER BY pi.orden ASC

                ),

                ',',

                1

            ) AS img

        FROM pos_product p

        INNER JOIN pos_product_category pc
            ON pc.product_id = p.product_id

        LEFT JOIN pos_inventario i
            ON i.product_id = p.product_id

        LEFT JOIN pos_product_image pi
            ON pi.product_id = p.product_id
            AND pi.is_visible = 1
            AND pi.borrado_el IS NULL

        WHERE p.neg_id = %i

        AND p.is_visible = 1

        AND p.borrado_el IS NULL

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

        $cid = intval(
            $p['category_id']
        );

        if (!isset($map[$cid])) {

            $map[$cid] = [];

        }

        /* ============================================================
           IMAGENES
        ============================================================ */

        $imagenes = DB::query("

            SELECT

                product_image_id,

                img,

                orden,

                is_visible

            FROM pos_product_image

            WHERE product_id = %i

            AND borrado_el IS NULL

            ORDER BY orden ASC

        ",
            $p['product_id']
        );

        foreach($imagenes as &$img){

            $img['product_image_id'] = intval(
                $img['product_image_id']
            );

            $img['orden'] = intval(
                $img['orden']
            );

            $img['is_visible'] = intval(
                $img['is_visible']
            );

        }

        $map[$cid][] = [

            'product_id' => intval(
                $p['product_id']
            ),

            'name' => $p['name'],

            'price' => floatval(
                $p['price']
            ),

            'stock' => intval(
                $p['stock']
            ),

            'neg_id' => intval(
                $p['neg_id']
            ),

            'description' =>
                $p['description'],

            'cod_producto' =>
                $p['cod_producto'],

            'tipo_producto' =>
                $p['tipo_producto'],

            'marca_des' =>
                $p['marca_des'],

            'is_visible' => intval(
                $p['is_visible']
            ),

            'img' => $p['img']
                ? $p['img']
                : null,

            'imagenes' =>
                $imagenes

        ];
    }

    /* ============================================================
       5. FILTRAR CATEGORÍAS VACÍAS
    ============================================================ */

    $categorias_filtradas = [];

    foreach ($categorias as $c) {

        $cid = intval(
            $c['category_id']
        );

        $productos_categoria =
            $map[$cid] ?? [];

        if (
            count($productos_categoria) <= 0
        ) {
            continue;
        }

        $c['productos'] =
            $productos_categoria;

        $categorias_filtradas[] = $c;
    }

    $categorias =
        $categorias_filtradas;

    /* ============================================================
       6. MÁS VENDIDOS
    ============================================================ */

    $mas_vendidos = [];

    $catMasVendidos = array_filter(

        $categorias,

        function ($c) {

            $clave = strtolower(
                trim($c['clave_txt'])
            );

            $clave = str_replace(
                '-',
                '_',
                $clave
            );

            return $clave === 'mas_vendidos';

        }

    );

    if ($catMasVendidos) {

        $catMasVendidos =
            array_values(
                $catMasVendidos
            )[0];

        $mas_vendidos =
            $catMasVendidos['productos']
            ?? [];
    }

    /* ============================================================
       RESPONSE
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

Flight::route('POST /VG0a/productoStock', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $product_id = intval(
        $d['product_id'] ?? 0
    );

    $stock = intval(
        $d['stock'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($product_id <= 0){

        Flight::json([
            'status' => 'error',
            'msg' => 'product_id requerido'
        ], 400);

        return;
    }

    if($stock <= 0){

        Flight::json([
            'status' => 'error',
            'msg' => 'stock inválido'
        ], 400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           PRODUCTO
        ====================================== */

        $producto = DB::queryFirstRow("

            SELECT

                p.product_id,
                p.price,
                p.neg_id,

                i.stock_actual

            FROM pos_product p

            LEFT JOIN pos_inventario i
                ON i.product_id = p.product_id

            WHERE p.product_id = %i

            LIMIT 1

        ", $product_id);

        if(!$producto){

            DB::rollback();

            Flight::json([
                'status' => 'error',
                'msg' => 'Producto no encontrado'
            ], 404);

            return;
        }

        /* ======================================
           STOCKS
        ====================================== */

        $stock_actual = intval(
            $producto['stock_actual'] ?? 0
        );

        $nuevo_stock =
            $stock_actual + $stock;

        /* ======================================
           INVENTARIO
        ====================================== */

        $existeInventario =
            DB::queryFirstField("

                SELECT 1

                FROM pos_inventario

                WHERE product_id = %i

                LIMIT 1

            ", $product_id);

        if($existeInventario){

            DB::update(
                'pos_inventario',
                [

                    'stock_actual' =>
                        $nuevo_stock

                ],
                "product_id=%i",
                $product_id
            );

        }else{

            DB::insert(
                'pos_inventario',
                [

                    'product_id' =>
                        $product_id,

                    'stock_actual' =>
                        $nuevo_stock,

                    'neg_id' =>
                        $producto['neg_id']

                ]
            );

        }

        /* ======================================
           MOVIMIENTO
        ====================================== */

        DB::insert(
            'pos_inventario_movimiento',
            [

                'product_id' =>
                    $product_id,

                'tipo' => 'AJUSTE',

                'origen' => 'AJUSTE',

                'cantidad' =>
                    $stock,

                'precio_unitario' =>
                    floatval(
                        $producto['price']
                    ),

                'fecha' => $now,

                'stock_resultante' =>
                    $nuevo_stock,

                'neg_id' =>
                    $producto['neg_id']

            ]
        );

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' => 'Stock actualizado correctamente',

            'data' => [

                'product_id' =>
                    $product_id,

                'stock_anterior' =>
                    $stock_actual,

                'stock_agregado' =>
                    $stock,

                'stock_actual' =>
                    $nuevo_stock

            ]

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});


Flight::route('POST /CF3a/productoVisible', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $product_id = intval(
        $d['product_id'] ?? 0
    );

    $is_visible = intval(
        $d['is_visible'] ?? -1
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($product_id <= 0){

        Flight::json([
            'status' => 'error',
            'msg' => 'product_id requerido'
        ], 400);

        return;
    }

    if(
        $is_visible !== 0
        &&
        $is_visible !== 1
    ){

        Flight::json([
            'status' => 'error',
            'msg' => 'is_visible inválido'
        ], 400);

        return;
    }

    try {

        /* ======================================
           VALIDAR PRODUCTO
        ====================================== */

        $producto = DB::queryFirstRow("

            SELECT

                product_id,
                is_visible

            FROM pos_product

            WHERE product_id = %i

            LIMIT 1

        ", $product_id);

        if(!$producto){

            Flight::json([
                'status' => 'error',
                'msg' => 'Producto no encontrado'
            ], 404);

            return;
        }

        /* ======================================
           UPDATE
        ====================================== */

        DB::update(
            'pos_product',
            [

                'is_visible' => $is_visible,

                'fecha_modificacion' =>
                    date('Y-m-d H:i:s')

            ],
            "product_id=%i",
            $product_id
        );

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' => 'Visibilidad actualizada',

            'data' => [

                'product_id' =>
                    $product_id,

                'is_visible' =>
                    $is_visible

            ]

        ]);

    } catch(Exception $e){

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});

Flight::route('POST /RvKx/nuevoProducto', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       FIRMA
    ====================================== */

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       CAMPOS
    ====================================== */

    $product_id = intval(
        $d['product_id'] ?? 0
    );

    $editando = intval(
        $d['editando'] ?? 0
    );

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $name = trim(
        $d['name'] ?? ''
    );

    $price = floatval(
        $d['price'] ?? 0
    );

    $stock = intval(
        $d['stock'] ?? 0
    );

    $description = trim(
        $d['description'] ?? ''
    );

    $marca_des = trim(
        $d['marca_des']
        ?? 'GENERICA'
    );

    $categorias = $d['categorias'] ?? [];

    /* ======================================
       VALIDAR
    ====================================== */

    if($neg_id <= 0){

        Flight::json([

            'status'=>'error',

            'msg'=>'neg_id requerido'

        ],400);

        return;
    }

    if(!$name){

        Flight::json([

            'status'=>'error',

            'msg'=>'name requerido'

        ],400);

        return;
    }

    if($price <= 0){

        Flight::json([

            'status'=>'error',

            'msg'=>'price inválido'

        ],400);

        return;
    }

    DB::startTransaction();

    try{

        $now_dt = date(
            "Y-m-d H:i:s"
        );

        /* ======================================
           NEGOCIO
        ====================================== */

        $negocio = DB::queryFirstRow("

            SELECT neg_id

            FROM reg_neg

            WHERE neg_id = %i

            AND borrado_el IS NULL

            LIMIT 1

        ", $neg_id);

        if(!$negocio){

            DB::rollback();

            Flight::json([

                'status'=>'error',

                'msg'=>'Negocio no encontrado'

            ],404);

            return;
        }

        /* ======================================
           EDITAR PRODUCTO
        ====================================== */

        if(
            $editando
            &&
            $product_id > 0
        ){

            $producto = DB::queryFirstRow("

                SELECT

                    product_id

                FROM pos_product

                WHERE product_id = %i

                AND neg_id = %i

                AND borrado_el IS NULL

                LIMIT 1

            ",
                $product_id,
                $neg_id
            );

            if(!$producto){

                DB::rollback();

                Flight::json([

                    'status'=>'error',

                    'msg'=>'Producto no encontrado'

                ],404);

                return;
            }

            /* ======================================
               ACTUALIZAR PRODUCTO
            ====================================== */

            DB::update(

                'pos_product',

                [

                    'name' =>
                        $name,

                    'marca_des' =>
                        $marca_des,

                    'price' =>
                        $price,

                    'description' =>
                        $description,

                    'fecha_modificacion' =>
                        $now_dt

                ],

                "product_id=%i",

                $product_id

            );

            /* ======================================
               ELIMINAR CATEGORIAS
            ====================================== */

            DB::delete(

                'pos_product_category',

                "product_id=%i",

                $product_id

            );

            /* ======================================
               NUEVAS CATEGORIAS
            ====================================== */

            if(!empty($categorias)){

                foreach($categorias as $cat){

                    $category_id = is_array($cat)

                        ? intval(
                            $cat['category_id']
                            ?? 0
                        )

                        : intval($cat);

                    if($category_id <= 0){
                        continue;
                    }

                    DB::insert(

                        'pos_product_category',

                        [

                            'product_id' =>
                                $product_id,

                            'category_id' =>
                                $category_id,

                            'neg_id' =>
                                $neg_id,

                            'is_visible' => 1

                        ]

                    );

                }

            }

            /* ======================================
               INVENTARIO
            ====================================== */

            if($stock < 0){
                $stock = 0;
            }

            $inventario = DB::queryFirstRow("

                SELECT

                    inventario_id,
                    stock_actual

                FROM pos_inventario

                WHERE product_id = %i

                LIMIT 1

            ", $product_id);

            if($inventario){

                DB::update(

                    'pos_inventario',

                    [

                        'stock_actual' =>
                            $stock

                    ],

                    "inventario_id=%i",

                    $inventario['inventario_id']

                );

            } else {

                DB::insert(

                    'pos_inventario',

                    [

                        'product_id' =>
                            $product_id,

                        'stock_actual' =>
                            $stock,

                        'neg_id' =>
                            $neg_id

                    ]

                );

            }

            /* ======================================
               MOVIMIENTO INVENTARIO
            ====================================== */

            DB::insert(

                'pos_inventario_movimiento',

                [

                    'product_id' =>
                        $product_id,

                    'tipo' =>
                        'AJUSTE',

                    'origen' =>
                        'EDICION',

                    'cantidad' =>
                        $stock,

                    'precio_unitario' =>
                        $price,

                    'fecha' =>
                        $now_dt,

                    'stock_resultante' =>
                        $stock,

                    'neg_id' =>
                        $neg_id

                ]

            );

            /* ======================================
               PRODUCTO RESPONSE
            ====================================== */

            $product = DB::queryFirstRow("

                SELECT

                    p.product_id,
                    p.cod_producto,
                    p.name,
                    p.description,
                    p.price,
                    p.is_visible,

                    IFNULL(
                        i.stock_actual,
                        0
                    ) AS stock

                FROM pos_product p

                LEFT JOIN pos_inventario i
                    ON i.product_id = p.product_id

                WHERE p.product_id = %i

                LIMIT 1

            ", $product_id);

            /* ======================================
               IMAGENES
            ====================================== */

            $imagenes = DB::query("

                SELECT

                    product_image_id,
                    img,
                    orden,
                    is_visible

                FROM pos_product_image

                WHERE product_id = %i

                AND borrado_el IS NULL

                ORDER BY orden ASC

            ", $product_id);

            foreach($imagenes as &$img){

                $img['product_image_id'] = intval(
                    $img['product_image_id']
                );

                $img['orden'] = intval(
                    $img['orden']
                );

                $img['is_visible'] = intval(
                    $img['is_visible']
                );

            }

            /* ======================================
               CATEGORIAS RESPONSE
            ====================================== */

            $cats = DB::query("

                SELECT

                    c.category_id,
                    c.name AS descripcion

                FROM pos_product_category pc

                INNER JOIN pos_category c
                    ON c.category_id =
                       pc.category_id

                WHERE pc.product_id = %i

                ORDER BY c.name ASC

            ", $product_id);

            foreach($cats as &$c){

                $c['category_id'] = intval(
                    $c['category_id']
                );

            }

            $product['product_id'] = intval(
                $product['product_id']
            );

            $product['price'] = floatval(
                $product['price']
            );

            $product['stock'] = intval(
                $product['stock']
            );

            $product['is_visible'] = intval(
                $product['is_visible']
            );

            $product['imagenes'] =
                $imagenes;

            $product['categorias'] =
                $cats;

            DB::commit();

            Flight::json([

                'status'=>'ok',

                'msg'=>'Producto actualizado correctamente',

                'editando'=>true,

                'product'=>$product

            ]);

            return;

        }

        /* ======================================
           CREAR PRODUCTO
        ====================================== */

        DB::insert(

            'pos_product',

            [

                'cod_producto' =>
                    'P'
                    .
                    str_pad(

                        rand(1,999999),

                        6,

                        '0',

                        STR_PAD_LEFT

                    ),

                'name' =>
                    $name,

                'tipo_producto' =>
                    'ABARROTES',

                'marca_des' =>
                    $marca_des,

                'price' =>
                    $price,

                'description' =>
                    $description,

                'fecha_creacion' =>
                    $now_dt,

                'fecha_modificacion' =>
                    $now_dt,

                'neg_id' =>
                    $neg_id,

                'subcategoria_id' => null,

                'is_visible' => 1,

                'borrado_el' => null

            ]

        );

        $product_id = DB::insertId();

        /* ======================================
           CATEGORÍAS
        ====================================== */

        if(!empty($categorias)){

            foreach($categorias as $cat){

                $category_id = is_array($cat)

                    ? intval(
                        $cat['category_id']
                        ?? 0
                    )

                    : intval($cat);

                if($category_id <= 0){
                    continue;
                }

                DB::insert(

                    'pos_product_category',

                    [

                        'product_id' =>
                            $product_id,

                        'category_id' =>
                            $category_id,

                        'neg_id' =>
                            $neg_id,

                        'is_visible' => 1

                    ]

                );

            }

        }

        /* ======================================
           IMAGEN DEFAULT
        ====================================== */

        DB::insert(

            'pos_product_image',

            [

                'product_id' =>
                    $product_id,

                'img' =>
                    'https://barsi-img.b-cdn.net/recursos/6qz5.png',

                'orden' => 1,

                'is_visible' => 1

            ]

        );

        /* ======================================
           INVENTARIO
        ====================================== */

        if($stock < 0){
            $stock = 0;
        }

        DB::insert(

            'pos_inventario',

            [

                'product_id' =>
                    $product_id,

                'stock_actual' =>
                    $stock,

                'neg_id' =>
                    $neg_id

            ]

        );

        /* ======================================
           MOVIMIENTO INVENTARIO
        ====================================== */

        DB::insert(

            'pos_inventario_movimiento',

            [

                'product_id' =>
                    $product_id,

                'tipo' =>
                    'AJUSTE',

                'origen' =>
                    'AJUSTE',

                'cantidad' =>
                    $stock,

                'precio_unitario' =>
                    $price,

                'fecha' =>
                    $now_dt,

                'stock_resultante' =>
                    $stock,

                'neg_id' =>
                    $neg_id

            ]

        );

        /* ======================================
           PRODUCTO RESPONSE
        ====================================== */

        $product = DB::queryFirstRow("

            SELECT

                p.product_id,
                p.cod_producto,
                p.name,
                p.description,
                p.price,
                p.is_visible,

                IFNULL(
                    i.stock_actual,
                    0
                ) AS stock

            FROM pos_product p

            LEFT JOIN pos_inventario i
                ON i.product_id = p.product_id

            WHERE p.product_id = %i

            LIMIT 1

        ", $product_id);

        /* ======================================
           IMAGENES
        ====================================== */

        $imagenes = DB::query("

            SELECT

                product_image_id,
                img,
                orden,
                is_visible

            FROM pos_product_image

            WHERE product_id = %i

            AND borrado_el IS NULL

            ORDER BY orden ASC

        ", $product_id);

        foreach($imagenes as &$img){

            $img['product_image_id'] = intval(
                $img['product_image_id']
            );

            $img['orden'] = intval(
                $img['orden']
            );

            $img['is_visible'] = intval(
                $img['is_visible']
            );

        }

        /* ======================================
           CATEGORIAS RESPONSE
        ====================================== */

        $cats = DB::query("

            SELECT

                c.category_id,
                c.name AS descripcion

            FROM pos_product_category pc

            INNER JOIN pos_category c
                ON c.category_id =
                   pc.category_id

            WHERE pc.product_id = %i

            ORDER BY c.name ASC

        ", $product_id);

        foreach($cats as &$c){

            $c['category_id'] = intval(
                $c['category_id']
            );

        }

        $product['product_id'] = intval(
            $product['product_id']
        );

        $product['price'] = floatval(
            $product['price']
        );

        $product['stock'] = intval(
            $product['stock']
        );

        $product['is_visible'] = intval(
            $product['is_visible']
        );

        $product['imagenes'] =
            $imagenes;

        $product['categorias'] =
            $cats;

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status'=>'ok',

            'msg'=>'Producto creado correctamente',

            'editando'=>false,

            'product'=>$product

        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

Flight::route('POST /ZnO3/eliminarProducto', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $product_id = intval(
        $d['product_id'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$product_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'product_id requerido'

        ],400);

        return;
    }

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           VALIDAR PRODUCTO
        ====================================== */

        $producto = DB::queryFirstRow("

            SELECT

                product_id,
                name,
                borrado_el

            FROM pos_product

            WHERE product_id = %i

            LIMIT 1

        ", $product_id);

        if(!$producto){

            Flight::json([

                'status' => 'error',

                'msg' => 'Producto no encontrado'

            ],404);

            return;
        }

        /* ======================================
           ELIMINAR (SOFT DELETE)
        ====================================== */

        DB::update(

            'pos_product',

            [

                'borrado_el' =>
                    $now,

                'fecha_modificacion' =>
                    $now

            ],

            "product_id=%i",

            $product_id

        );

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Producto eliminado correctamente',

            'product_id' =>
                $product_id,

            'borrado_el' =>
                $now

        ]);

    } catch(Exception $e){

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});

Flight::route('POST /IlY2/eliminarCategoria', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $category_id = intval(
        $d['category_id'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$category_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'category_id requerido'

        ],400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           VALIDAR CATEGORIA
        ====================================== */

        $categoria = DB::queryFirstRow("

            SELECT

                category_id,
                name,
                neg_id,
                borrado_el

            FROM pos_category

            WHERE category_id = %i

            LIMIT 1

        ", $category_id);

        if(!$categoria){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Categoría no encontrada'

            ],404);

            return;
        }

        /* ======================================
           SOFT DELETE CATEGORIA
        ====================================== */

        DB::update(

            'pos_category',

            [

                'borrado_el' =>
                    $now

            ],

            "category_id=%i",

            $category_id

        );

        /* ======================================
           PRODUCTOS DE LA CATEGORIA
        ====================================== */

        $productos = DB::query("

            SELECT DISTINCT

                p.product_id

            FROM pos_product_category pc

            INNER JOIN pos_product p
                ON p.product_id =
                   pc.product_id

            WHERE pc.category_id = %i

            AND p.borrado_el IS NULL

        ", $category_id);

        $productos_afectados = [];

        /* ======================================
           SOFT DELETE PRODUCTOS
        ====================================== */

        foreach($productos as $p){

            $product_id = intval(
                $p['product_id']
            );

            $productos_afectados[] =
                $product_id;

            DB::update(

                'pos_product',

                [

                    'borrado_el' =>
                        $now,

                    'fecha_modificacion' =>
                        $now

                ],

                "product_id=%i",

                $product_id

            );

        }

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Categoría eliminada correctamente',

            'category_id' =>
                $category_id,

            'productos_afectados' =>
                $productos_afectados,

            'cantidad_productos' =>
                count(
                    $productos_afectados
                ),

            'borrado_el' =>
                $now

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});

Flight::route('POST /KgOS/ocultarCategoria', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $category_id = intval(
        $d['category_id'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$category_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'category_id requerido'

        ],400);

        return;
    }

    try {

        /* ======================================
           VALIDAR CATEGORIA
        ====================================== */

        $categoria = DB::queryFirstRow("

            SELECT

                category_id,
                name,
                is_activo,
                borrado_el

            FROM pos_category

            WHERE category_id = %i

            LIMIT 1

        ", $category_id);

        if(!$categoria){

            Flight::json([

                'status' => 'error',

                'msg' => 'Categoría no encontrada'

            ],404);

            return;
        }

        if(
            !is_null(
                $categoria['borrado_el']
            )
        ){

            Flight::json([

                'status' => 'error',

                'msg' => 'La categoría está eliminada'

            ],400);

            return;
        }

        /* ======================================
           TOGGLE
        ====================================== */

        $nuevo_estado = intval(
            !$categoria['is_activo']
        );

        DB::update(

            'pos_category',

            [

                'is_activo' =>
                    $nuevo_estado

            ],

            "category_id=%i",

            $category_id

        );

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>

                $nuevo_estado
                ?

                'Categoría activada'

                :

                'Categoría ocultada',

            'category_id' =>
                $category_id,

            'is_activo' =>
                $nuevo_estado

        ]);

    } catch(Exception $e){

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});