<?php
/* ---------------------------------------------------------
 * 📁 MODULO: tab_usu (Gestión de Usuarios)
 * Orquestador principal de rutas y renderizado con Mustache
 * --------------------------------------------------------- */

Flight::route('GET /usu/inicio', function () {
    include DEFINITION;
    autentificar_administrador();    

    // 2. Generar los assets de la cabecera usando perso.class.php (igual que el ejemplo)
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

    // 3. Obtener HTML del menú administrativo
    ob_start();
    include VARPATH . "/public/admin/menu.php";
    $menu_html = ob_get_clean();

    // 4. Obtener HTML del pie de página
    ob_start();
    include VARPATH . "/public/admin/footer.php";
    $footer_html = ob_get_clean();

    // 5. Variables globales que se inyectarán en la plantilla principal
    $data = [
        'title'       => 'Gestión de Usuarios',
        'varhost'     => $varhost,
        'apphost'     => $apphost,
        'assets_head' => $assets_head,
        'menu_html'   => $menu_html,
        'footer_html' => $footer_html
    ];

    // 6. Cargar los partials desde la carpeta comp_pag_usu
    $basePath = VARPATH . '/public/admin/tab_usu/comp_pag_usu';

    $partials = [
        'head'           => file_get_contents($basePath . '/head.html'),
        'tabla_usuarios' => file_get_contents($basePath . '/tabla_usuarios.html'),
        'modal_crear'    => file_get_contents($basePath . '/modal_crear.html'),
        'modal_editar'   => file_get_contents($basePath . '/modal_editar.html'),
        'modal_detalle'  => file_get_contents($basePath . '/modal_detalle.html'),
        'modal_buscar'   => file_get_contents($basePath . '/modal_buscar.html'),
        'scripts'        => file_get_contents($basePath . '/scripts.html'),
    ];

    // 7. Renderizar utilizando el archivo layout pag_usu.html y la clase Mustache
    echo (new Mustache)->render(
        file_get_contents(VARPATH . '/public/admin/tab_usu/pag_usu.html'),
        $data,
        $partials
    );
});

// Incluir los subcontroladores modulares de la API REST de FlightPHP
include_once __DIR__ . '/api/control01.php';
include_once __DIR__ . '/api/control02.php';
include_once __DIR__ . '/api/control03.php';