const rubrosMethods = {
  abrirModalRubro(n){
    this.negRubro = {
      neg_id: parseInt(n.neg_id,10),
      nombre: n.nombre
    };
    this.listarRubrosNeg().then(()=>{
      $('#modalRubro').modal('show');
    });
  },
  listarRubrosNeg(){
    this.bloquear('Cargando rubros...');
    return axios.get(`${this.apphost}/rubroxneg/listar`, {
      params:{ neg_id:this.negRubro.neg_id }
    })
    .then(r=>{
      this.rubrosNeg = r.data.data || [];
      this.$nextTick(()=>{
        if(!this.dtRubro){
          this.dtRubro = $('#tablaRubros').DataTable({
            scrollX:true,
            destroy:true
          });
          const self = this;
          $('#tablaRubros tbody').on('click','a.eliminar-rubro',function(e){
            e.preventDefault();
            const id = $(this).data('id');
            self.eliminarRubro(id);
          });
        }
        this.dtRubro.clear();
        this.rubrosNeg.forEach(r=>{
          const btn = `
            <a href="#" class="eliminar-rubro" data-id="${r.rubroxneg_id}">
              <i class="fa fa-trash"></i>
            </a>
          `;
          this.dtRubro.row.add([r.rubro_id, r.nombre, btn]);
        });
        this.dtRubro.draw(false);
      });
    })
    .finally(()=>$.unblockUI());
  },
  abrirModalAgregarRubro(){
    this.nuevoRubro = { rubro_id:null };
    this.bloquear('Cargando rubros...');
    axios.get(`${this.apphost}/rubro/listar`)
      .then(r=>{
        this.rubrosOptions = r.data.data || [];
        $('#modalRubro').modal('hide');
        $('#modalAgregarRubro').modal('show');
      })
      .finally(()=>$.unblockUI());
  },
  guardarRubro(){
    if(!this.nuevoRubro.rubro_id){
      return apprise('Selecciona un rubro');
    }
    this.bloquear('Guardando...');
    axios.post(`${this.apphost}/rubroxneg/crear`,{
      neg_id:this.negRubro.neg_id,
      rubro_id: typeof this.nuevoRubro.rubro_id === 'object' ? parseInt(this.nuevoRubro.rubro_id.rubro_id) : parseInt(this.nuevoRubro.rubro_id)
    })
    .then(()=>{
      apprise('Rubro agregado');
      $('#modalAgregarRubro').modal('hide');
    })
    .finally(()=>{
      $.unblockUI();
      this.listarRubrosNeg().then(()=>{
        $('#modalRubro').modal('show');
      });
    });
  },
  eliminarRubro(id){
    apprise('¿Eliminar rubro?',{confirm:true}, ok=>{
      if(!ok) return;
      this.bloquear('Eliminando...');
      axios.post(`${this.apphost}/rubroxneg/eliminar`,{ rubroxneg_id:id })
        .then(()=>{ apprise('Eliminado'); })
        .finally(()=>{
          $.unblockUI();
          this.listarRubrosNeg();
        });
    });
  },
  abrirModalLogo(n){
    this.logoNeg = {
      neg_id: parseInt(n.neg_id, 10),
      preview: n.img_logo || '',
      file: null,
      nueva_preview: ''
    };
    $('#modalLogoNeg').modal('show');
  },
  onSelectLogo(e){
    const file = e.target.files[0];
    if(!file) return;
    this.logoNeg.file = file;
    this.logoNeg.nueva_preview = URL.createObjectURL(file);
  },
  subirLogo(){
    apprise('Todavía no implementado 😏');
  },
  logoDefecto(){
    this.bloquear('Actualizando logo...');
    axios.post(`${this.apphost}/QQvN/neg/logo/defecto`, { neg_id: this.logoNeg.neg_id })
      .then(r=>{
        if(r.data.status=='ok'){
          this.logoNeg.preview = r.data.img_logo;
          apprise('Logo actualizado');
        }
      })
      .finally(()=>{
        $.unblockUI();
        this.listarNeg();
      });
  },
  logoRandom(){
    this.bloquear('Generando logo random...');
    axios.post(`${this.apphost}/QQvN/neg/logo/random`, { neg_id: this.logoNeg.neg_id })
      .then(r=>{
        if(r.data.status=='ok'){
          this.logoNeg.preview = r.data.img_logo;
          apprise('Logo random aplicado');
        }
      })
      .finally(()=>{
        $.unblockUI();
        this.listarNeg();
      });
  }
};