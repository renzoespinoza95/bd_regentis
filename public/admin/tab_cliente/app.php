<?php
Flight::route(

'POST /C2kb/crearCliente',

function(){

    $d = json_decode(
            Flight::request()->getBody(),
            true
        ) ?: [];

        /* =========================
           🔥 FIRMA
        ========================== */

        $xin  = $d['xin'] ?? '';
        $yuan = $d['yuan'] ?? '';
        $neg_id = $d['neg_id'] ?? '';

        firma($xin, $yuan);


    try{        

        /* ======================================
           📦 PAYLOAD
        ====================================== */



        $nombres_apellidos =
            trim(
                $d['nombres_apellidos']
                ?? ''
            );

        $xin =
            trim(
                $d['xin']
                ?? ''
            );

        $yuan =
            trim(
                $d['yuan']
                ?? ''
            );

        /* ======================================
           🔐 FIRMA
        ====================================== */

        firma(
            $xin,
            $yuan
        );

        /* ======================================
           🧪 VALIDAR
        ====================================== */

        if(!$nombres_apellidos){

            Flight::json([

                'status'=>'error',

                'msg'=>'Ingrese nombres'

            ],400);

            return;

        }

        /* ======================================
           💾 INSERT
        ====================================== */

        DB::insert(

            'pos_cliente',

            [

                'nombres_apellidos'=>
                    $nombres_apellidos,

                'dni'=>
                    null,

                'cod_usu'=>
                    null,

                'ruc'=>
                    null,

                'direccion'=>
                    null,

                'celular'=>
                    null,

                'email'=>
                    null,

                'is_activo'=>
                    1,

                'usu_id'=>
                    null,

                'puesto'=>
                    null,

                'distrito'=>
                    null,

                'map_lat'=>
                    null,

                'map_lng'=>
                    null,

                'neg_id'=>
                    $neg_id,

                'borrado_el'=>
                    null

            ]

        );

        /* ======================================
           ✅ RESPONSE
        ====================================== */

        Flight::json([

            'status'=>'ok',

            'cliente_id'=>
                DB::insertId(),

            'msg'=>
                'Cliente creado'

        ]);

    }catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

}); 


Flight::route('POST /H9I8/editarCliente', function(){

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

    $cliente_id = intval(
        $d['cliente_id'] ?? 0
    );

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $nombres_apellidos = trim(
        $d['nombres_apellidos'] ?? ''
    );

    $dni = trim(
        $d['dni'] ?? ''
    );

    $cod_usu = trim(
        $d['cod_usu'] ?? ''
    );

    $ruc = trim(
        $d['ruc'] ?? ''
    );

    $direccion = trim(
        $d['direccion'] ?? ''
    );

    $celular = trim(
        $d['celular'] ?? ''
    );

    $email = trim(
        $d['email'] ?? ''
    );

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    $puesto = trim(
        $d['puesto'] ?? 'Cliente'
    );

    $distrito = trim(
        $d['distrito'] ?? ''
    );

    $map_lat = trim(
        $d['map_lat'] ?? ''
    );

    $map_lng = trim(
        $d['map_lng'] ?? ''
    );

    $is_activo = intval(
        $d['is_activo'] ?? 1
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($cliente_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'cliente_id requerido'

        ],400);

        return;
    }

    if($neg_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ],400);

        return;
    }

    if(!$nombres_apellidos){

        Flight::json([

            'status' => 'error',

            'msg' =>
                'nombres_apellidos requerido'

        ],400);

        return;
    }

    /* ======================================
       CLIENTE
    ====================================== */

    $cliente = DB::queryFirstRow("

        SELECT cliente_id

        FROM pos_cliente

        WHERE cliente_id = %i
        AND neg_id = %i
        AND borrado_el IS NULL

        LIMIT 1

    ",
        $cliente_id,
        $neg_id
    );

    if(!$cliente){

        Flight::json([

            'status' => 'error',

            'msg' => 'Cliente no encontrado'

        ],404);

        return;
    }

    /* ======================================
       DUPLICADO DNI
    ====================================== */

    if($dni){

        $existe_dni = DB::queryFirstRow("

            SELECT cliente_id

            FROM pos_cliente

            WHERE dni = %s
            AND neg_id = %i
            AND cliente_id <> %i
            AND borrado_el IS NULL

            LIMIT 1

        ",
            $dni,
            $neg_id,
            $cliente_id
        );

        if($existe_dni){

            Flight::json([

                'status' => 'error',

                'msg' =>
                    'Ya existe otro cliente con ese DNI'

            ],400);

            return;
        }

    }

    /* ======================================
       DUPLICADO RUC
    ====================================== */

    if($ruc){

        $existe_ruc = DB::queryFirstRow("

            SELECT cliente_id

            FROM pos_cliente

            WHERE ruc = %s
            AND neg_id = %i
            AND cliente_id <> %i
            AND borrado_el IS NULL

            LIMIT 1

        ",
            $ruc,
            $neg_id,
            $cliente_id
        );

        if($existe_ruc){

            Flight::json([

                'status' => 'error',

                'msg' =>
                    'Ya existe otro cliente con ese RUC'

            ],400);

            return;
        }

    }

    /* ======================================
       UPDATE
    ====================================== */

    DB::update(

        'pos_cliente',

        [

            'nombres_apellidos' =>
                $nombres_apellidos,

            'dni' =>
                $dni,

            'cod_usu' =>
                $cod_usu
                ? $cod_usu
                : null,

            'ruc' =>
                $ruc
                ? $ruc
                : null,

            'direccion' =>
                $direccion,

            'celular' =>
                $celular,

            'email' =>
                $email,

            'is_activo' =>
                $is_activo,

            'usu_id' =>
                $usu_id > 0
                ? $usu_id
                : null,

            'puesto' =>
                $puesto,

            'distrito' =>
                $distrito,

            'map_lat' =>
                $map_lat
                ? $map_lat
                : null,

            'map_lng' =>
                $map_lng
                ? $map_lng
                : null

        ],

        "

            cliente_id = %i
            AND neg_id = %i

        ",

        $cliente_id,
        $neg_id

    );

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status' => 'ok',

        'msg' =>
            'Cliente actualizado correctamente',

        'cliente_id' =>
            $cliente_id

    ]);

});

