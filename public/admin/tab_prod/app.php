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

            c.is_activo,

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
        p.variantes_json,
            p.is_visible,
            p.compra_total,
            p.cantidad_comprada,
p.variantes_json,
            p.precio_volumen_json,

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

        $variantes = [
            'atributo' => '',
            'opciones' => []
        ];

        if(
            !empty(
                $p['variantes_json']
            )
        ){

            $variantes_decoded = json_decode(
                $p['variantes_json'],
                true
            );

            if(
                is_array(
                    $variantes_decoded
                )
            ){

                $variantes = $variantes_decoded;

            }

        }

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

        $precio_volumen = [];

        if(
            !empty(
                $p['precio_volumen_json']
            )
        ){

            $precio_volumen = json_decode(

                $p['precio_volumen_json'],

                true

            );

            if(
                !is_array(
                    $precio_volumen
                )
            ){
                $precio_volumen = [];
            }
        }


        $variantes = [

                'atributo' => '',

                'opciones' => []

            ];

            if(
                !empty(
                    $p['variantes_json']
                )
            ){

                $variantes = json_decode(

                    $p['variantes_json'],

                    true

                );

                if(
                    !is_array(
                        $variantes
                    )
                ){

                    $variantes = [

                        'atributo' => '',

                        'opciones' => []

                    ];

                }

            }

        $map[$cid][] = [

            'product_id' => intval(
                $p['product_id']
            ),

            'cod_producto' =>
                $p['cod_producto'],

            'variantes_json' =>
                $p['variantes_json'],

            'variantes' =>
                $variantes,                

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

            'compra_total' => floatval(
                $p['compra_total']
            ),

            'cantidad_comprada' => intval(
                $p['cantidad_comprada']
            ),

            'precio_volumen' =>
                $precio_volumen,

            'variantes_json' =>
                $p['variantes_json'],

            'variantes' =>
                $variantes,                

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

    if($neg_id <= 0){

        Flight::json([

            'status' => 'ok',

            'data' => [

                'redirect' => 'mod_404'

            ]

        ]);

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

                'status' => 'ok',

                'neg_id' => $neg_id,

                'data' => [

                    'redirect' => 'mod_404'

                ]

            ]);

            return;
        }

        /* ============================================================
           VALIDAR MEMBRESIA
        ============================================================ */

        $membresia = veri_membresia_neg(
            $negocio['neg_id']
        );

        if(

            !$membresia

            ||

            intval(
                $membresia['is_aprobado']
            ) !== 1

        ){

            Flight::json([

                'status' => 'ok',

                'neg_id' => $neg_id,

                'data' => [

                    'redirect' => 'mod_404'

                ]

            ]);

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
            p.compra_total,
p.variantes_json,
            p.cantidad_comprada,

            p.precio_volumen_json,

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

        $precio_volumen = [];

        if(
            !empty(
                $p['precio_volumen_json']
            )
        ){

            $precio_volumen = json_decode(

                $p['precio_volumen_json'],

                true

            );

            if(
                !is_array(
                    $precio_volumen
                )
            ){
                $precio_volumen = [];
            }
        }

        $variantes = [

            'atributo' => '',

            'opciones' => []

        ];

        if(
            !empty(
                $p['variantes_json']
            )
        ){

            $variantes = json_decode(

                $p['variantes_json'],

                true

            );

            if(
                !is_array(
                    $variantes
                )
            ){

                $variantes = [

                    'atributo' => '',

                    'opciones' => []

                ];

            }

        }

        $map[$cid][] = [

            'product_id' => intval(
                $p['product_id']
            ),

            'name' => $p['name'],

            'price' => floatval(
                $p['price']
            ),

            'compra_total' => floatval(
                $p['compra_total']
            ),

            'cantidad_comprada' => intval(
                $p['cantidad_comprada']
            ),

            'precio_volumen' =>
                $precio_volumen,

            'variantes_json' =>
                $p['variantes_json'],

            'variantes' =>
                $variantes,    

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
       TEMA DEL NEGOCIO
    ============================================================ */

    $tema = DB::queryFirstRow("

        SELECT

            t.tema_id,
            t.nombre_tema,
            t.topnavbar,
            t.fondo,
            t.boton,
            t.fondo_card,
            t.subtitulo

        FROM reg_temaxneg txn

        INNER JOIN reg_tema t
            ON t.tema_id = txn.tema_id

        WHERE txn.neg_id = %i

        ORDER BY txn.temaxneg_id DESC

        LIMIT 1

    ", $neg_id);

    if(!$tema){

        $tema = [

            'tema_id'      => 0,
            'nombre_tema'  => '',
            'topnavbar'    => '',
            'fondo'        => '',
            'boton'        => '',
            'subtitulo'        => '',
            'fondo_card'   => ''

        ];

    }    

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

            'tema' => $tema,

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

    $variantes_json = trim(
    $d['variantes_json'] ?? ''
);

if($variantes_json === ''){

    $variantes_json = null;

}else{

    json_decode($variantes_json, true);

    if(json_last_error() !== JSON_ERROR_NONE){

        Flight::json([
            'status'=>'error',
            'msg'=>'variantes_json inválido'
        ],400);

        return;
    }

}

    $categorias = $d['categorias'] ?? [];

    /* ======================================
       VALIDAR
    ====================================== */

    if($neg_id <= 0){

        Flight::json([

            'status' => 'ok',

            'data' => [

                'redirect' => 'mod_404'

            ]

        ]);

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

                    'variantes_json' =>
                        $variantes_json,

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

                'variantes_json' =>
                    $variantes_json,


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

/* =========================================================
   GUARDAR IMAGEN PRODUCTO
========================================================= */

Flight::route('POST /M4LT/guardarImagenProducto', function () {

    try {

        include DEFINITION;

        DB::query("SET NAMES 'utf8mb4'");

        $d = json_decode(
            Flight::request()->getBody(),
            true
        ) ?: [];

        /* =========================================
           FIRMA
        ========================================= */

        $xin  = $d['xin'] ?? '';
        $yuan = $d['yuan'] ?? '';

        firma($xin, $yuan);

        /* =========================================
           PAYLOAD
        ========================================= */

        $product_id = intval(
            $d['product_id'] ?? 0
        );

        $url_imagen = trim(
            $d['url_imagen'] ?? ''
        );

        if(!$product_id){

            Flight::json([

                'status' => 'error',

                'msg' => 'product_id requerido'

            ],400);

            return;

        }

        if(!$url_imagen){

            Flight::json([

                'status' => 'error',

                'msg' => 'url_imagen requerida'

            ],400);

            return;

        }

        /* =========================================
           VALIDAR PRODUCTO
        ========================================= */

        $producto = DB::queryFirstRow("

            SELECT

                product_id,
                cod_producto

            FROM pos_product

            WHERE product_id = %i

            AND borrado_el IS NULL

            LIMIT 1

        ", $product_id);

        if(!$producto){

            Flight::json([

                'status' => 'error',

                'msg' => 'Producto no encontrado'

            ],404);

            return;

        }

        /* =========================================
           MOVER ORDENES EXISTENTES
           +1 A TODOS
        ========================================= */

        DB::query("

            UPDATE pos_product_image

            SET orden = orden + 1

            WHERE product_id = %i

            AND borrado_el IS NULL

        ", $product_id);

        /* =========================================
           INSERTAR NUEVA FOTO
           COMO PRINCIPAL
        ========================================= */

        DB::insert(

            'pos_product_image',

            [

                'product_id' =>

                    $product_id,

                'cod_producto' =>

                    $producto['cod_producto'],

                'img' =>

                    $url_imagen,

                'orden' =>

                    1,

                'is_visible' =>

                    1,

                'fecha_creacion' =>

                    date('Y-m-d H:i:s'),

                'borrado_el' =>

                    null

            ]

        );

        $product_image_id = DB::insertId();

        /* =========================================
           RESPONSE
        ========================================= */

        Flight::json([

            'status' => 'ok',

            'product_image_id' =>

                $product_image_id,

            'product_id' =>

                $product_id,

            'img' =>

                $url_imagen,

            'orden' =>

                1

        ]);

    } catch (Exception $e) {

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage(),

            'line' => $e->getLine(),

            'file' => $e->getFile()

        ],500);

    }

});


Flight::route('POST /CK2f/eliminarFotoProducto', function(){

    include DEFINITION;

    $json = Flight::request()->getBody();

    $data = json_decode($json);

    /* ======================================
       FIRMA
    ====================================== */

    $xin = $data->xin ?? null;

    $yuan = $data->yuan ?? null;

    firma($xin, $yuan);

    /* ======================================
       PAYLOAD
    ====================================== */

    $product_image_id = intval(
        $data->product_image_id ?? 0
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($product_image_id <= 0){

        echo json_encode([

            "res" => "error",

            "msg" => "product_image_id inválido"

        ]);

        return;
    }

    /* ======================================
       FOTO
    ====================================== */

    $info_foto = DB::queryFirstRow("

        SELECT *

        FROM pos_product_image

        WHERE product_image_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $product_image_id);

    if(!$info_foto){

        echo json_encode([

            "res" => "error",

            "msg" => "Foto no encontrada"

        ]);

        return;
    }

    /* ======================================
       SOFT DELETE
    ====================================== */

    DB::update(

        'pos_product_image',

        [

            'borrado_el' => date(
                'Y-m-d H:i:s'
            )

        ],

        'product_image_id=%i',

        $product_image_id

    );

    /* ======================================
       RESPONSE
    ====================================== */

    echo json_encode([

        "res" => "ok",

        "msg" => "Foto eliminada"

    ]);

});

Flight::route('POST /QVhq/ordenarFotoProducto', function(){

    include DEFINITION;

    $json = Flight::request()->getBody();

    $data = json_decode($json);

    /* ======================================
       FIRMA
    ====================================== */

    $xin = $data->xin ?? null;

    $yuan = $data->yuan ?? null;

    firma($xin, $yuan);

    /* ======================================
       PAYLOAD
    ====================================== */

    $lista = $data->lista ?? [];

    /* ======================================
       VALIDAR
    ====================================== */

    if(

        !is_array($lista)

        ||

        empty($lista)

    ){

        echo json_encode([

            "res" => "error",

            "msg" => "Lista inválida"

        ]);

        return;
    }

    /* ======================================
       RECORRER
    ====================================== */

    foreach($lista as $item){

        $product_image_id = intval(
            $item->product_image_id ?? 0
        );

        $orden = intval(
            $item->orden ?? 0
        );

        if($product_image_id <= 0){
            continue;
        }

        DB::update(

            'pos_product_image',

            [

                'orden' => $orden

            ],

            '

                product_image_id=%i

                AND borrado_el IS NULL

            ',

            $product_image_id

        );

    }

    /* ======================================
       RESPONSE
    ====================================== */

    echo json_encode([

        "res" => "ok",

        "msg" => "Orden actualizado"

    ]);

});


Flight::route('POST /W4ta/calculadoraPrecio', function(){

    try{

        include DEFINITION;

        DB::query("SET NAMES 'utf8mb4'");

        $d = json_decode(

            Flight::request()->getBody(),

            true

        ) ?: [];

        /* =====================================
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

        /* =====================================
           PARAMETROS
        ====================================== */

        $product_id = intval(
            $d['product_id'] ?? 0
        );

        $neg_id = intval(
            $d['neg_id'] ?? 0
        );

        $compra_total = floatval(
            $d['compra_total'] ?? 0
        );

        $cantidad_comprada = intval(
            $d['cantidad_comprada'] ?? 0
        );

        $rangos = $d['rangos'] ?? [];

        if(
            $product_id <= 0
            ||
            $neg_id <= 0
        ){

            Flight::json([

                'status' => 'error',

                'msg' => 'Parámetros inválidos'

            ], 400);

            return;

        }

        /* =====================================
           PRODUCTO
        ====================================== */

        $producto = DB::queryFirstRow("

            SELECT

                product_id,
                neg_id

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

            Flight::json([

                'status' => 'error',

                'msg' => 'Producto no encontrado'

            ], 404);

            return;

        }

        /* =====================================
           COSTO BASE
        ====================================== */

        $costo_unitario_base = 0;

        if(
            $cantidad_comprada > 0
        ){

            $costo_unitario_base = round(

                $compra_total
                /
                $cantidad_comprada,

                2

            );

        }

        $precio_unidad = 0;

        $rangos_final = [];

        /* =====================================
           CALCULAR
        ====================================== */

        foreach($rangos as $r){

            $cantidad_desde = intval(
                $r['cantidad_desde']
            );

            $nombre_rango = trim(
                $r['nombre_rango']
            );

            $ganancia = floatval(
                $r['ganancia_porcentaje']
            );

            $precio_unitario = round(

                $costo_unitario_base

                +

                (
                    $costo_unitario_base
                    *
                    $ganancia
                    /
                    100
                ),

                2

            );

            if(
                $cantidad_desde == 1
            ){

                $precio_unidad =
                    $precio_unitario;

            }

            $rangos_final[] = [

                'cantidad_desde' =>
                    $cantidad_desde,

                'nombre_rango' =>
                    $nombre_rango,

                'ganancia_porcentaje' =>
                    $ganancia,

                'precio_unitario_calculado' =>
                    $precio_unitario

            ];

        }

        /* =====================================
           GUARDAR EN PRODUCTO
        ====================================== */

        DB::update(

            'pos_product',

            [

                'price' =>
                    $precio_unidad,

                'compra_total' =>
                    $compra_total,

                'cantidad_comprada' =>
                    $cantidad_comprada,

                'precio_volumen_json' =>
                    json_encode(

                        $rangos_final,

                        JSON_UNESCAPED_UNICODE

                    ),

                'fecha_modificacion' =>
                    date('Y-m-d H:i:s')

            ],

            '

                product_id=%i

            ',

            $product_id

        );

        /* =====================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'product_id' =>
                $product_id,

            'neg_id' =>
                $neg_id,

            'compra_total' =>
                $compra_total,

            'cantidad_comprada' =>
                $cantidad_comprada,

            'costo_unitario_base' =>
                $costo_unitario_base,

            'precio_unidad_actualizado' =>
                $precio_unidad,

            'rangos' =>
                $rangos_final

        ]);

    }
    catch(Throwable $e){

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage(),

            'line' => $e->getLine(),

            'file' => $e->getFile()

        ], 500);

    }

});


Flight::route('POST /V8ke/calculadoraPrecioDetalle', function(){

    try{

        include DEFINITION;

        DB::query("SET NAMES 'utf8mb4'");

        $d = json_decode(

            Flight::request()->getBody(),

            true

        ) ?: [];

        /* =====================================
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

        /* =====================================
           PARAMETROS
        ====================================== */

        $product_id = intval(
            $d['product_id'] ?? 0
        );

        $neg_id = intval(
            $d['neg_id'] ?? 0
        );

        if(
            $product_id <= 0
            ||
            $neg_id <= 0
        ){

            Flight::json([

                'status' => 'error',

                'msg' => 'Parámetros inválidos'

            ], 400);

            return;

        }

        /* =====================================
           PRODUCTO
        ====================================== */

        $producto = DB::queryFirstRow("

            SELECT

                product_id,
                neg_id,
                name,
                price,

                compra_total,
                cantidad_comprada,
                precio_volumen_json

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

            Flight::json([

                'status' => 'error',

                'msg' => 'Producto no encontrado'

            ], 404);

            return;

        }

        /* =====================================
           COSTO BASE
        ====================================== */

        $compra_total = floatval(
            $producto['compra_total'] ?? 0
        );

        $cantidad_comprada = intval(
            $producto['cantidad_comprada'] ?? 0
        );

        $costo_unitario_base = 0;

        if(
            $cantidad_comprada > 0
        ){

            $costo_unitario_base = round(

                $compra_total
                /
                $cantidad_comprada,

                2

            );

        }

        /* =====================================
           RANGOS
        ====================================== */

        $rangos = [];

        if(
            !empty(
                $producto['precio_volumen_json']
            )
        ){

            $rangos = json_decode(

                $producto['precio_volumen_json'],

                true

            );

            if(!is_array($rangos)){

                $rangos = [];

            }

        }

        /* =====================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'product_id' =>
                intval(
                    $producto['product_id']
                ),

            'neg_id' =>
                intval(
                    $producto['neg_id']
                ),

            'producto_nombre' =>
                $producto['name'],

            'compra_total' =>
                $compra_total,

            'cantidad_comprada' =>
                $cantidad_comprada,

            'costo_unitario_base' =>
                $costo_unitario_base,

            'precio_unidad_actualizado' =>
                floatval(
                    $producto['price']
                ),

            'rangos' =>
                $rangos

        ]);

    }
    catch(Throwable $e){

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage(),

            'line' => $e->getLine(),

            'file' => $e->getFile()

        ], 500);

    }

});

