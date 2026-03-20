<div class="row-fluid" id="appCaja">
<!-- este es mi frontend usando boostrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
<div class="span12">
  <div class="titulo-fijo clearfix">

    <div style="float:left;">
      <h2 style="margin:0;">Gestión de Caja</h2>
    </div>

    <div class="btn-group pull-right">
      <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-bandcamp"></i>
        <span class="caret"></span>
      </button>

      <ul class="dropdown-menu pull-right">
        <li>
          <a href="#" @click.prevent="abrirModalAbrir">
            <i class="fa fa-plus"></i> Abrir caja nueva
          </a>
        </li>
      </ul>
    </div>

  </div>

    <!-- ALERTA -->
    <div class="alert alert-info" v-if="cajasAbiertas.length==0">
      No hay cajas abiertas actualmente.
      <button class="btn btn-success pull-right" @click="abrirModalAbrir">
        Abrir Caja
      </button>
    </div>    

    <!-- LISTADO CAJAS ABIERTAS -->
    <table class="table table-bordered" v-if="cajasAbiertas.length">
      <thead>
        <tr>
          <th>ID Caja</th>
          <th>Administrador</th>
          <th>Fecha Apertura</th>
          <th>Efectivo Inicial</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="c in cajasAbiertas"
        :key="c.caja_id"
        :class="{ 'caja-cerrada': c.estado === 'CERRADA' }">

          <td>{{ c.caja_id }}</td>
          <td>{{ c.administrador }}</td>
          <td>{{ formatearFecha(c.fecha_apertura) }}</td>
          <td>{{ c.efectivo_inicial }}</td>
          
          <td>
            <template v-if="c.estado === 'ABIERTA'">
              <button class="btn btn-primary btn-mini"
                      @click="abrirModalMovimiento(c,'INGRESO')">
                Recibir
              </button>
              <button class="btn btn-warning btn-mini"
                      @click="abrirModalMovimiento(c,'EGRESO')">
                Entregar
              </button>
              <button class="btn btn-danger btn-mini"
                      @click="abrirModalCerrar(c)">
                Cerrar Caja
              </button>
              <button class="btn btn-info btn-mini"
                      @click="verMovimientos(c)">
                Movimientos
              </button>
            </template>

            <template v-else>
              <div style="text-align:center">
                <span class="label label-default">CERRADO</span><br>
                <small style="color:#777">
                  {{ formatearFecha(c.fecha_cierre) }}
                </small>
              </div>
            </template>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- MODAL ABRIR CAJA -->
  <div id="modalAbrirCaja" class="modal hide fade">
    <div class="modal-header">
      <h3>Abrir Caja</h3>
    </div>
    <div class="modal-body">

      <label>Administrador que RECIBE</label>
      <select v-model="formAbrir.administrador_recibe">
        <option v-for="a in administradores" :value="a.administrador_id">
          {{ a.nombres_apellidos }}
        </option>
      </select>

      <label>Administrador que ENTREGA</label>
      <select v-model="formAbrir.administrador_entrega">
        <option v-for="a in administradores" :value="a.administrador_id">
          {{ a.nombres_apellidos }}
        </option>
      </select>

      <label>Monto Inicial</label>
      <input type="number" v-model.number="formAbrir.monto">

    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" @click="abrirCaja">Abrir</button>
      <button class="btn" data-dismiss="modal">Cancelar</button>
    </div>
  </div>

  <!-- MODAL MOVIMIENTO -->
  <div id="modalMovimiento" class="modal hide fade">
    <div class="modal-header">
      <h3>{{ tipoMovimiento=='INGRESO'?'Recibir Efectivo':'Entregar Efectivo' }}</h3>
    </div>
    <div class="modal-body">

      <label>Monto</label>
      <input type="number" v-model.number="formMovimiento.monto">

      <label>Medio de Pago</label>
      <select v-model="formMovimiento.medio_pago">
        <option>EFECTIVO</option>
        <option>YAPE</option>
        <option>PLIN</option>
        <option>TARJETA</option>
      </select>

      <label>Administrador Relacionado</label>
      <select v-model="formMovimiento.administrador_ref">
        <option v-for="a in administradores" :value="a.administrador_id">
          {{ a.nombres_apellidos }}
        </option>
      </select>

    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" @click="guardarMovimiento">Guardar</button>
      <button class="btn" data-dismiss="modal">Cancelar</button>
    </div>
  </div>

  <!-- MODAL CERRAR CAJA -->
  <div id="modalCerrarCaja" class="modal hide fade">
    <div class="modal-header">
      <h3>Cerrar Caja</h3>
    </div>
    <div class="modal-body">
      <label>Monto Final Entregado</label>
      <input type="number" v-model.number="formCerrar.monto">
      <label>Administrador que RECIBE el cierre</label>
      <select v-model="formCerrar.administrador_recibe">
        <option v-for="a in administradores" :value="a.administrador_id">
          {{ a.nombres_apellidos }}
        </option>
      </select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-danger" @click="cerrarCaja">Cerrar</button>
      <button class="btn" data-dismiss="modal">Cancelar</button>
    </div>
  </div>


  <div id="modalMovimientos" class="modal hide fade">
  <div class="modal-header">
    <h3>Movimientos de Caja</h3>
  </div>
  <div class="modal-body">
    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Origen</th>
          <th>Monto</th>
          <th>Administrador</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="m in movimientos">
          <td>{{ m.fecha }}</td>
          <td>{{ m.tipo }}</td>
          <td>{{ m.origen }}</td>
          <td>S/ {{ m.monto }}</td>
          <td>{{ m.administrador_origen || '—' }}</td>
        </tr>
      </tbody>
    </table>

    <hr>

    <div class="row-fluid">
      <div class="span4">
        <strong>Total Ingresos:</strong><br>
        <span style="color:green;font-size:16px">
          S/ {{ totalIngresos }}
        </span>
      </div>

      <div class="span4">
        <strong>Total Egresos:</strong><br>
        <span style="color:red;font-size:16px">
          S/ {{ totalEgresos }}
        </span>
      </div>

      <div class="span4">
        <strong>Diferencia:</strong><br>
        <span style="font-size:18px;font-weight:bold"
              :style="{color: saldoCaja >= 0 ? 'green' : 'red'}">
          S/ {{ saldoCaja }}
        </span>
      </div>
    </div>

  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal">Cerrar</button>
  </div>
