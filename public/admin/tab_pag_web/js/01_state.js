// F:\xampp\htdocs\bd_regentis\public\admin\tab_pag_web\js\01_state.js

const pagWebState = {
  apphost: (typeof window.apphost !== 'undefined' ? window.apphost : ''),
  
  // Datos del negocio para el título
  negId: 0,
  nombreNegocio: '',

  // Selección actual de columnas jerárquicas de la vista principal
  catSeleccionada: null,
  subcatSeleccionada: null,

  // Filtro de categoría en el panel izquierdo del modal de subcategorías
  catModalSubcatFiltro: null,

  // Entidad cuyas imágenes se están viendo en el modal Lista de Imágenes: 'item' | 'cat' | 'subcat'
  entidadActual: 'item',

  videoFormEditar: {
    pag_item_vid_id: null,
    titulo: ''
  },
  items: [],
  urlImagenPegar: '',

  // Zona "Ingrese la URL de la imagen" del modal Lista de Imágenes
  urlImagenTmp: '',
  urlsImagenTxt: '',
  previewUrlList: [],
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

  previewsTemporales: [],
  archivosSeleccionados: [],
  imagenModalUrl: '',

  // 🔥 PROPIEDADES PARA EL ÁRBOL DE CONTENIDO (Web, POS y Sliders)
  arbolWeb: [],
  arbolPos: [],
  arbolSliders: []
};