<?php

function guardar_html($html) {
    $res = trim($html);
    $res = htmlentities($res, ENT_QUOTES, 'UTF-8');
    //$res = stripslashes($res);
    return $res;
}

function mostrar_html($html) {
    $res = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    return $res;
}

 function encode_items(&$item, $key)
 {
    $item = util::mostrar_palabra_latina($item);
 }

function com($texto) {
    echo "<!-- " . strtoupper($texto) . " -->";
    n();
}

// salto de linea
function n() {
    echo "\n";
}
//
function css($css) {
    echo "<link rel='stylesheet' href='" . $css . "' />";
    n();
}

//
function js($js) {
    echo "<script src='" . $js . "'></script>";
    n();
}

function vari($nombre_variable)
{

    $query = <<<EOF
    SELECT * FROM reg_vari WHERE nombre = '$nombre_variable'
    EOF;

    $res = DB::queryFirstRow($query);
    $vari = $res['valor'];    


    if($vari == "") {;
        echo "NO EXISTE: $nombre_variable";
        exit;
    }
    return $vari;

}

function vedit($nombre_variable, $valor){

vari($nombre_variable);
variables_sistema::editar_variables_sistema(
    $nombre_variable,
    "valor",
    $valor
);

}

function veri_api_key($app, $req = "")
{
$vari = vari("API_KEY");
if($vari == "") {;
echo "NO EXISTE: $nombre_variable";
exit;
}

//==============
$cliente_api_key = $app->request()->params('API_KEY');


    switch ($req) {
        case "":
            $cliente_api_key = $app->request()->params('API_KEY');
            break;
        case "POST":
            $request = Slim::getInstance()->request();
            $cliente_api_key = json_decode($request->getBody());            
            $cliente_api_key = (array) $cliente_api_key;
                        
            $cliente_api_key = $cliente_api_key['API_KEY'];  
            break;
        case "PUT":
            $request = Slim::getInstance()->request();
            $cliente_api_key = json_decode($request->getBody());
            $cliente_api_key = (array) $cliente_api_key;
            
            $cliente_api_key = $cliente_api_key['API_KEY'];            
            break;
    }




    $sistema_api_key = vari("API_KEY");

    if($cliente_api_key != $sistema_api_key ||
            $cliente_api_key == "") {

        $respuesta = array(
                "response" => array(
                    "tipo" => "ERROR",
                    "descripcion" => "No esta Autorizado para usar la API"                
            )); 
        echo json_encode($respuesta);   
        exit;
    }
//==============
}

function dd($value, $name = "") {
    $timestamp = date('H:i:s.u'); // Add timestamp with microseconds
    echo "<pre>";
    echo "[{$timestamp}] {$name} := " . print_r($value, true);
    echo "</pre>";
    die();
}

function poke() {
    return perso::ok() . time() ;
}

function require_jwt() {

    if (!ACTIVO_JWT) return;

    $headers = getallheaders();

    if (empty($headers['Authorization'])) {
        Flight::halt(401, 'Token requerido');
    }

    if (!preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
        Flight::halt(401, 'Formato inválido');
    }

    try {
        $payload = JWT::decode($matches[1], JWT_SECRET);
        Flight::set('auth_user', $payload);
    } catch (Exception $e) {
        Flight::halt(401, 'Token inválido o expirado');
    }
}

