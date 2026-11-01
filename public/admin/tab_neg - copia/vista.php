<!-- =========================================
     NEGOCIOS + MERCADOS (Bootstrap 2.3.2 / jQuery2 / Vue2 / Axios / DataTables)
========================================= -->

<div class="row-fluid" id="appNeg">
  <div class="span12">

    <div class="titulo-fijo clearfix">

      <div style="float:left;">
        <h2 style="margin:0;">Negocios</h2>
      </div>

      <div class="btn-group pull-right">
        <button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
          <i class="fa fa-bandcamp"></i>
          <span class="caret"></span>
        </button>

        <ul class="dropdown-menu pull-right">
          <li>
            <a href="#" @click.prevent="abrirModalCrearNeg">
              <i class="fa fa-arrow-circle-right"></i> Agregar Neg
            </a>
          </li>

          <li>
            <a href="#" @click.prevent="abrirModalMercados">
              <i class="fa fa-arrow-circle-right"></i> Mercados
            </a>
          </li>
          <li>
            <a
              href="#"
              @click.prevent="abrirModalCategoriasMercado"
            >
              <i class="fa fa-tags"></i>
              Categorías Mercado
            </a>
          </li>
        </ul>
      </div>
    </div>
    <div class="span12 tabla_esp_sup">
      <!-- TABLA NEGOCIOS -->
      <table id="tablaNeg" class="table table-bordered table-condensed">
        <thead>
          <tr>
            <th>ID</th>
            <th>Logo</th>            
            <th>Nombre</th>
            <th>Puesto</th>
            <th>Mercado</th>
            <th>Rubros</th>
            <th>Activo</th>            
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>  
    <!-- =========================
         MODAL CREAR NEG
    ========================== -->
    <div id="modalCrearNeg" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Nuevo Negocio</h3>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="nuevoNeg.nombre" class="input-xxlarge" placeholder="Nombre del negocio">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Puesto</label>
          <div class="controls">
            <input v-model="nuevoNeg.puesto" class="input-large" placeholder="Ej: A-12">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Mercado</label>
          <div class="controls">
            <!-- vue-select -->
            <v-select
              :options="mercadosOptions"              
              label="nombre"
              placeholder="Selecciona un mercado..."
              v-model="nuevoNeg.mercado_id"
              style="width: 420px;"
            ></v-select>
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Rubros</label>

          <div class="controls">

            <v-select
              multiple
              :options="rubrosOptions"
              label="nombre"
              v-model="nuevoNeg.rubros"
              placeholder="Selecciona rubros..."
              style="width:420px;"
            ></v-select>

          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearNeg">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =========================
         MODAL EDITAR NEG
    ========================== -->
    <div id="modalEditarNeg" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Editar Negocio</h3>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="formNeg.nombre" class="input-xxlarge" placeholder="Nombre del negocio">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Puesto</label>
          <div class="controls">
            <input v-model="formNeg.puesto" class="input-large" placeholder="Ej: A-12">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Mercado</label>
          <div class="controls">
            <v-select
              :options="mercadosOptions"              
              label="nombre"
              placeholder="Selecciona un mercado..."
              v-model="formNeg.mercado_id"
              style="width: 420px;"
            ></v-select>
          </div>
        </div>


        <div class="control-group">
          <label class="control-label">Rubros</label>

          <div class="controls">

            <v-select
              multiple
              :options="rubrosOptions"
              label="nombre"
              v-model="formNeg.rubros"
              placeholder="Selecciona rubros..."
              style="width:420px;"
            ></v-select>

          </div>
        </div>        

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarNeg">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- =========================
         MODAL PROPIETARIO (NEGXUSU)
    ========================== -->
    <div id="modalPropietario" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Propietario del Negocio</h3>
        <div style="margin-top:6px; color:#777;">
          Negocio: <b>{{ negProp.nombre }}</b> <span v-if="negProp.neg_id">(#{{ negProp.neg_id }})</span>
        </div>
      </div>

      <div class="modal-body">

  <!-- BUSCADOR -->

  <div class="control-group">

    <label class="control-label">
      Código de usuario
    </label>

    <div
      class="controls"
      style="
        display:flex;
        gap:8px;
        align-items:center;
      "
    >

      <input
        type="text"
        class="span12"
        v-model="propietario.cod_usu"
        placeholder="Ejemplo: USR002"
      />

      <button
        class="btn btn-primary"
        @click="buscarUsuarioCodUsu"
      >

        <i class="icon-search icon-white"></i>

        Buscar

      </button>

    </div>

  </div>

  <!-- USUARIO ENCONTRADO -->

  <div
    v-if="propietario.usuario"
    class="well"
    style="margin-top:15px;"
  >

    <div
      style="
        display:flex;
        gap:12px;
      "
    >

      <img
        :src="propietario.usuario.img_perfil || 'https://barsi-img.b-cdn.net/recursos/sg3f.png'"
        style="
          width:70px;
          height:70px;
          border-radius:14px;
          object-fit:cover;
        "
      >

      <div style="flex:1;">

        <div style="font-weight:bold;">
          {{ propietario.usuario.nombres_apellidos }}
        </div>

        <div>
          @{{ propietario.usuario.sobrenombre }}
        </div>

        <div>
          {{ propietario.usuario.cod_usu }}
        </div>

        <div>
          {{ propietario.usuario.celular }}
        </div>

        <div>
          {{ propietario.usuario.email }}
        </div>

      </div>

      <div>

        <button
          class="btn btn-success"
          @click="asignarPropietario"
        >

          Asignar

        </button>

      </div>

    </div>

  </div>

  <!-- LISTA -->

  <hr>

  <h4>
    Propietarios actuales
  </h4>

  <div
    v-if="listaPropietarios.length <= 0"
    class="alert"
  >

    No hay propietarios asignados

  </div>

  <div
    v-for="p in listaPropietarios"
    :key="p.negxusu_id"
    class="well"
    style="margin-top:10px;"
  >

    <div
      style="
        display:flex;
        gap:12px;
      "
    >

      <img
        :src="p.img_perfil || 'https://barsi-img.b-cdn.net/recursos/sg3f.png'"
        style="
          width:60px;
          height:60px;
          border-radius:14px;
          object-fit:cover;
        "
      >

      <div style="flex:1;">

        <div style="font-weight:bold;">
          {{ p.nombres_apellidos }}
        </div>

        <div>
          @{{ p.sobrenombre }}
        </div>

        <div>
          {{ p.cod_usu }}
        </div>

        <div>
          {{ p.celular }}
        </div>

        <div>
          {{ p.email }}
        </div>

      </div>

      <div>

        <button
          class="btn btn-danger btn-mini"
          @click="eliminarAsignacion(p)"
        >

          Eliminar asignación

        </button>

      </div>

    </div>

  </div>

</div>

      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- =========================
         MODAL MERCADOS (LISTA)
    ========================== -->
    <div id="modalMercados" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Mercados</h3>
      </div>

      <div class="modal-body">
        <table id="tablaMercados" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Dirección</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <div class="modal-footer">
        <button class="btn btn-success" @click="abrirModalCrearMercadoDesdeLista">
          <i class="icon-plus icon-white"></i> Agregar
        </button>
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>

    <!-- =========================
         MODAL CREAR MERCADO
    ========================== -->
    <div id="modalCrearMercado" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">×</button>
        <h3>Nuevo Mercado</h3>
      </div>

      <div class="modal-body">
        <div class="control-group">
          <label class="control-label">Nombre</label>
          <div class="controls">
            <input v-model="nuevoMercado.nombre" class="input-xxlarge" placeholder="Nombre del mercado">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Dirección</label>
          <div class="controls">
            <input v-model="nuevoMercado.direccion" class="input-xxlarge" placeholder="Dirección">
          </div>
        </div>

        <div class="control-group">
          <label class="control-label">Activo</label>
          <div class="controls">
            <select v-model="nuevoMercado.is_activo" class="input-small">
              <option :value="1">SI</option>
              <option :value="0">NO</option>
            </select>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearMercado">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    

    <div id="modalRubro" class="modal hide fade fullscreen">
        <div class="modal-header">
          <button class="close" data-dismiss="modal">×</button>
          <h3>Rubros del negocio</h3>
          <div>Negocio: <b>{{ negRubro.nombre }}</b></div>
        </div>

        <div class="modal-body">
          <table id="tablaRubros" class="table table-bordered table-condensed">
            <thead>
              <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Acción</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <div class="modal-footer">
          <button class="btn btn-success" @click="abrirModalAgregarRubro">
            + Rubro
          </button>
          <button class="btn" data-dismiss="modal">Cerrar</button>
        </div>
      </div>


      <div id="modalAgregarRubro" class="modal hide fade fullscreen">
        <div class="modal-header">
          <button class="close" data-dismiss="modal">×</button>
          <h3>Agregar Rubro</h3>
        </div>

        <div class="modal-body">
          <v-select
            :options="rubrosOptions"
            :reduce="r => r.rubro_id"
            label="nombre"
            v-model="nuevoRubro.rubro_id"
            placeholder="Selecciona rubro..."
            style="width:400px;"
          ></v-select>
        </div>

        <div class="modal-footer">
          <button class="btn btn-primary" @click="guardarRubro">Guardar</button>
          <button class="btn" data-dismiss="modal">Cancelar</button>
        </div>
      </div>

    <!-- =========================
         MODAL EDITAR MERCADO
    ========================== -->
    <!-- =========================
     MODAL EDITAR MERCADO
========================== -->
<div id="modalEditarMercado" class="modal hide fade fullscreen" tabindex="-1">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal">×</button>
    <h3>Editar Mercado</h3>
  </div>

  <div class="modal-body">

    <!-- NOMBRE -->
    <div class="control-group">
      <label class="control-label">Nombre</label>
      <div class="controls">
        <input 
          v-model="formMercado.nombre" 
          class="input-xxlarge" 
          placeholder="Nombre del mercado"
        >
      </div>
    </div>

    <!-- DIRECCION -->
    <div class="control-group">
      <label class="control-label">Dirección</label>
      <div class="controls">
        <input 
          v-model="formMercado.direccion" 
          class="input-xxlarge" 
          placeholder="Dirección"
        >
      </div>
    </div>

    <div class="control-group">

    <label class="control-label">
      Categoría
    </label>

    <div class="controls">

      <v-select

        :options="catMercadoOptions"

        label="nombre"

        v-model="formMercado.cat_mercado"

        placeholder="Seleccione categoría"

        style="width:420px;"

      ></v-select>

    </div>

  </div>

    <!-- ACTIVO -->
    <div class="control-group">
      <label class="control-label">Activo</label>
      <div class="controls">
        <select v-model="formMercado.is_activo" class="input-small">
          <option :value="1">SI</option>
          <option :value="0">NO</option>
        </select>
      </div>
    </div>

    <!-- LOGO -->
    <div class="control-group">
      <label class="control-label">Logo</label>

      <div class="controls">

        <input 
          v-model="formMercado.logo"
          class="input-xxlarge"
          placeholder="https://..."
        >

        <!-- preview -->
        <div style="margin-top:10px;">

          <img 
            v-if="formMercado.logo"
            :src="formMercado.logo"
            style="
              width:120px;
              height:120px;
              object-fit:cover;
              border-radius:16px;
              border:1px solid #ddd;
              background:#fff;
            "
          >

        </div>

      </div>
    </div>

    <!-- COLOR -->
    <div class="control-group">
      <label class="control-label">Top Navbar Color</label>

      <div class="controls" style="display:flex; gap:10px; align-items:center;">

        <input 
          v-model="formMercado.topnavbar_color"
          class="input-medium"
          placeholder="#FD7635"
        >

        <!-- preview -->
        <div
          :style="{
            width:'45px',
            height:'45px',
            borderRadius:'10px',
            border:'1px solid #ccc',
            background: formMercado.topnavbar_color || '#fff'
          }"
        ></div>

      </div>
    </div>

    <!-- PATRON FONDO -->
    <div class="control-group">
      <label class="control-label">Patrón Fondo (CSS)</label>

      <div class="controls">

        <textarea
          v-model="formMercado.patron_fondo"
          class="input-xxlarge"
          rows="10"
          placeholder="background-color: #fff;"
        ></textarea>

        <!-- preview -->
        <div style="margin-top:12px;">

          <div
            :style="cssToObject(formMercado.patron_fondo)"
            style="
              width:100%;
              height:180px;
              border-radius:16px;
              border:1px solid #ddd;
            "
          ></div>

        </div>

      </div>
    </div>

  </div>

  <div class="modal-footer">
    <button class="btn btn-primary" @click="guardarMercado">
      Guardar
    </button>

    <button class="btn" data-dismiss="modal">
      Cancelar
    </button>
  </div>
