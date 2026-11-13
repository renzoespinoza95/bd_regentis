const itemMethods = {
  cargarTodo() {
    bloquearUI('Cargando catálogo...');
    Promise.all([
      axios.get(`${this.apphost}/xoxo/reg_cat/listar`),
      axios.get(`${this.apphost}/xoxo/reg_subcat/listar`),
      axios.get(`${this.apphost}/xoxo/reg_item/listar`),
      axios.get(`${this.apphost}/xoxo/negocio/info`)
    ])
    .then(([catRes, subcatRes, itemRes, negRes]) => {
      this.categorias = catRes.data.data || [];
      this.subcategorias = subcatRes.data.data || [];
      this.items = itemRes.data.data || [];
      
      if (negRes.data && negRes.data.status === 'ok') {
        this.negId = negRes.data.neg_id;
        this.nombreNegocio = negRes.data.nombre;
      }

      if (!this.catSeleccionada) {
        this.seleccionarCategoria({ cat_pag_web_id: 'todos', titulo: 'Todos', is_visible: 1 });
      } else if (this.subcatSeleccionada) {
        this.filtrarTablaPorSubcat();
      }
    })
    .finally(() => desbloquearUI());
  },

  // Recarga solo los ítems y vuelve a dibujar el DataTable.
  refrescarItems() {
    return axios.get(`${this.apphost}/xoxo/reg_item/listar`)
      .then(res => {
        this.items = (res.data && res.data.data) || [];
        this.filtrarTablaPorSubcat();
      });
  },

  moverCarruselCat(direccion) {
    const contenedor = this.$refs.carruselCategoriasRef;
    if (contenedor) {
      contenedor.scrollBy({ left: direccion * 250, behavior: 'smooth' });
    }
  },

  seleccionarCategoria(cat) {
    this.catSeleccionada = cat;
    if (cat.cat_pag_web_id === 'todos') {
      this.seleccionarSubcategoria({ subcat_pag_web_id: 'todos', titulo: 'Todos', is_visible: 1 });
      return;
    }
    const subs = this.subcategorias.filter(s => s.cat_pag_web_id == cat.cat_pag_web_id);
    if (subs.length > 0) {
      this.seleccionarSubcategoria(subs[0]);
    } else {
      this.subcatSeleccionada = null;
      this.filtrarTablaPorSubcat();
    }
  },

  seleccionarSubcategoria(subcat) {
    this.subcatSeleccionada = subcat;
    this.filtrarTablaPorSubcat();
  },

  contarSubcats(cat_id) {
    return this.subcategorias.filter(s => s.cat_pag_web_id == cat_id).length;
  },

  contarItems(subcat_id) {
    return this.items.filter(i => i.subcat_pag_web_id == subcat_id).length;
  },

  cambiarVisibleItem(item_id, is_visible) {
    axios.post(`${this.apphost}/xoxo/reg_item/actualizarVisible`, {
      item_pag_web_id: item_id,
      is_visible: is_visible
    })
    .then(res => {
      if (res.data && res.data.status === 'ok') {
        const it = this.items.find(x => x.item_pag_web_id == item_id);
        if (it) it.is_visible = is_visible;
      } else {
        apprise('No se pudo actualizar la visibilidad del ítem');
      }
    })
    .catch(() => apprise('Error de conexión'));
  },

  modificarClaveItem(row) {
    apprise('Modificar Clave TXT:', { input: row.clave_txt || '' }, (r) => {
      if (r === false || r === null) return;
      
      bloquearUI('Actualizando clave...');
      axios.post(`${this.apphost}/xoxo/reg_item/actualizarClave`, {
        item_pag_web_id: row.item_pag_web_id,
        clave_txt: r
      })
      .then(res => {
        if (res.data && res.data.status === 'ok') {
          row.clave_txt = r;
          this.filtrarTablaPorSubcat();
          apprise('Clave actualizada correctamente');
        } else {
          apprise(res.data.msg || 'No se pudo actualizar');
        }
      })
      .catch(() => apprise('Error de conexión'))
      .finally(() => desbloquearUI());
    });
  },

  filtrarTablaPorSubcat() {
    this.$nextTick(() => {
      if (!this.dt) {
        this.dt = $('#tablaItems').DataTable({
          language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
          scrollX: true,
          dom: 'frtip',
          order: [[0, 'desc']],
          stripeClasses: []
        });

        const self = this;
        $('#tablaItems tbody')
          .on('click', '.editar', function () {
            const id = $(this).data('id');
            const row = self.items.find(x => x.item_pag_web_id == id);
            self.abrirEditarItem(row);
          })
          .on('click', '.eliminar', function () {
            const id = $(this).data('id');
            self.eliminar(id);
          })
          .on('click', '.editar-clave', function () {
            const id = $(this).data('id');
            const row = self.items.find(x => x.item_pag_web_id == id);
            self.modificarClaveItem(row);
          })
          .on('click', '.copiar-clave', function () {
            const id = $(this).data('id');
            const row = self.items.find(x => x.item_pag_web_id == id);
            if (row && row.clave_txt) {
              navigator.clipboard.writeText(row.clave_txt)
                .then(() => apprise('¡Clave copiada al portapapeles!'))
                .catch(() => apprise('No se pudo copiar'));
            } else {
              apprise('No hay clave TXT para copiar');
            }
          })
          .on('click', '.img', function (e) {
            e.preventDefault();
            self.itemActual = $(this).data('id');
            self.verImagenes(self.itemActual);
          })
          .on('click', '.vid', function (e) {
            e.preventDefault();
            self.itemActual = $(this).data('id');
            self.verVideos(self.itemActual);
          })
          .on('change', '.chk-item-visible', function () {
            const id = $(this).data('id');
            const isChecked = $(this).is(':checked') ? 1 : 0;
            self.cambiarVisibleItem(id, isChecked);
          });
      }

      this.dt.clear();

      let itemsFiltrados = [];
      const catIdsNegocio = this.categorias.map(c => c.cat_pag_web_id);
      const subcatIdsNegocio = this.subcategorias
        .filter(s => catIdsNegocio.includes(s.cat_pag_web_id))
        .map(s => s.subcat_pag_web_id);

      if (this.subcatSeleccionada) {
        if (this.subcatSeleccionada.subcat_pag_web_id === 'todos') {
          itemsFiltrados = this.items.filter(i => subcatIdsNegocio.includes(i.subcat_pag_web_id));
        } else if (this.subcatSeleccionada.subcat_pag_web_id === 'videos') {
          itemsFiltrados = this.items.filter(i => 
            subcatIdsNegocio.includes(i.subcat_pag_web_id) && 
            Array.isArray(i.videos) && 
            i.videos.length > 0
          );
        } else {
          itemsFiltrados = this.items.filter(i => i.subcat_pag_web_id == this.subcatSeleccionada.subcat_pag_web_id);
        }
      }

      itemsFiltrados.forEach(i => {
        const checkedHtml = (i.is_visible == 1 || i.is_visible === undefined) ? 'checked' : '';
        const claveTxtVal = i.clave_txt ? i.clave_txt : '';
        const contenidoVal = i.contenido ? i.contenido : '';

        // Miniaturas de imágenes
        const imgs = Array.isArray(i.imagenes) ? i.imagenes : [];
        const thumbsHtml = imgs.length
          ? `<div class="item-thumbs">${imgs.map(im =>
              `<img src="${im.url_img}" class="img item-thumb" data-id="${i.item_pag_web_id}" loading="lazy" title="Ver imágenes">`
            ).join('')}</div>`
          : '';

        this.dt.row.add([
          i.item_pag_web_id,
          `<div>${i.titulo}</div>${thumbsHtml}`,
          `
          <div style="display: flex; justify-content: space-between; align-items: center;">
            <span style="word-break: break-all;">${claveTxtVal}</span>
            <div>
              <button class="btn btn-info btn-mini copiar-clave" data-id="${i.item_pag_web_id}" title="Copiar Clave TXT" style="margin-right: 3px;">
                <i class="fa fa-copy"></i>
              </button>
              <button class="btn btn-warning btn-mini editar-clave" data-id="${i.item_pag_web_id}" title="Modificar Clave TXT">
                <i class="icon-pencil"></i>
              </button>
            </div>
          </div>
          `,
          `<div style="max-height: 80px; overflow-y: auto; font-size: 12px;">${contenidoVal}</div>`,
          `<div style="text-align:center;"><input type="checkbox" class="chk-item-visible" data-id="${i.item_pag_web_id}" ${checkedHtml} style="cursor:pointer;"></div>`,
          `
          <div class="btn-group">
            <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
              ⚙ <span class="caret"></span>
            </button>
            <ul class="dropdown-menu pull-right">
              <li><a href="#" class="editar" data-id="${i.item_pag_web_id}">Editar</a></li>
              <li><a href="#" class="eliminar" data-id="${i.item_pag_web_id}">Eliminar</a></li>
              <li class="divider"></li>
              <li><a href="#" class="img" data-id="${i.item_pag_web_id}">Imagen</a></li>
              <li><a href="#" class="vid" data-id="${i.item_pag_web_id}">Video</a></li>
            </ul>
          </div>
          `
        ]);
      });

      this.dt.draw();
    });
  },

  abrirNuevoItemDirecto() {
    this.itemForm = {
      item_pag_web_id: null,
      titulo: '',
      precio: 0,
      stock: 0,
      contenido: '',
      subtitulo_detalle: '',
      html01: '',
      html02: '',
      is_visible: 1,
      clave_txt: '',
      cat: this.catSeleccionada && this.catSeleccionada.cat_pag_web_id !== 'todos' ? this.catSeleccionada : null,
      subcat: this.subcatSeleccionada && this.subcatSeleccionada.subcat_pag_web_id !== 'todos' ? this.subcatSeleccionada : null
    };

    $('#modalItem').modal('show');

    this.$nextTick(() => {
      if ($('#txtContenido').next('.note-editor').length) $('#txtContenido').summernote('destroy');
      if ($('#txtSubtitulo').next('.note-editor').length) $('#txtSubtitulo').summernote('destroy');
      if ($('#txtHtml01').next('.note-editor').length) $('#txtHtml01').summernote('destroy');
      if ($('#txtHtml02').next('.note-editor').length) $('#txtHtml02').summernote('destroy');

      $('#txtContenido').summernote({ height: 200 }).summernote('code', '');
      $('#txtSubtitulo').summernote({ height: 150 }).summernote('code', '');
      $('#txtHtml01').summernote({ height: 150 }).summernote('code', '');
      $('#txtHtml02').summernote({ height: 150 }).summernote('code', '');
    });
  },

  abrirNuevoItem() {
    this.itemForm = {
      item_pag_web_id: null,
      titulo: '',
      precio: 0,
      stock: 0,
      contenido: '',
      subtitulo_detalle: '',
      html01: '',
      html02: '',
      is_visible: 1,
      clave_txt: '',
      cat: this.catSeleccionada && this.catSeleccionada.cat_pag_web_id !== 'todos' ? this.catSeleccionada : null,
      subcat: this.subcatSeleccionada && this.subcatSeleccionada.subcat_pag_web_id !== 'todos' ? this.subcatSeleccionada : null
    };

    $('#modalItem').modal('show');

    this.$nextTick(() => {
      if ($('#txtContenido').next('.note-editor').length) $('#txtContenido').summernote('destroy');
      if ($('#txtSubtitulo').next('.note-editor').length) $('#txtSubtitulo').summernote('destroy');
      if ($('#txtHtml01').next('.note-editor').length) $('#txtHtml01').summernote('destroy');
      if ($('#txtHtml02').next('.note-editor').length) $('#txtHtml02').summernote('destroy');

      $('#txtContenido').summernote({ height: 200 }).summernote('code', '');
      $('#txtSubtitulo').summernote({ height: 150 }).summernote('code', '');
      $('#txtHtml01').summernote({ height: 150 }).summernote('code', '');
      $('#txtHtml02').summernote({ height: 150 }).summernote('code', '');
    });
  },

  abrirEditarItem(row) {
    this.itemForm = {
      item_pag_web_id: row.item_pag_web_id,
      titulo: row.titulo,
      clave_txt: row.clave_txt || '',
      precio: row.precio,
      stock: row.stock,
      contenido: row.contenido || '',
      subtitulo_detalle: row.subtitulo_detalle || '',
      html01: row.html01 || '',
      html02: row.html02 || '',
      is_visible: (row.is_visible !== undefined && row.is_visible !== null) ? Number(row.is_visible) : 1,
      cat: null,
      subcat: null
    };

    const sub = this.subcategorias.find(s => s.subcat_pag_web_id == row.subcat_pag_web_id);
    if (sub) {
      const cat = this.categorias.find(c => c.cat_pag_web_id == sub.cat_pag_web_id);
      if (cat) {
        this.itemForm.cat = cat;
        this.$nextTick(() => {
          this.itemForm.subcat = sub;
        });
      }
    }

    $('#modalItem').modal('show');

    this.$nextTick(() => {
      if ($('#txtContenido').next('.note-editor').length) $('#txtContenido').summernote('destroy');
      if ($('#txtSubtitulo').next('.note-editor').length) $('#txtSubtitulo').summernote('destroy');
      if ($('#txtHtml01').next('.note-editor').length) $('#txtHtml01').summernote('destroy');
      if ($('#txtHtml02').next('.note-editor').length) $('#txtHtml02').summernote('destroy');

      $('#txtContenido').summernote({ height: 200 }).summernote('code', row.contenido || '');
      $('#txtSubtitulo').summernote({ height: 150 }).summernote('code', row.subtitulo_detalle || '');
      $('#txtHtml01').summernote({ height: 150 }).summernote('code', row.html01 || '');
      $('#txtHtml02').summernote({ height: 150 }).summernote('code', row.html02 || '');
    });
  },

  guardarItem() {
    this.itemForm.contenido = $('#txtContenido').summernote('code');
    this.itemForm.subtitulo_detalle = $('#txtSubtitulo').summernote('code');
    this.itemForm.html01 = $('#txtHtml01').summernote('code');
    this.itemForm.html02 = $('#txtHtml02').summernote('code');

    const url = this.itemForm.item_pag_web_id
      ? `${this.apphost}/xoxo/reg_item/editar`
      : `${this.apphost}/xoxo/reg_item/crear`;

    if (!this.itemForm.subcat) {
      return apprise('Selecciona subcategoría');
    }

    bloquearUI('Guardando item...');

    axios.post(url, {
      item_pag_web_id: this.itemForm.item_pag_web_id,
      titulo: this.itemForm.titulo,
      clave_txt: this.itemForm.clave_txt,
      precio: this.itemForm.precio,
      stock: this.itemForm.stock,
      contenido: this.itemForm.contenido,
      subtitulo_detalle: this.itemForm.subtitulo_detalle,
      html01: this.itemForm.html01,
      html02: this.itemForm.html02,
      is_visible: this.itemForm.is_visible,
      subcat_id: this.itemForm.subcat?.subcat_pag_web_id || null
    })
    .then(() => {
      $('#modalItem').modal('hide');
      this.cargarTodo();
    })
    .finally(() => desbloquearUI());
  },

  eliminar(id) {
    apprise('¿Eliminar este ítem?', { confirm: true }, ok => {
      if (!ok) return;
      bloquearUI('Eliminando ítem...');
      axios.post(`${this.apphost}/xoxo/reg_item/eliminar`, { item_pag_web_id: id })
        .then(() => this.cargarTodo())
        .finally(() => desbloquearUI());
    });
  }
};