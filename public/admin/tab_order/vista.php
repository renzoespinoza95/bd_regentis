<div class="row-fluid" id="appOrder">
<!-- este es mi frontend usando boostrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
  <div class="span12">
    <h2>Ventas</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrear">
        <i class="icon-plus icon-white"></i> Nueva venta
      </button>
      <button class="btn btn-info" @click="abrirModalClientes">
        <i class="icon-user icon-white"></i> Clientes
      </button>
      <div class="btn-group">
        <button class="btn btn-warning dropdown-toggle" data-toggle="dropdown">
          <i class="icon-list icon-white"></i> Reportes <span class="caret"></span>
        </button>
        <ul class="dropdown-menu">
          <li>
            <a href="#" @click="abrirReporteVentas">Ventas por Fecha</a>
          </li>
          <li>
            <a href="#" @click="abrirReporteVentasAdmin">Ventas por Fecha + Admin</a>
          </li>
          <li>
            <a href="#" @click="abrirReporteResumenVentas">
              Resumen Ventas
            </a>
          </li>
        </ul>
      </div>

      <button class="btn btn-info" style="margin-left:5px" @click="abrirModalMesas">
        <i class="icon-th-large icon-white"></i> Mesas
      </button>



    </div>

    <table id="tablaOrder" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Código</th>
          <th>Cliente</th>
          <th>Mesa</th>
          <th>Modo</th>
          <th>Administrador</th>
          <th>Tipo de pago</th>
          <th>Fecha</th>
          <th>Total</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>

    <!-- ===============================
         MODAL DETALLE (incluye CRUD detail)
    ================================ -->
    <div id="modalDetalleOrder" class="modal hide fade">
      <div class="modal-header">
        <h3>Detalle de Orden #{{ detalle.product_order_id }}</h3>
      </div>
      <div class="modal-body">

        <div class="row-fluid order-resumen">

          <div class="span6">
            <p><b>Código:</b> {{ detalle.serial }}</p>
            <p><b>Cliente:</b> {{ detalle.cliente }}</p>
            <p><b>Tipo de pago:</b> {{ detalle.tipo_pago }}</p>
          </div>

          <div class="span6">            
            <p><b>Total orden:</b> 
              <span class="label label-success">
                S/ {{ totalDetalleOrden }}
              </span>
            </p>
          </div>

        </div>

        <h4>Productos:</h4>

        <!-- Tabla detalles -->
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Producto</th>
              <th>Cant.</th>
              <th>Precio</th>
              <th>Total</th>
              <th>Acc.</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in detallesOrder" :key="d.product_order_detail_id">
              <td>{{ d.product_name }}</td>
              <td>{{ d.amount }}</td>
              <td>{{ d.price_item }}</td>
              <td>{{ (d.amount * d.price_item).toFixed(2) }}</td>
              <td>
                <button
                  class="btn btn-mini btn-danger"
                  v-if="!ordenCerrada"
                  @click="eliminarDetail(d)">
                  X
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <button
          class="btn btn-success"
          v-if="!ordenCerrada"
          @click="abrirCrearDetail">
          Nuevo prod.
        </button>

      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- MODAL NUEVA ORDEN -->
    <div id="modalCrearOrder" class="modal hide fade">
      <div class="modal-header"><h3>Nueva Orden</h3></div>
      <div class="modal-body">

      <div class="control-group">
        <label>Cliente</label>
        <v-select
          :options="clientes"
          label="label"
          v-model="nueva.cliente"
        ></v-select>
      </div>

      <div class="control-group">
        <label>Tipo de pago</label>
        <div class="controls">
          <select v-model="nueva.tipo_pago_id">
            <option value="">-- Seleccione --</option>
            <option v-for="t in tiposPago" :value="t.tipo_pago_id">
              {{ t.descripcion }}
            </option>
          </select>
        </div>
      </div>

      <div class="control-group">
        <label>Mesa</label>
        <v-select
          :options="opcionesMesa"
          label="label"
          v-model="nueva.mesa"
        ></v-select>
      </div>




        <h4>Items</h4>

