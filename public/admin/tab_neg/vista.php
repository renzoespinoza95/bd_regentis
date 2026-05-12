<!-- =========================================
     NEGOCIOS + MERCADOS (Bootstrap 2.3.2 / jQuery2 / Vue2 / Axios / DataTables)
========================================= -->

<div class="row-fluid" id="appNeg">
  <div class="span12">

    <div class="titulo-fijo clearfix">

      <div style="float:left;">
        <h2 style="margin:0;">Negocios</h2>
      </div>

      <div class="btn-group pull-right">
        <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-bandcamp"></i>
          <span class="caret"></span>
        </button>

        <ul class="dropdown-menu pull-right">
          <li>
            <a href="#" @click.prevent="abrirModalCrearNeg">
              <i class="fa fa-arrow-circle-right"></i> Agregar Neg
            </a>
          </li>

          <li>
            <a href="#" @click.prevent="abrirModalMercados">
              <i class="fa fa-arrow-circle-right"></i> Mercados
            </a>
          </li>
        </ul>
      </div>
    </div>
    <div class="span12 tabla_esp_sup">
      <!-- TABLA NEGOCIOS -->
      <table id="tablaNeg" class="table table-bordered table-condensed">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Puesto</th>
            <th>Mercado</th>
            <th>Rubros</th>
            <th>Activo</th>            
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>  
    <!-- =========================
         MODAL CREAR NEG
    ========================== -->
    <div id="modalCrearNeg" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Nuevo Negocio</h3>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="nuevoNeg.nombre" class="input-xxlarge" placeholder="Nombre del negocio">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Puesto</label>
          <div class="controls">
            <input v-model="nuevoNeg.puesto" class="input-large" placeholder="Ej: A-12">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Mercado</label>
          <div class="controls">
            <!-- vue-select -->
            <v-select
              :options="mercadosOptions"              
              label="nombre"
              placeholder="Selecciona un mercado..."
              v-model="nuevoNeg.mercado_id"
              style="width: 420px;"
            ></v-select>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Rubros</label>

          <div class="controls">

            <v-select
              multiple
              :options="rubrosOptions"
              label="nombre"
              v-model="nuevoNeg.rubros"
              placeholder="Selecciona rubros..."
              style="width:420px;"
            ></v-select>

          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearNeg">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =========================
         MODAL EDITAR NEG
    ========================== -->
    <div id="modalEditarNeg" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Editar Negocio</h3>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="formNeg.nombre" class="input-xxlarge" placeholder="Nombre del negocio">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Puesto</label>
          <div class="controls">
            <input v-model="formNeg.puesto" class="input-large" placeholder="Ej: A-12">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Mercado</label>
          <div class="controls">
            <v-select
              :options="mercadosOptions"              
              label="nombre"
              placeholder="Selecciona un mercado..."
              v-model="formNeg.mercado_id"
              style="width: 420px;"
            ></v-select>
          </div>
        </div>


        <div class="control-group">
          <label class="control-label">Rubros</label>

          <div class="controls">

            <v-select
              multiple
              :options="rubrosOptions"
              label="nombre"
              v-model="formNeg.rubros"
              placeholder="Selecciona rubros..."
              style="width:420px;"
            ></v-select>

          </div>
        </div>        

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarNeg">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =========================
         MODAL PROPIETARIO (NEGXUSU)
    ========================== -->
    <div id="modalPropietario" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Propietario del Negocio</h3>
        <div style="margin-top:6px; color:#777;">
          Negocio: <b>{{ negProp.nombre }}</b> <span v-if="negProp.neg_id">(#{{ negProp.neg_id }})</span>
        </div>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">DNI</label>
          <div class="controls" style="display:flex; gap:8px; align-items:center;">
            <input v-model="propDni" class="input-medium" placeholder="DNI">
            <button class="btn btn-primary" @click="buscarUsuPorDni">
              <i class="icon-search icon-white"></i> Buscar
            </button>
          </div>
        </div>

        <!-- Panel de usuario encontrado o asignado -->
        <div v-if="panelUsu.visible" class="well" style="margin-top:12px;">

            <h4 style="margin-top:0;">Usuario</h4>

            <table class="table table-condensed">

              <tr>
                <td style="width:160px;"><b>DNI</b></td>
                <td>{{ panelUsu.dni }}</td>
              </tr>

              <tr>
                <td><b>Nombres</b></td>
                <td>{{ panelUsu.nombres_apellidos }}</td>
              </tr>

              <tr>
                <td><b>Activo</b></td>
                <td>
                  <span class="label" :class="panelUsu.is_activo ? 'label-success' : 'label-important'">
                    {{ panelUsu.is_activo ? 'SI' : 'NO' }}
                  </span>
                </td>
              </tr>

              <tr v-if="panelUsu.negxusu_id">
                <td><b>Asignado</b></td>
                <td>
                  <span class="label label-info">SI</span>
                </td>
              </tr>

            </table>

            <!-- BOTON ASIGNAR -->
            <div v-if="!panelUsu.negxusu_id" style="margin-top:10px;">

              <button class="btn btn-success" @click="asignarPropietario">

                <i class="icon-user icon-white"></i>
                Asignar propietario

              </button>

            </div>

            <!-- BOTON ELIMINAR -->
            <div v-if="panelUsu.negxusu_id" style="margin-top:10px;">

              <button class="btn btn-danger" @click="eliminarNegxusu">

                <i class="icon-trash icon-white"></i>
                Eliminar asignación

              </button>

            </div>

          </div>

        <div v-else class="alert" style="margin-top:12px;">
          Escribe un DNI y presiona <b>Buscar</b>.
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- =========================
         MODAL MERCADOS (LISTA)
    ========================== -->
    <div id="modalMercados" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Mercados</h3>
      </div>

      <div class="modal-body">
        <table id="tablaMercados" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Dirección</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button class="btn btn-success" @click="abrirModalCrearMercadoDesdeLista">
          <i class="icon-plus icon-white"></i> Agregar
        </button>
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- =========================
         MODAL CREAR MERCADO
    ========================== -->
    <div id="modalCrearMercado" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Nuevo Mercado</h3>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="nuevoMercado.nombre" class="input-xxlarge" placeholder="Nombre del mercado">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Dirección</label>
          <div class="controls">
            <input v-model="nuevoMercado.direccion" class="input-xxlarge" placeholder="Dirección">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Activo</label>
          <div class="controls">
            <select v-model="nuevoMercado.is_activo" class="input-small">
              <option :value="1">SI</option>
              <option :value="0">NO</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearMercado">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    

    <div id="modalRubro" class="modal hide fade fullscreen">
        <div class="modal-header">
          <button class="close" data-dismiss="modal">×</button>
          <h3>Rubros del negocio</h3>
          <div>Negocio: <b>{{ negRubro.nombre }}</b></div>
        </div>

        <div class="modal-body">
          <table id="tablaRubros" class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <div class="modal-footer">
          <button class="btn btn-success" @click="abrirModalAgregarRubro">
            + Rubro
          </button>
          <button class="btn" data-dismiss="modal">Cerrar</button>
        </div>
      </div>


      <div id="modalAgregarRubro" class="modal hide fade fullscreen">
        <div class="modal-header">
          <button class="close" data-dismiss="modal">×</button>
          <h3>Agregar Rubro</h3>
        </div>

        <div class="modal-body">
          <v-select
            :options="rubrosOptions"
            :reduce="r => r.rubro_id"
            label="nombre"
            v-model="nuevoRubro.rubro_id"
            placeholder="Selecciona rubro..."
            style="width:400px;"
          ></v-select>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" @click="guardarRubro">Guardar</button>
          <button class="btn" data-dismiss="modal">Cancelar</button>
        </div>
      </div>

    <!-- =========================
         MODAL EDITAR MERCADO
    ========================== -->
    <!-- =========================
     MODAL EDITAR MERCADO
========================== -->
<div id="modalEditarMercado" class="modal hide fade fullscreen" tabindex="-1">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Editar Mercado</h3>
  </div>

  <div class="modal-body">

    <!-- NOMBRE -->
    <div class="control-group">
      <label class="control-label">Nombre</label>
      <div class="controls">
        <input 
          v-model="formMercado.nombre" 
          class="input-xxlarge" 
          placeholder="Nombre del mercado"
        >
      </div>
    </div>

    <!-- DIRECCION -->
    <div class="control-group">
      <label class="control-label">Dirección</label>
      <div class="controls">
        <input 
          v-model="formMercado.direccion" 
          class="input-xxlarge" 
          placeholder="Dirección"
        >
      </div>
    </div>

    <!-- ACTIVO -->
    <div class="control-group">
      <label class="control-label">Activo</label>
      <div class="controls">
        <select v-model="formMercado.is_activo" class="input-small">
          <option :value="1">SI</option>
          <option :value="0">NO</option>
        </select>
      </div>
    </div>

    <!-- LOGO -->
    <div class="control-group">
      <label class="control-label">Logo</label>

      <div class="controls">

        <input 
          v-model="formMercado.logo"
          class="input-xxlarge"
          placeholder="https://..."
        >

        <!-- preview -->
        <div style="margin-top:10px;">

          <img 
            v-if="formMercado.logo"
            :src="formMercado.logo"
            style="
              width:120px;
              height:120px;
              object-fit:cover;
              border-radius:16px;
              border:1px solid #ddd;
              background:#fff;
            "
          >

        </div>

      </div>
    </div>

    <!-- COLOR -->
    <div class="control-group">
      <label class="control-label">Top Navbar Color</label>

      <div class="controls" style="display:flex; gap:10px; align-items:center;">

        <input 
          v-model="formMercado.topnavbar_color"
          class="input-medium"
          placeholder="#FD7635"
        >

        <!-- preview -->
        <div
          :style="{
            width:'45px',
            height:'45px',
            borderRadius:'10px',
            border:'1px solid #ccc',
            background: formMercado.topnavbar_color || '#fff'
          }"
        ></div>

      </div>
    </div>

    <!-- PATRON FONDO -->
    <div class="control-group">
      <label class="control-label">Patrón Fondo (CSS)</label>

      <div class="controls">

        <textarea
          v-model="formMercado.patron_fondo"
          class="input-xxlarge"
          rows="10"
          placeholder="background-color: #fff;"
        ></textarea>

        <!-- preview -->
        <div style="margin-top:12px;">

          <div
            :style="cssToObject(formMercado.patron_fondo)"
            style="
              width:100%;
              height:180px;
              border-radius:16px;
              border:1px solid #ddd;
            "
          ></div>

        </div>

      </div>
    </div>

  </div>

  <div class="modal-footer">
    <button class="btn btn-primary" @click="guardarMercado">
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
/* =========================================================
   Registrar componente vue-select (vue-select-2-6-4.min.js)
   (asumiendo que expone window.VueSelect.VueSelect)
========================================================= */
Vue.component('v-select', VueSelect.VueSelect);

