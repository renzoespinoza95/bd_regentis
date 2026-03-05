<div class="row-fluid" id="appUsuxreg">
  <div class="span10">
    <h2>Registro de Actividades (usuxreg)</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrear">
        <i class="icon-plus icon-white"></i> Nuevo Registro
      </button>
    </div>

    <table id="tablaUsuxreg" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Usu ID</th>
          <th>Acción</th>
          <th>Reg Destino ID</th>
          <th>Descripción</th>
          <th>Fecha</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <!-- ======================= -->
    <!-- Modal Detalle           -->
    <!-- ======================= -->
    <div id="modalDetalleUsuxreg" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Detalle usuxreg</h3></div>
      <div class="modal-body">
        <p><strong>ID:</strong> {{ detalle.usuxreg_id }}</p>
        <p><strong>Usuario:</strong> {{ detalle.usu_id }}</p>
        <p><strong>Acción:</strong> {{ detalle.accion }}</p>
        <p><strong>Destino:</strong> {{ detalle.reg_destino_id }}</p>
        <p><strong>Descripción:</strong> {{ detalle.descripcion }}</p>
        <p><strong>Extra:</strong> {{ detalle.extra_data }}</p>
        <p><strong>Fecha:</strong> {{ detalle.fecha_creacion }}</p>
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- ======================= -->
    <!-- Modal Crear usuxreg     -->
    <!-- ======================= -->
    <div id="modalCrearUsuxreg" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Nuevo Registro usuxreg</h3></div>
      <div class="modal-body">

        <!-- usu_id -->
        <div class="control-group">
          <label class="control-label">Usuario</label>
          <div class="controls">
            <select v-model="nuevo.usu_id" class="input-xlarge">
              <option value="">-- Seleccionar usuario --</option>
              <option v-for="u in usuarios" :value="u.usu_id">
                {{ u.usu_id }} - {{ u.sobrenombre }}
              </option>
            </select>
          </div>
        </div>

        <!-- accion -->
        <div class="control-group">
          <label class="control-label">Acción</label>
          <div class="controls">
            <input v-model="nuevo.accion" class="input-xxlarge">
          </div>
        </div>

        <!-- reg_destino -->
        <div class="control-group">
          <label class="control-label">Destino</label>
          <div class="controls">
            <select v-model="nuevo.reg_destino_id" class="input-xlarge">
              <option value="">-- Seleccionar destino --</option>
              <option v-for="d in destinos" :value="d.reg_destino_id">
                {{ d.reg_destino_id }} - {{ d.tipo }} → {{ d.referencia_id }}
              </option>
            </select>

            <button class="btn btn-mini btn-primary" @click="abrirModalCrearDestino">
              Nuevo Destino
            </button>
          </div>
        </div>

        <!-- descripcion -->
        <div class="control-group">
          <label class="control-label">Descripción</label>
          <div class="controls">
            <textarea v-model="nuevo.descripcion" class="input-xxlarge"></textarea>
          </div>
        </div>

        <!-- extra_data -->
        <div class="control-group">
          <label class="control-label">Extra Data (JSON)</label>
          <div class="controls">
            <textarea v-model="nuevo.extra_data" class="input-xxlarge"></textarea>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearUsuxreg">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- ======================= -->
    <!-- Modal Crear reg_destino -->
    <!-- ======================= -->
    <div id="modalCrearDestino" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Nuevo reg_destino</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label class="control-label">Tipo</label>
          <div class="controls">
            <input v-model="destino.tipo" class="input-large">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Referencia ID</label>
          <div class="controls">
            <input v-model="destino.referencia_id" class="input-large">
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearDestino">Crear Destino</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- ======================= -->
    <!-- Modal Editar usuxreg    -->
    <!-- ======================= -->
    <div id="modalEditarUsuxreg" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Editar Registro usuxreg</h3></div>

      <div class="modal-body">

        <!-- usu_id -->
        <div class="control-group">
          <label class="control-label">Usuario</label>
          <div class="controls">
            <select v-model="form.usu_id" class="input-xlarge">
              <option value="">-- Seleccionar usuario --</option>
              <option v-for="u in usuarios" :value="u.usu_id">
                {{ u.usu_id }} - {{ u.sobrenombre }}
              </option>
            </select>
          </div>
        </div>

        <!-- accion -->
        <div class="control-group">
          <label class="control-label">Acción</label>
          <div class="controls">
            <input v-model="form.accion" class="input-xxlarge">
          </div>
        </div>

        <!-- reg_destino_id -->
        <div class="control-group">
          <label class="control-label">Destino</label>
          <div class="controls">
            <select v-model="form.reg_destino_id" class="input-xlarge">
              <option value="">-- Seleccionar destino --</option>
              <option v-for="d in destinos" :value="d.reg_destino_id">
                {{ d.reg_destino_id }} - {{ d.tipo }} → {{ d.referencia_id }}
              </option>
            </select>

            <button class="btn btn-mini btn-primary" @click="abrirModalCrearDestino">
              Nuevo Destino
            </button>
          </div>
        </div>

        <!-- descripcion -->
        <div class="control-group">
          <label class="control-label">Descripción</label>
          <div class="controls">
            <textarea v-model="form.descripcion" class="input-xxlarge"></textarea>
          </div>
        </div>

        <!-- extra_data -->
        <div class="control-group">
          <label class="control-label">Extra Data (JSON)</label>
          <div class="controls">
            <textarea v-model="form.extra_data" class="input-xxlarge"></textarea>
          </div>
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
const appUsuxreg = new Vue({
  el: '#appUsuxreg',
  data: {
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    usuxregs: [],
    usuarios: [],
    destinos: [],
    nuevo: { usu_id:'', accion:'', descripcion:'', reg_destino_id:'', extra_data:'' },
    form: {},
    detalle: {},
    destino: { tipo:'', referencia_id:'' },
    dt: null
  },

  methods: {

    listar() {
      $.blockUI({ message:"<h3>Cargando...</h3>" });

      axios.get(`${this.apphost}/usuxreg/listar`).then(r=>{
        this.usuxregs = r.data;

        this.$nextTick(()=>{
          if(!this.dt){
            this.dt = $('#tablaUsuxreg').DataTable({
              scrollX:true,
              dom:'frtip',
              order:[[0,'desc']]
            });

            const self=this;
            $('#tablaUsuxreg tbody')
                .on('click', 'a.detalle-item', function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    const id = $(this).data('id');
                    const item = self.usuxregs.find(x => x.usuxreg_id == id);
                    if(item) self.abrirModalDetalle(item);
                })
                .on('click', 'a.editar-item', function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    const id = $(this).data('id');
                    const item = self.usuxregs.find(x => x.usuxreg_id == id);
                    if(item) self.abrirModalEditar(item);
                })
                .on('click', 'a.eliminar-item', function(e){
                    e.preventDefault();
                    e.stopPropagation();
                    const id = $(this).data('id');
                    const item = self.usuxregs.find(x => x.usuxreg_id == id);
                    if(item) self.eliminarUsuxreg(item);
                });

          }

          this.dt.clear();
          this.usuxregs.forEach(u=>{
            const actions = `
              <div class="btn-group">
                <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                  Opciones <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="detalle-item"  data-id="${u.usuxreg_id}">Detalle</a></li>
                  <li><a href="#" class="editar-item"   data-id="${u.usuxreg_id}">Editar</a></li>
                  <li><a href="#" class="eliminar-item" data-id="${u.usuxreg_id}">Eliminar</a></li>
                </ul>
              </div>`;
            this.dt.row.add([ u.usuxreg_id, u.usu_id, u.accion, u.reg_destino_id, u.descripcion, u.fecha_creacion, actions ]);
          });
          this.dt.draw(false);
        });
      }).finally(()=> $.unblockUI());
    },

    cargarUsuarios() {
      axios.get(`${this.apphost}/usu/listar`).then(r=>{
        this.usuarios = r.data;
      });
    },

    cargarDestinos() {
      axios.get(`${this.apphost}/regdestino/listar`).then(r=>{
        this.destinos = r.data;
      });
    },

    abrirModalCrear() {
      this.nuevo = { usu_id:'', accion:'', descripcion:'', reg_destino_id:'', extra_data:'' };
      $('#modalCrearUsuxreg').modal('show');
    },

    crearUsuxreg() {
      axios.post(`${this.apphost}/usuxreg/crear`, this.nuevo)
      .then(()=>{
        $('#modalCrearUsuxreg').modal('hide');
        apprise("¡Creado!");
        this.listar();
      });
    },

    abrirModalDetalle(item){
      this.detalle = item;
      $('#modalDetalleUsuxreg').modal('show');
    },

    abrirModalEditar(item){
      this.form = Object.assign({}, item);
      $('#modalEditarUsuxreg').modal('show');
    },

    guardarEdicion(){
      axios.post(`${this.apphost}/usuxreg/editar`, this.form)
      .then(()=>{
        $('#modalEditarUsuxreg').modal('hide');
        apprise("¡Actualizado!");
        this.listar();
      });
    },

    eliminarUsuxreg(item){
      apprise(`Eliminar registro #${item.usuxreg_id}?`, {confirm:true}, ok=>{
        if(!ok) return;
        axios.post(`${this.apphost}/usuxreg/eliminar`, { usuxreg_id:item.usuxreg_id })
             .then(()=> this.listar());
      });
    },

    abrirModalCrearDestino() {
      this.destino = { tipo:'', referencia_id:'' };
      $('#modalCrearDestino').modal('show');
    },

    crearDestino(){
      axios.post(`${this.apphost}/regdestino/crear`, this.destino)
      .then(()=>{
        $('#modalCrearDestino').modal('hide');
        apprise("Destino creado");
        this.cargarDestinos();
      });
    }
  },

  mounted(){
    this.cargarUsuarios();
    this.cargarDestinos();
    this.listar();
  }
});
</script>

