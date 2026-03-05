<?php
/* -------------------------------
 * Vista /tab3
 * ------------------------------- */
Flight::route('GET /cc', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;                          // asegúrate de tener esta var en tu bootstrap
    include $path_public . '/admin/tab_cc/inicio.php';
});

function ensure_chat_id(int $a, int $b): int {
    // mantener orden usu1 < usu2
    $ids = [$a,$b]; sort($ids);
    [$u1,$u2] = $ids;

    $chatId = DB::queryFirstField(
        "SELECT chat_id FROM chat WHERE usu1_id=%i AND usu2_id=%i", $u1, $u2
    );
    $now = date('Y-m-d H:i:s');

    if (!$chatId) {
        DB::insert('chat', [
            'usu1_id'          => $u1,
            'usu2_id'          => $u2,
            'fecha_creacion'   => $now,
            'is_visible'       => 1,
            'is_visto_usu1_id' => 0,
            'is_visto_usu2_id' => 0,
            'ultimo_mensaje'   => '',
            'is_bloqueado'     => 0
        ]);
        $chatId = (int)DB::insertId();
    }
    return (int)$chatId;
}

function insert_text_msg(int $chatId, int $rem, int $dest, string $texto, int $tipo=1): int {
    $now = date('Y-m-d H:i:s');
    DB::insert('msg', [
        'chat_id'        => $chatId,
        'rem_id'         => $rem,
        'dest_id'        => $dest,
        'contenido_rem'  => $texto,
        'fecha_creacion' => $now,
        'tipoxmsg_id'    => $tipo,   // 1=texto, 2=json_ia
        'is_una_vista'   => 0
    ]);

    // actualizar preview y vistos (reset 0/0 para que el otro tenga no leído)
    $preview = mb_substr($texto, 0, 400, 'UTF-8');
    DB::update('chat', [
        'ultimo_mensaje'   => $preview,
        'is_visto_usu1_id' => 0,
        'is_visto_usu2_id' => 0
    ], 'chat_id=%i', $chatId);

    return (int)DB::insertId();
}


/* Endpoint: abrir chat con Barsi -------------------------------------- */
Flight::route('GET /chat/open_barsi/@uid', function ($uid) {
    DB::query("SET NAMES 'utf8mb4'");
    $uid = intval($uid);
    if ($uid <= 0) return Flight::json(['error'=>'uid inválido'], 400);

    $chatId = ensure_chat_id($uid, 1);
    Flight::json(['ok'=>true, 'chat_id'=>$chatId, 'barsi_id'=>1]);
});


// Helper cURL JSON POST (para Qdrant)
function http_json_post($url, $payload, $timeout = 25, $headers = []) {
  $ch = curl_init($url);
  $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
  $baseHeaders = [
    'Content-Type: application/json',
    'Accept: application/json'
  ];
  $headers = array_merge($baseHeaders, $headers);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $json,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT        => $timeout,
  ]);
  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($err) throw new Exception("cURL error: $err");
  if ($code < 200 || $code >= 300) throw new Exception("HTTP $code: $resp");
  $data = json_decode($resp, true);
  if ($data === null) throw new Exception("Invalid JSON from Qdrant");
  return $data;
}

// =========================
// 1) Eliminar todos los mensajes de un chat
// =========================
Flight::route('POST /msg/eliminar_chat', function () {
  DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
  $body = json_decode(Flight::request()->getBody(), true) ?: [];
  $chatId = intval($body['chat_id'] ?? 0);
  if ($chatId <= 0) {
    Flight::json(['error' => 'chat_id inválido'], 400);
    return;
  }

  // Opcional: validar que exista el chat
  $exists = DB::queryFirstField("SELECT COUNT(*) FROM chat WHERE chat_id=%i", $chatId);
  if (!$exists) {
    Flight::json(['error' => 'Chat no existe'], 404);
    return;
  }

  DB::delete('msg', 'chat_id=%i', $chatId);
  $deleted = DB::affectedRows();

  // limpiar preview/lecturas
  DB::update('chat', [
    'ultimo_mensaje'   => '',
    'is_visto_usu1_id' => 0,
    'is_visto_usu2_id' => 0
  ], 'chat_id=%i', $chatId);

  Flight::json(['ok' => true, 'deleted' => intval($deleted)], 200);
});

// Enviar mensaje simple (sin OpenAI ni Qdrant)
Flight::route('POST /msg/enviar', function () {
  DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
  $body = json_decode(Flight::request()->getBody(), true) ?: [];

  $chatId   = intval($body['chat_id'] ?? 0);
  $remId    = intval($body['rem_id'] ?? 0);
  $destId   = intval($body['dest_id'] ?? 0);
  $texto    = trim((string)($body['texto'] ?? ''));
  $tipo     = intval($body['tipoxmsg_id'] ?? 1); // 1=texto

  if ($remId <= 0 || $destId <= 0 || $texto === '') {
    Flight::json(['error' => 'Parámetros inválidos'], 400);
    return;
  }
  if ($chatId <= 0) {
    $chatId = ensure_chat_id($remId, $destId);
  }

  $msgId = insert_text_msg($chatId, $remId, $destId, $texto, $tipo);
  Flight::json(['ok' => true, 'chat_id' => $chatId, 'msg_id' => $msgId], 201);
});
