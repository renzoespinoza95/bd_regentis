<?php
class h2 {
/* 
USO
===
$lista_tarifas = mud_tarifas::lista_tarifas_clasico();

echo h2::cbo(array(
    'id'=>'cbo_tarifas_id_agregar',
    'name'=>'cbo_tarifas_id_agregar',
    'lista_item_clasico'=> $lista_tarifas,
    'item_clasico_id'=>'tarifas_id',
    'palabra_latina'=>1,
    'item_clasico_descripcion'=> 'nombre_tarifa',
    'item_seleccionado_id'=> 1
));
*/    
public static function cbo($datos) {
    // Atributos para el <select>
    $attributes = [
        'id' => isset($datos['id']) ? $datos['id'] : null,
        'name' => isset($datos['name']) ? $datos['name'] : null,
        'class' => isset($datos['class']) ? $datos['class'] : 'chzn-select',
        'style' => isset($datos['width']) ? "width: {$datos['width']}" : "width: 100%",
        'multiple' => isset($datos['multiple']) ? 'multiple' : null,
    ];

    // Iniciar la etiqueta <select> con atributos generados
    $select = "<select " . self::parseAttributes($attributes) . ">";

    // Procesar las opciones
    $options = "";
    foreach ($datos['lista_item_clasico'] as $elemento) {
        // Determinar si esta opción debe estar seleccionada
        $isSelected = false;

        if (isset($datos['lista_item_seleccionado']) && is_array($datos['lista_item_seleccionado'])) {
            foreach ($datos['lista_item_seleccionado'] as $seleccionado) {
                if ($elemento[$datos['item_clasico_id']] == $seleccionado[$datos['item_clasico_id']]) {
                    $isSelected = true;
                    break;
                }
            }
        } elseif (isset($datos['item_seleccionado_id'])) {
            if ($elemento[$datos['item_clasico_id']] == $datos['item_seleccionado_id']) {
                $isSelected = true;
            }
        }

        // Obtener el texto de la opción
        if (isset($datos['palabra_latina'])) {
            $texto_item = util::mostrar_palabra_latina($elemento[$datos['item_clasico_descripcion']]);
        } else {
            $texto_item = isset($datos['normalize']) 
                ? util::normalize_nombres($elemento[$datos['item_clasico_descripcion']]) 
                : $elemento[$datos['item_clasico_descripcion']];
        }

        // Construir la opción
        $optionAttributes = [
            'value' => $elemento[$datos['item_clasico_id']],
            'selected' => $isSelected ? 'selected' : null,
        ];
        $options .= "<option " . self::parseAttributes($optionAttributes) . ">" . $texto_item . "</option>" . PHP_EOL;
    }

    // Agregar opción vacía si corresponde
    if (isset($datos['vacio'])) {
        $options .= "<option value='0'></option>" . PHP_EOL;
    }

    // Cerrar la etiqueta <select>
    $select .= $options . "</select>";

    // Devolver el combo completo
    return $select;
}

public static function txta($datos) {        
    // Atributos para el <textarea>
    $attributes = [
        'disabled' => isset($datos['disabled']) ? 'disabled' : null,
        'data01' => isset($datos['data01']) ? $datos['data01'] : null,
        'data02' => isset($datos['data02']) ? $datos['data02'] : null,
        'class' => isset($datos['class']) ? $datos['class'] : null,
        'placeholder' => isset($datos['placeholder']) ? $datos['placeholder'] : null,
        'id' => isset($datos['id']) ? $datos['id'] : null,
        'name' => isset($datos['name']) ? $datos['name'] : null,
        'type' => isset($datos['type']) ? $datos['type'] : null,
        'rel' => isset($datos['rel']) ? $datos['rel'] : null,
        'value' => isset($datos['value']) ? $datos['value'] : null,
        'autocomplete' => isset($datos['autocomplete']) ? $datos['autocomplete'] : 'off',
    ];

    // Contenido dentro del <textarea>
    $texto = isset($datos['texto']) ? $datos['texto'] : '';

    // Generar el HTML del <textarea>
    $res = "<textarea " . self::parseAttributes($attributes) . ">$texto</textarea>";
    
    return $res;
}
    
public static function txt($datos) {
    $attributes = [
        'type' => isset($datos['type']) ? $datos['type'] : 'text',
        'name' => isset($datos['name']) ? $datos['name'] : '',
        'id' => isset($datos['id']) ? $datos['id'] : '',
        'class' => isset($datos['class']) ? $datos['class'] : '',
        'placeholder' => isset($datos['placeholder']) ? $datos['placeholder'] : '',
        'value' => isset($datos['value']) ? $datos['value'] : '',
        'autocomplete' => isset($datos['autocomplete']) ? $datos['autocomplete'] : 'off',
        'disabled' => isset($datos['disabled']) ? 'disabled' : null,
        'checked' => isset($datos['checked']) ? 'checked' : null
    ];
    $attributesString = self::parseAttributes($attributes);

    return "<input $attributesString />";
}

    
 
/*
public static function footer_modal_agregar() {

          $res = <<<EOF
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">
        <i class="fa fa-mail-reply"></i> Cancelar</button>
    <button class="btn btn-primary">
        <i class="fa fa-save"></i> Guardar</button>
  </div>
EOF;
    return $res;
}

public static function botones_facebox() {

          $res = <<<EOF
<div class="form-actions">
             <button type="button" class="btn btn-warning" id="btn_cancelar"><i class="fa fa-mail-reply"></i> Cancelar</button>   
            <button class="btn btn-primary" type="submit">
            <i class="fa fa-save"> </i> Guardar</button>  
</div>
<script type="text/javascript">

</script>
EOF;
    return $res;
}


public static function footer_facebox() {

          $res = <<<EOF
<div class="form-actions">
             <button type="button" class="btn btn-warning" id="btn_cancelar"><i class="fa fa-mail-reply"></i> Cancelar</button>   
            <button class="btn btn-primary" type="submit">
            <i class="fa fa-save"> </i> Guardar</button>  
</div>
<script type="text/javascript">
$('#btn_cancelar').click(function(){
   $.facebox.close();
});
</script>
EOF;
    return $res;
}

*/

public static function todohost($apphost, $varhost, $apihost = null, $adicional = null) {
    include DEFINITION;
      com("TODOHOST");
      $bunny_storage_url = BUNNY_CDN_BASE;
$aplicar = <<<EOF
<script type="text/javascript">
   let apphost = '$apphost';  
   let varhost = '$varhost';
   let apihost = '$apihost';
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

public static function parseAttributes($attributes) {
    $result = [];
    foreach ($attributes as $key => $value) {
        if (!is_null($value) && $value !== "") {
            $result[] = $key . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
    }
    return implode(' ', $result);
}

// ++++++++++++++
// ++ END UTIL ++
// ++++++++++++++
} 
 
