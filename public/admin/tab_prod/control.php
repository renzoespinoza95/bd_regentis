<?php
// backend php8.2 + flightphp + meekrodb2

Flight::route('GET /prod', function () {

    include DEFINITION;
    autentificar_administrador();

    global $path_public;

    include $path_public . '/admin/tab_prod/inicio.php';
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
            'created_at'         => $now_unix,
            'last_update'        => $now_unix,
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
            'img'=>'sin_foto.jpg'
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
            'last_update'=>$now_unix,
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
            'priority'=>0,
            'created_at'=>$now_unix,
            'last_update'=>$now_unix
        ]);

        Flight::json(['status'=>'ok']);

    }catch(Exception $e){

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

Flight::route('GET /pet/product/buscar_global', function(){

    include DEFINITION;
    autentificar_administrador();

    $q = trim(Flight::request()->query['q']);

    if(strlen($q) < 4){
        Flight::json([]);
        return;
    }

    $rows = DB::query("
        SELECT 
            cod_prod_plazavea,
            nombre,
            categoria_global_id
        FROM prod_plazavea
        WHERE nombre LIKE %ss
        ORDER BY nombre
        LIMIT 100
    ", $q);

    Flight::json($rows);

});