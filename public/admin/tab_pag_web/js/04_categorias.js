// F:\xampp\htdocs\bd_regentis\public\admin\tab_pag_web\js\04_categorias.js

const categoriaMethods = {
  abrirCategorias() {
    bloquearUI('Cargando categorías...');
    axios.get(`${this.apphost}/xoxo/reg_cat/listar`)
      .then(r => {
        this.categorias = r.data.data || [];
        this.$nextTick(() => {
          if (!this.dtCat) {
            this.dtCat = $('#tablaCat').DataTable({
              language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
              scrollX: true,
              dom: 'frtip',
              order: [[0, 'desc']]
            });

            const self = this;
            $('#tablaCat tbody')
              .on('click', '.editar-cat', function () {
                const id = $(this).data('id');
                const row = self.categorias.find(x => x.cat_pag_web_id == id);
                self.abrirEditarCategoria(row);
              })
              .on('click', '.eliminar-cat', function () {
                const id = $(this).data('id');
                self.eliminarCategoria(id);
              })
              .on('click', '.imagenes-cat', function () {
                const id = $(this).data('id');
                self.verImagenesCategoria(id);
              })
              .on('change', '.chk-cat-visible', function () {
                const id = $(this).data('id');
                const isChecked = $(this).is(':checked') ? 1 : 0;
                self.cambiarVisibleCategoria(id, isChecked);
              });
          }

          this.dtCat.clear();
          this.categorias.forEach(c => {
            const vistaThumb = c.url_img 
              ? `<img src="${c.url_img}" style="max-width: 60px; max-height: 40px; border-radius: 3px; object-fit: cover;">`
              : `<span class="muted" style="font-size: 11px;">Sin img</span>`;

            const checkedHtml = (c.is_visible == 1) ? 'checked' : '';

            this.dtCat.row.add([
              c.cat_pag_web_id,
              vistaThumb,
              c.titulo,
              c.clave_txt || '',
              `<div style="text-align:center;"><input type="checkbox" class="chk-cat-visible" data-id="${c.cat_pag_web_id}" ${checkedHtml} style="cursor:pointer;"></div>`,
              `
              <div class="btn-group">
                <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                  Opciones <span class="caret"></span>
                </button>
                <ul class="dropdown-menu pull-right">
                  <li><a href="#" class="editar-cat" data-id="${c.cat_pag_web_id}">Editar</a></li>
                  <li><a href="#" class="eliminar-cat" data-id="${c.cat_pag_web_id}">Eliminar</a></li>
                  <li class="divider"></li>
                  <li><a href="#" class="imagenes-cat" data-id="${c.cat_pag_web_id}">Imágenes</a></li>
                </ul>
              </div>
              `
            ]);
          });

          this.dtCat.draw();
          $('#modalCategorias').modal('show');
        });
      })
      .finally(() => desbloquearUI());
  },

  cambiarVisibleCategoria(cat_id, is_visible) {
    axios.post(`${this.apphost}/xoxo/reg_cat/actualizarVisible`, {
      cat_pag_web_id: cat_id,
      is_visible: is_visible
    })
    .then(res => {
      if (res.data && res.data.status === 'ok') {
        const c = this.categorias.find(x => x.cat_pag_web_id == cat_id);
        if (c) c.is_visible = is_visible;
      } else {
        apprise('No se pudo actualizar la visibilidad');
      }
    })
    .catch(() => apprise('Error de conexión'));
  },

  abrirCrearCategoria() {
    this.catForm = { 
      titulo: '', 
      clave_txt: '', 
      url_img: '', 
      is_visible: 1, 
      texto01: '', 
      texto02: '' 
    };

    $('#modalCategorias').modal('hide');
    $('#modalCrearCategoria').modal('show');

    this.$nextTick(() => {
      if ($('#txtCatTexto01').next('.note-editor').length) $('#txtCatTexto01').summernote('destroy');
      if ($('#txtCatTexto02').next('.note-editor').length) $('#txtCatTexto02').summernote('destroy');

      $('#txtCatTexto01').summernote({ height: 120 }).summernote('code', '');
      $('#txtCatTexto02').summernote({ height: 120 }).summernote('code', '');
    });
  },

  guardarCategoria() {
    if (!this.catForm.titulo) {
      return apprise('Escribe el título');
    }

    this.catForm.texto01 = $('#txtCatTexto01').summernote('code');
    this.catForm.texto02 = $('#txtCatTexto02').summernote('code');

    bloquearUI('Guardando categoría...');
    axios.post(`${this.apphost}/xoxo/reg_cat/crear`, this.catForm)
      .then(() => {
        $('#modalCrearCategoria').modal('hide');
        this.cargarTodo();
        setTimeout(() => {
          this.abrirCategorias();
        }, 300);
      })
      .finally(() => desbloquearUI());
  },

  abrirEditarCategoria(row) {
    this.catForm = {
      cat_pag_web_id: row.cat_pag_web_id,
      titulo: row.titulo,
      clave_txt: row.clave_txt || '',
      url_img: row.url_img || '',
      is_visible: (row.is_visible !== undefined && row.is_visible !== null) ? Number(row.is_visible) : 1,
      texto01: row.texto01 || '',
      texto02: row.texto02 || ''
    };

    $('#modalCategorias').modal('hide');
    $('#modalEditarCategoria').modal('show');

    this.$nextTick(() => {
      if ($('#txtCatEditarTexto01').next('.note-editor').length) $('#txtCatEditarTexto01').summernote('destroy');
      if ($('#txtCatEditarTexto02').next('.note-editor').length) $('#txtCatEditarTexto02').summernote('destroy');

      $('#txtCatEditarTexto01').summernote({ height: 120 }).summernote('code', row.texto01 || '');
      $('#txtCatEditarTexto02').summernote({ height: 120 }).summernote('code', row.texto02 || '');
    });
  },

  actualizarCategoria() {
    if (!this.catForm.titulo) {
      return apprise('Escribe el título');
    }

    this.catForm.texto01 = $('#txtCatEditarTexto01').summernote('code');
    this.catForm.texto02 = $('#txtCatEditarTexto02').summernote('code');

    bloquearUI('Actualizando categoría...');
    axios.post(`${this.apphost}/xoxo/reg_cat/editar`, {
      cat_pag_web_id: this.catForm.cat_pag_web_id,
      titulo: this.catForm.titulo,
      clave_txt: this.catForm.clave_txt,
      url_img: this.catForm.url_img,
      is_visible: this.catForm.is_visible,
      texto01: this.catForm.texto01,
      texto02: this.catForm.texto02
    })
    .then(() => {
      $('#modalEditarCategoria').modal('hide');
      this.cargarTodo();
      setTimeout(() => {
        this.abrirCategorias();
      }, 300);
    })
    .finally(() => desbloquearUI());
  },

  eliminarCategoria(id) {
    apprise('¿Eliminar esta categoría?', { confirm: true }, ok => {
      if (!ok) return;
      bloquearUI('Eliminando categoría...');
      axios.post(`${this.apphost}/xoxo/reg_cat/eliminar`, { cat_pag_web_id: id })
        .then(() => {
          this.cargarTodo();
          this.abrirCategorias();
        })
        .finally(() => desbloquearUI());
    });
  }
};