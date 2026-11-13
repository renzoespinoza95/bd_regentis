const sliderMethods = {
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

  // ================= Visor de imagen a pantalla completa =================
  verImagenGrande(url) {
    if (url) this.imagenGrande = url;
  },
  cerrarImagen() {
    this.imagenGrande = '';
  },

  // ================= Zona de imagen (crear / editar) =================
  objetivo(mode) {
    return mode === 'crear' ? this.nuevo : this.formulario;
  },
  limpiarInputArchivo(mode) {
    const ref = mode === 'crear' ? this.$refs.fileCrear : this.$refs.fileEditar;
    if (ref) ref.value = '';
  },
  extraerUrls(texto) {
    const vistos = {};
    return String(texto || '')
      .split(/\s+/)
      .map(u => u.trim())
      .filter(u => /^https?:\/\/\S+$/i.test(u))
      .filter(u => (vistos[u] ? false : (vistos[u] = true)));
  },
  cargarArchivo(file, mode) {
    if (!file) return;
    if (!file.type || file.type.indexOf('image/') !== 0) {
      apprise('El archivo seleccionado no es una imagen', { okBtn: 'Entendido' });
      return;
    }
    const t = this.objetivo(mode);
    const reader = new FileReader();
    reader.onload = ev => {
      t.imgFile = file;
      t.imgUrl = '';
      t.imgPreview = ev.target.result;
      if (mode === 'editar') t.imagenEliminada = false;
    };
    reader.readAsDataURL(file);
  },
  onFileChange(e, mode) {
    const file = e.target.files[0];
    if (!file) return;
    this.cargarArchivo(file, mode);
  },
  establecerUrl(url, mode) {
    const t = this.objetivo(mode);
    t.imgFile = null;
    t.imgUrl = url;
    t.imgPreview = url;
    if (mode === 'editar') t.imagenEliminada = false;
    this.limpiarInputArchivo(mode);
  },
  aceptarUrl(mode) {
    const t = this.objetivo(mode);
    const urls = this.extraerUrls(t.urlInput + '\n' + t.urlsTexto);

    if (!urls.length) {
      return apprise('Ingresa una URL válida que empiece con http:// o https://', { okBtn: 'Entendido' });
    }

    this.establecerUrl(urls[0], mode);
    t.urlInput = '';
    t.urlsTexto = '';

    if (urls.length > 1) {
      apprise('Esta entidad tiene una sola imagen: se tomó la primera URL.', { okBtn: 'Entendido' });
    }
  },
  pegarUrls(mode) {
    setTimeout(() => {
      const t = this.objetivo(mode);
      const urls = this.extraerUrls(t.urlsTexto);
      if (urls.length) this.establecerUrl(urls[0], mode);
    }, 0);
  },
  zonaDragOver(mode) {
    this.objetivo(mode).arrastrando = true;
  },
  zonaDragLeave(mode) {
    this.objetivo(mode).arrastrando = false;
  },
  zonaDrop(e, mode) {
    const t = this.objetivo(mode);
    t.arrastrando = false;
    const dt = e.dataTransfer;
    if (!dt) return;

    // 1) Archivos de imagen arrastrados
    if (dt.files && dt.files.length) {
      const archivos = Array.from(dt.files).filter(f => f.type && f.type.indexOf('image/') === 0);
      if (archivos.length) {
        this.cargarArchivo(archivos[0], mode);
        if (archivos.length > 1) {
          apprise('Esta entidad tiene una sola imagen: se tomó la primera.', { okBtn: 'Entendido' });
        }
        return;
      }
    }

    // 2) Enlaces (URL) arrastrados
    const texto = dt.getData('text/uri-list') || dt.getData('text/plain') || '';
    const urls = this.extraerUrls(texto);

    if (urls.length) {
      t.urlsTexto = urls.join('\n');
      this.establecerUrl(urls[0], mode);
      if (urls.length > 1) {
        apprise('Esta entidad tiene una sola imagen: se tomó la primera URL.', { okBtn: 'Entendido' });
      }
    } else {
      apprise('Suelta una imagen o un enlace URL válido', { okBtn: 'Entendido' });
    }
  },
  quitarImagen(mode) {
    const t = this.objetivo(mode);

    // Hay una imagen pendiente (archivo o URL aún no guardada): solo se descarta
    if (t.imgFile || t.imgUrl) {
      t.imgFile = null;
      t.imgUrl = '';
      this.limpiarInputArchivo(mode);
      t.imgPreview = (mode === 'editar' && !t.imagenEliminada) ? (t.img || '') : '';
      return;
    }

    if (mode === 'crear') {
      t.imgPreview = '';
      return;
    }

    // Editar: imagen ya guardada en el registro
    this.confirmarEliminarImagen();
  },
  usarUrl(mode) {
    const t = this.objetivo(mode);

    let url = t.imgUrl;
    if (!url) {
      const urls = this.extraerUrls(t.urlInput + '\n' + t.urlsTexto);
      url = urls[0];
    }

    if (!url) {
      return apprise('Ingresa o pega una URL válida de imagen', { okBtn: 'Entendido' });
    }

    // Nuevo slider: aún no existe el registro, solo se deja lista la URL
    if (mode === 'crear') {
      this.establecerUrl(url, mode);
      t.urlInput = '';
      t.urlsTexto = '';
      return apprise('URL lista. Se guardará al presionar "Crear".', { okBtn: 'Ok' });
    }

    // Editar slider: actualiza el registro de reg_slider
    if (!t.slider_id) {
      return apprise('ID inválido');
    }

    bloquearUI('Actualizando imagen...');
    fetch(this.apphost + '/slider/actualizar-imagen', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ slider_id: t.slider_id, img: url })
    })
      .then(r => r.json())
      .then(data => {
        if (data.success) {
          t.img = url;
          t.imgUrl = '';
          t.imgFile = null;
          t.imgPreview = url;
          t.imagenEliminada = false;
          t.urlInput = '';
          t.urlsTexto = '';
          this.limpiarInputArchivo('editar');
          return this.obtenerSliders().then(() => {
            desbloquearUI();
            apprise('Imagen actualizada correctamente');
          });
        }
        desbloquearUI();
        apprise('Error: ' + (data.error || 'Desconocido'));
      })
      .catch(err => {
        desbloquearUI();
        apprise('Error de conexión');
        console.error(err);
      });
  },

  confirmarEliminarImagen() {
    const urlActual = this.formulario.img || '';

    if (!urlActual) {
      this.formulario.imgPreview = '';
      this.formulario.imagenEliminada = true;
      return;
    }

    const esBunny = urlActual.indexOf('barsi-img.b-cdn.net') !== -1;
    const mensaje = esBunny
      ? '¿Estás seguro de eliminar la imagen del servidor Bunny?'
      : '¿Quitar la imagen de este slider?';

    apprise(mensaje, { confirm: true }, async r => {
      if (!r) return;

      // Imagen externa: solo se quita del formulario, se guarda con "Guardar"
      if (!esBunny) {
        this.formulario.imgPreview = '';
        this.formulario.img = '';
        this.formulario.imgFile = null;
        this.formulario.imgUrl = '';
        this.formulario.imagenEliminada = true;
        return;
      }

      try {
        bloquearUI('Eliminando imagen de Bunny...');

        const res = await fetch(this.apphost + '/slider/eliminar-imagen', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ url_imagen: urlActual })
        });
        const data = await res.json();

        desbloquearUI();

        if (data.ok) {
          this.formulario.imgPreview = '';
          this.formulario.imgFile = null;
          this.formulario.imgUrl = '';
          this.formulario.img = '';
          this.formulario.imagenEliminada = true;
          apprise('Imagen eliminada de Bunny correctamente');
        } else {
          apprise('Error al eliminar: ' + (data.error || 'Desconocido'));
        }
      } catch (e) {
        desbloquearUI();
        apprise('Error de conexión');
        console.error(e);
      }
    });
  },

  // ================= Crear / Editar =================
  abrirModalCrear() {
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm   = String(hoy.getMonth() + 1).padStart(2, '0');
    const dd   = String(hoy.getDate()).padStart(2, '0');
    const mediodia = `${yyyy}-${mm}-${dd}T12:00`;

    this.nuevo = {
      imgFile: null,
      imgUrl: '',
      imgPreview: '',
      urlInput: '',
      urlsTexto: '',
      arrastrando: false,
      orden: 0,
      is_visible: 1,
      fecha_fin: mediodia,
      neg_id: 0,
      descripcion: ''
    };

    $('#modalCrearSlider').modal('show');
    this.$nextTick(() => {
      this.limpiarInputArchivo('crear');
      $('#snCrearDescripcion').summernote('code', '');
    });
  },

  async crearSlider() {
    const ff = new Date(this.nuevo.fecha_fin);

    if (isNaN(ff.getTime())) {
      return apprise('Debes seleccionar una fecha fin válida', { okBtn: 'Entendido' });
    }

    if (!this.nuevo.imgFile && !this.nuevo.imgUrl) {
      return apprise('Debes seleccionar una imagen o indicar una URL para el slider', { okBtn: 'Entendido' });
    }

    try {
      const negId = this.usuario.neg_id || 0;
      const subfolder = `bd_regentis/neg_${negId}/slider`;

      let imgFinal = this.nuevo.imgUrl;

      if (this.nuevo.imgFile) {
        bloquearUI('Subiendo imagen a Flask y procesando...');
        imgFinal = await subirImagenAFlask(this.nuevo.imgFile, subfolder);
      }

      bloquearUI('Guardando slider...');
      const descripcion = $('#snCrearDescripcion').summernote('code');

      const formData = new FormData();
      formData.append('img', imgFinal);
      formData.append('orden', this.nuevo.orden);
      formData.append('is_visible', this.nuevo.is_visible);
      formData.append('fecha_fin', this.nuevo.fecha_fin);
      formData.append('neg_id', negId);
      formData.append('descripcion', descripcion);        

      const res = await fetch(apphost + '/slider/crear', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      desbloquearUI();

      if (data.success) {
        $('#modalCrearSlider').modal('hide');
        this.obtenerSliders();
        apprise('Slider creado', { okBtn: 'Ok' }, () => {
          window.location.reload();
        });
      } else {
        apprise('Error: ' + (data.error || 'Desconocido'));
      }
    } catch (e) {
      desbloquearUI();
      apprise('Error: ' + e.message);
      console.error(e);
    }
  },

  actualizarDescripcion() {
    if (!this.formulario || !this.formulario.slider_id) {
        apprise('ID inválido');
        return;
    }

    let descripcion = $('#snEditarDescripcion').summernote('code');
    bloquearUI('Guardando descripción...');

    const formData = new FormData();
    formData.append('slider_id', this.formulario.slider_id);
    formData.append('descripcion', descripcion);

    axios.post(this.apphost + '/slider/actualizarDescripcion', formData)
    .then(res => {
        desbloquearUI();
        if (res.data && res.data.success) {
            apprise('Descripción actualizada correctamente');
            $('#modalEditarSlider').modal('hide');
            this.obtenerSliders();
        } else {
            apprise('Error: ' + (res.data.error || 'Error desconocido'));
        }
    })
    .catch(() => {
        desbloquearUI();
        apprise('Error de conexión');
    });
  },

  abrirModalEditar(s) {
    let ff = s.fecha_fin;
    if (ff && ff.length === 10) ff += 'T12:00';

    this.formulario = { 
      ...s, 
      fecha_fin: ff,
      imgFile: null,
      imgUrl: '',
      imgPreview: s.img_thumb || s.img || '',
      imagenEliminada: false,
      urlInput: '',
      urlsTexto: '',
      arrastrando: false
    };

    $('#modalEditarSlider').modal('show');
    this.$nextTick(() => this.limpiarInputArchivo('editar'));
    setTimeout(() => {
      $('#snEditarDescripcion').summernote('code', s.descripcion || '');
    }, 200);
  },

  crearSliderAutomatico(){
    bloquearUI('Creando slider automático...');

    axios.post(this.apphost + '/GcVL/slider/automatico')
    .then(r => {
      if(r.data && r.data.success){
        // Se mantiene el bloqueo hasta que el DataTable ya esté actualizado
        return this.obtenerSliders().then(() => {
          desbloquearUI();
          apprise('Slider automático creado 😏');
        });
      }
      desbloquearUI();
      apprise('Error al crear slider');
    })
    .catch(() => {
      desbloquearUI();
      apprise('Error de conexión');
    });
  },

  async guardarEdicion() {
    const ff = new Date(this.formulario.fecha_fin);

    if (isNaN(ff.getTime())) {
      return apprise('Debes seleccionar una fecha fin válida');
    }

    try {
      const negId = this.usuario.neg_id || this.formulario.neg_id || 0;
      const subfolder = `bd_regentis/neg_${negId}/slider`;

      let cdnUrl = '';
      if (this.formulario.imgFile) {
        bloquearUI('Subiendo nueva imagen a Flask y procesando...');
        cdnUrl = await subirImagenAFlask(this.formulario.imgFile, subfolder);
      } else if (this.formulario.imgUrl) {
        cdnUrl = this.formulario.imgUrl;
      }

      bloquearUI('Actualizando slider...');
      const descripcion = $('#snEditarDescripcion').summernote('code');

      const formData = new FormData();
      formData.append('slider_id', this.formulario.slider_id);
      if (cdnUrl) {
        formData.append('img', cdnUrl);
      } else if (this.formulario.imagenEliminada) {
        formData.append('img', '');
      }
      formData.append('imagen_eliminada', this.formulario.imagenEliminada ? '1' : '0');
      formData.append('orden', this.formulario.orden);
      formData.append('is_visible', this.formulario.is_visible);
      formData.append('fecha_fin', this.formulario.fecha_fin);
      formData.append('neg_id', negId);
      formData.append('descripcion', descripcion);      

      const res = await fetch(apphost + '/slider/editar', {
        method: 'POST',
        body: formData
      });
      const data = await res.json();

      desbloquearUI();

      if (data.success) {
        $('#modalEditarSlider').modal('hide');
        this.obtenerSliders();
        apprise('Slider actualizado');
      } else {
        apprise('Error: ' + (data.error || 'Desconocido'));
      }
    } catch (e) {
      desbloquearUI();
      apprise('Error: ' + e.message);
      console.error(e);
    }
  },

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

  // Soft delete: el servidor marca borrado_el, no borra el registro
  eliminarSlider(s) {
    apprise(`¿Eliminar slider #${s.slider_id}?`, { confirm: true }, r => {
      if (!r) return;

      bloquearUI('Eliminando slider...');
      fetch(this.apphost + '/slider/eliminar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ slider_id: s.slider_id })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          return this.obtenerSliders().then(() => desbloquearUI());
        }
        desbloquearUI();
        apprise('Error: ' + (data.error || 'Desconocido'));
      })
      .catch(() => {
        desbloquearUI();
        apprise('Error de conexión');
      });
    });
  },

  abrirModalDetalle(s) {
    fetch(this.apphost + '/slider/detalle/' + s.slider_id)
      .then(r => r.json())
      .then(data => {
        const cdn_url = data.img;
        this.detalle = { ...data, img_thumb: cdn_url };
        $('#modalDetalleSlider').modal('show');
      });
  }
};