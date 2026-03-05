<?php
/* -------------------------------
// este es mi backend usando php8.1, flightphp y meekrodb2 
 * ------------------------------- */
Flight::route('GET /menu', function () {

    include DEFINITION;
    autentificar_administrador();

    global $path_public;

    include $path_public . '/admin/tab_menu/inicio.php';
});


/* ================================
   LISTAR MENUS
================================ */
Flight::route('GET /menu/listar', function () {

    $rows = DB::query("
        SELECT 
            m.menu_id,
            m.titulo,
            m.orden,
            (
                SELECT GROUP_CONCAT(r.descripcion SEPARATOR ', ')
                FROM reg_rolxmenu rm
                INNER JOIN reg_rol r
                    ON r.rol_id = rm.rol_id
                WHERE rm.menu_id = m.menu_id
                AND rm.is_activo = 1
            ) AS roles,
            (
                SELECT COUNT(*) 
                FROM reg_submenu s 
                WHERE s.menu_id = m.menu_id
            ) AS total_submenus
        FROM reg_menu m
        ORDER BY m.orden ASC
    ");

    Flight::json($rows);
});


/* ================================
   CAMBIAR ORDEN MENU
================================ */
Flight::route('POST /menu/cambiar-orden', function () {

    $id    = intval(Flight::request()->data->menu_id);
    $delta = intval(Flight::request()->data->delta);

    DB::query("
        UPDATE reg_menu 
        SET orden = orden + %i 
        WHERE menu_id = %i
    ", $delta, $id);

    Flight::json(['status'=>'ok']);
});


/* ================================
   GUARDAR MENU
================================ */
Flight::route('POST /menu/guardar', function () {

    $d = Flight::request()->data->getData();

    if (empty($d['menu_id'])) {

        DB::insert('reg_menu', [
            'titulo' => $d['titulo'],
            'orden'  => intval($d['orden'])
        ]);

    } else {

        DB::update('reg_menu', [
            'titulo' => $d['titulo']
        ], "menu_id=%i", intval($d['menu_id']));
    }

    Flight::json(['status'=>'ok']);
});


/* ================================
   ELIMINAR MENU
================================ */
Flight::route('POST /menu/eliminar', function () {

    $id = intval(Flight::request()->data->menu_id);

    DB::delete('reg_submenu', "menu_id=%i", $id);
    DB::delete('reg_menu', "menu_id=%i", $id);

    Flight::json(['status'=>'ok']);
});


/* ================================
   LISTAR SUBMENUS
================================ */
Flight::route('GET /submenu/listar/@menu_id', function ($menu_id) {

    $rows = DB::query("
        SELECT * 
        FROM reg_submenu 
        WHERE menu_id=%i 
        ORDER BY orden
    ", $menu_id);

    Flight::json($rows);
});


/* ================================
   API MENUS POR ROL
================================ */
Flight::group('/api', function() {

  Flight::route('GET /menus/por-rol/@rol:[0-9]+', function($rol) {

    try {

      $rows = DB::query(
        "SELECT
           m.menu_id,
           m.titulo   AS menu_titulo,
           m.orden    AS menu_orden,
           s.submenu_id,
           s.titulo   AS submenu_titulo,
           s.url,
           s.target,
           s.orden    AS submenu_orden
         FROM reg_rolxmenu rm
         INNER JOIN reg_menu m ON m.menu_id = rm.menu_id
         LEFT JOIN reg_submenu s ON s.menu_id = m.menu_id
         WHERE rm.rol_id = %i
           AND rm.is_activo = 1
         ORDER BY m.orden, s.orden, s.submenu_id",
         $rol
      );

      $byMenu = [];

      foreach ($rows as $r) {

        $mid = (int)$r['menu_id'];

        if (!isset($byMenu[$mid])) {
          $byMenu[$mid] = [
            'menu_id' => $mid,
            'titulo'  => $r['menu_titulo'],
            'orden'   => (int)$r['menu_orden'],
            'lista_submenu' => []
          ];
        }

        if (!empty($r['submenu_id'])) {
          $byMenu[$mid]['lista_submenu'][] = [
            'submenu_id' => (int)$r['submenu_id'],
            'titulo'     => $r['submenu_titulo'],
            'url'        => $r['url'],
            'target'     => $r['target'],
            'orden'      => (int)$r['submenu_orden']
          ];
        }
      }

      Flight::json([
        'ok' => true,
        'menus' => array_values($byMenu)
      ]);

    } catch (Throwable $e) {

      Flight::json([
        'ok' => false,
        'error' => $e->getMessage()
      ], 500);
    }
  });

});


/* ================================
   FUNCION MENUS POR ROL
================================ */
function lista_menu_con_submenus_por_rol_id($rol_id)
{
    $res = DB::query("
        SELECT 
            m.menu_id,
            m.titulo,
            m.orden,
            s.submenu_id,
            s.titulo AS submenu_titulo,
            s.url,
            s.orden AS submenu_orden,
            s.target
        FROM reg_rolxmenu rm
        INNER JOIN reg_menu m
            ON m.menu_id = rm.menu_id
        LEFT JOIN reg_submenu s
            ON m.menu_id = s.menu_id
        WHERE rm.rol_id = %i
        AND rm.is_activo = 1
        ORDER BY m.orden, s.orden
    ", $rol_id);

    $menus = [];

    foreach ($res as $row) {

        $menu_id = $row['menu_id'];

        if (!isset($menus[$menu_id])) {
            $menus[$menu_id] = [
                'menu_id' => $row['menu_id'],
                'titulo' => $row['titulo'],
                'orden' => $row['orden'],
                'lista_submenu' => []
            ];
        }

        if (!empty($row['submenu_id'])) {
            $menus[$menu_id]['lista_submenu'][] = [
                'submenu_id' => $row['submenu_id'],
                'titulo' => $row['submenu_titulo'],
                'url' => $row['url'],
                'orden' => $row['submenu_orden'],
                'target' => $row['target']
            ];
        }
    }

    return array_values($menus);
}


/* ================================
   ACTUALIZAR ORDEN MENUS
================================ */
Flight::route('POST /menu/actualizar-orden', function () {

    $data = Flight::request()->data->getData();

    if (empty($data['orden']) || !is_array($data['orden'])) {
        Flight::json(['status'=>'error','msg'=>'Orden inválido'], 400);
        return;
    }

    DB::startTransaction();

    try {

        foreach ($data['orden'] as $row) {

            DB::update(
                'reg_menu',
                ['orden' => intval($row['orden'])],
                "menu_id=%i",
                intval($row['menu_id'])
            );
        }

        DB::commit();

        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ], 500);
    }
});


/* ================================
   ACTUALIZAR ORDEN SUBMENUS
================================ */
Flight::route('POST /submenu/actualizar-orden', function () {

    $data = Flight::request()->data->getData();

    if (empty($data['orden']) || !is_array($data['orden'])) {
        Flight::json(['status'=>'error','msg'=>'Orden inválido'], 400);
        return;
    }

    DB::startTransaction();

    try {

        foreach ($data['orden'] as $row) {

            DB::update(
                'reg_submenu',
                ['orden' => intval($row['orden'])],
                "submenu_id=%i",
                intval($row['submenu_id'])
            );
        }

        DB::commit();

        Flight::json(['status'=>'ok']);

    } catch (Exception $e) {

        DB::rollback();

        Flight::json([
            'status'=>'error',
            'msg'=>$e->getMessage()
        ], 500);
    }
});


/* ================================
   GUARDAR SUBMENU
================================ */
Flight::route('POST /submenu/guardar', function () {

    $data = Flight::request()->data->getData();

    if (empty($data['menu_id']) || empty($data['titulo'])) {
        Flight::json(['status'=>'error','msg'=>'Datos incompletos'], 400);
        return;
    }

    if (!empty($data['submenu_id'])) {

        DB::update('reg_submenu', [
            'titulo' => $data['titulo'],
            'url'    => $data['url'],
            'orden'  => intval($data['orden']),
            'target' => $data['target']
        ], "submenu_id=%i", intval($data['submenu_id']));

    } else {

        DB::insert('reg_submenu', [
            'menu_id' => intval($data['menu_id']),
            'titulo'  => $data['titulo'],
            'url'     => $data['url'],
            'orden'   => intval($data['orden']),
            'target'  => $data['target']
        ]);
    }

    Flight::json(['status'=>'ok']);
});


/* ================================
   ELIMINAR SUBMENU
================================ */
Flight::route('POST /submenu/eliminar', function () {

    $data = Flight::request()->data->getData();

    DB::delete(
        'reg_submenu',
        "submenu_id=%i",
        intval($data['submenu_id'])
    );

    Flight::json(['status'=>'ok']);
});


/* ================================
   LISTAR ROLES
================================ */
Flight::route('GET /rol/listar', function () {

    $rows = DB::query("
        SELECT *
        FROM reg_rol
        ORDER BY rol_id
    ");

    Flight::json($rows);
});


/* ================================
   GUARDAR ROL
================================ */
Flight::route('POST /rol/guardar', function () {

    $d = Flight::request()->data->getData();

    if(empty($d['rol_id'])){

        DB::insert('reg_rol',[
            'descripcion'=>$d['descripcion'],
            'is_activo'=>1
        ]);

    } else {

        DB::update('reg_rol',[
            'descripcion'=>$d['descripcion']
        ],"rol_id=%i",$d['rol_id']);
    }

    Flight::json(['status'=>'ok']);
});


/* ================================
   ELIMINAR ROL
================================ */
Flight::route('POST /rol/eliminar', function () {

    $id = intval(Flight::request()->data->rol_id);

    DB::delete('reg_rolxmenu',"rol_id=%i",$id);
    DB::delete('reg_rol',"rol_id=%i",$id);

    Flight::json(['status'=>'ok']);
});


/* ================================
   GUARDAR MENUS POR ROL
================================ */
Flight::route('POST /rol/guardar-menus', function(){

  $data = Flight::request()->data->getData();

  $rol = intval($data['rol_id']);
  $menus = $data['menus'] ?? [];

  DB::startTransaction();

  DB::delete('reg_rolxmenu',"rol_id=%i", $rol);

  foreach($menus as $menu_id){

    DB::insert('reg_rolxmenu',[
      'rol_id'=>$rol,
      'menu_id'=>intval($menu_id),
      'is_activo'=>1
    ]);
  }

  DB::commit();

  Flight::json(['status'=>'ok']);
});


/* ================================
   MENUS DE UN ROL
================================ */
Flight::route('GET /rol/menus/@rol:[0-9]+', function($rol){

    try {

        $rows = DB::query("
            SELECT menu_id
            FROM reg_rolxmenu
            WHERE rol_id = %i
            AND is_activo = 1
            ORDER BY menu_id
        ", $rol);

        Flight::json($rows);

    } catch (Throwable $e) {

        Flight::json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }

});
