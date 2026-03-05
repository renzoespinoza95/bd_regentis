<style>
  /* Ajustes para que luzca bien con BS 2.3.2 */
  .sn-editor .note-editor.note-frame {
    border: 1px solid #ddd;
    border-radius: 4px;
  }
  .input-xxlarge { width: 600px; } /* ya la usas en tu proyecto */
  .modal .well { max-height: 400px; overflow:auto; }
</style>

<div class="row-fluid" id="appAutoMsg">
  <div class="span12">
    <h2>Auto Mensajes</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrear">
        <i class="icon-plus icon-white"></i> Nuevo Mensaje
      </button>
    </div>

    <table id="tablaAutoMsg" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th style="width:80px;">ID</th>
          <th>Clave</th>
          <th style="width:160px;">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <!-- Si usas DataTables por JS, el tbody se repobla desde this.dt -->
        <tr v-for="a in autos" :key="a.auto_msg_id">
          <td>{{ a.auto_msg_id }}</td>
          <td>{{ a.clave_txt }}</td>
          <td>
            <div class="btn-group">
              <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                Opciones <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" @click.prevent="abrirModalDetalle(a)">Detalle</a></li>
                <li><a href="#" @click.prevent="abrirModalEditar(a)">Editar</a></li>
                <li><a href="#" @click.prevent="eliminarAuto(a)">Eliminar</a></li>
              </ul>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Detalle -->
    <div id="modalDetalleAuto" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Detalle</h3></div>
      <div class="modal-body">
        <p><strong>ID:</strong> {{ detalle.auto_msg_id }}</p>
        <p><strong>Clave:</strong> {{ detalle.clave_txt }}</p>
        <p><strong>Mensaje:</strong></p>
        <div class="well" v-html="detalle.texto_msg"></div>
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- Modal Crear -->
    <div id="modalCrearAuto" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Nuevo Mensaje</h3></div>
      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Clave</label>
          <div class="controls">
            <input v-model.trim="nuevo.clave_txt" class="input-xxlarge" placeholder="p.ej: bienvenida_cliente">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Texto (Summernote)</label>
          <div class="controls">
            <div id="snCrear" class="sn-editor"></div>
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearAuto">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- Modal Editar -->
    <div id="modalEditarAuto" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Editar Mensaje</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label class="control-label">ID</label>
          <div class="controls">
            <input :value="form.auto_msg_id" class="input-small" disabled>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Clave</label>
          <div class="controls">
            <input v-model.trim="form.clave_txt" class="input-xxlarge">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Texto (Summernote)</label>
          <div class="controls">
            <div id="snEditar" class="sn-editor"></div>
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
/* global apphost, $, axios, apprise */

