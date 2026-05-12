<?php

Flight::route('POST /EXiz/trabajador/listar', function(){

	DB::query("SET NAMES 'utf8mb4'");

	$d = json_decode(
		Flight::request()->getBody(),
		true
	) ?: [];

	$neg_id = intval($d['neg_id'] ?? 0);

	/* ======================================
	   VALIDAR
	====================================== */
	if(!$neg_id){

		Flight::json([
			'status' => 'error',
			'msg' => 'neg_id requerido'
		], 400);

		return;
	}

	/* ======================================
	   LISTAR
	====================================== */
	$rows = DB::query("

		SELECT 

			nxu.negxusu_id,

			u.usu_id,

			u.nombres_apellidos AS nombre,

			u.celular AS telefono,

			u.img_perfil,

			u.email,

			u.tipoxusu_id,

			nxu.is_activo,

			tu.descripcion AS tipo_trabajador

		FROM reg_negxusu nxu

		INNER JOIN reg_usu u 
			ON u.usu_id = nxu.usu_id

		LEFT JOIN reg_tipoxusu tu
			ON tu.tipoxusu_id = u.tipoxusu_id

		WHERE nxu.neg_id = %i

		ORDER BY nxu.negxusu_id DESC

	", $neg_id);

	/* ======================================
	   RESPONSE
	====================================== */
	Flight::json([
		'status' => 'ok',
		'data' => $rows
	]);

});

Flight::route('POST /YWRF/despachar', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $carrito_id =
        intval($d['carrito_id'] ?? 0);

    $neg_id =
        intval($d['neg_id'] ?? 0);

    $usu_id =
        intval($d['usu_id'] ?? 0);

    $direccion =
        trim($d['direccion'] ?? '');

    $fecha_entrega =
        trim($d['fecha_entrega'] ?? '');

    /* ======================================
       VALIDAR
    ====================================== */
    if (
        !$carrito_id ||
        !$neg_id ||
        !$usu_id
    ) {

        Flight::json([
            'status' => 'error',
            'msg' => 'Datos incompletos'
        ], 400);

        return;
    }

    DB::startTransaction();

    try {

        /* ======================================
           VALIDAR CARRITO
        ====================================== */
        $carrito = DB::queryFirstRow("

            SELECT *
            FROM reg_carrito
            WHERE carrito_id = %i
            LIMIT 1

        ", $carrito_id);

        if (!$carrito) {

            DB::rollback();

            Flight::json([
                'status' => 'error',
                'msg' => 'Carrito no encontrado'
            ], 404);

            return;
        }

        /* ======================================
           ACTUALIZAR ESTADO
        ====================================== */
        DB::update(
            'reg_carrito',
            [
                'estado' => 'transito'
            ],
            "carrito_id=%i",
            $carrito_id
        );

        /* ======================================
           CREAR ENTREGA
        ====================================== */
        DB::insert(
            'deli_entrega',
            [

                'neg_id' =>
                    $neg_id,

                'carrito_id' =>
                    $carrito_id,

                'trab_usu_id' =>
                    $usu_id,

                'direccion' =>
                    ($direccion ?: null),

                'fecha_salida' =>
                    date('Y-m-d H:i:s'),

                'fecha_entrega' =>
                    ($fecha_entrega ?: null),

                'estado' =>
                    'transito'
            ]
        );

        $deli_entrega_id =
            DB::insertId();

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */
        Flight::json([

            'status' => 'ok',

            'deli_entrega_id' =>
                $deli_entrega_id,

            'carrito_id' =>
                $carrito_id
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }
});

