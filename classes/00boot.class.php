<?php
class boot {

// INICIO

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
  
  
  public static function facebox()
  {
    com("Facebox");
    $mi_boot = self::$boot;
    echo "<script src='" . $mi_boot . "/facebox/facebox.js'></script>";
    n();
    echo "<link rel='stylesheet' href='" . $mi_boot . "/facebox/facebox.css' type='text/css'>";
    n();
  $aplicar = <<<EOF
  <script type="text/javascript">
  $(document).ready(function(){
// Facebox

  $('a[rel*=facebox]').facebox({
    loadingImage : '$mi_boot/facebox/loading.gif',
    closeImage   : '$mi_boot/facebox/closelabel.png'
  });
  });
</script>
EOF;
    echo $aplicar;
    n();
    
  }
  
  public static function img_input()
  {
    com("Img Input");
    $mi_boot = self::$boot;
    echo "<script src='" . $mi_boot . "/img_input/jquery.img-preview.js'></script>";
    n();
    echo "<link rel='stylesheet' href='" . $mi_boot . "/facebox/facebox.css' type='text/css'>";
    n();
  $aplicar = <<<EOF
  <script type="text/javascript">

   $(function() {
    $('.file-input').imgPreview(
      {
        warning_message:"No es un archivo de imagen",
        max_size: 1500999
      }
    );
});

</script>
EOF;
    echo $aplicar;
    n();
    
  }  
 
  
public static function block_ui()
  {
    com("Block UI");
    js(self::$boot . "/block_ui/jquery.blockUI.js");
    n();
  }

public static function tinymce($alto = "200")
{
    com("tinymce");
    js(self::$boot . "/tinymce/tinymce.min.js");  

  $aplicar = <<<EOF
  <script type="text/javascript">
tinymce.init({
  selector: ".tinymce",
  plugins: [
    "code ",
    "paste"
  ],
  toolbar: "undo redo | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link code ",
  menubar:false,
  statusbar: false,
  height: $alto,
  width: '100%'
});


$( document ).ready(function() {  
  $(document).on('click', '.saveButton', function() {
    console.log(this.id);
    var clickedId = this.id;
    if(clickedId == 'preview'){
      if(tinymce.activeEditor.getContent()) {
        $('#previewModal').modal({
          backdrop: 'static',
          keyboard: false
        });
      }
    } else if(clickedId == 'save') {
      $('#codeForm').submit();      
    } 
  }); 
  
  $(document).on('click', '#cancel', function() {
    $('#previewModal').modal('hide');   
  });
  
  $("#previewModal").on("shown.bs.modal", function () { 
    $('#previewCode').html(tinymce.activeEditor.getContent());
  });
});
</script>
EOF;
    echo $aplicar;
    n();
  }  
  

public static function filtering()
{
    com("Filtering");
    js(self::$boot . "/filtering/qjsearch.js");
  $aplicar = <<<EOF
  <script type="text/javascript">
// filtering

$('.filtering').qjsearch();
</script>
EOF;
    echo $aplicar;
    n();      
  }  

  
  public static function js_datepicker()
  {
      $aplicar = <<<EOF
  <script type="text/javascript">
$(function () {

$.datepicker.regional['es'] = {closeText: 'Cerrar',
 prevText: '<Ant',
 nextText: 'Sig>',
 currentText: 'Hoy',
 monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
 monthNamesShort: ['Ene','Feb','Mar','Abr', 'May','Jun','Jul','Ago','Sep', 'Oct','Nov','Dic'],
 dayNames: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
 dayNamesShort: ['Dom','Lun','Mar','Mié','Juv','Vie','Sáb'],
 dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sá'],
 weekHeader: 'Sm',
 dateFormat: 'dd/mm/yy',
 firstDay: 1,
 isRTL: false,
 showMonthAfterYear: false,
 yearSuffix: ''
 };          
   
$.datepicker.setDefaults($.datepicker.regional["es"]);
$(".datepicker").datepicker(
{
//dateFormat: "yy-mm-dd", //<----here
  dateFormat: "dd/mm/yy", //<----here
});
});
</script>
EOF;
    echo $aplicar;      
  }   
  
    
  public static function tt()
  {
    com("Tooltip");
    $mi_boot = self::$boot;    
  $aplicar = <<<EOF
  <script type="text/javascript">
  
    $(document).ready(function(){
        $(".tt").tooltip({ placement: 'top'});
    });
          
  </script>
EOF;
    echo $aplicar;
    n();    
  }
  
  
  public static function apprise()
  {
    com("Apprise");
    css(self::$boot . "/apprise/apprise.min.css");
    js(self::$boot . "/apprise/apprise.js");
  }

