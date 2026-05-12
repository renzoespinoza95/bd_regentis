<?php

Flight::route('GET /Cuvg/neg/listar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("
            SELECT n.neg_id,n.nombre,n.puesto,n.mercado_id,n.is_activo
            FROM reg_neg n
            ORDER BY n.neg_id DESC
        ");

        Flight::json(['status'=>'ok','data'=>$rows]);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /app/principal', function() {

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $d = json_decode(
            Flight::request()->getBody(),
            true
        ) ?: [];

        $mercado_id = intval($d['mercado_id'] ?? 0);

        $neg_id = intval($d['neg_id'] ?? 0);

        /* =========================
           🔥 SI mercado_id = 0
           RESOLVER DESDE NEGOCIO
        ========================== */

        if($mercado_id === 0){

            if(!$neg_id){

                Flight::json([
                    'status'=>'error',
                    'msg'=>'neg_id requerido cuando mercado_id = 0'
                ],400);

                return;
            }

            $mercado_id = DB::queryFirstField("

                SELECT mercado_id

                FROM reg_neg

                WHERE neg_id = %i
                AND is_activo = 1

                LIMIT 1

            ", $neg_id);

            if(!$mercado_id){

                Flight::json([
                    'status'=>'error',
                    'msg'=>'No se encontró mercado para ese negocio'
                ],404);

                return;
            }

        }

        /* =========================
           MERCADO
        ========================== */

        $mercado = DB::queryFirstRow("

            SELECT 

                mercado_id,
                nombre,
                direccion,
                ciudad,
                provincia,
                departamento,
                map_lat,
                map_lng,
                logo,
                topnavbar_color,
                patron_fondo

            FROM reg_mercado

            WHERE mercado_id = %i
            AND is_activo = 1

            LIMIT 1

        ", $mercado_id);

        if(!$mercado){

            Flight::json([
                'status'=>'error',
                'msg'=>'Mercado no encontrado'
            ],404);

            return;
        }

        /* =========================
           SLIDERS
        ========================== */

        $sliders = DB::query("

            SELECT 

                slider_id,
                img,
                orden,
                descripcion

            FROM reg_slider

            WHERE grupo = 'B'
            AND is_visible = 1

            ORDER BY orden ASC

        ");

        /* =========================
           RUBROS
        ========================== */

        $rubros = DB::query("

            SELECT

                rubro_id,
                nombre,
                icono

            FROM reg_rubro

            WHERE is_activo = 1

            ORDER BY nombre ASC

        ");

        /* =========================
           NEGOCIOS DEL MERCADO
        ========================== */

        $negocios = DB::query("

            SELECT 

                n.neg_id,
                n.nombre,
                n.puesto,
                n.descripcion,
                n.img_logo,
                n.mercado_id,
                n.is_validado,
                n.map_lat,
                n.map_lng

            FROM reg_neg n

            WHERE n.is_activo = 1
            AND n.mercado_id = %i

            ORDER BY n.neg_id DESC

        ", $mercado_id);

        /* =========================
           RUBROS POR NEGOCIO
        ========================== */

        foreach($negocios as &$n){

            $rubrosNeg = DB::query("

                SELECT

                    rxn.rubroxneg_id,
                    rxn.rubro_id,

                    r.nombre,
                    r.icono

                FROM reg_rubroxneg rxn

                INNER JOIN reg_rubro r
                    ON r.rubro_id = rxn.rubro_id

                WHERE rxn.neg_id = %i
                AND rxn.is_activo = 1
                AND r.is_activo = 1

                ORDER BY r.nombre ASC

            ", $n['neg_id']);

            $n['rubros'] = $rubrosNeg;

        }

        /* =========================
           RESPONSE
        ========================== */

        Flight::json([

            'status' => 'ok',

            'data' => [

                'mercado' => $mercado,

                'sliders' => $sliders,

                'rubros'  => $rubros,

                'negocios'=> $negocios

            ]

        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});