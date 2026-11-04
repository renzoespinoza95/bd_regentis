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
        if ($.fn.DataTable.isDataTable(sel)) { $(sel).DataTable().destroy(); }
        
        $(sel).DataTable({
          destroy: true,
          retrieve: true,
          scrollX: true,
          dom: 'frtip',
          order: [[1,'desc']],
          rowCallback: (row, data) => {
            // Vincular evento de copia dinámicamente con el formato requerido por el usuario
            $(row).find('.btn-copiar-pin').off('click').on('click', function() {
              const textoACopiar = $(this).data('copiar');
              if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(textoACopiar).then(() => {
                  if (typeof apprise === 'function') {
                    apprise(textoACopiar);
                  }
                });
              } else {
                const aux = document.createElement('textarea');
                aux.value = textoACopiar;
                document.body.appendChild(aux);
                aux.select();
                document.execCommand('copy');
                document.body.removeChild(aux);
                if (typeof apprise === 'function') {
                  apprise(textoACopiar);
                }
              }
            });
          }
        });
        
        agregarScrollBotones($(sel));
      });
    },
    abrirModalCrear() {
      $('#modalCrearUsuario').modal('show');
    },
    crearUsuario() {
      axios.post(this.apphost + '/xico/usu/crear', this.nuevo).then(() => {
        $('#modalCrearUsuario').modal('hide');
        this.obtenerUsuarios();
      });
    },
    abrirModalEditar(u) {
      this.formulario = { usu_id: u.usu_id, cod_usuario: u.cod_usu, dni: u.dni || '', sobrenombre: u.sobrenombre || '', nombres_apellidos: u.nombres_apellidos || '' };
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