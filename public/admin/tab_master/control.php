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
   GENERAR APP DESDE TEMPLATE (NUEVO MODELO)
========================================================= */

Flight::route('POST /api/app/generar-desde-template', function(){

    include DEFINITION;

    try {

        db_utf8();
        $data = req_data();

        $neg_id = get_neg_id_from_data($data);
        $enabled = $data['enabled_screens'] ?? [];

        if ($neg_id <= 0) {
            api_error("neg_id requerido");
            return;
        }

        if (!is_array($enabled)) {
            api_error("enabled_screens debe ser un array");
            return;
        }

        global $path_public;

        $template_path = $path_public . '/admin/tab_master/master_template.json';

        if (!file_exists($template_path)) {
            api_error("No existe master_template.json");
            return;
        }

        $template = json_decode(file_get_contents($template_path), true);

        if (!is_array($template)) {
            api_error("Template inválido");
            return;
        }

        /* ----------------------------------
           CREAR SCREENS
        ---------------------------------- */

        foreach ($enabled as $screen_code) {

            if (!isset($template[$screen_code])) {
                continue;
            }

            DB::insert('deux_screen', [
                'neg_id'   => $neg_id,
                'nombre'   => $screen_code,
                'json_def' => json_encode($template[$screen_code], JSON_UNESCAPED_UNICODE)
            ]);
        }

        api_ok([
            "msg" => "App generada correctamente"
        ]);

    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }

});


/* =========================================================
   ACTUALIZAR SCREENS DESDE TEMPLATE
========================================================= */

Flight::route('POST /api/actualizarScreen', function(){

    include DEFINITION;

    try {

        db_utf8();
        $data = req_data();

        $neg_id = get_neg_id_from_data($data);
        $modo = $data['modo'] ?? 'update';

        if ($neg_id <= 0) {
            api_error("neg_id requerido");
            return;
        }

        global $path_public;

        $template_path = $path_public . '/admin/tab_master/master_template.json';

        if (!file_exists($template_path)) {
            api_error("No existe master_template.json");
            return;
        }

        $template = json_decode(file_get_contents($template_path), true);

        if (!is_array($template)) {
            api_error("Template inválido");
            return;
        }

        $screens_template = array_keys($template);

        $actualizadas = [];
        $insertadas = [];
        $eliminadas = [];

        foreach ($template as $screen_code => $json_def) {

            if (!is_array($json_def)) continue;

            $json_clean = json_encode($json_def, JSON_UNESCAPED_UNICODE);

            $exists = DB::queryFirstField("
                SELECT COUNT(*)
                FROM deux_screen
                WHERE neg_id = %i
                AND nombre = %s
            ", $neg_id, $screen_code);

            if ($exists > 0) {

                DB::update('deux_screen', [
                    'json_def' => $json_clean
                ], "
                    neg_id = %i
                    AND nombre = %s
                ", $neg_id, $screen_code);

                $actualizadas[] = $screen_code;

            } else {

                DB::insert('deux_screen', [
                    'neg_id'   => $neg_id,
                    'nombre'   => $screen_code,
                    'json_def' => $json_clean
                ]);

                $insertadas[] = $screen_code;
            }
        }

        if ($modo === 'sync') {

            $db_screens = DB::query("
                SELECT nombre
                FROM deux_screen
                WHERE neg_id = %i
            ", $neg_id);

            foreach ($db_screens as $row) {

                if (!in_array($row['nombre'], $screens_template)) {

                    DB::query("
                        DELETE FROM deux_screen
                        WHERE neg_id = %i
                        AND nombre = %s
                    ", $neg_id, $row['nombre']);

                    $eliminadas[] = $row['nombre'];
                }
            }
        }

        api_ok([
            "msg" => "Screens sincronizadas",
            "actualizadas" => $actualizadas,
            "insertadas" => $insertadas,
            "eliminadas" => $eliminadas,
            "modo" => $modo
        ]);

    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }

});


/* =========================================================
   GET SCREEN (CORE DEL SISTEMA)
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
            SELECT json_def
            FROM deux_screen
            WHERE (neg_id = %i OR neg_id IS NULL)
              AND nombre = %s
            ORDER BY CASE WHEN neg_id=%i THEN 1 ELSE 2 END
            LIMIT 1
        ", $neg_id, $code, $neg_id);

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
           FEATURES POR ROL
        ---------------------------------- */

        if (isset($screen_json['features']) && is_array($screen_json['features'])) {

            $features_all = $screen_json['features'];

            if (isset($features_all[$tipo_usuario])) {
                $screen_json['features'] = $features_all[$tipo_usuario];
            } else {
                $screen_json['features'] = [];
            }
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