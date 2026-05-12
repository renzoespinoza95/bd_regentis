<?php
// este es mi backend usando php8.2, flightphp y meekrodb2

/* ============================================================
   VISTA PRINCIPAL
   ============================================================ */
Flight::route('GET /cat', function () {

    include DEFINITION;
    autentificar_administrador();

    require_once VARPATH . '/public/admin/tab_cat/inicio.php';

});


/* ============================================================
   LISTAR CATEGORÍAS
   SOLO DEL NEGOCIO DEL ADMINISTRADOR
   ============================================================ */
Flight::route('GET /category/listar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    $neg_id = intval($administrador_actual['neg_id']);

    $rows = DB::query("
        SELECT
            category_id AS id,
            category_id,
            neg_id,
            name,
            icon,
            brief,
            color,
            priority
        FROM pos_category
        WHERE neg_id = %i
        ORDER BY category_id DESC
    ", $neg_id);

    Flight::json($rows);

});


/* ============================================================
   CREAR CATEGORÍA
   ============================================================ */
Flight::route('POST /category/crear', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    $now = time()*1000;

    $neg_id = intval($administrador_actual['neg_id']);

    DB::insert('pos_category', [

        'neg_id'      => $neg_id,
        'name'        => $d['name'],
        'icon'        => $d['icon'],
        'brief'       => $d['brief'],
        'color'       => $d['color'],
        'priority'    => 0
    ]);

    Flight::json([
        'status' => 'ok'
    ]);

});


/* ============================================================
   EDITAR CATEGORÍA
   SOLO SI PERTENECE AL NEGOCIO
   ============================================================ */
Flight::route('POST /category/editar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    $now = time()*1000;

    $neg_id = intval($administrador_actual['neg_id']);

    DB::update(
        'pos_category',
        [
            'name'        => $d['name'],
            'brief'       => $d['brief'],
            'color'       => $d['color'],
            'icon'        => $d['icon']
        ],
        "category_id=%i AND neg_id=%i",
        $d['category_id'],
        $neg_id
    );

    Flight::json([
        'status'=>'ok'
    ]);

});


/* ============================================================
   ELIMINAR CATEGORÍA
   SOLO SI PERTENECE AL NEGOCIO
   ============================================================ */
Flight::route('POST /category/eliminar', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    $neg_id = intval($administrador_actual['neg_id']);

    DB::delete(
        'pos_category',
        "category_id=%i AND neg_id=%i",
        $d['category_id'],
        $neg_id
    );

    Flight::json([
        'status'=>'ok'
    ]);

});

Flight::route('GET /Vy74/crearCategorias/@neg_id', function($neg_id){

    include DEFINITION;
    autentificar_administrador();

    $neg_id = intval($neg_id);

    if(!$neg_id){
        Flight::json([
            'status'=>'error',
            'msg'=>'neg_id requerido'
        ],400);
        return;
    }

    DB::startTransaction();

    try{

        // 🔥 traer todas las categorías globales activas
        $cats = DB::query("
            SELECT *
            FROM reg_categoria_global
            WHERE is_activo = 1
            ORDER BY orden ASC
        ");

        $insertados = 0;

        foreach($cats as $c){

            DB::insert('pos_category',[
                'neg_id'               => $neg_id,
                'name'                 => $c['nombre'],
                'icon'                 => $c['icono'],
                'priority'             => $c['orden'],
                'categoria_global_id'  => $c['categoria_global_id']
            ]);

            $insertados++;
        }

        DB::commit();

        Flight::json([
            'status'=>'ok',
            'msg'=>"Categorías creadas correctamente",
            'total'=>$insertados,
            'neg_id'=>$neg_id
        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }

});