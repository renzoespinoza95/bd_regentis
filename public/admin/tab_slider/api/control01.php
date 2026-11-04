<?php
// Backend parte 1: Vistas, Endpoints de Menús Tree, Usuario Info, Operaciones CRUD de Slider y Eliminación PHP en Bunny

Flight::route('GET /slider/inicio', function () {
    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;
    $apphost = $varhost;

    ob_start();
    perso::vuejs2();
    perso::vue_select();
    perso::start($version);
    perso::favicon();
    perso::font_awesome();
    perso::jquery2();
    perso::jquery_ui();
    perso::js();
    perso::apprise();
    perso::block_ui();
    perso::perso($version);
    perso::global_env($apphost, $varhost);
    perso::datatables();
    perso::summernote();
    $assets_head = ob_get_clean();

    ob_start();
    include VARPATH . "/public/admin/footer.php";
    $footer_html = ob_get_clean();

    $data = [
        'title'       => 'Administración - Sliders',
        'varhost'     => $varhost,
        'apphost'     => $apphost,
        'assets_head' => $assets_head,
        'footer_html' => $footer_html
    ];

    $basePath = VARPATH . '/public/admin/tab_slider/comp_slider';

    $partials = [
        'head'           => file_get_contents($basePath . '/head.html'),
        'navbar_sliders' => file_get_contents($basePath . '/navbar_sliders.html'),
        'menu_flot'      => file_get_contents(VARPATH . '/public/admin/menu_flot.html'),
        'modal_crear'    => file_get_contents($basePath . '/modal_crear.html'),
        'modal_editar'   => file_get_contents($basePath . '/modal_editar.html'),
        'modal_detalle'  => file_get_contents($basePath . '/modal_detalle.html'),
        'scripts'        => file_get_contents($basePath . '/scripts.html'),
    ];

    echo (new Mustache)->render(
        file_get_contents(VARPATH . '/public/admin/tab_slider/slider.html'),
        $data,
        $partials
    );
});

Flight::route('GET /menus/tree', function () {
    include DEFINITION;
    autentificar_administrador();

    global $administrador_actual;
    $rol_id = $administrador_actual['rol_id'] ?? 1;

    DB::query("SET NAMES 'utf8'");

    $menus = DB::query("
        SELECT m.menu_id, m.titulo, m.orden
        FROM reg_rolxmenu rxm
        INNER JOIN reg_menu m ON m.menu_id = rxm.menu_id
        WHERE rxm.rol_id = %i
        AND rxm.is_activo = 1
        AND m.borrado_el IS NULL
        ORDER BY m.orden ASC
    ", $rol_id);

    if (empty($menus)) {
        $menus = DB::query("SELECT menu_id, titulo, orden FROM reg_menu WHERE borrado_el IS NULL ORDER BY orden ASC");
    }

    foreach ($menus as &$menu) {
        $menu['lista_submenu'] = DB::query("
            SELECT submenu_id, titulo, url, target, orden
            FROM reg_submenu
            WHERE menu_id = %i
            AND borrado_el IS NULL
            ORDER BY orden ASC
        ", $menu['menu_id']);
    }

    Flight::json($menus);
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

Flight::route('POST /slider/eliminar-imagen', function () {
    include DEFINITION;
    autentificar_administrador();

    $d = json_decode(Flight::request()->getBody(), true);
    $url_imagen = $d['url_imagen'] ?? '';

    if (empty($url_imagen)) {
        Flight::json(['ok' => false, 'error' => 'url_imagen requerida']);
        return;
    }

    if (strpos($url_imagen, BUNNY_CDN_BASE) !== false) {
        $path_parts = parse_url($url_imagen, PHP_URL_PATH);
        $storage_url = BUNNY_STORAGE_URL . $path_parts;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $storage_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "AccessKey: " . BUNNY_STORAGE_ACCESSKEY,
            "Accept: application/json"
        ]);
        curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200 || $http_code == 204 || $http_code == 404) {
            Flight::json(['ok' => true]);
        } else {
            Flight::json(['ok' => false, 'error' => 'Error al eliminar en Bunny Storage (HTTP ' . $http_code . ')']);
        }
    } else {
        Flight::json(['ok' => false, 'error' => 'La URL no pertenece a Bunny CDN']);
    }
});

Flight::route('POST /slider/crear', function () {
    include DEFINITION;
    autentificar_administrador();

    $img            = $_POST['img'] ?? '';
    $orden          = $_POST['orden'] ?? 0;
    $is_visible     = $_POST['is_visible'] ?? 1;
    $fecha_fin      = $_POST['fecha_fin'] ?? null;
    $descripcion    = $_POST['descripcion'] ?? '';
    $neg_id         = $administrador_actual['neg_id'];

    if (empty($img)) {
        Flight::json(['success' => false, 'error' => 'URL de imagen requerida']);
        return;
    }

    DB::insert('reg_slider', [
        'img'            => $img,
        'orden'          => $orden,
        'is_visible'     => $is_visible,
        'fecha_fin'      => $fecha_fin,
        'descripcion'    => $descripcion,
        'neg_id'         => $neg_id
    ]);

    Flight::json([
        'success'   => true,
        'slider_id' => DB::insertId(),
        'img'       => $img
    ]);
});

Flight::route('POST /slider/editar', function () {
    include DEFINITION;
    autentificar_administrador();

    $slider_id        = $_POST['slider_id'] ?? null;
    $img              = $_POST['img'] ?? '';
    $orden            = $_POST['orden'] ?? 0;
    $is_visible       = $_POST['is_visible'] ?? 1;
    $fecha_fin        = $_POST['fecha_fin'] ?? null;
    $descripcion      = $_POST['descripcion'] ?? '';    
    $neg_id           = $administrador_actual['neg_id'];
    $imagen_eliminada = $_POST['imagen_eliminada'] ?? '0';

    if (!$slider_id) {
        Flight::json(['success' => false, 'error' => 'slider_id requerido']);
        return;
    }    

    $update = [
        'orden'       => $orden,
        'is_visible'  => $is_visible,
        'fecha_fin'   => $fecha_fin,
        'descripcion' => $descripcion
    ];

    if (!empty($img)) {
        $update['img'] = $img;
    } elseif ($imagen_eliminada === '1') {
        $update['img'] = '';
    }

    DB::update('reg_slider', $update, 'slider_id = %i AND neg_id = %i', $slider_id, $neg_id);
    Flight::json(['success' => true]);
});

Flight::route('POST /slider/eliminar', function () {
    $d = json_decode(Flight::request()->getBody(), true);
    $slider_id = $d['slider_id'] ?? null;

    if (!$slider_id) {
        Flight::json(['success' => false, 'error' => 'slider_id requerido']);
        return;
    }

    DB::delete('reg_slider', 'slider_id = %i', $slider_id);
    Flight::json(['success' => true]);
});