<?php
Flight::route('GET /Z8gJ/chat/listar/@usu_id', function ($usu_id) {

    DB::query("SET NAMES 'utf8mb4'");

    $usu_id = intval($usu_id);

    if ($usu_id <= 0) {
        Flight::json(['error' => 'usu_id inválido'], 400);
        return;
    }

    $rows = DB::query("
        SELECT 
            c.chat_id,

            -- 👤 identificar el otro usuario
            CASE 
                WHEN c.usu1_id = %i THEN c.usu2_id
                ELSE c.usu1_id
            END AS otro_id,

            c.ultimo_mensaje,

            u.nombres_apellidos,
            u.sobrenombre,
            u.img_perfil,
            u.provincia,

            -- 🕒 fecha del último mensaje (más eficiente que subquery por fila)
            MAX(m.fecha_creacion) AS fecha_ultimo,

            -- 🔴 NO LEÍDOS (tipo WhatsApp)
            (
              SELECT COUNT(*)
              FROM reg_msg m2
              WHERE m2.chat_id = c.chat_id
              AND m2.msg_id >
                CASE 
                  WHEN c.usu1_id = %i THEN c.last_seen_msg_id_u1
                  ELSE c.last_seen_msg_id_u2
                END
              AND m2.dest_id = %i
            ) AS no_leidos

        FROM reg_chat c

        -- 👤 join al otro usuario
        JOIN reg_usu u ON u.usu_id = (
            CASE 
                WHEN c.usu1_id = %i THEN c.usu2_id
                ELSE c.usu1_id
            END
        )

        -- 💬 mensajes para calcular última fecha
        LEFT JOIN reg_msg m ON m.chat_id = c.chat_id

        WHERE (c.usu1_id = %i OR c.usu2_id = %i)

        GROUP BY c.chat_id

        ORDER BY fecha_ultimo DESC
    ",
        // 🔥 parámetros (orden exacto)
        $usu_id,          // CASE otro_id
        $usu_id,          // CASE no_leidos
        $usu_id,          // dest_id en no_leidos
        $usu_id,          // CASE join usuario
        $usu_id, $usu_id  // WHERE
    );

    $data = [];

    foreach ($rows as $r) {

        $data[] = [
            'chat_id' => (int)$r['chat_id'],

            'usuario' => [
                'usu_id' => (int)$r['otro_id'],
                'name' => !empty($r['nombres_apellidos'])
                    ? $r['nombres_apellidos']
                    : (!empty($r['sobrenombre'])
                        ? $r['sobrenombre']
                        : 'Usuario'),

                'avatar_mini' => !empty($r['img_perfil'])
                    ? $r['img_perfil']
                    : "https://picsum.photos/seed/" . $r['otro_id'] . "/40/40",

                'provincia' => $r['provincia']
            ],

            'ultimo_mensaje' => $r['ultimo_mensaje'] ?? '',
            'fecha_ultimo'   => $r['fecha_ultimo'],

            // 🔴 badge tipo WhatsApp
            'no_leidos' => (int)$r['no_leidos']
        ];
    }

    Flight::json([
        'ok' => true,
        'data' => $data
    ]);
});

