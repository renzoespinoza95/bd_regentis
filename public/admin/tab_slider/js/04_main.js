new Vue({
  el: '#appSlider',
  data: sliderState,
  delimiters: ['[[', ']]'],
  methods: {
    ...sliderMethods
  },
  mounted() {
    this.obtenerUsuarioInfo();
    this.obtenerSliders();

    // Esc cierra el visor de imagen a pantalla completa
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && this.imagenGrande) {
        this.cerrarImagen();
      }
    });

    this.$nextTick(() => {
      this.activarDrag();

      $('#snCrearDescripcion').summernote({
        height: 150
      });

      $('#snEditarDescripcion').summernote({
        height: 150
      });
    });
  }
});