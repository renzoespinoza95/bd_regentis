const catMercadosMethods = {
  cargarCategoriasMercado(){
    return axios.get(`${this.apphost}/WEwr/catmercado/listar`)
      .then(r=>{
        this.catMercadoOptions = r.data.data || [];
      });
  },
  abrirModalCrearCategoriaMercado(){
    this.nuevoCatMercado = { nombre: '', is_visible: 1, is_activo: 1 };
    $('#modalCategoriasMercado').modal('hide');
    $('#modalCrearCategoriaMercado').modal('show');
  },
  crearCategoriaMercado(){
    if(!this.nuevoCatMercado.nombre || !this.nuevoCatMercado.nombre.trim()){
      return apprise('Escribe el nombre');
    }
    this.bloquear('Creando categoría...');
    axios.post(`${this.apphost}/WEwr/catmercado/crear`, {
      nombre: this.nuevoCatMercado.nombre.trim(),
      is_visible: parseInt(this.nuevoCatMercado.is_visible),
      is_activo: parseInt(this.nuevoCatMercado.is_activo)
    })
    .then(r=>{
      if(r.data.status == 'ok'){
        apprise('Categoría creada');
        $('#modalCrearCategoriaMercado').modal('hide');
        this.nuevoCatMercado = { nombre: '', is_visible: 1, is_activo: 1 };
      }else{
        apprise(r.data.msg || 'Error');
      }
    })
    .catch(err=>{ console.error(err); apprise('Error al crear categoría'); })
    .finally(()=>{
      $.unblockUI();
      this.listarCategoriasMercado().then(()=>{
        $('#modalCategoriasMercado').modal('show');
      });
    });
  },
  listarCategoriasMercado(){
    this.bloquear('Cargando categorías...');
    return axios.get(`${this.apphost}/WEwr/catmercado/listar`)
      .then(r=>{
        this.categoriasMercado = r.data.data || [];
        this.$nextTick(()=>{
          if(!this.dtCatMercado){
            this.dtCatMercado = $('#tablaCategoriasMercado').DataTable({
              language: (typeof dt_language !== 'undefined') ? dt_language : undefined,
              scrollX:true,
              destroy:true
            });
            const self = this;
            $('#tablaCategoriasMercado tbody')
              .on('click', '.editar-catmercado', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                const row = self.categoriasMercado.find(x => parseInt(x.cat_mercado_id) === parseInt(id));
                if(!row){ apprise('No se encontró la categoría'); return; }
                self.formCatMercado = {
                  cat_mercado_id: parseInt(row.cat_mercado_id),
                  nombre: row.nombre || '',
                  is_visible: parseInt(row.is_visible) || 0,
                  is_activo: parseInt(row.is_activo) || 0
                };
                $('#modalCategoriasMercado').modal('hide');
                setTimeout(function(){ $('#modalEditarCategoriaMercado').modal('show'); },300);
              })
              .on('change', '.toggle-activo-catmercado', function(){
                const cat_mercado_id = $(this).data('id');
                const is_activo = $(this).is(':checked') ? 1 : 0;
                axios.post(`${self.apphost}/WEwr/catmercado/activo`, { cat_mercado_id, is_activo });
              })
              .on('change', '.toggle-visible-catmercado', function(){
                const cat_mercado_id = $(this).data('id');
                const is_visible = $(this).is(':checked') ? 1 : 0;
                axios.post(`${self.apphost}/WEwr/catmercado/visible`, { cat_mercado_id, is_visible });
              })
              .on('click', '.eliminar-catmercado', function(e){
                e.preventDefault();
                const id = $(this).data('id');
                apprise('¿Eliminar categoría?', { confirm:true }, function(ok){
                  if(!ok){ return; }
                  self.bloquear('Eliminando...');
                  axios.post(`${self.apphost}/WEwr/catmercado/eliminar`, { cat_mercado_id:id })
                  .then(()=>{
                    apprise('Eliminado');
                    self.listarCategoriasMercado();
                  })
                  .finally(()=>{ $.unblockUI(); });
                });
              });
          }
          this.dtCatMercado.clear();
          this.categoriasMercado.forEach(c=>{
            const visible = `
              <label class="switch-mini">
                <input type="checkbox" class="toggle-visible-catmercado" data-id="${c.cat_mercado_id}" ${parseInt(c.is_visible) ? 'checked' : ''}>
                <span></span>
              </label>
            `;
            const activo = `
              <label class="switch-mini">
                <input type="checkbox" class="toggle-activo-catmercado" data-id="${c.cat_mercado_id}" ${parseInt(c.is_activo) ? 'checked' : ''}>
                <span></span>
              </label>
            `;
            const acciones = `
              <div class="btn-group">
                <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                  Opciones <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                  <li><a href="#" class="editar-catmercado" data-id="${c.cat_mercado_id}">Editar</a></li>
                  <li><a href="#" class="eliminar-catmercado" data-id="${c.cat_mercado_id}">Eliminar</a></li>
                </ul>
              </div>
            `;
            this.dtCatMercado.row.add([c.cat_mercado_id, c.nombre || '', visible, activo, acciones]);
          });
          this.dtCatMercado.draw(false);
        });
      })
      .catch(err=>{ console.error(err); })
      .finally(()=>{ $.unblockUI(); });
  },
  guardarCategoriaMercado(){
    if(!this.formCatMercado.nombre || !this.formCatMercado.nombre.trim()){
      return apprise('Escribe el nombre');
    }
    this.bloquear('Actualizando categoría...');
    axios.post(`${this.apphost}/WEwr/catmercado/editar`, {
      cat_mercado_id: parseInt(this.formCatMercado.cat_mercado_id),
      nombre: this.formCatMercado.nombre.trim(),
      is_visible: parseInt(this.formCatMercado.is_visible),
      is_activo: parseInt(this.formCatMercado.is_activo)
    })
    .then(r=>{
      if(r.data.status == 'ok'){
        apprise('Categoría actualizada');
        $('#modalEditarCategoriaMercado').modal('hide');
      }else{
        apprise(r.data.msg || 'Error');
      }
    })
    .catch(err=>{ console.error(err); apprise('Error al actualizar'); })
    .finally(()=>{
      $.unblockUI();
      this.listarCategoriasMercado().then(()=>{
        $('#modalCategoriasMercado').modal('show');
      });
    });
  },
  abrirModalCategoriasMercado(){
    this.listarCategoriasMercado().then(()=>{
      $('#modalCategoriasMercado').modal('show');
    });
  }
};