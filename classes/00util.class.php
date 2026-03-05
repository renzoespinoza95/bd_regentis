<?php

class util {

// --------------
// | MODREWRITE |
// --------------

public static function esta_activo_mod_rewrite() {
    $status = null;
    foreach (apache_get_modules() as $mod) {
        if ($mod == "mod_rewrite") {
            $status = "Mod Rewrite presente";
        }
    }
    echo $status;
}

// -------------------
// | ESTA_ACTIVO_PDO |
// -------------------  
public static function esta_activo_pdo() {
    foreach (PDO::getAvailableDrivers() as $driver) {
        echo $driver . "<br/>";
    }
}

// ---------------------
// | PARAMETROS_SERVER |
// ---------------------

public static function parametros_server() {
    $varpath = $_SERVER['DOCUMENT_ROOT'] . $_SERVER['REQUEST_URI'];
    $varpath = dirname(str_replace("/", "\\", $varpath)) . "\\";
    $varhost = "http://" . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

    // --
    echo "varpath: " . $varpath;
    br();
    echo "varhost: " . $varhost;
    br();
}

// ---------------------
// | VERIFICAR_BROWSER |
// ---------------------

public static function verificar_browser($mb) {

    if ($mb->getBrowser() == Browser::BROWSER_IE && $mb->getVersion() < 9) {
        return false;
    } else {
        return true;
    }
}

// -- Codifica para la insercion en Mysql con PDO
public static function html_encode($var) {
    //$var=strtolower(trim($var));
    return htmlentities($var, ENT_QUOTES, 'UTF-8');
}

// -- Agrega un nuevo str al array
public static function insertar_al_array($dest_arr, $agreg_str) {
    array_push($dest_arr, $agreg_str);
    $nuevo_str_coll = implode(",", $dest_arr);

    return $nuevo_str_coll;
}

public static function maximo_peso_archivo() {
    return ini_get("upload_max_filesize");
}



function real_captcha($value) {
    $hash = 5381;
    $value = strtoupper($value);
    for ($i = 0; $i < strlen($value); $i++) {
        $hash = (($hash << 5) + $hash) + ord(substr($value, $i));
    }
    return $hash;
}
    
public static function fechaSlash2fechaMySQL($fecha) {
    list($dia,$mes,$anio)=explode("/",$fecha);
    $fecha = "$anio-$mes-$dia";
    return $fecha;
}

public static function fecha_guion($fecha) {    
    return self::fechaSlash2fechaMySQL($fecha);
}

public static function fechaMSSQL2fechaSlash($fecha) {
    $fecha = substr($fecha,0,10);
    list($anio,$mes,$dia)=explode("-",$fecha);
    $fecha = "$dia/$mes/$anio";
    return $fecha;
}  

public static function guardar_imagen_como_jpg_con_ancho(
                                        $simple_image, 
                                        $post_file, 
                                        $ancho, 
                                        $nombre_archivo, 
                                        $ruta_fisica) {
    extract($post_file);
    $simple_image->load($tmp_name);
    $simple_image->resizeToWidth($ancho);
    $ruta_img_full = $ruta_fisica . $nombre_archivo . ".jpg";
    $simple_image->save($ruta_img_full);
    $res = $nombre_archivo . ".jpg";
    return $res;
}

public static function guardar_imagen_como_jpg_sin_ancho(
                                                $simple_image, 
                                                $post_file, 
                                                $nombre_archivo, 
                                                $ruta_fisica) {        
    extract($post_file);
    $simple_image->load($tmp_name);
    $ruta_img_full = $ruta_fisica . $nombre_archivo . ".jpg";
    $simple_image->save($ruta_img_full);
    $res = $nombre_archivo . ".jpg";
    return $res;
}
  

    
public static function normalize_nombres ($string) {
    $table = array(
        'Š'=>'S', 'š'=>'s', 'Đ'=>'Dj', 'đ'=>'dj', 'Ž'=>'Z', 'ž'=>'z', 'Č'=>'C', 'č'=>'c', 'Ć'=>'C', 'ć'=>'c',
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
        'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O',
        'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss',
        'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e',
        'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ò'=>'o', 'ó'=>'o',
        'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b',
        'ÿ'=>'y', 'Ŕ'=>'R', 'ŕ'=>'r',
    );
    
    return strtr(utf8_encode($string), $table);
}   



 

public static function conv_fecha_normal_hacia_fecha_sello($raw_date) {
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
            $l_mes = "ENE";
            break;
        case "02";
            $l_mes = "FEB";
            break;
        case "03";
            $l_mes = "MAR";
            break;
        case "04";
            $l_mes = "ABR";
            break;
        case "05";
            $l_mes = "MAY";
            break;
        case "06";
            $l_mes = "JUN";
            break;
        case "07";
            $l_mes = "JUL";
            break;
        case "08";
            $l_mes = "AGO";
            break;
        case "09";
            $l_mes = "SET";
            break;
        case "10";
            $l_mes = "OCT";
            break;
        case "11";
            $l_mes = "NOV";
            break;
        case "12";
            $l_mes = "DIC";
            break;
    }
    $l_emp = $day . " " . $l_mes . " " . $year;
    return $l_emp;
}

