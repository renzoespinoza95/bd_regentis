<?php
Flight::route('GET /ff/estxclie', function () {
    include DEFINITION;
    $cant = 250;
    for ($i = 0; $i < $cant; $i++) {
        $estado_id      = amarilis::min_max_numero(1, 250);
        $usu_id         = amarilis::min_max_numero(1, 250);
        $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');

        DB::insert('estxclie', [
            'estado_id'      => $estado_id,
            'usu_id'         => $usu_id,
            'fecha_creacion' => $fecha_creacion
        ]);
    }
    echo poke();
});

/* -----------------------------
 * slider → slider (img, neg_id)
 * ----------------------------- */
Flight::route('GET /ff/slider', function () {
    include DEFINITION;
    $cant = 250;
    for ($i = 0; $i < $cant; $i++) {
        $img            = amarilis::flor_imagen(600);
        $orden          = amarilis::min_max_numero(1, 50);
        $is_visible     = amarilis::min_max_numero(0, 1);
        $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');
        $fecha_fin      = amarilis::fecha_random('01/01/2025', '05/01/2025');
        $neg_id         = amarilis::min_max_numero(1, $cant);

        DB::insert('slider', [
            'img'            => $img,
            'orden'          => $orden,
            'is_visible'     => $is_visible,
            'fecha_creacion' => $fecha_creacion,
            'fecha_fin'      => $fecha_fin,
            'neg_id'         => $neg_id
        ]);
    }
    echo poke();
});

/* -----------------------------
 * estado → estado (img, fichera_id)
 * ----------------------------- */
Flight::route('GET /ff/estado', function () {
    include DEFINITION;
    
    $query = "SELECT * FROM fich";
    $lista_fich = DB::query($query);

    foreach($lista_fich as $fich) {       
        
        $cant = 4;

        for ($i = 0; $i < $cant; $i++) {
            $img = amarilis::min_max_numero(1, 10) . ".jpg";
            $is_visible      = amarilis::min_max_numero(0, 1);
            $visitas         = amarilis::min_max_numero(0, 500);
            $fecha_creacion  = amarilis::fecha_random('12/06/2025', '13/06/2025');
            $fich_id = $fich['fich_id'];
            $fecha_fin       = amarilis::fecha_random('13/06/2025', '14/06/2025');

            DB::insert('estado', [
                'img'            => $img,
                'is_visible'     => $is_visible,
                'visitas'        => $visitas,
                'fecha_creacion' => $fecha_creacion,
                'fich_id'     => $fich_id,
                'fecha_fin'      => $fecha_fin
            ]);
        }

    }

    echo poke();
});

/* -----------------------------
 * neg → neg (img_logo)
 * ----------------------------- */
Flight::route('GET /ff/neg', function () {
    include DEFINITION;
    $cant = 250;
    for ($i = 0; $i < $cant; $i++) {
        $nombre           = lorem::ipsum(2);
        $celular_informes = '9' . amarilis::numero_random(8);
        $fecha_creacion   = amarilis::fecha_random('01/01/2025', '05/01/2025');
        $is_activo        = amarilis::min_max_numero(0, 1);
        $provincia        = lorem::ipsum(2);
        $maps             = amarilis::random_maps();
        $map_lat          = $maps['map_lat'];
        $map_lng          = $maps['map_lng'];
        $place_id         = amarilis::flor(12);
        $direccion        = lorem::ipsum(3) . ' ' . amarilis::min_max_numero(1, 100);
        $is_validado      = amarilis::min_max_numero(0, 1);
        $img_logo         = lorem::ipsum(2);

        DB::insert('neg', [
            'nombre'           => $nombre,
            'celular_informes' => $celular_informes,
            'fecha_creacion'   => $fecha_creacion,
            'is_activo'        => $is_activo,
            'provincia'        => $provincia,
            'map_lat'          => $map_lat,
            'map_lng'          => $map_lng,
            'place_id'         => $place_id,
            'direccion'        => $direccion,
            'is_validado'      => $is_validado,
            'img_logo'         => $img_logo
        ]);
    }
    echo poke();
});


/* -----------------------------
 * fichxserv → fichxserv
 * ----------------------------- */
Flight::route('GET /ff/fichxserv', function () {
    include DEFINITION;  // carga MeekroDB2, clases amarilis, util y lorem

    // 1) Traer todos los fich_id existentes
    $fichs = DB::query("SELECT fich_id FROM fich");
    if (empty($fichs)) {
        echo "No hay fichas para procesar.";
        return;
    }

    // 2) Traer todos los serv_id existentes
    $servs = DB::query("SELECT serv_id FROM serv");
    $servIds = array_column($servs, 'serv_id');
    if (empty($servIds)) {
        echo "No hay servicios disponibles.";
        return;
    }

    // 3) Para cada ficha, asignar entre 1 y 4 servicios al azar
    foreach ($fichs as $f) {
        $fichId = $f['fich_id'];

        // Cuántos servicios le pongo (hasta 4, o el total si tienes menos)
        $max = min(4, count($servIds));
        $count = amarilis::min_max_numero(1, $max);

        // Barajar y quedarnos con los primeros $count
        shuffle($servIds);
        $selected = array_slice($servIds, 0, $count);

        // Insertar cada asociación
        foreach ($selected as $servId) {
            // Evitamos duplicados por si ya existiera
            $existe = DB::queryFirstField(
              "SELECT COUNT(*) FROM fichxserv WHERE fich_id = %i AND serv_id = %i",
              $fichId, $servId
            );
            if ($existe) {
                continue;
            }

            DB::insert('fichxserv', [
                'serv_id' => $servId,
                'fich_id' => $fichId
            ]);
        }
    }

    echo poke();
});


/* -----------------------------
 * fotoxfich → fotoxfich
 * ----------------------------- */
Flight::route('GET /ff/fotoxfich', function () {
    include DEFINITION;

    $query = "select * from fich";
    $lista_fich = DB::query($query);    

    $cant = 4;

    foreach($lista_fich as $fich) {

        for($i=0; $i<$cant; $i++) {

            $fich_id        = $fich['fich_id'];
            $img            = amarilis::min_max_numero(1, 10) . ".jpg";
            $is_visible     = amarilis::min_max_numero(0, 1);
            $is_valido      = amarilis::min_max_numero(0, 1);
            $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');
            DB::insert('fotoxfich', [
                'fich_id'        => $fich_id,
                'img'            => $img,
                'is_visible'     => $is_visible,
                'is_valido'      => $is_valido,
                'fecha_creacion' => $fecha_creacion
            ]);
        }        
    }
    echo poke();
});


/* -----------------------------
 * negxclie → negxclie
 * ----------------------------- */
Flight::route('GET /ff/negxclie', function () {
    include DEFINITION;
    $cant = 250;
    for ($i = 0; $i < $cant; $i++) {
        $usu_id         = amarilis::min_max_numero(1, 250);
        $neg_id         = amarilis::min_max_numero(1, 250);
        $is_activo      = amarilis::min_max_numero(0, 1);
        $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');
        DB::insert('negxclie', [
            'usu_id'         => $usu_id,
            'neg_id'         => $neg_id,
            'is_activo'      => $is_activo,
            'fecha_creacion' => $fecha_creacion
        ]);
    }
    echo poke();
});