Flight::route('GET /Z8gJ/chat/info/@usu_id/@chat_id', function ($usu_id, $chat_id) {

    DB::query("SET NAMES 'utf8mb4'");

    $usu_id = intval($usu_id);
    $chat_id = intval($chat_id);

    if ($usu_id <= 0 || $chat_id <= 0) {
        Flight::json(['error'=>'Parámetros inválidos'], 400);
        return;
    }

    $row = DB::queryFirstRow("
        SELECT 
            c.chat_id,

            CASE 
                WHEN c.usu1_id = %i THEN c.usu2_id
                ELSE c.usu1_id
            END AS otro_id,

            u.nombres_apellidos,
            u.sobrenombre,
            u.img_perfil,
            c.last_seen_msg_id_u1,
            c.last_seen_msg_id_u2
        FROM reg_chat c
        JOIN reg_usu u ON u.usu_id = (
            CASE 
                WHEN c.usu1_id = %i THEN c.usu2_id
                ELSE c.usu1_id
            END
        )

        WHERE c.chat_id = %i
    ", $usu_id, $usu_id, $chat_id);

    if (!$row) {
        Flight::json(['error'=>'Chat no encontrado'], 404);
        return;
    }

    Flight::json([
        'ok' => true,
        'usuario' => [
            'usu_id' => (int)$row['otro_id'],
            'name' => !empty($row['nombres_apellidos'])
                ? $row['nombres_apellidos']
                : $row['sobrenombre'],

            'avatar' => !empty($row['img_perfil'])
                ? $row['img_perfil']
                : "https://picsum.photos/seed/" . $row['otro_id'] . "/40/40"
        ]
    ]);
});

Flight::route('POST /Z8gJ/msg/enviar', function () {

    DB::query("SET NAMES 'utf8mb4'");

    // 📥 leer payload
    $data = json_decode(file_get_contents("php://input"), true);

    $chat_id = intval($data['chat_id'] ?? 0);
    $rem_id  = intval($data['rem_id'] ?? 0);
    $dest_id = intval($data['dest_id'] ?? 0);
    $texto   = trim($data['texto'] ?? '');

    // ❌ validación
    if ($chat_id <= 0 || $rem_id <= 0 || $dest_id <= 0 || $texto === '') {
        Flight::json(['error' => 'Datos inválidos'], 400);
        return;
    }

    // 🔥 verificar que el chat existe y pertenece al usuario
    $chat = DB::queryFirstRow("
        SELECT chat_id, usu1_id, usu2_id
        FROM reg_chat
        WHERE chat_id = %i
        AND (usu1_id = %i OR usu2_id = %i)
    ", $chat_id, $rem_id, $rem_id);

    if (!$chat) {
        Flight::json(['error' => 'No autorizado o chat inexistente'], 403);
        return;
    }

    try {

        // 🔥 USAR FUNCIÓN CENTRAL (NO DUPLICAR LÓGICA)
        insert_text_msg_app($chat_id, $rem_id, $dest_id, $texto);

        Flight::json([
            'ok' => true
        ]);

    } catch (Exception $e) {

        Flight::json([
            'error' => 'Error al enviar mensaje',
            'detalle' => $e->getMessage()
        ], 500);

    }

});

Flight::route('GET /Z8gJ/msg/listar/@chat_id/@usu_id', function ($chat_id, $usu_id) {

    DB::query("SET NAMES 'utf8mb4'");

    $chat_id = intval($chat_id);
    $usu_id  = intval($usu_id);

    // ❌ validar parámetros
    if ($chat_id <= 0 || $usu_id <= 0) {

        Flight::json([
            'error' => 'Parámetros inválidos'
        ], 400);

        return;
    }

    /* ======================================
       🔒 VALIDAR CHAT
    ====================================== */

    $chat = DB::queryFirstRow("
        SELECT 
            chat_id,
            usu1_id,
            usu2_id,
            last_seen_msg_id_u1,
            last_seen_msg_id_u2
        FROM reg_chat
        WHERE chat_id = %i
          AND (
                usu1_id = %i
                OR usu2_id = %i
          )
    ", $chat_id, $usu_id, $usu_id);

    if (!$chat) {

        Flight::json([
            'error' => 'No autorizado'
        ], 403);

        return;
    }

    /* ======================================
       📥 MENSAJES
    ====================================== */

    $rows = DB::query("
        SELECT 
            msg_id,
            chat_id,
            rem_id,
            dest_id,
            contenido_rem,
            fecha_creacion,

            -- 🔥 NUEVO
            contexto_json

        FROM reg_msg

        WHERE chat_id = %i

        ORDER BY msg_id ASC
    ", $chat_id);

    /* ======================================
       🔥 NORMALIZAR
    ====================================== */

    foreach ($rows as &$r) {

        $r['msg_id'] = intval($r['msg_id']);
        $r['chat_id'] = intval($r['chat_id']);
        $r['rem_id'] = intval($r['rem_id']);
        $r['dest_id'] = intval($r['dest_id']);

        // 🔥 parsear contexto
        if (!empty($r['contexto_json'])) {

            $decoded = json_decode($r['contexto_json'], true);

            $r['contexto_json'] = $decoded ?: null;

        } else {

            $r['contexto_json'] = null;

        }

    }

    /* ======================================
       👁️ MARCAR COMO VISTO
    ====================================== */

    $maxId = DB::queryFirstField("
        SELECT MAX(msg_id)
        FROM reg_msg
        WHERE chat_id = %i
          AND dest_id = %i
    ", $chat_id, $usu_id);

    if ($maxId) {

        // 🔥 soy usu1
        if ($chat['usu1_id'] == $usu_id) {

            DB::update('reg_chat', [
                'last_seen_msg_id_u1' => $maxId
            ], "chat_id=%i", $chat_id);

            $chat['last_seen_msg_id_u1'] = $maxId;

        } else {

            // 🔥 soy usu2
            DB::update('reg_chat', [
                'last_seen_msg_id_u2' => $maxId
            ], "chat_id=%i", $chat_id);

            $chat['last_seen_msg_id_u2'] = $maxId;
        }
    }

    /* ======================================
       🚀 RESPUESTA
    ====================================== */

    Flight::json([

        'ok' => true,

        'mensajes' => $rows,

        'chat' => [

            'usu1_id' => intval($chat['usu1_id']),
            'usu2_id' => intval($chat['usu2_id']),

            'last_seen_msg_id_u1' => intval($chat['last_seen_msg_id_u1']),
            'last_seen_msg_id_u2' => intval($chat['last_seen_msg_id_u2'])

        ]

    ]);

});

function insert_text_msg_app(int $chatId, int $rem, int $dest, string $texto, int $tipo=1): int {

    $now = date('Y-m-d H:i:s');

    DB::insert('reg_msg', [
        'chat_id'        => $chatId,
        'rem_id'         => $rem,
        'dest_id'        => $dest,
        'contenido_rem'  => $texto,
        'fecha_creacion' => $now,
        'tipoxmsg_id'    => $tipo,
        'is_una_vista'   => 0
    ]);

    $msgId = (int)DB::insertId();

    // Preview
    $preview = mb_substr($texto, 0, 400, 'UTF-8');

    // Obtener usuarios del chat
    $chat = DB::queryFirstRow("SELECT usu1_id, usu2_id FROM reg_chat WHERE chat_id=%i", $chatId);

    $update = [
        'ultimo_mensaje' => $preview
    ];

    // Marcar NO leído solo al receptor
    if ($rem == $chat['usu1_id']) {
        $update['last_seen_msg_id_u2'] = 0;
    } else {
        $update['last_seen_msg_id_u1'] = 0;
    }

    DB::update('reg_chat', $update, "chat_id=%i", $chatId);

    return $msgId;
}

Flight::route('POST /reg/tk_cant_msg', function(){

    $req = Flight::request()->data->getData();
    $uid = intval($req['usu_id']);

    if ($uid <= 0) {
        Flight::json([
            'estado' => 'error',
            'mensaje' => 'usu_id inválido'
        ], 400);
        return;
    }

    try {

        DB::query("SET NAMES 'utf8mb4' COLLATE utf8mb4_unicode_ci");

        /* ======================================
           💬 CHATS
        ====================================== */

        $rows = DB::query("
            SELECT 
                ch.chat_id,

                ch.usu1_id,
                u1.sobrenombre AS usuario1,
                CONCAT('https://picsum.photos/seed/', u1.usu_id, '/40/40') AS img_usu1,

                ch.usu2_id,
                u2.sobrenombre AS usuario2,
                CONCAT('https://picsum.photos/seed/', u2.usu_id, '/40/40') AS img_usu2,

                ch.fecha_creacion,
                ch.ultimo_mensaje,
                ch.is_bloqueado,

                (
                  SELECT COUNT(*)
                  FROM reg_msg m
                  WHERE m.chat_id = ch.chat_id
                  AND m.msg_id >
                    CASE 
                      WHEN %i = ch.usu1_id THEN ch.last_seen_msg_id_u1
                      ELSE ch.last_seen_msg_id_u2
                    END
                  AND m.dest_id = %i
                ) AS no_leidos

            FROM reg_chat ch
            JOIN reg_usu u1 ON u1.usu_id = ch.usu1_id
            JOIN reg_usu u2 ON u2.usu_id = ch.usu2_id

            WHERE ch.usu1_id = %i OR ch.usu2_id = %i

            ORDER BY ch.fecha_creacion DESC
        ",
        $uid, $uid,
        $uid, $uid
        );

        /* ======================================
           🔴 MENSAJES NUEVOS
        ====================================== */

        $nuevos = DB::queryFirstField("
            SELECT COUNT(*)
            FROM reg_msg m
            JOIN reg_chat ch ON ch.chat_id = m.chat_id
            WHERE (ch.usu1_id = %i OR ch.usu2_id = %i)
            AND m.dest_id = %i
            AND m.msg_id >
              CASE 
                WHEN %i = ch.usu1_id THEN ch.last_seen_msg_id_u1
                ELSE ch.last_seen_msg_id_u2
              END
        ", $uid, $uid, $uid, $uid);

        /* ======================================
           🛒 CARRITO (ACTIVO)
        ====================================== */

        $carrito_count = DB::queryFirstField("
            SELECT IFNULL(SUM(cd.cantidad),0)
            FROM reg_carrito c
            JOIN reg_carrito_detalle cd ON cd.carrito_id = c.carrito_id
            WHERE c.usu_id = %i
              AND c.estado = 'activo'
        ", $uid);

        /* ======================================
           📦 PEDIDOS ENVIADOS
        ====================================== */

        $carritos_enviados = DB::queryFirstField("
            SELECT COUNT(*)
            FROM reg_carrito
            WHERE usu_id = %i
              AND estado = 'enviado'
        ", $uid);

        /* ======================================
           ❤️ FAVORITOS
        ====================================== */

        $favoritos_count = DB::queryFirstField("
            SELECT COUNT(*)
            FROM reg_fav
            WHERE usu_id = %i
        ", $uid);

        /* ======================================
           📊 TRACKING
        ====================================== */

        $tracking = $req['tracking'] ?? [];

        if(is_array($tracking)){

            foreach($tracking as $t){

                $track_usu_id = intval($t['usu_id'] ?? 0);

                $accion = trim($t['accion'] ?? '');

                $descripcion = trim($t['descripcion'] ?? '');

                $extra_data = $t['extra_data'] ?? [];

                if(
                    !$track_usu_id
                    || !$accion
                ){
                    continue;
                }

                /* ======================================
                   🔥 PRODUCT_ID
                ====================================== */

                $product_id = intval(
                    $extra_data['product_id'] ?? 0
                );

                /* ======================================
                   🔥 EVITAR MÁS DE 10 POR DÍA
                ====================================== */

                if(
                    $accion === 'ver_producto'
                    && $product_id > 0
                ){

                    $count = DB::queryFirstField("

                        SELECT COUNT(*)

                        FROM reg_usuxreg

                        WHERE usu_id = %i

                        AND accion = 'ver_producto'

                        AND DATE(fecha_creacion)=CURDATE()

                        AND JSON_EXTRACT(
                            extra_data,
                            '$.product_id'
                        ) = %i

                    ",
                        $track_usu_id,
                        $product_id
                    );

                    if($count >= 10){
                        continue;
                    }

                }

                /* ======================================
                   💾 INSERT
                ====================================== */

                DB::insert(
                    'reg_usuxreg',
                    [

                        'usu_id' => $track_usu_id,

                        'accion' => $accion,

                        'descripcion' => $descripcion,

                        'extra_data' => json_encode(
                            $extra_data,
                            JSON_UNESCAPED_UNICODE
                        ),

                        'fecha_creacion' => date('Y-m-d H:i:s')

                    ]
                );

            }

        }

        /* ======================================
           🚀 RESPONSE
        ====================================== */

        Flight::json([
            'estado'             => 'ok',
            'mensajes_nuevos'    => (int)$nuevos,
            'carrito_count'      => (int)$carrito_count,
            'carritos_enviados'  => (int)$carritos_enviados, // 🔥 NUEVO
            'favoritos_count'    => (int)$favoritos_count,
            'data'               => $rows
        ]);

    } catch (Exception $ex) {

        Flight::json([
            'estado' => 'error',
            'mensaje' => $ex->getMessage()
        ], 500);
    }

});

Flight::route('POST /N3GT/contactosNeg', function(){

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $neg_id = intval($d['neg_id'] ?? 0);

    if(!$neg_id){
        Flight::json([
            'status' => 'error',
            'msg' => 'neg_id requerido'
        ], 400);
        return;
    }

    try {

        $rows = DB::query("
            SELECT 
                nxu.negxusu_id,

                u.usu_id,
                u.cod_usu,
                u.nombres_apellidos,
                u.sobrenombre,
                u.img_perfil,

                tu.tipoxusu_id,
                tu.descripcion AS tipo_usuario

            FROM reg_negxusu nxu

            INNER JOIN reg_usu u 
                ON u.usu_id = nxu.usu_id

            LEFT JOIN reg_tipoxusu tu 
                ON tu.tipoxusu_id = u.tipoxusu_id

            WHERE nxu.neg_id = %i
              AND nxu.is_activo = 1

            ORDER BY u.nombres_apellidos ASC
        ", $neg_id);

        /* ======================================
           🔥 NORMALIZAR
        ====================================== */
        foreach($rows as &$r){
            $r['negxusu_id'] = intval($r['negxusu_id']);
            $r['usu_id'] = intval($r['usu_id']);
            $r['tipoxusu_id'] = intval($r['tipoxusu_id']);
        }

        Flight::json([
            'status' => 'ok',
            'data' => $rows
        ]);

    } catch(Exception $e){

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

Flight::route('POST /Baat/msg/enviarDirecto', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $d = json_decode(Flight::request()->getBody(), true) ?: [];

    $rem_id = intval($d['rem_id'] ?? 0);
    $dest_id = intval($d['dest_id'] ?? 0);
    $texto = trim($d['texto'] ?? '');
    $contexto = $d['contexto'] ?? null;

    if(!$rem_id || !$dest_id || $texto === ''){
        Flight::json([
            'status' => 'error',
            'msg' => 'rem_id, dest_id y texto requeridos'
        ], 400);
        return;
    }

    DB::startTransaction();

    try {

        /* ======================================
           🔍 BUSCAR CHAT EXISTENTE
        ====================================== */
        $chat = DB::queryFirstRow("
            SELECT chat_id
            FROM reg_chat
            WHERE (usu1_id=%i AND usu2_id=%i)
               OR (usu1_id=%i AND usu2_id=%i)
            LIMIT 1
        ", $rem_id, $dest_id, $dest_id, $rem_id);

        if(!$chat){

            /* ======================================
               🆕 CREAR CHAT
            ====================================== */
            DB::insert('reg_chat', [
                'usu1_id' => $rem_id,
                'usu2_id' => $dest_id,
                'fecha_creacion' => date('Y-m-d H:i:s'),
                'ultimo_mensaje' => $texto,                
                'last_seen_msg_id_u1' => 0,
                'last_seen_msg_id_u2' => 0
            ]);

            $chat_id = DB::insertId();

        } else {
            $chat_id = intval($chat['chat_id']);
        }

        /* ======================================
           💬 INSERTAR MENSAJE
        ====================================== */
        DB::insert('reg_msg', [
            'chat_id' => $chat_id,
            'rem_id' => $rem_id,
            'dest_id' => $dest_id,
            'contenido_rem' => $texto,
            'contenido_dest' => $texto,
            'contexto_json' => $contexto
                    ? json_encode($contexto, JSON_UNESCAPED_UNICODE)
                    : null,
            'fecha_creacion' => date('Y-m-d H:i:s')
        ]);

        $msg_id = DB::insertId();

        /* ======================================
           🔄 ACTUALIZAR CHAT
        ====================================== */
        DB::update('reg_chat', [
            'ultimo_mensaje' => $texto
        ], "chat_id=%i", $chat_id);

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'chat_id' => $chat_id,
            'msg_id' => $msg_id
        ]);

    } catch(Exception $e){

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

function enviar_auto_msg($dest_id, $clave_txt){

    DB::query("SET NAMES 'utf8mb4'");

    $rem_id = 2;

    $dest_id = intval($dest_id);

    $clave_txt = trim($clave_txt);

    if(!$dest_id || !$clave_txt){
        return false;
    }

    DB::startTransaction();

    try {

        /* ======================================
           🔍 BUSCAR MENSAJE AUTOMÁTICO
        ====================================== */

        $auto = DB::queryFirstRow("

            SELECT
                auto_msg_id,
                texto_msg

            FROM reg_auto_msg

            WHERE clave_txt = %s

            LIMIT 1

        ", $clave_txt);

        if(
            !$auto
            || empty($auto['texto_msg'])
        ){
            DB::rollback();
            return false;
        }

        /* ======================================
           🔥 LIMPIAR HTML
        ====================================== */

        $texto = trim(

            strip_tags(
                $auto['texto_msg']
            )

        );

        if($texto === ''){

            DB::rollback();

            return false;

        }

        /* ======================================
           🔍 BUSCAR CHAT
        ====================================== */

        $chat = DB::queryFirstRow("

            SELECT chat_id

            FROM reg_chat

            WHERE
            (
                usu1_id=%i
                AND usu2_id=%i
            )

            OR
            (
                usu1_id=%i
                AND usu2_id=%i
            )

            LIMIT 1

        ",
            $rem_id,
            $dest_id,

            $dest_id,
            $rem_id
        );

        /* ======================================
           🆕 CREAR CHAT
        ====================================== */

        if(!$chat){

            DB::insert(
                'reg_chat',
                [

                    'usu1_id' => $rem_id,

                    'usu2_id' => $dest_id,

                    'fecha_creacion' =>
                        date('Y-m-d H:i:s'),

                    'ultimo_mensaje' =>
                        $texto,

                    'last_seen_msg_id_u1' => 0,

                    'last_seen_msg_id_u2' => 0

                ]
            );

            $chat_id =
                DB::insertId();

        } else {

            $chat_id =
                intval($chat['chat_id']);

        }

        /* ======================================
           💬 INSERTAR MENSAJE
        ====================================== */

        DB::insert(
            'reg_msg',
            [

                'chat_id' => $chat_id,

                'rem_id' => $rem_id,

                'dest_id' => $dest_id,

                'contenido_rem' => $texto,

                'contenido_dest' => $texto,

                'fecha_creacion' =>
                    date('Y-m-d H:i:s')

            ]
        );

        $msg_id = DB::insertId();

        /* ======================================
           🔄 ACTUALIZAR CHAT
        ====================================== */

        DB::update(
            'reg_chat',
            [

                'ultimo_mensaje' => $texto

            ],

            "chat_id=%i",

            $chat_id
        );

        DB::commit();

        return [

            'status' => 'ok',

            'chat_id' => $chat_id,

            'msg_id' => $msg_id

        ];

    } catch(Exception $e){

        DB::rollback();

        return [

            'status' => 'error',

            'msg' => $e->getMessage()

        ];

    }

}

/* =========================================
   ENVIAR MANUAL VISUAL
========================================= */

function enviar_manual_visual(
    $dest_id,
    $imagenes = []
){

    /* =========================================
       VALIDAR DESTINO
    ========================================== */

    $dest_id = intval($dest_id);

    if($dest_id <= 0){
        return false;
    }

    /* =========================================
       BUSCAR CHAT
       (2 ↔ usuario)
    ========================================== */

    $chat = DB::queryFirstRow("

        SELECT

            chat_id

        FROM reg_chat

        WHERE

        (
            usu1_id = 2
            AND usu2_id = %i
        )

        OR

        (
            usu1_id = %i
            AND usu2_id = 2
        )

        LIMIT 1

    ", $dest_id, $dest_id);

    /* =========================================
       CREAR CHAT
    ========================================== */

    if(!$chat){

        DB::insert('reg_chat',[

            'usu1_id' => 2,

            'usu2_id' => $dest_id,

            'fecha_creacion' => date('Y-m-d H:i:s'),

            'is_visible' => 1,

            'ultimo_mensaje' => '',

            'is_bloqueado' => 0

        ]);

        $chat_id = DB::insertId();

    }else{

        $chat_id = intval(
            $chat['chat_id']
        );

    }

    /* =========================================
       MENSAJE AUTO
    ========================================== */

    $auto = DB::queryFirstRow("

        SELECT

            auto_msg_id,
            clave_txt,
            texto_msg

        FROM reg_auto_msg

        WHERE clave_txt = 'TXT_MANUAL_VISUAL'

        LIMIT 1

    ");

    if(!$auto){
        return false;
    }

    /* =========================================
       LIMPIAR HTML
    ========================================== */

    $texto_msg = trim(

        strip_tags(
            $auto['texto_msg']
        )

    );

    /* =========================================
       VALIDAR IMÁGENES
    ========================================== */

    if(!is_array($imagenes)){
        $imagenes = [];
    }

    /* =========================================
       CONTEXTO JSON
    ========================================== */

    $contexto_json = json_encode([

        'tipo' => 'visor_imagenes',

        'data' => [

            'titulo' => '✨ Mejoras visuales',

            'descripcion' => $texto_msg,

            'boton_texto' => 'Ver imágenes',

            'imagenes' => $imagenes

        ]

    ], JSON_UNESCAPED_UNICODE);

    /* =========================================
       INSERTAR MENSAJE
    ========================================== */

    DB::insert('reg_msg',[

        'chat_id' => $chat_id,

        'rem_id' => 2,

        'dest_id' => $dest_id,

        'contenido_rem' => $texto_msg,

        'contenido_dest' => $texto_msg,

        'tipo_mensaje' => 'TEXTO',

        'fecha_creacion' => date('Y-m-d H:i:s'),

        'contexto_json' => $contexto_json

    ]);

    $msg_id = DB::insertId();

    /* =========================================
       UPDATE CHAT
    ========================================== */

    DB::update('reg_chat',[

        'ultimo_mensaje' => $texto_msg

    ],"chat_id=%i",$chat_id);

    /* =========================================
       RESPONSE
    ========================================== */

    return [

        'ok' => true,

        'chat_id' => $chat_id,

        'msg_id' => $msg_id

    ];

}