<script>
  Vue.component('v-select', VueSelect.VueSelect);
</script>
<div id="appUsuario" class="row-fluid">
  <div class="span10">
    <h2>Gestión de Usuarios</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrear">
        <i class="icon-plus icon-white"></i> Nuevo Usuario
      </button>
      <button class="btn btn-info" @click="abrirModalBuscar">
        <i class="icon-search icon-white"></i> Buscar Usuario
      </button>
    </div>

    <table id="tablaUsuarios" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th>Código</th>
              <th>Sobrenombre</th>
              <th>Celular</th>
              <th>Provincia</th>
              <th>Creación</th>
              <th>Tipo Usuario</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in usuarios" :key="u.usu_id">
              <td>
                <img :src="u.avatar_mini" alt="avatar" class="avatar-mini">
              </td>
              <td>{{ u.usu_id }}</td>
              <td>{{ u.cod_usu }}</td>
              <td>{{ u.sobrenombre }}</td>
              <td>{{ u.celular }}</td>
              <td>{{ u.provincia }}</td>
              <td>{{ u.fecha_creacion }}</td>
              <td>{{ getTipoDescripcion(u.tipoxusu_id) }}</td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" @click.prevent="abrirModalEditar(u)">Editar</a></li>
                    <li><a href="#" @click.prevent="eliminarUsuario(u)">Liquidar</a></li>
                    <li><a href="#" @click.prevent="abrirModalDetalle(u)">Detalle</a></li>
                    <li><a href="#" @click.prevent="abrirModalChats(u)">Chats</a></li>
                    <li><a href="#" @click.prevent="abrirModalNuevoChat(u)">Mensaje nuevo</a></li>
                  </ul>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

 <!-- Modal Crear Usuario -->
    <div id="modalCrearUsuario" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Nuevo Usuario</h3></div>
      <div class="modal-body">
        <form class="form-horizontal">
          <div class="control-group">
            <label class="control-label">Código Usuario</label>
            <div class="controls">
              <input v-model="nuevo.cod_usu" class="input-small">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Google UID</label>
            <div class="controls">
              <input v-model="nuevo.google_uid" class="input-xxlarge">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">URL Perfil</label>
            <div class="controls">
              <input v-model="nuevo.img_perfil" class="input-xxlarge">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Sobrenombre</label>
            <div class="controls">
              <input v-model="nuevo.sobrenombre" class="input-xxlarge">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Celular</label>
            <div class="controls">
              <input v-model="nuevo.celular" class="input-small">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Provincia</label>
            <div class="controls">
              <input v-model="nuevo.provincia" class="input-small">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Fecha Nacimiento</label>
            <div class="controls">
              <input type="date" v-model="nuevo.fecha_nacimiento" class="input-small">
            </div>
          </div>

          <div class="control-group">
            <label class="control-label">Tipo Usuario</label>
            <div class="controls">
              <v-select
                :options="tipousuarios"
                label="descripcion"
                :reduce="t => t.tipoxusu_id"
                v-model="nuevo.tipoxusu_id"
                placeholder="Seleccione tipo de usuario…"
              ></v-select>
            </div>
          </div>

          <div class="control-group">
            <label class="control-label">Activo</label>
            <div class="controls">
              <input type="checkbox" v-model="nuevo.is_activo">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Premium</label>
            <div class="controls">
              <input type="checkbox" v-model="nuevo.is_premium">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Fin Premium</label>
            <div class="controls">
              <input type="datetime-local" v-model="nuevo.fecha_fin_premium" class="input-small">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearUsuario">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>


    <!-- Modal Editar Usuario -->