/*--------------------------------------------------------------
  GET /ff/chat
  Genera chats de prueba y 30 mensajes por chat.
----------------------------------------------------------------*/
Flight::route('GET /ff/chat', function () {

    /* ---------- Config / helpers ---------- */
    include DEFINITION;           // carga MeekroDB2, lorem, amarilis, poke()…

    date_default_timezone_set('America/Lima');

    // Cursor temporal que avanza 5 h por INSERT
    $cursor = new DateTime('2025-01-01 00:00:00');
    $step   = new DateInterval('PT5H');   // 5 horas

    /* ---------- 1) Pairs (tipo 1  ↔  tipo 2) ---------- */
    $pairs = DB::query(
        'SELECT u1.usu_id AS usu1_id, u2.usu_id AS usu2_id
           FROM usu u1
           JOIN usu u2
         WHERE u1.tipoxusu_id = %i
           AND u2.tipoxusu_id = %i',
        1,  // tipo de usu1
        2   // tipo de usu2
    );

    /* ---------- 2) Insertar chats + msgs ---------- */
    foreach ($pairs as $row) {

        /* 2.a) Cabecera del chat -------------------- */
        DB::insert('chat', [
            'usu1_id'           => (int)$row['usu1_id'],
            'usu2_id'           => (int)$row['usu2_id'],
            'fecha_creacion'    => $cursor->format('Y-m-d H:i:s'),
            'is_visible'        => 1,
            'is_visto_usu1_id'  => 0,
            'is_visto_usu2_id'  => 0,
            'ultimo_mensaje'    => lorem::ipsum(5),
            'is_bloqueado'      => 0
        ]);
        $chatId = DB::insertId();
        // Avanza 5 h para la siguiente fila (chat o msg)
        $cursor->add($step);

        /* 2.b) Treinta mensajes ---------------------- */
        for ($i = 0; $i < 30; $i++) {

            DB::insert('msg', [
                'chat_id'        => $chatId,
                // alterna remitente aleatoriamente entre usu1 y usu2
                'rem_id'         => amarilis::dos_numeros(
                                      (int)$row['usu1_id'],
                                      (int)$row['usu2_id']),
                'contenido_rem'  => lorem::ipsum(5),
                'fecha_creacion' => $cursor->format('Y-m-d H:i:s'),
                'tipoxmsg_id'    => 1,
                'is_una_vista'   => 0
                // los demás campos (map_lat, map_lng, img, etc.) quedan NULL/0
            ]);

            // Avanza 5 h tras cada mensaje
            $cursor->add($step);
        }
    }

    echo poke();   // devuelve tu respuesta de control / OK
});



/* -----------------------------
 * accxusu → accxusu
 * ----------------------------- */
Flight::route('GET /ff/accxusu', function () {
    include DEFINITION;
    $cant = 200;
    for ($i = 0; $i < $cant; $i++) {
        $usu_id         = amarilis::min_max_numero(1, 250);
        $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');
        DB::insert('accxusu', [
            'usu_id'         => $usu_id,
            'fecha_creacion' => $fecha_creacion
        ]);
    }
    echo poke();
});

/* -----------------------------
 * gpay → gpay
 * ----------------------------- */
Flight::route('GET /ff/gpay', function () {
    include DEFINITION;
    $cant = 200;
    for ($i = 0; $i < $cant; $i++) {
        $usu_id         = amarilis::min_max_numero(1, 100);
        $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');
        $monto          = (string)amarilis::min_max_numero(1, 100);
        $gplay_info1    = amarilis::flor(10);
        $gplay_info2    = amarilis::flor(10);
        $gplay_info3    = amarilis::flor(10);

        DB::insert('gpay', [
            'usu_id'         => $usu_id,
            'fecha_creacion' => $fecha_creacion,
            'monto'          => $monto,
            'gplay_info1'    => $gplay_info1,
            'gplay_info2'    => $gplay_info2,
            'gplay_info3'    => $gplay_info3
        ]);
    }
    echo poke();
});

/* -----------------------------
 * ubixusu → ubixusu
 * ----------------------------- */
Flight::route('GET /ff/ubixusu', function () {
    include DEFINITION;
    $cant = 200;
    for ($i = 0; $i < $cant; $i++) {
        $usu_id         = amarilis::min_max_numero(1, 100);
        $maps           = amarilis::random_maps();
        $map_lat        = $maps['map_lat'];
        $map_lng        = $maps['map_lng'];
        $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');

        DB::insert('ubixusu', [
            'usu_id'         => $usu_id,
            'map_lat'        => $map_lat,
            'map_lng'        => $map_lng,
            'fecha_creacion' => $fecha_creacion
        ]);
    }
    echo poke();
});

/* -----------------------------
 * pubxneg → pubxneg
 * ----------------------------- */
Flight::route('GET /ff/pubxneg', function () {
    include DEFINITION;
    $cant = 200;
    for ($i = 0; $i < $cant; $i++) {
        $neg_id         = amarilis::min_max_numero(1, 250);
        $descripcion    = util::guardar_palabra_latina(lorem::ipsum(3));
        $img            = 'https://picsum.photos/600?' . amarilis::min_max_numero(1, 250);
        $is_visible     = amarilis::min_max_numero(0, 1);
        $is_valido      = amarilis::min_max_numero(0, 1);
        $fecha_creacion = amarilis::fecha_random('01/01/2025', '05/01/2025');

        DB::insert('pubxneg', [
            'neg_id'         => $neg_id,
            'descripcion'    => $descripcion,
            'img'            => $img,
            'is_visible'     => $is_visible,
            'is_valido'      => $is_valido,
            'fecha_creacion' => $fecha_creacion
        ]);
    }
    echo poke();
});

/* -----------------------------
 * pagweb → pagweb
 * ----------------------------- */
Flight::route('GET /ff/pagweb', function () {
    include DEFINITION;
    $cant = 50;
    for ($i = 0; $i < $cant; $i++) {
        $nombre = util::guardar_palabra_latina(lorem::ipsum(2));
        DB::insert('pagweb', ['nombre' => $nombre]);
    }
    echo poke();
});

/* -----------------------------
 * parrweb → parrweb
 * ----------------------------- */
Flight::route('GET /ff/parrweb', function () {
    include DEFINITION;
    $cant = 50;
    for ($i = 0; $i < $cant; $i++) {
        $contenido   = '<p>' . util::guardar_palabra_latina(lorem::ipsum(5)) . '</p>';
        $url_video01 = 'https://youtu.be/' . amarilis::flor(11);
        $url_video02 = 'https://youtu.be/' . amarilis::flor(11);
        $url_video03 = 'https://youtu.be/' . amarilis::flor(11);
        $url_video04 = 'https://youtu.be/' . amarilis::flor(11);
        $url_img01   = amarilis::flor_imagen(300);
        $url_img02   = amarilis::flor_imagen(300);
        $url_img03   = amarilis::flor_imagen(300);
        $url_img04   = amarilis::flor_imagen(300);
        $pagweb_id   = amarilis::min_max_numero(1, 50);

        DB::insert('parrweb', [
            'contenido'   => $contenido,
            'url_video01' => $url_video01,
            'url_video02' => $url_video02,
            'url_video03' => $url_video03,
            'url_video04' => $url_video04,
            'url_img01'   => $url_img01,
            'url_img02'   => $url_img02,
            'url_img03'   => $url_img03,
            'url_img04'   => $url_img04,
            'pagweb_id'   => $pagweb_id
        ]);
    }
    echo poke();
});



