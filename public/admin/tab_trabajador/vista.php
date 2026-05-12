<div class="row-fluid" id="appTrabajador">

	<div class="span12">

		<div class="titulo-fijo clearfix">

			<div style="float:left;">
				<h2 style="margin:0;">Trabajadores Delivery</h2>
			</div>

			<div class="btn-group pull-right">

				<button class="btn btn-info dropdown-toggle" data-toggle="dropdown">
					<i class="fa fa-bandcamp"></i>
					<span class="caret"></span>
				</button>

				<ul class="dropdown-menu pull-right">

					<li>
						<a href="#" @click.prevent="abrirModalAgregar">
							<i class="fa fa-plus"></i> Agregar trabajador
						</a>
					</li>

				</ul>

			</div>

		</div>
		<div class="span12 tabla_esp_sup">
				<table id="tablaTrabajador" class="table table-bordered table-striped">

					<thead>
						<tr>
							<th>ID</th>
							<th>cod_usu</th>
							<th>DNI</th>							
							<th>Nombre</th>
							<th>tipo_usuario</th>
							<th>Teléfono</th>							
							<th>Estado</th>
							<th>Acciones</th>
						</tr>
					</thead>

					<tbody></tbody>

				</table>
		</div>
	</div>


<!-- MODAL AGREGAR -->

<div id="modalAgregarTrabajador" class="modal hide fade">

	<div class="modal-header">
		<h3>Agregar Trabajador</h3>
	</div>

	<div class="modal-body">

		<div class="control-group">

			<label>DNI</label>

			<div class="controls">

				<input v-model="dni" class="input-medium">

				<button class="btn btn-info" @click="buscarUsuario">
					<i class="fa fa-search"></i> Buscar
				</button>

			</div>

		</div>


		<div v-if="usuarioEncontrado" class="well">

			<p><b>Nombre:</b> {{usuarioEncontrado.nombres_apellidos}}</p>

			<p><b>Email:</b> {{usuarioEncontrado.email}}</p>

		</div>

	</div>

	<div class="modal-footer">

		<button class="btn btn-primary" @click="agregarTrabajador">
			Agregar trabajador
		</button>

		<button class="btn" data-dismiss="modal">
			Cancelar
		</button>

	</div>

</div>

</div>
<script>

	const appTrabajador = new Vue({

		el:'#appTrabajador',

		data:{

			apphost:(typeof apphost!=='undefined'?apphost:''),

			trabajadores:[],

			dni:'',

			usuarioEncontrado:null,

			dt:null

		},

		methods:{

			listar(){

				$.blockUI({
					message:'<h4>Cargando trabajadores...</h4>'
				})

				axios.get(`${this.apphost}/reg/trabajador/listar`)
				.then(r=>{

					this.trabajadores = r.data

					this.$nextTick(()=>{

						if(!this.dt){

							this.dt = $('#tablaTrabajador').DataTable({
								language: dt_language,
								scrollX: true,
								order: [[0,'desc']]
							})

							const self = this

							$('#tablaTrabajador tbody')

							.on('click','.suspender-trabajador',function(e){

								e.preventDefault()

								const id = $(this).data('id')

								const t = self.trabajadores.find(x => x.negxusu_id == id)

								self.suspenderTrabajador(t)

							})

							.on('click','.toggle-trabajador',function(e){

								e.preventDefault()

								const id = $(this).data('id')

								const t = self.trabajadores.find(x => x.negxusu_id == id)

								const accion = t.is_activo == 1 ? 'suspender' : 'habilitar'

								apprise(`¿${accion} trabajador <b>${t.nombre}</b>?`,
									{confirm:true},
									ok=>{

										if(!ok) return

										axios.post(`${self.apphost}/reg/trabajador/toggle`,{
											negxusu_id: t.negxusu_id
										})
										.finally(()=>self.listar())

									})

							})							

							.on('click','.eliminar-trabajador',function(e){

								e.preventDefault()

								const id = $(this).data('id')

								const t = self.trabajadores.find(x => x.negxusu_id == id)

								self.eliminarTrabajador(t)

							})

						}

						this.dt.clear()

						this.trabajadores.forEach(t=>{

							const estado = t.is_activo==1
							? '<span class="label label-success">Activo</span>'
							: '<span class="label label-important">Suspendido</span>'

							const accionToggle = t.is_activo == 1 ? 'Suspender' : 'Habilitar'

							const acciones = `

							<div class="btn-group">

							<button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
							⚙ <span class="caret"></span>
							</button>

							<ul class="dropdown-menu">

							<li>
							<a href="#" class="toggle-trabajador" data-id="${t.negxusu_id}">
							${accionToggle}
							</a>
							</li>

							<li>
							<a href="#" class="eliminar-trabajador" data-id="${t.negxusu_id}">
							Eliminar
							</a>
							</li>

							</ul>

							</div>
							`

							this.dt.row.add([
								t.negxusu_id,
								t.cod_usu,
								t.dni,
								t.nombre,
								t.tipo_usuario,
								t.telefono,
								estado,
								acciones
							])

						})

						this.dt.draw(false)

					})

				})
				.finally(()=>$.unblockUI())

			},


			abrirModalAgregar(){

				this.dni=''
				this.usuarioEncontrado=null

				$('#modalAgregarTrabajador').modal('show')

			},

			buscarUsuario(){

				if(!this.dni.trim()){
					apprise('Escribe un DNI')
					return
				}

				axios.get(`${this.apphost}/reg/trabajador/buscar/${this.dni}`)
				.then(r=>{

					const u = r.data

					// 🔥 VALIDACIÓN
					if(parseInt(u.tipoxusu_id) !== 1){

						this.usuarioEncontrado = null

						apprise('El usuario no es Visitante')

						return
					}

					this.usuarioEncontrado = u

				})
				.catch(()=>{
					apprise('Usuario no encontrado')
				})

			},

			agregarTrabajador(){

				if(!this.usuarioEncontrado){

					apprise('Primero busca un usuario')

					return

				}

				axios.post(`${this.apphost}/reg/trabajador/agregar`,{

					usu_id:this.usuarioEncontrado.usu_id

				})

				.then(()=>{

					$('#modalAgregarTrabajador').modal('hide')

					apprise('Trabajador agregado')

				})

				.finally(()=>this.listar())

			},

			suspenderTrabajador(t){

				apprise(`¿Suspender trabajador <b>${t.nombre}</b>?`,
					{confirm:true},
					ok=>{

						if(!ok) return

						axios.post(`${this.apphost}/reg/trabajador/suspender`,{
							negxusu_id: t.negxusu_id
						})
						.then(()=>{
							apprise('Trabajador suspendido')
						})
						.finally(()=>this.listar())

					})

			},

			eliminarTrabajador(t){

				apprise(`¿Eliminar trabajador <b>${t.nombre}</b>?`,
					{confirm:true},
					ok=>{

						if(!ok) return

						axios.post(`${this.apphost}/reg/trabajador/eliminar`,{
							negxusu_id: t.negxusu_id
						})
						.then(()=>{
							apprise('Trabajador eliminado')
						})
						.finally(()=>this.listar())

					})

			}

		},

		mounted(){

			this.listar()

		}

	})

</script>