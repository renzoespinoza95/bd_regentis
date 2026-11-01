const membresiasMethods = {
  abrirModalMembresia(n){
    this.negMembresia = {
      neg_id: parseInt(n.neg_id),
      nombre: n.nombre
    };
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2,'0');
    const dd = String(hoy.getDate()).padStart(2,'0');
    const fechaInicio = `${yyyy}-${mm}-${dd}`;
    const fechaFin = new Date(hoy);
    fechaFin.setMonth(fechaFin.getMonth() + 1);
    const yyyy2 = fechaFin.getFullYear();
    const mm2 = String(fechaFin.getMonth() + 1).padStart(2,'0');
    const dd2 = String(fechaFin.getDate()).padStart(2,'0');
    const fechaFinStr = `${yyyy2}-${mm2}-${dd2}`;

    this.nuevoPago = {
      motivo: 'MENSUAL',
      monto: 20,
      fecha_inicio_premium: fechaInicio,
      fecha_fin_premium: fechaFinStr,
      is_aprobado: 1
    };
    this.listarMembresias().then(()=>{
      $('#modalMembresia').modal('show');
    });
  },
  listarMembresias(){
    this.bloquear('Cargando membresías...');
    return axios.post(`${this.apphost}/neg_pago/listar`, { neg_id: this.negMembresia.neg_id })
      .then(r=>{
        this.membresias = r.data.rows || [];
        this.$nextTick(()=>{
          if(!this.dtMembresia){
            this.dtMembresia = $('#tablaMembresias').DataTable({
              scrollX: true,
              destroy: true,
              pageLength: 25,
              order: [[0,'desc']]
            });
            const self = this;
            $('#tablaMembresias tbody')
              .on('change', '.toggle-aprobado', function(e){
                e.preventDefault();
                const chk = $(this);
                const id = chk.data('id');
                apprise('¿Cambiar estado de aprobación?', { confirm:true }, function(ok){
                  if(!ok){
                    chk.prop('checked', !chk.prop('checked'));
                    return;
                  }
                  axios.post(`${self.apphost}/neg_pago/toggle_aprobado`, { neg_pago_id:id })
                  .then(()=>{ self.listarMembresias(); });
                });
              })
              .on('click', '.eliminar-membresia', function(e){
                e.preventDefault();
                const neg_pago_id = $(this).data('id');
                apprise('¿Eliminar membresía?', { confirm:true }, function(ok){
                  if(!ok){ return; }
                  axios.post(`${self.apphost}/neg_pago/eliminar`, { neg_pago_id })
                  .then(()=>{
                    apprise('Registro eliminado');
                    self.listarMembresias();
                  });
                });
              });
          }
          this.dtMembresia.clear();
          this.membresias.forEach(m=>{
            const btnEliminar = `
              <button class="btn btn-mini btn-danger eliminar-membresia" data-id="${m.neg_pago_id}" title="Eliminar">
                <i class="icon-white icon-trash"></i>
              </button>
            `;
            const chkAprobado = `
              <input type="checkbox" class="toggle-aprobado" data-id="${m.neg_pago_id}" ${parseInt(m.is_aprobado) ? 'checked' : ''}>
            `;
            this.dtMembresia.row.add([
              m.neg_pago_id,
              m.motivo,
              parseFloat(m.monto || 0).toFixed(2),
              m.fecha_inicio_premium || '',
              m.fecha_fin_premium || '',
              chkAprobado,
              btnEliminar
            ]);
          });
          this.dtMembresia.draw(false);
        });
      })
      .catch(err=>{ console.error(err); apprise('Error al cargar membresías'); })
      .finally(()=>{ $.unblockUI(); });
  },
  guardarMembresia(){
    const hoy = new Date();
    const yyyy = hoy.getFullYear();
    const mm = String(hoy.getMonth() + 1).padStart(2,'0');
    const dd = String(hoy.getDate()).padStart(2,'0');
    const fechaInicio = `${yyyy}-${mm}-${dd}`;
    const fechaFin = new Date(hoy);

    switch(this.nuevoPago.motivo){
      case 'PAGO_ANUAL':
        fechaFin.setFullYear(fechaFin.getFullYear() + 1);
        break;
      case 'MENSUAL':
        fechaFin.setMonth(fechaFin.getMonth() + 1);
        break;
      default:
        fechaFin.setDate(fechaFin.getDate() + 30);
        break;
    }

    const yyyy2 = fechaFin.getFullYear();
    const mm2 = String(fechaFin.getMonth() + 1).padStart(2,'0');
    const dd2 = String(fechaFin.getDate()).padStart(2,'0');
    const fechaFinStr = `${yyyy2}-${mm2}-${dd2}`;

    this.bloquear('Guardando membresía...');
    axios.post(`${this.apphost}/neg_pago/crear`, {
      neg_id: this.negMembresia.neg_id,
      motivo: this.nuevoPago.motivo,
      monto: this.nuevoPago.monto,
      fecha_inicio_premium: fechaInicio,
      fecha_fin_premium: fechaFinStr,
      is_aprobado: 1
    })
    .then(()=>{
      apprise('Membresía registrada');
      this.nuevoPago = { motivo:'MENSUAL', monto:20, fecha_inicio_premium:'', fecha_fin_premium:'', is_aprobado:1 };
      this.listarMembresias();
    })
    .catch(err=>{ console.error(err); apprise('Error al registrar membresía'); })
    .finally(()=>{ $.unblockUI(); });
  },
  cambiarFechasMembresia(){
    const hoy = new Date();
    const fin = new Date(hoy);
    if(this.nuevoPago.motivo === 'PAGO_ANUAL'){
      fin.setFullYear(fin.getFullYear() + 1);
    }else{
      fin.setMonth(fin.getMonth() + 1);
    }
    this.nuevoPago.fecha_fin_premium = fin.toISOString().substring(0,10);
  }
};