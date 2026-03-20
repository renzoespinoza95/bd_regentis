<?php
// Backend PHP 8.2 + FlightPHP + MeekroDB2

/* ======================================
   PÁGINA PRINCIPAL ÓRDENES
====================================== */
Flight::route('GET /order', function () {
    include DEFINITION;
    autentificar_administrador();
    global $path_public;
    include $path_public . '/admin/tab_order/inicio.php';
});


/* ======================================
   LISTAR ÓRDENES
====================================== */
Flight::route('GET /product_order/listar', function(){

    autentificar_administrador();
    global $administrador_actual;

    $rows = DB::query("
        SELECT 
          po.product_order_id,
          po.serial,
          po.total_fees,
          po.modo_order_id,
          mo.nombre AS modo_order,
          cl.nombre AS cliente,
          m.nombre AS mesa_nombre,
          u.nombres_apellidos AS administrador,
          tp.descripcion AS tipo_pago,
          po.fecha_creacion
        FROM pos_product_order po
        LEFT JOIN pos_cliente cl ON cl.cliente_id = po.cliente_id
        LEFT JOIN resto_mesa m ON m.mesa_id = po.mesa_id
        LEFT JOIN pos_modo_order mo ON mo.modo_order_id = po.modo_order_id
        LEFT JOIN pos_tipo_pago tp ON tp.tipo_pago_id = po.tipo_pago_id
        LEFT JOIN reg_usu u ON u.usu_id = po.usu_id
        WHERE po.neg_id = %i
        ORDER BY po.product_order_id DESC
    ", $administrador_actual['neg_id']);


    foreach($rows as &$r){
        $r['fecha'] = date('d/m/Y H:i', strtotime($r['fecha_creacion']));
    }


    Flight::json($rows);
});


/* ======================================
   GENERAR SERIAL ORDEN
====================================== */
function generarCodigoOrden(){
  return strtoupper(bin2hex(random_bytes(4)));
}


/* ======================================
   CREAR ORDEN
====================================== */
Flight::route('POST /product_order/crear', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $usu_id = $administrador_actual['usu_id'];
    $neg_id = $administrador_actual['neg_id'];

    $caja = DB::queryFirstRow("
        SELECT *
        FROM pos_caja
        WHERE usu_id = %i
        AND neg_id = %i
        AND DATE(fecha_apertura) = CURDATE()
        AND estado = 'ABIERTA'
        ORDER BY fecha_apertura DESC
        LIMIT 1
    ", $usu_id, $neg_id);

    if (!$caja) {
        Flight::json([
            'status'=>'error',
            'msg'=>'La caja de este usuario está cerrada'
        ],403);
        return;
    }

    $d = Flight::request()->data->getData();

    if (empty($d['cliente_id'])) {
        Flight::json(['status'=>'error','msg'=>'Debe seleccionar cliente'],400);
        return;
    }

    if (empty($d['tipo_pago_id'])) {
        Flight::json(['status'=>'error','msg'=>'Debe seleccionar tipo de pago'],400);
        return;
    }

    if (empty($d['items']) || !is_array($d['items'])) {
        Flight::json(['status'=>'error','msg'=>'Debe agregar productos'],400);
        return;
    }

    $mesa_id = isset($d['mesa_id']) ? (int)$d['mesa_id'] : -1;

    if ($mesa_id < 0) {
        Flight::json(['status'=>'error','msg'=>'Seleccione mesa o DIRECTO'],400);
        return;
    }

    $modo_order_id = 1;
    $mesa_id_db = null;
    $fecha_inicio = null;

    if ($mesa_id > 0) {

        $ocupada = DB::queryFirstField("
            SELECT COUNT(*)
            FROM pos_product_order
            WHERE mesa_id=%i AND modo_order_id=2 AND neg_id=%i
        ", $mesa_id, $neg_id);

        if ($ocupada) {
            Flight::json(['status'=>'error','msg'=>'Mesa ocupada'],409);
            return;
        }

        $modo_order_id = 2;
        $mesa_id_db = $mesa_id;
        $fecha_inicio = date('Y-m-d H:i:s');

        DB::update('resto_mesa',
            ['estado'=>'OCUPADA'],
            "mesa_id=%i AND neg_id=%i",
            $mesa_id,$neg_id
        );
    }

    DB::startTransaction();

    try {

        $now = time()*1000;

        DB::insert('pos_product_order',[
            'serial'=>generarCodigoOrden(),
            'usu_id'=>$usu_id,
            'cliente_id'=>$d['cliente_id'],
            'caja_id'=>$caja['caja_id'],
            'tipo_pago_id'=>$d['tipo_pago_id'],
            'mesa_id'=>$mesa_id_db,
            'modo_order_id'=>$modo_order_id,
            'total_fees'=>0,
            'tax'=>0,
            'fecha_inicio'=>$fecha_inicio,
            'fecha_creacion'=>date('Y-m-d H:i:s'),
            'fecha_modificacion'=>date('Y-m-d H:i:s'),
            'created_at'=>$now,
            'last_update'=>$now,
            'neg_id'=>$neg_id
        ]);

        $order_id = DB::insertId();

        foreach ($d['items'] as $i) {

            DB::insert('pos_product_order_detail',[
                'order_id'=>$order_id,
                'product_id'=>$i['product_id'],
                'product_name'=>DB::queryFirstField(
                    "SELECT name FROM pos_product WHERE product_id=%i AND neg_id=%i",
                    $i['product_id'],$neg_id
                ),
                'amount'=>$i['amount'],
                'price_item'=>$i['price_item'],
                'created_at'=>$now,
                'last_update'=>$now,
                'fecha_creacion'=>date('Y-m-d H:i:s'),
                'fecha_modificacion'=>date('Y-m-d H:i:s')
            ]);

            registrar_movimiento_inventario(
                $i['product_id'],
                'SALIDA',
                'VENTA',
                $i['amount'],
                $i['price_item'],
                $order_id,
                'pos_product_order'
            );
        }

        recalcular_total_orden($order_id);

        DB::commit();

        Flight::json([
            'status'=>'ok',
            'product_order_id'=>$order_id,
            'mesa_id'=>$mesa_id_db
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});


/* ======================================
   EDITAR ORDEN
====================================== */
Flight::route('POST /product_order/editar', function(){

    autentificar_administrador();
    global $administrador_actual;

    $d = Flight::request()->data->getData();
    $now = time()*1000;

    DB::update('pos_product_order',[
        'last_update'=>$now,
        'fecha_modificacion'=>date("Y-m-d H:i:s")
    ],
    "product_order_id=%i AND neg_id=%i",
    $d['product_order_id'],$administrador_actual['neg_id']);

    Flight::json(['status'=>'ok']);
});


/* ======================================
   ELIMINAR ORDEN
====================================== */
Flight::route('POST /product_order/eliminar', function(){

    autentificar_administrador();
    global $administrador_actual;

    $d = Flight::request()->data->getData();

    DB::delete(
        'pos_product_order',
        "product_order_id=%i AND neg_id=%i",
        $d['product_order_id'],
        $administrador_actual['neg_id']
    );

    Flight::json(['status'=>'ok']);
});


/* ======================================
   DETALLE ORDEN
====================================== */
Flight::route('GET /yup/product_order/detalle/@id', function($id){

    autentificar_administrador();
    global $administrador_actual;

    $order = DB::queryFirstRow("
        SELECT 
            o.*,
            tp.descripcion AS tipo_pago,
            CONCAT(c.dni,' - ',c.nombre) AS cliente
        FROM pos_product_order o
        LEFT JOIN pos_tipo_pago tp 
               ON tp.tipo_pago_id = o.tipo_pago_id
        LEFT JOIN pos_cliente c
               ON c.cliente_id = o.cliente_id
        WHERE o.product_order_id = %i
        AND o.neg_id = %i
    ", $id, $administrador_actual['neg_id']);

    $det = DB::query("
        SELECT 
            d.*,
            p.name AS product_name
        FROM pos_product_order_detail d
        LEFT JOIN pos_product p 
               ON p.product_id = d.product_id
        WHERE d.order_id = %i
        ORDER BY d.product_order_detail_id ASC
    ", $id);

    Flight::json([
        'order'=>$order,
        'detalles'=>$det
    ]);
});


/* ======================================
   CREAR ITEM
====================================== */
Flight::route('POST /product_order_detail/crear', function(){

    autentificar_administrador();
    global $administrador_actual;

    $d = Flight::request()->data->getData();
    $now = time()*1000;

    DB::startTransaction();

    try {

        DB::insert('pos_product_order_detail',[
            'order_id'=>$d['order_id'],
            'product_id'=>$d['product_id'],
            'product_name'=>DB::queryFirstField(
                "SELECT name FROM pos_product WHERE product_id=%i AND neg_id=%i",
                $d['product_id'],$administrador_actual['neg_id']
            ),
            'amount'=>$d['amount'],
            'price_item'=>$d['price_item'],
            'created_at'=>$now,
            'last_update'=>$now,
            'fecha_creacion'=>date('Y-m-d H:i:s'),
            'fecha_modificacion'=>date('Y-m-d H:i:s')
        ]);

        registrar_movimiento_inventario(
            $d['product_id'],
            'SALIDA',
            'VENTA',
            $d['amount'],
            $d['price_item'],
            $d['order_id'],
            'pos_product_order'
        );

        recalcular_total_orden($d['order_id']);

        DB::commit();

        Flight::json(['status'=>'ok']);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});


/* ======================================
   ELIMINAR ITEM
====================================== */
Flight::route('POST /product_order_detail/eliminar', function(){

    autentificar_administrador();
    global $administrador_actual;

    $d = Flight::request()->data->getData();

    $item = DB::queryFirstRow(
        "SELECT * FROM pos_product_order_detail WHERE product_order_detail_id=%i",
        $d['product_order_detail_id']
    );

    DB::startTransaction();

    try {

        registrar_movimiento_inventario(
            $item['product_id'],
            'ENTRADA',
            'DEVOLUCION',
            $item['amount'],
            $item['price_item'],
            $item['order_id'],
            'pos_product_order'
        );

        DB::delete(
            'pos_product_order_detail',
            "product_order_detail_id=%i",
            $d['product_order_detail_id']
        );

        recalcular_total_orden($item['order_id']);

        DB::commit();

        Flight::json(['status'=>'ok']);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});


/* ======================================
   RECALCULAR TOTAL
====================================== */
function recalcular_total_orden($order_id){

    $total = DB::queryFirstField("
        SELECT IFNULL(SUM(amount*price_item),0)
        FROM pos_product_order_detail
        WHERE order_id=%i
    ", $order_id);

    DB::update('pos_product_order',[
        'total_fees'=>$total,
        'fecha_modificacion'=>date('Y-m-d H:i:s'),
        'last_update'=>time()*1000
    ],"product_order_id=%i",$order_id);

}

/* ======================================
   EDITAR ITEM DE ORDEN
====================================== */
Flight::route('POST /product_order_detail/editar', function () {

  include DEFINITION;
  autentificar_administrador();
  global $administrador_actual;

  $d = Flight::request()->data->getData();

  $old = DB::queryFirstRow(
    "SELECT d.*
     FROM pos_product_order_detail d
     INNER JOIN pos_product_order o ON o.product_order_id = d.order_id
     WHERE d.product_order_detail_id=%i
     AND o.neg_id=%i",
    $d['product_order_detail_id'],
    $administrador_actual['neg_id']
  );

  DB::startTransaction();
  try {

    registrar_movimiento_inventario(
      $old['product_id'],
      'ENTRADA',
      'AJUSTE',
      $old['amount'],
      $old['price_item'],
      $old['order_id'],
      'pos_product_order'
    );

    registrar_movimiento_inventario(
      $d['product_id'],
      'SALIDA',
      'VENTA',
      $d['amount'],
      $d['price_item'],
      $old['order_id'],
      'pos_product_order'
    );

    actualizar_estado_orden($old['order_id'], 'EDITADO');

    DB::update('pos_product_order_detail',[
      'amount'=>$d['amount'],
      'price_item'=>$d['price_item'],
      'last_update'=>time()*1000,
      'fecha_modificacion'=>date('Y-m-d H:i:s')
    ],"product_order_detail_id=%i",$d['product_order_detail_id']);

    recalcular_total_orden($old['order_id']);

    DB::commit();
    Flight::json(['status'=>'ok']);

  } catch(Exception $e){
    DB::rollback();
    Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
  }
});


/* ======================================
   ACTUALIZAR ESTADO ORDEN
====================================== */
function actualizar_estado_orden($order_id, $estado){

    DB::update(
        'pos_product_order',
        [
            'status'=>$estado,
            'fecha_modificacion'=>date('Y-m-d H:i:s'),
            'last_update'=>time()*1000
        ],
        "product_order_id=%i",
        $order_id
    );
}


/* ======================================
   LISTAR CLIENTES
====================================== */
Flight::route('GET /cliente/listar', function(){

    /* ----------------------------------
       DEFINICIONES
    ---------------------------------- */

    include DEFINITION;

    /* ----------------------------------
       AUTENTICACIÓN
    ---------------------------------- */

    autentificar_administrador();

    global $administrador_actual;

    $neg_id = $administrador_actual['neg_id'];

    /* ----------------------------------
       CONSULTA
    ---------------------------------- */

    $rows = DB::query("
        SELECT 
            c.cliente_id,
            c.dni,
            c.nombre
        FROM reg_negxclie nx
        INNER JOIN pos_cliente c
            ON c.cliente_id = nx.cliente_id
        WHERE nx.neg_id = %i
        AND c.is_activo = 1
        ORDER BY c.nombre
    ", $neg_id);

    /* ----------------------------------
       RESPUESTA
    ---------------------------------- */

    Flight::json($rows);

});

/* ======================================
   CREAR CLIENTE
====================================== */
Flight::route('POST /cliente/crear', function(){

    /* ----------------------------------
       DEFINICIONES
    ---------------------------------- */

    include DEFINITION;

    /* ----------------------------------
       AUTENTICACIÓN
    ---------------------------------- */

    autentificar_administrador();

    global $administrador_actual;

    $neg_id = $administrador_actual['neg_id'];

    /* ----------------------------------
       OBTENER DATA
    ---------------------------------- */

    $d = Flight::request()->data->getData();

    if (empty($d['dni']) || empty($d['nombre'])) {

        Flight::json([
            'error' => 'Debe ingresar DNI y nombre'
        ],400);

        return;
    }

    /* ----------------------------------
       BUSCAR CLIENTE GLOBAL
    ---------------------------------- */

    $cliente = DB::queryFirstRow("
        SELECT cliente_id
        FROM pos_cliente
        WHERE dni=%s
        LIMIT 1
    ", $d['dni']);

    DB::startTransaction();

    try{

        /* ----------------------------------
           SI NO EXISTE → CREAR CLIENTE GLOBAL
        ---------------------------------- */

        if (!$cliente) {

            DB::insert('pos_cliente',[
                'dni'       => $d['dni'],
                'nombre'    => $d['nombre'],
                'is_activo' => 1
            ]);

            $cliente_id = DB::insertId();

        } else {

            $cliente_id = $cliente['cliente_id'];

        }

        /* ----------------------------------
           VERIFICAR RELACIÓN CON NEGOCIO
        ---------------------------------- */

        $existe_relacion = DB::queryFirstField("
            SELECT COUNT(*)
            FROM reg_negxclie
            WHERE neg_id=%i
            AND cliente_id=%i
        ", $neg_id, $cliente_id);

        /* ----------------------------------
           SI NO EXISTE RELACIÓN → CREARLA
        ---------------------------------- */

        if ($existe_relacion == 0) {

            DB::insert('reg_negxclie',[
                'neg_id' => $neg_id,
                'cliente_id' => $cliente_id
            ]);

        }

        DB::commit();

        Flight::json([
            'ok' => 1,
            'cliente_id' => $cliente_id
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'error' => $e->getMessage()
        ],500);

    }

});
/* ======================================
   ADMINISTRADOR ACTUAL
====================================== */
Flight::route('GET /auth/administrador-actual', function () {

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $admin = DB::queryFirstRow("
        SELECT usu_id,nombres_apellidos,email
        FROM reg_usu
        WHERE usu_id=%i
    ", $administrador_actual['usu_id']);

    $hoy = date('Y-m-d');

    $caja = DB::queryFirstRow("
        SELECT *
        FROM pos_caja
        WHERE usu_id=%i
        AND neg_id=%i
        AND DATE(fecha_apertura)=%s
        ORDER BY caja_id DESC
        LIMIT 1
    ",
        $administrador_actual['usu_id'],
        $administrador_actual['neg_id'],
        $hoy
    );

    if (!$caja) {
        $caja=['estado'=>'CERRADA'];
    }

    Flight::json([
        'status'=>'ok',
        'administrador'=>[
            'administrador_id'=>$admin['usu_id'],
            'nombre'=>$admin['nombres_apellidos'] ?? '',
            'email'=>$admin['email'] ?? ''
        ],
        'caja'=>$caja
    ]);
});


/* ======================================
   TIPOS DE PAGO
====================================== */
Flight::route('GET /tipo_pago/listar', function(){

    $rows = DB::query("
        SELECT tipo_pago_id, descripcion
        FROM pos_tipo_pago
        ORDER BY orden ASC
    ");

    Flight::json($rows);
});


/* ======================================
   LISTAR MESAS
====================================== */
Flight::route('GET /mesa/listar', function(){

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("
        SELECT mesa_id,nombre,estado
        FROM resto_mesa
        WHERE neg_id=%i
        ORDER BY mesa_id ASC
    ", $administrador_actual['neg_id']);

    Flight::json($rows);
});


/* ======================================
   LISTAR ADMINISTRADORES
====================================== */
Flight::route('GET /order/administrador/listar', function(){

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("
        SELECT usu_id AS administrador_id,nombres_apellidos
        FROM reg_usu
        WHERE is_activo=1
        ORDER BY nombres_apellidos ASC
    ");

    Flight::json($rows);
});


/* ======================================
   REPORTE PDF VENTAS
====================================== */
Flight::route('GET /imp_ventas_fecha', function(){

    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;

    $req = Flight::request();

    $ini = trim($req->query->ini ?? '');
    $fin = trim($req->query->fin ?? '');

    if ($ini === '' || $fin === '') {
        Flight::halt(400,'Debe enviar las fechas ini y fin');
    }

    DB::query("SET NAMES 'utf8mb4'");

    $ventas = DB::query("
        SELECT 
            po.product_order_id,
            po.fecha_creacion,
            po.total_fees,
            po.cliente_id AS cliente,
            u.nombres_apellidos AS administrador
        FROM pos_product_order po
        LEFT JOIN reg_usu u ON u.usu_id = po.usu_id
        WHERE po.fecha_creacion BETWEEN %s AND %s
        AND po.neg_id=%i
        ORDER BY po.product_order_id
    ",
        $ini.' 00:00:00',
        $fin.' 23:59:59',
        $administrador_actual['neg_id']
    );

    $listado=[];
    $total_general=0;
    $i=1;

    foreach ($ventas as &$v){

        $detalles = DB::query("
            SELECT 
                d.product_name AS producto,
                d.amount AS cantidad,
                d.price_item AS precio,
                (d.amount * d.price_item) AS subtotal
            FROM pos_product_order_detail d
            WHERE d.order_id=%i
        ", $v['product_order_id']);

        $v['indice']=$i++;
        $v['detalles']=$detalles;
        $total_general += $v['total_fees'];

        $listado[]=$v;
    }

    $template_data=[
        'informacion'=>[[
            'razon_social'=>'CLUB SOCIAL LIMA NORTE S.A.C',
            'ruc'=>vari('RUC'),
            'logo'=>$varhost.'/public/admin/login/images/logo_login.png',
            'titulo_reporte'=>"REPORTE DE VENTAS DEL $ini AL $fin",
            'fecha'=>date('d/m/Y H:i'),
            'total_items'=>count($ventas),
            'total_general'=>number_format($total_general,2)
        ]],
        'listado'=>$listado
    ];

    $html = (new Mustache)->render(
        file_get_contents(VARPATH.'/public/reportes/reporte_html/imp_ventas_fecha.html'),
        $template_data
    );

    global $wkh_pdf;

    $pdf = VARPATH.'/public/reportes/archivos_temporales/ventas_'.time().'.pdf';

    $wkh_pdf->addPage($html);
    exec($wkh_pdf->getCommand($pdf));

    Flight::redirect(
        $varhost.'/public/reportes/archivos_temporales/'.basename($pdf)
    );
});

Flight::route('GET /reporte_ventas_fecha_admin_excel', function(){

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=ventas_admin.xls");

    $ini = $_GET['ini'];
    $fin = $_GET['fin'];
    $admin = (int)($_GET['admin_id'] ?? 0);

    $cond_admin = $admin ? "AND v.administrador_id = $admin" : "";

    $rows = DB::query("
        SELECT 
            v.venta_id,
            v.cliente_nombre,
            a.nombres_apellidos,
            v.total
        FROM venta v
        LEFT JOIN administrador a 
               ON a.administrador_id = v.administrador_id
        WHERE v.neg_id=%i
          AND v.fecha_creacion BETWEEN %s AND %s
          $cond_admin
    ",
        $administrador_actual['neg_id'],
        $ini.' 00:00:00',
        $fin.' 23:59:59'
    );

    echo "<table border='1'>
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Administrador</th>
          <th>Total</th>
        </tr>";

    foreach($rows as $r){
        echo "<tr>
          <td>{$r['venta_id']}</td>
          <td>{$r['cliente_nombre']}</td>
          <td>{$r['nombres_apellidos']}</td>
          <td>{$r['total']}</td>
        </tr>";
    }

    echo "</table>";
});


Flight::route('GET /imp_ventas_fecha_admin', function(){

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $req = Flight::request();

    $ini = trim($req->query->ini ?? $_GET['ini'] ?? '');
    $fin = trim($req->query->fin ?? $_GET['fin'] ?? '');
    $admin_id = (int)($req->query->admin_id ?? $_GET['admin_id'] ?? 0);

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }

    DB::query("SET NAMES 'utf8mb4'");

    $ventas = DB::query("
        SELECT 
            v.venta_id,
            v.fecha_creacion,
            v.total,
            v.cliente_nombre,
            a.nombres_apellidos AS administrador
        FROM venta v
        LEFT JOIN administrador a 
               ON a.administrador_id = v.administrador_id
        WHERE v.neg_id=%i
          AND v.fecha_creacion BETWEEN %s AND %s
          AND ( %i = 0 OR v.administrador_id = %i )
        ORDER BY v.venta_id
    ",
        $administrador_actual['neg_id'],
        $ini.' 00:00:00',
        $fin.' 23:59:59',
        $admin_id,
        $admin_id
    );

    $listado = [];
    $total_general = 0;
    $i = 1;

    foreach ($ventas as $v) {

        $detalles = DB::query("
            SELECT 
                d.producto_nombre AS producto,
                d.cantidad,
                d.precio_unitario AS precio,
                (d.cantidad * d.precio_unitario) AS subtotal
            FROM venta_detalle d
            WHERE d.venta_id = %i
        ", $v['venta_id']);

        $v['indice']   = $i++;
        $v['detalles'] = $detalles;

        $total_general += $v['total'];
        $listado[] = $v;
    }

    $template_data = [
        'informacion' => [[
            'razon_social'   => 'CLUB SOCIAL LIMA NORTE S.A.C',
            'ruc'            => vari('RUC'),
            'logo'           => $varhost . '/public/admin/login/images/logo_login.png',
            'titulo_reporte' => "REPORTE DE VENTAS DEL $ini AL $fin",
            'fecha'          => date('d/m/Y H:i'),
            'total_items'    => count($ventas),
            'total_general'  => number_format($total_general, 2)
        ]],
        'listado' => $listado
    ];

    $html = (new Mustache)->render(
        file_get_contents(
            VARPATH . '/public/reportes/reporte_html/imp_ventas_fecha_admin.html'
        ),
        $template_data
    );

    global $wkh_pdf;

    $pdf = VARPATH . '/public/reportes/archivos_temporales/ventas_admin_'
         . time() . '.pdf';

    $wkh_pdf->addPage($html);
    exec($wkh_pdf->getCommand($pdf));

    Flight::redirect(
        $varhost . '/public/reportes/archivos_temporales/' . basename($pdf)
    );
});


function recalcular_total_venta($venta_id){

    $total = DB::queryFirstField("
        SELECT IFNULL(SUM(cantidad * precio_unitario),0)
        FROM venta_detalle
        WHERE venta_id=%i
    ", $venta_id);

    DB::update('venta',[
        'total'=>$total,
        'fecha_modificacion'=>date('Y-m-d H:i:s'),
        'last_update'=>time()*1000
    ],"venta_id=%i",$venta_id);
}



Flight::route('POST /venta/liberar_mesa', function(){

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data->getData();
    $venta_id = (int)$d['venta_id'];

    $venta = DB::queryFirstRow("
        SELECT mesa_id
        FROM venta
        WHERE venta_id=%i
        AND neg_id=%i
    ",
        $venta_id,
        $administrador_actual['neg_id']
    );

    if(!$venta || !$venta['mesa_id']){
        Flight::json(['status'=>'error'],400);
        return;
    }

    DB::startTransaction();
    try {

        DB::update('venta',[
          'fecha_fin'=>date('Y-m-d H:i:s'),
          'estado'=>'CERRADA',
          'fecha_modificacion'=>date('Y-m-d H:i:s'),
          'last_update'=>time()*1000
        ],"venta_id=%i",$venta_id);

        DB::update('mesa',[
          'estado'=>'DISPONIBLE'
        ],"mesa_id=%i",$venta['mesa_id']);

        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch(Exception $e){
        DB::rollback();
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }

});


Flight::route('POST /ventas/liberarMesaOcupada', function(){

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $d = Flight::request()->data;
    $mesa_id = (int)($d['mesa_id'] ?? 0);

    if(!$mesa_id){
        Flight::json([
            'status'=>'error',
            'msg'=>'Mesa inválida'
        ],400);
        return;
    }

    $pendientes = DB::queryFirstField("
        SELECT COUNT(*)
        FROM venta
        WHERE neg_id=%i
          AND mesa_id=%i
          AND estado='ABIERTA'
    ",
        $administrador_actual['neg_id'],
        $mesa_id
    );

    if($pendientes > 0){
        Flight::json([
            'status'=>'error',
            'msg'=>'La mesa tiene pedidos pendientes'
        ],409);
        return;
    }

    DB::startTransaction();
    try {

        DB::update('mesa',[
            'estado'=>'DISPONIBLE'
        ],"mesa_id=%i",$mesa_id);

        DB::commit();

        Flight::json([
            'status'=>'ok'
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }

});

Flight::route('GET /reporte/resumen-ventas', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;
    global $wkh_pdf, $varhost;

    $ini = trim($_GET['ini'] ?? '');
    $fin = trim($_GET['fin'] ?? '');

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Fechas requeridas');
    }

    DB::query("SET NAMES 'utf8mb4'");

    // ===============================
    // 1️⃣ PAGO DIRECTO
    // ===============================
    $pago_directo = DB::queryFirstField("
        SELECT IFNULL(SUM(total),0)
        FROM venta
        WHERE neg_id=%i
          AND modo_order_id = 1
          AND fecha_creacion BETWEEN %s AND %s
    ",
        $administrador_actual['neg_id'],
        $ini.' 00:00:00',
        $fin.' 23:59:59'
    );

    // ===============================
    // 2️⃣ MESAS PAGADAS
    // ===============================
    $rows = DB::query("
        SELECT 
            m.nombre AS mesa,
            SUM(v.total) AS total
        FROM venta v
        INNER JOIN mesa m ON m.mesa_id = v.mesa_id
        WHERE v.neg_id=%i
          AND v.modo_order_id = 3
          AND v.fecha_creacion BETWEEN %s AND %s
        GROUP BY m.mesa_id
        ORDER BY m.nombre
    ",
        $administrador_actual['neg_id'],
        $ini.' 00:00:00',
        $fin.' 23:59:59'
    );

    $total_mesas = array_sum(array_column($rows, 'total'));
    $total_general = $pago_directo + $total_mesas;

    $template_data = [
        'fecha_ini'     => date('d/m/Y', strtotime($ini)),
        'fecha_fin'     => date('d/m/Y', strtotime($fin)),
        'pago_directo'  => number_format($pago_directo, 2),
        'mesas'         => array_map(function($r){
            return [
                'mesa'  => $r['mesa'],
                'total' => number_format($r['total'], 2)
            ];
        }, $rows),
        'total_general' => number_format($total_general, 2)
    ];

    $html = (new Mustache)->render(
        file_get_contents(
            VARPATH . '/public/reportes/reporte_html/imp_resumen_ventas_dia.html'
        ),
        $template_data
    );

    $tmp_html = VARPATH . '/public/reportes/archivos_temporales/tmp_resumen.html';
    file_put_contents($tmp_html, $html);

    $nombre_pdf = 'resumen_ventas_' . time() . '.pdf';
    $ruta_pdf   = VARPATH . '/public/reportes/archivos_temporales/' . $nombre_pdf;

    $wkh_pdf->addPage($tmp_html);
    exec($wkh_pdf->getCommand($ruta_pdf), $out, $ret);

    if ($ret !== 0 || !file_exists($ruta_pdf)) {
        echo "<pre>NO SE CREÓ EL PDF</pre>";
        exit;
    }

    Flight::redirect(
        $varhost . '/public/reportes/archivos_temporales/' . $nombre_pdf
    );
});



Flight::route('GET /imp_ventas_fecha_admin_excel', function(){

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=ventas_admin.xls");

    $ini = trim($_GET['ini'] ?? '');
    $fin = trim($_GET['fin'] ?? '');
    $admin_id = (int)($_GET['admin_id'] ?? 0);

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }

    DB::query("SET NAMES 'utf8mb4'");

    $ventas = DB::query("
        SELECT 
            v.venta_id,
            v.fecha_creacion,
            v.cliente_nombre AS cliente,
            a.nombres_apellidos AS administrador
        FROM venta v
        LEFT JOIN administrador a 
               ON a.administrador_id = v.administrador_id
        WHERE v.neg_id=%i
          AND v.fecha_creacion BETWEEN %s AND %s
          AND ( %i = 0 OR v.administrador_id = %i )
        ORDER BY v.venta_id
    ",
        $administrador_actual['neg_id'],
        $ini.' 00:00:00',
        $fin.' 23:59:59',
        $admin_id,
        $admin_id
    );

    $total_general = 0;

    echo "<table border='1'>";

    echo "<tr>
            <th colspan='6'>
              REPORTE DE VENTAS POR ADMINISTRADOR
              DEL $ini AL $fin
            </th>
          </tr>";

    echo "<tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Administrador</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Subtotal</th>
          </tr>";

    foreach ($ventas as $v) {

        $detalles = DB::query("
            SELECT 
                d.producto_nombre,
                d.cantidad,
                d.precio_unitario,
                (d.cantidad * d.precio_unitario) AS subtotal
            FROM venta_detalle d
            WHERE d.venta_id = %i
        ", $v['venta_id']);

        foreach ($detalles as $d) {

            $subtotal = (float)$d['subtotal'];
            $total_general += $subtotal;

            echo "<tr>
                <td>{$v['venta_id']}</td>
                <td>{$v['cliente']}</td>
                <td>{$v['administrador']}</td>
                <td>{$d['producto_nombre']}</td>
                <td>{$d['cantidad']}</td>
                <td>".number_format($subtotal,2)."</td>
            </tr>";
        }
    }

    echo "<tr>
            <td colspan='5'><strong>TOTAL GENERAL</strong></td>
            <td><strong>".number_format($total_general,2)."</strong></td>
          </tr>";

    echo "</table>";
});

