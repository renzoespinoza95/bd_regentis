const IMG_SUBCATEGORIA_DEFECTO = 'https://barsi-img.b-cdn.net/regx/4GjSVb.jpg';

const subcategoriasMethods = {
  cargarSubcategorias(){
    return axios.get(`${this.apphost}/product/listar_subcategorias`)
    .then(r => {
      if(!Array.isArray(r.data)){
        apprise((r.data && r.data.msg) || 'No se pudieron cargar las subcategorías');
        return;
      }
      this.subcategorias = r.data.map(s => ({
        subcategoria_id: Number(s.subcategoria_id),
        category_id: Number(s.category_id),
        nombre: (s.nombre || '').trim(),
        img: s.img || IMG_SUBCATEGORIA_DEFECTO,
        orden: Number(s.orden || 0),
        is_activo: (s.is_activo === null || s.is_activo === undefined) ? 1 : Number(s.is_activo),
        total_productos: Number(s.total_productos || 0)
      }));

      if(this.subcategoriaSeleccionada !== null &&
         !this.subcategorias.some(s => s.subcategoria_id === this.subcategoriaSeleccionada)){
        this.subcategoriaSeleccionada = null;
        this.actualizarTablaDataTable();
      }
    })
    .catch(() => apprise('Error de conexión al cargar subcategorías'));
  },

  subcategoriasDe(categoryId){
    return this.subcategorias.filter(s => s.category_id === Number(categoryId));
  },

  subActivaEsInactiva(){
    if(this.subcategoriaSeleccionada === null) return false;
    const s = this.subcategorias.find(x => x.subcategoria_id === this.subcategoriaSeleccionada);
    return !!s && s.is_activo === 0;
  },

  seleccionarSubcategoria(id){
    this.subcategoriaSeleccionada = (this.subcategoriaSeleccionada === id) ? null : id;
    this.actualizarTablaDataTable();
  },

  desplazarCarrusel(dir){
    const el = this.$refs.carruselSub;
    if(el){
      el.scrollBy({ left: dir * 240, behavior: 'smooth' });
    }
  },

  abrirModalCrearSubcategoria(){
    if(this.categoriaSeleccionada === null){
      apprise('Selecciona una categoría en "2. CATEGORÍAS" para crear la subcategoría.');
      return;
    }
    this.subNueva = {
      nombre: '',
      img: IMG_SUBCATEGORIA_DEFECTO,
      is_activo: 1,
      urlTmp: '',
      urlsArrastradas: '',
      previewUrlList: []
    };
    $('#modalCrearSubcategoria').modal('show');
    setTimeout(() => {
      const inp = $('#modalCrearSubcategoria input[type=text]').first();
      if(inp.length) inp.focus();
    }, 400);
  },

  aceptarUrlSubNueva(){
    const url = (this.subNueva.urlTmp || '').trim();
    if(!/^https?:\/\//i.test(url)){
      apprise('Ingrese una URL válida (http o https)');
      return;
    }
    this.subNueva.img = url;
    this.subNueva.urlTmp = '';
    apprise('URL aplicada correctamente');
  },

  soltarUrlsSubNueva(e){
    const text = e.dataTransfer && e.dataTransfer.getData('text');
    if(text){
      this.subNueva.urlsArrastradas = (this.subNueva.urlsArrastradas ? this.subNueva.urlsArrastradas + '\n' : '') + text;
      this.actualizarPreviewSubNuevaUrlTextarea();
    }
  },

  actualizarPreviewSubNuevaUrlTextarea(){
    const texto = this.subNueva.urlsArrastradas || '';
    const lineas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 0 && /^https?:\/\//i.test(l));
    this.subNueva.previewUrlList = lineas;
  },

  quitarSubNuevaUrlLista(index){
    let lineas = [...this.subNueva.previewUrlList];
    lineas.splice(index, 1);
    this.subNueva.urlsArrastradas = lineas.join('\n');
    this.actualizarPreviewSubNuevaUrlTextarea();
  },

  aplicarSubNuevaUrlsArrastradas(){
    const lista = this.subNueva.previewUrlList || [];
    if(!lista.length) return;
    const url = lista[0];
    this.subNueva.img = url;
    this.subNueva.urlsArrastradas = '';
    this.subNueva.previewUrlList = [];
    apprise('Imagen aplicada correctamente');
  },

  crearSubcategoria(){
    const s = this.subNueva;
    const nombre = (s.nombre || '').trim();
    if(!nombre){
      apprise('Escribe un nombre para la subcategoría');
      return;
    }
    if(this.categoriaSeleccionada === null){
      apprise('Selecciona una categoría primero');
      return;
    }

    $.blockUI({ message: 'Guardando subcategoría...' });
    axios.post(`${this.apphost}/product/subcategoria_crear`, {
      category_id: Number(this.categoriaSeleccionada),
      nombre: nombre,
      img: s.img || IMG_SUBCATEGORIA_DEFECTO,
      is_activo: s.is_activo === 0 ? 0 : 1
    })
    .then(res => {
      if(res.data.status === 'ok'){
        $('#modalCrearSubcategoria').modal('hide');
        apprise('Subcategoría creada correctamente');
        return this.cargarSubcategorias();
      } else {
        apprise(res.data.msg || 'No se pudo crear la subcategoría');
      }
    })
    .catch(e => apprise((e.response && e.response.data && e.response.data.msg) || 'Error al crear la subcategoría'))
    .finally(() => $.unblockUI());
  },

  abrirModalEditarSubcategoria(){
    if(this.subcategoriaSeleccionada === null) return;
    const s = this.subcategorias.find(x => x.subcategoria_id === this.subcategoriaSeleccionada);
    if(!s) return;

    this.subEditar = {
      subcategoria_id: s.subcategoria_id,
      nombre: s.nombre,
      img: s.img || IMG_SUBCATEGORIA_DEFECTO,
      is_activo: s.is_activo === 0 ? 0 : 1,
      urlTmp: '',
      urlsArrastradas: '',
      previewUrlList: []
    };
    $('#modalEditarSubcategoria').modal('show');
  },

  aceptarUrlSub(){
    const url = (this.subEditar.urlTmp || '').trim();
    if(!/^https?:\/\//i.test(url)){
      apprise('Ingrese una URL válida (http o https)');
      return;
    }
    this.subEditar.img = url;
    this.subEditar.urlTmp = '';
    apprise('URL aplicada correctamente');
  },

  soltarUrlsSub(e){
    const text = e.dataTransfer && e.dataTransfer.getData('text');
    if(text){
      this.subEditar.urlsArrastradas = (this.subEditar.urlsArrastradas ? this.subEditar.urlsArrastradas + '\n' : '') + text;
      this.actualizarPreviewSubUrlTextarea();
    }
  },

  actualizarPreviewSubUrlTextarea(){
    const texto = this.subEditar.urlsArrastradas || '';
    const lineas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 0 && /^https?:\/\//i.test(l));
    this.subEditar.previewUrlList = lineas;
  },

  quitarSubUrlLista(index){
    let lineas = [...this.subEditar.previewUrlList];
    lineas.splice(index, 1);
    this.subEditar.urlsArrastradas = lineas.join('\n');
    this.actualizarPreviewSubUrlTextarea();
  },

  aplicarSubUrlsArrastradas(){
    const lista = this.subEditar.previewUrlList || [];
    if(!lista.length) return;
    if(!this.subEditar.subcategoria_id) return;

    const url = lista[0];
    $.blockUI({ message: 'Actualizando imagen...' });
    axios.post(`${this.apphost}/product/subcategoria_img`, {
      subcategoria_id: this.subEditar.subcategoria_id,
      img: url
    })
    .then(res => {
      if(res.data.status === 'ok'){
        this.subEditar.img = url;
        this.subEditar.urlsArrastradas = '';
        this.subEditar.previewUrlList = [];
        apprise('Imagen de la subcategoría actualizada');
        return this.cargarSubcategorias();
      } else {
        apprise(res.data.msg || 'No se pudo actualizar la imagen');
      }
    })
    .catch(e => apprise((e.response && e.response.data && e.response.data.msg) || 'Error al actualizar la imagen'))
    .finally(() => $.unblockUI());
  },

  guardarSubcategoria(){
    const s = this.subEditar;
    const nombre = (s.nombre || '').trim();
    if(!nombre){
      apprise('Escribe un nombre para la subcategoría');
      return;
    }

    $.blockUI({ message: 'Guardando cambios...' });
    axios.post(`${this.apphost}/product/subcategoria_editar`, {
      subcategoria_id: s.subcategoria_id,
      nombre: nombre,
      img: s.img || '',
      is_activo: s.is_activo === 0 ? 0 : 1
    })
    .then(res => {
      if(res.data.status === 'ok'){
        $('#modalEditarSubcategoria').modal('hide');
        apprise('Subcategoría actualizada correctamente');
        return this.cargarSubcategorias();
      } else {
        apprise(res.data.msg || 'No se pudo actualizar la subcategoría');
      }
    })
    .catch(e => apprise((e.response && e.response.data && e.response.data.msg) || 'Error al actualizar la subcategoría'))
    .finally(() => $.unblockUI());
  },

  eliminarSubcategoria(){
    const s = this.subEditar;
    if(!s || !s.subcategoria_id) return;

    const nombre = this.escaparHtml(s.nombre || '');
    apprise(
      `¿Eliminar la subcategoría <b>${nombre}</b>?<br><br>` +
      `También se eliminarán los productos que dependen de ella.`,
      {confirm: true},
      (ok) => {
        if(!ok) return;

        $.blockUI({ message: 'Eliminando subcategoría...' });
        axios.post(`${this.apphost}/product/subcategoria_eliminar`, {
          subcategoria_id: s.subcategoria_id
        })
        .then(res => {
          if(res.data.status === 'ok'){
            $('#modalEditarSubcategoria').modal('hide');
            this.subcategoriaSeleccionada = null;
            apprise(`Subcategoría eliminada (${res.data.productos || 0} productos)`);
            return Promise.all([
              this.cargarSubcategorias(),
              this.cargarCategorias(),
              this.listar()
            ]);
          } else {
            apprise(res.data.msg || 'No se pudo eliminar la subcategoría');
          }
        })
        .catch(e => apprise((e.response && e.response.data && e.response.data.msg) || 'Error al eliminar la subcategoría'))
        .finally(() => $.unblockUI());
      }
    );
  },

  construirArbol(q){
    const norm = (t) => String(t || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '');
    const b = norm(q).trim();
    const arbol = [];

    this.categorias.forEach(c => {
      const subs = this.subcategoriasDe(c.category_id);
      if(!b){
        arbol.push({ cat: c, subs: subs });
        return;
      }
      if(norm(c.descripcion).includes(b)){
        arbol.push({ cat: c, subs: subs });
        return;
      }
      const coinciden = subs.filter(s => norm(s.nombre).includes(b));
      if(coinciden.length){
        arbol.push({ cat: c, subs: coinciden });
      }
    });
    return arbol;
  },

  toggleSubcategoriaProducto(obj, sub, marcado){
    if(marcado){
      obj.subcategoria_id = sub.subcategoria_id;
      const cid = Number(sub.category_id);
      if(!obj.categorias.map(Number).includes(cid)){
        obj.categorias.push(cid);
      }
    } else if(obj.subcategoria_id === sub.subcategoria_id){
      obj.subcategoria_id = null;
    }
  },

  limpiarSubHuerfana(obj){
    if(obj.subcategoria_id === null || obj.subcategoria_id === undefined) return;
    const sub = this.subcategorias.find(s => s.subcategoria_id === obj.subcategoria_id);
    if(!sub || !obj.categorias.map(Number).includes(sub.category_id)){
      obj.subcategoria_id = null;
    }
  }
};