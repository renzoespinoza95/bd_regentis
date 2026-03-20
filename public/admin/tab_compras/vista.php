<!-- este es mi frontend usando boostrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
<div class="row-fluid" id="appCompra">
  <div class="span12">
    <div class="titulo-fijo clearfix">

    <div style="float:left;">
      <h2 style="margin:0;">Compras</h2>
    </div>

    <div class="btn-group pull-right">
      <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-bandcamp"></i>
        <span class="caret"></span>
      </button>

      <ul class="dropdown-menu pull-right">
        <li>
          <a href="#" @click.prevent="abrirModalCrear">
            <i class="fa fa-plus"></i> Nueva Compra
          </a>
        </li>

        <li>
          <a href="#" @click.prevent="abrirReporteFechas">
            <i class="fa fa-plus"></i> Reporte por Fechas
          </a>
        </li>
      </ul>
    </div>

  </div>
    <!-- ===========================
            TABLA
    ============================ -->
    <table id="tablaCompra" class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Proveedor</th>
          <th>Fecha</th>
          <th>Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>

    <!-- ===========================
           MODAL DETALLE
    ============================ -->
    <div id="modalDetalleCompra" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Detalle de Compra</h3></div>
      <div class="modal-body">

        <p><strong>ID:</strong> {{ detalle.cabecera.compra_id }}</p>
        <p><strong>Proveedor:</strong> {{ detalle.cabecera.razon_social }}</p>
        <p><strong>Fecha:</strong> {{ detalle.cabecera.fecha_compra }}</p>
        <p><strong>Total:</strong> S/ {{ detalle.cabecera.total }}</p>

        <h4>Productos</h4>
        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Costo</th>
              <th>Cantidad</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in detalle.detalle">
              <td>{{ d.producto }}</td>
              <td>{{ d.costo_unitario }}</td>
              <td>{{ d.cantidad }}</td>
              <td>{{ d.subtotal }}</td>
            </tr>
          </tbody>
        </table>

      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- ===========================
           MODAL CREAR COMPRA
    ============================ -->
    <div id="modalCrearCompra" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Nueva Compra</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Proveedor</label>
          <div class="controls">
            <v-select
              :options="proveedores"
              label="nombre"
              v-model="nuevo.proveedor"
              placeholder="Seleccione proveedor">
            </v-select>
          </div>
        </div>

        <div class="control-group">
          <label>Fecha</label>
          <div class="controls">
            <input type="date" v-model="nuevo.fecha_compra">
          </div>
        </div>

        <h4>Items</h4>

        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Costo</th>
              <th>Cant.</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
            <tbody>
              <tr v-for="(it,i) in nuevo.items" :key="i">
                <td>{{ it.producto }}</td>
                <td>{{ it.costo_unitario }}</td>
                <td>{{ it.cantidad }}</td>
                <td>{{ it.cantidad * it.costo_unitario }}</td>
                <td>
                  <a href="#" @click.prevent="quitarItem(it)">x</a>
                </td>
              </tr>
            </tbody>
        </table>

        <button class="btn btn-small btn-success"
                @click="abrirModalAgregarItemCompra">
          + Agregar prod.
        </button>        

        <hr>
        <h3>Total: S/ {{ totalNuevo }}</h3>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearCompra">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- ===========================
           MODAL EDITAR COMPRA
           (SOLO OBSERVACIÓN)
    ============================ -->
    <div id="modalEditarCompra" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Editar Compra</h3></div>
      <div class="modal-body">

        <p><strong>ID:</strong> {{ form.compra_id }}</p>

        <div class="control-group">
          <label>Observación</label>
          <div class="controls">
            <textarea v-model="form.observaciones" class="input-xxlarge"></textarea>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarEdicion">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <div id="modalReporteFechas" class="modal hide fade">
      <div class="modal-header">
        <h3>Reporte de Compras por Fecha</h3>
      </div>

      <div class="modal-body">
        <label>Fecha Inicio</label>
        <input type="date" v-model="filtro.fecha_ini">

        <label>Fecha Término</label>
        <input type="date" v-model="filtro.fecha_fin">
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="imprimirReporte">
          Imprimir PDF
        </button>
        <button class="btn btn-success" @click="descargarExcel">
          Descargar Excel
        </button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>


    <div id="modalAddItems" class="modal hide fade">
      <div class="modal-header">
        <h3>Agregar Productos a Compra #{{ compraAdd.compra_id }}</h3>
      </div>

      <div class="modal-body">

        <h4>Productos actuales</h4>

        <table class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Costo</th>
              <th>Cantidad</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="p in itemsExistentes">
              <td>{{ p.producto }}</td>
              <td>{{ p.precio_unitario }}</td>
              <td>{{ p.cantidad }}</td>
            </tr>
          </tbody>
        </table>

        <h4 style="margin-top:15px">Agregar nuevos productos</h4>

        <table class="table table-bordered">
          <tbody>
            <tr v-for="it in compraAdd.items_nuevos">
              <td>
                <select v-model="it.product_id" class="input-large">
                  <option v-for="p in productos" :value="p.product_id">{{ p.name }}</option>
                </select>
              </td>
              <td><input v-model="it.costo_unitario" class="input-mini"></td>
              <td><input v-model="it.cantidad" class="input-mini"></td>
              <td>
                <a href="#" @click.prevent="quitarItemAdd(it)">x</a>
              </td>
            </tr>
          </tbody>
        </table>

        <button class="btn btn-mini" @click="agregarItemAdd">+ Item</button>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarAddItems">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- MODAL Agregar Producto (COMPRAS) -->
    <div id="modalAgregarItemCompra" class="modal hide fade">
      <div class="modal-header">
        <h3>Agregar Producto</h3>
      </div>

      <div class="modal-body">

        <label>Producto</label>
        <v-select
          :options="productosSelectCompra"
          label="label"
          v-model="itemTemp.producto"
        ></v-select>

        <label>Costo Unitario</label>
        <input type="number" v-model.number="itemTemp.costo_unitario">

        <label>Cantidad</label>
        <input type="number" v-model.number="itemTemp.cantidad" min="1">

        <label>Subtotal</label>
        <input
          type="number"
          :value="(itemTemp.cantidad * itemTemp.costo_unitario).toFixed(2)"
          readonly
        >

      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" @click="confirmarAgregarItemCompra">
          Aceptar
        </button>
        <button class="btn" data-dismiss="modal">
          Cancelar
        </button>
      </div>
    </div>


  </div>
