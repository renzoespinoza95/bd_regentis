<?php
Flight::route('POST /M0Jk/carritoCompras', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $usu_id        = intval($d['usu_id'] ?? 0);
    $product_id    = intval($d['product_id'] ?? 0);
    $cantidad      = intval($d['cantidad'] ?? 1);
    $neg_id        = intval($d['neg_id'] ?? 0);
    $fecha_entrega = $d['fecha_entrega'] ?? null;

    if(!$usu_id || !$product_id || !$neg_id){
        Flight::json([
            'status'=>'error',
            'msg'=>'usu_id, product_id y neg_id requeridos'
        ],400);
        return;
    }

    if($cantidad <= 0) $cantidad = 1;

    /* ======================================
       👤 VERIFICAR / CREAR CLIENTE
    ====================================== */
    $cliente = DB::queryFirstRow("
        SELECT cliente_id
        FROM pos_cliente
        WHERE usu_id=%i
          AND neg_id=%i
        LIMIT 1
    ", $usu_id, $neg_id);

    if(!$cliente){

        $usuario = DB::queryFirstRow("
            SELECT 
                nombres_apellidos,
                celular,
                email,
                dni,
                cod_usu,
                map_lat,
                map_lng
            FROM reg_usu
            WHERE usu_id=%i
        ", $usu_id);

        if($usuario){

            DB::insert('pos_cliente', [
                'nombres_apellidos' => $usuario['nombres_apellidos'],
                'dni'               => $usuario['dni'],
                'cod_usu'           => $usuario['cod_usu'],
                'direccion'         => null,
                'celular'           => $usuario['celular'],
                'email'             => $usuario['email'],
                'map_lat'           => $usuario['map_lat'],
                'map_lng'           => $usuario['map_lng'],
                'usu_id'            => $usu_id,
                'neg_id'            => $neg_id,
                'is_activo'         => 1
            ]);

            $cliente_id = DB::insertId(); // 🔥 IMPORTANTE

        } else {
            $cliente_id = null;
        }

    } else {
        $cliente_id = intval($cliente['cliente_id']); // 🔥 IMPORTANTE
    }

    DB::startTransaction();

    try {

        /* ======================================
           🔍 BUSCAR CARRITO ACTIVO
        ====================================== */
        $carrito = DB::queryFirstRow("
            SELECT carrito_id
            FROM reg_carrito
            WHERE usu_id=%i
              AND neg_id=%i
              AND estado='activo'
            LIMIT 1
        ", $usu_id, $neg_id);

        if(!$carrito){

            /* ======================================
               🆕 CREAR NUEVO CARRITO ACTIVO
            ====================================== */
            DB::insert('reg_carrito', [
                'usu_id' => $usu_id,
                'neg_id' => $neg_id,
                'cliente_id' => $cliente_id, // 🔥 AQUÍ VA
                'estado' => 'activo',
                'fecha_entrega' => $fecha_entrega,
                'fecha_modificacion' => date('Y-m-d H:i:s')
            ]);

            $carrito_id = DB::insertId();

        } else {

            $carrito_id = intval($carrito['carrito_id']);

            // 🔥 actualizar fecha si viene
            if($fecha_entrega){
                DB::update('reg_carrito', [
                    'fecha_entrega' => $fecha_entrega,
                    'fecha_modificacion' => date('Y-m-d H:i:s')
                ], "carrito_id=%i", $carrito_id);
            }
        }

        /* ======================================
           🔍 BUSCAR PRODUCTO EN DETALLE
        ====================================== */
        $detalle = DB::queryFirstRow("
            SELECT carrito_detalle_id, cantidad
            FROM reg_carrito_detalle
            WHERE carrito_id=%i
              AND product_id=%i
            LIMIT 1
        ", $carrito_id, $product_id);

        if($detalle){

            /* ======================================
               🔥 SUMAR CANTIDAD
            ====================================== */
            DB::update('reg_carrito_detalle', [
                'cantidad' => $detalle['cantidad'] + $cantidad,
                'fecha_modificacion' => date('Y-m-d H:i:s')
            ], "carrito_detalle_id=%i", $detalle['carrito_detalle_id']);

        } else {

            /* ======================================
               🆕 INSERTAR NUEVO PRODUCTO
            ====================================== */
            $producto = DB::queryFirstRow("
                SELECT price
                FROM pos_product
                WHERE product_id=%i
            ", $product_id);

            if(!$producto){
                DB::rollback();
                Flight::json([
                    'status'=>'error',
                    'msg'=>'Producto no existe'
                ],400);
                return;
            }

            DB::insert('reg_carrito_detalle', [
                'carrito_id' => $carrito_id,
                'product_id' => $product_id,
                'cantidad' => $cantidad,
                'precio_unitario' => $producto['price'],
                'fecha_modificacion' => date('Y-m-d H:i:s')
            ]);
        }

        DB::commit();

        Flight::json([
            'status'=>'ok',
            'carrito_id'=>$carrito_id
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }

});

Flight::route('POST /M0Jk/productosCarritos', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$usu_id){

        Flight::json([
            'status'=>'error',
            'msg'=>'usu_id requerido'
        ],400);

        return;
    }

    try {

        /* ======================================
           NEGOCIOS CON CARRITOS
        ====================================== */

        $negocios = DB::query("

            SELECT DISTINCT

                n.neg_id,
                n.nombre,
                n.img_logo,
                n.descripcion,
                n.puesto

            FROM reg_carrito c

            INNER JOIN reg_neg n
                ON n.neg_id = c.neg_id

            WHERE c.usu_id = %i
            AND c.estado = 'activo'
            AND n.is_activo = 1
            AND n.borrado_el IS NULL

            ORDER BY n.nombre ASC

        ", $usu_id);

        /* ======================================
           RECORRER NEGOCIOS
        ====================================== */

        foreach($negocios as &$neg){

            /* ======================================
               CARRITOS DEL NEGOCIO
            ====================================== */

            $carritos = DB::query("

                SELECT

                    carrito_id,
                    neg_id,
                    estado,
                    fecha_entrega,
                    fecha_creacion,
                    fecha_modificacion,
                    cliente_id,
                    tipo_pedido

                FROM reg_carrito

                WHERE usu_id = %i
                AND neg_id = %i
                AND estado = 'activo'

                ORDER BY carrito_id DESC

            ",
                $usu_id,
                $neg['neg_id']
            );

            /* ======================================
               RECORRER CARRITOS
            ====================================== */

            foreach($carritos as &$carrito){

                /* ======================================
                   PRODUCTOS DEL CARRITO
                ====================================== */

                $productos = DB::query("

                    SELECT

                        cd.carrito_detalle_id,
                        cd.product_id,
                        cd.cantidad,
                        cd.precio_unitario,
                        cd.fecha_creacion,
                        cd.fecha_modificacion,

                        p.name,
                        p.price,
                        p.description,
                        p.cod_producto,

                        SUBSTRING_INDEX(
                            GROUP_CONCAT(
                                pi.img
                                ORDER BY pi.orden ASC
                            ),
                            ',',
                            1
                        ) AS img

                    FROM reg_carrito_detalle cd

                    INNER JOIN pos_product p
                        ON p.product_id = cd.product_id

                    LEFT JOIN pos_product_image pi
                        ON pi.product_id = p.product_id
                        AND pi.is_visible = 1

                    WHERE cd.carrito_id = %i

                    GROUP BY cd.carrito_detalle_id

                    ORDER BY cd.carrito_detalle_id DESC

                ", $carrito['carrito_id']);

                /* ======================================
                   NORMALIZAR
                ====================================== */

                foreach($productos as &$p){

                    $p['product_id'] = intval(
                        $p['product_id']
                    );

                    $p['cantidad'] = intval(
                        $p['cantidad']
                    );

                    $p['precio_unitario'] = floatval(
                        $p['precio_unitario']
                    );

                    $p['price'] = floatval(
                        $p['price']
                    );

                }

                $carrito['productos'] = $productos;

            }

            $neg['carritos'] = $carritos;

        }

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status'=>'ok',

            'data'=>$negocios

        ]);

    } catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});


Flight::route('POST /M0Jk/eliminarProductoCarrito', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $carrito_detalle_id = intval($d['carrito_detalle_id'] ?? 0);

    if(!$carrito_detalle_id){
        Flight::json(['status'=>'error','msg'=>'carrito_detalle_id requerido'],400);
        return;
    }

    try {

        DB::delete(
            'reg_carrito_detalle',
            "carrito_detalle_id=%i",
            $carrito_detalle_id
        );

        Flight::json(['status'=>'ok']);

    } catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }

});

Flight::route('POST /M0Jk/editarCantidad', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $carrito_detalle_id = intval($d['carrito_detalle_id'] ?? 0);
    $cantidad = intval($d['cantidad'] ?? 0);

    if(!$carrito_detalle_id){
        Flight::json(['status'=>'error','msg'=>'carrito_detalle_id requerido'],400);
        return;
    }

    if($cantidad <= 0){
        Flight::json(['status'=>'error','msg'=>'cantidad inválida'],400);
        return;
    }

    try {

        DB::update('reg_carrito_detalle', [
            'cantidad'=>$cantidad,
            'fecha_modificacion'=>date('Y-m-d H:i:s')
        ], "carrito_detalle_id=%i", $carrito_detalle_id);

        Flight::json(['status'=>'ok']);

    } catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }

});

