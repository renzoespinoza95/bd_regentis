<?php
// este es mi backend usando php8.2 con flightphp y meekrodb2

function resizeTo800Jpg(string $pathTmp): string {

    $src = imagecreatefromstring(file_get_contents($pathTmp));
    if (!$src) {
        throw new Exception('No se pudo crear la imagen');
    }

    // 🔥 Usar constante ANCHO_SLIDER
    $newW = defined('ANCHO_SLIDER') ? ANCHO_SLIDER : 610;

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

function bunnyDelete(string $fileOrUrl): bool {

    // Si viene URL completa, extraer solo el filename
    $filename = basename($fileOrUrl);

    $url = BUNNY_STORAGE_URL . '/' . SLIDER_DIR . '/' . $filename;

    $headers = [
        "AccessKey: " . BUNNY_STORAGE_ACCESSKEY
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($status == 200 || $status == 204);
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
    autentificar_administrador();

    require_once VARPATH . '/public/admin/tab_slider/inicio.php';
});

Flight::route('GET /slider/listar', function () {

    include DEFINITION;
    autentificar_administrador();
    
    $neg_id = $administrador_actual['neg_id'];

    DB::query("SET NAMES 'utf8'");

    $rows = DB::query("
        SELECT 
            slider_id,
            img,
            orden,
            is_visible,
            fecha_creacion,
            descripcion,            
            fecha_fin,
            grupo,
            neg_id
        FROM reg_slider
        WHERE neg_id = %i
        ORDER BY orden ASC
    ", $neg_id);

    Flight::json($rows);
});

/* ==================================
   🟢 CREAR SLIDER
   ================================== */
Flight::route('POST /slider/crear', function () {

    include DEFINITION;
    autentificar_administrador();

    $orden          = $_POST['orden'] ?? 0;
    $is_visible     = $_POST['is_visible'] ?? 1;
    $fecha_creacion = $_POST['fecha_creacion'] ?? null;
    $fecha_fin      = $_POST['fecha_fin'] ?? null;
    $grupo          = $_POST['grupo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';

    $neg_id = $administrador_actual['neg_id']; // 🔥

    if (empty($_FILES['img']['tmp_name'])) {
        Flight::json(['success' => false, 'error' => 'Imagen requerida']);
        return;
    }

    $jpgPath = resizeTo800Jpg($_FILES['img']['tmp_name']);

    if (!file_exists($jpgPath)) {
        Flight::json(['success' => false, 'error' => 'Error al generar JPG']);
        return;
    }

    $filename = 'slider_' . date('Ymd_His') . '_' . rand(1000,9999) . '.jpg';

    if (!bunnyUpload($jpgPath, $filename)) {
        Flight::json(['success' => false, 'error' => 'Error al subir imagen']);
        return;
    }

    $rutaCompleta = rtrim(BUNNY_CDN_BASE, '/') . '/' . SLIDER_DIR . '/' . $filename;

    DB::insert('reg_slider', [
        'img'            => $rutaCompleta,
        'orden'          => $orden,
        'is_visible'     => $is_visible,
        'fecha_creacion' => $fecha_creacion,
        'fecha_fin'      => $fecha_fin,
        'descripcion'    => $descripcion,
        'grupo'          => $grupo,
        'neg_id'         => $neg_id
    ]);

    Flight::json([
        'success'   => true,
        'slider_id' => DB::insertId(),
        'img'       => $rutaCompleta
    ]);
});


/* ==================================
   🟡 EDITAR SLIDER
   ================================== */
Flight::route('POST /slider/editar', function () {

    include DEFINITION;
    autentificar_administrador();

    $slider_id      = $_POST['slider_id'] ?? null;
    $orden          = $_POST['orden'] ?? 0;
    $is_visible     = $_POST['is_visible'] ?? 1;
    $fecha_creacion = $_POST['fecha_creacion'] ?? null;
    $fecha_fin      = $_POST['fecha_fin'] ?? null;
    $grupo          = $_POST['grupo'] ?? '';
    $descripcion = $_POST['descripcion'] ?? '';    

    $neg_id = $administrador_actual['neg_id']; // 🔥

    if (!$slider_id) {
        Flight::json(['success' => false, 'error' => 'slider_id requerido']);
        return;
    }    

    $update = [
        'orden'          => $orden,
        'is_visible'     => $is_visible,
        'fecha_creacion' => $fecha_creacion,
        'fecha_fin'      => $fecha_fin,
        'descripcion'    => $descripcion, // 👈 NUEVO
        'grupo'          => $grupo
    ];

    if (!empty($_FILES['img']['tmp_name'])) {

        $jpgPath = resizeTo800Jpg($_FILES['img']['tmp_name']);

        if (!file_exists($jpgPath)) {
            Flight::json(['success' => false, 'error' => 'Error al generar JPG']);
            return;
        }

        $filename = 'slider_' . date('Ymd_His') . '_' . rand(1000,9999) . '.jpg';

        if (!bunnyUpload($jpgPath, $filename)) {
            Flight::json(['success' => false, 'error' => 'Error al subir imagen']);
            return;
        }

        $rutaCompleta = rtrim(BUNNY_CDN_BASE, '/') . '/' . SLIDER_DIR . '/' . $filename;

        $update['img'] = $rutaCompleta;

        // 🔥 eliminar anterior
        if (!empty($actual['img'])) {
            bunnyDelete($actual['img']);
        }
    }

    DB::update(
        'reg_slider',
        $update,
        'slider_id = %i AND neg_id = %i',
        $slider_id,
        $neg_id
    );

    Flight::json(['success' => true]);
});

/* ELIMINAR */
Flight::route('POST /slider/eliminar', function () {

    $d = json_decode(Flight::request()->getBody(), true);

    $slider_id = $d['slider_id'] ?? null;

    if (!$slider_id) {
        Flight::json(['success' => false, 'error' => 'slider_id requerido']);
        return;
    }

    // 🔥 Obtener imagen antes de borrar
    $row = DB::queryFirstRow(
        "SELECT img FROM reg_slider WHERE slider_id = %i",
        $slider_id
    );

    if ($row && !empty($row['img'])) {
        bunnyDelete($row['img']);
    }

    DB::delete('reg_slider', 'slider_id = %i', $slider_id);

    Flight::json(['success' => true]);
});

/* DETALLE */
Flight::route('GET /slider/detalle/@id', function ($id) {

    include DEFINITION;
    autentificar_administrador();

    DB::query("SET NAMES 'utf8'");

    $row = DB::queryFirstRow(
        "SELECT 
            slider_id,
            img,
            orden,
            is_visible,
            fecha_creacion,
            descripcion,            
            fecha_fin,
            grupo
         FROM reg_slider
         WHERE slider_id = %i
         AND neg_id = %i",
        $id,
        $administrador_actual['neg_id']
    );

    Flight::json($row);
});

Flight::route('POST /slider/ordenar', function () {

    include DEFINITION;
    autentificar_administrador();

    $data = json_decode(Flight::request()->getBody(), true);

    if (!isset($data['orden'])) {
        Flight::json(['success' => false]);
        return;
    }

    DB::startTransaction();

    try {

        foreach ($data['orden'] as $item) {

            DB::update('reg_slider', [
                'orden' => $item['orden']
            ], 'slider_id = %i',
               $item['slider_id']
            );
        }

        DB::commit();

        Flight::json(['success' => true]);

    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['success' => false]);
    }
});

Flight::route('POST /slider/actualizarDescripcion', function () {

    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;

    $slider_id  = $_POST['slider_id'] ?? null;
    $descripcion = $_POST['descripcion'] ?? '';

    $neg_id = $administrador_actual['neg_id'];

    if (!$slider_id) {
        Flight::json(['success' => false, 'error' => 'slider_id requerido']);
        return;
    }

    try {

        DB::query("
            UPDATE reg_slider
            SET descripcion = %s
            WHERE slider_id = %i AND neg_id = %i
        ",
            $descripcion,
            $slider_id,
            $neg_id
        );

        Flight::json(['success' => true]);

    } catch (Exception $e) {

        Flight::json([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
});


/* ==================================
   🤖 CREAR SLIDER AUTOMÁTICO
================================== */
Flight::route('POST /GcVL/slider/automatico', function () {

    include DEFINITION;

    autentificar_administrador();

    try {

        $neg_id =
            $administrador_actual['neg_id'];

        DB::query("SET NAMES 'utf8mb4'");

        $img =
            'https://barsi-img.b-cdn.net/recursos/71ye.png';

        // 🔥 obtener siguiente orden
        $ultimoOrden = intval(
            DB::queryFirstField("

                SELECT
                    IFNULL(MAX(orden),0)
                FROM reg_slider
                WHERE neg_id = %i

            ", $neg_id)
        );

        DB::insert('reg_slider', [

            'img' =>
                $img,

            'orden' =>
                $ultimoOrden + 1,

            'is_visible' =>
                1,

            'fecha_creacion' =>
                date('Y-m-d H:i:s'),

            'fecha_fin' =>
                date(
                    'Y-m-d H:i:s',
                    strtotime('+30 days')
                ),

            'descripcion' =>
                '',

            'grupo' =>
                'A',

            'neg_id' =>
                $neg_id

        ]);

        Flight::json([

            'success' => true,

            'slider_id' =>
                DB::insertId(),

            'img' =>
                $img

        ]);

    } catch(Exception $e){

        Flight::json([

            'success' => false,

            'error' =>
                $e->getMessage()

        ],500);

    }

});