<!-- Modal Editar Usuario -->
<div id="modalEditarUsuario" class="modal hide fade" tabindex="-1">
  <div class="modal-header">
    <h3>Editar Usuario</h3>
  </div>
  <div class="modal-body">
    <form class="form-horizontal">
      <div class="control-group">
        <label class="control-label">Código Usuario</label>
        <div class="controls">
          <input v-model="formulario.cod_usuario" class="input-small">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Google UID</label>
        <div class="controls">
          <input v-model="formulario.google_uid" class="input-xxlarge">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">URL Perfil</label>
        <div class="controls">
          <input v-model="formulario.img_perfil" class="input-xxlarge">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Sobrenombre</label>
        <div class="controls">
          <input v-model="formulario.sobrenombre" class="input-xxlarge">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Celular</label>
        <div class="controls">
          <input v-model="formulario.celular" class="input-small">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Provincia</label>
        <div class="controls">
          <input v-model="formulario.provincia" class="input-small">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Fecha de Nacimiento</label>
        <div class="controls">
          <input type="date" v-model="formulario.fecha_nacimiento" class="input-small">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Tipo Usuario</label>
        <div class="controls">
          <v-select
            :options="tipousuarios"
            label="descripcion"
            :reduce="t => t.tipoxusu_id"
            v-model="formulario.tipoxusu_id"
            placeholder="Seleccione tipo de usuario…"
          ></v-select>
        </div>
      </div>


      <div class="control-group">
        <label class="control-label">Activo</label>
        <div class="controls">
          <input type="checkbox" v-model="formulario.is_activo">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Premium</label>
        <div class="controls">
          <input type="checkbox" v-model="formulario.is_premium">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Fin de Premium</label>
        <div class="controls">
          <input type="datetime-local" v-model="formulario.fecha_fin_premium" class="input-small">
        </div>
      </div>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" @click="guardarEdicion">
      <i class="icon-ok icon-white"></i> Guardar
    </button>
    <button class="btn" data-dismiss="modal">Cancelar</button>
  </div>
</div>


    <!-- Modal Detalle Usuario -->
<!-- Modal Detalle Usuario -->
<div id="modalDetalleUsuario" class="modal hide fade" tabindex="-1">
    <div class="modal-header">
      <h3>Detalle Usuario</h3>
    </div>
    <div class="modal-body">
      <dl class="dl-horizontal">
        <dt>ID</dt>
        <dd>{{ detalle.usu_id }}</dd>

        <dt>Código Usuario</dt>
        <dd>{{ detalle.cod_usu }}</dd>

        <dt>Google UID</dt>
        <dd>{{ detalle.google_uid }}</dd>

        <dt>URL Perfil</dt>
        <dd>
          <a :href="detalle.img_perfil" target="_blank">{{ detalle.img_perfil }}</a>
          <div v-if="detalle.img_perfil">
            <img :src="detalle.img_perfil" alt="Foto de perfil" style="max-width:100px; margin-top:5px;">
          </div>
        </dd>

        <dt>Sobrenombre</dt>
        <dd>{{ detalle.sobrenombre }}</dd>

        <dt>Celular</dt>
        <dd>{{ detalle.celular }}</dd>

        <dt>Provincia</dt>
        <dd>{{ detalle.provincia }}</dd>

        <dt>Fecha de Nacimiento</dt>
        <dd>{{ detalle.fecha_nacimiento }}</dd>

        <dt>Creación</dt>
        <dd>{{ detalle.fecha_creacion }}</dd>

        <dt>Tipo Usuario</dt>
        <dd>{{ getTipoDescripcion(detalle.tipoxusu_id) }}</dd>

        <dt>Activo</dt>
        <dd>
          <span v-if="detalle.is_activo">Sí</span>
          <span v-else>No</span>
        </dd>

        <dt>Premium</dt>
        <dd>
          <span v-if="detalle.is_premium">Sí</span>
          <span v-else>No</span>
        </dd>

        <dt>Fin de Premium</dt>
        <dd>{{ detalle.fecha_fin_premium || '—' }}</dd>
      </dl>


