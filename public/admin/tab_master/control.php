<?php

/* =========================================================
   HELPERS INTERNOS
========================================================= */

function get_neg_id_from_get() {
    return intval($_GET['neg_id'] ?? 0);
}

function get_neg_id_from_data($data) {
    return intval($data['neg_id'] ?? 0);
}


/* =========================================================
   GENERAR APP DESDE TEMPLATE (RUBRO / MODULO / SCREEN)
========================================================= */

Flight::route('POST /api/app/generar-desde-template', function(){

    include DEFINITION;

    try {

        db_utf8();
        $data = req_data();

        $neg_id = get_neg_id_from_data($data);
        $rubro  = $data['rubro'] ?? 'retail';
        $enabled = $data['enabled_screens'] ?? [];

        if ($neg_id <= 0) {
            api_error("neg_id requerido");
            return;
        }

        if (!is_array($enabled)) {
            api_error("enabled_screens debe ser array");
            return;
        }


        $template_path = VARPATH . '/admin/tab_master/master_template.json';

        if (!file_exists($template_path)) {
            api_error("No existe master_template.json");
            return;
        }

        $template = json_decode(file_get_contents($template_path), true);

        if (!isset($template[$rubro])) {
            api_error("Rubro no existe en template");
            return;
        }

        $rubro_data = $template[$rubro];

        /* ----------------------------------
           INSERT SCREENS
        ---------------------------------- */

        foreach ($rubro_data as $modulo => $screens) {

            if (!is_array($screens)) continue;

            // 👉 obtener titulo del modulo
            $titulo_modulo = $screens['titulo'] ?? null;

            foreach ($screens as $screen_code => $json_def) {

                // 🚫 ignorar clave titulo
                if ($screen_code === 'titulo') continue;

                if (!empty($enabled) && !in_array($screen_code, $enabled)) {
                    continue;
                }

                DB::insert('deux_screen', [
                    'neg_id'   => $neg_id,
                    'rubro'    => $rubro,
                    'modulo'   => $modulo,
                    'titulo'   => $titulo_modulo,
                    'nombre'   => $screen_code,
                    'json_def' => json_encode($json_def, JSON_UNESCAPED_UNICODE)
                ]);
            }
        }

        api_ok([
            "msg" => "App generada correctamente",
            "rubro" => $rubro
        ]);

    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }

});


/* =========================================================
   ACTUALIZAR SCREENS (SYNC TEMPLATE)
========================================================= */

