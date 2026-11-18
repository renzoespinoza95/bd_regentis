const productoMethods = {
  listar(){
    this.bloquear('Cargando...');

    let url = `${this.apphost}/WOyw/prod/listar`;

    if(this.categoriaFiltro){
      url += `?categoria_global_id=${this.categoriaFiltro}`;
    }

    axios.get(url)
    .then(r=>{
      this.productos = r.data.data || [];

      this.$nextTick(()=>{

        if(!this.dt){
          this.dt = $('#tablaProd').DataTable();

          const self = this;

          $('#tablaProd tbody')
            .on('click','.editar',function(){
              const id = $(this).data('id');
              const row = self.productos.find(x=>x.cod_prod_plazavea==id);
              self.abrirEditar(row);
            })
            .on('click','.eliminar',function(){
              const id = $(this).data('id');
              self.eliminar(id);
            })
            .on('click','.imagen-preview',function(){
              const imgUrl = $(this).data('url');
              self.abrirModalVerImagen(imgUrl);
            });
        }

        this.dt.clear();

        this.productos.forEach(p=>{
          let carpeta = p.categoria ? p.categoria.trim().toLowerCase() : '';
          // Ruta con el puerto 84 correcto para XAMPP
          let imgUrl = `http://localhost:84/fotos_plazavea/${carpeta}/${p.cod_prod_plazavea}.jpg`;

          this.dt.row.add([
            // 1. Acciones
            `
            <div class="btn-group">
              <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                ⚙
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" class="editar" data-id="${p.cod_prod_plazavea}">Editar</a></li>
                <li><a href="#" class="eliminar" data-id="${p.cod_prod_plazavea}">Eliminar</a></li>
              </ul>
            </div>`,
            // 2. Cod
            p.cod_prod_plazavea,
            // 3. Imagen con loading="lazy" para carga diferida nativa
            `<img src="${imgUrl}" class="imagen-preview" data-url="${imgUrl}" width="40" style="cursor: pointer;" loading="lazy" title="Ver imagen grande" onerror="this.src='https://barsi-img.b-cdn.net/recursos/6qz5.png'">`,
            // 4. Nombre
            p.nombre,
            // 5. Marca
            p.marca,
            // 6. Precio
            p.precio,
            // 7. Categoría
            p.categoria
          ]);
        });

        this.dt.draw(false);

      });

    })
    .finally(()=>$.unblockUI());
  },

  abrirModalVerImagen(url){
    this.imagenModalUrl = url;
    $('#modalVerImagen').modal('show');
  },

  abrirBuscarGlobal(){
    this.txtBuscarGlobal = '';
    $('#modalBuscarGlobal').modal('show');

    this.$nextTick(()=>{
      if(this.dtBuscar){
        this.dtBuscar.destroy();
        $('#tablaBuscarGlobal tbody').empty();
      }
      this.dtBuscar = $('#tablaBuscarGlobal').DataTable();
    });
  },    

  onBuscarGlobal(){
    const q = this.txtBuscarGlobal.trim();
    if(q.length < 4){
      if(this.dtBuscar){
        this.dtBuscar.clear().draw();
      }
      return;
    }
    this.buscarGlobal(q);
  },    

  buscarGlobal(q){
    axios.get(`${this.apphost}/pet/product/buscar_global?q=${encodeURIComponent(q)}`)
    .then(r=>{
      const data = r.data || [];
      if(!this.dtBuscar){
        this.dtBuscar = $('#tablaBuscarGlobal').DataTable();
      }
      this.dtBuscar.clear();
      data.forEach(p=>{
        this.dtBuscar.row.add([
          p.cod_prod_plazavea,
          p.nombre,
          'S/ ' + p.precio.toFixed(2),
          p.categoria_nombre
        ]);
      });
      this.dtBuscar.draw();
    });
  },

  abrirModalCrear(){
    this.form = {
      cod_prod_plazavea:'',
      nombre:'',
      marca:'',
      precio:'',
      categoria:'',
      categoria_global_id:null
    };
    $('#modalProd').modal('show');
  },

  abrirEditar(row){
    this.form = {...row};
    $('#modalProd').modal('show');
  },

  guardar(){
    this.bloquear('Guardando...');
    axios.post(`${this.apphost}/WOyw/prod/guardar`,this.form)
    .then(()=>{
      $('#modalProd').modal('hide');
      this.listar();
    })
    .finally(()=>$.unblockUI());
  },

  eliminar(id){
    apprise('¿Eliminar?',{confirm:true},ok=>{
      if(!ok) return;
      this.bloquear('Eliminando...');
      axios.post(`${this.apphost}/WOyw/prod/eliminar`,{
        cod_prod_plazavea:id
      })
      .then(()=>this.listar())
      .finally(()=>$.unblockUI());
    });
  }
};