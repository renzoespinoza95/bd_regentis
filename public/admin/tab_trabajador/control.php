<?php
Flight::route('GET /reg/trabajador/inicio', function(){

	include DEFINITION;

	autentificar_administrador();	

	include $path_public.'/admin/tab_trabajador/inicio.php';

});

Flight::route('GET /reg/trabajador/listar', function(){

	global $administrador_actual;

	DB::query("SET NAMES 'utf8'");

	$rows=DB::query("

		SELECT *

		FROM deli_trabajador

		WHERE neg_id=%i

		ORDER BY deli_trabajador_id DESC

		",$administrador_actual['neg_id']);

	Flight::json($rows);

});

Flight::route('GET /reg/trabajador/buscar/@dni', function($dni){

	$row=DB::queryFirstRow("

		SELECT

		usu_id,

		nombres_apellidos,

		email

		FROM reg_usu

		WHERE dni=%s

		",$dni);

	if(!$row){

		Flight::halt(404);

	}

	Flight::json($row);

});

Flight::route('POST /reg/trabajador/agregar', function(){

	global $administrador_actual;

	$data=Flight::request()->data->getData();

	$usu_id=intval($data['usu_id']);

	$usuario=DB::queryFirstRow("

		SELECT *

		FROM reg_usu

		WHERE usu_id=%i

		",$usu_id);

	DB::insert('deli_trabajador',[

		'neg_id'=>$administrador_actual['neg_id'],

		'usu_id'=>$usu_id,

		'nombre'=>$usuario['nombres_apellidos'],

		'telefono'=>$usuario['celular'],

		'is_activo'=>1

	]);

	Flight::json(['status'=>'ok']);

});

Flight::route('POST /reg/trabajador/suspender', function(){

	$data=Flight::request()->data->getData();

	DB::update('deli_trabajador',[

		'is_activo'=>0

	],"deli_trabajador_id=%i",$data['deli_trabajador_id']);

	Flight::json(['status'=>'ok']);

});

Flight::route('POST /reg/trabajador/eliminar', function(){

	$data=Flight::request()->data->getData();

	DB::delete('deli_trabajador',

		"deli_trabajador_id=%i",

		$data['deli_trabajador_id']);

	Flight::json(['status'=>'ok']);

});

