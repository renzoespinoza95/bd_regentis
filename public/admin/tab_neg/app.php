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

        /* =========================
           🔥 FIRMA
        ========================== */

        $xin  = $d['xin'] ?? '';
        $yuan = $d['yuan'] ?? '';

        firma($xin, $yuan);

        /* =========================
           PAYLOAD
        ========================== */

        $mercado_id = intval(
            $d['mercado_id'] ?? 0
        );

        $neg_id = intval(
            $d['neg_id'] ?? 0
        );

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
           SLIDERS PRINCIPALES
        ========================== */

        $sliders = DB::query("

            SELECT 

                slider_id,
                img,
                orden,
                descripcion,
                titulo_superior,
                mercado_id

            FROM reg_prin_slider

            WHERE is_visible = 1
            AND mercado_id = %i

            ORDER BY orden ASC

        ", $mercado_id);

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
           VARIABLES SISTEMA
        ========================== */

        $version = vari('VERSION');

        $playstore = vari('PLAYSTORE');

        /* =========================
           RESPONSE
        ========================== */

        Flight::json([

            'status' => 'ok',

            'data' => [

                'mercado' => $mercado,

                'sliders' => $sliders,

                'rubros'  => $rubros,

                'negocios'=> $negocios,

                'version' => $version,

                'playstore' => $playstore

            ]

        ], 200, JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});


