<div class="row-fluid" id="appProduct">
<!-- este es mi frontend usando boostrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
<div class="span12">
  <div class="titulo-fijo clearfix">

    <div style="float:left;">
      <h2 style="margin:0;">Productos</h2>
    </div>

    <div class="btn-group pull-right">
      <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
        <i class="fa fa-bandcamp"></i>
        <span class="caret"></span>
      </button>

      <ul class="dropdown-menu pull-right">
        <li>
          <a href="#" @click.prevent="abrirModalCrear">
            <i class="fa fa-arrow-circle-right"></i> Nuevo Producto
          </a>
        </li>
        <li>
          <a href="#" @click.prevent="crearFantasmas">
            <i class="fa fa-magic"></i> 5 fantasmas
          </a>
        </li>

      </ul>
    </div>

  </div>

<div class="span12 tabla_esp_sup">
    <!-- ===========================
          TABLA PRODUCTOS
    ============================ -->
    <table id="tablaProduct" class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Foto</th>
          <th>Nombre</th>
          <th>Precio</th>
          <th>Stock</th>
          <th>Categorías</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>
</div>
    <!-- ===========================
          MODAL DETALLE
    ============================ -->
    <div id="modalDetalleProduct" class="modal hide fade">
      <div class="modal-header"><h3>Detalle del Producto</h3></div>
      <div class="modal-body">
        <p><strong>ID:</strong> {{ detalle.product_id  }}</p>
        <p><strong>Nombre:</strong> {{ detalle.name }}</p>
        <p><strong>Precio:</strong> S/ {{ detalle.price }}</p>
        <p><strong>Stock:</strong> {{ detalle.stock }}</p>
        <p><strong>Descripción:</strong> {{ detalle.description }}</p>

        <h4>Categorías:</h4>
        <ul>
          <li v-for="c in detalle.categories">{{ c.descripcion }}</li>
        </ul>

        <h4>Imágenes:</h4>
        <img v-for="img in detalle.images"
        :src="img.image" style="width:80px;margin:5px;border:1px solid #ccc;">
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- ===========================
          MODAL CREAR PRODUCTO
    ============================ -->
    <div id="modalCrearProduct" class="modal hide fade">
      <div class="modal-header"><h3>Nuevo Producto</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Producto global</label>
          <div class="controls" style="position:relative">

            <input
            v-model="buscarGlobal"
            @keyup="buscarProductoGlobal"
            class="input-xxlarge"
            placeholder="Escribe 4 letras para buscar">

            <ul v-if="resultadosGlobal.length"
            style="position:absolute;
            background:#195D72;
            border:1px solid #ccc;
            width:400px;
            max-height:200px;
            overflow:auto;
            z-index:9999;
            list-style:none;
            margin:0;
            padding:0">

            <li v-for="p in resultadosGlobal"
            @click="seleccionarProductoGlobal(p)"
            style="padding:6px;cursor:pointer">

            {{ p.nombre }}

          </li>

        </ul>

      </div>
    </div>

    <div class="control-group">
      <label>Nombre</label>
      <div class="controls"><input v-model="nuevo.name" class="input-xxlarge"></div>
    </div>

    <div class="row-fluid">

      <!-- PRECIO REF -->
      <div class="span4">
        <label>Precio ref</label>
        <div class="controls" style="display:flex;gap:5px">

          <input v-model="nuevo.price_ref" disabled style="width:80%">

          <!-- BOTÓN COPIAR -->
          <button class="btn btn-mini btn-info"
          @click="copiarPrecio">
          ➡
          </button>

        </div>
      </div>

      <!-- PRECIO -->
      <div class="span4">
        <label>Precio</label>
        <div class="controls">
          <input v-model="nuevo.price" style="width:80%">
        </div>
      </div>

      <!-- STOCK -->
      <div class="span4">
        <label>Stock</label>
        <div class="controls">
          <input 
          v-model="nuevo.stock" 
          type="number"
          step="1"
          min="0"
          @input="validarStock"
          style="width:80%">
        </div>
      </div>

    </div>



    <div class="control-group">
      <label>Categorías</label>
      <v-select multiple :options="categorias"
      label="descripcion"
      v-model="nuevo.categorias"
      class="input-xxlarge">
    </v-select>
  </div>

  <div class="control-group">
    <label>Descripción</label>
    <div class="controls">
      <textarea v-model="nuevo.description" class="input-xxlarge"></textarea>
    </div>
  </div>

