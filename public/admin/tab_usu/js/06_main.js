new Vue({
  el: '#appUsuario',
  delimiters: ['[[', ']]'],
  data: usuState,
  mixins: [usuariosCrudMixin, usuariosUtilsMixin, usuariosExtrasMixin],
  mounted() {
    this.obtenerUsuarios();
    this.obtenerTipousuarios();
  }
});