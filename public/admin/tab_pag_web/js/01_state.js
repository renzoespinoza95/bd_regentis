// F:\xampp\htdocs\bd_regentis\public\admin\tab_pag_web\js\01_state.js

const pagWebState = {
  apphost: (typeof window.apphost !== 'undefined' ? window.apphost : ''),
  
  // Selección actual de columnas jerárquicas de la vista principal
  catSeleccionada: null,
  subcatSeleccionada: null,

  // Filtro de categoría en el panel izquierdo del modal de subcategorías
  catModalSubcatFiltro: null,

  videoFormEditar: {
    pag_item_vid_id: null,
    titulo: ''
  },
  items: [],
  urlImagenPegar: '',
  categorias: [],
  subcategorias: [],
  videoActual: {},
  itemForm: {
    item_pag_web_id: null,
    titulo: '',
    precio: 0,
    subtitulo_detalle: '',
    html01: '',
    html02: '',
    is_visible: 1,
    stock: 0,
    clave_txt: '',
    contenido: '',
    cat: null,
    subcat: null
  },
  catForm: {
    cat_pag_web_id: null,
    titulo: '',
    clave_txt: '',
    url_img: '',
    is_visible: 1,
    texto01: '',
    texto02: ''
  },
  videoForm: {
    titulo: '',
    codigo: ''
  },
  dtCat: null,
  subcatForm: {
    subcat_pag_web_id: null,
    titulo: '',
    clave_txt: '',
    url_img: '',
    is_visible: 1,
    texto01: '',
    texto02: '',
    cat: null
  },
  imagenes: [],
  videos: [],
  dtSubcat: null,
  dt: null,
  preview: null,
  itemActual: 0,

  // 🔥 NUEVAS PROPIEDADES REACTIVAS REQUERIDAS PARA LA SUBIDA MÚLTIPLE Y VISTA PREVIA
  previewsTemporales: [],
  archivosSeleccionados: [],
  imagenModalUrl: ''
};