</div>    


<div
  id="modalCategoriasMercado"
  class="modal hide fade fullscreen"
  tabindex="-1"
>

  <div class="modal-header">

    <button
      type="button"
      class="close"
      data-dismiss="modal"
    >
      ×
    </button>

    <h3>
      Categorías de Mercado
    </h3>

  </div>

  <div class="modal-body">

    <table
      id="tablaCategoriasMercado"
      class="table table-bordered table-condensed"
    >

      <thead>

        <tr>

          <th>ID</th>

          <th>Nombre</th>

          <th>Visible</th>

          <th>Activo</th>

          <th>Acciones</th>

        </tr>

      </thead>

      <tbody></tbody>

    </table>

  </div>

  <div class="modal-footer">

    <button
      class="btn btn-success"
      @click="abrirModalCrearCategoriaMercado"
    >

      <i class="icon-plus icon-white"></i>

      Agregar

    </button>

    <button
      class="btn"
      data-dismiss="modal"
    >

      Cerrar

    </button>

  </div>

</div>

<div
    id="modalEditarCategoriaMercado"
    class="modal hide fade"
    tabindex="-1"
>

    <div class="modal-header">

        <button
            type="button"
            class="close"
            data-dismiss="modal"
        >
            ×
        </button>

        <h3>
            Editar Categoría
        </h3>

    </div>

    <div class="modal-body">

        <label>
            Nombre
        </label>

        <input

            v-model="formCatMercado.nombre"

            type="text"

            class="input-xxlarge"

        >

    </div>

    <div class="modal-footer">

        <button

            class="btn btn-primary"

            @click="guardarCategoriaMercado"

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

<div
  id="modalCrearCategoriaMercado"
  class="modal hide fade fullscreen"
>

  <div class="modal-header">

    <button
      class="close"
      data-dismiss="modal"
    >
      ×
    </button>

    <h3>
      Nueva Categoría Mercado
    </h3>

  </div>

  <div class="modal-body">

    <div class="control-group">

      <label class="control-label">

        Nombre

      </label>

      <div class="controls">

        <input
          v-model="nuevoCatMercado.nombre"
          class="input-xxlarge"
        >

      </div>

    </div>

  </div>

  <div class="modal-footer">

    <button
      class="btn btn-primary"
      @click="crearCategoriaMercado"
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

<div
  id="modalMembresia"
  class="modal hide fade fullscreen"
>

  <div class="modal-header">

    <button
      class="close"
      data-dismiss="modal"
    >
      ×
    </button>

    <h3>
      Membresías
    </h3>

    <div>
      Negocio:
      <b>
        {{ negMembresia.nombre }}
      </b>
    </div>

  </div>

  <div class="modal-body">

    <h4>
      Nueva membresía
    </h4>

    <div class="control-group">

      <label>
        Motivo
      </label>

     <select
        v-model="nuevoPago.motivo"
        @change="cambiarFechasMembresia"
      >

        <option value="FREE">
          FREE
        </option>

        <option value="MENSUAL">
          MENSUAL
        </option>

        <option value="PAGO_ANUAL">
          PAGO_ANUAL
        </option>

        <option value="SLIDER_AI">
          SLIDER_AI
        </option>

      </select>

    </div>

    <div class="control-group">

      <label>
        Monto
      </label>

      <input
        type="number"
        step="0.01"
        v-model="nuevoPago.monto"
      >

    </div>

    <div class="control-group">

      <label>
        Fecha inicio
      </label>

      <input
        type="date"
        v-model="nuevoPago.fecha_inicio_premium"
      >

    </div>

    <div class="control-group">

      <label>
        Fecha fin
      </label>

      <input
        type="date"
        v-model="nuevoPago.fecha_fin_premium"
      >

    </div>

    <button
      class="btn btn-success"
      @click="guardarMembresia"
    >
      Agregar
    </button>

    <hr>

    <table
      id="tablaMembresias"
      class="table table-bordered table-condensed"
    >

      <thead>

        <tr>

          <th>ID</th>

          <th>Motivo</th>

          <th>Monto</th>

          <th>Inicio</th>

          <th>Fin</th>

          <th>Habilitado</th>
          <th> ⚙️ </th>

        </tr>

      </thead>

      <tbody></tbody>

    </table>

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


