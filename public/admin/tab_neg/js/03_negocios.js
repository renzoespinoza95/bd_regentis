const negociosMethods = {
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
  cargarRubros(){
    return axios.get(`${this.apphost}/rubro/listar`)
      .then(r=>{
        this.rubrosOptions = r.data.data || [];
      });
  },
  abrirModalCrearNeg() {
    this.nuevoNeg = { nombre: '', puesto: '', mercado_id: null, rubros: [] };
    this.bloquear('Cargando datos...');
    Promise.all([this.cargarRubros(), this.cargarMercadosParaCombo()])
      .then(() => {
        $('#modalCrearNeg').modal('show');
      })
      .finally(() => $.unblockUI());
  },
  crearNeg(){
    if (!this.nuevoNeg.nombre.trim()) { return apprise('Escribe el nombre'); }
    if (!this.nuevoNeg.puesto.trim()) { return apprise('Escribe el puesto'); }
    if (!this.nuevoNeg.mercado_id) { return apprise('Selecciona un mercado'); }

    const mercado_id = typeof this.nuevoNeg.mercado_id === 'object' ? this.nuevoNeg.mercado_id.mercado_id : this.nuevoNeg.mercado_id;
    const payload = {
      nombre: this.nuevoNeg.nombre.trim(),
      puesto: this.nuevoNeg.puesto.trim(),
      mercado_id: mercado_id,
      rubros: (this.nuevoNeg.rubros || []).map(r => r.rubro_id)
    };

    this.bloquear('Creando negocio...');
    axios.post(`${this.apphost}/neg/crear`, payload)
      .then(()=>{
        $('#modalCrearNeg').modal('hide');
        apprise('¡Creado!');
        this.nuevoNeg = { nombre: '', puesto: '', mercado_id: null, rubros: [] };
      })
      .finally(()=>{
        $.unblockUI();
        this.listarNeg();
      });
  },
  abrirModalEditarNeg(n) {
    this.bloquear('Cargando categorías...');
    Promise.all([this.cargarRubros(), this.cargarMercadosParaCombo()])
    .then(()=>{
      this.formNeg = {
        neg_id: n.neg_id,
        nombre: n.nombre,
        puesto: n.puesto,
        celular_informes: n.celular_informes || '',
        direccion: n.direccion || '',
        img_logo: n.img_logo || '',
        fecha_fin_premium: n.fecha_fin_premium || '',
        mercado_id: this.mercadosOptions.find(m => m.mercado_id == n.mercado_id),
        rubros: n.rubros_obj || []
      };
      $('#modalEditarNeg').modal('show');
    })
    .finally(()=>$.unblockUI());
  },
  guardarNeg() {
    if (!this.formNeg.nombre.trim()) { return apprise('Escribe el nombre'); }
    if (!this.formNeg.puesto.trim()) { return apprise('Escribe el puesto'); }
    if (!this.formNeg.mercado_id) { return apprise('Selecciona un mercado'); }

    const mercado_id = typeof this.formNeg.mercado_id === 'object' ? this.formNeg.mercado_id.mercado_id : this.formNeg.mercado_id;
    const payload = {
      neg_id: this.formNeg.neg_id,
      nombre: this.formNeg.nombre.trim(),
      puesto: this.formNeg.puesto.trim(),
      celular_informes: (this.formNeg.celular_informes || '').trim(),
      direccion: (this.formNeg.direccion || '').trim(),
      img_logo: (this.formNeg.img_logo || '').trim(),
      fecha_fin_premium: (this.formNeg.fecha_fin_premium || '').trim(),
      mercado_id: mercado_id,
      rubros: (this.formNeg.rubros || []).map(r => r.rubro_id)
    };

    this.bloquear('Actualizando negocio…');
    axios.post(`${this.apphost}/neg/editar`, payload)
      .then(()=>{
        $('#modalEditarNeg').modal('hide');
        apprise('¡Actualizado!');
      })
      .finally(()=>{
        $.unblockUI();
        this.listarNeg();
      });
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
  obtenerNombreMercado(mercado_id) {
    const mid = parseInt(mercado_id, 10);
    const m = this.mercadosOptions.find(x => parseInt(x.mercado_id,10) === mid);
    return m ? m.nombre : '';
  },

  // --- MÉTODOS DEL MODAL DE LOGO Y POLLING FLASK ---
  abrirModalLogo(n) {
    this.logoNeg = {
      neg_id: n.neg_id,
      nombre: n.nombre,
      preview: n.img_logo || '',
      nueva_preview: '',
      file: null
    };
    $('#modalLogoNeg').modal('show');
  },

  onFileChangeLogo(e) {
    const file = e.target.files[0];
    if (!file) return;

    this.logoNeg.file = file;

    const reader = new FileReader();
    reader.onload = (evt) => {
      this.logoNeg.nueva_preview = evt.target.result;
    };
    reader.readAsDataURL(file);
  },

  guardarLogoBunny() {
    if (!this.logoNeg.file) {
      return apprise('Selecciona una imagen primero');
    }

    const formData = new FormData();
    formData.append('file', this.logoNeg.file);

    const flaskHost = typeof url_flask !== 'undefined' ? url_flask : 'http://localhost:5000';

    this.bloquear('Enviando imagen a Flask...');
    axios.post(`${flaskHost}/tk_guardar_img`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    .then(r => {
      if (r.data.ok) {
        const jobId = r.data.job_id;
        this.iniciarPollingJobParaGuardar(jobId, flaskHost);
      } else {
        $.unblockUI();
        apprise(r.data.error || 'No se pudo procesar la imagen');
      }
    })
    .catch(() => {
      $.unblockUI();
      apprise('Error de conexión con el servicio de Flask');
    });
  },

  iniciarPollingJobParaGuardar(jobId, flaskHost) {
    this.bloquear('Procesando imagen en segundo plano...');
    
    const interval = setInterval(() => {
      axios.get(`${flaskHost}/jobs/result?job_id=${jobId}`)
        .then(r => {
          const data = r.data;
          
          if (data.status === 'finished') {
            clearInterval(interval);
            
            if (data.result && data.result.url) {
              const urlFinal = data.result.url;
              this.logoNeg.nueva_preview = urlFinal;
              
              axios.post(`${this.apphost}/neg/logo/actualizar`, {
                neg_id: this.logoNeg.neg_id,
                img_logo: urlFinal
              })
              .then(() => {
                $.unblockUI();
                $('#modalLogoNeg').modal('hide');
                apprise('¡Logo actualizado correctamente!');
                this.listarNeg();
              })
              .catch(() => {
                $.unblockUI();
                apprise('Error al actualizar el logo en la base de datos');
              });

            } else {
              $.unblockUI();
              apprise('El proceso finalizó pero no devolvió la URL de la imagen.');
            }
          } else if (data.status === 'failed') {
            clearInterval(interval);
            $.unblockUI();
            apprise('El worker de Flask falló al procesar la imagen.');
          }
        })
        .catch(() => {
          clearInterval(interval);
          $.unblockUI();
          apprise('Error al consultar el estado de la tarea en Flask');
        });
    }, 2000);
  },

  eliminarLogoBunny() {
    apprise('¿Desea eliminar la imagen actual?', { confirm: true }, ok => {
      if (!ok) return;
      
      this.bloquear('Eliminando imagen...');
      axios.post(`${this.apphost}/QQvN/neg/logo/defecto`, { neg_id: this.logoNeg.neg_id })
        .then(r => {
          if (r.data.status === 'ok') {
            this.logoNeg.preview = r.data.img_logo;
            this.logoNeg.nueva_preview = '';
            this.logoNeg.file = null;
            apprise('Imagen eliminada / restablecida por defecto');
            this.listarNeg();
          }
        })
        .finally(() => $.unblockUI());
    });
  },

  listarNeg() {
    this.bloquear('Cargando negocios…');
    this.cargarMercadosParaCombo()
      .then(() => axios.get(`${this.apphost}/neg/listar`))
      .then(r => {
        this.negs = r.data.data || [];
        this.$nextTick(() => {
          if (!this.dtNeg) {
            this.dtNeg = $('#tablaNeg').DataTable({
              language: (typeof dt_language !== 'undefined' ? dt_language : undefined),
              scrollX: true,
              dom: 'frtip',
              order: [[0,'desc']]
            });
            const self = this;
            $('#tablaNeg tbody')
              .on('click', 'a.editar-neg', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                if (row) { self.abrirModalEditarNeg(row); }
              })
              .on('change', '.toggle-activo-neg', function(){
                const neg_id = $(this).data('id');
                const is_activo = $(this).is(':checked') ? 1 : 0;
                axios.post(`${self.apphost}/neg/activo`, { neg_id, is_activo })
                .then(()=>{ apprise('Estado actualizado'); })
                .catch(()=>{ apprise('Error al actualizar'); });
              })              
              .on('click', 'a.eliminar-neg', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                if (row) { self.eliminarNeg(row); }
              })
              .on('click', 'a.logo-neg', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                if (row) { self.abrirModalLogo(row); }
              })
              .on('click', 'a.propietario-neg', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                if (row) { self.abrirModalPropietario(row); }
              });
          }
          this.dtNeg.clear();
          this.negs.forEach(n => {
            const mercadoNombre = this.obtenerNombreMercado(n.mercado_id);
            const fechaFinPremium = n.fecha_fin_premium || '<span class="text-muted">Sin fecha</span>';
            const activo = `
              <label class="switch-mini">
                <input type="checkbox" class="toggle-activo-neg" data-id="${n.neg_id}" ${parseInt(n.is_activo,10) ? 'checked' : ''}>
                <span></span>
              </label>
            `;
            let rubrosHtml = `<span class="label">Sin rubros</span>`;
            if(Array.isArray(n.rubros_obj) && n.rubros_obj.length > 0){
              rubrosHtml = n.rubros_obj.map(r => `
                <span class="label label-info" style="margin-right:4px;margin-bottom:4px;display:inline-block;padding:4px 8px;border-radius:12px;font-size:11px;">
                  <span style="font-size:14px;">${r.icono || '📦'}</span>
                  ${r.nombre}
                </span>
              `).join('');
            }
            const actions = `
              <div class="btn-group">
                <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                  ⚙ <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="editar-neg" data-id="${n.neg_id}">Editar</a></li>
                  <li><a href="#" class="eliminar-neg" data-id="${n.neg_id}">Eliminar</a></li>
                  <li class="divider"></li>
                  <li><a href="#" class="propietario-neg" data-id="${n.neg_id}">Propietario</a></li>
                  <li><a href="#" class="logo-neg" data-id="${n.neg_id}">Subir logo</a></li>
                </ul>
              </div>
            `;
            const logo = `
              <img src="${n.img_logo || 'https://barsi-img.b-cdn.net/recursos/sg3f.png'}" style="width:55px;height:55px;object-fit:cover;border-radius:14px;border:1px solid #ddd;background:#fff;">
            `;
            this.dtNeg.row.add([n.neg_id, logo, n.nombre || '', n.puesto || '', mercadoNombre || '', fechaFinPremium, rubrosHtml, activo, actions]);
          });
          this.dtNeg.draw(false);
        });
      })
      .finally(() => $.unblockUI());
  }
};