<!-- Este es mi frontend usando bootstrap2.3.2, vuejs2 modo estandalone y jquery2.0 -->
<div id="appSlider" class="row-fluid">
  <div class="span10">
    <h2>Gestión de Sliders</h2>

    <div class="form-actions">
      <button class="btn btn-success" @click="abrirModalCrear">
        <i class="icon-plus icon-white"></i> Nuevo Slider
      </button>
    </div>

    <table id="tablaSliders" class="table table-bordered table-striped">
      <thead>
        <tr>
          <th>ID</th>
          <th>Grupo</th>
          <th>Vista</th>
          <th>Orden</th>
          <th>Visible</th>
          <th>Creación</th>
          <th>Fin</th>
          <th>Negocio ID</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="s in sliders" :key="s.slider_id">
          <td>{{ s.slider_id }}</td>
          <td>{{ s.grupo }}</td>
          <td><img :src="s.img_thumb" style="max-width:100px; max-height:60px;" /></td>
          <td>{{ s.orden }}</td>
          <td>{{ s.is_visible ? 'Sí' : 'No' }}</td>
          <td>{{ s.fecha_creacion }}</td>
          <td>{{ s.fecha_fin }}</td>
          <td>{{ s.neg_id }}</td>
          <td>
            <div class="btn-group">
              <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown">
                Opciones <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" @click.prevent="abrirModalEditar(s)">Editar</a></li>
                <li><a href="#" @click.prevent="eliminarSlider(s)">Eliminar</a></li>
                <li><a href="#" @click.prevent="abrirModalDetalle(s)">Detalle</a></li>
              </ul>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <!-- Modal Crear -->
    <div id="modalCrearSlider" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header"><h3>Nuevo Slider</h3></div>
      <div class="modal-body">
        <form class="form-horizontal">
          <div class="control-group">
            <label class="control-label">Imagen</label>
            <div class="controls">
              <input type="file" @change="onFileChange($event, 'crear')">
              <div v-if="nuevo.imgPreview" class="mt-2">
                <img :src="nuevo.imgPreview" style="max-width:200px; max-height:100px;" />
              </div>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Grupo</label>
            <div class="controls">
              <select v-model="nuevo.grupo" class="input-small">
                <option disabled value="">Seleccione</option>
                <option v-for="l in letrasAZ" :key="l" :value="l">{{ l }}</option>
              </select>
            </div>
          </div>

          <div class="control-group">
            <label class="control-label">Orden</label>
            <div class="controls">
              <input type="number" v-model.number="nuevo.orden" class="input-small">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Visible</label>
            <div class="controls">
              <select v-model.number="nuevo.is_visible" class="input-small">
                <option :value="1">Sí</option>
                <option :value="0">No</option>
              </select>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Fecha Creación</label>
            <div class="controls">
              <input type="datetime-local" v-model="nuevo.fecha_creacion" class="input-medium">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Fecha Fin</label>
            <div class="controls">
              <input type="datetime-local" v-model="nuevo.fecha_fin" class="input-medium">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Negocio ID</label>
            <div class="controls">
              <input type="number" v-model.number="nuevo.neg_id" class="input-small">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="crearSlider">Crear</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- Modal Editar -->
    <div id="modalEditarSlider" class="modal hide fade fullscreen" tabindex="-1">
      <div class="modal-header"><h3>Editar Slider</h3></div>
      <div class="modal-body">
        <form class="form-horizontal">
          <div class="control-group">
            <label class="control-label">Imagen</label>
            <div class="controls">
              <input type="file" @change="onFileChange($event, 'editar')">
              <div v-if="formulario.imgPreview" class="mt-2">
                <img :src="formulario.imgPreview" style="max-width:200px; max-height:100px;" />
              </div>
            </div>
          </div>

          <div class="control-group">
            <label class="control-label">Grupo</label>
            <div class="controls">
              <select v-model="formulario.grupo" class="input-small">
                <option disabled value="">Seleccione</option>
                <option v-for="l in letrasAZ" :key="l" :value="l">{{ l }}</option>
              </select>
            </div>
          </div>

          <div class="control-group">
            <label class="control-label">Orden</label>
            <div class="controls">
              <input type="number" v-model.number="formulario.orden" class="input-small">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Visible</label>
            <div class="controls">
              <select v-model.number="formulario.is_visible" class="input-small">
                <option :value="1">Sí</option>
                <option :value="0">No</option>
              </select>
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Fecha Creación</label>
            <div class="controls">
              <input type="datetime-local" v-model="formulario.fecha_creacion" class="input-medium">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Fecha Fin</label>
            <div class="controls">
              <input type="datetime-local" v-model="formulario.fecha_fin" class="input-medium">
            </div>
          </div>
          <div class="control-group">
            <label class="control-label">Negocio ID</label>
            <div class="controls">
              <input type="number" v-model.number="formulario.neg_id" class="input-small">
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-primary" @click="guardarEdicion">Guardar</button>
        <button class="btn" data-dismiss="modal">Cancelar</button>
      </div>
    </div>

    <!-- Modal Detalle -->
    <div id="modalDetalleSlider" class="modal hide fade" tabindex="-1">
      <div class="modal-header"><h3>Detalle Slider</h3></div>
      <div class="modal-body">
        <dl class="dl-horizontal">
          <dt>ID</dt><dd>{{ detalle.slider_id }}</dd>
          <dt>Imagen</dt><dd><img :src="detalle.img_thumb" style="max-width:200px; max-height:100px;" /></dd>
          <dt>Orden</dt><dd>{{ detalle.orden }}</dd>
          <dt>Visible</dt><dd>{{ detalle.is_visible ? 'Sí' : 'No' }}</dd>
          <dt>Creación</dt><dd>{{ detalle.fecha_creacion }}</dd>
          <dt>Fin</dt><dd>{{ detalle.fecha_fin }}</dd>
          <dt>Negocio ID</dt><dd>{{ detalle.neg_id }}</dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
