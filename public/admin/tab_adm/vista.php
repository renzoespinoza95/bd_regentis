<div id="appAdmin" class="row-fluid">
  <!-- este es mi frontend usando boostrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
  <div class="span12">

    <h2>Administradores</h2>

    <button class="btn btn-success" @click="nuevoAdmin">
      <i class="icon-plus icon-white"></i> Nuevo
    </button>

    <table id="tablaAdmins" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Cuenta</th>
          <th>Clave</th>
          <th>Rol</th>
          <th>Tipo Usuario</th>
          <th>Negocio</th>
          <th>Activo</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="a in admins">

          <td>{{ a.usu_id }}</td>
          <td>{{ a.nombres_apellidos }}</td>
          <td>{{ a.sobrenombre }}</td>
          <td>{{ a.clavel }}</td>
          <td>{{ a.rol }}</td>
          <td>{{ a.tipoxusu || '—' }}</td>
          <td>{{ a.negocio || '—' }}</td>
          <td>
            <span class="label"
            :class="a.is_activo==1?'label-success':'label-important'">
            {{ a.is_activo==1?'Sí':'No' }}
            </span>
          </td>

          <td>
            <button class="btn btn-mini"
            @click="editar(a)">Editar</button>

            <button class="btn btn-mini btn-danger"
            @click="eliminar(a)">Desactivar</button>
          </td>

        </tr>
      </tbody>
    </table>


    <!-- MODAL -->
    <div class="modal hide fade" id="modalAdmin">

      <div class="modal-header">
        <h3>{{ form.usu_id?'Editar':'Nuevo' }}</h3>
      </div>

      <div class="modal-body">

        <label>Nombres y apellidos</label>
        <input class="input-xxlarge"
        v-model="form.nombres_apellidos">

        <label>Nombre de usuario</label>
        <input class="input-xxlarge"
        v-model="form.sobrenombre">

        <label>Clave</label>
        <div style="display:flex; align-items:center; gap:5px;">

          <input class="input-large"
          :type="mostrarClave ? 'text' : 'password'"
          v-model="form.clavel">

          <button class="btn btn-mini"
          type="button"
          @click="toggleClave">

            <i class="icon-eye"
            :class="mostrarClave ? 'icon-eye-open' : 'icon-eye-close'"></i>

          </button>

        </div>


        <label>Rol</label>
        <select v-model="form.rol_id"
        class="input-xlarge">

          <option disabled value="">
          -- Seleccione --
          </option>

          <option
          v-for="t in roles"
          :value="t.rol_id">

          {{ t.nombre }}

          </option>

        </select>

        <label>Tipo Usuario</label>
        <select v-model="form.tipoxusu_id" class="input-xlarge">
          <option disabled value="">-- Seleccione --</option>
          <option v-for="t in tiposUsuario" :value="t.tipoxusu_id">
            {{ t.descripcion }}
          </option>
        </select>

        <label>Negocio</label>

        <v-select
          :options="negocios"
          label="nombre"
          v-model="selectedNegocio"
          placeholder="Seleccione negocio">
        </v-select>       


        <label>
          <input type="checkbox"
          v-model="form.is_activo">
          Activo
        </label>

      </div>


      <div class="modal-footer">

        <button class="btn btn-primary"
        @click="guardar">

        Guardar

        </button>

        <button class="btn"
        data-dismiss="modal">

        Cancelar

        </button>

      </div>

    </div>

  </div>
</div>


<script>
Vue.component('v-select', VueSelect.VueSelect);
new Vue({

  el:'#appAdmin',

  data:{

    apphost: apphost,
    mostrarClave:false,
    admins:[],
    tiposUsuario:[],
    negocios:[],
    roles:[],
    selectedNegocio: null,
    form:{}

  },


  methods:{

    listar(){

      axios.get(`${this.apphost}/JXEc/admin/listar`)
      .then(r=>{

        this.admins = r.data;

        this.$nextTick(()=>{

          if ($.fn.DataTable.isDataTable('#tablaAdmins')) {
            $('#tablaAdmins').DataTable().destroy();
          }

          $('#tablaAdmins').DataTable({
                language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });

        });

      });

    },

    nuevoAdmin(){

      this.form={
        is_activo:1,
        rol_id:null
      };

      $('#modalAdmin').modal('show');

    },

    editar(a){

      this.form = JSON.parse(JSON.stringify(a));

      // 🔥 esperar que negocios estén cargados
      if (!this.negocios.length) {

        this.cargarNegocios().then(() => {
          this.setNegocioSeleccionado(a);
        });

      } else {
        this.setNegocioSeleccionado(a);
      }

      $('#modalAdmin').modal('show');

    },

    setNegocioSeleccionado(a){

      this.selectedNegocio = this.negocios.find(
        n => n.neg_id == a.neg_id
      ) || null;

    },    

    toggleClave(){
      this.mostrarClave = !this.mostrarClave;
    },


    cargarRoles(){

      axios.get(`${this.apphost}/rol-admin/listar`)
      .then(r => this.roles = r.data);

    },


    guardar(){

      const url = this.form.usu_id
      ? '/admin/editar'
      : '/admin/crear';

      // 🔥 AQUÍ ESTÁ LA CLAVE
      this.form.neg_id = this.selectedNegocio
        ? this.selectedNegocio.neg_id
        : null;

      console.log('NEG_ID FINAL 👉', this.form.neg_id);
      console.log('FORM 👉', this.form);

      axios.post(this.apphost+url,this.form)
      .then(()=>{

        $('#modalAdmin').modal('hide');
        this.listar();

      });

    },
    cargarTiposUsuario(){
      axios.get(`${this.apphost}/JXEc/tipoxusu/listar`)
      .then(r => this.tiposUsuario = r.data);
    },

    cargarNegocios(){
      axios.get(`${this.apphost}/JXEc/neg/listar`)
      .then(r => this.negocios = r.data);
    },

    eliminar(a){

      if(!confirm('¿Desactivar administrador?')) return;

      axios.post(`${this.apphost}/admin/eliminar`,
      { usu_id:a.usu_id })
      .then(()=>this.listar());

    }

  },


  mounted(){

    this.listar();
    this.cargarRoles();
    this.cargarTiposUsuario();
    this.cargarNegocios();
  }

});

</script>