<hr>
<form class="form-horizontal" @submit.prevent="subirFotoUsuario">
  <div class="control-group">
    <label class="control-label">Foto de perfil</label>
    <div class="controls">
      <input type="file" accept="image/*" @change="onFileChange">
      <button type="submit" class="btn btn-primary" :disabled="!archivoUsuario || subiendo">
        <i class="icon-upload icon-white"></i>
        {{ subiendo ? 'Subiendo…' : 'Subir' }}
      </button>
      <span v-if="errorUpload" class="text-error" style="margin-left:10px;">{{ errorUpload }}</span>
    </div>
  </div>

  <div class="control-group" v-if="detalle.img_perfil">
    <label class="control-label">Vista previa</label>
    <div class="controls">
      <a :href="buildFull(detalle.img_perfil)" target="_blank">
        <img :src="buildMini(detalle.img_perfil)" alt="Perfil" style="max-width:120px; border-radius:6px;">
      </a>
      <div class="muted" style="margin-top:5px;">Archivo: {{ detalle.img_perfil }}</div>
    </div>
  </div>
</form>


    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal">
        <i class="icon-remove"></i> Cerrar
      </button>
    </div>
  </div>


<!-- Modal Buscar Usuario (modificado para POST) -->
    <div id="modalBuscarUsuario" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Buscar Usuario</h3></div>
      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Sobrenombre</label>
          <div class="controls">
            <input v-model="buscarSobrenombre" class="input-xxlarge" placeholder="Escribe sobrenombre...">
            <button class="btn btn-mini" @click="buscarUsuario">
              <i class="icon-search"></i> Buscar
            </button>
          </div>
        </div>
        <table class="table table-bordered table-striped" v-if="resultados.length">
          <thead>
            <tr>
              <th>ID</th>
              <th>Código</th>
              <th>Sobrenombre</th>
              <th>Celular</th>
              <th>Provincia</th>
              <th>Tipo Usuario</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in resultados" :key="u.usuario_id">
              <td>{{ u.usuario_id }}</td>
              <td>{{ u.cod_usuario }}</td>
              <td>{{ u.sobrenombre }}</td>
              <td>{{ u.celular }}</td>
              <td>{{ u.provincia }}</td>
              <td>{{ getTipoDescripcion(u.tipoxusu_id) }}</td>
            </tr>
          </tbody>
        </table>
        <div v-else class="text-center text-muted">
          <em>No se encontraron usuarios.</em>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

<!-- Modal “Crear Chat” -->
<div id="modalNuevoChat" class="modal hide fade fullscreen" tabindex="-1">
  <div class="modal-header">
    <h3>Crear Nuevo Chat + Mensaje Inicial</h3>
  </div>
  <div class="modal-body">
    <form class="form-horizontal">
      
      <!-- Remitente (quien envía) -->
      <div class="control-group">
        <label class="control-label">Remitente</label>
        <div class="controls">
          <span class="uneditable-input">{{ remitenteNombre }}</span>
        </div>
      </div>
      <input type="hidden" v-model="nuevoChat.rem_id">

      <!-- Seleccionar Usuario 2 -->
      <div class="control-group">
        <label class="control-label">Usuario 2</label>
        <div class="controls">
          <v-select
            :options="opcionesUsuarios"
            label="text"
            :reduce="o => o.id"
            v-model="nuevoChat.usu2_id"
            placeholder="Seleccione Usuario 2…"
          ></v-select>
        </div>
      </div>


      <!-- Mensaje Inicial -->
      <div class="control-group">
        <label class="control-label">Mensaje Inicial</label>
        <div class="controls">
          <textarea id="summerMensaje" v-model="nuevoChat.mensaje"></textarea>
        </div>
      </div>
    </form>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" @click="crearChat">
      <i class="icon-ok icon-white"></i> Guardar
    </button>
    <button class="btn" data-dismiss="modal">Cancelar</button>
  </div>
</div>


