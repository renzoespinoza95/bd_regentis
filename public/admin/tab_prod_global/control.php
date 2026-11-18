<?php
Flight::route('GET /prod_global', function () {
    include DEFINITION;
    $usu_id = autentificar_administrador();

    // 1. Agrupar todas las llamadas a perso en $assets_head
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
    $assets_head = ob_get_clean();

    // 2. Agrupar el footer en $footer_html
    ob_start();
    include VARPATH . "/public/admin/footer.php";
    $footer_html = ob_get_clean();

    // 3. Variables para la vista de Mustache
    $data = [
        'title'        => 'Productos PlazaVea - Regentis',
        'varhost'      => $varhost,
        'apphost'      => $apphost,
        'assets_head'  => $assets_head,
        'footer_html'  => $footer_html
    ];

    // 4. Directorio de componentes parciales
    $basePath = __DIR__ . '/comp_prod_global';

    $partials = [
        'navbar_prod'           => file_get_contents($basePath . '/navbar_prod.html'),
        'modal_prod'            => file_get_contents($basePath . '/modal_prod.html'),
        'modal_buscar_global'   => file_get_contents($basePath . '/modal_buscar_global.html'),
        'modal_imagen'          => file_get_contents($basePath . '/modal_imagen.html'),
        'modal_categorias'      => file_get_contents($basePath . '/modal_categorias.html'),
        'modal_crear_categoria' => file_get_contents($basePath . '/modal_crear_categoria.html'),
        'scripts'               => file_get_contents($basePath . '/scripts.html'),
        'menu_flot'             => file_get_contents(VARPATH . '/public/admin/menu_flot.html')
    ];

    // 5. Renderizar plantilla Mustache
    echo (new Mustache)->render(
        file_get_contents(__DIR__ . '/inicio.html'),
        $data,
        $partials
    );
    exit;
});

include_once __DIR__ . '/api/control01.php';
include_once __DIR__ . '/api/control02.php';