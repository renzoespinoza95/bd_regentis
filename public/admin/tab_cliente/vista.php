<!-- =========================================================
     CLIENTES (Bootstrap 2.3.2 / Vue2 / Axios / DataTables)
========================================================= -->

<div class="row-fluid" id="appClientes">

  <div class="span12">

    <!-- =====================================
         HEADER
    ====================================== -->

    <div class="titulo-fijo clearfix">

      <div style="float:left;">
        <h2 style="margin:0;">Clientes</h2>
      </div>

      <div class="btn-group pull-right">

        <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">

          <i class="fa fa-users"></i>
          <span class="caret"></span>

        </button>

        <ul class="dropdown-menu pull-right">

          <li>
            <a href="#" @click.prevent="abrirModalCrearCliente">
              <i class="fa fa-plus"></i>
              Nuevo Cliente
            </a>
          </li>

          <li>
            <a href="#" @click.prevent="crear5Fantasmas">
              👻 5 Fantasmas
            </a>
          </li>

        </ul>

      </div>

    </div>

    <!-- =====================================
         TABLA
    ====================================== -->

    <div class="span12 tabla_esp_sup">

      <table
        id="tablaClientes"
        class="table table-bordered table-condensed"
      >

        <thead>

          <tr>

            <th>ID</th>
            <th>Cliente</th>
            <th>DNI</th>
            <th>Celular</th>
            <th>Email</th>
            <th>Activo</th>
            <th>Acciones</th>

          </tr>

        </thead>

        <tbody></tbody>

      </table>

    </div>

    <!-- =====================================
         MODAL CREAR / EDITAR
    ====================================== -->

    <div
      id="modalCliente"
      class="modal hide fade fullscreen"
      tabindex="-1"
    >

      <div class="modal-header">

        <button
          type="button"
          class="close"
          data-dismiss="modal"
        >×</button>

        <h3>
          {{ formCliente.cliente_id ? 'Editar Cliente' : 'Nuevo Cliente' }}
        </h3>

      </div>

      <div class="modal-body">

        <div class="control-group">

          <label class="control-label">
            Nombres
          </label>

          <div class="controls">

            <input
              v-model="formCliente.nombres_apellidos"
              class="input-xxlarge"
            >

          </div>

        </div>

        <div class="control-group">

          <label class="control-label">
            DNI
          </label>

          <div class="controls">

            <input
              v-model="formCliente.dni"
              class="input-large"
            >

          </div>

        </div>

        <div class="control-group">

          <label class="control-label">
            Celular
          </label>

          <div class="controls">

            <input
              v-model="formCliente.celular"
              class="input-large"
            >

          </div>

        </div>

        <div class="control-group">

          <label class="control-label">
            Email
          </label>

          <div class="controls">

            <input
              v-model="formCliente.email"
              class="input-xxlarge"
            >

          </div>

        </div>

        <div class="control-group">

          <label class="control-label">
            Dirección
          </label>

          <div class="controls">

            <textarea
              v-model="formCliente.direccion"
              class="input-xxlarge"
            ></textarea>

          </div>

        </div>

        <div class="control-group">

          <label class="control-label">
            Activo
          </label>

          <div class="controls">

            <select
              v-model="formCliente.is_activo"
              class="input-small"
            >

              <option :value="1">SI</option>
              <option :value="0">NO</option>

            </select>

          </div>

        </div>

      </div>

      <div class="modal-footer">

        <button
          class="btn btn-primary"
          @click="guardarCliente"
        >

          Guardar

        </button>

        <button
          class="btn"
          data-dismiss="modal"
        >

          Cancelar

        </button>

      </div>

    </div>

    <!-- =====================================
         MODAL YAPLIN
    ====================================== -->

    <div
      id="modalYaplin"
      class="modal hide fade fullscreen"
      tabindex="-1"
    >

      <div class="modal-header">

        <button
          type="button"
          class="close"
          data-dismiss="modal"
        >×</button>

        <h3>

          Yaplin del Cliente

        </h3>

      </div>

      <div class="modal-body">

        <div
          v-if="listaYaplin.length<=0"
          class="alert"
        >

          No existen imágenes

        </div>

        <div
          v-for="y in listaYaplin"
          :key="y.yaplin_id"
          style="
            margin-bottom:15px;
            border:1px solid #ddd;
            padding:10px;
          "
        >

          <div style="margin-bottom:10px;">

            <b>{{ y.billetera }}</b>

            -
            {{ y.monto }}

          </div>

          <img
            :src="y.imagen_url"
            style="
              width:100%;
              max-width:400px;
              border-radius:6px;
            "
          >

        </div>

      </div>

      <div class="modal-footer">

        <button
          class="btn"
          data-dismiss="modal"
        >

          Cerrar

        </button>

      </div>

    </div>

  </div>

