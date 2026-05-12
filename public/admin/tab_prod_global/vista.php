<!-- =========================================
     PROD_PLAZAVEA + CATEGORIA GLOBAL
========================================= -->

<div class="row-fluid" id="appProd">
  <div class="span12">

    <!-- TITULO -->
    <div class="titulo-fijo clearfix">
      <div style="float:left;">
        <h2 style="margin:0;">Productos PlazaVea</h2>
      </div>

      <div class="btn-group pull-right">
        <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-cubes"></i>
          <span class="caret"></span>
        </button>

        <ul class="dropdown-menu pull-right">
          <li>
            <a href="#" @click.prevent="abrirModalCategorias">
              Ver categoría global
            </a>
          </li>
          <li>
            <a href="#" @click.prevent="abrirModalCrear">
              Nuevo item
            </a>
          </li>
          <li>
            <a href="#" @click.prevent="abrirBuscarGlobal">
              Buscar global
            </a>
          </li>
        </ul>
      </div>
    </div>

    <!-- TABLA PRINCIPAL -->
    <table id="tablaProd" class="table table-bordered table-condensed">
      <thead>
        <tr>
          <th>Cod</th>
          <th>Nombre</th>
          <th>Marca</th>
          <th>Precio</th>
          <th>Categoría</th>
          <th>Imagen</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

  </div>

<!-- =========================
     MODAL PRODUCTO
========================= -->
<div id="modalProd" class="modal hide fade">
  <div class="modal-header">
    <h3>Producto</h3>
  </div>
  <div class="modal-body">

    <input v-model="form.nombre" class="input-xxlarge" placeholder="Nombre">
    <input v-model="form.marca" class="input-large" placeholder="Marca">
    <input v-model="form.precio" class="input-small" placeholder="Precio">

    <v-select
      :options="categorias"
      label="nombre"
      :reduce="x=>x.categoria_global_id"
      v-model="form.categoria_global_id"
    ></v-select>

  </div>

  <div class="modal-footer">
    <button class="btn btn-primary" @click="guardar">Guardar</button>
  </div>
</div>


<div id="modalBuscarGlobal" class="modal hide fade fullscreen">

  <div class="modal-header">
    <button class="close" data-dismiss="modal">×</button>
    <h3>Buscar Global</h3>
  </div>

  <div class="modal-body">

    <!-- BUSCADOR -->
    <input 
      v-model="txtBuscarGlobal"
      @keyup="onBuscarGlobal"
      class="input-xxlarge"
      placeholder="Escribe al menos 4 letras..."
      style="margin-bottom:10px;"
    >

    <!-- TABLA -->
    <table id="tablaBuscarGlobal" class="table table-bordered table-condensed">
      <thead>
        <tr>
          <th>Cod</th>
          <th>Nombre</th>
          <th>Precio</th>
          <th>Categoría</th>
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
     MODAL IMAGEN
========================= -->
<div id="modalImagen" class="modal hide fade">
  <div class="modal-body">

    <img v-if="imgPreview" :src="imgPreview" style="width:150px">

    <input type="file" id="fileImg" @change="previewImg">

  </div>

  <div class="modal-footer">
    <button class="btn btn-success" @click="subirImagen">Subir imagen</button>
  </div>
</div>

<!-- =========================
     MODAL CATEGORIAS
========================= -->
<div id="modalCategorias" class="modal hide fade fullscreen">

  <div class="modal-header">
    <button class="close" data-dismiss="modal">×</button>
    <h3>Categorías Globales</h3>
  </div>

  <div class="modal-body">

    <table id="tablaCategorias" class="table table-bordered table-condensed">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Activo</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>

  </div>

  <div class="modal-footer">
    <button class="btn btn-success" @click="abrirCrearCategoria">
      Agregar
    </button>
    <button class="btn" data-dismiss="modal">Cerrar</button>
  </div>

</div>

<div id="modalCrearCategoria" class="modal hide fade">

  <div class="modal-header">
    <h3>Crear Categoría</h3>
  </div>

  <div class="modal-body">

    <input v-model="formCategoria.nombre" 
           class="input-xxlarge" 
           placeholder="Nombre de categoría">

  </div>

  <div class="modal-footer">
    <button class="btn btn-primary" @click="guardarCategoria">Guardar</button>
    <button class="btn" data-dismiss="modal">Cancelar</button>
  </div>

