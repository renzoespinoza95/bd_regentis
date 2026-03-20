<?php
// este es mi backend usando php8.2, flightphp y meekrodb2

/* ============================================
 *  VISTA PRINCIPAL /compras
 * ============================================ */
Flight::route('GET /compras', function () {

    include DEFINITION;
    autentificar_administrador();

    global $path_public;
    global $administrador_actual;

    include $path_public . '/admin/tab_compras/inicio.php';
});


/* ============================================
 *  FUNCIÓN: registrar movimiento de pos_inventario
 * ============================================ */
function registrar_movimiento_inventario(
    $producto_id,
    $tipo,
    $origen,
    $cantidad,
    $precio_unitario,
    $referencia_id,
    $referencia_tabla
) {

    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    // buscar pos_inventario
    $inv = DB::queryFirstRow(
        "SELECT inventario_id, stock_actual 
         FROM pos_inventario 
         WHERE product_id=%i
         AND neg_id=%i",
        $producto_id,
        $neg_id
    );

    // si NO existe, crear pos_inventario
    if (!$inv) {

        DB::insert('pos_inventario', [
            'neg_id'       => $neg_id,
            'product_id'   => $producto_id,
            'stock_actual' => 0,
            'stock_min'    => 0,
            'stock_max'    => 0
        ]);

        $stock_actual = 0;

    } else {

        $stock_actual = (int)$inv['stock_actual'];

    }

    // calcular nuevo stock
    if ($tipo === 'ENTRADA') {
        $nuevo_stock = $stock_actual + $cantidad;
    } elseif ($tipo === 'SALIDA') {
        $nuevo_stock = $stock_actual - $cantidad;
    } else {
        $nuevo_stock = $stock_actual;
    }

    // registrar movimiento
    DB::insert('pos_inventario_movimiento', [
        'neg_id'           => $neg_id,
        'product_id'       => $producto_id,
        'tipo'             => $tipo,
        'origen'           => $origen,
        'cantidad'         => $cantidad,
        'precio_unitario'  => $precio_unitario,
        'referencia_id'    => $referencia_id,
        'referencia_tabla' => $referencia_tabla,
        'stock_resultante' => $nuevo_stock,
        'fecha'            => date('Y-m-d H:i:s')
    ]);

    // actualizar pos_inventario
    DB::update(
        'pos_inventario',
        ['stock_actual' => $nuevo_stock],
        "product_id=%i AND neg_id=%i",
        $producto_id,
        $neg_id
    );
}



/* ============================================
 *  GET /pos_proveedor/listar
 * ============================================ */
Flight::route('GET /proveedor/listar', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $rows = DB::query("
        SELECT *
        FROM pos_proveedor
        WHERE is_activo=1
        ORDER BY nombre ASC
    ", $neg_id);

    Flight::json($rows);
});


/* ============================================
 *  GET /producto/listar (solo para compras)
 * ============================================ */
Flight::route('GET /producto/listar', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $sql = "
      SELECT 
        p.product_id,
        p.name,
        p.price,
        IFNULL(MAX(i.stock_actual),0) AS stock
      FROM product p
      LEFT JOIN pos_inventario i 
        ON i.product_id = p.product_id
        AND i.neg_id = %i
      WHERE p.neg_id = %i
      GROUP BY p.product_id
      ORDER BY p.name ASC
    ";

    Flight::json(DB::query($sql,$neg_id,$neg_id));
});


/* ============================================
 *  GET /pos_compra/listar
 * ============================================ */
Flight::route('GET /compra/listar', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $sql = "
        SELECT 
            c.compra_id,
            p.nombre AS razon_social,
            c.fecha_creacion AS fecha_compra,
            c.total_compra AS total,
            c.observaciones
        FROM pos_compra c
        LEFT JOIN pos_proveedor p 
            ON p.proveedor_id = c.proveedor_id
        WHERE c.neg_id = %i
        ORDER BY c.compra_id DESC
    ";

    Flight::json(DB::query($sql,$neg_id));
});



/* ============================================
 *  POST /pos_compra/crear
 * ============================================ */
