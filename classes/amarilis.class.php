<?php
class amarilis {


//ej: $fecha = util::fecha_random('02/03/2022', '02/11/2022');
public static function fecha_random($fecha_inicio, $fecha_final) {
    // Explode original
    list($p1_i, $p2_i, $anio_i) = explode('/', $fecha_inicio);
    list($p1_f, $p2_f, $anio_f) = explode('/', $fecha_final);

    // Detectar formato según segunda parte > 12
    if ((int)$p2_i > 12) {
        // era MM/DD/YYYY
        $mes_i = (int)$p1_i;
        $dia_i = (int)$p2_i;
    } else {
        // asumimos DD/MM/YYYY
        $dia_i = (int)$p1_i;
        $mes_i = (int)$p2_i;
    }
    if ((int)$p2_f > 12) {
        $mes_f = (int)$p1_f;
        $dia_f = (int)$p2_f;
    } else {
        $dia_f = (int)$p1_f;
        $mes_f = (int)$p2_f;
    }

    // Años
    $anio_i = (int)$anio_i;
    $anio_f = (int)$anio_f;

    // Año aleatorio
    $anio_random = rand($anio_i, $anio_f);

    // Rango de meses
    $mes_min = ($anio_random === $anio_i) ? $mes_i : 1;
    $mes_max = ($anio_random === $anio_f) ? $mes_f : 12;
    $mes_random = rand($mes_min, $mes_max);

    // Rango de días
    $dia_min = ($anio_random === $anio_i && $mes_random === $mes_i)
        ? $dia_i : 1;
    $dia_max = ($anio_random === $anio_f && $mes_random === $mes_f)
        ? $dia_f
        : cal_days_in_month(CAL_GREGORIAN, $mes_random, $anio_random);
    $dia_random = rand($dia_min, $dia_max);

    // Formatear
    $mes_random = str_pad($mes_random, 2, '0', STR_PAD_LEFT);
    $dia_random = str_pad($dia_random, 2, '0', STR_PAD_LEFT);

    return "{$anio_random}-{$mes_random}-{$dia_random}";
}



public static function hora_random() {
    // Convert to timetamps
    $min = strtotime('2019-01-01');
    $max = strtotime('2019-01-02');

    // Generate random number using above bounds
    $val = rand($min, $max);

    // Convert back to desired date format
    return date('H:i:s', $val);
}

public static function min_max_hora_random($hora_inicio, $hora_termino) {

    $min = strtotime('2019-01-01 ' . $hora_inicio);
    $max = strtotime('2019-01-01 ' . $hora_termino);

    $mCalc = self::min_max_numero($min, $max);

    $res = date("H:i:s",$mCalc);

    return $res;
}  


public static function count_decimals($x) {
   return strlen(substr(strrchr($x+"", "."), 1));
}

/* ej:
var_dump(random(0.001, 0.009)); // 0.004
var_dump(random(0.001, 0.009, 1)); // 0.0046
var_dump(random(0.001, 0.009, 2)); // 0.00458
var_dump(random(0.001, 0.009, 5)); // 0.00458014
*/
public static function decimal_random($min, $max, $precision = 0) {
   $decimals = max(self::count_decimals($min), self::count_decimals($max)) + $precision;
   $factor = pow(10, $decimals);
   return rand($min*$factor, $max*$factor) / $factor;
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

public static function obtenerNombreArchivoSinExtension($nombreCompleto) {
    // Usamos la función pathinfo para obtener información sobre el archivo
    $infoArchivo = pathinfo($nombreCompleto);

    // Extraemos el nombre del archivo sin la extensión
    $nombreSinExtension = $infoArchivo['filename'];

    return $nombreSinExtension;
}


public static function convertirCadenaAArray($cadena) {

    $arrayResultante = explode(';', $cadena);
    return $arrayResultante;
}

public static function truncarFrase($frase, $maxCaracteres = 160) {
    // Verificar si la longitud de la frase es menor o igual al límite
    if (strlen($frase) <= $maxCaracteres) {
        return $frase;
    }

    // Truncar la frase a la longitud máxima
    $fraseRecortada = substr($frase, 0, $maxCaracteres);

    // Encontrar la última palabra completa
    $ultimaPalabra = strrpos($fraseRecortada, ' ');

    // Verificar si se encontró una última palabra completa
    if ($ultimaPalabra !== false) {
        $fraseRecortada = substr($fraseRecortada, 0, $ultimaPalabra);
    }

    return $fraseRecortada;
}


public static function flor($cantidad) {

    $validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ1234567890";
    $validCharNumber = strlen($validCharacters);
    $result = "";

    for ($i = 0; $i < $cantidad; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);
        $result .= $validCharacters[$index];

    }
    return $result;
}

public static function texto_random($cantidad) {

    $validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ";
    $validCharNumber = strlen($validCharacters);
    $result = "";

    for ($i = 0; $i < $cantidad; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);
        $result .= $validCharacters[$index];

    }
    return $result;
}

