<?php
/* -------------------------------
 * Vista USUARIO
 * ------------------------------- */
Flight::route('GET /usu/inicio', function () {
    include DEFINITION;
    autentificar_administrador();
    require_once VARPATH . '/public/admin/tab_usu/inicio.php';
});

Flight::route('GET /usuario/listar', function(){
    include DEFINITION;
    try{
        DB::query("SET NAMES 'utf8mb4'");

        $rows = DB::query("
            SELECT 
                u.usu_id,
                u.cod_usu,
                u.img_perfil,
                u.sobrenombre,
                u.nombres_apellidos,
                u.fecha_nacimiento,
                u.celular,
                u.clavel,
                u.provincia,
                u.fecha_creacion,
                u.tipoxusu_id,
                u.dni,
                u.google_uid,
                r.nombre AS rol_nombre,
                u.is_activo,
                -- 🔥 AGREGAMOS EL ID DEL NEGOCIO AQUÍ
                n.neg_id, 
                IFNULL(NULLIF(n.nombre,''), '—') AS negocio_nombre
            FROM reg_usu u
            LEFT JOIN reg_rol r 
                ON r.rol_id = u.rol_id
            LEFT JOIN reg_negxusu nxu
                ON nxu.usu_id = u.usu_id
                AND nxu.is_activo = 1
                AND nxu.borrado_el IS NULL
            LEFT JOIN reg_neg n
                ON n.neg_id = nxu.neg_id
            WHERE u.borrado_el IS NULL    
            ORDER BY u.usu_id DESC
        ");

        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($rows, JSON_UNESCAPED_UNICODE);
        exit;

    }catch(Exception $e){
        if (ob_get_length()) ob_clean();
        echo json_encode(['status'=>'error','msg'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
        exit;
    }
});

Flight::route('GET /tipoxusu/listar', function(){
    include DEFINITION;

    $rows = DB::query('SELECT * FROM reg_tipoxusu ORDER BY 1 DESC');
    Flight::json($rows);
});

Flight::route('POST /reg/usuario/crear', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        $d = json_decode(Flight::request()->getBody(), true) ?: [];

        // Requerido
        $googleUid = trim((string)($d['google_uid'] ?? ''));
        if ($googleUid === '') {
            Flight::json(['success' => false, 'error' => 'Falta google_uid'], 400);
            return;
        }

        // Opcional: email (no sobreescribe si viene vacío)
        $email = isset($d['email']) ? trim((string)$d['email']) : '';
        if ($email === '') $email = null;                  // normaliza vacío a NULL
        if ($email !== null && strlen($email) > 200) {     // por si acaso
            $email = substr($email, 0, 200);
        }

        // ¿Ya existe por google_uid?
        $u = DB::queryFirstRow(
            "SELECT usu_id, cod_usu, google_uid, tipoxusu_id, email
               FROM usu
              WHERE google_uid = %s",
            $googleUid
        );

        if ($u) {
            // actualizar último acceso y, si llegó email, guardarlo
            $upd = ['fecha_ultimo_acceso' => date('Y-m-d H:i:s')];
            if ($email !== null && $email !== ($u['email'] ?? null)) {
                $upd['email'] = $email;
            }
            if (!empty($upd)) {
                DB::update('usu', $upd, "usu_id=%i", $u['usu_id']);
            }

            $tipox = (int)($u['tipoxusu_id'] ?? 0);
            $ruta  = in_array($tipox, [1,2], true) ? '/tabs/tab1' : '/mod11/1';

            Flight::json([
                'success'     => true,
                'usu_id'      => (int)$u['usu_id'],
                'cod_usu'     => $u['cod_usu'],
                'google_uid'  => $u['google_uid'],
                'tipoxusu_id' => $tipox ?: null,
                'ruta'        => $ruta
            ]);
            return;
        }

        // Crear nuevo
        // Genera cod_usu único (5 dígitos + 1 letra)
        $tries = 0;
        do {
            $tries++;
            $codUsu = str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT) . chr(random_int(65, 90));
            $existe = DB::queryFirstField("SELECT 1 FROM usu WHERE cod_usu=%s", $codUsu);
        } while ($existe && $tries < 20);

        DB::insert('usu', [
            'cod_usu'            => $codUsu,
            'google_uid'         => $googleUid,
            'img_perfil'         => '1.jpg',
            'sobrenombre'        => null,
            'celular'            => null,
            'fecha_nacimiento'   => null,
            'provincia'          => null,
            'map_lat'            => null,
            'map_lng'            => null,
            'fecha_creacion'     => date('Y-m-d H:i:s'),
            'is_activo'          => 1,
            'is_premium'         => 1,
            'fecha_fin_premium'  => null,
            'tipoxusu_id'        => null,
            'is_fantasma'        => 0,
            'is_acepto_terminos' => 0,
            'descripcion'        => 'Sin descripcion',
            'fecha_ultimo_acceso'=> date('Y-m-d H:i:s'),
            'email'              => $email   // <<-- se guarda si llegó; NULL si no
        ]);
        $newId = DB::insertId();

        try { enviarMensajesBienvenida((int)$newId, 1); } catch (Throwable $ex) { error_log($ex->getMessage()); }

        Flight::json([
            'success'     => true,
            'usu_id'      => (int)$newId,
            'cod_usu'     => $codUsu,
            'google_uid'  => $googleUid,
            'tipoxusu_id' => null,
            'ruta'        => '/mod04'
        ]);
    } catch (\MeekroDBException $e) {
        Flight::json(['success' => false, 'error' => 'DB: '.$e->getMessage()], 500);
    } catch (Throwable $e) {
        Flight::json(['success' => false, 'error' => 'PHP: '.$e->getMessage().' @ '.$e->getFile().':'.$e->getLine()], 500);
    }
});


/* ---- Editar ---- */
Flight::route('POST /usuario/editar', function () {
    $d = json_decode(Flight::request()->getBody(), true);

    DB::update('usu', [
        'cod_usu'        => $d['cod_usu']        ?? null,
        'google_uid'     => $d['google_uid']     ?? null,
        'img_perfil'     => $d['img_perfil']     ?? null,
        'sobrenombre'    => $d['sobrenombre']    ?? '',
        'celular'        => $d['celular']        ?? null,
        'fecha_nacimiento'=> $d['fecha_nacimiento']?? null,
        'provincia'      => $d['provincia']      ?? null,
        'map_lat'        => $d['map_lat']        ?? null,
        'map_lng'        => $d['map_lng']        ?? null,
        'is_activo'      => $d['is_activo']      ?? 1,
        'is_premium'     => $d['is_premium']     ?? 0,
        'fecha_fin_premium'=> $d['fecha_fin_premium']?? null,
        'tipoxusu_id'    => $d['tipoxusu_id']    ?? 1,
        'is_fantasma'    => $d['is_fantasma']    ?? 0
    ], 'usu_id = %i', $d['usu_id']);

    Flight::json(['success' => true]);
});

