<?php

Flight::route('POST /render/chat/listar', function () {

    $req   = Flight::request()->data->getData();
    // dd($req);
    $uid   = $req['usu_id'];
    $zz    = $req['zz'];
    // dd($zz);
    // $zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    $pics_avatar = vari('PICS_AVATAR_MINI');
    $rows = DB::query("
        SELECT 
          ch.chat_id,
          ch.usu1_id,
          u1.sobrenombre AS usuario1,
          CONCAT('$pics_avatar', u1.img_perfil) as img_usu1,  
          ch.usu2_id,
          u2.sobrenombre AS usuario2,
          CONCAT('$pics_avatar', u2.img_perfil) as img_usu2,  
          ch.fecha_creacion,
          ch.is_visible,
          ch.is_visto_usu1_id,
          ch.is_visto_usu2_id,
          ch.ultimo_mensaje,
          ch.is_bloqueado
        FROM chat ch
        JOIN usu u1 ON u1.usu_id = ch.usu1_id
        JOIN usu u2 ON u2.usu_id = ch.usu2_id
        WHERE ch.usu1_id = %i OR ch.usu2_id = %i
        ORDER BY ch.fecha_creacion DESC
    ", $uid, $uid);
    Flight::json($rows);
});


Flight::route('POST /render/neg/listar', function () {

    $req   = Flight::request()->data->getData();
    // dd($req);
    $uid   = $req['usu_id'];
    $zz    = $req['zz'];
    // dd($zz);
    // $zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    DB::query("SET NAMES 'utf8'");
    $rows = DB::query('SELECT * FROM neg ORDER BY nombre');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});


/*--*/
/* ---- Mensajes de un chat ---- */
Flight::route('POST /render/msg/listar', function () {

    $req   = Flight::request()->data->getData();
    // dd($req);
    $cid   = $req['cid'];
    $uid   = $req['uid'];
    $zz    = $req['zz'];
    // dd($zz);
    // $zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    $url_img = BUNNY_CDN_BASE . '/' . rtrim(vari('FOTO_FICH_MAX'), '/');


    /* ── 1) Averiguamos quién es usu1 y usu2 ─────────────── */
    $chat = DB::queryFirstRow(
        "SELECT usu1_id, usu2_id
           FROM chat
          WHERE chat_id = %i",
        $cid
    );

    if (!$chat) {
        Flight::json(['error' => 'chat no encontrado'], 404);
        return;
    }

    /* ── 2) Marcamos SOLO el flag que corresponde ────────── */
    if ((int)$uid === (int)$chat['usu1_id']) {
        DB::update('chat', ['is_visto_usu1_id' => 1], 'chat_id = %i', $cid);
    } elseif ((int)$uid === (int)$chat['usu2_id']) {
        DB::update('chat', ['is_visto_usu2_id' => 1], 'chat_id = %i', $cid);
    } else {
        // El uid no pertenece al chat ⇒ no hacemos nada (o puedes devolver 403)
    }

    /* ── 3) Obtenemos los mensajes ───────────────────────── */
    $rows = DB::query(
        "SELECT
            m.msg_id,
            m.chat_id,
            m.rem_id,
            u.sobrenombre,
            m.contenido_rem,
            m.fecha_creacion,
            m.tipoxmsg_id,
            m.map_lat,
            m.map_lng,            
            CONCAT('$url_img', m.img) as img,  
            m.sticker_id,
            m.is_una_vista,
            m.dest_id,
            m.contenido_dest
         FROM msg AS m
         LEFT JOIN usu AS u ON u.usu_id = m.rem_id
         WHERE m.chat_id = %i
         ORDER BY m.msg_id DESC",
        $cid
    );

    /* ── 4) Respondemos ─────────────────────────────────── */
    Flight::json($rows);
});


/* ---- Crear Chat + Mensaje Inicial ---- */
Flight::route('POST /render/chat/crear', function () {

    // 1) Leer JSON del cliente
    $d = json_decode(Flight::request()->getBody(), true);

    $chat_id = intval($d['chat_id'] ?? 0);
    $rem_id  = intval($d['rem_id']  ?? 0);
    $texto   = trim($d['contenido_rem'] ?? '');

    if (!$chat_id || !$rem_id || $texto === '') {
        return Flight::json([
            'success' => false,
            'error'   => 'Faltan datos esenciales'
        ], 400);
    }

    // 2) Obtener datos del chat
    $chat = DB::queryFirstRow("
        SELECT *
        FROM chat
        WHERE chat_id = %i
    ", $chat_id);

    if (!$chat) {
        return Flight::json([
            'success' => false,
            'error'   => 'Chat no encontrado'
        ], 404);
    }

    $u1 = intval($chat['usu1_id']);
    $u2 = intval($chat['usu2_id']);

    // 3) Determinar destinatario
    $dest_id = ($rem_id === $u1) ? $u2 : $u1;

    $now = date('Y-m-d H:i:s');

    // 4) Insertar mensaje
    DB::insert('msg', [
        'chat_id'       => $chat_id,
        'rem_id'        => $rem_id,
        'dest_id'       => $dest_id,
        'contenido_rem' => $texto,
        'fecha_creacion'=> $now,
        'tipoxmsg_id'   => 1,
        'is_una_vista'  => 0
    ]);

    $msg_id = DB::insertId();

    // 5) Actualizar cabecera del chat
    DB::update('chat', [
        'ultimo_mensaje'        => $texto,
        'is_visible'            => 1,
        'is_bloqueado'          => 0,
        'last_seen_msg_id_u1'   => ($rem_id === $u1 ? $msg_id : $chat['last_seen_msg_id_u1']),
        'last_seen_msg_id_u2'   => ($rem_id === $u2 ? $msg_id : $chat['last_seen_msg_id_u2']),
        'is_visto_usu1_id'      => ($rem_id === $u1 ? 1 : 0),
        'is_visto_usu2_id'      => ($rem_id === $u2 ? 1 : 0)
    ], "chat_id=%i", $chat_id);

    // 6) Respuesta final
    Flight::json([
        'success' => true,
        'chat_id' => $chat_id,
        'msg_id'  => $msg_id
    ]);
});


Flight::route('POST /render/neg/listar', function () {
    DB::query("SET NAMES 'utf8'");
    $rows = DB::query('SELECT * FROM neg ORDER BY nombre');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});


Flight::route('POST /nflank/slider/listar', function () {
    include DEFINITION;
    $req   = Flight::request()->data->getData();
    // dd($req);
    $cid   = $req['cid'];
    $uid   = $req['uid'];
    $zz    = $req['zz'];
    // dd($zz);
    // $zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    $rows = DB::query("
        SELECT *,
               CONCAT('" . vari('PICS_SLIDER_FULL') . "/', img) AS img_thumb
        FROM slider
        ORDER BY slider_id DESC
    ");
    Flight::json($rows);
});


Flight::route('POST /nflank/estado/agrupados', function () {
    include DEFINITION;
    $req   = Flight::request()->data->getData();
    // dd($req);
    $cid   = $req['cid'];
    $uid   = $req['uid'];
    $zz    = $req['zz'];
    // dd($zz);
    // $zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    // --- Config desde tus variables ---
    $CDN_BASE   = BUNNY_CDN_BASE;        // p.ej. https://barsi-img.b-cdn.net
    $BUNNY_DIR  = trim(vari('ESTADO_MAX'), '/');      // p.ej. 'estados'
    $USU_DIR    = trim(vari('FOTO_FICH_MINI'), '/');          // p.ej. 'usu_t'

    // Bases finales
    $ESTADOS_BASE = $CDN_BASE . '/' . $BUNNY_DIR;            // https://.../estados
    $USU_BASE     = $CDN_BASE . '/' . $USU_DIR;              // https://.../usu_t

    // Optimizer (opcional)
    $MAX_FULL     = (int) (vari('IMG_FULL') ?: 1080);
    $MAX_MINI     = (int) (vari('IMG_MINI') ?: 360);
    $USE_OPTIMIZER = false; // pon true si activaste Bunny Optimizer en la Pull Zone

    $SUF_FULL = $USE_OPTIMIZER ? ("?width={$MAX_FULL}&quality=75") : "";
    $SUF_MINI = $USE_OPTIMIZER ? ("?width={$MAX_MINI}&quality=75") : "";

    // NOTA: aquí asumo que u.img_perfil guarda SOLO el filename (sin subcarpeta).
    // Si en tu Bunny los avatares estuvieran en usu_t/{usu_id}/archivo, cambia la línea de usu_mini:
    // CONCAT(%s, '/', u.usu_id, '/', COALESCE(NULLIF(u.img_perfil,''), 'default.jpg')) AS usu_mini,

    $rows = DB::query(
        "SELECT
            e.fich_id,
            u.usu_id,
            u.sobrenombre,
            CONCAT(%s, '/', COALESCE(NULLIF(u.img_perfil,''), 'default.jpg')) AS usu_mini,
            GROUP_CONCAT(CONCAT(%s, '/', f.usu_id, '/', e.img, %s)
                         ORDER BY e.fecha_creacion DESC SEPARATOR ';')       AS imagenes,
            GROUP_CONCAT(CONCAT(%s, '/', f.usu_id, '/', e.img, %s)
                         ORDER BY e.fecha_creacion DESC SEPARATOR ';')       AS imagenes_mini,
            IF(MAX(e.fecha_creacion) >= CONCAT(CURDATE(), ' 00:00:00'), 'verde', 'rojo') AS color
         FROM estado e
         JOIN fich f ON f.fich_id = e.fich_id
         JOIN usu  u ON u.usu_id  = f.usu_id
         WHERE e.is_visible = 1
           AND NOW() <= e.fecha_fin
         GROUP BY e.fich_id, u.usu_id, u.sobrenombre
         ORDER BY e.fich_id",
        $USU_BASE,
        $ESTADOS_BASE, $SUF_FULL,
        $ESTADOS_BASE, $SUF_MINI
    );

    Flight::json($rows);
});


Flight::route('POST /nflank/fich/listar/@usu_id', function ($usu_id) {
    include DEFINITION;
    $req   = Flight::request()->data->getData();
    // dd($req);
    $cid   = $req['cid'];
    $uid   = $req['uid'];
    $zz    = $req['zz'];
    // dd($zz);
    // $zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    $CDN_BASE   = BUNNY_CDN_BASE; 

    diario($usu_id, 'inicio_sesion', null);

    $id = intval($usu_id);
    if ($id > 0) {
        try {
            $existe = DB::queryFirstField("SELECT 1 FROM usu WHERE usu_id=%i LIMIT 1", $id);
            if ($existe) {
                DB::update('usu',  ['fecha_ultimo_acceso' => DB::sqleval('NOW()')], "usu_id=%i", $id);
                DB::update('fich', ['fecha_ultimo_acceso' => DB::sqleval('NOW()')], "usu_id=%i", $id);
            }
        } catch (Exception $e) {}
    }

    $pic_fich = $CDN_BASE . "/" . trim(vari('FOTO_FICH_MAX'));
    $pic_usu  = $CDN_BASE . "/" . trim(vari('USU_FULL'));

    // dd($pic_fich);

    $fxHoyJoin = "
      LEFT JOIN (
        SELECT fx.*
        FROM fichxubi fx
        JOIN (
          SELECT fich_id, MAX(fecha_creacion) AS mx
          FROM fichxubi
          WHERE DATE(CONVERT_TZ(fecha_creacion,'+00:00','-05:00')) = CURDATE()
          GROUP BY fich_id
        ) ult ON ult.fich_id = fx.fich_id AND ult.mx = fx.fecha_creacion
      ) fxhoy ON fxhoy.fich_id = f.fich_id
    ";

    $rows = DB::query("
      SELECT
        f.fich_id,
        COALESCE((
          SELECT CONCAT(%s, '/', ff.img)
          FROM fotoxfich ff
          WHERE ff.fich_id = f.fich_id
          ORDER BY ff.fecha_creacion DESC
          LIMIT 1
        ), '') AS ultima_foto,
        f.fecha_creacion,
        f.usu_id,
        u.is_activo,
        f.is_validado,
        u.descripcion,
        f.visitas,
        f.neg_id,
        f.fecha_ultimo_acceso,
        u.cod_usu,
        u.sobrenombre,
        u.celular,
        u.is_fantasma,
        u.provincia,
        CONCAT(%s, '/', u.img_perfil) AS img_perfil,
        fxhoy.nombre_local   AS nombre_local_hoy,
        fxhoy.neg_id         AS neg_id_hoy
      FROM fich f
      LEFT JOIN usu u ON u.usu_id = f.usu_id
      $fxHoyJoin
      WHERE u.is_activo = 1
        AND f.fecha_ultimo_acceso >= CURDATE()
        AND f.fecha_ultimo_acceso <  (CURDATE() + INTERVAL 1 DAY)
        -- 🔒 exigir que exista ubicación de HOY
        AND fxhoy.fichxubi_id IS NOT NULL
        -- excluir “descanso” y neg_id=1
        AND COALESCE(fxhoy.neg_id, 0) <> 1
        AND UPPER(COALESCE(fxhoy.nombre_local, '')) NOT LIKE '%DESCANS%'
      ORDER BY f.fecha_ultimo_acceso DESC, f.fich_id DESC
      LIMIT 0,250
    ", $pic_fich, $pic_usu);

    Flight::json($rows);
});


Flight::route('POST /nflank/tk_tab_pres', function () {
    include DEFINITION; // Necesario para vari() y BUNNY_CDN_BASE

    visitante();

    $req = Flight::request()->data->getData();

    // uid o usu_id (ambos aceptados)
    $uid = intval($req['uid'] ?? $req['usu_id'] ?? 0);
    $zz  = $req['zz'] ?? null;
/*
    if ($uid === 0) {
        Flight::halt(400, json_encode(['error' => 'usu_id/uid es requerido']), 400);
        return;
    }
    */

    DB::query("SET NAMES 'utf8mb4'");
    $CDN_BASE = BUNNY_CDN_BASE;
    $results  = [];

    // ============================================================
    // A) DIRECTORIOS DE ESTADOS (actualizados según tus variables)
    // ============================================================
    $DIR_ESTADO_MAX  = trim(vari('ESTADO_MAX'), '/');   // t_estado_max
    $DIR_ESTADO_MINI = trim(vari('ESTADO_MINI'), '/');  // t_estado_mini
    $DIR_ESTADO_ORI  = trim(vari('ESTADO_ORI'), '/');   // t_estado_ori

    // BASES completas
    $ESTADO_MAX_BASE  = $CDN_BASE . '/' . $DIR_ESTADO_MAX;
    $ESTADO_MINI_BASE = $CDN_BASE . '/' . $DIR_ESTADO_MINI;
    $ESTADO_ORI_BASE  = $CDN_BASE . '/' . $DIR_ESTADO_ORI;

    // Directorio de usuarios
    $DIR_USU = trim(vari('FOTO_FICH_MAX'), '/');
    $USU_BASE = $CDN_BASE . '/' . $DIR_USU;

    // Optimizador
    $MAX_FULL      = (int) (vari('IMG_FULL') ?: 1080);
    $MAX_MINI      = (int) (vari('IMG_MINI') ?: 360);
    $USE_OPTIMIZER = false;
    $SUF_FULL      = $USE_OPTIMIZER ? ("?width={$MAX_FULL}&quality=75") : "";
    $SUF_MINI      = $USE_OPTIMIZER ? ("?width={$MAX_MINI}&quality=75") : "";

    // ============================================================
    // A) LISTA ESTADOS AGRUPADOS (actualizado a tus directorios)
    // ============================================================
    $results['estados'] = DB::query("
        SELECT
            e.fich_id,
            u.usu_id,
            u.sobrenombre,
            CONCAT(%s, '/', COALESCE(NULLIF(u.img_perfil,''), 'default.jpg')) AS usu_mini,

            -- Imagenes MAX (t_estado_max)
            GROUP_CONCAT(
                CONCAT(%s, '/', e.img, %s)
                ORDER BY e.fecha_creacion DESC
                SEPARATOR ';'
            ) AS imagenes,

            -- Imagenes MINI (t_estado_mini)
            GROUP_CONCAT(
                CONCAT(%s, '/', e.img, %s)
                ORDER BY e.fecha_creacion DESC
                SEPARATOR ';'
            ) AS imagenes_mini,

            -- Color verde si es de hoy
            IF(MAX(e.fecha_creacion) >= CONCAT(CURDATE(), ' 00:00:00'), 'verde', 'rojo') AS color

        FROM estado e
        JOIN fich f ON f.fich_id = e.fich_id
        JOIN usu  u ON u.usu_id  = f.usu_id
        WHERE e.is_visible = 1
          AND NOW() <= e.fecha_fin
        GROUP BY e.fich_id, u.usu_id, u.sobrenombre
        ORDER BY e.fich_id
    ",
        $USU_BASE,
        $ESTADO_MAX_BASE,  $SUF_FULL,   // imagenes
        $ESTADO_MINI_BASE, $SUF_MINI    // imagenes_mini
    );

    // ----------------------------------------------------------------------------------
    // B. LISTA SLIDERS (Corregido)
    // ----------------------------------------------------------------------------------
    $SLIDER_DIR  = 'sliders';
    $SLIDER_BASE = $CDN_BASE . '/' . $SLIDER_DIR;

    $results['sliders'] = DB::query("
        SELECT *,
               CONCAT(%s, '/', img) AS img_thumb
        FROM slider
        WHERE grupo = 'A'
        ORDER BY orden ASC
    ", $SLIDER_BASE);


    // ============================================================
    // C) LISTA FICHAS
    // ============================================================
    if ($uid > 0) {
        try {
            $existe = DB::queryFirstField("SELECT 1 FROM usu WHERE usu_id=%i LIMIT 1", $uid);
            if ($existe) {
                DB::update('usu', ['fecha_ultimo_acceso' => DB::sqleval('NOW()')], "usu_id=%i", $uid);
                DB::update('fich', ['fecha_ultimo_acceso' => DB::sqleval('NOW()')], "usu_id=%i", $uid);
            }
        } catch (Exception $e) {}
    }

    $pic_fich = $CDN_BASE . "/" . trim(vari('FOTO_FICH_MAX'));
    $pic_usu  = $CDN_BASE . "/" . trim(vari('FOTO_FICH_MAX'));

    // Último local del día
    $fxHoyJoin = "
      LEFT JOIN (
        SELECT fx.*
        FROM fichxubi fx
        JOIN (
          SELECT fich_id, MAX(fecha_creacion) AS mx
          FROM fichxubi
          WHERE DATE(CONVERT_TZ(fecha_creacion,'+00:00','-05:00')) = CURDATE()
          GROUP BY fich_id
        ) ult ON ult.fich_id = fx.fich_id AND ult.mx = fx.fecha_creacion
      ) fxhoy ON fxhoy.fich_id = f.fich_id
    ";

    $results['fichas'] = DB::query("
      SELECT
        f.fich_id,
        COALESCE((
          SELECT CONCAT(%s, '/', ff.img)
          FROM fotoxfich ff
          WHERE ff.fich_id = f.fich_id
          ORDER BY ff.fecha_creacion DESC
          LIMIT 1
        ), '') AS ultima_foto,
        f.fecha_creacion,
        f.usu_id,
        u.is_activo,
        f.is_validado,
        u.descripcion,
        f.visitas,
        f.neg_id,
        f.fecha_ultimo_acceso,
        u.cod_usu,
        u.sobrenombre,
        u.celular,
        u.is_fantasma,
        u.provincia,
        CONCAT(%s, '/', u.img_perfil) AS img_perfil,
        fxhoy.nombre_local AS nombre_local_hoy,
        fxhoy.neg_id AS neg_id_hoy
      FROM fich f
      LEFT JOIN usu u ON u.usu_id = f.usu_id
      $fxHoyJoin
      WHERE u.is_activo = 1
        AND f.fecha_ultimo_acceso >= CURDATE()
        AND f.fecha_ultimo_acceso < (CURDATE() + INTERVAL 1 DAY)
        AND fxhoy.fichxubi_id IS NOT NULL
        AND COALESCE(fxhoy.neg_id, 0) <> 1
        AND UPPER(COALESCE(fxhoy.nombre_local, '')) NOT LIKE '%DESCANS%'
      ORDER BY f.fecha_ultimo_acceso DESC, f.fich_id DESC
      LIMIT 0,30
    ", $pic_fich, $pic_usu);

    // ============================================================
    // D) RESPUESTA FINAL
    // ============================================================
    Flight::json($results, 200, JSON_UNESCAPED_UNICODE);
});


// =========================================
// LISTAR MÁSCARAS PARA APP IONIC
// =========================================
Flight::route('GET /bb/recurso/listar-mascaras/@rec_id:[0-9]+', function($rec_id) {

    // Cambia tiporec_id = 1 por el ID correspondiente a "máscara"
    $rows = DB::query("
        SELECT *
        FROM recurso
        WHERE tiporec_id = $rec_id
        ORDER BY recurso_id DESC
    ");

    // Construimos la URL completa desde BunnyCDN
    foreach ($rows as &$r) {
        $r['url_img'] = BUNNY_CDN_BASE . '/recursos/' . $r['img'];
    }

    Flight::json($rows);
});

Flight::route('POST /chat/subirFoto', function () {

    $raw = Flight::request()->getBody();
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        return Flight::json(["success"=>false, "error"=>"JSON inválido"], 400);
    }

    $chat_id       = intval($data['chat_id'] ?? 0);
    $rem_id        = intval($data['rem_id'] ?? 0);
    $dest_id       = intval($data['dest_id'] ?? 0);
    $contenido_rem = trim($data['contenido_rem'] ?? '');
    $filename      = trim($data['filename'] ?? '');

    if ($chat_id<=0 || $rem_id<=0 || $dest_id<=0 || $filename==='') {
        return Flight::json(["success"=>false, "error"=>"Datos incompletos"], 400);
    }

    try {

        DB::insert("msg", [
            "chat_id"        => $chat_id,
            "rem_id"         => $rem_id,
            "dest_id"        => $dest_id,
            "contenido_rem"  => $contenido_rem,
            "img"            => $filename,
            "tipoxmsg_id"    => 2,
            "fecha_creacion" => date("Y-m-d H:i:s")
        ]);

        return Flight::json([
            "success"  => true,
            "msg_id"   => DB::insertId(),
            "filename" => $filename
        ]);

    } catch (Exception $e) {

        return Flight::json([
            "success" => false,
            "error"   => "Error SQL al insertar",
            "debug"   => $e->getMessage()
        ], 500);
    }
});


// ================================================
//  POST /app/actualizaSobrenombre
//  JSON: { "usuario_id": 2, "sobrenombre": "NuevoNick" [, "tipoxusu_id": 3] }
// ================================================
Flight::route('POST /app/actualizaSobrenombre', function () {

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) Flight::halt(400, 'JSON inválido');
    if (!isset($data['usuario_id'], $data['sobrenombre']))
        Flight::halt(400, 'Faltan parámetros');

    $usuario_id  = (int)$data['usuario_id'];
    $sobrenombre = trim($data['sobrenombre']);

    // Ahora permite letras, números, espacio y '_' (máx 40)
    if (!preg_match('/^[A-Za-z0-9 _]{1,40}$/', $sobrenombre))
        Flight::halt(400, 'Sobrenombre con caracteres no permitidos');

    DB::update('usu', ['sobrenombre' => $sobrenombre], 'usu_id=%i', $usuario_id);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true]);
});

Flight::route('POST /usu/actualizar_fecha_nacimiento', function () {

    $data = Flight::request()->data->getData();
    $usu_id           = isset($data['usu_id'])           ? intval($data['usu_id']) : 0;
    $fecha_nacimiento = isset($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : '';

    // Validaciones básicas
    if ($usu_id <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
        Flight::json(['status' => 'error', 'msg' => 'Datos inválidos'], 400);
        return;
    }

    try {
        DB::query(
            "UPDATE usu SET fecha_nacimiento = %s WHERE usu_id = %i",
            $fecha_nacimiento,
            $usu_id
        );

        Flight::json(['status' => 'ok']);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
});

Flight::route('GET /usu/detalleUsu/@usu_id', function(string $usu_id) {

    include DEFINITION;

    // Aquí se guarda realmente la imagen de perfil (mini)
    $foto_fich_mini = vari("FOTO_FICH_MINI");
    $dir_img_perfil = BUNNY_CDN_BASE . "/" . $foto_fich_mini . "/";

    // Obtener usuario
    $usuario = DB::queryFirstRow("
        SELECT 
            *
        FROM usu 
        WHERE usu_id=%i
    ", $usu_id);

    if (!$usuario) { 
        Flight::json(['success'=>false], 404); 
        return; 
    }

    // --- fich_id según tipo de usuario ---
    $fich_id = 0;
    if ((int)$usuario['tipoxusu_id'] === 2) {
        $fich_id = (int) (DB::queryFirstField("
            SELECT fich_id 
            FROM fich 
            WHERE usu_id=%i
            ORDER BY fecha_creacion DESC, fich_id DESC
            LIMIT 1
        ", $usu_id) ?? 0);
    }
    $usuario['fich_id'] = $fich_id;

    // Agregar ruta real del directorio de img_perfil
    $usuario['dir_img_perfil'] = $dir_img_perfil;

    // Eliminar pics
    // $usuario['pics'] eliminado según solicitud

    Flight::json(['success'=>true, 'data'=>$usuario]);
});

function visitante() {
    // DIA + FECHA en español
    $dias = ["Domingo","Lunes","Martes","Miércoles","Jueves","Viernes","Sábado"];
    $meses = [
        "Enero","Febrero","Marzo","Abril","Mayo","Junio",
        "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"
    ];

    $hoy = new DateTime('now', new DateTimeZone('America/Lima'));
    $dia_semana = $dias[(int)$hoy->format('w')];
    $dia = $hoy->format('d');
    $mes = $meses[(int)$hoy->format('n') - 1];
    $anio = $hoy->format('Y');
    $fecha_texto = "$dia_semana $dia de $mes del $anio";

    $fecha_hora_actual = $hoy->format('Y-m-d H:i:s');
    $fecha_inicio = $hoy->format('Y-m-d 00:00:00');
    $fecha_fin    = $hoy->format('Y-m-d 23:59:59');

    // Buscar registro del día actual
    $row = DB::queryFirstRow("
        SELECT *
        FROM usuxreg
        WHERE accion = %s
          AND fecha_creacion BETWEEN %s AND %s
        ORDER BY usuxreg_id DESC
        LIMIT 1
    ", 'visitante', $fecha_inicio, $fecha_fin);


    // ==========================================================
    // 🔥 SI ES UN NUEVO DÍA → CREAR REGISTRO CON visitantes = 0
    // ==========================================================
    if (!$row) {

        $visitantes = 0; // ← como pediste

        DB::insert("usuxreg", [
            'usu_id'         => 0,
            'accion'         => 'visitante',
            'descripcion'    => "Hoy $fecha_texto tenemos $visitantes visitantes",
            'extra_data'     => json_encode(['visitantes' => $visitantes]),
            'fecha_creacion' => $fecha_hora_actual
        ]);

        return [
            "ok" => true,
            "nuevo_dia" => true,
            "visitantes" => $visitantes,
            "descripcion" => "Hoy $fecha_texto tenemos $visitantes visitantes",
            "hora_actualizada" => $fecha_hora_actual
        ];
    }


    // ==========================================================
    // 🔥 SI EXISTE → Aumentar visitantes
    // ==========================================================
    $extra = json_decode($row['extra_data'] ?? '{}', true);
    $visitantes = intval($extra['visitantes'] ?? 0) + 1;

    $descripcion = "Hoy $fecha_texto tenemos $visitantes visitantes";

    DB::update("usuxreg", [
        'descripcion'    => $descripcion,
        'extra_data'     => json_encode(['visitantes' => $visitantes]),
        'fecha_creacion' => $fecha_hora_actual
    ], "usuxreg_id=%i", $row['usuxreg_id']);

    return [
        "ok" => true,
        "nuevo_dia" => false,
        "visitantes" => $visitantes,
        "descripcion" => $descripcion,
        "hora_actualizada" => $fecha_hora_actual
    ];
}

// --------------------------------------------------------------
// 1) Obtiene plantilla desde regdesc
// --------------------------------------------------------------
function obtenerDescripcionAccion($accion) {
    $row = DB::queryFirstRow(
        "SELECT descripcion FROM regdesc WHERE accion = %s",
        $accion
    );
    return $row ? $row['descripcion'] : '';
}

// --------------------------------------------------------------
// 2) Construye la descripción final con los valores dinámicos
// --------------------------------------------------------------
function armarDescripcionFinal($plantilla, $usu_id, $extra_data) {

    // --- 1) Nombre del usuario que realiza la acción ---
    $usu = DB::queryFirstRow("SELECT sobrenombre FROM usu WHERE usu_id=%i", $usu_id);
    $nom_usu = $usu ? '@' . $usu['sobrenombre'] : '@usuario';

    // --- 2) Obtener nombre de fichera si existe fich_id_dest ---
    $nom_fich = '@fich';
    if (isset($extra_data['fich_id_dest'])) {
        $fich_id = intval($extra_data['fich_id_dest']);

        $r = DB::queryFirstRow("
            SELECT u.sobrenombre 
            FROM fich f
            LEFT JOIN usu u ON u.usu_id = f.usu_id
            WHERE f.fich_id = %i
        ", $fich_id);

        if ($r) $nom_fich = '@' . $r['sobrenombre'];
    }

    // --- 3) Obtener nombre de negocio si existe neg_id_dest ---
    $nom_neg = '@negocio';
    if (isset($extra_data['neg_id_dest'])) {
        $neg_id = intval($extra_data['neg_id_dest']);

        $r = DB::queryFirstRow("SELECT nombre FROM neg WHERE neg_id=%i", $neg_id);
        if ($r) $nom_neg = '@' . $r['nombre'];
    }

    // --- 4) Reemplazar variables en plantilla ---
    $final = str_replace(
        ['{nom_usu}', '{nom_fich}', '{nom_neg}'],
        [$nom_usu, $nom_fich, $nom_neg],
        $plantilla
    );

    return $final;
}

// --------------------------------------------------------------
// 3) FUNCIÓN diario() FINAL
// --------------------------------------------------------------
function diario($usu_id, $accion, $extra_data = null) {

    // plantilla base desde regdesc
    $plantilla = obtenerDescripcionAccion($accion);

    // asegurar array
    if (!is_array($extra_data)) $extra_data = [];

    // descripción final con variables reemplazadas
    $descripcion_final = armarDescripcionFinal($plantilla, $usu_id, $extra_data);

    // insertar en tabla
    DB::insert('usuxreg', [
        'usu_id'         => intval($usu_id),
        'accion'         => $accion,
        'descripcion'    => $descripcion_final,
        'extra_data'     => json_encode($extra_data),
        'fecha_creacion' => date('Y-m-d H:i:s')
    ]);
}

function get_usu_id($fich_id) {
    if (!$fich_id) return null;

    $row = DB::queryFirstRow(
        "SELECT usu_id FROM fich WHERE fich_id = %i",
        $fich_id
    );

    return $row ? intval($row['usu_id']) : null;
}



Flight::route('GET /ion/neg/seleccionarNeg', function () {
    $rows = DB::query('SELECT * FROM neg ORDER BY nombre');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});

function get_img_perfil($fich_id) {

    // 1) Buscar el usu_id dueño de la ficha
    $row = DB::queryFirstRow("
        SELECT u.img_perfil
        FROM fich f
        LEFT JOIN usu u ON u.usu_id = f.usu_id
        WHERE f.fich_id = %i
        LIMIT 1
    ", $fich_id);

    // 2) Si existe, devolver img_perfil; de lo contrario null
    return $row ? $row['img_perfil'] : null;
}


Flight::route('POST /bb/usuxreg/listar', function() {

    include DEFINITION;

    // ==============================
    // 📌 Leer parámetros
    // ==============================
    $req    = Flight::request()->data->getData();
    $offset = intval($req['offset'] ?? 0);
    $limit  = intval($req['limit'] ?? 50);

    if ($offset < 0) $offset = 0;
    if ($limit > 100) $limit = 100; // protección

    // ==============================
    // 📌 Cargar reglas de acciones (tabla regdesc)
    // ==============================
    $regdesc = DB::query("SELECT accion, location FROM regdesc");

    $actionMap = [];
    foreach ($regdesc as $r) {
        $actionMap[$r['accion']] = $r['location'];
    }

    // ==============================
    // 📌 Traer últimos registros con paginación
    // ==============================
    $rows = DB::query("
        SELECT 
            uxr.usuxreg_id,
            uxr.usu_id,
            u.sobrenombre AS user,
            u.img_perfil,
            uxr.accion,
            uxr.descripcion,
            uxr.extra_data,
            uxr.fecha_creacion
        FROM usuxreg uxr
        LEFT JOIN usu u ON uxr.usu_id = u.usu_id
        ORDER BY uxr.fecha_creacion DESC
        LIMIT %i OFFSET %i
    ", $limit, $offset);

    if (!$rows) {
        Flight::json([], 200);
        return;
    }

    foreach ($rows as &$r) {

    // ========================================
    // Decodificar extra_data
    // ========================================
    $extra = json_decode($r['extra_data'] ?? '{}', true);

    $usuDest  = $extra['usu_id_dest']  ?? null;
    $negDest  = $extra['neg_id_dest']  ?? null;
    $fichDest = $extra['fich_id_dest'] ?? null;
    $coment   = $extra['comentario']   ?? null;

    // ========================================
    // AVATAR LOGIC 🎯
    // ========================================

    if ($fichDest) {
        // Caso 1: ficha normal
        $r['avatar_dest'] = BUNNY_CDN_BASE . "/" . vari("FOTO_FICH_MINI") . "/" . get_img_perfil($fichDest);

    } elseif ($negDest) {
        // Caso 2: NEGOCIO
        $r['avatar_dest'] = "../assets/dg17.jpg";

    } else {
        // Caso 3: Ningún destino
        $r['avatar_dest'] = "../assets/logo_barsi.png";
    }

    // ========================================
    // LOCATION LOGIC ❤️
    // ========================================
    $pattern = $actionMap[$r['accion']] ?? null;

    if ($fichDest) {
        // Caso normal de fichera
        if ($pattern && $pattern !== "(NULL)") {
            $loc = str_replace("{fich_id_dest}", $fichDest, $pattern);
            $r['location'] = $loc;
        } else {
            $r['location'] = null;
        }

    } elseif ($negDest) {
        // Caso NEGOCIO: ruta especial para abrir modal desde Ionic
        $r['location'] = "LOCAL:" . $negDest;

    } else {
        // Sin link
        $r['location'] = null;
    }

    // ========================================
    // Campos finales para frontend
    // ========================================
    $r['usu_id_dest']  = $usuDest;
    $r['neg_id_dest']  = $negDest;
    $r['fich_id_dest'] = $fichDest;
    $r['comentario']   = $coment;

    $r['tags'] = [];
}


    // ========================================
    // 📌 Respuesta final
    // ========================================
    Flight::json($rows);
});


/* ---- CREAR nuevo estado (Bunny) ---- */
/* =========================================================
 *  POST /bunny/estado/crear
 *  Sube imagen a Bunny Storage y crea registro en `estado`
 * =======================================================*/

Flight::route('POST /bunny/estado/crear', function () {
    include DEFINITION;

    // 1) Leer payload (JSON o form-data)
    $req  = Flight::request();
    $raw  = $req->getBody();
    $data = json_decode($raw, true);
    if (!is_array($data) || empty($data)) {
        // Soporta application/x-www-form-urlencoded o multipart/form-data
        $data = $req->data->getData();
    }

    // 2) Entradas esperadas
    $fich_id  = intval($data['fich_id'] ?? 0);
    $filename = trim((string)($data['filename'] ?? $data['file'] ?? $data['img'] ?? ''));

    $cid = get_usu_id($fich_id);

    diario($cid, 'subio_foto', [ "fich_id_dest" => $fich_id ]);

    // Opcional: urls que pueden venir del endpoint foto_estado.py
    $cdn      = (isset($data['cdn_urls']) && is_array($data['cdn_urls'])) ? $data['cdn_urls'] : [];
    $url_max  = trim((string)($data['url_max']  ?? ($cdn['max']       ?? '')));
    $url_mini = trim((string)($data['url_mini'] ?? ($cdn['mini']      ?? '')));
    $url_ori  = trim((string)($data['url_ori']  ?? $data['url_original'] ?? ($cdn['original'] ?? '')));

    if ($fich_id <= 0) {
        Flight::json(['ok' => false, 'msg' => 'fich_id requerido'], 400);
        return;
    }

    // Si no mandaron filename explícito, lo derivamos de cualquiera de las URLs
    if ($filename === '') {
        foreach ([$url_max, $url_mini, $url_ori] as $u) {
            if ($u) {
                $path = parse_url($u, PHP_URL_PATH);
                if ($path) {
                    $filename = basename($path);
                    break;
                }
            }
        }
    }
    if ($filename === '') {
        Flight::json(['ok' => false, 'msg' => 'filename o cdn_urls requeridos'], 400);
        return;
    }

    // 3) Config Bunny (CDN + carpetas ESTADO)
    $BUNNY_CDN_BASE = defined('BUNNY_CDN_BASE')
        ? rtrim(BUNNY_CDN_BASE, '/')
        : rtrim((string) vari('BUNNY_CDN_BASE'), '/');

    // Carpetas de ESTADO (con fallbacks)
    // Si no tienes aún estado_*, puedes crear esas claves; mientras tanto, caen en defaults.
    $t_estado_ori  = trim((string) (vari('estado_ori')  ?: 't_estado_ori'),  '/');
    $t_estado_mini = trim((string) (vari('estado_mini') ?: 't_estado_mini'), '/');
    $t_estado_max  = trim((string) (vari('estado_max')  ?: 't_estado_max'),  '/');


    // 4) Insertar en BD (guardamos solo el filename)
    DB::insert('estado', [
        'fich_id'        => $fich_id,
        'img'            => $filename,
        'is_visible'     => 1,
        'visitas'        => 0,
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'fecha_fin'      => date('Y-m-d H:i:s', strtotime('+1 day')),
    ]);
    $estado_id = (int) DB::insertId();

    // 5) URLs públicas por CDN (sin subcarpeta por usuario)
    $img_ori  = $BUNNY_CDN_BASE . '/' . $t_estado_ori  . '/' . $filename;
    $img_mini = $BUNNY_CDN_BASE . '/' . $t_estado_mini . '/' . $filename;
    $img_full = $BUNNY_CDN_BASE . '/' . $t_estado_max  . '/' . $filename;

    $cid = get_usu_id($fich_id);

    diario($cid, 'subio_foto', [ "fich_id_dest" => $fich_id ]);

    Flight::json([
        'ok'         => true,
        'estado_id'  => $estado_id,
        'filename'   => $filename,
        'cdn_base'   => $BUNNY_CDN_BASE,
        'folders'    => [
            'ori'  => $t_estado_ori,
            'mini' => $t_estado_mini,
            'max'  => $t_estado_max
        ],
        'urls'       => [
            'original' => $img_ori,
            'mini'     => $img_mini,
            'max'      => $img_full
        ],
    ]);
});
/*
Flight::route('POST /estado/uno/@fich_id', function ($fich_id) {

    include DEFINITION;

    // Directorios según configuración
    $CDN_BASE = BUNNY_CDN_BASE;

    $DIR_ESTADO_MAX  = trim(vari('ESTADO_MAX'), '/');
    $DIR_ESTADO_MINI = trim(vari('ESTADO_MINI'), '/');
    $DIR_USU         = trim(vari('FOTO_FICH_MAX'), '/');

    $ESTADO_MAX_BASE  = $CDN_BASE . '/' . $DIR_ESTADO_MAX;
    $ESTADO_MINI_BASE = $CDN_BASE . '/' . $DIR_ESTADO_MINI;
    $USU_BASE         = $CDN_BASE . '/' . $DIR_USU;

    $MAX_FULL      = (int) (vari('IMG_FULL') ?: 1080);
    $MAX_MINI      = (int) (vari('IMG_MINI') ?: 360);
    $USE_OPTIMIZER = false;

    $SUF_FULL = $USE_OPTIMIZER ? ("?width={$MAX_FULL}&quality=75") : "";
    $SUF_MINI = $USE_OPTIMIZER ? ("?width={$MAX_MINI}&quality=75") : "";


    // ======================================================
    // CONSULTA — UNA FICHERA, TODAS SUS IMÁGENES DEL ESTADO
    // ======================================================
    $row = DB::queryFirstRow("
        SELECT
            e.fich_id,
            u.usu_id,
            u.sobrenombre,

            CONCAT(%s, '/', COALESCE(NULLIF(u.img_perfil,''), 'default.jpg')) AS usu_mini,

            GROUP_CONCAT(
                CONCAT(%s, '/', e.img, %s)
                ORDER BY e.fecha_creacion DESC
                SEPARATOR ';'
            ) AS imagenes,

            GROUP_CONCAT(
                CONCAT(%s, '/', e.img, %s)
                ORDER BY e.fecha_creacion DESC
                SEPARATOR ';'
            ) AS imagenes_mini,

            IF(MAX(e.fecha_creacion) >= CONCAT(CURDATE(), ' 00:00:00'), 'verde', 'rojo') AS color

        FROM estado e
        JOIN fich f ON f.fich_id = e.fich_id
        JOIN usu  u ON u.usu_id  = f.usu_id
        WHERE e.fich_id = %i
          AND e.is_visible = 1
        ORDER BY e.fecha_creacion DESC
    ",
        $USU_BASE,
        $ESTADO_MAX_BASE,  $SUF_FULL,
        $ESTADO_MINI_BASE, $SUF_MINI,
        $fich_id
    );

    if (!$row) {
        Flight::json(['ok' => false, 'error' => 'estado no encontrado'], 404);
        return;
    }

    // RESPUESTA
    Flight::json([
        'ok'             => true,
        'fich_id'        => (int) $row['fich_id'],
        'usu_id'         => (int) $row['usu_id'],
        'sobrenombre'    => $row['sobrenombre'],
        'usu_mini'       => $row['usu_mini'],
        'imagenes'       => $row['imagenes'] ?: '',
        'imagenes_mini'  => $row['imagenes_mini'] ?: '',
        'color'          => $row['color']
    ]);
});
*/

Flight::route('POST /estado/uno', function () {

    include DEFINITION;

    // ================
    // Leer parámetros
    // ================
    $req = Flight::request()->data->getData();

    $fich_id = intval($req['fich_id'] ?? 0);
    $cid = intval($req['cid'] ?? 0); // <-- nuevo dato que AMBAS
                                             //     partes usarán

    if ($fich_id <= 0) {
        Flight::json(['ok' => false, 'error' => 'fich_id requerido'], 400);
        return;
    }

    diario($cid, 'vio_estado', [
        'fich_id_dest' => $fich_id
    ]);


    // Directorios según configuración
    $CDN_BASE = BUNNY_CDN_BASE;

    $DIR_ESTADO_MAX  = trim(vari('ESTADO_MAX'), '/');
    $DIR_ESTADO_MINI = trim(vari('ESTADO_MINI'), '/');
    $DIR_USU         = trim(vari('FOTO_FICH_MAX'), '/');

    $ESTADO_MAX_BASE  = $CDN_BASE . '/' . $DIR_ESTADO_MAX;
    $ESTADO_MINI_BASE = $CDN_BASE . '/' . $DIR_ESTADO_MINI;
    $USU_BASE         = $CDN_BASE . '/' . $DIR_USU;

    $MAX_FULL      = (int) (vari('IMG_FULL') ?: 1080);
    $MAX_MINI      = (int) (vari('IMG_MINI') ?: 360);
    $USE_OPTIMIZER = false;

    $SUF_FULL = $USE_OPTIMIZER ? ("?width={$MAX_FULL}&quality=75") : "";
    $SUF_MINI = $USE_OPTIMIZER ? ("?width={$MAX_MINI}&quality=75") : "";


    // ======================================================
    // CONSULTA — UNA FICHERA, TODAS SUS IMÁGENES DEL ESTADO
    // ======================================================
    $row = DB::queryFirstRow("
        SELECT
            e.fich_id,
            u.usu_id,
            u.sobrenombre,
            u.tipoxusu_id,
            CONCAT(%s, '/', COALESCE(NULLIF(u.img_perfil,''), 'default.jpg')) AS usu_mini,

            GROUP_CONCAT(
                CONCAT(%s, '/', e.img, %s)
                ORDER BY e.fecha_creacion DESC
                SEPARATOR ';'
            ) AS imagenes,

            GROUP_CONCAT(
                CONCAT(%s, '/', e.img, %s)
                ORDER BY e.fecha_creacion DESC
                SEPARATOR ';'
            ) AS imagenes_mini,

            IF(MAX(e.fecha_creacion) >= CONCAT(CURDATE(), ' 00:00:00'), 'verde', 'rojo') AS color

        FROM estado e
        JOIN fich f ON f.fich_id = e.fich_id
        JOIN usu  u ON u.usu_id  = f.usu_id
        WHERE e.fich_id = %i
          AND e.is_visible = 1
        ORDER BY e.fecha_creacion DESC
    ",
        $USU_BASE,
        $ESTADO_MAX_BASE,  $SUF_FULL,
        $ESTADO_MINI_BASE, $SUF_MINI,
        $fich_id
    );

    if (!$row) {
        Flight::json(['ok' => false, 'error' => 'estado no encontrado'], 404);
        return;
    }

    // ======================================================
    // Aquí puedes usar user_id para registrar la vista 👇
    // ======================================================
    // DB::insert('estado_vistas', [
    //     'fich_id' => $fich_id,
    //     'user_id' => $user_id,
    //     'fecha'   => date('Y-m-d H:i:s')
    // ]);

    // RESPUESTA FINAL
    Flight::json([
        'ok'             => true,
        'fich_id'        => (int) $row['fich_id'],
        'usu_id'         => (int) $row['usu_id'],
        'sobrenombre'    => $row['sobrenombre'],
        'usu_mini'       => $row['usu_mini'],
        'imagenes'       => $row['imagenes'] ?: '',
        'imagenes_mini'  => $row['imagenes_mini'] ?: '',
        'tipoxusu_id' => $row['tipoxusu_id'],
        'color'          => $row['color']
    ]);
});

// =========================================
// /bunny/fotoxfich/crear
// Guarda el registro en BD + Vision + devuelve lista actualizada
// =========================================

Flight::route('POST /bunny/fotoxfich/crear', function () {
    include DEFINITION; // debe incluir vari(), DB, BUNNY_CDN_BASE, etc.

    // 1) Leer payload
    $req = Flight::request();
    $raw = $req->getBody();
    $data = json_decode($raw, true);

    if (!is_array($data) || empty($data)) {
        // Soporta form-data
        $data = $req->data->getData();
    }

    // 2) Entradas esperadas
    $fich_id  = intval($data['fich_id'] ?? 0);
    $filename = trim((string)($data['filename'] ?? $data['file'] ?? $data['img'] ?? ''));

    $cid = get_usu_id($fich_id);
    diario($cid, 'subio_foto', [  'fich_id_dest' => $fich_id ]);

    // Campos opcionales (Vision)
    $gv_adult        = isset($data['gv_adult'])        ? trim((string)$data['gv_adult'])        : null;
    $gv_racy         = isset($data['gv_racy'])         ? trim((string)$data['gv_racy'])         : null;
    $gv_nudity_level = isset($data['gv_nudity_level']) ? intval($data['gv_nudity_level'])       : null;
    $gv_block_flux   = isset($data['gv_block_flux'])   ? intval($data['gv_block_flux'])         : 0;
    $gv_checked_at   = isset($data['gv_checked_at'])   ? trim((string)$data['gv_checked_at'])   : null;

    // URLs opcionales enviadas por Python
    $cdn = (isset($data['cdn_urls']) && is_array($data['cdn_urls'])) ? $data['cdn_urls'] : [];

    $url_max  = trim((string)($data['url_max']  ?? ($cdn['max']       ?? '')));
    $url_mini = trim((string)($data['url_mini'] ?? ($cdn['mini']      ?? '')));
    $url_ori  = trim((string)($data['url_ori']  ?? $data['url_original'] ?? ($cdn['original'] ?? '')));

    if ($fich_id <= 0) {
        Flight::json(['ok' => false, 'msg' => 'fich_id requerido'], 400);
        return;
    }

    // Intentar derivar filename desde una URL si no vino explícito
    if ($filename === '') {
        foreach ([$url_max, $url_mini, $url_ori] as $u) {
            if ($u) {
                $path = parse_url($u, PHP_URL_PATH);
                if ($path) {
                    $filename = basename($path);
                    break;
                }
            }
        }
    }

    if ($filename === '') {
        Flight::json(['ok' => false, 'msg' => 'filename o cdn_urls requeridos'], 400);
        return;
    }

    // 3) Configurar Bunny
    $BUNNY_CDN_BASE = defined('BUNNY_CDN_BASE')
        ? rtrim(BUNNY_CDN_BASE, '/')
        : rtrim((string) vari('BUNNY_CDN_BASE'), '/');

    // Directorios configurados en BD (vari)
    $t_foto_fich_max  = trim((string)(vari('foto_fich_max')  ?: 't_foto_fich_max'),  '/');
    $t_foto_fich_mini = trim((string)(vari('foto_fich_mini') ?: 't_foto_fich_mini'), '/');

    // 4) Guardar en BD (filename + vision)
    $insertData = [
        'fich_id'        => $fich_id,
        'img'            => $filename,
        'is_visible'     => 1,
        'is_valido'      => 1,
        'fecha_creacion' => date('Y-m-d H:i:s'),
        'gv_adult'       => $gv_adult,
        'gv_racy'        => $gv_racy,
        'gv_nudity_level'=> $gv_nudity_level,
        'gv_block_flux'  => $gv_block_flux, // siempre 0 salvo que Python lo fuerce
        'gv_checked_at'  => $gv_checked_at ?: null,
    ];

    // Si mandaron valores Vision pero no fecha → asignar
    if (($gv_adult || $gv_racy || $gv_nudity_level !== null) && !$gv_checked_at) {
        $insertData['gv_checked_at'] = date('Y-m-d H:i:s');
    }

    DB::insert('fotoxfich', $insertData);

    // 5) Listar imágenes actualizadas
    $rows = DB::query("
        SELECT
            fx.fotoxfich_id,
            fx.fich_id,
            fx.img,
            fx.is_visible,
            fx.is_valido,
            fx.fecha_creacion,
            fx.gv_adult,
            fx.gv_racy,
            fx.gv_nudity_level,
            fx.gv_block_flux,
            fx.gv_checked_at
        FROM fotoxfich fx
        WHERE fx.fich_id = %i
        ORDER BY fx.fecha_creacion DESC
    ", $fich_id);

    // Armar URLs finales para frontend
    $base_mini = $BUNNY_CDN_BASE . '/' . $t_foto_fich_mini;
    $base_max  = $BUNNY_CDN_BASE . '/' . $t_foto_fich_max;

    foreach ($rows as &$r) {
        $r['ruta_img']      = $base_mini . '/' . $r['img']; // mini
        $r['ruta_img_full'] = $base_max  . '/' . $r['img']; // max
    }
    unset($r);

    // 6) Respuesta final
    Flight::json([
        'ok'       => true,
        'saved'    => $filename,
        'rows'     => $rows,
        'bases'    => [
            'mini' => $base_mini,
            'max'  => $base_max,
        ]
    ]);
});

Flight::route('POST /nflank/tk_tab_pres_fichas_paginado', function () {
    include DEFINITION;

    $req    = Flight::request()->data->getData();
    $offset = intval($req['offset'] ?? 0);
    $limit  = intval($req['limit'] ?? 30);
    $uid    = intval($req['uid'] ?? 0);
    $zz     = $req['zz'] ?? '';

    if ($limit <= 0)  $limit = 30;
    if ($limit > 100) $limit = 100;
    if ($offset < 0)  $offset = 0;

    DB::query("SET NAMES 'utf8mb4'");

    $pic_fich = BUNNY_CDN_BASE . "/" . trim(vari('FOTO_FICH_MAX'));
    $pic_usu  = BUNNY_CDN_BASE . "/" . trim(vari('FOTO_FICH_MAX'));

    // último local del día
    $fxHoyJoin = "
      LEFT JOIN (
        SELECT fx.*
        FROM fichxubi fx
        JOIN (
          SELECT fich_id, MAX(fecha_creacion) AS mx
          FROM fichxubi
          WHERE DATE(CONVERT_TZ(fecha_creacion,'+00:00','-05:00')) = CURDATE()
          GROUP BY fich_id
        ) ult ON ult.fich_id = fx.fich_id AND ult.mx = fx.fecha_creacion
      ) fxhoy ON fxhoy.fich_id = f.fich_id
    ";

    $rows = DB::query("
      SELECT
        f.fich_id,
        COALESCE((
            SELECT CONCAT(%s, '/', ff.img)
            FROM fotoxfich ff
            WHERE ff.fich_id = f.fich_id
            ORDER BY ff.fecha_creacion DESC
            LIMIT 1
        ), '') AS ultima_foto,
        f.fecha_creacion,
        f.usu_id,
        u.sobrenombre,
        u.descripcion,
        u.provincia,
        CONCAT(%s, '/', u.img_perfil) AS img_perfil,
        fxhoy.nombre_local AS nombre_local_hoy,
        fxhoy.neg_id AS neg_id_hoy
      FROM fich f
      LEFT JOIN usu u ON u.usu_id = f.usu_id
      $fxHoyJoin
      WHERE u.is_activo = 1
        AND f.fecha_ultimo_acceso >= CURDATE()
        AND f.fecha_ultimo_acceso < (CURDATE() + INTERVAL 1 DAY)
        AND fxhoy.fichxubi_id IS NOT NULL
        AND COALESCE(fxhoy.neg_id, 0) <> 1
        AND UPPER(COALESCE(fxhoy.nombre_local, '')) NOT LIKE '%DESCANS%'
      ORDER BY f.fecha_ultimo_acceso DESC, f.fich_id DESC
      LIMIT %i, %i
    ", $pic_fich, $pic_usu, $offset, $limit);

    Flight::json($rows ?: [], 200, JSON_UNESCAPED_UNICODE);
});

Flight::route('POST /ion/fichres/crear', function () {
    include DEFINITION;
    

    // Helpers --------------------------------------------
    $getInt = function ($v) use (&$getInt) {
        // Acepta: 123 | "123" | ["id"=>123] | (object)["id"=>123] | ["value"=>123] | etc.
        if (is_null($v)) return 0;
        if (is_int($v) || is_float($v) || (is_string($v) && is_numeric($v))) {
            return (int)$v;
        }
        if (is_object($v)) $v = (array)$v;
        if (is_array($v)) {
            if (isset($v['id']) && is_numeric($v['id'])) return (int)$v['id'];
            if (isset($v['value']) && is_numeric($v['value'])) return (int)$v['value'];
            // como último recurso, busca el primer valor numérico
            foreach ($v as $vv) if (is_numeric($vv)) return (int)$vv;
            return 0;
        }
        return 0;
    };

    $d = Flight::request()->data->getData();

    $fich_id       = $getInt($d['fich_id'] ?? 0);
    $usu_id        = $getInt($d['usu_id']  ?? 0);                // <-- FIX
    $neg_raw       = $d['neg_id'] ?? null;
    $neg_id        = ($neg_raw === '' || $neg_raw === null) ? null : $getInt($neg_raw);  // <-- FIX
    $fecha_visita  = $d['fecha_visita']   ?? null;               // 'YYYY-MM-DD'

    $cid = $usu_id;

    diario($cid, 'comento_atencion_fichera', [
        'fich_id_dest' => $fich_id
    ]);

    $overall_score = $d['overall_score'] ?? null;
    $overall_score = ($overall_score === '' || $overall_score === null) ? null : (float)$overall_score;

    // OJO: si te llega 0/1, string "0"/"1" o boolean
    $is_publicado  = (!empty($d['is_publicado']) && (int)$d['is_publicado'] === 1) ? 1 : 0;

    $comentario    = $d['comentario'] ?? '';
    $items         = (isset($d['items']) && is_array($d['items'])) ? $d['items'] : [];

    if ($fich_id <= 0 || $usu_id <= 0 || empty($fecha_visita)) {
        Flight::json(['status'=>'error','msg'=>'Datos incompletos (fich_id, usu_id, fecha_visita)'], 400);
        return;
    }

    DB::startTransaction();
    try {
        // 1) Cabecera
        DB::insert('fich_resena', [
          'fich_id'       => $fich_id,
          'neg_id'        => $neg_id,           // puede ser NULL
          'usu_id'        => $usu_id,
          'fecha_visita'  => $fecha_visita,
          'comentario'    => $comentario,
          'overall_score' => $overall_score,
          'is_publicado'  => $is_publicado,
          'created_at'    => date('Y-m-d H:i:s'),
          'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        $resena_id = DB::insertId();

        // 2) Ítems
        foreach ($items as $ix => $it) {
            $pid = $getInt($it['fich_pregunta_id'] ?? 0);
            if ($pid <= 0) continue;

            DB::insert('fich_resena_item', [
              'fich_resena_id'    => $resena_id,
              'fich_pregunta_id'  => $pid,
              'original_answer'   => $it['original_answer']   ?? null,
              'normalized_answer' => $it['normalized_answer'] ?? null,
              'numeric_score'     => isset($it['numeric_score']) ? (float)$it['numeric_score'] : null,
              'bool_value'        => isset($it['bool_value']) ? (int)$it['bool_value'] : null,
              'choice_code'       => $it['choice_code']       ?? null,
              'opcion_item_id'    => isset($it['opcion_item_id']) ? (int)$it['opcion_item_id'] : null,
              'orden'             => $ix + 1,
              'created_at'        => date('Y-m-d H:i:s'),
              'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        DB::commit();
        Flight::json(['status'=>'ok','fich_resena_id'=>$resena_id]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});

/* Crear reseña — SIN fich_id */
Flight::route('POST /ion/negres/crear', function () {
    include DEFINITION;
    DB::query("SET NAMES 'utf8mb4'");
    $d = Flight::request()->data->getData();

    $neg_id       = _getIntId($d['neg_id'] ?? 0);
    $usu_id       = _getIntId($d['usu_id'] ?? 0);
    $fecha_visita = $d['fecha_visita'] ?? null;

    $overall_score = $d['overall_score'] ?? null;
    $overall_score = ($overall_score === '' || $overall_score === null) ? null : (float)$overall_score;

    $is_publicado = (!empty($d['is_publicado']) && (int)$d['is_publicado'] === 1) ? 1 : 0;
    $comentario   = $d['comentario'] ?? '';
    $items        = (isset($d['items']) && is_array($d['items'])) ? $d['items'] : [];

    if ($neg_id<=0 || $usu_id<=0 || empty($fecha_visita)) {
        Flight::json(['status'=>'error','msg'=>'Datos incompletos (neg_id, usu_id, fecha_visita)'], 400);
        return;
    }

    $cid = $usu_id;

    diario($cid, 'comento_atencion_negocio', [
        'neg_id_dest' => $neg_id
    ]);


    DB::startTransaction();
    try {
        DB::insert('neg_resena', [
          'neg_id'        => $neg_id,
          'usu_id'        => $usu_id,
          'fecha_visita'  => $fecha_visita,
          'comentario'    => $comentario,
          'overall_score' => $overall_score,
          'is_publicado'  => $is_publicado,
          'created_at'    => date('Y-m-d H:i:s'),
          'updated_at'    => date('Y-m-d H:i:s'),
        ]);
        $rid = DB::insertId();

        foreach ($items as $ix=>$it) {
            $pid = _getIntId($it['neg_pregunta_id'] ?? 0);
            if ($pid<=0) continue;
            DB::insert('neg_resena_item', [
              'neg_resena_id'   => $rid,
              'neg_pregunta_id' => $pid,
              'original_answer'   => $it['original_answer']   ?? null,
              'normalized_answer' => $it['normalized_answer'] ?? null,
              'numeric_score'     => isset($it['numeric_score']) ? (float)$it['numeric_score'] : null,
              'bool_value'        => isset($it['bool_value']) ? (int)$it['bool_value'] : null,
              'choice_code'       => $it['choice_code'] ?? null,
              'opcion_item_id'    => isset($it['opcion_item_id']) ? (int)$it['opcion_item_id'] : null,
              'orden'             => $ix+1,
              'created_at'        => date('Y-m-d H:i:s'),
              'updated_at'        => date('Y-m-d H:i:s'),
            ]);
        }

        DB::commit();
        Flight::json(['status'=>'ok','neg_resena_id'=>$rid]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});


Flight::route('POST /ion/fichxubi/guardar', function() {
    DB::query("SET NAMES 'utf8mb4'");

    // Obtener payload (JSON o form-data)
    $req = Flight::request()->data->getData();
    if (empty($req) && ($raw = Flight::request()->getBody())) {
        $tmp = json_decode($raw, true);
        if (is_array($tmp)) $req = $tmp;
    }

    // Campos requeridos/opcionales
    $fich_id        = isset($req['fich_id'])      ? (int)$req['fich_id']      : 0;
    $nombre_local   = trim($req['nombre_local']    ?? '');
    $direccion      = trim($req['direccion']       ?? '');
    $map_lat        = trim($req['map_lat']         ?? '');
    $map_lng        = trim($req['map_lng']         ?? '');
    $provincia      = trim($req['provincia']       ?? '');
    $ciudad         = trim($req['ciudad']          ?? '');
    $departamento   = trim($req['departamento']    ?? '');
    $fecha_creacion = trim($req['fecha_creacion']  ?? '');
    $neg_id         = isset($req['neg_id'])        ? (int)$req['neg_id']      : 0;

    // Extras opcionales para `neg`
    $place_id           = trim($req['place_id']            ?? '');
    $celular_informes   = trim($req['celular_informes']    ?? '');
    $img_logo           = trim($req['img_logo']            ?? '');

    // Validación mínima
    if (!$fich_id || $nombre_local === '' || $direccion === '' || $fecha_creacion === '') {
        Flight::halt(400, json_encode([
            'error' => 'fich_id, nombre_local, direccion y fecha_creacion son requeridos'
        ], JSON_UNESCAPED_UNICODE));
    }

    try {
        DB::startTransaction();

        // 1) Usa neg_id si viene y existe
        if ($neg_id > 0) {
            $existeNeg = DB::queryFirstField("SELECT 1 FROM neg WHERE neg_id = %i LIMIT 1", $neg_id);
            if (!$existeNeg) $neg_id = 0;
        }

        // 2) Busca por place_id si no hay neg_id resuelto
        if ($neg_id <= 0 && $place_id !== '') {
            $neg_id = (int)(DB::queryFirstField(
                "SELECT neg_id FROM neg WHERE place_id = %s LIMIT 1",
                $place_id
            ) ?: 0);
        }

        // 3) Busca por NOMBRE + COORDENADAS exactas
        if ($neg_id <= 0 && $nombre_local !== '' && $map_lat !== '' && $map_lng !== '') {
            $neg_id = (int)(DB::queryFirstField("
                SELECT neg_id
                FROM neg
                WHERE LOWER(TRIM(nombre)) = LOWER(TRIM(%s))
                  AND TRIM(COALESCE(map_lat, '')) = TRIM(%s)
                  AND TRIM(COALESCE(map_lng, '')) = TRIM(%s)
                LIMIT 1
            ", $nombre_local, $map_lat, $map_lng) ?: 0);
        }

        // 4) Crear o reutilizar NEG y SIEMPRE tocar fecha_ultimo_acceso
        if ($neg_id <= 0) {
            // Nuevo NEG: guarda fecha_ultimo_acceso en el INSERT
            DB::insert('neg', [
                'nombre'              => $nombre_local,
                'celular_informes'    => $celular_informes !== '' ? $celular_informes : null,
                'fecha_creacion'      => $fecha_creacion, // o DB::sqleval('NOW()')
                'is_activo'           => 1,
                'ciudad'              => $ciudad       !== '' ? $ciudad       : null,
                'provincia'           => $provincia    !== '' ? $provincia    : null,
                'departamento'        => $departamento !== '' ? $departamento : null,
                'map_lat'             => $map_lat      !== '' ? $map_lat      : null,
                'map_lng'             => $map_lng      !== '' ? $map_lng      : null,
                'place_id'            => $place_id     !== '' ? $place_id     : null,
                'direccion'           => $direccion    !== '' ? $direccion    : null,
                'is_validado'         => 0,
                'img_logo'            => $img_logo     !== '' ? $img_logo     : null,
                'fecha_ultimo_acceso' => DB::sqleval('NOW()')  // <-- aquí
            ]);
            $neg_id = (int) DB::insertId();
        } else {
            // NEG existente: solo toca fecha_ultimo_acceso
            DB::update(
                'neg',
                ['fecha_ultimo_acceso' => DB::sqleval('NOW()')], // <-- aquí
                'neg_id = %i',
                $neg_id
            );
        }

        // 5) Insert en `fichxubi` usando el neg_id (sea existente o recién creado)
        $fecha_hora_inicio = trim($req['fecha_hora_inicio'] ?? ''); // ⬅️ nuevo
        if ($fecha_hora_inicio === '') {
            $fecha_hora_inicio = date('Y-m-d H:i:s'); // default ahora
        }
        // ...
        DB::insert('fichxubi', [
            'fich_id'        => $fich_id,
            'nombre_local'   => $nombre_local,
            'direccion'      => $direccion,
            'map_lat'        => $map_lat,
            'map_lng'        => $map_lng,
            'departamento'   => $departamento,
            'provincia'      => $provincia,
            'ciudad'         => $ciudad,
            'fecha_creacion' => $fecha_creacion,
            'neg_id'         => $neg_id,
            'fecha_hora_inicio' => $fecha_hora_inicio   // ⬅️ nuevo
        ]);
        $fichxubi_id = (int) DB::insertId();

        DB::commit();

        Flight::json([
            'status'       => 'ok',
            'neg_id'       => $neg_id,
            'fichxubi_id'  => $fichxubi_id,
            'modo'         => ($place_id || ($map_lat && $map_lng)) ? 'reutilizado_o_creado' : 'creado'
        ]);
    } catch (\MeekroDBException $e) {
        DB::rollback();
        Flight::halt(500, json_encode([
            'error'   => 'Error al guardar datos',
            'details' => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE));
    }
});


// GET /ion/fichxubi/actual?usu_id=123&fecha=2025-09-12
Flight::route('GET /ion/fichxubi/actual', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        include DEFINITION; // si lo usas en otros endpoints

        // ───────── Parámetros ─────────
        $usu_id  = intval($_GET['usu_id']  ?? 0);
        $fich_id = intval($_GET['fich_id'] ?? 0);
        $fecha   = trim((string)($_GET['fecha'] ?? ''));
        if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d'); // hoy por defecto
        }
        $ini = $fecha . ' 00:00:00';
        $fin = $fecha . ' 23:59:59';

        if ($fich_id <= 0 && $usu_id <= 0) {
            Flight::json(['success' => false, 'error' => 'Falta usu_id o fich_id'], 400);
            return;
        }

        // Bases de imágenes (como en /usu/detalleUsu)
        $pics_full = BUNNY_CDN_BASE . "/" . rtrim(vari('FOTO_FICH_MAX'), '/');
        $pics_mini = BUNNY_CDN_BASE . "/" . rtrim(vari('FOTO_FICH_MINI'), '/');

        // ───────── Resolver fich_id si llega usu_id ─────────
        if ($fich_id <= 0 && $usu_id > 0) {
            $fich_id = intval(DB::queryFirstField("
                SELECT fich_id
                FROM fich
                WHERE usu_id=%i
                ORDER BY fecha_creacion DESC, fich_id DESC
                LIMIT 1
            ", $usu_id) ?? 0);

            if ($fich_id <= 0) {
                Flight::json(['success' => false, 'error' => 'No se encontró fich_id para el usu_id'], 404);
                return;
            }
        }

        // ───────── Buscar registro de HOY ─────────
        $row = DB::queryFirstRow("
            SELECT fichxubi_id, fich_id, nombre_local, direccion,
                   map_lat, map_lng, ciudad, provincia, departamento,
                   neg_id, fecha_hora_inicio
            FROM fichxubi
            WHERE fich_id=%i
              AND fecha_hora_inicio BETWEEN %s AND %s
            ORDER BY fecha_hora_inicio DESC, fichxubi_id DESC
            LIMIT 1
        ", $fich_id, $ini, $fin);

        $is_today = true;

        // ───────── Fallback: último registro histórico ─────────
        if (!$row) {
            $row = DB::queryFirstRow("
                SELECT fichxubi_id, fich_id, nombre_local, direccion,
                       map_lat, map_lng, ciudad, provincia, departamento,
                       neg_id, fecha_hora_inicio
                FROM fichxubi
                WHERE fich_id=%i
                ORDER BY fecha_hora_inicio DESC, fichxubi_id DESC
                LIMIT 1
            ", $fich_id);

            $is_today = false;
        }

        if (!$row) {
            Flight::json(['success' => false, 'error' => 'No se encontró ubicación actual'], 404);
            return;
        }

        // Normaliza "data"
        $out = [
            'fichxubi_id'       => (int)$row['fichxubi_id'],
            'fich_id'           => (int)$row['fich_id'],
            'neg_id'            => ($row['neg_id'] !== null ? (int)$row['neg_id'] : null),
            'nombre_local'      => (string)($row['nombre_local'] ?? ''),
            'direccion'         => (string)($row['direccion'] ?? ''),
            'map_lat'           => ($row['map_lat'] !== null ? (string)$row['map_lat'] : null),
            'map_lng'           => ($row['map_lng'] !== null ? (string)$row['map_lng'] : null),
            'ciudad'            => (string)($row['ciudad'] ?? ''),
            'provincia'         => (string)($row['provincia'] ?? ''),
            'departamento'      => (string)($row['departamento'] ?? ''),
            'fecha_hora_inicio' => (string)$row['fecha_hora_inicio'],
            'is_today'          => $is_today,
            'fecha_consulta'    => $fecha
        ];

        // ───────── Compañeras en el mismo local (hoy) ─────────
        $companeras = [];
        $neg_id = intval($row['neg_id'] ?? 0);

        if ($neg_id > 0) {
            // 1) Todas las fichas que hoy están en este neg_id
            $rowsComp = DB::query("
                SELECT fxu.fichxubi_id, fxu.fich_id,
                       f.usu_id,
                       u.sobrenombre, u.img_perfil
                FROM fichxubi fxu
                JOIN fich f ON f.fich_id = fxu.fich_id
                JOIN usu  u ON u.usu_id  = f.usu_id
                WHERE fxu.neg_id=%i
                  AND fxu.fecha_hora_inicio BETWEEN %s AND %s
                ORDER BY u.sobrenombre ASC, fxu.fichxubi_id DESC
            ", $neg_id, $ini, $fin);

            // 2) Mapa de fich_id -> servicios con descripción
            $fichIds = array_values(array_unique(array_map(fn($r) => (int)$r['fich_id'], $rowsComp)));
            $servMap = [];
            if ($fichIds) {
                $svcRows = DB::query("
                    SELECT fs.fich_id, fs.serv_id, fs.tiempo, fs.precio, s.descripcion
                    FROM fichxserv fs
                    JOIN serv s ON s.serv_id = fs.serv_id
                    WHERE fs.fich_id IN %li
                ", $fichIds);

                foreach ($svcRows as $s) {
                    $fid = (int)$s['fich_id'];
                    if (!isset($servMap[$fid])) $servMap[$fid] = [];
                    $servMap[$fid][] = [
                        'serv_id'     => (int)$s['serv_id'],
                        'tiempo'      => ($s['tiempo'] !== null ? (int)$s['tiempo'] : null),
                        'precio'      => ($s['precio'] !== null ? (int)$s['precio'] : null),
                        'descripcion' => (string)($s['descripcion'] ?? '')
                    ];
                }
            }

            // 3) Armar salida de compañeras
            foreach ($rowsComp as $r) {
                $fid = (int)$r['fich_id'];
                $companeras[] = [
                    'usu_id'      => (int)$r['usu_id'],
                    'fich_id'     => $fid,
                    'sobrenombre' => (string)($r['sobrenombre'] ?? ''),
                    'img_perfil'  => (string)($r['img_perfil'] ?? ''),
                    'pics'        => ['mini' => $pics_mini, 'full' => $pics_full],
                    'servicios'   => array_values($servMap[$fid] ?? [])
                ];
            }
        }

        Flight::json([
            'success'    => true,
            'data'       => $out,
            'companeras' => $companeras
        ]);
    } catch (Throwable $e) {
        error_log('/ion/fichxubi/actual: ' . $e->getMessage());
        Flight::json(['success' => false, 'error' => 'Error interno del servidor'], 500);
    }
});

// GET /ion/fichxubi/hoy?neg_id=123
Flight::route('GET /ion/fichxubi/hoy', function () {
  include DEFINITION;
  DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

  $neg_id = intval($_GET['neg_id'] ?? 0);
  if ($neg_id <= 0) { Flight::json([]); return; }

  // Si tu DB está en otra zona horaria, puedes fijarla por sesión:
  // DB::query("SET time_zone = '-05:00'"); // Lima

  $rows = DB::query("
    SELECT 
      f.fich_id,
      u.usu_id,
      u.img_perfil,
      MAX(fx.fecha_hora_inicio) AS last_time,
      %s AS mini,
      %s AS full
    FROM fichxubi fx
    JOIN fich f ON f.fich_id = fx.fich_id
    JOIN usu  u ON u.usu_id  = f.usu_id
    WHERE fx.neg_id = %i
      AND fx.fecha_hora_inicio >= CURDATE()
      AND fx.fecha_hora_inicio < DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    GROUP BY f.fich_id, u.usu_id, u.img_perfil
    ORDER BY last_time DESC
    LIMIT 30
  ",
    BUNNY_CDN_BASE .'/'.rtrim(vari('FOTO_FICH_MINI'),'/'),
    BUNNY_CDN_BASE .'/'.rtrim(vari('FOTO_FICH_MAX'),'/'),
    $neg_id
  );

  $out = array_map(function($r){
    return [
      'usu_id'     => (int)$r['usu_id'],
      'fich_id'    => (int)$r['fich_id'],
      'img_perfil' => $r['img_perfil'],
      'pics'       => ['mini'=>$r['mini'], 'full'=>$r['full']]
      // 'last_time' => $r['last_time'],
    ];
  }, $rows);

  Flight::json($out);
});


/*  POST /chat/responderMensaje
    Body JSON:
    {
      "msg_id"        : 123,               // ID del mensaje que citas
      "contenido_dest": "Texto de respuesta"
    }
*/
Flight::route('POST /ion/chat/responderMensaje', function () {
    // 1) Recibir y validar
    $req      = Flight::request()->data->getData();
    $origId   = isset($req['msg_id'])           ? (int)$req['msg_id']          : 0;
    $nuevoDst = trim($req['contenido_dest'] ?? '');

    if (!$origId || $nuevoDst === '') {
        Flight::json(['success' => false, 'error' => 'Datos incompletos'], 400);
        return;
    }

    DB::query("SET NAMES 'utf8'");

    // 2) Obtener el mensaje original (incluye chat_id)
    $row = DB::queryFirstRow(
      "SELECT 
         chat_id,
         rem_id,
         dest_id,
         contenido_rem,
         tipoxmsg_id,
         map_lat,
         map_lng,
         img,
         sticker_id,
         is_una_vista
       FROM msg
       WHERE msg_id = %i",
      $origId
    );

    if (!$row) {
        Flight::json(['success' => false, 'error' => 'msg_id no encontrado'], 404);
        return;
    }

    // 3) Insertar un nuevo registro clonando los datos y cambiando contenido_dest
    $now = date('Y-m-d H:i:s');
    DB::insert('msg', [
        'chat_id'        => $row['chat_id'],
        'rem_id'         => $row['rem_id'],
        'dest_id'        => $row['dest_id'],
        'contenido_rem'  => $row['contenido_rem'],
        'contenido_dest' => $nuevoDst,
        'fecha_creacion' => $now,
        'tipoxmsg_id'    => $row['tipoxmsg_id'],
        'map_lat'        => $row['map_lat'],
        'map_lng'        => $row['map_lng'],
        'img'            => $row['img'],
        'sticker_id'     => $row['sticker_id'],
        'is_una_vista'   => $row['is_una_vista']
    ]);
    $newMsgId = DB::insertId();

    // 4) Resetear los flags de visto en el chat
    DB::update('chat', [
      'is_visto_usu1_id' => 0,
      'is_visto_usu2_id' => 0
    ], 'chat_id = %i', $row['chat_id']);

    // 5) Devolver éxito y el ID del nuevo mensaje
    Flight::json([
      'success' => true,
      'msg_id'  => $newMsgId
    ]);
});


Flight::route('POST /ion/tk_foto_clie', function () {

    $d = json_decode(Flight::request()->getBody(), true);

    $usu_id   = intval($d['usu_id']   ?? 0);
    $filename = trim($d['filename']   ?? '');

    if ($usu_id <= 0 || $filename === '') {
        Flight::json([
            'ok'  => false,
            'msg' => 'Datos inválidos (usu_id o filename)'
        ], 400);
        return;
    }

    try {

        // Guardar solo el nombre del archivo dentro de la BD
        DB::update(
            'usu',
            ['img_perfil' => $filename],
            'usu_id = %i',
            $usu_id
        );

        Flight::json([
            'ok'       => true,
            'msg'      => 'Foto actualizada',
            'filename' => $filename
        ]);

    } catch (Exception $e) {

        Flight::json([
            'ok'  => false,
            'msg' => $e->getMessage()
        ], 500);
    }
});

// POST /usu/cuentaEditar
// POST /usu/cuentaEditar
Flight::route('POST /usu/cuentaEditar', function () {
    DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $usu_id           = intval($d['usu_id'] ?? 0);
    $sobrenombre      = trim((string)($d['sobrenombre'] ?? ''));
    $descripcion      = trim((string)($d['descripcion'] ?? ''));
    $fecha_nacimiento = trim((string)($d['fecha_nacimiento'] ?? ''));
    $servicios        = (isset($d['servicios']) && is_array($d['servicios'])) ? $d['servicios'] : null;

    if ($usu_id <= 0) {
        return Flight::json(['status'=>'error','msg'=>'usu_id inválido'], 400);
    }

    if ($sobrenombre === '' || !preg_match('/^[A-Za-z0-9_]+$/', $sobrenombre)) {
        return Flight::json(['status'=>'error','msg'=>'Sobrenombre inválido'], 422);
    }

    if ($fecha_nacimiento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
        return Flight::json(['status'=>'error','msg'=>'Fecha inválida'], 422);
    }

    try {
        DB::startTransaction();

        // Asegurar longitud
        $descripcion = mb_substr($descripcion, 0, 200);

        // Actualizar usuario
        DB::query(
            "UPDATE usu
               SET sobrenombre = %s,
                   descripcion = %s,
                   fecha_nacimiento = %s
             WHERE usu_id = %i",
            $sobrenombre,
            ($descripcion === '' ? 'Sin descripcion' : $descripcion),
            ($fecha_nacimiento === '' ? null : $fecha_nacimiento),
            $usu_id
        );

        // --- Procesar SERVICIOS si corresponde (rol 2) ---
        $saved_count = 0;
        $fich_id_out = null;

        if ($servicios !== null) {
            $tipoxusu_id = intval(DB::queryFirstField(
                'SELECT tipoxusu_id FROM usu WHERE usu_id=%i', $usu_id
            ));

            if ($tipoxusu_id === 2) {

                $fich_id = intval(DB::queryFirstField(
                    'SELECT fich_id FROM fich WHERE usu_id=%i 
                     ORDER BY fecha_creacion DESC, fich_id DESC LIMIT 1',
                    $usu_id
                ) ?? 0);

                if ($fich_id <= 0) {
                    DB::insert('fich', [
                        'usu_id'         => $usu_id,
                        'fecha_creacion' => date('Y-m-d H:i:s'),
                        'is_activo'      => 1,
                        'is_validado'    => 0
                    ]);
                    $fich_id = intval(DB::insertId());
                }

                $validos = [];
                foreach ($servicios as $it) {
                    $sid = intval($it['serv_id'] ?? 0);
                    $tm  = intval($it['tiempo']  ?? 0);
                    $pr  = intval($it['precio']  ?? -1);

                    if ($sid > 0 && $tm > 0 && $pr >= 0) {
                        $validos[] = $it;
                    }
                }

                DB::delete('fichxserv', 'fich_id=%i', $fich_id);

                foreach ($validos as $v) {
                    DB::insert('fichxserv', [
                        'fich_id' => $fich_id,
                        'serv_id' => $v['serv_id'],
                        'tiempo'  => $v['tiempo'],
                        'precio'  => $v['precio']
                    ]);
                    $saved_count++;
                }

                $fich_id_out = $fich_id;
            }
        }

        // 🔥 Obtener EL USUARIO COMPLETO ACTUALIZADO (incluye descripcion)
        $usuario_full = DB::queryFirstRow("
            SELECT 
                usu_id,
                cod_usu,
                google_uid,
                email,
                img_perfil,
                sobrenombre,
                celular,
                is_activo,
                fecha_nacimiento,
                provincia,
                map_lat,
                map_lng,
                fecha_creacion,
                is_premium,
                fecha_fin_premium,
                tipoxusu_id,
                is_fantasma,
                is_acepto_terminos,
                IFNULL(descripcion, 'Sin descripcion') AS descripcion,
                fecha_ultimo_acceso
            FROM usu
            WHERE usu_id=%i
        ", $usu_id);

        DB::commit();

        return Flight::json([
            'status' => 'ok',
            'usuario' => $usuario_full,
            'servicios' => [
                'procesado'  => ($servicios !== null),
                'guardados'  => $saved_count,
                'fich_id'    => $fich_id_out
            ]
        ]);

    } catch (Exception $e) {
        DB::rollback();
        return Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});

/** 
 * Actualiza la foto de perfil del usuario (usu.img_perfil)
 * Ruta: POST /ion/usu/actualizar_img_perfil
 * Body esperado:
 * {
 *   "usu_id": 9,
 *   "img_perfil": "20251111_010930_910-xxxx.jpg"
 * }
 */
Flight::route('POST /ion/usu/actualizar_img_perfil', function() {

    include DEFINITION;

    $req = Flight::request()->data->getData();

    $usu_id     = intval($req['usu_id'] ?? 0);
    $img_perfil = trim($req['img_perfil'] ?? '');

    // ============================
    // VALIDACIONES BÁSICAS
    // ============================
    if ($usu_id <= 0) {
        Flight::json([
            "status"  => "error",
            "mensaje" => "usu_id inválido"
        ]);
        return;
    }

    if ($img_perfil === '') {
        Flight::json([
            "status"  => "error",
            "mensaje" => "img_perfil no enviado"
        ]);
        return;
    }

    // ============================
    // VALIDAR QUE USUARIO EXISTE
    // ============================
    $existe = DB::queryFirstField("SELECT COUNT(*) FROM usu WHERE usu_id = %i", $usu_id);

    if (!$existe) {
        Flight::json([
            "status"  => "error",
            "mensaje" => "El usuario no existe"
        ]);
        return;
    }

    // ============================
    // REALIZAR UPDATE
    // ============================
    DB::update('usu', [
        "img_perfil" => $img_perfil
    ], "usu_id=%i", $usu_id);

    // ============================
    // RESPUESTA
    // ============================
    Flight::json([
        "status"  => "ok",
        "mensaje" => "Foto de perfil actualizada",
        "usu_id"  => $usu_id,
        "img"     => $img_perfil
    ]);
});
