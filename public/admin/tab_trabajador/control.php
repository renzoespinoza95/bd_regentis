<?php
Flight::route('GET /reg/trabajador/inicio', function(){

	include DEFINITION;

	autentificar_administrador();	

	include VARPATH.'/public/admin/tab_trabajador/inicio.php';

});



/**
 * 🔥 LISTAR TRABAJADORES (AHORA DESDE reg_negxusu)
 */
Flight::route('GET /reg/trabajador/listar', function(){

    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    $neg_id = intval($administrador_actual['neg_id']);

    $rows = DB::query("

        SELECT 
            nxu.negxusu_id,
            nxu.neg_id,
            nxu.usu_id,
            nxu.is_activo,
            nxu.fecha_creacion,

            u.cod_usu,
            u.nombres_apellidos AS nombre,
            u.sobrenombre,
            u.dni,
            u.celular AS telefono,
            u.email,
            u.img_perfil,
            u.tipoxusu_id,

            tu.clave_txt,
            tu.descripcion AS tipo_usuario

        FROM reg_negxusu nxu

        INNER JOIN reg_usu u 
            ON u.usu_id = nxu.usu_id

        LEFT JOIN reg_tipoxusu tu
            ON tu.tipoxusu_id = u.tipoxusu_id

        WHERE nxu.neg_id = %i

        ORDER BY nxu.negxusu_id DESC

    ", $neg_id);

    /* ======================================
       🔥 NORMALIZAR
    ====================================== */
    foreach($rows as &$r){
        $r['negxusu_id'] = intval($r['negxusu_id']);
        $r['neg_id']     = intval($r['neg_id']);
        $r['usu_id']     = intval($r['usu_id']);
        $r['is_activo']  = intval($r['is_activo']);
        $r['tipoxusu_id']= intval($r['tipoxusu_id']);
    }

    Flight::json($rows);

});



/**
 * 🔎 BUSCAR USUARIO POR DNI (SE MANTIENE IGUAL)
 */
Flight::route('GET /reg/trabajador/buscar/@dni', function($dni){

	$row = DB::queryFirstRow("SELECT
			usu_id,
			nombres_apellidos,
			email,
			tipoxusu_id
		FROM reg_usu
		WHERE dni=%s
	", $dni);

	if(!$row){
		Flight::halt(404);
	}

	Flight::json($row);

});



/**
 * ➕ AGREGAR TRABAJADOR (AHORA EN reg_negxusu)
 */
Flight::route('POST /reg/trabajador/agregar', function(){

	global $administrador_actual;

	$data = Flight::request()->data->getData();

	$usu_id = intval($data['usu_id']);

	// 🔍 obtener usuario
	$usuario = DB::queryFirstRow("
		SELECT usu_id, tipoxusu_id
		FROM reg_usu
		WHERE usu_id=%i
	", $usu_id);

	if(!$usuario){
		Flight::json([
			'status'=>'error',
			'msg'=>'Usuario no existe'
		]);
		return;
	}

	// 🔥 VALIDACIÓN: debe ser tipo 1
	if(intval($usuario['tipoxusu_id']) !== 1){
		Flight::json([
			'status'=>'error',
			'msg'=>'Solo usuarios tipo cliente pueden ser trabajadores'
		]);
		return;
	}

	// 🔍 verificar duplicado en negocio
	$existe = DB::queryFirstField("
		SELECT 1
		FROM reg_negxusu
		WHERE usu_id=%i AND neg_id=%i
	", $usu_id, $administrador_actual['neg_id']);

	if($existe){
		Flight::json([
			'status'=>'error',
			'msg'=>'El usuario ya pertenece a este negocio'
		]);
		return;
	}

	// 🔥 TRANSACTION (importante)
	DB::startTransaction();

	try {

		// ➕ insertar relación
		DB::insert('reg_negxusu', [

			'neg_id'  => $administrador_actual['neg_id'],
			'usu_id'  => $usu_id,
			'is_activo' => 1,
			'deli_tipo_trabajador_id' => 1,
			'fecha_creacion' => date('Y-m-d H:i:s')

		]);

		// 🔥 cambiar tipo usuario a trabajador (4)
		DB::update('reg_usu', [
			'tipoxusu_id' => 4
		], "usu_id=%i", $usu_id);

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



/**
 * ⛔ SUSPENDER TRABAJADOR
 */
Flight::route('POST /reg/trabajador/suspender', function(){

	$data = Flight::request()->data->getData();

	DB::update('reg_negxusu', [
		'is_activo' => 0
	], "negxusu_id=%i", $data['negxusu_id']);

	Flight::json(['status'=>'ok']);

});



/**
 * ❌ ELIMINAR TRABAJADOR
 */
Flight::route('POST /reg/trabajador/eliminar', function(){

	$data = Flight::request()->data->getData();

	$negxusu_id = intval($data['negxusu_id']);

	$row = DB::queryFirstRow("
		SELECT usu_id
		FROM reg_negxusu
		WHERE negxusu_id=%i
	", $negxusu_id);

	if(!$row){
		Flight::json(['status'=>'error','msg'=>'No encontrado']);
		return;
	}

	DB::startTransaction();

	try {

		// 🔥 borrar relación
		DB::delete('reg_negxusu', "negxusu_id=%i", $negxusu_id);

		// 🔥 reset usuario
		DB::update('reg_usu', [
			'tipoxusu_id' => 1
		], "usu_id=%i", $row['usu_id']);

		DB::commit();

		Flight::json(['status'=>'ok']);

	} catch(Exception $e){

		DB::rollback();

		Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
	}

});

Flight::route('POST /reg/trabajador/toggle', function(){

	$data = Flight::request()->data->getData();

	$negxusu_id = intval($data['negxusu_id']);

	$row = DB::queryFirstRow("
		SELECT usu_id, is_activo
		FROM reg_negxusu
		WHERE negxusu_id=%i
	", $negxusu_id);

	if(!$row){
		Flight::json(['status'=>'error','msg'=>'No encontrado']);
		return;
	}

	$nuevo_estado = $row['is_activo'] == 1 ? 0 : 1;

	DB::startTransaction();

	try {

		// 🔥 actualizar estado en relación
		DB::update('reg_negxusu', [
			'is_activo' => $nuevo_estado
		], "negxusu_id=%i", $negxusu_id);

		// 🔥 si se desactiva → vuelve a cliente
		if($nuevo_estado == 0){

			DB::update('reg_usu', [
				'tipoxusu_id' => 1
			], "usu_id=%i", $row['usu_id']);

		}else{

			// 🔥 si se activa → vuelve a trabajador
			DB::update('reg_usu', [
				'tipoxusu_id' => 4
			], "usu_id=%i", $row['usu_id']);
		}

		DB::commit();

		Flight::json(['status'=>'ok']);

	} catch(Exception $e){

		DB::rollback();

		Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
	}

});


