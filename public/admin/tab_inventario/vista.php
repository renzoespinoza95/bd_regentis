<div class="row-fluid" id="appInventario">

  <div class="span12">
      <div class="titulo-fijo clearfix">

    <div style="float:left;">
      <h2 style="margin:0;">Inventario</h2>
    </div>

  </div>

    <!-- TABLA -->
    <table id="tablaInv" class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Producto</th>
          <th>Stock</th>
          <th>Mín</th>
          <th>Máx</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>

    <!-- MODAL DETALLE -->
    <div id="modalDetalleInv" class="modal hide fade">
      <div class="modal-header"><h3>Detalle Inventario</h3></div>
      <div class="modal-body">

        <p><b>Producto:</b> {{ detalle.inventario.producto }}</p>
        <p><b>Stock:</b> {{ detalle.inventario.stock_actual }}</p>

        <h4>Movimientos:</h4>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Origen</th>
              <th>Cantidad</th>
              <th>Precio</th>
              <th>Stock</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in detalle.movimientos">
              <td>{{ m.fecha }}</td>
              <td>{{ m.tipo }}</td>
              <td>{{ m.origen }}</td>
              <td>{{ m.cantidad }}</td>
              <td>{{ m.precio_unitario }}</td>
              <td>{{ m.stock_resultante }}</td>
            </tr>
          </tbody>
        </table>

      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- MODAL CREAR -->
    <div id="modalCrearInv" class="modal hide fade">
      <div class="modal-header"><h3>Nuevo Inventario</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Producto</label>
          <div class="controls">
            <select v-model="nuevo.producto_id">
              <option v-for="p in productos" :value="p.product_id">{{ p.name }}</option>
            </select>
          </div>
        </div>

        <div class="control-group">
          <label>Stock Inicial</label>
          <div class="controls"><input v-model="nuevo.stock_actual"></div>
        </div>

        <div class="control-group">
          <label>Stock Mínimo</label>
          <div class="controls"><input v-model="nuevo.stock_min"></div>
        </div>

        <div class="control-group">
          <label>Stock Máximo</label>
          <div class="controls"><input v-model="nuevo.stock_max"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crear">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- MODAL EDITAR -->
    <div id="modalEditarInv" class="modal hide fade">
      <div class="modal-header"><h3>Editar Inventario</h3></div>
      <div class="modal-body">

        <p><b>Producto:</b> {{ form.producto }}</p>

        <div class="control-group">
          <label>Mínimo</label>
          <div class="controls"><input v-model="form.stock_min"></div>
        </div>

        <div class="control-group">
          <label>Máximo</label>
          <div class="controls"><input v-model="form.stock_max"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardar">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <div id="modalLimitesInv" class="modal hide fade">
  <div class="modal-header">
    <h3>Establecer límites</h3>
  </div>

  <div class="modal-body">

    <p><b>Producto:</b> {{ form.producto }}</p>

    <div class="control-group">
      <label>Stock mínimo</label>
      <div class="controls">
        <input type="number" v-model.number="form.stock_min">
      </div>
    </div>

    <div class="control-group">
      <label>Stock máximo</label>
      <div class="controls">
        <input type="number" v-model.number="form.stock_max">
      </div>
    </div>

  </div>

  <div class="modal-footer">
    <button class="btn btn-primary" @click="guardarLimites">
      Guardar
    </button>
    <button class="btn" data-dismiss="modal">
      Cancelar
    </button>
  </div>
</div>


  </div>
</div>