<!-- Modal Chats REEMPLAZADO -->
<div id="modalChats" class="modal hide fade fullscreen" tabindex="-1">
  <div class="modal-header">
    <h3>Chats de {{ usuarioActual.sobrenombre }}</h3>
  </div>
  <div class="modal-body">
    
    <!-- Accordion de chats -->
    <div class="accordion" id="accordionChats">
      <div class="accordion-group" v-for="c in chats" :key="c.chat_id">
        
        <!-- Encabezado del accordion (toggle) -->
        <div class="accordion-heading">
          <a 
            class="accordion-toggle" 
            data-toggle="collapse"
            :data-parent="'#accordionChats'"
            :href="'#collapseChat' + c.chat_id"
            @click.prevent="abrirChat(c)"
          >
            Chat <i class="fa fa-user-circle-o"></i> {{ c.usuario1 }}&nbsp;con&nbsp;<i class="fa fa-user-circle-o"></i> {{ c.usuario2 }}
          </a>
        </div>
        
        <!-- Contenido colapsable -->
        <div 
          :id="'collapseChat' + c.chat_id" 
          class="accordion-body collapse"
        >
          <div class="accordion-inner">
            
            <!-- Si ya cargó mensajes para este chat, muestro la tabla -->
            <table 
              class="table table-bordered table-striped" 
              v-if="chatActual.chat_id === c.chat_id"
            >
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Rem</th>
                  <th>Fecha</th>
                  <th>Contenido</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="m in mensajes" :key="m.msg_id">
                  <td>{{ m.msg_id }}</td>
                  <td>{{ m.sobrenombre }}</td>
                  <td>{{ m.fecha_creacion }}</td>
                  <td>{{ m.contenido_rem }}</td>
                </tr>
              </tbody>
            </table>
            
            <!-- Si aún no está cargado (ni coincide chatActual), muestro un placeholder -->
            <div v-else class="text-center">
              <em>Selecciona el chat para ver los mensajes...</em>
            </div>
            
          </div>
        </div>
        
      </div>
    </div>
    <!-- /Accordion de chats -->
    
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal">Cerrar</button>
  </div>
</div>


  </div>
</div>

