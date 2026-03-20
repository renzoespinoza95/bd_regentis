<div class="row-fluid" id="appModulos">
  <div class="span12">

    <h2>Lista de Módulos</h2>

    <div class="form-actions">

      <button class="btn btn-success" @click="nuevoModulo">
        <i class="icon-plus icon-white"></i> Agregar
      </button>

      <button class="btn btn-info" @click="abrirNegocios">
        <i class="icon-th icon-white"></i> Negocios
      </button>

    </div>

    <table id="tablaModulos" class="table table-bordered table-striped">

      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Descripción</th>
          <th>Acciones</th>
        </tr>
      </thead>

      <tbody></tbody>

    </table>


    <!-- ===========================
    MODAL MODULO
    =========================== -->

    <div class="modal hide fade" id="modalModulo">

      <div class="modal-header">
        <h3>{{ form.modulo_id ? 'Editar módulo' : 'Nuevo módulo' }}</h3>
      </div>

      <div class="modal-body">

        <label>Nombre</label>
        <input class="input-xlarge" v-model="form.nombre">

        <label>Descripción</label>
        <input class="input-xlarge" v-model="form.descripcion">

      </div>

      <div class="modal-footer">

        <button class="btn btn-primary" @click="guardarModulo">Guardar</button>

        <button class="btn" data-dismiss="modal">Cancelar</button>

      </div>

    </div>



    <!-- ===========================
    MODAL NEGOCIOS
    =========================== -->

    <div class="modal hide fade modal-full" id="modalNegocios">

      <div class="modal-header">
        <h3>Negocios</h3>
      </div>

      <div class="modal-body">

        <div class="form-actions">
          <button class="btn btn-success" @click="nuevoNegocio">
            <i class="icon-plus icon-white"></i> Agregar
          </button>
        </div>

        <table id="tablaNegocios" class="table table-bordered table-striped">

          <thead>
            <tr>
              <th>ID</th>
              <th>Código</th>
              <th>Nombre</th>
              <th>Ciudad</th>
              <th>Puesto</th>
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



    <!-- ===========================
    MODAL NEGOCIO
    =========================== -->

    <div class="modal hide fade" id="modalNegocio">

      <div class="modal-header">
        <h3>{{ negocioForm.neg_id ? 'Editar negocio' : 'Nuevo negocio' }}</h3>
      </div>

      <div class="modal-body">

        <label>Código</label>
        <input class="input-xlarge" v-model="negocioForm.cod_neg">

        <label>Nombre</label>
        <input class="input-xlarge" v-model="negocioForm.nombre">

        <label>Ciudad</label>
        <input class="input-xlarge" v-model="negocioForm.ciudad">

        <label>Puesto</label>
        <input class="input-xlarge" v-model="negocioForm.puesto">

      </div>

      <div class="modal-footer">

        <button class="btn btn-primary" @click="guardarNegocio">
          Guardar
        </button>

        <button class="btn" @click="volverNegociosDesdeEditar">
          Cancelar
        </button>

      </div>

    </div>



    <!-- ===========================
    MODAL MODULOS NEGOCIO
    =========================== -->

    <div class="modal hide fade" id="modalModulosNegocio">

      <div class="modal-header">
        <h3>Módulos de {{ negocioActual.nombre }}</h3>
      </div>

      <div class="modal-body">

        <label v-for="m in modulosNegocio" style="display:block">

          <input
            type="checkbox"
            :value="m.modulo_id"
            v-model="modulosAsignados">

          {{ m.nombre }}

        </label>

      </div>

      <div class="modal-footer">

        <button class="btn btn-primary" @click="guardarModulos">
          Guardar
        </button>

        <button class="btn" @click="volverNegocios">
          Volver
        </button>

      </div>

    </div>


  </div>
</div>


<script>

