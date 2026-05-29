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

    <table id="tablaUsuarios" class="table table-bordered table-condensed sel-fila">
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th>Código</th>
              <th>Google</th>
              <th width="90">
                  Fantasma
              </th>
              <th>Sobrenombre</th>
              <th>Nom. Ape.</th>
              <th>Celular</th>
              <th>DNI</th>
              <th>Clavel</th>
              <th>Rol</th>
              <th>Tipo Usuario</th>
              <th>Negocio</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in usuarios" :key="u.usu_id">
              <td>
                <div class="avatar-mini">
                  <img :src="u.img_perfil" alt="avatar">
                 </div> 
              </td>
              <td>{{ u.usu_id }}</td>
              <td>{{ u.cod_usu }}</td>
              <td>{{ u.google_uid }}</td>
              <td class="text-center">

                  <input
                      type="checkbox"
                      :checked="parseInt(u.is_fantasma) === 1"
                      @click.prevent="toggleFantasma(u)"
                  >

              </td>            
              <td>{{ u.sobrenombre }}</td>
              <td>{{ u.nombres_apellidos }}</td>
              <td>{{ u.celular }}</td>
              <td>{{ u.dni }}</td>
              <td>{{ u.clavel }}</td>
              <td>{{ u.rol_nombre }}</td>
              <td>{{ getTipoDescripcion(u.tipoxusu_id) }}</td>
              <td>{{ u.negocio_nombre  }}</td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                  ⚙ <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" @click.prevent="abrirModalEditar(u)">Editar</a></li>
                    <li><a href="#" @click.prevent="eliminarUsuario(u)">Liquidar</a></li>
                    <li><a href="#" @click.prevent="abrirModalDetalle(u)">Detalle</a></li>
                    <li>
                      <a
                        href="#"
                        @click.prevent="reiniciarUsuario(u)"
                      >
                        Reiniciar
                      </a>
                    </li>                    
                    <li>
                      <a
                        href="#"
                        @click.prevent="clave12(u)"
                      >
                        Clave 12
                      </a>
                    </li>
                    <li>
                      <a
                        href="#"
                        @click.prevent="nuevoNegocio(u)"
                      >
                        BtnNegocio
                      </a>
                    </li> 

                    <li>
                      <a
                        href="#"
                        @click.prevent="crearNegocioFantasma(u)"
                      >
                        NegFantasma
                      </a>
                    </li>

                    <li>
                      <a
                        href="#"
                        @click.prevent="iniciarSesionUsuario(u)"
                      >
                        Sesion
                      </a>
                    </li>


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
              <div class="controls" style="display:flex; gap:5px;">
                
                <input v-model="nuevo.cod_usuario" class="input-small">

                <button type="button" class="btn btn-mini" @click="generarCodigo">
                  🎲
                </button>

              </div>
          </div>
          <div class="control-group">
            <label class="control-label">DNI</label>
            <div class="controls" style="display:flex; gap:5px;">
              
              <input v-model="nuevo.dni" class="input-small">

              <button type="button" class="btn btn-mini" @click="generarDni">
                🎲
              </button>

            </div>
          </div>          

        <div class="control-group">
          <label class="control-label">Google UID</label>
          <div class="controls" style="display:flex; gap:5px;">
            
            <input v-model="nuevo.google_uid" class="input-xxlarge">

            <button type="button" class="btn btn-mini" @click="generarUid">
              🎲
            </button>

          </div>
        </div>
            <div class="control-group">

                <label class="control-label">
                    URL Perfil
                </label>

                <div class="controls">

                    <div style="display:flex; gap:5px; margin-bottom:10px;">

                        <input
                            type="text"
                            v-model="formulario.img_perfil"
                            class="input-xxlarge"
                        >

                        <button
                            type="button"
                            class="btn"
                            @click="generarAvatarRandom"
                        >
                            🎲
                        </button>

                    </div>

                    <!-- PREVIEW -->
                    <div
                        v-if="avatarPreview"
                        style="margin-bottom:10px;"
                    >
                        <img
                            :src="avatarPreview"
                            style="
                                width:120px;
                                height:120px;
                                border-radius:10px;
                                object-fit:cover;
                                border:1px solid #ccc;
                            "
                        >
                    </div>

                    <!-- GUARDAR -->
                    <button
                        type="button"
                        class="btn btn-success"
                        @click="guardarAvatarBunny"
                    >
                        Guardar en Bunny
                    </button>

                </div>

            </div>
          <div class="control-group">
            <label class="control-label">Sobrenombre</label>
            <div class="controls">
              <input v-model="nuevo.sobrenombre" class="input-large">
            </div>
          </div>
          <div class="control-group">
              <label class="control-label">Celular</label>

              <div class="controls">

                  <div style="display:flex; gap:5px; align-items:center;">

                      <input
                          type="text"
                          v-model="formulario.celular"
                          class="input-medium"
                      >

                      <button
                          type="button"
                          class="btn"
                          @click="generarCelularRandom"
                      >
                          🎲
                      </button>

                  </div>

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
         <button
            class="btn btn-warning"
            @click="crearUsuarioAutomatico"
          >
            <i class="icon-magic icon-white"></i>
            Automático
          </button>
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
        <div class="controls" style="display:flex; gap:5px;">
          
          <input v-model="formulario.cod_usuario">

          <button type="button" @click="generarCodigoEditar">🎲</button>

        </div>
      </div>

      <div class="control-group">
        <label class="control-label">DNI</label>
        <div class="controls" style="display:flex; gap:5px;">
          
          <input v-model="formulario.dni">

          <button type="button" @click="generarDniEditar">🎲</button>

        </div>
      </div>      

      <div class="control-group">
        <label class="control-label">Google UID</label>
        <div class="controls" style="display:flex; gap:5px;">
          
          <input v-model="formulario.google_uid" class="input-xxlarge">

          <button type="button" class="btn btn-mini" @click="generarUidEditar">
            🎲
          </button>

        </div>
      </div>

          <div class="control-group">

              <label class="control-label">
                  URL Perfil
              </label>

              <div class="controls">

                  <div style="display:flex; gap:5px; margin-bottom:10px;">

                      <input
                          type="text"
                          v-model="formulario.img_perfil"
                          class="input-xxlarge"
                      >

                      <button
                          type="button"
                          class="btn"
                          @click="generarAvatarRandom"
                      >
                          🎲
                      </button>

                  </div>

                  <!-- PREVIEW -->
                  <div
                      v-if="avatarPreview"
                      style="margin-bottom:10px;"
                  >
                      <img
                          :src="avatarPreview"
                          style="
                              width:120px;
                              height:120px;
                              border-radius:10px;
                              object-fit:cover;
                              border:1px solid #ccc;
                          "
                      >
                  </div>

                  <!-- GUARDAR -->
                  <button
                      type="button"
                      class="btn btn-success"
                      @click="guardarAvatarBunny"
                  >
                      Guardar en Bunny
                  </button>

              </div>

          </div>

      <div class="control-group">
        <label class="control-label">Sobrenombre</label>
        <div class="controls">
          <input v-model="formulario.sobrenombre" class="input-xxlarge">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Nombres y Apellidos</label>
        <div class="controls">
          <input v-model="formulario.nombres_apellidos" class="input-xxlarge">
        </div>
      </div>   

        <div class="control-group">
            <label class="control-label">Celular</label>

            <div class="controls">

                <div style="display:flex; gap:5px; align-items:center;">

                    <input
                        type="text"
                        v-model="formulario.celular"
                        class="input-medium"
                    >

                    <button
                        type="button"
                        class="btn"
                        @click="generarCelularRandom"
                    >
                        🎲
                    </button>

                </div>

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
          <input type="date" v-model="formulario.fecha_nacimiento" class="input-medium">
        </div>
      </div>

      <div class="control-group">
        <label class="control-label">Tipo Usuario</label>
        <div class="controls">
          <v-select
            :options="tipousuarios"
            label="descripcion"
            v-model="formulario.tipoxusu_obj"
          />
        </div>
      </div>

      <div class="control-group">
          <label class="control-label">Asignar Negocio:</label>
          <div class="controls">
              <v-select 
                  :options="negociosValidados" 
                  label="nombre" 
                  v-model="negocioSeleccionado"
                  placeholder="Seleccione un negocio..."
              >
                  <template #no-options="{ search, searching, loading }">
                      No hay negocios disponibles.
                  </template>
              </v-select>
              <span class="help-block" style="font-size: 0.8em;">
                  Al guardar, el usuario se vinculará a este negocio.
              </span>
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


  </div>
