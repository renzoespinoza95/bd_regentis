<?php
/* =========================================================
   ENDPOINTS: reg_neg / reg_mercado / reg_negxusu / reg_usu
   Stack: PHP 8.1 + FlightPHP + MeekroDB2
========================================================= */

/* -------------------------------
   Helpers
------------------------------- */
function api_json($payload, $http_code = 200) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code($http_code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
}

function api_ok($data = null) {
    api_json(['status' => 'ok', 'data' => $data]);
}

function api_error($msg, $http_code = 400, $extra = []) {
    api_json(array_merge(['status' => 'error', 'msg' => $msg], $extra), $http_code);
}

function req_data() {
    return Flight::request()->data->getData();
}

function to_int($v, $default = 0) {
    if (!isset($v)) return $default;
    if ($v === '') return $default;
    return intval($v);
}

function to_str($v, $default = '') {
    if (!isset($v)) return $default;
    return trim((string)$v);
}

function db_utf8() {
    // utf8mb4 para acentos/emojis
    DB::query("SET NAMES 'utf8mb4'");
}

/* =========================================================
   VISTAS (si usas vistas tipo /admin/...)
========================================================= */

// GET /neg/inicio
Flight::route('GET /neg/inicio', function () {
    include DEFINITION;
    autentificar_administrador();    
    include $path_public . '/admin/tab_neg/inicio.php';
});

// GET /mercado/inicio
Flight::route('GET /mercado/inicio', function () {
    include DEFINITION;
    login_admin::autentificar_administrador();
    global $path_public;
    include $path_public . '/admin/tab_mercado/inicio.php';
});


/* =========================================================
   NEGOCIOS (reg_neg)
========================================================= */

