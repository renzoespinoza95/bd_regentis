const productosMethods = {

  escaparHtml(txt){
    return String(txt === null || txt === undefined ? '' : txt)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  },

  // Summernote vacío devuelve <p><br></p>; lo normalizamos a ''
  limpiarHtmlVacio(html){
    const h = html || '';
    const texto = h.replace(/<[^>]*>/g, '').replace(/&nbsp;/g, '').trim();
    if(texto !== '' || /<img|<iframe|<video/i.test(h)) return h;
    return '';
  },

  listar(){
    return axios.get(`${this.apphost}/product/listar`).then(r => {
      this.productos = Array.isArray(r.data) ? r.data : [];
      this.actualizarTablaDataTable();
    });
  },

  seleccionarCategoria(id){
    this.categoriaSeleccionada = id;
    // Al cambiar de categoría se limpia el filtro de subcategoría
    this.subcategoriaSeleccionada = null;
    this.actualizarTablaDataTable();
  },

  // Cambia is_visible de un producto (checkbox de la tabla)
  cambiarVisible(p, checked, $chk){
    const nuevo = checked ? 1 : 0;
    const $tr = $chk.closest('tr');
    $chk.prop('disabled', true);

    axios.post(`${this.apphost}/product/visible`, {
      product_id: p.product_id,
      is_visible: nuevo
    })
    .then(res => {
      if(res.data.status === 'ok'){
        p.is_visible = nuevo;
        $tr.toggleClass('fila-inactiva', nuevo === 0);
      } else {
        $chk.prop('checked', !checked);
        apprise(res.data.msg || 'No se pudo cambiar la visibilidad');
      }
    })
    .catch(() => {
      $chk.prop('checked', !checked);
      apprise('Error de conexión');
    })
    .finally(() => $chk.prop('disabled', false));
  },

  actualizarTablaDataTable(){
    const self = this;
    setTimeout(() => {
      if ($.fn.DataTable.isDataTable('#tablaProduct')) {
        $('#tablaProduct').DataTable().clear().destroy();
        self.dt = null;
      }

      self.dt = $('#tablaProduct').DataTable({
        dom: 'frtip',
        order: [[1, 'desc']],
        columnDefs: [{ orderable: false, targets: [0, 5] }],
        // Los productos con is_visible = 0 se ven en gris y cursiva
        createdRow: function(row, data){
          const p = self.productos.find(x => x.product_id == data[1]);
          if (p && Number(p.is_visible) === 0) {
            $(row).addClass('fila-inactiva');
          }
        },
        language: typeof dt_language !== 'undefined' ? dt_language : {}
      });

      $('#tablaProduct tbody').off('.prod');

      $('#tablaProduct tbody')
      .on('click.prod', 'a.detalle', function(e){
        e.preventDefault();
        const id = $(this).data('id');
        const p = self.productos.find(x => x.product_id == id);
        self.abrirDetalle(p);
      })
      .on('click.prod', 'a.foto', function(e){
        e.preventDefault();
        const id = $(this).data('id');
        const p = self.productos.find(x => x.product_id == id);
        self.abrirFotoProducto(p);
      })
      .on('click.prod', 'a.editar', function(e){
        e.preventDefault();
        const id = $(this).data('id');
        const p = self.productos.find(x => x.product_id == id);
        self.abrirEditar(p);
      })
      .on('click.prod', 'a.eliminar', function(e){
        e.preventDefault();
        const id = $(this).data('id');
        const p = self.productos.find(x => x.product_id == id);
        self.eliminar(p);
      })
      .on('change.prod', 'input.chk-visible', function(){
        const $chk = $(this);
        const id = $chk.data('id');
        const p = self.productos.find(x => x.product_id == id);
        if(!p) return;
        self.cambiarVisible(p, $chk.is(':checked'), $chk);
      });

      self.dt.clear();

      const catSel = self.categoriaSeleccionada;
      const subSel = self.subcategoriaSeleccionada;
      const productosFiltrados = self.productos.filter(p => {
        if (catSel !== null) {
          const ids = (p.categories_ids || []).map(Number);
          if (!ids.includes(Number(catSel))) return false;
        }
        if (subSel !== null) {
          if (Number(p.subcategoria_id) !== Number(subSel)) return false;
        }
        return true;
      });

      productosFiltrados.forEach(p => {
        const actions = `
            <div class="btn-group">
              <button class="btn btn-mini btn-primary dropdown-toggle" data-toggle="dropdown" title="Acciones">
                <i class="icon-cog"></i> <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="#" class="detalle" data-id="${p.product_id}"><i class="icon-eye-open"></i> Detalle</a></li>
                <li><a href="#" class="editar" data-id="${p.product_id}"><i class="icon-pencil"></i> Editar</a></li>
                <li><a href="#" class="eliminar" data-id="${p.product_id}"><i class="icon-trash"></i> Eliminar</a></li>
                <li><a href="#" class="foto" data-id="${p.product_id}"><i class="icon-picture"></i> Foto</a></li>
              </ul>
            </div>
        `;

        const visibleHtml = `
          <div style="text-align:center;">
            <input type="checkbox" class="chk-visible" data-id="${p.product_id}" title="Visible" ${Number(p.is_visible) === 1 ? 'checked' : ''}>
          </div>
        `;

        let fotosHtml = '';
        if (p.imagenes && p.imagenes.length > 0) {
          fotosHtml = '<div style="display:flex; gap:5px; margin-top:5px; flex-wrap:wrap;">';
          p.imagenes.forEach(imgObj => {
            fotosHtml += `<img src="${imgObj.img}" style="width:35px;height:35px;object-fit:cover;border-radius:4px;border:1px solid #ccc;">`;
          });
          fotosHtml += '</div>';
        } else {
          fotosHtml = `<div style="width:35px; height:35px; border-radius:4px; border:1px dashed #7f8c8d; background:#1a252f; display:flex; align-items:center; justify-content:center; font-size:9px; color:#95a5a6; text-align:center; margin-top:5px; line-height:1.1;">(vacío)</div>`;
        }

        const nombreConFotos = `
          <div style="font-weight:bold; font-size:14px;">${self.escaparHtml(p.name)}</div>
          ${fotosHtml}
        `;

        self.dt.row.add([
          actions,
          p.product_id,
          nombreConFotos,
          'S/ ' + parseFloat(p.price).toFixed(2),
          p.stock,
          visibleHtml
        ]);
      });
      self.dt.draw(false);
    }, 50);
  },

  // ---------- CREAR ----------
  abrirModalCrear(){
    this.nuevo = {
      name: '',
      price: '',
      stock: 99,
      description: '',
      contenido01: '',
      contenido02: '',
      categorias: this.categoriaSeleccionada !== null ? [Number(this.categoriaSeleccionada)] : [],
      subcategoria_id: this.subcategoriaSeleccionada !== null ? Number(this.subcategoriaSeleccionada) : null
    };
    this.busquedaCatCrear = '';
    $('#modalCrearProduct').modal('show');
    this.cargarCategorias();
    this.cargarSubcategorias();
  },

  crearProduct(){
    const n = this.nuevo;

    if(!n.name || !n.name.trim()){
      apprise('El nombre es obligatorio');
      return;
    }

    const stock = parseInt(n.stock, 10);
    const payload = {
      name: n.name.trim(),
      price: parseFloat(n.price) || 0,
      stock: isNaN(stock) ? 0 : stock,
      description: n.description || '',
      contenido01: this.limpiarHtmlVacio(n.contenido01),
      contenido02: this.limpiarHtmlVacio(n.contenido02),
      categorias: n.categorias.map(Number),
      subcategoria_id: n.subcategoria_id ? Number(n.subcategoria_id) : null
    };

    $.blockUI({ message: 'Creando producto...' });
    axios.post(`${this.apphost}/product/crear`, payload)
    .then(res => {
      if(res.data.status === 'ok'){
        apprise('Producto creado correctamente');
        $('#modalCrearProduct').modal('hide');
        this.listar();
        this.cargarCategorias();
        this.cargarSubcategorias();
      } else {
        apprise(res.data.msg || 'Error al crear');
      }
    })
    .catch(() => apprise('Error de conexión'))
    .finally(() => $.unblockUI());
  },

  // ---------- EDITAR ----------
  abrirEditar(p){
    $.blockUI({ message: 'Cargando datos...' });

    axios.get(`${this.apphost}/product/detalle/${p.product_id}`)
    .then(res => {
      const d = res.data;
      if(!d || d.status === 'error'){
        apprise((d && d.msg) || 'No se pudo cargar el producto');
        return;
      }

      const stock = parseInt(d.stock, 10);

      this.form = {
        product_id: d.product_id,
        name: d.name || '',
        price: parseFloat(d.price) || 0,
        stock: isNaN(stock) ? 0 : stock,
        description: d.description || '',
        contenido01: d.contenido01 || '',
        contenido02: d.contenido02 || '',
        categorias: (d.categories || []).map(c => Number(c.category_id)),
        subcategoria_id: d.subcategoria_id ? Number(d.subcategoria_id) : null
      };
      this.busquedaCatEditar = '';

      $('#modalEditarProduct').modal('show');
      this.cargarCategorias();
      this.cargarSubcategorias();
    })
    .catch(() => apprise('Error al obtener datos'))
    .finally(() => $.unblockUI());
  },

  guardarEdicion(){
    const f = this.form;

    if(!f.name || !f.name.trim()){
      apprise('El nombre es obligatorio');
      return;
    }

    const stock = parseInt(f.stock, 10);
    const payload = {
      product_id: f.product_id,
      name: f.name.trim(),
      price: parseFloat(f.price) || 0,
      stock: isNaN(stock) ? 0 : stock,
      description: f.description || '',
      contenido01: this.limpiarHtmlVacio(f.contenido01),
      contenido02: this.limpiarHtmlVacio(f.contenido02),
      categorias: f.categorias.map(Number),
      subcategoria_id: f.subcategoria_id ? Number(f.subcategoria_id) : null
    };

    $.blockUI({ message: 'Guardando cambios...' });
    axios.post(`${this.apphost}/product/editar`, payload)
    .then(res => {
      if(res.data.status === 'ok'){
        apprise('Producto actualizado correctamente');
        $('#modalEditarProduct').modal('hide');
        this.listar();
        this.cargarCategorias();
        this.cargarSubcategorias();
      } else {
        apprise(res.data.msg || 'Error al actualizar');
      }
    })
    .catch(() => apprise('Error de conexión'))
    .finally(() => $.unblockUI());
  },

  // ---------- DETALLE ----------
  abrirDetalle(p){
    axios.get(`${this.apphost}/product/detalle/${p.product_id}`).then(r => {
      const d = r.data;
      let catsHtml = '<ul>';
      if(d.categories && d.categories.length > 0){
        d.categories.forEach(c => {
          catsHtml += `<li>${this.escaparHtml(c.descripcion)}</li>`;
        });
      } else {
        catsHtml += '<li>Sin categorías</li>';
      }
      catsHtml += '</ul>';

      const sub = d.subcategoria_id
        ? this.subcategorias.find(s => s.subcategoria_id === Number(d.subcategoria_id))
        : null;
      const subHtml = sub
        ? `<span class="badge badge-info">${this.escaparHtml(sub.nombre)}</span>`
        : 'Sin subcategoría';

      let imgsHtml = '<div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 5px;">';
      if(d.images && d.images.length > 0){
        d.images.forEach(img => {
          imgsHtml += `<img src="${img.img}" style="width:80px; height:80px; object-fit:cover; margin:5px; border:1px solid #ccc; border-radius: 6px;">`;
        });
      } else {
        imgsHtml += '<span style="color:#7f8c8d;">Sin imágenes</span>';
      }
      imgsHtml += '</div>';

      const html = `
        <p><strong>ID:</strong> ${d.product_id}</p>
        <p><strong>Nombre:</strong> ${this.escaparHtml(d.name)}</p>
        <p><strong>Precio:</strong> S/ ${d.price}</p>
        <p><strong>Stock:</strong> ${d.stock}</p>
        <p><strong>Visible:</strong> ${Number(d.is_visible) === 1 ? 'Sí' : 'No'}</p>
        <p><strong>Descripción:</strong> ${this.escaparHtml(d.description) || 'Sin descripción'}</p>
        <p><strong>Contenido 01:</strong> ${d.contenido01 || 'Sin contenido'}</p>
        <p><strong>Contenido 02:</strong> ${d.contenido02 || 'Sin contenido'}</p>
        <h4 style="color: #e67e22; margin-top:15px;">Categorías:</h4>
        ${catsHtml}
        <p><strong>Subcategoría:</strong> ${subHtml}</p>
        <h4 style="color: #e67e22; margin-top:15px;">Imágenes:</h4>
        ${imgsHtml}
      `;

      $('#bodyDetalleProduct').html(html);
      $('#modalDetalleProduct').modal('show');
    });
  },

  // ---------- ELIMINAR (soft delete en el backend) ----------
  eliminar(p){
    apprise(`¿Eliminar producto <b>${this.escaparHtml(p.name)}</b>?`, {confirm: true}, ok => {
      if(!ok) return;
      axios.post(`${this.apphost}/product/eliminar`, { product_id: p.product_id })
      .then(() => {
        this.listar();
        this.cargarCategorias();
        this.cargarSubcategorias();
      });
    });
  },

  // ---------- FANTASMAS ----------
  crearFantasmas(){
    if(this.categoriaSeleccionada === null){
      apprise('Selecciona una categoría en "2. CATEGORÍAS" para crear los 5 productos fantasma.');
      return;
    }

    const categoriaId = Number(this.categoriaSeleccionada);
    const nombreCat = this.escaparHtml(this.nombreCategoriaActiva);

    apprise(`¿Crear 5 productos fantasma en la categoría <b>${nombreCat}</b>?`, {'verify': true}, (r) => {
      if(!r) return;
      $.blockUI({ message: 'Creando productos fantasma...' });
      axios.post(`${this.apphost}/OO09/productosFantasma`, { category_id: categoriaId })
      .then(res => {
        if(res.data.status == 'ok'){
          apprise('Productos fantasma creados 🚀');
          this.listar();
          this.cargarCategorias();
        } else {
          apprise(res.data.msg || 'No se pudo crear');
        }
      })
      .catch(e => {
        apprise(e.response?.data?.msg || 'Error al crear productos');
      })
      .finally(() => {
        $.unblockUI();
      });
    });
  }
};