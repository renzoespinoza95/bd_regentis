const fotosMethods = {
  abrirFotoProducto(p){
    this.fotoProduct = {
      product_id: parseInt(p.product_id, 10)
    };
    this.archivosSeleccionados = [];
    $('#inputUrlImagenPegar').val('');
    $('#textareaUrlsArrastradas').val('');
    $('#previewUrlsContainer').html('').hide();
    $('#btnAgregarUrlsDrop').hide();
    $('#previewArchivoContainer').html('').hide();
    $('#contenidoDropZone').show();
    $('#btnAgregarArrastradas').hide();
    $('#inputArchivoFoto').val('');
    this.cargarGaleriaImagenes();
    $('#modalFotoProduct').modal('show');
  },

  cargarGaleriaImagenes(){
    axios.get(`${this.apphost}/product/detalle/${this.fotoProduct.product_id}`)
    .then(r => {
      const data = r.data;
      this.galeriaImagenes = data.images || data.imagenes || [];
      this.renderizarGaleriaHtml();
    })
    .catch(() => {
      this.galeriaImagenes = [];
      this.renderizarGaleriaHtml();
    });
  },

  renderizarGaleriaHtml(){
    let html = '';
    
    if(!this.galeriaImagenes || this.galeriaImagenes.length === 0){
      html = `<div style="width: 100%; text-align: center; padding: 15px; color: #bdc3c7; font-style: italic;">No hay imágenes registradas para este producto.</div>`;
    } else {
      this.galeriaImagenes.forEach(imgObj => {
        const imgUrl = imgObj.img || imgObj.url || '';
        const imgId = imgObj.product_image_id || imgObj.id || 0;
        html += `
          <div style="position: relative; width: 110px; height: 110px; border-radius: 8px; border: 1px solid #4e6d8c; overflow: hidden; background: #1a252f;">
            <img src="${imgUrl}" style="width: 100%; height: 100%; object-fit: cover;" />
            <button class="btn-eliminar-img" data-id="${imgId}" title="Eliminar imagen" style="position: absolute; top: 4px; right: 4px; background: rgba(231, 76, 60, 0.9); color: white; border: none; border-radius: 50%; width: 22px; height: 22px; line-height: 20px; text-align: center; font-weight: bold; font-size: 12px; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.5);">
              ×
            </button>
          </div>
        `;
      });
    }

    $('#contenedorGaleriaImgs').html(html);

    const self = this;
    $('.btn-eliminar-img').off('click').on('click', function(){
      const idImg = $(this).data('id');
      self.eliminarImagenGaleria(idImg);
    });
  },

  guardarUrlImagenModal(){
    const url = $('#inputUrlImagenPegar').val();
    if (!url || !url.trim()) {
      apprise('Ingrese una URL válida');
      return;
    }
    
    $.blockUI({ message: 'Guardando imagen...' });
    axios.post(`${this.apphost}/product/imagen_agregar`, {
      product_id: this.fotoProduct.product_id,
      img: url.trim()
    })
    .then(res => {
      if (res.data.status === 'ok') {
        apprise('Imagen agregada correctamente');
        $('#inputUrlImagenPegar').val('');
        this.cargarGaleriaImagenes();
        this.listar();
      } else {
        apprise('Error al guardar la imagen');
      }
    })
    .catch(() => apprise('Error de conexión'))
    .finally(() => $.unblockUI());
  },

  actualizarPreviewsUrls(){
    const texto = $('#textareaUrlsArrastradas').val() || '';
    const lineas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 0 && /^https?:\/\//i.test(l));
    const container = $('#previewUrlsContainer');
    
    container.html('');

    if(lineas.length === 0){
      container.hide();
      $('#btnAgregarUrlsDrop').hide();
      return;
    }

    container.show().css('display', 'flex');
    $('#btnAgregarUrlsDrop').show();

    lineas.forEach((url, index) => {
      const thumbHtml = `
        <div style="position: relative; width: 80px; height: 80px; border-radius: 6px; border: 1px solid #4e6d8c; overflow: hidden; background: #1a252f;">
          <img src="${url}" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='https://barsi-img.b-cdn.net/regx/Wirr77.jpg';" />
          <button class="btn-quitar-url-temporal" data-index="${index}" title="Quitar URL" style="position: absolute; top: 2px; right: 2px; background: rgba(231, 76, 60, 0.9); color: white; border: none; border-radius: 50%; width: 18px; height: 18px; line-height: 16px; text-align: center; font-weight: bold; font-size: 10px; cursor: pointer;">
            ×
          </button>
        </div>
      `;
      container.append(thumbHtml);
    });
  },

  quitarUrlTemporal(index){
    const texto = $('#textareaUrlsArrastradas').val() || '';
    let lineas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 0 && /^https?:\/\//i.test(l));
    lineas.splice(index, 1);
    $('#textareaUrlsArrastradas').val(lineas.join('\n'));
    this.actualizarPreviewsUrls();
  },

  procesarUrlsArrastradas(){
    const texto = $('#textareaUrlsArrastradas').val() || '';
    const lineas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 0 && /^https?:\/\//i.test(l));

    if(lineas.length === 0){
      apprise('Ingrese o suelte al menos una URL válida');
      return;
    }

    $.blockUI({ message: 'Agregando URLs a la tabla...' });

    let index = 0;
    const agregarSiguiente = () => {
      if(index >= lineas.length){
        $.unblockUI();
        apprise('¡URLs agregadas correctamente a la tabla! 🚀');
        $('#textareaUrlsArrastradas').val('');
        $('#previewUrlsContainer').html('').hide();
        $('#btnAgregarUrlsDrop').hide();
        this.cargarGaleriaImagenes();
        this.listar();
        return;
      }

      axios.post(`${this.apphost}/product/imagen_agregar`, {
        product_id: this.fotoProduct.product_id,
        img: lineas[index]
      })
      .then(() => {
        index++;
        agregarSiguiente();
      })
      .catch(() => {
        index++;
        agregarSiguiente();
      });
    };

    agregarSiguiente();
  },

  agregarArchivosDropzone(files){
    if(!this.archivosSeleccionados) this.archivosSeleccionados = [];

    for(let i = 0; i < files.length; i++){
      this.archivosSeleccionados.push(files[i]);
    }

    this.renderizarPreviewsTemporales();
  },

  renderizarPreviewsTemporales(){
    const container = $('#previewArchivoContainer');
    container.html('');

    if(this.archivosSeleccionados.length === 0){
      container.hide();
      $('#contenidoDropZone').show();
      $('#btnAgregarArrastradas').hide();
      return;
    }

    $('#contenidoDropZone').hide();
    container.show().css('display', 'flex');
    $('#btnAgregarArrastradas').show();

    this.archivosSeleccionados.forEach((file, index) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const thumbHtml = `
          <div style="position: relative; width: 90px; height: 90px; border-radius: 6px; border: 1px solid #4e6d8c; overflow: hidden; background: #1a252f;">
            <img src="${e.target.result}" style="width: 100%; height: 100%; object-fit: cover;" />
            <button class="btn-quitar-temporal" data-index="${index}" title="Quitar archivo" style="position: absolute; top: 3px; right: 3px; background: rgba(231, 76, 60, 0.9); color: white; border: none; border-radius: 50%; width: 20px; height: 20px; line-height: 18px; text-align: center; font-weight: bold; font-size: 11px; cursor: pointer;">
              ×
            </button>
          </div>
        `;
        container.append(thumbHtml);
      };
      reader.readAsDataURL(file);
    });
  },

  quitarImagenTemporal(index){
    this.archivosSeleccionados.splice(index, 1);
    this.renderizarPreviewsTemporales();
  },

  agregarImagenesArrastradas(){
    if(!this.archivosSeleccionados || this.archivosSeleccionados.length === 0){
      apprise('No hay imágenes seleccionadas para agregar');
      return;
    }

    const flaskUrl = typeof url_flask !== 'undefined' ? url_flask : '';
    if (!flaskUrl) {
      apprise('Error: url_flask no está definida');
      return;
    }

    const negId = window.neg_id || 1;
    const subfolderVal = `bd_regentis/neg_${negId}/pag_web`;

    $.blockUI({ message: 'Subiendo y agregando imágenes...' });

    const archivos = [...this.archivosSeleccionados];

    const subirSiguiente = (index) => {
      if (index >= archivos.length) {
        $.unblockUI();
        apprise('¡Imagen(es) procesada(s) y agregada(s) con éxito! 🚀');
        this.archivosSeleccionados = [];
        this.renderizarPreviewsTemporales();
        $('#inputArchivoFoto').val('');
        this.cargarGaleriaImagenes();
        this.listar();
        return;
      }

      let formData = new FormData();
      formData.append('file', archivos[index]);
      formData.append('item', this.fotoProduct.product_id);
      formData.append('subfolder', subfolderVal);

      axios.post(`${flaskUrl}/tk_guardar_img`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      .then(res => {
        if (res.data && (res.data.ok || res.data.status === 'success') && res.data.job_id) {
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

  subirImagenArchivo(){
    this.agregarImagenesArrastradas();
  },

  verificarEstadoJobSecuencial(flaskUrl, jobId, callback){
    const intervalo = setInterval(() => {
      axios.get(`${flaskUrl}/jobs/result?job_id=${jobId}`)
      .then(res => {
        const data = res.data;
        if (data.status === 'finished') {
          clearInterval(intervalo);

          const resultObj = data.result;
          if (resultObj && resultObj.ok && resultObj.url) {
            axios.post(`${this.apphost}/product/imagen_agregar`, {
              product_id: this.fotoProduct.product_id,
              img: resultObj.url
            })
            .then(() => {
              if (typeof callback === 'function') callback();
            })
            .catch(() => {
              if (typeof callback === 'function') callback();
            });
          } else {
            if (typeof callback === 'function') callback();
          }

        } else if (data.status === 'failed') {
          clearInterval(intervalo);
          if (typeof callback === 'function') callback();
        }
      })
      .catch(() => {
        clearInterval(intervalo);
        if (typeof callback === 'function') callback();
      });
    }, 2000);
  },

  eliminarImagenGaleria(product_image_id){
    apprise('¿Desea eliminar esta imagen?', {'confirm': true}, (r) => {
      if (!r) return;

      $.blockUI({ message: 'Eliminando imagen...' });
      axios.post(`${this.apphost}/product/imagen_eliminar`, {
        product_image_id: product_image_id
      })
      .then(res => {
        if (res.data.status === 'ok') {
          apprise('Imagen eliminada');
          this.cargarGaleriaImagenes();
          this.listar();
        } else {
          apprise(res.data.msg || 'No se pudo eliminar');
        }
      })
      .catch(() => apprise('Error al eliminar'))
      .finally(() => $.unblockUI());
    });
  }
};