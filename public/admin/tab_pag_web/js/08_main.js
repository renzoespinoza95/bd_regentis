new Vue({
  el: '#appPagWeb',
  data: pagWebState,
  computed: {
    subcatsVisibles() {
      if (!this.catSeleccionada) return [];
      return this.subcategorias.filter(s => s.cat_pag_web_id == this.catSeleccionada.cat_pag_web_id);
    },
    subcatsFiltradas() {
      if (!this.itemForm.cat) return [];
      return this.subcategorias.filter(s => s.cat_pag_web_id == this.itemForm.cat.cat_pag_web_id);
    }
  },
  methods: {
    ...itemMethods,
    ...categoriaMethods,
    ...subcategoriaMethods,
    ...imagenMethods,
    ...videoMethods
  },
  watch: {
    'itemForm.cat'() {
      // Solo resetear si la subcategoría actual no pertenece a la nueva categoría
      if (this.itemForm.subcat && this.itemForm.cat && this.itemForm.subcat.cat_pag_web_id != this.itemForm.cat.cat_pag_web_id) {
        this.itemForm.subcat = null;
      }
    }
  },
  mounted() {
    this.cargarTodo();
  }
});