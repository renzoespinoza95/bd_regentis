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

/* =========================================
   NEG/LISTAR
========================================= */

Flight::route('GET /neg/listar', function() {

    autentificar_administrador();

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("

            SELECT 

                n.neg_id,
                n.nombre,
                n.puesto,
                n.img_logo,
                n.mercado_id,
                n.is_activo

            FROM reg_neg n

            WHERE n.borrado_el IS NULL

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

/* =========================================
   NEG/ELIMINAR
========================================= */
/* =========================================
   NEG/ELIMINAR
========================================= */

Flight::route('POST /neg/eliminar', function() {

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $neg_id = isset($data['neg_id'])
            ? intval($data['neg_id'])
            : 0;

        if ($neg_id <= 0) {

            Flight::json([
                'status'=>'error',
                'msg'=>'neg_id inválido'
            ],400);

            return;
        }

        DB::startTransaction();

        try {

            DB::update(

                'reg_neg',

                [

                    'borrado_el' =>
                        date('Y-m-d H:i:s'),

                    'is_activo' => 0

                ],

                "neg_id=%i",

                $neg_id

            );

            DB::update(

                'reg_negxusu',

                [

                    'is_activo' => 0

                ],

                "neg_id=%i",

                $neg_id

            );

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

/* =========================================================
   MERCADOS
========================================================= */

Flight::route('GET /mercado/listar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("
            SELECT

                m.mercado_id,
                m.cat_mercado_id,
                m.nombre,
                m.direccion,
                m.is_activo,
                m.logo,
                m.topnavbar_color,
                m.patron_fondo,

                cm.nombre AS categoria

            FROM reg_mercado m

            LEFT JOIN reg_cat_mercado cm
            ON cm.cat_mercado_id = m.cat_mercado_id

            ORDER BY m.mercado_id DESC
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

        $cat_mercado_id = intval(
            $data['cat_mercado_id'] ?? 0
        );



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
            'cat_mercado_id' =>
                $cat_mercado_id > 0
                    ? $cat_mercado_id
                    : null,
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

function crear_tienda_fantasma(){

    DB::query("SET NAMES 'utf8mb4'");

    /* ======================================
       RUBRO RANDOM
    ====================================== */

    $rubro = DB::queryFirstRow("

        SELECT
            rubro_id,
            nombre

        FROM reg_rubro

        WHERE is_activo = 1

        ORDER BY RAND()

        LIMIT 1

    ");

    if(!$rubro){

        return [];
    }

    /* ======================================
       NICK RANDOM
    ====================================== */

    $nick = DB::queryFirstField("

        SELECT nick

        FROM tt_nick

        ORDER BY RAND()

        LIMIT 1

    ");

    /* ======================================
       NEGOCIO
    ====================================== */

    $nombre_negocio =
        ucfirst($nick)
        . ' '
        . generarSimple();

    $celular =
        '9'
        . rand(
            10000000,
            99999999
        );

    /* ======================================
       CATEGORIAS RANDOM
    ====================================== */

    $categorias = DB::query("

        SELECT

            categoria_global_id,
            nombre,
            icono

        FROM reg_categoria_global

        WHERE is_activo = 1
        AND borrado_el IS NULL

        ORDER BY RAND()

        LIMIT 3

    ");

    $categorias_array = [];

    foreach($categorias as $cg){

        $productos_array = [];

        /* ==========================
           3 PRODUCTOS RANDOM
        ========================== */

        for($i=0; $i<3; $i++){

            $plazavea_id = rand(
                1,
                21024
            );

            $p = DB::queryFirstRow("

                SELECT

                    plazavea_id,
                    cod_prod_plazavea,
                    nombre,
                    marca,
                    precio,
                    categoria,
                    url_imagen

                FROM prod_plazavea

                WHERE plazavea_id = %i

                LIMIT 1

            ", $plazavea_id);

            if(!$p){
                continue;
            }

            $productos_array[] = [

                'cod_prod_plazavea' =>
                    $p['cod_prod_plazavea'],

                'name' =>
                    $p['nombre'],

                'tipo_producto' =>
                    'ABARROTES',

                'marca_des' =>
                    $p['marca'],

                'price' =>
                    floatval(
                        $p['precio']
                    ),

                'description' =>
                    $p['categoria'],

                'stock' =>
                    rand(5,50)

            ];

        }

        $categorias_array[] = [

            'categoria_global_id' =>
                $cg['categoria_global_id'],

            'name' =>
                $cg['nombre'],

            'icon' =>
                $cg['icono'],

            'productos' =>
                $productos_array

        ];

    }

    /* ======================================
       RETURN ARRAY
    ====================================== */

    return [

        'negocio' => [

            'nombre' =>
                $nombre_negocio,

            'descripcion' =>
                'Tienda automática Regentis',

            'mercado_id' => 1,

            'puesto' =>
                'P-' . rand(1,999),

            'ciudad' => 'Lima',

            'provincia' => 'Lima',

            'departamento' => 'Lima',

            'direccion' =>
                'Mercado Regentis',

            'celular_informes' =>
                $celular,

            'img_logo' =>
                'https://barsi-img.b-cdn.net/recursos/sg3f.png',

            'lista_yape' => [

                $celular

            ],

            'is_activo' => 1,

            'is_validado' => 1,

            'rubro_id' =>
                $rubro['rubro_id']

        ],

        'slider' => [

            'img' =>
                'https://barsi-img.b-cdn.net/p_info/slider_20260513_180039_4794.jpg',

            'titulo_superior' =>
                'Bienvenido a Regentis',

            'descripcion' =>
                'Promociones especiales'

        ],

        'categorias' =>
            $categorias_array

    ];

}

function generarSimple(){

    $letras = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    $mix = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    $txt = '';

    /* ======================================
       3 LETRAS
    ====================================== */

    for($i=0; $i<3; $i++){

        $txt .= $letras[
            rand(0, strlen($letras)-1)
        ];

    }

    /* ======================================
       4 MIX
    ====================================== */

    for($i=0; $i<4; $i++){

        $txt .= $mix[
            rand(0, strlen($mix)-1)
        ];

    }

    return $txt;
}

Flight::route('POST /Pfnf/tiendaFantasma', function(){

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    /* ======================================
       VALIDAR
    ====================================== */

    if($usu_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'usu_id requerido'

        ], 400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           USUARIO
        ====================================== */

        $usuario = DB::queryFirstRow("

            SELECT usu_id

            FROM reg_usu

            WHERE usu_id = %i

            LIMIT 1

        ", $usu_id);

        if(!$usuario){

            DB::rollback();

            Flight::json([

                'status' => 'error',

                'msg' => 'Usuario no encontrado'

            ], 404);

            return;
        }

        /* ======================================
           ARRAY FANTASMA
        ====================================== */

        $data =
            crear_tienda_fantasma();

        if(
            empty($data)
        ){

            throw new Exception(
                'No se pudo generar tienda'
            );
        }

        /* ======================================
           NEGOCIO
        ====================================== */

        $negocio =
            $data['negocio'];

        /* ======================================
           COD NEG
        ====================================== */

        do {

            $cod_neg =
                'NEG'
                . generarSimple();

            $existe_cod =
                DB::queryFirstField("

                    SELECT neg_id

                    FROM reg_neg

                    WHERE cod_neg = %s

                    LIMIT 1

                ", $cod_neg);

        } while($existe_cod);

        /* ======================================
           INSERT NEGOCIO
        ====================================== */

        DB::insert(
            'reg_neg',
            [

                'cod_neg' =>
                    $cod_neg,

                'nombre' =>
                    $negocio['nombre'],

                'celular_informes' =>
                    $negocio['celular_informes'],

                'fecha_creacion' =>
                    $now,

                'is_activo' =>
                    $negocio['is_activo'],

                'ciudad' =>
                    $negocio['ciudad'],

                'provincia' =>
                    $negocio['provincia'],

                'departamento' =>
                    $negocio['departamento'],

                'direccion' =>
                    $negocio['direccion'],

                'is_validado' =>
                    $negocio['is_validado'],

                'img_logo' =>
                    $negocio['img_logo'],

                'puesto' =>
                    $negocio['puesto'],

                'descripcion' =>
                    $negocio['descripcion'],

                'mercado_id' =>
                    $negocio['mercado_id'],

                'lista_yape' => json_encode(
                    $negocio['lista_yape'],
                    JSON_UNESCAPED_UNICODE
                )

            ]
        );

        $neg_id = DB::insertId();

        veri_publico_general($neg_id);

        /* ======================================
           RUBRO NEGOCIO
        ====================================== */

        if(
            !empty(
                $negocio['rubro_id']
            )
        ){

            DB::insert(
                'reg_rubroxneg',
                [

                    'neg_id' =>
                        $neg_id,

                    'rubro_id' =>
                        $negocio['rubro_id'],

                    'is_activo' => 1

                ]
            );

        }
        /* ======================================
           SLIDER
        ====================================== */

        if(
            !empty(
                $data['slider']
            )
        ){

            DB::insert(
                'reg_slider',
                [

                    'img' =>
                        $data['slider']['img'],

                    'orden' => 1,

                    'is_visible' => 1,

                    'fecha_creacion' =>
                        $now,

                    'fecha_fin' => NULL,

                    'neg_id' =>
                        $neg_id,

                    'grupo' => 'B',

                    'descripcion' =>
                        $data['slider']['descripcion'],

                    'titulo_superior' =>
                        $data['slider']['titulo_superior'],

                    'borrado_el' => NULL

                ]
            );

        }

        /* ======================================
           CATEGORIAS
        ====================================== */

        $productos_creados = [];

        if(
            !empty(
                $data['categorias']
            )
        ){

            foreach(
                $data['categorias']
                as $cat
            ){

                /* ==========================
                   CATEGORY
                ========================== */

                DB::insert(
                    'pos_category',
                    [

                        'name' =>
                            $cat['name'],

                        'icon' =>
                            $cat['icon'],

                        'brief' =>
                            'Productos de '
                            . $cat['name'],

                        'color' =>
                            '#FD7635',

                        'priority' =>
                            rand(1,50),

                        'neg_id' =>
                            $neg_id,

                        'categoria_global_id' =>
                            $cat['categoria_global_id'],

                        'is_activo' => 1,

                        'clave_txt' =>
                            strtolower(
                                trim(
                                    $cat['name']
                                )
                            ),

                        'img' =>
                            'https://barsi-img.b-cdn.net/recursos/ffc1.png'

                    ]
                );

                $category_id =
                    DB::insertId();

                /* ==========================
                   PRODUCTOS
                ========================== */

                if(
                    !empty(
                        $cat['productos']
                    )
                ){

                    foreach(
                        $cat['productos']
                        as $p
                    ){

                        DB::insert(
                            'pos_product',
                            [

                                'cod_producto' =>
                                    'AUTO_' . uniqid(),
                                'name' =>
                                    $p['name'],

                                'tipo_producto' =>
                                    $p['tipo_producto'],

                                'marca_des' =>
                                    $p['marca_des'],
                                'price' =>
                                    $p['price'],

                                'description' =>
                                    $p['description'],

                                'fecha_creacion' =>
                                    $now,

                                'fecha_modificacion' =>
                                    $now,

                                'neg_id' =>
                                    $neg_id,

                                'is_visible' => 1

                            ]
                        );

                        $product_id =
                            DB::insertId();

                        $productos_creados[] =
                            $product_id;

                        /* ==========================
                           PRODUCT CATEGORY
                        ========================== */

                        DB::insert(
                            'pos_product_category',
                            [

                                'product_id' =>
                                    $product_id,

                                'category_id' =>
                                    $category_id,

                                'is_visible' => 1,

                                'neg_id' =>
                                    $neg_id

                            ]
                        );

                        /* ==========================
                           IMAGE
                        ========================== */

                        DB::insert(
                            'pos_product_image',
                            [

                                'product_id' =>
                                    $product_id,

                                'img' =>
                                    'https://barsi-img.b-cdn.net/recursos/6qz5.png',

                                'orden' => 1,

                                'is_visible' => 1

                            ]
                        );

                        /* ==========================
                           INVENTARIO
                        ========================== */

                        DB::insert(
                            'pos_inventario',
                            [

                                'product_id' =>
                                    $product_id,

                                'stock_actual' =>
                                    $p['stock'],

                                'neg_id' =>
                                    $neg_id

                            ]
                        );

                        DB::insert(
                            'pos_inventario_movimiento',
                            [

                                'product_id' =>
                                    $product_id,

                                'tipo' =>
                                    'AJUSTE',

                                'origen' =>
                                    'AJUSTE',

                                'cantidad' =>
                                    $p['stock'],

                                'precio_unitario' =>
                                    $p['price'],

                                'fecha' =>
                                    $now,

                                'stock_resultante' =>
                                    $p['stock'],

                                'neg_id' =>
                                    $neg_id

                            ]
                        );

                    }

                }

            }

        }

        /* ======================================
           MAS VENDIDOS
        ====================================== */

        $cat_mas =
            DB::queryFirstRow("

                SELECT category_id

                FROM pos_category

                WHERE neg_id = %i
                AND clave_txt = 'mas-vendidos'

                LIMIT 1

            ", $neg_id);

        if(!$cat_mas){

            DB::insert(
                'pos_category',
                [

                    'name' =>
                        'Más vendidos',

                    'icon' =>
                        '🔥',

                    'brief' =>
                        'Productos más solicitados',

                    'color' =>
                        '#FF4D4F',

                    'priority' =>
                        999,

                    'neg_id' =>
                        $neg_id,

                    'is_activo' => 1,

                    'clave_txt' =>
                        'mas-vendidos',

                    'img' =>
                        'https://barsi-img.b-cdn.net/recursos/ffc1.png'

                ]
            );

            $mas_category_id =
                DB::insertId();

            shuffle(
                $productos_creados
            );

            $top2 =
                array_slice(
                    $productos_creados,
                    0,
                    2
                );

            foreach($top2 as $pid){

                DB::insert(
                    'pos_product_category',
                    [

                        'product_id' =>
                            $pid,

                        'category_id' =>
                            $mas_category_id,

                        'is_visible' => 1,

                        'neg_id' =>
                            $neg_id

                    ]
                );

            }

        }

        /* ======================================
           NEGXUSU
        ====================================== */

        DB::insert(
            'reg_negxusu',
            [

                'usu_id' =>
                    $usu_id,

                'neg_id' =>
                    $neg_id,

                'is_activo' => 1,

                'fecha_creacion' =>
                    $now

            ]
        );

        /* ======================================
           PROPIETARIO
        ====================================== */

        $tipoxusu_id =
            DB::queryFirstField("

                SELECT tipoxusu_id

                FROM reg_tipoxusu

                WHERE LOWER(clave_txt)
                LIKE '%propietario%'

                LIMIT 1

            ");

        if(!$tipoxusu_id){
            $tipoxusu_id = 2;
        }

        DB::update(
            'reg_usu',
            [

                'tipoxusu_id' =>
                    $tipoxusu_id

            ],
            "usu_id=%i",
            $usu_id
        );

        DB::commit();

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Tienda fantasma creada',

            'neg_id' =>
                $neg_id

        ]);

    } catch(Throwable $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage(),

            'line' => $e->getLine(),

            'file' => $e->getFile()

        ], 500);

    }

});


Flight::route('POST /OO09/productosFantasma', function(){

    include DEFINITION;

    autentificar_administrador();

    global $administrador_actual;

    DB::query("SET NAMES 'utf8mb4'");

    /* ======================================
       NEGOCIO
    ====================================== */

    $neg_id = isset($administrador_actual['neg_id'])
        ? intval($administrador_actual['neg_id'])
        : 0;

    if($neg_id <= 0){

        Flight::json([

            'status' => 'error',

            'msg' => 'neg_id inválido'

        ], 400);

        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');

        /* ======================================
           CATEGORIA GLOBAL RANDOM
        ====================================== */

        $cg = DB::queryFirstRow("

            SELECT

                categoria_global_id,
                nombre,
                icono

            FROM reg_categoria_global

            WHERE is_activo = 1
            AND borrado_el IS NULL

            ORDER BY RAND()

            LIMIT 1

        ");

        if(!$cg){

            throw new Exception(
                'No existe categoría global'
            );
        }

        /* ======================================
           VERIFICAR CATEGORY
        ====================================== */

        $category = DB::queryFirstRow("

            SELECT

                category_id

            FROM pos_category

            WHERE neg_id = %i
            AND categoria_global_id = %i
            AND is_activo = 1

            LIMIT 1

        ",
            $neg_id,
            $cg['categoria_global_id']
        );

        /* ======================================
           CREAR CATEGORY
        ====================================== */

        if(!$category){

            DB::insert(
                'pos_category',
                [

                    'name' =>
                        $cg['nombre'],

                    'icon' =>
                        $cg['icono'],

                    'brief' =>
                        'Productos de '
                        . $cg['nombre'],

                    'color' =>
                        '#FD7635',

                    'priority' =>
                        rand(1,50),

                    'neg_id' =>
                        $neg_id,

                    'categoria_global_id' =>
                        $cg['categoria_global_id'],

                    'is_activo' => 1,

                    'clave_txt' =>
                        strtolower(
                            trim(
                                $cg['nombre']
                            )
                        ),

                    'img' =>
                        'https://barsi-img.b-cdn.net/recursos/ffc1.png'

                ]
            );

            $category_id =
                DB::insertId();

        }else{

            $category_id =
                intval(
                    $category['category_id']
                );

        }

        /* ======================================
           5 PRODUCTOS RANDOM
        ====================================== */

        $productos_creados = [];

        for($i=0; $i<5; $i++){

            $plazavea_id = rand(
                1,
                21024
            );

            $p = DB::queryFirstRow("

                SELECT

                    plazavea_id,
                    cod_prod_plazavea,
                    nombre,
                    marca,
                    precio,
                    categoria,
                    url_imagen

                FROM prod_plazavea

                WHERE plazavea_id = %i

                LIMIT 1

            ", $plazavea_id);

            if(!$p){
                continue;
            }

            /* ==========================
               PRODUCTO
            ========================== */

            DB::insert(
                'pos_product',
                [

                    'cod_producto' =>
                        'AUTO_' . uniqid(),

                    'name' =>
                        $p['nombre']
                        ?? 'Producto',

                    'tipo_producto' =>
                        'ABARROTES',

                    'marca_des' =>
                        $p['marca']
                        ?? 'GENERICO',

                    'price' =>
                        floatval(
                            $p['precio']
                            ?? rand(5,100)
                        ),

                    'description' =>
                        $p['categoria']
                        ?? '',

                    'fecha_creacion' =>
                        $now,

                    'fecha_modificacion' =>
                        $now,

                    'neg_id' =>
                        $neg_id,

                    'is_visible' => 1

                ]
            );

            $product_id =
                DB::insertId();

            $productos_creados[] =
                $product_id;

            /* ==========================
               PRODUCT CATEGORY
            ========================== */

            DB::insert(
                'pos_product_category',
                [

                    'product_id' =>
                        $product_id,

                    'category_id' =>
                        $category_id,

                    'is_visible' => 1,

                    'neg_id' =>
                        $neg_id

                ]
            );

            /* ==========================
               IMAGE
            ========================== */

            DB::insert(
                'pos_product_image',
                [

                    'product_id' =>
                        $product_id,

                    'img' =>
                        'https://barsi-img.b-cdn.net/recursos/6qz5.png',

                    'orden' => 1,

                    'is_visible' => 1

                ]
            );

            /* ==========================
               INVENTARIO
            ========================== */

            $stock = rand(5,50);

            DB::insert(
                'pos_inventario',
                [

                    'product_id' =>
                        $product_id,

                    'stock_actual' =>
                        $stock,

                    'neg_id' =>
                        $neg_id

                ]
            );

            DB::insert(
                'pos_inventario_movimiento',
                [

                    'product_id' =>
                        $product_id,

                    'tipo' =>
                        'AJUSTE',

                    'origen' =>
                        'AJUSTE',

                    'cantidad' =>
                        $stock,

                    'precio_unitario' =>
                        floatval(
                            $p['precio']
                            ?? 0
                        ),

                    'fecha' =>
                        $now,

                    'stock_resultante' =>
                        $stock,

                    'neg_id' =>
                        $neg_id

                ]
            );

        }

        /* ======================================
           MAS VENDIDOS
        ====================================== */

        $cat_mas =
            DB::queryFirstRow("

                SELECT category_id

                FROM pos_category

                WHERE neg_id = %i
                AND clave_txt = 'mas-vendidos'

                LIMIT 1

            ", $neg_id);

        if(!$cat_mas){

            DB::insert(
                'pos_category',
                [

                    'name' =>
                        'Más vendidos',

                    'icon' =>
                        '🔥',

                    'brief' =>
                        'Productos más solicitados',

                    'color' =>
                        '#FF4D4F',

                    'priority' =>
                        999,

                    'neg_id' =>
                        $neg_id,

                    'is_activo' => 1,

                    'clave_txt' =>
                        'mas-vendidos',

                    'img' =>
                        'https://barsi-img.b-cdn.net/recursos/ffc1.png'

                ]
            );

            $mas_category_id =
                DB::insertId();

        }else{

            $mas_category_id =
                intval(
                    $cat_mas['category_id']
                );

        }

        shuffle(
            $productos_creados
        );

        $top2 =
            array_slice(
                $productos_creados,
                0,
                2
            );

        foreach($top2 as $pid){

            $existeRelacion =
                DB::queryFirstField("

                    SELECT 1

                    FROM pos_product_category

                    WHERE product_id = %i
                    AND category_id = %i

                    LIMIT 1

                ",
                    $pid,
                    $mas_category_id
                );

            if(!$existeRelacion){

                DB::insert(
                    'pos_product_category',
                    [

                        'product_id' =>
                            $pid,

                        'category_id' =>
                            $mas_category_id,

                        'is_visible' => 1,

                        'neg_id' =>
                            $neg_id

                    ]
                );

            }

        }

        DB::commit();

        Flight::json([

            'status' => 'ok',

            'msg' =>
                'Productos fantasma creados',

            'category_id' =>
                $category_id,

            'productos_creados' =>
                count(
                    $productos_creados
                )

        ]);

    } catch(Throwable $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage(),

            'line' => $e->getLine(),

            'file' => $e->getFile()

        ], 500);

    }

});

/* =========================================
   NEG/ACTIVO
========================================= */

Flight::route('POST /neg/activo', function(){

    autentificar_administrador();

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $neg_id = isset($data['neg_id'])
            ? intval($data['neg_id'])
            : 0;

        $is_activo = isset($data['is_activo'])
            ? intval($data['is_activo'])
            : -1;

        if($neg_id <= 0){

            Flight::json([
                'status'=>'error',
                'msg'=>'neg_id inválido'
            ],400);

            return;
        }

        if(
            $is_activo !== 0
            &&
            $is_activo !== 1
        ){

            Flight::json([
                'status'=>'error',
                'msg'=>'is_activo inválido'
            ],400);

            return;
        }

        DB::update(

            'reg_neg',

            [

                'is_activo' => $is_activo

            ],

            "neg_id=%i",

            $neg_id

        );

        Flight::json([

            'status'=>'ok'

        ]);

    } catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

/* =========================================
   QQvN/NEG/LOGO/DEFECTO
========================================= */

Flight::route('POST /QQvN/neg/logo/defecto', function(){

    autentificar_administrador();

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $neg_id = intval(
            $data['neg_id'] ?? 0
        );

        if($neg_id <= 0){

            Flight::json([
                'status'=>'error',
                'msg'=>'neg_id inválido'
            ],400);

            return;
        }

        $logo =
            'https://barsi-img.b-cdn.net/recursos/sg3f.png';

        DB::update(

            'reg_neg',

            [

                'img_logo' => $logo

            ],

            "neg_id=%i",

            $neg_id

        );

        Flight::json([

            'status'=>'ok',

            'img_logo'=>$logo

        ]);

    } catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

/* =========================================
   QQvN/NEG/LOGO/RANDOM
========================================= */

Flight::route('POST /QQvN/neg/logo/random', function(){

    autentificar_administrador();

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $neg_id = intval(
            $data['neg_id'] ?? 0
        );

        if($neg_id <= 0){

            Flight::json([
                'status'=>'error',
                'msg'=>'neg_id inválido'
            ],400);

            return;
        }

        $img = DB::queryFirstField("

            SELECT url

            FROM tt_imagen

            ORDER BY RAND()

            LIMIT 1

        ");

        if(!$img){

            Flight::json([
                'status'=>'error',
                'msg'=>'No existen imágenes'
            ],404);

            return;
        }

        DB::update(

            'reg_neg',

            [

                'img_logo' => $img

            ],

            "neg_id=%i",

            $neg_id

        );

        Flight::json([

            'status'=>'ok',

            'img_logo'=>$img

        ]);

    } catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});


/* =========================================
   QQvN/BUSCAR/USUARIO/COD_USU
========================================= */

Flight::route('POST /QQvN/buscarUsuarioCodUsu', function(){

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $data = Flight::request()->data->getData();

    $cod_usu = trim(
        $data['cod_usu'] ?? ''
    );

    if(!$cod_usu){

        Flight::json([
            'status'=>'error',
            'msg'=>'cod_usu requerido'
        ],400);

        return;
    }

    $usu = DB::queryFirstRow("

        SELECT
            usu_id,
            cod_usu,
            nombres_apellidos,
            sobrenombre,
            celular,
            dni,
            img_perfil,
            email,
            tipoxusu_id
        FROM reg_usu
        WHERE cod_usu = %s
        AND (
            borrado_el IS NULL
        )
        LIMIT 1

    ", $cod_usu);

    if(!$usu){

        Flight::json([
            'status'=>'error',
            'msg'=>'Usuario no encontrado'
        ],404);

        return;
    }

    Flight::json([

        'status'=>'ok',

        'usuario'=>$usu

    ]);

});

/* =========================================
   QQvN/NEG/PROPIETARIOS
========================================= */

Flight::route('POST /QQvN/neg/propietarios', function(){

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $data = Flight::request()->data->getData();

    $neg_id = intval(
        $data['neg_id'] ?? 0
    );

    $rows = DB::query("

        SELECT

            nx.negxusu_id,

            nx.neg_id,

            nx.usu_id,

            nx.is_activo,

            nx.fecha_creacion,

            u.cod_usu,

            u.nombres_apellidos,

            u.sobrenombre,

            u.celular,

            u.email,

            u.img_perfil,

            u.tipoxusu_id

        FROM reg_negxusu nx

        INNER JOIN reg_usu u
        ON u.usu_id = nx.usu_id

        WHERE nx.neg_id = %i

        AND nx.borrado_el IS NULL

        AND u.tipoxusu_id = 2

        ORDER BY nx.negxusu_id DESC

    ", $neg_id);

    Flight::json([

        'status'=>'ok',

        'rows'=>$rows

    ]);

});

/* =========================================
   QQvN/NEG/PROPIETARIO/ELIMINAR
========================================= */

Flight::route('POST /QQvN/neg/propietario/eliminar', function(){

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $data = Flight::request()->data->getData();

    $negxusu_id = intval(
        $data['negxusu_id'] ?? 0
    );

    if(!$negxusu_id){

        Flight::json([
            'status'=>'error',
            'msg'=>'negxusu_id requerido'
        ],400);

        return;
    }

    $row = DB::queryFirstRow("

        SELECT
            negxusu_id,
            usu_id
        FROM reg_negxusu
        WHERE negxusu_id = %i

    ", $negxusu_id);

    if(!$row){

        Flight::json([
            'status'=>'error',
            'msg'=>'Asignación no encontrada'
        ],404);

        return;
    }

    DB::update(

        'reg_negxusu',

        [

            'borrado_el' =>
                date('Y-m-d H:i:s')

        ],

        "negxusu_id=%i",

        $negxusu_id

    );

    DB::update(

        'reg_usu',

        [

            'tipoxusu_id' => 1

        ],

        "usu_id=%i",

        $row['usu_id']

    );

    Flight::json([

        'status'=>'ok'

    ]);

});


Flight::route('POST /WEwr/mercado/activo', function(){

    autentificar_administrador();

    try{

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $mercado_id = intval(
            $data['mercado_id'] ?? 0
        );

        $is_activo = intval(
            $data['is_activo'] ?? -1
        );

        if($mercado_id <= 0){

            Flight::json([
                'status'=>'error',
                'msg'=>'mercado_id inválido'
            ],400);

            return;
        }

        if(
            $is_activo !== 0
            &&
            $is_activo !== 1
        ){

            Flight::json([
                'status'=>'error',
                'msg'=>'is_activo inválido'
            ],400);

            return;
        }

        DB::update(

            'reg_mercado',

            [
                'is_activo'=>$is_activo
            ],

            "mercado_id=%i",

            $mercado_id

        );

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

Flight::route('GET /WEwr/catmercado/listar', function(){

    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query("

        SELECT

            cat_mercado_id,
            nombre,
            is_visible,
            is_activo
        FROM reg_cat_mercado

        WHERE borrado_el IS NULL

        ORDER BY nombre ASC

    ");

    Flight::json([

        'status'=>'ok',

        'data'=>$rows

    ]);

});

Flight::route('GET /WEwr/catmercado/listar', function(){

    try{

        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("

            SELECT

                cat_mercado_id,
                nombre,
                is_visible,
                is_activo

            FROM reg_cat_mercado

            WHERE borrado_el IS NULL

            ORDER BY cat_mercado_id DESC

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

Flight::route('POST /WEwr/catmercado/crear', function(){

    try{

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()
            ->data
            ->getData();

        $nombre = trim(
            $data['nombre'] ?? ''
        );

        $is_visible = intval(
            $data['is_visible'] ?? 1
        );

        $is_activo = intval(
            $data['is_activo'] ?? 1
        );

        if($nombre === ''){

            Flight::json([

                'status'=>'error',

                'msg'=>'Nombre requerido'

            ],400);

            return;
        }

        DB::insert(

            'reg_cat_mercado',

            [

                'nombre'=>$nombre,

                'is_visible'=>$is_visible,

                'is_activo'=>$is_activo

            ]

        );

        Flight::json([

            'status'=>'ok',

            'cat_mercado_id'=>
                DB::insertId()

        ]);

    }catch(Exception $e){

        Flight::json([

            'status'=>'error',

            'msg'=>$e->getMessage()

        ],500);

    }

});

Flight::route('POST /WEwr/catmercado/editar', function(){

    try{

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()
            ->data
            ->getData();

        $cat_mercado_id = intval(
            $data['cat_mercado_id'] ?? 0
        );

        $nombre = trim(
            $data['nombre'] ?? ''
        );

        $is_visible = intval(
            $data['is_visible'] ?? 1
        );

        $is_activo = intval(
            $data['is_activo'] ?? 1
        );

        if($cat_mercado_id <= 0){

            Flight::json([

                'status'=>'error',

                'msg'=>'cat_mercado_id inválido'

            ],400);

            return;
        }

        if($nombre === ''){

            Flight::json([

                'status'=>'error',

                'msg'=>'Nombre requerido'

            ],400);

            return;
        }

        DB::update(

            'reg_cat_mercado',

            [

                'nombre'=>$nombre,

                'is_visible'=>$is_visible,

                'is_activo'=>$is_activo

            ],

            "cat_mercado_id=%i",

            $cat_mercado_id

        );

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

Flight::route('POST /WEwr/catmercado/eliminar', function(){

    try{

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()
            ->data
            ->getData();

        $cat_mercado_id = intval(
            $data['cat_mercado_id'] ?? 0
        );

        if($cat_mercado_id <= 0){

            Flight::json([

                'status'=>'error',

                'msg'=>'cat_mercado_id inválido'

            ],400);

            return;
        }

        DB::update(

            'reg_cat_mercado',

            [

                'borrado_el'=>
                    date('Y-m-d H:i:s')

            ],

            "cat_mercado_id=%i",

            $cat_mercado_id

        );

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

Flight::route('POST /WEwr/catmercado/activo', function(){

    try{

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()
            ->data
            ->getData();

        $cat_mercado_id = intval(
            $data['cat_mercado_id'] ?? 0
        );

        $is_activo = intval(
            $data['is_activo'] ?? -1
        );

        if($cat_mercado_id <= 0){

            Flight::json([

                'status'=>'error',

                'msg'=>'cat_mercado_id inválido'

            ],400);

            return;
        }

        if(
            $is_activo !== 0
            &&
            $is_activo !== 1
        ){

            Flight::json([

                'status'=>'error',

                'msg'=>'is_activo inválido'

            ],400);

            return;
        }

        DB::update(

            'reg_cat_mercado',

            [

                'is_activo'=>$is_activo

            ],

            "cat_mercado_id=%i",

            $cat_mercado_id

        );

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

Flight::route('POST /WEwr/catmercado/visible', function(){

    try{

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()
            ->data
            ->getData();

        $cat_mercado_id = intval(
            $data['cat_mercado_id'] ?? 0
        );

        $is_visible = intval(
            $data['is_visible'] ?? -1
        );

        if($cat_mercado_id <= 0){

            Flight::json([

                'status'=>'error',

                'msg'=>'cat_mercado_id inválido'

            ],400);

            return;
        }

        if(
            $is_visible !== 0
            &&
            $is_visible !== 1
        ){

            Flight::json([

                'status'=>'error',

                'msg'=>'is_visible inválido'

            ],400);

            return;
        }

        DB::update(

            'reg_cat_mercado',

            [

                'is_visible'=>$is_visible

            ],

            "cat_mercado_id=%i",

            $cat_mercado_id

        );

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