</div>
<script>
Vue.component(
  'v-select',
  VueSelect.VueSelect
);

const appClientes = new Vue({

  el:'#appClientes',

  data:{

    apphost:
      (
        typeof apphost !== 'undefined'
      )
      ? apphost
      : '',

    clientes:[],

    dt:null,

    listaYaplin:[],

    formCliente:{

      cliente_id:0,

      nombres_apellidos:'',

      dni:'',

      celular:'',

      email:'',

      direccion:'',

      is_activo:1

    }

  },

  methods:{

    bloquear(msg){

      $.blockUI({

        message:`<h4>${msg}</h4>`,

        css:{
          border:'none',
          padding:'15px',
          background:'#000',
          opacity:.6,
          color:'#fff'
        }

      })

    },

    /* =====================================
       LISTAR
    ====================================== */

    listarClientes(){

      this.bloquear(
        'Cargando clientes...'
      )

      axios
      .get(
        `${this.apphost}/L45L/clientes/listar`
      )
      .then(r=>{

        this.clientes =
          r.data.data || []

        this.$nextTick(()=>{

          if(!this.dt){

            this.dt =
              $('#tablaClientes')
              .DataTable({

                language:
                  (
                    typeof dt_language
                    !== 'undefined'
                  )
                  ? dt_language
                  : undefined,

                scrollX:true,

                dom:'frtip',

                order:[[0,'desc']]

              })

            const self=this

            $('#tablaClientes tbody')

            .on(
              'click',
              'a.editar-cli',
              function(e){

                e.preventDefault()

                const id =
                  $(this).data('id')

                const row =
                  self.clientes.find(
                    x=>
                    parseInt(x.cliente_id)
                    ===
                    parseInt(id)
                  )

                if(row){

                  self.abrirEditar(row)

                }

              }
            )

            .on(
              'click',
              'a.eliminar-cli',
              function(e){

                e.preventDefault()

                const id =
                  $(this).data('id')

                const row =
                  self.clientes.find(
                    x=>
                    parseInt(x.cliente_id)
                    ===
                    parseInt(id)
                  )

                if(row){

                  self.eliminarCliente(row)

                }

              }
            )

            .on(
  'click',
  'a.reiniciar-cli',
  function(e){

    e.preventDefault()

    const id =

      $(this).data('id')

    const row =

      self.clientes.find(

        x =>

        parseInt(x.cliente_id)

        ===

        parseInt(id)

      )

    if(row){

      self.reiniciarCliente(row)

    }

  }
)

            .on(
              'click',
              'a.yaplin-cli',
              function(e){

                e.preventDefault()

                const id =
                  $(this).data('id')

                self.abrirYaplin(id)

              }
            )

          }

          this.dt.clear()

          this.clientes.forEach(c=>{

            const actions = `

              <div class="btn-group">

                <button
                  class="btn btn-mini dropdown-toggle"
                  data-toggle="dropdown"
                >

                  ⚙
                  <span class="caret"></span>

                </button>

                <ul class="dropdown-menu">

                  <li>
                    <a
                      href="#"
                      class="editar-cli"
                      data-id="${c.cliente_id}"
                    >
                      Editar
                    </a>
                  </li>

                  <li>
                    <a
                      href="#"
                      class="eliminar-cli"
                      data-id="${c.cliente_id}"
                    >
                      Eliminar
                    </a>
                  </li>

                  <li class="divider"></li>

                  <li>
                    <a
                      href="#"
                      class="yaplin-cli"
                      data-id="${c.cliente_id}"
                    >
                      Yaplin
                    </a>
                  </li>
              <li>
    <a
      href="#"
      class="reiniciar-cli"
      data-id="${c.cliente_id}"
    >
      Reiniciar
    </a>
</li>

                </ul>

              </div>

            `

            this.dt.row.add([

              c.cliente_id,

              c.nombres_apellidos || '',

              c.dni || '',

              c.celular || '',

              c.email || '',

              parseInt(c.is_activo)
              ? 'SI'
              : 'NO',

              actions

            ])

          })

          this.dt.draw(false)

        })

      })
      .finally(()=>{

        $.unblockUI()

      })

    },

    /* =====================================
       CREAR
    ====================================== */

    abrirModalCrearCliente(){

      this.formCliente = {

        cliente_id:0,

        nombres_apellidos:'',

        dni:'',

        celular:'',

        email:'',

        direccion:'',

        is_activo:1

      }

      $('#modalCliente')
      .modal('show')

    },

crear5Fantasmas(){

  apprise(

    '¿Deseas crear 5 clientes fantasma?',

    {

      verify:true

    },

    (r)=>{

      if(!r){
        return;
      }

      $.blockUI({

        message:
          '<h4>Creando clientes fantasma...</h4>'

      });

      axios
      .post(

        `${this.apphost}/MBr4/clientesFantasmasSinUsuId`

      )
      .then(res=>{

        if(
          res.data.status == 'ok'
        ){

          apprise(
            'Clientes fantasma creados correctamente 👻'
          );

          this.listarClientes();

        }else{

          apprise(

            res.data.msg ||

            'No se pudieron crear'

          );

        }

      })
      .catch(e=>{

        console.error(e);

        apprise(

          e.response?.data?.msg ||

          'Error al crear clientes'

        );

      })
      .finally(()=>{

        $.unblockUI();

      });

    }

  );

},    

    /* =====================================
       EDITAR
    ====================================== */

    abrirEditar(c){

      this.formCliente = {

        cliente_id:
          parseInt(c.cliente_id),

        nombres_apellidos:
          c.nombres_apellidos || '',

        dni:
          c.dni || '',

        celular:
          c.celular || '',

        email:
          c.email || '',

        direccion:
          c.direccion || '',

        is_activo:
          parseInt(c.is_activo)

      }

      $('#modalCliente')
      .modal('show')

    },

    /* =====================================
       GUARDAR
    ====================================== */

    guardarCliente(){

      if(
        !this.formCliente
        .nombres_apellidos
      ){

        apprise(
          'Escribe nombres'
        )

        return

      }

      const endpoint =
        this.formCliente.cliente_id
        ?
        'editar'
        :
        'crear'

      this.bloquear(
        'Guardando cliente...'
      )

      axios
      .post(

        `${this.apphost}/L45L/clientes/${endpoint}`,

        this.formCliente

      )
      .then(r=>{

        if(
          r.data.status=='ok'
        ){

          $('#modalCliente')
          .modal('hide')

          apprise(
            'Guardado correctamente'
          )

          this.listarClientes()

        }
        else{

          apprise(
            r.data.msg
          )

        }

      })
      .finally(()=>{

        $.unblockUI()

      })

    },

    reiniciarCliente(c){

  apprise(

    '¿Deseas reiniciar este cliente con datos aleatorios?',

    {

      verify:true

    },

    (r)=>{

      if(!r){

        return;

      }

      this.bloquear(

        'Reiniciando cliente...'

      );

      axios

      .post(

        `${this.apphost}/Hc6Y/reiniciarCliente`,

        {

          cliente_id:

            c.cliente_id

        }

      )

      .then(res=>{

        if(

          res.data.status == 'ok'

        ){

          apprise(

            'Cliente reiniciado correctamente'

          );

          this.listarClientes();

        }
        else{

          apprise(

            res.data.msg

          );

        }

      })

      .catch(e=>{

        apprise(

          e.response?.data?.msg ||

          'Error al reiniciar cliente'

        );

      })

      .finally(()=>{

        $.unblockUI();

      });

    }

  );

},

    /* =====================================
       ELIMINAR
    ====================================== */

    eliminarCliente(c){

      apprise(

        `¿Eliminar cliente #${c.cliente_id}?`,

        {confirm:true},

        ok=>{

          if(!ok) return

          this.bloquear(
            'Eliminando cliente...'
          )

          axios
          .post(

            `${this.apphost}/L45L/clientes/eliminar`,

            {

              cliente_id:
                c.cliente_id

            }

          )
          .then(r=>{

            apprise(
              'Cliente eliminado'
            )

            this.listarClientes()

          })
          .finally(()=>{

            $.unblockUI()

          })

        }

      )

    },

    /* =====================================
       YAPLIN
    ====================================== */

    abrirYaplin(cliente_id){

      this.listaYaplin=[]

      this.bloquear(
        'Cargando yaplin...'
      )

      axios
      .get(

        `${this.apphost}/L45L/yaplin/listar`,

        {

          params:{
            cliente_id:cliente_id
          }

        }

      )
      .then(r=>{

        this.listaYaplin =
          r.data.data || []

        $('#modalYaplin')
        .modal('show')

      })
      .finally(()=>{

        $.unblockUI()

      })

    },

    /* =====================================
       5 FANTASMAS
    ====================================== */

    crear5Fantasmas(){

      apprise(

        '¿Crear 5 clientes fantasma?',

        {confirm:true},

        ok=>{

          if(!ok) return

          this.bloquear(
            'Creando clientes...'
          )

          axios
          .post(

            `${this.apphost}/MBr4/clientesFantasmasSinUsuId`

          )
          .then(r=>{

            apprise(
              'Clientes creados'
            )

            this.listarClientes()

          })
          .finally(()=>{

            $.unblockUI()

          })

        }

      )

    }

  },

  mounted(){

    this.listarClientes()

  }

})  
</script>