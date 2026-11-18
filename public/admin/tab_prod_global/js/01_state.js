const prodState = {
  apphost: (typeof apphost !== 'undefined' ? apphost : ''),
  varhost: (typeof varhost !== 'undefined' ? varhost : ''),
  productos: [],
  categorias: [],
  categoriaFiltro: null,
  dt: null,
  txtBuscarGlobal: '',
  dtBuscar: null,
  dtCat: null,
  formCategoria: {
      categoria_global_id: 0,
      nombre: ''
  },
  form: {
    cod_prod_plazavea: '',
    nombre: '',
    marca: '',
    precio: '',
    categoria: '',
    categoria_global_id: null
  },
  imgPreview: null,
  imagenModalUrl: '' // 👈 URL para el modal de visualización de imagen
};