Flight::route('GET /ff/fich', function () {
    include DEFINITION;  // Debe cargar MeekroDB2 y tus credenciales

    // 1) Traer todos los usuarios existentes
    $usuarios = DB::query("SELECT * FROM usu");

    foreach ($usuarios as $u) {
        // 2) Solo procesar usuarios de tipo 2
        if ((int)$u['tipoxusu_id'] === 2) {

            // 2.a) Comprobar si ya existe ficha para este usuario
            $tieneFich = DB::queryFirstField(
                "SELECT COUNT(*) FROM fich WHERE usu_id = %i",
                $u['usu_id']
            );
            if ($tieneFich > 0) {
                continue; // ya tiene ficha, saltar al siguiente
            }

            // 2.b) Insertar nuevo registro en fich
            DB::insert('fich', [
                'fecha_creacion'       => $u['fecha_creacion'],
                'usu_id'               => $u['usu_id'],
                'is_activo'            => 1,
                'is_validado'          => 0,
                'descripcion'          => lorem::ipsum(5),
                'visitas'              => amarilis::min_max_numero(0, 500),
                'neg_id'               => 0,
                'fecha_ultimo_acceso'  => $u['fecha_creacion']
            ]);
        }
    }

    // 3) Devolver respuesta
    echo poke();
});




/**
 * Rellena la tabla fichxubi con ubicaciones de prueba
 * Para cada fich crea 3 ubicaciones (puedes cambiar $cant).
 * ─ GET /ff/fichxubi
 */
Flight::route('GET /ff/fichxubi', function () {
    include DEFINITION;                // constantes / carga de MeekroDB
    DB::query("SET NAMES 'utf8'");

    // 1) Listar todas las fich
    $lista_fich = DB::query('SELECT fich_id FROM fich');

    foreach ($lista_fich as $fich) {

        DB::insert('fichxubi', [
                    'fich_id'        => $fich['fich_id'],
                    'nombre_local'   => "Estoy descansando",
                    'direccion'      => "",
                    'map_lat'        => "",
                    'map_lng'        => "",
                    'provincia'      => "",
                    'ciudad'         => "",
                    'fecha_creacion' => date('Y-m-d H:i:s'),
                    'neg_id'         => 1
        ]);

        $cant = 3;                     // cuántas ubicaciones por fich

        for ($i = 0; $i < $cant; $i++) {

            // 2) Datos aleatorios
            $gps         = amarilis::random_maps();      // ['map_lat','map_lng','provincia','ciudad']
            $map_lat     = $gps['map_lat'];
            $map_lng     = $gps['map_lng'];
            $provincia   = lorem::ipsum(2);
            $ciudad      = lorem::ipsum(2);

            $nombre_local = lorem::ipsum(3);
            $direccion    = lorem::ipsum(4);
            $fecha_crea   = amarilis::fecha_random('12/06/2025', '15/06/2025');
            $neg_id       = amarilis::min_max_numero(2, 11); // 0 = sin negocio asociado            

            // 3) Insertar
            DB::insert('fichxubi', [
                'fich_id'        => $fich['fich_id'],
                'nombre_local'   => $nombre_local,
                'direccion'      => $direccion,
                'map_lat'        => $map_lat,
                'map_lng'        => $map_lng,
                'provincia'      => $provincia,
                'ciudad'         => $ciudad,
                'fecha_creacion' => $fecha_crea,
                'neg_id'         => $neg_id
            ]);
        }
    }

    echo poke();   // ⬅︎ mismo helpers que en tu /ff/estado
});


Flight::route('GET /ff/av_etiq', function () {
    include DEFINITION;
    $cant = 250;
    for ($i = 0; $i < $cant; $i++) {
        $nombre      = lorem::ipsum(5);


        DB::insert('av_etiq', [
            'nombre'      => $nombre
        ]);
    }
    echo poke();
});

/* ─────────────────────────────────────────────────────────────
   GET /tt/popuchart
   Poblado masivo: usu_id 2..10, fechas 01/08/2025–18/08/2025,
   horas 06:00–22:00 (0–n registros aleatorios por hora)
   ───────────────────────────────────────────────────────────── */
Flight::route('GET /tt/popuchart', function () {
    include DEFINITION;

    $from = new DateTime('2025-08-01 00:00:00');
    $to   = new DateTime('2025-08-18 23:59:59');

    DB::startTransaction();
    try {
        for ($d = clone $from; $d <= $to; $d->modify('+1 day')) {
            for ($h = 6; $h <= 22; $h++) {
                // cuántos registros meter en esta hora
                $n = amarilis::min_max_numero(0, 6); // 0..6 por hora
                for ($i = 0; $i < $n; $i++) {
                    $m  = amarilis::min_max_numero(0, 59);
                    $s  = amarilis::min_max_numero(0, 59);
                    $dt = clone $d; $dt->setTime($h, $m, $s);

                    DB::insert('usuxreg', [
                        'usu_id'         => amarilis::min_max_numero(2, 10),
                        'descripcion'    => 'auto ' . amarilis::min_max_numero(100, 999),
                        'fecha_creacion' => $dt->format('Y-m-d H:i:s')
                    ]);
                }
            }
        }
        DB::commit();
        echo poke(); // tu helper de ok
    } catch (Exception $e) {
        DB::rollback();
        Flight::halt(500, json_encode(['error'=>$e->getMessage()]));
    }
});

/**
 * Genera una imagen 500x500 estilo "pixel-art de cuadros" con motivo y paleta al azar.
 * $rutaSalida: directorio (se crea si no existe) o ruta de archivo .jpg
 * Retorna: ruta final del archivo .jpg generado.
 */
