const temaxnegMethods = {
    abrirModalTemaxNeg() {
        this.listarTemaXNeg();
        $('#modalTemaXNeg').modal('show');
    },
    abrirModalCrearTemaXNeg() {
        this.bloquear('Cargando...');
        Promise.all([
            this.cargarTemas(),
            this.cargarNegocios()
        ])
        .then(() => {
            this.nuevoTemaXNeg = {
                tema_id: null,
                neg_id: null
            };
            $('#modalTemaXNeg').modal('hide');
            $('#modalCrearTemaXNeg').modal('show');
        })
        .finally(() => {
            this.desbloquear();
        });
    },
    volverModalTemaXNeg() {
        $('#modalCrearTemaXNeg').modal('hide');
        $('#modalTemaXNeg').modal('show');
    },
    volverModalTemaXNegEditar() {
        $('#modalEditarTemaXNeg').modal('hide');
        $('#modalTemaXNeg').modal('show');
    },
    cargarNegocios() {
        return axios.get(this.apphost + '/U9bTnegListarCombo')
            .then(r => {
                this.negociosOptions = r.data.data || [];
            });
    },
    crearTemaXNeg() {
        if (!this.nuevoTemaXNeg.tema_id) {
            apprise('Selecciona un tema');
            return;
        }
        if (!this.nuevoTemaXNeg.neg_id) {
            apprise('Selecciona un negocio');
            return;
        }
        this.bloquear('Guardando relación...');
        axios.post(this.apphost + '/U9bTtemaxnegCrear', this.nuevoTemaXNeg)
            .then(() => {
                apprise('Registro creado');
                $('#modalCrearTemaXNeg').modal('hide');
            })
            .catch(e => {
                console.error(e);
                apprise('Error al guardar');
            })
            .finally(() => {
                this.desbloquear();
                this.listarTemaXNeg();
                $('#modalTemaXNeg').modal('show');
            });
    },
    guardarTemaXNeg() {
        if (!this.formTemaXNeg.tema_id) {
            apprise('Selecciona un tema');
            return;
        }
        if (!this.formTemaXNeg.neg_id) {
            apprise('Selecciona un negocio');
            return;
        }
        this.bloquear('Actualizando relación...');
        const payload = {
            temaxneg_id: this.formTemaXNeg.temaxneg_id,
            tema_id: this.formTemaXNeg.tema.tema_id,
            neg_id: this.formTemaXNeg.negocio.neg_id
        };
        axios.post(this.apphost + '/U9bTtemaxnegEditar', payload)
            .then(() => {
                apprise('Registro actualizado');
                $('#modalEditarTemaXNeg').modal('hide');
            })
            .catch(e => {
                console.error(e);
                apprise('Error al actualizar');
            })
            .finally(() => {
                this.desbloquear();
                this.listarTemaXNeg();
                $('#modalTemaXNeg').modal('show');
            });
    },
    listarTemaXNeg() {
        this.bloquear('Cargando relaciones...');
        axios.get(this.apphost + '/U9bTtemaxnegListar')
            .then(r => {
                this.temasXNeg = r.data.data || [];
                this.$nextTick(() => {
                    if (!this.dtTemaXNeg) {
                        this.dtTemaXNeg = $('#tablaTemaXNeg').DataTable({
                            language: typeof dt_language !== 'undefined' ? dt_language : undefined,
                            scrollX: true,
                            dom: 'frtip',
                            order: [[0, 'desc']]
                        });
                        const self = this;
                        $('#tablaTemaXNeg tbody')
                            .on('click', 'a.editar-temaxneg', function(e) {
                                e.preventDefault();
                                const id = $(this).data('id');
                                const row = self.temasXNeg.find(x => parseInt(x.temaxneg_id) === parseInt(id));
                                if (row) {
                                    self.bloquear('Cargando...');
                                    Promise.all([
                                        self.cargarTemas(),
                                        self.cargarNegocios()
                                    ])
                                    .then(() => {
                                        self.formTemaXNeg = {
                                            temaxneg_id: parseInt(row.temaxneg_id),
                                            tema: self.temasOptions.find(x => Number(x.tema_id) === Number(row.tema_id)),
                                            negocio: self.negociosOptions.find(x => Number(x.neg_id) === Number(row.neg_id))
                                        };
                                        self.$nextTick(() => {
                                            self.formTemaXNeg.tema_id = parseInt(row.tema_id);
                                            self.formTemaXNeg.neg_id = parseInt(row.neg_id);
                                            $('#modalTemaXNeg').modal('hide');
                                            $('#modalEditarTemaXNeg').modal('show');
                                        });
                                    })
                                    .finally(() => {
                                        self.desbloquear();
                                    });
                                }
                            })
                            .on('click', 'a.eliminar-temaxneg', function(e) {
                                e.preventDefault();
                                const id = $(this).data('id');
                                self.eliminarTemaXNeg(id);
                            });
                    }
                    this.dtTemaXNeg.clear();
                    this.temasXNeg.forEach(r => {
                        const acciones = `
                        <div class="btn-group">
                            <button class="btn btn-mini dropdown-toggle" data-toggle="dropdown">
                                ⚙ <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a href="#" class="editar-temaxneg" data-id="${r.temaxneg_id}">Editar</a></li>
                                <li><a href="#" class="eliminar-temaxneg" data-id="${r.temaxneg_id}">Eliminar</a></li>
                            </ul>
                        </div>
                        `;
                        this.dtTemaXNeg.row.add([
                            r.temaxneg_id,
                            r.nombre_tema || '',
                            r.negocio || '',
                            acciones
                        ]);
                    });
                    this.dtTemaXNeg.draw(false);
                });
            })
            .finally(() => {
                this.desbloquear();
            });
    },
    eliminarTemaXNeg(id) {
        apprise('¿Eliminar registro?', { confirm: true }, ok => {
            if (!ok) return;
            this.bloquear('Eliminando...');
            axios.post(this.apphost + '/U9bTtemaxnegEliminar', { temaxneg_id: id })
                .then(() => {
                    apprise('Eliminado');
                })
                .finally(() => {
                    this.desbloquear();
                    this.listarTemaXNeg();
                });
        });
    }
};