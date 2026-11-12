Vue.component('categoria-form', {
  props: {
    cat: { type: Object, required: true },
    emojis: { type: Array, default: function(){ return []; } }
  },
  template: '#tpl-categoria-form',
  data(){
    return { hover: false };
  },
  computed: {
    previewSrc(){
      return this.cat.preview || this.cat.img || '';
    }
  },
  methods: {
    abrirSelector(){
      this.$refs.input.click();
    },

    elegir(e){
      const files = e.target.files;
      if(files && files.length){
        this.tomar(files[0]);
      }
      e.target.value = '';
    },

    soltarArchivo(e){
      this.hover = false;
      const files = e.dataTransfer && e.dataTransfer.files;
      if(files && files.length){
        this.tomar(files[0]);
      }
    },

    soltarUrlsCat(e){
      const text = e.dataTransfer && e.dataTransfer.getData('text');
      if(text){
        this.cat.urlsArrastradas = (this.cat.urlsArrastradas ? this.cat.urlsArrastradas + '\n' : '') + text;
        this.actualizarPreviewUrlTextarea();
      }
    },

    tomar(file){
      if(!file) return;
      if(!/^image\//.test(file.type)){
        apprise('Selecciona un archivo de imagen');
        return;
      }
      if(this.cat.preview){
        URL.revokeObjectURL(this.cat.preview);
      }
      this.cat.file = file;
      this.cat.preview = URL.createObjectURL(file);
    },

    aceptarUrl(){
      const url = (this.cat.urlTmp || '').trim();
      if(!/^https?:\/\//i.test(url)){
        apprise('Ingrese una URL válida (http o https)');
        return;
      }
      if(this.cat.preview){
        URL.revokeObjectURL(this.cat.preview);
      }
      this.cat.file = null;
      this.cat.preview = '';
      this.cat.img = url;
      this.cat.urlTmp = '';
      apprise('URL aplicada correctamente');
    },

    actualizarPreviewUrlTextarea(){
      const texto = this.cat.urlsArrastradas || '';
      const lineas = texto.split('\n').map(l => l.trim()).filter(l => l.length > 0 && /^https?:\/\//i.test(l));
      this.cat.previewUrlList = lineas;
    },

    quitarUrlLista(index){
      let lineas = [...this.cat.previewUrlList];
      lineas.splice(index, 1);
      this.cat.urlsArrastradas = lineas.join('\n');
      this.actualizarPreviewUrlTextarea();
    },

    // Botón "Usar URL": toma la primera URL de la lista.
    // Si la categoría ya existe, actualiza pos_category.img al instante;
    // si es nueva, solo la aplica y se guarda al crear.
    aplicarUrlsArrastradas(){
      const lista = this.cat.previewUrlList || [];
      if(!lista.length) return;

      const url = lista[0];
      const limpiar = () => {
        if(this.cat.preview){
          URL.revokeObjectURL(this.cat.preview);
        }
        this.cat.file = null;
        this.cat.preview = '';
        this.cat.img = url;
        this.cat.urlsArrastradas = '';
        this.cat.previewUrlList = [];
      };

      if(!this.cat.category_id){
        limpiar();
        apprise('URL aplicada a la imagen de la categoría');
        return;
      }

      $.blockUI({ message: 'Actualizando imagen...' });
      axios.post(`${window.apphost || ''}/product/categoria_img`, {
        category_id: this.cat.category_id,
        img: url
      })
      .then(res => {
        if(res.data.status === 'ok'){
          limpiar();
          apprise('Imagen de la categoría actualizada');
          this.$emit('img-guardada');
        } else {
          apprise(res.data.msg || 'No se pudo actualizar la imagen');
        }
      })
      .catch(e => apprise((e.response && e.response.data && e.response.data.msg) || 'Error al actualizar la imagen'))
      .finally(() => $.unblockUI());
    },

    quitar(){
      if(this.cat.preview){
        URL.revokeObjectURL(this.cat.preview);
      }
      this.cat.file = null;
      this.cat.preview = '';
      this.cat.img = '';
    },

    subirClick(){
      if(!this.cat.file){
        apprise('Selecciona o arrastra una imagen primero');
        return;
      }
      $.blockUI({ message: 'Subiendo imagen al CDN...' });
      this.subirArchivo()
      .then(() => apprise('Imagen subida. Se guardará al confirmar la categoría'))
      .catch(e => apprise((e && e.message) || 'Error al subir la imagen'))
      .finally(() => $.unblockUI());
    },

    asegurarImagen(){
      if(this.cat.file){
        return this.subirArchivo();
      }
      return Promise.resolve(this.cat.img || '');
    },

    subirArchivo(){
      const cat = this.cat;
      const flaskUrl = typeof url_flask !== 'undefined' ? url_flask : '';
      if(!flaskUrl){
        return Promise.reject(new Error('Error: url_flask no está definida'));
      }

      const negId = window.neg_id || 1;
      const formData = new FormData();
      formData.append('file', cat.file);
      formData.append('item', cat.category_id || Date.now());
      formData.append('subfolder', `bd_regentis/neg_${negId}/pag_web`);

      return axios.post(`${flaskUrl}/tk_guardar_img`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' }
      })
      .then(res => {
        if(res.data && (res.data.ok || res.data.status === 'success') && res.data.job_id){
          return this.esperarJob(flaskUrl, res.data.job_id);
        }
        throw new Error('No se pudo iniciar la subida de la imagen');
      })
      .then(url => {
        if(cat.preview){
          URL.revokeObjectURL(cat.preview);
        }
        cat.img = url;
        cat.file = null;
        cat.preview = '';
        return url;
      });
    },

    esperarJob(flaskUrl, jobId){
      return new Promise((resolve, reject) => {
        let intentos = 0;
        const intervalo = setInterval(() => {
          intentos++;
          axios.get(`${flaskUrl}/jobs/result?job_id=${jobId}`)
          .then(res => {
            const data = res.data;
            if(data.status === 'finished'){
              clearInterval(intervalo);
              const r = data.result;
              if(r && r.ok && r.url){
                resolve(r.url);
              } else {
                reject(new Error('El servidor de imágenes no devolvió la URL'));
              }
            } else if(data.status === 'failed'){
              clearInterval(intervalo);
              reject(new Error('Falló el procesamiento de la imagen'));
            } else if(intentos > 60){
              clearInterval(intervalo);
              reject(new Error('Tiempo de espera agotado al subir la imagen'));
            }
          })
          .catch(err => {
            clearInterval(intervalo);
            reject(err);
          });
        }, 2000);
      });
    }
  },
  beforeDestroy(){
    if(this.cat && this.cat.preview){
      URL.revokeObjectURL(this.cat.preview);
    }
  }
});