<div class="row-fluid" id="appCategory">
<!-- este es mi frontend usando boostrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
  <div class="span12">
    <h2>Categorías</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrear">
        <i class="icon-plus icon-white"></i> Nueva Categoría
      </button>
    </div>

    <!-- =============================
           TABLA CATEGORY
    ============================== -->
    <table id="tablaCategory" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Icono</th>
          <th>Breve</th>
          <th>Acciones</th>
        </tr>
      </thead>
    </table>

    <!-- =============================
           MODAL CREAR
    ============================== -->
    <div id="modalCrearCategory" class="modal hide fade">
      <div class="modal-header"><h3>Nueva Categoría</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Nombre</label>
          <div class="controls"><input class="input-xxlarge" v-model="nuevo.name"></div>
        </div>

        <div class="control-group">
          <label>Color</label>
          <div class="controls">
            <select v-model="nuevo.color" class="input-xlarge">
              <option v-for="c in colores"
                      :value="c"
                      :style="{
                        background: c,
                        color: getTextColor(c),
                        fontWeight: 'bold'
                      }">
                {{ c }}
              </option>
            </select>
          </div>
        </div>

        <div class="control-group">
          <label>Emoji</label>
          <div class="controls">

            <input class="input-small text-center text-large"
                   v-model="nuevo.icon"
                   placeholder="😀">

            <select class="input-medium"
                    @change="nuevo.icon = $event.target.value">

              <option value="">Seleccionar</option>
              <option>😀</option>
              <option>😁</option>
              <option>😂</option>
              <option>😍</option>
              <option>😎</option>
              <option>🥳</option>
              <option>🔥</option>
              <option>💎</option>
              <option>🛒</option>
              <option>🍎</option>
              <option>🍔</option>
              <option>🥬</option>
              <option>🍞</option>
              <option>🍗</option>
              <option>🧃</option>
              <option>🧼</option>
              <option>🧴</option>
              <option>👕</option>
              <option>👟</option>

            </select>

          </div>
        </div>        

        <div class="control-group">
          <label>Descripción breve</label>
          <div class="controls"><input class="input-xxlarge" v-model="nuevo.brief"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crear">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =============================
           MODAL EDITAR
    ============================== -->
    <div id="modalEditarCategory" class="modal hide fade">
      <div class="modal-header"><h3>Editar Categoría</h3></div>
      <div class="modal-body">

        <div class="control-group">
          <label>Nombre</label>
          <div class="controls"><input class="input-xxlarge" v-model="form.name"></div>
        </div>

        <div class="control-group">
          <label>Color</label>
          <div class="controls">
            <select v-model="form.color" class="input-xlarge">
              <option v-for="c in colores"
                      :value="c"
                      :style="{
                        background: c,
                        color: getTextColor(c),
                        fontWeight: 'bold'
                      }">
                {{ c }}
              </option>
            </select>
          </div>
        </div>

        <div class="control-group">
          <label>Emoji</label>
          <div class="controls">

            <input class="input-small text-center text-large"
                   v-model="form.icon"
                   placeholder="😀">

            <select class="input-medium"
                    @change="form.icon = $event.target.value">

              <option value="">Seleccionar</option>
              <option>😀</option>
              <option>😁</option>
              <option>😂</option>
              <option>😍</option>
              <option>😎</option>
              <option>🥳</option>
              <option>🔥</option>
              <option>💎</option>
              <option>🛒</option>
              <option>🍎</option>
              <option>🍔</option>
              <option>🥬</option>
              <option>🍞</option>
              <option>🍗</option>
              <option>🧃</option>
              <option>🧼</option>
              <option>🧴</option>
              <option>👕</option>
              <option>👟</option>

            </select>

          </div>
        </div>        

        <div class="control-group">
          <label>Descripción breve</label>
          <div class="controls"><input class="input-xxlarge" v-model="form.brief"></div>
        </div>

      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardar">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

  </div>
</div>

<script>
new Vue({
  el: "#appCategory",
  data:{
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),
    categorias:[],
    nuevo:{ name:'', color:'#999999', brief:'' },
    form:{},
    colores: [
      '#FA8072','#800020','#FFF9C4','#C8E6C9','#6A1B9A',
      '#0277BD','#FF6F00','#F4511E','#FDD835','#7CB342',
      '#8E24AA','#039BE5','#37474F','#AFB42B','#5E35B1',
      '#FB8C00','#C62828','#AD1457','#B71C1C','#D32F2F',
      '#E53935','#1E88E5','#43A047','#FBC02D','#8D6E63'
    ],
    dt:null
  },
  methods:{
    listar(){
      axios.get(`${this.apphost}/category/listar`)
      .then(r=>{
        this.categorias = r.data;

        this.$nextTick(()=>{
          if(!this.dt){
            this.dt = $('#tablaCategory').DataTable({
              dom:'frtip',
              order:[[0,'desc']]
            });

            const self=this;
            $('#tablaCategory tbody')
            .on('click','a.editar',function(){
              const id = $(this).data('id');
              const c = self.categorias.find(x=>x.id==id);
              self.abrirEditar(c);
            })
            .on('click','a.eliminar',function(){
              const id = $(this).data('id');
              const c = self.categorias.find(x=>x.id==id);
              self.eliminar(c);
            });
          }

          this.dt.clear();
          this.categorias.forEach(c=>{
            const actions = `
              <div class="btn-group">
                <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                  Opciones <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="editar" data-id="${c.id}">Editar</a></li>
                  <li><a href="#" class="eliminar" data-id="${c.id}">Eliminar</a></li>
                </ul>
              </div>
            `;
            this.dt.row.add([
              c.id,
              c.name,
              `<span style="font-size:22px">${c.icon || '❓'}</span>`,
              c.brief,
              actions
            ]);
          });
          this.dt.draw(false);
        });
      });
    },

    abrirModalCrear(){
      this.nuevo = { name:'', icon:'😀', color:'#999999', brief:'' };
      $('#modalCrearCategory').modal('show');
    },

    getTextColor(hex){
      const c = hex.substring(1);
      const rgb = parseInt(c,16);
      const r = (rgb>>16)&255;
      const g = (rgb>>8)&255;
      const b = rgb&255;
      const yiq = (r*299 + g*587 + b*114) / 1000;
      return yiq >= 150 ? '#000' : '#fff';
    },

    crear(){
      axios.post(`${this.apphost}/category/crear`, this.nuevo)
      .then(()=>{
        $('#modalCrearCategory').modal('hide');
        this.listar();
      });
    },

    abrirEditar(c){
      this.form = JSON.parse(JSON.stringify(c));
      $('#modalEditarCategory').modal('show');
    },

    guardar(){
      axios.post(`${this.apphost}/category/editar`, this.form)
      .then(()=>{
        $('#modalEditarCategory').modal('hide');
        this.listar();
      });
    },

    eliminar(c){
      apprise(`¿Eliminar categoría <b>${c.name}</b>?`, {confirm:true}, ok=>{
        if(!ok) return;
        axios.post(`${this.apphost}/category/eliminar`, { id:c.id })
        .then(()=>this.listar());
      });
    }
  },

  mounted(){
    this.listar();
  }
});
</script>
