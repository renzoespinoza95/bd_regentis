<?php

/* ===============================
   INICIO (VISTA)
================================ */
Flight::route('GET /deli-trabajador/inicio', function () {
    include DEFINITION;
    autentificar_administrador();

    require_once VARPATH . '/public/admin/tab_tipoxmod/inicio.php';
});

/* ===============================
   COMBOS
================================ */

/* GET /reg-neg/listar  (para vue-select negocio) */
Flight::route('GET /reg-neg/listar', function() {
    DB::query("SET NAMES 'utf8'");
    // Ajusta el campo nombre según tu tabla reg_neg
    $rows = DB::query("SELECT neg_id, nombre FROM reg_neg ORDER BY nombre ASC");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});

/* GET /deli-tipo-trabajador/listar (para vue-select tipos) */
Flight::route('GET /deli-tipo-trabajador/listar', function() {
    DB::query("SET NAMES 'utf8'");
    $rows = DB::query("
        SELECT deli_tipo_trabajador_id, nombre
        FROM deli_tipo_trabajador
        ORDER BY nombre ASC
    ");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});



/* GET /reg-modulo/listar (para multiselect de módulos) */
Flight::route('GET /reg-modulo/listar', function() {
    DB::query("SET NAMES 'utf8'");
    $rows = DB::query("
        SELECT modulo_id, nombre, descripcion, is_activo
        FROM reg_modulo
        ORDER BY modulo_id ASC
    ");
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});

/* GET /reg-usu/buscar-dni?dni=... */
Flight::route('GET /reg-usu/buscar-dni', function() {
    DB::query("SET NAMES 'utf8'");
    $dni = isset($_GET['dni']) ? trim($_GET['dni']) : '';

    if ($dni === '') {
        Flight::json(['status'=>'error','msg'=>'DNI requerido'], 400);
        return;
    }

    // Ajusta campos según tu tabla reg_usu
    $row = DB::queryFirstRow("
        SELECT usu_id, dni, nombres_apellidos, celular
        FROM reg_usu
        WHERE dni = %s
        LIMIT 1
    ", $dni);

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($row ? $row : (object)[], JSON_UNESCAPED_UNICODE);
});

/* ===============================
   TRABAJADOR (LISTAR / CREAR / DESACTIVAR / ELIMINAR)
================================ */

/* GET /deli-trabajador/listar */
Flight::route('GET /deli-trabajador/listar', function() {
    DB::query("SET NAMES 'utf8'");

    $rows = DB::query("
            SELECT 
              dt.deli_trabajador_id,
              dt.neg_id,
              rn.nombre AS neg_nombre,
              dt.usu_id,
              ru.nombres_apellidos AS usu_nombre,
              ru.dni,
              dt.telefono,
              dt.is_activo,

              GROUP_CONCAT(DISTINCT dtt.nombre SEPARATOR ', ') AS tipos_trabajador,

              GROUP_CONCAT(DISTINCT rm.nombre SEPARATOR ', ') AS modulos_acceso

            FROM deli_trabajador dt

            LEFT JOIN reg_neg rn ON rn.neg_id = dt.neg_id
            LEFT JOIN reg_usu ru ON ru.usu_id = dt.usu_id

            LEFT JOIN deli_trabajadorxtipo dxt 
              ON dxt.deli_trabajador_id = dt.deli_trabajador_id

            LEFT JOIN deli_tipo_trabajador dtt 
              ON dtt.deli_tipo_trabajador_id = dxt.deli_tipo_trabajador_id

            LEFT JOIN deli_tipo_trabajador_modulo dttm 
              ON dttm.deli_tipo_trabajador_id = dxt.deli_tipo_trabajador_id

            LEFT JOIN reg_modulo rm 
              ON rm.modulo_id = dttm.modulo_id

            GROUP BY dt.deli_trabajador_id
            ORDER BY dt.deli_trabajador_id DESC
            ");

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});

Flight::route('POST /deli-trabajador/crear', function() {

    $data = Flight::request()->data->getData();

    /* =========================================================
       1) NORMALIZAR DATOS (soporta objetos o enteros)
    ========================================================== */

    $usu_id = intval($data['usu_id'] ?? 0);

    // neg_id puede venir como entero o como objeto {neg_id:1, nombre:...}
    $neg_id = 0;
    if (isset($data['neg_id'])) {
        if (is_array($data['neg_id'])) {
            $neg_id = intval($data['neg_id']['neg_id'] ?? 0);
        } else {
            $neg_id = intval($data['neg_id']);
        }
    }

    // tipos puede venir como [4,5] o [{deli_tipo_trabajador_id:4,...}]
    $tipos = [];
    if (isset($data['tipos']) && is_array($data['tipos'])) {
        foreach ($data['tipos'] as $t) {
            if (is_array($t)) {
                $tipos[] = intval($t['deli_tipo_trabajador_id'] ?? 0);
            } else {
                $tipos[] = intval($t);
            }
        }
    }

    /* =========================================================
       2) VALIDACIONES
    ========================================================== */

    if ($usu_id <= 0) {
        Flight::json(['status'=>'error','msg'=>'Usuario inválido'], 400);
        return;
    }

    if ($neg_id <= 0) {
        Flight::json(['status'=>'error','msg'=>'Negocio inválido'], 400);
        return;
    }

    if (empty($tipos)) {
        Flight::json(['status'=>'error','msg'=>'Debe seleccionar al menos un tipo de trabajador'], 400);
        return;
    }

    DB::query("SET NAMES 'utf8'");

    /* =========================================================
       3) VALIDAR USUARIO
    ========================================================== */

    $u = DB::queryFirstRow("
        SELECT usu_id, nombres_apellidos, celular, dni
        FROM reg_usu
        WHERE usu_id = %i
        LIMIT 1
    ", $usu_id);

    if (!$u) {
        Flight::json(['status'=>'error','msg'=>'Usuario no existe'], 404);
        return;
    }

    /* =========================================================
       4) VALIDAR NEGOCIO
    ========================================================== */

    $neg = DB::queryFirstRow("
        SELECT neg_id
        FROM reg_neg
        WHERE neg_id = %i
        LIMIT 1
    ", $neg_id);

    if (!$neg) {
        Flight::json(['status'=>'error','msg'=>'Negocio no existe'], 404);
        return;
    }

    /* =========================================================
       5) EVITAR DUPLICADO (mismo usu_id en mismo negocio)
    ========================================================== */

    $exists = DB::queryFirstField("
        SELECT deli_trabajador_id
        FROM deli_trabajador
        WHERE usu_id = %i
        AND neg_id = %i
        LIMIT 1
    ", $usu_id, $neg_id);

    if ($exists) {
        Flight::json(['status'=>'error','msg'=>'Este usuario ya es trabajador en ese negocio'], 400);
        return;
    }

    /* =========================================================
       6) INSERTAR CON TRANSACCIÓN
    ========================================================== */

    DB::startTransaction();

    try {

        // Insertar trabajador
        DB::insert('deli_trabajador', [
            'neg_id'    => $neg_id,
            'usu_id'    => $usu_id,
            'nombre'    => $u['nombres_apellidos'],
            'telefono'  => $u['celular'],
            'is_activo' => 1
        ]);

        $deli_trabajador_id = DB::insertId();

        // Insertar tipos
        foreach ($tipos as $tipo_id) {

            if ($tipo_id <= 0) continue;

            DB::query("
                INSERT INTO deli_trabajadorxtipo
                    (deli_trabajador_id, deli_tipo_trabajador_id)
                VALUES (%i, %i)
                ON DUPLICATE KEY UPDATE fecha_creacion = fecha_creacion
            ", $deli_trabajador_id, $tipo_id);
        }

        DB::commit();

        Flight::json([
            'status' => 'ok',
            'deli_trabajador_id' => $deli_trabajador_id
        ]);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status' => 'error',
            'msg' => $e->getMessage()
        ], 500);
    }

});

/* POST /deli-trabajador/desactivar { deli_trabajador_id } */
Flight::route('POST /deli-trabajador/desactivar', function() {
    $data = Flight::request()->data->getData();
    $id = isset($data['deli_trabajador_id']) ? intval($data['deli_trabajador_id']) : 0;

    if ($id <= 0) {
        Flight::json(['status'=>'error','msg'=>'ID inválido'], 400);
        return;
    }

    DB::update('deli_trabajador', ['is_activo' => 0], "deli_trabajador_id=%i", $id);
    Flight::json(['status'=>'ok']);
});

/* POST /deli-trabajador/eliminar { deli_trabajador_id } */
Flight::route('POST /deli-trabajador/eliminar', function() {
    $data = Flight::request()->data->getData();
    $id = isset($data['deli_trabajador_id']) ? intval($data['deli_trabajador_id']) : 0;

    if ($id <= 0) {
        Flight::json(['status'=>'error','msg'=>'ID inválido'], 400);
        return;
    }

    DB::startTransaction();
    try {
        // si NO tienes FK con cascade en deli_trabajadorxtipo, lo borramos manual:
        DB::delete('deli_trabajadorxtipo', "deli_trabajador_id=%i", $id);

        DB::delete('deli_trabajador', "deli_trabajador_id=%i", $id);
        DB::commit();
        Flight::json(['status'=>'ok']);
    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});

/* ===============================
   TIPO TRABAJADOR (CREAR)
================================ */

/* POST /deli-tipo-trabajador/crear { nombre } */
Flight::route('POST /deli-tipo-trabajador/crear', function() {
    DB::query("SET NAMES 'utf8'");
    $data = Flight::request()->data->getData();
    $nombre = isset($data['nombre']) ? trim($data['nombre']) : '';

    if ($nombre === '') {
        Flight::json(['status'=>'error','msg'=>'Nombre requerido'], 400);
        return;
    }

    DB::insert('deli_tipo_trabajador', ['nombre' => $nombre]);
    Flight::json(['status'=>'ok','deli_tipo_trabajador_id'=>DB::insertId()]);
});

/* ===============================
   TIPO ↔ MÓDULO (LISTAR / CREAR MULTI / ELIMINAR)
================================ */

/* GET /deli-tipo-trabajador-modulo/listar */
Flight::route('GET /deli-tipo-trabajador-modulo/listar', function() {
    DB::query("SET NAMES 'utf8'");

    $rows = DB::query("
        SELECT
            x.deli_tipo_trabajador_modulo_id,
            x.deli_tipo_trabajador_id,
            t.nombre AS tipo_nombre,
            x.modulo_id,
            m.nombre AS modulo_nombre,
            x.fecha_creacion
        FROM deli_tipo_trabajador_modulo x
        INNER JOIN deli_tipo_trabajador t ON t.deli_tipo_trabajador_id = x.deli_tipo_trabajador_id
        INNER JOIN reg_modulo m ON m.modulo_id = x.modulo_id
        ORDER BY x.deli_tipo_trabajador_modulo_id DESC
    ");

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
});

/* POST /deli-tipo-trabajador-modulo/crear-multi
   payload: { deli_tipo_trabajador_id, modulos:[1,2,3] }
*/
Flight::route('POST /deli-tipo-trabajador-modulo/crear-multi', function() {

    $data = Flight::request()->data->getData();

    // 🔥 Soporta objeto o entero
    $tipo_id = 0;
    if (isset($data['deli_tipo_trabajador_id'])) {
        if (is_array($data['deli_tipo_trabajador_id'])) {
            $tipo_id = intval($data['deli_tipo_trabajador_id']['deli_tipo_trabajador_id'] ?? 0);
        } else {
            $tipo_id = intval($data['deli_tipo_trabajador_id']);
        }
    }

    $modulos = isset($data['modulos']) && is_array($data['modulos']) ? $data['modulos'] : [];

    if ($tipo_id <= 0 || empty($modulos)) {
        Flight::json(['status'=>'error','msg'=>'Datos inválidos'], 400);
        return;
    }

    DB::startTransaction();
    try {

        foreach ($modulos as $mod) {

            // 🔥 Soporta objeto o entero
            $mod_id = 0;
            if (is_array($mod)) {
                $mod_id = intval($mod['modulo_id'] ?? 0);
            } else {
                $mod_id = intval($mod);
            }

            if ($mod_id <= 0) continue;

            DB::query("
                INSERT INTO deli_tipo_trabajador_modulo (deli_tipo_trabajador_id, modulo_id)
                VALUES (%i, %i)
                ON DUPLICATE KEY UPDATE fecha_creacion = fecha_creacion
            ", $tipo_id, $mod_id);
        }

        DB::commit();
        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {
        DB::rollback();
        Flight::json(['status'=>'error','msg'=>$e->getMessage()], 500);
    }
});

/* POST /deli-tipo-trabajador-modulo/eliminar { deli_tipo_trabajador_modulo_id } */
Flight::route('POST /deli-tipo-trabajador-modulo/eliminar', function() {

    $data = Flight::request()->data->getData();
    $id = isset($data['deli_tipo_trabajador_modulo_id']) ? intval($data['deli_tipo_trabajador_modulo_id']) : 0;

    if ($id <= 0) {
        Flight::json(['status'=>'error','msg'=>'ID inválido'], 400);
        return;
    }

    DB::delete('deli_tipo_trabajador_modulo', "deli_tipo_trabajador_modulo_id=%i", $id);
    Flight::json(['status'=>'ok']);
});