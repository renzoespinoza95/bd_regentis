<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Lima');

define('VARPATH', dirname(__FILE__));
define ('DEFINITION', VARPATH . "/app/definition.php");

require 'flight/Flight.php';

require_once VARPATH."/inc/config.inc.php";
//require_once VARPATH."/classes/util.class.php";
require_once VARPATH."/classes/perso.class.php";
require_once VARPATH."/classes/Meekrodb2.class.php";
require_once VARPATH."/classes/JWT.class.php";
//require_once VARPATH."/classes/paginator.class.php";
require_once VARPATH."/classes/amarilis.class.php";
require_once VARPATH."/classes/Mustache.class.php";
require_once VARPATH."/classes/Lorem.class.php";
//require_once VARPATH."/classes/boot.class.php";
require_once VARPATH."/classes/commons.php";


perso::config($varhost);   

// Meekro
DB::$user = $username;
DB::$password = $password;
DB::$dbName = $dbname;
DB::$host = $host;
DB::query("SET NAMES utf8mb4");

$path_public = VARPATH . "/public";

$sesion_admin_administrador_id = $_COOKIE['sesion_admin_administrador_id_' . $nombre_app] ?? null;
$sesion_admin_administrador_email = $_COOKIE['sesion_admin_administrador_email_' . $nombre_app] ?? null;


if (perso::_es_apache()) {
    // Headers CORS (una sola vez)
    if (!headers_sent()) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With');
        header('Access-Control-Expose-Headers: Content-Length, Content-Range');
    }

    // Preflight OPTIONS (solo bajo Apache)
    Flight::route('OPTIONS *', function () {
        if (!headers_sent()) {
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization, X-Requested-With');
            header('Access-Control-Max-Age: 86400');
        }
        // 204 = No Content (mejor que 200 "OK" para preflight)
        Flight::halt(204);
    });
}


include VARPATH. "/classes/SimpleImage.class.php";
include VARPATH. "/classes/upload.class.php";

require_once VARPATH."/app/gatti.control.php";

$upload = new Upload;
$simple_image = new SimpleImage();

//=== WKHTML ===
require_once VARPATH."/classes/WkHtmlToPdf.php";

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    //echo 'This is a server using Windows!';
    $options['bin'] = 'C:\wkhtmltopdf\bin\wkhtmltopdf.exe';
} else {
    //echo 'This is a server not using Windows!';
    $options['bin'] = '/usr/bin/wkhtmltopdf';
}

$wkh_pdf = new WkHtmlToPdf($options);


$valor_key = $nombre_app . vari("KEY");

$administrador_actual = null;

if (!empty($sesion_admin_administrador_id) && is_string($sesion_admin_administrador_id)) {

    // 🔐 desencriptar id usuario de la sesión
    $usu_id = perso::decrypt($sesion_admin_administrador_id, $valor_key);

    if (!empty($usu_id)) {

        $usu_id = str_replace("*", "", $usu_id);
        $usu_id = intval($usu_id);

        if ($usu_id > 0) {

            // 👤 obtener administrador actual
            $administrador_actual = DB::queryFirstRow("
                SELECT
                    u.usu_id,
                    u.nombres_apellidos,
                    u.email,
                    u.img_perfil,
                    u.rol_id,
                    r.nombre AS rol_nombre,
                    r.submenu_inicio,

                    nxu.negxusu_id,
                    nxu.neg_id,
                    nxu.is_activo AS negxusu_activo,
                    nxu.fecha_creacion AS negxusu_fecha_creacion,

                    n.cod_neg,
                    n.nombre AS negocio_nombre,
                    n.puesto,
                    n.direccion AS negocio_direccion,
                    n.ciudad AS negocio_ciudad,
                    n.provincia AS negocio_provincia,
                    n.departamento AS negocio_departamento,
                    n.map_lat AS negocio_lat,
                    n.map_lng AS negocio_lng,
                    n.img_logo,
                    n.is_validado,

                    m.mercado_id,
                    m.nombre AS mercado_nombre,
                    m.direccion AS mercado_direccion,
                    m.ciudad AS mercado_ciudad,
                    m.provincia AS mercado_provincia,
                    m.departamento AS mercado_departamento,
                    m.map_lat AS mercado_lat,
                    m.map_lng AS mercado_lng

                FROM reg_usu u

                LEFT JOIN reg_rol r
                    ON u.rol_id = r.rol_id

                LEFT JOIN reg_negxusu nxu
                    ON u.usu_id = nxu.usu_id
                    AND nxu.is_activo = 1

                LEFT JOIN reg_neg n
                    ON nxu.neg_id = n.neg_id

                LEFT JOIN reg_mercado m
                    ON n.mercado_id = m.mercado_id

                WHERE u.usu_id = %i
                AND u.is_activo = 1

                LIMIT 1
            ", $usu_id);

        }

    }

}

// var_dump($administrador_actual);
// exit;

Flight::set('flight.handle_errors', false);

Flight::start();