<table class="table table-bordered table-striped">
  <thead>
    <tr>
      <th>Producto</th>
      <th>Cant.</th>
      <th>Precio</th>
      <th>Total</th>
      <th>Acc.</th>
    </tr>
  </thead>
  <tbody>
    <tr v-for="(i,idx) in nueva.items" :key="idx">
      <td>{{ i.product_name }}</td>
      <td>{{ i.amount }}</td>
      <td>{{ i.price_item }}</td>
      <td>{{ (i.amount*i.price_item).toFixed(2) }}</td>
      <td>
        <button class="btn btn-mini btn-danger"
                @click="nueva.items.splice(idx,1)">X</button>
      </td>
    </tr>
  </tbody>
</table>

<button class="btn btn-success" @click="abrirModalAgregarItemNuevaOrden">
  Agregar productos
</button>


      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearOrder">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- MODAL EDITAR ORDEN -->
    <div id="modalEditarOrder" class="modal hide fade">
      <div class="modal-header"><h3>Editar Orden</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Cliente</label>
          <div class="controls"><input v-model="form.buyer"></div>
        </div>


        <div class="control-group">
          <label>Estado</label>
          <div class="controls">
            <select v-model="form.status">
              <option>WAITING</option>
              <option>PAID</option>
              <option>SENT</option>
              <option>CANCELLED</option>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarOrder">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- --- CRUD DETALLES (Modal Crear / Editar) ---- -->

    <!-- Crear Detalle -->
    <div id="modalCrearDetail" class="modal hide fade">
      <div class="modal-header"><h3>Detalle: Agregar prod.</h3></div>
      <div class="modal-body">
        <label>Producto</label>
        <v-select
          :options="productosSelect"
          label="label"
          v-model="detailForm.producto"
        ></v-select>

        <label>Cantidad</label>
        <input v-model.number="detailForm.amount">

        <label>Total</label>
        <input :value="totalDetalle.toFixed(2)" readonly>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearDetail">Agregar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- Editar Detalle -->
    <div id="modalEditarDetail" class="modal hide fade">
      <div class="modal-header"><h3>Editar Ítem</h3></div>
      <div class="modal-body">
        <label>Producto</label>
        <select v-model="detailForm.product_id">
          <option v-for="p in productos" :value="p">
            {{ p.name }} - S/ {{ p.price }}
          </option>
        </select>

        <label>Cantidad</label>
        <input v-model.number="detailForm.amount">

        <label>Total</label>
        <input :value="totalItem" readonly>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarDetail">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>


    <!-- MODAL Nuevo: Agregar prod. (NUEVA ORDEN) -->
    <div id="modalAgregarItemNuevaOrden" class="modal hide fade">
      <div class="modal-header">
        <h3>Nuevo: Agregar prod.</h3>
      </div>

      <div class="modal-body">

        <label>Producto</label>
        <v-select
          :options="productosSelect"
          label="label"
          v-model="itemForm.producto"
        ></v-select>

        <label>Cantidad</label>
        <input type="number" v-model.number="itemForm.amount" min="1">

        <label>Precio Unitario</label>
        <input type="number" :value="itemForm.price_item" readonly>

        <label>Total</label>
        <input type="number" :value="totalItemNuevaOrden" readonly>

      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" @click="confirmarAgregarItem">
          Agregar
        </button>
        <button class="btn" data-dismiss="modal">
          Cancelar
        </button>
      </div>
    </div>


    <div id="modalClientes" class="modal hide fade">
      <div class="modal-header">
        <h3>Clientes</h3>
      </div>
      <div class="modal-body">

        <button class="btn btn-success" @click="abrirModalNuevoCliente">
          + Agregar Cliente
        </button>

        <table id="tablaClientes" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>DNI</th>
              <th>Nombre</th>
              <th>Acciones</th>
            </tr>
          </thead>
        </table>

      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <div id="modalNuevoCliente" class="modal hide fade">
      <div class="modal-header">
        <h3>Nuevo Cliente</h3>
      </div>
      <div class="modal-body">

        <label>DNI</label>
        <div class="input-append">
          <input v-model="clienteForm.dni">
          <button class="btn" @click="generarDniFake">🎲</button>
        </div>

        <label>Nombre</label>
        <input v-model="clienteForm.nombre">

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarCliente">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>


    <div id="modalEditarCliente" class="modal hide fade">
      <div class="modal-header">
        <h3>Editar Cliente</h3>
      </div>
      <div class="modal-body">

        <label>DNI</label>
        <input v-model="clienteEdit.dni">

        <label>Nombre</label>
        <input v-model="clienteEdit.nombre">

        <label>Dirección</label>
        <input v-model="clienteEdit.direccion">

        <label>Teléfono</label>
        <input v-model="clienteEdit.telefono">

        <label>Email</label>
        <input v-model="clienteEdit.email">

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="actualizarCliente">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>


    <div id="modalReporteVentas" class="modal hide fade">
      <div class="modal-header">
        <h3>Reporte de Ventas por Fecha</h3>
      </div>

      <div class="modal-body">
        <label>Fecha Inicio</label>
        <input type="date" v-model="reporte.fecha_inicio">

        <label>Fecha Fin</label>
        <input type="date" v-model="reporte.fecha_fin">
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" @click="reporteVentasPDF">
          📄 PDF
        </button>
        <button class="btn btn-success" @click="reporteVentasExcel">
          📊 Excel
        </button>
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>


    <div id="modalMesas" class="modal hide fade">
      <div class="modal-header">
        <h3>Estado de Mesas</h3>
      </div>

      <div class="modal-body">

        <table class="table table-bordered">
          <thead>
            <tr>
              <th>Mesa</th>
              <th>Estado</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="m in mesas" :key="m.mesa_id">
              <td>{{ m.nombre }}</td>
              <td>
                <span
                  class="label"
                  :class="m.estado === 'DISPONIBLE' ? 'label-success' : 'label-important'">
                  {{ m.estado }}
                </span>
              </td>
              <td>
                <button
                  class="btn btn-mini btn-danger"
                  v-if="m.estado === 'OCUPADA'"
                  @click="confirmarLiberarMesa(m)">
                  Liberar
                </button>
              </td>
            </tr>
          </tbody>
        </table>


      </div>

      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>



    <div id="modalReporteVentasAdmin" class="modal hide fade">
      <div class="modal-header">
        <h3>Reporte de Ventas por Fecha y Administrador</h3>
      </div>

      <div class="modal-body">
        
        <label>Administrador</label>
        <select v-model="reporte.admin_id">
          <option value="">-- Todos --</option>
          <option 
            v-for="a in administradores" 
            :value="a.administrador_id">
            {{ a.nombres_apellidos }}
          </option>
        </select>

        <label>Fecha Inicio</label>
        <input type="date" v-model="reporte.fecha_inicio">

        <label>Fecha Fin</label>
        <input type="date" v-model="reporte.fecha_fin">
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" @click="reporteVentasAdminPDF">
          📄 PDF
        </button>
        <button class="btn btn-success" @click="reporteVentasAdminExcel">
          📊 Excel
        </button>
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>


    <div id="modalResumenVentas" class="modal hide fade">
      <div class="modal-header">
        <h3>Resumen de Ventas</h3>
      </div>

      <div class="modal-body">
        <label>Fecha Inicio</label>
        <input type="date" v-model="resumen.fecha_inicio">

        <label>Fecha Fin</label>
        <input type="date" v-model="resumen.fecha_fin">
      </div>

      <div class="modal-footer">
        <button class="btn btn-danger" @click="resumenVentasPDF">
          📄 PDF
        </button>
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>






  </div>
