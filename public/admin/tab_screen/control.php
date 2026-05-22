<?php

Flight::route('GET /screen/inicio', function () {

    include DEFINITION;

    autentificar_administrador();

    include VARPATH . '/public/admin/tab_screen/inicio.php';

});


/* =========================================
   SCREENS
========================================= */

Flight::route('GET /xoxo/frida/screen/listar', function(){

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("

        SELECT 

            s.screen_id,
            s.nombre,
            s.titulo,
            s.explicacion,
            s.vue_route,
            s.tipoxusu_id,

            t.descripcion AS tipoxusu_descripcion,

            (
                SELECT COUNT(*)
                FROM deux_screenxrubro sxr
                WHERE sxr.screen_id = s.screen_id
            ) AS total_rubros

        FROM deux_screen s

        LEFT JOIN reg_tipoxusu t
            ON t.tipoxusu_id = s.tipoxusu_id

        ORDER BY s.screen_id DESC

    ");

    /* =========================================
       RUBROS POR SCREEN
    ========================================= */

    foreach($rows as &$r){

        $r['rubros'] = DB::query("

            SELECT 

                r.rubro_id,
                r.nombre,
                r.icono

            FROM deux_screenxrubro sxr

            INNER JOIN reg_rubro r
                ON r.rubro_id = sxr.rubro_id

            WHERE sxr.screen_id = %i

            ORDER BY r.nombre ASC

        ", $r['screen_id']);

    }

    Flight::json([
        'status'=>'ok',
        'data'=>$rows
    ]);

});



/* =========================================
   CREAR SCREEN
========================================= */

Flight::route('POST /xoxo/frida/screen/crear', function(){

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $data = Flight::request()->data->getData();

    $nombre        = trim($data['nombre'] ?? '');

    $titulo        = trim($data['titulo'] ?? '');

    $explicacion   = trim($data['explicacion'] ?? '');

    $vue_route     = trim($data['vue_route'] ?? '');

    $tipoxusu_id   = intval($data['tipoxusu_id'] ?? 0);

    $rubros        = $data['rubros'] ?? [];

    /* =========================================
       VALIDAR
    ========================================= */

    if($nombre==''){

        Flight::json([
            'status'=>'error',
            'msg'=>'nombre requerido'
        ],400);

        return;
    }

    DB::startTransaction();

    try{

        /* =========================================
           INSERT SCREEN
        ========================================= */

        DB::insert('deux_screen',[

            'nombre'        => $nombre,

            'titulo'        => $titulo,

            'explicacion'   => $explicacion,

            'vue_route'     => $vue_route,

            'tipoxusu_id'   => $tipoxusu_id ?: null

        ]);

        $screen_id = DB::insertId();

        /* =========================================
           RUBROS
        ========================================= */

        if(is_array($rubros)){

            foreach($rubros as $rubro_id){

                $rubro_id = intval($rubro_id);

                if($rubro_id <= 0){
                    continue;
                }

                DB::insert('deux_screenxrubro',[

                    'screen_id' => $screen_id,

                    'rubro_id' => $rubro_id

                ]);

            }

        }

        DB::commit();

        Flight::json([

            'status'=>'ok',

            'screen_id'=>$screen_id

        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});



/* =========================================
   EDITAR SCREEN
========================================= */

Flight::route('POST /xoxo/frida/screen/editar', function(){

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $data = Flight::request()->data->getData();

    $screen_id     = intval($data['screen_id'] ?? 0);

    $nombre        = trim($data['nombre'] ?? '');

    $titulo        = trim($data['titulo'] ?? '');

    $explicacion   = trim($data['explicacion'] ?? '');

    $vue_route     = trim($data['vue_route'] ?? '');

    $tipoxusu_id   = intval($data['tipoxusu_id'] ?? 0);

    $rubros        = $data['rubros'] ?? [];

    /* =========================================
       VALIDAR
    ========================================= */

    if($screen_id <= 0){

        Flight::json([
            'status'=>'error',
            'msg'=>'screen_id inválido'
        ],400);

        return;
    }

    if($nombre==''){

        Flight::json([
            'status'=>'error',
            'msg'=>'nombre requerido'
        ],400);

        return;
    }
    
    DB::startTransaction();

    try{

        /* =========================================
           UPDATE SCREEN
        ========================================= */

        DB::update('deux_screen',[

            'nombre'        => $nombre,

            'titulo'        => $titulo,

            'explicacion'   => $explicacion,

            'vue_route'     => $vue_route,

            'tipoxusu_id'   => $tipoxusu_id ?: null

        ],"screen_id=%i",$screen_id);

        /* =========================================
           ELIMINAR RUBROS
        ========================================= */

        DB::delete(
            'deux_screenxrubro',
            "screen_id=%i",
            $screen_id
        );

        /* =========================================
           INSERTAR RUBROS
        ========================================= */

        if(is_array($rubros)){

            foreach($rubros as $rubro_id){

                $rubro_id = intval($rubro_id);

                if($rubro_id <= 0){
                    continue;
                }

                DB::insert('deux_screenxrubro',[

                    'screen_id' => $screen_id,

                    'rubro_id' => $rubro_id

                ]);

            }

        }

        DB::commit();

        Flight::json([
            'status'=>'ok'
        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});



/* =========================================
   ELIMINAR SCREEN
========================================= */

Flight::route('POST /xoxo/frida/screen/eliminar', function(){

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $data = Flight::request()->data->getData();

    $screen_id = intval($data['screen_id'] ?? 0);

    if($screen_id <= 0){

        Flight::json([
            'status'=>'error',
            'msg'=>'screen_id inválido'
        ],400);

        return;
    }

    DB::startTransaction();

    try{

        DB::delete(
            'deux_screenxrubro',
            "screen_id=%i",
            $screen_id
        );

        DB::delete(
            'deux_screen',
            "screen_id=%i",
            $screen_id
        );

        DB::commit();

        Flight::json([
            'status'=>'ok'
        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});



/* =========================================
   TIPOS USUARIO
========================================= */

Flight::route('GET /xoxo/frida/tipoxusu/listar', function(){

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("

        SELECT 

            tipoxusu_id,
            clave_txt,
            descripcion

        FROM reg_tipoxusu

        ORDER BY descripcion ASC

    ");

    Flight::json([
        'status'=>'ok',
        'data'=>$rows
    ]);

});



/* =========================================
   RUBROS
========================================= */

Flight::route('GET /xoxo/frida/rubro/listar', function(){

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("

        SELECT 

            rubro_id,
            nombre,
            icono,
            is_activo

        FROM reg_rubro

        WHERE is_activo = 1

        ORDER BY nombre ASC

    ");

    Flight::json([
        'status'=>'ok',
        'data'=>$rows
    ]);

});