new Vue({
  el: '#appPagWeb',
  data: Object.assign({}, pagWebState, {
    archivoJsonContenido: null,
    archivoSeleccionadoNombre: ''
  }),
  computed: {
    categoriasConTodos() {
      return [{ cat_pag_web_id: 'todos', titulo: 'Todos', clave_txt: '', url_img: '', is_visible: 1 }, ...this.categorias];
    },
    subcatsVisibles() {
      if (!this.catSeleccionada) return [];
      if (this.catSeleccionada.cat_pag_web_id === 'todos') {
        return [
          { subcat_pag_web_id: 'todos', titulo: 'Todos', is_visible: 1 },
          { subcat_pag_web_id: 'videos', titulo: 'Videos', is_visible: 1 }
        ];
      }
      return this.subcategorias.filter(s => s.cat_pag_web_id == this.catSeleccionada.cat_pag_web_id);
    },
    subcatsFiltradas() {
      if (!this.itemForm.cat) return [];
      return this.subcategorias.filter(s => s.cat_pag_web_id == this.itemForm.cat.cat_pag_web_id);
    },
    arbolJsonLimpio() {
      const limpiarObjeto = (obj) => {
        if (Array.isArray(obj)) {
          return obj.map(item => limpiarObjeto(item));
        } else if (obj !== null && typeof obj === 'object') {
          let limpio = {};
          for (let key in obj) {
            if (!key.endsWith('_id') && key !== 'id') {
              limpio[key] = limpiarObjeto(obj[key]);
            }
          }
          return limpio;
        }
        return obj;
      };

      const webLimpio = limpiarObjeto(this.arbolWeb);
      const posLimpio = limpiarObjeto(this.arbolPos);
      const slidersLimpio = limpiarObjeto(this.arbolSliders);

      return JSON.stringify({ web: webLimpio, pos: posLimpio, sliders: slidersLimpio }, null, 2);
    }
  },
  methods: {
    ...itemMethods,
    ...categoriaMethods,
    ...subcategoriaMethods,
    ...imagenMethods,
    ...videoMethods,

    abrirArbolContenido() {
      this.cargarArbolCompleto();
      $('#modalArbolContenido').modal('show');
    },

    cargarArbolCompleto() {
      bloquearUI('Cargando árbol de contenido...');
      axios.get(`${this.apphost}/xoxo/arbol_contenido/obtener`)
        .then(res => {
          if (res.data && res.data.status === 'ok') {
            this.arbolWeb = res.data.web || [];
            this.arbolPos = res.data.pos || [];
            this.arbolSliders = res.data.sliders || [];
          } else {
            apprise('No se pudo cargar el árbol de contenido');
          }
        })
        .catch(() => apprise('Error de conexión al cargar árbol'))
        .finally(() => desbloquearUI());
    },

    copiarJsonArbol() {
      navigator.clipboard.writeText(this.arbolJsonLimpio)
        .then(() => apprise('¡JSON limpio copiado al portapapeles!'))
        .catch(() => apprise('No se pudo copiar el JSON'));
    },

    descargarJsonArbol() {
      let negId = 0;
      if (this.arbolWeb.length > 0 && this.arbolWeb[0].neg_id) {
        negId = this.arbolWeb[0].neg_id;
      } else if (this.arbolPos.length > 0 && this.arbolPos[0].neg_id) {
        negId = this.arbolPos[0].neg_id;
      } else if (this.arbolSliders.length > 0 && this.arbolSliders[0].neg_id) {
        negId = this.arbolSliders[0].neg_id;
      }

      const filename = `contenido_neg_id_${negId}.json`;
      const blob = new Blob([this.arbolJsonLimpio], { type: 'application/json' });
      const link = document.createElement('a');
      link.href = URL.createObjectURL(blob);
      link.download = filename;
      link.click();
      URL.revokeObjectURL(link.href);
    },

    onFileSelected(event) {
      const file = event.target.files[0];
      if (!file) {
        this.archivoJsonContenido = null;
        this.archivoSeleccionadoNombre = '';
        return;
      }
      this.archivoSeleccionadoNombre = file.name;
      const reader = new FileReader();
      reader.onload = (e) => {
        try {
          this.archivoJsonContenido = JSON.parse(e.target.result);
          apprise('Archivo JSON leído correctamente.');
        } catch (err) {
          apprise('El archivo seleccionado no es un JSON válido.');
          this.archivoJsonContenido = null;
          this.archivoSeleccionadoNombre = '';
        }
      };
      reader.readAsText(file);
    },

    procesarImportacionJson() {
      if (!this.archivoJsonContenido) {
        apprise('No hay contenido JSON para procesar.');
        return;
      }

      bloquearUI('Sincronizando e importando contenido...');
      axios.post(`${this.apphost}/xoxo/arbol_contenido/importar`, {
        contenido: this.archivoJsonContenido
      })
      .then(res => {
        if (res.data && res.data.status === 'ok') {
          apprise('¡Importación y sincronización completada con éxito!');
          this.cargarArbolCompleto();
          this.cargarTodo();
        } else {
          apprise('Error al importar: ' + (res.data.msg || 'Desconocido'));
        }
      })
      .catch(() => apprise('Error de conexión al procesar importación'))
      .finally(() => desbloquearUI());
    }
  },
  watch: {
    'itemForm.cat'() {
      if (this.itemForm.subcat && this.itemForm.cat && this.itemForm.subcat.cat_pag_web_id != this.itemForm.cat.cat_pag_web_id) {
        this.itemForm.subcat = null;
      }
    }
  },
  mounted() {
    this.cargarTodo();
  }
});