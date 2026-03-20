<?php
Flight::route('GET /nn', function () {
    categorias_nuevo_negocio(1);
    echo poke();

});

Flight::route('GET /tt/phpi', function () {
    phpinfo();
});
// =============================================
// Endpoint para generar datos de compra, inventario y kardex
// =============================================

Flight::route('GET /generar-dataset', function () {

    // Lee productos y proveedores desde la base de datos
    $productos = DB::query("SELECT id, name, price FROM product WHERE price > 0");  // Solo productos con precio mayor a 0
    $proveedores = DB::query("SELECT proveedor_id, nombre FROM proveedor WHERE is_activo = 1");

    $compras = [];
    $detalle = [];
    $movimientos = [];
    $compraID = 1;

    // ========================== FUNCIONES AUXILIARES ============================
    // Generar fecha aleatoria entre 2024-01-01 y 2024-12-31
    function fecha_random_2024() {
        $inicio = strtotime("2024-01-01 00:00:00");
        $fin    = strtotime("2024-12-31 23:59:59");
        $randTS = rand($inicio, $fin);
        return date("Y-m-d H:i:s", $randTS);
    }

    // Costo de compra aleatorio entre 70% del precio ±5%
    function costo_compra($price) {
        $base = $price * 0.70;
        $var  = $base * (rand(-5, 5) / 100);
        return round($base + $var, 2);
    }

    // Subtotal con ligera variación
    function subtotal_variado($cantidad, $costo) {
        $base = $cantidad * $costo;
        $var  = $base * (rand(-3, 3) / 100);
        return round($base + $var, 2);
    }

    // ========================= GENERAR COMPRAS =========================
    foreach (range(1, 500) as $n) {

        $fecha = fecha_random_2024();
        $prov  = $proveedores[array_rand($proveedores)];
        $proveedor_id = $prov['proveedor_id'];

        // Cantidad de ítems por compra (2 a 10 productos)
        $numItems = rand(2, 10);

        $items = [];
        $total_compra = 0;

        for ($i = 0; $i < $numItems; $i++) {

            // Producto aleatorio
            $prod = $productos[array_rand($productos)];

            // Cantidad aleatoria entre 1 y 20
            $cantidad = rand(1, 20);
            $costo = costo_compra($prod['price']);
            $subtotal = subtotal_variado($cantidad, $costo);

            // Actualiza el inventario acumulado
            $inventario = DB::queryFirstField("SELECT stock_actual FROM inventario WHERE producto_id = %i", $prod['id']);
            $new_stock = $inventario + $cantidad;

            DB::update('inventario', [
                'stock_actual' => $new_stock
            ], "producto_id = %i", $prod['id']);

            // Detalle de la compra
            $detalle[] = [
                'compra_id'     => $compraID,
                'producto_id'   => $prod['id'],
                'cantidad'      => $cantidad,
                'precio_unitario' => $costo,
                'subtotal'      => $subtotal
            ];

            // Movimiento de inventario (entrada)
            $movimientos[] = [
                'producto_id'       => $prod['id'],
                'tipo'              => 'ENTRADA',
                'origen'            => 'COMPRA',
                'cantidad'          => $cantidad,
                'precio_unitario'   => $costo,
                'fecha'             => $fecha,
                'referencia_id'     => $compraID,
                'referencia_tabla'  => 'compra',
                'stock_resultante'  => $new_stock
            ];

            // Total compra
            $total_compra += $subtotal;
        }

        // Inserta la cabecera de la compra
        DB::insert('compra', [
            'proveedor_id'   => $proveedor_id,
            'fecha_creacion' => $fecha,
            'total_compra'   => round($total_compra, 2),
            'observaciones'  => 'Compra generada automáticamente'
        ]);

        $compraID++;  // Incrementa el ID de la compra
    }

    // ========================== EXPORTAR A BASE DE DATOS =========================
    // Inserta todos los detalles de las compras
    DB::insert('compra_detalle', $detalle);

    // Inserta todos los movimientos de inventario
    DB::insert('inventario_movimiento', $movimientos);

    // Respuesta exitosa
    Flight::json(['status' => 'ok', 'message' => 'Dataset generado exitosamente']);
});



