<?php
/* =========================================================
   ENDPOINTS: reg_neg / reg_mercado / reg_negxusu / reg_usu
   Stack: PHP 8.1 + FlightPHP + MeekroDB2
========================================================= */

/* =========================================================
   VISTAS
========================================================= */

Flight::route('GET /neg/inicio', function () {
    include DEFINITION;
    autentificar_administrador();    
    include VARPATH . '/public/admin/tab_neg/inicio.php';
});

Flight::route('GET /mercado/inicio', function () {
    include DEFINITION;
    autentificar_administrador();
    require_once VARPATH . '/public/admin/tab_mercado/inicio.php';
});

    /* =========================================
       NEG/LISTAR
    ========================================= */

    /* =========================================
   NEG/LISTAR
========================================= */

Flight::route('GET /neg/listar', function() {

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("

            SELECT 

                n.neg_id,
                n.nombre,
                n.puesto,
                n.mercado_id,
                n.is_activo

            FROM reg_neg n

            ORDER BY n.neg_id DESC

        ");

        foreach($rows as &$r){

            $rubros = DB::query("

                SELECT

                    rxn.rubroxneg_id,
                    rxn.neg_id,
                    rxn.rubro_id,
                    rxn.is_activo,

                    rr.nombre,
                    rr.icono

                FROM reg_rubroxneg rxn

                INNER JOIN reg_rubro rr
                    ON rr.rubro_id = rxn.rubro_id

                WHERE rxn.neg_id = %i
                AND rxn.is_activo = 1
                AND rr.is_activo = 1

                ORDER BY rr.nombre ASC

            ", $r['neg_id']);

            $r['rubros_obj'] = $rubros;

            $r['rubros'] = implode(
                ', ',
                array_map(
                    fn($x)=>$x['nombre'],
                    $rubros
                )
            );

        }

        Flight::json([
            'status'=>'ok',
            'data'=>$rows
        ]);

    } catch (Exception $e) {

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);

    }

});

/* =========================================
   NEG/CREAR
========================================= */

/* =========================================
   NEG/CREAR
========================================= */

Flight::route('POST /neg/crear', function() {

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $nombre = isset($data['nombre'])
            ? trim($data['nombre'])
            : '';

        $puesto = isset($data['puesto'])
            ? trim($data['puesto'])
            : '';

        $mercado_id = isset($data['mercado_id'])
            ? intval($data['mercado_id'])
            : 0;

        if ($nombre === '') {

            Flight::json([
                'status'=>'error',
                'msg'=>'Nombre requerido'
            ],400);

            return;
        }

        if ($puesto === '') {

            Flight::json([
                'status'=>'error',
                'msg'=>'Puesto requerido'
            ],400);

            return;
        }

        if ($mercado_id <= 0) {

            Flight::json([
                'status'=>'error',
                'msg'=>'Mercado requerido'
            ],400);

            return;
        }

        DB::startTransaction();

        try {

            DB::insert('reg_neg',[

                'nombre'=>$nombre,

                'puesto'=>$puesto,

                'mercado_id'=>$mercado_id,

                'fecha_creacion'=>date('Y-m-d H:i:s'),

                'is_activo'=>1,

                'cod_neg'=>'0'

            ]);

            $neg_id = DB::insertId();

            /* =========================================
               RUBROS
            ========================================= */

            $rubros = $data['rubros'] ?? [];

            if(is_array($rubros)){

                foreach($rubros as $rubro_id){

                    $rubro_id = intval($rubro_id);

                    if($rubro_id <= 0){
                        continue;
                    }

                    DB::insert('reg_rubroxneg',[

                        'neg_id'    => $neg_id,

                        'rubro_id'  => $rubro_id,

                        'is_activo' => 1

                    ]);

                }

            }

            DB::commit();

            Flight::json([

                'status'=>'ok',

                'neg_id'=>$neg_id

            ]);

        } catch(Exception $e){

            DB::rollback();

            throw $e;
        }

    } catch (Exception $e) {

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);

    }

});

