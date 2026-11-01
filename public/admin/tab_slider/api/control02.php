<?php
// Backend parte 2: Detalle, Ordenamiento, Actualización de descripción y Creación automática

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
            grupo,
            neg_id
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
            ], 'slider_id = %i', $item['slider_id']);
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

    $slider_id   = $_POST['slider_id'] ?? null;
    $descripcion = $_POST['descripcion'] ?? '';
    $neg_id      = $administrador_actual['neg_id'];

    if (!$slider_id) {
        Flight::json(['success' => false, 'error' => 'slider_id requerido']);
        return;
    }

    try {
        DB::query("
            UPDATE reg_slider
            SET descripcion = %s
            WHERE slider_id = %i AND neg_id = %i
        ", $descripcion, $slider_id, $neg_id);

        Flight::json(['success' => true]);
    } catch (Exception $e) {
        Flight::json(['success' => false, 'error' => $e->getMessage()]);
    }
});

Flight::route('POST /GcVL/slider/automatico', function () {
    include DEFINITION;
    autentificar_administrador();

    try {
        $neg_id = $administrador_actual['neg_id'];
        DB::query("SET NAMES 'utf8mb4'");

        $img = 'https://barsi-img.b-cdn.net/recursos/71ye.png';

        $ultimoOrden = intval(
            DB::queryFirstField("
                SELECT IFNULL(MAX(orden),0)
                FROM reg_slider
                WHERE neg_id = %i
            ", $neg_id)
        );

        DB::insert('reg_slider', [
            'img'            => $img,
            'orden'          => $ultimoOrden + 1,
            'is_visible'     => 1,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'fecha_fin'      => date('Y-m-d H:i:s', strtotime('+30 days')),
            'descripcion'    => '',
            'grupo'          => 'A',
            'neg_id'         => $neg_id
        ]);

        Flight::json([
            'success'   => true,
            'slider_id' => DB::insertId(),
            'img'       => $img
        ]);
    } catch(Exception $e) {
        Flight::json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});