public static function fecha_sello($fecha) {
    return self::conv_fecha_normal_hacia_fecha_sello($fecha);
}

public static function fecha_nombre_mes($raw_date, $completo = null) {
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
            if(isset($completo)) {
                $l_mes = "ENERO";
            } else {
                $l_mes = "ENE";
            }
            
            break;
        case "02";
            if(isset($completo)) {
                $l_mes = "FEBRERO";
            } else {
                $l_mes = "FEB";
            }
            
            break;
        case "03";
            if(isset($completo)) {
                $l_mes = "MARZO";
            } else {
                $l_mes = "MAR";
            }
            
            break;
        case "04";
            if(isset($completo)) {
                $l_mes = "ABRIL";
            } else {
                $l_mes = "ABR";
            }
            
            break;
        case "05";
            if(isset($completo)) {
                $l_mes = "MAYO";
            } else {
                $l_mes = "MAY";
            }
            
            break;
        case "06";
            if(isset($completo)) {
                $l_mes = "JUNIO";
            } else {
                $l_mes = "JUN";
            }
            
            break;
        case "07";
            if(isset($completo)) {
                $l_mes = "JULIO";
            } else {
                $l_mes = "JUL";
            }
            
            break;
        case "08";
            if(isset($completo)) {
                $l_mes = "AGOSTO";
            } else {
                $l_mes = "AGO";
            }
            
            break;
        case "09";
            if(isset($completo)) {
                $l_mes = "SETIEMBRE";
            } else {
                $l_mes = "SET";
            }
            
            break;
        case "10";
            if(isset($completo)) {
                $l_mes = "OCTUBRE";
            } else {
                $l_mes = "OCT";
            }
            
            break;
        case "11";
            if(isset($completo)) {
                $l_mes = "NOVIEMBRE";
            } else {
                $l_mes = "NOV";
            }
            
            break;
        case "12";
            if(isset($completo)) {
                $l_mes = "DICIEMBRE";
            } else {
                $l_mes = "DIC";
            }
            
            break;
    }
    
    return $l_mes;
}

public static function mostrar_sql($texto) {
    return iconv("CP1252", "UTF-8", $texto);
}

public static function guardar_imagen_con_ancho(
                                            $simple_image, 
                                            $post_file, 
                                            $ancho, 
                                            $nombre_archivo, 
                                            $ruta_fisica) {
    extract($post_file);       
   
    if ($post_file['error'] <> 0) {                        
        $tmp_name = $ruta_fisica . "/imagen_vacio.jpg";         
    };
    
    
    $simple_image->load($tmp_name);
    $simple_image->resizeToWidth($ancho);
    $ruta_img_full = $ruta_fisica . $nombre_archivo . "." . $simple_image->extension_imagen();
    $simple_image->save($ruta_img_full, $simple_image->tipo_de_imagen());
    $res = $nombre_archivo . "." . $simple_image->extension_imagen();
    return $res;
}

