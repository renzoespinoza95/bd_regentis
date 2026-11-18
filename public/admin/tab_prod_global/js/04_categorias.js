const categoriaMethods = {
  abrirModalCategorias(){
    $('#modalCategorias').modal('show');
    this.listarCategorias();
  },

  listarCategorias(){
    this.bloquear('Cargando categorías...');

    axios.get(`${this.apphost}/WOyw/categoria/listar`)
    .then(r=>{
      this.categorias = r.data.data || [];

      // Si existe la tabla en el modal de categorías, la renderizamos
      this.$nextTick(()=>{
        if(!document.getElementById('tablaCategorias')) return;

        if(this.dtCat){
          this.dtCat.destroy();
          $('#tablaCategorias tbody').empty();
        }

        this.dtCat = $('#tablaCategorias').DataTable();

        const self = this;

        $('#tablaCategorias tbody')
          .off()
          .on('click','.ver-productos',function(){
            const id = $(this).data('id');
            self.verProductosPorCategoria(id);
          })
          .on('click','.editar-cat',function(){
            const id = $(this).data('id');
            const row = self.categorias.find(x=>x.categoria_global_id==id);
            self.editarCategoria(row);
          })
          .on('click','.eliminar-cat',function(){
            const id = $(this).data('id');
            self.eliminarCategoria(id);
          });

        this.categorias.forEach(c=>{
          this.dtCat.row.add([
            c.categoria_global_id,
            c.nombre,
            c.is_activo == 1 ? 'SI':'NO',
            `
            <div class="btn-group">
              <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                ⚙
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" class="ver-productos" data-id="${c.categoria_global_id}">Productos</a></li>
                <li><a href="#" class="editar-cat" data-id="${c.categoria_global_id}">Editar</a></li>
                <li><a href="#" class="eliminar-cat" data-id="${c.categoria_global_id}">Eliminar</a></li>
              </ul>
            </div>`
          ]);
        });

        this.dtCat.draw();
      });
    })
    .finally(()=>$.unblockUI());
  },

  abrirCrearCategoria(){
    this.formCategoria = {
      categoria_global_id: 0,
      nombre: ''
    };
    $('#modalCategorias').modal('hide');
    $('#modalCrearCategoria').modal('show');
  },

  verProductosPorCategoria(id){
    this.categoriaFiltro = id;
    this.listar();
  },

  guardarCategoria(){
    if(!this.formCategoria.nombre){
      apprise('Escribe el nombre');
      return;
    }

    this.bloquear('Guardando...');

    axios.post(`${this.apphost}/WOyw/categoria/crear`,this.formCategoria)
    .then(()=>{
      $('#modalCrearCategoria').modal('hide');
      this.listarCategorias();
    })
    .finally(()=>$.unblockUI());
  },

  editarCategoria(row){
    this.formCategoria = {...row};
    $('#modalCategorias').modal('hide');
    $('#modalCrearCategoria').modal('show');
  },

  eliminarCategoria(id){
    apprise('¿Eliminar categoría?',{confirm:true},ok=>{
      if(!ok) return;
      this.bloquear('Eliminando...');
      axios.post(`${this.apphost}/WOyw/categoria/eliminar`,{
        categoria_global_id: id
      })
      .then(()=>this.listarCategorias())
      .finally(()=>$.unblockUI());
    });
  },

  limpiarFiltro(){
    this.categoriaFiltro = null;
    this.listar();
  }
};