Flight::route('POST /M0Jk/enviarCarrito', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $carrito_id    = intval($d['carrito_id'] ?? 0);
    $usu_id        = intval($d['usu_id'] ?? 0);
    $fecha_entrega = $d['fecha_entrega'] ?? null;

    if(!$carrito_id || !$usu_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'carrito_id y usu_id requeridos'
        ], 400);
        return;
    }

    DB::startTransaction();

    try {

        /* ======================================
           🔍 VALIDAR CARRITO ACTIVO
        ====================================== */
        $carrito = DB::queryFirstRow("
            SELECT carrito_id, estado
            FROM reg_carrito
            WHERE carrito_id=%i
              AND usu_id=%i
              AND estado='activo'
            LIMIT 1
        ", $carrito_id, $usu_id);

        if(!$carrito){
            DB::rollback();
            Flight::json([
                'status' => 'error',
                'msg' => 'Carrito no válido o ya enviado'
            ], 400);
            return;
        }

        /* ======================================
           🔍 VALIDAR QUE TENGA PRODUCTOS
        ====================================== */
        $total_items = DB::queryFirstField("
            SELECT COUNT(*)
            FROM reg_carrito_detalle
            WHERE carrito_id=%i
        ", $carrito_id);

        if($total_items == 0){
            DB::rollback();
            Flight::json([
                'status' => 'error',
                'msg' => 'El carrito está vacío'
            ], 400);
            return;
        }

        /* ======================================
           🚀 CAMBIAR ESTADO + GUARDAR FECHA
        ====================================== */
        DB::update('reg_carrito', [
            'estado' => 'enviado',
            'fecha_entrega' => $fecha_entrega,
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ], "carrito_id=%i", $carrito_id);

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'msg' => 'Pedido enviado correctamente',
            'carrito_id' => $carrito_id,
            'fecha_entrega' => $fecha_entrega
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

Flight::route('POST /VS26/finalizarPedidoCarrito', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $carrito_id    = intval($d['carrito_id'] ?? 0);
    $fecha_entrega = $d['fecha_entrega'] ?? null;

    if(!$carrito_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'carrito_id requerido'
        ], 400);
        return;
    }

    DB::startTransaction();

    try {

        /* ======================================
           🔍 VALIDAR CARRITO
        ====================================== */
        $carrito = DB::queryFirstRow("
            SELECT carrito_id, estado
            FROM reg_carrito
            WHERE carrito_id=%i
            LIMIT 1
        ", $carrito_id);

        if(!$carrito){
            DB::rollback();
            Flight::json([
                'status' => 'error',
                'msg' => 'Carrito no existe'
            ], 404);
            return;
        }

        if($carrito['estado'] != 'activo'){
            DB::rollback();
            Flight::json([
                'status' => 'error',
                'msg' => 'El carrito ya fue procesado'
            ], 400);
            return;
        }

        /* ======================================
           🔍 VALIDAR PRODUCTOS
        ====================================== */
        $total_items = DB::queryFirstField("
            SELECT COUNT(*)
            FROM reg_carrito_detalle
            WHERE carrito_id=%i
        ", $carrito_id);

        if($total_items == 0){
            DB::rollback();
            Flight::json([
                'status' => 'error',
                'msg' => 'El carrito está vacío'
            ], 400);
            return;
        }

        /* ======================================
           🔍 VALIDAR FECHA (opcional pero pro)
        ====================================== */
        if($fecha_entrega){

            $fecha_hoy = date('Y-m-d');
            $fecha_input = date('Y-m-d', strtotime($fecha_entrega));

            if($fecha_input < $fecha_hoy){
                DB::rollback();
                Flight::json([
                    'status' => 'error',
                    'msg' => 'La fecha de entrega no puede ser pasada'
                ], 400);
                return;
            }
        }

        /* ======================================
           🚀 ACTUALIZAR ESTADO + FECHA
        ====================================== */
        DB::update('reg_carrito', [
            'estado' => 'enviado',
            'fecha_entrega' => $fecha_entrega,
            'fecha_modificacion' => date('Y-m-d H:i:s')
        ], "carrito_id=%i", $carrito_id);

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'msg' => 'Pedido finalizado correctamente',
            'carrito_id' => $carrito_id,
            'fecha_entrega' => $fecha_entrega
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

Flight::route('POST /Ow7y/mis_pedidos', function () {

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    $xin = isset($d['xin'])
        ? trim((string)$d['xin'])
        : '';

    $yuan = isset($d['yuan'])
        ? trim((string)$d['yuan'])
        : '';

    /* ======================================
       VALIDAR FIRMA
    ====================================== */
    firma($xin, $yuan);

    /* ======================================
       VALIDAR
    ====================================== */
    if(!$usu_id){

        Flight::json([
            'status' => 'error',
            'msg' => 'usu_id requerido'
        ], 400);

        return;
    }

    try {

        /* ======================================
           LISTAR PEDIDOS
        ====================================== */
        $rows = DB::query("

            SELECT

                c.carrito_id,
                c.usu_id,
                c.neg_id,
                c.estado,
                c.fecha_entrega,
                c.fecha_creacion,
                c.fecha_modificacion,

                n.nombre AS neg_nombre,
                n.img_logo AS neg_logo,
                n.descripcion AS neg_descripcion,
                n.puesto AS neg_puesto,

                (
                    SELECT IFNULL(
                        SUM(cd.cantidad),
                        0
                    )
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = c.carrito_id
                ) AS total_productos,

                (
                    SELECT IFNULL(
                        SUM(
                            cd.cantidad * cd.precio_unitario
                        ),
                        0
                    )
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = c.carrito_id
                ) AS total_pedido

            FROM reg_carrito c

            INNER JOIN reg_neg n
                ON n.neg_id = c.neg_id

            WHERE c.usu_id = %i

            AND c.estado IN (
                'enviado',
                'transito',
                'comprado',
                'devolucion',
                'rechazado',
                'anulado'
            )

            ORDER BY c.carrito_id DESC

        ", $usu_id);

        /* ======================================
           DETALLE PRODUCTOS
        ====================================== */
        foreach($rows as &$r){

            $r['productos'] = DB::query("

                SELECT

                    cd.carrito_detalle_id,
                    cd.product_id,
                    cd.cantidad,
                    cd.precio_unitario,

                    p.name,
                    p.price,

                    (
                        SELECT pi.img

                        FROM pos_product_image pi

                        WHERE pi.product_id = p.product_id

                        ORDER BY pi.product_image_id ASC

                        LIMIT 1

                    ) AS img

                FROM reg_carrito_detalle cd

                INNER JOIN pos_product p
                    ON p.product_id = cd.product_id

                WHERE cd.carrito_id = %i

                ORDER BY cd.carrito_detalle_id ASC

            ", $r['carrito_id']);

        }

        Flight::json([
            'status' => 'ok',
            'data' => $rows
        ]);

    } catch(Exception $e){

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);

    }

});

Flight::route('POST /N5BR/detalle_pedido', function () {

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    try {

        $d = json_decode(
            Flight::request()->getBody(),
            true
        ) ?: [];

        /* ======================================
           CAMPOS
        ====================================== */

        $carrito_id = intval(
            $d['carrito_id'] ?? 0
        );

        $xin = trim(
            $d['xin'] ?? ''
        );

        $yuan = trim(
            $d['yuan'] ?? ''
        );

        /* ======================================
           FIRMA
        ====================================== */

        firma(
            $xin,
            $yuan
        );

        /* ======================================
           VALIDAR
        ====================================== */

        if(!$carrito_id){

            Flight::json([

                'status' => 'error',

                'msg' => 'Pedido inválido'

            ], 400);

            return;
        }

        /* ======================================
           🔍 OBTENER PEDIDO
        ====================================== */

        $pedido = DB::queryFirstRow("

            SELECT

                c.carrito_id,

                c.usu_id,

                c.neg_id,

                c.estado,

                c.tipo_pedido,

                c.fecha_entrega,

                c.fecha_creacion,

                c.fecha_modificacion,

                /* =========================
                   👤 USUARIO
                ========================== */

                u.cod_usu,

                u.nombres_apellidos,

                u.celular,

                u.sobrenombre,

                /* =========================
                   🏪 NEGOCIO
                ========================== */

                n.nombre AS neg_nombre,

                n.img_logo AS neg_logo,

                n.descripcion AS neg_descripcion,

                n.puesto AS neg_puesto,

                /* =========================
                   📦 TOTALES
                ========================== */

                (

                    SELECT IFNULL(
                        SUM(cd.cantidad),
                        0
                    )

                    FROM reg_carrito_detalle cd

                    WHERE cd.carrito_id =
                          c.carrito_id

                ) AS total_productos,

                (

                    SELECT IFNULL(

                        SUM(
                            cd.cantidad
                            *
                            cd.precio_unitario
                        ),

                        0

                    )

                    FROM reg_carrito_detalle cd

                    WHERE cd.carrito_id =
                          c.carrito_id

                ) AS total_pedido

            FROM reg_carrito c

            INNER JOIN reg_neg n
                ON n.neg_id = c.neg_id

            INNER JOIN reg_usu u
                ON u.usu_id = c.usu_id

            WHERE c.carrito_id = %i

            LIMIT 1

        ", $carrito_id);

        if(!$pedido){

            Flight::json([

                'status' => 'error',

                'msg' => 'Pedido no encontrado'

            ], 404);

            return;
        }

        /* ======================================
           📦 PRODUCTOS
        ====================================== */

        $productos = DB::query("

            SELECT

                cd.carrito_detalle_id,

                cd.product_id,

                cd.cantidad,

                cd.precio_unitario,

                p.name,

                p.price,

                (

                    SELECT pi.img

                    FROM pos_product_image pi

                    WHERE pi.product_id =
                          p.product_id

                    ORDER BY
                        pi.product_image_id ASC

                    LIMIT 1

                ) AS img,

                (

                    cd.cantidad
                    *
                    cd.precio_unitario

                ) AS subtotal

            FROM reg_carrito_detalle cd

            INNER JOIN pos_product p
                ON p.product_id = cd.product_id

            WHERE cd.carrito_id = %i

            ORDER BY
                cd.carrito_detalle_id ASC

        ", $carrito_id);

        /* ======================================
           💳 YAPLINS
        ====================================== */

        $yaplins = DB::query("

            SELECT

                yaplin_id,

                estado,

                imagen_url,

                observacion,

                fecha_creacion

            FROM reg_yaplin

            WHERE carrito_id = %i

            ORDER BY yaplin_id DESC

        ", $carrito_id);

        /* ======================================
           🚀 RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'pedido' => $pedido,

            'yaplins' => $yaplins,

            'productos' => $productos

        ]);

    } catch(Exception $e){

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});

Flight::route('POST /nraz/lista_pedidos', function () {

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    try {

        $d = json_decode(
            Flight::request()->getBody(),
            true
        ) ?: [];

        /* ======================================
           CAMPOS
        ====================================== */
        $neg_id = isset($d['neg_id'])
            ? intval($d['neg_id'])
            : 0;

        $xin = isset($d['xin'])
            ? trim((string)$d['xin'])
            : '';

        $yuan = isset($d['yuan'])
            ? trim((string)$d['yuan'])
            : '';

        /* ======================================
           VALIDAR FIRMA
        ====================================== */
        firma($xin, $yuan);

        /* ======================================
           VALIDAR
        ====================================== */
        if(!$neg_id){

            Flight::json([
                'status' => 'error',
                'msg' => 'neg_id inválido'
            ], 400);

            return;
        }

        /* ======================================
           🔍 LISTAR PEDIDOS
        ====================================== */
        $rows = DB::query("

            SELECT

                c.carrito_id,

                CONCAT('#', c.carrito_id) AS codigo,

                c.usu_id,

                c.neg_id,

                c.estado,
                c.tipo_pedido,

                c.fecha_creacion AS fecha,

                IFNULL(
                    u.nombres_apellidos,
                    'Cliente'
                ) AS cliente,

                IFNULL(
                    n.nombre,
                    'Negocio'
                ) AS neg_nombre,

                IFNULL(
                    n.img_logo,
                    ''
                ) AS neg_logo,

                IFNULL(
                    n.puesto,
                    ''
                ) AS neg_puesto,

                (
                    SELECT IFNULL(
                        SUM(cd.cantidad),
                        0
                    )
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = c.carrito_id
                ) AS productos,

                (
                    SELECT IFNULL(
                        SUM(
                            cd.cantidad * cd.precio_unitario
                        ),
                        0
                    )
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = c.carrito_id
                ) AS total

            FROM reg_carrito c

            LEFT JOIN reg_usu u
                ON u.usu_id = c.usu_id

            LEFT JOIN reg_neg n
                ON n.neg_id = c.neg_id

            WHERE c.neg_id = %i

            AND c.estado IN (
                'activo',
                'transito',
                'comprado',
                'devolucion',
                'enviado',
                'rechazado',
                'anulado'
            )

            ORDER BY c.carrito_id DESC

        ", $neg_id);

        /* ======================================
           🔍 BUSCAR YAPLINS
        ====================================== */
        foreach($rows as &$row){

            $carrito_id = intval(
                $row['carrito_id']
            );

            $yaplins = DB::query("

                SELECT

                    yaplin_id,

                    estado,

                    imagen_url

                FROM reg_yaplin

                WHERE carrito_id = %i

                ORDER BY yaplin_id DESC

            ", $carrito_id);

            $row['yaplins'] = $yaplins;
        }

        /* ======================================
           🚀 RESPONSE
        ====================================== */
        Flight::json([
            'status' => 'ok',
            'data' => $rows
        ]);

    } catch(Exception $e){

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);

    }

});

Flight::route('POST /Ow7y/porRecibir', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $usu_id = intval($d['usu_id'] ?? 0);

    if(!$usu_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'usu_id requerido'
        ], 400);
        return;
    }

    try {

        /* ======================================
           🚚 PEDIDOS EN TRANSITO
        ====================================== */
        $rows = DB::query("
            SELECT
                c.carrito_id,
                c.usu_id,
                c.neg_id,
                c.estado,
                c.fecha_entrega,
                c.fecha_creacion,
                c.fecha_modificacion,

                n.nombre AS neg_nombre,
                n.img_logo AS neg_logo,
                n.descripcion AS neg_descripcion,
                n.puesto AS neg_puesto,

                de.deli_entrega_id,
                de.direccion,
                de.fecha_salida,
                de.fecha_entrega AS fecha_entrega_real,
                de.estado AS entrega_estado,

                u.usu_id AS repartidor_id,
                u.nombres_apellidos AS repartidor_nombre,
                u.sobrenombre AS repartidor_sobrenombre,
                u.celular AS repartidor_celular,

                tu.tipoxusu_id,
                tu.descripcion AS tipo_usuario,

                (
                    SELECT IFNULL(SUM(cd.cantidad),0)
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = c.carrito_id
                ) AS total_productos,

                (
                    SELECT IFNULL(SUM(cd.cantidad * cd.precio_unitario),0)
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = c.carrito_id
                ) AS total_pedido

            FROM reg_carrito c

            INNER JOIN reg_neg n
                ON n.neg_id = c.neg_id

            INNER JOIN deli_entrega de
                ON de.carrito_id = c.carrito_id

            INNER JOIN reg_usu u
                ON u.usu_id = de.trab_usu_id

            LEFT JOIN reg_tipoxusu tu
                ON tu.tipoxusu_id = u.tipoxusu_id

            WHERE c.usu_id = %i
              AND c.estado = 'transito'

            ORDER BY c.carrito_id DESC
        ", $usu_id);

        /* ======================================
           📦 DETALLE PRODUCTOS
        ====================================== */
        foreach($rows as &$r){

            $r['productos'] = DB::query("
                SELECT
                    cd.carrito_detalle_id,
                    cd.product_id,
                    cd.cantidad,
                    cd.precio_unitario,

                    p.name,
                    p.price,

                    (
                        SELECT pi.img
                        FROM pos_product_image pi
                        WHERE pi.product_id = p.product_id
                        ORDER BY pi.product_image_id ASC
                        LIMIT 1
                    ) AS img

                FROM reg_carrito_detalle cd

                INNER JOIN pos_product p
                    ON p.product_id = cd.product_id

                WHERE cd.carrito_id = %i

                ORDER BY cd.carrito_detalle_id ASC
            ", $r['carrito_id']);
        }

        Flight::json([
            'status' => 'ok',
            'data' => $rows
        ]);

    } catch(Exception $e){

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

Flight::route('POST /FVhB/eliminarPedido', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $carrito_id = intval(
        $d['carrito_id'] ?? 0
    );

    if(!$carrito_id){

        Flight::json([
            'status' => 'error',
            'msg' => 'carrito_id requerido'
        ], 400);

        return;
    }

    DB::startTransaction();

    try {

        /* ======================================
           🔍 VALIDAR EXISTENCIA
        ====================================== */
        $carrito = DB::queryFirstRow("

            SELECT
                carrito_id,
                estado

            FROM reg_carrito

            WHERE carrito_id = %i

            LIMIT 1

        ", $carrito_id);

        if(!$carrito){

            DB::rollback();

            Flight::json([
                'status' => 'error',
                'msg' => 'El pedido no existe'
            ], 404);

            return;
        }

        /* ======================================
           🔒 VALIDACIÓN PRO
        ====================================== */
        if($carrito['estado'] == 'transito'){

            DB::rollback();

            Flight::json([
                'status' => 'error',
                'msg' => 'No se puede anular un pedido en tránsito'
            ], 400);

            return;
        }

        /* ======================================
           🔄 CAMBIAR ESTADO
        ====================================== */
        DB::update(
            'reg_carrito',
            [
                'estado' => 'anulado',
                'fecha_modificacion' => date('Y-m-d H:i:s')
            ],
            "carrito_id=%i",
            $carrito_id
        );

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'msg' => 'Pedido anulado correctamente',
            'carrito_id' => $carrito_id
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

Flight::route('POST /QFeC/pedidosDelivery', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $trab_usu_id = intval($d['trab_usu_id'] ?? 0);

    if(!$trab_usu_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'trab_usu_id requerido'
        ], 400);
        return;
    }

    try {

        /* ======================================
           🚚 PEDIDOS EN TRANSITO DEL DELIVERY
        ====================================== */
        $rows = DB::query("
            SELECT
                de.deli_entrega_id,
                de.neg_id,
                de.carrito_id,
                de.trab_usu_id,
                de.direccion,
                de.fecha_salida,
                de.fecha_entrega,
                de.estado AS entrega_estado,
                de.fecha_creacion,

                c.estado AS carrito_estado,
                c.fecha_creacion AS carrito_fecha,

                n.nombre AS neg_nombre,
                n.img_logo AS neg_logo,
                n.puesto AS neg_puesto,

                u.usu_id,
                u.nombres_apellidos,
                u.sobrenombre,
                u.celular,

                (
                    SELECT IFNULL(SUM(cd.cantidad),0)
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = de.carrito_id
                ) AS total_productos,

                (
                    SELECT IFNULL(SUM(cd.cantidad * cd.precio_unitario),0)
                    FROM reg_carrito_detalle cd
                    WHERE cd.carrito_id = de.carrito_id
                ) AS total_pedido

            FROM deli_entrega de

            INNER JOIN reg_carrito c
                ON c.carrito_id = de.carrito_id

            INNER JOIN reg_neg n
                ON n.neg_id = de.neg_id

            INNER JOIN reg_usu u
                ON u.usu_id = c.usu_id

            WHERE de.trab_usu_id = %i
              AND de.estado = 'transito'

            ORDER BY de.deli_entrega_id DESC
        ", $trab_usu_id);

        /* ======================================
           📦 DETALLE PRODUCTOS
        ====================================== */
        foreach($rows as &$r){

            $r['productos'] = DB::query("
                SELECT
                    cd.carrito_detalle_id,
                    cd.product_id,
                    cd.cantidad,
                    cd.precio_unitario,

                    p.name,
                    p.price,

                    (
                        SELECT pi.img
                        FROM pos_product_image pi
                        WHERE pi.product_id = p.product_id
                        ORDER BY pi.product_image_id ASC
                        LIMIT 1
                    ) AS img

                FROM reg_carrito_detalle cd

                INNER JOIN pos_product p
                    ON p.product_id = cd.product_id

                WHERE cd.carrito_id = %i

                ORDER BY cd.carrito_detalle_id ASC
            ", $r['carrito_id']);
        }

        Flight::json([
            'status' => 'ok',
            'data' => $rows
        ]);

    } catch(Exception $e){

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

Flight::route('GET /QFeC/tipo_pago/listar', function(){

    $rows = DB::query("
        SELECT tipo_pago_id, descripcion
        FROM pos_tipo_pago
        ORDER BY orden ASC
    ");

    Flight::json($rows);
});

Flight::route('POST /P22W/obtenerYaplin', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $imagen_url = isset($d['imagen_url'])
        ? trim((string)$d['imagen_url'])
        : null;

    $usu_id = isset($d['usu_id'])
        ? intval($d['usu_id'])
        : null;

    $carrito_id = isset($d['carrito_id'])
        ? intval($d['carrito_id'])
        : null;

    $cliente_id = isset($d['cliente_id'])
        ? intval($d['cliente_id'])
        : null;

    $neg_id = isset($d['neg_id'])
        ? intval($d['neg_id'])
        : null;

    $observacion = isset($d['observacion'])
        ? trim((string)$d['observacion'])
        : null;

    /* ======================================
       VALIDAR
    ====================================== */

    if(empty($imagen_url)){

        Flight::json([

            'status' => 'error',

            'msg' => 'imagen_url requerido'

        ],400);

        return;
    }

    DB::startTransaction();

    try {

        /* ======================================
           ESTADO
        ====================================== */

        $estado = 'PENDIENTE';

        /* ======================================
           INSERTAR
        ====================================== */

        DB::insert('reg_yaplin',[

            'imagen_url'   => $imagen_url,

            'usu_id'       => $usu_id,

            'cliente_id'   => $cliente_id,

            'neg_id'       => $neg_id,

            'carrito_id'   => $carrito_id,

            'estado'       => $estado,

            'observacion'  => $observacion,

            'is_activo'    => 1

        ]);

        $yaplin_id = DB::insertId();

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' => 'Yaplin registrado correctamente',

            'yaplin_id' => $yaplin_id,

            'estado' => $estado

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ],500);

    }

});

Flight::route('POST /ULx3/rechazarPedido', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $carrito_id = intval(
        $d['carrito_id'] ?? 0
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$carrito_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'carrito_id requerido'

        ],400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           VALIDAR DELIVERY
        ====================================== */

        $delivery = DB::queryFirstRow("

            SELECT

                deli_entrega_id,
                estado

            FROM deli_entrega

            WHERE carrito_id = %i

            LIMIT 1

        ", $carrito_id);

        if(!$delivery){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Delivery no encontrado'

            ],404);

            return;
        }

        /* ======================================
           ACTUALIZAR DELIVERY
        ====================================== */

        DB::update(

            'deli_entrega',

            [

                'estado' =>
                    'rechazado',

                'fecha_modificacion' =>
                    $now

            ],

            "carrito_id=%i",

            $carrito_id

        );

        /* ======================================
           ACTUALIZAR CARRITO
        ====================================== */

        DB::update(

            'reg_carrito',

            [

                'estado' =>
                    'rechazado',

                'fecha_modificacion' =>
                    $now

            ],

            "carrito_id=%i",

            $carrito_id

        );

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Pedido rechazado correctamente',

            'carrito_id' =>
                $carrito_id,

            'deli_entrega_id' =>
                $delivery['deli_entrega_id']

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});

Flight::route('POST /YCOr/rechazarPedidoDirecto', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $carrito_id = intval(
        $d['carrito_id'] ?? 0
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$carrito_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'carrito_id requerido'

        ],400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           VALIDAR CARRITO
        ====================================== */

        $carrito = DB::queryFirstRow("

            SELECT

                carrito_id,
                estado,
                tipo_pedido,
                mesa_id

            FROM reg_carrito

            WHERE carrito_id = %i

            LIMIT 1

        ", $carrito_id);

        if(!$carrito){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Carrito no encontrado'

            ],404);

            return;
        }

        /* ======================================
           NUEVO TIPO PEDIDO
        ====================================== */

        $nuevo_tipo_pedido =
            $carrito['tipo_pedido'];

        if(

            strtoupper(
                $carrito['tipo_pedido']
            ) == 'MESA_PEDIDO'

        ){

            $nuevo_tipo_pedido =
                'MESA_ANULADO';

        }

        /* ======================================
           ACTUALIZAR CARRITO
        ====================================== */

        DB::update(

            'reg_carrito',

            [

                'estado' =>
                    'rechazado',

                'tipo_pedido' =>
                    $nuevo_tipo_pedido,

                'fecha_modificacion' =>
                    $now

            ],

            "carrito_id=%i",

            $carrito_id

        );

        /* ======================================
           LIBERAR MESA
        ====================================== */

        if(

            strtoupper(
                $carrito['tipo_pedido']
            ) == 'MESA_PEDIDO'

            &&

            !empty(
                $carrito['mesa_id']
            )

        ){

            DB::update(

                'resto_mesa',

                [

                    'estado' =>
                        'DISPONIBLE'

                ],

                "mesa_id=%i",

                $carrito['mesa_id']

            );

        }

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Pedido rechazado correctamente',

            'carrito_id' =>
                $carrito_id,

            'tipo_pedido' =>
                $nuevo_tipo_pedido

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});

Flight::route('POST /FjeU/clienteListar', function(){

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
       NEG_ID
    ====================================== */

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$neg_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ],400);

        return;
    }

    /* ======================================
       LISTAR CLIENTES
    ====================================== */

    $rows = DB::query("

        SELECT 

            c.cliente_id,

            c.nombres_apellidos AS nombre,

            c.dni,

            c.cod_usu,

            c.ruc,

            c.puesto,

            c.direccion,

            c.celular AS telefono,

            c.email,

            c.distrito,

            c.map_lat,

            c.map_lng

        FROM pos_cliente c

        WHERE 
            (
                c.neg_id = %i
                AND c.is_activo = 1
                AND c.borrado_el IS NULL
            )

            OR c.cliente_id = 1

        ORDER BY 
            (c.cliente_id = 1) DESC,
            c.nombres_apellidos ASC

    ", $neg_id);

    /* ======================================
       POR PAGAR
    ====================================== */

    foreach($rows as &$r){

        $deudas = DB::query("

            SELECT

                p1.por_pagar_id,

                p1.product_order_id,

                p1.monto_total_order,

                p1.monto_pagado,

                p1.monto_restante,

                p1.tipo_pago,

                p1.tipo_movimiento,

                p1.fecha_creacion

            FROM pos_por_pagar p1

            INNER JOIN (

                SELECT

                    product_order_id,

                    MAX(por_pagar_id) AS max_id

                FROM pos_por_pagar

                WHERE cliente_id = %i

                AND neg_id = %i

                AND monto_restante > 0

                AND borrado_el IS NULL

                GROUP BY product_order_id

            ) p2

                ON p2.max_id = p1.por_pagar_id

            ORDER BY p1.por_pagar_id DESC

        ",
            $r['cliente_id'],
            $neg_id
        );

        if(!empty($deudas)){

            $r['por_pagar'] = [

                'total_registros' =>
                    count($deudas),

                'por_pagar_ids' =>
                    array_map(

                        function($x){

                            return intval(
                                $x['por_pagar_id']
                            );

                        },

                        $deudas

                    ),

                'deudas' =>
                    $deudas

            ];

        }

    }

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status' => 'ok',

        'data' => $rows

    ]);

});


Flight::route('POST /BnyQ/ventaDirecta', function(){

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

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $usu_id_vendedor = intval(
        $d['usu_id_vendedor'] ?? 0
    );

    $cliente_id = intval(
        $d['cliente_id'] ?? 1
    );

    $tipo_pago = trim(
        strtoupper(
            $d['tipo_pago'] ?? ''
        )
    );

    $modo_order = trim(
        strtoupper(
            $d['modo_order']
            ?? 'PAGO_DIRECTO'
        )
    );

    $fecha_inicio = !empty($d['fecha_inicio'])
        ? trim($d['fecha_inicio'])
        : null;

    $fecha_fin = !empty($d['fecha_fin'])
        ? trim($d['fecha_fin'])
        : null;

    $yaplin_id = isset($d['yaplin_id'])
        ? intval($d['yaplin_id'])
        : null;

    if($yaplin_id <= 0){
        $yaplin_id = null;
    }

    $items = $d['items'] ?? [];

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$neg_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ],400);

        return;
    }

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$usu_id_vendedor){

        Flight::json([

            'status' => 'error',

            'msg' => 'usu_id_vendedor requerido'

        ],400);

        return;
    }

    if(!$tipo_pago){

        Flight::json([

            'status' => 'error',

            'msg' => 'tipo_pago requerido'

        ],400);

        return;
    }

    if(empty($items)){

        Flight::json([

            'status' => 'error',

            'msg' => 'Debe enviar productos'

        ],400);

        return;
    }

    /* ======================================
       ENUM TIPO PAGO
    ====================================== */

    $tipos_pago_validos = [

        'EFECTIVO',
        'YAPE',
        'PLIN',
        'TRANFERENCIA',
        'CREDITO',
        'POR_PAGAR'

    ];

    if(
        !in_array(
            $tipo_pago,
            $tipos_pago_validos
        )
    ){

        Flight::json([

            'status' => 'error',

            'msg' => 'tipo_pago inválido'

        ],400);

        return;
    }

    /* ======================================
       ENUM MODO ORDER
    ====================================== */

    $modos_validos = [

        'PAGO_DIRECTO',
        'MESA_PEDIDO',
        'MESA_PAGADO',
        'ESTACIONAMIENTO_PEDIDO',
        'ESTACIONAMIENTO_PAGADO'

    ];

    if(
        !in_array(
            $modo_order,
            $modos_validos
        )
    ){

        Flight::json([

            'status' => 'error',

            'msg' => 'modo_order inválido'

        ],400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           NEGOCIO
        ====================================== */

        $negocio = DB::queryFirstRow("

            SELECT
                neg_id,
                nombre

            FROM reg_neg

            WHERE neg_id = %i

            LIMIT 1

        ", $neg_id);

        if(!$negocio){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Negocio no encontrado'

            ],404);

            return;
        }

        /* ======================================
           USUARIO VENDEDOR
        ====================================== */

        $usuario = DB::queryFirstRow("

            SELECT usu_id

            FROM reg_usu

            WHERE usu_id = %i

            LIMIT 1

        ", $usu_id_vendedor);

        if(!$usuario){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Vendedor no encontrado'

            ],404);

            return;
        }

        /* ======================================
           CLIENTE
        ====================================== */

        $cliente = DB::queryFirstRow("

            SELECT
                cliente_id,
                nombres_apellidos

            FROM pos_cliente

            WHERE cliente_id = %i

            LIMIT 1

        ", $cliente_id);

        if(!$cliente){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Cliente no encontrado'

            ],404);

            return;
        }

        /* ======================================
           YAPLIN
        ====================================== */

        if($yaplin_id !== null){

            DB::update(

                'reg_yaplin',

                [

                    'carrito_id' => null,

                    'neg_id' =>
                        $neg_id,

                    'cliente_id' =>
                        $cliente_id,

                    'usu_id' =>
                        $usu_id_vendedor,

                    'estado' =>
                        'PENDIENTE',

                    'fecha_modificacion' =>
                        $now

                ],

                "yaplin_id=%i",

                $yaplin_id

            );

        }

        /* ======================================
           TOTAL
        ====================================== */

        $total = 0;

        foreach($items as $it){

            $total += (

                floatval(
                    $it['precio']
                )

                *

                intval(
                    $it['cantidad']
                )

            );

        }

        /* ======================================
           SERIAL
        ====================================== */

        $serial =
            'VD-'
            . date('YmdHis')
            . '-'
            . rand(100,999);

        /* ======================================
           CREAR ORDEN
        ====================================== */

        DB::insert(

            'pos_product_order',

            [

                'neg_id' =>
                    $neg_id,

                'cliente_id' =>
                    $cliente_id,

                'tipo_pago' =>
                    $tipo_pago,

                'modo_order' =>
                    $modo_order,

                 'usu_id_vendedor' =>
                    $usu_id_vendedor,

                'fecha_inicio' =>
                    $fecha_inicio,

                'fecha_fin' =>
                    $fecha_fin,

                'total_fees' =>
                    0,

                'tax' =>
                    0,

                'serial' =>
                    $serial,

                'fecha_creacion' =>
                    $now,

                'fecha_modificacion' =>
                    $now,

                'borrado_el' => null

            ]

        );

        $product_order_id =
            DB::insertId();

        /* ======================================
           DETALLES
        ====================================== */

        foreach($items as $it){

            $product_id = intval(
                $it['product_id'] ?? 0
            );

            $amount = intval(
                $it['cantidad'] ?? 0
            );

            $price = floatval(
                $it['precio'] ?? 0
            );

            if(!$product_id || !$amount){
                continue;
            }

            /* ======================================
               PRODUCTO
            ====================================== */

            $producto = DB::queryFirstRow("

                SELECT
                    product_id,
                    name

                FROM pos_product

                WHERE product_id = %i

                LIMIT 1

            ", $product_id);

            if(!$producto){

                DB::rollback();

                Flight::json([

                    'status' => 'error',

                    'msg' => 'Producto no encontrado'

                ],404);

                return;
            }

            /* ======================================
               DETALLE
            ====================================== */

            DB::insert(

                'pos_product_order_detail',

                [

                    'product_order_id' =>
                        $product_order_id,

                    'product_id' =>
                        $product_id,

                    'product_name' =>
                        $producto['name'],

                    'amount' =>
                        $amount,

                    'price_item' =>
                        $price,

                    'fecha_creacion' =>
                        $now,

                    'fecha_modificacion' =>
                        $now,

                    'borrado_el' => null

                ]

            );

            /* ======================================
               STOCK ACTUAL
            ====================================== */

            $stock_actual = DB::queryFirstField("

                SELECT stock_actual

                FROM pos_inventario

                WHERE product_id = %i

                LIMIT 1

            ", $product_id);

            $stock_actual =
                intval($stock_actual);

            $nuevo_stock = (
                $stock_actual
                -
                $amount
            );

            /* ======================================
               INVENTARIO MOVIMIENTO
            ====================================== */

            DB::insert(

                'pos_inventario_movimiento',

                [

                    'product_id' =>
                        $product_id,

                    'tipo' =>
                        'SALIDA',

                    'origen' =>
                        'VENTA',

                    'cantidad' =>
                        $amount,

                    'precio_unitario' =>
                        $price,

                    'fecha' =>
                        $now,

                    'stock_resultante' =>
                        $nuevo_stock,

                    'neg_id' =>
                        $neg_id

                ]

            );

            /* ======================================
               DESCONTAR STOCK
            ====================================== */

            DB::query("

                UPDATE pos_inventario

                SET stock_actual =
                    stock_actual - %i

                WHERE product_id = %i

            ",
                $amount,
                $product_id
            );

        }

         /* ======================================
            TOTAL FINAL
         ====================================== */

        DB::update(

            'pos_product_order',

            [

                'total_fees' =>
                    $total

            ],

            "product_order_id=%i",

            $product_order_id

        );

        /* ======================================
           POR PAGAR
        ====================================== */

        if(
            $tipo_pago
            ==
            'POR_PAGAR'
        ){

            $resp_deuda = deuda_movimiento(

                $product_order_id,

                $cliente_id,

                $neg_id,

                'DEUDA_INICIAL',

                0,

                'POR_PAGAR',

                'Venta creada como deuda'

            );

            if(
                empty($resp_deuda['ok'])
            ){

                DB::rollback();

                Flight::json([

                    'status' => 'error',

                    'msg' =>
                        'Error creando deuda: '
                        .
                        $resp_deuda['msg']

                ],500);

                return;

            }

        }

        /* ======================================
           YAPLIN
        ====================================== */

        if($yaplin_id !== null){

            DB::update(

                'reg_yaplin',

                [

                    'carrito_id' => null,

                    'neg_id' =>
                        $neg_id,

                    'cliente_id' =>
                        $cliente_id,

                    'usu_id' =>
                        $usu_id,

                    'estado' =>
                        'PENDIENTE',

                    'fecha_modificacion' =>
                        $now

                ],

                "yaplin_id=%i",

                $yaplin_id

            );

        }

        DB::commit();

        /* ======================================
           PDF
        ====================================== */

        $url_pdf = imprimir_recibo_venta_directa(

            $product_order_id

        );

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Venta directa registrada correctamente',

            'product_order_id' =>
                $product_order_id,

            'tipo_pago' =>
                $tipo_pago,

            'yaplin_id' =>
                $yaplin_id,

            'total' =>
                $total,

            'url_pdf' =>
                $url_pdf

        ]);
    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});

Flight::route('POST /HOGO/listaVentas', function(){

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
       NEG_ID
    ====================================== */

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $fecha_inicio = trim(
        $d['fecha_inicio'] ?? ''
    );

    $fecha_termino = trim(
        $d['fecha_termino'] ?? ''
    );

    if($neg_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ], 400);

        return;
    }

    /* ======================================
       NEGOCIO
    ====================================== */

    $negocio = DB::queryFirstRow("

        SELECT

            neg_id,
            nombre

        FROM reg_neg

        WHERE neg_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    if(!$negocio){

        Flight::json([

            'status' => 'error',

            'msg' => 'Negocio no encontrado'

        ], 404);

        return;
    }

    /* ======================================
       ORDENES
    ====================================== */

    $where_fecha = '';

    $params = [
        $neg_id
    ];

    if(
        $fecha_inicio !== ''
        &&
        $fecha_termino !== ''
    ){

        $where_fecha = "

            AND DATE(o.fecha_creacion)
            BETWEEN %s AND %s

        ";

        $params[] = $fecha_inicio;
        $params[] = $fecha_termino;
    }

    $sql = "

            SELECT

                o.product_order_id,

                o.usu_id_vendedor,

                o.total_fees,

                o.tax,

                o.serial,

                o.fecha_creacion,

                o.fecha_modificacion,

                o.cliente_id,

                o.tipo_pago,

                o.modo_order,

                o.fecha_inicio,

                o.fecha_fin,

                o.neg_id,

                c.nombres_apellidos
                    AS cliente_nombre,

                c.dni
                    AS cliente_dni,

                c.celular
                    AS cliente_celular,

                u.nombres_apellidos
                    AS usuario_nombre

            FROM pos_product_order o

            LEFT JOIN pos_cliente c
                   ON c.cliente_id = o.cliente_id

            LEFT JOIN reg_usu u
                   ON u.usu_id = o.usu_id_vendedor

            WHERE o.neg_id = %i

            AND o.borrado_el IS NULL

            {$where_fecha}

            ORDER BY
                o.product_order_id DESC

        ";

        $ventas = DB::query(
            $sql,
            ...$params
        );

    /* ======================================
       DETALLES
    ====================================== */

    foreach($ventas as &$v){

        $v['product_order_id'] = intval(
            $v['product_order_id']
        );

        $v['usu_id_vendedor'] = intval(
            $v['usu_id_vendedor']
        );

        $v['cliente_id'] = intval(
            $v['cliente_id']
        );

        $v['neg_id'] = intval(
            $v['neg_id']
        );

        $v['total_fees'] = floatval(
            $v['total_fees']
        );

        $v['tax'] = floatval(
            $v['tax']
        );

        /* ======================================
           DETALLE PRODUCTOS
        ====================================== */

        $detalles = DB::query("

            SELECT

                d.product_order_detail_id,

                d.product_order_id,

                d.product_id,

                d.product_name,

                d.amount,

                d.price_item,

                d.fecha_creacion,

                d.fecha_modificacion,

                p.cod_producto,

                p.tipo_producto,

                p.marca_des,

                p.price,

                p.description,

                p.is_visible,

                (
                    d.amount
                    *
                    d.price_item
                ) AS total_item

            FROM pos_product_order_detail d

            LEFT JOIN pos_product p
                   ON p.product_id = d.product_id

            WHERE
                d.product_order_id = %i

            AND d.borrado_el IS NULL

            ORDER BY
                d.product_order_detail_id ASC

        ",
            $v['product_order_id']
        );

        foreach($detalles as &$det){

            $det['product_order_detail_id'] =
                intval(
                    $det['product_order_detail_id']
                );

            $det['product_order_id'] =
                intval(
                    $det['product_order_id']
                );

            $det['product_id'] =
                intval(
                    $det['product_id']
                );

            $det['amount'] =
                intval(
                    $det['amount']
                );

            $det['price_item'] =
                floatval(
                    $det['price_item']
                );

            $det['price'] =
                floatval(
                    $det['price']
                );

            $det['total_item'] =
                floatval(
                    $det['total_item']
                );

            $det['is_visible'] =
                intval(
                    $det['is_visible']
                );

            /* ======================================
               IMAGENES
            ====================================== */

            $imagenes = DB::query("

                SELECT

                    product_image_id,

                    img,

                    orden,

                    is_visible

                FROM pos_product_image

                WHERE product_id = %i

                AND borrado_el IS NULL

                ORDER BY orden ASC

            ",
                $det['product_id']
            );

            foreach($imagenes as &$img){

                $img['product_image_id'] =
                    intval(
                        $img['product_image_id']
                    );

                $img['orden'] =
                    intval(
                        $img['orden']
                    );

                $img['is_visible'] =
                    intval(
                        $img['is_visible']
                    );

            }

            $det['imagenes'] =
                $imagenes;

        }

        $v['detalles'] = $detalles;

        /* ======================================
           POR PAGAR
        ====================================== */

        $deudas = DB::query("

            SELECT

                por_pagar_id,

                product_order_id,

                cliente_id,

                tipo_movimiento,

                monto_total_order,

                monto_pagado,

                monto_restante,

                tipo_pago,

                descripcion,

                fecha_creacion

            FROM pos_por_pagar

            WHERE
                product_order_id = %i

            AND borrado_el IS NULL

            ORDER BY
                por_pagar_id ASC

        ",
            $v['product_order_id']
        );

        foreach($deudas as &$de){

            $de['por_pagar_id'] =
                intval(
                    $de['por_pagar_id']
                );

            $de['product_order_id'] =
                intval(
                    $de['product_order_id']
                );

            $de['cliente_id'] =
                intval(
                    $de['cliente_id']
                );

            $de['monto_total_order'] =
                floatval(
                    $de['monto_total_order']
                );

            $de['monto_pagado'] =
                floatval(
                    $de['monto_pagado']
                );

            $de['monto_restante'] =
                floatval(
                    $de['monto_restante']
                );

        }

        if(!empty($deudas)){

            $ultimo_mov = end($deudas);

            $v['por_pagar'] = [

                'tiene_deuda' =>

                    floatval(
                        $ultimo_mov['monto_restante']
                    ) > 0,

                'monto_restante' =>

                    floatval(
                        $ultimo_mov['monto_restante']
                    ),

                'monto_total_order' =>

                    floatval(
                        $ultimo_mov['monto_total_order']
                    ),

                'movimientos' =>
                    $deudas

            ];

        } else {

            $v['por_pagar'] = [

                'tiene_deuda' =>
                    false,

                'monto_restante' =>
                    0,

                'monto_total_order' =>
                    0,

                'movimientos' => []

            ];

        }

    }

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status' => 'ok',

        'negocio' => $negocio,

        'ventas' => $ventas

    ]);

});