new Vue({
  el: '#appSlider',
  data: {
    apphost: apphost,
    cdn_base_url: cdn_base_url,
    sliders: [],
    letrasAZ: Array.from({length: 26}, (_, i) => String.fromCharCode(65 + i)),
    nuevo: {
      imgFile: null,
      imgPreview: '',
      orden: 0,
      is_visible: 1,
      fecha_creacion: '',
      fecha_fin: '',
      neg_id: 0,
      grupo: ''   // <── NUEVO
    },
    formulario: {
      slider_id: null,
      imgFile: null,
      imgPreview: '',
      orden: 0,
      is_visible: 1,
      fecha_creacion: '',
      fecha_fin: '',
      neg_id: 0,
      grupo: '' 
    },
    detalle: {}
  },
  methods: {
    obtenerSliders() {
          fetch(this.apphost + '/slider/listar')
            .then(r => r.json())
            .then(data => {
              this.sliders = data.map(s => ({
                ...s,
                // 💡 FIX: Construir la URL del CDN usando el nombre del archivo 'img' y la ruta 'sliders'
                img_thumb: cdn_base_url + '/sliders/' + s.img
              }));
              this.$nextTick(() => {
                if ($.fn.DataTable.isDataTable('#tablaSliders')) {
                  $('#tablaSliders').DataTable().destroy();
                }
                $('#tablaSliders').DataTable({ scrollX: true, dom: 'frtip' });
              });
            });
    },
    onFileChange(e, mode) {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = ev => {
        if (mode === 'crear') {
          this.nuevo.imgFile = file;
          this.nuevo.imgPreview = ev.target.result;
        } else {
          this.formulario.imgFile = file;
          this.formulario.imgPreview = ev.target.result;
        }
      };
      reader.readAsDataURL(file);
    },
    abrirModalCrear() {
      // 1) Construyo la fecha de hoy con hora fija a mediodía
      const hoy = new Date();
      const yyyy = hoy.getFullYear();
      const mm   = String(hoy.getMonth() + 1).padStart(2, '0');
      const dd   = String(hoy.getDate()).padStart(2, '0');
      const mediodia = `${yyyy}-${mm}-${dd}T12:00`;

      // 2) Inicializo el objeto nuevo
      this.nuevo = {
        imgFile:        null,
        imgPreview:     '',
        orden:          0,
        is_visible:     1,
        fecha_creacion: mediodia,
        fecha_fin:      mediodia,
        neg_id:         0
      };

      $('#modalCrearSlider').modal('show');
    },
    crearSlider() {
      // 0) Log de debug
      console.log('Valores del formulario crear:', this.nuevo);

      // 1) Validar que las cadenas sean fechas válidas
      const fc = new Date(this.nuevo.fecha_creacion);
      const ff = new Date(this.nuevo.fecha_fin);
      if (isNaN(fc.getTime()) || isNaN(ff.getTime())) {
        return apprise('Debes seleccionar fecha de creación y fecha fin válidas', { okBtn: 'Entendido' });
      }
      
      // Validar imagen
      if (!this.nuevo.imgFile) {
        return apprise('Debes seleccionar una imagen para el slider', { okBtn: 'Entendido' });
      }

      // 2) Preparar FormData
      const formData = new FormData();
      formData.append('img',            this.nuevo.imgFile);
      formData.append('orden',          this.nuevo.orden);
      formData.append('is_visible',     this.nuevo.is_visible);
      formData.append('fecha_creacion', this.nuevo.fecha_creacion);
      formData.append('fecha_fin',      this.nuevo.fecha_fin);
      formData.append('neg_id',         this.nuevo.neg_id);
      formData.append('grupo', this.nuevo.grupo);


      // 3) Envío a Flask
      fetch(apphost + '/slider/crear', {
        method: 'POST',
        body: formData
      })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                $('#modalCrearSlider').modal('hide');
                this.obtenerSliders(); // 👈 Actualiza la lista para mostrar la nueva imagen
                apprise('Slider creado', { okBtn: 'Ok' }, () => {
                    window.location.reload();
                });
            } else {
                apprise('Error al crear slider: ' + (data.error || 'Desconocido'), { okBtn: 'Entendido' });
            }
        })
        .catch(e => {
            apprise('Error de red al crear slider', { okBtn: 'Entendido' });
            console.error(e);
        });
    },

    abrirModalEditar(s) {
          // Al cargar el slider, ya viene con fecha completa desde el back...
          let fc = s.fecha_creacion;
          let ff = s.fecha_fin;
          if (fc && fc.length === 10) fc += 'T12:00';
          if (ff && ff.length === 10) ff += 'T12:00';
          
          // 💡 FIX: Usa el img_thumb ya construido para el preview inicial
          this.formulario = { 
              ...s, 
              fecha_creacion: fc,
              fecha_fin: ff,
              imgFile: null, 
              imgPreview: s.img_thumb 
          };
          $('#modalEditarSlider').modal('show');
    },
    guardarEdicion() {
      const fc = new Date(this.formulario.fecha_creacion);
      const ff = new Date(this.formulario.fecha_fin);
      if (isNaN(fc.getTime()) || isNaN(ff.getTime())) {
        return apprise('Debes seleccionar fecha de creación y fecha fin válidas', { okBtn: 'Entendido' });
      }

      const formData = new FormData();
      formData.append('slider_id', this.formulario.slider_id);
      if (this.formulario.imgFile) formData.append('img', this.formulario.imgFile); // Solo si se seleccionó una nueva
      formData.append('orden', this.formulario.orden);
      formData.append('is_visible', this.formulario.is_visible);
      formData.append('fecha_creacion', this.formulario.fecha_creacion);
      formData.append('fecha_fin', this.formulario.fecha_fin);
      formData.append('neg_id', this.formulario.neg_id);
      formData.append('grupo', this.formulario.grupo);


      fetch(apphost + '/slider/editar', {
        method: 'POST',
        body: formData
      })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                $('#modalEditarSlider').modal('hide');
                this.obtenerSliders(); // 👈 Actualiza la lista para mostrar la nueva imagen
                apprise('Slider actualizado');
            } else {
                apprise('Error al editar slider: ' + (data.error || 'Desconocido'), { okBtn: 'Entendido' });
            }
        })
        .catch(e => {
            apprise('Error de red al editar slider', { okBtn: 'Entendido' });
            console.error(e);
        });
    },
    eliminarSlider(s) {
        // ... (sin cambios, usa this.obtenerSliders() que ya fue actualizado)
      apprise(`¿Eliminar slider #${s.slider_id}?`, { confirm: true }, r => {
        if (r) {
          fetch(this.apphost + '/slider/eliminar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ slider_id: s.slider_id })
          }).then(() => {
            this.obtenerSliders();
            // apprise('Slider eliminado');
            window.location = apphost + "/slider/inicio";
          });
        }
      });
    },
    abrirModalDetalle(s) {
        // Asumimos que PHP ha sido modificado para devolver solo 'img'
      fetch(this.apphost + '/slider/detalle/' + s.slider_id)
        .then(r => r.json())
        .then(data => {
            // 💡 FIX: Construir la URL del CDN usando el nombre del archivo 'img' y la ruta 'sliders'
            const cdn_url = cdn_base_url + '/sliders/' + data.img;
            this.detalle = { ...data, img_thumb: cdn_url };
          $('#modalDetalleSlider').modal('show');
        });
    }
  },
  mounted() {
    this.obtenerSliders();
  }
});
</script>
