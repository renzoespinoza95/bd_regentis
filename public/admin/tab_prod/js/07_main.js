window.vueAppProduct = new Vue({
  el: '#appProduct',
  data: Object.assign({}, productoState, { archivosSeleccionados: [] }),
  computed: {
    totalProductosGeneral(){
      return this.productos.length;
    },
    nombreCategoriaActiva(){
      if(this.categoriaSeleccionada === null){
        return "Todas las Categorías";
      }
      const cat = this.categorias.find(c => c.category_id == this.categoriaSeleccionada);
      return cat ? cat.descripcion : "Seleccionada";
    },
    categoriaActiva(){
      if(this.categoriaSeleccionada === null) return null;
      return this.categorias.find(c => c.category_id == this.categoriaSeleccionada) || null;
    },
    subcategoriasActivas(){
      if(this.categoriaSeleccionada === null) return [];
      return this.subcategoriasDe(this.categoriaSeleccionada);
    },
    nombreSubcategoriaActiva(){
      if(this.subcategoriaSeleccionada === null) return '';
      const s = this.subcategorias.find(x => x.subcategoria_id === this.subcategoriaSeleccionada);
      return s ? s.nombre : '';
    },
    categoriasFiltradasPanel(){
      const norm = (t) => String(t || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
      const q = norm(this.busquedaCategorias).trim();
      if(!q) return this.categorias;
      return this.categorias.filter(c =>
        norm(c.descripcion).includes(q) || norm(c.brief).includes(q)
      );
    },
    arbolCrear(){
      return this.construirArbol(this.busquedaCatCrear);
    },
    arbolEditar(){
      return this.construirArbol(this.busquedaCatEditar);
    }
  },
  methods: {
    ...uiHelpers,
    ...productosMethods,
    ...categoriasMethods,
    ...subcategoriasMethods,
    ...globalSearchMethods,
    ...fotosMethods
  },
  mounted(){
    this.cargarCategorias();
    this.cargarSubcategorias();
    this.listar();

    const self = this;

    $(document).on('click', '#btnConfirmarUrl', function(e){
      e.preventDefault();
      self.guardarUrlImagenModal();
    });

    $(document).on('input', '#textareaUrlsArrastradas', function(){
      self.actualizarPreviewsUrls();
    });

    $(document).on('click', '#btnAgregarUrlsDrop', function(e){
      e.preventDefault();
      self.procesarUrlsArrastradas();
    });

    $(document).on('click', '.btn-quitar-url-temporal', function(e){
      e.stopPropagation();
      const index = $(this).data('index');
      self.quitarUrlTemporal(index);
    });

    $(document).on('dragover', '#dropZoneUrls', function(e){
      e.preventDefault();
      e.stopPropagation();
      $(this).css('border-color', '#3498db');
    });

    $(document).on('dragleave', '#dropZoneUrls', function(e){
      e.preventDefault();
      e.stopPropagation();
      $(this).css('border-color', '#e67e22');
    });

    $(document).on('drop', '#dropZoneUrls', function(e){
      e.preventDefault();
      e.stopPropagation();
      $(this).css('border-color', '#e67e22');
      const text = e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.getData('text');
      if(text){
        const current = $('#textareaUrlsArrastradas').val();
        $('#textareaUrlsArrastradas').val(current ? current + '\n' + text : text);
        self.actualizarPreviewsUrls();
      }
    });

    $(document).on('click', '#btnAgregarArrastradas', function(e){
      e.preventDefault();
      self.subirImagenArchivo();
    });

    $(document).on('click', '#dropZoneFoto', function(e){
      if($(e.target).hasClass('btn-quitar-temporal')) return;
      $('#inputArchivoFoto').click();
    });

    $(document).on('click', '.btn-quitar-temporal', function(e){
      e.stopPropagation();
      const index = $(this).data('index');
      self.quitarImagenTemporal(index);
    });

    $(document).on('dragover', '#dropZoneFoto', function(e){
      e.preventDefault();
      e.stopPropagation();
      $(this).css('border-color', '#f39c12');
    });

    $(document).on('dragleave', '#dropZoneFoto', function(e){
      e.preventDefault();
      e.stopPropagation();
      $(this).css('border-color', '#27ae60');
    });

    $(document).on('drop', '#dropZoneFoto', function(e){
      e.preventDefault();
      e.stopPropagation();
      $(this).css('border-color', '#27ae60');
      
      if(e.originalEvent.dataTransfer && e.originalEvent.dataTransfer.files){
        self.agregarArchivosDropzone(e.originalEvent.dataTransfer.files);
      }
    });

    $(document).on('change', '#inputArchivoFoto', function(e){
      if(this.files){
        self.agregarArchivosDropzone(this.files);
      }
    });
  }
});