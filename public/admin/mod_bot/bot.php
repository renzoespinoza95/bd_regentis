<?php
Flight::route('GET /tt/tt', function () {
    include DEFINITION;
    $usu_id = 5;    
    dd(veri_membresia($usu_id));    
    // echo poke();
});



// Flight::route('GET /tt/tt', function(){

//     include DEFINITION;

//     DB::query("SET NAMES 'utf8mb4'");

//     try {

//         /* =====================================
//            NEGOCIOS
//         ====================================== */

//         $negocios = DB::query("

//             SELECT

//                 neg_id,
//                 nombre

//             FROM reg_neg

//             WHERE borrado_el IS NULL

//             ORDER BY neg_id ASC

//         ");

//         $procesados = [];

//         /* =====================================
//            RECORRER
//         ====================================== */

//         foreach($negocios as $n){

//             $neg_id = intval(
//                 $n['neg_id']
//             );

//             $r = veri_publico_general(
//                 $neg_id
//             );

//             $procesados[] = [

//                 'neg_id' =>
//                     $neg_id,

//                 'nombre' =>
//                     $n['nombre'],

//                 'resultado' =>
//                     $r

//             ];

//         }

//         /* =====================================
//            RESPONSE
//         ====================================== */

//         Flight::json([

//             'ok' => true,

//             'total_negocios' =>
//                 count($procesados),

//             'data' =>
//                 $procesados

//         ]);

//     } catch(Exception $e){

//         Flight::json([

//             'ok' => false,

//             'msg' => $e->getMessage()

//         ], 500);

//     }

// });


// Flight::route('GET /tt/tt', function () {
//     include DEFINITION;
//     dd(crear_tienda_fantasma());
//     exit;
//     $usu_id = 12;    
//     enviar_boton_tienda($usu_id);
//     echo poke();
// });


Flight::route('GET /tt/xx', function () {
    include DEFINITION;
    $usu_id = 12;
    $imagenes = ['http://localhost:84/bd_landia/a1.jpg','http://localhost:84/bd_landia/a2.jpg','http://localhost:84/bd_landia/a3.jpg'];
    enviar_manual_visual($usu_id, $imagenes);
    echo poke();
});


Flight::route('GET /tt/yy', function () {
    include DEFINITION;
    $usu_id = 12;
    enviar_auto_msg(
        $usu_id,
        'TXT_REGISTRO'
    );
    echo poke();
});

Flight::route('GET /tt/diario', function () {
    include DEFINITION;

    $usu_id = "3";
    $fich_id_dest = "6";

    //diario(1, 'inicio_conversacion', ['fich_id_dest' => 8]);

    //diario($usu_id, 'inicio_sesion', null);

    diario($usu_id, 'nuevo_miembro', null);    

    echo poke();
});