Flight::route('POST /compra/crear', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $data = Flight::request()->data->getData();

    $proveedor_id = intval($data['proveedor_id']);

    if (!empty($data['fecha_compra'])) {
        $fecha = date('Y-m-d H:i:s', strtotime($data['fecha_compra']));
    } else {
        $fecha = date('Y-m-d H:i:s');
    }

    $items         = $data['items'] ?? [];
    $observaciones = $data['observaciones'] ?? '';

    if (empty($items)) {
        Flight::json(['status'=>'error','msg'=>'No hay items'], 400);
        return;
    }

    DB::startTransaction();

    try {

        DB::insert('pos_compra', [
            'neg_id'         => $neg_id,
            'proveedor_id'   => $proveedor_id,
            'fecha_creacion' => $fecha,
            'observaciones'  => $observaciones,
            'total_compra'   => 0
        ]);

        $compra_id = DB::insertId();
        $total = 0;

        foreach ($items as $it) {

            $producto_id = intval($it['product_id']);
            $cantidad    = intval($it['cantidad']);
            $costo       = floatval($it['costo_unitario']);

            $subtotal = $cantidad * $costo;
            $total   += $subtotal;

            DB::insert('pos_compra_detalle', [
                'compra_id'       => $compra_id,
                'product_id'      => $producto_id,
                'cantidad'        => $cantidad,
                'precio_unitario' => $costo,
                'subtotal'        => $subtotal
            ]);

            registrar_movimiento_inventario(
                $producto_id,
                'ENTRADA',
                'pos_compra',
                $cantidad,
                $costo,
                $compra_id,
                'pos_compra'
            );
        }

        DB::update(
            'pos_compra',
            ['total_compra' => $total],
            "compra_id=%i AND neg_id=%i",
            $compra_id,
            $neg_id
        );

        DB::commit();

        Flight::json([
            'status'=>'ok',
            'compra_id'=>$compra_id
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);

    }
});


/* ============================================
 *  GET /pos_compra/detalle/@id
 * ============================================ */
