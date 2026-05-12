<div class="row-fluid" id="appVariables">
  <div class="span12">

    <h2>Variables del Sistema</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrear">
        <i class="icon-plus icon-white"></i> Nueva Variable
      </button>
    </div>

    <!-- =========================
         TABLA PRINCIPAL
    ========================= -->
    <table id="tablaVariables" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Valor</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>

    <!-- =========================
         MODAL DETALLE
    ========================= -->
    <div id="modalDetalleVariable" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <h3>Detalle Variable</h3>
      </div>
      <div class="modal-body">
        <p><strong>ID:</strong> {{ detalle.vari_id }}</p>
        <p><strong>Nombre:</strong> {{ detalle.nombre }}</p>
        <p><strong>Valor:</strong> {{ detalle.valor }}</p>
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- =========================
         MODAL CREAR
    ========================= -->
    <div id="modalCrearVariable" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <h3>Nueva Variable</h3>
      </div>
      <div class="modal-body">
        <div class="control-group">
          <label>Nombre</label>
          <input v-model="nuevo.nombre" class="input-xxlarge">
        </div>

        <div class="control-group">
          <label>Valor</label>
          <textarea v-model="nuevo.valor" class="input-xxlarge"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearVariable">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =========================
         MODAL EDITAR
    ========================= -->
    <div id="modalEditarVariable" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <h3>Editar Variable</h3>
      </div>
      <div class="modal-body">
        <div class="control-group">
          <label>Nombre</label>
          <input v-model="form.nombre" class="input-xxlarge">
        </div>

        <div class="control-group">
          <label>Valor</label>
          <textarea v-model="form.valor" class="input-xxlarge"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarEdicion">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

  </div>
</div>

<script>
const appVariables = new Vue({

  el: '#appVariables',

  data: {
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    variables: [],
    nuevo: {
      nombre: '',
      valor: ''
    },
    form: {},
    detalle: {},
    dt: null
  },

  methods: {

    /* =========================
       LISTAR
    ========================= */
    listar() {

      $.blockUI({
        message: '<h4>Cargando variables…</h4>',
        css:{border:'none',padding:'15px',background:'#000',opacity:.6,color:'#fff'}
      });

      axios.get(`${this.apphost}/variables/listar`)
        .then(r => {

          this.variables = r.data.data || [];

          this.$nextTick(() => {

            if(!this.dt){

              const self = this;

              this.dt = $('#tablaVariables').DataTable({
                language: dt_language,
                scrollX: true,
                order: [[0,'desc']]
              });

              $('#tablaVariables tbody')

                .on('click','a.detalle-variable', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const v = self.variables.find(x=>x.vari_id==id);
                  if(v) self.abrirModalDetalle(v);
                })

                .on('click','a.editar-variable', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const v = self.variables.find(x=>x.vari_id==id);
                  if(v) self.abrirModalEditar(v);
                })

                .on('click','a.eliminar-variable', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const v = self.variables.find(x=>x.vari_id==id);
                  if(v) self.eliminarVariable(v);
                });

            }

            this.dt.clear();

            this.variables.forEach(v => {

              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="detalle-variable" data-id="${v.vari_id}">Detalle</a></li>
                    <li><a href="#" class="editar-variable" data-id="${v.vari_id}">Editar</a></li>
                    <li><a href="#" class="eliminar-variable" data-id="${v.vari_id}">Eliminar</a></li>
                  </ul>
                </div>`;

              this.dt.row.add([
                v.vari_id,
                v.nombre,
                v.valor,
                actions
              ]);
            });

            this.dt.draw(false);

          });

        })
        .finally(() => $.unblockUI());
    },

    /* =========================
       DETALLE
    ========================= */
    abrirModalDetalle(v){
      this.detalle = v;
      $('#modalDetalleVariable').modal('show');
    },

    /* =========================
       CREAR
    ========================= */
    abrirModalCrear(){
      this.nuevo = { nombre:'', valor:'' };
      $('#modalCrearVariable').modal('show');
    },

    crearVariable(){

      if(!this.nuevo.nombre.trim())
        return apprise('Escribe un nombre');

      $.blockUI({
        message: '<h4>Creando…</h4>',
        css:{border:'none',padding:'15px',background:'#000',opacity:.6,color:'#fff'}
      });

      axios.post(`${this.apphost}/variables/crear`, this.nuevo)
        .then(()=>{
          $('#modalCrearVariable').modal('hide');
          apprise('¡Creado!');
        })
        .finally(()=>{
          $.unblockUI();
          this.listar();
        });
    },

    /* =========================
       EDITAR
    ========================= */
    abrirModalEditar(v){
      this.form = Object.assign({}, v);
      $('#modalEditarVariable').modal('show');
    },

    guardarEdicion(){

      if(!this.form.nombre.trim())
        return apprise('Escribe un nombre');

      $.blockUI({
        message: '<h4>Actualizando…</h4>',
        css:{border:'none',padding:'15px',background:'#000',opacity:.6,color:'#fff'}
      });

      axios.post(`${this.apphost}/variables/editar`, this.form)
        .then(()=>{
          $('#modalEditarVariable').modal('hide');
          apprise('¡Actualizado!');
        })
        .finally(()=>{
          $.unblockUI();
          this.listar();
        });
    },

    /* =========================
       ELIMINAR
    ========================= */
    eliminarVariable(v){

      apprise(`¿Eliminar variable <b>#${v.vari_id}</b>?`,
      {confirm:true},
      ok=>{
        if(!ok) return;

        axios.post(`${this.apphost}/variables/eliminar`, {
          vari_id: v.vari_id
        })
        .finally(()=> this.listar());
      });

    }

  },

  mounted(){
    this.listar();
  }

});
</script>