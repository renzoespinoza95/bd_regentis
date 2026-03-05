<?php
/* -------------------------------
// este es mi backend usando php8.2, flightphp y meekrodb2
 * ------------------------------- */
Flight::route('GET /caja', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;                          // asegúrate de tener esta var en tu bootstrap
    include $path_public . '/admin/tab_caja/inicio.php';
});

/* -------------------------
   LISTAR ADMINISTRADORES
------------------------- */
Flight::route('GET /administrador/listar', function(){
    DB::query("SET NAMES 'utf8mb4'");
    $rows = DB::query("
        SELECT administrador_id, nombres_apellidos
        FROM administradortbl
        WHERE is_activo = 1
        ORDER BY nombres_apellidos
    ");
    Flight::json($rows);
});

/* -------------------------
   ESTADO DE CAJAS ABIERTAS
------------------------- */
Flight::route('GET /caja/estado', function(){
    DB::query("SET NAMES 'utf8mb4'");
    $rows = DB::query("
        SELECT c.caja_id,
               c.administrador_id,
               a.nombres_apellidos AS administrador,
               c.fecha_apertura,
               c.fecha_cierre,
               c.efectivo_inicial,
               c.estado
        FROM caja c
        INNER JOIN administradortbl a 
          ON a.administrador_id = c.administrador_id
        ORDER BY c.fecha_apertura DESC
    ");
    Flight::json($rows);
});


/* -------------------------
   ABRIR CAJA
------------------------- */
Flight::route('POST /caja/abrir', function() {

    $d = json_decode(Flight::request()->getBody(), true);

    // Validación de administradores (monto puede ser 0)
    if (
        empty($d['administrador_id']) ||
        empty($d['administrador_origen_id'])
    ) {
        Flight::json([
            'error'=>'Debe seleccionar el administrador que recibe y el que entrega'
        ], 400);
        return;
    }

    // 🔒 Verificar si el administrador YA tiene caja abierta
    $existe = DB::queryFirstField("
        SELECT COUNT(*) 
        FROM caja
        WHERE estado = 'ABIERTA'
          AND administrador_id = %i
    ", $d['administrador_id']);

    if ($existe > 0) {
        Flight::json([
            'error'=>'Este administrador ya tiene una caja abierta. Debe cerrarla primero.'
        ], 409);
        return;
    }

    DB::startTransaction();

    DB::insert('caja', [
        'administrador_id' => $d['administrador_id'],
        'fecha_apertura'   => date('Y-m-d H:i:s'),
        'efectivo_inicial' => floatval($d['efectivo_inicial']), // puede ser 0
        'estado'           => 'ABIERTA'
    ]);

    $caja_id = DB::insertId();

    // Registrar movimiento inicial SOLO si hay monto > 0
    if (floatval($d['efectivo_inicial']) > 0) {
        DB::insert('caja_movimiento', [
            'caja_id'          => $caja_id,
            'tipo'             => 'INGRESO',
            'origen'           => 'AJUSTE',
            'monto'            => $d['efectivo_inicial'],
            'medio_pago'       => 'EFECTIVO',
            'referencia_id'    => $d['administrador_origen_id'],
            'referencia_tabla' => 'administradortbl',
            'fecha'            => date('Y-m-d H:i:s')
        ]);
    }

    DB::commit();
    Flight::json(['status'=>'ok']);
});


/* -------------------------
   REGISTRAR MOVIMIENTO
------------------------- */
Flight::route('POST /caja/movimiento', function(){
    $d = Flight::request()->data->getData();

    DB::insert('caja_movimiento', [
        'caja_id'          => $d['caja_id'],
        'tipo'             => $d['tipo'], // INGRESO / EGRESO
        'origen'           => 'OTRO',
        'monto'            => $d['monto'],
        'medio_pago'       => $d['medio_pago'],
        'referencia_id'    => $d['administrador_ref'],
        'referencia_tabla' => 'administradortbl',
        'fecha'            => date('Y-m-d H:i:s')
    ]);

    Flight::json(['status'=>'ok']);
});

/* -------------------------
   CERRAR CAJA
------------------------- */
Flight::route('POST /caja/cerrar', function(){
    $d = Flight::request()->data->getData();

    DB::startTransaction();

    // Registrar egreso final
    DB::insert('caja_movimiento', [
        'caja_id'          => $d['caja_id'],
        'tipo'             => 'EGRESO',
        'origen'           => 'AJUSTE',
        'monto'            => $d['monto'],
        'medio_pago'       => 'EFECTIVO',
        'referencia_id'    => $d['administrador_recibe'],
        'referencia_tabla' => 'administradortbl',
        'fecha'            => date('Y-m-d H:i:s')
    ]);

    // Cerrar caja
    DB::update('caja', [
        'fecha_cierre'   => date('Y-m-d H:i:s'),
        'efectivo_cierre'=> $d['monto'],
        'estado'         => 'CERRADA'
    ], "caja_id=%i", $d['caja_id']);

    DB::commit();
    Flight::json(['status'=>'ok']);
});

Flight::route('GET /caja/movimientos/@caja_id', function($caja_id){
  $rows = DB::query("
    SELECT 
      cm.caja_movimiento_id,
      cm.tipo,
      cm.origen,
      cm.monto,
      cm.medio_pago,
      a.nombres_apellidos AS administrador_origen,
      cm.fecha
    FROM caja_movimiento cm
    LEFT JOIN administradortbl a 
      ON a.administrador_id = cm.referencia_id
    WHERE cm.caja_id = %i
    ORDER BY cm.fecha DESC
  ", $caja_id);

  Flight::json($rows);
});