public static function guardar_imagen_sin_ancho(
    $simple_image, 
    $post_file, 
    $nombre_archivo, 
    $ruta_fisica) {
    
    extract($post_file);
    
    if ($post_file['error'] <> 0) {                        
        $tmp_name = $ruta_fisica . "/imagen_vacio.jpg";         
    };
    
    $simple_image->load($tmp_name);
    $ruta_img_full = $ruta_fisica . $nombre_archivo . "." . $simple_image->extension_imagen();
    $simple_image->save(
        $ruta_img_full, 
        $simple_image->tipo_de_imagen(),
        75
    );
    $res = $nombre_archivo . "." . $simple_image->extension_imagen();
    return $res;
}
    
public static function guardar_crop(
            $simple_image, 
            $url, 
            $nombre_archivo, 
            $ruta_fisica,
            $crop_x, 
            $crop_y, 
            $crop_w, 
            $crop_h,
            $rotacion) {        
        
    $simple_image->load($url);
    $simple_image->crop(
            $crop_x, 
            $crop_y, 
            $crop_w, 
            $crop_h,
            $rotacion);
    
    $ruta_img_full = $ruta_fisica . $nombre_archivo . "." . $simple_image->extension_imagen();
    $simple_image->save($ruta_img_full, $simple_image->tipo_de_imagen());
    $res = $nombre_archivo . "." . $simple_image->extension_imagen();
    //return $res;
    return $ruta_img_full;
}    

//++++++++++++++++++++++++++  
// -- Muestra las tildes del MySQL 
public static function mostrar_html($html) {
    $res = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
    return $res;
}

// -- Elimina un str del array   

public static function eliminar_del_array($orig_arr, $cond_str) {

    foreach ($orig_arr as $key => &$value) {
        if ($value == $cond_str) {
            unset($orig_arr[$key]);
        }
    }

    $nuevo_str_coll = implode("|", $orig_arr);
    return $nuevo_str_coll;
}

// -- Convierte un array a string

public static function convertir_array_a_string($arr) {
    $nuevo_str_coll = implode("|", $arr);
    return $nuevo_str_coll;
}

// -- Convierte un str a array

public static function convertir_string_a_array($str) {

    $arr = explode("|", $str);
    return $arr;
}

public static function guardar_sql($texto) {
    $texto = trim($texto);
    return iconv("UTF-8", "CP1252", $texto);
}
    
// public static function guardar_palabra_latina($texto) {
//     $texto = trim($texto);
//     return iconv("UTF-8", "CP1252", $texto);
// }

public static function guardar_palabra_latina($texto) {
    if (is_null($texto)) {
        return null;
    }

    $texto = trim($texto);
    return iconv("UTF-8", "CP1252", $texto);
}

public static function mostrar_palabra_latina($texto) {
    if (is_null($texto) || $texto === '') {
        return '';
    }
    return iconv("CP1252", "UTF-8", $texto);
}

public static function conv_fecha_normal_hacia_mysql($fecha) {
list($dia,$mes,$anio)=explode("/",$fecha);
$fecha = "$anio-$mes-$dia";
return $fecha;
}

public static function html_decode($var) {
    return html_entity_decode($var, ENT_QUOTES, 'UTF-8');
}

