<?php
Flight::route('GET /WOyw/prod/listar', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $categoria_global_id = isset($_GET['categoria_global_id']) ? intval($_GET['categoria_global_id']) : 0;
        $sql = "
            SELECT 
                cod_prod_plazavea,
                nombre,
                marca,
                precio,
                categoria,
                categoria_global_id,
                url_imagen
            FROM prod_plazavea
            WHERE 1=1
        ";
        if($categoria_global_id > 0){
            $sql .= " AND categoria_global_id = $categoria_global_id";
        }
        $sql .= " ORDER BY nombre ASC";
        $rows = DB::query($sql);
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'=>'ok',
            'data'=>$rows
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }catch(Exception $e){
        if (ob_get_length()) ob_clean();
        echo json_encode([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

Flight::route('POST /WOyw/prod/guardar', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $cod = trim($data['cod_prod_plazavea'] ?? '');
        $nombre = trim($data['nombre'] ?? '');
        $marca = trim($data['marca'] ?? '');
        $precio = floatval($data['precio'] ?? 0);
        $categoria = trim($data['categoria'] ?? '');
        $categoria_global_id = intval($data['categoria_global_id'] ?? 0);

        if($nombre==''){
            Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return;
        }
        if($precio<=0){
            Flight::json(['status'=>'error','msg'=>'Precio inválido'],400); return;
        }
        if($categoria_global_id<=0){
            Flight::json(['status'=>'error','msg'=>'Categoría requerida'],400); return;
        }

        $cat = DB::queryFirstRow("
            SELECT categoria_global_id 
            FROM reg_categoria_global 
            WHERE categoria_global_id=%i
        ",$categoria_global_id);

        if(!$cat){
            Flight::json(['status'=>'error','msg'=>'Categoría no existe'],400); return;
        }

        if($cod==''){
            $cod = uniqid();
            DB::insert('prod_plazavea',[
                'cod_prod_plazavea'=>$cod,
                'nombre'=>$nombre,
                'marca'=>$marca,
                'precio'=>$precio,
                'categoria'=>$categoria,
                'categoria_global_id'=>$categoria_global_id,
                'version_actual'=>1
            ]);
        }else{
            DB::update('prod_plazavea',[
                'nombre'=>$nombre,
                'marca'=>$marca,
                'precio'=>$precio,
                'categoria'=>$categoria,
                'categoria_global_id'=>$categoria_global_id
            ],"cod_prod_plazavea=%s",$cod);
        }
        Flight::json(['status'=>'ok','cod'=>$cod]);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WOyw/prod/eliminar', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $cod = $data['cod_prod_plazavea'] ?? '';
        if($cod==''){
            Flight::json(['status'=>'error','msg'=>'Código requerido'],400); return;
        }
        DB::delete('prod_plazavea',"cod_prod_plazavea=%s",$cod);
        Flight::json(['status'=>'ok']);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /WOyw/prod/subir-img', function(){
    include DEFINITION;
    autentificar_administrador();
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $cod = $_POST['cod_prod_plazavea'] ?? '';
        if($cod==''){
            Flight::json(['status'=>'error','msg'=>'Código requerido'],400); return;
        }
        if(!isset($_FILES['file'])){
            Flight::json(['status'=>'error','msg'=>'Archivo requerido'],400); return;
        }
        $file = $_FILES['file'];
        $nombre = 'prod_'.time().'_'.rand(1000,9999).'.jpg';
        $ruta = VARPATH.'/public/uploads/'.$nombre;
        move_uploaded_file($file['tmp_name'],$ruta);
        $url = '/uploads/'.$nombre;
        DB::update('prod_plazavea',[
            'url_imagen'=>$url
        ],"cod_prod_plazavea=%s",$cod);
        Flight::json(['status'=>'ok','url'=>$url]);
    }catch(Exception $e){
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});