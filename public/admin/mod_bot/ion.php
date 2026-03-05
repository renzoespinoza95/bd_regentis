<?php
Flight::route('POST /app/login', function () {

    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        Flight::json(['success' => false]);
        return;
    }

    // 🔐 Validar administrador
    $admin = DB::queryFirstRow("
        SELECT administrador_id, nombres_apellidos
        FROM administradortbl
        WHERE email = %s
          AND clavel = %s
          AND is_activo = 1
        LIMIT 1
    ", $email, $password);

    if (!$admin) {
        Flight::json(['success' => false]);
        return;
    }

    // 📅 Fecha de hoy
    $hoy = date('Y-m-d');

    // 💰 Buscar caja del día
    $caja = DB::queryFirstRow("
        SELECT caja_id, estado
        FROM caja
        WHERE administrador_id = %i
          AND DATE(fecha_apertura) = %s
        ORDER BY caja_id DESC
        LIMIT 1
    ", $admin['administrador_id'], $hoy);

    if ($caja) {
        $estado_caja = $caja['estado']; // ABIERTA o CERRADA
        $caja_id     = $caja['caja_id'];
    } else {
        $estado_caja = 'CERRADA';
        $caja_id     = null;
    }

    // 🕒 Actualizar último acceso
    DB::update('administradortbl', [
        'fecha_ultimo_acceso' => date('Y-m-d H:i:s')
    ], 'administrador_id=%i', $admin['administrador_id']);

    // 📤 Respuesta final
    Flight::json([
        'success'             => true,
        'administrador_id'    => $admin['administrador_id'],
        'nombres_apellidos'   => $admin['nombres_apellidos'],
        'estado_caja'         => $estado_caja,
        'caja_id'             => $caja_id
    ]);
});


Flight::route('GET /api/info', function () {

    $version = Flight::request()->query['version'] ?? 0;

    Flight::json([
        'status' => 'success',
        'info' => [
            'active'   => true,
            'tax'      => 18,
            'currency' => 'PEN',
            'shipping' => ['JIMM', 'NENE', 'PLUSS'],
            'version'  => (int)$version
        ]
    ]);
});

// anterior: getListCategory
Flight::route('GET /api/category/list', function () {

    $rows = DB::query("
        SELECT 
            id,
            name,
            icon,
            draft,
            brief,
            color,
            priority,
            created_at,
            last_update
        FROM category
        WHERE draft = 0
        ORDER BY priority ASC, name
    ");

    Flight::json([
        'status'     => 'success',
        'categories' => $rows
    ]);
});

Flight::route('GET /api/product/list', function () {

    // ============================
    // 📄 Paginación
    // ============================
    $page   = max(1, (int)(Flight::request()->query['page'] ?? 1));
    $count  = (int)(Flight::request()->query['count'] ?? 10);
    $offset = ($page - 1) * $count;

    // ============================
    // 🔎 Filtros
    // ============================
    $q           = Flight::request()->query['q'] ?? '';
    $category_id = Flight::request()->query['category_id'] ?? null;

    // ============================
    // 🧠 WHERE dinámico (actualizado)
    // ============================
    $where  = "1=1";
    $params = [];

    if ($q !== '') {
        $where .= " AND p.name LIKE %s";
        $params[] = '%' . $q . '%';
    }

    if ($category_id !== null && $category_id !== '') {
        $where .= " AND pc.category_id = %i";
        $params[] = (int)$category_id;
    }

    // ============================
    // 🔢 TOTAL
    // ============================
    $count_total = DB::queryFirstField("
        SELECT COUNT(DISTINCT p.product_id)
        FROM product p
        LEFT JOIN product_category pc ON pc.product_id = p.product_id
        WHERE $where
    ", ...$params);

    // ============================
    // 📦 LISTADO
    // ============================
    $params[] = $count;
    $params[] = $offset;

    $products = DB::query("
        SELECT DISTINCT
            p.product_id,
            p.name,
            p.price,
            p.price_discount,
            p.created_at,
            p.last_update,
            p.fecha_creacion,
            p.fecha_modificacion
        FROM product p
        LEFT JOIN product_category pc ON pc.product_id = p.product_id
        WHERE $where
        ORDER BY p.product_id DESC
        LIMIT %i OFFSET %i
    ", ...$params);

    // ============================
    // 📤 RESPONSE
    // ============================
    Flight::json([
        'status'      => 'success',
        'count'       => $count,
        'count_total' => (int)$count_total,
        'pages'       => (int)ceil($count_total / $count),
        'products'    => $products
    ]);
});

Flight::route('GET /api/product/detail/@id', function ($id) {

    // ============================
    // 📦 PRODUCTO + STOCK
    // ============================
    $product = DB::queryFirstRow("
        SELECT 
            p.product_id,
            p.name,
            p.price,
            p.price_discount,
            p.description,
            p.created_at,
            p.last_update,
            p.fecha_creacion,
            p.fecha_modificacion,
            IFNULL(i.stock_actual, 0) AS stock
        FROM product p
        LEFT JOIN inventario i 
            ON i.product_id = p.product_id
        WHERE p.product_id = %i
        LIMIT 1
    ", $id);

    if (!$product) {
        Flight::json([
            'status' => 'failed',
            'msg' => 'Product not found'
        ]);
        return;
    }

    // ============================
    // 🖼️ IMÁGENES
    // ============================
    $product_images = DB::query("
        SELECT product_id, name
        FROM product_image
        WHERE product_id = %i
    ", $id);

    // ============================
    // 🏷️ CATEGORÍAS
    // ============================
    $categories = DB::query("
        SELECT 
            c.id,
            c.name,
            c.icon,
            c.brief,
            c.color,
            c.priority
        FROM category c
        INNER JOIN product_category pc 
            ON pc.category_id = c.id
        WHERE pc.product_id = %i
        ORDER BY c.priority ASC
    ", $id);

    // ============================
    // 📤 RESPONSE LIMPIO
    // ============================
    Flight::json([
        'status' => 'success',
        'product' => [
            'product_id'         => (int)$product['product_id'],
            'name'               => $product['name'],
            'price'              => (float)$product['price'],
            'price_discount'     => (float)$product['price_discount'],
            'description'        => $product['description'],
            'created_at'         => (int)$product['created_at'],
            'last_update'        => (int)$product['last_update'],
            'fecha_creacion'     => $product['fecha_creacion'],
            'fecha_modificacion' => $product['fecha_modificacion'],
            'stock'              => (int)$product['stock'],
            'categories'         => $categories,
            'product_images'     => $product_images
        ]
    ]);
});




Flight::route('GET /api/tipo-pago/list', function () {

    $rows = DB::query("
        SELECT 
            tipo_pago_id,
            descripcion,
            orden
        FROM tipo_pago
        ORDER BY orden ASC, descripcion ASC
    ");

    Flight::json([
        'status' => 'success',
        'data'   => $rows
    ]);
});

Flight::route('GET /api/cliente/list', function () {

    $rows = DB::query("
        SELECT 
            *
        FROM cliente
        ORDER BY cliente_id ASC
    ");

    Flight::json([
        'status' => 'success',
        'data'   => $rows
    ]);
});



Flight::route('GET /ion/slider', function () {
    include DEFINITION;
    // Traer sliders visibles
    $rows = DB::query("
        SELECT
            slider_id,
            img,
            descripcion,
            fecha_creacion,
            fecha_fin
        FROM slider
        WHERE is_visible = 1
        ORDER BY orden ASC
    ");

    $news_infos = [];

    foreach ($rows as $r) {
        $news_infos[] = [
            'id'            => (int)$r['slider_id'],
            'title'         => $r['descripcion'],
            'brief_content' => $r['descripcion'],
            'image'         => BUNNY_CDN_BASE . "/" . SLIDER_DIR . "/" . $r['img'],
            'draft'         => 0,
            'status'        => 'FEATURED',
            // Android espera timestamps en milisegundos
            'created_at'    => strtotime($r['fecha_creacion']) * 1000,
            'last_update'   => strtotime($r['fecha_fin']) * 1000,
        ];
    }

    Flight::json([
        'status'     => 'success',
        'news_infos' => $news_infos
    ]);
});

Flight::route(
    'GET /ion/reportepos/@administrador_id/@fecha',
    function ($administrador_id, $fecha) {

        // 🗓️ Si viene "hoy", usamos fecha actual
        if ($fecha === 'hoy') {
            $fecha = date('Y-m-d');
        }

        // 🧪 Validación básica
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            Flight::json([
                'status' => 'failed',
                'msg' => 'Formato de fecha inválido (YYYY-MM-DD)'
            ]);
            return;
        }

        // =====================================================
        // 📦 VENTAS PAGADAS DEL DÍA (MESAS)
        // modo_order_id = 3 → MESA PAGADA
        // =====================================================
        $ventas = DB::query("
            SELECT
                product_order_id,
                administrador_id,
                cliente_id,
                tipo_pago_id,
                mesa_id,
                modo_order_id,
                total_fees,
                tax,
                fecha_fin
            FROM product_order
            WHERE administrador_id = %i
              AND modo_order_id IN (1,3)
              AND DATE(fecha_fin) = %s
            ORDER BY fecha_fin ASC
        ", $administrador_id, $fecha);

        // =====================================================
        // 🔢 RESUMEN DE VENTAS
        // =====================================================
        $resumenVentas = DB::queryFirstRow("
            SELECT
                COUNT(*) AS total_ventas,
                IFNULL(SUM(total_fees), 0) AS total_dia
            FROM product_order
            WHERE administrador_id = %i
              AND modo_order_id IN (1,3)
              AND DATE(fecha_fin) = %s
        ", $administrador_id, $fecha);

        // =====================================================
        // 🧾 BUSCAR CAJA DEL ADMINISTRADOR
        // =====================================================
        $caja = DB::queryFirstRow("
            SELECT caja_id
            FROM caja
            WHERE administrador_id = %i
              AND DATE(fecha_apertura) = %s
            ORDER BY caja_id DESC
            LIMIT 1
        ", $administrador_id, $fecha);


        $movimientos = [];
        $totalIngresos = 0;
        $totalEgresos  = 0;

        if ($caja) {

            // =====================================================
            // 💸 MOVIMIENTOS DE CAJA
            // =====================================================
            $movimientos = DB::query("
                SELECT
                    fecha,
                    tipo,
                    origen,
                    monto,
                    medio_pago,
                    referencia_id,
                    referencia_tabla
                FROM caja_movimiento
                WHERE caja_id = %i
                ORDER BY fecha ASC
            ", $caja['caja_id']);

            // =====================================================
            // 📊 RESUMEN CAJA
            // =====================================================
            $res = DB::queryFirstRow("
                SELECT
                    IFNULL(SUM(CASE WHEN tipo = 'INGRESO' THEN monto ELSE 0 END), 0) AS ingresos,
                    IFNULL(SUM(CASE WHEN tipo = 'EGRESO' THEN monto ELSE 0 END), 0) AS egresos
                FROM caja_movimiento
                WHERE caja_id = %i
            ", $caja['caja_id']);

            $totalIngresos = (float)$res['ingresos'];
            $totalEgresos  = (float)$res['egresos'];
        }

        // =====================================================
        // 📤 RESPONSE FINAL
        // =====================================================
        Flight::json([
            'status' => 'success',
            'fecha' => $fecha,
            'administrador_id' => (int)$administrador_id,

            'ventas' => [
                'resumen' => [
                    'total_ventas' => (int)$resumenVentas['total_ventas'],
                    'total_dia'    => (float)$resumenVentas['total_dia']
                ],
                'data' => $ventas
            ],

            'caja' => [
                'movimientos' => $movimientos,
                'resumen' => [
                    'total_ingresos' => $totalIngresos,
                    'total_egresos'  => $totalEgresos,
                    'diferencia'     => round($totalIngresos - $totalEgresos, 2)
                ]
            ]
        ]);
    }
);


Flight::route('GET /api/mesa/pedido-activo/@mesa_id', function ($mesa_id) {

    // ⏱️ Rango del día (en ms)
    $todayStart = strtotime('today') * 1000;
    $todayEnd   = strtotime('tomorrow') * 1000;

    // 🧾 PEDIDO ACTIVO DE MESA (MESA PEDIDO = 2)
    $order = DB::queryFirstRow("
        SELECT *
        FROM product_order
        WHERE mesa_id = %i
          AND modo_order_id = 2
          AND created_at BETWEEN %i AND %i
        ORDER BY product_order_id DESC
        LIMIT 1
    ", $mesa_id, $todayStart, $todayEnd);

    // 🔴 NO HAY PEDIDO ACTIVO
    if (!$order) {
        Flight::json([
            'status' => 'empty'
        ]);
        return;
    }

    // 📦 DETALLE DEL PEDIDO
    $details = DB::query("
        SELECT *
        FROM product_order_detail
        WHERE order_id = %i
    ", $order['product_order_id']);

    Flight::json([
        'status' => 'success',
        'order'  => $order,
        'items'  => $details
    ]);
});



Flight::route('POST /api/mesa/agregar-productos', function () {

    $payload = json_decode(file_get_contents('php://input'), true);

    DB::startTransaction();

    try {

        foreach ($payload['items'] as $d) {

            DB::insert('product_order_detail', [
                'order_id'    => $payload['order_id'],
                'product_id'  => $d['product_id'],
                'product_name'=> $d['product_name'],
                'amount'      => $d['amount'],
                'price_item'  => $d['price_item'],
                'created_at'  => $d['created_at'],
                'last_update' => $d['last_update']
            ]);

            DB::query("
                UPDATE inventario
                SET stock_actual = stock_actual - %i
                WHERE product_id = %i
            ", $d['amount'], $d['product_id']);
        }

        DB::commit();

        Flight::json(['status' => 'success']);

    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status' => 'failed']);
    }
});

Flight::route('POST /api/mesa/crear-pedido', function () {

    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload || empty($payload['mesa_id'])) {
        Flight::json([
            'status' => 'failed',
            'msg'    => 'mesa_id requerido'
        ]);
        return;
    }

    $mesa_id = (int)$payload['mesa_id'];

    // 🧭 modo_order_id = 2 → MESA PEDIDO
    $modo_order_id = 2;

    // ⏱️ Fechas
    $fecha_inicio = date('Y-m-d H:i:s');
    $now_ms = (int)(microtime(true) * 1000);

    DB::startTransaction();

    try {

        // 🔎 Validar mesa
        $mesa = DB::queryFirstRow("
            SELECT estado
            FROM mesa
            WHERE mesa_id = %i
            LIMIT 1
        ", $mesa_id);

        if (!$mesa) {
            DB::rollback();
            Flight::json([
                'status' => 'failed',
                'msg'    => 'La mesa no existe'
            ]);
            return;
        }

        if ($mesa['estado'] !== 'DISPONIBLE') {
            DB::rollback();
            Flight::json([
                'status' => 'failed',
                'msg'    => 'La mesa ya está ocupada'
            ]);
            return;
        }

        // 🧾 Crear pedido (SOLO CAMPOS EXISTENTES)
        DB::insert('product_order', [
            'mesa_id'        => $mesa_id,
            'modo_order_id'  => $modo_order_id,
            'fecha_inicio'   => $fecha_inicio,
            'created_at'     => $now_ms,
            'last_update'    => $now_ms,
            'total_fees'     => 0,
            'tax'            => 0
        ]);

        $order_id = DB::insertId();

        // 🔒 Marcar mesa ocupada
        DB::update('mesa', [
            'estado' => 'OCUPADA'
        ], 'mesa_id = %i', $mesa_id);

        DB::commit();

        Flight::json([
            'status' => 'success',
            'order'  => [
                'product_order_id' => $order_id,
                'mesa_id'          => $mesa_id,
                'modo_order_id'    => $modo_order_id,
                'fecha_inicio'     => $fecha_inicio
            ]
        ]);

    } catch (Exception $e) {

        DB::rollback();

        // ⚠️ En desarrollo (opcional)
        Flight::json([
            'status' => 'failed',
            'msg'    => $e->getMessage(),
            'error'  => DB::error()
        ]);
    }
});


Flight::route('POST /api/order/submit', function () {

    try {

        // =====================================================
        // 📥 LEER PAYLOAD
        // =====================================================
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);

        if (!$payload) {
            throw new Exception("JSON inválido", 100);
        }

        if (empty($payload['product_order'])) {
            throw new Exception("Falta product_order", 101);
        }

        if (empty($payload['product_order_detail'])) {
            throw new Exception("Falta product_order_detail", 102);
        }

        $o = $payload['product_order'];
        $details = $payload['product_order_detail'];

        // =====================================================
        // ⏱️ FECHAS
        // =====================================================
        $nowMs  = (int)(microtime(true) * 1000);
        $nowSql = date('Y-m-d H:i:s');

        $mesa_id = (int)($o['mesa_id'] ?? 0);
        $order_id = null;
        $serial = null;

        DB::startTransaction();

        // =====================================================
        // 🪑 BUSCAR PEDIDO ABIERTO SI ES MESA
        // =====================================================
        if ($mesa_id > 0) {

            $order = DB::queryFirstRow("
                SELECT product_order_id
                FROM product_order
                WHERE mesa_id = %i
                  AND status = 'ABIERTA'
                  AND fecha_fin IS NULL
                ORDER BY product_order_id DESC
                LIMIT 1
            ", $mesa_id);

            if ($order) {
                $order_id = (int)$order['product_order_id'];
            }
        }

        // =====================================================
        // ➕ CREAR PRODUCT_ORDER SI NO EXISTE
        // =====================================================
        // =====================================================
        // ➕ CREAR PRODUCT_ORDER (PAGO DIRECTO)
        // =====================================================
        if (!$order_id) {

            $modo_order_id = 1; // ✅ PAGO DIRECTO
            $serial = 'POS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));

            DB::insert('product_order', [
                'cliente_id'       => $o['cliente_id'] ?? null,
                'administrador_id' => $o['administrador_id'] ?? null,
                'caja_id'          => $o['caja_id'] ?? null,
                'tipo_pago_id'     => $o['tipo_pago_id'] ?? null,

                'mesa_id'          => null,              // 👈 DIRECTO, no mesa
                'modo_order_id'    => $modo_order_id,    // 👈 1 = DIRECTO

                'total_fees'       => $o['total_fees'] ?? 0,
                'tax'              => $o['tax'] ?? 0,
                'serial'           => $serial,

                'fecha_inicio'     => null,
                'fecha_fin'        => $nowSql,            // 👈 ya está pagado

                'created_at'       => $nowMs,
                'last_update'      => $nowMs
            ]);

            $order_id = DB::insertId();

            if (!$order_id) {
                throw new Exception("No se pudo crear product_order", 200);
            }
        }


        // =====================================================
        // 📦 INSERTAR DETALLES
        // =====================================================
        foreach ($details as $i => $d) {

            if (empty($d['product_id'])) {
                throw new Exception("Detalle[$i]: product_id vacío", 300);
            }

            if (empty($d['amount'])) {
                throw new Exception("Detalle[$i]: amount vacío", 301);
            }

            if (!isset($d['price_item'])) {
                throw new Exception("Detalle[$i]: price_item vacío", 302);
            }

            // 🔍 OBTENER NOMBRE DEL PRODUCTO
            $product_name = DB::queryFirstField(
                "SELECT name FROM product WHERE product_id = %i",
                $d['product_id']
            );

            if (!$product_name) {
                throw new Exception(
                    "Producto no existe (product_id={$d['product_id']})",
                    303
                );
            }

            DB::insert('product_order_detail', [
                'order_id'     => $order_id,
                'product_id'   => $d['product_id'],
                'product_name' => $product_name,
                'amount'       => $d['amount'],
                'price_item'   => $d['price_item'],
                'created_at'   => $nowMs,
                'last_update'  => $nowMs
            ]);

            // =================================================
            // 📉 DESCONTAR STOCK
            // =================================================
            $rows = DB::query("
                UPDATE inventario
                SET stock_actual = stock_actual - %i
                WHERE product_id = %i
            ", $d['amount'], $d['product_id']);

            if (DB::affectedRows() === 0) {
                throw new Exception(
                    "Inventario no encontrado para product_id={$d['product_id']}",
                    400
                );
            }
        }

        DB::commit();

        Flight::json([
            'status' => 'success',
            'data' => [
                'order_id' => $order_id,
                'serial'   => $serial
            ]
        ]);

    } catch (Throwable $e) {

        DB::rollback();

        Flight::json([
            'status' => 'failed',
            'error' => [
                'code'    => $e->getCode(),
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ],
            'payload' => $payload ?? null
        ], 500);
    }
});


Flight::route('GET /api/mesa/pedido-abierto/@mesa_id', function ($mesa_id) {

    // 🔎 Buscar pedido abierto de mesa
    $order = DB::queryFirstRow("
        SELECT *
        FROM product_order
        WHERE mesa_id = %i
          AND modo_order_id = 2
          AND fecha_fin IS NULL
        ORDER BY product_order_id DESC
        LIMIT 1
    ", $mesa_id);

    // 🔴 No hay pedido abierto
    if (!$order) {
        Flight::json([
            'status' => 'empty'
        ]);
        return;
    }

    // 📦 Detalle del pedido
    $items = DB::query("
        SELECT 
            product_id,
            product_name,
            amount,
            price_item,
            (amount * price_item) AS total
        FROM product_order_detail
        WHERE order_id = %i
    ", $order['product_order_id']);

    // 🧮 Totales
    $subtotal = 0;
    foreach ($items as $i) {
        $subtotal += (float)$i['total'];
    }

    $tax   = (float)($order['tax'] ?? 0);
    $total = $subtotal + $tax;

    // ✅ Response final
    Flight::json([
        'status' => 'success',
        'data' => [
            'order_id' => $order['product_order_id'],
            'mesa_id'  => $order['mesa_id'],
            'subtotal' => $subtotal,
            'tax'      => $tax,
            'total'    => $total,
            'items'    => $items
        ]
    ]);
});



Flight::route('GET /api/order/detail/@id', function ($id) {

    $orderId = (int)$id;

    if ($orderId <= 0) {
        Flight::json([
            'status' => 'failed',
            'msg' => 'ID de orden inválido'
        ]);
        return;
    }

    // 📦 Detalle de productos
    $items = DB::query("
        SELECT
            d.product_id,
            d.product_name,
            d.amount,
            d.price_item,
            o.total_fees
        FROM product_order_detail d
        INNER JOIN product_order o 
            ON o.product_order_id = d.order_id
        WHERE d.order_id = %i
        ORDER BY d.product_order_detail_id ASC
    ", $orderId);

    if (!$items) {
        Flight::json([
            'status' => 'failed',
            'msg' => 'Orden sin detalle'
        ]);
        return;
    }

    Flight::json([
        'status'   => 'success',
        'order_id' => $orderId,
        'total'    => (float)$items[0]['total_fees'],
        'data'     => $items
    ]);
});


Flight::route('POST /api/order/submitMesa', function () {

    $payload = json_decode(file_get_contents('php://input'), true);

    if (
        !$payload ||
        empty($payload['product_order']) ||
        empty($payload['product_order']['mesa_id'])
    ) {
        Flight::json([
            'status' => 'failed',
            'msg'    => 'mesa_id requerido'
        ]);
        return;
    }

    $o = $payload['product_order'];

    $mesa_id    = (int)$o['mesa_id'];
    $adminId    = $o['administrador_id'] ?? null;
    $cajaId     = $o['caja_id'] ?? null;
    $clienteId  = $o['cliente_id'] ?? null;
    $tipoPagoId = $o['tipo_pago_id'] ?? null;

    $nowMs  = (int)(microtime(true) * 1000);
    $nowSql = date('Y-m-d H:i:s');

    DB::startTransaction();

    try {

        // =====================================================
        // 🔎 BUSCAR PEDIDO ABIERTO DE LA MESA
        // modo_order_id = 2  → MESA
        // fecha_fin IS NULL  → ABIERTO
        // =====================================================
        $order = DB::queryFirstRow("
            SELECT product_order_id
            FROM product_order
            WHERE mesa_id = %i
              AND modo_order_id = 2
              AND fecha_fin IS NULL
            ORDER BY product_order_id DESC
            LIMIT 1
        ", $mesa_id);

        if (!$order) {
            throw new Exception('No hay pedido abierto para esta mesa');
        }

        $order_id = (int)$order['product_order_id'];

        // =====================================================
        // 💰 CALCULAR TOTAL DESDE DETALLE
        // =====================================================
        $total = DB::queryFirstField("
            SELECT IFNULL(SUM(amount * price_item), 0)
            FROM product_order_detail
            WHERE order_id = %i
        ", $order_id);

        $total = round((float)$total, 2);

        // =====================================================
        // 🧾 SERIAL
        // =====================================================
        $serial = 'MESA-' . date('Y') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

        // =====================================================
        // 🔒 CERRAR PEDIDO (PAGADO)
        // =====================================================
        DB::update('product_order', [
            'modo_order_id'    => 3, // 🟢 MESA PAGADO
            'serial'           => $serial,
            'total_fees'       => $total,
            'fecha_fin'        => $nowSql,
            'last_update'      => $nowMs,
            'administrador_id' => $adminId,
            'caja_id'          => $cajaId,
            'cliente_id'       => $clienteId,
            'tipo_pago_id'     => $tipoPagoId
        ], 'product_order_id = %i', $order_id);


        // =====================================================
        // 🔓 LIBERAR MESA
        // =====================================================
        DB::update('mesa', [
            'estado' => 'DISPONIBLE'
        ], 'mesa_id = %i', $mesa_id);

        DB::commit();

        Flight::json([
            'status' => 'success',
            'data' => [
                'order_id' => $order_id,
                'serial'   => $serial,
                'total'    => number_format($total, 2, '.', '')
            ]
        ]);

    } catch (Exception $e) {

        DB::rollback();

        error_log('[submitMesa ERROR] ' . $e->getMessage());

        Flight::json([
            'status' => 'failed',
            'msg'    => $e->getMessage()
        ]);
    }
});


Flight::route('POST /ion/mesa/liberarMesaVacia', function () {

    // 📥 Leer payload JSON
    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload || empty($payload['mesa_id'])) {
        Flight::json([
            'status' => 'failed',
            'msg'    => 'mesa_id requerido'
        ], 400);
        return;
    }

    $mesa_id = (int)$payload['mesa_id'];

    // 🔎 Verificar que la mesa exista
    $mesa = DB::queryFirstRow("
        SELECT mesa_id, estado
        FROM mesa
        WHERE mesa_id = %i
        LIMIT 1
    ", $mesa_id);

    if (!$mesa) {
        Flight::json([
            'status' => 'failed',
            'msg'    => 'La mesa no existe'
        ], 404);
        return;
    }

    // 🔓 Liberar mesa
    DB::update('mesa', [
        'estado' => 'DISPONIBLE'
    ], 'mesa_id = %i', $mesa_id);

    Flight::json([
        'status' => 'success',
        'data'   => [
            'mesa_id' => $mesa_id,
            'estado'  => 'DISPONIBLE'
        ]
    ]);
});

Flight::route('GET /ion/slider/yape', function () {

    $row = DB::queryFirstRow("
        SELECT
            slider_id,
            img,
            descripcion
        FROM slider
        WHERE grupo = 'B'
          AND is_visible = 1
        ORDER BY orden ASC
        LIMIT 1
    ");

    if (!$row) {
        Flight::json([
            'status' => 'failed',
            'msg' => 'No hay slider Yape disponible'
        ], 404);
        return;
    }

    Flight::json([
        'status' => 'success',
        'data' => [
            'slider_id'  => (int)$row['slider_id'],
            'image'      => BUNNY_CDN_BASE . '/' . SLIDER_DIR . '/' . $row['img'],
            'descripcion'=> $row['descripcion']
        ]
    ]);
});

Flight::route('POST /ion/guardarYape', function () {

    include DEFINITION;
    global $varhost;

    define('VARPATH', dirname(__FILE__));
    $UPLOAD_DIR = VARPATH . '/pics/yape/';

    /* ================================
     * VALIDACIÓN
     * ================================ */
    if (!isset($_FILES['imagen'])) {
        Flight::json([
            'status' => 'error',
            'message' => 'No se recibió la imagen'
        ]);
        return;
    }

    if (!isset($_POST['product_order_id'])) {
        Flight::json([
            'status' => 'error',
            'message' => 'No se recibió product_order_id'
        ]);
        return;
    }

    $product_order_id = (int) $_POST['product_order_id'];

    if ($product_order_id <= 0) {
        Flight::json([
            'status' => 'error',
            'message' => 'product_order_id inválido'
        ]);
        return;
    }

    /* ================================
     * CREAR DIRECTORIO
     * ================================ */
    if (!is_dir($UPLOAD_DIR)) {
        mkdir($UPLOAD_DIR, 0755, true);
    }

    try {

        /* ================================
         * PROCESAR IMAGEN
         * ================================ */
        $img = new SimpleImage();
        $img->load($_FILES['imagen']['tmp_name']);

        $nombreArchivo = 'yape_' . $product_order_id . '_' . date('Ymd_His') . '.jpg';
        $rutaFisica    = $UPLOAD_DIR . $nombreArchivo;
        $rutaRelativa  = 'pics/yape/' . $nombreArchivo;

        $img->save($rutaFisica, IMAGETYPE_JPEG, 90);

        /* ================================
         * INSERT BD
         * ================================ */
        DB::insert('yape', [
            'product_order_id' => $product_order_id,
            'img'              => $rutaRelativa
        ]);

        /* ================================
         * RUTA COMPLETA
         * ================================ */
        $urlCompleta = rtrim($varhost, '/') . '/ion/' . $rutaRelativa;

        Flight::json([
            'status' => 'success',
            'message' => 'Imagen Yape guardada correctamente',
            'img' => $urlCompleta
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status' => 'error',
            'message' => 'Error al guardar imagen',
            'error' => $e->getMessage()
        ]);
    }
});


Flight::route('GET /ion/listaYape', function () {

    include DEFINITION;
    global $varhost;

    try {

        $rows = DB::query("
            SELECT 
                yape_id,
                product_order_id,
                fecha_creacion,
                img
            FROM yape
            ORDER BY yape_id DESC
            LIMIT 100
        ");

        $data = [];

        foreach ($rows as $r) {
            $data[] = [
                'yape_id'          => (int)$r['yape_id'],
                'product_order_id' => (int)$r['product_order_id'],
                'fecha_creacion'   => $r['fecha_creacion'],
                'img'              => $varhost . $r['img']
            ];
        }

        Flight::json([
            'status' => 'success',
            'data'   => $data
        ]);

    } catch (Throwable $e) {

        Flight::json([
            'status'  => 'error',
            'message' => 'Error al listar yapes',
            'error'   => $e->getMessage()
        ], 500);
    }
});


Flight::route('GET /ion/pantallaYape/@product_order_id', function ($product_order_id) {

    include DEFINITION; // donde está DB y $varhost

    try {

        $row = DB::queryFirstRow("
            SELECT
                yape_id,
                fecha_creacion,
                product_order_id,
                img
            FROM yape
            WHERE product_order_id = %i
            ORDER BY fecha_creacion DESC
            LIMIT 1
        ", $product_order_id);

        if (!$row) {
            Flight::json([
                'status'  => 'error',
                'message' => 'No existe Yape para este pedido'
            ]);
            return;
        }

        // ruta completa de la imagen
        $row['img_full'] = $varhost . "/" . $row['img'];

        Flight::json([
            'status' => 'success',
            'data'   => $row
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status'  => 'error',
            'message' => 'Error al obtener Yape',
            'error'   => $e->getMessage()
        ]);
    }
});


Flight::route('POST /pos/eliminarDetallePedido', function () {

    $payload = json_decode(file_get_contents('php://input'), true);

    if (!$payload || empty($payload['product_order_detail_id'])) {
        Flight::json([
            'status' => 'failed',
            'msg'    => 'product_order_detail_id requerido'
        ]);
        return;
    }

    $detail_id = (int)$payload['product_order_detail_id'];

    DB::startTransaction();

    try {

        // 🔎 Verificar que exista el detalle
        $exists = DB::queryFirstField("
            SELECT COUNT(*)
            FROM product_order_detail
            WHERE product_order_detail_id = %i
        ", $detail_id);

        if ($exists == 0) {
            throw new Exception("Detalle no encontrado");
        }

        // 🗑️ Eliminar SOLO el detalle seleccionado
        DB::query("
            DELETE FROM product_order_detail
            WHERE product_order_detail_id = %i
        ", $detail_id);

        DB::commit();

        Flight::json([
            'status' => 'success'
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status' => 'failed',
            'msg'    => $e->getMessage()
        ]);
    }
});
