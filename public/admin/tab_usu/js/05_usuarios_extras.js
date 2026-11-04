const usuariosExtrasMixin = {
  methods: {
    async crearPin(u){
      bloquearUI('Generando PIN...');
      const response = await axios.post(this.apphost + '/KdB0/crearPin', { usu_id: u.usu_id });
      desbloquearUI();
      
      if(response.data.status === 'ok'){
        // Asignamos directamente los valores al usuario en memoria para actualización reactiva instantánea
        u.pin_code = response.data.pin_code;
        
        // Recargamos la lista completa para refrescar el DataTable de forma limpia
        this.obtenerUsuarios();
        
        apprise('Código de Usuario: ' + response.data.cod_usu + '\nPIN: ' + response.data.pin_code);
      } else {
        apprise(response.data.msg || 'No se pudo generar el PIN');
      }
    },
    reiniciarUsuario(u){
      apprise('¿Deseas reiniciar este usuario?', { 'verify': true }, async (r) => {
        if(!r) return;
        const response = await axios.post(this.apphost + '/usuario/reiniciar', { usu_id: u.usu_id });
        if(response.data.status === 'ok'){
          apprise('Usuario reiniciado correctamente');
          this.obtenerUsuarios();
        } else {
          apprise(response.data.msg || 'No se pudo reiniciar');
        }
      });
    },
    toggleFantasma(u) {
      axios.post(this.apphost + '/IS54/toggleIsFantasma', { usu_id: u.usu_id }).then(res => {
        if (res.data.res === 'ok') {
          u.is_fantasma = parseInt(res.data.is_fantasma);
          apprise(res.data.msg);
        }
      });
    },
    crearUsuarioAutomatico(){
      bloquearUI('Creando usuario automático...');
      axios.post(this.apphost + '/NTol/usuarioAutomatico').then(r => {
        desbloquearUI();
        if(r.data.success){
          $('#modalCrearUsuario').modal('hide');
          this.obtenerUsuarios();
          apprise('Usuario automático creado 🚀');
        }
      }).catch(() => desbloquearUI());
    },
    clave12(u){
      apprise('¿Asignar clave 12qw12 a este usuario?', { 'verify': true }, async (r) => {
        if(!r) return;
        const res = await axios.post(this.apphost + '/usuario/clave12', { usu_id: u.usu_id });
        if(res.data.status === 'ok'){
          apprise('Clave actualizada correctamente');
        }
      });
    },
    nuevoNegocio(u){
      apprise('¿Enviar botón de nuevo negocio a este usuario?', { 'verify': true }, async (r) => {
        if(!r) return;
        const res = await axios.post(this.apphost + '/usuario/nuevoNegocio', { usu_id: u.usu_id });
        if(res.data.status === 'ok'){
          apprise('Botón enviado correctamente');
        }
      });
    },
    iniciarSesionUsuario(u){
      apprise('¿Iniciar sesión como este usuario?', { 'verify': true }, async (r) => {
        if(!r) return;
        bloquearUI('Iniciando sesión...');
        const res = await axios.post(this.apphost + '/YfrW/loginNeg', { usu_id: u.usu_id });
        desbloquearUI();
        if(res.data.res === 'ok'){
          window.location.href = this.apphost + '/admin/dash';
        } else {
          apprise(res.data.msg || 'No se pudo iniciar sesión');
        }
      });
    }
  }
};