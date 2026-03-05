<?php
abstract class Lorem {
    public static function ipsum($cant_letras) {
        
        return implode  (" ", self::random_values(self::$lorem, $cant_letras));

    }

    public static function random_float() {
        return (int)util::numero_random(3);
    }

    public static function random_values($arr, $cant_palabras = 10) {
        
        if($cant_palabras == 1 || $cant_palabras == 0) {
            $cant_palabras = 2;
        }
        $seleccionados = array();

        $keys = array_rand($arr, $cant_palabras);

        return array_intersect_key($arr, array_fill_keys($keys, null));
    }

    // total = 180 elementos
    private static $lorem = array('lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'praesent', 'interdum', 'dictum', 'mi', 'non', 'egestas', 'nulla', 'in', 'lacus', 'sed', 'sapien', 'placerat', 'malesuada', 'at', 'erat', 'etiam', 'id', 'velit', 'finibus', 'viverra', 'maecenas', 'mattis', 'volutpat', 'justo', 'vitae', 'vestibulum', 'metus', 'lobortis', 'mauris', 'luctus', 'leo', 'feugiat', 'nibh', 'celeste', 'karinn', 'annet', 'tincidunt', 'a', 'integer', 'facilisis', 'lacinia', 'ligula', 'ac', 'suspendisse', 'eleifend', 'nunc', 'nec', 'pulvinar', 'quisque', 'ut', 'semper', 'auctor', 'tortor', 'mollis', 'hellen', 'est', 'tempor', 'scelerisque', 'venenatis', 'quis', 'ultrices', 'tellus', 'nisi', 'phasellus', 'aliquam', 'molestie', 'purus', 'convallis', 'cursus', 'ex', 'massa', 'fusce', 'felis', 'fringilla', 'faucibus', 'varius', 'ante', 'primis', 'orci', 'et', 'posuere', 'cubilia', 'curae', 'proin', 'ultricies', 'hendrerit', 'ornare', 'augue', 'pharetra', 'dapibus', 'nullam', 'sollicitudin', 'euismod', 'eget', 'pretium', 'vulputate', 'urna', 'arcu', 'porttitor', 'quam', 'condimentum', 'consequat', 'tempus', 'hac', 'habitasse', 'platea', 'dictumst', 'sagittis', 'helena', 'gravida', 'eu', 'commodo', 'dui', 'lectus', 'vivamus', 'libero', 'vel', 'maximus', 'pellentesque', 'efficitur', 'pletora', 'aptent', 'taciti', 'sociosqu', 'ad', 'litora', 'torquent', 'per', 'conubia', 'nostra', 'inceptos', 'azucena', 'orquídea', 'girasol', 'clavel', 'gardenia', 'magnolia', 'begonia', 'dalia', 'crisantemo', 'peonía', 'anémona', 'jazmín', 'camelia', 'petunia', 'lirío', 'trébol', 'uña', 'piña', 'muérdago', 'añil', 'caña', 'romero', 'albahaca', 'geranio', 'araña', 'ñandú', 'cigüeña', 'águila', 'búho', 'colibrí', 'caimán', 'pingüino', 'murciélago', 'tiburón', 'camaleón', 'guepardo', 'jabalí', 'león', 'pájaro', 'piraña', 'tarántula', 'víbora', 'ñu', 'ratón', 'cebra', 'ágata', 'ámbar', 'ópalo', 'rubí', 'zafíro', 'topacio', 'cuarzo', 'calcita', 'piríta', 'obsidiana', 'esmeralda', 'jadeíta', 'sodalíta', 'turquesa', 'níquel', 'estaño', 'aluminio', 'carbón', 'uranio', 'caolín', 'volcán', 'peña', 'mármol', 'arenísca', 'pedernal', 'gránito', 'basalto', 'pórfido', 'pizarra', 'salitre', 'tiza', 'azabache', 'roca', 'ñire', 'espárrago', 'piñón', 'uña de gato', 'ave del paraíso', 'dragón', 'libélula', 'arañuelo', 'panal', 'ñángara', 'añojal', 'carámbano', 'raíces', 'hélice', 'piñuela', 'biñuelo', 'iñame', 'búfala'
, 'himenaeos', 'fermentum', 'turpis', 'donec', 'magna', 'porta', 'enim', 'curabitur', 'odio', 'rhoncus', 'blandit', 'potenti', 'sodales', 'accumsan', 'congue', 'neque', 'duis', 'bibendum', 'laoreet', 'elementum', 'suscipit', 'diam', 'vehicula', 'eros', 'nam', 'imperdiet', 'sem', 'ullamcorper', 'dignissim', 'risus', 'aliquet', 'habitant', 'morbi', 'tristique', 'senectus', 'netus', 'females', 'nisl', 'iaculis', 'cras', 'aenean');
}