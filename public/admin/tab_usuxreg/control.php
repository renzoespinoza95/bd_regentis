<?php
/* ==========================
   USUXREG
   ========================== */

/* /usuxreg/inicio */
Flight::route('GET /usuxreg/inicio', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;
    include $path_public . '/admin/tab_usuxreg/inicio.php';
});

/* /usuxreg/listar */
Flight::route('GET /usuxreg/listar', function(){
    DB::query("SET NAMES 'utf8'");
    $rows = DB::query("SELECT * FROM usuxreg ORDER BY usuxreg_id DESC");
    Flight::json($rows);
});

/* /usuxreg/crear */
Flight::route('POST /usuxreg/crear', function(){
    $d = Flight::request()->data->getData();
    DB::insert('usuxreg', [
      'usu_id'         => $d['usu_id'],
      'accion'         => $d['accion'],
      'descripcion'    => $d['descripcion'],
      'reg_destino_id' => $d['reg_destino_id'],
      'extra_data'     => $d['extra_data']
    ]);
    Flight::json(['status'=>'ok']);
});

/* /usuxreg/editar */
Flight::route('POST /usuxreg/editar', function(){
    $d = Flight::request()->data->getData();
    DB::update('usuxreg', [
      'usu_id'         => $d['usu_id'],
      'accion'         => $d['accion'],
      'descripcion'    => $d['descripcion'],
      'reg_destino_id' => $d['reg_destino_id'],
      'extra_data'     => $d['extra_data']
    ], "usuxreg_id=%i", $d['usuxreg_id']);
    Flight::json(['status'=>'ok']);
});

/* /usuxreg/eliminar */
Flight::route('POST /usuxreg/eliminar', function(){
    $d = Flight::request()->data->getData();
    DB::delete('usuxreg', "usuxreg_id=%i", $d['usuxreg_id']);
    Flight::json(['status'=>'ok']);
});


/* ==========================
   REG_DESTINO
   ========================== */

/* /regdestino/listar */
Flight::route('GET /regdestino/listar', function(){
    $rows = DB::query("SELECT * FROM reg_destino ORDER BY reg_destino_id DESC");
    Flight::json($rows);
});

/* /regdestino/crear */
Flight::route('POST /regdestino/crear', function(){
    $d = Flight::request()->data->getData();
    DB::insert('reg_destino', [
      'tipo'          => $d['tipo'],
      'referencia_id' => $d['referencia_id']
    ]);
    Flight::json(['status'=>'ok']);
});
