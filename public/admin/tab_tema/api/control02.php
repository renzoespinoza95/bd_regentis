<?php

Flight::route('POST /U9bTtemaEliminar', function(){
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

        DB::startTransaction();
        try{
            DB::delete('reg_temaxneg', 'tema_id=%i', $tema_id);
            DB::delete('reg_tema', 'tema_id=%i', $tema_id);
            DB::commit();
        }catch(Exception $e){
            DB::rollback();
            throw $e;
        }

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

Flight::route('GET /U9bTtemaxnegListar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $rows = DB::query("
            SELECT
                txn.temaxneg_id,
                txn.tema_id,
                txn.neg_id,
                t.nombre_tema,
                n.nombre AS negocio,
                t.topnavbar
            FROM reg_temaxneg txn
            INNER JOIN reg_neg n ON n.neg_id = txn.neg_id
            INNER JOIN reg_tema t ON t.tema_id = txn.tema_id
            ORDER BY txn.temaxneg_id DESC
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

Flight::route('POST /U9bTtemaxnegCrear', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $tema_id = isset($data['tema_id']) && is_array($data['tema_id']) ? intval($data['tema_id']['tema_id'] ?? 0) : intval($data['tema_id'] ?? 0);
        $neg_id = isset($data['neg_id']) && is_array($data['neg_id']) ? intval($data['neg_id']['neg_id'] ?? 0) : intval($data['neg_id'] ?? 0);

        if($tema_id <= 0){
            Flight::json(['status'=>'error', 'msg'=>'tema_id requerido'],400);
            return;
        }
        if($neg_id <= 0){
            Flight::json(['status'=>'error', 'msg'=>'neg_id requerido'],400);
            return;
        }

        DB::startTransaction();
        try {
            DB::delete('reg_temaxneg', 'neg_id=%i', $neg_id);

            DB::insert('reg_temaxneg', [
                'tema_id' => $tema_id,
                'neg_id'  => $neg_id
            ]);

            $nuevoId = DB::insertId();
            DB::commit();

            Flight::json([
                'status'=>'ok',
                'temaxneg_id'=> $nuevoId,
                'tema_id'=> $tema_id,
                'neg_id'=> $neg_id
            ]);
        } catch(Exception $ex){
            DB::rollback();
            throw $ex;
        }

    }catch(Exception $e){
        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);
    }
});

Flight::route('POST /U9bTtemaxnegEditar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $temaxneg_id = intval($data['temaxneg_id'] ?? 0);
        $tema_id = intval($data['tema_id'] ?? 0);
        $neg_id = intval($data['neg_id'] ?? 0);

        if($temaxneg_id<=0){
            Flight::json(['status'=>'error', 'msg'=>'temaxneg_id inválido'],400);
            return;
        }

        DB::startTransaction();
        try {
            if($neg_id > 0){
                DB::delete('reg_temaxneg', 'neg_id=%i AND temaxneg_id != %i', $neg_id, $temaxneg_id);
            }

            DB::update('reg_temaxneg', [
                'tema_id'=> $tema_id,
                'neg_id'=> $neg_id
            ], 'temaxneg_id=%i', $temaxneg_id);

            DB::commit();
        } catch(Exception $ex){
            DB::rollback();
            throw $ex;
        }

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

Flight::route('POST /U9bTtemaxnegEliminar', function(){
    try{
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $temaxneg_id = intval($data['temaxneg_id'] ?? 0);

        if($temaxneg_id<=0){
            Flight::json([
                'status'=>'error',
                'msg'=>'temaxneg_id inválido'
            ],400);
            return;
        }

        DB::delete('reg_temaxneg', 'temaxneg_id=%i', $temaxneg_id);

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