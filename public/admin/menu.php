<?php
$usu_id = $administrador_actual['usu_id'];
$rol_id = (int)$administrador_actual['rol_id'];
$admin_nombre = $administrador_actual['nombres_apellidos'];

//var_dump($administrador_actual);
//exit;
?>

<div id="masterMenuApp">
  <div class="container">
    <a class="brand" :href="APPHOST + '/admin/dash'">
      <img :src="logoUrl" alt="logo-admin" />
    </a>

    <!-- Botón lupa -->
    <div class="btn-group pull-right" style="margin-right: 8px">
      <a
        href="#modalBusquedaMenu"
        class="btn"
        data-toggle="modal"
        title="Buscar en menús"
      >
        <i class="icon-list-alt icon-black"></i>
      </a>
    </div>

    <!-- Usuario -->
    <div class="btn-group pull-right">
      <a href="#" data-toggle="dropdown" class="btn dropdown-toggle">
        <i class="icon-user"></i>
        {{ adminNombre }}
        <span class="caret"></span>
      </a>

      <ul class="dropdown-menu">
        <li>
          <a href="javascript:void(0)" @click="abrirMisDatos">
            Mis datos
          </a>
        </li>

        <li class="divider"></li>

        <li>
          <a :href="APPHOST + '/finAdmin'">
            Salir
          </a>
        </li>
      </ul>
    </div>

    <div class="nav-collapse">
      <ul class="nav">
        <li class="dropdown" v-for="menu in menus" :key="menu.menu_id">
          <a data-toggle="dropdown" class="dropdown-toggle" href="#">
            {{ menu.titulo }}
            <b class="caret"></b>
          </a>

          <ul
            class="dropdown-menu"
            v-if="menu.lista_submenu && menu.lista_submenu.length"
          >
            <li v-for="submenu in menu.lista_submenu" :key="submenu.submenu_id">
              <a :href="urlCompleta(submenu.url)" v-bind="linkAttrs(submenu)">
                {{ submenu.titulo }}
              </a>
            </li>
          </ul>
        </li>
      </ul>
    </div>
  </div>

  <!-- ===============================
  MODAL BUSCAR MENUS
  ================================= -->

  <div id="modalBusquedaMenu" class="modal hide fade fullscreen">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">×</button>

      <h3>
        <i class="icon-search"></i>
        Buscar opciones
      </h3>
    </div>

    <div class="modal-body">
      <input
        type="text"
        class="input-block-level"
        placeholder="Filtra por texto..."
        v-model="filtro"
        @input="filtrar"
        style="margin-bottom: 10px"
      />

      <div class="accordion" id="accordionMenus">
        <div
          class="accordion-group"
          v-for="m in menusFiltrados"
          :key="m.menu_id"
        >
          <div class="accordion-heading">
            <a
              class="accordion-toggle"
              data-toggle="collapse"
              :data-parent="'#accordionMenus'"
              :data-target="'#menu'+m.menu_id"
              href="javascript:void(0)"
            >
              {{ m.titulo }}
            </a>
          </div>

          <div class="accordion-body collapse" :id="'menu'+m.menu_id">
            <div class="accordion-inner" style="padding: 8px 12px">
              <ul
                class="nav nav-pills nav-stacked"
                v-if="m.lista_submenu && m.lista_submenu.length"
              >
                <li
                  v-for="s in m.lista_submenu"
                  :key="s.submenu_id"
                  style="margin-bottom: 4px"
                >
                  <a :href="urlCompleta(s.url)" v-bind="linkAttrs(s)">
                    {{ s.titulo }}
                  </a>
                </li>
              </ul>

              <div v-else class="muted">
                Sin opciones.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <a href="#" class="btn" data-dismiss="modal">
        Cerrar
      </a>
    </div>
  </div>

  <!-- ===============================
  MODAL MIS DATOS
  ================================= -->

  <div id="modalMisDatos" class="modal hide fade">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal">×</button>

      <h3>
        <i class="icon-user"></i>
        Mis datos
      </h3>
    </div>

    <div class="modal-body">
      <div v-if="cargandoMisDatos" class="muted">
        Cargando...
      </div>

      <table v-else class="table table-bordered">

        <tr>
          <th>Sobrenombre</th>
          <td>{{ misDatos.email }}</td>
        </tr>

        <tr>
          <th>Nombres apellidos</th>
          <td>{{ misDatos.nombres_apellidos }}</td>
        </tr>
        
        <tr>
          <th>Descripcion</th>
          <td>{{ misDatos.descripcion }}</td>
        </tr>        

        <tr>
          <th>Rol</th>
          <td>{{ misDatos.rol_nombre }}</td>
        </tr>

        <tr>
          <th>Mercado</th>
          <td>{{ misDatos.mercado_id }} → {{ misDatos.mercado_nombre }}</td>
        </tr>

        <tr>
          <th>Negocio</th>
          <td>{{ misDatos.neg_id }} → {{ misDatos.negocio_nombre }}</td>
        </tr>
      </table>
    </div>

    <div class="modal-footer">
      <a href="#" class="btn" data-dismiss="modal">
        Cerrar
      </a>
    </div>
  </div>
