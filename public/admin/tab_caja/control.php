<?php
/* -------------------------------
// este es mi backend usando php8.2, flightphp y meekrodb2
 * ------------------------------- */

Flight::route('GET /caja', function () {

    include DEFINITION;
    autentificar_administrador();

    require_once VARPATH . '/public/admin/tab_caja/inicio.php';
});


/* -------------------------
   LISTAR USUARIOS DEL NEGOCIO
------------------------- */
Flight::route('GET /administrador/listar', function(){

    /* ----------------------------------
       CHARSET
    ---------------------------------- */

    DB::query("SET NAMES 'utf8mb4'");

    /* ----------------------------------
       AUTENTICACIÓN
    ---------------------------------- */

    autentificar_administrador();

    global $administrador_actual;

    /* ----------------------------------
       VALIDAR NEGOCIO
    ---------------------------------- */

    $neg_id = isset($administrador_actual['neg_id'])
        ? intval($administrador_actual['neg_id'])
        : 0;

    if ($neg_id <= 0) {

        Flight::json([
            'status' => 'error',
            'msg' => 'negocio inválido'
        ],400);

        return;
    }

    /* ----------------------------------
       CONSULTA
       reg_usu + deli_trabajador
    ---------------------------------- */

    $rows = DB::query("
        SELECT
            u.usu_id AS administrador_id,
            u.nombres_apellidos
        FROM deli_trabajador dt
        INNER JOIN reg_usu u
            ON u.usu_id = dt.usu_id
        WHERE dt.neg_id = %i
        AND dt.is_activo = 1
        AND u.is_activo = 1
        ORDER BY u.nombres_apellidos
    ", $neg_id);

    /* ----------------------------------
       RESPUESTA JSON
    ---------------------------------- */

    Flight::json($rows);

});


/* -------------------------
   ESTADO DE CAJAS DEL NEGOCIO
------------------------- */
Flight::route('GET /caja/estado', function(){

    DB::query("SET NAMES 'utf8mb4'");

    autentificar_administrador();

    global $administrador_actual;

    $rows = DB::query("
        SELECT 
            c.caja_id,
            c.usu_id,
            c.neg_id,
            u.nombres_apellidos AS administrador,
            c.fecha_apertura,
            c.fecha_cierre,
            c.efectivo_inicial,
            c.estado
        FROM pos_caja c
        INNER JOIN reg_usu u
            ON u.usu_id = c.usu_id
        WHERE c.neg_id = %i
        ORDER BY c.fecha_apertura DESC
    ", $administrador_actual['neg_id']);

    Flight::json($rows);
});


/* -------------------------
   ABRIR pos_caja
------------------------- */
Flight::route('POST /caja/abrir', function() {

    /* ----------------------------------
       AUTENTICAR ADMINISTRADOR
    ---------------------------------- */

    autentificar_administrador();

    global $administrador_actual;

    /* ----------------------------------
       OBTENER PAYLOAD
    ---------------------------------- */

    $d = json_decode(Flight::request()->getBody(), true);

    /* ----------------------------------
       VALIDAR DATOS
    ---------------------------------- */

    if (
        empty($d['administrador_id']) ||
        empty($d['administrador_origen_id'])
    ) {

        Flight::json([
            'error' => 'Debe seleccionar el administrador que recibe y el que entrega'
        ], 400);

        return;
    }

    $usu_id = intval($d['administrador_id']);

    /* ----------------------------------
       VALIDAR SI YA TIENE CAJA ABIERTA
    ---------------------------------- */

    $existe = DB::queryFirstField("
        SELECT COUNT(*)
        FROM pos_caja
        WHERE estado = 'ABIERTA'
        AND usu_id = %i
        AND neg_id = %i
    ", $usu_id, $administrador_actual['neg_id']);

    if ($existe > 0) {

        Flight::json([
            'error' => 'Este administrador ya tiene una pos_caja abierta'
        ], 409);

        return;
    }

    /* ----------------------------------
       INICIAR TRANSACCIÓN
    ---------------------------------- */

    DB::startTransaction();

    try {

        /* ----------------------------------
           CREAR CAJA
        ---------------------------------- */

        DB::insert('pos_caja', [

            'neg_id'           => $administrador_actual['neg_id'],
            'usu_id'           => $usu_id,
            'fecha_apertura'   => date('Y-m-d H:i:s'),
            'efectivo_inicial' => floatval($d['efectivo_inicial']),
            'estado'           => 'ABIERTA'

        ]);

        $caja_id = DB::insertId();

        /* ----------------------------------
           REGISTRAR MOVIMIENTO INICIAL
        ---------------------------------- */

        if (floatval($d['efectivo_inicial']) > 0) {

            DB::insert('pos_caja_movimiento', [

                'caja_id'          => $caja_id,
                'neg_id'           => $administrador_actual['neg_id'],
                'tipo'             => 'INGRESO',
                'origen'           => 'AJUSTE',
                'monto'            => floatval($d['efectivo_inicial']),
                'medio_pago'       => 'EFECTIVO',
                'referencia_id'    => intval($d['administrador_origen_id']),
                'referencia_tabla' => 'reg_usu',
                'fecha'            => date('Y-m-d H:i:s')

            ]);

        }

        /* ----------------------------------
           COMMIT
        ---------------------------------- */

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'caja_id' => $caja_id
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'error' => $e->getMessage()
        ], 500);

    }

});

/* -------------------------
   REGISTRAR MOVIMIENTO
------------------------- */
Flight::route('POST /caja/movimiento', function(){

    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    DB::insert('pos_caja_movimiento', [

        'caja_id'          => $d['caja_id'],
        'neg_id'           => $administrador_actual['neg_id'],
        'tipo'             => $d['tipo'],
        'origen'           => 'OTRO',
        'monto'            => $d['monto'],
        'medio_pago'       => $d['medio_pago'],
        'referencia_id'    => $d['administrador_ref'],
        'referencia_tabla' => 'reg_usu',
        'fecha'            => date('Y-m-d H:i:s')

    ]);

    Flight::json(['status'=>'ok']);

});


/* -------------------------
   CERRAR pos_caja
------------------------- */
Flight::route('POST /caja/cerrar', function(){

    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();

    DB::startTransaction();

    DB::insert('pos_caja_movimiento', [

        'caja_id'          => $d['caja_id'],
        'tipo'             => 'EGRESO',
        'origen'           => 'AJUSTE',
        'monto'            => $d['monto'],
        'medio_pago'       => 'EFECTIVO',
        'referencia_id'    => $d['administrador_recibe'],
        'referencia_tabla' => 'reg_usu',
        'fecha'            => date('Y-m-d H:i:s')

    ]);

    DB::update('pos_caja', [

        'fecha_cierre'    => date('Y-m-d H:i:s'),
        'efectivo_cierre' => $d['monto'],
        'estado'          => 'CERRADA'

    ], "caja_id=%i AND neg_id=%i", $d['caja_id'], $administrador_actual['neg_id']);

    DB::commit();

    Flight::json(['status'=>'ok']);

});


/* -------------------------
   MOVIMIENTOS DE pos_caja
------------------------- */
Flight::route('GET /caja/movimientos/@caja_id', function($caja_id){

    autentificar_administrador();

    global $administrador_actual;

    $rows = DB::query("

        SELECT 
            cm.caja_movimiento_id,
            cm.tipo,
            cm.origen,
            cm.monto,
            cm.medio_pago,
            u.nombres_apellidos AS administrador_origen,
            cm.fecha

        FROM pos_caja_movimiento cm

        LEFT JOIN reg_usu u
            ON u.usu_id = cm.referencia_id

        WHERE cm.caja_id = %i
        AND cm.neg_id = %i

        ORDER BY cm.fecha DESC

    ", $caja_id, $administrador_actual['neg_id']);

    Flight::json($rows);

});