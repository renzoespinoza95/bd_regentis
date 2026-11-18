const sliderCrudMethods = {
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
      titulo_superior: '',
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
      formData.append('titulo_superior', this.nuevo.titulo_superior || '');
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

  crearSliderAutomatico() {
    bloquearUI('Creando slider automático...');

    axios.post(this.apphost + '/GcVL/slider/automatico')
    .then(r => {
      if (r.data && r.data.success) {
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

  abrirModalEditar(s) {
    let ff = s.fecha_fin;
    if (ff && ff.length === 10) ff += 'T12:00';

    this.formulario = { 
      ...s, 
      titulo_superior: s.titulo_superior || '',
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
      formData.append('titulo_superior', this.formulario.titulo_superior || '');
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