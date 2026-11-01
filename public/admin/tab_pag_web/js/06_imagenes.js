// tab_pag_web/js/06_imagenes.js

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
          if (this.entidadActual === 'item') {
            this.activarDragImagenes();
          }
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
        fetch(urlOrdenar, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ orden })
        })
        .then(r => r.json())
        .then(() => {
          vm.verImagenes(vm.itemActual);
        });
      }
    });
  },

  abrirSubirImagen() {
    this.archivosSeleccionados = [];
    this.previewsTemporales = [];
    
    $('#modalImagenes').modal('hide');
    $('#modalSubirImagen').modal('show');

    const vm = this;
    window.onpaste = function(event) {
      const items = (event.clipboardData || event.originalEvent.clipboardData).items;
      for (let index in items) {
        let item = items[index];
        if (item.kind === 'file') {
          let blob = item.getAsFile();
          if (blob && blob.type.startsWith('image/')) {
            if (vm.entidadActual === 'cat' || vm.entidadActual === 'subcat') {
              vm.archivosSeleccionados = [blob];
              vm.previewsTemporales = [{
                id: Math.random().toString(36).substring(2),
                url: URL.createObjectURL(blob),
                file: blob
              }];
            } else {
              vm.archivosSeleccionados.push(blob);
              vm.previewsTemporales.push({
                id: Math.random().toString(36).substring(2),
                url: URL.createObjectURL(blob),
                file: blob
              });
            }
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

    if (this.entidadActual === 'cat' || this.entidadActual === 'subcat') {
      const file = files[0];
      this.archivosSeleccionados = [file];
      this.previewsTemporales = [{
        id: Math.random().toString(36).substring(2),
        url: URL.createObjectURL(file),
        file: file
      }];
    } else {
      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        this.archivosSeleccionados.push(file);
        this.previewsTemporales.push({
          id: Math.random().toString(36).substring(2),
          url: URL.createObjectURL(file),
          file: file
        });
      }
    }
    e.target.value = '';
  },

  manejarDropArchivos(e) {
    const files = e.dataTransfer.files;
    if (!files || files.length === 0) return;

    if (this.entidadActual === 'cat' || this.entidadActual === 'subcat') {
      const file = files[0];
      if (file.type.startsWith('image/')) {
        this.archivosSeleccionados = [file];
        this.previewsTemporales = [{
          id: Math.random().toString(36).substring(2),
          url: URL.createObjectURL(file),
          file: file
        }];
      }
    } else {
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
    bloquearUI('Subiendo imagen(es)...');

    const archivos = [...this.archivosSeleccionados];
    const negId = window.neg_id || (this.catSeleccionada ? this.catSeleccionada.neg_id : 1); 
    const subfolderVal = `bd_regentis/neg_${negId}/pag_web`;

    const subirSiguiente = (index) => {
      if (index >= archivos.length) {
        desbloquearUI();
        $('#modalSubirImagen').modal('hide');
        
        if (this.entidadActual === 'cat') {
          this.cargarTodo();
          if (this.dtCat) this.renderTablaCat();
          this.verImagenesCategoria(this.itemActual);
        } else if (this.entidadActual === 'subcat') {
          this.cargarTodo();
          if (this.dtSubcat) this.renderTablaSubcatModal();
          this.verImagenesSubcategoria(this.itemActual);
        } else {
          this.verImagenes(this.itemActual);
        }

        apprise('¡Imagen(es) procesada(s) y guardada(s) con éxito!');
        return;
      }

      let f = new FormData();
      f.append('file', archivos[index]);
      f.append('item', this.itemActual);
      f.append('subfolder', subfolderVal);

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
                urlPhpCrear = `${this.apphost}/xoxo/reg_cat/actualizar_url_img`;
                payload = { cat_pag_web_id: this.itemActual, url_img: urlImagenCDN };
              } else if (this.entidadActual === 'subcat') {
                urlPhpCrear = `${this.apphost}/xoxo/reg_subcat/actualizar_url_img`;
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
      urlPhp = `${this.apphost}/xoxo/reg_cat/actualizar_url_img`;
      payload = { cat_pag_web_id: this.itemActual, url_img: this.urlImagenPegar };
    } else if (this.entidadActual === 'subcat') {
      urlPhp = `${this.apphost}/xoxo/reg_subcat/actualizar_url_img`;
      payload = { subcat_pag_web_id: this.itemActual, url_img: this.urlImagenPegar };
    }

    axios.post(urlPhp, payload)
      .then(() => {
        $('#modalPegarImagen').modal('hide');
        setTimeout(() => {
          if (this.entidadActual === 'cat') {
            this.cargarTodo();
            if (this.dtCat) this.renderTablaCat();
            this.verImagenesCategoria(this.itemActual);
          } else if (this.entidadActual === 'subcat') {
            this.cargarTodo();
            if (this.dtSubcat) this.renderTablaSubcatModal();
            this.verImagenesSubcategoria(this.itemActual);
          } else {
            this.verImagenes(this.itemActual);
          }
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
      
      const imgId = img.pag_item_img_id || img.cat_pag_web_id || img.subcat_pag_web_id;
      
      if (self.entidadActual === 'cat') {
        axios.post(`${self.apphost}/xoxo/reg_cat/actualizar_url_img`, { cat_pag_web_id: self.itemActual, url_img: '' })
          .then(() => {
            self.cargarTodo();
            if (self.dtCat) self.renderTablaCat();
            self.verImagenesCategoria(self.itemActual);
          })
          .finally(() => desbloquearUI());
        return;
      } else if (self.entidadActual === 'subcat') {
        axios.post(`${self.apphost}/xoxo/reg_subcat/actualizar_url_img`, { subcat_pag_web_id: self.itemActual, url_img: '' })
          .then(() => {
            self.cargarTodo();
            if (self.dtSubcat) self.renderTablaSubcatModal();
            self.verImagenesSubcategoria(self.itemActual);
          })
          .finally(() => desbloquearUI());
        return;
      }

      axios.post(`${self.apphost}/xoxo/reg_img/eliminar`, { id: imgId })
        .then(() => {
          self.verImagenes(self.itemActual);
        })
        .catch(() => apprise('Error al eliminar'))
        .finally(() => desbloquearUI());
    });
  }
};