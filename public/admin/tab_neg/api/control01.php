<?php
Flight::route('GET /neg/inicio', function () {
    include DEFINITION;
    autentificar_administrador();    
    
    ob_start();
    perso::start();
    perso::favicon();
    perso::jquery2();
    perso::jquery_ui();
    perso::vuejs2();
    perso::apprise();
    perso::vue_select();
    perso::font_awesome();
    perso::js();
    perso::block_ui();
    perso::perso();
    perso::datatables();
    perso::global_env($GLOBALS['apphost'], $GLOBALS['varhost']);
    $head_extra = ob_get_clean();

    $m = new Mustache();
    
    $template = file_get_contents(VARPATH . '/public/admin/tab_neg/neg.html');
    
    $partials = [
        'head'             => file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/head.html'),
        'navbar_negocios'  => file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/navbar_negocios.html'),
        'modal_negocio'    => file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/modal_negocio.html'),
        'modal_mercados'   => file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/modal_mercados.html'),
        'modal_cat_mercado'=> file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/modal_cat_mercado.html'),
        'modal_propietario'=> file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/modal_propietario.html'),
        'modal_rubros'     => file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/modal_rubros.html'),
        'modal_logo'       => file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/modal_logo.html'),
        'scripts'          => file_get_contents(VARPATH . '/public/admin/tab_neg/comp_neg/scripts.html')
    ];

    ob_start();
    include VARPATH . "/public/admin/menu.php";
    $menu_html = ob_get_clean();

    ob_start();
    include VARPATH . "/public/admin/footer.php";
    $footer_html = ob_get_clean();

    // Obtenemos el título del sitio desde la variable global $varhost o definición
    $titulo_sitio = vari('titulo_sitio');

    echo $m->render($template, [
        'head_extra'  => $head_extra,
        'menu_html'   => $menu_html,
        'footer_html' => $footer_html,
        'varhost'     => $varhost,
        'titulo_sitio'=> $titulo_sitio
    ], $partials);
});

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
                n.is_activo,
                n.celular_informes,
                n.direccion,
                n.fecha_fin_premium
            FROM reg_neg n
            WHERE n.borrado_el IS NULL
            ORDER BY n.neg_id DESC
        ");
        foreach($rows as &$r){
            $rubros = DB::query("
                SELECT rxn.rubroxneg_id, rxn.neg_id, rxn.rubro_id, rxn.is_activo, rr.nombre, rr.icono
                FROM reg_rubroxneg rxn
                INNER JOIN reg_rubro rr ON rr.rubro_id = rxn.rubro_id
                WHERE rxn.neg_id = %i AND rxn.is_activo = 1 AND rr.is_activo = 1
                ORDER BY rr.nombre ASC
            ", $r['neg_id']);
            $r['rubros_obj'] = $rubros;
            $r['rubros'] = implode(', ', array_map(fn($x)=>$x['nombre'], $rubros));

            $prop = DB::queryFirstRow("
                SELECT u.cod_usu, u.nombres_apellidos
                FROM reg_negxusu nx
                INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
                WHERE nx.neg_id = %i AND nx.is_activo = 1 AND nx.borrado_el IS NULL
                ORDER BY nx.negxusu_id DESC
                LIMIT 1
            ", $r['neg_id']);
            $r['cod_usu'] = $prop ? $prop['cod_usu'] : '';
            $r['nombres_apellidos'] = $prop ? $prop['nombres_apellidos'] : '';
        }
        Flight::json(['status'=>'ok', 'data'=>$rows]);
    } catch (Exception $e) {
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /neg/crear', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $nombre = trim($data['nombre'] ?? '');
        $puesto = trim($data['puesto'] ?? '');
        $mercado_id = intval($data['mercado_id'] ?? 0);

        if ($nombre === '') { Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return; }
        if ($puesto === '') { Flight::json(['status'=>'error','msg'=>'Puesto requerido'],400); return; }
        if ($mercado_id <= 0) { Flight::json(['status'=>'error','msg'=>'Mercado requerido'],400); return; }

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
            $rubros = $data['rubros'] ?? [];
            if(is_array($rubros)){
                foreach($rubros as $rubro_id){
                    $rubro_id = intval($rubro_id);
                    if($rubro_id <= 0) continue;
                    DB::insert('reg_rubroxneg',[
                        'neg_id' => $neg_id,
                        'rubro_id' => $rubro_id,
                        'is_activo' => 1
                    ]);
                }
            }
            DB::commit();
            Flight::json(['status'=>'ok', 'neg_id'=>$neg_id]);
        } catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    } catch (Exception $e) {
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /neg/editar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        $nombre = trim($data['nombre'] ?? '');
        $puesto = trim($data['puesto'] ?? '');
        $mercado_id = intval($data['mercado_id'] ?? 0);
        $celular_informes = trim($data['celular_informes'] ?? '');
        $direccion = trim($data['direccion'] ?? '');
        $img_logo = trim($data['img_logo'] ?? '');
        $fecha_fin_premium = trim($data['fecha_fin_premium'] ?? '');

        if ($neg_id <= 0) { Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }
        if ($nombre === '') { Flight::json(['status'=>'error','msg'=>'Nombre requerido'],400); return; }
        if ($puesto === '') { Flight::json(['status'=>'error','msg'=>'Puesto requerido'],400); return; }
        if ($mercado_id <= 0) { Flight::json(['status'=>'error','msg'=>'Mercado requerido'],400); return; }

        DB::startTransaction();
        try {
            DB::update('reg_neg',[
                'nombre' => $nombre,
                'puesto' => $puesto,
                'mercado_id' => $mercado_id,
                'celular_informes' => ($celular_informes === '' ? null : $celular_informes),
                'direccion' => ($direccion === '' ? null : $direccion),
                'img_logo' => ($img_logo === '' ? null : $img_logo),
                'fecha_fin_premium' => ($fecha_fin_premium === '' ? null : $fecha_fin_premium)
            ],"neg_id=%i",$neg_id);

            DB::delete('reg_rubroxneg', "neg_id=%i", $neg_id);
            $rubros = $data['rubros'] ?? [];
            if(is_array($rubros)){
                foreach($rubros as $rubro_id){
                    $rubro_id = intval($rubro_id);
                    if($rubro_id <= 0) continue;
                    DB::insert('reg_rubroxneg',[
                        'neg_id' => $neg_id,
                        'rubro_id' => $rubro_id,
                        'is_activo' => 1
                    ]);
                }
            }
            DB::commit();
            Flight::json(['status'=>'ok']);
        } catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    } catch (Exception $e) {
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /neg/logo/actualizar', function() {
    autentificar_administrador();
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        $img_logo = trim($data['img_logo'] ?? '');

        if ($neg_id <= 0) { Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }
        if ($img_logo === '') { Flight::json(['status'=>'error','msg'=>'URL de logo requerida'],400); return; }

        DB::update('reg_neg', [
            'img_logo' => $img_logo
        ], "neg_id=%i", $neg_id);

        Flight::json(['status'=>'ok']);
    } catch (Exception $e) {
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /neg/eliminar', function() {
    try {
        DB::query("SET NAMES 'utf8mb4'");
        $data = Flight::request()->data->getData();
        $neg_id = intval($data['neg_id'] ?? 0);
        if ($neg_id <= 0) { Flight::json(['status'=>'error','msg'=>'neg_id inválido'],400); return; }

        DB::startTransaction();
        try {
            DB::update('reg_neg', ['borrado_el' => date('Y-m-d H:i:s'), 'is_activo' => 0], "neg_id=%i", $neg_id);
            DB::update('reg_negxusu', ['is_activo' => 0], "neg_id=%i", $neg_id);
            DB::commit();
            Flight::json(['status'=>'ok']);
        } catch(Exception $e){
            DB::rollback();
            throw $e;
        }
    } catch (Exception $e) {
        Flight::json(['status'=>'error', 'msg'=>$e->getMessage()],500);
    }
});