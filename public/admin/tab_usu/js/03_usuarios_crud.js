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
      this.negocioSeleccionado = this.negociosValidados.find(n => Number(n.neg_id) === Number(u.neg_id)) || null;
      $('#modalEditarUsuario').modal('show');
    },
    guardarEdicion() {
      const idFinalNegocio = (this.negocioSeleccionado && typeof this.negocioSeleccionado === 'object') ? this.negocioSeleccionado.neg_id : this.negocioSeleccionado;
      const payload = { ...this.formulario, neg_id: idFinalNegocio };
      axios.post(this.apphost + '/xico/usu/editar', payload).then(() => {
        $('#modalEditarUsuario').modal('hide');
        this.obtenerUsuarios();
      });
    },
    eliminarUsuario(u) {
      if(confirm(`Liquidar usuario ${u.usu_id}?`)){
        axios.post(this.apphost + '/usuario/liquidar', { usu_id: u.usu_id }).then(() => { this.obtenerUsuarios(); });
      }
    },
    abrirModalBuscar() { this.resultados = []; $('#modalBuscarUsuario').modal('show'); },
    buscarUsuario() {
      axios.post(this.apphost + '/usuario/buscar', { sobrenombre: this.buscarSobrenombre }).then(r => { this.resultados = r.data; });
    },
    async cargarNegociosValidados() {
      const r = await axios.get(this.apphost + '/xico/negocios/validados');
      this.negociosValidados = r.data.map(n => ({ neg_id: Number(n.neg_id), nombre: n.nombre }));
    }
  }
};