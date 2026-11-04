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
    private static $lorem = array('lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'praesent', 'interdum', 'dictum', 'mi', 'non', 'egestas', 'nulla', 'in', 'lacus', 'sed', 'sapien', 'placerat', 'malesuada', 'at', 'erat', 'etiam', 'josefina', 'velit', 'finibus', 'viverra', 'maecenas', 'mattis', 'volutpat', 'justus', 'vitae', 'vestibulum', 'metus', 'lobortis', 'mauris', 'luctus', 'leo', 'feugiat', 'nibh', 'tincidunt', 'a', 'integer', 'facilisis', 'lacinia', 'ligula', 'ac', 'suspendisse', 'eleifend', 'nunc', 'nec', 'pulvinar', 'quisque', 'ut', 'semper', 'auctor', 'tortor', 'mollis', 'est', 'tempor', 'scelerisque', 'venenatis', 'quis', 'ultrices', 'tellus', 'nisi', 'phasellus', 'aliquam', 'molestie', 'purus', 'convallis', 'cursus', 'ex', 'massa', 'fusce', 'felis', 'fringilla', 'faucibus', 'varius', 'ante', 'primis', 'orci', 'et', 'posuere', 'cubilia', 'curae', 'proin', 'ultricies', 'hendrerit', 'ornare', 'augue', 'pharetra', 'dapibus', 'nullam', 'sollicitudin', 'euismod', 'eget', 'pretium', 'vulputate', 'urna', 'arcu', 'porttitor', 'quam', 'condimentum', 'consequat', 'tempus', 'hac', 'habitasse', 'platea', 'dictumst', 'sagittis', 'gravida', 'eu', 'commodo', 'dui', 'lectus', 'vivamus', 'libero', 'vel', 'maximus', 'pellentesque', 'efficitur', 'pletora', 'aptent', 'taciti', 'sociosqu', 'amarilis', 'litora', 'torquent', 'bianca', 'conubia', 'nostra', 'inceptos', 'himenaeos', 'fermentum', 'turpis', 'donec', 'magna', 'porta', 'enim', 'curabitur', 'odio', 'rhoncus', 'blandit', 'potenti', 'sodales', 'accumsan', 'congue', 'neque', 'duis', 'bibendum', 'laoreet', 'elementum', 'suscipit', 'diam', 'vehicula', 'eros', 'nam', 'imperdiet', 'sem', 'ullamcorper', 'dignissim', 'risus', 'aliquet', 'habitant', 'morbi', 'tristique', 'senectus', 'netus', 'females', 'nisl', 'iaculis', 'cras', 'Azahar', 'Floris', 'Jardín', 'Orquídea', 'Tulipán', 'Begonia', 'Magnolia', 'Azucena', 'Girasol', 'Jasmin', 'Amapola', 'Violeta', 'Dalia', 'Hortensia', 'Lirio', 'Camelia', 'Narciso', 'Margarita', 'Petunia', 'Pensamiento', 'Anturio', 'Cala', 'Gladiolo', 'Mimosa', 'Peonía', 'Geranio', 'Verbena', 'Lavanda', 'Clavel', 'Cerezo', 'Bambú', 'Sauce', 'Helecho', 'Olivo', 'Roble', 'Ciprés', 'Almendro', 'Eucalipto', 'Secoya', 'Pino', 'Palmera', 'Cedro', 'Jazmín', 'Bonsái', 'Hiedra', 'Musgo', 'Secoya', 'Nopal', 'Olmo', 'Loto', 'Verde', 'Celeste', 'Amarillo', 'Púrpura', 'Carmín', 'Azul', 'Dorado', 'Plateado', 'Turquesa', 'Coral', 'Escarlata', 'Violeta', 'Magenta', 'Cian', 'Rosa', 'Marfil', 'Índigo', 'Ámbar', 'Esmeralda', 'Rubí', 'Gris', 'Blanco', 'Negro', 'Bordó', 'Granate', 'Siena', 'Ocre', 'Crema', 'Lila', 'Malva', 'Cosmos', 'Universo', 'Planeta', 'Galaxia', 'Estrella', 'Cometa', 'Nebulosa', 'Órbita', 'Satélite', 'Eclipse', 'Constelación', 'Vía Láctea', 'Polvo estelar', 'Súpernova', 'Cúmulo', 'Cielo', 'Sol', 'Luna', 'Atmósfera', 'Horizonte');
}