/* =========================================
   NEG/EDITAR
========================================= */

/* =========================================
   NEG/EDITAR
========================================= */

Flight::route('POST /neg/editar', function() {

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $neg_id = isset($data['neg_id'])
            ? intval($data['neg_id'])
            : 0;

        $nombre = isset($data['nombre'])
            ? trim($data['nombre'])
            : '';

        $puesto = isset($data['puesto'])
            ? trim($data['puesto'])
            : '';

        $mercado_id = isset($data['mercado_id'])
            ? intval($data['mercado_id'])
            : 0;

        if ($neg_id <= 0) {

            Flight::json([
                'status'=>'error',
                'msg'=>'neg_id inválido'
            ],400);

            return;
        }

        if ($nombre === '') {

            Flight::json([
                'status'=>'error',
                'msg'=>'Nombre requerido'
            ],400);

            return;
        }

        if ($puesto === '') {

            Flight::json([
                'status'=>'error',
                'msg'=>'Puesto requerido'
            ],400);

            return;
        }

        if ($mercado_id <= 0) {

            Flight::json([
                'status'=>'error',
                'msg'=>'Mercado requerido'
            ],400);

            return;
        }

        DB::startTransaction();

        try {

            DB::update('reg_neg',[

                'nombre'=>$nombre,

                'puesto'=>$puesto,

                'mercado_id'=>$mercado_id

            ],"neg_id=%i",$neg_id);

            /* =========================================
               ELIMINAR RUBROS
            ========================================= */

            DB::delete(
                'reg_rubroxneg',
                "neg_id=%i",
                $neg_id
            );

            /* =========================================
               INSERTAR RUBROS
            ========================================= */

            $rubros = $data['rubros'] ?? [];

            if(is_array($rubros)){

                foreach($rubros as $rubro_id){

                    $rubro_id = intval($rubro_id);

                    if($rubro_id <= 0){
                        continue;
                    }

                    DB::insert('reg_rubroxneg',[

                        'neg_id'    => $neg_id,

                        'rubro_id'  => $rubro_id,

                        'is_activo' => 1

                    ]);

                }

            }

            DB::commit();

            Flight::json([
                'status'=>'ok'
            ]);

        } catch(Exception $e){

            DB::rollback();

            throw $e;
        }

    } catch (Exception $e) {

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);

    }

});

