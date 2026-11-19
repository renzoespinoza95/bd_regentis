const usuariosCrudMixin = {
  methods: {
    obtenerUsuarios() {  
      axios.get(this.apphost + '/usuario/listar')
        .then(r => {
          this.usuarios = r.data;
          this.$nextTick(() => { this.initDataTable('#tablaUsuarios'); });
        });
    },
    obtenerTipousuarios() {
      axios.get(this.apphost + '/tipoxusu/listar').then(r => { this.tipousuarios = r.data; });
    },
    getTipoDescripcion(id) {
      const t = this.tipousuarios.find(x => x.tipoxusu_id == id);
      return t ? t.descripcion : '';
    },
    initDataTable(sel) {
      this.$nextTick(() => {
        if ($.fn.DataTable.isDataTable(sel)) { 
          $(sel).DataTable().destroy(); 
        }
        
        $(sel).DataTable({
          destroy: true,
          scrollX: true,
          dom: 'frtip',
          order: [[2, 'desc']],
          columnDefs: [
            { orderable: false, targets: [0, 1] }
          ],
          rowCallback: (row, data) => {
            $(row).find('.btn-copiar-dato').off('click').on('click', function() {
              const textoACopiar = String($(this).attr('data-copiar') || '');
              const campo = $(this).attr('data-campo') || 'Dato';
              if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textoACopiar).then(() => {
                  mostrarToastCopia(campo, textoACopiar);
                });
              } else {
                const aux = document.createElement('textarea');
                aux.value = textoACopiar;
                document.body.appendChild(aux);
                aux.select();
                document.execCommand('copy');
                document.body.removeChild(aux);
                mostrarToastCopia(campo, textoACopiar);
              }
            });
          }
        });
        
        if (typeof agregarScrollBotones === 'function') {
          agregarScrollBotones($(sel));
        }
      });
    },
    abrirModalEditar(u) {
      this.formulario = {
        usu_id: u.usu_id,
        cod_usuario: u.cod_usu || '',
        google_uid: u.google_uid || '',
        dni: u.dni || '',
        img_perfil: u.img_perfil || '',
        sobrenombre: u.sobrenombre || '',
        nombres_apellidos: u.nombres_apellidos || '',
        celular: u.celular || '',
        provincia: u.provincia || '',
        tipoxusu_id: u.tipoxusu_id ? parseInt(u.tipoxusu_id) : null,
        is_activo: u.is_activo !== undefined ? parseInt(u.is_activo) : 1
      };
      $('#modalEditarUsuario').modal('show');
    },
    guardarEdicion() {
      const payload = { ...this.formulario };
      axios.post(this.apphost + '/xico/usu/editar', payload).then(() => {
        $('#modalEditarUsuario').modal('hide');
        this.obtenerUsuarios();
      });
    },
    abrirModalBuscar() { this.resultados = []; $('#modalBuscarUsuario').modal('show'); },
    buscarUsuario() {
      axios.post(this.apphost + '/usuario/buscar', { sobrenombre: this.buscarSobrenombre }).then(r => { this.resultados = r.data; });
    }
  }
};