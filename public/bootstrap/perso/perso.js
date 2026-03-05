function fecha_guion(fecha) 
{

  var parts = fecha.split("/");

  var month = parts[0]; // 12
  var day = parts[1]; // 15
  var year = parts[2]; // 2009
  
  return year + "-" + day + "-" + month;
}

//+++ blockUI inicio +++
var config_blockui = {
        css: {  
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff' 
         }
};

var init = {
        css: {  
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff' 
         }
};

var bu = {
        css: {  
            border: 'none', 
            padding: '15px', 
            backgroundColor: '#000', 
            '-webkit-border-radius': '10px', 
            '-moz-border-radius': '10px', 
            opacity: .5, 
            color: '#fff' 
         }
};

var  msg_exito = function(){
    showNotification({
        type : "success",
        autoClose: true,
        duration: 4,
        message: "Datos actualizados"
    });
}

var  msg_error = function(){
    showNotification({
        type : "error",
        autoClose: true,
        duration: 4,
        message: "Error al procesar"
    });    
}    