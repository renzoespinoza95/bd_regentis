<div class="row-fluid" id="appTrab">
  <div class="span12">

    <h2>Trabajadores</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalAgregarTrabajador">
        <i class="icon-plus icon-white"></i> Agregar
      </button>

      <button class="btn btn-info" style="margin-left:6px" @click="abrirModalModulos">
        <i class="icon-th icon-white"></i> Módulos
      </button>
    </div>

    <table id="tablaTrab" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Negocio</th>
          <th>Usuario</th>
          <th>DNI</th>
          <th>Teléfono</th>
          <th>Activo</th>
          <th>Tipos</th>
          <th>Módulos</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <!-- DataTable dibuja -->
      </tbody>
    </table>

    <!-- =========================================================
      MODAL: AGREGAR TRABAJADOR
    ========================================================== -->
    <div id="modalAgregarTrab" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Agregar Trabajador</h3>
      </div>

      <div class="modal-body">

        <div class="control-group">
          <label class="control-label">DNI</label>
          <div class="controls">
            <div class="input-append">
              <input v-model="nuevoTrab.dni" class="input-medium" placeholder="DNI">
              <button class="btn" @click="buscarUsuarioPorDni">
                <i class="icon-search"></i> Buscar
              </button>
            </div>
          </div>
        </div>

        <div v-if="usuEncontrado" class="alert alert-success">
          <b>Encontrado:</b><br>
          <div><b>Usu ID:</b> {{ usuEncontrado.usu_id }}</div>
          <div><b>Nombre:</b> {{ usuEncontrado.nombres_apellidos }}</div>
          <div><b>Celular:</b> {{ usuEncontrado.celular }}</div>
        </div>

        <div v-else class="alert" style="display:block">
          Ingresa el DNI y pulsa <b>Buscar</b>.
        </div>

        <hr>

        <div class="control-group">
          <label class="control-label">Negocio</label>
          <div class="controls">
            <!-- vue-select -->
            <v-select
              :options="negs"
              label="nombre"
              :reduce="n => n.neg_id"
              v-model="nuevoTrab.neg_id"
              placeholder="Selecciona negocio..."
            ></v-select>
            <p class="help-block">Elige el negocio (neg_id) donde trabajará.</p>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Tipos de trabajador</label>
          <div class="controls">
            <!-- vue-select multiselect -->
            <v-select
              multiple
              :options="tiposTrab"
              label="nombre"
              :reduce="t => t.deli_tipo_trabajador_id"
              v-model="nuevoTrab.tipos"
              placeholder="Selecciona tipos..."
            ></v-select>
            <p class="help-block">Esto se guardará en <b>deli_trabajadorxtipo</b>.</p>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarTrabajador">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =========================================================
      MODAL: MÓDULOS (deli_tipo_trabajador_modulo)
    ========================================================== -->
    <div id="modalModulos" class="modal hide fade" tabindex="-1" style="width: 900px; margin-left: -450px;">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Asignación: Tipo Trabajador ↔ Módulos</h3>
      </div>

      <div class="modal-body">

        <div class="form-actions" style="margin-top:0;">
          <button class="btn btn-success" @click="abrirModalAgregarTipoTrabajador">
            <i class="icon-plus icon-white"></i> Agregar tipo trabajador
          </button>

          <button class="btn btn-primary" style="margin-left:6px" @click="abrirModalAgregarTipoXMod">
            <i class="icon-random icon-white"></i> Agregar tipo x mod
          </button>
        </div>

        <table id="tablaTipoXMod" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tipo trabajador</th>
              <th>Módulo</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <!-- DataTable dibuja -->
          </tbody>
        </table>

      </div>

      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- =========================================================
      MODAL: AGREGAR TIPO TRABAJADOR
      (cierra modalModulos -> abre este -> guarda -> vuelve a abrir modalModulos)
    ========================================================== -->
    <div id="modalAddTipoTrab" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Agregar Tipo de Trabajador</h3>
      </div>

      <div class="modal-body">

        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="nuevoTipo.nombre" class="input-xlarge" placeholder="Ej: MOTORIZADO">
          </div>
        </div>

        <hr>

        <h4>Tipos existentes</h4>

        <table id="tablaTiposExistentes" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarTipoTrabajador">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =========================================================
      MODAL: AGREGAR TIPO X MOD (multi módulos)
      (cierra modalModulos -> abre este -> guarda -> vuelve a abrir modalModulos)
    ========================================================== -->
    <div id="modalAddTipoXMod" class="modal hide fade" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Agregar Tipo x Módulos</h3>
      </div>

      <div class="modal-body">

        <div class="control-group">
          <label class="control-label">Tipo trabajador</label>
          <div class="controls">
            <v-select
              :options="tiposTrab"
              label="nombre"
              :reduce="t => t.deli_tipo_trabajador_id"
              v-model="formTipoXMod.deli_tipo_trabajador_id"
              placeholder="Selecciona tipo..."
            ></v-select>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Módulos</label>
          <div class="controls">
            <v-select
              multiple
              :options="modulos"
              label="nombre"
              :reduce="m => m.modulo_id"
              v-model="formTipoXMod.modulos"
              placeholder="Selecciona módulos..."
            ></v-select>
            <p class="help-block">Esto inserta en <b>deli_tipo_trabajador_modulo</b> (varios módulos de una).</p>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarTipoXMod">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

  </div>