  public static function notify()
  {
    com("Notify");
    js(self::$boot . "/notify/js/jquery_notification_v.1.js");
    css(self::$boot . "/notify/css/jquery_notification.css");
    
    $mi_boot =self::$boot;
    
      $aplicar = <<<EOF
      
<script type="text/javascript">    
    
    function mostrar_mensaje_error(){
        showNotification({
            type : "error",
            autoClose: true,
            duration: 4,
            message: "Los datos ingresados no son correctos!"
        });    
    } 

</script>

<div class="hidden">
	<script type="text/javascript">

			if (document.images) {
				img1 = new Image();
				img2 = new Image();
				img3 = new Image();
        img4 = new Image();

				img1.src = "$mi_boot/notify/images/error_bg.png";
				img2.src = "$mi_boot/notify/images/info_bg.png";
				img3.src = "$mi_boot/notify/images/succ_bg.png";
        img4.src = "$mi_boot/notify/images/warn_bg.png";
			}

	</script>
</div>   

EOF;
    echo $aplicar;
    n();

    
  } 

  
public static function uniform()
  {
    com("Uniform");
    css(self::$boot . "/uniform/css/ready.uni-form.css");
    js(self::$boot . "/uniform/js/uni-form-validation.jquery.js");
    js(self::$boot . "/uniform/localization/es.js");  
   $aplicar = <<<EOF
<script type="text/javascript">
$(function(){
  $('.uniForm').uniform({
    prevent_submit : true
  });
}); 
</script>
EOF;
    echo $aplicar;
    n();    
  }

  public static function js()
  {
    com("Bootstrap JS");
    js(self::$boot . "/assets/js/bootstrap2.3.2.js");
  }
  
  public static function jpages()
  {
    com("JPages");
    css(self::$boot . "/jpages/css/jPages.css");
    js(self::$boot . "/jpages/js/jPages.js");
  }
  
  public static function taffy()
  {
    com("Taffy");    
    js(self::$boot . "/taffy/taffy.js");
  }
  
  public static function flavius()
  {
    com("Flavius Pagination");
    js(self::$boot . "/flaviusmatis/jquery.simplePagination.js");
    css(self::$boot . "/flaviusmatis/simplePagination.css");  
  }
  
  public static function font_awesome()
  {
    com("Font Awesome");
    css(self::$boot . "/font-awesome/css/font-awesome.css");
  }
  
  public static function chosen()
  {
    com("Chosen");
    css(self::$boot . "/chosen/chosen/chosen.css");
    js(self::$boot . "/chosen/chosen/chosen.jquery.js"); 
    n();    
   $codex = <<<EOF
<script type="text/javascript">
  // Simple select Chosen
  $('.chzn-select').chosen({
    no_results_text: "Ninguno encontrado"
  });  
</script>
EOF;
    echo $codex;
    n();    
  }
  
public static function mostrar_mensaje_exito()
{     
    com("mostrar_mensaje_exito");
    css(self::$boot . "/notify/css/jquery_notification.css");
    js(self::$boot . "/notify/js/jquery_notification_v.1.js"); 
   $aplicar = <<<EOF
  <script type="text/javascript">
var  mostrar_mensaje_exito = function(){
    showNotification({
        type : "sucess",
        autoClose: true,
        duration: 4,
        message: "Cambios efectuados exitosamente"
    });    
}
</script>
EOF;
    echo $aplicar;
    n();   
  }
  

public static function real_captcha()
  {     
    com("Real Captcha");
    css(self::$boot . "/real_captcha/jquery.realperson.css");
    js(self::$boot . "/real_captcha/jquery.plugin.js"); 
    js(self::$boot . "/real_captcha/jquery.realperson.js");
   $aplicar = <<<EOF
  <script type="text/javascript">
$(function() {
	$('#verificacion_visual').realperson();
});
</script>
EOF;
    echo $aplicar;
    n();   
  }  

  public static function masked()
  {
    com("Masked");
    js(self::$boot . "/masked/masked.js");
       $aplicar = <<<EOF
<script type="text/javascript">
        jQuery(function($){
              $('.masked').mask("99/99/9999");
        });
</script>
EOF;
    echo $aplicar;
    n();
  }
  
  public static function fancybox2()
  {
    com("Fancybox2");
    js(self::$boot . "/fancybox2/lib/jquery.mousewheel-3.0.6.pack.js");
    js(self::$boot . "/fancybox2/source/jquery.fancybox.js?v=2.1.5");
    css(self::$boot . "/fancybox2/source/jquery.fancybox.css?v=2.1.5");
    css(self::$boot . "/fancybox2/source/helpers/jquery.fancybox-buttons.css?v=1.0.5");
    js(self::$boot . "/fancybox2/source/helpers/jquery.fancybox-buttons.js?v=1.0.5");
    css(self::$boot . "/fancybox2/source/helpers/jquery.fancybox-thumbs.css?v=1.0.7");
    js(self::$boot . "/fancybox2/source/helpers/jquery.fancybox-thumbs.js?v=1.0.7");
    js(self::$boot . "/fancybox2/source/helpers/jquery.fancybox-media.js?v=1.0.6");
    
     $aplicar =<<<EOF
<script type="text/javascript">
			jQuery(document).ready(function() {
			jQuery(".fancybox").fancybox();
		});
		</script>
EOF;
    echo $aplicar;
    n();
  
  }  
  
