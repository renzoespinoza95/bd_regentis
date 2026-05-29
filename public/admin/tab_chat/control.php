<?php
/* -------------------------------
 * Vista /chat
 * ------------------------------- */
Flight::route('GET /chat', function () {

    include DEFINITION;
    autentificar_administrador();

    include VARPATH . '/public/admin/tab_chat/inicio.php';
});


/* ============================================================
 * OBTENER USUARIO LOGUEADO (SEGURO)
 * ============================================================ */
function get_admin_id() {

    global $ssa_id, $valor_key;

    if (
        isset($ssa_id) &&
        is_string($ssa_id) &&
        strlen($ssa_id) > 10
    ) {
        $usu_id = perso::decrypt($ssa_id, $valor_key);

        if (!is_string($usu_id)) return 0;

        $usu_id = preg_replace('/\D/', '', $usu_id);
        $usu_id = intval($usu_id);

        return $usu_id > 0 ? $usu_id : 0;
    }

    return 0;
}


/* ============================================================
 * CREAR O BUSCAR CHAT
 * ============================================================ */
function ensure_chat_id(int $a, int $b): int {

    // Ordenar para evitar duplicados
    $ids = [$a, $b];
    sort($ids);
    [$u1, $u2] = $ids;

    $chatId = DB::queryFirstField(
        "SELECT chat_id FROM reg_chat WHERE usu1_id=%i AND usu2_id=%i",
        $u1, $u2
    );

    $now = date('Y-m-d H:i:s');

    if (!$chatId) {
        DB::insert('reg_chat', [
            'usu1_id' => $u1,
            'usu2_id' => $u2,
            'fecha_creacion' => $now,
            'is_visible' => 1,
            'ultimo_mensaje' => '',
            'is_bloqueado' => 0,
            'last_seen_msg_id_u1' => 0,
            'last_seen_msg_id_u2' => 0
        ]);

        $chatId = (int)DB::insertId();
    }

    return (int)$chatId;
}


/* ============================================================
 * INSERTAR MENSAJE
 * ============================================================ */
