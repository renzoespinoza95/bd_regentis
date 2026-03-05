<!-- jQuery UI 1.10.3 (SORTABLE compatible con jQuery 2.0) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/css/base/jquery.ui.all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>

<div class="row-fluid" id="appMenu">
<div class="span12">

<h2>Lista de Menu</h2>

<div class="form-actions">
<button class="btn btn-success" @click="nuevoMenu">
<i class="icon-plus icon-white"></i> Agregar
</button>

<button class="btn btn-info" @click="abrirModalRoles">
<i class="icon-user icon-white"></i> Roles
</button>
</div>

<table class="table table-bordered table-striped">

<thead>
<tr>
<th>ID</th>
<th>Título</th>
<th>Orden</th>
<th>Roles</th>
<th>Submenús</th>
<th>Acciones</th>
</tr>
</thead>

<tbody ref="tbodyMenu">

<tr v-for="m in menus" :key="m.menu_id" :data-id="m.menu_id">

<td>{{ m.menu_id }}</td>
<td>{{ m.titulo }}</td>

<td style="cursor:move">
<span class="label label-info">⇅</span>
</td>

<td>{{ m.roles }}</td>

<td>
<button class="btn btn-mini btn-primary" @click="abrirSubmenus(m)">
☰ ({{ m.total_submenus }})
</button>
</td>

<td>
<div class="btn-group">
<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
⚙ <span class="caret"></span>
</button>

<ul class="dropdown-menu">
<li><a href="#" @click.prevent="editarMenu(m)">Editar</a></li>
<li><a href="#" @click.prevent="eliminarMenu(m)">Eliminar</a></li>
</ul>

</div>
</td>

</tr>

</tbody>
</table>


<!-- ===========================
MODAL MENU
=========================== -->

<div class="modal hide fade" id="modalMenu">

<div class="modal-header">
<h3>{{ form.menu_id ? 'Editar Menú' : 'Nuevo Menú' }}</h3>
</div>

<div class="modal-body">

<label>Título</label>
<input class="input-xxlarge" v-model="form.titulo">

</div>

<div class="modal-footer">

<button class="btn btn-primary" @click="guardarMenu">Guardar</button>

<button class="btn" data-dismiss="modal">Cancelar</button>

</div>

</div>


<!-- ===========================
MODAL SUBMENUS
=========================== -->

<div class="modal hide fade" id="modalSubmenus">

<div class="modal-header">
<h3>Submenús de {{ menuActual.titulo }}</h3>
</div>

<div class="modal-body">

<button class="btn btn-success btn-mini" @click="nuevoSubmenu">
<i class="icon-plus icon-white"></i> Agregar Submenú
</button>

<table class="table table-bordered table-striped" style="margin-top:10px">

<thead>
<tr>
<th>Título</th>
<th>URL</th>
<th>Orden</th>
<th>Target</th>
<th>Acciones</th>
</tr>
</thead>

<tbody ref="tbodySubmenu">

<tr v-for="s in submenus" :key="s.submenu_id" :data-id="s.submenu_id">

<td>{{ s.titulo }}</td>
<td>{{ s.url }}</td>

<td class="drag-handle" style="cursor:move;text-align:center">
<span class="label label-info">⇅</span>
</td>

<td>{{ s.target }}</td>

<td>
<button class="btn btn-mini" @click="editarSubmenu(s)">Editar</button>
<button class="btn btn-mini btn-danger" @click="eliminarSubmenu(s)">Eliminar</button>
</td>

</tr>

</tbody>
</table>

</div>

<div class="modal-footer">
<button class="btn" data-dismiss="modal">Cerrar</button>
</div>

</div>


<!-- ===========================
MODAL SUBMENU FORM
=========================== -->

<div class="modal hide fade" id="modalSubmenuForm">

<div class="modal-header">
<h3>{{ formSub.submenu_id ? 'Editar Submenú' : 'Nuevo Submenú' }}</h3>
</div>

<div class="modal-body">

<label>Título</label>
<input class="input-xlarge" v-model="formSub.titulo">

<label>URL</label>
<input class="input-xlarge" v-model="formSub.url">

<label>Orden</label>
<input class="input-mini" v-model="formSub.orden">

<label>Target</label>
<input class="input-mini" v-model="formSub.target">

</div>

<div class="modal-footer">

<button class="btn btn-primary" @click="guardarSubmenu">Guardar</button>

<button class="btn" data-dismiss="modal">Cancelar</button>

</div>

</div>


<!-- ===========================
MODAL ROLES
=========================== -->

<div class="modal hide fade" id="modalRoles">

<div class="modal-header">
<h3>Roles</h3>
</div>

<div class="modal-body">

<button class="btn btn-success btn-mini" @click="nuevoRol">
<i class="icon-plus icon-white"></i> Agregar
</button>

<table class="table table-bordered table-striped" style="margin-top:10px">

<thead>
<tr>
<th>ID</th>
<th>Descripción</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

<tr v-for="t in tipos" :key="t.rol_id">

<td>{{ t.rol_id }}</td>
<td>{{ t.nombre }}</td>
<td>{{ t.descripcion }}</td>

<td>

<div class="btn-group">

<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
⚙ <span class="caret"></span>
</button>

<ul class="dropdown-menu">

