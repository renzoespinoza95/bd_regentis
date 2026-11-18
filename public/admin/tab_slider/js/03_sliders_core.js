const sliderCoreMethods = {
  obtenerUsuarioInfo() {
    fetch(this.apphost + '/slider/usuario/info')
      .then(r => r.json())
      .then(res => {
        if (res.ok && res.datos) {
          this.usuario = res.datos;
        }
      })
      .catch(err => console.error('Error al cargar datos de usuario:', err));
  },

  obtenerSliders() {
    return fetch(this.apphost + '/slider/listar?_=' + Date.now(), { cache: 'no-store' })
      .then(r => r.json())
      .then(data => {
        const lista = (Array.isArray(data) ? data : []).map(s => ({
          ...s,
          img_thumb: s.img
        }));

        // Se destruye el DataTable ANTES de que Vue actualice el DOM,
        // para que Vue no choque con las filas que maneja DataTables.
        if ($.fn.DataTable.isDataTable('#tablaSliders')) {
          $('#tablaSliders').DataTable().destroy();
        }

        this.sliders = lista;

        this.$nextTick(() => {
          $('#tablaSliders').DataTable({
            language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
            scrollX: true,
            dom: 'frtip',
            order: [[4, 'asc']],
            columnDefs: [
              { targets: 0, orderable: false, searchable: false }
            ]
          });
        });
      });
  },

  abrirModalMenuTree() {
    fetch(this.apphost + '/menus/tree')
      .then(r => r.json())
      .then(data => {
        this.listaMenus = Array.isArray(data) ? data : [];
        $('#modalMenuTree').modal('show');
      })
      .catch(() => {
        $('#modalMenuTree').modal('show');
      });
  },

  // ================= Check de visibilidad (DataTable) =================
  cambiarVisible(s, e) {
    const chk = e.target;
    const nuevoValor = chk.checked ? 1 : 0;

    fetch(this.apphost + '/slider/visible', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slider_id: s.slider_id, is_visible: nuevoValor })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          s.is_visible = nuevoValor;
        } else {
          chk.checked = !chk.checked;
          apprise('Error: ' + (data.error || 'Desconocido'));
        }
      })
      .catch(err => {
        chk.checked = !chk.checked;
        apprise('Error de conexión');
        console.error(err);
      });
  },

  // ================= Drag & Drop Ordenamiento =================
  activarDrag() {
    const vm = this;
    $('#sortable').sortable({
      handle: '.drag-handle',
      update: function () {
        let orden = [];
        $('#sortable tr').each(function (index) {
          orden.push({
            slider_id: $(this).data('id'),
            orden: index + 1
          });
        });

        fetch(vm.apphost + '/slider/ordenar', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ orden })
        })
        .then(r => r.json())
        .then(() => {
          vm.obtenerSliders();
        });
      }
    });
  },

  // ================= Visor de imagen a pantalla completa =================
  verImagenGrande(url) {
    if (url) this.imagenGrande = url;
  },
  cerrarImagen() {
    this.imagenGrande = '';
  }
};