<script>
new Vue({
  el: '#appUsuario',
  data: {
    apphost: apphost,
    usuarios: [],
    tipousuarios: [],
    nuevo: {
      cod_usuario: '',
      google_uid: '',
      img_perfil: '',
      sobrenombre: '',
      celular: '',
      provincia: '',
      fecha_nacimiento: '',
      tipoxusu_id: null,
      is_activo: 1,
      is_premium: 0,
      fecha_fin_premium: ''
    },
    remitenteNombre: '',
    formulario: {},
    detalle: {},
    buscarSobrenombre: '',
    resultados: [],
    chats: [],
    mensajes: [],
    usuarioActual: {},
    chatActual: {},
     // ------------- NUEVO ----------------
    opcionesUsuarios: [],   // para Select2
    nuevoChat: {
      rem_id:  null,    // ← nuevo campo
      usu2_id: null,
      mensaje: ''
    },
    archivoUsuario: null,
    subiendo: false,
    errorUpload: ''
  },
  methods: {
    obtenerUsuarios() {  
      axios.get(this.apphost + '/usuario/listar')
        .then(r => {
          this.usuarios = r.data;
          this.initDataTable('#tablaUsuarios');
        })
        .catch(err => {
          console.error('Error al listar usuarios:', err);
          if (err.response && err.response.status === 401) {
            apprise('Sesión expirada, por favor vuelve a iniciar sesión');
          }
        });
    },
    obtenerTipousuarios() {
      axios.get(this.apphost + '/tipoxusu/listar')
        .then(r => { this.tipousuarios = r.data; });
    },
    getTipoDescripcion(id) {
      // console.log("getTipoDescripcion", id);
      const t = this.tipousuarios.find(x => x.tipoxusu_id == id);
      return t ? t.descripcion : '';
    },
    initDataTable(sel) {
      this.$nextTick(() => {
        if ($.fn.DataTable.isDataTable(sel)) $(sel).DataTable().destroy();
          $(sel).DataTable({
            scrollX: true,
            dom: 'frtip',
            columnDefs: [
              { targets: 0, orderable: false, searchable: false } // columna Foto
            ],
            order: [[1, 'desc']] // ordenar por ID (ahora es la col 1)
          });
      });
    },
    abrirModalCrear() {
      this.nuevo = {
        cod_usuario: '',
        google_uid: '',
        img_perfil: '',
        sobrenombre: '',
        celular: '',
        provincia: '',
        fecha_nacimiento: '',
        tipoxusu_id: null,
        is_activo: 1,
        is_premium: 0,
        fecha_fin_premium: ''
      };
      $('#modalCrearUsuario').modal('show');
    },
    crearUsuario() {
      axios.post(this.apphost + '/usuario/crear', this.nuevo)
        .then(() => {
          $('#modalCrearUsuario').modal('hide');
          this.obtenerUsuarios();
        });
    },
    abrirModalEditar(u) {
      // Asegúrate de mapear correctamente tipoxusu_id desde el registro
      this.formulario = Object.assign({}, u, {
        tipoxusu_id: u.tipoxusu_id ?? u.tipoxusuario_id ?? null
      });
      $('#modalEditarUsuario').modal('show');
    },
    guardarEdicion() {
      axios.post(this.apphost + '/usuario/editar', this.formulario)
        .then(()=>{ $('#modalEditarUsuario').modal('hide'); this.obtenerUsuarios(); });
    },
    eliminarUsuario(u) {
      apprise(`Liquidar usuario ${u.usu_id}?`,{confirm:true}, r=>{
        if(r) axios.post(this.apphost+'/usuario/liquidar',{ usu_id:u.usu_id })
                 .then(()=>this.obtenerUsuarios());
      });
    },

    abrirModalDetalle(u) {
      axios.get(this.apphost + '/usu/detalleUsu/' + u.usu_id)
        .then(r => {
          if (!r.data?.success) throw new Error('No se pudo cargar detalle');
          this.detalle = r.data.data; // incluye { img_perfil, pics:{full,mini}, ... }
          this.archivoUsuario = null;
          this.errorUpload = '';
          $('#modalDetalleUsuario').modal('show');
        })
        .catch(err => {
          console.error(err);
          apprise('No se pudo cargar el detalle del usuario.');
        });
    },




    abrirModalBuscar()   { this.buscarNombre=''; this.resultados=[]; $('#modalBuscarUsuario').modal('show'); },
    buscarUsuario() {
      // Ahora enviamos por POST al endpoint /usuario/buscar
      axios.post(this.apphost + '/usuario/buscar', {
        nombre: this.buscarNombre
      })
      .then(r => {
        // r.data es el array de usuarios que coinciden
        this.resultados = r.data;
      })
      .catch(err => {
        console.error('Error al buscar usuario:', err);
        this.resultados = [];
      });
    },
    abrirModalChats(u) {
      this.usuarioActual = u;
      axios.get(this.apphost + '/chat/listar/' + u.usu_id)
        .then(r=>{ this.chats = r.data; this.mensajes = []; this.chatActual={}; $('#modalChats').modal('show'); });
    },
    abrirChat(c) {
      console.log("c", c);
      this.chatActual = c;
      axios.get(this.apphost + '/msg/listar/' + c.chat_id +  "/" + c.usu1_id)
        .then(r=> this.mensajes = r.data);
    },

     // ------------------- NUEVO: abre modal para crear chat -------------------
    abrirModalNuevoChat(u) {
      this.nuevoChat.rem_id  = u.usu_id;
      this.nuevoChat.usu1_id = u.usu_id; // si lo mantienes así
      this.nuevoChat.usu2_id = null;
      this.nuevoChat.mensaje = '';
      this.remitenteNombre   = u.sobrenombre;

      // Opciones para el v-select de Usuario 2
      axios.get(this.apphost + '/usuario/listar').then(r => {
        this.opcionesUsuarios = r.data.map(x => ({
          id:   x.usu_id,
          text: x.sobrenombre
        }));

        // Summernote lo puedes mantener igual
        this.$nextTick(() => {
          if (!$('#summerMensaje').data('summernote')) {
            $('#summerMensaje').summernote({
              height: 150,
              callbacks: {
                onChange: contents => { this.nuevoChat.mensaje = contents; }
              }
            });
          }
          $('#summerMensaje').summernote('code', this.nuevoChat.mensaje);
          $('#modalNuevoChat').modal('show');
        });
      });
    },


    // ------------------- NUEVO: método para crear chat + mensaje -------------------
    crearChat() {
      if (!this.nuevoChat.usu2_id) {
        apprise('Debes seleccionar el Usuario 2.');
        return;
      }

      // Tomar el HTML desde Summernote y validar que no sea vacío visualmente
      const html = $('#summerMensaje').summernote('code') || '';
      const plain = $('<div>').html(html).text().trim();
      if (!plain) {
        apprise('Debes escribir el mensaje inicial.');
        return;
      }

      // Regla: usu1_id es el del remitente (como ya lo estás manejando)
      const usu1 = this.nuevoChat.usu1_id;   // remitente
      const usu2 = this.nuevoChat.usu2_id;   // destinatario seleccionado

      // El endpoint actual necesita dest_id explícito:
      const dest_id = (this.nuevoChat.rem_id === usu1) ? usu2 : usu1;

      const payload = {
        usu1_id:       usu1,
        usu2_id:       usu2,
        rem_id:        this.nuevoChat.rem_id,
        dest_id:       dest_id,
        contenido_rem: html   // si prefieres solo texto, usa `plain`
      };

      axios.post(this.apphost + '/chat/crear', payload)
        .then(() => {
          $('#modalNuevoChat').modal('hide');
          this.abrirModalChats({ usu_id: this.nuevoChat.rem_id });
        })
        .catch(err => {
          console.error('Error al crear chat:', err);
          apprise('Ocurrió un error al crear el chat.');
        });
    },



    onFileChange(e) {
      this.errorUpload = '';
      const f = e.target.files && e.target.files[0];
      if (!f) { this.archivoUsuario = null; return; }
      if (!/^image\//.test(f.type)) { this.errorUpload = 'El archivo debe ser una imagen.'; this.archivoUsuario = null; return; }
      if (f.size > 5 * 1024 * 1024) { this.errorUpload = 'Máximo 5MB.'; this.archivoUsuario = null; return; }
      this.archivoUsuario = f;
    },

    buildFull(name) { 
      const b = this.detalle?.pics?.full || ''; 
      return b ? (b + '/' + name) : name; 
    },
    buildMini(name) { 
      const b = this.detalle?.pics?.mini || ''; 
      return b ? (b + '/' + name) : name; 
    },

    async subirFotoUsuario() {
      if (!this.detalle?.usu_id) { this.errorUpload = 'Falta usu_id.'; return; }
      if (!this.archivoUsuario)  { this.errorUpload = 'Selecciona una imagen.'; return; }
      try {
        this.subiendo = true; this.errorUpload = '';
        const fd = new FormData();
        fd.append('usu_id', this.detalle.usu_id);
        fd.append('imagen', this.archivoUsuario);
        const r = await axios.post(this.apphost + '/usu/subirFoto', fd, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        // Actualiza solo filename; las bases ya están en this.detalle.pics
        this.detalle.img_perfil = r.data.filename;
        this.archivoUsuario = null;
        apprise('Foto actualizada correctamente.');
      } catch (err) {
        console.error(err);
        this.errorUpload = (err.response && err.response.data && err.response.data.error) || 'Error al subir.';
      } finally {
        this.subiendo = false;
      }
    }
  },
  mounted() {
    this.obtenerUsuarios();
    this.obtenerTipousuarios();
  }
});
</script>
<style>
  .avatar-mini{
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    display: block;
  }
</style>

