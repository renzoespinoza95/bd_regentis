$(document).on('click', '.sel-fila tbody tr', function () {

  const $tabla = $(this).closest('table');

  // quitar selección solo dentro de esa tabla
  $tabla.find('tbody tr').removeClass('fila-seleccionada');

  // agregar selección a la fila clickeada
  $(this).addClass('fila-seleccionada');

}); 

function agregarScrollBotones(tabla){

  const $wrapper = tabla.closest('.dataTables_wrapper');
  const $scroll = $wrapper.find('.dataTables_scroll');

  if (!$scroll.length) return;

  if ($scroll.find('.scroll-btn-left').length) return;

  const btnLeft = $('<div class="scroll-btn-left"><i class="fa fa-angle-left"></i></div>');
  const btnRight = $('<div class="scroll-btn-right"><i class="fa fa-angle-right"></i></div>');

  $scroll.css('position','relative'); // 🔥 clave

  $scroll.append(btnLeft, btnRight);

  const $scrollBody = $wrapper.find('.dataTables_scrollBody');

  btnLeft.on('click', () => {
    $scrollBody.animate({ scrollLeft: $scrollBody.scrollLeft() - 200 }, 200);
  });

  btnRight.on('click', () => {
    $scrollBody.animate({ scrollLeft: $scrollBody.scrollLeft() + 200 }, 200);
  });

}