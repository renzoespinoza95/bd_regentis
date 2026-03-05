<?php

/* ======================================================
VISTA MODULOS
====================================================== */

Flight::route('GET /mods', function(){

    include DEFINITION;
    autentificar_administrador();

    global $path_public;

    include $path_public . '/admin/tab_mod/inicio.php';

});


/* ======================================================
LISTAR MODULOS
====================================================== */

Flight::route('GET /modulo/listar', function(){

    $rows = DB::query("
        SELECT *
        FROM reg_modulo
        ORDER BY modulo_id
    ");

    Flight::json($rows);

});


/* ======================================================
GUARDAR MODULO
====================================================== */

Flight::route('POST /modulo/guardar', function(){

    $data = Flight::request()->data->getData();

    if(empty($data['modulo_id'])){

        DB::insert('reg_modulo',[
            'nombre'=>$data['nombre'],
            'descripcion'=>$data['descripcion'],
            'is_activo'=>1
        ]);

    }
    else{

        DB::update('reg_modulo',[
            'nombre'=>$data['nombre'],
            'descripcion'=>$data['descripcion']
        ],"modulo_id=%i",$data['modulo_id']);

    }

    Flight::json(['status'=>'ok']);

});


/* ======================================================
ELIMINAR MODULO
====================================================== */

Flight::route('POST /modulo/eliminar', function(){

    $id = intval(Flight::request()->data->modulo_id);

    DB::delete('reg_neg_modulo',"modulo_id=%i",$id);
    DB::delete('reg_modulo',"modulo_id=%i",$id);

    Flight::json(['status'=>'ok']);

});


/* ======================================================
MODULOS DE NEGOCIO
====================================================== */

Flight::route('GET /negocio/modulos/@neg_id', function($neg_id){

    $rows = DB::query("
        SELECT
            m.modulo_id,
            m.nombre,
            m.descripcion,
            IF(nm.neg_mod_id IS NULL,0,1) AS activo
        FROM reg_modulo m
        LEFT JOIN reg_neg_modulo nm
            ON nm.modulo_id = m.modulo_id
            AND nm.neg_id = %i
        ORDER BY m.nombre
    ",$neg_id);

    Flight::json($rows);

});


/* ======================================================
GUARDAR MODULOS DE NEGOCIO
====================================================== */

Flight::route('POST /negocio/modulos/guardar', function(){

    $data = Flight::request()->data->getData();

    $neg_id = intval($data['neg_id']);
    $modulos = $data['modulos'] ?? [];

    DB::startTransaction();

    DB::delete('reg_neg_modulo',"neg_id=%i",$neg_id);

    foreach($modulos as $modulo_id){

        DB::insert('reg_neg_modulo',[
            'neg_id'=>$neg_id,
            'modulo_id'=>intval($modulo_id),
            'is_activo'=>1
        ]);

    }

    DB::commit();

    Flight::json(['status'=>'ok']);

});


Flight::route('GET /negocio/listar', function(){

    $rows = DB::query("
        SELECT
            neg_id,
            cod_neg,
            nombre,
            ciudad,
            puesto
        FROM reg_neg
        ORDER BY nombre
    ");

    Flight::json($rows);

});


/* =====================================================
   NEGOCIOS
===================================================== */

/* ---------------------------------------
   GET /negocio/listar
--------------------------------------- */
Flight::route('GET /negocio/listar', function () {

    DB::query("SET NAMES 'utf8'");

    $rows = DB::query("
        SELECT
            neg_id,
            cod_neg,
            nombre,
            ciudad,
            puesto
        FROM reg_neg
        ORDER BY neg_id DESC
    ");

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);

});


/* ---------------------------------------
   POST /negocio/guardar
--------------------------------------- */
Flight::route('POST /negocio/guardar', function () {

    $data = Flight::request()->data->getData();

    if (empty($data['neg_id'])) {

        DB::insert('reg_neg', [
            'cod_neg' => $data['cod_neg'],
            'nombre'  => $data['nombre'],
            'ciudad'  => $data['ciudad'],
            'puesto'  => $data['puesto']
        ]);

    } else {

        DB::update('reg_neg', [
            'cod_neg' => $data['cod_neg'],
            'nombre'  => $data['nombre'],
            'ciudad'  => $data['ciudad'],
            'puesto'  => $data['puesto']
        ], "neg_id=%i", $data['neg_id']);

    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'ok'], JSON_UNESCAPED_UNICODE);

});


/* ---------------------------------------
   POST /negocio/eliminar
--------------------------------------- */
Flight::route('POST /negocio/eliminar', function () {

    $data = Flight::request()->data->getData();

    DB::delete('reg_neg', "neg_id=%i", $data['neg_id']);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'ok'], JSON_UNESCAPED_UNICODE);

});