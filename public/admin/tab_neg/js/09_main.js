const appNeg = new Vue({
  el: '#appNeg',
  data: negState,
  methods: {
    ...uiHelpersMethods,
    ...negociosMethods,
    ...mercadosMethods,
    ...catMercadosMethods,
    ...propietariosMethods,
    ...membresiasMethods,
    ...rubrosMethods
  },
  mounted() {
    this.listarNeg();
    this.cargarRubros();
  },
  watch: {
    'nuevoPago.motivo': function(){
      const hoy = new Date();
      const yyyy = hoy.getFullYear();
      const mm = String(hoy.getMonth() + 1).padStart(2,'0');
      const dd = String(hoy.getDate()).padStart(2,'0');
      const fechaHoy = `${yyyy}-${mm}-${dd}`;
      this.nuevoPago.fecha_inicio_premium = fechaHoy;

      const fin = new Date(hoy);
      if(this.nuevoPago.motivo === 'PAGO_ANUAL'){
        fin.setFullYear(fin.getFullYear() + 1);
      } else if(this.nuevoPago.motivo === 'MENSUAL'){
        fin.setMonth(fin.getMonth() + 1);
      } else {
        fin.setDate(fin.getDate() + 7);
      }

      const yyyy2 = fin.getFullYear();
      const mm2 = String(fin.getMonth() + 1).padStart(2,'0');
      const dd2 = String(fin.getDate()).padStart(2,'0');
      this.nuevoPago.fecha_fin_premium = `${yyyy2}-${mm2}-${dd2}`;
    }
  }
});