Flight::route('GET /compra/detalle/@id', function ($compra_id) {

    /* ----------------------------------
       AUTENTICAR
    ---------------------------------- */

    autentificar_administrador();

    global $administrador_actual;

    $neg_id = $administrador_actual['neg_id'];

    /* ----------------------------------
       CABECERA DE LA COMPRA
    ---------------------------------- */

    $cab = DB::queryFirstRow("
        SELECT 
            c.compra_id,
            p.nombre AS razon_social,
            c.fecha_creacion AS fecha_compra,
            c.total_compra AS total,
            c.observaciones
        FROM pos_compra c
        LEFT JOIN pos_proveedor p 
            ON p.proveedor_id = c.proveedor_id
        WHERE c.compra_id = %i
        AND c.neg_id = %i
    ", $compra_id, $neg_id);

    /* ----------------------------------
       DETALLE DE LA COMPRA
    ---------------------------------- */

    $det = DB::query("
        SELECT 
            d.compra_detalle_id,
            d.cantidad,
            d.precio_unitario AS costo_unitario,
            d.subtotal,
            pr.name AS producto
        FROM pos_compra_detalle d
        INNER JOIN pos_product pr 
            ON pr.product_id = d.product_id
        WHERE d.compra_id = %i
    ", $compra_id);

    /* ----------------------------------
       RESPUESTA
    ---------------------------------- */

    Flight::json([
        'cabecera' => $cab,
        'detalle'  => $det
    ]);

});

/* ============================================
 *  POST /pos_compra/editar
 * ============================================ */
Flight::route('POST /compra/editar', function () {

    autentificar_administrador();
    global $administrador_actual;

    $neg_id = intval($administrador_actual['neg_id']);

    $data = Flight::request()->data->getData();

    DB::update(
        'pos_compra',
        [
            'observaciones' => $data['observaciones']
        ],
        "compra_id=%i AND neg_id=%i",
        intval($data['compra_id']),
        $neg_id
    );

    Flight::json(['status'=>'ok']);
});



/* ============================================
 *  POST /pos_compra/eliminar
 * ============================================ */
Flight::route('POST /compra/eliminar', function () {

    $compra_id = intval(Flight::request()->data->compra_id);

    DB::startTransaction();
    try {

        // obtener detalle
        $det = DB::query("SELECT * FROM pos_compra_detalle WHERE compra_id=%i", $compra_id);

        foreach ($det as $it) {
            registrar_movimiento_inventario(
                $it['product_id'],
                'SALIDA',
                'DEVOLUCION',
                $it['cantidad'],
                $it['precio_unitario'],
                $compra_id,
                'pos_compra'
            );
        }

        DB::delete('pos_compra_detalle', "compra_id=%i", $compra_id);
        DB::delete('pos_compra', "compra_id=%i", $compra_id);

        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});


/* ============================================
 *  GET /pos_inventario/movimientos/@producto_id
 * ============================================ */
Flight::route('GET /inventario/movimientos/@producto_id', function ($producto_id) {

    $rows = DB::query("
        SELECT * 
        FROM pos_inventario_movimiento
        WHERE product_id=%i
        ORDER BY fecha DESC
    ", $producto_id);

    Flight::json($rows);
});

Flight::route('GET /imp_compras_fecha', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    $request = Flight::request();

    $ini = trim($request->query->ini ?? $_GET['ini'] ?? '');
    $fin = trim($request->query->fin ?? $_GET['fin'] ?? '');

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }


    $fini = util::fecha_barra($ini);
    $ffin = util::fecha_barra($fin);

      $rows = DB::query("
        SELECT 
            c.compra_id,
            p.nombre AS pos_proveedor,
            CONCAT(
                LPAD(DAY(c.fecha_creacion), 2, '0'), '/',
                LPAD(MONTH(c.fecha_creacion), 2, '0'), '/',
                YEAR(c.fecha_creacion), ' ',
                LPAD(HOUR(c.fecha_creacion), 2, '0'), ':',
                LPAD(MINUTE(c.fecha_creacion), 2, '0')
            ) AS fecha_creacion,
            c.total_compra
        FROM pos_compra c
        LEFT JOIN pos_proveedor p ON p.proveedor_id = c.proveedor_id
        WHERE c.fecha_creacion BETWEEN %s AND %s
        ORDER BY c.fecha_creacion
    ", $ini . ' 00:00:00', $fin . ' 23:59:59');



    $template_data['informacion'] = [[
        'razon_social'  => 'CLUB SOCIAL LIMA NORTE S.A.C',
        'ruc'           => vari('RUC'),
        'titulo_reporte'=> 'REPORTE DE COMPRAS DEL ' . $fini . ' AL ' . $ffin,
        'fecha'         => date('d/m/Y H:i'),
        'logo'           => $varhost . '/public/admin/login/images/logo_login.png',
        'total_items'   => count($rows)
    ]];

    $i = 1;
    foreach ($rows as &$r) {
        $r['indice'] = $i++;
    }

    $template_data['listado'] = $rows;


    $total_general = 0;
    foreach ($rows as $r) {
        $total_general += $r['total_compra'];
    }

    $template_data['total_general'] = number_format($total_general, 2);


    $html = (new Mustache)->render(
        file_get_contents(VARPATH.'/public/reportes/reporte_html/imp_compras_fecha.html'),
        $template_data
    );

    $pdf = VARPATH.'/public/reportes/archivos_temporales/compras_'.time().'.pdf';
    $wkh_pdf->addPage($html);
    exec($wkh_pdf->getCommand($pdf));

    Flight::redirect($varhost.'/public/reportes/archivos_temporales/'.basename($pdf));
});


Flight::route('GET /imp_compras_fecha_excel', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=compras.xls");

    $request = Flight::request();

    $ini = trim($request->query->ini ?? $_GET['ini'] ?? '');
    $fin = trim($request->query->fin ?? $_GET['fin'] ?? '');

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $ini) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
        Flight::halt(400, 'Formato de fecha inválido');
    }

    $rows = DB::query("
        SELECT 
            c.compra_id,
            p.nombre,
            c.fecha_creacion,
            c.total_compra
        FROM pos_compra c
        LEFT JOIN pos_proveedor p ON p.proveedor_id = c.proveedor_id
        WHERE c.fecha_creacion BETWEEN %s AND %s
        ORDER BY c.fecha_creacion ASC
    ", $ini.' 00:00:00', $fin.' 23:59:59');

    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>pos_proveedor</th><th>Fecha</th><th>Total</th></tr>";

    foreach ($rows as $r) {
        echo "<tr>
            <td>{$r['compra_id']}</td>
            <td>{$r['nombre']}</td>
            <td>{$r['fecha_creacion']}</td>
            <td>{$r['total_compra']}</td>
        </tr>";
    }

    echo "</table>";
});


Flight::route('POST /compra/agregar-items', function(){

  $data = Flight::request()->data->getData();
  DB::startTransaction();

  foreach($data['items'] as $it){
    $subtotal = $it['cantidad']*$it['costo_unitario'];

    DB::insert('pos_compra_detalle',[
      'compra_id'=>$data['compra_id'],
      'product_id'=>$it['product_id'],
      'cantidad'=>$it['cantidad'],
      'precio_unitario'=>$it['costo_unitario'],
      'subtotal'=>$subtotal
    ]);

    registrar_movimiento_inventario(
      $it['product_id'],'ENTRADA','AJUSTE',
      $it['cantidad'],$it['costo_unitario'],
      $data['compra_id'],'pos_compra'
    );
  }

  DB::query("
    UPDATE pos_compra
    SET total_compra = (
      SELECT SUM(subtotal) FROM pos_compra_detalle WHERE compra_id=%i
    )
    WHERE compra_id=%i
  ",$data['compra_id'],$data['compra_id']);

  DB::commit();
  Flight::json(['status'=>'ok']);
});

Flight::route('GET /compra/items/@compra_id', function ($compra_id) {

    $rows = DB::query("
        SELECT 
            d.product_id,
            p.name AS producto,
            d.cantidad,
            d.precio_unitario
        FROM pos_compra_detalle d
        INNER JOIN pos_product p ON p.product_id = d.product_id
        WHERE d.compra_id = %i
    ", $compra_id);

    Flight::json($rows);
});

/* ============================================

* POST /pos_compra/eliminar
* ============================================ */
  Flight::route('POST /compra/eliminar', function () {

  autentificar_administrador();
  global $administrador_actual;

  $neg_id = intval($administrador_actual['neg_id']);

  $compra_id = intval(Flight::request()->data->compra_id);

  DB::startTransaction();

  try {
  
   // obtener detalle
   $det = DB::query("
       SELECT *
       FROM pos_compra_detalle
       WHERE compra_id=%i
   ", $compra_id);

   foreach ($det as $it) {

       registrar_movimiento_inventario(
           $it['product_id'],
           'SALIDA',
           'DEVOLUCION',
           $it['cantidad'],
           $it['precio_unitario'],
           $compra_id,
           'pos_compra'
       );

   }

   DB::delete('pos_compra_detalle', "compra_id=%i", $compra_id);

   DB::delete(
       'pos_compra',
       "compra_id=%i AND neg_id=%i",
       $compra_id,
       $neg_id
   );

   DB::commit();

   Flight::json(['status'=>'ok']);
  

  } catch (Exception $e) {

  
   DB::rollback();

   Flight::json([
       'status'=>'error',
       'msg'=>$e->getMessage()
   ],500);
  

  }

});

/* ============================================

* GET /pos_inventario/movimientos/@producto_id
* ============================================ */
  Flight::route('GET /pos_inventario/movimientos/@producto_id', function ($producto_id) {

  autentificar_administrador();
  global $administrador_actual;

  $neg_id = intval($administrador_actual['neg_id']);

  $rows = DB::query("
  SELECT *
  FROM pos_inventario_movimiento
  WHERE product_id=%i
  AND neg_id=%i
  ORDER BY fecha DESC
  ", $producto_id,$neg_id);

  Flight::json($rows);
  });

/* ============================================

* GET /imp_compras_fecha
* ============================================ */
  Flight::route('GET /imp_compras_fecha', function(){

  include DEFINITION;
  autentificar_administrador();

  global $administrador_actual;
  global $varhost;

  $neg_id = intval($administrador_actual['neg_id']);

  $request = Flight::request();

  $ini = trim($request->query->ini ?? $_GET['ini'] ?? '');
  $fin = trim($request->query->fin ?? $_GET['fin'] ?? '');

  if ($ini === '' || $fin === '') {
  Flight::halt(400, 'Debe enviar las fechas ini y fin');
  }

  $fini = util::fecha_barra($ini);
  $ffin = util::fecha_barra($fin);

  $rows = DB::query("
  SELECT
  c.compra_id,
  p.nombre AS pos_proveedor,
  CONCAT(
  LPAD(DAY(c.fecha_creacion),2,'0'),'/',
  LPAD(MONTH(c.fecha_creacion),2,'0'),'/',
  YEAR(c.fecha_creacion),' ',
  LPAD(HOUR(c.fecha_creacion),2,'0'),':',
  LPAD(MINUTE(c.fecha_creacion),2,'0')
  ) AS fecha_creacion,
  c.total_compra
  FROM pos_compra c
  LEFT JOIN pos_proveedor p
  ON p.proveedor_id = c.proveedor_id
  WHERE c.neg_id=%i
  AND c.fecha_creacion BETWEEN %s AND %s
  ORDER BY c.fecha_creacion
  ", $neg_id,$ini.' 00:00:00',$fin.' 23:59:59');

  $template_data['informacion'] = [[
  'razon_social'  => 'CLUB SOCIAL LIMA NORTE S.A.C',
  'ruc'           => vari('RUC'),
  'titulo_reporte'=> 'REPORTE DE COMPRAS DEL '.$fini.' AL '.$ffin,
  'fecha'         => date('d/m/Y H:i'),
  'logo'          => $varhost.'/public/admin/login/images/logo_login.png',
  'total_items'   => count($rows)
  ]];

  $i = 1;
  foreach ($rows as &$r) {
  $r['indice'] = $i++;
  }

  $template_data['listado'] = $rows;

  $total_general = 0;

  foreach ($rows as $r) {
  $total_general += $r['total_compra'];
  }

  $template_data['total_general'] = number_format($total_general,2);

  $html = (new Mustache)->render(
  file_get_contents(VARPATH.'/public/reportes/reporte_html/imp_compras_fecha.html'),
  $template_data
  );

  $pdf = VARPATH.'/public/reportes/archivos_temporales/compras_'.time().'.pdf';

  $wkh_pdf->addPage($html);

  exec($wkh_pdf->getCommand($pdf));

  Flight::redirect($varhost.'/public/reportes/archivos_temporales/'.basename($pdf));

});

/* ============================================

* GET /imp_compras_fecha_excel
* ============================================ */
  Flight::route('GET /imp_compras_fecha_excel', function(){

  include DEFINITION;
  autentificar_administrador();

  global $administrador_actual;

  $neg_id = intval($administrador_actual['neg_id']);

  header("Content-Type: application/vnd.ms-excel");
  header("Content-Disposition: attachment; filename=compras.xls");

  $request = Flight::request();

  $ini = trim($request->query->ini ?? $_GET['ini'] ?? '');
  $fin = trim($request->query->fin ?? $_GET['fin'] ?? '');

  if ($ini === '' || $fin === '') {
  Flight::halt(400,'Debe enviar las fechas ini y fin');
  }

  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$ini) ||
  !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fin)) {
  Flight::halt(400,'Formato de fecha inválido');
  }

  $rows = DB::query("
  SELECT
  c.compra_id,
  p.nombre,
  c.fecha_creacion,
  c.total_compra
  FROM pos_compra c
  LEFT JOIN pos_proveedor p
  ON p.proveedor_id = c.proveedor_id
  WHERE c.neg_id=%i
  AND c.fecha_creacion BETWEEN %s AND %s
  ORDER BY c.fecha_creacion ASC
  ", $neg_id,$ini.' 00:00:00',$fin.' 23:59:59');

  echo "<table border='1'>";
  echo "<tr><th>ID</th><th>pos_proveedor</th><th>Fecha</th><th>Total</th></tr>";

  foreach ($rows as $r) {

  
   echo "<tr>
       <td>{$r['compra_id']}</td>
       <td>{$r['nombre']}</td>
       <td>{$r['fecha_creacion']}</td>
       <td>{$r['total_compra']}</td>
   </tr>";
  

  }

  echo "</table>";

});

/* ============================================

* POST /pos_compra/agregar-items
* ============================================ */
  Flight::route('POST /compra/agregar-items', function(){

autentificar_administrador();
global $administrador_actual;

$neg_id = intval($administrador_actual['neg_id']);

$data = Flight::request()->data->getData();

DB::startTransaction();

foreach($data['items'] as $it){


$subtotal = $it['cantidad']*$it['costo_unitario'];

DB::insert('pos_compra_detalle',[

  'compra_id'=>$data['compra_id'],
  'product_id'=>$it['product_id'],
  'cantidad'=>$it['cantidad'],
  'precio_unitario'=>$it['costo_unitario'],
  'subtotal'=>$subtotal

]);

registrar_movimiento_inventario(

  $it['product_id'],
  'ENTRADA',
  'AJUSTE',
  $it['cantidad'],
  $it['costo_unitario'],
  $data['compra_id'],
  'pos_compra'

);


}

DB::query("
UPDATE pos_compra
SET total_compra = (
SELECT SUM(subtotal)
FROM pos_compra_detalle
WHERE compra_id=%i
)
WHERE compra_id=%i
AND neg_id=%i
",$data['compra_id'],$data['compra_id'],$neg_id);

DB::commit();

Flight::json(['status'=>'ok']);

});

/* ============================================

* GET /pos_compra/items/@compra_id
* ============================================ */
  Flight::route('GET /compra/items/@compra_id', function ($compra_id) {

  autentificar_administrador();
  global $administrador_actual;

  $neg_id = intval($administrador_actual['neg_id']);

  $rows = DB::query("

  
   SELECT 
       d.product_id,
       p.name AS producto,
       d.cantidad,
       d.precio_unitario
   FROM pos_compra_detalle d
   INNER JOIN product p 
       ON p.product_id = d.product_id
   INNER JOIN pos_compra c
       ON c.compra_id = d.compra_id
   WHERE d.compra_id = %i
   AND c.neg_id = %i
  

  ", $compra_id,$neg_id);

  Flight::json($rows);

});