public static function array_to_table($data,$args=false) {
	if (!is_array($args)) { $args = array(); }
	foreach (array('class','column_widths','custom_headers','format_functions','nowrap_head','nowrap_body','capitalize_headers') as $key) {
		if (array_key_exists($key,$args)) { $$key = $args[$key]; } else { $$key = false; }
	}
	if ($class) { $class = ' class="'.$class.'"'; } else { $class = ''; }
	if (!is_array($column_widths)) { $column_widths = array(); }

    //get rid of headers row, if it exists (headers should exist as keys)
    if (array_key_exists('headers',$data)) { unset($data['headers']); }

	$t = '<table'.$class.'>';
	$i = 0;
	foreach ($data as $row) {
		$i++;
		//display headers
		if ($i == 1) { 
			foreach ($row as $key => $value) {
				if (array_key_exists($key,$column_widths)) { $style = ' style="width:'.$column_widths[$key].'px;"'; } else { $style = ''; }
				$t .= '<col'.$style.' />';
			}
			$t .= '<thead><tr>';
			foreach ($row as $key => $value) {
				if (is_array($custom_headers) && array_key_exists($key,$custom_headers) && ($custom_headers[$key])) { $header = $custom_headers[$key]; }
				elseif ($capitalize_headers) { $header = ucwords($key); }
				else { $header = $key; }
				if ($nowrap_head) { $nowrap = ' nowrap'; } else { $nowrap = ''; }
				$t .= '<td'.$nowrap.'>'.$header.'</td>';
			}
			$t .= '</tr></thead>';
		}

		//display values
		if ($i == 1) { $t .= '<tbody>'; }
		$t .= '<tr>';
		foreach ($row as $key => $value) {
			if (is_array($format_functions) && array_key_exists($key,$format_functions) && ($format_functions[$key])) {
				$function = $format_functions[$key];
				if (!function_exists($function)) { custom_die('Data format function does not exist: '.htmlspecialchars($function)); }
				$value = $function($value);
			}
			if ($nowrap_body) { $nowrap = ' nowrap'; } else { $nowrap = ''; }
			$t .= '<td data-title="' . $key . '"'.$nowrap.'>'.$value.'</td>';
		}
		$t .= '</tr>';
	}
	$t .= '</tbody>';
	$t .= '</table>';
	return $t;
}
    
public static function preparar_para_encriptar($string) {
    return $string."*";
}

public static function crear_mensaje($tipo_mensaje, $mensaje) {
    
if($tipo_mensaje == "exito") {
    $class = "alert alert-success";
}   

if($tipo_mensaje == "error") {
    $class = "alert alert-error";
}   

    //$ruta_completa = $host . $ruta_aplicacion;
    $aplicar = <<<EOF
<div class="$class">
  <button class="close" data-dismiss="alert">×</button>
  $mensaje
</div>
EOF;
    echo $aplicar;
    n();
}
    
public static function ruta_directorio_anio_mes($path_data, $ruta_variable_sistema) {

   $fecha_actual = date("Y-m-d");

   $mes = date("m", strtotime($fecha_actual));
   $anio = date("o", strtotime($fecha_actual));

   $anio_mes = $anio . "_" . $mes;

   $ruta_fisica = $path_data . $ruta_variable_sistema;
   $ruta_fisica = $ruta_fisica . $anio_mes;

   if (!file_exists($ruta_fisica)) {
        mkdir($ruta_fisica, 0777, true);
   }

   return $ruta_fisica . "/";

}

public static function convertir_youtube_url($url) {

    $url_listo = preg_replace('/.+(\?|&)v=([A-Za-z0-9\-_]+).*/', '$2', $url);
    return $url_listo;
}

// agregar clases

public static function autoloader($path) {

    foreach (glob($path . "/*.php") as $filename) {
        require_once $filename;
    }
}
    
public static function traducir_url($url) {

    $redireccionar_aqui = substr($url,3);
    $redireccionar_aqui = base64_decode($redireccionar_aqui);

    return $redireccionar_aqui;
}
    
public static function url_actual() {

    $url_actual = "http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    return self::encriptar_simple($url_actual);
}

    // public static function url_vivaldi($url_listado, $page, $ipp) {

    //     $url_actual = $url_listado . "?page=" . $page . "&ipp=" . $ipp;
    //     return self::encriptar_simple($url_actual);
    // }