function deuda_movimiento(

    $product_order_id,
    $cliente_id,
    $neg_id,

    $tipo_movimiento,

    $monto_pagado,

    $tipo_pago = 'EFECTIVO',

    $descripcion = ''

){

    /* =====================================
       LIMPIAR
    ====================================== */

    $product_order_id = intval(
        $product_order_id
    );

    $cliente_id = intval(
        $cliente_id
    );

    $neg_id = intval(
        $neg_id
    );

    $tipo_movimiento = trim(
        strtoupper(
            $tipo_movimiento
        )
    );

    $monto_pagado = floatval(
        $monto_pagado
    );

    $tipo_pago = trim(
        strtoupper(
            $tipo_pago
        )
    );

    $descripcion = trim(
        $descripcion
    );

    /* =====================================
       VALIDAR IDS
    ====================================== */

    if(
        !$product_order_id
        ||
        !$cliente_id
        ||
        !$neg_id
    ){

        return [

            'ok' => false,

            'msg' => 'IDs inválidos'

        ];

    }

    /* =====================================
       TIPOS
    ====================================== */

    $tipos_validos = [

        'DEUDA_INICIAL',
        'PAGO',
        'AJUSTE'

    ];

    if(
        !in_array(
            $tipo_movimiento,
            $tipos_validos
        )
    ){

        return [

            'ok' => false,

            'msg' =>
                'tipo_movimiento inválido'

        ];

    }

    /* =====================================
       ORDEN
    ====================================== */

    $order = DB::queryFirstRow("

        SELECT

            product_order_id,
            total_fees

        FROM pos_product_order

        WHERE product_order_id = %i
        AND borrado_el IS NULL

        LIMIT 1

    ", $product_order_id);

    if(!$order){

        return [

            'ok' => false,

            'msg' =>
                'Orden no encontrada'

        ];

    }

    $monto_total_order = floatval(
        $order['total_fees']
    );

    /* =====================================
       RESTANTE ACTUAL
    ====================================== */

    $ultimo = DB::queryFirstRow("

        SELECT

            monto_restante

        FROM pos_por_pagar

        WHERE product_order_id = %i
        AND borrado_el IS NULL

        ORDER BY por_pagar_id DESC

        LIMIT 1

    ", $product_order_id);

    $restante_actual =
        $ultimo
        ?
        floatval(
            $ultimo['monto_restante']
        )
        :
        $monto_total_order;

    /* =====================================
       CALCULAR RESTANTE
    ====================================== */

    if(
        $tipo_movimiento
        ==
        'DEUDA_INICIAL'
    ){

        $monto_pagado = 0;

        $monto_restante =
            $monto_total_order;

    }else{

        $monto_restante =
            $restante_actual
            -
            $monto_pagado;

        if($monto_restante < 0){
            $monto_restante = 0;
        }

    }

    /* =====================================
       INSERT
    ====================================== */

    DB::insert(

        'pos_por_pagar',

        [

            'product_order_id' =>
                $product_order_id,

            'cliente_id' =>
                $cliente_id,

            'neg_id' =>
                $neg_id,

            'tipo_movimiento' =>
                $tipo_movimiento,

            'monto_total_order' =>
                $monto_total_order,

            'monto_pagado' =>
                $monto_pagado,

            'monto_restante' =>
                $monto_restante,

            'tipo_pago' =>
                $tipo_pago,

            'descripcion' =>
                $descripcion,

            'fecha_creacion' =>
                date(
                    'Y-m-d H:i:s'
                ),

            'borrado_el' => null

        ]

    );

    $por_pagar_id = DB::insertId();

    /* =====================================
       RESPONSE
    ====================================== */

    return [

        'ok' => true,

        'por_pagar_id' =>
            $por_pagar_id,

        'monto_total_order' =>
            $monto_total_order,

        'monto_pagado' =>
            $monto_pagado,

        'monto_restante' =>
            $monto_restante

    ];

}

Flight::route('POST /UHUD/realizarPago', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* =====================================
       CAMPOS
    ====================================== */

    $product_order_id = intval(
        $d['product_order_id'] ?? 0
    );

    $monto_pagado = floatval(
        $d['monto_pagado'] ?? 0
    );

    $tipo_pago = trim(
        strtoupper(
            $d['tipo_pago'] ?? 'EFECTIVO'
        )
    );

    $descripcion = trim(
        $d['descripcion'] ?? ''
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* =====================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* =====================================
       VALIDAR
    ====================================== */

    if(!$product_order_id){

        Flight::json([

            'ok' => false,

            'msg' =>
                'product_order_id requerido'

        ], 400);

        return;
    }

    if($monto_pagado <= 0){

        Flight::json([

            'ok' => false,

            'msg' =>
                'monto_pagado inválido'

        ], 400);

        return;
    }

    try {

        /* =====================================
           ORDEN
        ====================================== */

        $order = DB::queryFirstRow("

            SELECT

                product_order_id,
                cliente_id,
                neg_id

            FROM pos_product_order

            WHERE product_order_id = %i
            AND borrado_el IS NULL

            LIMIT 1

        ", $product_order_id);

        if(!$order){

            Flight::json([

                'ok' => false,

                'msg' =>
                    'Orden no encontrada'

            ], 404);

            return;
        }

        /* =====================================
           IDS
        ====================================== */

        $cliente_id = intval(
            $order['cliente_id']
        );

        $neg_id = intval(
            $order['neg_id']
        );

        /* =====================================
           TRANSACCION
        ====================================== */

        DB::startTransaction();

        /* =====================================
           REGISTRAR PAGO
        ====================================== */

        $movimiento = deuda_movimiento(

            $product_order_id,

            $cliente_id,

            $neg_id,

            'PAGO',

            $monto_pagado,

            $tipo_pago,

            $descripcion

        );

        if(!$movimiento['ok']){

            DB::rollback();

            Flight::json([

                'ok' => false,

                'msg' =>
                    $movimiento['msg']

            ], 400);

            return;
        }

        DB::commit();

        /* =====================================
           RESPONSE
        ====================================== */

        Flight::json([

            'ok' => true,

            'msg' =>
                'Pago registrado correctamente',

            'data' => [

                'por_pagar_id' =>

                    $movimiento['por_pagar_id'],

                'monto_total_order' =>

                    $movimiento['monto_total_order'],

                'monto_pagado' =>

                    $movimiento['monto_pagado'],

                'monto_restante' =>

                    $movimiento['monto_restante']

            ]

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'ok' => false,

            'msg' => $e->getMessage()

        ], 500);

    }

});

Flight::route('POST /BAJc/mesasNegocio', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$neg_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ],400);

        return;
    }

    /* ======================================
       MESAS
    ====================================== */

    $rows = DB::query("

        SELECT

            mesa_id,

            nombre,

            estado,

            neg_id

        FROM resto_mesa

        WHERE neg_id = %i

        AND borrado_el IS NULL

        ORDER BY nombre ASC

    ", $neg_id);

    /* ======================================
       NORMALIZAR
    ====================================== */

    foreach($rows as &$r){

        $r['mesa_id'] = intval(
            $r['mesa_id']
        );

        $r['neg_id'] = intval(
            $r['neg_id']
        );

    }

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status' => 'ok',

        'neg_id' => $neg_id,

        'data' => $rows

    ]);

});