function veri_publico_general($neg_id){

    /* =====================================
       LIMPIAR
    ====================================== */

    $neg_id = intval(
        $neg_id
    );

    if(!$neg_id){

        return [

            'ok' => false,

            'msg' => 'neg_id inválido'

        ];

    }

    /* =====================================
       VERIFICAR NEGOCIO
    ====================================== */

    $negocio = DB::queryFirstRow("

        SELECT

            neg_id

        FROM reg_neg

        WHERE neg_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    if(!$negocio){

        return [

            'ok' => false,

            'msg' => 'Negocio no encontrado'

        ];

    }

    /* =====================================
       VERIFICAR PLAN FREE
    ====================================== */

    $plan_free = DB::queryFirstRow("

        SELECT

            neg_pago_id

        FROM reg_neg_pago

        WHERE neg_id = %i

        AND motivo = 'FREE'

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    /* =====================================
       CREAR PLAN FREE
    ====================================== */

    if(!$plan_free){

        $fecha_inicio =
            date('Y-m-d H:i:s');

        $fecha_fin =
            date(

                'Y-m-d H:i:s',

                strtotime(
                    '+7 days'
                )

            );

        DB::insert(

            'reg_neg_pago',

            [

                'neg_id' =>
                    $neg_id,

                'usu_id_yapeo' =>
                    null,

                'motivo' =>
                    'FREE',

                'monto' =>
                    0.00,

                'fecha_inicio_premium' =>
                    $fecha_inicio,

                'fecha_fin_premium' =>
                    $fecha_fin,

                'yaplin_id' =>
                    null,

                'is_aprobado' =>
                    1,

                'borrado_el' =>
                    null

            ]

        );

    }

    /* =====================================
       VERIFICAR CLIENTE
    ====================================== */

    $cliente = DB::queryFirstRow("

        SELECT

            cliente_id

        FROM pos_cliente

        WHERE neg_id = %i

        AND nombres_apellidos = 'PUBLICO_GENERAL'

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    /* =====================================
       YA EXISTE
    ====================================== */

    if($cliente){

        $cliente_id = intval(
            $cliente['cliente_id']
        );

    } else {

        /* =====================================
           CREAR CLIENTE
        ====================================== */

        DB::insert(

            'pos_cliente',

            [

                'nombres_apellidos' =>
                    'PUBLICO_GENERAL',

                'dni' =>
                    '999X',

                'cod_usu' =>
                    '999X',

                'ruc' =>
                    null,

                'direccion' =>
                    null,

                'celular' =>
                    null,

                'email' =>
                    null,

                'is_activo' => 1,

                'usu_id' =>
                    null,

                'puesto' =>
                    null,

                'distrito' =>
                    null,

                'map_lat' =>
                    null,

                'map_lng' =>
                    null,

                'neg_id' =>
                    $neg_id,

                'borrado_el' =>
                    null

            ]

        );

        $cliente_id = DB::insertId();

    }

    /* =====================================
       VERIFICAR RUBROS
       10 = Comida preparada
       8  = Jugos y bebidas
       7  = Panadería
       12 = Restaurantes
    ====================================== */

    $tiene_rubro = DB::queryFirstField("

        SELECT

            COUNT(*)

        FROM reg_rubroxneg n

        WHERE n.neg_id = %i

        AND n.rubro_id IN (10,8,7,12)

        AND n.is_activo = 1

    ", $neg_id);

    /* =====================================
       SI TIENE RUBRO RESTAURANTE
       VERIFICAR MESAS
    ====================================== */

    if($tiene_rubro > 0){

        $mesas_existentes = DB::queryFirstField("

            SELECT

                COUNT(*)

            FROM resto_mesa

            WHERE neg_id = %i

            AND borrado_el IS NULL

        ", $neg_id);

        /* =====================================
           NO TIENE MESAS
           CREAR MESA 01 → MESA 10
        ====================================== */

        if(intval($mesas_existentes) <= 0){

            for($i = 1; $i <= 10; $i++){

                DB::insert(

                    'resto_mesa',

                    [

                        'nombre' =>

                            'MESA ' .
                            str_pad(
                                $i,
                                2,
                                '0',
                                STR_PAD_LEFT
                            ),

                        'estado' =>
                            'DISPONIBLE',

                        'neg_id' =>
                            $neg_id,

                        'borrado_el' =>
                            null

                    ]

                );

            }

        }

    }

    /* =====================================
       RESPONSE
    ====================================== */

    return [

        'ok' => true,

        'cliente_id' => $cliente_id,

        'creado' => !$cliente

    ];

}