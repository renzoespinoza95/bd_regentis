// F:\xampp\htdocs\bd_regentis\public\admin\tab_pag_web\js\05_subcategorias.js

const subcategoriaMethods = {
  abrirSubcategorias() {
    bloquearUI('Cargando subcategorías...');
    axios.get(`${this.apphost}/xoxo/reg_subcat/listar`)
      .then(r => {
        this.subcategorias = r.data.data || [];
        this.catModalSubcatFiltro = this.catSeleccionada || null;

        this.$nextTick(() => {
          this.renderTablaSubcatModal();
          $('#modalSubcategorias').modal('show');
        });
      })
      .finally(() => desbloquearUI());
  },

  filtrarSubcatsPorCatModal(cat) {
    this.catModalSubcatFiltro = cat;
    this.renderTablaSubcatModal();
  },

  onBuscarSubcatModal(e) {
    if (this.dtSubcat) {
      this.dtSubcat.search(e.target.value).draw();
    }
  },

  limpiarBuscadorSubcatModal() {
    const input = document.getElementById('txtBuscarSubcatModal');
    if (input) input.value = '';
    if (this.dtSubcat) {
      this.dtSubcat.search('').draw();
    }
  },

  renderTablaSubcatModal() {
    if (!this.dtSubcat) {
      this.dtSubcat = $('#tablaSubcat').DataTable({
        language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
        scrollX: true,
        dom: 'rtip',
        order: [[0, 'desc']]
      });

      const self = this;
      $('#tablaSubcat tbody')
        .on('click', '.editar-subcat', function () {
          const id = $(this).data('id');
          const row = self.subcategorias.find(x => x.subcat_pag_web_id == id);
          self.abrirEditarSubcat(row);
        })
        .on('click', '.eliminar-subcat', function () {
          const id = $(this).data('id');
          self.eliminarSubcat(id);
        })
        .on('click', '.imagenes-subcat', function () {
          const id = $(this).data('id');
          self.verImagenesSubcategoria(id);
        })
        .on('change', '.chk-subcat-visible', function () {
          const id = $(this).data('id');
          const isChecked = $(this).is(':checked') ? 1 : 0;
          self.cambiarVisibleSubcategoria(id, isChecked);
        });
    }

    this.dtSubcat.clear();

    const lista = this.catModalSubcatFiltro
      ? this.subcategorias.filter(s => s.cat_pag_web_id == this.catModalSubcatFiltro.cat_pag_web_id)
      : this.subcategorias;

    lista.forEach(s => {
      const vistaThumb = s.url_img 
        ? `<img src="${s.url_img}" style="max-width: 60px; max-height: 40px; border-radius: 3px; object-fit: cover;">`
        : `<span class="muted" style="font-size: 11px;">Sin img</span>`;

      const checkedHtml = (s.is_visible == 1) ? 'checked' : '';

      this.dtSubcat.row.add([
        s.subcat_pag_web_id,
        vistaThumb,
        s.titulo,
        s.clave_txt || '',
        s.categoria || '',
        `<div style="text-align:center;"><input type="checkbox" class="chk-subcat-visible" data-id="${s.subcat_pag_web_id}" ${checkedHtml} style="cursor:pointer;"></div>`,
        `
        <div class="btn-group">
          <button class="btn btn-warning btn-small dropdown-toggle" data-toggle="dropdown">
            Opciones <span class="caret"></span>
          </button>
          <ul class="dropdown-menu pull-right">
            <li><a href="#" class="editar-subcat" data-id="${s.subcat_pag_web_id}">Editar</a></li>
            <li><a href="#" class="eliminar-subcat" data-id="${s.subcat_pag_web_id}">Eliminar</a></li>
            <li class="divider"></li>
            <li><a href="#" class="imagenes-subcat" data-id="${s.subcat_pag_web_id}">Imágenes</a></li>
          </ul>
        </div>
        `
      ]);
    });

    this.dtSubcat.draw();
  },

  cambiarVisibleSubcategoria(subcat_id, is_visible) {
    axios.post(`${this.apphost}/xoxo/reg_subcat/actualizarVisible`, {
      subcat_pag_web_id: subcat_id,
      is_visible: is_visible
    })
    .then(res => {
      if (res.data && res.data.status === 'ok') {
        const s = this.subcategorias.find(x => x.subcat_pag_web_id == subcat_id);
        if (s) s.is_visible = is_visible;
      } else {
        apprise('No se pudo actualizar la visibilidad');
      }
    })
    .catch(() => apprise('Error de conexión'));
  },

  abrirCrearSubcatDirecta() {
    this.subcatForm = {
      titulo: '',
      clave_txt: '',
      url_img: '',
      is_visible: 1,
      texto01: '',
      texto02: '',
      cat: this.catSeleccionada
    };
    $('#modalCrearSubcat').modal('show');

    this.$nextTick(() => {
      if ($('#txtSubcatTexto01').next('.note-editor').length) $('#txtSubcatTexto01').summernote('destroy');
      if ($('#txtSubcatTexto02').next('.note-editor').length) $('#txtSubcatTexto02').summernote('destroy');

      $('#txtSubcatTexto01').summernote({ height: 120 }).summernote('code', '');
      $('#txtSubcatTexto02').summernote({ height: 120 }).summernote('code', '');
    });
  },

  abrirCrearSubcat() {
    this.subcatForm = { 
      titulo: '', 
      clave_txt: '', 
      url_img: '', 
      is_visible: 1,
      texto01: '',
      texto02: '',
      cat: this.catModalSubcatFiltro || this.catSeleccionada || null 
    };

    $('#modalSubcategorias').modal('hide');
    $('#modalCrearSubcat').modal('show');

    this.$nextTick(() => {
      if ($('#txtSubcatTexto01').next('.note-editor').length) $('#txtSubcatTexto01').summernote('destroy');
      if ($('#txtSubcatTexto02').next('.note-editor').length) $('#txtSubcatTexto02').summernote('destroy');

      $('#txtSubcatTexto01').summernote({ height: 120 }).summernote('code', '');
      $('#txtSubcatTexto02').summernote({ height: 120 }).summernote('code', '');
    });
  },

  guardarSubcat() {
    if (!this.subcatForm.titulo) return apprise('Escribe el título');
    if (!this.subcatForm.cat) return apprise('Selecciona categoría');

    this.subcatForm.texto01 = $('#txtSubcatTexto01').summernote('code');
    this.subcatForm.texto02 = $('#txtSubcatTexto02').summernote('code');

    bloquearUI('Guardando subcategoría...');
    axios.post(`${this.apphost}/xoxo/reg_subcat/crear`, {
      titulo: this.subcatForm.titulo,
      clave_txt: this.subcatForm.clave_txt,
      url_img: this.subcatForm.url_img,
      is_visible: this.subcatForm.is_visible,
      texto01: this.subcatForm.texto01,
      texto02: this.subcatForm.texto02,
      cat_id: this.subcatForm.cat.cat_pag_web_id
    })
    .then(() => {
      $('#modalCrearSubcat').modal('hide');
      this.cargarTodo();
      setTimeout(() => {
        this.abrirSubcategorias();
      }, 300);
    })
    .finally(() => desbloquearUI());
  },

  abrirEditarSubcat(row) {
    bloquearUI('Cargando datos...');
    axios.get(`${this.apphost}/xoxo/reg_cat/listar`)
      .then(r => {
        this.categorias = r.data.data || [];
        this.subcatForm = {
          subcat_pag_web_id: row.subcat_pag_web_id,
          titulo: row.titulo,
          clave_txt: row.clave_txt || '',
          url_img: row.url_img || '',
          is_visible: (row.is_visible !== undefined && row.is_visible !== null) ? Number(row.is_visible) : 1,
          texto01: row.texto01 || '',
          texto02: row.texto02 || '',
          cat: null
        };

        const cat = this.categorias.find(c => c.cat_pag_web_id == row.cat_pag_web_id);
        if (cat) {
          this.$nextTick(() => {
            this.subcatForm.cat = cat;
          });
        }

        $('#modalSubcategorias').modal('hide');
        $('#modalEditarSubcat').modal('show');

        this.$nextTick(() => {
          if ($('#txtSubcatEditarTexto01').next('.note-editor').length) $('#txtSubcatEditarTexto01').summernote('destroy');
          if ($('#txtSubcatEditarTexto02').next('.note-editor').length) $('#txtSubcatEditarTexto02').summernote('destroy');

          $('#txtSubcatEditarTexto01').summernote({ height: 120 }).summernote('code', row.texto01 || '');
          $('#txtSubcatEditarTexto02').summernote({ height: 120 }).summernote('code', row.texto02 || '');
        });
      })
      .finally(() => desbloquearUI());
  },

  actualizarSubcat() {
    if (!this.subcatForm.titulo) return apprise('Escribe el título');
    if (!this.subcatForm.cat) return apprise('Selecciona categoría');

    this.subcatForm.texto01 = $('#txtSubcatEditarTexto01').summernote('code');
    this.subcatForm.texto02 = $('#txtSubcatEditarTexto02').summernote('code');

    bloquearUI('Actualizando subcategoría...');
    axios.post(`${this.apphost}/xoxo/reg_subcat/editar`, {
      subcat_pag_web_id: this.subcatForm.subcat_pag_web_id,
      titulo: this.subcatForm.titulo,
      clave_txt: this.subcatForm.clave_txt,
      url_img: this.subcatForm.url_img,
      is_visible: this.subcatForm.is_visible,
      texto01: this.subcatForm.texto01,
      texto02: this.subcatForm.texto02,
      cat_id: this.subcatForm.cat.cat_pag_web_id
    })
    .then(() => {
      $('#modalEditarSubcat').modal('hide');
      this.cargarTodo();
      setTimeout(() => {
        this.abrirSubcategorias();
      }, 300);
    })
    .finally(() => desbloquearUI());
  },

  eliminarSubcat(id) {
    apprise('¿Eliminar esta subcategoría?', { confirm: true }, ok => {
      if (!ok) return;
      bloquearUI('Eliminando subcategoría...');
      axios.post(`${this.apphost}/xoxo/reg_subcat/eliminar`, { subcat_pag_web_id: id })
        .then(() => {
          this.cargarTodo();
          this.abrirSubcategorias();
        })
        .finally(() => desbloquearUI());
    });
  }
};