Flight::route('POST /api/actualizarScreen', function(){

include DEFINITION;

try {

    db_utf8();
    $data = req_data();

    $neg_id = intval($data['neg_id'] ?? 0);
    $rubro  = $data['rubro'] ?? 'retail';
    $modo   = $data['modo'] ?? 'update';

    if ($neg_id <= 0) {
        api_error("neg_id requerido");
        return;
    }

    $template_path = VARPATH . '/admin/tab_master/master_template.json';

    if (!file_exists($template_path)) {
        api_error("No existe master_template.json");
        return;
    }

    $template = json_decode(file_get_contents($template_path), true);

    if (!isset($template[$rubro])) {
        api_error("Rubro no existe");
        return;
    }

    $rubro_data = $template[$rubro];

    $template_keys = [];

    foreach ($rubro_data as $modulo => $screens) {

        if (!is_array($screens)) continue;

        /* ==================================
           🔥 DETECTAR TITULO SEGURO
        ================================== */

        $titulo_modulo = null;

        if (isset($screens['titulo']) && is_string($screens['titulo'])) {
            $titulo_modulo = trim($screens['titulo']);
        }

        /* DEBUG OPCIONAL
        echo $modulo . " => " . $titulo_modulo . "\n";
        */

        /* ==================================
           LIMPIAR TITULO DEL ARRAY
        ================================== */

        $screens_limpio = $screens;

        if (isset($screens_limpio['titulo'])) {
            unset($screens_limpio['titulo']);
        }

        /* ==================================
           RECORRER SCREENS
        ================================== */

        foreach ($screens_limpio as $screen_code => $json_def) {

            $template_keys[] = $screen_code;

            $json_clean = json_encode($json_def, JSON_UNESCAPED_UNICODE);

            $exists = DB::queryFirstField("
                SELECT COUNT(*)
                FROM deux_screen
                WHERE neg_id=%i AND nombre=%s
            ", $neg_id, $screen_code);

            if ($exists) {

                DB::update('deux_screen', [
                    'json_def' => $json_clean,
                    'rubro'    => $rubro,
                    'modulo'   => $modulo,
                    'titulo'   => $titulo_modulo
                ], "
                    neg_id=%i AND nombre=%s
                ", $neg_id, $screen_code);

            } else {

                DB::insert('deux_screen', [
                    'neg_id'   => $neg_id,
                    'rubro'    => $rubro,
                    'modulo'   => $modulo,
                    'titulo'   => $titulo_modulo,
                    'nombre'   => $screen_code,
                    'json_def' => $json_clean
                ]);
            }
        }
    }

    /* ==================================
       SYNC DELETE
    ================================== */

    if ($modo === 'sync') {

        $db = DB::query("
            SELECT nombre
            FROM deux_screen
            WHERE neg_id=%i
        ", $neg_id);

        foreach ($db as $row) {

            if (!in_array($row['nombre'], $template_keys)) {

                DB::query("
                    DELETE FROM deux_screen
                    WHERE neg_id=%i AND nombre=%s
                ", $neg_id, $row['nombre']);
            }
        }
    }

    api_ok([
        "msg" => "Screens sincronizadas OK"
    ]);

} catch (Exception $e) {
    api_error($e->getMessage(), 500);
}


});



/* =========================================================
   GET SCREEN
========================================================= */

Flight::route('GET /api/screen/@code', function($code){

    try {

        db_utf8();

        $neg_id = intval($_GET['neg_id'] ?? 0);
        $usu_id = intval($_GET['usu_id'] ?? 0);
        $tipo_usuario = $_GET['tipoxusu'] ?? null;

        if ($neg_id <= 0) {
            api_error("neg_id requerido");
            return;
        }

        /* ----------------------------------
           TIPO USUARIO
        ---------------------------------- */

        if ($tipo_usuario) {

            $tipo_usuario = strtoupper(trim($tipo_usuario));

        } else if ($usu_id == 0) {

            $tipo_usuario = "CONSUMIDOR";

        } else {

            $tipo_usuario_db = DB::queryFirstField("
                SELECT t.descripcion
                FROM reg_usu u
                JOIN reg_negxusu nxu ON nxu.usu_id = u.usu_id
                JOIN reg_tipoxusu t ON t.tipoxusu_id = u.tipoxusu_id
                WHERE nxu.neg_id = %i
                  AND u.usu_id = %i
                  AND nxu.is_activo = 1
                LIMIT 1
            ", $neg_id, $usu_id);

            $tipo_usuario = $tipo_usuario_db ?: "CONSUMIDOR";
        }

        /* ----------------------------------
           SCREEN
        ---------------------------------- */

        $screen = DB::queryFirstRow("
            SELECT json_def, titulo, modulo, rubro
            FROM deux_screen
            WHERE neg_id=%i AND nombre=%s
            LIMIT 1
        ", $neg_id, $code);

        if (!$screen) {
            api_error("Pantalla no encontrada");
            return;
        }

        $screen_json = json_decode($screen['json_def'], true);

        if (!is_array($screen_json)) {
            api_error("json_def inválido");
            return;
        }

        /* ----------------------------------
           FILTRAR ROLES
        ---------------------------------- */

        if (isset($screen_json['roles'])) {

            $roles = $screen_json['roles'];

            if (isset($roles[$tipo_usuario])) {
                $screen_json['roles'] = $roles[$tipo_usuario];
            } else {
                $screen_json['roles'] = [];
            }
        }

        /* ----------------------------------
           🔥 RESOLVER DATA SOURCE (CLAVE)
        ---------------------------------- */

        /* ----------------------------------
           🔥 RESOLVER DATA SOURCE + RENDER ITEMS
        ---------------------------------- */

        if (isset($screen_json['data']['source'])) {

            $source = $screen_json['data']['source'];
            $params = $screen_json['data']['params'] ?? [];

            // 🔥 REEMPLAZAR VARIABLES
            foreach ($params as $k => $v) {
                if (!is_string($v)) continue;

                $v = str_replace('{{neg_id}}', $neg_id, $v);
                $v = str_replace('{{usu_id}}', $usu_id, $v);

                $params[$k] = $v;
            }

            // 🔥 INYECTAR SI NO EXISTEN
            if (!isset($params['neg_id'])) $params['neg_id'] = $neg_id;
            if (!isset($params['usu_id'])) $params['usu_id'] = $usu_id;

            $query = http_build_query($params);

            $base = "http://localhost:84/bd_regentis";
            $url  = $base . $source . '?' . $query;

            $response = @file_get_contents($url);

            $items = [];

            if ($response !== false) {

                $json = json_decode($response, true);

                if (isset($json['data']) && is_array($json['data'])) {
                    $items = $json['data'];
                }
            }

            $screen_json['data']['items'] = $items;

            /* ----------------------------------
               🔥 BUILD RENDER ITEMS
            ---------------------------------- */

            $render_items = [];

            $layout_fields = $screen_json['layout']['fields'] ?? [];

            foreach ($items as $item) {

                $fields_render = [];

                foreach ($layout_fields as $field) {

                    $name  = $field['name'] ?? '';
                    $label = $field['label'] ?? $name;

                    $value = $item[$name] ?? null;

                    $fields_render[] = [
                        "name"  => $name,
                        "label" => $label,
                        "value" => $value,
                        "component" => $field['component'] ?? 'TextView'
                    ];
                }

                $render_items[] = [
                    "raw" => $item,
                    "fields" => $fields_render
                ];
            }

            $screen_json['data']['render_items'] = $render_items;
        }

        /* ----------------------------------
           NEGOCIO
        ---------------------------------- */

        $negocio = DB::queryFirstRow("
            SELECT *
            FROM reg_neg
            WHERE neg_id=%i
        ", $neg_id);

        if (!$negocio) {
            api_error("Negocio no encontrado");
            return;
        }

        /* ----------------------------------
           THEME
        ---------------------------------- */

        $theme = DB::queryFirstRow("
            SELECT *
            FROM reg_theme
            WHERE theme_id=%i
        ", $negocio['theme_id'] ?? 0);

        if (!$theme) {
            $theme = [
                "color_primario"   => "#FD7635",
                "color_secundario" => "#f7efec",
                "fuente"           => "Montserrat",
                "layout_tipo"      => "standard",
                "logo"             => null,
                "portada"          => null
            ];
        }

        /* ----------------------------------
           RESPONSE FINAL
        ---------------------------------- */

        api_ok([
            "screen"  => $screen_json,
            "meta" => [
                "titulo" => $screen['titulo'],
                "modulo" => $screen['modulo'],
                "rubro"  => $screen['rubro']
            ],
            "theme"   => $theme,
            "negocio" => [
                "neg_id" => $negocio['neg_id'],
                "nombre" => $negocio['nombre'],
                "logo"   => $negocio['img_logo']
            ],
            "user" => [
                "usu_id"   => $usu_id,
                "tipoxusu" => $tipo_usuario
            ]
        ]);

    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }

});

Flight::route('GET /app/screenxusu', function(){

    include DEFINITION;

    try {

        db_utf8();

        $usu_id = intval($_GET['usu_id'] ?? 0);
        $neg_id = intval($_GET['neg_id'] ?? 0);

        if ($neg_id <= 0) {
            api_error("neg_id requerido");
            return;
        }

        /* ==================================
           OBTENER TIPO USUARIO
        ================================== */

        if ($usu_id == 0) {

            $tipoxusu_id = DB::queryFirstField("
                SELECT tipoxusu_id
                FROM reg_tipoxusu
                WHERE UPPER(descripcion) = 'CONSUMIDOR'
                LIMIT 1
            ");

            $tipoxusu_desc = 'CONSUMIDOR';

        } else {

            $row_tipo = DB::queryFirstRow("
                SELECT 
                    u.tipoxusu_id,
                    t.descripcion
                FROM reg_usu u
                JOIN reg_tipoxusu t ON t.tipoxusu_id = u.tipoxusu_id
                JOIN reg_negxusu nxu ON nxu.usu_id = u.usu_id
                WHERE u.usu_id = %i
                  AND nxu.neg_id = %i
                  AND nxu.is_activo = 1
                LIMIT 1
            ", $usu_id, $neg_id);

            $tipoxusu_id   = $row_tipo['tipoxusu_id'] ?? null;
            $tipoxusu_desc = $row_tipo['descripcion'] ?? '';
        }

        if (!$tipoxusu_id) {
            api_error("Tipo de usuario no encontrado");
            return;
        }

        /* ==================================
           INFO NEGOCIO
        ================================== */

        $negocio = DB::queryFirstRow("
            SELECT 
                neg_id,
                nombre,
                img_logo AS logo
            FROM reg_neg
            WHERE neg_id = %i
            LIMIT 1
        ", $neg_id);

        /* ==================================
           OBTENER DATA RELACIONAL
        ================================== */

        $rows = DB::query("
            SELECT 
                r.rubro_id,
                r.nombre AS rubro,

                m.modulo_id,
                m.nombre AS modulo,
                m.screen_id_inicio,

                si.nombre AS screen_inicio_nombre,

                s.screen_id,
                s.nombre,
                s.titulo

            FROM deux_screen s

            INNER JOIN deux_modulo m 
                ON m.modulo_id = s.modulo_id

            INNER JOIN deux_rubro r 
                ON r.rubro_id = m.rubro_id

            LEFT JOIN deux_screen si 
                ON si.screen_id = m.screen_id_inicio

            INNER JOIN deux_tipousuxscreen ts 
                ON ts.screen_id = s.screen_id

            WHERE s.neg_id = %i
              AND ts.tipoxusu_id = %i

            ORDER BY r.orden, m.orden, s.nombre
        ", $neg_id, $tipoxusu_id);

        /* ==================================
           ARMAR ESTRUCTURA
        ================================== */

        $result = [];

        foreach ($rows as $row) {

            $rubro_id  = $row['rubro_id'];
            $modulo_id = $row['modulo_id'];

            // RUBRO
            if (!isset($result[$rubro_id])) {
                $result[$rubro_id] = [
                    "rubro_id" => $rubro_id,
                    "rubro"    => $row['rubro'],
                    "modulos"  => []
                ];
            }

            // MODULO
            if (!isset($result[$rubro_id]["modulos"][$modulo_id])) {

                $result[$rubro_id]["modulos"][$modulo_id] = [
                    "modulo_id"            => $modulo_id,
                    "modulo"               => $row['modulo'],
                    "screen_id_inicio"     => $row['screen_id_inicio'],
                    "screen_inicio_nombre" => $row['screen_inicio_nombre'],
                    "screens"              => []
                ];
            }

            // SCREEN
            $result[$rubro_id]["modulos"][$modulo_id]["screens"][] = [
                "screen_id" => $row['screen_id'],
                "nombre"    => $row['nombre'],
                "titulo"    => $row['titulo']
            ];
        }

        /* ==================================
           NORMALIZAR ARRAY
        ================================== */

        $final = [];

        foreach ($result as $rubro) {

            $modulos = [];

            foreach ($rubro["modulos"] as $modulo) {
                $modulo["screens"] = array_values($modulo["screens"]);
                $modulos[] = $modulo;
            }

            $rubro["modulos"] = $modulos;

            $final[] = $rubro;
        }

        /* ==================================
           RESPONSE
        ================================== */

        api_ok([
            "negocio" => $negocio,
            "user" => [
                "usu_id"    => $usu_id,
                "tipoxusu"  => $tipoxusu_desc,
                "tipoxusu_id" => $tipoxusu_id
            ],
            "data" => $final
        ]);

    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }

});