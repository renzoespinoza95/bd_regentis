const mercadosMethods = {
  abrirModalMercados() {
    this.listarMercados().then(() => $('#modalMercados').modal('show'));
  },
  listarMercados() {
    this.bloquear('Cargando mercados…');
    return axios.get(`${this.apphost}/mercado/listar`)
      .then(r => {
        this.mercados = r.data.data || [];
        this.mercadosOptions = this.mercados.map(m => ({
          mercado_id: parseInt(m.mercado_id),
          cat_mercado_id: m.cat_mercado_id ? parseInt(m.cat_mercado_id) : null,
          nombre: m.nombre,
          direccion: m.direccion,
          is_activo: parseInt(m.is_activo)
        }));
        this.$nextTick(() => {
          if (!this.dtMerc) {
            this.dtMerc = $('#tablaMercados').DataTable({
              language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
              scrollX: true,
              dom: 'frtip',
              order: [[0,'desc']]
            });
            const self = this;
            $('#tablaMercados tbody')
              .on('click', 'a.editar-merc', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.mercados.find(x => parseInt(x.mercado_id,10) === parseInt(id,10));
                if (row) self.abrirModalEditarMercadoDesdeLista(row);
              })
              .on('change', '.toggle-activo-mercado', function(){
                const mercado_id = $(this).data('id');
                const is_activo = $(this).is(':checked') ? 1 : 0;
                axios.post(`${self.apphost}/WEwr/mercado/activo`, { mercado_id, is_activo })
                .then(()=>{ apprise('Estado actualizado'); })
                .catch(()=>{ apprise('Error al actualizar'); });
              })
              .on('click', 'a.eliminar-merc', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.mercados.find(x => parseInt(x.mercado_id,10) === parseInt(id,10));
                if (row) self.eliminarMercado(row);
              });
          }
          this.dtMerc.clear();
          this.mercados.forEach(m => {
            const activo = `
              <label class="switch-mini">
                <input type="checkbox" class="toggle-activo-mercado" data-id="${m.mercado_id}" ${parseInt(m.is_activo,10) ? 'checked' : ''}>
                <span></span>
              </label>
            `;
            const actions = `
              <div class="btn-group">
                <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                  Opciones <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="editar-merc" data-id="${m.mercado_id}">Editar</a></li>
                  <li><a href="#" class="eliminar-merc" data-id="${m.mercado_id}">Eliminar</a></li>
                </ul>
              </div>
            `;
            this.dtMerc.row.add([m.mercado_id, (m.nombre || ''), (m.categoria || ''), (m.direccion || ''), activo, actions]);
          });
          this.dtMerc.draw(false);
        });
      })
      .finally(() => $.unblockUI());
  },
  abrirModalCrearMercadoDesdeLista() {
    this.nuevoMercado = { nombre: '', direccion: '', is_activo: 1 };
    $('#modalMercados').modal('hide');
    $('#modalCrearMercado').modal('show');
  },
  crearMercado() {
    if (!this.nuevoMercado.nombre || !this.nuevoMercado.nombre.trim()) return apprise('Escribe el nombre');
    this.bloquear('Guardando mercado…');
    axios.post(`${this.apphost}/mercado/crear`, this.nuevoMercado)
      .then(() => {
        apprise('¡Mercado creado!');
        $('#modalCrearMercado').modal('hide');
      })
      .finally(() => {
        $.unblockUI();
        this.listarMercados().then(() => $('#modalMercados').modal('show'));
      });
  },
  abrirModalEditarMercadoDesdeLista(m) {
    this.cargarCategoriasMercado()
      .then(() => {
        const categoriaEncontrada = this.catMercadoOptions.find(x => parseInt(x.cat_mercado_id) === parseInt(m.cat_mercado_id));
        this.formMercado = {
          mercado_id: parseInt(m.mercado_id, 10),
          cat_mercado: categoriaEncontrada || null,
          nombre: (m.nombre || ''),
          direccion: (m.direccion || ''),
          is_activo: (parseInt(m.is_activo, 10) ? 1 : 0),
          logo: (m.logo || ''),
          topnavbar_color: (m.topnavbar_color || ''),
          patron_fondo: (m.patron_fondo || '')
        };
        $('#modalMercados').modal('hide');
        $('#modalEditarMercado').modal('show');
      })
      .catch(err => { console.error(err); });
  },
  guardarMercado() {
    if (!this.formMercado.nombre || !this.formMercado.nombre.trim()) {
      return apprise('Escribe el nombre');
    }
    const payload = {
      ...this.formMercado,
      cat_mercado_id: this.formMercado.cat_mercado ? parseInt(this.formMercado.cat_mercado.cat_mercado_id) : null
    };
    this.bloquear('Actualizando mercado…');
    axios.post(`${this.apphost}/mercado/editar`, payload)
      .then(() => {
        apprise('¡Mercado actualizado!');
        $('#modalEditarMercado').modal('hide');
      })
      .finally(() => {
        $.unblockUI();
        this.listarMercados().then(() => $('#modalMercados').modal('show'));
        this.listarNeg();
      });
  },
  eliminarMercado(m) {
    apprise(`¿Eliminar mercado <b>#${m.mercado_id}</b>?`, { confirm:true }, ok => {
      if (!ok) return;
      this.bloquear('Eliminando mercado…');
      axios.post(`${this.apphost}/mercado/eliminar`, { mercado_id: m.mercado_id })
        .then(() => apprise('Mercado eliminado'))
        .finally(() => {
          $.unblockUI();
          this.listarMercados();
          this.listarNeg();
        });
    });
  }
};