Flight::route('POST /usuario/liquidar', function () {

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
    if(!$usu_id){

        Flight::json([
            'success' => false,
            'msg' => 'usu_id requerido'
        ],400);

        return;
    }

    /* ======================================
       OBTENER USUARIO
    ====================================== */
    $u = DB::queryFirstRow("
        SELECT *
        FROM reg_usu
        WHERE usu_id = %i
        LIMIT 1
    ", $usu_id);

    if(!$u){

        Flight::json([
            'success' => false,
            'msg' => 'Usuario no encontrado'
        ],404);

        return;
    }

    /* ======================================
       RESPALDO JSON
    ====================================== */
    $backup_json = json_encode(
        $u,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );

    /* ======================================
       GENERAR NUEVO CÓDIGO
    ====================================== */
    $base_cod = trim($u['cod_usu']);

    if($base_cod == ''){
        $base_cod = 'USUARIO_'.$usu_id;
    }

    $nuevo_cod = 'LIQ_' . $base_cod;

    /* ======================================
       VALIDAR DUPLICADOS
    ====================================== */
    $existe = DB::queryFirstField("
        SELECT COUNT(*)
        FROM reg_usu
        WHERE (
            cod_usu = %s
            OR google_uid = %s
        )
        AND usu_id <> %i
    ", $nuevo_cod, $nuevo_cod, $usu_id);

    if($existe > 0){

        $nuevo_cod = 'LIQ_' . $base_cod . '_' . $usu_id;

        $existe2 = DB::queryFirstField("
            SELECT COUNT(*)
            FROM reg_usu
            WHERE (
                cod_usu = %s
                OR google_uid = %s
            )
            AND usu_id <> %i
        ", $nuevo_cod, $nuevo_cod, $usu_id);

        if($existe2 > 0){

            Flight::json([
                'success' => false,
                'msg' => 'No se pudo generar código único'
            ],400);

            return;
        }
    }

    /* ======================================
       LIQUIDAR
    ====================================== */
    DB::update('reg_usu', [

        'cod_usu'            => $nuevo_cod,

        'google_uid'         => $nuevo_cod,

        'nombres_apellidos'  => $nuevo_cod,

        'email'              => $nuevo_cod,

        'img_perfil'         => 'https://barsi-img.b-cdn.net/recursos/logo-regentis.png',

        'sobrenombre'        => 'liquidado_' . $nuevo_cod,

        'celular'            => '0',

        'dni'                => null,

        'provincia'          => null,

        'fecha_nacimiento'   => null,

        'is_activo'          => 0,

        'borrado_el'  =>  date('Y-m-d H:i:s'),

        'descripcion'        => $backup_json

    ], 'usu_id = %i', $usu_id);

    /* ======================================
       RESPONSE
    ====================================== */
    Flight::json([

        'success' => true,

        'usu_id' => $usu_id,

        'nuevo_cod' => $nuevo_cod

    ]);

});

/* ---- Eliminar ---- */
Flight::route('POST /usuario/eliminar', function () {
    $d = json_decode(Flight::request()->getBody(), true);
    DB::delete('usu', 'usu_id = %i', $d['usu_id']);
    Flight::json(['success' => true]);
});

/* ---- Buscar por sobrenombre vía POST ---- */
Flight::route('POST /usuario/buscar', function () {
    $d = json_decode(Flight::request()->getBody(), true);
    $texto = trim($d['sobrenombre'] ?? '');
    if ($texto === '') {
        Flight::json([]);
        return;
    }
    DB::query("SET NAMES 'utf8'");
    $like = '%' . $texto . '%';
    $rows = DB::query(
        'SELECT * 
         FROM usu 
         WHERE sobrenombre LIKE %s 
         ORDER BY usu_id DESC',
        $like
    );
    Flight::json($rows);
});


/* -------------------------------
 * Chat y Mensaje
 * ------------------------------- */
/* ---- Chats de un usuario ---- */
Flight::route('GET /chat/listar/@uid', function ($uid) {
    DB::query("SET NAMES 'utf8mb4'");
    $pics_avatar = BUNNY_CDN_BASE . "/" . vari('FOTO_FICH_MINI') . "/";

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
        FROM reg_chat ch
        JOIN reg_usu u1 ON u1.usu_id = ch.usu1_id
        JOIN reg_usu u2 ON u2.usu_id = ch.usu2_id
        WHERE ch.usu1_id = %i OR ch.usu2_id = %i
        ORDER BY ch.fecha_creacion DESC
    ",
      $pics_avatar, $pics_avatar,
      $uid, $uid,  // para mi_visto
      $uid, $uid,  // para su_visto
      $uid, $uid   // filtro
    );

    Flight::json($rows);
});


function process_chat_payload(array $chat, string $now): array {
    DB::query("SET NAMES 'utf8mb4'");

    $a = intval($chat['usu1_id'] ?? 0);
    $b = intval($chat['usu2_id'] ?? 0);
    if (!$a || !$b || $a === $b) {
        return ['success'=>false, 'error'=>'usu1_id/usu2_id inválidos'];
    }

    // Normalizamos orden del par
    $ids = [$a, $b]; sort($ids); list($u1, $u2) = $ids;

    // Acepta: {rem_id, dest_id, texto}  (legacy)
    //   o     {messages:[{rem_id,dest_id,texto,tipoxmsg_id?}, ...]}
    $msgs = [];
    if (!empty($chat['messages']) && is_array($chat['messages'])) {
        $msgs = $chat['messages'];
    } else {
        $rem   = intval($chat['rem_id']  ?? 0);
        $dest  = intval($chat['dest_id'] ?? 0);
        $texto = trim((string)($chat['texto'] ?? ''));
        if (!$rem || !$dest || $texto === '') {
            return ['success'=>false, 'error'=>'chat incompleto'];
        }
        $msgs = [[ 'rem_id'=>$rem, 'dest_id'=>$dest, 'texto'=>$texto, 'tipoxmsg_id'=>($chat['tipoxmsg_id'] ?? 1) ]];
    }

    // Validación de cada mensaje
    foreach ($msgs as $i => $m) {
        $rem   = intval($m['rem_id'] ?? 0);
        $dest  = intval($m['dest_id'] ?? 0);
        $texto = trim((string)($m['texto'] ?? ''));
        if (!in_array($rem, [$u1,$u2], true) || !in_array($dest, [$u1,$u2], true) || $rem === $dest) {
            return ['success'=>false, 'error'=>"rem_id/dest_id inválidos en messages[$i]"];
        }
        if ($texto === '') {
            return ['success'=>false, 'error'=>"texto vacío en messages[$i]"];
        }
        $msgs[$i]['texto']    = $texto;
        $msgs[$i]['textoLim'] = mb_substr($texto, 0, 400, 'UTF-8');
        $msgs[$i]['tipoxmsg_id'] = intval($m['tipoxmsg_id'] ?? 1);
    }

    try {
        DB::startTransaction();

        // Asegurar chat
        $chatId = DB::queryFirstField(
            "SELECT chat_id FROM chat WHERE usu1_id = %i AND usu2_id = %i", $u1, $u2
        );
        if (!$chatId) {
            DB::insert('chat', [
                'usu1_id'           => $u1,
                'usu2_id'           => $u2,
                'fecha_creacion'    => $now,
                'is_visible'        => 1,
                'is_visto_usu1_id'  => 0,
                'is_visto_usu2_id'  => 0,
                'ultimo_mensaje'    => '',
                'is_bloqueado'      => 0
            ]);
            $chatId = DB::insertId();
        }

        // Insertar mensajes en orden
        foreach ($msgs as $m) {
            DB::insert('msg', [
                'chat_id'        => $chatId,
                'rem_id'         => intval($m['rem_id']),
                'dest_id'        => intval($m['dest_id']),
                'contenido_rem'  => $m['texto'],
                'fecha_creacion' => $now,
                'tipoxmsg_id'    => intval($m['tipoxmsg_id']),
                'is_una_vista'   => 0
            ]);
        }

        // Actualizar preview con el último
        $last = end($msgs);
        DB::update('chat', [
            'ultimo_mensaje'    => $last['textoLim'],
            'is_visible'        => 1,
            'is_visto_usu1_id'  => 0,
            'is_visto_usu2_id'  => 0
        ], 'chat_id = %i', $chatId);

        DB::commit();
        return ['success'=>true, 'chat_id'=>$chatId, 'inserted_msgs'=>count($msgs)];
    } catch (Exception $e) {
        DB::rollback();
        return ['success'=>false, 'error'=>'Error chat: '.$e->getMessage()];
    }
}


/**
 * Crea/actualiza chat y registra mensaje.
 * Igual a tu /chat/crear, pero como helper reutilizable.
 * Espera: $chat = ['usu1_id','usu2_id','rem_id','dest_id','texto']
 */
function process_chat_create_and_message(array $chat, string $now): array {
    $a     = intval($chat['usu1_id'] ?? 0);
    $b     = intval($chat['usu2_id'] ?? 0);
    $rem   = intval($chat['rem_id']  ?? 0);
    $dest  = intval($chat['dest_id'] ?? 0);
    $texto = trim((string)($chat['texto'] ?? ''));

    if (!$a || !$b || !$rem || !$dest || $texto === '') {
        return ['success'=>false, 'error'=>'chat incompleto'];
    }
    if ($a === $b) {
        return ['success'=>false, 'error'=>'No puedes crear chat contigo mismo'];
    }

    DB::query("SET NAMES 'utf8mb4'");
    $textoLim = mb_substr($texto, 0, 400, 'UTF-8');

    try {
        DB::startTransaction();

        $ids = [$a, $b];
        sort($ids);
        list($u1, $u2) = $ids;

        if (!in_array($rem, [$u1,$u2], true) || !in_array($dest, [$u1,$u2], true) || $rem === $dest) {
            DB::rollback();
            return ['success'=>false, 'error'=>'rem_id/dest_id inválidos'];
        }

        $chatId = DB::queryFirstField(
            "SELECT chat_id FROM chat WHERE usu1_id = %i AND usu2_id = %i",
            $u1, $u2
        );

        if ($chatId) {
            DB::update('chat', [
                'ultimo_mensaje'    => $textoLim,
                'is_visible'        => 1,
                'is_visto_usu1_id'  => 0,
                'is_visto_usu2_id'  => 0
            ], 'chat_id = %i', $chatId);
        } else {
            DB::insert('chat', [
                'usu1_id'           => $u1,
                'usu2_id'           => $u2,
                'fecha_creacion'    => $now,
                'is_visible'        => 1,
                'is_visto_usu1_id'  => 0,
                'is_visto_usu2_id'  => 0,
                'ultimo_mensaje'    => $textoLim,
                'is_bloqueado'      => 0
            ]);
            $chatId = DB::insertId();
        }

        DB::insert('msg', [
            'chat_id'        => $chatId,
            'rem_id'         => $rem,
            'dest_id'        => $dest,
            'contenido_rem'  => $texto,
            'fecha_creacion' => $now,
            'tipoxmsg_id'    => 1,
            'is_una_vista'   => 0
        ]);

        DB::commit();
        return ['success'=>true, 'chat_id'=>$chatId];
    } catch (Exception $e) {
        DB::rollback();
        return ['success'=>false, 'error'=>'Error al crear chat: '.$e->getMessage()];
    }
}




/* ---- Selector de usuarios para dropdown ---- */
Flight::route('GET /usuario/seleccion', function() {
    DB::query("SET NAMES 'utf8'");
    $rows = DB::query(
        'SELECT usu_id AS id, sobrenombre AS text 
         FROM usu 
         WHERE is_activo = 1
         ORDER BY sobrenombre'
    );
    Flight::json($rows);
});

// Archivo: app.php o donde definas tus rutas

Flight::route('POST /app/actualizaTipoxusu', function () {
    $data = Flight::request()->data->getData();
    
    $usuario_id   = (int) ($data['usuario_id']   ?? 0);
    $tipoxusu_id  = (int) ($data['tipoxusu_id']  ?? 0);

    if (!$usuario_id || !$tipoxusu_id) {
        Flight::json(['error' => 'Datos incompletos.'], 400);
        return;
    }

    try {
        // 1) Actualizamos el rol del usuario
        DB::update('usu', [
            'tipoxusu_id' => $tipoxusu_id
        ], 'usu_id=%i', $usuario_id);

        // 2) Determinamos qué fich_id devolver
        $fich_id = 0;
        if ($tipoxusu_id !== 1) {
            // a) Buscamos un registro existente en fich
            $fich_id = (int) DB::queryFirstField(
                'SELECT fich_id FROM fich WHERE usu_id=%i',
                $usuario_id
            );
            // b) Si no existe, lo creamos
            if ($fich_id === 0) {
                $fich_id = (int) DB::insert('fich', [
                    'usu_id' => $usuario_id
                ]);
            }
        }

        // 3) Devolvemos éxito y el fich_id (0 si tipoxusu_id = 1)
        Flight::json([
            'success' => true,
            'mensaje' => 'Rol actualizado.',
            'fich_id' => $fich_id
        ]);
    } catch (Exception $e) {
        Flight::json([
            'error' => 'Error al actualizar: ' . $e->getMessage()
        ], 500);
    }
});

// Devuelve la cantidad de chats sin leer para el usuario
Flight::route('GET /chat/nuevosMensajes/@uid', function ($uid) {
    DB::query("SET NAMES 'utf8'");

    $nuevos = DB::queryFirstField(
        "SELECT COUNT(*) 
           FROM chat
          WHERE (usu1_id = %i AND is_visto_usu1_id = 0)
             OR (usu2_id = %i AND is_visto_usu2_id = 0)",
        $uid,
        $uid
    );

    Flight::json(['mensajes_nuevos' => (int)$nuevos]);
});



/*  POST /chat/responderMensaje
    Body JSON:
    {
      "msg_id"        : 123,               // ID del mensaje que citas
      "contenido_dest": "Texto de respuesta"
    }
*/
Flight::route('POST /chat/responderMensaje', function () {
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


/**
 * Basado en tu ejemplo, pero para USU.
 * Usa IMG_FULL / IMG_MINI y PICS_USU_FULL / PICS_USU_MINI
 */
function procesarImagenUsu(array $fileInfo, string $filenameBase): array {
    $si = new SimpleImage();

    // Cargar original para conocer tipo/extension
    $si->load($fileInfo['tmp_name']);
    $ext = $si->extension_imagen(); // 'jpg' | 'gif' | 'png'
    $filename = $filenameBase . '.' . $ext;

    // FULL
    $si->load($fileInfo['tmp_name']); // recarga original
    $maxFull = (int) vari('IMG_FULL');
    if ($si->getWidth() > $maxFull) {
        $si->resizeToWidth($maxFull);
    }
    $fullPath = rtrim(VARPATH, '/'). '/' . trim(vari('PICS_USU_FULL'), '/') . '/' . $filename;
    if (!file_exists(dirname($fullPath))) {
        mkdir(dirname($fullPath), 0755, true);
    }
    $si->save($fullPath, $si->tipo_de_imagen(), 75);

    // MINI
    $si->load($fileInfo['tmp_name']); // recarga original
    $maxMini = (int) vari('IMG_MINI');
    if ($si->getWidth() > $maxMini) {
        $si->resizeToWidth($maxMini);
    }
    $miniPath = rtrim(VARPATH, '/'). '/' . trim(vari('PICS_USU_MINI'), '/') . '/' . $filename;
    if (!file_exists(dirname($miniPath))) {
        mkdir(dirname($miniPath), 0755, true);
    }
    $si->save($miniPath, $si->tipo_de_imagen(), 75);

    return ['full' => $fullPath, 'mini' => $miniPath, 'filename' => $filename];
}


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

    // Valida sobrenombre: solo A-Z a-z 0-9 _
    if ($sobrenombre === '' || !preg_match('/^[A-Za-z0-9_]+$/', $sobrenombre)) {
        return Flight::json(['status'=>'error','msg'=>'Sobrenombre inválido'], 422);
    }

    // Valida fecha YYYY-MM-DD (básica)
    if ($fecha_nacimiento !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_nacimiento)) {
        return Flight::json(['status'=>'error','msg'=>'Fecha inválida (YYYY-MM-DD)'], 422);
    }

    try {
        DB::startTransaction();

        // 1) Actualiza datos de usuario
        // 1) Normaliza y corta a 200 chars por si acaso
        $descripcion = mb_substr($descripcion, 0, 200);

        // 1) Actualiza datos de usuario (UPDATE explícito)
        DB::query(
          "UPDATE usu
              SET sobrenombre = %s,
                  descripcion = %s,
                  fecha_nacimiento = %s
            WHERE usu_id = %i",
          $sobrenombre,
          $descripcion,
          ($fecha_nacimiento === '' ? null : $fecha_nacimiento),
          $usu_id
        );        

        // Confirmación inmediata (opcional pero útil para depurar)
        $check = DB::queryFirstRow("SELECT descripcion FROM usu WHERE usu_id=%i", $usu_id);
        // si quieres, inclúyelo en la respuesta:
        $descripcion_guardada = $check ? $check['descripcion'] : null;


        // 2) Si vienen servicios, solo procesa cuando tipoxusu_id = 2
        $saved_count = 0;
        $fich_id_out = null;

        if ($servicios !== null) {
            // Lee el rol actual
            $tipoxusu_id = intval(DB::queryFirstField('SELECT tipoxusu_id FROM usu WHERE usu_id=%i', $usu_id) ?? 0);

            if ($tipoxusu_id === 2) {
                // Asegura fich_id (crea si no existe)
                $fich_id = intval(DB::queryFirstField(
                    'SELECT fich_id FROM fich WHERE usu_id=%i ORDER BY fecha_creacion DESC, fich_id DESC LIMIT 1',
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

                // Normaliza/valida items. Si la lista es vacía => borra todo y listo.
                $limpios = [];
                foreach ($servicios as $i => $it) {
                    $sid = intval($it['serv_id'] ?? 0);
                    $tm  = isset($it['tiempo']) ? intval($it['tiempo']) : null;
                    $pr  = isset($it['precio']) ? intval($it['precio']) : null;

                    // Si el cliente envió el arreglo pero con “casillas” sin completar, las ignoramos (no fallamos).
                    if ($sid > 0 && $tm !== null && $tm > 0 && $pr !== null && $pr >= 0) {
                        $limpios[] = [
                            'serv_id' => $sid,
                            'tiempo'  => $tm,
                            'precio'  => $pr
                        ];
                    }
                }

                // REEMPLAZO EN BLOQUE: borra lo actual e inserta lo válido que llegó
                DB::delete('fichxserv', 'fich_id=%i', $fich_id);

                foreach ($limpios as $row) {
                    DB::insert('fichxserv', [
                        'fich_id' => $fich_id,
                        'serv_id' => $row['serv_id'],
                        'tiempo'  => $row['tiempo'],
                        'precio'  => $row['precio']
                    ]);
                    $saved_count++;
                }

                $fich_id_out = $fich_id;
            }
            // Si tipoxusu_id != 2, se ignora "servicios" silenciosamente (no es error).
        }

        DB::commit();

        return Flight::json([
            'status'      => 'ok',
            'servicios'   => [
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


// GET /servicioFichera/@fich_id
Flight::route('GET /servicioFichera/@fich_id', function ($fich_id) {
    $fich_id = intval($fich_id);
    if ($fich_id <= 0) {
        Flight::json(['status'=>'error','msg'=>'fich_id inválido'], 400);
        return;
    }

    // Opcional: charset
    DB::query("SET NAMES 'utf8mb4'");

    $rows = DB::query(
        "SELECT s.serv_id, s.descripcion
           FROM fichxserv fx
           JOIN serv s ON s.serv_id = fx.serv_id
          WHERE fx.fich_id = %i
          ORDER BY s.serv_id",
        $fich_id
    );

    $ids = array_map(fn($r) => intval($r['serv_id']), $rows);

    Flight::json([
        'fich_id'   => $fich_id,
        'servicios' => $rows,   // [{serv_id, descripcion}, ...]
        'ids'       => $ids     // [1,3,5,...] — cómodo para marcar en front
    ]);
});

Flight::route('POST /ubixusu/guardarUbicacion', function () {
    header('Content-Type: application/json; charset=utf-8');

    // Acepta JSON y form-data
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) { $data = $_POST; }

    $usu_id       = isset($data['usu_id']) ? intval($data['usu_id']) : 0;
    $map_lat      = isset($data['map_lat']) ? trim((string)$data['map_lat']) : null;
    $map_lng      = isset($data['map_lng']) ? trim((string)$data['map_lng']) : null;
    $provincia    = array_key_exists('provincia', $data) ? trim((string)$data['provincia']) : null;
    $departamento = array_key_exists('departamento', $data) ? trim((string)$data['departamento']) : null;
    $ciudad       = array_key_exists('ciudad', $data) ? trim((string)$data['ciudad']) : null;

    if ($usu_id <= 0) {
        Flight::json(['ok' => false, 'error' => 'Parámetro usu_id inválido'], 400);
        return;
    }
    if ($map_lat === null || $map_lat === '' || $map_lng === null || $map_lng === '') {
        Flight::json(['ok' => false, 'error' => 'map_lat y map_lng son requeridos'], 400);
        return;
    }

    try {
        DB::insert('ubixusu', [
            'usu_id'         => $usu_id,
            'map_lat'        => $map_lat,
            'map_lng'        => $map_lng,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'provincia'      => ($provincia !== '') ? $provincia : null,
            'departamento'   => ($departamento !== '') ? $departamento : null,
            'ciudad'         => ($ciudad !== '') ? $ciudad : null,
        ]);
        $nuevoId = DB::insertId();  

        Flight::json(['ok' => true, 'ubixusu_id' => $nuevoId, 'message' => 'Ubicación guardada'], 200);
    } catch (\MeekroDBException $e) {
        // error_log($e->getMessage());
        Flight::json(['ok' => false, 'error' => 'Error de base de datos'], 500);
    }
});


Flight::route('GET /chat/mensajesNoVistos/@uid', function ($uid) {
  DB::query("SET NAMES 'utf8'");

  $uid = (int)$uid;

  // Suma todos los mensajes dirigidos a @uid con msg_id > last_seen del usuario
  $row = DB::queryFirstRow("
    SELECT COALESCE(SUM(cnt),0) AS total FROM (
      SELECT COUNT(*) AS cnt
      FROM chat c
      JOIN msg  m ON m.chat_id = c.chat_id 
                 AND m.dest_id = %i
                 AND m.msg_id  > c.last_seen_msg_id_u1
      WHERE c.usu1_id = %i

      UNION ALL

      SELECT COUNT(*) AS cnt
      FROM chat c
      JOIN msg  m ON m.chat_id = c.chat_id 
                 AND m.dest_id = %i
                 AND m.msg_id  > c.last_seen_msg_id_u2
      WHERE c.usu2_id = %i
    ) x
  ", $uid, $uid, $uid, $uid);

  Flight::json(['mensajes_nuevos' => (int)$row['total']]);
});


Flight::route('POST /chat/history', function () {
    // Charset
    DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

    // Zona horaria Lima (ajústala si tu server ya está en Lima)
    date_default_timezone_set('America/Lima');

    $body  = json_decode(Flight::request()->getBody(), true) ?: [];
    $usuId = intval($body['usu_id'] ?? 0);
    $botId = intval($body['bot_id'] ?? 0);

    // Parámetros opcionales
    $hours = intval($body['hours'] ?? 4);
    $limit = intval($body['limit'] ?? 200);

    // Sanitizar / acotar
    if ($hours < 1)  $hours = 1;
    if ($hours > 72) $hours = 72;      // como máximo 72h
    if ($limit < 1)  $limit = 1;
    if ($limit > 500)$limit = 500;     // límite superior de seguridad

    // Validación mínima
    if ($usuId <= 0 || $botId <= 0 || $usuId === $botId) {
        return Flight::json([
            'success' => false,
            'error'   => 'Parámetros inválidos: usu_id y bot_id deben ser > 0 y distintos'
        ], 400);
    }

    // Ventana de tiempo: "últimas N horas de HOY"
    // Calculamos ahora y medianoche de hoy; el inicio es el máximo de (ahora - hours, medianoche)
    $now      = new DateTime('now', new DateTimeZone('America/Lima'));
    $midnight = (clone $now)->setTime(0, 0, 0);
    $start    = (clone $now)->modify("-{$hours} hours");
    if ($start < $midnight) {
        $start = $midnight;
    }
    $from = $start->format('Y-m-d H:i:s');
    $to   = $now->format('Y-m-d H:i:s');

    try {
        // Normalizar el par (usu1_id < usu2_id)
        $ids = [$usuId, $botId];
        sort($ids);
        list($u1, $u2) = $ids;

        // Buscar chat (si no existe, devolvemos lista vacía)
        $chatId = DB::queryFirstField(
            "SELECT chat_id FROM chat WHERE usu1_id = %i AND usu2_id = %i",
            $u1, $u2
        );

        if (!$chatId) {
            return Flight::json([
                'success'     => true,
                'chat_id'     => null,
                'window_from' => $from,
                'window_to'   => $to,
                'hours'       => $hours,
                'count'       => 0,
                'messages'    => []
            ], 200);
        }

        // Traer mensajes dentro de la ventana (orden ascendente)
        $rows = DB::query(
            "SELECT 
                 msg_id,
                 rem_id,
                 dest_id,
                 contenido_rem   AS texto,
                 fecha_creacion  AS fecha,
                 tipoxmsg_id
             FROM msg
             WHERE chat_id = %i
               AND fecha_creacion BETWEEN %s AND %s
             ORDER BY msg_id ASC
             LIMIT %i",
            $chatId, $from, $to, $limit
        );

        // Mapear a estructura limpia
        $outMsgs = [];
        foreach ($rows as $r) {
            $outMsgs[] = [
                'msg_id'      => intval($r['msg_id']),
                'rem_id'      => intval($r['rem_id']),
                'dest_id'     => intval($r['dest_id']),
                'texto'       => (string)$r['texto'],
                'fecha'       => (string)$r['fecha'],
                'tipoxmsg_id' => isset($r['tipoxmsg_id']) ? intval($r['tipoxmsg_id']) : null,
            ];
        }

        return Flight::json([
            'success'     => true,
            'chat_id'     => intval($chatId),
            'window_from' => $from,
            'window_to'   => $to,
            'hours'       => $hours,
            'count'       => count($outMsgs),
            'messages'    => $outMsgs
        ], 200);

    } catch (Exception $e) {
        return Flight::json([
            'success' => false,
            'error'   => 'Error al consultar historial: '.$e->getMessage()
        ], 500);
    }
});

// POST /pastime/upsert
// Recibe JSON con {points:[{payload:{...}}], faltantes?:[], estado?:'publicado'|'borrador', ...}
// y procesa usando save_pastime_offers(...) .
// Acepta también el envoltorio { "result": { ... } }.

Flight::route('POST /pastime/upsert', function () {
    DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

    $now  = date('Y-m-d H:i:s');
    $body = json_decode(Flight::request()->getBody(), true) ?: [];

    // Soporta plano o envuelto en result
    $res    = $body['result'] ?? $body;
    $points = $res['points'] ?? [];

    // Validación mínima
    if (!is_array($points) || !count($points)) {
        return Flight::json([
            'success' => false,
            'error'   => 'Falta el arreglo "points" con al menos un elemento',
            'hint'    => 'Envía: { "points": [ { "payload": { "usu_id":..., "hobby":"...", "consumo_min":... } } ] }'
        ], 400);
    }

    // Estado por defecto:
    // - Si vino explícito en el request (res.estado), úsalo.
    // - Si no, depende de faltantes: sin faltantes => publicado, con faltantes => borrador.
    $estadoParam = trim((string)($res['estado'] ?? ''));
    $faltantes   = $res['faltantes'] ?? [];
    $estadoDef   = $estadoParam !== ''
        ? $estadoParam
        : (empty($faltantes) ? 'publicado' : 'borrador');

    // Ejecutar lógica principal
    $offers = save_pastime_offers($points, $res, $estadoDef, $now);

    // Si hubo error en BD
    if (empty($offers['success'])) {
        return Flight::json($offers, 500);
    }

    // Respuesta OK
    return Flight::json([
        'success'         => true,
        'estado_aplicado' => $estadoDef,
        'received_points' => count($points),
        'offers'          => $offers
    ], 200);
});


// POST /pastime/set-qdrant-id
Flight::route('POST /pastime/set-qdrant-id', function () {
    DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    $body = json_decode(Flight::request()->getBody(), true) ?: [];

    $offer_id = intval($body['offer_id'] ?? 0);
    $point_id = trim((string)($body['qdrant_point_id'] ?? ''));

    if ($offer_id <= 0 || $point_id === '') {
        Flight::json(['error' => 'offer_id o qdrant_point_id inválidos'], 400);
        return;
    }

    // Idempotente: solo llenar si está vacío (ajústalo si quieres sobreescribir)
    DB::query(
        "UPDATE pastime_offer
           SET qdrant_point_id = %s, fecha_qdrant = NOW()
         WHERE offer_id = %i
           AND (qdrant_point_id IS NULL OR qdrant_point_id = '')",
        $point_id, $offer_id
    );

    Flight::json([
        'ok' => true,
        'offer_id' => $offer_id,
        'qdrant_point_id' => $point_id,
        'affected' => DB::affectedRows()
    ], 200);
});



// GET /chat/listar/@uid
Flight::route('GET /ion/chat/listadoActual/@uid', function ($uid) {
    // 0) Sanitizar/validar
    $uid = intval($uid);
    if ($uid <= 0) {
        Flight::json(['estado' => 'error', 'mensaje' => 'uid inválido'], 400);
        return;
    }

    try {
        // 1) Charset
        DB::query("SET NAMES 'utf8mb4' COLLATE utf8mb4_unicode_ci");

        // 2) Base de imágenes (equivalente a vari('PICS_USU_MINI'))
        $pics_avatar = vari('USU_MINI');

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

Flight::route('POST /chat/crear', function () {
    $d        = json_decode(Flight::request()->getBody(), true) ?: [];
    $a        = intval($d['usu1_id']      ?? 0);
    $b        = intval($d['usu2_id']      ?? 0);
    $rem      = intval($d['rem_id']       ?? 0);
    $dest     = intval($d['dest_id']      ?? 0);
    $texto    = trim((string)($d['contenido_rem'] ?? ''));
    $imagenes = is_array($d['imagenes'] ?? null) ? $d['imagenes'] : []; // [{ruta_full,ruta_mini,filename},...]

    // Validación mínima: texto O imágenes
    if (!$a || !$b || !$rem || !$dest || ($texto === '' && count($imagenes) === 0)) {
        return Flight::json(['success'=>false,'error'=>'Faltan datos'],400);
    }
    if ($a === $b) {
        return Flight::json(['success'=>false,'error'=>'No puedes crear chat contigo mismo'],400);
    }

    $ids = [$a, $b]; sort($ids); list($u1,$u2) = $ids;
    if (!in_array($rem, [$u1,$u2], true) || !in_array($dest, [$u1,$u2], true) || $rem === $dest) {
        return Flight::json(['success'=>false,'error'=>'rem_id/dest_id inválidos para el par'],400);
    }

    DB::query("SET NAMES 'utf8mb4'");
    $now      = date('Y-m-d H:i:s');
    $textoLim = mb_substr($texto, 0, 400, 'UTF-8');

    // ¿Es mensaje con imágenes?
    $esImagen = count($imagenes) > 0;
    $tipo     = $esImagen ? 2 : 1;
    $unaVista = $esImagen ? 1 : 0;

    try {
        DB::startTransaction();

        // Chat
        $chatId = DB::queryFirstField(
            "SELECT chat_id FROM chat WHERE usu1_id=%i AND usu2_id=%i",
            $u1, $u2
        );

        $ultimo = $esImagen
          ? ($textoLim !== '' ? $textoLim : '📷 Imagen')
          : $textoLim;

        if ($chatId) {
            DB::update('chat', [
                'ultimo_mensaje'    => $ultimo,
                'is_visible'        => 1,
                'is_visto_usu1_id'  => 0,
                'is_visto_usu2_id'  => 0,
                'fecha_creacion'    => DB::sqleval('NOW()')
            ], 'chat_id = %i', $chatId);
        } else {
            DB::insert('chat', [
                'usu1_id'        => $u1,
                'usu2_id'        => $u2,
                'fecha_creacion' => DB::sqleval('NOW()'),
                'is_visible'     => 1,
                'is_visto_usu1_id'=>0,
                'is_visto_usu2_id'=>0,
                'ultimo_mensaje' => $ultimo,
                'is_bloqueado'   => 0
            ]);
            $chatId = DB::insertId();
        }

        // Mensaje
        DB::insert('msg', [
            'chat_id'        => $chatId,
            'rem_id'         => $rem,
            'dest_id'        => $dest,
            'contenido_rem'  => $texto,     // puede venir vacío si hay imágenes
            'fecha_creacion' => $now,
            'tipoxmsg_id'    => $tipo,      // 2 si imagen
            'is_una_vista'   => $unaVista   // 1 si imagen
        ]);
        $msgId = DB::insertId();

        // Relacionar imágenes (si hay)
        foreach ($imagenes as $it) {
            $ruta_full = trim((string)($it['ruta_full'] ?? ''));
            $ruta_mini = trim((string)($it['ruta_mini'] ?? ''));
            if ($ruta_full && $ruta_mini) {
                DB::insert('msg_img', [
                    'msg_id'     => $msgId,
                    'ruta_full'  => $ruta_full,
                    'ruta_mini'  => $ruta_mini,
                    'is_vista_dest' => 0
                ]);
            }
        }

        DB::commit();

        Flight::json([
          'success' => true,
          'chat_id' => $chatId,
          'msg_id'  => $msgId
        ]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['success'=>false,'error'=>'Error al crear chat: '.$e->getMessage()],500);
    }
});

Flight::route('GET /msg/listar/@cid/@uid', function ($cid, $uid) {
    // 0) Sanitizar/validar
    $cid = intval($cid);
    $uid = intval($uid);
    if ($cid <= 0 || $uid <= 0) {
        return Flight::json(['error' => 'Parámetros inválidos'], 400);
    }

    $url_img = BUNNY_CDN_BASE . '/' . vari('FOTO_FICH_MAX') . '/';

    // 1) Charset
    DB::query("SET NAMES 'utf8mb4' COLLATE utf8mb4_unicode_ci");

    // 2) Traer chat y validar pertenencia
    $chat = DB::queryFirstRow(
        "SELECT chat_id, usu1_id, usu2_id
           FROM chat
          WHERE chat_id = %i",
        $cid
    );
    if (!$chat) {
        return Flight::json(['error' => 'Chat no encontrado'], 404);
    }

    $u1 = intval($chat['usu1_id']);
    $u2 = intval($chat['usu2_id']);
    if ($uid !== $u1 && $uid !== $u2) {
        return Flight::json(['error' => 'No autorizado'], 403);
    }

    // 3) Marcar "visto" para quien abrió
    if ($uid === $u1) {
        DB::update('chat', ['is_visto_usu1_id' => 1], 'chat_id = %i', $cid);
    } else {
        DB::update('chat', ['is_visto_usu2_id' => 1], 'chat_id = %i', $cid);
    }

    // 4) Registrar último msg_id visto por el usuario (si las columnas existen)
    try {
        $maxMsgId = intval(DB::queryFirstField(
            "SELECT COALESCE(MAX(msg_id), 0) FROM msg WHERE chat_id = %i",
            $cid
        ));
        if ($uid === $u1) {
            DB::update('chat', ['last_seen_msg_id_u1' => $maxMsgId], 'chat_id = %i', $cid);
        } else {
            DB::update('chat', ['last_seen_msg_id_u2' => $maxMsgId], 'chat_id = %i', $cid);
        }
    } catch (Exception $e) {
        // Silencioso si aún no tienes las columnas en prod
        // error_log('WARN last_seen update: '.$e->getMessage());
    }

    // 5) Listar mensajes (DESC)
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
         ORDER BY m.msg_id DESC
         LIMIT 200",
        $cid
    );

    if (!$rows) {
        return Flight::json([]); // sin mensajes
    }

    // 6) Cargar imágenes de todos los mensajes de una sola vez
    /*
    $msgIds = array_map(fn($r) => intval($r['msg_id']), $rows);
    $inList = implode(',', array_map('intval', $msgIds));

    $imgByMsg = [];
    if ($inList) {
        $imgRows = DB::query("
            SELECT msg_id, ruta_mini, ruta_full, is_vista_dest
              FROM msg_img
             WHERE msg_id IN ($inList)
             ORDER BY msg_img_id ASC
        ");
        foreach ($imgRows as $ir) {
            $mid = intval($ir['msg_id']);
            if (!isset($imgByMsg[$mid])) $imgByMsg[$mid] = [];
            $imgByMsg[$mid][] = [
                'ruta_mini'     => $ir['ruta_mini'],
                'ruta_full'     => $ir['ruta_full'],
                'is_vista_dest' => intval($ir['is_vista_dest'])
            ];
        }
    }
    */

    // 7) Construir salida con lógica de “una vista”
    $out = [];
    foreach ($rows as $r) {
        $msgId       = intval($r['msg_id']);
        $tipox       = intval($r['tipoxmsg_id']);
        $una         = intval($r['is_una_vista']) === 1;
        $isDestViwer = ($uid === intval($r['dest_id']));

        // imágenes asociadas (si hay)
        $allImgs = $imgByMsg[$msgId] ?? [];
        $img_total  = count($allImgs);
        $img_vistas = 0;
        /*
        foreach ($allImgs as $ii) {
            if (intval($ii['is_vista_dest']) === 1) $img_vistas++;
        }
        */

        // Aplicar filtro “una sola vista” sólo si:
        // - es mensaje de imagen (tipox=2)
        // - el flag is_una_vista=1
        // - el que lista es el DESTINATARIO
        /*
        $imagenes_out = [];
        if ($tipox === 2) {
            if ($una && $isDestViwer) {
                foreach ($allImgs as $ii) {
                    if (intval($ii['is_vista_dest']) === 0) {
                        $imagenes_out[] = [
                            'ruta_mini' => $ii['ruta_mini'],
                            'ruta_full' => $ii['ruta_full'],
                        ];
                    }
                }
            } else {
                foreach ($allImgs as $ii) {
                    $imagenes_out[] = [
                        'ruta_mini' => $ii['ruta_mini'],
                        'ruta_full' => $ii['ruta_full'],
                    ];
                }
            }
        }
        */

        // Registro final (incluye todos los campos “viejos” + nuevos contadores/imagenes)
        $out[] = [
            'msg_id'         => $r['msg_id'],
            'chat_id'        => $r['chat_id'],
            'rem_id'         => $r['rem_id'],
            'sobrenombre'    => $r['sobrenombre'],
            'contenido_rem'  => $r['contenido_rem'],
            'fecha_creacion' => $r['fecha_creacion'],
            'tipoxmsg_id'    => $r['tipoxmsg_id'],
            'map_lat'        => $r['map_lat'],
            'map_lng'        => $r['map_lng'],
            'img'            => $r['img'],          // compat anterior (imagen única)
            'sticker_id'     => $r['sticker_id'],
            'is_una_vista'   => $r['is_una_vista'],
            'dest_id'        => $r['dest_id'],
            'contenido_dest' => $r['contenido_dest'],
            // 👇 NUEVO: contadores y arreglo de imágenes múltiples
            'img_total'      => $img_total,
            'img_vistas'     => $img_vistas,
            'img_restantes'  => max(0, $img_total - $img_vistas)
            //'imagenes'       => $imagenes_out
        ];
    }

    return Flight::json($out);
});


Flight::route('POST /msg/marcarVista', function () {
    $d   = json_decode(Flight::request()->getBody(), true) ?: [];
    $msg = intval($d['msg_id'] ?? 0);
    $uid = intval($d['viewer_id'] ?? 0);
    if (!$msg || !$uid) return Flight::json(['success'=>false,'error'=>'faltan datos'],400);

    $row = DB::queryFirstRow("SELECT dest_id, is_una_vista FROM msg WHERE msg_id=%i", $msg);
    if (!$row) return Flight::json(['success'=>false,'error'=>'msg no existe'],404);

    // Solo el destinatario “consume”
    if (intval($row['is_una_vista']) === 1 && intval($row['dest_id']) === $uid) {
        DB::update('msg_img', ['is_vista_dest'=>1], 'msg_id=%i', $msg);
    }
    Flight::json(['success'=>true]);
});


// Asegura que un usu_id (rol 2) tenga ficha y devuelve fich_id
Flight::route('POST /fich/asegurar', function () {
    DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    $d = json_decode(Flight::request()->getBody(), true) ?: [];
    $usu_id = intval($d['usu_id'] ?? 0);
    if ($usu_id <= 0) { Flight::json(['success'=>false,'error'=>'usu_id inválido'],400); return; }

    $tipox = intval(DB::queryFirstField('SELECT tipoxusu_id FROM usu WHERE usu_id=%i', $usu_id) ?? 0);
    if ($tipox !== 2) { Flight::json(['success'=>false,'error'=>'Usuario no es rol 2'],422); return; }

    $fich_id = intval(DB::queryFirstField('SELECT fich_id FROM fich WHERE usu_id=%i ORDER BY fich_id DESC LIMIT 1', $usu_id) ?? 0);
    if ($fich_id <= 0) {
        DB::insert('fich', [
            'usu_id'         => $usu_id,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'is_activo'      => 1,
            'is_validado'    => 0
        ]);
        $fich_id = intval(DB::insertId());
    }
    Flight::json(['success'=>true, 'fich_id'=>$fich_id]);
});


function msg_auto(string $clave_txt, ?string $default = null): ?string
{
    try {
        // queryFirstField retorna el primer campo de la primera fila o null si no hay resultados
        $texto = DB::queryFirstField(
            "SELECT texto_msg 
               FROM auto_msg 
              WHERE clave_txt = %s 
              LIMIT 1",
            $clave_txt
        );
        return $texto ?? $default;
    } catch (Exception $e) {
        // Loguea si tienes un logger; aquí devolvemos el default
        // error_log('msg_auto error: '.$e->getMessage());
        return $default;
    }
}

/**
 * Envía 3 mensajes automáticos (INTRO, MISION, INSTRUCCIONES)
 * desde $remitenteId (por defecto 1) hacia $nuevoUsuId en un único chat.
 */
function enviarMensajesBienvenida(int $nuevoUsuId, int $remitenteId = 1): void
{
    $now = date('Y-m-d H:i:s');

    // Traemos textos desde la tabla auto_msg (si no existen, puedes poner un fallback)
    $intro = msg_auto('INTRO', '¡Bienvenido a Barsi! 🥂');
    $mision = msg_auto('MISION', 'Nuestra misión: ayudarte a crear encuentros reales y divertidos.');
    $instr = msg_auto('INSTRUCCIONES', 'Pasos: completa tu perfil, elige tu rol y chatea para coordinar.');

    // Armamos el lote de mensajes en orden
    $msgs = [];
    foreach ([$intro, $mision, $instr] as $txt) {
        $txt = trim((string)$txt);
        if ($txt !== '') {
            $msgs[] = [
                'rem_id'      => $remitenteId,
                'dest_id'     => $nuevoUsuId,
                'texto'       => $txt,
                'tipoxmsg_id' => 1, // texto
            ];
        }
    }

    if (!$msgs) {
        return; // no hay nada que enviar
    }

    // Usa tu helper para asegurar/crear chat e insertar todos los mensajes en una sola transacción
    process_chat_payload([
        'usu1_id'  => $remitenteId,
        'usu2_id'  => $nuevoUsuId,
        'messages' => $msgs
    ], $now);
}

/**
 * Genera un nombre único tipo:
 * usu_123_20250913T212045_1694659245123_a1b2c3d4e5
 */
function generarNombreUnico(int $usu_id, string $tmpPath): string {
    // timestamp legible + milisegundos
    $tsIso = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Ymd\THis');
    $ms    = (int) round((microtime(true) - floor(microtime(true))) * 1000);

    // random seguro + (opcional) hash de contenido para mayor unicidad/dedupe
    $rand  = bin2hex(random_bytes(5));                  // 10 hex
    $hash  = substr(sha1_file($tmpPath) ?: $rand, 0, 10); // si falla sha1_file, usa $rand

    return "usu_{$usu_id}_{$tsIso}_" . sprintf('%03d', $ms) . "_{$hash}";
}

function generarUID(){

    do {

        $uid = 'UID' . str_pad(
            mt_rand(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        $existe = DB::queryFirstField("

            SELECT COUNT(*)

            FROM reg_usu

            WHERE google_uid = %s

        ", $uid);

    } while($existe > 0);

    return $uid;
}


// GET /usu/fantasmas
Flight::route('GET /usu/fantasmas', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (defined('DEFINITION')) { include DEFINITION; }
        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

        // Traer SOLO fantasmas y SOLO roles 1 (cliente) o 2 (fichera)
        $rows = DB::query("
            SELECT 
                u.usu_id,
                u.tipoxusu_id,
                COALESCE(
                    NULLIF(TRIM(u.sobrenombre), ''),
                    NULLIF(TRIM(u.cod_usu), '')
                ) AS nombre
            FROM usu u
            WHERE COALESCE(u.is_fantasma, 0) = 1
              AND u.tipoxusu_id IN (1, 2)
            ORDER BY u.usu_id
        ");

        $clientes = [];
        $ficheras = [];

        foreach ($rows as $r) {
            $item = [
                'id'     => (int)$r['usu_id'],
                'nombre' => (string)($r['nombre'] ?? ''), // si está vacío, se queda vacío (sin defaults)
            ];

            if ((int)$r['tipoxusu_id'] === 1) {
                $clientes[] = $item;
            } elseif ((int)$r['tipoxusu_id'] === 2) {
                $ficheras[] = $item;
            }
        }

        echo json_encode([
            'success' => true,
            'data'    => [
                'clientes' => $clientes,
                'ficheras' => $ficheras,
                'totales'  => [
                    'clientes' => count($clientes),
                    'ficheras' => count($ficheras),
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error al obtener fantasmas',
            'error'   => $e->getMessage(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
});

function google_tokeninfo(string $idToken): array
{
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($idToken);

    // file_get_contents con timeout corto
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw !== false) {
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    // Fallback cURL (por si allow_url_fopen está off)
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
        ]);
        $out = curl_exec($ch);
        curl_close($ch);
        if ($out !== false) {
            $data = json_decode($out, true);
            return is_array($data) ? $data : [];
        }
    }
    return [];
}

Flight::route('POST /xico/usu/crear', function () {
    try {
        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

        $d = json_decode(Flight::request()->getBody(), true) ?: [];

        // 🔥 Insert
        DB::insert('reg_usu', [
          'cod_usu'            => $d['cod_usuario'] ?? null,
          'dni'                => $d['dni'] ?? null, // 🔥 ESTA ES LA CLAVE
          'google_uid'         => $d['google_uid'] ?? null,
          'img_perfil'         => $d['img_perfil'] ?? null,
          'sobrenombre'        => $d['sobrenombre'] ?? null,
          'celular'            => $d['celular'] ?? null,
          'provincia'          => $d['provincia'] ?? null,
          'fecha_nacimiento'   => $d['fecha_nacimiento'] ?? null,
          'tipoxusu_id'        => $d['tipoxusu_id'] ?? null,
          'is_activo'          => isset($d['is_activo']) ? (int)$d['is_activo'] : 1,
          'is_premium'         => isset($d['is_premium']) ? (int)$d['is_premium'] : 0,
          'fecha_fin_premium'  => $d['fecha_fin_premium'] ?? null,
          'fecha_creacion'     => date('Y-m-d H:i:s')
        ]);

        Flight::json([
            'success'=>true,
            'usu_id'=>DB::insertId()
        ]);

    } catch(Exception $e){
        Flight::json(['success'=>false,'msg'=>$e->getMessage()],500);
    }
});

Flight::route('POST /xico/usu/editar', function () {
    try {
        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");

        $d = json_decode(Flight::request()->getBody(), true) ?: [];

        // 🔥 PASO VITAL: Definir la variable $usu_id para usarla después
        $usu_id = $d['usu_id'] ?? null;

        if (empty($usu_id)) {
            Flight::json(['success'=>false,'msg'=>'usu_id requerido'], 400);
            return;
        }

        // 1. Actualizar tabla principal
        DB::update('reg_usu', [
            'cod_usu'            => $d['cod_usuario'] ?? null,
            'google_uid'         => $d['google_uid'] ?? null,
            'dni'                => $d['dni'] ?? null,
            'img_perfil'         => $d['img_perfil'] ?? null,
            'sobrenombre'        => $d['sobrenombre'] ?? null,
            'celular'            => $d['celular'] ?? null,
            'provincia'          => $d['provincia'] ?? null,
            'fecha_nacimiento'   => $d['fecha_nacimiento'] ?? null,
            'tipoxusu_id'        => $d['tipoxusu_id'] ?? null,
            'nombres_apellidos' => $d['nombres_apellidos'] ?? null,
            'is_activo'          => isset($d['is_activo']) ? (int)$d['is_activo'] : 1
        ], "usu_id=%i", $usu_id);

        // 2. 🔥 Lógica para reg_negxusu
        // 🔥 NUEVA LÓGICA DE ACTUALIZACIÓN O INSERCIÓN (UPSERT)
        if (!empty($d['neg_id'])) {
            $neg_id = (int)$d['neg_id'];

            // 1. Verificamos si el usuario ya tiene ALGÚN negocio asignado
            $relacionExistente = DB::queryFirstRow("SELECT * FROM reg_negxusu WHERE usu_id = %i", $usu_id);

            if ($relacionExistente) {
                // 2. Si ya existe una relación, simplemente actualizamos el neg_id
                DB::update('reg_negxusu', [
                    'neg_id'    => $neg_id,
                    'is_activo' => 1 // Nos aseguramos de que esté activo al editar
                ], "usu_id=%i", $usu_id);
            } else {
                // 3. Si no tiene ninguna relación previa, creamos una nueva
                DB::insert('reg_negxusu', [
                    'usu_id'          => $usu_id,
                    'neg_id'          => $neg_id,
                    'is_activo'       => 1,
                    'fecha_creacion'  => date('Y-m-d H:i:s')
                ]);
            }
        }

        Flight::json(['success' => true]);

    } catch(Exception $e){
        Flight::json(['success' => false, 'msg' => $e->getMessage()], 500);
    }
});

// En app.php o control.php
Flight::route('GET /xico/negocios/validados', function() {
    try {
        $rows = DB::query("SELECT neg_id, nombre FROM reg_neg WHERE is_validado = 1 ORDER BY nombre ASC");
        Flight::json($rows);
    } catch (Exception $e) {
        Flight::json(['success' => false, 'msg' => $e->getMessage()], 500);
    }
});

Flight::route(
    'POST /usuario/subirAvatarBunny',
    function () {

    include DEFINITION;

    autentificar_administrador();

    DB::query("SET NAMES 'utf8mb4'");

    $url    = $_POST['url'] ?? '';
    $usu_id = intval($_POST['usu_id'] ?? 0);

    /* ======================================
       VALIDAR
    ====================================== */
    if (!$url) {

        Flight::json([

            'success' => false,

            'error' => 'URL requerida'
        ]);

        return;
    }

    if (!$usu_id) {

        Flight::json([

            'success' => false,

            'error' => 'usu_id requerido'
        ]);

        return;
    }

    try {

        /* ======================================
           DESCARGAR IMAGEN
        ====================================== */

        $context = stream_context_create([

            'http' => [

                'timeout' => 30,

                'header' =>
                    "User-Agent: Mozilla/5.0\r\n"
            ]
        ]);

        $img = file_get_contents(
            $url,
            false,
            $context
        );

        if (!$img) {

            Flight::json([

                'success' => false,

                'error' =>
                    'No se pudo descargar avatar'
            ]);

            return;
        }

        /* ======================================
           TEMP FILE
        ====================================== */

        $tmp =
            tempnam(
                sys_get_temp_dir(),
                'avatar_'
            ) . '.jpg';

        file_put_contents(
            $tmp,
            $img
        );

        /* ======================================
           NOMBRE
        ====================================== */

        $filename =

            'avatar_' .

            date('Ymd_His') .

            '_' .

            rand(1000,9999) .

            '.jpg';

        /* ======================================
           URL STORAGE
        ====================================== */

        $storageUrl =

            rtrim(
                BUNNY_STORAGE_URL,
                '/'
            ) .

            '/' .

            SLIDER_DIR .

            '/' .

            $filename;

        $headers = [

            "AccessKey: " .
            BUNNY_STORAGE_ACCESSKEY,

            "Content-Type: image/jpeg"
        ];

        /* ======================================
           SUBIR BUNNY
        ====================================== */

        $fp = fopen($tmp, 'r');

        $ch = curl_init($storageUrl);

        curl_setopt(
            $ch,
            CURLOPT_PUT,
            true
        );

        curl_setopt(
            $ch,
            CURLOPT_INFILE,
            $fp
        );

        curl_setopt(
            $ch,
            CURLOPT_INFILESIZE,
            filesize($tmp)
        );

        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            $headers
        );

        curl_setopt(
            $ch,
            CURLOPT_RETURNTRANSFER,
            true
        );

        $response =
            curl_exec($ch);

        $status =
            curl_getinfo(
                $ch,
                CURLINFO_HTTP_CODE
            );

        $error =
            curl_error($ch);

        curl_close($ch);

        fclose($fp);

        unlink($tmp);

        /* ======================================
           ERROR CURL
        ====================================== */

        if ($error) {

            Flight::json([

                'success' => false,

                'error' => $error
            ]);

            return;
        }

        /* ======================================
           STATUS
        ====================================== */

        if (
            $status != 200 &&
            $status != 201
        ) {

            Flight::json([

                'success' => false,

                'error' =>
                    'Bunny HTTP ' .
                    $status,

                'response' =>
                    $response
            ]);

            return;
        }

        /* ======================================
           CDN
        ====================================== */

        $cdn =

            rtrim(
                BUNNY_CDN_BASE,
                '/'
            ) .

            '/' .

            SLIDER_DIR .

            '/' .

            $filename;

        /* ======================================
           🔥 UPDATE USUARIO
        ====================================== */

        DB::update(

            'reg_usu',

            [
                'img_perfil' => $cdn
            ],

            'usu_id=%i',

            $usu_id
        );

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'success' => true,

            'url' => $cdn
        ]);

    } catch(Exception $e){

        Flight::json([

            'success' => false,

            'error' => $e->getMessage()
        ]);
    }
});

Flight::route('POST /usuario/reiniciar', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    if(!$usu_id){

        Flight::json([
            'status' => 'error',
            'msg' => 'usu_id requerido'
        ], 400);

        return;
    }

    DB::startTransaction();

    try {

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
           RANDOMS
        ====================================== */

        $nombre = DB::queryFirstField("

            SELECT nombre

            FROM tt_nombre

            ORDER BY RAND()

            LIMIT 1

        ");

        $apellido = DB::queryFirstField("

            SELECT apellido

            FROM tt_apellido

            ORDER BY RAND()

            LIMIT 1

        ");

        $nick = DB::queryFirstField("

            SELECT nick

            FROM tt_nick

            ORDER BY RAND()

            LIMIT 1

        ");

        /* ======================================
           GENERAR DATOS
        ====================================== */

        $nombre_completo =
            trim($nombre . ' ' . $apellido);

        $dni = str_pad(
            rand(1, 99999999),
            8,
            '0',
            STR_PAD_LEFT
        );

        $celular = '9' . rand(
            10000000,
            99999999
        );

        /* ======================================
           ELIMINAR NEGOCIOS
        ====================================== */

        DB::delete(
            'reg_negxusu',
            "usu_id=%i",
            $usu_id
        );

        /* ======================================
           UPDATE USUARIO
        ====================================== */

        DB::update(
            'reg_usu',
            [

                'nombres_apellidos' =>
                    $nombre_completo,

                'sobrenombre' =>
                    $nick,

                'google_uid' =>
                    generarUID(),    

                'celular' =>
                    $celular,

                'dni' =>
                    $dni,

                'tipoxusu_id' => 1,

                'rol_id' => 1,

                'is_acepto_terminos' => 0,

                'clavel' => '12qw12',

                'img_perfil' =>
                    'https://barsi-img.b-cdn.net/recursos/logo-regentis.png'

            ],
            "usu_id=%i",
            $usu_id
        );

        DB::commit();

        Flight::json([

            'status' => 'ok',

            'msg' => 'Usuario reiniciado',

            'data' => [

                'usu_id' => $usu_id,

                'nombre' => $nombre_completo,

                'nick' => $nick,

                'dni' => $dni,

                'celular' => $celular

            ]

        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([

            'status' => 'error',

            'msg' => $e->getMessage()

        ], 500);

    }

});

Flight::route('POST /usuario/clave12', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    if(!$usu_id){

        Flight::json([
            'status' => 'error',
            'msg' => 'usu_id requerido'
        ], 400);

        return;
    }

    $usuario = DB::queryFirstRow("

        SELECT usu_id

        FROM reg_usu

        WHERE usu_id = %i

        LIMIT 1

    ", $usu_id);

    if(!$usuario){

        Flight::json([
            'status' => 'error',
            'msg' => 'Usuario no encontrado'
        ], 404);

        return;
    }

    DB::update(
        'reg_usu',
        [

            'clavel' => '12qw12'

        ],
        "usu_id=%i",
        $usu_id
    );

    Flight::json([

        'status' => 'ok',

        'msg' => 'Clave actualizada correctamente'

    ]);

});

Flight::route('POST /usuario/nuevoNegocio', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(
        Flight::request()->getBody(),
        true
    ) ?: [];

    $usu_id = intval(
        $d['usu_id'] ?? 0
    );

    if(!$usu_id){

        Flight::json([
            'status' => 'error',
            'msg' => 'usu_id requerido'
        ], 400);

        return;
    }

    $usuario = DB::queryFirstRow("

        SELECT usu_id

        FROM reg_usu

        WHERE usu_id = %i

        LIMIT 1

    ", $usu_id);

    if(!$usuario){

        Flight::json([
            'status' => 'error',
            'msg' => 'Usuario no encontrado'
        ], 404);

        return;
    }

    /* ======================================
       ENVIAR BOTÓN TIENDA
    ====================================== */

    $r = enviar_boton_tienda(
        $usu_id
    );

    /* ======================================
       FOTO RANDOM MASCOTAS
    ====================================== */

    $img_perfil = 'https://loremflickr.com/300/300/puppy?lock='
        . rand(1,999999);

    DB::update(
        'reg_usu',
        [

            'img_perfil' => $img_perfil

        ],
        "usu_id=%i",
        $usu_id
    );

    if(!$r){

        Flight::json([
            'status' => 'error',
            'msg' => 'No se pudo enviar'
        ], 500);

        return;
    }

    Flight::json([

        'status' => 'ok',

        'msg' => 'Botón enviado correctamente'

    ]);

});

Flight::route('POST /NTol/usuarioAutomatico', function(){

    include DEFINITION;

    DB::query("SET NAMES 'utf8mb4'");

    DB::startTransaction();

    try{

        /* ======================================
           RANDOMS
        ====================================== */

        $nombre = DB::queryFirstField("

            SELECT nombre

            FROM tt_nombre

            ORDER BY RAND()

            LIMIT 1

        ");

        $apellido = DB::queryFirstField("

            SELECT apellido

            FROM tt_apellido

            ORDER BY RAND()

            LIMIT 1

        ");

        $nick = DB::queryFirstField("

            SELECT nick

            FROM tt_nick

            ORDER BY RAND()

            LIMIT 1

        ");

        /* ======================================
           GENERAR DATOS
        ====================================== */

        $nombre_completo =
            trim($nombre . ' ' . $apellido);

        $dni = str_pad(

            rand(1, 99999999),

            8,

            '0',

            STR_PAD_LEFT

        );

        $celular = '9' . rand(

            10000000,

            99999999

        );

        $cod_usu =

            'ADM'

            . rand(1000,9999);

        $google_uid = strtoupper(

            substr(

                md5(
                    uniqid('',true)
                ),

                0,

                10

            )

        );

        $fecha_actual =
            date('Y-m-d H:i:s');

        /* ======================================
           INSERT USUARIO
        ====================================== */

        DB::insert(

            'reg_usu',

            [

                'cod_usu' =>
                    $cod_usu,

                'google_uid' =>
                    $google_uid,

                'email' =>
                    null,

                'img_perfil' =>

                    'https://barsi-img.b-cdn.net/recursos/logo-regentis.png',

                'sobrenombre' =>
                    $nick,

                'nombres_apellidos' =>
                    $nombre_completo,

                'celular' =>
                    $celular,

                'dni' =>
                    $dni,

                'is_activo' => 1,

                'fecha_creacion' =>
                    $fecha_actual,

                'tipoxusu_id' => 1,

                'rol_id' => 1,

                'is_fantasma' => 1,

                'is_acepto_terminos' => 0,

                'clavel' => '12qw12'

            ]

        );

        $usu_id = DB::insertId();

        DB::commit();

        /* ======================================
           RESPONSE
        ====================================== */

        Flight::json([

            'success' => true,

            'usu_id' => $usu_id,

            'usuario' => [

                'usu_id' =>
                    $usu_id,

                'cod_usu' =>
                    $cod_usu,

                'google_uid' =>
                    $google_uid,

                'img_perfil' =>

                    'https://barsi-img.b-cdn.net/recursos/logo-regentis.png',

                'sobrenombre' =>
                    $nick,

                'nombres_apellidos' =>
                    $nombre_completo,

                'celular' =>
                    $celular,

                'dni' =>
                    $dni,

                'fecha_creacion' =>
                    $fecha_actual,

                'tipoxusu_id' => 1,

                'rol_nombre' => '—',

                'negocio_nombre' => '—',

                'is_fantasma' => 1

            ]

        ]);

    }catch(Exception $e){

        DB::rollback();

        Flight::json([

            'success' => false,

            'msg' => $e->getMessage()

        ],500);

    }

});