</div>
<div class="modal-footer">
  <!--
  <button class="btn btn-success pull-left" @click="crearAutomatico">
    <i class="fa fa-bolt"></i> automático
  </button>  
  <button class="btn btn-primary" @click="crearProduct">Crear</button>
  -->
  <button class="btn btn-primary" @click="crearAutomatico">
    <i class="fa fa-bolt"></i> automático
  </button>
  <button class="btn" data-dismiss="modal">Cancelar</button>
</div>
</div>

    <!-- ===========================
          MODAL EDITAR PRODUCTO
    ============================ -->
    <div id="modalEditarProduct" class="modal hide fade">
      <div class="modal-header"><h3>Editar Producto</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Nombre</label>
          <div class="controls"><input v-model="form.name" class="input-xxlarge"></div>
        </div>

        <div class="control-group">
          <label>Precio</label>
          <div class="controls"><input v-model="form.price"></div>
        </div>

        <div class="control-group">
          <label>Categorías</label>
          <v-select
          multiple
          :options="categorias"
          label="descripcion"
          v-model="form.categorias"
          class="input-xxlarge">
        </v-select>


      </div>

      <div class="control-group">
        <label>Descripción</label>
        <div class="controls">
          <textarea v-model="form.description" class="input-xxlarge"></textarea>
        </div>
      </div>

    </div>
    <div class="modal-footer">
      <button class="btn btn-primary" @click="guardarEdicion">Guardar</button>
      <button class="btn" data-dismiss="modal">Cancelar</button>
    </div>
  </div>


    <!-- ===========================
      MODAL CREAR CATEGORIA
      =========================== -->
      <div id="modalCrearCategoria" class="modal hide fade">
        <div class="modal-header"><h3>Nueva Categoría</h3></div>
        <div class="modal-body">

          <div class="control-group">
            <label>Nombre</label>
            <div class="controls">
              <input v-model="catNueva.name" class="input-xxlarge">
            </div>
          </div>

          <div class="control-group">
            <label>Ícono (archivo o nombre)</label>
            <div class="controls">
              <input v-model="catNueva.icon">
            </div>
          </div>

          <div class="control-group">
            <label>Color</label>
            <div class="controls">
              <input v-model="catNueva.color" placeholder="#ff0000">
            </div>
          </div>

          <div class="control-group">
            <label>Descripción breve</label>
            <div class="controls">
              <input v-model="catNueva.brief" class="input-xxlarge">
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button class="btn btn-primary" @click="crearCategoria">Crear</button>
          <button class="btn" data-dismiss="modal">Cancelar</button>
        </div>
      </div>

      <!-- ===========================
      MODAL FOTO PRODUCTO
=========================== -->
<div id="modalFotoProduct" class="modal hide fade">

  <div class="modal-header">

    <h3>
      Foto del Producto
    </h3>

  </div>

  <div class="modal-body">

    <!-- ACTUAL -->

    <div style="margin-bottom:15px;">

      <label>
        Imagen actual
      </label>

      <div>

        <img
          :src="fotoProduct.preview || 'https://barsi-img.b-cdn.net/recursos/6qz5.png'"
          style="
            width:160px;
            height:160px;
            object-fit:cover;
            border-radius:12px;
            border:1px solid #ddd;
          "
        >

      </div>

    </div>

    <!-- FILE -->

    <div class="control-group">

      <label>
        Seleccionar imagen
      </label>

      <div class="controls">

        <input
          type="file"
          accept="image/*"
          @change="onSelectFotoProducto"
        >

      </div>

    </div>

    <!-- PREVIEW -->

    <div
      v-if="fotoProduct.previewNueva"
      style="margin-top:15px;"
    >

      <label>
        Previsualización
      </label>

      <div>

        <img
          :src="fotoProduct.previewNueva"
          style="
            width:160px;
            height:160px;
            object-fit:cover;
            border-radius:12px;
            border:1px solid #ddd;
          "
        >

      </div>

    </div>

  </div>

  <div class="modal-footer">

    <button
      class="btn btn-info pull-left"
      @click="randomFotoProducto"
    >

      🎲 Random

    </button>

    <button
      class="btn btn-primary"
      @click="subirFotoProducto"
    >

      Subir

    </button>

    <button
      class="btn"
      data-dismiss="modal"
    >

      Cerrar

    </button>

  </div>

