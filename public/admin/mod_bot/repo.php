<?php
/*
PYTHON
Invoke-RestMethod -Method POST "http://localhost:5000/tk_cant_msg" `
    -Headers @{ "Content-Type" = "application/json" } `
    -Body '{ "usu_id": 8 }'

*/

Flight::route('POST /etiquetas/guardar', function () {
    $body = Flight::request()->getBody();
    $etiquetas = json_decode($body, true);

    if (!is_array($etiquetas)) {
        Flight::json(['success' => false, 'error' => 'Formato JSON inválido'], 400);
        return;
    }

    $insertadas = [];
    foreach ($etiquetas as $item) {
        if (!isset($item['etiqueta']) || trim($item['etiqueta']) === '') {
            continue;
        }

        $descripcion = trim($item['etiqueta']);

        // Evitar duplicados exactos
        $existe = DB::queryFirstField(
            "SELECT 1 FROM pt_etiquetas WHERE descripcion = %s",
            $descripcion
        );

        if (!$existe) {
            DB::insert('pt_etiquetas', [
                'descripcion' => $descripcion
            ]);
            $insertadas[] = $descripcion;
        }
    }

    Flight::json([
        'success' => true,
        'insertadas' => $insertadas,
        'total' => count($insertadas)
    ]);
});

Flight::route('POST /render/avisos', function(){
    // Consulta todos los registros de fichxubi
    $todos = DB::query("SELECT * FROM `fichxubi`");
    // Devuelve el resultado como JSON
    Flight::json($todos);
});

Flight::route('POST /render/chat_priv', function(){
    // Obtenemos todos los chats
    $chats = DB::query("SELECT * FROM `chat`");
    // Devolvemos JSON con unicode sin escapar para mantener ñ, á, etc.
    Flight::json($chats, 200, JSON_UNESCAPED_UNICODE);
});


/*
Flight::route('POST /render/fich/fichaFichera', function() {
    include DEFINITION;

    $req   = Flight::request()->data->getData();
    // dd($req);
    $fich_id   = $req['fich_id'];
    $uid   = $req['usu_id'];
    $zz    = $req['zz'];
    // dd($zz);
    // $zz_tu    = util::tu_barsi($req['zz']);
    // dd($zz_tu);

    // if(util::validar_token($zz_tu) == 0) {
    //     echo "WS No autorizado";
    //     exit;
    // }

    // asegurarnos de que termine con “/”
    $url_img = rtrim(vari('FOTO_FICH_MAX'), '/') . '/';

    $id = intval($fich_id);

    $sql = "
      SELECT
        u.usu_id,
        f.fich_id,
        u.sobrenombre,
        f.descripcion,
        -- fotos con URL completa
        GROUP_CONCAT(
          DISTINCT CONCAT('{$url_img}', '/', f.usu_id , '/', ff.img)
          ORDER BY ff.fecha_creacion DESC
          SEPARATOR '; '
        ) AS fotos,
        -- servicios
        GROUP_CONCAT(
          DISTINCT s.descripcion
          ORDER BY s.descripcion
          SEPARATOR '; '
        ) AS servicios,
        -- resto de subconsultas...
        COALESCE(
          ( SELECT CAST(fx.fichxubi_id AS CHAR) FROM fichxubi fx
            WHERE fx.fich_id = f.fich_id
              AND DATE(CONVERT_TZ(fx.fecha_creacion,'+00:00','-05:00')) = CURDATE()
            ORDER BY fx.fecha_creacion DESC LIMIT 1
          ), 'ESTOY DESCANSANDO'
        ) AS ultimo_fichxubi_id,
        COALESCE(
          ( SELECT fx2.nombre_local FROM fichxubi fx2
            WHERE fx2.fich_id = f.fich_id
              AND DATE(CONVERT_TZ(fx2.fecha_creacion,'+00:00','-05:00')) = CURDATE()
            ORDER BY fx2.fecha_creacion DESC LIMIT 1
          ), 'ESTOY DESCANSANDO'
        ) AS nombre_local,
        COALESCE(
          ( SELECT fx3.map_lat FROM fichxubi fx3
            WHERE fx3.fich_id = f.fich_id
              AND DATE(CONVERT_TZ(fx3.fecha_creacion,'+00:00','-05:00')) = CURDATE()
            ORDER BY fx3.fecha_creacion DESC LIMIT 1
          ), ''
        ) AS map_lat,
        COALESCE(
          ( SELECT fx4.map_lng FROM fichxubi fx4
            WHERE fx4.fich_id = f.fich_id
              AND DATE(CONVERT_TZ(fx4.fecha_creacion,'+00:00','-05:00')) = CURDATE()
            ORDER BY fx4.fecha_creacion DESC LIMIT 1
          ), ''
        ) AS map_lng,
        COALESCE(
          ( SELECT fx5.provincia FROM fichxubi fx5
            WHERE fx5.fich_id = f.fich_id
              AND DATE(CONVERT_TZ(fx5.fecha_creacion,'+00:00','-05:00')) = CURDATE()
            ORDER BY fx5.fecha_creacion DESC LIMIT 1
          ), ''
        ) AS provincia,
        COALESCE(
          ( SELECT fx6.ciudad FROM fichxubi fx6
            WHERE fx6.fich_id = f.fich_id
              AND DATE(CONVERT_TZ(fx6.fecha_creacion,'+00:00','-05:00')) = CURDATE()
            ORDER BY fx6.fecha_creacion DESC LIMIT 1
          ), ''
        ) AS ciudad
      FROM fich f
      LEFT JOIN usu u       ON u.usu_id    = f.usu_id
      LEFT JOIN fotoxfich ff ON ff.fich_id  = f.fich_id
      LEFT JOIN fichxserv fs ON fs.fich_id  = f.fich_id
      LEFT JOIN serv s      ON s.serv_id   = fs.serv_id
      WHERE f.fich_id = %i
      GROUP BY f.fich_id, u.usu_id, u.sobrenombre, f.descripcion
    ";

    $row = DB::queryFirstRow($sql, $id);

    if (!$row) {
        Flight::halt(404, json_encode([
            'error' => "Ficha #{$id} no encontrada"
        ]));
        return;
    }

    Flight::json($row);
});
*/

