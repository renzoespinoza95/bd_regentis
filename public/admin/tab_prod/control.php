<?php
// backend php8.2 + flightphp + meekrodb2

Flight::route('GET /prod', function () {

    include DEFINITION;
    autentificar_administrador();
    require_once VARPATH . '/public/admin/tab_prod/inicio.php';
});


/* ============================================================
   LISTAR PRODUCTOS
   ============================================================ */
Flight::route('GET /product/listar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

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
    ",$neg_id);

    foreach ($rows as &$r) {
        $r['categories_ids'] = $r['categories_ids']
            ? array_map('intval', explode(',', $r['categories_ids']))
            : [];
    }

    Flight::json($rows);
});


/* ============================================================
   LISTAR CATEGORÍAS
   ============================================================ */
Flight::route('GET /product/listar_categorias', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $rows = DB::query("
        SELECT category_id, name AS descripcion
        FROM pos_category
        WHERE neg_id=%i
        ORDER BY name ASC
    ",$neg_id);

    Flight::json($rows);
});


/* ============================================================
   CREAR PRODUCTO
   ============================================================ */
Flight::route('POST /product/crear', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data->getData();

    $now_unix = time()*1000;
    $now_dt   = date("Y-m-d H:i:s");

    DB::startTransaction();

    try {

        DB::insert('pos_product',[
            'neg_id'             => $neg_id,
            'name'               => $d['name'],
            'price'              => $d['price'],
            'price_discount'     => 0,
            'description'        => $d['description'],
            'fecha_creacion'     => $now_dt,
            'fecha_modificacion' => $now_dt
        ]);

        $product_id = DB::insertId();


        if(!empty($d['categorias'])){
            foreach($d['categorias'] as $cat){

                $category_id = is_array($cat)
                    ? intval($cat['category_id'])
                    : intval($cat);

                DB::insert('pos_product_category',[
                    'product_id'=>$product_id,
                    'category_id'=>$category_id
                ]);
            }
        }


        DB::insert('pos_product_image',[
            'product_id'=>$product_id,
            'img'=>'https://barsi-img.b-cdn.net/recursos/logo-regentis.png'
        ]);

        DB::commit();

        Flight::json(['status'=>'ok']);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});


/* ============================================================
   EDITAR PRODUCTO
   ============================================================ */
Flight::route('POST /product/editar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data->getData();

    $now_unix = time()*1000;
    $now_dt   = date("Y-m-d H:i:s");

    DB::startTransaction();

    try{

        DB::update('pos_product',[
            'name'=>$d['name'],
            'price'=>$d['price'],
            'description'=>$d['description'],
            'fecha_modificacion'=>$now_dt
        ],"product_id=%i AND neg_id=%i",$d['product_id'],$neg_id);


        DB::delete('pos_product_category',"product_id=%i",$d['product_id']);


        if(!empty($d['categorias'])){
            foreach($d['categorias'] as $cat){

                DB::insert('pos_product_category',[
                    'product_id'=>$d['product_id'],
                    'category_id'=>intval($cat['category_id'])
                ]);
            }
        }

        DB::commit();

        Flight::json(['status'=>'ok']);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});


/* ============================================================
   DETALLE PRODUCTO
   ============================================================ */
