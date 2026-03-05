<?php
Flight::route('POST /ion/tk_cant_msg', function(){

    $req   = Flight::request()->data->getData();
    // dd($req);
    $uid   = $req['usu_id'];
    $zz    = $req['zz'];
    // dd($zz);
    //$zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    
    try {
        // 1) Charset
        DB::query("SET NAMES 'utf8mb4' COLLATE utf8mb4_unicode_ci");

        // 2) Base de imágenes (equivalente a vari('FOTO_FICH_MINI'))
        $pics_avatar = BUNNY_CDN_BASE . "/" . vari('FOTO_FICH_MINI') . "/";

        // 3) Listado de chats (igual al tuyo, sin cambios funcionales)
        $rows = DB::query("
            SELECT 
              ch.chat_id,
              ch.usu1_id,
              u1.sobrenombre AS usuario1,
              CONCAT(%s, u1.img_perfil) AS img_usu1,
              ch.usu2_id,
              u2.sobrenombre AS usuario2,
              CONCAT(%s, u2.img_perfil) AS img_usu2,
              ch.fecha_creacion,
              ch.is_visible,
              ch.is_visto_usu1_id,
              ch.is_visto_usu2_id,
              /* visto para el que consulta (@uid) */
              CASE 
                WHEN %i = ch.usu1_id THEN ch.is_visto_usu1_id
                WHEN %i = ch.usu2_id THEN ch.is_visto_usu2_id
                ELSE 0
              END AS mi_visto,
              /* visto para el otro */
              CASE 
                WHEN %i = ch.usu1_id THEN ch.is_visto_usu2_id
                WHEN %i = ch.usu2_id THEN ch.is_visto_usu1_id
                ELSE 0
              END AS su_visto,
              ch.ultimo_mensaje,
              ch.is_bloqueado
            FROM chat ch
            JOIN usu u1 ON u1.usu_id = ch.usu1_id
            JOIN usu u2 ON u2.usu_id = ch.usu2_id
            WHERE ch.usu1_id = %i OR ch.usu2_id = %i
            ORDER BY ch.fecha_creacion DESC
        ",
          $pics_avatar, $pics_avatar,
          $uid, $uid,  // para mi_visto
          $uid, $uid,  // para su_visto
          $uid, $uid   // filtro
        );

        // 4) Contador global de no leídos (absorbe /chat/nuevosMensajes/@uid)
        $nuevos = DB::queryFirstField("
            SELECT COALESCE(SUM(
              CASE
                WHEN %i = ch.usu1_id THEN (1 - ch.is_visto_usu1_id)
                WHEN %i = ch.usu2_id THEN (1 - ch.is_visto_usu2_id)
                ELSE 0
              END
            ), 0) AS mensajes_nuevos
            FROM chat ch
            WHERE ch.usu1_id = %i OR ch.usu2_id = %i
        ", $uid, $uid, $uid, $uid);

        // 5) Respuesta unificada
        Flight::json([
            'estado'           => 'ok',
            'mensajes_nuevos'  => (int)$nuevos,
            'data'             => $rows
        ]);
    } catch (Exception $ex) {
        Flight::json(['estado' => 'error', 'mensaje' => $ex->getMessage()], 500);
    }

});
