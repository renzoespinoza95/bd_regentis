<div class="row-fluid" id="appScreen">
  <div class="span12">

<!-- ================= TITULO ================= -->
<div class="titulo-fijo clearfix">
  <div style="float:left;">
    <h2 style="margin:0;">Screens</h2>
  </div>

  <div class="btn-group pull-right">
    <button class="btn btn-info" @click="abrirModalCrearScreen">
      <i class="fa fa-plus"></i> Nuevo screen
    </button>
  </div>
</div>

<!-- ================= TABLA SCREEN ================= -->
<div class="span12 tabla_esp_sup">
  <table id="tablaScreen" class="table table-bordered table-condensed">
    <thead>
      <tr>
        <th>ID</th>
        <th>Rubro</th>
        <th>Tipo Usuario</th>
        <th>Nombre</th>
        <th>Título</th>
        <th>Ruta</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- ================= MODAL SCREEN ================= -->
<div id="modalScreen" class="modal hide fade fullscreen">
  <div class="modal-header">
    <button class="close" data-dismiss="modal">×</button>
    <h3>{{ modoScreen=='crear' ? 'Nuevo Screen' : 'Editar Screen' }}</h3>
  </div>

  <div class="modal-body">

    <div class="control-group">
      <label>Rubro</label>
      <v-select
  multiple
  :options="rubrosOptions"
  label="nombre"
  v-model="formScreen.rubros">
</v-select>
    </div>

    <div class="control-group">
      <label>Tipo Usuario</label>
      <v-select :options="tiposUsuOptions" label="descripcion" v-model="formScreen.tipoxusu_obj"></v-select>
    </div>

    <div class="control-group">
      <label>Nombre</label>
      <input v-model="formScreen.nombre" @input="generarRutaVue" class="input-xxlarge">
    </div>

    <div class="control-group">
      <label>Título</label>
      <input v-model="formScreen.titulo" class="input-xxlarge">
    </div>

    <div class="control-group">
      <label>Ruta Vue</label>
      <input v-model="formScreen.vue_route" class="input-xxlarge" readonly>
    </div>

  </div>

  <div class="modal-footer">
    <button class="btn btn-primary" @click="guardarScreen">Guardar</button>
    <button class="btn" data-dismiss="modal">Cancelar</button>
  </div>
</div>

  </div>
</div>

<script>
Vue.component('v-select', VueSelect.VueSelect);

const appScreen = new Vue({
  el:'#appScreen',

  data:{
    apphost:(typeof apphost!=='undefined'?apphost:''),

    screens:[],
    dtScreen:null,

    rubrosOptions:[],
    tiposUsuOptions:[],

    modoScreen:'crear',
    formScreen:{
      screen_id:null,
      nombre:'',
      titulo:'',
      vue_route:'',
      rubros:[],
      tipoxusu_obj:null
    }
  },

  methods:{

    bloquear(msg){
      $.blockUI({message:`<h4>${msg}</h4>`});
    },

    /* ================= LISTAR ================= */
    listarScreen(){

      this.bloquear('Cargando...');

      axios.get(`${this.apphost}/xoxo/frida/screen/listar`)
      .then(r=>{

        this.screens=r.data.data||[];

        this.$nextTick(()=>{

          if(!this.dtScreen){
            this.dtScreen=$('#tablaScreen').DataTable();

            const self=this;

            $('#tablaScreen tbody')
              .on('click','.edit-screen',function(){
                self.editarScreen($(this).data('id'));
              })
              .on('click','.del-screen',function(){
                self.eliminarScreen($(this).data('id'));
              });
          }

          this.dtScreen.clear();

          this.screens.forEach(s=>{

            const btn=`
            <div class="btn-group">
              <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                ⚙ <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" class="edit-screen" data-id="${s.screen_id}">Editar</a></li>
                <li><a href="#" class="del-screen" data-id="${s.screen_id}">Eliminar</a></li>
              </ul>
            </div>`;

            const rubrosHtml = (s.rubros || [])
              .map(r => `
                <span class="label label-info" style="margin-right:4px;">
                  ${r.icono || ''} ${r.nombre}
                </span>
              `)
              .join('');

            this.dtScreen.row.add([

              s.screen_id,

              rubrosHtml ||

              '<span class="muted">Sin rubros</span>',

              s.tipoxusu_descripcion ||

              '<span class="muted">Sin definir</span>',

              s.nombre || '',

              s.titulo || '',

              s.vue_route || '',

              btn

            ]);
            
          });

          this.dtScreen.draw(false);

        });

      })
      .finally(()=>$.unblockUI());
    },

    cargarRubros(){
      return axios.get(`${this.apphost}/xoxo/frida/rubro/listar`)
      .then(r=>{
        this.rubrosOptions = r.data.data || [];
      });
    },

    cargarTiposUsuario(){
      return axios.get(`${this.apphost}/xoxo/frida/tipoxusu/listar`)
      .then(r=>{
        this.tiposUsuOptions = r.data.data || [];
      });
    },

    abrirModalCrearScreen(){
      this.modoScreen='crear';
      this.formScreen={};
      Promise.all([this.cargarRubros(), this.cargarTiposUsuario()])
      .then(()=>{
        $('#modalScreen').modal('show');
      });
    },

    editarScreen(id){

      const s=this.screens.find(x=>x.screen_id==id);
      if(!s)return;

      this.modoScreen='editar';

      this.formScreen={
        screen_id:s.screen_id,
        nombre:s.nombre,
        titulo:s.titulo,
        vue_route:s.vue_route,
        rubros:[],
        tipoxusu_obj:null
      };

      Promise.all([this.cargarRubros(), this.cargarTiposUsuario()])
      .then(()=>{

        this.formScreen.rubros = s.rubros || [];
        this.formScreen.tipoxusu_obj = this.tiposUsuOptions.find(t=>t.tipoxusu_id==s.tipoxusu_id);

        $('#modalScreen').modal('show');

      });

    },

    guardarScreen(){

      this.bloquear('Guardando...');

      const url = this.modoScreen=='crear'
        ?'/xoxo/frida/screen/crear'
        :'/xoxo/frida/screen/editar';

      axios.post(this.apphost+url,{

        screen_id:this.formScreen.screen_id,

        nombre:this.formScreen.nombre,

        titulo:this.formScreen.titulo,

        vue_route:this.formScreen.vue_route,

        tipoxusu_id:this.formScreen.tipoxusu_obj?.tipoxusu_id,

        rubros:(this.formScreen.rubros || [])
          .map(r=>r.rubro_id)

      })
      .then(()=>{

        $('#modalScreen').modal('hide');

        apprise('Guardado');

      })
      .finally(()=>{

        $.unblockUI();

        this.listarScreen();

      });

    },

    eliminarScreen(id){
      apprise('¿Eliminar?',{confirm:true},ok=>{
        if(!ok)return;

        this.bloquear('Eliminando...');
        axios.post(`${this.apphost}/xoxo/frida/screen/eliminar`,{screen_id:id})
        .finally(()=>{
          $.unblockUI();
          this.listarScreen();
        });
      });
    },

    generarRutaVue(){

      if(!this.formScreen.nombre){
        this.formScreen.vue_route='';
        return;
      }

      let ruta = this.formScreen.nombre
        .toLowerCase()
        .trim()
        .replace(/\s+/g,'_')
        .replace(/[^a-z0-9_]/g,'');

      this.formScreen.vue_route = '/' + ruta;

    }

  },

  mounted(){
    this.listarScreen();
  }

});
</script>