const appAutoMsg = new Vue({
  el: '#appAutoMsg',
  data: {
    apphost: apphost,          // ya lo usas en tu proyecto
    autos: [],                 // lista de registros
    nuevo: { clave_txt:'', texto_msg:'' },
    form:  { auto_msg_id:0, clave_txt:'', texto_msg:'' },
    detalle: {},
    dt: null,
    snCrearInit: false,
    snEditarInit: false
  },
  methods: {
    /* ========== DataTables + Listado =============================== */
    listar () {
      $.blockUI({
        message: '<h4>Cargando mensajes…</h4>',
        css:{border:'none',padding:'15px',background:'#000',opacity:.6,color:'#fff'}
      });

      axios.get(`${this.apphost}/auto_msg/listar`)
        .then(r => {
          this.autos = r.data || [];

          this.$nextTick(() => {
            if (!this.dt) {
              this.dt = $('#tablaAutoMsg').DataTable({
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });

              // Delego eventos para acciones dentro del DataTable
              const self = this;
              $('#tablaAutoMsg tbody')
                .on('click','a.detalle-auto', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const it = self.autos.find(x => x.auto_msg_id == id);
                  if (it) self.abrirModalDetalle(it);
                })
                .on('click','a.editar-auto', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const it = self.autos.find(x => x.auto_msg_id == id);
                  if (it) self.abrirModalEditar(it);
                })
                .on('click','a.eliminar-auto', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const it = self.autos.find(x => x.auto_msg_id == id);
                  if (it) self.eliminarAuto(it);
                });
            }

            // Repoblar filas
            this.dt.clear();
            this.autos.forEach(a => {
              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="detalle-auto" data-id="${a.auto_msg_id}">Detalle</a></li>
                    <li><a href="#" class="editar-auto"  data-id="${a.auto_msg_id}">Editar</a></li>
                    <li><a href="#" class="eliminar-auto" data-id="${a.auto_msg_id}">Eliminar</a></li>
                  </ul>
                </div>`;
              this.dt.row.add([ a.auto_msg_id, this.esc(a.clave_txt), actions ]);
            });
            this.dt.draw(false);
          });
        })
        .finally(() => $.unblockUI());
    },

    /* ========== Util ========== */
    esc (t) {
      return String(t ?? '').replace(/[&<>"']/g, s => ({
        '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
      }[s]));
    },

    /* ========== Detalle ========== */
    abrirModalDetalle (a) {
      this.detalle = a;
      $('#modalDetalleAuto').modal('show');
    },

    /* ========== Crear ========== */
    abrirModalCrear () {
      this.nuevo = { clave_txt:'', texto_msg:'' };
      $('#modalCrearAuto').off('shown').on('shown', () => this.initSummernoteCrear(true));
      $('#modalCrearAuto').modal('show');
    },
    initSummernoteCrear (reset=false) {
      const vm = this;
      if (reset) $('#snCrear').html('');
      if (this.snCrearInit) { $('#snCrear').summernote('destroy'); }
      $('#snCrear').summernote({
        height: 220,
        placeholder: 'Escribe el mensaje…',
        callbacks: {
          onChange: function(contents){ vm.nuevo.texto_msg = contents; }
        }
      });
      this.snCrearInit = true;
    },
    crearAuto () {
      if (!this.nuevo.clave_txt.trim()) return apprise('La clave es obligatoria');
      if (!this.nuevo.texto_msg.trim()) return apprise('El texto del mensaje es obligatorio');

      $.blockUI({
        message: '<h4>Creando…</h4>',
        css:{border:'none',padding:'15px',background:'#000',opacity:.6,color:'#fff'}
      });

      axios.post(`${this.apphost}/auto_msg/crear`, this.nuevo)
        .then(() => {
          $('#modalCrearAuto').modal('hide');
          apprise('¡Creado!');
        })
        .finally(() => {
          $.unblockUI();
          this.listar();
          // limpiar summernote crear
          try { $('#snCrear').summernote('destroy'); this.snCrearInit=false; } catch(e){}
        });
    },

    /* ========== Editar ========== */
    abrirModalEditar (a) {
      this.form = Object.assign({}, a);
      $('#modalEditarAuto').off('shown').on('shown', () => this.initSummernoteEditar(this.form.texto_msg));
      $('#modalEditarAuto').modal('show');
    },
    initSummernoteEditar (html='') {
      const vm = this;
      if (this.snEditarInit) { $('#snEditar').summernote('destroy'); }
      $('#snEditar').html(html || '');
      $('#snEditar').summernote({
        height: 220,
        callbacks: {
          onChange: function(contents){ vm.form.texto_msg = contents; }
        }
      });
      this.snEditarInit = true;
    },
    guardarEdicion () {
      if (!this.form.clave_txt.trim())  return apprise('La clave es obligatoria');
      if (!this.form.texto_msg.trim())  return apprise('El texto del mensaje es obligatorio');

      $.blockUI({
        message: '<h4>Actualizando…</h4>',
        css:{border:'none',padding:'15px',background:'#000',opacity:.6,color:'#fff'}
      });

      axios.post(`${this.apphost}/auto_msg/editar`, this.form)
        .then(() => {
          $('#modalEditarAuto').modal('hide');
          apprise('¡Actualizado!');
        })
        .finally(() => {
          $.unblockUI();
          this.listar();
          // limpiar summernote editar
          try { $('#snEditar').summernote('destroy'); this.snEditarInit=false; } catch(e){}
        });
    },

    /* ========== Eliminar ========== */
    eliminarAuto (a) {
      apprise(`¿Eliminar mensaje <b>#${a.auto_msg_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;
        axios.post(`${this.apphost}/auto_msg/eliminar`, { auto_msg_id: a.auto_msg_id })
             .finally(() => this.listar());
      });
    }
  },
  mounted() {
    this.listar();
  }
});
</script>
