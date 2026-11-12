<?php
// backend php8.2 + flightphp + meekrodb2

Flight::route('GET /prod', function () {
    include DEFINITION;
    autentificar_administrador();
    global $administrador_actual;
    $neg_id = intval($administrador_actual['neg_id'] ?? 1);

    // Obtener el nombre del negocio actual de la base de datos
    $nombreNegocio = DB::queryFirstField(
        "SELECT nombre FROM reg_neg WHERE neg_id = %i AND borrado_el IS NULL",
        $neg_id
    ) ?: 'Tienda Express';

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
        'title'           => 'Administración - Productos',
        'varhost'         => $varhost,
        'apphost'         => $apphost,
        'neg_id'          => $neg_id,
        'nombre_negocio'  => $nombreNegocio,
        'assets_head'     => $assets_head,
        'footer_html'     => $footer_html
    ];

    $basePath = VARPATH . '/public/admin/tab_prod/comp_prod';

    $partials = [
        'head'                       => file_get_contents($basePath . '/head.html'),
        'navbar_prod'                => file_get_contents($basePath . '/navbar_prod.html'),
        'modal_detalle'              => file_get_contents($basePath . '/modal_detalle.html'),
        'modal_crear'                => file_get_contents($basePath . '/modal_crear.html'),
        'modal_editar'               => file_get_contents($basePath . '/modal_editar.html'),
        'modal_categoria_nueva'      => file_get_contents($basePath . '/modal_categoria_nueva.html'),
        'modal_categoria_editar'     => file_get_contents($basePath . '/modal_categoria_editar.html'),
        'modal_subcategoria_nueva'   => file_get_contents($basePath . '/modal_subcategoria_nueva.html'),
        'modal_subcategoria_editar'  => file_get_contents($basePath . '/modal_subcategoria_editar.html'),
        'categoria_form'             => file_get_contents($basePath . '/categoria_form.html'),
        'modal_foto'                 => file_get_contents($basePath . '/modal_foto.html'),
        'modal_fotos_header'         => file_get_contents($basePath . '/modal_fotos_header.html'),
        'modal_fotos_body'           => file_get_contents($basePath . '/modal_fotos_body.html'),
        'modal_fotos_footer'         => file_get_contents($basePath . '/modal_fotos_footer.html'),
        'modal_reporte'              => file_get_contents($basePath . '/modal_reporte.html'),
        'scripts'                    => file_get_contents($basePath . '/scripts.html'),
        'menu_flot'                  => file_get_contents(VARPATH . '/public/admin/menu_flot.html'),
    ];

    echo (new Mustache)->render(
        file_get_contents(VARPATH . '/public/admin/tab_prod/prod.html'),
        $data,
        $partials
    );
});

include_once __DIR__ . '/api/control01.php';
include_once __DIR__ . '/api/control02.php';
include_once __DIR__ . '/api/control03.php';
include_once __DIR__ . '/api/control04.php';
include_once __DIR__ . '/api/control05.php';
include_once __DIR__ . '/api/control06.php';

function normalizar($txt){
    $txt = strtolower($txt);
    $txt = str_replace(
        ['á','é','í','ó','ú','ñ','z'],
        ['a','e','i','o','u','n','s'],
        $txt
    );
    return $txt;
}

function limpiarBusqueda($txt){
    $txt = strtolower($txt);
    $txt = str_replace(
        ['á','é','í','ó','ú','ñ'],
        ['a','e','i','o','u','n'],
        $txt
    );
    $txt = preg_replace('/[^a-z0-9\s]/', ' ', $txt);
    $txt = preg_replace('/\s+/', ' ', $txt);
    return trim($txt);
}