Flight::route('POST /b3by/atenderMesita', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $usu_id_vendedor = intval(
        $d['usu_id_vendedor'] ?? 0
    );

    $mesa_id = intval(
        $d['mesa_id'] ?? 0
    );

    $productos = $d['productos'] ?? [];

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$neg_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ],400);

        return;
    }

    if(!$usu_id_vendedor){

        Flight::json([

            'status' => 'error',

            'msg' => 'usu_id_vendedor requerido'

        ],400);

        return;
    }

    if(!$mesa_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'mesa_id requerido'

        ],400);

        return;
    }

    if(
        !is_array($productos)
        ||
        empty($productos)
    ){

        Flight::json([

            'status' => 'error',

            'msg' => 'productos requerido'

        ],400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           VALIDAR NEGOCIO
        ====================================== */

        $negocio = DB::queryFirstRow("

            SELECT

                neg_id,
                nombre

            FROM reg_neg

            WHERE neg_id = %i

            AND is_activo = 1

            AND borrado_el IS NULL

            LIMIT 1

        ", $neg_id);

        if(!$negocio){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Negocio no encontrado'

            ],404);

            return;
        }

        /* ======================================
           VALIDAR VENDEDOR
        ====================================== */

        $vendedor = DB::queryFirstRow("

            SELECT

                usu_id,
                nombres_apellidos

            FROM reg_usu

            WHERE usu_id = %i

            AND borrado_el IS NULL

            LIMIT 1

        ", $usu_id_vendedor);

        if(!$vendedor){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Vendedor no encontrado'

            ],404);

            return;
        }

        /* ======================================
           VALIDAR MESA
        ====================================== */

        $mesa = DB::queryFirstRow("

            SELECT

                mesa_id,
                nombre,
                estado

            FROM resto_mesa

            WHERE mesa_id = %i

            AND neg_id = %i

            AND borrado_el IS NULL

            LIMIT 1

        ",
            $mesa_id,
            $neg_id
        );

        if(!$mesa){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Mesa no encontrada'

            ],404);

            return;
        }

        /* ======================================
           CLIENTE PUBLICO GENERAL
        ====================================== */

        $cliente_id = DB::queryFirstField("

            SELECT

                cliente_id

            FROM pos_cliente

            WHERE neg_id = %i

            AND nombres_apellidos = 'PUBLICO_GENERAL'

            AND borrado_el IS NULL

            LIMIT 1

        ", $neg_id);

        if(!$cliente_id){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'PUBLICO_GENERAL no existe'

            ],404);

            return;
        }

        /* ======================================
           BUSCAR CARRITO ACTIVO MESA
        ====================================== */

        $carrito = DB::queryFirstRow("

            SELECT

                carrito_id

            FROM reg_carrito

            WHERE mesa_id = %i

            AND neg_id = %i

            AND tipo_pedido = 'MESA_PEDIDO'

            AND estado IN (
                'enviado',
                'transito'
            )

            ORDER BY carrito_id DESC

            LIMIT 1

        ",
            $mesa_id,
            $neg_id
        );

        /* ======================================
           CREAR CARRITO
        ====================================== */

        if(!$carrito){

            DB::insert(

                'reg_carrito',

                [

                    'usu_id' =>
                        $usu_id_vendedor,

                    'neg_id' =>
                        $neg_id,

                    'estado' =>
                        'enviado',

                    'fecha_entrega' =>
                        null,

                    'fecha_creacion' =>
                        $now,

                    'fecha_modificacion' =>
                        $now,

                    'cliente_id' =>
                        $cliente_id,

                    'tipo_pedido' =>
                        'MESA_PEDIDO',

                    'usu_id_vendedor' =>
                        $usu_id_vendedor,

                    'mesa_id' =>
                        $mesa_id

                ]

            );

            $carrito_id = DB::insertId();

        } else {

            $carrito_id = intval(
                $carrito['carrito_id']
            );

            DB::update(

                'reg_carrito',

                [

                    'fecha_modificacion' =>
                        $now

                ],

                "carrito_id=%i",

                $carrito_id

            );

        }

        /* ======================================
           DETALLES
        ====================================== */

        foreach($productos as $p){

            $product_id = intval(
                $p['product_id'] ?? 0
            );

            $cantidad = intval(
                $p['cantidad'] ?? 0
            );

            $precio = floatval(
                $p['precio'] ?? 0
            );

            if(
                !$product_id
                ||
                $cantidad <= 0
            ){
                continue;
            }

            /* ======================================
               VALIDAR PRODUCTO
            ====================================== */

            $producto = DB::queryFirstRow("

                SELECT

                    product_id,
                    name

                FROM pos_product

                WHERE product_id = %i

                AND neg_id = %i

                AND borrado_el IS NULL

                LIMIT 1

            ",
                $product_id,
                $neg_id
            );

            if(!$producto){

                DB::rollback();

                Flight::json([

                    'status' => 'error',

                    'msg' =>
                        'Producto inválido: '
                        .
                        $product_id

                ],404);

                return;
            }

            /* ======================================
               EXISTE EN CARRITO
            ====================================== */

            $detalle = DB::queryFirstRow("

                SELECT

                    carrito_detalle_id,
                    cantidad

                FROM reg_carrito_detalle

                WHERE carrito_id = %i

                AND product_id = %i

                LIMIT 1

            ",
                $carrito_id,
                $product_id
            );

            if($detalle){

                DB::update(

                    'reg_carrito_detalle',

                    [

                        'cantidad' =>

                            intval(
                                $detalle['cantidad']
                            )

                            +

                            $cantidad,

                        'precio_unitario' =>
                            $precio,

                        'fecha_modificacion' =>
                            $now

                    ],

                    "carrito_detalle_id=%i",

                    $detalle['carrito_detalle_id']

                );

            } else {

                DB::insert(

                    'reg_carrito_detalle',

                    [

                        'carrito_id' =>
                            $carrito_id,

                        'product_id' =>
                            $product_id,

                        'cantidad' =>
                            $cantidad,

                        'precio_unitario' =>
                            $precio,

                        'fecha_creacion' =>
                            $now,

                        'fecha_modificacion' =>
                            $now

                    ]

                );

            }

        }

        /* ======================================
           MESA OCUPADA
        ====================================== */

        DB::update(

            'resto_mesa',

            [

                'estado' =>
                    'OCUPADA'

            ],

            "mesa_id=%i",

            $mesa_id

        );

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Mesita atendida correctamente',

            'carrito_id' =>
                $carrito_id,

            'mesa_id' =>
                $mesa_id,

            'mesa_estado' =>
                'OCUPADA'

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});


