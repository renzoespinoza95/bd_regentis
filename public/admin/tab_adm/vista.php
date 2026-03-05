<div id="appAdmin" class="row-fluid">
  <!-- este es mi frontend usando boostrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
  <div class="span12">

    <h2>Administradores</h2>

    <button class="btn btn-success" @click="nuevoAdmin">
      <i class="icon-plus icon-white"></i> Nuevo
    </button>

    <table class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Cuenta</th>
          <th>Clave</th>
          <th>Rol</th>
          <th>Activo</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="a in admins">

          <td>{{ a.usu_id }}</td>
          <td>{{ a.nombres_apellidos }}</td>
          <td>{{ a.email }}</td>
          <td>{{ a.clavel }}</td>
          <td>{{ a.rol }}</td>

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
        v-model="form.email">

        <label>Clave</label>
        <input class="input-large"
        type="password"
        v-model="form.clavel">


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

new Vue({

  el:'#appAdmin',

  data:{

    apphost: apphost,

    admins:[],

    roles:[],

    form:{}

  },


  methods:{


    listar(){

      axios.get(`${this.apphost}/admin/listar`)
      .then(r=>this.admins=r.data);

    },


    nuevoAdmin(){

      this.form={
        is_activo:1,
        rol_id:null
      };

      $('#modalAdmin').modal('show');

    },


    editar(a){

      this.form=JSON.parse(JSON.stringify(a));

      $('#modalAdmin').modal('show');

    },


    cargarRoles(){

      axios.get(`${this.apphost}/rol-admin/listar`)
      .then(r => this.roles = r.data);

    },


    guardar(){

      const url = this.form.usu_id
      ? '/admin/editar'
      : '/admin/crear';

      axios.post(this.apphost+url,this.form)
      .then(()=>{

        $('#modalAdmin').modal('hide');

        this.listar();

      });

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

  }

});

</script>