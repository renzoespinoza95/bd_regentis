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

function mh($item) {
    $res = html_entity_decode($item, ENT_QUOTES, 'UTF-8');
    return $res;
}

function solo_texto($html, $cantidad) {
    $res = strip_tags($html);
    $res = limite_palabras($res, $cantidad);

    return $res;
}


function limite_palabras($text, $limit = 25, $ending = '...') {

    //$text = strip_tags($text);
    $text = preg_replace("/\s\s+/", "", (strip_tags($text)));
    if (strlen($text) > $limit) {
        //$text = strip_tags($text);
        $text = substr($text, 0, $limit);
        $text = substr($text, 0, -(strlen(strrchr($text, ' '))));
        $text = $text . $ending;
    }

    return $text;
}

function valor_random() {

    $validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ1234567890";
    $validCharNumber = strlen($validCharacters);
    $result = "";

    for ($i = 0; $i < $this->persistencia; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);
        $result .= $validCharacters[$index];
    }

    return $result;
}

function string_random() {

    $validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ";
    $validCharNumber = strlen($validCharacters);
    $result = "";

    for ($i = 0; $i < $this->persistencia; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);
        $result .= $validCharacters[$index];
    }

    return $result;
}

// Obtener un numero random

function cantidad_numeros_random() {
    $cantidad = 8;
    $validCharacters = "1234567890";
    $validCharNumber = strlen($validCharacters);
    $result = "";

    for ($i = 0; $i < $cantidad; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);
        $result .= $validCharacters[$index];
    }

    $tiempoUnico = time();

    $result = $result . "_" . $tiempoUnico;

    return $result;
}

// Obtener un true-false random

function logico_random() {

    $validCharacters = "1234567890";
    $validCharNumber = strlen($validCharacters);
    $result = "";

    for ($i = 0; $i < 4; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);
        $result .= $validCharacters[$index];
    }

    if ($result % 2 == 0) {
        return "1";
    } else {
        return "0";
    }
}

function diferencia_de_horas_y_dias($startDate, $endDate, $format = 3) {
    list($date, $time) = explode(' ', $endDate);
    $startdate = explode("-", $date);
    $starttime = explode(":", $time);

    list($date, $time) = explode(' ', $startDate);
    $enddate = explode("-", $date);
    $endtime = explode(":", $time);

    $secondsDifference = mktime($endtime[0], $endtime[1], $endtime[2], $enddate[1], $enddate[2], $enddate[0]) - mktime($starttime[0], $starttime[1], $starttime[2], $startdate[1], $startdate[2], $startdate[0]);

    switch ($format) {
        // Difference in Minutes 
        case 1:
            return floor($secondsDifference / 60);
        // Difference in Hours     
        case 2:
            return floor($secondsDifference / 60 / 60);
        // Difference in Days     
        case 3:
            return floor($secondsDifference / 60 / 60 / 24);
        // Difference in Weeks     
        case 4:
            return floor($secondsDifference / 60 / 60 / 24 / 7);
        // Difference in Months     
        case 5:
            return floor($secondsDifference / 60 / 60 / 24 / 7 / 4);
        // Difference in Years     
        default:
            return floor($secondsDifference / 365 / 60 / 60 / 24);
    }
}

// devuelve la fecha y hora en texto  

function fecha_hora_en_texto($raw_date) {
    if (($raw_date == '0001-01-01 00:00:00') || ($raw_date == ''))
        return false;

    $year = (int) substr($raw_date, 0, 4);
    $month = (int) substr($raw_date, 5, 2);
    $day = (int) substr($raw_date, 8, 2);
    $hour = (int) substr($raw_date, 11, 2);
    $minute = (int) substr($raw_date, 14, 2);
    $second = (int) substr($raw_date, 17, 2);
    $lemp = "";
    $l_mes = "";
    switch ($month) {
        case "01";
            $l_mes = "Enero";
            break;
        case "02";
            $l_mes = "Febrero";
            break;
        case "03";
            $l_mes = "Marzo";
            break;
        case "04";
            $l_mes = "Abril";
            break;
        case "05";
            $l_mes = "Mayo";
            break;
        case "06";
            $l_mes = "Junio";
            break;
        case "07";
            $l_mes = "Julio";
            break;
        case "08";
            $l_mes = "Agosto";
            break;
        case "09";
            $l_mes = "Setiembre";
            break;
        case "10";
            $l_mes = "Octubre";
            break;
        case "11";
            $l_mes = "Noviembre";
            break;
        case "12";
            $l_mes = "Diciembre";
            break;
    }
    $l_emp = $day . " " . $l_mes . " de " . $year;
    return $l_emp;
}