Flight::route('POST /b3by/miMesita', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    /* ======================================
       CAMPOS
    ====================================== */

    $mesa_id = intval(
        $d['mesa_id'] ?? 0
    );

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $xin = trim(
        $d['xin'] ?? ''
    );

    $yuan = trim(
        $d['yuan'] ?? ''
    );

    /* ======================================
       FIRMA
    ====================================== */

    firma(
        $xin,
        $yuan
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$mesa_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'mesa_id requerido'

        ],400);

        return;
    }

    if(!$neg_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ],400);

        return;
    }

    try {

        /* ======================================
           MESA
        ====================================== */

        $mesa = DB::queryFirstRow("

            SELECT

                mesa_id,
                nombre,
                estado,
                neg_id

            FROM resto_mesa

            WHERE mesa_id = %i

            AND neg_id = %i

            AND borrado_el IS NULL

            LIMIT 1

        ",
            $mesa_id,
            $neg_id
        );

        if(!$mesa){

            Flight::json([

                'status' => 'error',

                'msg' => 'Mesa no encontrada'

            ],404);

            return;
        }

        /* ======================================
           CARRITO ACTIVO MESA
        ====================================== */

        $carrito = DB::queryFirstRow("

            SELECT

                c.carrito_id,
                c.usu_id,
                c.neg_id,
                c.estado,
                c.fecha_entrega,
                c.fecha_creacion,
                c.fecha_modificacion,
                c.cliente_id,
                c.tipo_pedido,
                c.usu_id_vendedor,
                c.mesa_id,

                u.nombres_apellidos
                    AS vendedor_nombre,

                m.nombre
                    AS mesa_nombre

            FROM reg_carrito c

            LEFT JOIN reg_usu u
                ON u.usu_id =
                   c.usu_id_vendedor

            LEFT JOIN resto_mesa m
                ON m.mesa_id =
                   c.mesa_id

            WHERE c.mesa_id = %i

            AND c.neg_id = %i

            AND c.estado IN (
                'enviado',
                'transito'
            )

            ORDER BY c.carrito_id DESC

            LIMIT 1

        ",
            $mesa_id,
            $neg_id
        );

        if(!$carrito){

            Flight::json([

                'status' => 'ok',

                'mesa' => $mesa,

                'carrito' => null,

                'detalles' => []

            ]);

            return;
        }

        /* ======================================
           DETALLES
        ====================================== */

        $detalles = DB::query("

            SELECT

                d.carrito_detalle_id,

                d.carrito_id,

                d.product_id,

                d.cantidad,

                d.precio_unitario,

                d.fecha_creacion,

                d.fecha_modificacion,

                p.name,

                p.description,

                p.price,

                (

                    SELECT pi.img

                    FROM pos_product_image pi

                    WHERE pi.product_id =
                          p.product_id

                    AND pi.borrado_el IS NULL

                    ORDER BY pi.orden ASC

                    LIMIT 1

                ) AS img

            FROM reg_carrito_detalle d

            LEFT JOIN pos_product p
                ON p.product_id =
                   d.product_id

            WHERE d.carrito_id = %i

            ORDER BY
                d.carrito_detalle_id ASC

        ",
            $carrito['carrito_id']
        );

        /* ======================================
           NORMALIZAR
        ====================================== */

        foreach($detalles as &$x){

            $x['carrito_detalle_id'] =
                intval(
                    $x['carrito_detalle_id']
                );

            $x['carrito_id'] =
                intval(
                    $x['carrito_id']
                );

            $x['product_id'] =
                intval(
                    $x['product_id']
                );

            $x['cantidad'] =
                intval(
                    $x['cantidad']
                );

            $x['precio_unitario'] =
                floatval(
                    $x['precio_unitario']
                );

            $x['price'] =
                floatval(
                    $x['price']
                );

            if(empty($x['img'])){

                $x['img'] =
                    'https://picsum.photos/300?random='
                    .
                    $x['product_id'];

            }

        }

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'status' => 'ok',

            'mesa' => $mesa,

            'carrito' => $carrito,

            'detalles' => $detalles

        ]);

    } catch(Exception $e){

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage()

        ],500);

    }

});

