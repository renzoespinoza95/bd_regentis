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