function crearImagenCuadros(string $rutaSalida, int $ancho = 500, int $alto = 500, ?int $semilla = null): string {
    if (!extension_loaded('gd')) {
        throw new RuntimeException('La extensión GD no está habilitada en PHP.');
    }
    if ($semilla !== null) mt_srand($semilla);

    // --- Grid (10x10 de 50px) ---
    $cols = 10; $rows = 10;
    $cellW = intdiv($ancho, $cols);
    $cellH = intdiv($alto,  $rows);
    $w = $cellW * $cols; $h = $cellH * $rows;

    $img = imagecreatetruecolor($w, $h);
    $bg  = imagecolorallocate($img, 20, 20, 20);
    imagefilledrectangle($img, 0, 0, $w-1, $h-1, $bg);

    // --- Paletas predefinidas (aleatorias) ---
    $paletas = [
        [[117,95,197],[102,178,255],[255,153,51],[251,86,7],[36,183,152]],
        [[41,128,185],[52,152,219],[26,188,156],[39,174,96],[243,156,18]],
        [[231,76,60],[192,57,43],[46,204,113],[52,73,94],[155,89,182]],
        [[44,62,80],[127,140,141],[149,165,166],[241,196,15],[211,84,0]],
        [[26,26,26],[64,64,64],[96,170,255],[255,221,87],[255,105,97]],
    ];
    $paleta = $paletas[array_rand($paletas)];

    // --- Motivo aleatorio ---
    $motivos = ['mosaico','damero','rayas','simetria','cruces'];
    $motivo  = $motivos[array_rand($motivos)];

    // Cache de colores GD
    $gdColors = [];
    $color = function(array $rgb) use ($img, &$gdColors) {
        $k = implode(',', $rgb);
        if (!isset($gdColors[$k])) $gdColors[$k] = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
        return $gdColors[$k];
    };

    switch ($motivo) {
        case 'mosaico':
            for ($y=0; $y<$rows; $y++) {
                for ($x=0; $x<$cols; $x++) {
                    $rgb = $paleta[array_rand($paleta)];
                    imagefilledrectangle($img, $x*$cellW, $y*$cellH, ($x+1)*$cellW-1, ($y+1)*$cellH-1, $color($rgb));
                }
            }
            break;

        case 'damero':
            $c1 = $color($paleta[array_rand($paleta)]);
            $c2 = $color($paleta[array_rand($paleta)]);
            for ($y=0; $y<$rows; $y++) {
                for ($x=0; $x<$cols; $x++) {
                    $c = (($x+$y)%2===0) ? $c1 : $c2;
                    imagefilledrectangle($img, $x*$cellW, $y*$cellH, ($x+1)*$cellW-1, ($y+1)*$cellH-1, $c);
                }
            }
            break;

        case 'rayas':
            $vertical = (mt_rand(0,1)===1);
            if ($vertical) {
                for ($i=0; $i<$cols; $i++) {
                    $rgb = $paleta[array_rand($paleta)];
                    imagefilledrectangle($img, $i*$cellW, 0, ($i+1)*$cellW-1, $h-1, $color($rgb));
                }
            } else {
                for ($i=0; $i<$rows; $i++) {
                    $rgb = $paleta[array_rand($paleta)];
                    imagefilledrectangle($img, 0, $i*$cellH, $w-1, ($i+1)*$cellH-1, $color($rgb));
                }
            }
            break;

        case 'simetria':
            $mid = intdiv($cols, 2);
            for ($y=0; $y<$rows; $y++) {
                for ($x=0; $x<$mid; $x++) {
                    $rgb = $paleta[array_rand($paleta)];
                    $c = $color($rgb);
                    // izquierda
                    imagefilledrectangle($img, $x*$cellW, $y*$cellH, ($x+1)*$cellW-1, ($y+1)*$cellH-1, $c);
                    // espejo derecha
                    $mx = $cols - 1 - $x;
                    imagefilledrectangle($img, $mx*$cellW, $y*$cellH, ($mx+1)*$cellW-1, ($y+1)*$cellH-1, $c);
                }
            }
            if ($cols%2===1) {
                $x = $mid;
                for ($y=0; $y<$rows; $y++) {
                    $c = $color($paleta[array_rand($paleta)]);
                    imagefilledrectangle($img, $x*$cellW, $y*$cellH, ($x+1)*$cellW-1, ($y+1)*$cellH-1, $c);
                }
            }
            break;

        case 'cruces':
            $base = $color($paleta[array_rand($paleta)]);
            imagefilledrectangle($img, 0, 0, $w-1, $h-1, $base);
            imagesetthickness($img, max(1, intdiv(min($cellW,$cellH), 6)));
            for ($y=0; $y<$rows; $y++) {
                for ($x=0; $x<$cols; $x++) {
                    if (mt_rand(0,100) < 60) {
                        $c = $color($paleta[array_rand($paleta)]);
                        $cx = $x*$cellW + intdiv($cellW,2);
                        $cy = $y*$cellH + intdiv($cellH,2);
                        imageline($img, $cx - intdiv($cellW,3), $cy, $cx + intdiv($cellW,3), $cy, $c);
                        imageline($img, $cx, $cy - intdiv($cellH,3), $cx, $cy + intdiv($cellH,3), $c);
                    }
                }
            }
            break;
    }

    // Borde opcional
    if (mt_rand(0,1)===1) {
        imagesetthickness($img, 3);
        imagerectangle($img, 1, 1, $w-2, $h-2, $color([20,20,20]));
    }

    // --- Resolver ruta destino ---
    $dest = $rutaSalida;
    if (is_dir($dest) || str_ends_with($dest, DIRECTORY_SEPARATOR)) {
        if (!is_dir($dest) && !@mkdir($dest, 0775, true) && !is_dir($dest)) {
            imagedestroy($img);
            throw new RuntimeException("No se pudo crear el directorio: $dest");
        }
        $dest = rtrim($dest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR .
                'cuadros_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.jpg';
    } else {
        $dir = dirname($dest);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($img);
            throw new RuntimeException("No se pudo crear el directorio: $dir");
        }
        if (!preg_match('/\.(jpg|jpeg)$/i', $dest)) $dest .= '.jpg';
    }

    imagejpeg($img, $dest, 90);
    imagedestroy($img);
    return $dest;
}

// ===== Ejemplos de uso =====
// 1) Guardar en carpeta (genera nombre automático):
// $ruta = crearImagenCuadros(__DIR__ . '/salidas/');
// echo "Creada: $ruta\n";

// 2) Guardar con nombre exacto:
// $ruta = crearImagenCuadros(__DIR__ . '/salidas/mi_imagen.jpg');
// echo "Creada: $ruta\n";


/* -----------------------------------------------------------
   GET /ff/seed-usu(/@cant)
   - Crea N usuarios aleatorios en `usu`
   - Si tipoxusu_id = 2 (fichera):
       • Crea su `fich`
       • Crea ubicación inicial en `fichxubi` ("Estoy descansando")
       • Actualiza fich.fichxubi_actual con el nuevo fichxubi_id
   - Genera imagen de perfil con crearImagenCuadros()
----------------------------------------------------------- */
Flight::route('GET /ff/seed-usu(/@cant)', function ($cant = null) {
    include DEFINITION;

    date_default_timezone_set('America/Lima');
    ini_set('memory_limit', '512M');
    set_time_limit(0);

    $cant = (int)($cant ?? 1000);
    if ($cant < 1) $cant = 1;

    // Carpeta pública para las fotos de perfil (ajústala a tu proyecto)
    $destDir  = VARPATH . vari('FOTO_FICH_MAX');
    if (!is_dir($destDir) && !@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
        Flight::halt(500, json_encode(['error' => 'No se pudo crear la carpeta de perfiles']));
        return;
    }

    // Traer IDs válidos de tipoxusu (FK)
    $tipos = DB::queryFirstColumn('SELECT tipoxusu_id FROM tipoxusu');
    if (empty($tipos)) {
        Flight::halt(400, json_encode(['error' => 'No hay registros en tipoxusu; crea al menos uno.']));
        return;
    }

    // Fecha de nacimiento aleatoria (DATE)
    $rndBirth = function (string $desde = '01/01/1975', string $hasta = '31/12/2007'): string {
        $d = DateTime::createFromFormat('d/m/Y', $desde);
        $h = DateTime::createFromFormat('d/m/Y', $hasta);
        if (!$d || !$h) return '1990-01-01';
        $ts = mt_rand($d->getTimestamp(), $h->getTimestamp());
        return date('Y-m-d', $ts);
    };

    DB::startTransaction();
    try {
        for ($i = 0; $i < $cant; $i++) {

            $fechaCreacion   = amarilis::fecha_random('01/01/2025', date('d/m/Y'));
            $fechaUltAcceso  = $fechaCreacion;

            // Locación random (para el usuario, no para la ubicación inicial)
            $maps    = amarilis::random_maps();
            $map_lat = $maps['map_lat'] ?? null;
            $map_lng = $maps['map_lng'] ?? null;

            // Premium random (~20%)
            $isPremium        = amarilis::min_max_numero(0, 100) < 20 ? 1 : 0;
            $fechaFinPremium  = $isPremium ? amarilis::fecha_random(date('d/m/Y'), '31/12/2025') : null;

            // Elegir tipo
            //$tipoElegido = $tipos[array_rand($tipos)];
            $tipoElegido = 2;

            // 1) Insertar USU
            DB::insert('usu', [
                'cod_usu'             => 'u_' . amarilis::flor(12),
                'google_uid'          => 'g_' . amarilis::flor(16),
                'img_perfil'          => '1.jpg', // provisional
                'sobrenombre'         => lorem::ipsum(2),
                'celular'             => '9' . amarilis::numero_random(8),
                'is_activo'           => 1,
                'fecha_nacimiento'    => $rndBirth(),
                'provincia'           => lorem::ipsum(2),
                'map_lat'             => $map_lat,
                'map_lng'             => $map_lng,
                'fecha_creacion'      => $fechaCreacion,
                'is_premium'          => $isPremium,
                'fecha_fin_premium'   => $fechaFinPremium,
                'tipoxusu_id'         => $tipoElegido,
                'is_fantasma'         => 1,
                'is_acepto_terminos'  => 1,
                'descripcion'         => 'Sin descripcion',
                'fecha_ultimo_acceso' => $fechaUltAcceso
            ]);
            $usuId = DB::insertId();

            // 2) Generar imagen de perfil y actualizar
            $rutaFinal = crearImagenCuadros($destDir . "usu_{$usuId}.jpg", 500, 500);
            DB::update('usu', [
                'img_perfil' => "usu_{$usuId}.jpg" // o 'perfiles/' . basename($rutaFinal)
            ], 'usu_id=%i', $usuId);

            // 3) Si es FICHERA (tipoxusu_id = 2), crear FICH + FICHXUBI inicial
            if ((int)$tipoElegido === 2) {
                // 3.a) FICH
                DB::insert('fich', [
                    'fecha_creacion'      => $fechaCreacion,
                    'usu_id'              => $usuId,
                    'is_activo'           => 1,
                    'is_validado'         => 0,
                    'visitas'             => amarilis::min_max_numero(0, 500),
                    'neg_id'              => 0,
                    'fecha_ultimo_acceso' => util::fecha_hora_actual(),
                    'fichxubi_actual'     => 0
                ]);
                $fichId = DB::insertId();

                // 3.b) FICHXUBI inicial (“Estoy descansando”)
                DB::insert('fichxubi', [
                    'fich_id'        => $fichId,
                    'nombre_local'   => 'CLIMAX NC',
                    'direccion'      => 'Av. Nicolás de Piérola 611, Lima 15001',
                    'map_lat'        => '-12.049344487881365',
                    'map_lng'        => '-77.03784663363824',
                    'provincia'      => '',
                    'ciudad'         => '',
                    'fecha_creacion' => util::fecha_hora_actual(),
                    'fecha_hora_inicio' => util::fecha_hora_actual(),
                    'neg_id'         => 2   // ajusta a 1 si prefieres asociar a un "descanso" por defecto
                ]);
                $fichxubiId = DB::insertId();

                // 3.c) Actualizar el puntero actual en FICH
                if ($fichxubiId) {
                    DB::update('fich', ['fichxubi_actual' => $fichxubiId], 'fich_id=%i', $fichId);
                }
            }
        }

        DB::commit();
        echo poke();
    } catch (Exception $e) {
        DB::rollback();
        Flight::halt(500, json_encode(['error' => $e->getMessage()]));
    }
});