// GET /neg/listar
Flight::route('GET /neg/listar', function() {
    try {
        db_utf8();

        // Devuelve lo que necesita el frontend:
        // neg_id, nombre, puesto, mercado_id, is_activo
        $rows = DB::query("
            SELECT
                n.neg_id,
                n.nombre,
                n.puesto,
                n.mercado_id,
                n.is_activo
            FROM reg_neg n
            ORDER BY n.neg_id DESC
        ");

        api_ok($rows);
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

// POST /neg/crear
Flight::route('POST /neg/crear', function() {
    try {
        db_utf8();
        $data = req_data();

        $nombre     = to_str($data['nombre'] ?? '');
        $puesto     = to_str($data['puesto'] ?? '');
        $mercado_id = to_int($data['mercado_id'] ?? 0);

        if ($nombre === '') api_error('Nombre requerido', 400);
        if ($puesto === '') api_error('Puesto requerido', 400);
        if ($mercado_id <= 0) api_error('Mercado requerido', 400);

        // Validar que mercado exista
        $m = DB::queryFirstRow("SELECT mercado_id FROM reg_mercado WHERE mercado_id=%i", $mercado_id);
        if (!$m) api_error('Mercado no existe', 400);

        // Insertar solo lo mínimo que pediste, el resto queda NULL/DEFAULT
        DB::insert('reg_neg', [
            'nombre'         => $nombre,
            'puesto'         => $puesto,
            'mercado_id'     => $mercado_id,
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'is_activo'      => 1,
            'cod_neg'        => '0',
        ]);

        api_ok(['neg_id' => DB::insertId()]);
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

// POST /neg/editar
Flight::route('POST /neg/editar', function() {
    try {
        db_utf8();
        $data = req_data();

        $neg_id     = to_int($data['neg_id'] ?? 0);
        $nombre     = to_str($data['nombre'] ?? '');
        $puesto     = to_str($data['puesto'] ?? '');
        $mercado_id = to_int($data['mercado_id'] ?? 0);

        if ($neg_id <= 0) api_error('neg_id inválido', 400);
        if ($nombre === '') api_error('Nombre requerido', 400);
        if ($puesto === '') api_error('Puesto requerido', 400);
        if ($mercado_id <= 0) api_error('Mercado requerido', 400);

        // Validar negocio
        $n = DB::queryFirstRow("SELECT neg_id FROM reg_neg WHERE neg_id=%i", $neg_id);
        if (!$n) api_error('Negocio no existe', 404);

        // Validar mercado
        $m = DB::queryFirstRow("SELECT mercado_id FROM reg_mercado WHERE mercado_id=%i", $mercado_id);
        if (!$m) api_error('Mercado no existe', 400);

        DB::update('reg_neg', [
            'nombre'     => $nombre,
            'puesto'     => $puesto,
            'mercado_id' => $mercado_id
        ], "neg_id=%i", $neg_id);

        api_ok();
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

// POST /neg/eliminar
Flight::route('POST /neg/eliminar', function() {
    try {
        db_utf8();
        $data = req_data();
        $neg_id = to_int($data['neg_id'] ?? 0);

        if ($neg_id <= 0) api_error('neg_id inválido', 400);

        // Si quieres evitar huérfanos en reg_negxusu, borramos primero asignaciones
        DB::startTransaction();
        try {
            DB::delete('reg_negxusu', "neg_id=%i", $neg_id);
            DB::delete('reg_neg', "neg_id=%i", $neg_id);
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            throw $e;
        }

        api_ok();
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});


/* =========================================================
   MERCADOS (reg_mercado)
========================================================= */

// GET /mercado/listar
Flight::route('GET /mercado/listar', function() {
    try {
        db_utf8();

        $rows = DB::query("
            SELECT
                mercado_id,
                nombre,
                direccion,
                is_activo
            FROM reg_mercado
            ORDER BY mercado_id DESC
        ");

        api_ok($rows);
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

// POST /mercado/crear
Flight::route('POST /mercado/crear', function() {
    try {
        db_utf8();
        $data = req_data();

        $nombre    = to_str($data['nombre'] ?? '');
        $direccion = to_str($data['direccion'] ?? '');
        $is_activo = to_int($data['is_activo'] ?? 1);

        if ($nombre === '') api_error('Nombre requerido', 400);
        if ($is_activo !== 0 && $is_activo !== 1) $is_activo = 1;

        DB::insert('reg_mercado', [
            'nombre'         => $nombre,
            'direccion'      => ($direccion === '' ? null : $direccion),
            'fecha_creacion' => date('Y-m-d H:i:s'),
            'is_activo'      => $is_activo
        ]);

        api_ok(['mercado_id' => DB::insertId()]);
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

// POST /mercado/editar
Flight::route('POST /mercado/editar', function() {
    try {
        db_utf8();
        $data = req_data();

        $mercado_id = to_int($data['mercado_id'] ?? 0);
        $nombre     = to_str($data['nombre'] ?? '');
        $direccion  = to_str($data['direccion'] ?? '');
        $is_activo  = to_int($data['is_activo'] ?? 1);

        if ($mercado_id <= 0) api_error('mercado_id inválido', 400);
        if ($nombre === '') api_error('Nombre requerido', 400);
        if ($is_activo !== 0 && $is_activo !== 1) $is_activo = 1;

        $m = DB::queryFirstRow("SELECT mercado_id FROM reg_mercado WHERE mercado_id=%i", $mercado_id);
        if (!$m) api_error('Mercado no existe', 404);

        DB::update('reg_mercado', [
            'nombre'    => $nombre,
            'direccion' => ($direccion === '' ? null : $direccion),
            'is_activo' => $is_activo
        ], "mercado_id=%i", $mercado_id);

        api_ok();
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

// POST /mercado/eliminar
Flight::route('POST /mercado/eliminar', function() {
    try {
        db_utf8();
        $data = req_data();
        $mercado_id = to_int($data['mercado_id'] ?? 0);

        if ($mercado_id <= 0) api_error('mercado_id inválido', 400);

        // Si hay negocios en ese mercado, normalmente deberías impedirlo.
        // Aquí lo intentamos y si falla devolvemos el mensaje.
        // Puedes cambiar esto por una validación previa si quieres.
        DB::delete('reg_mercado', "mercado_id=%i", $mercado_id);

        api_ok();
    } catch (Exception $e) {
        // Si hay restricción o integridad referencial, caerá aquí
        api_error($e->getMessage(), 500);
    }
});


/* =========================================================
   PROPIETARIO: reg_negxusu
========================================================= */

// GET /negxusu/obtener?neg_id=123
Flight::route('GET /negxusu/obtener', function() {
    try {
        db_utf8();
        $neg_id = to_int(Flight::request()->query['neg_id'] ?? 0);

        if ($neg_id <= 0) api_error('neg_id inválido', 400);

        // Si hay asignación, devolvemos el usuario asignado
        $row = DB::queryFirstRow("
            SELECT
                nx.negxusu_id,
                nx.neg_id,
                nx.usu_id,
                u.dni,
                u.nombres_apellidos,
                u.is_activo
            FROM reg_negxusu nx
            INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
            WHERE nx.neg_id = %i
              AND nx.is_activo = 1
            ORDER BY nx.negxusu_id DESC
            LIMIT 1
        ", $neg_id);

        api_ok($row ? $row : null);
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

// POST /negxusu/eliminar  { negxusu_id }
Flight::route('POST /negxusu/eliminar', function() {
    try {
        db_utf8();
        $data = req_data();
        $negxusu_id = to_int($data['negxusu_id'] ?? 0);

        if ($negxusu_id <= 0) api_error('negxusu_id inválido', 400);

        DB::delete('reg_negxusu', "negxusu_id=%i", $negxusu_id);

        api_ok();
    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});


/* =========================================================
   USUARIOS: buscar por DNI (y mostrar asignado si existe)
========================================================= */

// POST /usu/buscar-dni  { dni, neg_id }
// - Si el negocio YA tiene propietario asignado -> devuelve ese asignado (aunque el dni sea otro)
// - Si no hay asignación -> busca por dni en reg_usu y devuelve ese usuario (sin negxusu_id)
Flight::route('POST /usu/buscar-dni', function() {
    try {
        db_utf8();
        $data = req_data();

        $dni    = to_str($data['dni'] ?? '');
        $neg_id = to_int($data['neg_id'] ?? 0);

        if ($dni === '') api_error('DNI requerido', 400);
        if ($neg_id <= 0) api_error('neg_id inválido', 400);

        // 1) Si ya existe propietario asignado a ese negocio, devolverlo
        $asig = DB::queryFirstRow("
            SELECT
                nx.negxusu_id,
                nx.neg_id,
                nx.usu_id,
                u.dni,
                u.nombres_apellidos,
                u.is_activo
            FROM reg_negxusu nx
            INNER JOIN reg_usu u ON u.usu_id = nx.usu_id
            WHERE nx.neg_id = %i
              AND nx.is_activo = 1
            ORDER BY nx.negxusu_id DESC
            LIMIT 1
        ", $neg_id);

        if ($asig) {
            api_ok($asig);
            return;
        }

        // 2) Si no existe asignación, buscar por DNI
        $u = DB::queryFirstRow("
            SELECT
                usu_id,
                dni,
                nombres_apellidos,
                is_activo
            FROM reg_usu
            WHERE dni = %s
            LIMIT 1
        ", $dni);

        if (!$u) {
            api_ok(null);
            return;
        }

        // devolver sin negxusu_id (0)
        $u['negxusu_id'] = 0;
        api_ok($u);

    } catch (Exception $e) {
        api_error($e->getMessage(), 500);
    }
});

/* =========================================================
   ASIGNAR PROPIETARIO A NEGOCIO
========================================================= */

// POST /negxusu/asignar
// body: { neg_id, usu_id }

Flight::route('POST /negxusu/asignar', function() {

    try {

        DB::query("SET NAMES 'utf8mb4'");

        $data = Flight::request()->data->getData();

        $neg_id = isset($data['neg_id']) ? intval($data['neg_id']) : 0;
        $usu_id = isset($data['usu_id']) ? intval($data['usu_id']) : 0;

        if ($neg_id <= 0) {
            Flight::json([
                'status' => 'error',
                'msg' => 'neg_id inválido'
            ],400);
            return;
        }

        if ($usu_id <= 0) {
            Flight::json([
                'status' => 'error',
                'msg' => 'usu_id inválido'
            ],400);
            return;
        }

        /* ----------------------------------
           VALIDAR NEGOCIO
        ---------------------------------- */

        $neg = DB::queryFirstRow("
            SELECT neg_id
            FROM reg_neg
            WHERE neg_id=%i
        ", $neg_id);

        if (!$neg) {
            Flight::json([
                'status'=>'error',
                'msg'=>'Negocio no existe'
            ],404);
            return;
        }

        /* ----------------------------------
           VALIDAR USUARIO
        ---------------------------------- */

        $usu = DB::queryFirstRow("
            SELECT usu_id
            FROM reg_usu
            WHERE usu_id=%i
        ", $usu_id);

        if (!$usu) {
            Flight::json([
                'status'=>'error',
                'msg'=>'Usuario no existe'
            ],404);
            return;
        }

        /* ----------------------------------
           VALIDAR QUE NO EXISTA PROPIETARIO
        ---------------------------------- */

        $existe = DB::queryFirstRow("
            SELECT negxusu_id
            FROM reg_negxusu
            WHERE neg_id=%i
            AND is_activo=1
        ", $neg_id);

        if ($existe) {

            Flight::json([
                'status'=>'error',
                'msg'=>'Este negocio ya tiene propietario'
            ],400);

            return;
        }

        /* ----------------------------------
           INSERTAR
        ---------------------------------- */

        DB::startTransaction();

        try {

            DB::insert('reg_negxusu', [

                'usu_id' => $usu_id,
                'neg_id' => $neg_id,
                'is_activo' => 1,
                'fecha_creacion' => date('Y-m-d H:i:s')

            ]);

            DB::commit();

        } catch(Exception $e){

            DB::rollback();
            throw $e;

        }

        Flight::json([
            'status'=>'ok',
            'negxusu_id'=>DB::insertId()
        ]);

    }

    catch(Exception $e){

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ],500);

    }

});