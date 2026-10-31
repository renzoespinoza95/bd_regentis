const videoMethods = {
  verVideos(id) {
    this.itemActual = id;
    bloquearUI('Cargando videos...');
    axios.get(`${this.apphost}/xoxo/reg_vid/listar`, { params: { id } })
      .then(r => {
        this.videos = r.data.data || [];
        $('#modalVideos').modal('show');
      })
      .finally(() => desbloquearUI());
  },

  abrirSubirVideo() {
    this.videoForm = { titulo: '', codigo: '' };
    $('#previewVideo').html('');
    $('#modalVideos').modal('hide');
    setTimeout(() => {
      $('#modalSubirVideo').modal('show');
    }, 200);
  },

  guardarVideo() {
    if (!this.videoForm.titulo) return apprise('Escribe el título');
    if (!this.videoForm.codigo) return apprise('Ingresa el enlace de Vimeo');

    bloquearUI('Guardando video...');
    axios.post(`${this.apphost}/xoxo/reg_vid/crear`, {
      titulo: this.videoForm.titulo,
      codigo: this.videoForm.codigo,
      item: this.itemActual
    })
    .then(() => {
      $('#modalSubirVideo').modal('hide');
      this.verVideos(this.itemActual);
    })
    .finally(() => desbloquearUI());
  },

  editarVideo(v) {
    this.videoFormEditar = {
      pag_item_vid_id: v.pag_item_vid_id,
      titulo: v.titulo
    };
    $('#modalVideos').modal('hide');
    setTimeout(() => {
      $('#modalEditarVideo').modal('show');
    }, 200);
  },

  guardarEditarVideo() {
    if (!this.videoFormEditar.titulo) {
      return apprise('Escribe el título');
    }

    bloquearUI('Guardando edición...');
    axios.post(`${this.apphost}/xoxo/reg_vid/editar`, {
      id: this.videoFormEditar.pag_item_vid_id,
      titulo: this.videoFormEditar.titulo
    })
    .then(() => {
      $('#modalEditarVideo').modal('hide');
      setTimeout(() => {
        this.verVideos(this.itemActual);
      }, 200);
    })
    .finally(() => desbloquearUI());
  },

  verVideo(v) {
    this.videoActual = v;
    $('#modalVideos').modal('hide');
    setTimeout(() => {
      $('#modalVideoPlayer').modal('show');
    }, 200);
  },

  cerrarVideo() {
    $('#modalVideoPlayer').modal('hide');
    setTimeout(() => {
      $('#modalVideos').modal('show');
    }, 200);
  },

  copiarCodigo(v) {
    const url = v.codigo_web || '';
    const match = url.match(/video\/(\d+)/);
    if (match && match[1]) {
      const limpio = `https://vimeo.com/${match[1]}`;
      navigator.clipboard.writeText(limpio)
        .then(() => apprise('Enlace copiado'))
        .catch(() => apprise('No se pudo copiar'));
    } else {
      apprise('Formato de video inválido');
    }
  },

  eliminarVideo(v) {
    const self = this;
    apprise('¿Eliminar este video?', { confirm: true }, ok => {
      if (!ok) return;
      bloquearUI('Eliminando video...');
      axios.post(`${self.apphost}/xoxo/reg_vid/eliminar`, { id: v.pag_item_vid_id })
        .then(() => self.verVideos(self.itemActual))
        .finally(() => desbloquearUI());
    });
  }
};