</div>

<script>

window.APPHOST      = <?php echo json_encode($apphost); ?>;
window.API_HOST     = APPHOST + '/api';
window.ROL_ID       = <?php echo (int)$rol_id; ?>;
window.ADMIN_NOMBRE = <?php echo json_encode($admin_nombre); ?>;
window.VARHOST      = <?php echo json_encode($varhost); ?>;

</script>

<script>

new Vue({

  el:"#masterMenuApp",

  data:{

    adminNombre:window.ADMIN_NOMBRE,
    logoUrl:window.VARHOST + "/public/ico/logo-admin.png",

    menus:[],
    menusFiltrados:[],
    filtro:"",

    misDatos:{
      rol:"",
      mercado:"",
      negocio:""
    },

    cargandoMisDatos:false

  },

  created(){

    this.cargarMenus()

  },

  mounted(){

    $("#modalMisDatos").on("show",()=>{
      this.cargarMisDatos()
    })

    $("#modalBusquedaMenu").on("show",()=>{
      this.filtro=""
      this.menusFiltrados=this.menus
    })

  },

  methods:{

    cargarMenus(){

      axios.get(API_HOST + "/menus/por-rol/" + ROL_ID)

      .then(res=>{

        if(res.data && res.data.ok){

          this.menus=(res.data.menus || []).map(m=>({

            ...m,

            titulo:m.titulo,

            lista_submenu:(m.lista_submenu || []).map(s=>({
              ...s,
              titulo:s.titulo
            }))

          }))

          this.menusFiltrados=this.menus

        }

      })

    },

    cargarMisDatos(){

      this.cargandoMisDatos=true

      axios.get(API_HOST + "/usuario/mis-datos")

      .then(r=>{

        if(r.data && r.data.ok){

          this.misDatos=r.data.datos

        }

      })

      .finally(()=>{

        this.cargandoMisDatos=false

      })

    },

    filtrar(){

      const q=this.filtro.trim().toLowerCase()

      if(!q){
        this.menusFiltrados=this.menus
        return
      }

      this.menusFiltrados=this.menus

      .map(m=>{

        const matchMenu=(m.titulo || "").toLowerCase().includes(q)

        const subs=(m.lista_submenu || []).filter(s=>
          (s.titulo || "").toLowerCase().includes(q) ||
          (s.url || "").toLowerCase().includes(q)
        )

        if(matchMenu || subs.length){

          return{
            ...m,
            lista_submenu:subs.length ? subs : m.lista_submenu
          }

        }

        return null

      })

      .filter(Boolean)

    },

    abrirMisDatos(){

      $("#modalMisDatos").modal({
        backdrop:false
      });

    },

    urlCompleta(u){

      if(!u) return APPHOST

      return u.startsWith("http")
        ? u
        : APPHOST + u

    },

    linkAttrs(s){

      const t=(s.target && s.target !== "1")
        ? s.target
        : null

      return t ? {target:t} : {}

    }

  }

})

</script>