public static function numero_random($cantidad) {

    $validCharacters = "1234567890";
    $validCharNumber = strlen($validCharacters);
    $result = "";

    for ($i = 0; $i < $cantidad; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);
        $result .= $validCharacters[$index];

    }
    return $result;
}

public static function max_numero($cantidad, $maximo, $cero = 0) {
    
    if($cero) {
        $num_tempo = self::numero_random($cantidad);
    } else {
        $num_tempo = self::numero_random($cantidad);
        while ($num_tempo == 0) {
            $num_tempo = self::numero_random($cantidad);
        }
    }

    if($num_tempo > $maximo) {        
            return self::max_numero($cantidad, $maximo);
    } else {       
            return $num_tempo;
    }

}

public static function min_max_numero($minimo, $maximo) { 

    return mt_rand($minimo, $maximo);
}    

public static function si_no() {
    $numero = (int)self::numero_random(2);
    $res = 0;

    if($numero%2) {
        $res = 1;
    } else {
        $res = 0;
    }

    return $res;
}

/**
 * Genera una coordenada aleatoria a una distancia máxima dada (en km)
 * respecto a una lat/lng de referencia.
 *
 * @param float $lat0   Latitud de referencia (grados)
 * @param float $lng0   Longitud de referencia (grados)
 * @param float $radius Radio máximo en kilómetros (por defecto 1 km)
 * @return array ['map_lat' => float, 'map_lng' => float]
 */
public static function random_maps(
    float $lat0  = -12.050783175064453,
    float $lng0  = -77.03881875997209,
    float $radius = 1.0
): array {

    // Radio medio terrestre en km
    $earthRadius = 6371.0;

    // Distancia aleatoria (0 – $r) y rumbo aleatorio (0 – 2π)
    $distance = $radius * (mt_rand() / mt_getrandmax());
    $bearing  = 2 * M_PI * (mt_rand() / mt_getrandmax());

    // Conversión a radianes
    $lat0Rad = deg2rad($lat0);
    $lng0Rad = deg2rad($lng0);
    $angularDistance = $distance / $earthRadius;

    // Fórmulas de desplazamiento sobre esfera
    $latRad = asin(
        sin($lat0Rad) * cos($angularDistance) +
        cos($lat0Rad) * sin($angularDistance) * cos($bearing)
    );

    $lngRad = $lng0Rad + atan2(
        sin($bearing) * sin($angularDistance) * cos($lat0Rad),
        cos($angularDistance) - sin($lat0Rad) * sin($latRad)
    );

    // Normalizar longitud (–π a π)
    $lngRad = fmod($lngRad + M_PI, 2 * M_PI) - M_PI;

    // Convertir de nuevo a grados y devolver array
    return [
        'map_lat' => rad2deg($latRad),
        'map_lng' => rad2deg($lngRad),
    ];
}


public static function dos_numeros(mixed $numero1, mixed $numero2): mixed {
    // Array con las dos opciones
    $opciones = [$numero1, $numero2];
    // Elige un índice aleatorio (0 o 1)
    $indice  = random_int(0, 1);
    return $opciones[$indice];
}

//+++++++++++++++
//+  FIN CLASS  +
//+++++++++++++++
}

