<?php
if (!empty($ssa_id) && is_string($ssa_id)) {
	$tipo_admin = $administrador_actual['rol_nombre'];

	switch ($tipo_admin) {

	    case 'GLOBAL':	   
	    	require_once VARPATH."/classes/JWT.class.php";
			require_once VARPATH."/classes/Mustache.class.php";
	        require_once VARPATH."/public/admin/tab_vari/control.php";
			require_once VARPATH."/public/admin/mod_bot/bot.php";
			require_once VARPATH."/public/admin/tab_prod/control.php";
			require_once VARPATH."/public/admin/tab_cat/control.php";
			require_once VARPATH."/public/admin/tab_order/control.php";
			require_once VARPATH."/public/admin/tab_inventario/control.php";
			require_once VARPATH."/public/admin/tab_proveedores/control.php";
			require_once VARPATH."/public/admin/tab_adm/control.php";
			require_once VARPATH."/public/admin/tab_menu/control.php";
			require_once VARPATH."/public/admin/tab_slider/control.php";
			require_once VARPATH."/public/admin/tab_cliente/control.php";			
			require_once VARPATH."/public/admin/tab_chat/control.php";
			require_once VARPATH."/public/admin/tab_neg/control.php";
			require_once VARPATH."/public/admin/tab_trabajador/control.php";
			require_once VARPATH."/public/admin/tab_usu/control.php";
			require_once VARPATH."/public/admin/tab_auto_msg/control.php";
			require_once VARPATH."/public/admin/tab_screen/control.php";
			require_once VARPATH."/public/admin/tab_prod_global/control.php";
			require_once VARPATH."/public/admin/tab_tema/control.php";

	        break;

	    case 'NEGOCIO':
	        require_once VARPATH."/public/admin/tab_vari/control.php";
			require_once VARPATH."/public/admin/tab_prod/control.php";
			require_once VARPATH."/public/admin/tab_cat/control.php";
			require_once VARPATH."/public/admin/tab_order/control.php";
			require_once VARPATH."/public/admin/tab_compras/control.php";
			require_once VARPATH."/public/admin/tab_inventario/control.php";
			require_once VARPATH."/public/admin/tab_proveedores/control.php";
			require_once VARPATH."/public/admin/tab_adm/control.php";
			require_once VARPATH."/public/admin/tab_menu/control.php";
			require_once VARPATH."/public/admin/tab_caja/control.php";
			require_once VARPATH."/public/admin/tab_slider/control.php";
			require_once VARPATH."/public/admin/tab_chat/control.php";
			require_once VARPATH."/public/admin/tab_tipoxmod/control.php";
			require_once VARPATH."/public/admin/tab_neg/control.php";
			require_once VARPATH."/public/admin/tab_trabajador/control.php";
			require_once VARPATH."/public/admin/tab_usu/control.php";
			require_once VARPATH."/public/admin/tab_auto_msg/control.php";
			require_once VARPATH."/public/admin/tab_master/control.php";
			require_once VARPATH."/classes/JWT.class.php";
			require_once VARPATH. "/classes/SimpleImage.class.php";
			require_once VARPATH."/classes/WkHtmlToPdf.php";
	        break;

	    case 'ADMIN_PAG_WEB':        
	        require_once VARPATH."/public/admin/tab_vari/control.php";
	        require_once VARPATH."/public/admin/tab_menu/control.php";
	       	require_once VARPATH."/classes/JWT.class.php";
			require_once VARPATH."/classes/Mustache.class.php";
			require_once VARPATH. "/classes/SimpleImage.class.php";
			require_once VARPATH."/classes/WkHtmlToPdf.php";
	        break;

	    case 'CONSUMIDOR':
	        echo "Acceso básico";
	        break;

	    default:
	        echo "Rol no reconocido";
	        break;
	}
}	

require_once VARPATH."/public/admin/tab_usu/app.php";
require_once VARPATH."/public/admin/tab_neg/app.php";
require_once VARPATH."/public/admin/tab_prod/app.php";
require_once VARPATH."/public/admin/login/app.php";
require_once VARPATH."/public/admin/tab_chat/app.php";
require_once VARPATH."/public/admin/tab_fav/app.php";
require_once VARPATH."/public/admin/tab_order/app.php";
require_once VARPATH."/public/admin/tab_trabajador/app.php";
require_once VARPATH."/public/admin/tab_cliente/app.php";
require_once VARPATH."/public/admin/tab_cat/app.php";
require_once VARPATH."/public/admin/tab_mercado/app.php";