</div>


</div>

<script>

Vue.component('v-select', VueSelect.VueSelect);

new Vue({
  el:'#appProd',

  data:{
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),

    productos:[],
    categorias:[],
    categoriaFiltro:null,
    dt:null,
    txtBuscarGlobal:'',
    dtBuscar:null,
    formCategoria:{
        categoria_global_id:0,
        nombre:''
    },
    form:{
      cod_prod_plazavea:'',
      nombre:'',
      marca:'',
      precio:'',
      categoria:'',
      categoria_global_id:null
    },

    imgPreview:null
  },

  methods:{

    bloquear(msg){
      $.blockUI({message:`<h4>${msg}</h4>`});
    },

    /* =========================
       LISTAR PRODUCTOS
    ========================== */
    listar(){

      this.bloquear('Cargando...');

      let url = `${this.apphost}/WOyw/prod/listar`;

      if(this.categoriaFiltro){
        url += `?categoria_global_id=${this.categoriaFiltro}`;
      }

      axios.get(url)
      .then(r=>{
        this.productos = r.data.data || [];

        this.$nextTick(()=>{

          if(!this.dt){
            this.dt = $('#tablaProd').DataTable();

            const self = this;

            $('#tablaProd tbody')
              .on('click','.editar',function(){
                const id = $(this).data('id');
                const row = self.productos.find(x=>x.cod_prod_plazavea==id);
                self.abrirEditar(row);
              })
              .on('click','.eliminar',function(){
                const id = $(this).data('id');
                self.eliminar(id);
              })
              .on('click','.imagen',function(){
                const id = $(this).data('id');
                const row = self.productos.find(x=>x.cod_prod_plazavea==id);
                self.abrirImagen(row);
              });
          }

          this.dt.clear();

          this.productos.forEach(p=>{
            this.dt.row.add([
              p.cod_prod_plazavea,
              p.nombre,
              p.marca,
              p.precio,
              p.categoria,
              p.url_imagen ? `<img src="${p.url_imagen}" width="40">`:'',
              `
              <div class="btn-group">
                <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                  ⚙
                </button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="editar" data-id="${p.cod_prod_plazavea}">Editar</a></li>
                  <li><a href="#" class="eliminar" data-id="${p.cod_prod_plazavea}">Eliminar</a></li>
                  <li><a href="#" class="imagen" data-id="${p.cod_prod_plazavea}">Imagen</a></li>
                </ul>
              </div>`
            ]);
          });

          this.dt.draw(false);

        });

      })
      .finally(()=>$.unblockUI());
    },

    abrirBuscarGlobal(){

      this.txtBuscarGlobal = '';

      $('#modalBuscarGlobal').modal('show');

      this.$nextTick(()=>{

        if(this.dtBuscar){
          this.dtBuscar.destroy();
          $('#tablaBuscarGlobal tbody').empty();
        }

        this.dtBuscar = $('#tablaBuscarGlobal').DataTable();

      });

    },    

    onBuscarGlobal(){

      const q = this.txtBuscarGlobal.trim();

      // 🔥 mínimo 4 letras
      if(q.length < 4){
        if(this.dtBuscar){
          this.dtBuscar.clear().draw();
        }
        return;
      }

      this.buscarGlobal(q);

    },    

    buscarGlobal(q){

      axios.get(`${this.apphost}/pet/product/buscar_global?q=${encodeURIComponent(q)}`)
      .then(r=>{

        const data = r.data || [];

        if(!this.dtBuscar){
          this.dtBuscar = $('#tablaBuscarGlobal').DataTable();
        }

        this.dtBuscar.clear();

        data.forEach(p=>{

          this.dtBuscar.row.add([
            p.cod_prod_plazavea,
            p.nombre,
            'S/ ' + p.precio.toFixed(2),
            p.categoria_nombre
          ]);

        });

        this.dtBuscar.draw();

      });

    },

    /* =========================
       CREAR / EDITAR
    ========================== */
    abrirModalCrear(){
      this.form = {
        cod_prod_plazavea:'',
        nombre:'',
        marca:'',
        precio:'',
        categoria:'',
        categoria_global_id:null
      };
      $('#modalProd').modal('show');
    },

    abrirEditar(row){
      this.form = {...row};
      $('#modalProd').modal('show');
    },

    guardar(){

      this.bloquear('Guardando...');

      axios.post(`${this.apphost}/WOyw/prod/guardar`,this.form)
      .then(()=>{

        $('#modalProd').modal('hide');
        this.listar();

      })
      .finally(()=>$.unblockUI());

    },

    eliminar(id){

      apprise('¿Eliminar?',{confirm:true},ok=>{
        if(!ok) return;

        this.bloquear('Eliminando...');

        axios.post(`${this.apphost}/WOyw/prod/eliminar`,{
          cod_prod_plazavea:id
        })
        .then(()=>this.listar())
        .finally(()=>$.unblockUI());

      });

    },

    /* =========================
       CATEGORIAS
    ========================== */
    abrirModalCategorias(){

      $('#modalCategorias').modal('show');

      // 🔥 ESTA ES LA CLAVE
      this.listarCategorias();

    },

    /* =========================
       IMAGEN
    ========================== */
    abrirImagen(row){

      this.form = {...row};
      this.imgPreview = row.url_imagen;

      $('#modalImagen').modal('show');

    },

    listarCategorias(){

      this.bloquear('Cargando categorías...');

      axios.get(`${this.apphost}/WOyw/categoria/listar`)
      .then(r=>{

        this.categorias = r.data.data || [];

        this.$nextTick(()=>{

          // 🔥 DESTRUIR SI EXISTE
          if(this.dtCat){
            this.dtCat.destroy();
            $('#tablaCategorias tbody').empty();
          }

          // 🔥 CREAR NUEVO
          this.dtCat = $('#tablaCategorias').DataTable();

          const self = this;

          $('#tablaCategorias tbody')
            .off() // 🔥 LIMPIAR EVENTOS
            .on('click','.ver-productos',function(){
              const id = $(this).data('id');
              self.verProductosPorCategoria(id);
            })
            .on('click','.editar-cat',function(){
              const id = $(this).data('id');
              const row = self.categorias.find(x=>x.categoria_global_id==id);
              self.editarCategoria(row);
            })
            .on('click','.eliminar-cat',function(){
              const id = $(this).data('id');
              self.eliminarCategoria(id);
            });

          this.categorias.forEach(c=>{

            this.dtCat.row.add([
              c.categoria_global_id,
              c.nombre,
              c.is_activo == 1 ? 'SI':'NO',
              `
              <div class="btn-group">
                <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                  ⚙
                </button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="ver-productos" data-id="${c.categoria_global_id}">Productos</a></li>
                  <li><a href="#" class="editar-cat" data-id="${c.categoria_global_id}">Editar</a></li>
                  <li><a href="#" class="eliminar-cat" data-id="${c.categoria_global_id}">Eliminar</a></li>
                </ul>
              </div>`
            ]);

          });

          this.dtCat.draw();

        });

      })
      .finally(()=>$.unblockUI());

    },

    abrirCrearCategoria(){

      this.formCategoria = {
        categoria_global_id: 0,
        nombre: ''
      };

      $('#modalCategorias').modal('hide');
      $('#modalCrearCategoria').modal('show');

    },

    verProductosPorCategoria(id){

      this.categoriaFiltro = id;

      $('#modalCategorias').modal('hide');

      this.listar();

    },

    guardarCategoria(){

      if(!this.formCategoria.nombre){
        apprise('Escribe el nombre');
        return;
      }

      this.bloquear('Guardando...');

      axios.post(`${this.apphost}/WOyw/categoria/crear`,this.formCategoria)
      .then(()=>{
        $('#modalCrearCategoria').modal('hide');
        this.listarCategorias();
      })
      .finally(()=>$.unblockUI());

    },    

    limpiarFiltro(){

      this.categoriaFiltro = null;
      this.listar();

    },

    previewImg(e){
      const file = e.target.files[0];
      this.imgPreview = URL.createObjectURL(file);
    },

    subirImagen(){

      const fd = new FormData();
      fd.append('file',$('#fileImg')[0].files[0]);
      fd.append('cod_prod_plazavea',this.form.cod_prod_plazavea);

      this.bloquear('Subiendo...');

      axios.post(`${this.apphost}/WOyw/prod/subir-img`,fd)
      .then(()=>{
        $('#modalImagen').modal('hide');
        this.listar();
      })
      .finally(()=>$.unblockUI());

    }

  },

  mounted(){
    this.listar();
  }

});
</script>

