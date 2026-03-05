<div class="row-fluid" id="appProveedor">

  <div class="span10">
    <h2>Proveedores</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirCrear">
        <i class="icon-plus icon-white"></i> Nuevo Proveedor
      </button>
    </div>

    <!-- TABLA -->
    <table id="tablaProveedor" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>RUC</th>
          <th>Teléfono</th>
          <th>Email</th>
          <th>Activo</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>

    <!-- MODAL DETALLE -->
    <div id="modalDetalleProveedor" class="modal hide fade">
      <div class="modal-header"><h3>Detalle del Proveedor</h3></div>
      <div class="modal-body">
        <p><b>ID:</b> {{ detalle.proveedor_id }}</p>
        <p><b>Nombre:</b> {{ detalle.nombre }}</p>
        <p><b>RUC:</b> {{ detalle.ruc }}</p>
        <p><b>Dirección:</b> {{ detalle.direccion }}</p>
        <p><b>Teléfono:</b> {{ detalle.telefono }}</p>
        <p><b>Email:</b> {{ detalle.email }}</p>
        <p><b>Activo:</b> {{ detalle.is_activo == 1 ? "Sí" : "No" }}</p>
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- MODAL CREAR -->
    <div id="modalCrearProveedor" class="modal hide fade">
      <div class="modal-header"><h3>Nuevo Proveedor</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Nombre</label>
          <div class="controls"><input v-model="nuevo.nombre" class="input-xxlarge"></div>
        </div>

        <div class="control-group">
          <label>RUC</label>
          <div class="controls"><input v-model="nuevo.ruc"></div>
        </div>

        <div class="control-group">
          <label>Dirección</label>
          <div class="controls"><textarea v-model="nuevo.direccion"></textarea></div>
        </div>

        <div class="control-group">
          <label>Teléfono</label>
          <div class="controls"><input v-model="nuevo.telefono"></div>
        </div>

        <div class="control-group">
          <label>Email</label>
          <div class="controls"><input v-model="nuevo.email"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crear">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="modalEditarProveedor" class="modal hide fade">
      <div class="modal-header"><h3>Editar Proveedor</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Nombre</label>
          <div class="controls"><input v-model="form.nombre" class="input-xxlarge"></div>
        </div>

        <div class="control-group">
          <label>RUC</label>
          <div class="controls"><input v-model="form.ruc"></div>
        </div>

        <div class="control-group">
          <label>Dirección</label>
          <div class="controls"><textarea v-model="form.direccion"></textarea></div>
        </div>

        <div class="control-group">
          <label>Teléfono</label>
          <div class="controls"><input v-model="form.telefono"></div>
        </div>

        <div class="control-group">
          <label>Email</label>
          <div class="controls"><input v-model="form.email"></div>
        </div>

        <div class="control-group">
          <label>Activo</label>
          <div class="controls">
            <select v-model="form.is_activo">
              <option :value="1">Sí</option>
              <option :value="0">No</option>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardar">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

  </div>
</div>

<script>
new Vue({
  el: "#appProveedor",
  data:{
    apphost: (typeof apphost !== "undefined" ? apphost : ""),
    dt: null,
    proveedores: [],
    nuevo: { nombre:"", ruc:"", direccion:"", telefono:"", email:"" },
    form:{},
    detalle:{}
  },

  methods:{

    listar(){
      axios.get(`${this.apphost}/proveedor/listar`).then(r=>{
        this.proveedores = r.data;

        this.$nextTick(()=>{

          if(!this.dt){
            this.dt = $("#tablaProveedor").DataTable({
              dom:"frtip",
              order:[[0,'desc']]
            });

            const self = this;

            $("#tablaProveedor tbody")
              .on("click","a.detalle",function(){
                const id = $(this).data("id");
                const p = self.proveedores.find(x=>x.proveedor_id==id);
                self.verDetalle(p);
              })
              .on("click","a.editar",function(){
                const id = $(this).data("id");
                const p = self.proveedores.find(x=>x.proveedor_id==id);
                self.abrirEditar(p);
              })
              .on("click","a.eliminar",function(){
                const id = $(this).data("id");
                const p = self.proveedores.find(x=>x.proveedor_id==id);
                self.eliminar(p);
              });

          }

          this.dt.clear();
          this.proveedores.forEach(pr=>{
            const acciones = `
            <div class="btn-group">
              <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                Opciones <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" class="detalle" data-id="${pr.proveedor_id}">Detalle</a></li>
                <li><a href="#" class="editar"  data-id="${pr.proveedor_id}">Editar</a></li>
                <li><a href="#" class="eliminar" data-id="${pr.proveedor_id}">Eliminar</a></li>
              </ul>
            </div>`;

            this.dt.row.add([
              pr.proveedor_id,
              pr.nombre,
              pr.ruc,
              pr.telefono,
              pr.email,
              pr.is_activo==1 ? "Sí" : "No",
              acciones
            ]);
          });

          this.dt.draw(false);
        });

      });
    },

    abrirCrear(){
      this.nuevo = { nombre:"", ruc:"", direccion:"", telefono:"", email:"" };
      $("#modalCrearProveedor").modal("show");
    },

    crear(){
      axios.post(`${this.apphost}/proveedor/crear`, this.nuevo)
      .then(()=>{
        $("#modalCrearProveedor").modal("hide");
        this.listar();
      });
    },

    abrirEditar(p){
      this.form = JSON.parse(JSON.stringify(p));
      $("#modalEditarProveedor").modal("show");
    },

    guardar(){
      axios.post(`${this.apphost}/proveedor/editar`, this.form)
      .then(()=>{
        $("#modalEditarProveedor").modal("hide");
        this.listar();
      });
    },

    verDetalle(p){
      axios.get(`${this.apphost}/proveedor/detalle/${p.proveedor_id}`).then(r=>{
        this.detalle = r.data;
        $("#modalDetalleProveedor").modal("show");
      });
    },

    eliminar(p){
      apprise(`¿Eliminar proveedor <b>${p.nombre}</b>?`,{confirm:true},ok=>{
        if(!ok) return;
        axios.post(`${this.apphost}/proveedor/eliminar`,{ proveedor_id:p.proveedor_id })
        .finally(()=>this.listar());
      });
    }

  },

  mounted(){
    this.listar();
  }
});
</script>
