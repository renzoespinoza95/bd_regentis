<?php
// este es mi backend usando php8.1, flightphp y meekrodb2 

Flight::route('GET /inventario', function () {

    include DEFINITION;
    autentificar_administrador();

    require_once VARPATH . '/public/admin/tab_inventario/inicio.php';
});


/* ============================================================
 * LISTAR INVENTARIO
 * ============================================================ */
Flight::route('GET /inventario/listar', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $sql = "
        SELECT 
            i.inventario_id,
            i.product_id,
            p.name AS producto,
            i.stock_actual            
        FROM pos_inventario i
        INNER JOIN pos_product p ON p.product_id = i.product_id
        WHERE i.neg_id=%i
        ORDER BY p.name ASC
    ";

    Flight::json(DB::query($sql,$neg_id));
});


/* ============================================================
 * LISTAR PRODUCTOS (para combos)
 * ============================================================ */
Flight::route('GET /inventario/productos', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $rows = DB::query("
        SELECT product_id,name
        FROM pos_product
        WHERE neg_id=%i
        ORDER BY name ASC
    ",$neg_id);

    Flight::json($rows);
});


/* ============================================================
 * CREAR INVENTARIO
 * ============================================================ */
Flight::route('POST /inventario/crear', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data;

    DB::insert("pos_inventario",[
        "neg_id"=>$neg_id,
        "product_id"=>$d["product_id"],
        "stock_actual"=>$d["stock_actual"]        
    ]);

    Flight::json(["status"=>"ok"]);
});


/* ============================================================
 * DETALLE INVENTARIO (trae movimientos)
 * ============================================================ */
Flight::route('GET /inventario/detalle/@id', function ($id) {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $inv = DB::queryFirstRow("
        SELECT 
            i.*, 
            p.name AS producto
        FROM pos_inventario i
        INNER JOIN pos_product p ON p.product_id = i.product_id
        WHERE i.inventario_id=%i
        AND p.neg_id=%i
    ", $id,$neg_id);


    if(!$inv){
        Flight::json(['status'=>'error','msg'=>'Inventario no encontrado'],404);
        return;
    }


    $mov = DB::query("
        SELECT *
        FROM pos_inventario_movimiento
        WHERE product_id=%i
        ORDER BY fecha DESC
    ",$inv['product_id']);


    Flight::json([
        'inventario'=>$inv,
        'movimientos'=>$mov
    ]);
});


/* ============================================================
 * EDITAR INVENTARIO
 * ============================================================ */
Flight::route('POST /inventario/editar', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data;

    DB::update("pos_inventario",[
        "stock_min"=>$d["xx"],
        "stock_max"=>$d["stoxxck_max"]
    ],"inventario_id=%i AND neg_id=%i",$d["inventario_id"],$neg_id);

    Flight::json(["status"=>"ok"]);
});


/* ============================================================
 * ELIMINAR INVENTARIO
 * ============================================================ */
Flight::route('POST /inventario/eliminar', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $id = intval(Flight::request()->data->inventario_id);

    DB::delete(
        "pos_inventario",
        "inventario_id=%i AND neg_id=%i",
        $id,
        $neg_id
    );

    Flight::json(["status"=>"ok"]);
});


/* ============================================================
 * REGISTRAR MOVIMIENTO
   BODY:
   {
     product_id: 3,
     tipo: 'ENTRADA',
     origen: 'AJUSTE',
     cantidad: 10,
     precio_unitario: 5,
     referencia_id: null,
     referencia_tabla: null
   }
 * ============================================================ */
Flight::route('POST /inventario/movimiento', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $d = Flight::request()->data;
    $prod = intval($d['product_id']);
    $cant = intval($d['cantidad']);


    /* Obtener inventario */
    $inv = DB::queryFirstRow("
        SELECT *
        FROM pos_inventario
        WHERE product_id=%i
        AND neg_id=%i
    ",$prod,$neg_id);


    if(!$inv){
        Flight::json(['status'=>'error','msg'=>'Inventario no encontrado'],400);
        return;
    }


    $nuevo_stock = $inv['stock_actual'];

    if($d['tipo']==='ENTRADA'){
        $nuevo_stock += $cant;
    }
    elseif($d['tipo']==='SALIDA'){
        $nuevo_stock -= $cant;
    }


    DB::startTransaction();

    try{

        DB::insert("pos_inventario_movimiento",[
            "product_id"=>$prod,
            "tipo"=>$d["tipo"],
            "origen"=>$d["origen"],
            "cantidad"=>$cant,
            "precio_unitario"=>$d["precio_unitario"],
            "referencia_id"=>$d["referencia_id"],
            "referencia_tabla"=>$d["referencia_tabla"],
            "stock_resultante"=>$nuevo_stock
        ]);


        DB::update("pos_inventario",[
            "stock_actual"=>$nuevo_stock
        ],"inventario_id=%i",$inv['inventario_id']);


        DB::commit();

        Flight::json(["status"=>"ok"]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});