/* -----------------------------------------------------------
   GET /ff/fich-backfill
   - Para cada usu tipoxusu_id=2 que no tenga fich:
       • Crea su `fich`
       • Crea `fichxubi` inicial ("Estoy descansando")
       • Actualiza fich.fichxubi_actual
   - Para fich existentes sin ubicaciones:
       • Crea `fichxubi` inicial y setea fichxubi_actual
----------------------------------------------------------- */
Flight::route('GET /ff/fich-backfill', function () {
    include DEFINITION;

    date_default_timezone_set('America/Lima');

    DB::startTransaction();
    try {
        // 1) Usuarios tipo 2 sin ficha → crear fich + ubic inicial
        $sinFich = DB::query(
            "SELECT u.usu_id, COALESCE(u.fecha_creacion, NOW()) AS fc
               FROM usu u
          LEFT JOIN fich f ON f.usu_id = u.usu_id
              WHERE u.tipoxusu_id = %i
                AND f.fich_id IS NULL",
            2
        );

        foreach ($sinFich as $u) {
            $fc = $u['fc'];

            DB::insert('fich', [
                'fecha_creacion'      => $fc,
                'usu_id'              => $u['usu_id'],
                'is_activo'           => 1,
                'is_validado'         => 0,
                'visitas'             => amarilis::min_max_numero(0, 500),
                'neg_id'              => 0,
                'fecha_ultimo_acceso' => $fc,
                'fichxubi_actual'     => 0
            ]);
            $fichId = DB::insertId();

            DB::insert('fichxubi', [
                'fich_id'        => $fichId,
                'nombre_local'   => 'CLIMAX NC',
                'direccion'      => 'Av. Nicolás de Piérola 611, Lima 15001',
                'map_lat'        => '-12.049344487881365',
                'map_lng'        => '-77.03784663363824',
                'provincia'      => '',
                'ciudad'         => '',
                'fecha_creacion' => $fc,
                'neg_id'         => 2
            ]);
            $fxId = DB::insertId();

            if ($fxId) {
                DB::update('fich', ['fichxubi_actual' => $fxId], 'fich_id=%i', $fichId);
            }
        }

        // 2) Fich existentes sin ninguna ubicación → crear ubic inicial y setear actual
        $fichSinUbi = DB::query(
            "SELECT f.fich_id, COALESCE(f.fecha_creacion, NOW()) AS fc
               FROM fich f
          LEFT JOIN fichxubi fx ON fx.fich_id = f.fich_id
              WHERE fx.fichxubi_id IS NULL"
        );

        foreach ($fichSinUbi as $f) {
            DB::insert('fichxubi', [
                'fich_id'        => $f['fich_id'],
                'nombre_local'   => 'CLIMAX NC',
                'direccion'      => 'Av. Nicolás de Piérola 611, Lima 15001',
                'map_lat'        => '-12.049344487881365',
                'map_lng'        => '-77.03784663363824',
                'provincia'      => '',
                'ciudad'         => '',
                'fecha_creacion' => $f['fc'],
                'neg_id'         => 2
            ]);
            $fxId = DB::insertId();

            if ($fxId) {
                DB::update('fich', ['fichxubi_actual' => $fxId], 'fich_id=%i', $f['fich_id']);
            }
        }

        DB::commit();
        echo poke();
    } catch (Exception $e) {
        DB::rollback();
        Flight::halt(500, json_encode(['error' => $e->getMessage()]));
    }
});