</div>


      <div id="modalReporteCategoria" class="modal hide fade">
        <div class="modal-header">
          <h3>Reporte por Categoría</h3>
        </div>

        <div class="modal-body">

          <div class="control-group">
            <label>Categorías</label>
            <v-select
            multiple
            :options="categorias"
            label="descripcion"
            v-model="reporteCategorias"
            class="input-xxlarge">
          </v-select>

          <p class="help-block">
            Si no seleccionas ninguna, se incluirán <b>todas</b>.
          </p>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="generarReporteCategoria">
          <i class="icon-print icon-white"></i> PDF
        </button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>



  </div>
</div>

<script>
  Vue.component('v-select', VueSelect.VueSelect);

  new Vue({
    el: '#appProduct',
    data:{
      apphost: (typeof apphost !== 'undefined' ? apphost : ''),
      productos: [],
      catNueva: { name:'', icon:'', color:'#999999', brief:'' },
      categorias: [],
      nuevo: { name:'', price:0, description:'', categorias:[] },
      form: {},
      buscarGlobal:'',
      nuevo: { name:'', price:0, price_ref:0, description:'',stock:99, categorias:[] },
      resultadosGlobal:[],
      reporteCategorias: [],
      detalle:{},
      fotoProduct: {

        product_id: 0,

        preview: '',

        file: null,

        previewNueva: ''

      },
      dt:null
    },
    methods:{
      listar(){
        axios.get(`${this.apphost}/product/listar`).then(r=>{
          this.productos = r.data;
          this.$nextTick(()=>{

            if(!this.dt){
              this.dt = $('#tablaProduct').DataTable({
                dom:'frtip', order:[[0,'desc']]
              });

              const self=this;
              $('#tablaProduct tbody')
              .on('click','a.detalle',function(e){
                const id = $(this).data("id");
                const p = self.productos.find(x => x.product_id == id);
                self.abrirDetalle(p);
              })
              .on('click','a.foto',function(e){

                const id = $(this).data("id");

                const p = self.productos.find(
                  x => x.product_id == id
                );

                self.abrirFotoProducto(p);

              })
              .on('click','a.editar',function(e){
                const id = $(this).data("id");
                const p = self.productos.find(x => x.product_id == id);
                self.abrirEditar(p);
              })
              .on('click','a.eliminar',function(e){
                const id = $(this).data("id");
                const p = self.productos.find(x => x.product_id == id);
                self.eliminar(p);
              });
            }

            this.dt.clear();
            this.productos.forEach(p=>{
              const actions = `
                  <div class="btn-group">

                    <button
                      class="btn btn-mini btn-primary dropdown-toggle"
                      data-toggle="dropdown"
                    >

                      Opciones

                      <span class="caret"></span>

                    </button>

                    <ul class="dropdown-menu">

                      <li>
                        <a href="#"
                           class="detalle"
                           data-id="${p.product_id}">
                           Detalle
                        </a>
                      </li>

                      <li>
                        <a href="#"
                           class="editar"
                           data-id="${p.product_id}">
                           Editar
                        </a>
                      </li>

                      <li>
                        <a href="#"
                           class="eliminar"
                           data-id="${p.product_id}">
                           Eliminar
                        </a>
                      </li>

                      <li>
                        <a href="#"
                           class="foto"
                           data-id="${p.product_id}">
                           Foto
                        </a>
                      </li>

                    </ul>

                  </div>
                  `;
              
              const foto = `

                <img
                  src="${
                    p.img ||
                    'https://barsi-img.b-cdn.net/recursos/6qz5.png'
                  }"
                  style="
                    width:55px;
                    height:55px;
                    object-fit:cover;
                    border-radius:10px;
                    border:1px solid #ddd;
                  "
                >

                `;

                this.dt.row.add([

                  p.product_id,

                  foto,

                  p.name,

                  p.price,

                  p.stock,

                  p.categories_names,

                  actions

                ]);
            });
            this.dt.draw(false);

          });
        });
      },

      mapearCategoriasEditar(p){
        this.form.categorias = this.categorias.filter(c =>
          p.categories_ids.includes(c.category_id)
          );
      },
      copiarPrecio(){
        this.nuevo.price = this.nuevo.price_ref;
      },

      crearFantasmas(){

        apprise(

          '¿Crear 5 productos fantasma?',

          {

            'verify': true

          },

          (r)=>{

            if(!r){
              return;
            }

            $.blockUI({

              message: 'Creando productos fantasma...'

            });

            axios.post(

              `${this.apphost}/OO09/productosFantasma`

            )
            .then(res=>{

              if(
                res.data.status == 'ok'
              ){

                apprise(
                  'Productos fantasma creados 🚀'
                );

                this.listar();

              }else{

                apprise(
                  res.data.msg ||
                  'No se pudo crear'
                );

              }

            })
            .catch(e=>{

              apprise(

                e.response?.data?.msg ||

                'Error al crear productos'

              );

            })
            .finally(()=>{

              $.unblockUI();

            });

          }

        );

      },      
      abrirModalCrear(){

        this.nuevo = {
          name:'',
          price:0,
          stock:99,
          price_ref:0,
          description:'',
          categorias:[]
        };

        this.buscarGlobal = '';
        this.resultadosGlobal = [];

        $('#modalCrearProduct').modal('show');

      },
      abrirFotoProducto(p){

        this.fotoProduct = {

          product_id:
            parseInt(
              p.product_id,
              10
            ),

          preview:
            p.img || '',

          file: null,

          previewNueva: ''

        };

        $('#modalFotoProduct')
          .modal('show');

      },

      onSelectFotoProducto(e){

        const file =
          e.target.files[0];

        if(!file){
          return;
        }

        this.fotoProduct.file =
          file;

        this.fotoProduct.previewNueva =
          URL.createObjectURL(file);

      },

      subirFotoProducto(){

        apprise(
          'Todavía no implementado 😏'
        );

      },

      randomFotoProducto(){

        $.blockUI({

          message:
            'Generando foto random...'

        });

        axios.post(

          `${this.apphost}/GcVL/producto/randomFoto`,

          {

            product_id:
              this.fotoProduct.product_id

          }

        )
        .then(r=>{

          if(r.data.status=='ok'){

            this.fotoProduct.preview =
              r.data.img;

            apprise(
              'Foto random aplicada 🚀'
            );

            this.listar();

          }

        })
        .catch(e=>{

          apprise(

            e.response?.data?.msg ||

            'Error'

          );

        })
        .finally(()=>{

          $.unblockUI();

        });

      },
      buscarProductoGlobal(){

        if(this.buscarGlobal.length < 4){
          this.resultadosGlobal = [];
          return;
        }

        axios.get(`${this.apphost}/pet/product/buscar_global`,{
          params:{ q:this.buscarGlobal }
        })
        .then(r=>{
          this.resultadosGlobal = r.data;
        });

      },      

      seleccionarProductoGlobal(p){

        this.nuevo.name = p.nombre;
        this.nuevo.price_ref = Number(p.precio); // 🔥 AQUÍ

        const cat = this.categorias.find(c =>
          c.category_id == p.categoria_global_id
        );

        if(cat){
          this.nuevo.categorias = [cat];
        }

        this.buscarGlobal = p.nombre;
        this.resultadosGlobal = [];

      },      

      crearProduct(){
        axios.post(`${this.apphost}/product/crear`, this.nuevo)
        .then(()=>{ $('#modalCrearProduct').modal('hide'); this.listar(); });
      },

      validarStock(){
        if(this.nuevo.stock === '' || this.nuevo.stock === null){
          return;
        }

        // 🔥 eliminar decimales y texto
        let val = this.nuevo.stock.toString().replace(/[^0-9]/g, '');

        this.nuevo.stock = val ? parseInt(val) : '';
      },

      crearAutomatico(){

        if(!this.nuevo.name){
          apprise("Primero selecciona un producto global");
          return;
        }

        const payload = {
          name: this.nuevo.name,
          price: this.nuevo.price || this.nuevo.price_ref,
          price_ref: this.nuevo.price_ref,
          stock: this.nuevo.stock ? parseInt(this.nuevo.stock) : 9999, // 🔥 FIX
          description: this.nuevo.description,
          categorias: this.nuevo.categorias
        };

        $.blockUI({ message: 'Procesando...' });

        axios.post(`${this.apphost}/tito/producto/agregar`, payload)
        .then(r=>{

          apprise("Producto creado automáticamente 🚀");

          $('#modalCrearProduct').modal('hide');
          this.listar();

        })
        .catch(e=>{
          apprise("Error: " + (e.response?.data?.msg || 'Error desconocido'));
        })
        .finally(()=>{
          $.unblockUI();
        });

      },      

      abrirEditar(p){

        console.group('✏️ abrirEditar');
        console.log('Producto:', p);
        console.log('categories_ids RAW:', p.categories_ids);
        console.groupEnd();

        this.form = {
          product_id: p.product_id,
          name: p.name,
          price: p.price,
          description: p.description || '',
          categorias: this.categorias.filter(c =>
            p.categories_ids.includes(c.category_id)
            )
        };

        console.log('form.categorias (NUMBERS):', this.form.categorias);

        $('#modalEditarProduct').modal('show');
      },


      guardarEdicion(){
        axios.post(`${this.apphost}/product/editar`, this.form)
        .then(()=>{ $('#modalEditarProduct').modal('hide'); this.listar(); });
      },

      abrirDetalle(p){
        axios.get(`${this.apphost}/product/detalle/${p.product_id}`).then(r=>{
          this.detalle = r.data;
          $('#modalDetalleProduct').modal('show');
        });
      },

      eliminar(p){
        apprise(`¿Eliminar producto <b>${p.name}</b>?`, {confirm:true}, ok=>{
          if(!ok) return;
          axios.post(`${this.apphost}/product/eliminar`, { product_id: p.product_id })
          .finally(()=>this.listar());
        });
      },

      cargarCategorias(){
        axios.get(`${this.apphost}/product/listar_categorias`)
        .then(r => {
          this.categorias = r.data.map(c => ({
            category_id: Number(c.category_id), // 🔥 CLAVE ABSOLUTA
            descripcion: c.descripcion.trim()
          }));

          console.log('✅ Categorías normalizadas:', this.categorias);
        });
      },


      abrirModalCrearCategoria(){
        this.catNueva = { name:'', icon:'', color:'#999999', brief:'' };
        $('#modalCrearCategoria').modal('show');
      },

      crearCategoria(){
        if(!this.catNueva.name.trim()){
          apprise("Escribe un nombre para la categoría");
          return;
        }

        axios.post(`${this.apphost}/product/categoria_crear`, this.catNueva)
        .then(() => {
          $('#modalCrearCategoria').modal('hide');
          apprise("Categoría creada correctamente");

          // recargar categorías para el v-select
          this.cargarCategorias();
        });
      },

      getCategoriaLabel(id) {
        const cat = this.categorias.find(c => c.category_id === id);
        return cat ? cat.descripcion : id;
      },

      abrirReporteProductos(){
        const url = `${this.apphost}/imp_lista_prod`;
        window.open(url, '_blank');
      },

      abrirReporteCategoria(){
        this.reporteCategorias = [];
        $('#modalReporteCategoria').modal('show');
      },

      generarReporteCategoria(){

      // si no selecciona nada → todas
        const ids = this.reporteCategorias.length
        ? this.reporteCategorias.map(c => c.category_id)
        : ['ALL'];

        const query = encodeURIComponent(JSON.stringify(ids));
        const url = `${this.apphost}/producto/reporteCategoriaProducto?categorias=${query}`;

        window.open(url, '_blank');
        $('#modalReporteCategoria').modal('hide');
      },


    },

    mounted(){
      this.cargarCategorias();
      this.listar();

      $('#modalCrearProduct').on('hidden', () => {

        this.nuevo = { name:'', price:0, stock:99, description:'', categorias:[] };
        this.buscarGlobal = '';
        this.resultadosGlobal = [];

      });
    }

  });
</script>
