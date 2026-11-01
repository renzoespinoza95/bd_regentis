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
    fetch(this.apphost + '/slider/listar')
    .then(r => r.json())
    .then(data => {
      this.sliders = data.map(s => ({
        ...s,
        img_thumb: s.img
      }));
      this.$nextTick(() => {
        if ($.fn.DataTable.isDataTable('#tablaSliders')) {
          $('#tablaSliders').DataTable().destroy();
        }
        $('#tablaSliders').DataTable({
          language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
          scrollX: true,
          dom: 'frtip',
          order: [[3,'asc']]
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
  onFileChange(e, mode) {
    const file = e.target.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = ev => {
      if (mode === 'crear') {
        this.nuevo.imgFile = file;
        this.nuevo.imgPreview = ev.target.result;
      } else {
        this.formulario.imgFile = file;
        this.formulario.imgPreview = ev.target.result;
        this.formulario.imagenEliminada = false;
      }
    };
    reader.readAsDataURL(file);
  },
  confirmarEliminarImagen() {
    apprise('¿Estás seguro de eliminar la imagen del servidor Bunny?', { confirm: true }, async r => {
      if (r) {
        try {
          bloquearUI('Eliminando imagen de Bunny...');
          
          const res = await fetch(this.apphost + '/slider/eliminar-imagen', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url_imagen: this.formulario.img })
          });
          const data = await res.json();

          desbloquearUI();

          if (data.ok) {
            this.formulario.imgPreview = '';
            this.formulario.imgFile = null;
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
      }
    });
  },
  abrirModalCrear() {
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm   = String(hoy.getMonth() + 1).padStart(2, '0');
    const dd   = String(hoy.getDate()).padStart(2, '0');
    const mediodia = `${yyyy}-${mm}-${dd}T12:00`;

    this.nuevo = {
      imgFile: null,
      imgPreview: '',
      orden: 0,
      is_visible: 1,
      fecha_fin: mediodia,
      neg_id: 0,
      descripcion: ''
    };

    $('#modalCrearSlider').modal('show');
    this.$nextTick(() => {
      $('#snCrearDescripcion').summernote('code', '');
    });
  },
  async crearSlider() {
    const ff = new Date(this.nuevo.fecha_fin);

    if (isNaN(ff.getTime())) {
      return apprise('Debes seleccionar una fecha fin válida', { okBtn: 'Entendido' });
    }

    if (!this.nuevo.imgFile) {
      return apprise('Debes seleccionar una imagen para el slider', { okBtn: 'Entendido' });
    }

    try {
      const negId = this.usuario.neg_id || 0;
      const subfolder = `bd_regentis/neg_${negId}/slider`;

      bloquearUI('Subiendo imagen a Flask y procesando...');
      const cdnUrl = await subirImagenAFlask(this.nuevo.imgFile, subfolder);

      bloquearUI('Guardando slider...');
      const descripcion = $('#snCrearDescripcion').summernote('code');

      const formData = new FormData();
      formData.append('img', cdnUrl);
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
      imgPreview: s.img_thumb,
      imagenEliminada: false
    };

    $('#modalEditarSlider').modal('show');
    setTimeout(() => {
      $('#snEditarDescripcion').summernote('code', s.descripcion || '');
    }, 200);
  },
  crearSliderAutomatico(){
    bloquearUI('Creando slider automático...');

    axios.post(this.apphost + '/GcVL/slider/automatico')
    .then(r => {
      desbloquearUI();
      if(r.data && r.data.success){
        this.obtenerSliders();
        apprise('Slider automático creado 😏');
      } else {
        apprise('Error al crear slider');
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
  eliminarSlider(s) {
    apprise(`¿Eliminar slider #${s.slider_id}?`, { confirm: true }, r => {
      if (r) {
        fetch(this.apphost + '/slider/eliminar', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ slider_id: s.slider_id })
        }).then(() => {
          this.obtenerSliders();
          window.location = apphost + "/slider/inicio";
        });
      }
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