<!-- =========================
     MODAL SUBIR LOGO
========================== -->

<div id="modalLogoNeg" class="modal hide fade fullscreen" tabindex="-1">

  <div class="modal-header">

    <button type="button" class="close" data-dismiss="modal">
      ×
    </button>

    <h3>
      Logo del negocio
    </h3>

  </div>

  <div class="modal-body">

    <!-- PREVIEW ACTUAL -->

    <div style="margin-bottom:20px;">

      <div style="font-weight:bold; margin-bottom:8px;">
        Logo actual
      </div>

      <img
        :src="logoNeg.preview || 'https://barsi-img.b-cdn.net/recursos/sg3f.png'"
        style="
          width:160px;
          height:160px;
          object-fit:cover;
          border-radius:18px;
          border:1px solid #ddd;
          background:#fff;
        "
      >

    </div>

    <!-- INPUT -->

    <div class="control-group">

      <label class="control-label">
        Seleccionar imagen
      </label>

      <div class="controls">

        <input
          type="file"
          accept="image/*"
          @change="onSelectLogo"
        >

      </div>

    </div>

    <!-- PREVIEW NUEVA -->

    <div v-if="logoNeg.nueva_preview">

      <div style="font-weight:bold; margin-bottom:8px;">
        Nueva imagen
      </div>

      <img
        :src="logoNeg.nueva_preview"
        style="
          width:160px;
          height:160px;
          object-fit:cover;
          border-radius:18px;
          border:1px solid #ddd;
          background:#fff;
        "
      >

    </div>

  </div>

  <div class="modal-footer">

    <button
      class="btn btn-warning"
      @click="logoDefecto"
    >
      Defecto
    </button>

    <button
      class="btn btn-info"
      @click="logoRandom"
    >
      Random
    </button>

    <button
      class="btn btn-primary"
      @click="subirLogo"
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


  </div>
</div>

<script>
/* =========================================================
   Registrar componente vue-select (vue-select-2-6-4.min.js)
   (asumiendo que expone window.VueSelect.VueSelect)
========================================================= */
Vue.component('v-select', VueSelect.VueSelect);

