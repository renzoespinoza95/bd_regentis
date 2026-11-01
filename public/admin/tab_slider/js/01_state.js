const sliderState = {
  apphost: typeof apphost !== 'undefined' ? apphost : '',
  varhost: typeof varhost !== 'undefined' ? varhost : '',
  sliders: [],
  listaMenus: [],
  usuario: {},
  letrasAZ: Array.from({length: 26}, (_, i) => String.fromCharCode(65 + i)),
  nuevo: {
    imgFile: null,
    imgPreview: '',
    orden: 0,
    is_visible: 1,
    fecha_creacion: '',
    fecha_fin: '',
    neg_id: 0,
    descripcion: '',
    grupo: ''
  },
  formulario: {
    slider_id: null,
    imgFile: null,
    imgPreview: '',
    orden: 0,
    is_visible: 1,
    fecha_creacion: '',
    fecha_fin: '',
    neg_id: 0,
    descripcion: '',
    grupo: ''
  },
  detalle: {}
};