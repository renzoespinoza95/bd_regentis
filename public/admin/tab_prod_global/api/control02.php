<?php
Flight::route('GET /WOyw/categoria/listar', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $rows = DB::query("
            SELECT categoria_global_id,nombre,is_activo
            FROM reg_categoria_global
            ORDER BY nombre ASC
        ");
        Flight::json(['status'=>'ok','data'=>$rows]);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WOyw/categoria/crear', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        $data = Flight::request()->data->getData();
        $nombre = trim($data['nombre'] ?? '');
        if($nombre==''){
            Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return;
        }
        DB::insert('reg_categoria_global',[
            'nombre'=>$nombre,
            'is_activo'=>1
        ]);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WOyw/categoria/editar', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        $data = Flight::request()->data->getData();
        $id = intval($data['categoria_global_id'] ?? 0);
        $nombre = trim($data['nombre'] ?? '');
        if($id<=0){
            Flight::json(['status'=>'error','msg'=>'ID inválido'],400); return;
        }
        if($nombre==''){
            Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return;
        }
        DB::update('reg_categoria_global',[
            'nombre'=>$nombre
        ],"categoria_global_id=%i",$id);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WOyw/categoria/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        $data = Flight::request()->data->getData();
        $id = intval($data['categoria_global_id'] ?? 0);
        if($id<=0){
            Flight::json(['status'=>'error','msg'=>'ID inválido'],400); return;
        }
        DB::delete('reg_categoria_global',"categoria_global_id=%i",$id);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});