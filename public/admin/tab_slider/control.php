<?php
// este es mi backend usando php8.2 con flightphp y meekrodb2

function resizeTo800Jpg(string $pathTmp): string {

    $src = imagecreatefromstring(file_get_contents($pathTmp));
    if (!$src) {
        throw new Exception('No se pudo crear la imagen');
    }

    // Ancho deseado
    $newW = 610;

    // Tamaño original
    $w = imagesx($src);
    $h = imagesy($src);

    // Calcular alto proporcional
    $newH = intval(($newW * $h) / $w);

    // Crear imagen destino
    $dst = imagecreatetruecolor($newW, $newH);

    // Fondo blanco (porque el destino final es JPG)
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);

    // Redimensionar proporcional
    imagecopyresampled(
        $dst, $src,
        0, 0, 0, 0,
        $newW, $newH,
        $w, $h
    );

    // Archivo temporal JPG
    $tempOut = tempnam(sys_get_temp_dir(), 'slider_') . '.jpg';
    imagejpeg($dst, $tempOut, 90);

    imagedestroy($src);
    imagedestroy($dst);

    return $tempOut;
}


function bunnyUpload(string $localPath, string $destName): bool {
    $url = BUNNY_STORAGE_URL . '/' . SLIDER_DIR . '/' . $destName;

    $headers = [
        "AccessKey: " . BUNNY_STORAGE_ACCESSKEY,
        "Content-Type: image/jpeg"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_PUT, true);
    curl_setopt($ch, CURLOPT_INFILE, fopen($localPath, 'r'));
    curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localPath));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($status == 201 || $status == 200);
}

/* -------------------------- */
/* Vistas SLIDER             */
/* -------------------------- */
Flight::route('GET /slider/inicio', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;
    include $path_public . '/admin/tab_slider/inicio.php';
});

/* ----------------- */
/* CRUD de “slider” */
/* ----------------- */

/* LISTAR */
// ANTES (Local path):
// CONCAT('" . vari('PICS_SLIDER_FULL') . "/', img) AS img_thumb
// DESPUÉS (Devuelve solo el campo 'img' y Vue construye la URL):

Flight::route('GET /slider/listar', function () {
    DB::query("SET NAMES 'utf8'");
    $rows = DB::query("
        SELECT *
        FROM slider
        ORDER BY slider_id DESC
    ");
    // El campo 'img' ahora contiene el nombre del archivo único (ej: 'slider_xxx.jpg')
    Flight::json($rows);
});

/* ----------------- */
/* CRUD de “slider” */
/* ----------------- */

/* CREAR */
/* ==================================
   🟢 CREAR SLIDER
   ================================== */
Flight::route('POST /slider/crear', function () {

    $orden          = $_POST['orden'] ?? 0;
    $is_visible     = $_POST['is_visible'] ?? 1;
    $fecha_creacion = $_POST['fecha_creacion'] ?? null;
    $fecha_fin      = $_POST['fecha_fin'] ?? null;
    $neg_id         = $_POST['neg_id'] ?? 0;
    $grupo          = $_POST['grupo'] ?? '';

    if (empty($_FILES['img']['tmp_name'])) {
        Flight::json(['success' => false, 'error' => 'Imagen requerida']);
        return;
    }

    // 1) Redimensionar y convertir a JPG
    $jpgPath = resizeTo800Jpg($_FILES['img']['tmp_name']);

    if (!file_exists($jpgPath)) {
        Flight::json(['success' => false, 'error' => 'Error al generar JPG']);
        return;
    }

    // 2) Nombre único
    $filename = 'slider_' . date('Ymd_His') . '_' . rand(1000,9999) . '.jpg';

    // 3) Subir a Bunny
    if (!bunnyUpload($jpgPath, $filename)) {
        Flight::json(['success' => false, 'error' => 'Error al subir imagen a Bunny']);
        return;
    }

    // 4) Registrar en BD
    DB::insert('slider', [
        'img'            => $filename,
        'orden'          => $orden,
        'is_visible'     => $is_visible,
        'fecha_creacion' => $fecha_creacion,
        'fecha_fin'      => $fecha_fin,
        'grupo'          => $grupo,
        'neg_id'         => $neg_id
    ]);

    Flight::json([
        'success'   => true,
        'slider_id' => DB::insertId(),
        'filename'  => $filename
    ]);
});


/* EDITAR */
/* ==================================
   🟡 EDITAR SLIDER
   ================================== */
Flight::route('POST /slider/editar', function () {

    $slider_id      = $_POST['slider_id'] ?? null;
    $orden          = $_POST['orden'] ?? 0;
    $is_visible     = $_POST['is_visible'] ?? 1;
    $fecha_creacion = $_POST['fecha_creacion'] ?? null;
    $fecha_fin      = $_POST['fecha_fin'] ?? null;
    $neg_id         = $_POST['neg_id'] ?? 0;
    $grupo          = $_POST['grupo'] ?? '';

    if (!$slider_id) {
        Flight::json(['success' => false, 'error' => 'slider_id requerido']);
        return;
    }

    // Datos comunes
    $update = [
        'orden'          => $orden,
        'is_visible'     => $is_visible,
        'fecha_creacion' => $fecha_creacion,
        'fecha_fin'      => $fecha_fin,
        'grupo'          => $grupo,
        'neg_id'         => $neg_id
    ];

    // Si viene una nueva imagen
    if (!empty($_FILES['img']['tmp_name'])) {

        $jpgPath = resizeTo800Jpg($_FILES['img']['tmp_name']);

        if (!file_exists($jpgPath)) {
            Flight::json(['success' => false, 'error' => 'Error al generar JPG']);
            return;
        }

        $filename = 'slider_' . date('Ymd_His') . '_' . rand(1000,9999) . '.jpg';

        if (!bunnyUpload($jpgPath, $filename)) {
            Flight::json(['success' => false, 'error' => 'Error al subir nueva imagen']);
            return;
        }

        $update['img'] = $filename;
    }

    DB::update('slider', $update, 'slider_id = %i', $slider_id);

    Flight::json([
        'success' => true,
        'updated' => true
    ]);
});



/* ELIMINAR */
Flight::route('POST /slider/eliminar', function () {
    $d = json_decode(Flight::request()->getBody(), true);
    DB::delete('slider', 'slider_id = %i', $d['slider_id']);
    Flight::json(['success' => true]);
});

/* DETALLE */
// ANTES (Local path):
// CONCAT('" . vari('PICS_SLIDER_MINI') . "/', img) AS img_thumb
// DESPUÉS (Devuelve solo el campo 'img' y Vue construye la URL):

Flight::route('GET /slider/detalle/@id', function ($id) {
    DB::query("SET NAMES 'utf8'");
    $row = DB::queryFirstRow(
        "SELECT *
         FROM slider
         WHERE slider_id = %i",
        $id
    );
    // El campo 'img' ahora contiene el nombre del archivo único (ej: 'slider_xxx.jpg')
    Flight::json($row);
});