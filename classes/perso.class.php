<?php
class perso {

  protected static $boot;
  protected static $publico;
  public static $varhost;

  public static function config($varhost) {
      self::$boot = $varhost . "/public/bootstrap";  
      self::$varhost = $varhost;
  }
  
  public static function start() {
  
  com("Bootstrap");
  echo "<link href='" . self::$boot . "/assets/css/bootstrap2.css' rel='stylesheet'>";
  n();
  
  echo "<link href='" . self::$boot . "/assets/css/bootstrap-responsive.css' rel='stylesheet'>";
  n();    
    
  }
  
  
  public static function favicon()
  {
    com("Favicon");     
    echo "<link rel='shortcut icon' href='" . self::$varhost . "/public/ico/favicon.png'>";
    n();
  }

  public static function jquery2() 
  {
   com("boot jquery 2.0.0");
   echo "<script src='" . self::$boot . "/assets/js/jquery2.0.0.js'></script>" . "\n";  
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

	public static function vuejs2() {
	    com("VUEJS2");
	    js(self::$boot . "/vuejs2/vue.min.js");
	    js(self::$boot . "/vuejs2/axios.min.js");
	    n();      
	}

  public static function apprise()
  {
    com("Apprise");
    css(self::$boot . "/apprise/apprise.min.css");
    js(self::$boot . "/apprise/apprise.js");
  }

public static function global_env($apphost, $varhost, $adicional = null) {
    include DEFINITION;
      com("global_env");
      $bunny_storage_url = BUNNY_CDN_BASE;
$aplicar = <<<EOF
<script type="text/javascript">
   let apphost = '$apphost';  
   let varhost = '$varhost';
   let cdn_base_url = '$bunny_storage_url';
   let adminhost = varhost + '/public/admin/';
   $adicional
   let dt_language = {
    search: "Buscar:",
    lengthMenu: "Mostrar _MENU_ registros",
    info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
    infoEmpty: "Mostrando 0 a 0 de 0 registros",
    infoFiltered: "(filtrado de _MAX_ registros)",
    loadingRecords: "Cargando...",
    zeroRecords: "No se encontraron resultados",
    emptyTable: "No hay datos disponibles",
    paginate: {
      first: "Primero",
      last: "Último",
      next: "Siguiente",
      previous: "Anterior"
    }
  };

(function() {

  const token = localStorage.getItem('jwt');

  if (!token) {
    // Si no hay token → redirige al login
    console.log("Acceso No Autorizado");
    return;
  }

  // Configura axios globalmente
  axios.defaults.headers.common['Authorization'] = 'Bearer ' + token;

})();  
</script>
EOF;
    echo $aplicar;
    n(); 
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

public static function preparar_para_encriptar($string) {
    return $string."*";
}

  public static function vue_select() {
      com("vue-select");
      js(self::$boot . "/vue-select/vue-select-2-6-4.min.js");
      n();      
  }  

  public static function font_awesome()
  {
    com("Font Awesome");
    css(self::$boot . "/font-awesome/css/font-awesome.css");
  }

  public static function js()
  {
    com("Bootstrap JS");
    js(self::$boot . "/assets/js/bootstrap2.3.2.js");
  }

  public static function block_ui()
  {
    com("Block UI");
    js(self::$boot . "/block_ui/jquery.blockUI.js");
    n();
  }

  public static function perso()
  {
  com("PERSO");
  css(self::$boot . "/perso/perso.css");
  js(self::$boot . "/perso/perso.js");
  }

  public static function datatables()
  {
    com("datatables");
    js(self::$boot . "/datatables/jquery.dataTables.js");
    css(self::$boot . "/datatables/jquery.dataTables.css");
    n();      
  }     

  public static function summernote() {
      com("summernote");
      js(self::$boot . "/summernote/summernote-lite.min.js");
      css(self::$boot . "/summernote/summernote-lite.min.css");
      n();      
  }  

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
  
// +++++++++++++
// | END PERSO |
// +++++++++++++

}