</div>

<script>
/* vue-select 2.6.4 (global) */
Vue.component('v-select', VueSelect.VueSelect);

const appTrab = new Vue({
  el: '#appTrab',
  data: {
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    dtTrab: null,
    dtTipoXMod: null,
    dtTipos: null,
    trabajadores: [],
    negs: [],
    tiposTrab: [],
    modulos: [],

    nuevoTrab: {
      dni: '',
      neg_id: null,
      tipos: []
    },

    usuEncontrado: null,

    nuevoTipo: { nombre: '' },

    formTipoXMod: {
      deli_tipo_trabajador_id: null,
      modulos: []
    }
  },
  methods: {
    block(msg) {
      $.blockUI({
        message: `<h4>${msg}</h4>`,
        css: { border:'none', padding:'15px', background:'#000', opacity:.6, color:'#fff' }
      });
    },
    unblock() { $.unblockUI(); },

    /* ============================
       CARGAS BASE
    ============================ */
    cargarCombosBase() {
      return Promise.all([
        axios.get(`${this.apphost}/reg-neg/listar`).then(r => { this.negs = r.data || []; }),
        axios.get(`${this.apphost}/deli-tipo-trabajador/listar`).then(r => { this.tiposTrab = r.data || []; }),
        axios.get(`${this.apphost}/reg-modulo/listar`).then(r => { this.modulos = r.data || []; })
      ]);
    },

    /* ============================
       LISTAR TRABAJADORES (DataTable)
    ============================ */
    listarTrabajadores() {
      this.block('Cargando trabajadores…');
      axios.get(`${this.apphost}/deli-trabajador/listar`)
        .then(r => {
          this.trabajadores = r.data || [];

          this.$nextTick(() => {
            if (!this.dtTrab) {
              this.dtTrab = $('#tablaTrab').DataTable({
                language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });

              const self = this;
              $('#tablaTrab tbody')
                .on('click', 'a.trab-desactivar', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  self.desactivarTrabajador(id);
                })
                .on('click', 'a.trab-eliminar', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  self.eliminarTrabajador(id);
                });
            }

            this.dtTrab.clear();

            this.trabajadores.forEach(t => {
              const activoTxt = (parseInt(t.is_activo, 10) === 1) ? 'SI' : 'NO';

              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="trab-desactivar" data-id="${t.deli_trabajador_id}">Desactivar</a></li>
                    <li><a href="#" class="trab-eliminar" data-id="${t.deli_trabajador_id}">Eliminar</a></li>
                  </ul>
                </div>
              `;

              this.dtTrab.row.add([
                t.deli_trabajador_id,
                (t.neg_nombre || ('#' + (t.neg_id || ''))),
                (t.usu_nombre || ('Usu #' + (t.usu_id || ''))),
                (t.dni || ''),
                (t.telefono || ''),
                activoTxt,
                t.tipos_trabajador || '',
                t.modulos_acceso || '',
                actions
              ]);
            });

            this.dtTrab.draw(false);
          });
        })
        .finally(() => this.unblock());
    },

    /* ============================
       MODAL: AGREGAR TRABAJADOR
    ============================ */
    abrirModalAgregarTrabajador() {
      this.nuevoTrab = { dni: '', neg_id: null, tipos: [] };
      this.usuEncontrado = null;

      this.block('Cargando datos…');
      this.cargarCombosBase()
        .finally(() => {
          this.unblock();
          $('#modalAgregarTrab').modal('show');
        });
    },

    listarTiposExistentes() {

      axios.get(`${this.apphost}/deli-tipo-trabajador/listar`)
        .then(r => {

          const rows = r.data || [];

          this.$nextTick(() => {

            if (!this.dtTipos) {
              this.dtTipos = $('#tablaTiposExistentes').DataTable({
                language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });
            }

            this.dtTipos.clear();

            rows.forEach(t => {
              this.dtTipos.row.add([
                t.deli_tipo_trabajador_id,
                t.nombre
              ]);
            });

            this.dtTipos.draw(false);

          });

        });

    },    

    buscarUsuarioPorDni() {
      const dni = (this.nuevoTrab.dni || '').trim();
      if (!dni) return apprise('Escribe el DNI');

      this.block('Buscando usuario…');
      axios.get(`${this.apphost}/reg-usu/buscar-dni`, { params: { dni } })
        .then(r => {
          if (r.data && r.data.usu_id) {
            this.usuEncontrado = r.data;
          } else {
            this.usuEncontrado = null;
            apprise('No se encontró usuario con ese DNI');
          }
        })
        .catch(() => {
          this.usuEncontrado = null;
          apprise('Error buscando usuario');
        })
        .finally(() => this.unblock());
    },

    guardarTrabajador() {
      if (!this.usuEncontrado || !this.usuEncontrado.usu_id) return apprise('Primero busca y selecciona un usuario por DNI');
      if (!this.nuevoTrab.neg_id) return apprise('Selecciona un negocio');
      if (!this.nuevoTrab.tipos || this.nuevoTrab.tipos.length === 0) return apprise('Selecciona al menos un tipo de trabajador');

      const payload = {
        dni: (this.nuevoTrab.dni || '').trim(),
        usu_id: this.usuEncontrado.usu_id,
        neg_id: this.nuevoTrab.neg_id,
        tipos: this.nuevoTrab.tipos
      };

      this.block('Guardando trabajador…');
      axios.post(`${this.apphost}/deli-trabajador/crear`, payload)
        .then(r => {
          if (r.data && r.data.status === 'ok') {
            $('#modalAgregarTrab').modal('hide');
            apprise('¡Trabajador creado!');
            this.listarTrabajadores();
          } else {
            apprise((r.data && r.data.msg) ? r.data.msg : 'No se pudo crear');
          }
        })
        .catch(() => apprise('Error guardando'))
        .finally(() => this.unblock());
    },

    /* ============================
       ACCIONES: DESACTIVAR / ELIMINAR
    ============================ */
    desactivarTrabajador(deli_trabajador_id) {
      apprise(`¿Desactivar trabajador <b>#${deli_trabajador_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;
        this.block('Desactivando…');
        axios.post(`${this.apphost}/deli-trabajador/desactivar`, { deli_trabajador_id })
          .then(() => {
            apprise('Desactivado');
          })
          .finally(() => {
            this.unblock();
            this.listarTrabajadores();
          });
      });
    },

    eliminarTrabajador(deli_trabajador_id) {
      apprise(`¿Eliminar trabajador <b>#${deli_trabajador_id}</b>?<br><small>Esto borrará también sus tipos por cascada si tienes FK/DELETE CASCADE.</small>`,
        { confirm:true },
        ok => {
          if (!ok) return;
          this.block('Eliminando…');
          axios.post(`${this.apphost}/deli-trabajador/eliminar`, { deli_trabajador_id })
            .then(() => apprise('Eliminado'))
            .finally(() => {
              this.unblock();
              this.listarTrabajadores();
            });
        }
      );
    },

    /* ============================
       MODAL: MÓDULOS (Tipo ↔ Mod)
    ============================ */
    abrirModalModulos() {
      this.block('Cargando módulos…');
      this.cargarCombosBase()
        .then(() => this.listarTipoXMod(false))
        .finally(() => {
          this.unblock();
          $('#modalModulos').modal('show');
        });
    },

    listarTipoXMod(keepOpen) {
      this.block('Cargando tabla tipo x módulo…');
      return axios.get(`${this.apphost}/deli-tipo-trabajador-modulo/listar`)
        .then(r => {
          const rows = r.data || [];

          this.$nextTick(() => {
            if (!this.dtTipoXMod) {
              this.dtTipoXMod = $('#tablaTipoXMod').DataTable({
                language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });

              const self = this;
              $('#tablaTipoXMod tbody')
                .on('click', 'a.dttm-eliminar', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  self.eliminarTipoXMod(id);
                });
            }

            this.dtTipoXMod.clear();

            rows.forEach(x => {
              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="dttm-eliminar" data-id="${x.deli_tipo_trabajador_modulo_id}">Eliminar</a></li>
                  </ul>
                </div>
              `;

              this.dtTipoXMod.row.add([
                x.deli_tipo_trabajador_modulo_id,
                x.tipo_nombre,
                x.modulo_nombre,
                (x.fecha_creacion || ''),
                actions
              ]);
            });

            this.dtTipoXMod.draw(false);

            if (keepOpen) {
              $('#modalModulos').modal('show');
            }
          });
        })
        .finally(() => this.unblock());
    },

    eliminarTipoXMod(deli_tipo_trabajador_modulo_id) {
      apprise(`¿Eliminar asignación <b>#${deli_tipo_trabajador_modulo_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;
        this.block('Eliminando…');
        axios.post(`${this.apphost}/deli-tipo-trabajador-modulo/eliminar`, { deli_tipo_trabajador_modulo_id })
          .then(() => apprise('Eliminado'))
          .finally(() => {
            this.unblock();
            this.listarTipoXMod(true);
          });
      });
    },

    /* ============================
       MODAL: AGREGAR TIPO TRABAJADOR
       (cierra modalModulos -> abre modalAddTipoTrab -> guarda -> vuelve modalModulos)
    ============================ */
    abrirModalAgregarTipoTrabajador() {
      this.nuevoTipo = { nombre: '' };
      $('#modalModulos').modal('hide');

      this.$nextTick(() => {
        $('#modalAddTipoTrab').modal('show');
        this.listarTiposExistentes();
      });
    },

    guardarTipoTrabajador() {
      const nombre = (this.nuevoTipo.nombre || '').trim();
      if (!nombre) return apprise('Escribe el nombre');

      this.block('Guardando tipo…');
      axios.post(`${this.apphost}/deli-tipo-trabajador/crear`, { nombre })
        .then(r => {
          if (r.data && r.data.status === 'ok') {
            $('#modalAddTipoTrab').modal('hide');
            apprise('¡Tipo creado!');
            this.listarTiposExistentes();
          } else {
            apprise((r.data && r.data.msg) ? r.data.msg : 'No se pudo crear');
          }
        })
        .finally(() => {
          this.unblock();
          // refrescar combos y reabrir modal anterior
          this.cargarCombosBase().finally(() => {
            $('#modalModulos').modal('show');
            this.listarTipoXMod(false);
          });
        });
    },

    /* ============================
       MODAL: AGREGAR TIPO X MOD (multi)
       (cierra modalModulos -> abre modalAddTipoXMod -> guarda -> vuelve modalModulos)
    ============================ */
    abrirModalAgregarTipoXMod() {
      this.formTipoXMod = { deli_tipo_trabajador_id: null, modulos: [] };
      $('#modalModulos').modal('hide');

      this.$nextTick(() => {
        $('#modalAddTipoXMod').modal('show');
      });
    },

    guardarTipoXMod() {
      if (!this.formTipoXMod.deli_tipo_trabajador_id) return apprise('Selecciona un tipo trabajador');
      if (!this.formTipoXMod.modulos || this.formTipoXMod.modulos.length === 0) return apprise('Selecciona módulos');

      const payload = {
        deli_tipo_trabajador_id: this.formTipoXMod.deli_tipo_trabajador_id,
        modulos: this.formTipoXMod.modulos
      };

      this.block('Guardando tipo x módulos…');
      axios.post(`${this.apphost}/deli-tipo-trabajador-modulo/crear-multi`, payload)
        .then(r => {
          if (r.data && r.data.status === 'ok') {
            $('#modalAddTipoXMod').modal('hide');
            apprise('¡Asignado!');
          } else {
            apprise((r.data && r.data.msg) ? r.data.msg : 'No se pudo asignar');
          }
        })
        .finally(() => {
          this.unblock();
          $('#modalModulos').modal('show');
          this.listarTipoXMod(false);
        });
    }
  },
  mounted() {
    this.cargarCombosBase().finally(() => {
      this.listarTrabajadores();
    });
  }
});
</script>