Flight::route('POST /YCTK/registrarVenta', function(){

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

    $carrito_id = intval(
        $d['carrito_id'] ?? 0
    );

    $tipo_pago = trim(
        strtoupper(
            $d['tipo_pago'] ?? ''
        )
    );

    $usu_id_vendedor = intval(
        $d['usu_id_vendedor'] ?? 0
    );

    $modo_order = trim(
        strtoupper(
            $d['modo_order']
            ?? 'PAGO_DIRECTO'
        )
    );

    $fecha_inicio = !empty($d['fecha_inicio'])
        ? $d['fecha_inicio']
        : null;

    $fecha_fin = !empty($d['fecha_fin'])
        ? $d['fecha_fin']
        : null;

    $yaplin_id = isset($d['yaplin_id'])
        ? intval($d['yaplin_id'])
        : null;

    if($yaplin_id <= 0){
        $yaplin_id = null;
    }

    /* ======================================
       VALIDAR
    ====================================== */

    if(!$carrito_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'carrito_id requerido'

        ], 400);

        return;
    }

    if(!$tipo_pago){

        Flight::json([

            'status' => 'error',

            'msg' => 'tipo_pago requerido'

        ], 400);

        return;
    }

    if(!$usu_id_vendedor){

        Flight::json([

            'status' => 'error',

            'msg' => 'usu_id_vendedor requerido'

        ], 400);

        return;
    }

    /* ======================================
       VALIDAR ENUM TIPO PAGO
    ====================================== */

    $tipos_pago_validos = [

        'EFECTIVO',
        'YAPE',
        'PLIN',
        'TRANFERENCIA',
        'POR_PAGAR'

    ];

    if(
        !in_array(
            $tipo_pago,
            $tipos_pago_validos
        )
    ){

        Flight::json([

            'status' => 'error',

            'msg' => 'tipo_pago inválido'

        ], 400);

        return;
    }

    /* ======================================
       VALIDAR ENUM MODO ORDER
    ====================================== */

    $modos_validos = [

        'PAGO_DIRECTO',
        'MESA_PEDIDO',
        'MESA_PAGADO',
        'ESTACIONAMIENTO_PEDIDO',
        'ESTACIONAMIENTO_PAGADO'

    ];

    if(
        !in_array(
            $modo_order,
            $modos_validos
        )
    ){

        Flight::json([

            'status' => 'error',

            'msg' => 'modo_order inválido'

        ], 400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           VALIDAR VENDEDOR
        ====================================== */

        $vendedor = DB::queryFirstRow("

            SELECT usu_id

            FROM reg_usu

            WHERE usu_id = %i

            LIMIT 1

        ", $usu_id_vendedor);

        if(!$vendedor){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Vendedor no encontrado'

            ], 404);

            return;
        }

        /* ======================================
           CARRITO
        ====================================== */

        $carrito = DB::queryFirstRow("

            SELECT

                c.carrito_id,
                c.usu_id,
                c.neg_id,
                c.estado,
                c.fecha_entrega,
                c.cliente_id,
                c.tipo_pedido,
                c.mesa_id,

                u.nombres_apellidos,

                n.nombre AS negocio

            FROM reg_carrito c

            INNER JOIN reg_usu u
                ON u.usu_id = c.usu_id

            INNER JOIN reg_neg n
                ON n.neg_id = c.neg_id

            WHERE c.carrito_id = %i

            LIMIT 1

        ", $carrito_id);

        if(!$carrito){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Carrito no encontrado'

            ], 404);

            return;
        }

        /* ======================================
           VALIDAR ESTADO
        ====================================== */

        $estados_validos = [

            'transito',
            'enviado'

        ];

        if(
            !in_array(
                $carrito['estado'],
                $estados_validos
            )
        ){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'El carrito ya fue procesado'

            ], 400);

            return;
        }

        /* ======================================
           ITEMS
        ====================================== */

        $items = DB::query("

            SELECT

                cd.carrito_detalle_id,
                cd.product_id,
                cd.cantidad,
                cd.precio_unitario,

                p.name

            FROM reg_carrito_detalle cd

            INNER JOIN pos_product p
                ON p.product_id = cd.product_id

            WHERE cd.carrito_id = %i

            ORDER BY cd.carrito_detalle_id ASC

        ", $carrito_id);

        if(empty($items)){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'El carrito no tiene productos'

            ], 400);

            return;
        }

        /* ======================================
           TOTAL
        ====================================== */

        $total = 0;

        foreach($items as $it){

            $total += (
                floatval(
                    $it['precio_unitario']
                )
                *
                intval(
                    $it['cantidad']
                )
            );

        }

        /* ======================================
           SERIAL
        ====================================== */

        $serial =
            'VT-'
            . date('YmdHis')
            . '-'
            . rand(100,999);

        /* ======================================
           ORDEN
        ====================================== */

        DB::insert(
            'pos_product_order',
            [

                'usu_id_vendedor' =>
                    $usu_id_vendedor,

                'total_fees' =>
                    $total,

                'tax' => 0,

                'serial' =>
                    $serial,

                'fecha_creacion' =>
                    $now,

                'fecha_modificacion' =>
                    $now,

                'cliente_id' =>
                    $carrito['cliente_id'],

                'tipo_pago' =>
                    $tipo_pago,

                'modo_order' =>
                    $modo_order,

                'fecha_inicio' =>
                    $fecha_inicio,

                'fecha_fin' =>
                    $fecha_fin,

                'neg_id' =>
                    $carrito['neg_id'],

                'borrado_el' => null

            ]
        );

        $product_order_id =
            DB::insertId();

        /* ======================================
           DETALLES
        ====================================== */

        foreach($items as $it){

            $product_id = intval(
                $it['product_id']
            );

            $cantidad = intval(
                $it['cantidad']
            );

            $precio = floatval(
                $it['precio_unitario']
            );

            DB::insert(
                'pos_product_order_detail',
                [

                    'product_order_id' =>
                        $product_order_id,

                    'product_id' =>
                        $product_id,

                    'product_name' =>
                        $it['name'],

                    'amount' =>
                        $cantidad,

                    'price_item' =>
                        $precio,

                    'fecha_creacion' =>
                        $now,

                    'fecha_modificacion' =>
                        $now,

                    'borrado_el' => null

                ]
            );

            $stock_actual =
                intval(
                    DB::queryFirstField("

                        SELECT stock_actual

                        FROM pos_inventario

                        WHERE product_id = %i

                        LIMIT 1

                    ", $product_id)
                );

            $nuevo_stock =
                $stock_actual
                -
                $cantidad;

            DB::insert(
                'pos_inventario_movimiento',
                [

                    'product_id' =>
                        $product_id,

                    'tipo' =>
                        'SALIDA',

                    'origen' =>
                        'VENTA',

                    'cantidad' =>
                        $cantidad,

                    'precio_unitario' =>
                        $precio,

                    'fecha' =>
                        $now,

                    'stock_resultante' =>
                        $nuevo_stock,

                    'neg_id' =>
                        $carrito['neg_id']

                ]
            );

            DB::query("

                UPDATE pos_inventario

                SET stock_actual =
                    stock_actual - %i

                WHERE product_id = %i

            ",
                $cantidad,
                $product_id
            );

        }

        /* ======================================
           POR PAGAR
        ====================================== */

        if(
            $tipo_pago == 'POR_PAGAR'
        ){

            $resp_deuda = deuda_movimiento(

                $product_order_id,

                $carrito['cliente_id'],

                $carrito['neg_id'],

                'DEUDA_INICIAL',

                0,

                'POR_PAGAR',

                'Venta creada como deuda'

            );

            if(
                empty($resp_deuda['ok'])
            ){

                DB::rollback();

                Flight::json([

                    'status' => 'error',

                    'msg' =>
                        'Error creando deuda: '
                        .
                        $resp_deuda['msg']

                ],500);

                return;

            }

        }

        /* ======================================
           CARRITO
        ====================================== */

        $nuevo_tipo_pedido =
            $carrito['tipo_pedido'];

        if(
            strtoupper(
                $carrito['tipo_pedido']
            ) == 'MESA_PEDIDO'
        ){

            $nuevo_tipo_pedido =
                'MESA_PAGADO';

        }

        DB::update(
            'reg_carrito',
            [

                'estado' =>
                    'comprado',

                'tipo_pedido' =>
                    $nuevo_tipo_pedido,

                'fecha_modificacion' =>
                    $now

            ],
            "carrito_id=%i",
            $carrito_id
        );

        /* ======================================
           LIBERAR MESA
        ====================================== */

        if(
            strtoupper(
                $carrito['tipo_pedido']
            ) == 'MESA_PEDIDO'
            &&
            !empty(
                $carrito['mesa_id']
            )
        ){

            DB::update(

                'resto_mesa',

                [

                    'estado' =>
                        'DISPONIBLE'

                ],

                "mesa_id=%i",

                $carrito['mesa_id']

            );

        }

        /* ======================================
           DELIVERY
        ====================================== */

        $delivery =
            DB::queryFirstRow("

                SELECT deli_entrega_id

                FROM deli_entrega

                WHERE carrito_id = %i

                LIMIT 1

            ", $carrito_id);

        if($delivery){

            DB::update(
                'deli_entrega',
                [

                    'estado' =>
                        'entregado',

                    'fecha_entrega' =>
                        $now

                ],
                "carrito_id=%i",
                $carrito_id
            );

        }

        /* ======================================
           YAPLIN
        ====================================== */

        if($yaplin_id !== null){

            DB::update(
                'reg_yaplin',
                [

                    'neg_id' =>
                        $carrito['neg_id'],

                    'cliente_id' =>
                        $carrito['cliente_id'],

                    'usu_id' =>
                        $usu_id_vendedor,

                    'estado' =>
                        'PROCESADO',

                    'fecha_modificacion' =>
                        $now

                ],
                "yaplin_id=%i",
                $yaplin_id
            );

        }

        DB::commit();

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Venta registrada correctamente',

            'product_order_id' =>
                $product_order_id,

            'carrito_id' =>
                $carrito_id,

            'tipo_pago' =>
                $tipo_pago,

            'total' =>
                $total

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


Flight::route('POST /P7DX/draftGuardar', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $xin  = trim($d['xin'] ?? '');
    $yuan = trim($d['yuan'] ?? '');

    firma($xin,$yuan);

    $venta_borrador_id = intval(
        $d['venta_borrador_id'] ?? 0
    );

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $usu_id_vendedor = intval(
        $d['usu_id_vendedor'] ?? 0
    );

    $cliente_id = intval(
        $d['cliente_id'] ?? 1
    );

    $cliente_nombre = trim(
        $d['cliente_nombre']
        ?? 'PUBLICO GENERAL'
    );

    $fecha = trim(
        $d['fecha'] ?? ''
    );

    $tipo_pago = trim(
        $d['tipo_pago']
        ?? 'EFECTIVO'
    );

    $contenido_json = json_encode(

        $d,

        JSON_UNESCAPED_UNICODE

    );

    $now = date('Y-m-d H:i:s');

    if($venta_borrador_id){

        DB::update(

            'pos_venta_borrador',

            [

                'cliente_id' =>
                    $cliente_id,

                'cliente_nombre' =>
                    $cliente_nombre,

                'fecha' =>
                    $fecha,

                'tipo_pago' =>
                    $tipo_pago,

                'contenido_json' =>
                    $contenido_json,

                'fecha_modificacion' =>
                    $now

            ],

            "venta_borrador_id=%i",

            $venta_borrador_id

        );

    } else {

        DB::insert(

            'pos_venta_borrador',

            [

                'neg_id' =>
                    $neg_id,

                'usu_id_vendedor' =>
                    $usu_id_vendedor,

                'cliente_id' =>
                    $cliente_id,

                'cliente_nombre' =>
                    $cliente_nombre,
                'fecha' =>
                    $fecha,

                'tipo_pago' =>
                    $tipo_pago,

                'contenido_json' =>
                    $contenido_json,

                'fecha_creacion' =>
                    $now,

                'fecha_modificacion' =>
                    $now,

                'borrado_el' =>
                    null

            ]

        );

        $venta_borrador_id =
            DB::insertId();

    }

    Flight::json([

        'status' => 'ok',

        'venta_borrador_id' =>
            $venta_borrador_id

    ]);

});

Flight::route('POST /H9KD/draftListar', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $xin  = trim($d['xin'] ?? '');
    $yuan = trim($d['yuan'] ?? '');

    firma($xin,$yuan);

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    $usu_id_vendedor = intval(
        $d['usu_id_vendedor'] ?? 0
    );

    $rows = DB::query("

        SELECT

            venta_borrador_id,

            cliente_id,

            cliente_nombre,

            fecha,

            tipo_pago,

            fecha_modificacion

        FROM pos_venta_borrador

        WHERE

            neg_id = %i

            AND usu_id_vendedor = %i

            AND borrado_el IS NULL

        ORDER BY

            fecha_modificacion DESC

    ",

        $neg_id,

        $usu_id_vendedor

    );

    Flight::json([

        'status' => 'ok',

        'data' =>
            $rows

    ]);

});

