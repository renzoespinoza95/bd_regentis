const productoState = {
  apphost: window.apphost || '',
  productos: [],
  categorias: [],
  subcategorias: [],
  categoriaSeleccionada: null,
  subcategoriaSeleccionada: null,
  busquedaCategorias: '',
  dt: null,
  busquedaCatCrear: '',
  busquedaCatEditar: '',
  imgSubDefecto: 'https://barsi-img.b-cdn.net/regx/4GjSVb.jpg',
  subNueva: {
    nombre: '',
    img: 'https://barsi-img.b-cdn.net/regx/4GjSVb.jpg',
    is_activo: 1,
    urlTmp: '',
    urlsArrastradas: '',
    previewUrlList: []
  },
  subEditar: {
    subcategoria_id: null,
    nombre: '',
    img: '',
    is_activo: 1,
    urlTmp: '',
    urlsArrastradas: '',
    previewUrlList: []
  },
  nuevo: {
    name: '',
    price: 0,
    stock: 99,
    description: '',
    contenido01: '',
    contenido02: '',
    categorias: [],
    subcategoria_id: null
  },
  form: {
    product_id: '',
    name: '',
    price: 0,
    stock: 99,
    description: '',
    contenido01: '',
    contenido02: '',
    categorias: [],
    subcategoria_id: null
  },
  catNueva: {
    name: '',
    icon: '',
    color: '#999999',
    brief: '',
    img: '',
    is_activo: 1,
    file: null,
    preview: '',
    panelUrl: false,
    urlTmp: '',
    urlsArrastradas: '',
    previewUrlList: []
  },
  catEditar: {
    category_id: null,
    name: '',
    icon: '',
    color: '#999999',
    brief: '',
    img: '',
    is_activo: 1,
    file: null,
    preview: '',
    panelUrl: false,
    urlTmp: '',
    urlsArrastradas: '',
    previewUrlList: []
  },
  emojisComercio: [
    // Comercio y dinero
    '🛒','🛍️','🏪','🏬','🏷️','💰','💵','💳','🧾','📦','🎁','🛎️','🏭','🚚','📈','🏦','💎','🪙',
    // Comida y bebida
    '🍔','🍕','🌭','🥪','🌮','🌯','🍣','🍤','🦐','🦀','🐟','🍲','🍜','🍝','🍛','🥗','🥩','🍗','🍖','🥓','🍳',
    '🥐','🍞','🧀','🥚','🥛','🍎','🍌','🍇','🍓','🍉','🍊','🥑','🥦','🥕','🌽','🍅','🥔','🧅',
    '🍫','🍬','🍰','🎂','🍦','🍩','🍪','🥤','☕','🍺','🍷','🍸','🧃',
    // Hogar y limpieza
    '🧴','🧼','🧻','🧹','🧺','🪑','🛋️','🛏️','🚪','💡','🔌','🔋','🕯️','🪞',
    // Ropa y accesorios
    '👕','👖','👗','👟','👠','👜','🎒','🕶️','⌚','💍','💄','🧢','🧥',
    // Tecnología
    '💻','📱','🎧','📷','🖥️','🎮','📺',
    // Herramientas
    '🛠️','🔧','🔨','🪛',
    // Otros
    '🧸','⚽','📚','✏️','🖊️','🌱','🌸','💐','🐶','🐱','🐾','💊','🩺','🧪','🚗','🏍️','🚲','⛽','🎵','✨','⭐','🔥'
  ],
  reporteCategorias: [],
  fotoProduct: {
    product_id: null
  },
  galeriaImagenes: []
};