const temaMethods = {
    ...uiHelpers,
    abrirModalCrearTema() {
        this.nuevoTema = {
            nombre_tema: '',
            miniatura: '',
            vista_app: ''
        };
        this.inputUrlsCrear = '';
        $('#modalCrearTema').modal('show');
    },
    abrirModalEditarTema(t) {
        this.formTema = {
            tema_id: parseInt(t.tema_id),
            nombre_tema: t.nombre_tema || '',
            miniatura: t.miniatura || '',
            vista_app: t.vista_app || ''
        };
        this.inputUrlsEditar = '';
        $('#modalEditarTema').modal('show');
    },
    abrirModalExportar() {
        $('#modalExportarTema').modal('show');
    },
    abrirModalImportar() {
        const fileInput = document.getElementById('archivoJsonImportar');
        if (fileInput) fileInput.value = '';
        $('#modalImportarTema').modal('show');
    },
    onDropCrear(e) {
        const text = e.dataTransfer.getData('text') || e.dataTransfer.getData('URL');
        if (text) {
            this.inputUrlsCrear = this.inputUrlsCrear ? this.inputUrlsCrear + '\n' + text.trim() : text.trim();
        }
    },
    onDropEditar(e) {
        const text = e.dataTransfer.getData('text') || e.dataTransfer.getData('URL');
        if (text) {
            this.inputUrlsEditar = this.inputUrlsEditar ? this.inputUrlsEditar + '\n' + text.trim() : text.trim();
        }
    },
    usarUrlCrear() {
        if (!this.inputUrlsCrear.trim()) return;
        const lines = this.inputUrlsCrear.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        if (lines.length > 0) {
            this.nuevoTema.miniatura = lines[0];
            apprise('Miniatura actualizada');
        }
    },
    usarUrlEditar() {
        if (!this.inputUrlsEditar.trim()) return;
        const lines = this.inputUrlsEditar.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        if (lines.length > 0) {
            this.formTema.miniatura = lines[0];
            apprise('Miniatura actualizada');
        }
    },
    usarUrlVistaAppEditar() {
        if (!this.inputUrlsEditar.trim()) return;
        const lines = this.inputUrlsEditar.split('\n').map(l => l.trim()).filter(l => l.length > 0);
        if (lines.length > 0) {
            this.formTema.vista_app = lines[0];
            apprise('Vista App actualizada');
        }
    },
    ejecutarImportacion() {
        const fileInput = document.getElementById('archivoJsonImportar');
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            apprise('Selecciona un archivo JSON');
            return;
        }
        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('json_file', file);

        this.bloquear('Importando datos...');
        axios.post(this.apphost + '/U9bTtemaImportarJson', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })
        .then(r => {
            if (r.data.status === 'ok') {
                apprise('Importación exitosa');
                $('#modalImportarTema').modal('hide');
                this.listarTemas();
            } else {
                apprise('Error: ' + (r.data.msg || 'Desconocido'));
            }
        })
        .catch(e => {
            console.error(e);
            apprise('Error en la importación');
        })
        .finally(() => {
            this.desbloquear();
        });
    },
    cargarTemas() {
        return axios.get(this.apphost + '/U9bTtemaListar')
            .then(r => {
                const rows = r.data.data || [];
                this.temasOptions = rows.map(x => ({
                    tema_id: parseInt(x.tema_id),
                    nombre_tema: x.nombre_tema || ''
                }));
            });
    },
    listarTemas() {
        this.bloquear('Cargando temas...');
        axios.get(this.apphost + '/U9bTtemaListar')
            .then(r => {
                this.temas = r.data.data || [];
                this.temasOptions = this.temas.map(t => ({
                    tema_id: parseInt(t.tema_id),
                    nombre_tema: t.nombre_tema || ''
                }));
                this.$nextTick(() => {
                    if (!this.dtTema) {
                        this.dtTema = $('#tablaTema').DataTable({
                            language: typeof dt_language !== 'undefined' ? dt_language : undefined,
                            scrollX: true,
                            dom: 'frtip',
                            order: [[0, 'desc']]
                        });
                        const self = this;
                        $('#tablaTema tbody')
                            .on('click', '.ver-imagen-fullscreen', function(e) {
                                e.preventDefault();
                                const url = $(this).data('url');
                                if (url) {
                                    self.imagenVisorUrl = url;
                                    $('#modalVisorImagen').modal('show');
                                }
                            })
                            .on('click', 'a.editar-tema', function(e) {
                                e.preventDefault();
                                const id = $(this).data('id');
                                const row = self.temas.find(x => parseInt(x.tema_id) === parseInt(id));
                                if (row) {
                                    self.abrirModalEditarTema(row);
                                }
                            })
                            .on('click', 'a.eliminar-tema', function(e) {
                                e.preventDefault();
                                const id = $(this).data('id');
                                const row = self.temas.find(x => parseInt(x.tema_id) === parseInt(id));
                                if (row) {
                                    self.eliminarTema(row);
                                }
                            });
                    }
                    this.dtTema.clear();
                    this.temas.forEach(t => {
                        const acciones = `
                        <div class="btn-group">
                            <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                                ⚙ <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="#" class="editar-tema" data-id="${t.tema_id}">Editar</a></li>
                                <li><a href="#" class="eliminar-tema" data-id="${t.tema_id}">Eliminar</a></li>
                            </ul>
                        </div>
                        `;
                        this.dtTema.row.add([
                            t.tema_id,
                            this.miniaturaPreview(t.miniatura),
                            this.miniaturaPreview(t.vista_app),
                            t.nombre_tema || '',
                            acciones
                        ]);
                    });
                    this.dtTema.draw(false);
                });
            })
            .finally(() => {
                this.desbloquear();
            });
    },
    crearTema() {
        this.bloquear('Guardando tema...');
        axios.post(this.apphost + '/U9bTtemaCrear', this.nuevoTema)
            .then(() => {
                $('#modalCrearTema').modal('hide');
                apprise('Tema creado');
            })
            .finally(() => {
                this.desbloquear();
                this.listarTemas();
            });
    },
    guardarTema() {
        this.bloquear('Actualizando tema...');
        axios.post(this.apphost + '/U9bTtemaEditar', this.formTema)
            .then(() => {
                $('#modalEditarTema').modal('hide');
                apprise('Tema actualizado');
            })
            .finally(() => {
                this.desbloquear();
                this.listarTemas();
            });
    },
    eliminarTema(t) {
        apprise('¿Eliminar tema #' + t.tema_id + '?', { confirm: true }, ok => {
            if (!ok) return;
            this.bloquear('Eliminando tema...');
            axios.post(this.apphost + '/U9bTtemaEliminar', { tema_id: t.tema_id })
                .then(() => {
                    apprise('Tema eliminado');
                })
                .finally(() => {
                    this.desbloquear();
                    this.listarTemas();
                });
        });
    }
};