  public static function slick()
  {  
    com("Slick");
    js(self::$boot . "/slick/slick/slick.js");
    css(self::$boot . "/slick/slick/slick.css");  
  }
  
  public static function numeral()
  {
    com("Numeral");    
    js(self::$boot . "/numeral/numeral.js");    
    n();
  }  

  public static function tablesorter($parametros = null)
  {
    com("Tablesorter");    
    $mi_boot = self::$boot;
    css(self::$boot . "/tablesorter/themes/blue/style.css");
    js(self::$boot . "/tablesorter/jquery.tablesorter.min.js");
        $aplicar = <<<EOF
<script type="text/javascript">
$(document).ready(function() {
// agregar class=tablesorter id=lista_tablesorter 
        $("#lista_tablesorter").tablesorter($parametros); 
 }); 
</script>
EOF;
    echo $aplicar;
    n(); 
    
  }

   public static function treeview($parametros = null)
  {
    com("Treeview");    
    $mi_boot = self::$boot;
    css(self::$boot . "/treeview/jquery.treeview.css");
    js(self::$boot . "/treeview/jquery.treeview.js");
        $aplicar = <<<EOF
<script type="text/javascript">
$(document).ready(function(){
	$("#tree").treeview();
});
</script>
EOF;
    echo $aplicar;
    n();
  }  

public static function fileInput() 
  {
    com("fileInput");    
    css(self::$boot . "/fileInput/css/component.css");  
    js(self::$boot . "/fileInput/js/custom-file-input.js");     
  }  

public static function jred()
{
com("jred");
js(self::$boot . "/jred/jquery.redirect.js");
n();
}  

public static function dropzone()
{
com("dropzone");
css(self::$boot . "/dropzone/dropzone.min.css");
js(self::$boot . "/dropzone/dropzone.min.js");
}

public static function perso()
{
com("PERSO");
css(self::$boot . "/perso/perso.css");
js(self::$boot . "/perso/perso.js");
}

public static function collapser($cant = "100")
{

    com("Collapser");    
    js(self::$boot . "/collapser/js/jquery.collapser.js");
    n();      
  $codex = <<<EOF
<script type="text/javascript">
$('.collapser').collapser({
    mode: 'chars',
    truncate: $cant
  });
</script>
EOF;
    echo $codex;
    n();    
}

public static function date_picker()
  {
    com("date_picker");
    css(self::$boot . "/date_picker/css/date-picker.css");
    js(self::$boot . "/date_picker/js/date-picker.js");  
    n();      
  }

public static function vuejs2() {
    com("VUEJS2");
    js(self::$boot . "/vuejs2/vue.min.js");
    js(self::$boot . "/vuejs2/axios.min.js");
    n();      
}

public static function angularjs()  {
    com("AngularJS");
    js(self::$boot . "/angularjs/angular.min.js");
    n();      
}

public static function select2() {
    com("select2");
    js(self::$boot . "/select2/select2.min.js");
    css(self::$boot . "/select2/select2.min.css");
    n();      
}

  public static function datatables()
  {
    com("datatables");
    js(self::$boot . "/datatables/jquery.dataTables.js");
    css(self::$boot . "/datatables/jquery.dataTables.css");
    n();      
  }   
  public static function clock()
  {  
    com("Clock");
    js(self::$boot . "/clock/jquery-clock-timepicker.js");
  }  

  public static function summernote() {
      com("summernote");
      js(self::$boot . "/summernote/summernote-lite.min.js");
      css(self::$boot . "/summernote/summernote-lite.min.css");
      n();      
  }  

  public static function mSwitch()
  {
    com("mSwitch");
    css(self::$boot . "/switch/src/switchify.css");
    js(self::$boot . "/switch/src/switchify.js");
    n();      
  $codex = <<<EOF
  <script>
    $('input[type=checkbox]').switchify();            
  </script>
EOF;
    echo $codex;
    n();
  }

  public static function scal() {
      com("scal calendario");
      js(self::$boot . "/scal/jquery.supercal.js");
      n();      
  }    
  
  public static function vue_select() {
      com("vue-select");
      js(self::$boot . "/vue-select/vue-select-2-6-4.min.js");
      n();      
  }     

// ++++++++++++
// | END BOOT |
// ++++++++++++

}