function numeros_para_paginacion($cant_total, $cant_items) {
    $paginas = (int) ($cant_total / $cant_items);
    $resto = ($cant_total % $cant_items);

    if ($resto > 0) {
        $paginas++;
    }

    $res = array();

    for ($i = 0; $i < $paginas; $i++) {
        array_push($res, $i * $cant_items);
    }

    return $res;
}

// Funciones Html

function br() { // BEGIN public static function br()
    echo "<br/>";
}

// END public static function br() 

function p($parrafo) { // BEGIN public static function br()
    echo "<p>" . $parrafo . "</p>";
}

function pre() { // BEGIN public static function pre()
    echo "<pre>";
}

// END public static function pre()

function cpre() { // BEGIN public static function close_pre()
    echo "</pre>";
}

 function encode_items(&$item, $key)
 {
    $item = util::mostrar_palabra_latina($item);
 }

 function pl($item)
 {    
    return util::mostrar_palabra_latina($item);
 }


// END public static function close_pre()
// html comentarios

function com($texto) {
    echo "<!-- " . strtoupper($texto) . " -->";
    n();
}

// salto de linea
function n() {
    echo "\n";
}

function tt() {
    return "  ";
}

function nl() {
    return "\r\n";
}

function pc() {
    return ";";
}

//
function css($css) {
    echo "<link rel='stylesheet' href='" . $css . "' />";
    n();
}

function print_css($css) {
    echo "<link rel='stylesheet' href='" . $css . "' media='print' />";
    n();
}

//
function js($js) {
    echo "<script src='" . $js . "'></script>";
    n();
}

function q() {
    return chr(39);
}

function sp() {
    return " ";
}

function qq() {
    return chr(34);
}

function eq() {
    return "=";
}

function m1() {
    return "<";
}

function m2() {
    return ">";
}

function heq($key, $value) {
    return $key . eq(). qq() . $value . qq();
}

function data($data, $value) {
    return $data . eq(). qq() . $value . qq();
}

function vari($nombre_variable)
{

$query = <<<EOF
SELECT * FROM reg_vari WHERE nombre_variable = '$nombre_variable'
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

function pl_rec(&$item)
{ 
$item = util::mostrar_palabra_latina($item);
}

function prea() {
    echo "<textarea style='height: 700px; width: 600px'>";
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

    global $sesion_admin_administrador_id, $nombre_app, $apphost;

    // Si no hay sesión
    if (empty($sesion_admin_administrador_id)) {
        include $path_public . "/admin/fin_sesion.php";
        exit;
    }

    // desencriptar id usuario
    $valor_key = $nombre_app . vari("KEY");

    $usu_id = perso::decrypt($sesion_admin_administrador_id, $valor_key);
    $usu_id = str_replace("*", "", $usu_id);

    // verificar que el usuario exista y esté activo
    $existe = DB::queryFirstField("
        SELECT COUNT(*)
        FROM reg_usu
        WHERE usu_id = %i
        AND is_activo = 1
    ", $usu_id);

    if (!$existe) {
        include $path_public . "/admin/fin_sesion.php";
        exit;
    }

    return $usu_id;
}

function categorias_nuevo_negocio($neg_id){

    $now_unix = time()*1000;

    $cats = DB::query("
        SELECT *
        FROM reg_categoria_global
        WHERE is_activo=1
        ORDER BY orden ASC
    ");

    foreach($cats as $c){

        DB::insert('pos_category',[
            'neg_id'      => $neg_id,
            'name'        => $c['nombre'],
            'icon'        => $c['icono'],
            'priority'    => $c['orden'],
            'categoria_global_id'    => $c['categoria_global_id'],
            'created_at'  => $now_unix,
            'last_update' => $now_unix
        ]);

    }

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