Flight::route('POST /Q2ZA/draftDetalle', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $xin  = trim($d['xin'] ?? '');
    $yuan = trim($d['yuan'] ?? '');

    firma($xin,$yuan);

    $venta_borrador_id = intval(

        $d['venta_borrador_id']
        ?? 0

    );

    $row = DB::queryFirstRow("

        SELECT *

        FROM pos_venta_borrador

        WHERE

            venta_borrador_id = %i

            AND borrado_el IS NULL

        LIMIT 1

    ",

        $venta_borrador_id

    );

    if(!$row){

        Flight::json([

            'status' => 'error',

            'msg' => 'Borrador no encontrado'

        ],404);

        return;

    }

    $contenido = [];

    if(

        !empty(
            $row['contenido_json']
        )

    ){

        $contenido = json_decode(

            $row['contenido_json'],

            true

        ) ?: [];

    }

    Flight::json([

        'status' => 'ok',

        'data' =>

            $contenido

    ]);

});

Flight::route('POST /W8LP/draftEliminar', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $xin  = trim($d['xin'] ?? '');
    $yuan = trim($d['yuan'] ?? '');

    firma($xin,$yuan);

    $venta_borrador_id = intval(

        $d['venta_borrador_id']
        ?? 0

    );

    if(!$venta_borrador_id){

        Flight::json([

            'status' => 'error',

            'msg' => 'venta_borrador_id requerido'

        ],400);

        return;

    }

    DB::update(

        'pos_venta_borrador',

        [

            'borrado_el' =>
                date('Y-m-d H:i:s')

        ],

        "venta_borrador_id=%i",

        $venta_borrador_id

    );

    Flight::json([

        'status' => 'ok',

        'msg' => 'Borrador eliminado'

    ]);

});

