// F:\xampp\htdocs\bd_regentis\public\admin\tab_pag_web\js\06_imagenes.js

const imagenMethods = {
  // ==========================================
  // APERTURA DE IMÁGENES SEGÚN ENTIDAD
  // ==========================================
  verImagenes(id) {
    this.entidadActual = 'item';
    this.itemActual = id;
    this.cargarListadoImagenes(`${this.apphost}/xoxo/reg_img/listar`, { id });
  },

  verImagenesCategoria(id) {
    this.entidadActual = 'cat';
    this.itemActual = id;
    this.cargarListadoImagenes(`${this.apphost}/xoxo/reg_cat_img/listar`, { id });
  },

  verImagenesSubcategoria(id) {
    this.entidadActual = 'subcat';
    this.itemActual = id;
    this.cargarListadoImagenes(`${this.apphost}/xoxo/reg_subcat_img/listar`, { id });
  },

  cargarListadoImagenes(urlEndpoint, params) {
    bloquearUI('Cargando imágenes...');
    axios.get(urlEndpoint, { params })
      .then(r => {
        this.imagenes = r.data.data || [];
        this.$nextTick(() => {
          this.activarDragImagenes();
        });
        $('#modalImagenes').modal('show');
      })
      .finally(() => desbloquearUI());
  },

  activarDragImagenes() {
    const vm = this;
    $('#contenedorImagenes').sortable({
      items: '.img-item',
      update: function () {
        let orden = [];
        $('#contenedorImagenes .img-item').each(function (index) {
          orden.push({
            id: $(this).data('id'),
            orden: index + 1
          });
        });

        let urlOrdenar = `${vm.apphost}/xoxo/reg_img/ordenar`;
        if (vm.entidadActual === 'cat') urlOrdenar = `${vm.apphost}/xoxo/reg_cat_img/ordenar`;
        if (vm.entidadActual === 'subcat') urlOrdenar = `${vm.apphost}/xoxo/reg_subcat_img/ordenar`;

        fetch(urlOrdenar, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ orden })
        })
        .then(r => r.json())
        .then(() => {
          if (vm.entidadActual === 'cat') vm.verImagenesCategoria(vm.itemActual);
          else if (vm.entidadActual === 'subcat') vm.verImagenesSubcategoria(vm.itemActual);
          else vm.verImagenes(vm.itemActual);
        });
      }
    });
  },

  abrirSubirImagen() {
    this.archivosSeleccionados = [];
    this.previewsTemporales = [];
    
    $('#modalImagenes').modal('hide');
    $('#modalSubirImagen').modal('show');

    // Escuchar evento global de pegado (Ctrl + V) mientras el modal esté abierto
    const vm = this;
    window.onpaste = function(event) {
      const items = (event.clipboardData || event.originalEvent.clipboardData).items;
      for (let index in items) {
        let item = items[index];
        if (item.kind === 'file') {
          let blob = item.getAsFile();
          if (blob && blob.type.startsWith('image/')) {
            vm.archivosSeleccionados.push(blob);
            vm.previewsTemporales.push({
              id: Math.random().toString(36).substring(2),
              url: URL.createObjectURL(blob),
              file: blob
            });
          }
        }
      }
    };
  },

  dispararInputFile() {
    const input = document.getElementById('fileImg');
    if (input) input.click();
  },

  seleccionarMultiplesImganes(e) {
    const files = e.target.files;
    if (!files || files.length === 0) return;

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      this.archivosSeleccionados.push(file);
      this.previewsTemporales.push({
        id: Math.random().toString(36).substring(2),
        url: URL.createObjectURL(file),
        file: file
      });
    }
    e.target.value = '';
  },

  manejarDropArchivos(e) {
    const files = e.dataTransfer.files;
    if (!files || files.length === 0) return;

    for (let i = 0; i < files.length; i++) {
      const file = files[i];
      if (file.type.startsWith('image/')) {
        this.archivosSeleccionados.push(file);
        this.previewsTemporales.push({
          id: Math.random().toString(36).substring(2),
          url: URL.createObjectURL(file),
          file: file
        });
      }
    }
  },

  quitarImagenTemporal(index) {
    this.previewsTemporales.splice(index, 1);
    this.archivosSeleccionados.splice(index, 1);
  },

  guardarMultiplesImganes() {
    if (this.archivosSeleccionados.length === 0) {
      return apprise('Selecciona o arrastra al menos una imagen');
    }

    const flaskUrl = typeof url_flask !== 'undefined' ? url_flask : '';
    if (!flaskUrl) {
      return apprise('Error: url_flask no está definida');
    }

    window.onpaste = null;
    bloquearUI('Subiendo imágenes al worker...');

    const archivos = [...this.archivosSeleccionados];

    const subirSiguiente = (index) => {
      if (index >= archivos.length) {
        desbloquearUI();
        $('#modalSubirImagen').modal('hide');
        
        if (this.entidadActual === 'cat') this.verImagenesCategoria(this.itemActual);
        else if (this.entidadActual === 'subcat') this.verImagenesSubcategoria(this.itemActual);
        else this.verImagenes(this.itemActual);

        apprise('¡Todas las imágenes se procesaron y guardaron con éxito!');
        return;
      }

      let f = new FormData();
      f.append('file', archivos[index]);
      f.append('item', this.itemActual);

      axios.post(`${flaskUrl}/tk_guardar_img`, f, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      .then(res => {
        if (res.data && res.data.ok) {
          const jobId = res.data.job_id;
          this.verificarEstadoJobSecuencial(flaskUrl, jobId, () => {
            subirSiguiente(index + 1);
          });
        } else {
          subirSiguiente(index + 1);
        }
      })
      .catch(() => {
        subirSiguiente(index + 1);
      });
    };

    subirSiguiente(0);
  },

  verificarEstadoJobSecuencial(flaskUrl, jobId, callback) {
    const intervalo = setInterval(() => {
      axios.get(`${flaskUrl}/jobs/result?job_id=${jobId}`)
        .then(res => {
          const status = res.data.status;
          if (status === 'finished') {
            clearInterval(intervalo);
            const resultadoWorker = res.data.result;
            const urlImagenCDN = resultadoWorker ? resultadoWorker.url : null;

            if (urlImagenCDN) {
              let urlPhpCrear = `${this.apphost}/xoxo/reg_img/crear_desde_url`;
              let payload = { item_pag_web_id: this.itemActual, url_img: urlImagenCDN };

              if (this.entidadActual === 'cat') {
                urlPhpCrear = `${this.apphost}/xoxo/reg_cat_img/crear_desde_url`;
                payload = { cat_pag_web_id: this.itemActual, url_img: urlImagenCDN };
              } else if (this.entidadActual === 'subcat') {
                urlPhpCrear = `${this.apphost}/xoxo/reg_subcat_img/crear_desde_url`;
                payload = { subcat_pag_web_id: this.itemActual, url_img: urlImagenCDN };
              }

              axios.post(urlPhpCrear, payload)
                .then(() => callback())
                .catch(() => callback());
            } else {
              callback();
            }
          } else if (status === 'failed') {
            clearInterval(intervalo);
            callback();
          }
        })
        .catch(() => {
          clearInterval(intervalo);
          callback();
        });
    }, 1500);
  },

  verImagenCompleta(img) {
    this.imagenModalUrl = img.url_img;
    $('#modalVerImagenFull').modal('show');
  },

  abrirPegarImagen() {
    this.urlImagenPegar = '';
    $('#modalImagenes').modal('hide');
    setTimeout(() => {
      $('#modalPegarImagen').modal('show');
    }, 200);
  },

  guardarImagenDesdeURL() {
    if (!this.urlImagenPegar) {
      return apprise('Pega una URL válida');
    }

    bloquearUI('Guardando URL...');
    
    let urlPhp = `${this.apphost}/xoxo/reg_img/crear_url`;
    let payload = { url: this.urlImagenPegar, item: this.itemActual };

    if (this.entidadActual === 'cat') {
      urlPhp = `${this.apphost}/xoxo/reg_cat_img/crear_url`;
      payload = { url: this.urlImagenPegar, cat: this.itemActual };
    } else if (this.entidadActual === 'subcat') {
      urlPhp = `${this.apphost}/xoxo/reg_subcat_img/crear_url`;
      payload = { url: this.urlImagenPegar, subcat: this.itemActual };
    }

    axios.post(urlPhp, payload)
      .then(() => {
        $('#modalPegarImagen').modal('hide');
        setTimeout(() => {
          if (this.entidadActual === 'cat') this.verImagenesCategoria(this.itemActual);
          else if (this.entidadActual === 'subcat') this.verImagenesSubcategoria(this.itemActual);
          else this.verImagenes(this.itemActual);
        }, 200);
      })
      .finally(() => desbloquearUI());
  },

  copiarImagen(img) {
    navigator.clipboard.writeText(img.url_img)
      .then(() => apprise('Imagen copiada'))
      .catch(() => apprise('No se pudo copiar'));
  },

  eliminarImagen(img) {
    const self = this;
    apprise('¿Eliminar imagen?', { confirm: true }, ok => {
      if (!ok) return;
      bloquearUI('Eliminando imagen...');
      
      const imgId = img.pag_item_img_id || img.cat_pag_img_id || img.subcat_pag_img_id;
      let urlEliminar = `${self.apphost}/xoxo/reg_img/eliminar`;
      
      if (self.entidadActual === 'cat') urlEliminar = `${self.apphost}/xoxo/reg_cat_img/eliminar`;
      if (self.entidadActual === 'subcat') urlEliminar = `${self.apphost}/xoxo/reg_subcat_img/eliminar`;

      axios.post(urlEliminar, { id: imgId })
        .then(() => {
          if (self.entidadActual === 'cat') self.verImagenesCategoria(self.itemActual);
          else if (self.entidadActual === 'subcat') self.verImagenesSubcategoria(self.itemActual);
          else self.verImagenes(self.itemActual);
        })
        .catch(() => apprise('Error al eliminar'))
        .finally(() => desbloquearUI());
    });
  }
};