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
    this.nuevoNeg = { nombre: '', slug: '', celular_yape: '' };
    this.bloquear('Verificando cuenta...');
    axios.post(`${this.apphost}/QQvN/verificarNegocioAdmin`)
      .then(r => {
        if(r.data.status === 'ok') {
          this.adminTieneNegocio = r.data.tiene_negocio;
          $('#modalCrearNeg').modal('show');
          if (this.adminTieneNegocio) {
            apprise('Esta cuenta ya administra un negocio');
          }
        } else {
          apprise('Error al verificar negocio de la cuenta');
        }
      })
      .catch(() => {
        apprise('Error al conectar con el servidor');
      })
      .finally(() => {
        $.unblockUI();
      });
  },
  crearNeg(){
    const nombre_negocio = this.nuevoNeg.nombre.trim();
    const slug = this.nuevoNeg.slug.trim();
    const celular_yape = this.nuevoNeg.celular_yape.trim();

    if (!nombre_negocio) { return apprise('Escribe el nombre del negocio'); }

    const payload = {
      nombre_negocio: nombre_negocio,
      slug: slug,
      celular_yape: celular_yape
    };

    this.bloquear('Creando tienda automática...');
    axios.post(`${this.apphost}/SubhVF/negocioAutomatico`, payload)
      .then(r => {
        if(r.data.status === 'ok') {
          $('#modalCrearNeg').modal('hide');
          apprise(r.data.msg || '¡Tienda creada correctamente!');
          this.nuevoNeg = { nombre: '', slug: '', celular_yape: '' };
        } else {
          apprise(r.data.msg || 'Error al crear la tienda');
        }
      })
      .catch(err => {
        const msg = err.response && err.response.data && err.response.data.msg ? err.response.data.msg : 'Error al conectar con el servidor';
        apprise(msg);
      })
      .finally(() => {
        $.unblockUI();
        this.listarNeg();
      });
  },
  abrirModalEditarNeg(n) {
    this.bloquear('Cargando datos...');
    Promise.all([this.cargarRubros(), this.cargarMercadosParaCombo()])
    .then(()=>{
      this.formNeg = {
        neg_id: n.neg_id,
        nombre: n.nombre,
        puesto: n.puesto,
        img_logo: n.img_logo || '',
        celular_informes: n.celular_informes || '',
        direccion: n.direccion || '',
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
      img_logo: (this.formNeg.img_logo || '').trim(),
      celular_informes: (this.formNeg.celular_informes || '').trim(),
      direccion: (this.formNeg.direccion || '').trim(),
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
            
            let fechaFinPremium = '<span class="text-muted">Sin fecha</span>';
            if (n.fecha_fin_premium) {
              const parts = n.fecha_fin_premium.substring(0, 10).split('-');
              if (parts.length === 3) {
                const yyyy = parseInt(parts[0], 10);
                const mm = parseInt(parts[1], 10) - 1;
                const dd = parseInt(parts[2], 10);
                
                const fechaFin = new Date(yyyy, mm, dd);
                const hoy = new Date();
                hoy.setHours(0,0,0,0);
                
                const diffTime = fechaFin - hoy;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                const fechaFormateada = `${String(dd).padStart(2,'0')}/${String(mm+1).padStart(2,'0')}/${yyyy}`;
                
                let badgeClass = 'badge-info';
                let badgeText = `${diffDays} días`;
                if (diffDays < 0) {
                  badgeClass = 'badge-important';
                  badgeText = `Vencido (${Math.abs(diffDays)}d)`;
                } else if (diffDays === 0) {
                  badgeClass = 'badge-warning';
                  badgeText = 'Hoy';
                }
                
                fechaFinPremium = `${fechaFormateada} <span class="badge ${badgeClass}" style="margin-left:5px;">${badgeText}</span>`;
              } else {
                fechaFinPremium = n.fecha_fin_premium;
              }
            }

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
            const propietarioInfo = n.cod_usu ? `<b>${n.cod_usu}</b> - ${n.nombres_apellidos || ''}` : '<span class="text-muted">Sin propietario</span>';
            this.dtNeg.row.add([n.neg_id, logo, n.nombre || '', propietarioInfo, mercadoNombre || '', fechaFinPremium, rubrosHtml, activo, actions]);
          });
          this.dtNeg.draw(false);
        });
      })
      .finally(() => $.unblockUI());
  }
};