const appNeg = new Vue({
  el: '#appNeg',
  data: {
    apphost: (typeof apphost !== 'undefined' ? apphost : ''),

    // data negocios
    negs: [],
    logoNeg: {

      neg_id: 0,

      preview: '',

      file: null,

      nueva_preview: ''

    },
    catMercadoOptions: [],
    dtNeg: null,
    formCategoria: {
      nombre: '',
      icono: '',
      is_activo: 1
    },
    dtCat:null,
    /* =========================================
       NUEVO FORMULARIO
    ========================================= */

    nuevoNeg: {

      nombre: '',

      puesto: '',

      mercado_id: null,

      rubros: [] // 🔥 NUEVO

    },

    formNeg: {

      neg_id: 0,

      nombre: '',

      puesto: '',

      mercado_id: null,

      rubros: [] // 🔥 NUEVO

    },

    categorias: [],
    categoriasOptions: [],

    // data mercados
    mercados: [],
    mercadosOptions: [],
    dtMerc: null,
    nuevoMercado: { nombre: '', direccion: '', is_activo: 1 },
    formMercado: {
      mercado_id: 0,
      cat_mercado: null,
      nombre: '',
      direccion: '',
      is_activo: 1,
      logo: '',
      topnavbar_color: '',
      patron_fondo: ''
    },

    formCatMercado: {

        cat_mercado_id: 0,

        nombre: '',

        is_visible: 1,

        is_activo: 1

    },

    negMembresia: {

      neg_id: 0,

      nombre: ''

    },

    membresias: [],

    dtMembresia: null,

    nuevoPago: {

      motivo: 'MENSUAL',

      monto: 0,

      fecha_inicio_premium: '',

      fecha_fin_premium: '',

      is_aprobado: 1

    },

    // propietario
    negProp: {

      neg_id: 0,

      nombre: ''

    },
    propietario: {

      cod_usu: '',

      usuario: null

    },

    categoriasMercado: [],

    dtCatMercado: null,

    nuevoCatMercado: {

      nombre: '',

      is_visible: 1,

      is_activo: 1

    },

    listaPropietarios: [],
    propDni: '',
    panelUsu: {
      visible: false,
      usu_id: 0,
      dni: '',
      nombres_apellidos: '',
      is_activo: 0,
      negxusu_id: 0
    },
    // rubros
    negRubro: { neg_id: 0, nombre: '' },
    rubrosNeg: [],
    dtRubro: null,
    nuevoRubro: { rubro_id: null },
    rubrosOptions: []
  },

  methods: {
    /* =========================
       Helpers UI
    ========================== */
    bloquear(msg) {
      $.blockUI({
        message: `<h4>${msg}</h4>`,
        css: { border:'none', padding:'15px', background:'#000', opacity:.6, color:'#fff' }
      });
    },

    /* =========================
       MERCADOS (cargar para combos y lista)
    ========================== */
    cargarMercadosParaCombo() {

      return axios.get(`${this.apphost}/mercado/listar`)
        .then(r => {

          this.mercados = r.data.data || [];

          this.mercadosOptions = this.mercados.map(m => ({
            mercado_id: parseInt(m.mercado_id),
            nombre: m.nombre,
            direccion: m.direccion,
            is_activo: parseInt(m.is_activo)
          }));

        });

    },

    listarMembresias(){

  this.bloquear(
    'Cargando membresías...'
  );

  return axios.post(

    `${this.apphost}/neg_pago/listar`,

    {

      neg_id:
        this.negMembresia.neg_id

    }

  )
  .then(r=>{

    this.membresias =
      r.data.rows || [];

    this.$nextTick(()=>{

      if(!this.dtMembresia){

        this.dtMembresia =
          $('#tablaMembresias')
          .DataTable({

            scrollX: true,

            destroy: true,

            pageLength: 25,

            order: [[0,'desc']]

          });

        const self = this;

        $('#tablaMembresias tbody')

          .on(
            'change',
            '.toggle-aprobado',
            function(e){

              e.preventDefault();

              const chk =
                $(this);

              const id =
                chk.data('id');

              apprise(

                '¿Cambiar estado de aprobación?',

                {
                  confirm:true
                },

                function(ok){

                  if(!ok){

                    chk.prop(
                      'checked',
                      !chk.prop('checked')
                    );

                    return;
                  }

                  axios.post(

                    `${self.apphost}/neg_pago/toggle_aprobado`,

                    {

                      neg_pago_id:id

                    }

                  )
                  .then(()=>{

                    self.listarMembresias();

                  });

                }

              );

            }
          )

          .on(
            'click',
            '.eliminar-membresia',
            function(e){

              e.preventDefault();

              const neg_pago_id =
                $(this).data('id');

              apprise(
                '¿Eliminar membresía?',
                {
                  confirm:true
                },
                function(ok){

                  if(!ok){
                    return;
                  }

                  axios.post(

                    `${self.apphost}/neg_pago/eliminar`,

                    {

                      neg_pago_id

                    }

                  )
                  .then(()=>{

                    apprise(
                      'Registro eliminado'
                    );

                    self.listarMembresias();

                  });

                }

              );

            }
          );

      }

      this.dtMembresia.clear();

        this.membresias.forEach(m=>{

          const btnEliminar = `

          <button
            class="
              btn
              btn-mini
              btn-danger
              eliminar-membresia
            "
            data-id="${m.neg_pago_id}"
            title="Eliminar"
          >

            <i class="icon-white icon-trash"></i>

          </button>

          `;

          const chkAprobado = `

            <input
              type="checkbox"
              class="toggle-aprobado"
              data-id="${m.neg_pago_id}"
              ${parseInt(m.is_aprobado) ? 'checked' : ''}
            >

            `;

          this.dtMembresia.row.add([

            m.neg_pago_id,

            m.motivo,

            parseFloat(
              m.monto || 0
            ).toFixed(2),

            m.fecha_inicio_premium || '',

            m.fecha_fin_premium || '',

            chkAprobado,

            btnEliminar

          ]);

        });

        this.dtMembresia.draw(false);

      });

    })
    .catch(err=>{

      console.error(err);

      apprise(
        'Error al cargar membresías'
      );

    })
    .finally(()=>{

      $.unblockUI();

    });

  },

    abrirModalRubro(n){
      this.negRubro = {
        neg_id: parseInt(n.neg_id,10),
        nombre: n.nombre
      };

      this.listarRubrosNeg().then(()=>{
        $('#modalRubro').modal('show');
      });
    },
   
    listarRubrosNeg(){
      this.bloquear('Cargando rubros...');

      return axios.get(`${this.apphost}/rubroxneg/listar`, {
        params:{ neg_id:this.negRubro.neg_id }
      })
      .then(r=>{
        this.rubrosNeg = r.data.data || [];

        this.$nextTick(()=>{

          if(!this.dtRubro){

            this.dtRubro = $('#tablaRubros').DataTable({
              scrollX:true,
              destroy:true
            });

            const self = this;

            $('#tablaRubros tbody').on('click','a.eliminar-rubro',function(e){
              e.preventDefault();
              const id = $(this).data('id');
              self.eliminarRubro(id);
            });

          }

          this.dtRubro.clear();

          this.rubrosNeg.forEach(r=>{

            const btn = `
              <a href="#" class="eliminar-rubro" data-id="${r.rubroxneg_id}">
                <i class="fa fa-trash"></i>
              </a>
            `;

            this.dtRubro.row.add([
              r.rubro_id,
              r.nombre,
              btn
            ]);

          });

          this.dtRubro.draw(false);

        });

      })
      .finally(()=>$.unblockUI());
    },

    abrirModalAgregarRubro(){

      this.nuevoRubro = { rubro_id:null };

      this.bloquear('Cargando rubros...');

      axios.get(`${this.apphost}/rubro/listar`)
        .then(r=>{
          this.rubrosOptions = r.data.data || [];
          $('#modalRubro').modal('hide');
          $('#modalAgregarRubro').modal('show');
        })
        .finally(()=>$.unblockUI());
    },

    guardarRubro(){

      if(!this.nuevoRubro.rubro_id){
        return apprise('Selecciona un rubro');
      }

      this.bloquear('Guardando...');

      axios.post(`${this.apphost}/rubroxneg/crear`,{
        neg_id:this.negRubro.neg_id,
        rubro_id: typeof this.nuevoRubro.rubro_id === 'object'
        ? parseInt(this.nuevoRubro.rubro_id.rubro_id)
        : parseInt(this.nuevoRubro.rubro_id)
      })
      .then(()=>{
        apprise('Rubro agregado');
        $('#modalAgregarRubro').modal('hide');
      })
      .finally(()=>{
        $.unblockUI();
        this.listarRubrosNeg().then(()=>{
          $('#modalRubro').modal('show');
        });
      });
    },


    eliminarRubro(id){

      apprise('¿Eliminar rubro?',{confirm:true}, ok=>{

        if(!ok) return;

        this.bloquear('Eliminando...');

        axios.post(`${this.apphost}/rubroxneg/eliminar`,{
          rubroxneg_id:id
        })
        .then(()=>{
          apprise('Eliminado');
        })
        .finally(()=>{
          $.unblockUI();
          this.listarRubrosNeg();
        });

      });

    },

    /* =========================
       NEGOCIOS - LISTAR (DataTable principal)
    ========================== */
    
  listarNeg() {

    this.bloquear('Cargando negocios…');

    this.cargarMercadosParaCombo()

      .then(() => axios.get(`${this.apphost}/neg/listar`))

      .then(r => {

        this.negs = r.data.data || [];

        this.$nextTick(() => {

          if (!this.dtNeg) {

            this.dtNeg = $('#tablaNeg').DataTable({

              language: (
                typeof dt_language !== 'undefined'
                  ? dt_language
                  : undefined
              ),

              scrollX: true,

              dom: 'frtip',

              order: [[0,'desc']]

            });

            const self = this;

            $('#tablaNeg tbody')

              .on('click', 'a.editar-neg', function(e){

                e.preventDefault();

                const id = $(this).data('id');

                const row = self.negs.find(
                  x => parseInt(x.neg_id,10) === parseInt(id,10)
                );

                if (row) {
                  self.abrirModalEditarNeg(row);
                }

              })

              .on(
                'click',
                'a.membresia-neg',
                function(e){

                  e.preventDefault();

                  const id =
                    $(this).data('id');

                  const row =
                    self.negs.find(
                      x =>
                        parseInt(x.neg_id)
                        ===
                        parseInt(id)
                    );

                  if(row){

                    self.abrirModalMembresia(
                      row
                    );

                  }

                }
              )

              .on('change', '.toggle-activo-neg', function(){

                  const neg_id = $(this).data('id')

                  const is_activo =
                    $(this).is(':checked')
                      ? 1
                      : 0

                  axios.post(

                    `${self.apphost}/neg/activo`,

                    {

                      neg_id,
                      is_activo

                    }

                  )
                  .then(()=>{

                    apprise('Estado actualizado')

                  })
                  .catch(()=>{

                    apprise('Error al actualizar')

                  })

              })              

              .on('click', 'a.eliminar-neg', function(e){

                e.preventDefault();

                const id = $(this).data('id');

                const row = self.negs.find(
                  x => parseInt(x.neg_id,10) === parseInt(id,10)
                );

                if (row) {
                  self.eliminarNeg(row);
                }

              })

              .on('click', 'a.logo-neg', function(e){

                e.preventDefault();

                const id = $(this).data('id');

                const row = self.negs.find(
                  x => parseInt(x.neg_id,10) === parseInt(id,10)
                );

                if (row) {

                  self.abrirModalLogo(row);

                }

              })

              .on('click', 'a.propietario-neg', function(e){

                e.preventDefault();

                const id = $(this).data('id');

                const row = self.negs.find(
                  x => parseInt(x.neg_id,10) === parseInt(id,10)
                );

                if (row) {
                  self.abrirModalPropietario(row);
                }

              });

          }

          this.dtNeg.clear();

          this.negs.forEach(n => {

            const mercadoNombre =
              this.obtenerNombreMercado(n.mercado_id);

            const activo = `

              <label class="switch-mini">

                <input
                  type="checkbox"
                  class="toggle-activo-neg"
                  data-id="${n.neg_id}"
                  ${parseInt(n.is_activo,10) ? 'checked' : ''}
                >

                <span></span>

              </label>

            `;

            /* =========================================
               RUBROS BONITOS 😏
            ========================================= */

            let rubrosHtml = `
              <span class="label">
                Sin rubros
              </span>
            `;

            if(
              Array.isArray(n.rubros_obj)
              &&
              n.rubros_obj.length > 0
            ){

              rubrosHtml = n.rubros_obj.map(r => `

                <span
                  class="label label-info"
                  style="
                    margin-right:4px;
                    margin-bottom:4px;
                    display:inline-block;
                    padding:4px 8px;
                    border-radius:12px;
                    font-size:11px;
                  "
                >

                  <span style="font-size:14px;">
                    ${r.icono || '📦'}
                  </span>

                  ${r.nombre}

                </span>

              `).join('');

            }

            const actions = `

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
                      class="editar-neg"
                      data-id="${n.neg_id}">
                      Editar
                    </a>
                  </li>

                  <li>
                    <a href="#"
                      class="eliminar-neg"
                      data-id="${n.neg_id}">
                      Eliminar
                    </a>
                  </li>

                  <li class="divider"></li>

                  <li>
                    <a href="#"
                      class="propietario-neg"
                      data-id="${n.neg_id}">
                      Propietario
                    </a>
                  </li>

                  <li>
                    <a href="#"
                      class="logo-neg"
                      data-id="${n.neg_id}">
                      Subir logo
                    </a>
                  </li>
              <li>
                <a href="#"
                  class="membresia-neg"
                  data-id="${n.neg_id}">
                  Membresía
                </a>
              </li>

                </ul>

              </div>

            `

            const logo = `

                <img
                  src="${
                    n.img_logo
                    ||
                    'https://barsi-img.b-cdn.net/recursos/sg3f.png'
                  }"
                  style="
                    width:55px;
                    height:55px;
                    object-fit:cover;
                    border-radius:14px;
                    border:1px solid #ddd;
                    background:#fff;
                  "
                >

              `;

              this.dtNeg.row.add([          

                n.neg_id,
                logo,
                n.nombre || '',

                n.puesto || '',

                mercadoNombre || '',

                rubrosHtml,

                activo,

                actions

              ]);

          });

          this.dtNeg.draw(false);

        });

      })

      .finally(() => $.unblockUI());

  },

    obtenerNombreMercado(mercado_id) {
      const mid = parseInt(mercado_id, 10);
      const m = this.mercadosOptions.find(x => parseInt(x.mercado_id,10) === mid);
      return m ? m.nombre : '';
    },

    /* =========================================
       CARGAR RUBROS
    ========================================= */

    cargarRubros(){

      return axios.get(`${this.apphost}/rubro/listar`)
        .then(r=>{

          this.rubrosOptions = r.data.data || [];

        });

    },    

    /* =========================
       NEGOCIOS - MODALES / CRUD
    ========================== */
    abrirModalCrearNeg() {
      this.nuevoNeg = { nombre: '', puesto: '', mercado_id: null };
      $('#modalCrearNeg').modal('show');
    },

    /* =========================================
       CREAR NEG
    ========================================= */

    crearNeg(){

      if (!this.nuevoNeg.nombre.trim()) {
        return apprise('Escribe el nombre')
      }

      if (!this.nuevoNeg.puesto.trim()) {
        return apprise('Escribe el puesto')
      }

      if (!this.nuevoNeg.mercado_id) {
        return apprise('Selecciona un mercado')
      }

      const mercado_id = typeof this.nuevoNeg.mercado_id === 'object'
        ? this.nuevoNeg.mercado_id.mercado_id
        : this.nuevoNeg.mercado_id

      const payload = {

        nombre: this.nuevoNeg.nombre.trim(),

        puesto: this.nuevoNeg.puesto.trim(),

        mercado_id: mercado_id,

        rubros: (this.nuevoNeg.rubros || [])
          .map(r => r.rubro_id)

      }

      this.bloquear('Creando negocio...')

      axios.post(`${this.apphost}/neg/crear`, payload)

        .then(()=>{

          $('#modalCrearNeg').modal('hide')

          apprise('¡Creado!')

          this.nuevoNeg = {

            nombre: '',

            puesto: '',

            mercado_id: null,

            rubros: []

          }

        })

        .finally(()=>{

          $.unblockUI()

          this.listarNeg()

        })

    },

    buscarUsuarioCodUsu(){

      if(!this.propietario.cod_usu){

        apprise(
          'Ingrese código de usuario'
        )

        return

      }

      this.bloquear(
        'Buscando usuario...'
      )

      axios.post(

        `${this.apphost}/QQvN/buscarUsuarioCodUsu`,

        {

          cod_usu:
            this.propietario.cod_usu

        }

      )
      .then(r=>{

        if(r.data.status=='ok'){

          this.propietario.usuario =
            r.data.usuario

        }

      })
      .catch(()=>{

        apprise(
          'Usuario no encontrado'
        )

      })
      .finally(()=>{

        $.unblockUI()

      })

    },

    /* =========================================
       EDITAR NEG
    ========================================= */

    abrirModalEditarNeg(n) {

      this.bloquear('Cargando categorías...')

      Promise.all([

        this.cargarRubros()

      ])
      .then(()=>{

        this.formNeg = {

          neg_id: n.neg_id,

          nombre: n.nombre,

          puesto: n.puesto,

          mercado_id: this.mercadosOptions.find(
            m => m.mercado_id == n.mercado_id
          ),

          rubros: n.rubros_obj || [] // 🔥

        };

        $('#modalEditarNeg').modal('show')

      })
      .finally(()=>$.unblockUI())
    },

    cssToObject(css) {

      if (!css) return {}

      const obj = {}

      css.split(';').forEach(rule => {

        const parts = rule.split(':')

        if (parts.length < 2) return

        const key = parts[0].trim()
          .replace(/-([a-z])/g, g => g[1].toUpperCase())

        const value = parts.slice(1).join(':').trim()

        if (key && value) {
          obj[key] = value
        }

      })

      return obj
    },    

    /* =========================================
       GUARDAR NEG
    ========================================= */

    guardarNeg() {

      if (!this.formNeg.nombre.trim()) {
        return apprise('Escribe el nombre')
      }

      if (!this.formNeg.puesto.trim()) {
        return apprise('Escribe el puesto')
      }

      if (!this.formNeg.mercado_id) {
        return apprise('Selecciona un mercado')
      }

      const mercado_id = typeof this.formNeg.mercado_id === 'object'
        ? this.formNeg.mercado_id.mercado_id
        : this.formNeg.mercado_id

      const payload = {

        neg_id: this.formNeg.neg_id,

        nombre: this.formNeg.nombre.trim(),

        puesto: this.formNeg.puesto.trim(),

        mercado_id: mercado_id,
        rubros: (this.formNeg.rubros || [])
          .map(r => r.rubro_id)

      }

      this.bloquear('Actualizando negocio…')

      axios.post(`${this.apphost}/neg/editar`, payload)

        .then(()=>{

          $('#modalEditarNeg').modal('hide')

          apprise('¡Actualizado!')

        })

        .finally(()=>{

          $.unblockUI()

          this.listarNeg()

        })

    },

    cargarCategoriasMercado(){

        return axios
            .get(
                `${this.apphost}/WEwr/catmercado/listar`
            )
            .then(r=>{

                this.catMercadoOptions =
                    r.data.data || []

            })

    },

    eliminarNeg(n) {
      apprise(`¿Eliminar negocio <b>#${n.neg_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;
        this.bloquear('Eliminando…');
        axios.post(`${this.apphost}/neg/eliminar`, { neg_id: n.neg_id })
          .then(() => apprise('Eliminado'))
          .finally(() => {
            $.unblockUI();
            this.listarNeg();
          });
      });
    },

    /* =========================
       PROPIETARIO (NEGXUSU)
    ========================== */
    abrirModalPropietario(n) {

      this.negProp = {

        neg_id: parseInt(
          n.neg_id,
          10
        ),

        nombre: (
          n.nombre || ''
        )

      }

      this.propietario = {

        cod_usu: '',

        usuario: null

      }

      this.listaPropietarios = []

      this.listarPropietarios()

      $('#modalPropietario').modal('show')

    },

    listarPropietarios(){

      this.bloquear(
        'Cargando propietarios...'
      )

      axios.post(

        `${this.apphost}/QQvN/neg/propietarios`,

        {

          neg_id:
            this.negProp.neg_id

        }

      )
      .then(r=>{

        this.listaPropietarios =
          r.data.rows || []

      })
      .finally(()=>{

        $.unblockUI()

      })

    },

    abrirModalMembresia(n){

      this.negMembresia = {

        neg_id:
          parseInt(n.neg_id),

        nombre:
          n.nombre

      };

      const hoy = new Date();

      const yyyy =
        hoy.getFullYear();

      const mm =
        String(
          hoy.getMonth() + 1
        ).padStart(2,'0');

      const dd =
        String(
          hoy.getDate()
        ).padStart(2,'0');

      const fechaInicio =
        `${yyyy}-${mm}-${dd}`;

      const fechaFin =
        new Date(hoy);

      fechaFin.setMonth(
        fechaFin.getMonth() + 1
      );

      const yyyy2 =
        fechaFin.getFullYear();

      const mm2 =
        String(
          fechaFin.getMonth() + 1
        ).padStart(2,'0');

      const dd2 =
        String(
          fechaFin.getDate()
        ).padStart(2,'0');

      const fechaFinStr =
        `${yyyy2}-${mm2}-${dd2}`;

      this.nuevoPago = {

        motivo: 'MENSUAL',

        monto: 20,

        fecha_inicio_premium:
          fechaInicio,

        fecha_fin_premium:
          fechaFinStr,

        is_aprobado: 1

      };

      this.listarMembresias()
        .then(()=>{

          $('#modalMembresia')
            .modal('show');

        });

    },  

    guardarMembresia(){

      const hoy = new Date();

      const yyyy =
        hoy.getFullYear();

      const mm =
        String(
          hoy.getMonth() + 1
        ).padStart(2,'0');

      const dd =
        String(
          hoy.getDate()
        ).padStart(2,'0');

      const fechaInicio =
        `${yyyy}-${mm}-${dd}`;

      const fechaFin =
        new Date(hoy);

      switch(
        this.nuevoPago.motivo
      ){

        case 'PAGO_ANUAL':

          fechaFin.setFullYear(
            fechaFin.getFullYear() + 1
          );

          break;

        case 'MENSUAL':

          fechaFin.setMonth(
            fechaFin.getMonth() + 1
          );

          break;

        default:

          fechaFin.setDate(
            fechaFin.getDate() + 30
          );

          break;

      }

      const yyyy2 =
        fechaFin.getFullYear();

      const mm2 =
        String(
          fechaFin.getMonth() + 1
        ).padStart(2,'0');

      const dd2 =
        String(
          fechaFin.getDate()
        ).padStart(2,'0');

      const fechaFinStr =
        `${yyyy2}-${mm2}-${dd2}`;

      this.bloquear(
        'Guardando membresía...'
      );

      axios.post(

        `${this.apphost}/neg_pago/crear`,

        {

          neg_id:
            this.negMembresia.neg_id,

          motivo:
            this.nuevoPago.motivo,

          monto:
            this.nuevoPago.monto,

          fecha_inicio_premium:
            fechaInicio,

          fecha_fin_premium:
            fechaFinStr,

          is_aprobado: 1

        }

      )
      .then(()=>{

        apprise(
          'Membresía registrada'
        );

        this.nuevoPago = {

          motivo:'MENSUAL',

          monto:20,

          fecha_inicio_premium:'',

          fecha_fin_premium:'',

          is_aprobado:1

        };

        this.listarMembresias();

      })
      .catch(err=>{

        console.error(
          err
        );

        apprise(
          'Error al registrar membresía'
        );

      })
      .finally(()=>{

        $.unblockUI();

      });

    },

    /* =========================
       MODAL LOGO
    ========================= */

    abrirModalLogo(n){

      this.logoNeg = {

        neg_id: parseInt(
          n.neg_id,
          10
        ),

        preview: n.img_logo || '',

        file: null,

        nueva_preview: ''

      }

      $('#modalLogoNeg').modal('show')

    },

    onSelectLogo(e){

      const file =
        e.target.files[0]

      if(!file){
        return
      }

      this.logoNeg.file = file

      this.logoNeg.nueva_preview =
        URL.createObjectURL(file)

    },

    subirLogo(){

      apprise(
        'Todavía no implementado 😏'
      )

    },

    logoDefecto(){

      this.bloquear(
        'Actualizando logo...'
      )

      axios.post(

        `${this.apphost}/QQvN/neg/logo/defecto`,

        {

          neg_id:
            this.logoNeg.neg_id

        }

      )
      .then(r=>{

        if(r.data.status=='ok'){

          this.logoNeg.preview =
            r.data.img_logo

          apprise(
            'Logo actualizado'
          )

        }

      })
      .finally(()=>{

        $.unblockUI()

        this.listarNeg()

      })

    },

    abrirModalCrearCategoriaMercado(){

        this.nuevoCatMercado = {

            nombre: '',

            is_visible: 1,

            is_activo: 1

        };

        $('#modalCategoriasMercado').modal('hide');

        $('#modalCrearCategoriaMercado').modal('show');

    },

    crearCategoriaMercado(){

        if(
            !this.nuevoCatMercado.nombre
            ||
            !this.nuevoCatMercado.nombre.trim()
        ){

            return apprise(
                'Escribe el nombre'
            );

        }

        this.bloquear(
            'Creando categoría...'
        );

        axios.post(

            `${this.apphost}/WEwr/catmercado/crear`,

            {

                nombre:
                    this.nuevoCatMercado.nombre.trim(),

                is_visible:
                    parseInt(
                        this.nuevoCatMercado.is_visible
                    ),

                is_activo:
                    parseInt(
                        this.nuevoCatMercado.is_activo
                    )

            }

        )
        .then(r=>{

            if(
                r.data.status == 'ok'
            ){

                apprise(
                    'Categoría creada'
                );

                $('#modalCrearCategoriaMercado')
                    .modal('hide');

                this.nuevoCatMercado = {

                    nombre: '',

                    is_visible: 1,

                    is_activo: 1

                };

            }else{

                apprise(
                    r.data.msg || 'Error'
                );

            }

        })
        .catch(err=>{

            console.error(err);

            apprise(
                'Error al crear categoría'
            );

        })
        .finally(()=>{

            $.unblockUI();

            this.listarCategoriasMercado()
                .then(()=>{

                    $('#modalCategoriasMercado')
                        .modal('show');

                });

        });

    },

      listarCategoriasMercado(){

      console.log('========================================');
      console.log('INICIO listarCategoriasMercado()');
      console.log('apphost:', this.apphost);
      console.log('========================================');

      this.bloquear(
          'Cargando categorías...'
      );

      return axios.get(

          `${this.apphost}/WEwr/catmercado/listar`

      )
      .then(r=>{

          console.log('========================================');
          console.log('RESPUESTA AXIOS');
          console.log(r);
          console.log('========================================');

          console.log('status:',
              r.data.status
          );

          console.log('data:',
              r.data.data
          );

          this.categoriasMercado =
              r.data.data || [];

          console.log('========================================');
          console.log('this.categoriasMercado');
          console.log(this.categoriasMercado);
          console.log(
              'Cantidad:',
              this.categoriasMercado.length
          );
          console.log('========================================');

          this.$nextTick(()=>{

              console.log('========================================');
              console.log('$nextTick ejecutado');
              console.log('========================================');

              console.log(
                  '#tablaCategoriasMercado existe:',
                  $('#tablaCategoriasMercado').length
              );

              console.log(
                  'dtCatMercado actual:'
              );

              console.log(
                  this.dtCatMercado
              );

              if(
                  !this.dtCatMercado
              ){

                  console.log(
                      'Creando DataTable...'
                  );

                  this.dtCatMercado =
                      $('#tablaCategoriasMercado')
                      .DataTable({

                          language:
                              (
                                  typeof dt_language
                                  !== 'undefined'
                              )
                              ? dt_language
                              : undefined,

                          scrollX:true,

                          destroy:true

                      });

                  console.log(
                      'DataTable creado'
                  );

                  console.log(
                      this.dtCatMercado
                  );

                  const self = this;

                  $('#tablaCategoriasMercado tbody')
                  
                  .on(
                      'click',
                      '.editar-catmercado',
                      function(e){

                          e.preventDefault();

                          console.log(
                              '================================'
                          );

                          console.log(
                              'CLICK EDITAR'
                          );

                          console.log(
                              '================================'
                          );

                          const id =
                              $(this).data('id');

                          console.log(
                              'ID:',
                              id
                          );

                          const row =
                              self.categoriasMercado.find(
                                  x =>
                                      parseInt(
                                          x.cat_mercado_id
                                      )
                                      ===
                                      parseInt(
                                          id
                                      )
                              );

                          console.log(
                              'ROW:'
                          );

                          console.log(
                              row
                          );

                          if(!row){

                              console.error(
                                  'NO SE ENCONTRO LA CATEGORIA'
                              );

                              apprise(
                                  'No se encontró la categoría'
                              );

                              return;

                          }

                          self.formCatMercado = {

                              cat_mercado_id:

                                  parseInt(
                                      row.cat_mercado_id
                                  ),

                              nombre:

                                  row.nombre || '',

                              is_visible:

                                  parseInt(
                                      row.is_visible
                                  ) || 0,

                              is_activo:

                                  parseInt(
                                      row.is_activo
                                  ) || 0

                          };

                          console.log(
                              'formCatMercado:'
                          );

                          console.log(
                              self.formCatMercado
                          );

                          console.log(
                              'CERRANDO modalCategoriasMercado'
                          );

                          $('#modalCategoriasMercado')
                              .modal('hide');

                          setTimeout(function(){

                              console.log(
                                  'ABRIENDO modalEditarCategoriaMercado'
                              );

                              $('#modalEditarCategoriaMercado')
                                  .modal('show');

                          },300);

                      }
                  )

                  .on(
                    'change',
                    '.toggle-activo-catmercado',
                    function(){

                        const cat_mercado_id =
                            $(this).data('id');

                        const is_activo =
                            $(this).is(':checked')
                                ? 1
                                : 0;

                        axios.post(

                            `${self.apphost}/WEwr/catmercado/activo`,

                            {

                                cat_mercado_id,

                                is_activo

                            }

                        );

                    }
                )

                  .on(
                      'change',
                      '.toggle-visible-catmercado',
                      function(){

                          const cat_mercado_id =
                              $(this).data('id');

                          const is_visible =
                              $(this).is(':checked')
                                  ? 1
                                  : 0;

                          axios.post(

                              `${self.apphost}/WEwr/catmercado/visible`,

                              {

                                  cat_mercado_id,

                                  is_visible

                              }

                          );

                      }
                  )

                  .on(
                    'click',
                    '.eliminar-catmercado',
                    function(e){

                        e.preventDefault();

                        const id =
                            $(this).data('id');

                        apprise(

                            '¿Eliminar categoría?',

                            {

                                confirm:true

                            },

                            function(ok){

                                if(!ok){
                                    return;
                                }

                                self.bloquear(
                                    'Eliminando...'
                                );

                                axios.post(

                                    `${self.apphost}/WEwr/catmercado/eliminar`,

                                    {

                                        cat_mercado_id:id

                                    }

                                )
                                .then(()=>{

                                    apprise(
                                        'Eliminado'
                                    );

                                    self.listarCategoriasMercado();

                                })
                                .finally(()=>{

                                    $.unblockUI();

                                });

                            }

                        );

                    }
                );

              }

              console.log(
                  'Limpiando DataTable...'
              );

              this.dtCatMercado.clear();

              console.log(
                  'Filas después clear:',
                  this.dtCatMercado.rows().count()
              );

              this.categoriasMercado
              .forEach(c=>{

                  console.log(
                      'Procesando categoría:'
                  );

                  console.log(c);

                  const visible = `

                  <label class="switch-mini">

                      <input
                          type="checkbox"
                          class="toggle-visible-catmercado"
                          data-id="${c.cat_mercado_id}"
                          ${parseInt(c.is_visible) ? 'checked' : ''}
                      >

                      <span></span>

                  </label>

                  `;

                  const activo = `

                    <label class="switch-mini">

                        <input
                            type="checkbox"
                            class="toggle-activo-catmercado"
                            data-id="${c.cat_mercado_id}"
                            ${parseInt(c.is_activo) ? 'checked' : ''}
                        >

                        <span></span>

                    </label>

                    `;

                  const acciones = `

                    <div class="btn-group">

                        <button
                            class="btn btn-mini dropdown-toggle"
                            data-toggle="dropdown"
                        >

                            Opciones

                            <span class="caret"></span>

                        </button>

                        <ul class="dropdown-menu">

                            <li>

                                <a
                                    href="#"
                                    class="editar-catmercado"
                                    data-id="${c.cat_mercado_id}"
                                >

                                    Editar

                                </a>

                            </li>

                            <li>

                                <a
                                    href="#"
                                    class="eliminar-catmercado"
                                    data-id="${c.cat_mercado_id}"
                                >

                                    Eliminar

                                </a>

                            </li>

                        </ul>

                    </div>

                    `;

                  console.log(
                      'Agregando fila:',
                      c.cat_mercado_id
                  );

                  this.dtCatMercado.row.add([

                      c.cat_mercado_id,

                      c.nombre || '',

                      visible,

                      activo,

                      acciones

                  ]);

              });

              console.log(
                  'Filas antes draw:',
                  this.dtCatMercado.rows().count()
              );

              this.dtCatMercado.draw(false);

              console.log(
                  'draw(false) ejecutado'
              );

              console.log(
                  'Filas después draw:',
                  this.dtCatMercado.rows().count()
              );

              console.log('========================================');
              console.log('FIN listarCategoriasMercado()');
              console.log('========================================');

          });

      })
      .catch(err=>{

          console.error('========================================');
          console.error('ERROR AXIOS');
          console.error(err);
          console.error('========================================');

      })
      .finally(()=>{

          console.log(
              'UNBLOCK UI'
          );

          $.unblockUI();

      });

  },

  cambiarFechasMembresia(){

    const hoy =
      new Date();

    const fin =
      new Date(hoy);

    if(
      this.nuevoPago.motivo
      ===
      'PAGO_ANUAL'
    ){

      fin.setFullYear(
        fin.getFullYear() + 1
      );

    }else{

      fin.setMonth(
        fin.getMonth() + 1
      );

    }

    this.nuevoPago.fecha_fin_premium =
      fin.toISOString()
        .substring(0,10);

  },

    guardarCategoriaMercado(){

        if(
            !this.formCatMercado.nombre
            ||
            !this.formCatMercado.nombre.trim()
        ){

            return apprise(
                'Escribe el nombre'
            );

        }

        this.bloquear(
            'Actualizando categoría...'
        );

        axios.post(

            `${this.apphost}/WEwr/catmercado/editar`,

            {

                cat_mercado_id:
                    parseInt(
                        this.formCatMercado.cat_mercado_id
                    ),

                nombre:
                    this.formCatMercado.nombre.trim(),

                is_visible:
                    parseInt(
                        this.formCatMercado.is_visible
                    ),

                is_activo:
                    parseInt(
                        this.formCatMercado.is_activo
                    )

            }

        )
        .then(r=>{

            if(
                r.data.status == 'ok'
            ){

                apprise(
                    'Categoría actualizada'
                );

                $('#modalEditarCategoriaMercado')
                    .modal('hide');

            }else{

                apprise(
                    r.data.msg || 'Error'
                );

            }

        })
        .catch(err=>{

            console.error(err);

            apprise(
                'Error al actualizar'
            );

        })
        .finally(()=>{

            $.unblockUI();

            this.listarCategoriasMercado()
                .then(()=>{

                    $('#modalCategoriasMercado')
                        .modal('show');

                });

        });

    },

    abrirModalCategoriasMercado(){

        this.listarCategoriasMercado()

            .then(()=>{

                $('#modalCategoriasMercado')
                    .modal('show');

            });

    },

    logoRandom(){

      this.bloquear(
        'Generando logo random...'
      )

      axios.post(

        `${this.apphost}/QQvN/neg/logo/random`,

        {

          neg_id:
            this.logoNeg.neg_id

        }

      )
      .then(r=>{

        if(r.data.status=='ok'){

          this.logoNeg.preview =
            r.data.img_logo

          apprise(
            'Logo random aplicado'
          )

        }

      })
      .finally(()=>{

        $.unblockUI()

        this.listarNeg()

      })

    },

    eliminarAsignacion(p){

      if(!confirm(
        '¿Eliminar asignación?'
      )){
        return
      }

      this.bloquear(
        'Eliminando asignación...'
      )

      axios.post(

        `${this.apphost}/QQvN/neg/propietario/eliminar`,

        {

          negxusu_id:
            p.negxusu_id

        }

      )
      .then(()=>{

        apprise(
          'Asignación eliminada'
        )

        this.listarPropietarios()

      })
      .finally(()=>{

        $.unblockUI()

      })

    },

    asignarPropietario(){

      if(
        !this.propietario.usuario
      ){
        return apprise(
          'Busca un usuario'
        )
      }

      this.bloquear(
        'Asignando propietario...'
      )

      axios.post(

        `${this.apphost}/negxusu/asignar`,

        {

          neg_id:
            this.negProp.neg_id,

          usu_id:
            this.propietario.usuario.usu_id

        }

      )
      .then(r=>{

        if(r.data.status=='ok'){

          apprise(
            'Propietario asignado'
          )

          this.propietario = {

            cod_usu: '',

            usuario: null

          }

          this.listarPropietarios()

          this.listarNeg()

        }
        else{

          apprise(
            r.data.msg || 'Error'
          )

        }

      })
      .finally(()=>{

        $.unblockUI()

      })

    },

    eliminarNegxusu() {
      if (!this.panelUsu.negxusu_id) return apprise('No hay asignación para eliminar');

      apprise(`¿Eliminar asignación del negocio <b>#${this.negProp.neg_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;

        this.bloquear('Eliminando asignación…');
        axios.post(`${this.apphost}/negxusu/eliminar`, { negxusu_id: this.panelUsu.negxusu_id })
          .then(() => {
            apprise('Asignación eliminada');
            // refrescar panel y tabla
            this.panelUsu = { visible:false, usu_id:0, dni:'', nombres_apellidos:'', is_activo:0, negxusu_id:0 };
            this.listarNeg();
          })
          .finally(() => $.unblockUI());
      });
    },

    /* =========================
       MERCADOS - LISTA (DataTable)
    ========================== */
    abrirModalMercados() {
      // cargar mercados y abrir
      this.listarMercados()
        .then(() => $('#modalMercados').modal('show'));
    },

    listarMercados() {
      this.bloquear('Cargando mercados…');

      return axios.get(`${this.apphost}/mercado/listar`)
        .then(r => {

          this.mercados = r.data.data || [];

          this.mercadosOptions = this.mercados.map(m => ({

            mercado_id: parseInt(m.mercado_id),

            cat_mercado_id: m.cat_mercado_id
              ? parseInt(m.cat_mercado_id)
              : null,

            nombre: m.nombre,

            direccion: m.direccion,

            is_activo: parseInt(m.is_activo)

          }));

          this.$nextTick(() => {
            if (!this.dtMerc) {
              this.dtMerc = $('#tablaMercados').DataTable({
                language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
                scrollX: true,
                dom: 'frtip',
                order: [[0,'desc']]
              });

              const self = this;
              $('#tablaMercados tbody')
                .on('click', 'a.editar-merc', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = self.mercados.find(x => parseInt(x.mercado_id,10) === parseInt(id,10));
                  if (row) self.abrirModalEditarMercadoDesdeLista(row);
                })
                .on(
                    'change',
                    '.toggle-activo-mercado',
                    function(){

                        const mercado_id =
                            $(this).data('id')

                        const is_activo =
                            $(this).is(':checked')
                                ? 1
                                : 0

                        axios.post(

                            `${self.apphost}/WEwr/mercado/activo`,

                            {

                                mercado_id,
                                is_activo

                            }

                        )
                        .then(()=>{

                            apprise(
                                'Estado actualizado'
                            )

                        })
                        .catch(()=>{

                            apprise(
                                'Error al actualizar'
                            )

                        })

                    }
                )
                .on('click', 'a.eliminar-merc', function(e){
                  e.preventDefault();
                  const id = $(this).data('id');
                  const row = self.mercados.find(x => parseInt(x.mercado_id,10) === parseInt(id,10));
                  if (row) self.eliminarMercado(row);
                });
            }

            this.dtMerc.clear();

            this.mercados.forEach(m => {
              const activo = `

                <label class="switch-mini">

                    <input
                        type="checkbox"
                        class="toggle-activo-mercado"
                        data-id="${m.mercado_id}"
                        ${parseInt(m.is_activo,10) ? 'checked' : ''}
                    >

                    <span></span>

                </label>

                `;

              const actions = `
                <div class="btn-group">
                  <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                    Opciones <span class="caret"></span>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="#" class="editar-merc" data-id="${m.mercado_id}">Editar</a></li>
                    <li><a href="#" class="eliminar-merc" data-id="${m.mercado_id}">Eliminar</a></li>
                  </ul>
                </div>
              `;

              this.dtMerc.row.add([
                  m.mercado_id,
                  (m.nombre || ''),
                  (m.categoria || ''),
                  (m.direccion || ''),
                  activo,
                  actions
              ]);
            });

            this.dtMerc.draw(false);
          });
        })
        .finally(() => $.unblockUI());
    },

    /* =========================
       MERCADOS - abrir crear/editar desde lista (cerrar y volver)
    ========================== */
    abrirModalCrearMercadoDesdeLista() {
      this.nuevoMercado = { nombre: '', direccion: '', is_activo: 1 };
      $('#modalMercados').modal('hide');
      $('#modalCrearMercado').modal('show');
    },

    crearMercado() {
      if (!this.nuevoMercado.nombre || !this.nuevoMercado.nombre.trim()) return apprise('Escribe el nombre');
      // direccion puede ser null, pero si quieres obligatoria me dices

      this.bloquear('Guardando mercado…');
      axios.post(`${this.apphost}/mercado/crear`, this.nuevoMercado)
        .then(() => {
          apprise('¡Mercado creado!');
          $('#modalCrearMercado').modal('hide');
        })
        .finally(() => {
          $.unblockUI();
          // recargar y volver a abrir la lista
          this.listarMercados().then(() => $('#modalMercados').modal('show'));
        });
    },

    abrirModalEditarMercadoDesdeLista(m) {

      console.log('====================================');
      console.log('INICIO abrirModalEditarMercadoDesdeLista');
      console.log('m recibido:');
      console.log(m);
      console.log('mercado_id:', m.mercado_id);
      console.log('cat_mercado_id:', m.cat_mercado_id);
      console.log('====================================');

      this.cargarCategoriasMercado()

        .then(() => {

          console.log('====================================');
          console.log('cargarCategoriasMercado() FINALIZÓ');
          console.log('catMercadoOptions completos:');
          console.log(this.catMercadoOptions);
          console.log('Cantidad categorías:', this.catMercadoOptions.length);
          console.log('====================================');

          this.catMercadoOptions.forEach((x, i) => {

            console.log(
              'Categoria',
              i,
              'cat_mercado_id:',
              x.cat_mercado_id,
              'nombre:',
              x.nombre
            );

          });

          const categoriaEncontrada = this.catMercadoOptions.find(
            x =>
              parseInt(x.cat_mercado_id) ===
              parseInt(m.cat_mercado_id)
          );

          console.log('====================================');
          console.log('Resultado FIND:');
          console.log(categoriaEncontrada);
          console.log('====================================');

          this.formMercado = {

            mercado_id: parseInt(
              m.mercado_id,
              10
            ),

            cat_mercado:

              categoriaEncontrada || null,

            nombre:
              (m.nombre || ''),

            direccion:
              (m.direccion || ''),

            is_activo:
              (
                parseInt(
                  m.is_activo,
                  10
                )
                  ? 1
                  : 0
              ),

            logo:
              (m.logo || ''),

            topnavbar_color:
              (
                m.topnavbar_color
                || ''
              ),

            patron_fondo:
              (
                m.patron_fondo
                || ''
              )

          };

          console.log('====================================');
          console.log('formMercado generado:');
          console.log(this.formMercado);
          console.log('formMercado.cat_mercado:');
          console.log(this.formMercado.cat_mercado);
          console.log('====================================');

          $('#modalMercados').modal('hide');

          $('#modalEditarMercado').modal('show');

          setTimeout(() => {

            console.log('====================================');
            console.log('POST APERTURA MODAL');
            console.log('formMercado actual:');
            console.log(this.formMercado);
            console.log('cat_mercado actual:');
            console.log(this.formMercado.cat_mercado);
            console.log('====================================');

          }, 500);

        })

        .catch(err => {

          console.error('====================================');
          console.error('ERROR cargarCategoriasMercado');
          console.error(err);
          console.error('====================================');

        });

    },

    guardarMercado() {

      if (!this.formMercado.nombre || !this.formMercado.nombre.trim()) {
        return apprise('Escribe el nombre');
      }

      const payload = {

        ...this.formMercado,

        cat_mercado_id:

          this.formMercado.cat_mercado

            ? parseInt(
                this.formMercado
                  .cat_mercado
                  .cat_mercado_id
              )

            : null

      };

      this.bloquear('Actualizando mercado…');

      axios.post(

        `${this.apphost}/mercado/editar`,

        payload

      )
      .then(() => {

        apprise('¡Mercado actualizado!');

        $('#modalEditarMercado').modal('hide');

      })
      .finally(() => {

        $.unblockUI();

        this.listarMercados()
          .then(() => $('#modalMercados').modal('show'));

        this.listarNeg();

      });

    },

    eliminarMercado(m) {
      apprise(`¿Eliminar mercado <b>#${m.mercado_id}</b>?`, { confirm:true }, ok => {
        if (!ok) return;

        this.bloquear('Eliminando mercado…');
        axios.post(`${this.apphost}/mercado/eliminar`, { mercado_id: m.mercado_id })
          .then(() => apprise('Mercado eliminado'))
          .finally(() => {
            $.unblockUI();
            this.listarMercados();
            this.listarNeg();
          });
      });
    }
  },

  mounted() {
    this.listarNeg();
    this.cargarRubros(); // 🔥 NUEVO
  },

  watch: {

    'nuevoPago.motivo': function(){

      const hoy = new Date();

      const yyyy = hoy.getFullYear();

      const mm = String(
        hoy.getMonth() + 1
      ).padStart(2,'0');

      const dd = String(
        hoy.getDate()
      ).padStart(2,'0');

      const fechaHoy =
        `${yyyy}-${mm}-${dd}`;

      this.nuevoPago.fecha_inicio_premium =
        fechaHoy;

      const fin =
        new Date(hoy);

      if(
        this.nuevoPago.motivo
        ===
        'PAGO_ANUAL'
      ){

        fin.setFullYear(
          fin.getFullYear() + 1
        );

      }
      else if(
        this.nuevoPago.motivo
        ===
        'MENSUAL'
      ){

        fin.setMonth(
          fin.getMonth() + 1
        );

      }
      else{

        fin.setDate(
          fin.getDate() + 7
        );

      }

      const yyyy2 =
        fin.getFullYear();

      const mm2 =
        String(
          fin.getMonth() + 1
        ).padStart(2,'0');

      const dd2 =
        String(
          fin.getDate()
        ).padStart(2,'0');

      this.nuevoPago.fecha_fin_premium =
        `${yyyy2}-${mm2}-${dd2}`;

    }

  }
});
</script>