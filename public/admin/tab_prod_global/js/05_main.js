Vue.component('v-select', VueSelect.VueSelect);

new Vue({
  el: '#appProd',
  data: prodState,
  methods: {
    ...uiHelpersMethods,
    ...productoMethods,
    ...categoriaMethods
  },
  mounted(){
    this.listarCategorias();
    this.listar();
  }
});