</div>

<script>
/* ======================================
   🔥 BLOQUEAR UI
====================================== */
function bloquearUI(mensaje = 'Procesando...') {

    $.blockUI({

        message:
            '<h4 style="color:#fff;">' +
            mensaje +
            '</h4>',

        css: {
            border: 'none',
            padding: '15px',
            backgroundColor: '#000',
            borderRadius: '10px',
            opacity: .7,
            color: '#fff',
            zIndex: 2000
        },

        overlayCSS: {
            backgroundColor: '#000',
            opacity: 0.6,
            zIndex: 1999
        }

    });
}

/* ======================================
   🔥 DESBLOQUEAR UI
====================================== */
function desbloquearUI() {
    $.unblockUI();
}

new Vue({
  el: '#appUsuario',
  data: {
    apphost: apphost,
    usuarios: [],
    avatarPreview: '',
    tipousuarios: [],
    nuevo: {
      cod_usuario: '',
      dni:'', // 🔥 nuevo
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
    formulario:{
      dni:'', // 🔥 nuevo
      tipoxusu_obj: null
    },
    detalle: {},
    buscarSobrenombre: '',
    resultados: [],
    negociosValidados: [],
    negocioSeleccionado: null,
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

          this.$nextTick(() => {
            this.initDataTable('#tablaUsuarios');

            setTimeout(() => { // 🔥 MUY IMPORTANTE
              agregarScrollBotones($('#tablaUsuarios'));
            }, 200);
          });

        })
        .catch(err => {
          console.error('Error al listar usuarios:', err);
          if (err.response && err.response.status === 401) {
            apprise('Sesión expirada, por favor vuelve a iniciar sesión');
          }
        });
    },
    generarCelularRandom() {

        const numero =
            '9' +
            Math.floor(
                10000000 + Math.random() * 90000000
            )

        this.formulario.celular = numero
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

        /* ======================================
           DESTRUIR SI EXISTE
        ====================================== */

        if (

          $.fn.DataTable.isDataTable(sel)

        ) {

          $(sel)
            .DataTable()
            .destroy();

        }

        /* ======================================
           REINICIALIZAR
        ====================================== */

        $(sel).DataTable({

          destroy: true,

          retrieve: true,

          language:
            (
              typeof dt_language !== 'undefined'
                ? dt_language
                : undefined
            ),

          scrollX: true,

          dom: 'frtip',

          order: [[0,'desc']]

        });

      });

    },
    abrirModalCrear() {
      this.nuevo = {
        cod_usuario: '',
        dni: '', // 🔥 AGREGAR
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
    crearNegocioFantasma(u){

      apprise(

        '¿Crear negocio fantasma para este usuario?',

        {

          'verify': true

        },

        async (r) => {

          if(!r){
            return;
          }

          try {

            bloquearUI(
              'Creando negocio fantasma...'
            );

            const response = await axios.post(

              this.apphost +
              '/Pfnf/tiendaFantasma',

              {

                usu_id: u.usu_id

              }

            );

            desbloquearUI();

            if(
              response.data.status === 'ok'
            ){

              apprise(
                'Negocio fantasma creado correctamente'
              );

              this.obtenerUsuarios();

            }else{

              apprise(
                response.data.msg ||
                'No se pudo crear'
              );

            }

          } catch(e){

            desbloquearUI();

            console.error(e);

            apprise(
              'Error al crear negocio fantasma'
            );

          }

        }

      );

    },


    clave12(u){

      apprise(

        '¿Asignar clave 12qw12 a este usuario?',

        {

          'verify': true

        },

        async (r) => {

          if(!r){
            return;
          }

          try {

            const response = await axios.post(

              this.apphost +
              '/usuario/clave12',

              {

                usu_id: u.usu_id

              }

            );

            if(
              response.data.status === 'ok'
            ){

              apprise(
                'Clave actualizada'
              );

            }else{

              apprise(
                response.data.msg ||
                'No se pudo actualizar'
              );

            }

          } catch(e){

            console.error(e);

            apprise(
              'Error al actualizar clave'
            );

          }

        }

      );

    },    

    reiniciarUsuario(u){

      apprise(

        '¿Deseas reiniciar este usuario?',

        {

          'verify': true

        },

        async (r) => {

          if(!r){
            return;
          }

          try {

            const response = await axios.post(

              this.apphost +
              '/usuario/reiniciar',

              {

                usu_id: u.usu_id

              }

            );

            if(
              response.data.status === 'ok'
            ){

              apprise(
                'Usuario reiniciado correctamente'
              );

              this.obtenerUsuarios();

            }else{

              apprise(
                response.data.msg ||
                'No se pudo reiniciar'
              );

            }

          } catch(e){

            console.error(e);

            apprise(
              'Error al reiniciar usuario'
            );

          }

        }

      );

    },  
     /* ======================================
       🎲 GENERAR AVATAR RANDOM
    ====================================== */
    generarAvatarRandom() {

        bloquearUI('Generando avatar...');

        const seed =
            Date.now() +
            '_' +
            Math.floor(Math.random() * 999999);

        const url =
            'https://i.pravatar.cc/300?u=' +
            seed;

        // 🔥 precargar imagen
        const img = new Image();

        img.onload = () => {

            this.avatarPreview = url;

            desbloquearUI();
        };

        img.onerror = () => {

            desbloquearUI();

            apprise(
                'No se pudo generar avatar'
            );
        };

        img.src = url;
    },

    /* ======================================
       ☁ SUBIR A BUNNY
    ====================================== */
    async guardarAvatarBunny() {

      try {

          if (!this.avatarPreview) {

              apprise(
                  'Primero genera un avatar'
              );

              return;
          }

          bloquearUI(
              'Subiendo avatar...'
          );

          const formData =
              new FormData();

          formData.append(
              'url',
              this.avatarPreview
          );

          formData.append(
              'usu_id',
              this.formulario.usu_id
          );


          const r = await fetch(

              this.apphost +
              '/usuario/subirAvatarBunny',

              {
                  method: 'POST',
                  body: formData
              }
          );

          const data =
              await r.json();

          desbloquearUI();

          if (data.success) {

          /* ======================================
             🔥 FORMULARIO
          ====================================== */

          this.formulario.img_perfil =
              data.url;

          this.avatarPreview =
              data.url;

          /* ======================================
             🔥 ACTUALIZAR TABLA LOCAL
          ====================================== */

          const idx =
              this.usuarios.findIndex(

                  u => Number(u.usu_id) ===
                       Number(this.formulario.usu_id)
              );

          if (idx !== -1) {

              this.usuarios[idx].img_perfil =
                  data.url;
          }

          /* ======================================
             🔥 REFRESCAR DATATABLE
          ====================================== */

          this.$nextTick(() => {

              if (
                  $.fn.DataTable.isDataTable(
                      '#tablaUsuarios'
                  )
              ) {

                  $('#tablaUsuarios')
                      .DataTable()
                      .destroy();
              }

              $('#tablaUsuarios').DataTable({

                  language:
                      (typeof dt_language !== 'undefined'
                          ? dt_language
                          : undefined),

                  scrollX: true
              });

          });

          /* ======================================
             🔥 ALERTA
          ====================================== */

          apprise(
              'Avatar subido correctamente'
          );
      } else {

              apprise(
                  data.error ||
                  'Error al subir'
              );
          }

      } catch (e) {

          desbloquearUI();

          console.error(e);

          apprise(
              'Error de conexión'
          );
      }
  },

crearUsuarioAutomatico(){

  $.blockUI({

    message:'Creando usuario automático...'

  });

  axios.post(

    this.apphost + '/NTol/usuarioAutomatico'

  )
  .then(r=>{

    if(!r.data.success){

      apprise(
        r.data.msg || 'Error'
      );

      return;
    }

    const u = r.data.usuario;

    /* ======================================
       ARRAY VUE
    ====================================== */

    this.usuarios.unshift(u);

    /* ======================================
       DATATABLE
    ====================================== */

    const dt = $('#tablaUsuarios')
      .DataTable();

    dt.row.add([

      `<img
        src="${u.img_perfil}"
        class="avatar-mini"
      >`,

      u.usu_id,

      u.cod_usu,

      u.google_uid,

      u.sobrenombre,

      u.nombres_apellidos,

      u.celular,

      u.dni,

      u.fecha_creacion,

      u.rol_nombre,

      this.getTipoDescripcion(
        u.tipoxusu_id
      ),

      u.negocio_nombre,

      `
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
              class="editar-usuario"
              data-id="${u.usu_id}">
              Editar
            </a>
          </li>

          <li>
            <a href="#"
              class="detalle-usuario"
              data-id="${u.usu_id}">
              Detalle
            </a>
          </li>

          <li>
            <a href="#"
              class="eliminar-usuario"
              data-id="${u.usu_id}">
              Liquidar
            </a>
          </li>

        </ul>

      </div>
      `

    ]).draw(false);

    $('#modalCrearUsuario')
      .modal('hide');

    apprise(
      'Usuario automático creado 🚀'
    );

  })
  .catch(e=>{

    apprise(

      e.response?.data?.msg ||

      'Error al crear usuario'

    );

  })
  .finally(()=>{

    $.unblockUI();

  });

},

    crearUsuario() {
      axios.post(this.apphost + '/xico/usu/crear', this.nuevo)
        .then((r) => {

          $('#modalCrearUsuario').modal('hide');

          // 🔥 crear objeto nuevo (igual al datatable)
          const nuevoUsuario = {
            usu_id: r.data.usu_id,
            cod_usu: this.nuevo.cod_usuario,
            dni: this.nuevo.dni,
            avatar_mini: this.nuevo.img_perfil || '',
            sobrenombre: this.nuevo.sobrenombre,
            celular: this.nuevo.celular,
            provincia: this.nuevo.provincia,
            fecha_creacion: new Date().toISOString().slice(0,19).replace('T',' '),
            rol_nombre: '—',            
            tipoxusu_id: this.nuevo.tipoxusu_id?.tipoxusu_id || null,
            negocio_nombre: '—'
          };

          // 🔥 agregar al array Vue
          this.usuarios.unshift(nuevoUsuario);

          // 🔥 agregar al DataTable sin recargar
          const dt = $('#tablaUsuarios').DataTable();

          dt.row.add([
            `<img src="${nuevoUsuario.avatar_mini}" class="avatar-mini">`,
            nuevoUsuario.usu_id,
            nuevoUsuario.cod_usu,
            nuevoUsuario.google_uid,
            nuevoUsuario.sobrenombre,
            nuevoUsuario.celular,
            nuevoUsuario.provincia,
            nuevoUsuario.fecha_creacion,
            nuevoUsuario.rol_nombre,
            this.getTipoDescripcion(nuevoUsuario.tipoxusu_id),
            nuevoUsuario.negocio_nombre,
            this.renderOpciones(nuevoUsuario.usu_id)
          ]).draw(false);

        });
    },

    renderOpciones(id){
      return `
        <div class="btn-group">
          <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
            ⚙ <span class="caret"></span>
          </button>
          <ul class="dropdown-menu">
            <li><a href="#" onclick="editarUsuario(${id})">Editar</a></li>
          </ul>
        </div>
      `;
    },
    abrirModalEditar(u) {

      this.formulario = {
        usu_id: u.usu_id,

        cod_usuario: u.cod_usu,   // 🔥 map correcto
        dni: u.dni || '',         // 🔥 importante
        google_uid: u.google_uid || '',
        nombres_apellidos: u.nombres_apellidos || '',
        img_perfil: u.img_perfil || '',
        sobrenombre: u.sobrenombre || '',
        celular: u.celular || '',
        provincia: u.provincia || '',
        fecha_nacimiento: u.fecha_nacimiento || '',

        tipoxusu_id: Number(u.tipoxusu_id) || null,

        is_activo: u.is_activo == 1,
        is_premium: u.is_premium == 1,
        fecha_fin_premium: u.fecha_fin_premium || ''
      };

      this.negocioSeleccionado = this.negociosValidados.find(
          n => Number(n.neg_id) === Number(u.neg_id)
      ) || null;

      this.formulario.tipoxusu_obj = this.tipousuarios.find(
        t => t.tipoxusu_id == u.tipoxusu_id
      ) || null;

      $('#modalEditarUsuario').modal('show');
    },


    async iniciarSesionUsuario(u){

      apprise(

        '¿Iniciar sesión como este usuario?',

        {
          'verify': true
        },

        async (r) => {

          if(!r){
            return;
          }

          try {

            bloquearUI(
              'Iniciando sesión...'
            );

            const response = await axios.post(

              this.apphost +
              '/YfrW/loginNeg',

              {

                usu_id: u.usu_id

              }

            );

            desbloquearUI();

            if(
              response.data.res === 'ok'
            ){

              window.location.href =
                this.apphost +
                '/admin/dash';

            }else{

              apprise(

                response.data.msg ||

                'No se pudo iniciar sesión'

              );

            }

          } catch(e){

            desbloquearUI();

            console.error(e);

            apprise(
              'Error al iniciar sesión'
            );

          }

        }

      );

    },    
    guardarEdicion() {
      // Extraemos el ID si negocioSeleccionado es un objeto, 
      // o lo usamos directamente si ya es un ID.
      const idFinalNegocio = (this.negocioSeleccionado && typeof this.negocioSeleccionado === 'object') 
                             ? this.negocioSeleccionado.neg_id 
                             : this.negocioSeleccionado;

      const payload = {
        ...this.formulario,
        tipoxusu_id: this.formulario.tipoxusu_obj?.tipoxusu_id,
        neg_id: idFinalNegocio // 🔥 Ahora enviamos solo el número "10"
      };

      axios.post(this.apphost + '/xico/usu/editar', payload)
        .then(() => {
          $('#modalEditarUsuario').modal('hide');
          this.obtenerUsuarios();
          apprise("Actualizado con éxito");
        });
    },

    eliminarUsuario(u) {

        apprise(

          `Liquidar usuario ${u.usu_id}?`,

          { confirm:true },

          r => {

            if(!r){
              return;
            }

            axios.post(

              this.apphost + '/usuario/liquidar',

              {

                usu_id:u.usu_id

              }

            )
            .then(()=>{

              /* ======================================
                 BUSCAR FILA REAL DEL DATATABLE
              ====================================== */

              $('#tablaUsuarios tbody tr').each(function(){

                const $tr = $(this);

                const idFila = $.trim(

                  $tr.find('td:eq(1)').text()

                );

                if(

                  Number(idFila) === Number(u.usu_id)

                ){

                  /* ======================================
                     REEMPLAZAR CELDAS
                  ====================================== */

                  $tr.find('td:eq(2)').html('—');
                  $tr.find('td:eq(3)').html('—');
                  $tr.find('td:eq(4)').html('—');
                  $tr.find('td:eq(5)').html('—');
                  $tr.find('td:eq(6)').html('—');
                  $tr.find('td:eq(7)').html('—');
                  $tr.find('td:eq(8)').html('—');
                  $tr.find('td:eq(9)').html('—');
                  $tr.find('td:eq(10)').html('—');
                  $tr.find('td:eq(11)').html('—');

                  /* ======================================
                     AVATAR
                  ====================================== */

                  $tr.find('td:eq(0)').html(`

                    <div class="avatar-mini">

                      <img
                        src="https://barsi-img.b-cdn.net/recursos/logo-regentis.png"
                      >

                    </div>

                  `);

                  /* ======================================
                     OPCIONES
                  ====================================== */

                  $tr.find('td:eq(12)').html('—');

                  /* ======================================
                     EFECTO VISUAL
                  ====================================== */

                  $tr.css({

                    opacity: 0.45,
                    background: '#f5f5f5'

                  });

                }

              });

              apprise(
                'Usuario liquidado'
              );

            })
            .catch(e=>{

              apprise(

                e.response?.data?.msg ||

                'Error al liquidar'

              );

            });

          }

        );

      },

    generarCodigo() {
        this.nuevo.cod_usuario = 'ADM' + this.randomDigits(4);
      },

      generarCodigoEditar(){
        this.formulario.cod_usuario = 'ADM' + this.randomDigits(4);
      },

      generarDni(){
        console.log("gen dni");
        this.nuevo.dni = this.randomDniFake();
      },

      generarDniEditar(){
        this.formulario.dni = this.randomDniFake();
      },

      generarUid(){
        this.nuevo.google_uid = this.randomUid();
      },

      generarUidEditar(){
        this.formulario.google_uid = this.randomUid();
      },

      randomUid(){
        const letras = 'ABCDEFGHIJKLMNPQRSTUVWXYZ'; // sin O

        const letraIni = letras[Math.floor(Math.random()*letras.length)];
        const letraFin = letras[Math.floor(Math.random()*letras.length)];

        let numeros = '';
        for(let i=0;i<6;i++){
          numeros += Math.floor(Math.random()*10);
        }

        return (letraIni + numeros + letraFin).toUpperCase();
      },

      // 🔥 helpers
      randomDigits(n){
        let s = '';
        for(let i=0;i<n;i++){
          s += Math.floor(Math.random()*10);
        }
        return s;
      },

      randomDniFake(){
        const letras = 'ABCDEFGHIJKLMNPQRSTUVWXYZ'; // sin O

        const letraIni = letras[Math.floor(Math.random()*letras.length)];
        const letraFin = letras[Math.floor(Math.random()*letras.length)];

        const numero = this.randomDigits(8);

        return (letraIni + numero + letraFin).toUpperCase();
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

    nuevoNegocio(u){

      apprise(

        '¿Enviar botón de nuevo negocio a este usuario?',

        {

          'verify': true

        },

        async (r) => {

          if(!r){
            return;
          }

          try {

            const response = await axios.post(

              this.apphost +
              '/usuario/nuevoNegocio',

              {

                usu_id: u.usu_id

              }

            );

            if(
              response.data.status === 'ok'
            ){

              apprise(
                'Botón enviado correctamente'
              );

            }else{

              apprise(
                response.data.msg ||
                'No se pudo enviar'
              );

            }

          } catch(e){

            console.error(e);

            apprise(
              'Error al enviar botón'
            );

          }

        }

      );

    },    

    async cargarNegociosValidados() {
          try {
            const r = await axios.get(this.apphost + '/xico/negocios/validados');
            this.negociosValidados = r.data.map(n => ({
                neg_id: Number(n.neg_id), // 🔥 Nos aseguramos de que el ID sea número aquí también
                nombre: n.nombre
            }));
          } catch (err) {
            console.error("Error cargando negocios:", err);
          }
    },

    toggleFantasma(u) {

    const accion =

        parseInt(u.is_fantasma) === 1

            ? 'desactivar modo fantasma'

            : 'activar modo fantasma';

    apprise(

        '¿Deseas ' +

        accion +

        ' para este usuario?',

        {

            verify: true

        },

        (res) => {

            if(!res){

                return;
            }

            bloquearUI(

                'Actualizando usuario...'

            );

            axios.post(

                this.apphost +

                '/IS54/toggleIsFantasma',

                {

                    usu_id: u.usu_id

                }

            )

            .then((response) => {

                desbloquearUI();

                if(

                    response.data.res !== 'ok'

                ){

                    apprise(

                        response.data.msg ||

                        'No se pudo actualizar'

                    );

                    return;
                }

                u.is_fantasma =

                    parseInt(

                        response.data.is_fantasma

                    );

                apprise(

                    response.data.msg

                );

            })

            .catch((error) => {

                desbloquearUI();

                console.error(error);

                apprise(

                    'No se pudo actualizar el usuario'

                );

            });

        }

    );

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
    this.cargarNegociosValidados();
  }
});
</script>
<style>
  .avatar-mini {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      overflow: hidden; /* 🔥 ESTO ES LO QUE TE FALTA */
  }

  .avatar-mini img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
  }
</style>