// Crea 3–6 fotos (configurable) para una fich y registra en fotoxfich
function crearFotosParaFich(
    int $fichId,
    string $fechaBase = null,
    int $min = 3,
    int $max = 6,
    int $sizeFull = 800,
    int $sizeMini = 400
): void {
    // 0) Resolver usu_id a partir de la fich
    $row = DB::queryFirstRow(
        "SELECT f.usu_id,
                COALESCE(u.fecha_creacion, f.fecha_creacion) AS fc
           FROM fich f
           JOIN usu  u ON u.usu_id = f.usu_id
          WHERE f.fich_id = %i",
        $fichId
    );
    if (!$row || empty($row['usu_id'])) {
        throw new RuntimeException("No se encontró usu_id para fich_id={$fichId}");
    }
    $usuId = (int)$row['usu_id'];

    // 1) Directorios base FULL y MINI
    $dirFullBase = rtrim(VARPATH . vari('FOTO_FICH_MAX'), "/\\") . DIRECTORY_SEPARATOR;
    $dirMiniBase = rtrim(VARPATH . vari('FOTO_FICH_MINI'), "/\\") . DIRECTORY_SEPARATOR;

    // 2) Subdirectorios por usuario: /{usu_id}/
    $dirFull = $dirFullBase . $usuId . DIRECTORY_SEPARATOR;
    $dirMini = $dirMiniBase . $usuId . DIRECTORY_SEPARATOR;

    // Crear subdirectorios si no existen
    if (!is_dir($dirFull) && !@mkdir($dirFull, 0775, true) && !is_dir($dirFull)) {
        throw new RuntimeException("No se pudo crear el directorio FULL: $dirFull");
    }
    if (!is_dir($dirMini) && !@mkdir($dirMini, 0775, true) && !is_dir($dirMini)) {
        throw new RuntimeException("No se pudo crear el directorio MINI: $dirMini");
    }

    // 3) Cantidad de fotos a generar
    if ($min < 1) $min = 1;
    if ($max < $min) $max = $min;
    $cantidad = mt_rand($min, $max);

    // 4) Rango de fechas
    $desde = $fechaBase
        ? date('d/m/Y', strtotime($fechaBase))
        : ($row['fc'] ? date('d/m/Y', strtotime($row['fc'])) : '01/01/2025');
    $hasta = date('d/m/Y');

    // 5) Generación
    for ($i = 1; $i <= $cantidad; $i++) {
        // Nombre base (sin rutas)
        $nombre = sprintf('fich_%d_%02d.jpg', $fichId, $i);

        // Evitar colisiones si ya existen archivos con el mismo nombre
        $fullPath = $dirFull . $nombre;
        $miniPath = $dirMini . $nombre;
        if (file_exists($fullPath) || file_exists($miniPath)) {
            $nombre   = sprintf('fich_%d_%02d_%04d.jpg', $fichId, $i, mt_rand(1000, 9999));
            $fullPath = $dirFull . $nombre;
            $miniPath = $dirMini . $nombre;
        }

        // Generar imágenes FULL y MINI con el mismo nombre
        crearImagenCuadros($fullPath, $sizeFull, $sizeFull);
        crearImagenCuadros($miniPath, $sizeMini, $sizeMini);

        // Fecha de creación aleatoria
        $fechaCreacion = amarilis::fecha_random($desde, $hasta);

        // Insertar registro (img = SOLO nombre+extensión)
        DB::insert('fotoxfich', [
            'fich_id'        => $fichId,
            'img'            => $nombre, // <- sin rutas
            'is_visible'     => amarilis::min_max_numero(0, 1),
            'is_valido'      => amarilis::min_max_numero(0, 1),
            'fecha_creacion' => $fechaCreacion
        ]);
    }
}



/* -----------------------------------------------------------
   GET /ff/fotoxfich-backfill(/@min/@max)
   - Crea entre @min y @max fotos para cada FICH sin fotos
----------------------------------------------------------- */
Flight::route('GET /ff/fotoxfich-backfill(/@min/@max)', function ($min = 3, $max = 6) {
    include DEFINITION;
    date_default_timezone_set('America/Lima');

    $min = (int)$min; $max = (int)$max;

    // fich sin ninguna foto
    $pendientes = DB::query(
        "SELECT f.fich_id, COALESCE(f.fecha_creacion, NOW()) AS fc
           FROM fich f
      LEFT JOIN fotoxfich fx ON fx.fich_id = f.fich_id
          WHERE fx.fich_id IS NULL"
    );

    if (empty($pendientes)) { echo poke(); return; }

    DB::startTransaction();
    try {
        foreach ($pendientes as $f) {
            crearFotosParaFich((int)$f['fich_id'], $f['fc'], $min, $max);
        }
        DB::commit();
        echo poke();
    } catch (Exception $e) {
        DB::rollback();
        Flight::halt(500, json_encode(['error' => $e->getMessage()]));
    }
});


/**
 * Crea N estados (3–6 por defecto) para una FICH.
 * Genera imágenes con crearImagenCuadros() en /www/estado/
 * y rellena: img, is_visible, visitas, fecha_creacion, fecha_fin, fich_id.
 */
function crearEstadosParaFich(
    int $fichId,
    string $rangoDesde = '01/01/2025',
    ?string $rangoHasta = null,
    int $min = 3,
    int $max = 6,
    int $sizeFull = 600,
    int $sizeMini = 300
): void {
    // 0) Resolver usu_id de la ficha
    $row = DB::queryFirstRow(
        "SELECT f.usu_id,
                COALESCE(u.fecha_creacion, f.fecha_creacion) AS fc
           FROM fich f
           JOIN usu  u ON u.usu_id = f.usu_id
          WHERE f.fich_id = %i",
        $fichId
    );
    if (!$row || empty($row['usu_id'])) {
        throw new RuntimeException("No se encontró usu_id para fich_id={$fichId}");
    }
    $usuId = (int)$row['usu_id'];

    // 1) Directorios base FULL y MINI
    $dirFullBase = rtrim(VARPATH . vari('ESTADO_MAX'), "/\\") . DIRECTORY_SEPARATOR;
    $dirMiniBase = rtrim(VARPATH . vari('ESTADO_MINI'), "/\\") . DIRECTORY_SEPARATOR;

    // 2) Subdirectorios por usuario: /{usu_id}/
    $dirFull = $dirFullBase . $usuId . DIRECTORY_SEPARATOR;
    $dirMini = $dirMiniBase . $usuId . DIRECTORY_SEPARATOR;

    // Crear subdirectorios si no existen
    if (!is_dir($dirFull) && !@mkdir($dirFull, 0775, true) && !is_dir($dirFull)) {
        throw new RuntimeException("No se pudo crear el directorio de estados FULL: $dirFull");
    }
    if (!is_dir($dirMini) && !@mkdir($dirMini, 0775, true) && !is_dir($dirMini)) {
        throw new RuntimeException("No se pudo crear el directorio de estados MINI: $dirMini");
    }

    // 3) Cantidad de estados
    if ($min < 1) $min = 1;
    if ($max < $min) $max = $min;
    $cant = mt_rand($min, $max);

    // 4) Rango de fechas
    if ($rangoHasta === null) $rangoHasta = date('d/m/Y');
    $desde = $rangoDesde;
    $hasta = $rangoHasta;

    // 5) Generación de estados
    for ($i = 1; $i <= $cant; $i++) {
        // Nombre base (sin rutas)
        $nombre = sprintf('estado_fich_%d_%02d.jpg', $fichId, $i);

        // Evitar colisiones si ya existen archivos
        $fullPath = $dirFull . $nombre;
        $miniPath = $dirMini . $nombre;
        if (file_exists($fullPath) || file_exists($miniPath)) {
            $nombre   = sprintf('estado_fich_%d_%02d_%04d.jpg', $fichId, $i, mt_rand(1000, 9999));
            $fullPath = $dirFull . $nombre;
            $miniPath = $dirMini . $nombre;
        }

        // Generar imágenes FULL y MINI
        crearImagenCuadros($fullPath, $sizeFull, $sizeFull);
        crearImagenCuadros($miniPath, $sizeMini, $sizeMini);

        // Fechas coherentes: fin >= creación (0–3 días después)
        $fc   = amarilis::fecha_random($desde, $hasta); // dd/mm/YYYY
        $fcDt = DateTime::createFromFormat('d/m/Y', $fc) ?: new DateTime();

        $diasExtra = amarilis::min_max_numero(0, 3);
        $ffDt = clone $fcDt; $ffDt->modify("+{$diasExtra} day");
        $ff = amarilis::fecha_random($fcDt->format('d/m/Y'), $ffDt->format('d/m/Y'));

        // Insertar estado (img = SOLO nombre de archivo)
        DB::insert('estado', [
            'img'            => $nombre, // <- sin rutas
            'is_visible'     => amarilis::min_max_numero(0, 1),
            'visitas'        => amarilis::min_max_numero(0, 500),
            'fecha_creacion' => $fc,
            'fich_id'        => $fichId,
            'fecha_fin'      => $ff
        ]);
    }
}