<script>
new Vue({
  el:"#appInventario",
  data:{
    apphost:(typeof apphost!=='undefined'?apphost:''),
    inventario:[],
    productos:[],
    detalle:{inventario:{},movimientos:[]},
    nuevo:{ producto_id:null, stock_actual:0, stock_min:0, stock_max:999 },
    form:{},
    dt:null
  },

  methods:{

    cargarProductos(){
      axios.get(`${this.apphost}/inventario/productos`)
           .then(r=>this.productos=r.data);
    },

    listar(){
      axios.get(`${this.apphost}/inventario/listar`).then(r=>{
        this.inventario = r.data;

        this.$nextTick(()=>{

          if(!this.dt){
            this.dt = $('#tablaInv').DataTable({
              dom:'frtip'
            });

            const self=this;
            $("#tablaInv tbody")
              .on("click","a.detalle",function(){
                const id=$(this).data("id");
                const item=self.inventario.find(x=>x.inventario_id==id);
                self.verDetalle(item);
              })
              .on("click","a.editar",function(){
                const id=$(this).data("id");
                const item=self.inventario.find(x=>x.inventario_id==id);
                self.abrirEditar(item);
              })
              .on("click","a.limites",function(){
                const id = $(this).data("id");
                const item = self.inventario.find(x => x.inventario_id == id);
                self.abrirLimites(item);
              })
              .on("click","a.eliminar",function(){
                const id=$(this).data("id");
                const item=self.inventario.find(x=>x.inventario_id==id);
                self.eliminar(item);
              });
          }

          this.dt.clear();
          this.inventario.forEach(i=>{
            const acciones = `
              <div class="btn-group">
                <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                  Opciones <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                  <li>
                    <a href="#" class="detalle" data-id="${i.inventario_id}">
                      Detalle
                    </a>
                  </li>
                  <li>
                    <a href="#" class="limites" data-id="${i.inventario_id}">
                      Establecer límites
                    </a>
                  </li>
                  <li class="divider"></li>
                  <li>
                    <a href="#" class="eliminar" data-id="${i.inventario_id}">
                      Eliminar
                    </a>
                  </li>
                </ul>
              </div>`;

            const iconoStock = i.stock_actual > i.stock_min
              ? `<i class="fa fa-thumbs-up" style="color:green"></i>`
              : `<i class="fa fa-thumbs-down" style="color:red"></i>`;

            const stockTxt = `
              ${i.stock_actual}
              &nbsp;${iconoStock}
            `;
  


            this.dt.row.add([
              i.inventario_id,
              i.producto,
              stockTxt,
              i.stock_min,
              i.stock_max,
              acciones
            ]);
          });
          this.dt.draw(false);

        });
      });
    },

    abrirLimites(i){
      this.form = JSON.parse(JSON.stringify(i));
      $('#modalLimitesInv').modal('show');
    },

    guardarLimites(){
      axios.post(`${this.apphost}/inventario/limites`,{
        inventario_id: this.form.inventario_id,
        stock_min: this.form.stock_min,
        stock_max: this.form.stock_max
      }).then(()=>{
        $('#modalLimitesInv').modal('hide');
        this.listar();
      });
    },


    verDetalle(i){
      axios.get(`${this.apphost}/inventario/detalle/${i.inventario_id}`).then(r=>{
        this.detalle = r.data;
        $("#modalDetalleInv").modal("show");
      });
    },

    abrirCrear(){
      this.nuevo={producto_id:null,stock_actual:0,stock_min:0,stock_max:999};
      $("#modalCrearInv").modal("show");
    },

    crear(){
      axios.post(`${this.apphost}/inventario/crear`,this.nuevo)
           .then(()=>{ $("#modalCrearInv").modal("hide"); this.listar(); });
    },

    abrirEditar(i){
      this.form = JSON.parse(JSON.stringify(i));
      $("#modalEditarInv").modal("show");
    },

    guardar(){
      axios.post(`${this.apphost}/inventario/editar`,this.form)
           .then(()=>{ $("#modalEditarInv").modal("hide"); this.listar(); });
    },

    eliminar(i){
      apprise(`¿Eliminar inventario del producto <b>${i.producto}</b>?`,{confirm:true},ok=>{
        if(!ok) return;
        axios.post(`${this.apphost}/inventario/eliminar`,{inventario_id:i.inventario_id})
             .finally(()=>this.listar());
      });
    }

  },

  mounted(){
    this.cargarProductos();
    this.listar();
  }
});
</script>
