const categoriasMethods = {
  cargarCategorias(){
    return axios.get(`${this.apphost}/product/listar_categorias`)
    .then(r => {
      if(!Array.isArray(r.data)){
        apprise((r.data && r.data.msg) || 'No se pudieron cargar las categorías');
        return;
      }
      this.categorias = r.data.map(c => ({
        category_id: Number(c.category_id),
        name: (c.name || c.descripcion || '').trim(),
        descripcion: (c.descripcion || c.name || '').trim(),
        brief: c.brief || '',
        clave_txt: c.clave_txt || '',
        icon: c.icon || '',
        color: c.color || '#999999',
        img: c.img || '',
        is_activo: (c.is_activo === null || c.is_activo === undefined) ? 1 : Number(c.is_activo),
        total_productos: Number(c.total_productos || 0)
      }));
    })
    .catch(() => apprise('Error de conexión al cargar categorías'));
  },

  esEmoji(txt){
    return !!txt && /\p{Extended_Pictographic}/u.test(txt);
  },

  // true si la categoría seleccionada tiene is_activo = 0
  catActivaEsInactiva(){
    if(this.categoriaSeleccionada === null) return false;
    const c = this.categorias.find(x => x.category_id == this.categoriaSeleccionada);
    return !!c && c.is_activo === 0;
  },

  catVacia(){
    return {
      category_id: null,
      name: '',
      icon: '',
      color: '#999999',
      brief: '',
      clave_txt: '',
      img: '',
      is_activo: 1,
      file: null,
      preview: '',
      panelUrl: false,
      urlTmp: '',
      urlsArrastradas: '',
      previewUrlList: []
    };
  },

  liberarPreviewCat(cat){
    if(cat && cat.preview){
      URL.revokeObjectURL(cat.preview);
    }
  },

  payloadCategoria(cat){
    return {
      category_id: cat.category_id,
      name: (cat.name || '').trim(),
      icon: cat.icon || '',
      color: cat.color || '#999999',
      brief: (cat.brief || '').trim(),
      clave_txt: (cat.clave_txt || '').trim(),
      img: cat.img || '',
      is_activo: (cat.is_activo === 0 || cat.is_activo === false) ? 0 : 1
    };
  },

  // ---------- NUEVA ----------
  abrirModalCrearCategoria(){
    this.liberarPreviewCat(this.catNueva);
    this.catNueva = this.catVacia();
    $('#modalCrearCategoria').modal('show');
  },

  crearCategoria(){
    const cat = this.catNueva;
    if(!cat.name || !cat.name.trim()){
      apprise('Escribe un nombre para la categoría');
      return;
    }

    $.blockUI({ message: 'Guardando categoría...' });
    this.$refs.formNueva.asegurarImagen()
    .then(() => axios.post(`${this.apphost}/product/categoria_crear`, this.payloadCategoria(cat)))
    .then(res => {
      if(res.data.status === 'ok'){
        $('#modalCrearCategoria').modal('hide');
        apprise('Categoría creada correctamente');
        this.cargarCategorias();
      } else {
        apprise(res.data.msg || 'No se pudo crear la categoría');
      }
    })
    .catch(e => apprise((e && e.message) || 'Error al crear la categoría'))
    .finally(() => $.unblockUI());
  },

  // ---------- EDITAR ----------
  abrirModalEditarCategoria(){
    if(this.categoriaSeleccionada === null) return;
    const c = this.categorias.find(x => x.category_id == this.categoriaSeleccionada);
    if(!c) return;

    this.liberarPreviewCat(this.catEditar);
    this.catEditar = Object.assign(this.catVacia(), {
      category_id: c.category_id,
      name: c.name || c.descripcion,
      icon: c.icon || '',
      color: c.color || '#999999',
      brief: c.brief || '',
      clave_txt: c.clave_txt || '',
      img: c.img || '',
      is_activo: c.is_activo === 0 ? 0 : 1
    });
    $('#modalEditarCategoria').modal('show');
  },

  guardarCategoria(){
    const cat = this.catEditar;
    if(!cat.name || !cat.name.trim()){
      apprise('Escribe un nombre para la categoría');
      return;
    }

    $.blockUI({ message: 'Guardando cambios...' });
    this.$refs.formEditar.asegurarImagen()
    .then(() => axios.post(`${this.apphost}/product/categoria_editar`, this.payloadCategoria(cat)))
    .then(res => {
      if(res.data.status === 'ok'){
        $('#modalEditarCategoria').modal('hide');
        apprise('Categoría actualizada correctamente');
        this.cargarCategorias();
      } else {
        apprise(res.data.msg || 'No se pudo actualizar la categoría');
      }
    })
    .catch(e => apprise((e && e.message) || 'Error al actualizar la categoría'))
    .finally(() => $.unblockUI());
  },

  // ---------- ELIMINAR (soft delete en cascada, desde el danger zone) ----------
  eliminarCategoria(){
    const cat = this.catEditar;
    if(!cat || !cat.category_id) return;

    const nombre = this.escaparHtml(cat.name || '');
    apprise(
      `¿Eliminar la categoría <b>${nombre}</b>?<br><br>` +
      `También se eliminarán sus subcategorías y los productos que dependen de ella.`,
      {confirm: true},
      (ok) => {
        if(!ok) return;

        $.blockUI({ message: 'Eliminando categoría...' });
        axios.post(`${this.apphost}/product/categoria_eliminar`, {
          category_id: cat.category_id
        })
        .then(res => {
          if(res.data.status === 'ok'){
            $('#modalEditarCategoria').modal('hide');
            this.categoriaSeleccionada = null;
            this.subcategoriaSeleccionada = null;
            apprise(
              `Categoría eliminada (${res.data.subcategorias || 0} subcategorías, ` +
              `${res.data.productos || 0} productos)`
            );
            return Promise.all([
              this.cargarCategorias(),
              this.cargarSubcategorias(),
              this.listar()
            ]);
          } else {
            apprise(res.data.msg || 'No se pudo eliminar la categoría');
          }
        })
        .catch(e => apprise((e.response && e.response.data && e.response.data.msg) || 'Error al eliminar la categoría'))
        .finally(() => $.unblockUI());
      }
    );
  },

  generarReporteCategoria(){
    // TODO: conectar con el endpoint real que genera el PDF.
    // this.reporteCategorias contiene las categorías seleccionadas
    // (si está vacío, el reporte debe incluir todas).
    const ids = (this.reporteCategorias || []).map(c => c.category_id);
    console.log('Reporte por categoría, ids:', ids);
    apprise('Reporte por categoría: falta conectar el endpoint del PDF');
  },

  getCategoriaLabel(id) {
    const cat = this.categorias.find(c => c.category_id === id);
    return cat ? cat.descripcion : id;
  }
};