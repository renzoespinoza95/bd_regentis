<?php
// este es mi backend usando php8.2, flightphp y meekrodb2
Flight::route('GET /order', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;                          // asegúrate de tener esta var en tu bootstrap
    include $path_public . '/admin/tab_order/inicio.php';
});

/* ======================================
   LISTAR ÓRDENES
====================================== */
Flight::route('GET /product_order/listar', function(){

    $rows = DB::query("
        SELECT 
          po.product_order_id,
          po.serial,
          po.total_fees,
          po.modo_order_id,
          mo.nombre AS modo_order,
          cl.nombre AS cliente,
          m.nombre AS mesa_nombre,
          a.nombres_apellidos AS administrador,
          tp.descripcion AS tipo_pago,
          DATE_FORMAT(po.fecha_creacion,'%d/%m/%Y %H:%i') AS fecha
        FROM product_order po
        LEFT JOIN cliente cl ON cl.cliente_id = po.cliente_id
        LEFT JOIN mesa m ON m.mesa_id = po.mesa_id
        LEFT JOIN modo_order mo ON mo.modo_order_id = po.modo_order_id
        LEFT JOIN tipo_pago tp ON tp.tipo_pago_id = po.tipo_pago_id
        LEFT JOIN administradortbl a ON a.administrador_id = po.administrador_id
        ORDER BY po.product_order_id DESC
    ");

    Flight::json($rows);
});


/* ======================================
   CREAR ORDEN
====================================== */
function generarCodigoOrden(){
  return strtoupper(bin2hex(random_bytes(4))); // ej: A9F2C1D3
}


Flight::route('POST /product_order/crear', function () {

    include DEFINITION;
    login_admin::autentificar_administrador();

    // ===============================
    // 1) Validar sesión
    // ===============================
    if (!$sesion_admin_administrador_id) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'Sesión no válida'
        ], 401);
        return;
    }

    // ===============================
    // 2) Obtener administrador_id
    // ===============================
    $valor_key = $nombre_app . vari("KEY");
    $administrador_id = (int) str_replace(
        "*",
        "",
        util::decrypt($sesion_admin_administrador_id, $valor_key)
    );

    if (!$administrador_id) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'Administrador inválido'
        ], 401);
        return;
    }

    // ===============================
    // 3) Buscar caja ABIERTA del día
    // ===============================
    $caja = DB::queryFirstRow("
        SELECT *
        FROM caja
        WHERE administrador_id = %i
          AND DATE(fecha_apertura) = CURDATE()
          AND estado = 'ABIERTA'
        ORDER BY fecha_apertura DESC
        LIMIT 1
    ", $administrador_id);

    if (!$caja) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'La caja de este usuario está cerrada'
        ], 403);
        return;
    }

    // ===============================
    // 4) Leer payload
    // ===============================
    $d = Flight::request()->data->getData();

    // -------------------------------
    // Validaciones obligatorias
    // -------------------------------
    if (empty($d['cliente_id'])) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'Debe seleccionar un cliente'
        ], 400);
        return;
    }

    if (empty($d['tipo_pago_id'])) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'Debe seleccionar tipo de pago'
        ], 400);
        return;
    }

    if (empty($d['items']) || !is_array($d['items'])) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'Debe agregar al menos un producto'
        ], 400);
        return;
    }

    // ===============================
    // 5) Lógica de mesa
    // ===============================
    $mesa_id = isset($d['mesa_id']) ? (int)$d['mesa_id'] : -1;

    if ($mesa_id < 0) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'Debe seleccionar una mesa o DIRECTO'
        ], 400);
        return;
    }

    // Valores por defecto: VENTA DIRECTA
    // valores por defecto
    $modo_order_id = 1; // PAGO DIRECTO
    $mesa_id_db = null;
    $fecha_inicio = null;
    $fecha_fin = null;

    // si es mesa
    if ($mesa_id > 0) {

        $ocupada = DB::queryFirstField("
            SELECT COUNT(*) 
            FROM product_order
            WHERE mesa_id=%i AND modo_order_id=2
        ", $mesa_id);

        if ($ocupada) {
            Flight::json(['status'=>'error','msg'=>'Mesa ocupada'],409);
            return;
        }

        $modo_order_id = 2; // MESA PEDIDO
        $mesa_id_db = $mesa_id;
        $fecha_inicio = date('Y-m-d H:i:s');

        DB::update('mesa',['estado'=>'OCUPADA'],"mesa_id=%i",$mesa_id);
    }

    // ===============================
    // 6) Crear orden (TRANSACCIÓN)
    // ===============================
    DB::startTransaction();

            try {

                $now = time() * 1000;

                DB::insert('product_order',[
                  'serial' => generarCodigoOrden(),
                  'administrador_id' => $administrador_id,
                  'cliente_id' => $d['cliente_id'],
                  'caja_id' => $caja['caja_id'],
                  'tipo_pago_id' => $d['tipo_pago_id'],
                  'mesa_id' => $mesa_id_db,
                  'modo_order_id' => $modo_order_id,
                  'total_fees' => 0,
                  'tax' => 0,
                  'fecha_inicio' => $fecha_inicio,
                  'fecha_creacion' => date('Y-m-d H:i:s'),
                  'fecha_modificacion' => date('Y-m-d H:i:s'),
                  'created_at' => time()*1000,
                  'last_update' => time()*1000
                ]);


                $order_id = DB::insertId();

                // ===============================
                // 7) Insertar detalles + inventario
                // ===============================
                foreach ($d['items'] as $i) {

            DB::insert('product_order_detail', [
                'order_id'       => $order_id,
                'product_id'     => $i['product_id'],
                'product_name'   => DB::queryFirstField(
                    "SELECT name FROM product WHERE product_id = %i",
                    $i['product_id']
                ),
                'amount'         => $i['amount'],
                'price_item'     => $i['price_item'],
                'created_at'     => $now,
                'last_update'    => $now,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'fecha_modificacion' => date('Y-m-d H:i:s')
            ]);

            registrar_movimiento_inventario(
                $i['product_id'],
                'SALIDA',
                'VENTA',
                $i['amount'],
                $i['price_item'],
                $order_id,
                'product_order'
            );
        }

        // 🔥 ESTA LÍNEA ES LA CLAVE
        recalcular_total_orden($order_id);

        DB::commit();


        // ===============================
        // 8) Respuesta final
        // ===============================
        Flight::json([
            'status'           => 'ok',
            'product_order_id' => $order_id,
            'modo'             => $modo,
            'status_orden'     => $status_orden,
            'mesa_id'          => $mesa_id_db
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);
    }
});