const appNeg = new Vue({
  el: '#appNeg',
  data: {
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),

    // data negocios
    negs: [],
    dtNeg: null,
    formCategoria: {
      nombre: '',
      icono: '',
      is_activo: 1
    },
    dtCat:null,
    /* =========================================
       NUEVO FORMULARIO
    ========================================= */

    nuevoNeg: {

      nombre: '',

      puesto: '',

      mercado_id: null,

      rubros: [] // 🔥 NUEVO

    },

    formNeg: {

      neg_id: 0,

      nombre: '',

      puesto: '',

      mercado_id: null,

      rubros: [] // 🔥 NUEVO

    },

    categorias: [],
    categoriasOptions: [],

    // data mercados
    mercados: [],
    mercadosOptions: [],
    dtMerc: null,
    nuevoMercado: { nombre: '', direccion: '', is_activo: 1 },
    formMercado: {
      mercado_id: 0,
      nombre: '',
      direccion: '',
      is_activo: 1,
      logo: '',
      topnavbar_color: '',
      patron_fondo: ''
    },

    // propietario
    negProp: { neg_id: 0, nombre: '' },
    propDni: '',
    panelUsu: {
      visible: false,
      usu_id: 0,
      dni: '',
      nombres_apellidos: '',
      is_activo: 0,
      negxusu_id: 0
    },
    // rubros
    negRubro: { neg_id: 0, nombre: '' },
    rubrosNeg: [],
    dtRubro: null,
    nuevoRubro: { rubro_id: null },
    rubrosOptions: []
  },

  methods: {
    /* =========================
       Helpers UI
    ========================== */
    bloquear(msg) {
      $.blockUI({
        message: `<h4>${msg}</h4>`,
        css: { border:'none', padding:'15px', background:'#000', opacity:.6, color:'#fff' }
      });
    },

    /* =========================
       MERCADOS (cargar para combos y lista)
    ========================== */
    cargarMercadosParaCombo() {

      return axios.get(`${this.apphost}/mercado/listar`)
        .then(r => {

          this.mercados = r.data.data || [];

          this.mercadosOptions = this.mercados.map(m => ({
            mercado_id: parseInt(m.mercado_id),
            nombre: m.nombre,
            direccion: m.direccion,
            is_activo: parseInt(m.is_activo)
          }));

        });

    },

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

            this.dtRubro.row.add([
              r.rubro_id,
              r.nombre,
              btn
            ]);

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
        rubro_id: typeof this.nuevoRubro.rubro_id === 'object'
        ? parseInt(this.nuevoRubro.rubro_id.rubro_id)
        : parseInt(this.nuevoRubro.rubro_id)
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

        axios.post(`${this.apphost}/rubroxneg/eliminar`,{
          rubroxneg_id:id
        })
        .then(()=>{
          apprise('Eliminado');
        })
        .finally(()=>{
          $.unblockUI();
          this.listarRubrosNeg();
        });

      });

    },

    /* =========================
       NEGOCIOS - LISTAR (DataTable principal)
    ========================== */
    
  listarNeg() {

    this.bloquear('Cargando negocios…');

    this.cargarMercadosParaCombo()

      .then(() => axios.get(`${this.apphost}/neg/listar`))

      .then(r => {

        this.negs = r.data.data || [];

        this.$nextTick(() => {

          if (!this.dtNeg) {

            this.dtNeg = $('#tablaNeg').DataTable({

              language: (
                typeof dt_language !== 'undefined'
                  ? dt_language
                  : undefined
              ),

              scrollX: true,

              dom: 'frtip',

              order: [[0,'desc']]

            });

            const self = this;

            $('#tablaNeg tbody')

              .on('click', 'a.editar-neg', function(e){

                e.preventDefault();

                const id = $(this).data('id');

                const row = self.negs.find(
                  x => parseInt(x.neg_id,10) === parseInt(id,10)
                );

                if (row) {
                  self.abrirModalEditarNeg(row);
                }

              })

              .on('click', 'a.eliminar-neg', function(e){

                e.preventDefault();

                const id = $(this).data('id');

                const row = self.negs.find(
                  x => parseInt(x.neg_id,10) === parseInt(id,10)
                );

                if (row) {
                  self.eliminarNeg(row);
                }

              })

              .on('click', 'a.propietario-neg', function(e){

                e.preventDefault();

                const id = $(this).data('id');

                const row = self.negs.find(
                  x => parseInt(x.neg_id,10) === parseInt(id,10)
                );

                if (row) {
                  self.abrirModalPropietario(row);
                }

              });

          }

          this.dtNeg.clear();

          this.negs.forEach(n => {

            const mercadoNombre =
              this.obtenerNombreMercado(n.mercado_id);

            const activo =
              parseInt(n.is_activo,10)
                ? 'SI'
                : 'NO';

            /* =========================================
               RUBROS BONITOS 😏
            ========================================= */

            let rubrosHtml = `
              <span class="label">
                Sin rubros
              </span>
            `;

            if(
              Array.isArray(n.rubros_obj)
              &&
              n.rubros_obj.length > 0
            ){

              rubrosHtml = n.rubros_obj.map(r => `

                <span
                  class="label label-info"
                  style="
                    margin-right:4px;
                    margin-bottom:4px;
                    display:inline-block;
                    padding:4px 8px;
                    border-radius:12px;
                    font-size:11px;
                  "
                >

                  <span style="font-size:14px;">
                    ${r.icono || '📦'}
                  </span>

                  ${r.nombre}

                </span>

              `).join('');

            }

            const actions = `

              <div class="btn-group">

                <button
                  class="btn btn-mini dropdown-toggle"
                  data-toggle="dropdown"
                >
                  ⚙ <span class="caret"></span>
                </button>

                <ul class="dropdown-menu">

                  <li>
                    <a href="#"
                      class="editar-neg"
                      data-id="${n.neg_id}">
                      Editar
                    </a>
                  </li>

                  <li>
                    <a href="#"
                      class="eliminar-neg"
                      data-id="${n.neg_id}">
                      Eliminar
                    </a>
                  </li>

                  <li class="divider"></li>

                  <li>
                    <a href="#"
                      class="propietario-neg"
                      data-id="${n.neg_id}">
                      Propietario
                    </a>
                  </li>

                </ul>

              </div>

            `;

            this.dtNeg.row.add([

              n.neg_id,

              n.nombre || '',

              n.puesto || '',

              mercadoNombre || '',

              rubrosHtml,

              activo,

              actions

            ]);

          });

          this.dtNeg.draw(false);

        });

      })

      .finally(() => $.unblockUI());

  },

    obtenerNombreMercado(mercado_id) {
      const mid = parseInt(mercado_id, 10);
      const m = this.mercadosOptions.find(x => parseInt(x.mercado_id,10) === mid);
      return m ? m.nombre : '';
    },

    /* =========================================
       CARGAR RUBROS
    ========================================= */

    cargarRubros(){

      return axios.get(`${this.apphost}/rubro/listar`)
        .then(r=>{

          this.rubrosOptions = r.data.data || [];

        });

    },    

    /* =========================
       NEGOCIOS - MODALES / CRUD
    ========================== */
    abrirModalCrearNeg() {
      this.nuevoNeg = { nombre: '', puesto: '', mercado_id: null };
      $('#modalCrearNeg').modal('show');
    },

    /* =========================================
       CREAR NEG
    ========================================= */

    crearNeg(){

      if (!this.nuevoNeg.nombre.trim()) {
        return apprise('Escribe el nombre')
      }

      if (!this.nuevoNeg.puesto.trim()) {
        return apprise('Escribe el puesto')
      }

      if (!this.nuevoNeg.mercado_id) {
        return apprise('Selecciona un mercado')
      }

      const mercado_id = typeof this.nuevoNeg.mercado_id === 'object'
        ? this.nuevoNeg.mercado_id.mercado_id
        : this.nuevoNeg.mercado_id

      const payload = {

        nombre: this.nuevoNeg.nombre.trim(),

        puesto: this.nuevoNeg.puesto.trim(),

        mercado_id: mercado_id,

        rubros: (this.nuevoNeg.rubros || [])
          .map(r => r.rubro_id)

      }

      this.bloquear('Creando negocio...')

      axios.post(`${this.apphost}/neg/crear`, payload)

        .then(()=>{

          $('#modalCrearNeg').modal('hide')

          apprise('¡Creado!')

          this.nuevoNeg = {

            nombre: '',

            puesto: '',

            mercado_id: null,

            rubros: []

          }

        })

        .finally(()=>{

          $.unblockUI()

          this.listarNeg()

        })

    },

    /* =========================================
       EDITAR NEG
    ========================================= */

    abrirModalEditarNeg(n) {

      this.bloquear('Cargando categorías...')

      Promise.all([

        this.cargarRubros()

      ])
      .then(()=>{

        this.formNeg = {

          neg_id: n.neg_id,

          nombre: n.nombre,

          puesto: n.puesto,

          mercado_id: this.mercadosOptions.find(
            m => m.mercado_id == n.mercado_id
          ),

          rubros: n.rubros_obj || [] // 🔥

        };

        $('#modalEditarNeg').modal('show')

      })
      .finally(()=>$.unblockUI())
    },

    cssToObject(css) {

      if (!css) return {}

      const obj = {}

      css.split(';').forEach(rule => {

        const parts = rule.split(':')

        if (parts.length < 2) return

        const key = parts[0].trim()
          .replace(/-([a-z])/g, g => g[1].toUpperCase())

        const value = parts.slice(1).join(':').trim()

        if (key && value) {
          obj[key] = value
        }

      })

      return obj
    },    

    /* =========================================
       GUARDAR NEG
    ========================================= */

    guardarNeg() {

      if (!this.formNeg.nombre.trim()) {
        return apprise('Escribe el nombre')
      }

      if (!this.formNeg.puesto.trim()) {
        return apprise('Escribe el puesto')
      }

      if (!this.formNeg.mercado_id) {
        return apprise('Selecciona un mercado')
      }

      const mercado_id = typeof this.formNeg.mercado_id === 'object'
        ? this.formNeg.mercado_id.mercado_id
        : this.formNeg.mercado_id

      const payload = {

        neg_id: this.formNeg.neg_id,

        nombre: this.formNeg.nombre.trim(),

        puesto: this.formNeg.puesto.trim(),

        mercado_id: mercado_id,
        rubros: (this.formNeg.rubros || [])
          .map(r => r.rubro_id)

      }

      this.bloquear('Actualizando negocio…')

      axios.post(`${this.apphost}/neg/editar`, payload)

        .then(()=>{

          $('#modalEditarNeg').modal('hide')

          apprise('¡Actualizado!')

        })

        .finally(()=>{

          $.unblockUI()

          this.listarNeg()

        })

    },

    eliminarNeg(n) {
      apprise(`¿Eliminar negocio <b>#${n.neg_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;
        this.bloquear('Eliminando…');
        axios.post(`${this.apphost}/neg/eliminar`, { neg_id: n.neg_id })
          .then(() => apprise('Eliminado'))
          .finally(() => {
            $.unblockUI();
            this.listarNeg();
          });
      });
    },

    /* =========================
       PROPIETARIO (NEGXUSU)
    ========================== */
    abrirModalPropietario(n) {
      this.negProp = { neg_id: parseInt(n.neg_id,10), nombre: (n.nombre || '') };
      this.propDni = '';
      this.panelUsu = { visible:false, usu_id:0, dni:'', nombres_apellidos:'', is_activo:0, negxusu_id:0 };

      // al abrir, si ya hay propietario asignado lo mostramos (sin DNI)
      this.bloquear('Cargando propietario…');
      axios.get(`${this.apphost}/negxusu/obtener`, { params: { neg_id: this.negProp.neg_id } })
        .then(r => {
          // esperamos: {status:'ok', data: {negxusu_id, usu_id, dni, nombres_apellidos, is_activo} } o data null
          const payload = r.data || {};
          const data = payload.data || null;

          if (data && data.usu_id) {
            this.panelUsu = {
              visible: true,
              usu_id: parseInt(data.usu_id,10),
              dni: (data.dni || ''),
              nombres_apellidos: (data.nombres_apellidos || ''),
              is_activo: (parseInt(data.is_activo,10) ? 1 : 0),
              negxusu_id: parseInt(data.negxusu_id,10)
            };
          } else {
            this.panelUsu.visible = false;
          }
        })
        .finally(() => {
          $.unblockUI();
          $('#modalPropietario').modal('show');
        });
    },

    buscarUsuPorDni() {
      const dni = (this.propDni || '').trim();
      if (!dni) return apprise('Escribe un DNI');

      this.bloquear('Buscando usuario…');
      axios.post(`${this.apphost}/usu/buscar-dni`, { dni: dni, neg_id: this.negProp.neg_id })
        .then(r => {
          // esperamos: {status:'ok', data:{usu_id,dni,nombres_apellidos,is_activo,negxusu_id?}}
          const payload = r.data || {};
          const u = payload.data || null;

          if (!u || !u.usu_id) {
            this.panelUsu = { visible:false, usu_id:0, dni:'', nombres_apellidos:'', is_activo:0, negxusu_id:0 };
            apprise('No se encontró usuario con ese DNI');
            return;
          }

          this.panelUsu = {
            visible: true,
            usu_id: parseInt(u.usu_id,10),
            dni: (u.dni || dni),
            nombres_apellidos: (u.nombres_apellidos || ''),
            is_activo: (parseInt(u.is_activo,10) ? 1 : 0),
            negxusu_id: (u.negxusu_id ? parseInt(u.negxusu_id,10) : 0)
          };

          // NOTA: aquí solo mostramos; si deseas asignar cuando no existe, me lo dices y lo agrego.
        })
        .finally(() => $.unblockUI());
    },

    asignarPropietario(){

        const neg_id = this.negProp.neg_id
        const usu_id = this.panelUsu.usu_id

        if(!neg_id || !usu_id){
          apprise('Datos inválidos')
          return
        }

        apprise(
          `¿Asignar este usuario como propietario del negocio <b>#${neg_id}</b>?`,
          {confirm:true},
          ok=>{

            if(!ok) return

            $.blockUI({
              message:'<h4>Asignando propietario...</h4>',
              css:{border:'none',padding:'15px',background:'#000',opacity:.6,color:'#fff'}
            })

            axios.post(`${this.apphost}/negxusu/asignar`,{

              neg_id:neg_id,
              usu_id:usu_id

            })
            .then(r=>{

              if(r.data.status=='ok'){

                apprise('Propietario asignado correctamente')

                this.panelUsu.negxusu_id = r.data.negxusu_id

                this.listarNeg()

              }
              else{

                apprise(r.data.msg)

              }

            })
            .catch(e=>{

              apprise('Error al asignar propietario')

            })
            .finally(()=>{

              $.unblockUI()

            })

          }
        )

      },

    eliminarNegxusu() {
      if (!this.panelUsu.negxusu_id) return apprise('No hay asignación para eliminar');

      apprise(`¿Eliminar asignación del negocio <b>#${this.negProp.neg_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;

        this.bloquear('Eliminando asignación…');
        axios.post(`${this.apphost}/negxusu/eliminar`, { negxusu_id: this.panelUsu.negxusu_id })
          .then(() => {
            apprise('Asignación eliminada');
            // refrescar panel y tabla
            this.panelUsu = { visible:false, usu_id:0, dni:'', nombres_apellidos:'', is_activo:0, negxusu_id:0 };
            this.listarNeg();
          })
          .finally(() => $.unblockUI());
      });
    },

    /* =========================
       MERCADOS - LISTA (DataTable)
    ========================== */
    abrirModalMercados() {
      // cargar mercados y abrir
      this.listarMercados()
        .then(() => $('#modalMercados').modal('show'));
    },

    listarMercados() {
      this.bloquear('Cargando mercados…');

      return axios.get(`${this.apphost}/mercado/listar`)
        .then(r => {

          this.mercados = r.data.data || [];

          this.mercadosOptions = this.mercados.map(m => ({
            mercado_id: parseInt(m.mercado_id),
            nombre: m.nombre,
            direccion: m.direccion,
            is_activo: parseInt(m.is_activo)
          }));

          this.$nextTick(() => {
            if (!this.dtMerc) {
              this.dtMerc = $('#tablaMercados').DataTable({
                language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });

              const self = this;
              $('#tablaMercados tbody')
                .on('click', 'a.editar-merc', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = self.mercados.find(x => parseInt(x.mercado_id,10) === parseInt(id,10));
                  if (row) self.abrirModalEditarMercadoDesdeLista(row);
                })
                .on('click', 'a.eliminar-merc', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = self.mercados.find(x => parseInt(x.mercado_id,10) === parseInt(id,10));
                  if (row) self.eliminarMercado(row);
                });
            }

            this.dtMerc.clear();

            this.mercados.forEach(m => {
              const activo = (parseInt(m.is_activo,10) ? 'SI' : 'NO');

              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="editar-merc" data-id="${m.mercado_id}">Editar</a></li>
                    <li><a href="#" class="eliminar-merc" data-id="${m.mercado_id}">Eliminar</a></li>
                  </ul>
                </div>
              `;

              this.dtMerc.row.add([
                m.mercado_id,
                (m.nombre || ''),
                (m.direccion || ''),
                activo,
                actions
              ]);
            });

            this.dtMerc.draw(false);
          });
        })
        .finally(() => $.unblockUI());
    },

    /* =========================
       MERCADOS - abrir crear/editar desde lista (cerrar y volver)
    ========================== */
    abrirModalCrearMercadoDesdeLista() {
      this.nuevoMercado = { nombre: '', direccion: '', is_activo: 1 };
      $('#modalMercados').modal('hide');
      $('#modalCrearMercado').modal('show');
    },

    crearMercado() {
      if (!this.nuevoMercado.nombre || !this.nuevoMercado.nombre.trim()) return apprise('Escribe el nombre');
      // direccion puede ser null, pero si quieres obligatoria me dices

      this.bloquear('Guardando mercado…');
      axios.post(`${this.apphost}/mercado/crear`, this.nuevoMercado)
        .then(() => {
          apprise('¡Mercado creado!');
          $('#modalCrearMercado').modal('hide');
        })
        .finally(() => {
          $.unblockUI();
          // recargar y volver a abrir la lista
          this.listarMercados().then(() => $('#modalMercados').modal('show'));
        });
    },

    abrirModalEditarMercadoDesdeLista(m) {

      this.formMercado = {
        mercado_id: parseInt(m.mercado_id,10),
        nombre: (m.nombre || ''),
        direccion: (m.direccion || ''),
        is_activo: (parseInt(m.is_activo,10) ? 1 : 0),

        logo: (m.logo || ''),
        topnavbar_color: (m.topnavbar_color || ''),
        patron_fondo: (m.patron_fondo || '')
      };

      $('#modalMercados').modal('hide');
      $('#modalEditarMercado').modal('show');
    },

    guardarMercado() {
      if (!this.formMercado.nombre || !this.formMercado.nombre.trim()) return apprise('Escribe el nombre');

      this.bloquear('Actualizando mercado…');
      axios.post(`${this.apphost}/mercado/editar`, this.formMercado)
        .then(() => {
          apprise('¡Mercado actualizado!');
          $('#modalEditarMercado').modal('hide');
        })
        .finally(() => {
          $.unblockUI();
          this.listarMercados().then(() => $('#modalMercados').modal('show'));
          // refrescar tabla principal de neg también (por nombre mercado)
          this.listarNeg();
        });
    },

    eliminarMercado(m) {
      apprise(`¿Eliminar mercado <b>#${m.mercado_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;

        this.bloquear('Eliminando mercado…');
        axios.post(`${this.apphost}/mercado/eliminar`, { mercado_id: m.mercado_id })
          .then(() => apprise('Mercado eliminado'))
          .finally(() => {
            $.unblockUI();
            this.listarMercados();
            this.listarNeg();
          });
      });
    }
  },

  mounted() {
    this.listarNeg();
    this.cargarRubros(); // 🔥 NUEVO
  }
});
</script>