function insert_text_msg(int $chatId, int $rem, int $dest, string $texto, int $tipo=1): int {

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


/* ============================================================
 * ABRIR CHAT CON BARSI
 * ============================================================ */
Flight::route('GET /chat/open_barsi/@uid', function ($uid) {

    DB::query("SET NAMES 'utf8mb4'");

    $adminId = get_admin_id();
    if ($adminId <= 0) {
        Flight::json(['error'=>'No autenticado'], 401);
        return;
    }

    $uid = intval($uid);
    if ($uid <= 0) {
        Flight::json(['error'=>'uid inválido'], 400);
        return;
    }

    $chatId = ensure_chat_id($adminId, $uid);

    Flight::json([
        'ok'=>true,
        'chat_id'=>$chatId
    ]);
});


/* ============================================================
 * LISTAR MENSAJES
 * ============================================================ */
Flight::route('GET /msg/listar/@chat_id/@viewer_id', function ($chat_id, $viewer_id) {

    DB::query("SET NAMES 'utf8mb4'");

    $chat_id = intval($chat_id);
    $viewer_id = intval($viewer_id);

    if ($chat_id <= 0) {
        Flight::json(['error'=>'chat_id inválido'], 400);
        return;
    }

    $rows = DB::query("
        SELECT *
        FROM reg_msg
        WHERE chat_id=%i
        ORDER BY msg_id DESC
        LIMIT 100
    ", $chat_id);

    // Marcar como leído
    if ($viewer_id > 0) {

            $maxId = DB::queryFirstField("
                SELECT COALESCE(MAX(msg_id),0)
                FROM reg_msg
                WHERE chat_id=%i
            ", $chat_id);

        $chat = DB::queryFirstRow("
            SELECT usu1_id, usu2_id
            FROM reg_chat
            WHERE chat_id=%i
        ", $chat_id);

        if ($viewer_id == $chat['usu1_id']) {
            DB::update('reg_chat', [
                'last_seen_msg_id_u1' => $maxId
            ], "chat_id=%i", $chat_id);
        } else {
            DB::update('reg_chat', [
                'last_seen_msg_id_u2' => $maxId
            ], "chat_id=%i", $chat_id);
        }
    }

    $chat = DB::queryFirstRow("
        SELECT usu1_id, usu2_id,
               last_seen_msg_id_u1,
               last_seen_msg_id_u2
        FROM reg_chat
        WHERE chat_id=%i
    ", $chat_id);

    Flight::json([
        'mensajes' => $rows,
        'chat' => $chat
    ]);
});


/* ============================================
   ENDPOINT: ENVIAR MENSAJE
============================================ */
Flight::route('POST /msg/enviar', function () {

    global $apikey_openai;
    global $administrador_actual;

    $data = json_decode(file_get_contents("php://input"), true);

    $chat_id = intval($data['chat_id']);
    $dest_id = intval($data['dest_id']);
    $texto   = trim($data['texto']);

    // ======================================
    // VALIDAR USUARIO
    // ======================================
    if (empty($administrador_actual) || empty($administrador_actual['usu_id'])) {

        Flight::json([
            "ok" => false,
            "error" => "Usuario no autenticado"
        ]);
        return;
    }

    $rem_id = intval($administrador_actual['usu_id']);

    // ======================================
    // GUARDAR MENSAJE USUARIO
    // ======================================
    DB::insert('reg_msg', [
        'chat_id' => $chat_id,
        'rem_id' => $rem_id,
        'dest_id' => $dest_id,
        'contenido_rem' => $texto,
        'fecha_creacion' => DB::sqleval("NOW()"),
        'tipoxmsg_id' => 1
    ]);

    // ======================================
    // 🔥 ACTUALIZAR PREVIEW SIEMPRE (FIX CLAVE)
    // ======================================
    DB::update('reg_chat', [
        'ultimo_mensaje' => mb_substr($texto, 0, 200, 'UTF-8')
    ], "chat_id=%i", $chat_id);

    // ======================================
    // 🔥 ACTUALIZAR CONTEXTO DINÁMICO
    // ======================================
    $contextoActual = DB::queryFirstField("
        SELECT contexto FROM reg_chat WHERE chat_id=%i
    ", $chat_id);

    $totalMensajes = DB::queryFirstField("
        SELECT COUNT(*) FROM reg_msg WHERE chat_id=%i
    ", $chat_id);

    if (!empty($contextoActual)) {

        if ($totalMensajes % 5 == 0) {

            $mensajes = obtener_ultimos_mensajes($chat_id, 15);

            $nuevoResumen = resumir_con_openai($mensajes);

            guardar_en_chat($chat_id, $nuevoResumen);
        }
    }

    // ======================================
    // SI NO ES IA → TERMINAR
    // ======================================
    if ($dest_id != 1) {

        Flight::json([
            "ok" => true
        ]);
        return;
    }

    // ======================================
    // PROCESAR IA
    // ======================================
    $resultado = procesarAgenteIA_v3($chat_id, $rem_id, $apikey_openai);

    // ======================================
    // GUARDAR RESPUESTA IA
    // ======================================
    DB::insert('reg_msg', [
        'chat_id' => $chat_id,
        'rem_id' => 1,
        'dest_id' => $rem_id,
        'contenido_rem' => $resultado['respuesta'],
        'fecha_creacion' => DB::sqleval("NOW()"),
        'tipoxmsg_id' => 1
    ]);

    // ======================================
    // 🔥 ACTUALIZAR CONTEXTO FINAL
    // ======================================
    if ($resultado['finalizado'] === true && !empty($resultado['contexto'])) {

        DB::update('reg_chat', [
            'contexto' => $resultado['contexto']
        ], "chat_id=%i", $chat_id);

        $negocio = DB::queryFirstRow("
            SELECT nxu.neg_id
            FROM reg_negxusu nxu
            WHERE nxu.usu_id = %i
            AND nxu.is_activo = 1
            LIMIT 1
        ", $rem_id);

        if ($negocio && !empty($negocio['neg_id'])) {

            DB::update('reg_neg', [
                'descripcion' => $resultado['contexto']
            ], "neg_id=%i", $negocio['neg_id']);
        }
    }

    // ======================================
    // 🔥 ACTUALIZAR PREVIEW CON RESPUESTA IA
    // ======================================
    DB::update('reg_chat', [
        'ultimo_mensaje' => mb_substr($resultado['respuesta'], 0, 200, 'UTF-8')
    ], "chat_id=%i", $chat_id);

    // ======================================
    // RESPUESTA FINAL
    // ======================================
    Flight::json([
        "ok" => true,
        "reply" => $resultado['respuesta']
    ]);

});

/* ============================================================
 * ELIMINAR MENSAJES DE CHAT
 * ============================================================ */
Flight::route('POST /msg/eliminar_chat', function () {

    DB::query("SET NAMES 'utf8mb4'");

    $body = json_decode(Flight::request()->getBody(), true) ?: [];

    $chatId = intval($body['chat_id'] ?? 0);

    if ($chatId <= 0) {
        Flight::json(['error'=>'chat_id inválido'], 400);
        return;
    }

    $exists = DB::queryFirstField(
        "SELECT COUNT(*) FROM reg_chat WHERE chat_id=%i",
        $chatId
    );

    if (!$exists) {
        Flight::json(['error'=>'Chat no existe'], 404);
        return;
    }

    DB::delete('reg_msg', "chat_id=%i", $chatId);

    DB::update('reg_chat', [
        'ultimo_mensaje' => '',
        'last_seen_msg_id_u1' => 0,
        'contexto' => null,
        'last_seen_msg_id_u2' => 0
    ], "chat_id=%i", $chatId);

    Flight::json(['ok'=>true], 200);
});

/* ============================================================
 * LISTAR USUARIOS (EXCLUYE AL ADMIN ACTUAL)
 * ============================================================ */
Flight::route('GET /oxi/usuario/listar', function () {

    DB::query("SET NAMES 'utf8mb4'");

    // 🔐 obtener usuario logueado
    $adminId = get_admin_id();

    if ($adminId <= 0) {
        Flight::json(['error' => 'No autenticado'], 401);
        return;
    }

    // 👥 obtener usuarios (excepto yo mismo)
    $rows = DB::query("
        SELECT
            u.usu_id,
            u.cod_usu,
            u.sobrenombre,
            u.nombres_apellidos,
            u.img_perfil,
            u.provincia,
            u.tipoxusu_id
        FROM reg_usu u
        WHERE u.is_activo = 1
        AND u.usu_id != %i
        ORDER BY u.usu_id DESC
    ", $adminId);

    // 🎨 formatear salida
    $usuarios = [];

    foreach ($rows as $u) {

        $nombre = !empty($u['sobrenombre'])
            ? $u['sobrenombre']
            : (!empty($u['nombres_apellidos'])
                ? $u['nombres_apellidos']
                : $u['cod_usu']);

        $usuarios[] = [
            'usu_id'        => (int)$u['usu_id'],
            'cod_usu'       => $u['cod_usu'],
            'name'          => $nombre,
            'sobrenombre'   => $u['sobrenombre'],
            'nombres_apellidos'   => $u['nombres_apellidos'],
            'provincia'     => $u['provincia'],
            'tipoxusu_id'   => (int)$u['tipoxusu_id'],

            // 🔥 avatar automático
            'avatar_mini'   => "https://picsum.photos/seed/" . $u['usu_id'] . "/40/40"
        ];
    }

    Flight::json($usuarios);
});

function resumir_con_openai($mensajes) {

    global $apikey_openai;

    $texto = "";

    foreach ($mensajes as $m) {
        $texto .= $m['rol'] . ": " . $m['contenido'] . "\n";
    }

    $prompt = "
Resume la conversación en máximo 5 líneas.
Extrae SOLO información importante del negocio del usuario.
No repitas mensajes, solo conclusiones.
";

    $payload = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => $prompt],
            ["role" => "user", "content" => $texto]
        ],
        "temperature" => 0.3
    ];

    $ch = curl_init("https://api.openai.com/v1/chat/completions");

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $apikey_openai",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    return trim($data['choices'][0]['message']['content'] ?? '');
}

function guardar_en_chat($chat_id, $resumen) {

    DB::query("
        UPDATE reg_chat
        SET contexto = %s
        WHERE chat_id = %i
    ", $resumen, $chat_id);

}

function obtener_ultimos_mensajes($chat_id, $limite = 15) {

    $rows = DB::query("
        SELECT rem_id, contenido_rem
        FROM msg
        WHERE chat_id = %i
        ORDER BY fecha_creacion DESC
        LIMIT %i
    ", $chat_id, $limite);

    $rows = array_reverse($rows);

    $mensajes = [];

    foreach ($rows as $r) {

        $mensajes[] = [
            "rol" => ($r['rem_id'] == 1 ? "assistant" : "user"),
            "contenido" => $r['contenido_rem']
        ];
    }

    return $mensajes;
}

function procesarAgenteIA_v3($chat_id, $rem_id, $apikey_openai)
{

    // ======================================
    // CONTEXTO ACTUAL
    // ======================================
    $contexto = DB::queryFirstField("
        SELECT contexto
        FROM reg_chat
        WHERE chat_id = %i
    ", $chat_id);

    // ======================================
    // 🔥 DEFINIR MODO (AQUÍ ESTABA EL ERROR)
    // ======================================
    if (empty($contexto)) {

        $modo = 'INTRO';

        $rowPrompt = DB::queryFirstRow("
            SELECT texto_msg 
            FROM reg_auto_msg 
            WHERE clave_txt = 'INTRO'
            LIMIT 1
        ");

        $promptSistema = $rowPrompt['texto_msg'];

        $limitHistorial = 50;

    } else {

        $modo = 'COMANDO';

        $rowPrompt = DB::queryFirstRow("
            SELECT texto_msg 
            FROM reg_auto_msg 
            WHERE clave_txt = 'COMANDO'
            LIMIT 1
        ");

        $promptSistema = $rowPrompt['texto_msg'] . "\nContexto: " . $contexto;

        $limitHistorial = 15;
    }

    // ======================================
    // HISTORIAL
    // ======================================
    $historial = DB::query("
        SELECT rem_id, contenido_rem
        FROM reg_msg
        WHERE chat_id = %i
        ORDER BY msg_id DESC
        LIMIT %i
    ", $chat_id, $limitHistorial);

    $historial = array_reverse($historial);

    // ======================================
    // ARMAR MENSAJES
    // ======================================
    $messages = [];

    $messages[] = [
        "role" => "system",
        "content" => $promptSistema
    ];

    foreach ($historial as $h) {

        $messages[] = [
            "role" => ($h['rem_id'] == 1 ? "assistant" : "user"),
            "content" => $h['contenido_rem']
        ];
    }

    // ======================================
    // LLAMADA OPENAI
    // ======================================
    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api.openai.com/v1/chat/completions",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer $apikey_openai",
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode([
            "model" => "gpt-4o-mini",
            "messages" => $messages,
            "temperature" => 0.6
        ])
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return [
            "respuesta" => "Error IA",
            "finalizado" => false,
            "contexto" => ""
        ];
    }

    curl_close($ch);

    $data = json_decode($response, true);

    $raw = $data['choices'][0]['message']['content'] ?? '';

    // ======================================
    // 🔥 LIMPIAR RESPUESTA
    // ======================================
    $raw = trim($raw);
    $raw = preg_replace('/^```json|```$/i', '', $raw);
    $raw = trim($raw);

    // ======================================
    // 🔥 MODO INTRO (USA JSON)
    // ======================================
    if ($modo === 'INTRO') {

        $json = json_decode($raw, true);

        if ($json && isset($json['respuesta'])) {

            return [
                "respuesta" => $json['respuesta'],
                "finalizado" => $json['finalizado'] ?? false,
                "contexto" => $json['contexto'] ?? ""
            ];
        }

        return [
            "respuesta" => $raw,
            "finalizado" => false,
            "contexto" => ""
        ];
    }

    // ======================================
    // 🔥 MODO COMANDO (SOLO TEXTO)
    // ======================================
    return [
        "respuesta" => $raw,
        "finalizado" => false,
        "contexto" => ""
    ];
}

Flight::route('POST /WDAA/cant_msg', function(){

    // 🔐 usuario desde sesión
    $adminId = get_admin_id();

    if ($adminId <= 0) {
        Flight::json([
            'estado' => 'error',
            'mensaje' => 'No autenticado'
        ], 401);
        return;
    }

    try {

        DB::query("SET NAMES 'utf8mb4' COLLATE utf8mb4_unicode_ci");

        // 🔥 LISTADO DE CHATS
        $rows = DB::query("
            SELECT 
                ch.chat_id,

                ch.usu1_id,
                u1.sobrenombre AS usuario1,

                -- 🖼️ IMAGEN PICSUM (usuario 1)
                CONCAT('https://picsum.photos/seed/', u1.usu_id, '/40/40') AS img_usu1,

                ch.usu2_id,
                u2.sobrenombre AS usuario2,

                -- 🖼️ IMAGEN PICSUM (usuario 2)
                CONCAT('https://picsum.photos/seed/', u2.usu_id, '/40/40') AS img_usu2,

                ch.fecha_creacion,
                ch.ultimo_mensaje,
                ch.is_bloqueado,

                -- 🔴 NO LEÍDOS POR CHAT
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
        $adminId, $adminId,
        $adminId, $adminId
        );

        // 🔴 TOTAL GLOBAL DE NO LEÍDOS
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
        ", $adminId, $adminId, $adminId, $adminId);

        Flight::json([
            'estado'          => 'ok',
            'mensajes_nuevos' => (int)$nuevos,
            'data'            => $rows
        ]);

    } catch (Exception $ex) {

        Flight::json([
            'estado' => 'error',
            'mensaje' => $ex->getMessage()
        ], 500);
    }

});