Flight::route('POST /neg/eliminar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $neg_id = isset($data['neg_id']) ? intval($data['neg_id']) : 0;

        if ($neg_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return;
        }

        DB::startTransaction();

        try {
            DB::delete('reg_negxusu',"neg_id=%i",$neg_id);
            DB::delete('reg_neg',"neg_id=%i",$neg_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

/* =========================================================
   MERCADOS
========================================================= */

Flight::route('GET /mercado/listar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("
            SELECT 
                mercado_id,
                nombre,
                direccion,
                is_activo,
                logo,
                topnavbar_color,
                patron_fondo
            FROM reg_mercado
            ORDER BY mercado_id DESC
        ");

        Flight::json(['status'=>'ok','data'=>$rows]);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /mercado/crear', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
        $direccion = isset($data['direccion']) ? trim($data['direccion']) : '';
        $is_activo = isset($data['is_activo']) ? intval($data['is_activo']) : 1;

        if ($nombre === '') {
            Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return;
        }

        if ($is_activo !== 0 && $is_activo !== 1) {
            $is_activo = 1;
        }

        DB::insert('reg_mercado',[
            'nombre'=>$nombre,
            'direccion'=>($direccion === '' ? null : $direccion),
            'fecha_creacion'=>date('Y-m-d H:i:s'),
            'is_activo'=>$is_activo
        ]);

        Flight::json(['status'=>'ok','mercado_id'=>DB::insertId()]);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /mercado/editar', function() {
    try {
        global $regentis;

        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $mercado_id = isset($data['mercado_id']) ? intval($data['mercado_id']) : 0;
        $nombre = isset($data['nombre']) ? trim($data['nombre']) : '';
        $direccion = isset($data['direccion']) ? trim($data['direccion']) : '';
        $is_activo = isset($data['is_activo']) ? intval($data['is_activo']) : 1;



        if ($mercado_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'mercado_id inválido'],400); return;
        }

        if ($nombre === '') {
            Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return;
        }

        if ($is_activo !== 0 && $is_activo !== 1) {
            $is_activo = 1;
        }

        $m = DB::queryFirstRow("SELECT mercado_id FROM reg_mercado WHERE mercado_id=%i",$mercado_id);
        if (!$m) {
            Flight::json(['status'=>'error','msg'=>'Mercado no existe'],404); return;
        }

        DB::update('reg_mercado',[
            'nombre'=>$nombre,
            'direccion'=>($direccion === '' ? null : $direccion),
            'is_activo'=>$is_activo,
            'logo'=>($data['logo'] ?: null),
            'topnavbar_color'=>($data['topnavbar_color'] ?: null),
            'patron_fondo'=>($data['patron_fondo'] ?: null)
        ],"mercado_id=%i",$mercado_id);

        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /mercado/eliminar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $mercado_id = isset($data['mercado_id']) ? intval($data['mercado_id']) : 0;

        if ($mercado_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'mercado_id inválido'],400); return;
        }

        DB::delete('reg_mercado',"mercado_id=%i",$mercado_id);

        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

/* =========================================================
   NEGXUSU
========================================================= */

Flight::route('GET /negxusu/obtener', function() {
    try {
        global $regentis;

        DB::query("SET NAMES 'utf8mb4'");
        $neg_id = isset(Flight::request()->query['neg_id']) ? intval(Flight::request()->query['neg_id']) : 0;

        if ($neg_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return;
        }

        $row = DB::queryFirstRow("
            SELECT nx.negxusu_id,nx.neg_id,nx.usu_id,u.dni,u.nombres_apellidos,u.is_activo
            FROM reg_negxusu nx
            INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
            WHERE nx.neg_id = %i AND nx.is_activo = 1
            ORDER BY nx.negxusu_id DESC
            LIMIT 1
        ",$neg_id);

        Flight::json(['status'=>'ok','data'=>$row ? $row : null]);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /negxusu/eliminar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $negxusu_id = isset($data['negxusu_id']) ? intval($data['negxusu_id']) : 0;

        if ($negxusu_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'negxusu_id inválido'],400); return;
        }

        DB::delete('reg_negxusu',"negxusu_id=%i",$negxusu_id);

        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

/* =========================================================
   BUSCAR DNI
========================================================= */

Flight::route('POST /usu/buscar-dni', function() {
    try {
        global $regentis;

        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $dni = isset($data['dni']) ? trim($data['dni']) : '';
        $neg_id = isset($data['neg_id']) ? intval($data['neg_id']) : 0;

        if ($dni === '') {
            Flight::json(['status'=>'error','msg'=>'DNI requerido'],400); return;
        }

        if ($neg_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return;
        }

        $asig = DB::queryFirstRow("
            SELECT nx.negxusu_id,nx.neg_id,nx.usu_id,u.dni,u.nombres_apellidos,u.is_activo
            FROM reg_negxusu nx
            INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
            WHERE nx.neg_id = %i AND nx.is_activo = 1
            ORDER BY nx.negxusu_id DESC
            LIMIT 1
        ",$neg_id);

        if ($asig) {
            Flight::json(['status'=>'ok','data'=>$asig]); return;
        }

        $u = DB::queryFirstRow("
            SELECT usu_id,dni,nombres_apellidos,is_activo
            FROM reg_usu
            WHERE dni = %s
            LIMIT 1
        ",$dni);

        if (!$u) {
            Flight::json(['status'=>'ok','data'=>null]); return;
        }

        $u['negxusu_id'] = 0;

        Flight::json(['status'=>'ok','data'=>$u]);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

/* =========================================================
   ASIGNAR PROPIETARIO
========================================================= */

Flight::route('POST /negxusu/asignar', function() {
    try {
        global $regentis;

        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();

        $neg_id = isset($data['neg_id']) ? intval($data['neg_id']) : 0;
        $usu_id = isset($data['usu_id']) ? intval($data['usu_id']) : 0;

        if ($neg_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return;
        }

        if ($usu_id <= 0) {
            Flight::json(['status'=>'error','msg'=>'usu_id inválido'],400); return;
        }

        $neg = DB::queryFirstRow("SELECT neg_id FROM reg_neg WHERE neg_id=%i",$neg_id);
        if (!$neg) {
            Flight::json(['status'=>'error','msg'=>'Negocio no existe'],404); return;
        }

        $usu = DB::queryFirstRow("SELECT * FROM reg_usu WHERE usu_id=%i",$usu_id);
        if (!$usu) {
            Flight::json(['status'=>'error','msg'=>'Usuario no existe'],404); return;
        }

        $existe = DB::queryFirstRow("
            SELECT negxusu_id
            FROM reg_negxusu
            WHERE neg_id=%i AND is_activo=1
        ",$neg_id);

        if ($existe) {
            Flight::json(['status'=>'error','msg'=>'Este negocio ya tiene propietario'],400); return;
        }

        $trabajador_existente = DB::queryFirstRow("
            SELECT deli_trabajador_id
            FROM deli_trabajador
            WHERE neg_id=%i AND usu_id=%i
        ",$neg_id,$usu_id);

        DB::startTransaction();

        try {
            DB::insert('reg_negxusu',[
                'usu_id'=>$usu_id,
                'neg_id'=>$neg_id,
                'is_activo'=>1,
                'fecha_creacion'=>date('Y-m-d H:i:s')
            ]);

            $negxusu_id = DB::insertId();

            if (!$trabajador_existente) {
                DB::insert('deli_trabajador',[
                    'neg_id'=>$neg_id,
                    'usu_id'=>$usu_id,
                    'nombre'=>$usu['nombres_apellidos'],
                    'telefono'=>$usu['celular'],
                    'is_activo'=>1
                ]);
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        Flight::json(['status'=>'ok','negxusu_id'=>$negxusu_id]);

    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});


Flight::route('GET /rubro/listar', function(){

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("

        SELECT

            rubro_id,
            nombre,
            icono,
            is_activo

        FROM reg_rubro

        WHERE is_activo = 1

        ORDER BY nombre ASC

    ");

    Flight::json([

        'status'=>'ok',

        'data'=>$rows

    ]);

});

Flight::route('GET /rubroxneg/listar', function(){
    $neg_id = intval(Flight::request()->query['neg_id']);

    $rows = DB::query("
        SELECT rn.rubroxneg_id,r.rubro_id,r.nombre
        FROM deux_rubroxneg rn
        INNER JOIN deux_rubro r ON r.rubro_id = rn.rubro_id
        WHERE rn.neg_id=%i
    ",$neg_id);

    Flight::json(['status'=>'ok','data'=>$rows]);
});

Flight::route('POST /rubroxneg/crear', function(){

    $data = Flight::request()->data->getData();

    DB::insert('deux_rubroxneg',[
        'neg_id'=>$data['neg_id'],
        'rubro_id'=>$data['rubro_id']
    ]);

    Flight::json(['status'=>'ok']);
});

Flight::route('POST /rubroxneg/eliminar', function(){

    $data = Flight::request()->data->getData();

    DB::delete('deux_rubroxneg',"rubroxneg_id=%i",$data['rubroxneg_id']);

    Flight::json(['status'=>'ok']);
});