</div>


</div>

<script>
new Vue({
  el:'#appCaja',
  data:{
    apphost: apphost || '',
    administradores:[],
    cajasAbiertas:[],
    movimientos:[],
    tipoMovimiento:'',

    formAbrir:{ administrador_recibe:'', administrador_entrega:'', monto:0 },
    formMovimiento:{ caja_id:'', monto:0, medio_pago:'EFECTIVO', administrador_ref:'' },
    formCerrar:{ caja_id:'', monto:0, administrador_recibe:'' }
  },

  methods:{
    cargar(){
      axios.get(`${this.apphost}/caja/estado`).then(r=>this.cajasAbiertas=r.data);
      axios.get(`${this.apphost}/administrador/listar`).then(r=>this.administradores=r.data);
    },

    verMovimientos(c){
      axios.get(`${this.apphost}/caja/movimientos/${c.caja_id}`)
        .then(r=>{
          this.movimientos = r.data;
          $('#modalMovimientos').modal('show');
        });
    },

    abrirModalAbrir(){
      this.formAbrir={ administrador_recibe:'', administrador_entrega:'', monto:0 };
      $('#modalAbrirCaja').modal('show');
    },

    abrirCaja(){

      if(!this.formAbrir.administrador_recibe){
        apprise('Debe seleccionar el administrador que RECIBE');
        return;
      }

      if(!this.formAbrir.administrador_entrega){
        apprise('Debe seleccionar el administrador que ENTREGA');
        return;
      }

      // monto puede ser 0 → NO se valida

      axios.post(`${this.apphost}/caja/abrir`, {
        administrador_id: this.formAbrir.administrador_recibe,
        administrador_origen_id: this.formAbrir.administrador_entrega,
        efectivo_inicial: this.formAbrir.monto
      })
      .then(()=>{
        $('#modalAbrirCaja').modal('hide');
        apprise('Caja abierta correctamente');
        this.cargar();
      })
      .catch(e=>{
        if(e.response && e.response.data && e.response.data.error){
          apprise(e.response.data.error);
        }else{
          apprise('Error al abrir la caja');
        }
      });

    },

    abrirModalMovimiento(c,tipo){
      this.tipoMovimiento = tipo;
      this.formMovimiento = {
        caja_id: c.caja_id,
        monto: 0,
        medio_pago: 'EFECTIVO',
        administrador_ref: ''
      };
      $('#modalMovimiento').modal('show');
    },

    guardarMovimiento(){

      if(!this.validarMonto(this.formMovimiento.monto)) return;

      axios.post(`${this.apphost}/caja/movimiento`, {
        caja_id: this.formMovimiento.caja_id,
        tipo: this.tipoMovimiento, // INGRESO / EGRESO
        monto: this.formMovimiento.monto,
        medio_pago: this.formMovimiento.medio_pago,
        administrador_ref: this.formMovimiento.administrador_ref
      }).then(()=>{
        $('#modalMovimiento').modal('hide');
        this.cargar();
        this.verMovimientos({ caja_id: this.formMovimiento.caja_id });
      });

    },

    formatearFecha(fecha){
      if(!fecha) return '';
      const f = new Date(fecha.replace(' ', 'T'));
      const dd = String(f.getDate()).padStart(2,'0');
      const mm = String(f.getMonth()+1).padStart(2,'0');
      const yyyy = f.getFullYear();
      const hh = String(f.getHours()).padStart(2,'0');
      const ii = String(f.getMinutes()).padStart(2,'0');
      return `${dd}/${mm}/${yyyy} ${hh}:${ii}`;
    },

    abrirModalCerrar(c){
      this.formCerrar={ caja_id:c.caja_id, monto:0, administrador_recibe:'' };
      $('#modalCerrarCaja').modal('show');
    },

    validarMonto(monto){
      monto = parseFloat(monto);
      if (isNaN(monto) || monto <= 0) {
        alert('El monto debe ser mayor a 0.00');
        return false;
      }
      return true;
    },
    cerrarCaja(){

      // ✅ VALIDACIÓN OBLIGATORIA
      if(!this.formCerrar.administrador_recibe){
        apprise('Debe seleccionar el administrador que RECIBE el cierre de caja');
        return;
      }

      // (opcional) el monto puede ser 0, así que NO se valida aquí

      axios.post(`${this.apphost}/caja/cerrar`, this.formCerrar)
        .then(() => {
          $('#modalCerrarCaja').modal('hide');
          apprise('Caja cerrada correctamente');
          this.cargar();
        })
        .catch(e => {
          if(e.response && e.response.data && e.response.data.error){
            apprise(e.response.data.error);
          }else{
            apprise('Error al cerrar la caja');
          }
        });
    }
  },

  mounted(){
    this.cargar();
  },

  computed:{
    totalIngresos(){
      return this.movimientos
        .filter(m => m.tipo === 'INGRESO')
        .reduce((s,m)=> s + parseFloat(m.monto), 0)
        .toFixed(2);
    },

    totalEgresos(){
      return this.movimientos
        .filter(m => m.tipo === 'EGRESO')
        .reduce((s,m)=> s + parseFloat(m.monto), 0)
        .toFixed(2);
    },

    saldoCaja(){
      return (this.totalIngresos - this.totalEgresos).toFixed(2);
    }
  }

});
</script>
<style>
.caja-cerrada {
  background-color: #f2f2f2;
  color: #777;
}  
</style>