</div>

<script>
Vue.component('v-select', VueSelect.VueSelect);
</script>
<script>
new Vue({
  el:"#appOrder",
  data:{
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    ordenes:[],
    productos:[],
    mesas: [],  
    form:{},
    detalle:{},
    resumen:{
      fecha_inicio:'',
      fecha_fin:''
    },
    detallesOrder:[],
    administradores: [],
    reporte:{
      fecha_inicio:'',
      fecha_fin:'',
      admin_id:''
    },
    reporteResultados:[],
    administradores:[],

    clientes:[],
    clienteForm:{ dni:'', nombre:'' },
    clienteEdit:{},
    dtClientes:null,

    detailForm:{
      order_id:null,
      producto:null,   // 👈 objeto seleccionado
      product_id:null,
      amount:1,
      price_item:0
    },
    itemForm:{
      producto: null,   // 👈 objeto seleccionado
      product_id: null,
      amount: 1,
      price_item: 0
    },
    dt:null,
    cajaActual: null,
    caja_id: null,
    tiposPago: [],
    nueva:{
      cliente_id: null,
      telefono: '',
      total_fees: 0,
      items: [],
      tipo_pago_id: null,
      mesa: null
    },
  },

  methods:{
    listar(){
      axios.get(`${this.apphost}/product_order/listar`).then(r=>{
        this.ordenes=r.data;

        this.$nextTick(()=>{
          if(!this.dt){
            this.dt = $('#tablaOrder').DataTable({
              dom:'frtip', order:[[0,'desc']]
            });

            const self=this;
            $('#tablaOrder tbody')
            .on('click','a.detalle',function(){
              const id = $(this).data("id");
              const o = self.ordenes.find(x => x.product_order_id == id);
              self.abrirDetalle(o);
            })
            .on('click','a.editar',function(){
              const id = $(this).data("id");
              const o = self.ordenes.find(x => x.product_order_id == id);
              self.abrirEditar(o);
            })
            .on('click','a.eliminar',function(){
              const id = $(this).data("id");
              const o = self.ordenes.find(x => x.product_order_id == id);
              self.eliminar(o);
            });

            $('#tablaOrder tbody').on('click','a.liberar',function(){
              const id = $(this).data('id');

              apprise('¿Liberar esta mesa?', {confirm:true}, ok=>{
                if(!ok) return;

                axios.post(`${self.apphost}/product_order/liberar_mesa`,{
                  product_order_id: id
                }).then(()=>{
                  self.listar();
                  self.cargarMesas();
                });
              });
            });

          }

          this.dt.clear();
          this.ordenes.forEach(o=>{

              let extra = '';

              // 👇 AQUÍ VA TU CÓDIGO
              if(o.modo_order_id === 2){
                extra = `
                  <li>
                    <a href="#" class="liberar" data-id="${o.product_order_id}">
                      Liberar mesa
                    </a>
                  </li>`;
              }

              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li>
                      <a href="#" class="detalle" data-id="${o.product_order_id}">
                        Detalle
                      </a>
                    </li>
                    ${extra}
                    <li class="divider"></li>
                    <li>
                      <a href="#" class="eliminar" data-id="${o.product_order_id}">
                        Eliminar
                      </a>
                    </li>
                  </ul>
                </div>`;


            const tipoPagoTxt = o.tipo_pago || '—';

            const mesaTxt = o.modo === 'MESA'
              ? (o.mesa_nombre || `#${o.mesa_id}`)
              : '—';

            const modoTxt = o.modo_order_id === 2
              ? `<span class="label label-important">${o.modo_order}</span>`
              : `<span class="label label-success">${o.modo_order}</span>`;



            this.dt.row.add([
              o.product_order_id,
              o.serial,
              o.cliente,
              o.mesa_nombre || '—',
              modoTxt,
              o.administrador || '—',
              o.tipo_pago || '—',
              o.fecha,
              o.total_fees,
              actions
            ]);


          });
          this.dt.draw(false);
        });
      });
    },

    abrirModalMesas(){
      axios.get(`${this.apphost}/mesa/listar`)
        .then(r => {
          this.mesas = r.data;
          $('#modalMesas').modal('show');
        });
    },

    abrirModalCrear() {
      axios.get(`${this.apphost}/auth/administrador-actual`).then(r=>{

        const caja = r.data.caja;

        if(caja.estado !== 'ABIERTA'){
          apprise('La caja de este usuario está cerrada');
          return;
        }

        // ✅ caja abierta
        this.cajaActual = caja;
        this.caja_id = caja.caja_id;

        // preparar orden
        this.nueva = {
          cliente_id:null,
          buyer:'',
          address:'',
          total_fees:0,
          items:[],
          caja_id: caja.caja_id,
          mesa: null
        };

        $('#modalCrearOrder').modal('show');

      }).catch(()=>{
        apprise('No se pudo verificar el estado de la caja');
      });
    },

    abrirReporteVentas(){
      this.reporteResultados = [];
      $('#modalReporteVentas').modal('show');
    },

    abrirReporteVentasAdmin(){
      this.reporteResultados = [];
      $('#modalReporteVentasAdmin').modal('show');
    },

    buscarReporteVentas(){
      axios.post(`${this.apphost}/reporte/ventas`, this.reporte)
        .then(r => this.reporteResultados = r.data);
    },

    buscarReporteVentasAdmin(){
      axios.post(`${this.apphost}/reporte/ventas-admin`, this.reporte)
        .then(r => this.reporteResultados = r.data);
    },

    abrirModalClientes(){
      $('#modalClientes').modal('show');
      this.listarClientes();
    },

    abrirModalAgregarItemNuevaOrden(){
      this.itemForm = {
        product_id: '',
        amount: 1,
        price_item: 0
      };
      $('#modalAgregarItemNuevaOrden').modal('show');
    },

    listarClientes(){
      axios.get(`${this.apphost}/cliente/listar`).then(r=>{
        this.clientes = r.data;

        this.$nextTick(()=>{
          if(!this.dtClientes){
            this.dtClientes = $('#tablaClientes').DataTable({
                   language: dt_language,
                   scrollX: true,
                   dom: 'frtip',
                   order:[[0,'desc']]
                 });
          }

          this.dtClientes.clear();
          this.clientes.forEach(c=>{
            this.dtClientes.row.add([
              c.dni,
              c.nombre,
              `<button class="btn btn-mini btn-primary editar" data-id="${c.cliente_id}">Editar</button>`
            ]);
          });
          this.dtClientes.draw(false);

          const self=this;
          $('#tablaClientes').off().on('click','.editar',function(){
            const id=$(this).data('id');
            self.abrirEditarCliente(
              self.clientes.find(x=>x.cliente_id==id)
            );
          });
        });
      });
    },

    generarDniFake(){
      const letras = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
      let dni = '';
      for(let i=0;i<8;i++){
        dni += Math.random()<0.7
          ? Math.floor(Math.random()*10)
          : letras[Math.floor(Math.random()*letras.length)];
      }
      this.clienteForm.dni = dni;
    },

    guardarCliente(){
      if(!this.clienteForm.dni || !this.clienteForm.nombre){
        alert('DNI y Nombre son obligatorios');
        return;
      }

      axios.post(`${this.apphost}/cliente/crear`, this.clienteForm)
      .then(()=>{
        $('#modalNuevoCliente').modal('hide');
        this.listarClientes();
      });
    },

    actualizarCliente(){
      axios.post(`${this.apphost}/cliente/editar`, this.clienteEdit)
      .then(()=>{
        $('#modalEditarCliente').modal('hide');
        this.listarClientes();
      });
    },

    abrirEditarCliente(c){
      this.clienteEdit = JSON.parse(JSON.stringify(c));
      $('#modalEditarCliente').modal('show');
    },


    reporteVentasPDF(){
      const { fecha_inicio, fecha_fin } = this.reporte;
      window.open(
        `${this.apphost}/imp_ventas_fecha?ini=${fecha_inicio}&fin=${fecha_fin}`,
        '_blank'
      );
    },

    reporteVentasExcel(){
      const { fecha_inicio, fecha_fin } = this.reporte;
      window.open(
        `${this.apphost}/imp_ventas_fecha_excel?ini=${fecha_inicio}&fin=${fecha_fin}`,
        '_blank'
      );
    },

    reporteVentasAdminPDF(){
      const { fecha_inicio, fecha_fin, admin_id } = this.reporte;
      window.open(
        `${this.apphost}/imp_ventas_fecha_admin?ini=${fecha_inicio}&fin=${fecha_fin}&admin_id=${admin_id}`,
        '_blank'
      );
    },

    reporteVentasAdminExcel(){
      let { fecha_inicio, fecha_fin, admin_id } = this.reporte;

      // 🔧 NORMALIZAR
      if (!fecha_inicio || !fecha_fin) {
        alert('Debe seleccionar fecha inicio y fin');
        return;
      }

      if (!admin_id) admin_id = 0;

      window.open(
        `${this.apphost}/imp_ventas_fecha_admin_excel` +
        `?ini=${fecha_inicio}&fin=${fecha_fin}&admin_id=${admin_id}`,
        '_blank'
      );
    },


    crearOrder() {

      // 1️⃣ Cliente obligatorio
      if(!this.nueva.cliente_id){
        apprise('Debe seleccionar un cliente');
        return;
      }

      // 2️⃣ Mesa obligatoria
      if(!this.nueva.mesa){
        apprise('Debe seleccionar una mesa o DIRECTO');
        return;
      }

      // 3️⃣ Items
      if(this.nueva.items.length === 0){
        apprise('Agregue al menos un ítem');
        return;
      }

      // 4️⃣ Tipo de pago
      if(!this.nueva.tipo_pago_id){
        apprise('Seleccione tipo de pago');
        return;
      }

      const mesa_id = this.nueva.mesa.mesa_id;

      axios.post(`${this.apphost}/product_order/crear`,{
        cliente_id: this.nueva.cliente_id,
        phone: this.nueva.telefono || '',
        comment: '',
        total_fees: this.totalOrden,
        tipo_pago_id: this.nueva.tipo_pago_id,
        mesa_id: mesa_id,
        items: this.nueva.items
      }).then(()=>{
        $('#modalCrearOrder').modal('hide');
        this.listar();
        this.cargarMesas();  
      }).catch(e=>{
        apprise(e.response?.data?.msg || 'Error al crear la orden');
      });
    },


    abrirEditar(o){
      this.form = JSON.parse(JSON.stringify(o));
      $('#modalEditarOrder').modal('show');
    },

    guardarOrder(){
      axios.post(`${this.apphost}/product_order/editar`, this.form)
      .then(()=>{
        $('#modalEditarOrder').modal('hide');
        this.listar();
      });
    },

    abrirDetalle(o){
      axios.get(`${this.apphost}/product_order/detalle/${o.product_order_id}`).then(r=>{
        this.detalle     = r.data.order;
        this.detallesOrder = r.data.detalles;
        $('#modalDetalleOrder').modal('show');
      });
    },

    agregarItem(){
      const p = this.productos.find(
        x=>x.product_id==this.itemForm.product_id
      );

      this.nueva.items.push({
        product_id: p.product_id,
        product_name: p.name,
        amount: this.itemForm.amount,
        price_item: p.price
      });

      $('#modalAgregarItem').modal('hide');
    },

    eliminar(o){
      apprise(`¿Eliminar orden #${o.product_order_id}?`, {confirm:true}, ok=>{
        if(!ok) return;
        axios.post(`${this.apphost}/product_order/eliminar`,{ product_order_id: o.product_order_id })
        .finally(()=>this.listar());
      });
    },

    // ----------- CRUD DETALLES -------------
    abrirCrearDetail(){
      this.detailForm = {
        order_id: this.detalle.product_order_id,
        product_id: null,
        amount: 1,
        price_item: 0
      };
      $('#modalCrearDetail').modal('show');
    },

    crearDetail(){
      axios.post(`${this.apphost}/product_order_detail/crear`, this.detailForm)
      .then(()=>{
        $('#modalCrearDetail').modal('hide');
        this.abrirDetalle(this.detalle);
      });
    },

    abrirEditarDetail(d){
      this.detailForm = JSON.parse(JSON.stringify(d));
      $('#modalEditarDetail').modal('show');
    },

    guardarDetail(){
      axios.post(`${this.apphost}/product_order_detail/editar`, this.detailForm)
      .then(()=>{
        $('#modalEditarDetail').modal('hide');
        this.abrirDetalle(this.detalle);
      });
    },

    abrirModalItem(){
      this.itemForm = { product_id:null, amount:1, price_item:0 };
      $('#modalCrearDetail').modal('show');
    },

    eliminarDetail(d){
      apprise(`¿Eliminar ítem ${d.product_name}?`, {confirm:true}, ok=>{
        if(!ok) return;
        axios.post(`${this.apphost}/product_order_detail/eliminar`,{
          product_order_detail_id: d.product_order_detail_id
        })
        .then(()=>this.abrirDetalle(this.detalle));
      });
    },

    confirmarAgregarItem(){
      const p = this.productos.find(
        x => x.product_id == this.itemForm.product_id
      );

      if(!p){
        alert('Seleccione un producto');
        return;
      }

      if(this.itemForm.amount <= 0){
        alert('Cantidad inválida');
        return;
      }

      this.nueva.items.push({
        product_id: p.product_id,
        product_name: p.name,
        amount: this.itemForm.amount,
        price_item: p.price
      });

      $('#modalAgregarItemNuevaOrden').modal('hide');
    },

    cargarMesas(){
      axios.get(`${this.apphost}/mesa/listar`)
        .then(r => this.mesas = r.data);
    },

    abrirReporteResumenVentas(){
      this.resumen = { fecha_inicio:'', fecha_fin:'' };
      $('#modalResumenVentas').modal('show');
    },

    resumenVentasPDF(){
      const { fecha_inicio, fecha_fin } = this.resumen;

      window.open(
        `${this.apphost}/reporte/resumen-ventas?ini=${fecha_inicio}&fin=${fecha_fin}`,
        '_blank'
      );
    },

    abrirModalNuevoCliente(){
      this.clienteForm = { dni:'', nombre:'' };
      $('#modalNuevoCliente').modal('show');
    },    

    confirmarLiberarMesa(mesa){
          apprise(
            `¿Deseas liberar la mesa <b>${mesa.nombre}</b>?<br>
             Se verificará si tiene pedidos pendientes.`,
            { confirm: true },
            ok => {
              if(!ok) return;
              this.liberarMesa(mesa);
            }
          );
    },

    liberarMesa(mesa){
      axios.post(`${this.apphost}/ventas/liberarMesaOcupada`, {
        mesa_id: mesa.mesa_id
      })
      .then(r => {
        if(r.data.status !== 'ok'){
          apprise(r.data.msg || 'No se pudo liberar la mesa');
          return;
        }
        apprise('Mesa liberada correctamente');
        this.cargarMesas(); // refresca estado
      })
      .catch(e => {
        apprise(e.response?.data?.msg || 'Error al liberar mesa');
      });
    },


    cargarProductos(){
        axios.get(`${this.apphost}/product/listar`)
        .then(r => this.productos = r.data);
    },
    onProductoChange(){
      const p = this.productos.find(
        x => x.product_id == this.detailForm.product_id
      );
      if(p){
        this.detailForm.price_item = p.price;
      }
    }
  },

  mounted(){
    // 🔐 restaurar JWT en Axios
    const jwt = localStorage.getItem('jwt');
    if(jwt){
      axios.defaults.headers.common['Authorization'] = `Bearer ${jwt}`;
    }
    
    this.cargarProductos();
    this.listar();
    this.cargarMesas();

    axios.get(`${this.apphost}/cliente/listar`)
      .then(r=>{
        this.clientes = r.data.map(c => ({
          ...c,
          label: `${c.dni} - ${c.nombre}`
        }));
    });

    axios.get(`${this.apphost}/tipo_pago/listar`)
    .then(r => this.tiposPago = r.data);  

    axios.get(`${this.apphost}/administrador/listar`)
    .then(r => {
      this.administradores = r.data;
    });

    // ✅ AQUÍ VA ESTO
    const self = this;
    $('#modalDetalleOrder').on('hidden', function () {
      self.listar();
    });

  },

  watch:{
    'itemForm.producto'(p){
      if(p){
        this.itemForm.product_id = p.product_id;
        this.itemForm.price_item = p.price;
      }
    },
    // cuando cambias producto en detalle
    'detailForm.product'(p){
      if(p){
        this.detailForm.price_item = p.price;
      }
    },

    // cuando seleccionas cliente
    'nueva.cliente'(c){
      if(c){
        this.nueva.cliente_id = c.cliente_id;
        this.nueva.telefono = c.telefono || '';
      }
    },

    'itemForm.product_id'(id){
      const p = this.productos.find(x => x.product_id == id);
      if(p){
        this.itemForm.price_item = p.price;
      }
    },

    'detailForm.producto'(p){
        if(p){
          this.detailForm.product_id = p.product_id;
          this.detailForm.price_item = p.price;
        }
    },    

    // cuando cambia el total calculado
    totalOrden(v){
      this.nueva.total_fees = v;
    }
  },

  computed:{

    ordenCerrada(){
      return !!this.detalle.fecha_fin;
    },

    totalDetalleOrden(){
      return this.detallesOrder.reduce(
        (s,d) => s + (d.amount * d.price_item),
        0
      ).toFixed(2);
    },

    productosSelect(){
      return this.productos.map(p => ({
        product_id: p.product_id,
        price: p.price,
        label: `${p.name} - S/ ${p.price}`
      }));
    },
    totalDetalle(){
      return (this.detailForm.amount || 0) *
             (this.detailForm.price_item || 0);
    },

    totalItem(){
      return this.detailForm.amount * this.detailForm.price_item;
    },

    totalOrden(){
      return this.nueva.items.reduce(
        (s,i)=> s + (i.amount * i.price_item),
        0
      ).toFixed(2);
    },   
    
    totalItemNuevaOrden(){
      return (this.itemForm.amount || 0) *
             (this.itemForm.price_item || 0);
    },

    opcionesMesa(){
      return [
        { mesa_id: 0, label: 'DIRECTO' },
        ...this.mesas
          .filter(m => m.estado === 'DISPONIBLE')
          .map(m => ({
            mesa_id: m.mesa_id,
            label: m.nombre
          }))
      ];
    }
  }

});
</script>
