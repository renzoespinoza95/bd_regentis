<?php
Flight::route('POST /NsQm/crearCategoria', function(){

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

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $name = trim(
        $d['name'] ?? ''
    );

    $icon = trim(
        $d['icon'] ?? ''
    );

    $brief = trim(
        $d['brief'] ?? ''
    );

    $color = trim(
        $d['color'] ?? ''
    );

    $priority = intval(
        $d['priority'] ?? 0
    );

    $categoria_global_id = intval(
        $d['categoria_global_id'] ?? 0
    );

    $clave_txt = trim(
        $d['clave_txt'] ?? ''
    );

    $img = trim(
        $d['img'] ?? ''
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($neg_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ], 400);

        return;
    }

    if(!$name){

        Flight::json([

            'status' => 'error',

            'msg' => 'name requerido'

        ], 400);

        return;
    }

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

        Flight::json([

            'status' => 'error',

            'msg' => 'Negocio no encontrado'

        ], 404);

        return;
    }

    /* ======================================
       CATEGORIA GLOBAL
    ====================================== */

    if($categoria_global_id > 0){

        $categoria_global = DB::queryFirstRow("

            SELECT
                categoria_global_id,
                nombre,
                icono

            FROM reg_categoria_global

            WHERE categoria_global_id = %i
            AND borrado_el IS NULL
            AND is_activo = 1

            LIMIT 1

        ", $categoria_global_id);

        if(!$categoria_global){

            Flight::json([

                'status' => 'error',

                'msg' => 'categoria_global_id inválido'

            ], 404);

            return;
        }

        if(!$icon){
            $icon = $categoria_global['icono'];
        }

    }

    /* ======================================
       DUPLICADO
    ====================================== */

    $duplicado = DB::queryFirstRow("

        SELECT category_id

        FROM pos_category

        WHERE neg_id = %i
        AND LOWER(name) = LOWER(%s)
        AND borrado_el IS NULL

        LIMIT 1

    ",
        $neg_id,
        $name
    );

    if($duplicado){

        Flight::json([

            'status' => 'error',

            'msg' => 'La categoría ya existe'

        ], 400);

        return;
    }

    /* ======================================
       INSERT
    ====================================== */

    DB::insert(

        'pos_category',

        [

            'name' =>
                $name,

            'icon' =>
                $icon,

            'brief' =>
                $brief,

            'color' =>
                $color,

            'priority' =>
                $priority,

            'neg_id' =>
                $neg_id,

            'categoria_global_id' =>
                $categoria_global_id > 0
                ? $categoria_global_id
                : null,

            'is_activo' => 1,

            'clave_txt' =>
                $clave_txt,

            'img' =>
                $img,

            'borrado_el' => null

        ]

    );

    $category_id = DB::insertId();

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status' => 'ok',

        'msg' =>
            'Categoría creada correctamente',

        'category_id' =>
            $category_id

    ]);

});