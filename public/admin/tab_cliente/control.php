<?php

/* =========================================================
   CLIENTES
========================================================= */
Flight::route('GET /cliente/inicio', function () {
    include DEFINITION;
    autentificar_administrador();    
    include VARPATH . '/public/admin/tab_cliente/inicio.php';
});

Flight::route(
'GET /L45L/clientes/listar',
function(){

    try{

        autentificar_administrador();

        global $administrador_actual;

        DB::query(
            "SET NAMES 'utf8mb4'"
        );

        $neg_id =
            intval(
                $administrador_actual['neg_id']
            );

        $rows = DB::query("

            SELECT

                cliente_id,
                nombres_apellidos,
                dni,
                celular,
                email,
                direccion,
                is_activo

            FROM pos_cliente

            WHERE neg_id = %i
            AND borrado_el IS NULL

            ORDER BY cliente_id DESC

        ", $neg_id);

        Flight::json([

            'status'=>'ok',

            'data'=>$rows

        ]);

    }catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

/* =========================================================
   CREAR
========================================================= */

Flight::route(
'POST /L45L/clientes/crear',
function(){

    try{

        autentificar_administrador();

        global $administrador_actual;

        DB::query(
            "SET NAMES 'utf8mb4'"
        );

        $neg_id =
            intval(
                $administrador_actual['neg_id']
            );

        $d =
            Flight::request()
            ->data
            ->getData();

        DB::insert(

            'pos_cliente',

            [

                'nombres_apellidos'=>
                    trim(
                        $d['nombres_apellidos']
                    ),

                'dni'=>
                    trim(
                        $d['dni']
                    ),

                'celular'=>
                    trim(
                        $d['celular']
                    ),

                'email'=>
                    trim(
                        $d['email']
                    ),

                'direccion'=>
                    trim(
                        $d['direccion']
                    ),

                'is_activo'=>
                    intval(
                        $d['is_activo']
                    ),

                'neg_id'=>
                    $neg_id

            ]

        );

        Flight::json([

            'status'=>'ok',

            'cliente_id'=>
                DB::insertId()

        ]);

    }catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

/* =========================================================
   EDITAR
========================================================= */

Flight::route(
'POST /L45L/clientes/editar',
function(){

    try{

        autentificar_administrador();

        global $administrador_actual;

        DB::query(
            "SET NAMES 'utf8mb4'"
        );

        $neg_id =
            intval(
                $administrador_actual['neg_id']
            );

        $d =
            Flight::request()
            ->data
            ->getData();

        $cliente_id =
            intval(
                $d['cliente_id']
            );

        DB::update(

            'pos_cliente',

            [

                'nombres_apellidos'=>
                    trim(
                        $d['nombres_apellidos']
                    ),

                'dni'=>
                    trim(
                        $d['dni']
                    ),

                'celular'=>
                    trim(
                        $d['celular']
                    ),

                'email'=>
                    trim(
                        $d['email']
                    ),

                'direccion'=>
                    trim(
                        $d['direccion']
                    ),

                'is_activo'=>
                    intval(
                        $d['is_activo']
                    )

            ],

            "

                cliente_id=%i
                AND neg_id=%i

            ",

            $cliente_id,
            $neg_id

        );

        Flight::json([

            'status'=>'ok'

        ]);

    }catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

/* =========================================================
   ELIMINAR
========================================================= */

Flight::route(
'POST /L45L/clientes/eliminar',
function(){

    try{

        autentificar_administrador();

        global $administrador_actual;

        DB::query(
            "SET NAMES 'utf8mb4'"
        );

        $neg_id =
            intval(
                $administrador_actual['neg_id']
            );

        $d =
            Flight::request()
            ->data
            ->getData();

        $cliente_id =
            intval(
                $d['cliente_id']
            );

        DB::update(

            'pos_cliente',

            [

                'borrado_el'=>
                    date(
                        'Y-m-d H:i:s'
                    )

            ],

            "

                cliente_id=%i
                AND neg_id=%i

            ",

            $cliente_id,
            $neg_id

        );

        Flight::json([

            'status'=>'ok'

        ]);

    }catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

/* =========================================================
   YAPLIN
========================================================= */

Flight::route(
'GET /L45L/yaplin/listar',
function(){

    try{

        autentificar_administrador();

        global $administrador_actual;

        DB::query(
            "SET NAMES 'utf8mb4'"
        );

        $neg_id =
            intval(
                $administrador_actual['neg_id']
            );

        $cliente_id =
            intval(
                Flight::request()
                ->query['cliente_id']
            );

        $rows = DB::query("

            SELECT

                yaplin_id,
                billetera,
                monto,
                imagen_url

            FROM reg_yaplin

            WHERE cliente_id = %i
            AND neg_id = %i

            ORDER BY yaplin_id DESC

        ",
            $cliente_id,
            $neg_id
        );

        Flight::json([

            'status'=>'ok',

            'data'=>$rows

        ]);

    }catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

Flight::route('POST /MBr4/clientesFantasmasSinUsuId', function(){

    include DEFINITION;

    autentificar_administrador();

    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    /* ======================================
       NEG_ID
    ====================================== */

    $neg_id = isset($administrador_actual['neg_id'])
        ? intval($administrador_actual['neg_id'])
        : 0;

    if($neg_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id inválido'

        ], 400);

        return;
    }

    /* ======================================
       VALIDAR NEGOCIO
    ====================================== */

    $negocio = DB::queryFirstRow("

        SELECT neg_id

        FROM reg_neg

        WHERE neg_id = %i

        LIMIT 1

    ", $neg_id);

    if(!$negocio){

        Flight::json([

            'status' => 'error',

            'msg' => 'Negocio no encontrado'

        ], 404);

        return;
    }

    DB::startTransaction();

    try {

        $clientes_creados = [];

        for($i=0; $i<5; $i++){

            /* ======================================
               NOMBRE RANDOM
            ====================================== */

            $nombre = DB::queryFirstField("

                SELECT nombre

                FROM tt_nombre

                ORDER BY RAND()

                LIMIT 1

            ");

            /* ======================================
               APELLIDO RANDOM
            ====================================== */

            $apellido = DB::queryFirstField("

                SELECT apellido

                FROM tt_apellido

                ORDER BY RAND()

                LIMIT 1

            ");

            $nombres_apellidos = trim(

                $nombre
                . ' '
                . $apellido

            );

            /* ======================================
               DNI RANDOM
            ====================================== */

            do {

                $dni = strval(

                    rand(
                        10000000,
                        99999999
                    )

                );

                $existe_dni =
                    DB::queryFirstField("

                        SELECT cliente_id

                        FROM pos_cliente

                        WHERE dni = %s

                        LIMIT 1

                    ", $dni);

            } while($existe_dni);

            /* ======================================
               CELULAR RANDOM
            ====================================== */

            $celular =
                '9'
                . rand(
                    10000000,
                    99999999
                );

            /* ======================================
               EMAIL RANDOM
            ====================================== */

            $email_base = strtolower(

                preg_replace(

                    '/[^a-z0-9]/',

                    '',

                    str_replace(
                        ' ',
                        '',
                        $nombres_apellidos
                    )

                )

            );

            $email =
                $email_base
                . rand(10,999)
                . '@gmail.com';

            /* ======================================
               INSERT CLIENTE
            ====================================== */

            DB::insert(

                'pos_cliente',

                [

                    'nombres_apellidos' =>
                        $nombres_apellidos,

                    'dni' =>
                        $dni,

                    'cod_usu' => null,

                    'ruc' => null,

                    'direccion' =>
                        'Dirección referencial',

                    'celular' =>
                        $celular,

                    'email' =>
                        $email,

                    'is_activo' => 1,

                    'usu_id' => null,

                    'puesto' =>
                        'Cliente',

                    'distrito' =>
                        'Lima',

                    'map_lat' => null,

                    'map_lng' => null,

                    'neg_id' =>
                        $neg_id,

                    'borrado_el' => null

                ]

            );

            $cliente_id =
                DB::insertId();

            $clientes_creados[] = [

                'cliente_id' =>
                    $cliente_id,

                'nombres_apellidos' =>
                    $nombres_apellidos,

                'dni' =>
                    $dni,

                'celular' =>
                    $celular,

                'email' =>
                    $email

            ];

        }

        DB::commit();

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Clientes fantasma creados correctamente',

            'clientes' =>
                $clientes_creados

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ], 500);

    }

});

Flight::route(

'POST /Hc6Y/reiniciarCliente',

function(){

    include DEFINITION;

    autentificar_administrador();

    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(

        Flight::request()->getBody(),

        true

    ) ?: [];

    $cliente_id = intval(

        $d['cliente_id'] ?? 0

    );

    $neg_id = intval(

        $administrador_actual['neg_id']

    );

    if($cliente_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'cliente_id requerido'

        ],400);

        return;
    }

    $cliente = DB::queryFirstRow("

        SELECT

            cliente_id

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

    DB::startTransaction();

    try{

        $nombre = DB::queryFirstField("

            SELECT nombre

            FROM tt_nombre

            ORDER BY RAND()

            LIMIT 1

        ");

        $apellido = DB::queryFirstField("

            SELECT apellido

            FROM tt_apellido

            ORDER BY RAND()

            LIMIT 1

        ");

        $nombres_apellidos = trim(

            $nombre

            . ' '

            . $apellido

        );

        do{

            $dni = strval(

                rand(

                    10000000,

                    99999999

                )

            );

            $existe_dni = DB::queryFirstField("

                SELECT cliente_id

                FROM pos_cliente

                WHERE dni = %s

                AND cliente_id <> %i

                LIMIT 1

            ",

                $dni,

                $cliente_id

            );

        }while($existe_dni);

        $celular =

            '9'

            . rand(

                10000000,

                99999999

            );

        $email_base = strtolower(

            preg_replace(

                '/[^a-z0-9]/',

                '',

                str_replace(

                    ' ',

                    '',

                    $nombres_apellidos

                )

            )

        );

        $email =

            $email_base

            . rand(

                10,

                999

            )

            . '@gmail.com';

        DB::update(

            'pos_cliente',

            [

                'nombres_apellidos' =>

                    $nombres_apellidos,

                'dni' =>

                    $dni,

                'celular' =>

                    $celular,

                'email' =>

                    $email,

                'direccion' =>

                    'Dirección referencial',

                'cod_usu' => null,

                'ruc' => null,

                'usu_id' => null,

                'puesto' => 'Cliente',

                'distrito' => 'Lima',

                'map_lat' => null,

                'map_lng' => null

            ],

            "cliente_id=%i",

            $cliente_id

        );

        DB::commit();

        Flight::json([

            'status' => 'ok',

            'msg' =>

                'Cliente reiniciado correctamente'

        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>

                $e->getMessage()

        ],500);

    }

});