<li><a href="#" @click.prevent="editarRol(t)">Editar</a></li>
<li><a href="#" @click.prevent="eliminarRol(t)">Eliminar</a></li>
<li><a href="#" @click.prevent="abrirPermisos(t)">Permisos</a></li>

</ul>

</div>

</td>

</tr>

</tbody>
</table>

</div>

<div class="modal-footer">
<button class="btn" data-dismiss="modal">Cerrar</button>
</div>

</div>


<!-- ===========================
MODAL ROL FORM
=========================== -->

<div class="modal hide fade" id="modalRolForm">

<div class="modal-header">
<h3>{{ formTipo.rol_id ? 'Editar Rol' : 'Nuevo Rol' }}</h3>
</div>

<div class="modal-body">

<label>Descripción</label>
<input class="input-xlarge" v-model="formTipo.descripcion">

</div>

<div class="modal-footer">

<button class="btn btn-primary" @click="guardarRol">Guardar</button>
<button class="btn" data-dismiss="modal">Cancelar</button>

</div>

</div>


<!-- ===========================
MODAL PERMISOS
=========================== -->

<div class="modal hide fade" id="modalPermisos">

<div class="modal-header">
<h3>Permisos - {{ tipoActual.descripcion }}</h3>
</div>

<div class="modal-body">

<label v-for="m in menus" style="display:block">

<input type="checkbox"
:value="m.menu_id"
v-model="menusAsignados">

{{ m.titulo }}

</label>

</div>

<div class="modal-footer">

<button class="btn btn-primary" @click="guardarPermisos">Guardar</button>

<button class="btn" data-dismiss="modal">Cerrar</button>

</div>

</div>

</div>
</div>


<script>

new Vue({

el:'#appMenu',

data:{

apphost:apphost,

menus:[],
tipos:[],
submenus:[],

menuActual:{},

form:{},
formSub:{},

formTipo:{},

tipoActual:{},

menusAsignados:[]

},

methods:{

listarMenus(){

axios.get(this.apphost+'/menu/listar')
.then(r=>this.menus=r.data);

},

cargarTipos(){

axios.get(this.apphost+'/rol/listar')
.then(r=>this.tipos=r.data);

},

nuevoMenu(){

this.form={ titulo:'' };

$('#modalMenu').modal('show');

},

editarMenu(m){

this.form=JSON.parse(JSON.stringify(m));

$('#modalMenu').modal('show');

},

guardarMenu(){

axios.post(this.apphost+'/menu/guardar',this.form)
.then(()=>{
$('#modalMenu').modal('hide');
this.listarMenus();
});

},

eliminarMenu(m){

if(!confirm('¿Eliminar menú?')) return;

axios.post(this.apphost+'/menu/eliminar',{ menu_id:m.menu_id })
.then(()=>this.listarMenus());

},

abrirModalRoles(){

this.cargarTipos();

$('#modalRoles').modal('show');

},

nuevoRol(){

$('#modalRoles').modal('hide');

this.formTipo={ descripcion:'' };

$('#modalRolForm').modal('show');

},

editarRol(t){

$('#modalRoles').modal('hide');

this.formTipo=JSON.parse(JSON.stringify(t));

$('#modalRolForm').modal('show');

},

guardarRol(){

axios.post(this.apphost+'/rol/guardar',this.formTipo)
.then(()=>{
$('#modalRolForm').modal('hide');
this.cargarTipos();
$('#modalRoles').modal('show');
});

},

eliminarRol(t){

if(!confirm('¿Eliminar rol?')) return;

axios.post(this.apphost+'/rol/eliminar',{
rol_id:t.rol_id
}).then(()=>this.cargarTipos());

},

abrirPermisos(t){

$('#modalRoles').modal('hide');

this.tipoActual=t;

this.menusAsignados=[];

axios.get(this.apphost+'/rol/menus/'+t.rol_id)
.then(r=>{

this.menusAsignados=r.data.map(x=>x.menu_id);

$('#modalPermisos').modal('show');

});

},

guardarPermisos(){

axios.post(this.apphost+'/rol/guardar-menus',{

rol_id:this.tipoActual.rol_id,
menus:this.menusAsignados

}).then(()=>{

$('#modalPermisos').modal('hide');

$('#modalRoles').modal('show');

});

},

abrirSubmenus(m){

this.menuActual=m;

axios.get(this.apphost+'/submenu/listar/'+m.menu_id)
.then(r=>{

this.submenus=r.data;

this.$nextTick(()=>{

$('#modalSubmenus').modal('show');

});

});

},

nuevoSubmenu(){

this.formSub={
menu_id:this.menuActual.menu_id,
orden:1,
target:'_self'
};

$('#modalSubmenuForm').modal('show');

},

editarSubmenu(s){

this.formSub=JSON.parse(JSON.stringify(s));

$('#modalSubmenuForm').modal('show');

},

guardarSubmenu(){

axios.post(this.apphost+'/submenu/guardar',this.formSub)
.then(()=>{
$('#modalSubmenuForm').modal('hide');
this.abrirSubmenus(this.menuActual);
});

},

eliminarSubmenu(s){

if(!confirm('¿Eliminar submenú?')) return;

axios.post(this.apphost+'/submenu/eliminar',{ submenu_id:s.submenu_id })
.then(()=>this.abrirSubmenus(this.menuActual));

}

},

mounted(){

this.cargarTipos();
this.listarMenus();

}

});

</script>