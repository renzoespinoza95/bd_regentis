<?php

Flight::route('GET /tema/inicio', function () {
    include DEFINITION;
    autentificar_administrador();
    require_once VARPATH . '/public/admin/tab_tema/tema.html';
});

Flight::route('GET /U9bTnegListarCombo', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $rows = DB::query("
            SELECT neg_id, nombre, puesto
            FROM reg_neg
            WHERE borrado_el IS NULL
            ORDER BY nombre ASC
        ");
        Flight::json([
            'status'=>'ok',
            'data'=>$rows
        ]);
    }catch(Exception $e){
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

Flight::route('GET /U9bTtemaListar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $rows = DB::query("
            SELECT tema_id, nombre_tema, miniatura, vista_app, topnavbar, boton, fondo_card
            FROM reg_tema
            ORDER BY tema_id DESC
        ");
        Flight::json([
            'status'=>'ok',
            'data'=>$rows
        ]);
    }catch(Exception $e){
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

Flight::route('POST /U9bTtemaCrear', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $nombre_tema = trim($data['nombre_tema'] ?? '');
        $miniatura = trim($data['miniatura'] ?? '');
        $vista_app = trim($data['vista_app'] ?? '');

        DB::insert('reg_tema', [
            'nombre_tema'=>$nombre_tema,
            'miniatura'=>$miniatura,
            'vista_app'=>$vista_app
        ]);

        Flight::json([
            'status'=>'ok',
            'tema_id'=>DB::insertId()
        ]);
    }catch(Exception $e){
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

Flight::route('POST /U9bTtemaEditar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $tema_id = intval($data['tema_id'] ?? 0);

        if($tema_id<=0){
            Flight::json([
                'status'=>'error',
                'msg'=>'tema_id inválido'
            ],400);
            return;
        }

        DB::update('reg_tema', [
            'nombre_tema'=>trim($data['nombre_tema'] ?? ''),
            'miniatura'=>trim($data['miniatura'] ?? ''),
            'vista_app'=>trim($data['vista_app'] ?? '')
        ], 'tema_id=%i', $tema_id);

        Flight::json([
            'status'=>'ok'
        ]);
    }catch(Exception $e){
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

Flight::route('GET /U9bTtemaExportarJson', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $rows = DB::query("SELECT * FROM reg_tema ORDER BY tema_id ASC");
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="reg_tema_' . date('Y-m-d_H-i-s') . '.json"');
        echo json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }catch(Exception $e){
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

Flight::route('POST /U9bTtemaImportarJson', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $request = Flight::request();
        $jsonData = null;

        if(isset($_FILES['json_file']) && $_FILES['json_file']['error'] === UPLOAD_ERR_OK){
            $content = file_get_contents($_FILES['json_file']['tmp_name']);
            $jsonData = json_decode($content, true);
        } else {
            $data = $request->data->getData();
            if(!empty($data['json_text'])){
                $jsonData = json_decode($data['json_text'], true);
            }
        }

        if(!is_array($jsonData)){
            Flight::json(['status'=>'error', 'msg'=>'JSON inválido o archivo no proporcionado'], 400);
            return;
        }

        DB::startTransaction();
        try {
            DB::query("SET FOREIGN_KEY_CHECKS = 0");
            DB::query("TRUNCATE TABLE reg_tema");
            DB::query("SET FOREIGN_KEY_CHECKS = 1");

            foreach($jsonData as $row){
                DB::insert('reg_tema', [
                    'tema_id'     => isset($row['tema_id']) ? intval($row['tema_id']) : null,
                    'nombre_tema' => $row['nombre_tema'] ?? null,
                    'topnavbar'   => $row['topnavbar'] ?? null,
                    'fondo'       => $row['fondo'] ?? null,
                    'boton'       => $row['boton'] ?? null,
                    'fondo_card'  => $row['fondo_card'] ?? null,
                    'subtitulo'   => $row['subtitulo'] ?? null,
                    'base_css'    => $row['base_css'] ?? null,
                    'estilo_css'  => $row['es0tilo_css'] ?? null,
                    'miniatura'   => $row['miniatura'] ?? null,
                    'vista_app'   => $row['vista_app'] ?? null
                ]);
            }
            DB::commit();
        } catch(Exception $ex){
            DB::query("SET FOREIGN_KEY_CHECKS = 1");
            DB::rollback();
            throw $ex;
        }

        Flight::json(['status'=>'ok', 'msg'=>'Importación completada con éxito']);
    }catch(Exception $e){
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});