/* -----------------------------------------------------------
   GET /ff/estado-backfill(/@min/@max)
   - Crea entre @min y @max estados para cada FICH sin estados
----------------------------------------------------------- */
Flight::route('GET /ff/estado-backfill(/@min/@max)', function ($min = 3, $max = 6) {
    include DEFINITION;
    date_default_timezone_set('America/Lima');

    $min = (int)$min; $max = (int)$max;

    // Fich sin estados
    $pendientes = DB::query(
        "SELECT f.fich_id, COALESCE(f.fecha_creacion, NOW()) AS fc
           FROM fich f
      LEFT JOIN estado e ON e.fich_id = f.fich_id
          WHERE e.fich_id IS NULL"
    );

    if (empty($pendientes)) { echo poke(); return; }

    DB::startTransaction();
    try {
        foreach ($pendientes as $f) {
            // Usamos rango desde la fecha de creación de la ficha hasta hoy
            $desde = date('d/m/Y', strtotime($f['fc']));
            $hasta = date('d/m/Y');
            crearEstadosParaFich((int)$f['fich_id'], $desde, $hasta, $min, $max);
        }
        DB::commit();
        echo poke();
    } catch (Exception $e) {
        DB::rollback();
        Flight::halt(500, json_encode(['error' => $e->getMessage()]));
    }
});