/* ======================================
   EDITAR ORDEN
====================================== */
Flight::route('POST /product_order/editar', function(){
    $d = Flight::request()->data->getData();
    $now = time()*1000;

    DB::update('product_order',[
        'buyer'=>$d['buyer'],
        'address'=>$d['address'],
        'status'=>$d['status'],
        'last_update'=>$now,
        'fecha_modificacion'=>date("Y-m-d H:i:s")
    ],"product_order_id=%i",$d['product_order_id']);

    Flight::json(['status'=>'ok']);
});

/* ======================================
   ELIMINAR ORDEN
====================================== */
Flight::route('POST /product_order/eliminar', function(){
    $d = Flight::request()->data->getData();
    DB::delete('product_order',"product_order_id=%i",$d['product_order_id']);
    Flight::json(['status'=>'ok']);
});

Flight::route('GET /product_order/detalle/@id', function($id){

    // Orden + tipo de pago
    $order = DB::queryFirstRow("
        SELECT o.*,
               tp.descripcion AS tipo_pago
        FROM product_order o
        LEFT JOIN tipo_pago tp 
               ON tp.tipo_pago_id = o.tipo_pago_id
        WHERE o.product_order_id = %i
    ", $id);

    // Detalles (items)
    $det = DB::query("
        SELECT d.*,
               p.name AS product_name
        FROM product_order_detail d
        LEFT JOIN product p 
               ON p.product_id = d.product_id
        WHERE d.order_id = %i
        ORDER BY d.product_order_detail_id ASC
    ", $id);

    Flight::json([
        'order'    => $order,
        'detalles' => $det
    ]);
});

Flight::route('POST /product_order_detail/crear', function(){

    $d = Flight::request()->data->getData();
    $now = time()*1000;

    DB::startTransaction();
    try {

        // insertar detalle
        DB::insert('product_order_detail',[
            'order_id' => $d['order_id'],
            'product_id' => $d['product_id'],
            'product_name' => DB::queryFirstField(
                "SELECT name FROM product WHERE product_id=%i",
                $d['product_id']
            ),
            'amount' => $d['amount'],
            'price_item' => $d['price_item'],
            'created_at' => $now,
            'last_update' => $now,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ]);

        // 🔴 DESCONTAR INVENTARIO
        registrar_movimiento_inventario(
            $d['product_id'],
            'SALIDA',
            'VENTA',
            $d['amount'],
            $d['price_item'],
            $d['order_id'],
            'product_order'
        );

        actualizar_estado_orden($d['order_id'], 'AGREGADO');

        recalcular_total_orden($d['order_id']);

        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch(Exception $e){
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /product_order_detail/eliminar', function(){

    $d = Flight::request()->data->getData();

    $item = DB::queryFirstRow(
        "SELECT * FROM product_order_detail WHERE product_order_detail_id=%i",
        $d['product_order_detail_id']
    );

    DB::startTransaction();
    try {

        // devolver stock
        registrar_movimiento_inventario(
            $item['product_id'],
            'ENTRADA',
            'DEVOLUCION_VENTA',
            $item['amount'],
            $item['price_item'],
            $item['order_id'],
            'product_order'
        );

        actualizar_estado_orden($d['order_id'], 'EDITADO');

        DB::delete(
            'product_order_detail',
            "product_order_detail_id=%i",
            $d['product_order_detail_id']
        );

        recalcular_total_orden($item['order_id']);

        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch(Exception $e){
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});


Flight::route('POST /product_order_detail/editar', function () {

  $d = Flight::request()->data->getData();

  $old = DB::queryFirstRow(
    "SELECT * FROM product_order_detail WHERE product_order_detail_id=%i",
    $d['product_order_detail_id']
  );

  DB::startTransaction();
  try {

    // devolver stock viejo
    registrar_movimiento_inventario(
      $old['product_id'],
      'ENTRADA',
      'AJUSTE',
      $old['amount'],
      $old['price_item'],
      $old['order_id'],
      'product_order'
    );

    // aplicar nuevo
    registrar_movimiento_inventario(
      $d['product_id'],
      'SALIDA',
      'VENTA',
      $d['amount'],
      $d['price_item'],
      $old['order_id'],
      'product_order'
    );

    actualizar_estado_orden($old['order_id'], 'EDITADO');

    DB::update('product_order_detail',[
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


function actualizar_estado_orden($order_id, $estado){
    DB::update(
        'product_order',
        [
            'status' => $estado,
            'fecha_modificacion' => date('Y-m-d H:i:s'),
            'last_update' => time()*1000
        ],
        "product_order_id=%i",
        $order_id
    );
}

Flight::route('GET /cliente/listar', function(){
  Flight::json(
    DB::query("SELECT cliente_id,dni,nombre FROM cliente WHERE is_activo=1")
  );
});

Flight::route('POST /cliente/crear', function(){
  $d = Flight::request()->data->getData();
  DB::insert('cliente',[
    'dni'=>$d['dni'],
    'nombre'=>$d['nombre']
  ]);
  Flight::json(['ok'=>1]);
});


Flight::route('GET /auth/administrador-actual', function () {

    include DEFINITION;

    // 🔐 verificar sesión
    if (!$sesion_admin_administrador_id) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'No autenticado'
        ], 401);
        return;
    }

    // 🔓 desencriptar administrador_id
    $valor_key = $nombre_app . vari("KEY");
    $administrador_id = str_replace(
        "*",
        "",
        util::decrypt($sesion_admin_administrador_id, $valor_key)
    );

    // 🧑‍💼 info del administrador
    $admin = login_admin::informacion_administrador_por_id($administrador_id);

    if (!$admin) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'Administrador no encontrado'
        ], 404);
        return;
    }

    // 📅 buscar caja del día
    $hoy = date('Y-m-d');

    $caja = DB::queryFirstRow("
        SELECT *
        FROM caja
        WHERE administrador_id = %i
          AND DATE(fecha_apertura) = %s
        ORDER BY caja_id DESC
        LIMIT 1
    ", $administrador_id, $hoy);

    // 🟥 si no hay caja → CERRADA
    if (!$caja) {
        $caja = [
            'estado' => 'CERRADA'
        ];
    }

    // ✅ respuesta
    Flight::json([
        'status' => 'ok',
        'administrador' => [
            'administrador_id' => $admin['administrador_id'],
            'nombre'           => $admin['nombres_apellidos'] ?? '',
            'email'            => $admin['email'] ?? ''
        ],
        'caja' => $caja
    ]);
});

Flight::route('GET /tipo_pago/listar', function(){
    $rows = DB::query("
        SELECT tipo_pago_id, descripcion
        FROM tipo_pago
        ORDER BY orden ASC
    ");
    Flight::json($rows);
});

Flight::route('GET /mesa/listar', function(){

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("
        SELECT 
            mesa_id,
            nombre,
            estado
        FROM mesa
        ORDER BY mesa_id ASC
    ");

    Flight::json($rows);
});


Flight::route('GET /administrador/listar', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("
        SELECT 
            administrador_id,
            nombres_apellidos
        FROM administradortbl
        WHERE is_activo = 1
        ORDER BY nombres_apellidos ASC
    ");

    Flight::json($rows);
});


Flight::route('GET /imp_ventas_fecha', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    $req = Flight::request();

    $ini = trim($req->query->ini ?? $_GET['ini'] ?? '');
    $fin = trim($req->query->fin ?? $_GET['fin'] ?? '');

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }

    DB::query("SET NAMES 'utf8mb4'");

    // ===============================
    // VENTAS (CABECERA)
    // ===============================
    $ventas = DB::query("
        SELECT 
            po.product_order_id,
            po.fecha_creacion,
            po.total_fees,
            po.cliente_id AS cliente,
            a.nombres_apellidos AS administrador
        FROM product_order po
        LEFT JOIN administradortbl a 
               ON a.administrador_id = po.administrador_id
        WHERE po.fecha_creacion BETWEEN %s AND %s
        ORDER BY po.product_order_id
    ", $ini.' 00:00:00', $fin.' 23:59:59');

    $listado = [];
    $total_general = 0;
    $i = 1;

    foreach ($ventas as &$v) {

        // ===============================
        // DETALLES POR VENTA
        // ===============================
        $detalles = DB::query("
            SELECT 
                d.product_name AS producto,
                d.amount AS cantidad,
                d.price_item AS precio,
                (d.amount * d.price_item) AS subtotal
            FROM product_order_detail d
            WHERE d.order_id = %i
        ", $v['product_order_id']);

        $v['indice']   = $i++;
        $v['detalles'] = $detalles;
        $total_general += $v['total_fees'];

        $listado[] = $v;
    }

    // ===============================
    // DATA PARA MUSTACHE
    // ===============================
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

    // ===============================
    // RENDER HTML
    // ===============================
    $html = (new Mustache)->render(
        file_get_contents(
            VARPATH . '/public/reportes/reporte_html/imp_ventas_fecha.html'
        ),
        $template_data
    );

    // ===============================
    // GENERAR PDF
    // ===============================
    global $wkh_pdf;

    $pdf = VARPATH . '/public/reportes/archivos_temporales/ventas_'
         . time() . '.pdf';

    $wkh_pdf->addPage($html);
    exec($wkh_pdf->getCommand($pdf));

    // ===============================
    // REDIRECT DESCARGA
    // ===============================
    Flight::redirect(
        $varhost . '/public/reportes/archivos_temporales/' . basename($pdf)
    );
});


Flight::route('GET /imp_ventas_fecha_excel', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=ventas.xls");

    $ini = trim($_GET['ini'] ?? '');
    $fin = trim($_GET['fin'] ?? '');

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }

    DB::query("SET NAMES 'utf8mb4'");

    // ===============================
    // CABECERA DE VENTAS
    // ===============================
    $ventas = DB::query("
        SELECT 
            po.product_order_id,
            po.fecha_creacion,
            c.nombre AS cliente,
            a.nombres_apellidos AS administrador
        FROM product_order po
        LEFT JOIN cliente c 
               ON c.cliente_id = po.cliente_id
        LEFT JOIN administradortbl a 
               ON a.administrador_id = po.administrador_id
        WHERE po.fecha_creacion BETWEEN %s AND %s
        ORDER BY po.product_order_id
    ", $ini.' 00:00:00', $fin.' 23:59:59');

    $total_general = 0;

    echo "<table border='1'>";
    echo "<tr>
            <th colspan='6'>
              REPORTE DE VENTAS DEL $ini AL $fin
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

    // ===============================
    // DETALLE POR VENTA
    // ===============================
    foreach ($ventas as $v) {

        $detalles = DB::query("
            SELECT 
                d.product_name,
                d.amount,
                d.price_item,
                (d.amount * d.price_item) AS subtotal
            FROM product_order_detail d
            WHERE d.order_id = %i
        ", $v['product_order_id']);

        foreach ($detalles as $d) {

            $subtotal = (float)$d['subtotal'];
            $total_general += $subtotal;

            echo "<tr>
                <td>{$v['product_order_id']}</td>
                <td>{$v['cliente']}</td>
                <td>{$v['administrador']}</td>
                <td>{$d['product_name']}</td>
                <td>{$d['amount']}</td>
                <td>".number_format($subtotal,2)."</td>
            </tr>";
        }
    }

    // ===============================
    // TOTAL GENERAL
    // ===============================
    echo "<tr>
            <td colspan='5'><strong>TOTAL GENERAL</strong></td>
            <td><strong>".number_format($total_general,2)."</strong></td>
          </tr>";

    echo "</table>";
});



Flight::route('GET /reporte_ventas_fecha_admin', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    $ini = $_GET['ini'] ?? '';
    $fin = $_GET['fin'] ?? '';
    $admin = (int)($_GET['admin_id'] ?? 0);

    $cond = $admin ? "AND po.administrador_id = $admin" : "";

    $ventas = DB::query("
        SELECT 
            po.product_order_id,
            po.buyer,
            po.total_fees,
            a.nombres_apellidos
        FROM product_order po
        LEFT JOIN administradortbl a 
               ON a.administrador_id = po.administrador_id
        WHERE po.fecha_creacion BETWEEN %s AND %s
        $cond
        ORDER BY po.product_order_id
    ", $ini.' 00:00:00', $fin.' 23:59:59');

    $total = array_sum(array_column($ventas,'total_fees'));

    Flight::json([
        'ventas'=>$ventas,
        'total'=>$total
    ]);
});

Flight::route('GET /reporte_ventas_fecha_admin_excel', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=ventas_admin.xls");

    $ini = $_GET['ini'];
    $fin = $_GET['fin'];
    $admin = (int)($_GET['admin_id'] ?? 0);

    $cond = $admin ? "AND po.administrador_id = $admin" : "";

    $rows = DB::query("
        SELECT 
            po.product_order_id,
            po.buyer,
            a.nombres_apellidos,
            po.total_fees
        FROM product_order po
        LEFT JOIN administradortbl a 
               ON a.administrador_id = po.administrador_id
        WHERE po.fecha_creacion BETWEEN %s AND %s
        $cond
    ", $ini.' 00:00:00', $fin.' 23:59:59');

    echo "<table border='1'>
        <tr>
          <th>ID</th>
          <th>Cliente</th>
          <th>Administrador</th>
          <th>Total</th>
        </tr>";

    foreach($rows as $r){
        echo "<tr>
          <td>{$r['product_order_id']}</td>
          <td>{$r['buyer']}</td>
          <td>{$r['nombres_apellidos']}</td>
          <td>{$r['total_fees']}</td>
        </tr>";
    }

    echo "</table>";
});



Flight::route('GET /imp_ventas_fecha_admin', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    $req = Flight::request();

    $ini = trim($req->query->ini ?? $_GET['ini'] ?? '');
    $fin = trim($req->query->fin ?? $_GET['fin'] ?? '');
    $admin_id = (int)($req->query->admin_id ?? $_GET['admin_id'] ?? 0);

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }

    DB::query("SET NAMES 'utf8mb4'");

    // ===============================
    // VENTAS (CABECERA)
    // ===============================
    $ventas = DB::query("
        SELECT 
            po.product_order_id,
            po.fecha_creacion,
            po.total_fees,
            po.cliente_id AS cliente,
            a.nombres_apellidos AS administrador
        FROM product_order po
        LEFT JOIN administradortbl a 
               ON a.administrador_id = po.administrador_id
        WHERE po.fecha_creacion BETWEEN %s AND %s
          AND ( %i = 0 OR po.administrador_id = %i )
        ORDER BY po.product_order_id
    ",
        $ini.' 00:00:00',
        $fin.' 23:59:59',
        $admin_id,
        $admin_id
    );

    // ===============================
    // ARMAR LISTADO + DETALLES
    // ===============================
    $listado = [];
    $total_general = 0;
    $i = 1;

    foreach ($ventas as $v) {

        $detalles = DB::query("
            SELECT 
                d.product_name AS producto,
                d.amount AS cantidad,
                d.price_item AS precio,
                (d.amount * d.price_item) AS subtotal
            FROM product_order_detail d
            WHERE d.order_id = %i
        ", $v['product_order_id']);

        $v['indice']   = $i++;
        $v['detalles'] = $detalles;

        $total_general += $v['total_fees'];
        $listado[] = $v;
    }

    // ===============================
    // DATA PARA MUSTACHE
    // ===============================
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

    $total_general = 0;

        foreach ($ventas as &$v) {

            $detalles = DB::query("
                SELECT 
                    d.product_name AS producto,
                    d.amount AS cantidad,
                    d.price_item AS precio,
                    (d.amount * d.price_item) AS subtotal
                FROM product_order_detail d
                WHERE d.order_id = %i
            ", $v['product_order_id']);

            foreach ($detalles as $d) {
                $total_general += $d['subtotal'];
            }

            $v['detalles'] = $detalles;
        }


    // ===============================
    // RENDER HTML
    // ===============================
    $html = (new Mustache)->render(
        file_get_contents(
            VARPATH . '/public/reportes/reporte_html/imp_ventas_fecha_admin.html'
        ),
        $template_data
    );

    //echo $html;
    //exit;

    // ===============================
    // GENERAR PDF
    // ===============================
    global $wkh_pdf;

    $pdf = VARPATH . '/public/reportes/archivos_temporales/ventas_admin_'
         . time() . '.pdf';

    $wkh_pdf->addPage($html);
    exec($wkh_pdf->getCommand($pdf));

    // ===============================
    // DESCARGA
    // ===============================
    Flight::redirect(
        $varhost . '/public/reportes/archivos_temporales/' . basename($pdf)
    );
});

function recalcular_total_orden($order_id){
    $total = DB::queryFirstField("
        SELECT IFNULL(SUM(amount * price_item), 0)
        FROM product_order_detail
        WHERE order_id = %i
    ", $order_id);

    DB::update('product_order',[
        'total_fees' => $total,
        'fecha_modificacion' => date('Y-m-d H:i:s'),
        'last_update' => time()*1000
    ], "product_order_id = %i", $order_id);
}



Flight::route('POST /product_order/liberar_mesa', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    $d = Flight::request()->data->getData();
    $order_id = (int)$d['product_order_id'];

    $order = DB::queryFirstRow(
      "SELECT mesa_id FROM product_order WHERE product_order_id=%i",
      $order_id
    );

    if(!$order || !$order['mesa_id']){
      Flight::json(['status'=>'error'],400);
      return;
    }

    DB::startTransaction();
    try {

        // cerrar orden
        DB::update('product_order',[
          'modo_order_id' => 3, // MESA PAGADO
          'fecha_fin' => date('Y-m-d H:i:s'),
          'fecha_modificacion' => date('Y-m-d H:i:s'),
          'last_update' => time()*1000
        ],"product_order_id=%i",$order_id);

        DB::update('mesa',['estado'=>'DISPONIBLE'],"mesa_id=%i",$mesa_id);


        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch(Exception $e){
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /product_order/liberar_mesa', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    $d = Flight::request()->data->getData();
    $order_id = (int)$d['product_order_id'];

    $order = DB::queryFirstRow(
      "SELECT mesa_id FROM product_order WHERE product_order_id=%i",
      $order_id
    );

    if(!$order || !$order['mesa_id']){
      Flight::json(['status'=>'error'],400);
      return;
    }

    DB::startTransaction();
    try {

        // cerrar orden
        DB::update('product_order',[
          'fecha_fin' => date('Y-m-d H:i:s'),
          'status' => 'CERRADA',
          'fecha_modificacion' => date('Y-m-d H:i:s'),
          'last_update' => time()*1000
        ],"product_order_id=%i",$order_id);

        // liberar mesa
        DB::update('mesa',[
          'estado' => 'DISPONIBLE'
        ],"mesa_id=%i",$order['mesa_id']);

        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch(Exception $e){
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});


Flight::route('POST /ventas/liberarMesaOcupada', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    $d = Flight::request()->data;
    $mesa_id = (int)($d['mesa_id'] ?? 0);

    if(!$mesa_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'Mesa inválida'
        ], 400);
        return;
    }

    // 🔍 verificar pedidos pendientes (hoy o antes)
    $pendientes = DB::queryFirstField("
        SELECT COUNT(*)
        FROM product_order
        WHERE mesa_id = %i
          AND modo_order_id = 2
          AND DATE(fecha_creacion) <= CURDATE()
    ", $mesa_id);

    if($pendientes > 0){
        Flight::json([
            'status' => 'error',
            'msg' => 'La mesa tiene pedidos pendientes'
        ], 409);
        return;
    }

    DB::startTransaction();
    try {

        // liberar mesa
        DB::update('mesa',[
            'estado' => 'DISPONIBLE'
        ], "mesa_id=%i", $mesa_id);

        DB::commit();

        Flight::json([
            'status' => 'ok'
        ]);

    } catch(Exception $e){
        DB::rollback();
        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }
});


Flight::route('GET /reporte/resumen-ventas', function () {

    include DEFINITION;
    login_admin::autentificar_administrador();
    global $wkh_pdf, $varhost;

    $ini = trim($_GET['ini'] ?? '');
    $fin = trim($_GET['fin'] ?? '');

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Fechas requeridas');
    }

    DB::query("SET NAMES 'utf8mb4'");

    // ===============================
    // 1️⃣ PAGO DIRECTO (modo 1)
    // ===============================
    $pago_directo = DB::queryFirstField("
        SELECT IFNULL(SUM(total_fees),0)
        FROM product_order
        WHERE modo_order_id = 1
          AND fecha_creacion BETWEEN %s AND %s
    ", $ini.' 00:00:00', $fin.' 23:59:59');

    // ===============================
    // 2️⃣ MESAS PAGADAS (modo 3)
    // ===============================
    $rows = DB::query("
        SELECT 
            m.nombre AS mesa,
            SUM(po.total_fees) AS total
        FROM product_order po
        INNER JOIN mesa m ON m.mesa_id = po.mesa_id
        WHERE po.modo_order_id = 3
          AND po.fecha_creacion BETWEEN %s AND %s
        GROUP BY m.mesa_id
        ORDER BY m.nombre
    ", $ini.' 00:00:00', $fin.' 23:59:59');

    $total_mesas = array_sum(array_column($rows, 'total'));
    $total_general = $pago_directo + $total_mesas;

    // ===============================
    // 3️⃣ DATA PARA MUSTACHE
    // ===============================
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

    // ===============================
    // 4️⃣ RENDER HTML
    // ===============================
    $html = (new Mustache)->render(
        file_get_contents(
            VARPATH . '/public/reportes/reporte_html/imp_resumen_ventas_dia.html'
        ),
        $template_data
    );

    // ===============================
    // 5️⃣ ARCHIVO TEMPORAL (HTML)
    // ===============================
    $tmp_html = VARPATH . '/public/reportes/archivos_temporales/tmp_resumen.html';
    file_put_contents($tmp_html, $html);

    // ===============================
    // 6️⃣ GENERAR PDF
    // ===============================
    $nombre_pdf = 'resumen_ventas_' . time() . '.pdf';
    $ruta_pdf   = VARPATH . '/public/reportes/archivos_temporales/' . $nombre_pdf;

    $wkh_pdf->addPage($tmp_html);
    exec($wkh_pdf->getCommand($ruta_pdf), $out, $ret);

    if ($ret !== 0 || !file_exists($ruta_pdf)) {
        echo "<pre>NO SE CREÓ EL PDF</pre>";
        exit;
    }

    // ===============================
    // 7️⃣ DESCARGA
    // ===============================
    Flight::redirect(
        $varhost . '/public/reportes/archivos_temporales/' . $nombre_pdf
    );
});


Flight::route('GET /imp_ventas_fecha_admin_excel', function(){

    include DEFINITION;
    login_admin::autentificar_administrador();

    header("Content-Type: application/vnd.ms-excel; charset=utf-8");
    header("Content-Disposition: attachment; filename=ventas_admin.xls");

    $ini = trim($_GET['ini'] ?? '');
    $fin = trim($_GET['fin'] ?? '');
    $admin_id = (int)($_GET['admin_id'] ?? 0);

    if ($ini === '' || $fin === '') {
        Flight::halt(400, 'Debe enviar las fechas ini y fin');
    }

    DB::query("SET NAMES 'utf8mb4'");

    // ===============================
    // CABECERA DE VENTAS (ADMIN)
    // ===============================
    $ventas = DB::query("
        SELECT 
            po.product_order_id,
            po.fecha_creacion,
            c.nombre AS cliente,
            a.nombres_apellidos AS administrador
        FROM product_order po
        LEFT JOIN cliente c 
               ON c.cliente_id = po.cliente_id
        LEFT JOIN administradortbl a 
               ON a.administrador_id = po.administrador_id
        WHERE po.fecha_creacion BETWEEN %s AND %s
          AND ( %i = 0 OR po.administrador_id = %i )
        ORDER BY po.product_order_id
    ",
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

    // ===============================
    // DETALLE POR VENTA
    // ===============================
    foreach ($ventas as $v) {

        $detalles = DB::query("
            SELECT 
                d.product_name,
                d.amount,
                d.price_item,
                (d.amount * d.price_item) AS subtotal
            FROM product_order_detail d
            WHERE d.order_id = %i
        ", $v['product_order_id']);

        foreach ($detalles as $d) {

            $subtotal = (float)$d['subtotal'];
            $total_general += $subtotal;

            echo "<tr>
                <td>{$v['product_order_id']}</td>
                <td>{$v['cliente']}</td>
                <td>{$v['administrador']}</td>
                <td>{$d['product_name']}</td>
                <td>{$d['amount']}</td>
                <td>".number_format($subtotal,2)."</td>
            </tr>";
        }
    }

    // ===============================
    // TOTAL GENERAL
    // ===============================
    echo "<tr>
            <td colspan='5'><strong>TOTAL GENERAL</strong></td>
            <td><strong>".number_format($total_general,2)."</strong></td>
          </tr>";

    echo "</table>";
});
