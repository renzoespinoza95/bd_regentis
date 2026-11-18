const temaState = {
    apphost: (typeof apphost !== 'undefined') ? apphost : '',
    temas: [],
    dtTema: null,
    temasOptions: [],
    inputUrlsCrear: '',
    inputUrlsEditar: '',
    imagenVisorUrl: '',
    exportItem: { tema_id: 0, nombre_tema: '', miniatura: '' },
    importItem: { tema_id: 0, nombre_tema: '', miniatura: '' },
    nuevoTema: {
        nombre_tema: '',
        miniatura: '',
        vista_app: ''
    },
    formTema: {
        tema_id: 0,
        nombre_tema: '',
        miniatura: '',
        vista_app: ''
    },
    dtTemaXNeg: null,
    temasXNeg: [],
    negociosOptions: [],
    nuevoTemaXNeg: {
        tema_id: null,
        neg_id: null
    },
    formTemaXNeg: {
        temaxneg_id: 0,
        tema: null,
        negocio: null
    }
};