// ================== TEST MASIVO DE FECHAS ==================
Flight::route('GET /ff/fecha_hora_inicio', function () {
    include DEFINITION;
    DB::query("SET NAMES 'utf8mb4'");

    // Fechas en zona Lima (-05:00)
    $tzLima = new DateTimeZone('-05:00');

    // HOY 00:00:00
    $todayMidnight = new DateTime('today', $tzLima);
    $todayMidnightLima = $todayMidnight->format('Y-m-d H:i:s');

    // MAÑANA 00:00:00
    $tomorrowMidnight = new DateTime('tomorrow', $tzLima);
    $tomorrowMidnightLima = $tomorrowMidnight->format('Y-m-d H:i:s');

    // HOY 01:00:00
    $today1am = clone $todayMidnight;
    $today1am->modify('+1 hour');
    $today1amLima = $today1am->format('Y-m-d H:i:s');

    DB::startTransaction();
    try {

        DB::query("UPDATE neg SET fecha_ultimo_acceso = %s", $todayMidnightLima);        

        // 1) fichxubi: setear fecha_hora_inicio = HOY 00:00:00 (Lima)
        DB::query("UPDATE fichxubi SET fecha_hora_inicio = %s", $todayMidnightLima);
        $fxuInicioUpdated = DB::affectedRows();

        // 2) fichxubi: setear fecha_creacion en UTC "ahora"
        DB::query("UPDATE fichxubi SET fecha_creacion = UTC_TIMESTAMP()");
        $fxuCreacUpdated = DB::affectedRows();

        // 3) estado: fecha_creacion = HOY 00:00:00, fecha_fin = MAÑANA 00:00:00 (Lima)
        DB::query("UPDATE estado SET fecha_creacion = %s, fecha_fin = %s", $todayMidnightLima, $tomorrowMidnightLima);
        $estUpdated = DB::affectedRows();

        // 4) fich: fecha_ultimo_acceso = HOY 01:00:00 (Lima) para TODOS los registros
        DB::query("UPDATE fich SET fecha_ultimo_acceso = %s", $today1amLima);
        $fichAccesoUpdated = DB::affectedRows();

        DB::commit();
        Flight::json([
            'status' => 'ok',
            'lima_today_00'     => $todayMidnightLima,
            'lima_today_01'     => $today1amLima,
            'lima_tomorrow_00'  => $tomorrowMidnightLima,
            'updates' => [
                'fichxubi.fecha_hora_inicio'     => $fxuInicioUpdated,
                'fichxubi.fecha_creacion(UTC)'   => $fxuCreacUpdated,
                'estado.fechas(hoy/tomorrow 00)' => $estUpdated,
                'fich.fecha_ultimo_acceso(01:00)' => $fichAccesoUpdated
            ]
        ]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status' => 'error', 'msg' => $e->getMessage()], 500);
    }
});


Flight::route('GET /llenarFichxUbi', function () {
    header('Content-Type: application/json; charset=utf-8');

    try {
        DB::query("SET NAMES 'utf8mb4'");

        // Param opcional: ?force=1 para insertar aunque ya exista fichxubi del fich
        $req   = Flight::request();
        $force = !empty($req->query['force']) && (int)$req->query['force'] === 1;

        // Negocios activos EXCLUYENDO neg_id = 1
        $negocios = DB::query("
            SELECT neg_id, nombre, ciudad, provincia, departamento, direccion, map_lat, map_lng
            FROM neg
            WHERE COALESCE(is_activo,1) = 1
              AND neg_id <> 1
        ");
        if (empty($negocios)) {
            Flight::json(['ok'=>false,'error'=>'No hay negocios activos disponibles (excluyendo neg_id=1).'], 400);
            return;
        }

        // Todos los fich
        $fichs = DB::query("SELECT fich_id FROM fich ORDER BY fich_id");
        if (empty($fichs)) {
            Flight::json([
                'ok'=>true,'inserted_rows'=>0,'updated_rows'=>0,'skipped'=>0,
                'message'=>'No hay fich para procesar.'
            ]);
            return;
        }

        DB::startTransaction();

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $detalles = [];

        foreach ($fichs as $f) {
            $fich_id = (int)$f['fich_id'];

            if (!$force) {
                $existe = DB::queryFirstField("SELECT COUNT(*) FROM fichxubi WHERE fich_id=%i", $fich_id);
                if ($existe > 0) { $skipped++; continue; }
            }

            // Elegir negocio al azar (ya excluye neg_id=1)
            $pick = $negocios[array_rand($negocios)];

            // Insert en fichxubi con fechas de hoy
            DB::insert('fichxubi', [
                'fich_id'           => $fich_id,
                'nombre_local'      => $pick['nombre'],
                'direccion'         => $pick['direccion'],
                'map_lat'           => $pick['map_lat'],
                'map_lng'           => $pick['map_lng'],
                'ciudad'            => $pick['ciudad'],
                'provincia'         => $pick['provincia'],
                'departamento'      => $pick['departamento'],
                'fecha_creacion'    => DB::sqleval('NOW()'),
                'neg_id'            => $pick['neg_id'],
                'fecha_hora_inicio' => DB::sqleval('NOW()'),
            ]);
            $fichxubi_id = DB::insertId();
            $inserted++;

            // Actualizar fich
            DB::update('fich', [
                'neg_id'              => $pick['neg_id'],
                'fichxubi_actual'     => $fichxubi_id,
                'fecha_ultimo_acceso' => DB::sqleval('NOW()'),
            ], "fich_id=%i", $fich_id);
            $updated++;

            $detalles[] = [
                'fich_id'     => $fich_id,
                'neg_id'      => (int)$pick['neg_id'],
                'fichxubi_id' => (int)$fichxubi_id,
            ];
        }

        DB::commit();

        Flight::json([
            'ok'            => true,
            'inserted_rows' => $inserted,
            'updated_rows'  => $updated,
            'skipped'       => $skipped,
            'force'         => $force ? 1 : 0,
            'message'       => 'Se insertó 1 fichxubi por cada fich con negocio aleatorio (excluyendo neg_id=1) y fechas de hoy.',
            'sample'        => array_slice($detalles, 0, 10)
        ]);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['ok'=>false,'error'=>$e->getMessage()], 500);
    }
});


// ===============================================
// ACTIVA A TODAS LAS FICHERAS PARA TK_TAB_PRES
// ===============================================
Flight::route('GET /ff/fich-activar', function () {
    include DEFINITION;
    date_default_timezone_set('America/Lima');

    DB::startTransaction();

    try {

        // ============================================================
        // 1) ACTIVAR TODAS LAS USUARIAS TIPO 2
        // ============================================================
        DB::update('usu', ['is_activo' => 1], "tipoxusu_id = %i", 2);


        // ============================================================
        // 2) ASEGURAR QUE TODAS LAS USUARIAS TIPO 2 TENGAN FICHA
        // ============================================================
        $sinFich = DB::query("
            SELECT u.usu_id,
                   COALESCE(u.fecha_creacion, NOW()) AS fc
            FROM usu u
            LEFT JOIN fich f ON f.usu_id = u.usu_id
            WHERE u.tipoxusu_id = 2
              AND f.fich_id IS NULL
        ");

        foreach ($sinFich as $u) {

            DB::insert('fich', [
                'fecha_creacion'      => $u['fc'],
                'usu_id'              => $u['usu_id'],
                'is_activo'           => 1,
                'is_validado'         => 0,
                'visitas'             => amarilis::min_max_numero(0, 500),
                'neg_id'              => 0,
                'fecha_ultimo_acceso' => DB::sqleval('NOW()'),
                'fichxubi_actual'     => 0
            ]);

            $fichId = DB::insertId();

            // Crear ubicación inicial
            DB::insert('fichxubi', [
                'fich_id'        => $fichId,
                'nombre_local'   => 'CLIMAX NC',
                'direccion'      => 'Av. Nicolás de Piérola 611, Lima 15001',
                'map_lat'        => '-12.049344487881365',
                'map_lng'        => '-77.03784663363824',
                'provincia'      => '',
                'ciudad'         => '',
                'fecha_creacion' => DB::sqleval('NOW()'),
                'neg_id'         => 2
            ]);
            $fxId = DB::insertId();

            DB::update('fich', ['fichxubi_actual' => $fxId], 'fich_id=%i', $fichId);
        }


        // ============================================================
        // 3) GARANTIZAR UBICACIÓN DEL DÍA PARA TODAS LAS FICHAS
        // ============================================================
        $fichas = DB::query("
            SELECT f.fich_id
            FROM fich f
            JOIN usu u ON u.usu_id = f.usu_id
            WHERE u.tipoxusu_id = 2
              AND u.is_activo = 1
        ");

        foreach ($fichas as $f) {

            // revisar si tiene una fichxubi HOY
            $tieneHoy = DB::queryFirstField("
                SELECT 1
                FROM fichxubi
                WHERE fich_id = %i
                  AND DATE(CONVERT_TZ(fecha_creacion,'+00:00','-05:00')) = CURDATE()
                LIMIT 1
            ", $f['fich_id']);

            // si no tiene → crear ubicación HOY
            if (!$tieneHoy) {
                DB::insert('fichxubi', [
                    'fich_id'        => $f['fich_id'],
                    'nombre_local'   => 'CLIMAX NC',
                    'direccion'      => 'Av. Nicolás de Piérola 611, Lima 15001',
                    'map_lat'        => '-12.049344487881365',
                    'map_lng'        => '-77.03784663363824',
                    'provincia'      => '',
                    'ciudad'         => '',
                    'fecha_creacion' => DB::sqleval('NOW()'),
                    'neg_id'         => 2
                ]);
                $fxId = DB::insertId();

                DB::update('fich', ['fichxubi_actual' => $fxId], 'fich_id=%i', $f['fich_id']);
            }

            // actualizar fecha_ultimo_acceso a HOY (requisito del listado)
            DB::update('fich', [
                'fecha_ultimo_acceso' => DB::sqleval('NOW()')
            ], "fich_id=%i", $f['fich_id']);
        }

        DB::commit();
        echo poke();

    } catch (Exception $e) {
        DB::rollback();
        Flight::halt(500, json_encode(['error' => $e->getMessage()]));
    }
});

// CREA 150 FICHERAS NUEVAS
Flight::route('GET /ff/debutantes', function () {
    include DEFINITION;

    date_default_timezone_set('America/Lima');

    $cant = 150; // número fijo de debutantes

    DB::startTransaction();
    try {

        for ($i = 0; $i < $cant; $i++) {

            // ===============================
            // 1) DATOS DE USU
            // ===============================
            $fechaCreacion  = amarilis::fecha_random('01/01/2025', date('d/m/Y'));
            $fechaUltAcceso = util::fecha_hora_actual();

            // Coordenadas aleatorias cerca del centro de Lima
            $coords = amarilis::random_maps(-12.05, -77.04, 1.2);

            // Nombre ficticio (sobrenombre)
            $sob = lorem::ipsum(2);

            DB::insert('usu', [
                'cod_usu'             => 'u_' . amarilis::flor(10),
                'google_uid'          => 'g_' . amarilis::flor(14),
                'img_perfil'          => 'default.jpg',   // NO SE CREA IMAGEN
                'sobrenombre'         => $sob,
                'celular'             => '9' . amarilis::numero_random(8),
                'is_activo'           => 1,
                'fecha_nacimiento'    => amarilis::fecha_random('01/01/1980', '31/12/2005'),
                'provincia'           => 'Lima',
                'map_lat'             => $coords['map_lat'],
                'map_lng'             => $coords['map_lng'],
                'fecha_creacion'      => $fechaCreacion,
                'is_premium'          => 0,
                'fecha_fin_premium'   => null,
                'tipoxusu_id'         => 2,     // ES FICHERA
                'is_fantasma'         => 1,
                'is_acepto_terminos'  => 1,
                'descripcion'         => 'Sin descripción',
                'fecha_ultimo_acceso' => $fechaUltAcceso
            ]);

            $usuId = DB::insertId();



            // ===============================
            // 2) CREAR FICH
            // ===============================
            DB::insert('fich', [
                'fecha_creacion'      => $fechaCreacion,
                'usu_id'              => $usuId,
                'is_validado'         => 0,
                'visitas'             => amarilis::min_max_numero(0, 300),
                'neg_id'              => 0,
                'fecha_ultimo_acceso' => $fechaUltAcceso,
                'fichxubi_actual'     => 0
            ]);

            $fichId = DB::insertId();



            // ===============================
            // 3) UBICACIÓN INICIAL VALIDA
            //    (NO descaso, NO neg_id = 1)
            // ===============================
            // Local aleatorio entre tus locales reales
            $local = DB::queryFirstRow("
                SELECT neg_id, nombre
                FROM neg
                WHERE is_activo = 1
                  AND neg_id <> 1    
                ORDER BY RAND()
                LIMIT 1
            ");

            if (!$local) {
                throw new Exception("No existen locales válidos en tabla NEG.");
            }

            DB::insert('fichxubi', [
                'fich_id'          => $fichId,
                'nombre_local'     => $local['nombre'],
                'direccion'        => '',
                'map_lat'          => $coords['map_lat'],
                'map_lng'          => $coords['map_lng'],
                'provincia'        => 'Lima',
                'ciudad'           => '',
                'fecha_creacion'   => $fechaUltAcceso,
                'fecha_hora_inicio'=> $fechaUltAcceso,
                'neg_id'           => $local['neg_id']
            ]);

            $fichxubiId = DB::insertId();

            // Setear ubicación actual
            DB::update('fich', [
                'fichxubi_actual' => $fichxubiId
            ], "fich_id=%i", $fichId);
        }

        DB::commit();
        echo poke();

    } catch (Exception $e) {
        DB::rollback();
        Flight::halt(500, json_encode(['error' => $e->getMessage()]));
    }
});