public static function url_vivaldi($url_listado, $todo_get) {

    $url_actual = $url_listado . "?" . $todo_get;
    return self::encriptar_simple($url_actual);

}    
    
public static function encriptar_simple($texto) {
        return rand(100,999).base64_encode($texto);
}

public static function guardar_html($html) {
    $res = trim($html);
    $res = htmlentities($res, ENT_QUOTES, 'UTF-8');
    //$res = stripslashes($res);
    return $res;
}

// fecha y hora actual
public static function fecha_hora_actual() {
    $hoy = date('Y-m-d h:i:s', time());
    return $hoy;
}

public static function fecha_actual() {
    $hoy = date('Y-m-d', time());
    return $hoy;
}

public static function formatearHora($fecha) {
    $dateTime = DateTime::createFromFormat('Y-m-d H:i:s', $fecha);
    return $dateTime ? $dateTime->format('g:i A') : null;
}

public static function reemplazar_caracteres_no_validos($texto, $reemplazo = "") {
    
    $texto = str_replace(",", $reemplazo, $texto);
    $texto = str_replace(".", $reemplazo, $texto);
    $texto = str_replace("¡", $reemplazo, $texto);
    $texto = str_replace("!", $reemplazo, $texto);
    $texto = str_replace("¿", $reemplazo, $texto);
    $texto = str_replace("?", $reemplazo, $texto);
    $texto = str_replace(":", $reemplazo, $texto);
    $texto = str_replace("{", $reemplazo, $texto);
    $texto = str_replace("}", $reemplazo, $texto);
    $texto = str_replace("<", $reemplazo, $texto);
    $texto = str_replace(">", $reemplazo, $texto);
    $texto = str_replace("*", $reemplazo, $texto);
    $texto = str_replace("/", $reemplazo, $texto);
    $texto = str_replace("-", $reemplazo, $texto);
    //$texto = str_replace("_", $reemplazo, $texto);
    $texto = str_replace("+", $reemplazo, $texto);
    $texto = str_replace("#", $reemplazo, $texto);
    $texto = str_replace("&", $reemplazo, $texto);
    $texto = str_replace("=", $reemplazo, $texto);
    $texto = str_replace("(", $reemplazo, $texto);
    $texto = str_replace(")", $reemplazo, $texto);
    $texto = str_replace("%", $reemplazo, $texto);
    $texto = str_replace("$", $reemplazo, $texto);
    $texto = str_replace("{", $reemplazo, $texto);
    $texto = str_replace("}", $reemplazo, $texto);
    $texto = str_replace('"', $reemplazo, $texto);
    $texto = str_replace("'", $reemplazo, $texto);

    $texto = trim(preg_replace('/[ ]{2,}|[\t]/', ' ', $texto));

    return $texto;
} 

public static function reemplazar_char($texto) {
    
    $texto = str_replace("%", "&#37;", $texto);
    $texto = trim(preg_replace('/[ ]{2,}|[\t]/', ' ', $texto));
    return $texto;
}   

public static function limite_palabras($text, $limit = 25, $ending = '...') {
    $text= preg_replace("/\s\s+/","",(strip_tags($text)));
    if (strlen($text) > $limit) {
        //$text = strip_tags($text);
        $text = substr($text, 0, $limit);
        $text = substr($text, 0, -(strlen(strrchr($text, ' '))));
        $text = $text . $ending;
    }
    return $text;
}

public static function devolver_extension($filename) {
      // get base name of the filename provided by user
      $filename = basename($filename);

      // break file into parts seperated by .
      $filename = explode('.', $filename);

      // take the last part of the file to get the file extension
      $filename = $filename[count($filename)-1];   

      // find mime type
      return $filename;
}

