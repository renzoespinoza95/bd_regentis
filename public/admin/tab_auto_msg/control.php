<?php
// routes/auto_msg.php

// INICIO (incluye la vista)
Flight::route('GET /auto_msg/inicio', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;
    include $path_public . '/admin/tab_auto_msg/inicio.php';
});

// LISTAR
Flight::route('GET /auto_msg/listar', function () {
    DB::query("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    $rows = DB::query("SELECT auto_msg_id, clave_txt, texto_msg FROM auto_msg ORDER BY auto_msg_id DESC");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});

// CREAR
Flight::route('POST /auto_msg/crear', function () {
    $data = Flight::request()->data->getData();
    $clave_txt = trim((string)($data['clave_txt'] ?? ''));
    $texto_msg = (string)($data['texto_msg'] ?? '');

    if ($clave_txt === '' || $texto_msg === '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','msg'=>'Datos incompletos'], JSON_UNESCAPED_UNICODE);
        return;
    }

    DB::insert('auto_msg', [
        'clave_txt' => $clave_txt,
        'texto_msg' => $texto_msg
    ]);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'ok'], JSON_UNESCAPED_UNICODE);
});

// EDITAR
Flight::route('POST /auto_msg/editar', function () {
    $data = Flight::request()->data->getData();

    $auto_msg_id = intval($data['auto_msg_id'] ?? 0);
    $clave_txt   = trim((string)($data['clave_txt'] ?? ''));
    $texto_msg   = (string)($data['texto_msg'] ?? '');

    if ($auto_msg_id <= 0 || $clave_txt === '' || $texto_msg === '') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','msg'=>'Datos inválidos'], JSON_UNESCAPED_UNICODE);
        return;
    }

    DB::update('auto_msg', [
        'clave_txt' => $clave_txt,
        'texto_msg' => $texto_msg
    ], "auto_msg_id=%i", $auto_msg_id);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'ok'], JSON_UNESCAPED_UNICODE);
});

// ELIMINAR
Flight::route('POST /auto_msg/eliminar', function () {
    $data = Flight::request()->data->getData();
    $auto_msg_id = intval($data['auto_msg_id'] ?? 0);

    if ($auto_msg_id <= 0) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status'=>'error','msg'=>'ID inválido'], JSON_UNESCAPED_UNICODE);
        return;
    }

    DB::delete('auto_msg', "auto_msg_id=%i", $auto_msg_id);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status'=>'ok'], JSON_UNESCAPED_UNICODE);
});
