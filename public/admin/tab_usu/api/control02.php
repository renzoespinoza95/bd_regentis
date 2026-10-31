<?php
// Endpoints de Creación, Edición y Subida de Avatares

Flight::route('POST /xico/usu/crear', function () {
    try {
        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        $d = json_decode(Flight::request()->getBody(), true) ?: [];

        DB::insert('reg_usu', [
          'cod_usu'            => $d['cod_usuario'] ?? null,
          'dni'                => $d['dni'] ?? null,
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

        Flight::json(['success' => true, 'usu_id' => DB::insertId()]);
    } catch(Exception $e){
        Flight::json(['success' => false, 'msg' => $e->getMessage()], 500);
    }
});

Flight::route('POST /xico/usu/editar', function () {
    try {
        DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
        $d = json_decode(Flight::request()->getBody(), true) ?: [];
        $usu_id = $d['usu_id'] ?? null;

        if (empty($usu_id)) {
            Flight::json(['success' => false, 'msg' => 'usu_id requerido'], 400);
            return;
        }

        DB::update('reg_usu', [
            'cod_usu'           => $d['cod_usuario'] ?? null,
            'google_uid'        => $d['google_uid'] ?? null,
            'dni'               => $d['dni'] ?? null,
            'img_perfil'        => $d['img_perfil'] ?? null,
            'sobrenombre'       => $d['sobrenombre'] ?? null,
            'celular'           => $d['celular'] ?? null,
            'provincia'         => $d['provincia'] ?? null,
            'tipoxusu_id'       => $d['tipoxusu_id'] ?? null,
            'nombres_apellidos' => $d['nombres_apellidos'] ?? null,
            'is_activo'         => isset($d['is_activo']) ? (int)$d['is_activo'] : 1
        ], "usu_id=%i", $usu_id);

        if (!empty($d['neg_id'])) {
            $neg_id = (int)$d['neg_id'];
            $relacionExistente = DB::queryFirstRow("SELECT * FROM reg_negxusu WHERE usu_id = %i", $usu_id);

            if ($relacionExistente) {
                DB::update('reg_negxusu', ['neg_id' => $neg_id, 'is_activo' => 1], "usu_id=%i", $usu_id);
            } else {
                DB::insert('reg_negxusu', ['usu_id' => $usu_id, 'neg_id' => $neg_id, 'is_activo' => 1, 'fecha_creacion' => date('Y-m-d H:i:s')]);
            }
        }

        Flight::json(['success' => true]);
    } catch(Exception $e){
        Flight::json(['success' => false, 'msg' => $e->getMessage()], 500);
    }
});

Flight::route('POST /usuario/subirAvatarBunny', function () {
    include DEFINITION;
    autentificar_administrador();
    DB::query("SET NAMES 'utf8mb4'");

    $url    = $_POST['url'] ?? '';
    $usu_id = intval($_POST['usu_id'] ?? 0);

    if (!$url || !$usu_id) {
        Flight::json(['success' => false, 'error' => 'Parámetros requeridos faltantes']);
        return;
    }

    try {
        $context = stream_context_create(['http' => ['timeout' => 30, 'header' => "User-Agent: Mozilla/5.0\r\n"]]);
        $img = file_get_contents($url, false, $context);
        if (!$img) {
            Flight::json(['success' => false, 'error' => 'No se pudo descargar avatar']);
            return;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'avatar_') . '.jpg';
        file_put_contents($tmp, $img);

        $filename = 'avatar_' . date('Ymd_His') . '_' . rand(1000,9999) . '.jpg';
        $storageUrl = rtrim(BUNNY_STORAGE_URL, '/') . '/' . SLIDER_DIR . '/' . $filename;
        $headers = ["AccessKey: " . BUNNY_STORAGE_ACCESSKEY, "Content-Type: image/jpeg"];

        $fp = fopen($tmp, 'r');
        $ch = curl_init($storageUrl);
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($tmp));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        unlink($tmp);

        if ($status != 200 && $status != 201) {
            Flight::json(['success' => false, 'error' => 'Bunny HTTP ' . $status]);
            return;
        }

        $cdn = rtrim(BUNNY_CDN_BASE, '/') . '/' . SLIDER_DIR . '/' . $filename;
        DB::update('reg_usu', ['img_perfil' => $cdn], 'usu_id=%i', $usu_id);

        Flight::json(['success' => true, 'url' => $cdn]);
    } catch(Exception $e){
        Flight::json(['success' => false, 'error' => $e->getMessage()]);
    }
});