function imprimir_recibo_venta_directa($product_order_id)
{
    include DEFINITION;
    DB::query("SET NAMES 'utf8mb4'");

    global $varhost;
    global $wkh_pdf;

    $venta = DB::queryFirstRow("

        SELECT

            o.product_order_id,
            o.total_fees,
            o.tipo_pago,
            o.fecha_creacion,

            c.nombres_apellidos cliente,
            c.dni,
            c.ruc cliente_ruc,
            c.celular,

            n.nombre negocio,
            n.img_logo,
            n.direccion,
            n.celular_informes,

            u.nombres_apellidos vendedor

        FROM pos_product_order o

        LEFT JOIN pos_cliente c
            ON c.cliente_id = o.cliente_id

        LEFT JOIN reg_neg n
            ON n.neg_id = o.neg_id

        LEFT JOIN reg_usu u
            ON u.usu_id = o.usu_id_vendedor

        WHERE o.product_order_id = %i

        LIMIT 1

    ", $product_order_id);

    if(!$venta){

        return null;

    }

    $detalles = DB::query("

        SELECT

            product_name producto,

            amount cantidad,

            price_item precio,

            (amount * price_item) subtotal

        FROM pos_product_order_detail

        WHERE product_order_id = %i

        AND borrado_el IS NULL

        ORDER BY product_order_detail_id

    ", $product_order_id);

    $template_data = [

        'informacion' => [[

            'logo' => $venta['img_logo'],

            'negocio' => $venta['negocio'],

            'direccion' => $venta['direccion'],

            'celular_negocio' => $venta['celular_informes'],

            'cliente' => $venta['cliente'],

            'dni' => $venta['dni'],

            'ruc_cliente' => $venta['cliente_ruc'],

            'celular' => $venta['celular'],

            'vendedor' => $venta['vendedor'],

            'tipo_pago' => str_replace(
                '_',
                ' ',
                $venta['tipo_pago']
            ),

            'fecha' => date(

                'd/m/Y H:i',

                strtotime(
                    $venta['fecha_creacion']
                )

            ),

            'product_order_id' =>
                $venta['product_order_id'],

            'total' => number_format(

                $venta['total_fees'],

                2,

                '.',

                ''

            )

        ]],

        'detalles' => $detalles

    ];

    $html = (new Mustache)->render(

        file_get_contents(

            VARPATH .

            '/public/reportes/reporte_html/recibo_venta_directa.html'

        ),

        $template_data

    );

    $pdf =

        $varpath_tmp .

        'recibo_' .

        $product_order_id .

        '_' .

        time() .

        '.pdf';

    $wkh_pdf->addPage($html);

    exec(

        $wkh_pdf->getCommand(

            $pdf

        )

    );

    return

        $varhost_tmp .        

        basename($pdf);
}

Flight::route('POST /GHDk/graficoVentas', function(){

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
       NEGOCIO
    ====================================== */

    $neg_id = intval(
        $d['neg_id'] ?? 0
    );

    if($neg_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id requerido'

        ], 400);

        return;
    }

    /* ======================================
       FECHAS
    ====================================== */

    $fecha_inicio = trim(
        $d['fecha_inicio'] ?? ''
    );

    $fecha_fin = trim(
        $d['fecha_fin'] ?? ''
    );

    if(
        $fecha_inicio === ''
        ||
        $fecha_fin === ''
    ){

        Flight::json([

            'status' => 'error',

            'msg' => 'fecha_inicio y fecha_fin requeridos'

        ], 400);

        return;
    }

    /* ======================================
       NEGOCIO EXISTE
    ====================================== */

    $negocio = DB::queryFirstRow("

        SELECT

            neg_id,
            nombre

        FROM reg_neg

        WHERE neg_id = %i

        AND borrado_el IS NULL

        LIMIT 1

    ", $neg_id);

    if(!$negocio){

        Flight::json([

            'status' => 'error',

            'msg' => 'Negocio no encontrado'

        ], 404);

        return;
    }

    /* ======================================
       VENTAS POR DIA
    ====================================== */

    $ventas = DB::query("

        SELECT

            DATE(fecha_creacion)
                AS fecha,

            SUM(total_fees)
                AS total_ventas

        FROM pos_product_order

        WHERE neg_id = %i

        AND borrado_el IS NULL

        AND DATE(fecha_creacion)
            BETWEEN %s AND %s

        GROUP BY
            DATE(fecha_creacion)

        ORDER BY
            DATE(fecha_creacion) ASC

    ",
        $neg_id,
        $fecha_inicio,
        $fecha_fin
    );

    /* ======================================
       GENERAR TODOS LOS DIAS
    ====================================== */

    $mapa = [];

    foreach($ventas as $v){

        $mapa[
            $v['fecha']
        ] = floatval(
            $v['total_ventas']
        );

    }

    $categorias = [];

    $serie = [];

    $inicio = new DateTime(
        $fecha_inicio
    );

    $fin = new DateTime(
        $fecha_fin
    );

    while(
        $inicio <= $fin
    ){

        $fecha = $inicio->format(
            'Y-m-d'
        );

        $categorias[] = $fecha;

        $serie[] =
            isset($mapa[$fecha])
            ?
            floatval(
                $mapa[$fecha]
            )
            :
            0;

        $inicio->modify(
            '+1 day'
        );

    }

    /* ======================================
       RESPONSE
    ====================================== */

    Flight::json([

        'status' => 'ok',

        'negocio' => $negocio,

        'apexcharts' => [

            'categories' =>
                $categorias,

            'series' => [

                [

                    'name' =>
                        'Ventas',

                    'data' =>
                        $serie

                ]

            ]

        ]

    ]);

});

