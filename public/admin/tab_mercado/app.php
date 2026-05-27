<?php
Flight::route('POST /NE5F/origen', function(){

    include DEFINITION;

    /* ======================================
       LISTAR CATEGORIAS
    ====================================== */

    $lista_categoria = DB::query("

        SELECT

            cm.cat_mercado_id,

            cm.nombre,

            cm.is_visible

        FROM reg_cat_mercado cm

        WHERE cm.borrado_el IS NULL

        AND cm.is_visible = 1

        ORDER BY cm.nombre ASC

    ");

    $respuesta = [];

    /* ======================================
       RECORRER CATEGORIAS
    ====================================== */

    foreach($lista_categoria as $cat){

        $lista_mercado = DB::query("

            SELECT

                m.mercado_id,

                m.nombre,

                m.direccion,

                m.ciudad,

                m.provincia,

                m.departamento,

                m.logo,

                m.topnavbar_color,

                m.map_lat,

                m.map_lng

            FROM reg_mercado m

            WHERE m.cat_mercado_id = %i

            AND m.borrado_el IS NULL

            ORDER BY m.nombre ASC

        ", $cat['cat_mercado_id']);

        $respuesta[] = [

            "cat_mercado_id" => intval(
                $cat['cat_mercado_id']
            ),

            "nombre" => $cat['nombre'],

            "is_visible" => intval(
                $cat['is_visible']
            ),

            "lista_mercado" => $lista_mercado

        ];

    }

    /* ======================================
       RESPONSE
    ====================================== */

    echo json_encode([

        "res" => "ok",

        "data" => $respuesta

    ]);

});