public static function devolver_nom_arch_sin_extension($filename) {
      // get base name of the filename provided by user
      $filename = basename($filename);

      // break file into parts seperated by .
      $filename = explode('.', $filename);

      // take the last part of the file to get the file extension
      $filename = $filename[0];   

      // find mime type
      return $filename;
}

public static function nombre_dia($nombre_dia) {

    $nombre = "";

    switch (strtolower($nombre_dia)) {
    case "mon":
    $nombre = "LUN";
    break;
    case "tue":
    $nombre = "MAR";
    break;
    case "wed":
    $nombre = "MIE";
    break;
    case "thu":
    $nombre = "JUE";
    break;
    case "fri":
    $nombre = "VIE";
    break;
    case "sat":
    $nombre = "SAB";
    break;
    default:
    $nombre = "DOM";
    }

    return $nombre;

}   

public static function extjpg($nombre_simple) {
    $nombre_simple = strtolower($nombre_simple);
    $nombre_simple = $nombre_simple . ".jpg";
    return $nombre_simple;
}   




public static function max_upload() {
    $max_upload = (int)(ini_get('upload_max_filesize'));
    $max_file_uploads = (int)(ini_get('max_file_uploads'));    
    $max_post = (int)(ini_get('post_max_size'));
    $memory_limit = (int)(ini_get('memory_limit'));
    $upload_mb = min($max_upload, $max_post, $memory_limit);
    $file_limit = min($max_upload, $max_post, $memory_limit);
    $total_limit = min($max_post, $memory_limit);

    echo "max_upload: " . $max_upload . br();
    echo "max_post: " . $max_post . br();
    echo "max_file_uploads: " . $max_file_uploads . br();
    echo "memory_limit: " . $memory_limit . br();
    echo "upload_mb: " . $upload_mb . br();
    echo "file_limit: " . $file_limit . br();
    echo "total_limit: " . $total_limit . br();
}

public static function lista_meses() {
    $lista_meses = array(
  array(
    "id_mes" => 1,
    "nombre" => "ENERO"
  ),
  array(
    "id_mes" => 2,
    "nombre" => "FEBRERO"
  ),
  array(
    "id_mes" => 3,
    "nombre" => "MARZO"
  ),
  array(
    "id_mes" => 4,
    "nombre" => "ABRIL"
  ),
  array(
    "id_mes" => 5,
    "nombre" => "MAYO"
  ),
  array(
    "id_mes" => 6,
    "nombre" => "JUNIO"
  ),
  array(
    "id_mes" => 7,
    "nombre" => "JULIO"
  ),
  array(
    "id_mes" => 8,
    "nombre" => "AGOSTO"
  ),
  array(
    "id_mes" => 9,
    "nombre" => "SETIEMBRE"
  ),
  array(
    "id_mes" => 10,
    "nombre" => "OCTUBRE"
  ),
  array(
    "id_mes" => 11,
    "nombre" => "NOVIEMBRE"
  ),
  array(
    "id_mes" => 12,
    "nombre" => "DICIEMBRE"
  )
);
    return $lista_meses;
}

public static function ok() {

    $res = array(
    'res'=> 'ok'
    );

    return json_encode($res);

}

public static function error() {

    $res = array(
    'res'=> 'error'
    );

    return json_encode($res);

}

public static function crear_directorio_si_no_existe($ruta_fisica) {

    if (!file_exists($ruta_fisica)) {
        mkdir($ruta_fisica, 0777, true);
    }    

}

public static function conv_float($numero) {

    $res = number_format((float)$numero,2, '.', '');
    return $res;

}


