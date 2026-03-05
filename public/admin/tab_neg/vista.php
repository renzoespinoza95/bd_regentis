<!-- =========================================
     NEGOCIOS + MERCADOS (Bootstrap 2.3.2 / jQuery2 / Vue2 / Axios / DataTables)
========================================= -->

<div class="row-fluid" id="appNeg">
  <div class="span12">

    <h2>Negocios</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrearNeg">
        <i class="icon-plus icon-white"></i> Agregar Neg
      </button>

      <button class="btn btn-info" style="margin-left:8px" @click="abrirModalMercados">
        <i class="icon-th icon-white"></i> Mercados
      </button>
    </div>

    <!-- TABLA NEGOCIOS -->
    <table id="tablaNeg" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Puesto</th>
          <th>Mercado</th>
          <th>Activo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

    <!-- =========================
         MODAL CREAR NEG
    ========================== -->
    <div id="modalCrearNeg" class="modal hide fade" tabindex="-1">
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
              :reduce="m => m.mercado_id"
              label="nombre"
              placeholder="Selecciona un mercado..."
              v-model="nuevoNeg.mercado_id"
              style="width: 420px;"
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
    <div id="modalEditarNeg" class="modal hide fade" tabindex="-1">
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
              :reduce="m => m.mercado_id"
              label="nombre"
              placeholder="Selecciona un mercado..."
              v-model="formNeg.mercado_id"
              style="width: 420px;"
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
    <div id="modalPropietario" class="modal hide fade" tabindex="-1">
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
    <div id="modalMercados" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Mercados</h3>
      </div>

      <div class="modal-body">
        <div class="form-actions" style="margin-top:0;">
          <button class="btn btn-success" @click="abrirModalCrearMercadoDesdeLista">
            <i class="icon-plus icon-white"></i> Agregar
          </button>
        </div>

        <table id="tablaMercados" class="table table-bordered table-striped">
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
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- =========================
         MODAL CREAR MERCADO
    ========================== -->
    <div id="modalCrearMercado" class="modal hide fade" tabindex="-1">
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

    <!-- =========================
         MODAL EDITAR MERCADO
    ========================== -->
    <div id="modalEditarMercado" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Editar Mercado</h3>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="formMercado.nombre" class="input-xxlarge" placeholder="Nombre del mercado">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Dirección</label>
          <div class="controls">
            <input v-model="formMercado.direccion" class="input-xxlarge" placeholder="Dirección">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Activo</label>
          <div class="controls">
            <select v-model="formMercado.is_activo" class="input-small">
              <option :value="1">SI</option>
              <option :value="0">NO</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarMercado">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
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
    nuevoNeg: { nombre: '', puesto: '', mercado_id: null },
    formNeg: { neg_id: 0, nombre: '', puesto: '', mercado_id: null },

    // data mercados
    mercados: [],
    mercadosOptions: [],
    dtMerc: null,
    nuevoMercado: { nombre: '', direccion: '', is_activo: 1 },
    formMercado: { mercado_id: 0, nombre: '', direccion: '', is_activo: 1 },

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
    }
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

    /* =========================
       NEGOCIOS - LISTAR (DataTable principal)
    ========================== */
    listarNeg() {
      this.bloquear('Cargando negocios…');

      // primero cargar mercados (para nombres y combos)
      this.cargarMercadosParaCombo()
        .then(() => axios.get(`${this.apphost}/neg/listar`))
        .then(r => {
          this.negs = r.data.data || [];

          this.$nextTick(() => {
            if (!this.dtNeg) {
              this.dtNeg = $('#tablaNeg').DataTable({
                language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });

              const self = this;
              $('#tablaNeg tbody')
                .on('click', 'a.editar-neg', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                  if (row) self.abrirModalEditarNeg(row);
                })
                .on('click', 'a.eliminar-neg', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                  if (row) self.eliminarNeg(row);
                })
                .on('click', 'a.propietario-neg', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                  if (row) self.abrirModalPropietario(row);
                });
            }

            // refrescar filas
            this.dtNeg.clear();

            this.negs.forEach(n => {
              const mercadoNombre = this.obtenerNombreMercado(n.mercado_id);
              const activo = (parseInt(n.is_activo,10) ? 'SI' : 'NO');

              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="editar-neg" data-id="${n.neg_id}">Editar</a></li>
                    <li><a href="#" class="eliminar-neg" data-id="${n.neg_id}">Eliminar</a></li>
                    <li class="divider"></li>
                    <li><a href="#" class="propietario-neg" data-id="${n.neg_id}">Propietario</a></li>
                  </ul>
                </div>
              `;

              this.dtNeg.row.add([
                n.neg_id,
                (n.nombre || ''),
                (n.puesto || ''),
                (mercadoNombre || ''),
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

    /* =========================
       NEGOCIOS - MODALES / CRUD
    ========================== */
    abrirModalCrearNeg() {
      this.nuevoNeg = { nombre: '', puesto: '', mercado_id: null };
      $('#modalCrearNeg').modal('show');
    },

    crearNeg() {
      if (!this.nuevoNeg.nombre || !this.nuevoNeg.nombre.trim()) return apprise('Escribe el nombre');
      if (!this.nuevoNeg.puesto || !this.nuevoNeg.puesto.trim()) return apprise('Escribe el puesto');
      if (!this.nuevoNeg.mercado_id) return apprise('Selecciona un mercado');

      this.bloquear('Creando negocio…');
      axios.post(`${this.apphost}/neg/crear`, this.nuevoNeg)
        .then(() => {
          $('#modalCrearNeg').modal('hide');
          apprise('¡Creado!');
        })
        .finally(() => {
          $.unblockUI();
          this.listarNeg();
        });
    },

    abrirModalEditarNeg(n) {
      this.formNeg = {
        neg_id: parseInt(n.neg_id,10),
        nombre: (n.nombre || ''),
        puesto: (n.puesto || ''),
        mercado_id: (n.mercado_id ? parseInt(n.mercado_id,10) : null)
      };
      $('#modalEditarNeg').modal('show');
    },

    guardarNeg() {
      if (!this.formNeg.nombre || !this.formNeg.nombre.trim()) return apprise('Escribe el nombre');
      if (!this.formNeg.puesto || !this.formNeg.puesto.trim()) return apprise('Escribe el puesto');
      if (!this.formNeg.mercado_id) return apprise('Selecciona un mercado');

      this.bloquear('Actualizando negocio…');
      axios.post(`${this.apphost}/neg/editar`, this.formNeg)
        .then(() => {
          $('#modalEditarNeg').modal('hide');
          apprise('¡Actualizado!');
        })
        .finally(() => {
          $.unblockUI();
          this.listarNeg();
        });
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
        is_activo: (parseInt(m.is_activo,10) ? 1 : 0)
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
  }
});
</script>