</div>
<script>
Vue.component('v-select', VueSelect.VueSelect);
</script>
<script>
new Vue({
  el: '#appCompra',
  data:{
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    compras: [],
    compraAdd:{ compra_id:null, items:[] },
    proveedores: [],
    itemsExistentes:[],
    productos: [],
    nuevo: {
      proveedor: null,
      fecha_compra: '',
      items: []
    },
    form: {},
    filtro:{ fecha_ini:'', fecha_fin:'' },
    detalle: { cabecera:{}, detalle:[] },
    itemTemp:{
      producto: null,
      product_id: null,
      costo_unitario: 0,
      cantidad: 1
    },

    dt: null
  },

  computed:{
    totalNuevo(){
      return this.nuevo.items.reduce((s,it)=> s + (it.cantidad * it.costo_unitario), 0);
    },
    productosSelectCompra(){
      return this.productos.map(p => ({
        product_id: p.product_id,
        label: `${p.name}`
      }));
    }

  },

  methods:{
    listar(){
      axios.get(`${this.apphost}/compra/listar`).then(r=>{
        this.compras = r.data;

        this.$nextTick(()=>{

          if(!this.dt){
            this.dt = $('#tablaCompra').DataTable({
              language: dt_language,
              dom:'frtip', order:[[0,'desc']]
            });

            const self=this;
            $('#tablaCompra tbody')
              .on('click','a.detalle',function(e){
                const id = $(this).data("id");
                self.abrirDetalle(id);
              })
              .on('click','a.editar',function(e){
                const id = $(this).data("id");
                const c = self.compras.find(x=>x.compra_id==id);
                self.abrirEditar(c);
              })
              .on('click','a.add-items',function(e){
                e.preventDefault();
                const id = $(this).data("id");
                const c = self.compras.find(x => x.compra_id == id);
                self.abrirAgregarProductos(c);
              })
              .on('click','a.eliminar',function(e){
                const id = $(this).data("id");
                const c = self.compras.find(x=>x.compra_id==id);
                self.eliminarCompra(c);
              });
          }

          this.dt.clear();

          this.compras.forEach(c=>{
            const acciones = `
            <div class="btn-group">
              <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                Opciones <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" class="detalle" data-id="${c.compra_id}">Detalle</a></li>
                <li><a href="#" class="eliminar" data-id="${c.compra_id}">Eliminar</a></li>
                <li>
                  <a href="#" class="add-items" data-id="${c.compra_id}">
                    Agregar Productos
                  </a>
                </li>
              </ul>
            </div>`;

            this.dt.row.add([
              c.compra_id,
              c.razon_social,
              c.fecha_compra,
              c.total,
              acciones
            ]);
          });

          this.dt.draw(false);
        });
      });
    },

    abrirModalCrear(){
      this.nuevo = { 
        proveedor_id:null, 
        fecha_compra:this.fechaHoy(),
        items:[] };
      $('#modalCrearCompra').modal('show');
    },

    abrirReporteFechas(){
        this.filtro={ fecha_ini:'', fecha_fin:'' };
        $('#modalReporteFechas').modal('show');
    },

    imprimirReporte(){
      const {fecha_ini,fecha_fin} = this.filtro;
      window.open(
        `${this.apphost}/imp_compras_fecha?ini=${fecha_ini}&fin=${fecha_fin}`,
        '_blank'
      );
    },

    descargarExcel(){
      const {fecha_ini,fecha_fin} = this.filtro;
      window.location =
        `${this.apphost}/imp_compras_fecha_excel?ini=${fecha_ini}&fin=${fecha_fin}`;
    },    

    agregarItem(){
      this.nuevo.items.push({ product_id:null, costo_unitario:0, cantidad:1 });
    },

    quitarItem(it){
      this.nuevo.items = this.nuevo.items.filter(x=>x!==it);
    },

    abrirModalAgregarItemCompra(){
      this.itemTemp = {
        producto: null,
        product_id: null,
        costo_unitario: 0,
        cantidad: 1
      };

      this.$nextTick(() => {
        this.liberarFocusVueSelect();
        $('#modalAgregarItemCompra').modal('show');
      });
    },

    confirmarAgregarItemCompra(){

      if(!this.itemTemp.producto){
        apprise('Seleccione un producto');
        return;
      }

      if(this.itemTemp.cantidad <= 0){
        apprise('Cantidad inválida');
        return;
      }

      this.nuevo.items.push({
        product_id: this.itemTemp.producto.product_id,
        producto: this.itemTemp.producto.label,
        cantidad: this.itemTemp.cantidad,
        costo_unitario: this.itemTemp.costo_unitario
      });

      $('#modalAgregarItemCompra').modal('hide');
    },

    abrirAgregarProductos(c){
      this.compraAdd = {
        compra_id: c.compra_id,
        items_nuevos: []
      };

      axios.get(`${this.apphost}/compra/items/${c.compra_id}`)
        .then(r => {
          this.itemsExistentes = r.data;
          $('#modalAddItems').modal('show');
        });
    },

    agregarItemAdd(){
      this.compraAdd.items_nuevos.push({
        product_id:null,
        cantidad:1,
        costo_unitario:0
      });
    },

    guardarAddItems(){
      axios.post(`${this.apphost}/compra/agregar-items`, {
        compra_id: this.compraAdd.compra_id,
        items: this.compraAdd.items_nuevos
      }).then(()=>{
        $('#modalAddItems').modal('hide');
        this.listar();
      });
    },

    fechaHoy(){
      const d = new Date();
      const year  = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, '0');
      const day   = String(d.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    },


    crearCompra(){

      if (!this.nuevo.proveedor || !this.nuevo.proveedor.proveedor_id) {
        alert('Debe seleccionar proveedor');
        return;
      }

      if (this.nuevo.items.length === 0) {
        alert('Debe agregar al menos un producto');
        return;
      }

      let fecha = this.nuevo.fecha_compra;
      if (fecha) {
        fecha = `${fecha} 12:00:00`;
      }

      axios.post(`${this.apphost}/compra/crear`, {
        proveedor_id: Number(this.nuevo.proveedor.proveedor_id),
        fecha_compra: fecha,
        observaciones: '',
        items: this.nuevo.items
      }).then(()=>{
        $('#modalCrearCompra').modal('hide');
        this.listar();
      });
    },

    abrirDetalle(id){
      axios.get(`${this.apphost}/compra/detalle/${id}`).then(r=>{
        this.detalle = r.data;
        $('#modalDetalleCompra').modal('show');
      });
    },

    liberarFocusVueSelect(){
      $(document).off('focusin.modal');
    },

    abrirEditar(c){
      this.form = JSON.parse(JSON.stringify(c));
      $('#modalEditarCompra').modal('show');
    },

    guardarEdicion(){
      axios.post(`${this.apphost}/compra/editar`, this.form)
      .then(()=>{ $('#modalEditarCompra').modal('hide'); this.listar(); });
    },

    eliminarCompra(c){
      apprise(`¿Eliminar compra #${c.compra_id}?`, {confirm:true}, ok=>{
        if(!ok) return;
        axios.post(`${this.apphost}/compra/eliminar`, { compra_id: c.compra_id })
        .finally(()=> this.listar());
      });
    },

    cargarProveedores(){
      axios.get(`${this.apphost}/proveedor/listar`)
      .then(r=> this.proveedores = r.data);
    },

    cargarProductos(){
      axios.get(`${this.apphost}/product/listar`)
      .then(r=> this.productos = r.data);
    }
  },

  mounted(){
    this.cargarProveedores();
    this.cargarProductos();
    this.listar();
  },

  watch:{
    'itemTemp.producto'(p){
      if(p){
        this.itemTemp.product_id = p.product_id;
      }
    }
  }

});
</script>