Flight::route('GET /generar-dataset-ventas', function() {

    // ==========================
    // 1) Validar columna administrador_id
    // ==========================
    $col = DB::queryFirstField("
        SELECT COUNT(*) 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE table_name='product_order'
        AND table_schema = DATABASE()
        AND column_name='administrador_id'
    ");

    if ($col == 0) {
        DB::query("ALTER TABLE product_order ADD COLUMN administrador_id BIGINT NULL");
    }

    // ==========================
    // 2) Leer productos y categorías
    // ==========================
    $productos = DB::query("SELECT id, name, price FROM product WHERE price > 0");
    $categorias = DB::query("SELECT id FROM category");

    if (!$productos) {
        Flight::json(['ok'=>false,'msg'=>'No hay productos con precio válido']);
        return;
    }
    if (!$categorias) {
        Flight::json(['ok'=>false,'msg'=>'No hay categorías']);
        return;
    }

    // ==========================
    // 3) Reset tablas
    // ==========================
    DB::query("SET FOREIGN_KEY_CHECKS=0");
    DB::query("TRUNCATE TABLE product_category");
    DB::query("TRUNCATE TABLE product_order_detail");
    DB::query("TRUNCATE TABLE product_order");
    DB::query("SET FOREIGN_KEY_CHECKS=1");

    $shipping_methods = ["delivery","pickup","express"];
    $statuses = ["PENDING","PROCESSING","SENT","FINISHED","CANCELLED"];

    $NUM_ORDENES = 300;
    $admin_demo = 1;

    for ($i=1; $i<=$NUM_ORDENES; $i++) {

        $created_ts = time() - rand(0, 3600*24*120);

        // ==========================
        // INSERT ORDEN
        // ==========================
        DB::insert("product_order", [
            "administrador_id" => $admin_demo,
            "code"             => "ORD-" . str_pad($i,6,"0",STR_PAD_LEFT),
            "buyer"            => "Cliente ".rand(1000,9999),
            "address"          => "Av. Siempre Viva ".rand(100,999),
            "email"            => "cliente".rand(1000,9999)."@gmail.com",
            "shipping"         => $shipping_methods[array_rand($shipping_methods)],
            "date_ship"        => $created_ts + rand(3600,86400),
            "phone"            => "9".rand(10000000,99999999),
            "comment"          => "Pedido generado automáticamente",
            "status"           => $statuses[array_rand($statuses)],
            "total_fees"       => 0,
            "tax"              => 0,
            "serial"           => uniqid(),
            "created_at"       => $created_ts,
            "last_update"      => $created_ts,
            "fecha_creacion"   => date("Y-m-d H:i:s", $created_ts),
            "fecha_modificacion"=>date("Y-m-d H:i:s", $created_ts)
        ]);

        // ❗ IMPORTANTE
        $order_id = DB::insertId();

        if (!$order_id) {
            Flight::json(["ok"=>false, "msg"=>"Fallo al insertar order ".$i]);
            return;
        }

        // ==========================
        // ITEMS
        // ==========================
        $num_items = rand(1,5);
        $items = array_rand($productos, $num_items);
        if (!is_array($items)) $items = [$items];

        $total = 0;

        foreach ($items as $idx) {
            $p = $productos[$idx];
            $qty = rand(1,4);
            $price = floatval($p['price']);

            DB::insert("product_order_detail", [
                "order_id"          => $order_id,
                "product_id"        => $p["id"],
                "product_name"      => $p["name"],
                "amount"            => $qty,
                "price_item"        => $price,
                "created_at"        => $created_ts,
                "last_update"       => $created_ts,
                "fecha_creacion"    => date("Y-m-d H:i:s",$created_ts),
                "fecha_modificacion"=> date("Y-m-d H:i:s",$created_ts)
            ]);

            $total += $qty * $price;
        }

        DB::update("product_order", [
            "total_fees" => $total,
            "tax"        => round($total * 0.18,2),
            "last_update"=> time()
        ], "id=%i",$order_id);
    }

    Flight::json(["ok"=>true,"msg"=>"Dataset generado correctamente"]);
});


/* ============================================
 *  GET /compra/recalcular-todos
 * ============================================ */
Flight::route('GET /compra/recalcular-todos', function () {

    $compras = DB::query("SELECT compra_id FROM compra");

    foreach ($compras as $c) {

        $row = DB::queryFirstRow("
            SELECT IFNULL(SUM(subtotal), 0) AS total_real
            FROM compra_detalle
            WHERE compra_id = %i
        ", $c['compra_id']);

        DB::update('compra', [
            'total_compra' => floatval($row['total_real'])
        ], "compra_id=%i", $c['compra_id']);
    }

    Flight::json([
        'status' => 'ok',
        'msg'    => 'Totales recalculados correctamente'
    ]);
});

Flight::route('GET /tt/asignar-categorias-azar', function () {

    include DEFINITION;
    login_admin::autentificar_administrador();

    // ============================
    // OBTENER PRODUCTOS Y CATEGORÍAS
    // ============================
    $productos   = DB::query("SELECT id FROM product");
    $categorias  = DB::query("SELECT id FROM category");

    if (empty($productos) || empty($categorias)) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'No hay productos o categorías'
        ], 400);
        return;
    }

    DB::startTransaction();

    try {

        foreach ($productos as $p) {

            $producto_id = $p['id'];

            // 🔹 cantidad aleatoria de categorías (1 a 3)
            $cantidad = rand(1, min(3, count($categorias)));

            // 🔹 mezclar categorías
            $cats_random = $categorias;
            shuffle($cats_random);

            // 🔹 tomar las primeras N
            $seleccionadas = array_slice($cats_random, 0, $cantidad);

            foreach ($seleccionadas as $c) {

                DB::insertIgnore('product_category', [
                    'product_id'  => $producto_id,
                    'category_id' => $c['id']
                ]);
            }
        }

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'msg'    => 'Categorías asignadas aleatoriamente a los productos'
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);
    }
});


Flight::route('GET /tt/llenar_imagenes', function () {

    include DEFINITION;
    login_admin::autentificar_administrador();

    // ============================
    // OBTENER PRODUCTOS
    // ============================
    $productos = DB::query("SELECT id FROM product");

    if (empty($productos)) {
        Flight::json([
            'status' => 'error',
            'msg'    => 'No hay productos'
        ], 400);
        return;
    }

    DB::startTransaction();

    try {

        foreach ($productos as $p) {

            // Evitar duplicar imagen por producto
            $existe = DB::queryFirstField("
                SELECT COUNT(*) 
                FROM product_image 
                WHERE product_id = %i
            ", $p['id']);

            if ($existe == 0) {
                DB::insert('product_image', [
                    'product_id' => $p['id'],
                    'name'       => 'prod01.jpg'
                ]);
            }
        }

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'msg'    => 'Imágenes prod01.jpg asignadas a los productos'
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg'    => $e->getMessage()
        ], 500);
    }
});
