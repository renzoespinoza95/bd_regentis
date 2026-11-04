<?php
Flight::route('GET /negxusu/obtener', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $neg_id = isset(Flight::request()->query['neg_id']) ? intval(Flight::request()->query['neg_id']) : 0;
        if ($neg_id <= 0) { Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

        $row = DB::queryFirstRow("
            SELECT nx.negxusu_id, nx.neg_id, nx.usu_id, u.dni, u.nombres_apellidos, u.is_activo
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
        $negxusu_id = intval($data['negxusu_id'] ?? 0);
        if ($negxusu_id <= 0) { Flight::json(['status'=>'error','msg'=>'negxusu_id inválido'],400); return; }

        DB::delete('reg_negxusu',"negxusu_id=%i",$negxusu_id);
        Flight::json(['status'=>'ok']);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /usu/buscar-dni', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $dni = trim($data['dni'] ?? '');
        $neg_id = intval($data['neg_id'] ?? 0);

        if ($dni === '') { Flight::json(['status'=>'error','msg'=>'DNI requerido'],400); return; }
        if ($neg_id <= 0) { Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

        $asig = DB::queryFirstRow("
            SELECT nx.negxusu_id, nx.neg_id, nx.usu_id, u.dni, u.nombres_apellidos, u.is_activo
            FROM reg_negxusu nx
            INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
            WHERE nx.neg_id = %i AND nx.is_activo = 1
            ORDER BY nx.negxusu_id DESC
            LIMIT 1
        ",$neg_id);

        if ($asig) { Flight::json(['status'=>'ok','data'=>$asig]); return; }

        $u = DB::queryFirstRow("
            SELECT usu_id, dni, nombres_apellidos, is_activo
            FROM reg_usu
            WHERE dni = %s
            LIMIT 1
        ",$dni);

        if (!$u) { Flight::json(['status'=>'ok','data'=>null]); return; }
        $u['negxusu_id'] = 0;
        Flight::json(['status'=>'ok','data'=>$u]);
    } catch (Exception $e) {
        Flight::json(['status'=>'error','msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /negxusu/asignar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        $usu_id = intval($data['usu_id'] ?? 0);

        if($neg_id <= 0){ Flight::json(['status' => 'error', 'msg' => 'neg_id inválido']); return; }
        if($usu_id <= 0){ Flight::json(['status' => 'error', 'msg' => 'usu_id inválido']); return; }

        $negocio = DB::queryFirstRow("SELECT * FROM reg_neg WHERE neg_id = %i AND borrado_el IS NULL LIMIT 1", $neg_id);
        if(!$negocio){ Flight::json(['status' => 'error', 'msg' => 'Negocio no existe']); return; }

        $usuario = DB::queryFirstRow("SELECT * FROM reg_usu WHERE usu_id = %i AND borrado_el IS NULL LIMIT 1", $usu_id);
        if(!$usuario){ Flight::json(['status' => 'error', 'msg' => 'Usuario no existe']); return; }

        $relacion = DB::queryFirstRow("SELECT negxusu_id FROM reg_negxusu WHERE neg_id = %i AND usu_id = %i AND borrado_el IS NULL LIMIT 1", $neg_id, $usu_id);
        if($relacion){ Flight::json(['status' => 'error', 'msg' => 'El usuario ya está asociado a este negocio']); return; }

        DB::startTransaction();
        try {
            DB::insert('reg_negxusu', [
                'neg_id' => $neg_id,
                'usu_id' => $usu_id,
                'is_activo' => 1,
                'fecha_creacion' => date('Y-m-d H:i:s')
            ]);
            $negxusu_id = DB::insertId();

            DB::update('reg_usu', ['tipoxusu_id' => 2], 'usu_id = %i', $usu_id);            
            DB::commit();
        } catch(Exception $e){
            DB::rollback();
            throw $e;
        }

        Flight::json([
            'status' => 'ok',
            'negxusu_id' => $negxusu_id,
            'neg_id' => $neg_id,
            'usu_id' => $usu_id
        ]);
    } catch(Exception $e){
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()]);
    }
});

Flight::route('GET /rubro/listar', function(){
    DB::query("SET NAMES 'utf8mb4'");
    $rows = DB::query("
        SELECT rubro_id, nombre, icono, is_activo
        FROM reg_rubro
        WHERE is_activo = 1
        ORDER BY nombre ASC
    ");
    Flight::json(['status'=>'ok', 'data'=>$rows]);
});


Flight::route('POST /SubhVF/negocioAutomatico', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    // Obtenemos el usu_id directamente del administrador actual autenticado
    $usu_id = intval(
        $administrador_actual['usu_id'] ?? 0
    );

    $nombre_negocio = trim(
        $d['nombre_negocio'] ?? ''
    );

    $slug = trim(
        $d['slug'] ?? ''
    );

    $mercado_id = 3;

    $celular_yape = trim(
        $d['celular_yape'] ?? ''
    );

    if($usu_id <= 0){

        Flight::json([
            'status'=>'error',
            'msg'=>'No se pudo identificar el usuario autenticado (usu_id inválido)'
        ],400);

        return;
    }

    if(!$nombre_negocio){

        Flight::json([
            'status'=>'error',
            'msg'=>'nombre_negocio requerido'
        ],400);

        return;
    }

    if($mercado_id <= 0){

        Flight::json([
            'status'=>'error',
            'msg'=>'mercado_id requerido'
        ],400);

        return;
    }

    // Verificar si el usuario ya administra un negocio activo
    $existe_neg_usuario = DB::queryFirstField("
        SELECT nx.negxusu_id 
        FROM reg_negxusu nx 
        INNER JOIN reg_neg n ON n.neg_id = nx.neg_id 
        WHERE nx.usu_id = %i AND nx.is_activo = 1 AND nx.borrado_el IS NULL AND (n.borrado_el IS NULL)
        LIMIT 1
    ", $usu_id);

    if($existe_neg_usuario){
        Flight::json([
            'status'=>'error',
            'msg'=>'Esta cuenta ya administra un negocio'
        ],400);
        return;
    }

    DB::startTransaction();

    try {

        $now = date('Y-m-d H:i:s');
        $fecha_fin_premium = date('Y-m-d', strtotime('+1 year'));

        $usuario = DB::queryFirstRow("

            SELECT usu_id

            FROM reg_usu

            WHERE usu_id = %i

            LIMIT 1

        ", $usu_id);

        if(!$usuario){

            throw new Exception(
                'Usuario no encontrado'
            );
        }

        $rubro = [
            'rubro_id' => 1
        ];

        do {

            $cod_neg =
                'NEG'
                . strtoupper(
                    substr(
                        md5(
                            uniqid()
                        ),
                        0,
                        7
                    )
                );

            $existe_cod =
                DB::queryFirstField("

                    SELECT neg_id

                    FROM reg_neg

                    WHERE cod_neg=%s

                    LIMIT 1

                ", $cod_neg);

        } while($existe_cod);

        DB::insert(
            'reg_neg',
            [

                'cod_neg' =>
                    $cod_neg,

                'nombre' =>
                    $nombre_negocio,

                'descripcion' =>
                    ucfirst(Lorem::ipsum(rand(8, 12))),

                'mercado_id' =>
                    $mercado_id,

                'puesto' =>
                    'P-' . rand(1,999),

                'ciudad' =>
                    'Lima',

                'provincia' =>
                    'Lima',

                 'slug' =>
                            $slug,                    

                'departamento' =>
                    'Lima',

                'direccion' =>
                    'Mercado Regentis',

                'celular_informes' =>
                    $celular_yape,

                'img_logo' =>
                    'https://barsi-img.b-cdn.net/recursos/logo-regentis.png',

                'lista_yape' => json_encode(
                    [$celular_yape],
                    JSON_UNESCAPED_UNICODE
                ),

                'is_activo' => 1,

                'is_validado' => 1,

                'fecha_fin_premium' =>
                    $fecha_fin_premium,

                'fecha_creacion' =>
                    $now

            ]
        );

        $neg_id = DB::insertId();

        DB::insert(
            'reg_rubroxneg',
            [

                'neg_id' =>
                    $neg_id,

                'rubro_id' =>
                    $rubro['rubro_id'],

                'is_activo' => 1

            ]
        );

        // ==========================================
        // 1. POBLAR TABLA: reg_slider (Usando Lorem::ipsum)
        // ==========================================
        $sliders = [
            [
                'img' => 'https://barsi-img.b-cdn.net/regx/2NXpIc.jpg',
                'titulo_superior' => ucfirst(Lorem::ipsum(rand(4, 7))),
                'descripcion' => ucfirst(Lorem::ipsum(rand(10, 15))),
                'orden' => 1,
                'is_visible' => 1
            ],
            [
                'img' => 'https://barsi-img.b-cdn.net/regx/4O13Ox.jpg',
                'titulo_superior' => ucfirst(Lorem::ipsum(rand(4, 7))),
                'descripcion' => ucfirst(Lorem::ipsum(rand(10, 15))),
                'orden' => 2,
                'is_visible' => 1
            ],
            [
                'img' => 'https://barsi-img.b-cdn.net/regx/4O13Ox.jpg',
                'titulo_superior' => ucfirst(Lorem::ipsum(rand(4, 7))),
                'descripcion' => ucfirst(Lorem::ipsum(rand(10, 15))),
                'orden' => 3,
                'is_visible' => 1
            ],
            [
                'img' => 'https://barsi-img.b-cdn.net/regx/di2nrF.jpg',
                'titulo_superior' => ucfirst(Lorem::ipsum(rand(4, 7))),
                'descripcion' => ucfirst(Lorem::ipsum(rand(10, 15))),
                'orden' => 4,
                'is_visible' => 1
            ]
        ];

        foreach($sliders as $slider){
            DB::insert(
                'reg_slider',
                [
                    'neg_id'          => $neg_id,
                    'img'             => $slider['img'],
                    'titulo_superior' => $slider['titulo_superior'],
                    'descripcion'     => $slider['descripcion'],
                    'orden'           => $slider['orden'],
                    'is_visible'      => $slider['is_visible'],
                    'fecha_creacion'  => $now
                ]
            );
        }

        // ==========================================
        // 2. POBLAR TABLA: reg_cat_pag_web, reg_subcat_pag_web y reg_item_pag_web
        // ==========================================
        
        DB::insert(
            'reg_cat_pag_web',
            [
                'neg_id'    => $neg_id,
                'titulo'    => 'pag_web',
                'clave_txt' => 'txt_pag_web',
                'is_visible'=> 1,
                'orden'     => 1
            ]
        );
        $cat_pag_web_id = DB::insertId();

        $subcategorias = [
            [
                'titulo' => 'marquesina',
                'clave_txt' => 'txt_marquesina',
                'orden' => 1,
                'items' => [
                    ['titulo' => ucfirst(Lorem::ipsum(rand(2, 3))), 'clave_txt' => null, 'contenido' => ucfirst(Lorem::ipsum(rand(2, 3))), 'orden' => 1, 'is_visible' => 1],
                    ['titulo' => ucfirst(Lorem::ipsum(rand(2, 3))), 'clave_txt' => null, 'contenido' => ucfirst(Lorem::ipsum(rand(2, 3))), 'orden' => 2, 'is_visible' => 1],
                    ['titulo' => ucfirst(Lorem::ipsum(rand(2, 3))), 'clave_txt' => null, 'contenido' => ucfirst(Lorem::ipsum(rand(2, 3))), 'orden' => 3, 'is_visible' => 1],
                    ['titulo' => ucfirst(Lorem::ipsum(rand(2, 3))), 'clave_txt' => null, 'contenido' => ucfirst(Lorem::ipsum(rand(2, 3))), 'orden' => 4, 'is_visible' => 1],
                    ['titulo' => ucfirst(Lorem::ipsum(rand(2, 3))), 'clave_txt' => null, 'contenido' => ucfirst(Lorem::ipsum(rand(2, 3))), 'orden' => 5, 'is_visible' => 1]
                ]
            ],
            [
                'titulo' => 'meta',
                'clave_txt' => 'txt_meta',
                'orden' => 2,
                'items' => [
                    ['titulo' => 'meta_description', 'clave_txt' => 'txt_meta_description', 'contenido' => ucfirst(Lorem::ipsum(rand(8, 12))), 'orden' => 1, 'is_visible' => 1],
                    ['titulo' => 'meta_keywords', 'clave_txt' => 'txt_meta_keywords', 'contenido' => ucfirst(Lorem::ipsum(rand(8, 12))), 'orden' => 2, 'is_visible' => 1],
                    ['titulo' => 'Quien soy', 'clave_txt' => 'txt_nosotros', 'contenido' => '<p>' . ucfirst(Lorem::ipsum(rand(20, 35))) . '</p>', 'orden' => 3, 'is_visible' => 1],
                    ['titulo' => 'tiktok', 'clave_txt' => 'txt_tiktok', 'contenido' => 'https://www.tiktok.com/@' . strtolower(Lorem::ipsum(1)), 'orden' => 4, 'is_visible' => 1],
                    ['titulo' => 'facebook', 'clave_txt' => 'txt_facebook', 'contenido' => 'https://www.facebook.com/' . strtolower(Lorem::ipsum(1)), 'orden' => 5, 'is_visible' => 1],
                    ['titulo' => 'whatsapp', 'clave_txt' => 'txt_whatsapp', 'contenido' => "Hola {$nombre_negocio}, los contacto desde su página web oficial para consultar por sus productos de estilo japonés (peluches, cosméticos, papelería y más).", 'orden' => 6, 'is_visible' => 1],
                    ['titulo' => 'visitas', 'clave_txt' => 'txt_visitas', 'contenido' => '1', 'orden' => 7, 'is_visible' => 1]
                ]
            ]
        ];

        foreach($subcategorias as $subcat){
            DB::insert(
                'reg_subcat_pag_web',
                [
                    'cat_pag_web_id' => $cat_pag_web_id,
                    'titulo'         => $subcat['titulo'],
                    'clave_txt'      => $subcat['clave_txt'],
                    'orden'          => $subcat['orden'],
                    'is_visible'     => 1
                ]
            );

            $subcat_pag_web_id = DB::insertId();

            foreach($subcat['items'] as $item){
                DB::insert(
                    'reg_item_pag_web',
                    [
                        'subcat_pag_web_id' => $subcat_pag_web_id,
                        'titulo'            => $item['titulo'],
                        'clave_txt'         => $item['clave_txt'],
                        'contenido'         => $item['contenido'],
                        'orden'             => $item['orden'],
                        'is_visible'        => $item['is_visible']
                    ]
                );
            }
        }

        $categorias = [

            [
                'name'=>ucfirst(Lorem::ipsum(2)),
                'icon'=>'🛒'
            ],

            [
                'name'=>ucfirst(Lorem::ipsum(2)),
                'icon'=>'🛍️'
            ]

        ];

        $productos_creados = [];

        foreach($categorias as $cat){

            DB::insert(
                'pos_category',
                [

                    'name' =>
                        $cat['name'],

                    'icon' =>
                        $cat['icon'],

                    'brief' =>
                        ucfirst(Lorem::ipsum(6)),

                    'color' =>
                        '#FD7635',

                    'priority' =>
                        rand(1,50),

                    'neg_id' =>
                        $neg_id,

                    'is_activo' => 1,

                    'clave_txt' =>
                        strtolower(
                            str_replace(
                                ' ',
                                '-',
                                $cat['name']
                            )
                        ),

                    'img' =>
                        'https://barsi-img.b-cdn.net/recursos/ffc1.png'

                ]
            );

            $category_id =
                DB::insertId();

            $productos = [];

            if($cat['name'] == $cat['name']){

                $productos = [

                    [
                        'name' => ucfirst(Lorem::ipsum(3)),
                        'price' => 10.50,
                        'stock' => 20
                    ],

                    [
                        'name' => ucfirst(Lorem::ipsum(3)),
                        'price' => 15.90,
                        'stock' => 15
                    ]

                ];

            }

            foreach($productos as $p){

                DB::insert(
                    'pos_product',
                    [

                        'cod_producto' =>
                            'AUTO_' . uniqid(),

                        'name' =>
                            $p['name'],

                        'tipo_producto' =>
                            'ABARROTES',

                        'marca_des' =>
                            'GENERICO',

                        'price' =>
                            $p['price'],

                        'description' =>
                            ucfirst(Lorem::ipsum(8)),

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
                'Tienda creada correctamente',

            'neg_id' =>
                $neg_id

        ]);

    } catch(Throwable $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' =>
                $e->getMessage(),

            'line' =>
                $e->getLine(),

            'file' =>
                $e->getFile()

        ],500);

    }

});