public static function arr2textTable($a, $b = array(), $c = 0) {
    $d = array();
    $e = "+";
    $f = "|";
    $g = 0;
    foreach ($a as $h)
        foreach ($h AS $i => $j) {
            $j = substr(str_replace(array("\n","\r","\t","  "), " ", $j), 0, 48);
            $k = strlen($j);
            $l = strlen($i);
            $k = $l > $k ? $l : $k;
            if (!isset($d[$i]) || $k > $d[$i])
                $d[$i] = $k;
        }
    foreach ($d as $m => $h) {
        $e .= str_pad("", $h + 2, "-") . "+";
            if (strlen($m) > $h)
                $m = substr($m, 0, $h - 1);
            $f .= " " . str_pad($m, $h, " ", isset($b[$g]) ? $b[$g] : $c) . " |";
            $g++;
    }
    $n = "{$e}\n{$f}\n{$e}\n";
    foreach ($a as $h) {
        $n .= "|";
        $g = 0;
        foreach ($h as $i => $o) {
            $n .= " " . str_pad($o, $d[$i], " ", isset($b[$g]) ? $b[$g] : $c) . " |";
            $g++;
        }
        $n .= "\n";
    }
    $p = array(
        "`((?:https?|ftp)://\S+[[:alnum:]]/?)`si",
        "`((?<!//)(www\.\S+[[:alnum:]]/?))`si"
    );
    $q = array(
        "<a href=\"$1\" rel=\"nofollow\">$1</a>",
        "<a href=\"http://$1\" rel=\"nofollow\">$1</a>"
    );
    return preg_replace($p, $q, "{$n}{$e}\n");
}

public static function conv_float_coma($numero) {

    $res = number_format((float)$numero,2);
    return $res;

}

public static function anio_actual()
{
    $fecha_actual = date("Y-m-d");
    $anio = date("o", strtotime($fecha_actual));
    return $anio;
}

public static function eliminar_todos_archivos($ruta_fisica_directorio) {
  $files = glob($ruta_fisica_directorio); // get all file names

  foreach($files as $file){ // iterate files
    if(is_file($file))
      unlink($file); // delete file
  }
}

public static function zeros($numero, $cantidad) {
    $res = str_pad($numero, $cantidad, '0', STR_PAD_LEFT);
    return $res;
}



public static function nombre_mes($numero_mes) {

    $l_mes = "";

    switch ($numero_mes){
    case "01"; $l_mes = "Enero"; break;
    case "02"; $l_mes = "Febrero"; break;
    case "03"; $l_mes = "Marzo"; break;
    case "04"; $l_mes = "Abril"; break;
    case "05"; $l_mes = "Mayo"; break;
    case "06"; $l_mes = "Junio"; break;
    case "07"; $l_mes = "Julio"; break;
    case "08"; $l_mes = "Agosto"; break;
    case "09"; $l_mes = "Setiembre"; break;
    case "10"; $l_mes = "Octubre"; break;
    case "11"; $l_mes = "Noviembre"; break;
    case "12"; $l_mes = "Diciembre"; break;
    }

    return $l_mes;

}

public static function ultimo_dia($fecha) {
    return date("t", strtotime($fecha));
}

public static function conv_on_off($valor){
    $res = 0;
    if($valor == "on") {
        $res = 1;
    } 

    return $res;
}

public static function mes($fecha)
{
    $mes = date("m", strtotime($fecha));
    return $mes;
}

public static function anio($fecha)
{
    $anio = date("o", strtotime($fecha));
    return $anio;
}

public static function base64_to_jpeg($base64_string, $output_file) {
    // open the output file for writing
    $ifp = fopen( $output_file, 'wb' );

    // split the string on commas
    // $data[ 0 ] == "data:image/png;base64"
    // $data[ 1 ] == <actual base64 string>
    $data = explode( ',', $base64_string );

    // we could add validation here with ensuring count( $data ) > 1
    fwrite( $ifp, base64_decode($data[1]));
    
    // clean up the file resource
    fclose( $ifp ); 
    return $output_file; 
}  

public static function count_decimals($x) {
   return strlen(substr(strrchr($x+"", "."), 1));
}