new Vue({

  el:'#appModulos',

  data:{

    apphost:apphost,

    modulos:[],
    negocios:[],
    modulosNegocio:[],

    form:{},

    negocioForm:{},

    negocioActual:{},

    modulosAsignados:[],

    dtModulos:null,
    dtNegocios:null

  },

  methods:{


    /* ===========================
       MODULOS
    =========================== */

    listarModulos(){

      axios.get(this.apphost+'/modulo/listar')
      .then(r=>{

        this.modulos=r.data;

        this.$nextTick(()=>{

          if(!this.dtModulos){

            this.dtModulos = $('#tablaModulos').DataTable({
              language: dt_language,
              scrollX:true,
              dom:'frtip',
              order:[[0,'desc']]
            });

            const self=this;

            $('#tablaModulos tbody')

            .on('click','a.editar-modulo',function(e){

              e.preventDefault();

              const id=$(this).data('id');

              const m=self.modulos.find(x=>x.modulo_id==id);

              if(m) self.editarModulo(m);

            })

            .on('click','a.eliminar-modulo',function(e){

              e.preventDefault();

              const id=$(this).data('id');

              const m=self.modulos.find(x=>x.modulo_id==id);

              if(m) self.eliminarModulo(m);

            });

          }

          this.dtModulos.clear();

          this.modulos.forEach(m=>{

            const acciones=`
            <div class="btn-group">
              <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                ⚙ <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li>
                  <a href="#" class="editar-modulo" data-id="${m.modulo_id}">
                    Editar
                  </a>
                </li>
                <li>
                  <a href="#" class="eliminar-modulo" data-id="${m.modulo_id}">
                    Eliminar
                  </a>
                </li>
              </ul>
            </div>`;

            this.dtModulos.row.add([
              m.modulo_id,
              m.nombre,
              m.descripcion,
              acciones
            ]);

          });

          this.dtModulos.draw(false);

        });

      });

    },


    nuevoModulo(){

      this.form={ nombre:'', descripcion:'' };

      $('#modalModulo').modal('show');

    },


    editarModulo(m){

      this.form=JSON.parse(JSON.stringify(m));

      $('#modalModulo').modal('show');

    },


    guardarModulo(){

      axios.post(this.apphost+'/modulo/guardar',this.form)
      .then(()=>{

        $('#modalModulo').modal('hide');

        this.listarModulos();

      });

    },


    eliminarModulo(m){

      if(!confirm('¿Eliminar módulo?')) return;

      axios.post(this.apphost+'/modulo/eliminar',{ modulo_id:m.modulo_id })
      .then(()=>this.listarModulos());

    },


    /* ===========================
       NEGOCIOS
    =========================== */

    listarNegocios(){

      axios.get(this.apphost+'/negocio/listar')
      .then(r=>{

        this.negocios=r.data;

        this.$nextTick(()=>{

          if(!this.dtNegocios){

            this.dtNegocios = $('#tablaNegocios').DataTable({
              language: dt_language,
              scrollX:true,
              dom:'frtip',
              order:[[0,'desc']]
            });

            const self=this;

            $('#tablaNegocios tbody')

            .on('click','a.editar-negocio',function(e){

              e.preventDefault();

              const id=$(this).data('id');

              const n=self.negocios.find(x=>x.neg_id==id);

              if(n) self.editarNegocio(n);

            })

            .on('click','a.eliminar-negocio',function(e){

              e.preventDefault();

              const id=$(this).data('id');

              const n=self.negocios.find(x=>x.neg_id==id);

              if(n) self.eliminarNegocio(n);

            })

            .on('click','a.modulos-negocio',function(e){

              e.preventDefault();

              const id=$(this).data('id');

              const n=self.negocios.find(x=>x.neg_id==id);

              if(n) self.abrirModulos(n);

            });

          }

          this.dtNegocios.clear();

          this.negocios.forEach(n=>{

            const acciones=`
            <div class="btn-group">
              <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                Opciones <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li>
                  <a href="#" class="editar-negocio" data-id="${n.neg_id}">
                    Editar
                  </a>
                </li>
                <li>
                  <a href="#" class="eliminar-negocio" data-id="${n.neg_id}">
                    Eliminar
                  </a>
                </li>
                <li>
                  <a href="#" class="modulos-negocio" data-id="${n.neg_id}">
                    Cargar módulos
                  </a>
                </li>
              </ul>
            </div>`;

            this.dtNegocios.row.add([
              n.neg_id,
              n.cod_neg,
              n.nombre,
              n.ciudad,
              n.puesto,
              acciones
            ]);

          });

          this.dtNegocios.draw(false);

        });

      });

    },


    abrirNegocios(){

      this.listarNegocios();

      $('#modalNegocios').modal('show');

    },


    nuevoNegocio(){

      $('#modalNegocios').modal('hide');

      this.negocioForm={
        cod_neg:'',
        nombre:'',
        ciudad:'',
        puesto:''
      };

      $('#modalNegocio').modal('show');

    },


    editarNegocio(n){

      $('#modalNegocios').modal('hide');

      this.negocioForm=JSON.parse(JSON.stringify(n));

      $('#modalNegocio').modal('show');

    },


    guardarNegocio(){

      axios.post(this.apphost+'/negocio/guardar',this.negocioForm)
      .then(()=>{

        $('#modalNegocio').modal('hide');

        this.listarNegocios();

        $('#modalNegocios').modal('show');

      });

    },


    volverNegociosDesdeEditar(){

      $('#modalNegocio').modal('hide');

      $('#modalNegocios').modal('show');

    },


    eliminarNegocio(n){

      apprise('¿Eliminar negocio?',{confirm:true},ok=>{

        if(!ok) return;

        axios.post(this.apphost+'/negocio/eliminar',{neg_id:n.neg_id})
        .then(()=>this.listarNegocios());

      });

    },


    abrirModulos(n){

      $('#modalNegocios').modal('hide');

      this.negocioActual=n;

      this.modulosAsignados=[];

      axios.get(this.apphost+'/negocio/modulos/'+n.neg_id)

      .then(r=>{

        this.modulosNegocio=r.data;

        this.modulosAsignados=r.data
          .filter(x=>x.activo==1)
          .map(x=>x.modulo_id);

        $('#modalModulosNegocio').modal('show');

      });

    },


    guardarModulos(){

      axios.post(this.apphost+'/negocio/modulos/guardar',{

        neg_id:this.negocioActual.neg_id,
        modulos:this.modulosAsignados

      })

      .then(()=>{

        $('#modalModulosNegocio').modal('hide');

        $('#modalNegocios').modal('show');

      });

    },


    volverNegocios(){

      $('#modalModulosNegocio').modal('hide');

      $('#modalNegocios').modal('show');

    }

  },

  mounted(){

    this.listarModulos();

  }

});

</script>