function lista_menu_con_submenus_v2($tipo_administrador_id)
{
    $menus = DB::query("
        SELECT m.menu_id, m.titulo, m.orden
        FROM tipo_administrador_menu tam
        INNER JOIN menu m ON m.menu_id = tam.menu_id
        WHERE tam.tipo_administrador_id = %i
        AND tam.is_activo = 1
        ORDER BY m.orden
    ", $tipo_administrador_id);

    foreach ($menus as &$menu) {
        $menu['lista_submenu'] = DB::query("
            SELECT submenu_id, titulo, url, target, orden
            FROM submenu
            WHERE menu_id = %i
            ORDER BY orden
        ", $menu['menu_id']);
    }

    return $menus;
}

function autentificar_administrador()
{
    include DEFINITION;

    // Si no hay sesión
    if (empty($ssa_id)) {
        include VARPATH . "/public/admin/fin_sesion.php";
        exit;
    }

    // desencriptar id usuario
    $valor_key = $nombre_app . vari("KEY");

    $usu_id = perso::decrypt($ssa_id, $valor_key);
    $usu_id = str_replace("*", "", $usu_id);

    // verificar que el usuario exista y esté activo
    $existe = DB::queryFirstField("
        SELECT COUNT(*)
        FROM reg_usu
        WHERE usu_id = %i
        AND is_activo = 1
    ", $usu_id);

    if (!$existe) {
        include VARPATH . "/public/admin/fin_sesion.php";
        exit;
    }

    return $usu_id;
}

// Usa la misma clave en Base64 (32 bytes al decodificar)
function barsi_key(): string {
    $b64 = BARSI_AES_KEY_B64;
    $key = base64_decode($b64, true);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('BARSI_AES_KEY_B64 inválida: se requieren 32 bytes al decodificar.');
    }
    return $key;
}

function enc_barsi(string $texto): string {
    $key = barsi_key();
    $iv = random_bytes(12); // 96-bit IV
    $cipher = 'aes-256-gcm';
    $tag = '';
    $ciphertext = openssl_encrypt($texto, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('Fallo en openssl_encrypt');
    }
    // Unificamos formato con Python/TS: Base64(iv || ciphertext || tag)
    return base64_encode($iv . $ciphertext . $tag);
}

function des_barsi(string $tokenB64): string {
    $key = barsi_key();
    $raw = base64_decode($tokenB64, true);
    if ($raw === false || strlen($raw) < 12 + 16) {
        throw new RuntimeException('Token inválido');
    }
    $iv = substr($raw, 0, 12);
    $tag = substr($raw, -16);                 // 128-bit tag al final
    $ciphertext = substr($raw, 12, -16);      // medio: solo ciphertext
    $cipher = 'aes-256-gcm';
    $plaintext = openssl_decrypt($ciphertext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        throw new RuntimeException('Fallo en openssl_decrypt (tag/clave/token inválidos)');
    }
    return $plaintext;
}    

function visita($nombre = 'visitas') {

    // 🔥 Asegurar charset
    DB::query("SET NAMES 'utf8mb4'");

    // 🔥 Incrementar variable dinámica
    DB::query("
        UPDATE reg_vari 
        SET valor = IFNULL(valor,0) + 1
        WHERE nombre = %s
    ", $nombre);

}

function generarCodigoUnico(): string {

    do {

        // 🔹 5 dígitos + 1 letra mayúscula (igual que tu diseño)
        $numeros = str_pad((string)random_int(0, 99999), 5, '0', STR_PAD_LEFT);
        $letra   = chr(random_int(65, 90)); // A-Z

        $codigo = $numeros . $letra;

        // 🔥 TABLA CORRECTA
        $existe = DB::queryFirstField(
            "SELECT 1 FROM reg_usu WHERE cod_usu = %s",
            $codigo
        );

    } while ($existe);

    return $codigo;
}


function firma($xin, $yuan){

    include DEFINITION;

    if(!$xin || !$yuan){

        Flight::json([
            'status' => 'error',
            'msg' => 'Firma incompleta'
        ], 401);

        exit;

    }
    // 50 segundos
    if(abs((time() * 1000) - $xin) > 50000){

        Flight::json([
            'status' => 'error',
            'msg' => 'Firma expirada'
        ], 401);

        exit;

    }

    $hash_server = md5(
        $xin . $fenix_key
    );

    if($yuan !== $hash_server){

        Flight::json([
            'status' => 'error',
            'msg' => 'Firma inválida'
        ], 401);

        exit;

    }

}