/*ej:
$lista = array();
array_push($lista, 14);
array_push($lista, 16);
array_push($lista, 18);
array_push($lista, 20);
array_push($lista, 22);
array_push($lista, 29);

$prob = util::elegir_elemento($lista);
*/
public static function elegir_elemento($lista) {

    $cant_elem = count($lista);

    $indx = self::min_max_numero(0, $cant_elem - 1);

    return $lista[$indx];
}

public static function eliminar_directorio_completo($dir, $remove = false) {
    
     $structure = glob(rtrim($dir, "/").'/*');

     if (is_array($structure)) {
        foreach($structure as $file) {
            if (is_dir($file))
                self::eliminar_directorio_completo($file,true);
            else if(is_file($file))
                unlink($file);
        }
     }

     if($remove) rmdir($dir);
}

public static function url_procesado()
{
    return "";
}

public static function completarFechaHora($hora) {
    // Obtener la fecha actual en formato Y-m-d
    $fechaActual = date('Y-m-d');
    
    // Validar el formato de la hora usando expresiones regulares
    if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora)) {
        return false; // Retorna false si el formato de la hora no es válido
    }

    // Concatenar la fecha actual con la hora proporcionada
    $fechaHoraCompleta = $fechaActual . ' ' . $hora . ':00'; // Agregar segundos

    return $fechaHoraCompleta;
}

public static function fecha_barra($fecha)
{
    $fecha = substr($fecha,0,10);
    list($anio,$mes,$dia)=explode("-",$fecha);
    $fecha = "$dia/$mes/$anio";
    return $fecha;
}

public static function fecha_inicio($fecha) {
    return $fecha . " 00:00:00";
}

public static function fecha_termino($fecha) {
    return $fecha . " 23:59:59";
}

public static function encrypt($string, $key) {
    $method = 'AES-256-CBC';
    $key = hash('sha256', $key, true); // 256-bit key
    $iv = openssl_random_pseudo_bytes(16); // 16 bytes IV para AES-256-CBC

    $encrypted = openssl_encrypt($string, $method, $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($iv . $encrypted);
}

public static function decrypt($encrypted, $key) {

    if (!is_string($encrypted) || trim($encrypted) === '') {
        return null;
    }

    $method = 'AES-256-CBC';
    $key = hash('sha256', $key, true);

    $data = base64_decode($encrypted, true);

    if ($data === false || strlen($data) < 17) {
        return null;
    }

    $iv = substr($data, 0, 16);
    $ciphertext = substr($data, 16);

    return openssl_decrypt($ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv);
}

    public static function url_amigable($str) {
        // Limpieza previa
        $str = trim($str);
        $str = mb_strtolower($str, 'UTF-8');

        // Reemplazo manual de caracteres especiales comunes
        $str = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'ü'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'u'],
            $str
        );

        // Eliminar caracteres no deseados (mantener letras, números y espacios)
        $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
        $str = preg_replace('/\s+/', ' ', $str); // Espacios múltiples a uno solo
        $str = str_replace(' ', '-', $str);      // Espacios por guiones

        return trim($str, '-'); // Eliminar guiones al inicio o final
    }

    public static function convertirEmojisAEntidadesHtml(string $texto): string {
        $convmap = [
            0x1F300, 0x1FAFF,   // Bloque completo de emojis comunes (Smileys, Gestos, Símbolos, Bandera, etc.)
            0,       0xFFFF
        ];
        return mb_encode_numericentity($texto, $convmap, 'UTF-8');
    }

    public static function _es_apache(): bool {
        $sv = strtolower($_SERVER['SERVER_SOFTWARE'] ?? '');
        if ($sv) {
            // Coincide con Apache/2.x, Apache, httpd, etc.
            return (strpos($sv, 'apache') !== false) || (strpos($sv, 'httpd') !== false);
        }
        // Fallbacks por si SERVER_SOFTWARE no está
        return function_exists('apache_get_version') || getenv('APACHE_RUN_USER');
    }



// ++++++++++++++
// ++ END UTIL ++
// ++++++++++++++
}