function veri_membresia($usu_id){

    /* =====================================
       LIMPIAR
    ====================================== */

    $usu_id = intval(
        $usu_id
    );

    if($usu_id <= 0){

        return null;

    }

    /* =====================================
       BUSCAR NEGOCIO DEL USUARIO
    ====================================== */

    $negocio_usuario = DB::queryFirstRow("

        SELECT

            neg_id

        FROM reg_negxusu

        WHERE usu_id = %i

        AND is_activo = 1

        AND borrado_el IS NULL

        ORDER BY negxusu_id DESC

        LIMIT 1

    ", $usu_id);

    if(!$negocio_usuario){

        return null;

    }

    $neg_id = intval(
        $negocio_usuario['neg_id']
    );

    /* =====================================
       BUSCAR MEMBRESIA
    ====================================== */

    $membresia = DB::queryFirstRow("

        SELECT

            motivo,

            is_aprobado,

            fecha_inicio_premium,

            fecha_fin_premium

        FROM reg_neg_pago

        WHERE neg_id = %i

        AND borrado_el IS NULL

        ORDER BY fecha_fin_premium DESC,
                 neg_pago_id DESC

        LIMIT 1

    ", $neg_id);

    if(!$membresia){

        return null;

    }

    /* =====================================
       RESPONSE
    ====================================== */

    return [

        'motivo' =>

            $membresia['motivo'],

        'is_aprobado' =>

            intval(
                $membresia['is_aprobado']
            ),

        'fecha_inicio_premium' =>

            $membresia['fecha_inicio_premium'],

        'fecha_fin_premium' =>

            $membresia['fecha_fin_premium']

    ];

}

Flight::route('POST /XQzQ/detalleNeg', function(){

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

    $neg_id = intval(
        $data->neg_id ?? 0
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($neg_id <= 0){

        echo json_encode([

            "res" => "error",

            "msg" => "neg_id inválido"

        ]);

        return;
    }

    /* ======================================
       NEGOCIO
    ====================================== */

    $info_negocio = DB::queryFirstRow("

        SELECT

            n.neg_id,

            n.cod_neg,

            n.nombre,

            n.celular_informes,

            n.fecha_creacion,

            n.is_activo,

            n.ciudad,

            n.provincia,

            n.departamento,

            n.map_lat,

            n.map_lng,

            n.place_id,

            n.direccion,

            n.is_validado,

            n.img_logo,

            n.fecha_ultimo_acceso,

            n.puesto,

            n.descripcion,

            n.mercado_id,

            n.lista_yape

        FROM reg_neg n

        WHERE n.neg_id = %i

        AND n.borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    /* ======================================
       NO ENCONTRADO
    ====================================== */

    if(!$info_negocio){

        echo json_encode([

            "res" => "error",

            "msg" => "Negocio no encontrado"

        ]);

        return;
    }

    /* ======================================
       RESPONSE
    ====================================== */

    echo json_encode([

        "res" => "ok",

        "data" => $info_negocio

    ]);

});

Flight::route('POST /Fw7L/editarMiNeg', function(){

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

    $neg_id = intval(
        $data->neg_id ?? 0
    );

    $nombre = trim(
        $data->nombre ?? ''
    );

    $celular_informes = trim(
        $data->celular_informes ?? ''
    );

    $ciudad = trim(
        $data->ciudad ?? ''
    );

    $provincia = trim(
        $data->provincia ?? ''
    );

    $departamento = trim(
        $data->departamento ?? ''
    );

    $map_lat = trim(
        $data->map_lat ?? ''
    );

    $map_lng = trim(
        $data->map_lng ?? ''
    );

    $place_id = trim(
        $data->place_id ?? ''
    );

    $direccion = trim(
        $data->direccion ?? ''
    );

    $img_logo = trim(
        $data->img_logo ?? ''
    );

    $puesto = trim(
        $data->puesto ?? ''
    );

    $descripcion = trim(
        $data->descripcion ?? ''
    );

    $lista_yape = $data->lista_yape ?? null;

    /* ======================================
       VALIDAR
    ====================================== */

    if($neg_id <= 0){

        echo json_encode([

            "res" => "error",

            "msg" => "neg_id inválido"

        ]);

        return;
    }

    /* ======================================
       NEGOCIO
    ====================================== */

    $info_negocio = DB::queryFirstRow("

        SELECT *

        FROM reg_neg

        WHERE neg_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    if(!$info_negocio){

        echo json_encode([

            "res" => "error",

            "msg" => "Negocio no encontrado"

        ]);

        return;
    }

    /* ======================================
       UPDATE DINAMICO
    ====================================== */

    $update = [];

    if(
        $nombre !== ''
        &&
        $nombre != $info_negocio['nombre']
    ){

        $update['nombre'] = $nombre;

    }

    if(
        $celular_informes !=
        $info_negocio['celular_informes']
    ){

        $update['celular_informes']
            = $celular_informes;

    }

    if(
        $ciudad !=
        $info_negocio['ciudad']
    ){

        $update['ciudad']
            = $ciudad;

    }

    if(
        $provincia !=
        $info_negocio['provincia']
    ){

        $update['provincia']
            = $provincia;

    }

    if(
        $departamento !=
        $info_negocio['departamento']
    ){

        $update['departamento']
            = $departamento;

    }

    if(
        $map_lat !=
        $info_negocio['map_lat']
    ){

        $update['map_lat']
            = $map_lat;

    }

    if(
        $map_lng !=
        $info_negocio['map_lng']
    ){

        $update['map_lng']
            = $map_lng;

    }

    if(
        $place_id !=
        $info_negocio['place_id']
    ){

        $update['place_id']
            = $place_id;

    }

    if(
        $direccion !=
        $info_negocio['direccion']
    ){

        $update['direccion']
            = $direccion;

    }

    if(
        $img_logo !== ''
        &&
        $img_logo !=
        $info_negocio['img_logo']
    ){

        $update['img_logo']
            = $img_logo;

    }

    if(
        $puesto !=
        $info_negocio['puesto']
    ){

        $update['puesto']
            = $puesto;

    }

    if(
        $descripcion !=
        $info_negocio['descripcion']
    ){

        $update['descripcion']
            = $descripcion;

    }

    /* ======================================
       LISTA YAPE
    ====================================== */

    $lista_yape_json = null;

    if(
        is_array($lista_yape)
    ){

        $lista_yape_json =
            json_encode(
                $lista_yape,
                JSON_UNESCAPED_UNICODE
            );

    }else{

        $lista_yape_json =
            trim(
                strval($lista_yape)
            );

    }

    if(
        $lista_yape_json !=
        $info_negocio['lista_yape']
    ){

        $update['lista_yape']
            = $lista_yape_json;

    }

    /* ======================================
       SIN CAMBIOS
    ====================================== */

    if(
        empty($update)
    ){

        echo json_encode([

            "res" => "ok",

            "msg" => "Sin cambios"

        ]);

        return;
    }

    /* ======================================
       UPDATE
    ====================================== */

    DB::update(

        'reg_neg',

        $update,

        'neg_id=%i',

        $neg_id

    );

    /* ======================================
       RESPONSE
    ====================================== */

    echo json_encode([

        "res" => "ok",

        "msg" => "Negocio actualizado",

        "campos_actualizados" =>
            array_keys($update)

    ]);

});