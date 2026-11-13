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
  llenarRandomNegocio() {
    const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    let randStr = '';
    for (let i = 0; i < 6; i++) {
      randStr += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    const nombresDemo = ['Tienda Express', 'Market Central', 'Bodega Don Jose', 'Comercial El Rado', 'Super Ahorro'];
    const nombreAleatorio = nombresDemo[Math.floor(Math.random() * nombresDemo.length)] + ' ' + randStr.toUpperCase();
    
    const slugAleatorio = nombreAleatorio.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');
    
    let celularAleatorio = '9';
    for (let i = 0; i < 8; i++) {
      celularAleatorio += Math.floor(Math.random() * 10);
    }

    this.nuevoNeg = {
      nombre: nombreAleatorio,
      slug: slugAleatorio,
      celular_yape: celularAleatorio
    };
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
    Promise.all([this.cargarRubros(), this.cargarMercadosForCombo ? Promise.resolve() : this.cargarMercadosParaCombo()])
    .then(()=>{
      this.formNeg = {
        neg_id: n.neg_id,
        cod_neg: n.cod_neg || '',
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
      cod_neg: (this.formNeg.cod_neg || '').trim(),
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
  reiniciarNegocio(n) {
    apprise(`¿Estás seguro de reiniciar el negocio <b>${n.nombre}</b>?<br><br>Esto aplicará soft delete a todos sus sliders, páginas web (categorías, subcategorías, ítems) y productos POS.`, { confirm: true }, ok => {
      if (!ok) return;
      this.bloquear('Reiniciando registros...');
      axios.post(`${this.apphost}/neg/reiniciar`, { neg_id: n.neg_id })
        .then(r => {
          if (r.data.status === 'ok') {
            apprise('Negocio reiniciado correctamente.');
            this.listarNeg();
          } else {
            apprise(r.data.msg || 'Error al reiniciar negocio');
          }
        })
        .catch(() => {
          apprise('Error de conexión al reiniciar negocio');
        })
        .finally(() => {
          $.unblockUI();
        });
    });
  },
  obtenerNombreMercado(mercado_id) {
    const mid = parseInt(mercado_id, 10);
    const m = this.mercadosOptions.find(x => parseInt(x.mercado_id,10) === mid);
    return m ? m.nombre : '';
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
              .on('click', 'a.reiniciar-neg', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.negs.find(x => parseInt(x.neg_id,10) === parseInt(id,10));
                if (row) { self.reiniciarNegocio(row); }
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
                  <li><a href="#" class="reiniciar-neg" data-id="${n.neg_id}">Reiniciar Neg</a></li>
                </ul>
              </div>
            `;
            const logo = `
              <img src="${n.img_logo || 'https://barsi-img.b-cdn.net/recursos/sg3f.png'}" style="width:55px;height:55px;object-fit:cover;border-radius:14px;border:1px solid #ddd;background:#fff;">
            `;
            const propietarioInfo = n.cod_usu ? `<b>${n.cod_usu}</b> - ${n.nombres_apellidos || ''}` : '<span class="text-muted">Sin propietario</span>';
            
            const contadoresHtml = `
              <div style="font-size: 11px; color: #f1c40f !important; margin-top: 4px; line-height: 1.3;">
                <div><b>Sliders:</b> ${n.total_sliders}</div>
                <div><b>Web (Cat/Sub/Item):</b> ${n.total_cat_web} / ${n.total_subcat_web} / ${n.total_items_web}</div>
                <div><b>POS (Cat/Sub/Item):</b> ${n.total_cat_pos} / ${n.total_subcat_pos} / ${n.total_items_pos}</div>
              </div>
            `;
            const codNegBadge = `<span class="badge badge-info" style="background-color: #5bc0de; color: #fff; margin-bottom: 4px; display: inline-block;">${n.cod_neg || '0'}</span>`;
            const nombreConDetalles = `
              <div>${codNegBadge}</div>
              <div><b>${n.nombre || ''}</b></div>
              ${contadoresHtml}
            `;

            this.dtNeg.row.add([n.neg_id, logo, nombreConDetalles, propietarioInfo, mercadoNombre || '', fechaFinPremium, rubrosHtml, activo, actions]);
          });
          this.dtNeg.draw(false);
        });
      })
      .finally(() => $.unblockUI());
  }
};