const usuariosExtrasMixin = {
  methods: {
    async crearPin(u){
      bloquearUI('Generando PIN y Clave...');
      const response = await axios.post(this.apphost + '/KdB0/crearPin', { usu_id: u.usu_id });
      desbloquearUI();
      
      if(response.data.status === 'ok'){
        u.pin_code = response.data.pin_code;
        u.clavel = response.data.clavel;
        this.obtenerUsuarios();
        apprise('Código de Usuario: ' + response.data.cod_usu + '\nPIN: ' + response.data.pin_code + '\nClave: ' + response.data.clavel);
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
    toggleIsActivo(u) {
      axios.post(this.apphost + '/IS54/toggleIsActivo', { usu_id: u.usu_id }).then(res => {
        if (res.data.res === 'ok') {
          u.is_activo = parseInt(res.data.is_activo);
          apprise(res.data.msg);
        }
      });
    },
    crearUsuarioAutomatico(){
      bloquearUI('Creando usuario automático...');
      axios.post(this.apphost + '/NTol/usuarioAutomatico').then(r => {
        desbloquearUI();
        if(r.data.success && r.data.usuario){
          // 1. Destruimos DataTables primero para evitar interferencia en el renderizado del DOM de Vue
          if ($.fn.DataTable.isDataTable('#tablaUsuarios')) {
            $('#tablaUsuarios').DataTable().destroy();
          }

          // 2. Agregamos el objeto al inicio de la matriz en Vue
          this.usuarios.unshift(r.data.usuario);

          // 3. Volvemos a inicializar DataTables sobre el DOM actualizado
          this.$nextTick(() => {
            this.initDataTable('#tablaUsuarios');
          });

          apprise('Usuario automático creado 🚀');
        } else {
          this.obtenerUsuarios();
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