Flight::route('GET /product/detalle/@product_id', function ($product_id) {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual,$varhost;

    $neg_id = intval($administrador_actual['neg_id']);

    $p = DB::queryFirstRow("
        SELECT p.*,
               IFNULL(i.stock_actual,0) AS stock
        FROM pos_product p
        LEFT JOIN pos_inventario i ON i.product_id=p.product_id
        WHERE p.product_id=%i
        AND p.neg_id=%i
    ",$product_id,$neg_id);


    if(!$p){
        Flight::json(['status'=>'error','msg'=>'No existe'],404);
        return;
    }


    $cats = DB::query("
        SELECT c.category_id,c.name AS descripcion
        FROM pos_product_category pc
        JOIN pos_category c ON c.category_id=pc.category_id
        WHERE pc.product_id=%i
    ",$product_id);


    $imgs = DB::query("
        SELECT name
        FROM pos_product_image
        WHERE product_id=%i
    ",$product_id);


    $base = $varhost . vari('IMG_PRODUCTO');

    foreach($imgs as &$img){
        $img['image'] = $base . $img['name'];
    }

    $p['categories']=$cats;
    $p['images']=$imgs;

    Flight::json($p);
});


/* ============================================================
   ELIMINAR PRODUCTO
   ============================================================ */
Flight::route('POST /product/eliminar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $neg_id=intval($administrador_actual['neg_id']);

    $d=Flight::request()->data->getData();

    DB::delete(
        'pos_product',
        "product_id=%i AND neg_id=%i",
        $d['product_id'],
        $neg_id
    );

    Flight::json(['status'=>'ok']);
});


/* ============================================================
   CREAR CATEGORIA
   ============================================================ */
Flight::route('POST /product/categoria_crear', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $neg_id=intval($administrador_actual['neg_id']);

    $d=Flight::request()->data->getData();

    if(!isset($d['name']) || trim($d['name'])==''){
        Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400);
        return;
    }

    $now_unix=time()*1000;

    try{

        DB::insert('pos_category',[
            'neg_id'=>$neg_id,
            'name'=>$d['name'],
            'icon'=>$d['icon'],
            'brief'=>$d['brief'],
            'color'=>$d['color'],
            'priority'=>0
        ]);

        Flight::json(['status'=>'ok']);

    }catch(Exception $e){

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

function normalizar($txt){
    $txt = strtolower($txt);
    $txt = str_replace(
        ['á','é','í','ó','ú','ñ','z'],
        ['a','e','i','o','u','n','s'],
        $txt
    );
    return $txt;
}

function limpiarBusqueda($txt){

    $txt = strtolower($txt);

    // 🔥 eliminar tildes y ñ
    $txt = str_replace(
        ['á','é','í','ó','ú','ñ'],
        ['a','e','i','o','u','n'],
        $txt
    );

    // 🔥 eliminar TODO lo que no sea letras o números
    $txt = preg_replace('/[^a-z0-9\s]/', ' ', $txt);

    // 🔥 quitar espacios repetidos
    $txt = preg_replace('/\s+/', ' ', $txt);

    return trim($txt);
}

Flight::route('GET /pet/product/buscar_global', function(){

    include DEFINITION;
    autentificar_administrador();

    $q = trim(Flight::request()->query['q'] ?? '');

    $q_limpio = limpiarBusqueda($q);

    $q_normal = normalizar($q_limpio);

    if(strlen($q) < 3){
        Flight::json([]);
        return;
    }

    // 🔥 transformar búsqueda tipo Google
    $words = explode(' ', $q_normal);
    $search = '';

    foreach($words as $w){
        $search .= '+' . $w . '* '; // obligatorio + prefijo *
    }

    $limit = 100;

    $rows = DB::query("
        SELECT 
            p.cod_prod_plazavea,
            p.nombre,
            p.precio,
            p.categoria_global_id,
            cg.nombre AS categoria_nombre
        FROM prod_plazavea p
        LEFT JOIN reg_categoria_global cg 
            ON cg.categoria_global_id = p.categoria_global_id
        WHERE 
            MATCH(p.nombre) AGAINST (%s IN BOOLEAN MODE)
            OR LOWER(p.nombre) LIKE %s
        LIMIT %i
    ", trim($search), "%$q_normal%", $limit);

    // 🔥 normalizar
    foreach($rows as &$r){
        $r['precio'] = (float)$r['precio'];
        $r['categoria_global_id'] = (int)$r['categoria_global_id'];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;

});

function categorias_nuevo_negocio($neg_id){

    $now_unix = time()*1000;

    $cats = DB::query("
        SELECT *
        FROM reg_categoria_global
        WHERE is_activo=1
        ORDER BY orden ASC
    ");

    foreach($cats as $c){

        DB::insert('pos_category',[
            'neg_id'      => $neg_id,
            'name'        => $c['nombre'],
            'icon'        => $c['icono'],
            'priority'    => $c['orden'],
            'categoria_global_id'    => $c['categoria_global_id']
        ]);

    }

}

Flight::route('POST /tito/producto/agregar', function(){

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data->getData();

    $now_unix = time()*1000;
    $now_dt   = date("Y-m-d H:i:s");

    DB::startTransaction();

    try{

        /* =============================
           1. VALIDAR
        ============================== */

        if(
            !isset($d['name']) ||
            trim($d['name']) == ''
        ){

            Flight::json([
                'status'=>'error',
                'msg'=>'Nombre requerido'
            ],400);

            return;
        }

        /* =============================
           2. CREAR PRODUCTO
        ============================== */

        DB::insert('pos_product',[
            'neg_id'             => $neg_id,
            'name'               => trim($d['name']),
            'price'              => floatval($d['price'] ?? 0),
            'price_discount'     => 0,
            'description'        => trim($d['description'] ?? ''),
            'fecha_creacion'     => $now_dt,
            'fecha_modificacion' => $now_dt
        ]);

        $product_id = DB::insertId();


        /* =============================
           3. CATEGORÍAS
        ============================== */

        if(!empty($d['categorias'])){

            foreach($d['categorias'] as $cat){

                $category_id = is_array($cat)
                    ? intval($cat['category_id'])
                    : intval($cat);

                if($category_id <= 0){
                    continue;
                }

                DB::insert('pos_product_category',[
                    'product_id'  => $product_id,
                    'category_id' => $category_id,
                    'neg_id'      => $neg_id,
                    'is_visible'  => 1
                ]);
            }
        }


        /* =============================
           4. IMAGEN DEFAULT
        ============================== */

        DB::insert('pos_product_image',[
            'product_id' => $product_id,
            'img'        => 'https://barsi-img.b-cdn.net/recursos/6qz5.png',
            'orden'      => 0,
            'is_visible' => 1
        ]);


        /* =============================
           5. INVENTARIO AJUSTE
        ============================== */

        $stock = intval($d['stock'] ?? 0);

        if($stock < 0){
            $stock = 0;
        }

        $price_ref = floatval($d['price_ref'] ?? 0);


        /* =============================
           6. CREAR INVENTARIO SI NO EXISTE
        ============================== */

        $inv = DB::queryFirstRow("
            SELECT *
            FROM pos_inventario
            WHERE product_id=%i
            AND neg_id=%i
        ", $product_id, $neg_id);


        if(!$inv){

            DB::insert('pos_inventario',[
                'product_id'   => $product_id,
                'stock_actual' => $stock,
                'stock_min'    => 0,
                'stock_max'    => 999999,
                'neg_id'       => $neg_id
            ]);

        }else{

            DB::update(
                'pos_inventario',
                [
                    'stock_actual' => $stock
                ],
                "inventario_id=%i",
                $inv['inventario_id']
            );
        }


        /* =============================
           7. MOVIMIENTO INVENTARIO
        ============================== */

        DB::insert('pos_inventario_movimiento',[
            'product_id'        => $product_id,
            'tipo'              => 'AJUSTE',
            'origen'            => 'AJUSTE',
            'cantidad'          => $stock,
            'precio_unitario'   => $price_ref,
            'fecha'             => $now_dt,
            'referencia_id'     => $product_id,
            'referencia_tabla'  => 'pos_product',
            'stock_resultante'  => $stock,
            'neg_id'            => $neg_id
        ]);


        DB::commit();

        Flight::json([
            'status'    => 'ok',
            'product_id'=> $product_id,
            'stock'     => $stock
        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }

});
