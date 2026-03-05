<?php
boot::start();
boot::jquery();
boot::apprise();
?>    
<script type="text/javascript">

$(document).ready(function() {

termino_sesion('<?php echo $mensaje ?>');
                             
}); 

 
function termino_sesion(string, args) {
apprise(string, args, function(r) {
	if(r) {
    location.href = "<?php echo $redireccionar ?>";
	} 
});
}
</script>