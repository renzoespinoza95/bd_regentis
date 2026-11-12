const uiHelpers = {
  validarStock(){
    if(this.nuevo.stock === '' || this.nuevo.stock === null){
      return;
    }
    let val = this.nuevo.stock.toString().replace(/[^0-9]/g, '');
    this.nuevo.stock = val ? parseInt(val) : '';
  },
  copiarPrecio(){
    this.nuevo.price = this.nuevo.price_ref;
  }
};

// Componente Vue 2 para Summernote con soporte v-model
// Uso: <summernote-editor v-model="campo"></summernote-editor>
Vue.component('summernote-editor', {
  props: {
    value: { type: String, default: '' },
    height: { type: Number, default: 180 }
  },
  template: '<div><textarea></textarea></div>',
  mounted(){
    const self = this;
    this.$ta = $(this.$el).find('textarea');
    this.$ta.summernote({
      height: this.height,
      callbacks: {
        onChange(contents){
          self.$emit('input', contents);
        }
      }
    });
    this.$ta.summernote('code', this.value || '');
  },
  watch: {
    value(nuevoValor){
      if(!this.$ta) return;
      const actual = this.$ta.summernote('code');
      if((nuevoValor || '') !== actual){
        this.$ta.summernote('code', nuevoValor || '');
      }
    }
  },
  beforeDestroy(){
    if(this.$ta){
      this.$ta.summernote('destroy');
    }
  }
});