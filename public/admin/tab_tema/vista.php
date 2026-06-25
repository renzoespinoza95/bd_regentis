<!-- =========================================================
     REG_TEMA
     PARTE 2A
     Bootstrap 2.3.2
========================================================= -->

<div class="row-fluid" id="appTema">

    <div class="span12">

        <!-- =====================================
             TITULO
        ====================================== -->
        <div class="titulo-fijo clearfix">

            <div style="float:left;">
                <h2 style="margin:0;">
                    Temas
                </h2>
            </div>

            <div class="btn-group pull-right">

                <button
                    class="btn btn-info dropdown-toggle"
                    data-toggle="dropdown">

                    <i class="fa fa-paint-brush"></i>

                    <span class="caret"></span>

                </button>

                <ul class="dropdown-menu pull-right">

                    <li>

                        <a
                            href="#"
                            @click.prevent="
                                abrirModalTemaxNeg
                            ">

                            <i class="fa fa-link"></i>

                            Tema x Neg

                        </a>

                    </li>

                    <li>

                        <a
                            href="#"
                            @click.prevent="
                                abrirModalCrearTema
                            ">

                            <i class="fa fa-plus"></i>

                            Nuevo Item

                        </a>

                    </li>

                </ul>

            </div>

        </div>

        <!-- =====================================
             TABLA PRINCIPAL
        ====================================== -->
        <div class="span12 tabla_esp_sup">

            <table
                id="tablaTema"
                class="table table-bordered table-condensed">
<thead>

    <tr>

        <th>ID</th>

        <th>Nombre</th>

        <th>Fondo</th>

        <th>Acciones</th>

    </tr>

</thead>

                <tbody></tbody>

            </table>

        </div>

        <!-- =====================================
             MODAL CREAR TEMA
        ====================================== -->
        <div
            id="modalCrearTema"
            class="modal hide fade fullscreen"
            tabindex="-1">

            <div class="modal-header">

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    ×

                </button>

                <h3>

                    Nuevo Tema

                </h3>

            </div>

            <div class="modal-body">

                <!-- TOPNAVBAR -->

                <div class="control-group">

                <label class="control-label">

                    Nombre Tema

                </label>

                <div class="controls">

                    <input

                        type="text"

                        class="input-xxlarge"

                        v-model="
                            nuevoTema.nombre_tema
                        ">

                </div>

            </div>
                <div class="control-group">

                    <label class="control-label">

                        Top Navbar

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                nuevoTema.topnavbar
                            "

                            class="input-xxlarge"

                            rows="4"

                            placeholder="#7DBA66"

                        ></textarea>

                    </div>

                </div>

                <!-- FONDO -->
                <div class="control-group">

                    <label class="control-label">

                        Fondo

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                nuevoTema.fondo
                            "

                            class="input-xxlarge"

                            rows="10"

                            placeholder="CSS del fondo"

                        ></textarea>

                    </div>

                </div>

                <!-- BOTON -->
                <div class="control-group">

                    <label class="control-label">

                        Botón

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                nuevoTema.boton
                            "

                            class="input-xxlarge"

                            rows="4"

                            placeholder="#7DBA66"

                        ></textarea>

                    </div>

                </div>

                <!-- FONDO CARD -->
                <div class="control-group">

                    <label class="control-label">

                        Fondo Card

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                nuevoTema.fondo_card
                            "

                            class="input-xxlarge"

                            rows="4"

                            placeholder="#FFFFFF"

                        ></textarea>

                    </div>

                </div>

<hr>

                    <h4>
                        Vista previa
                    </h4>

                    <div
                        style="
                            display:flex;
                            gap:15px;
                            flex-wrap:wrap;
                        ">

                        <!-- TOP NAVBAR -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Top Navbar

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.topnavbar
                                )">

                            </div>

                        </div>

                        <!-- FONDO -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Fondo

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.fondo
                                )">

                            </div>

                        </div>

                        <!-- BOTON -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Botón

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.boton
                                )">

                            </div>

                        </div>

                        <!-- FONDO CARD -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Fondo Card

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.fondo_card
                                )">

                            </div>

                        </div>

                    </div>



            </div>

            <div class="modal-footer">

                <button

                    class="btn btn-primary"

                    @click="crearTema">

                    Guardar

                </button>

                <button

                    class="btn"

                    data-dismiss="modal">

                    Cancelar

                </button>

            </div>

        </div>

        <!-- =====================================
             MODAL EDITAR TEMA
        ====================================== -->
        <div
            id="modalEditarTema"
            class="modal hide fade fullscreen"
            tabindex="-1">

            <div class="modal-header">

                <button
                    type="button"
                    class="close"
                    data-dismiss="modal">

                    ×

                </button>

                <h3>

                    Editar Tema

                </h3>

            </div>

            <div class="modal-body">

                <input
                    type="hidden"
                    v-model="
                        formTema.tema_id
                    ">

                <!-- TOPNAVBAR -->
                <div class="control-group">

                    <div class="control-group">

    <label class="control-label">

        Nombre Tema

    </label>

    <div class="controls">

        <input

            type="text"

            class="input-xxlarge"

            v-model="
                formTema.nombre_tema
            ">

    </div>

</div>

                    <label class="control-label">

                        Top Navbar

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                formTema.topnavbar
                            "

                            class="input-xxlarge"

                            rows="4"

                        ></textarea>

                    </div>

                </div>

                <!-- FONDO -->
                <div class="control-group">

                    <label class="control-label">

                        Fondo

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                formTema.fondo
                            "

                            class="input-xxlarge"

                            rows="10"

                        ></textarea>

                    </div>

                </div>

                <!-- BOTON -->
                <div class="control-group">

                    <label class="control-label">

                        Botón

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                formTema.boton
                            "

                            class="input-xxlarge"

                            rows="4"

                        ></textarea>

                    </div>

                </div>

                <!-- FONDO CARD -->
                <div class="control-group">

                    <label class="control-label">

                        Fondo Card

                    </label>

                    <div class="controls">

                        <textarea

                            v-model="
                                formTema.fondo_card
                            "

                            class="input-xxlarge"

                            rows="4"

                        ></textarea>

                    </div>

                </div>

                    <hr>

                    <h4>
                        Vista previa
                    </h4>

                    <div
                        style="
                            display:flex;
                            gap:15px;
                            flex-wrap:wrap;
                        ">

                        <!-- TOP NAVBAR -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Top Navbar

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.topnavbar
                                )">

                            </div>

                        </div>

                        <!-- FONDO -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Fondo

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.fondo
                                )">

                            </div>

                        </div>

                        <!-- BOTON -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Botón

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.boton
                                )">

                            </div>

                        </div>

                        <!-- FONDO CARD -->
                        <div>

                            <div
                                style="
                                    margin-bottom:5px;
                                    font-weight:bold;
                                ">

                                Fondo Card

                            </div>

                            <div

                                style="
                                    width:180px;
                                    height:180px;
                                    border:1px solid #ccc;
                                    border-radius:10px;
                                "

                                :style="cssToObject(
                                    formTema.fondo_card
                                )">

                            </div>

                        </div>

                    </div>

            </div>

            <div class="modal-footer">

                <button

                    class="btn btn-primary"

                    @click="
                        guardarTema
                    ">

                    Guardar

                </button>

                <button

                    class="btn"

                    data-dismiss="modal">

                    Cancelar

                </button>

            </div>

        </div>

    </div>


<!-- =====================================
     MODAL LISTADO TEMA X NEG
====================================== -->
<div
    id="modalTemaXNeg"
    class="modal hide fade fullscreen"
    tabindex="-1">

    <div class="modal-header">

        <button
            type="button"
            class="close"
            data-dismiss="modal">

            ×

        </button>

        <h3>

            Tema x Negocio

        </h3>

    </div>

    <div class="modal-body">

        <table
            id="tablaTemaXNeg"
            class="table table-bordered table-condensed">

            <thead>

                <tr>

                    <th>ID</th>

                    <th>Negocio</th>

                    <th>Tema</th>

                    <th>Acciones</th>

                </tr>

            </thead>

            <tbody></tbody>

        </table>

    </div>

    <div class="modal-footer">

        <button

            class="btn btn-success"

            @click="
                abrirModalCrearTemaXNeg
            ">

            <i class="icon-plus icon-white"></i>

            Agregar

        </button>

        <button

            class="btn"

            data-dismiss="modal">

            Cerrar

        </button>

    </div>

</div>

<!-- =====================================
     MODAL CREAR TEMA X NEG
====================================== -->
<div
    id="modalCrearTemaXNeg"
    class="modal hide fade fullscreen"
    tabindex="-1">

    <div class="modal-header">

        <button
            type="button"
            class="close"
            data-dismiss="modal">

            ×

        </button>

        <h3>

            Nuevo Tema x Negocio

        </h3>

    </div>

    <div class="modal-body">

        <!-- TEMA -->
        <div class="control-group">

            <label class="control-label">

                Tema

            </label>

            <div class="controls">

                <v-select

                    :options="temasOptions"

                    :reduce="x => x.tema_id"

                    label="nombre_tema"

                    placeholder="Selecciona un tema"

                    v-model="
                        nuevoTemaXNeg.tema_id
                    "

                    style="width:420px;"

                ></v-select>

            </div>

        </div>

        <!-- NEGOCIO -->
        <div class="control-group">

            <label class="control-label">

                Negocio

            </label>

            <div class="controls">

                <v-select

                    :options="negociosOptions"

                    :reduce="x => x.neg_id"

                    label="nombre"

                    placeholder="Selecciona un negocio"

                    v-model="
                        nuevoTemaXNeg.neg_id
                    "

                    style="width:420px;"

                ></v-select>

            </div>

        </div>

    </div>

    <div class="modal-footer">

        <button

            class="btn btn-primary"

            @click="
                crearTemaXNeg
            ">

            Guardar

        </button>

        <button

            class="btn"

            @click="
                volverModalTemaXNeg
            ">

            Cancelar

        </button>

    </div>

</div>

<!-- =====================================
     MODAL EDITAR TEMA X NEG
====================================== -->
<div
    id="modalEditarTemaXNeg"
    class="modal hide fade fullscreen"
    tabindex="-1">

    <div class="modal-header">

        <button
            type="button"
            class="close"
            data-dismiss="modal">

            ×

        </button>

        <h3>

            Editar Tema x Negocio

        </h3>

    </div>

    <div class="modal-body">

        <input
            type="hidden"
            v-model="
                formTemaXNeg.temaxneg_id
            ">

        <!-- TEMA -->
        <div class="control-group">

            <label class="control-label">

                Tema

            </label>

            <div class="controls">

                <v-select

                    :options="temasOptions"

                    label="nombre_tema"

                    v-model="
                        formTemaXNeg.tema
                    "

                ></v-select>

            </div>

        </div>

        <!-- NEGOCIO -->
        <div class="control-group">

            <label class="control-label">

                Negocio

            </label>

            <div class="controls">

                <v-select

                    :options="negociosOptions"

                    label="nombre"

                    v-model="
                        formTemaXNeg.negocio
                    "

                ></v-select>

            </div>

        </div>

    </div>

    <div class="modal-footer">

        <button

            class="btn btn-primary"

            @click="
                guardarTemaXNeg
            ">

            Guardar

        </button>

        <button

            class="btn"

            @click="
                volverModalTemaXNegEditar
            ">

            Cancelar

        </button>

    </div>

</div>    

</div>

<script>

Vue.component(
    'v-select',
    VueSelect.VueSelect
);

const appTema = new Vue({

    el:'#appTema',

    data:{

        apphost:
            (
                typeof apphost
                !== 'undefined'
            )
            ? apphost
            : '',

        /* =========================
           TEMAS
        ========================= */

        temas:[],

        dtTema:null,

        temasOptions:[],

        nuevoTema:{

            nombre_tema:'',

            topnavbar:'',

            fondo:'',

            boton:'',

            fondo_card:''

        },

        formTema:{

            tema_id:0,

            nombre_tema:'',

            topnavbar:'',

            fondo:'',

            boton:'',

            fondo_card:''

        },

        /* =========================
           SE USARA EN PARTE 3B
        ========================= */

        dtTemaXNeg:null,

        temasXNeg:[],

        negociosOptions:[],

        nuevoTemaXNeg:{

            tema_id:null,

            neg_id:null

        },

        formTemaXNeg:{

            temaxneg_id:0,

            tema:null,

            negocio:null

        }

    },

    methods:{

        /* =====================================
           BLOCK UI
        ===================================== */

        bloquear(msg){

            $.blockUI({

                message:

                    '<h4>' +

                    msg +

                    '</h4>',

                css:{

                    border:'none',

                    padding:'15px',

                    background:'#000',

                    opacity:.6,

                    color:'#fff'

                }

            });

        },

        abrirModalTemaxNeg(){

    this.listarTemaXNeg();

    $('#modalTemaXNeg')
        .modal('show');

},

/* =====================================
   ABRIR MODAL CREAR TEMA X NEG
===================================== */
abrirModalCrearTemaXNeg(){

    this.bloquear(
        'Cargando...'
    );

    Promise.all([

        this.cargarTemas(),

        this.cargarNegocios()

    ])

    .then(()=>{

        this.nuevoTemaXNeg = {

            tema_id:null,

            neg_id:null

        };

        $('#modalTemaXNeg')
            .modal('hide');

        $('#modalCrearTemaXNeg')
            .modal('show');

    })

    .finally(()=>{

        this.desbloquear();

    });

},

/* =====================================
   VOLVER DESDE CREAR
===================================== */
volverModalTemaXNeg(){

    $('#modalCrearTemaXNeg')
        .modal('hide');

    $('#modalTemaXNeg')
        .modal('show');

},

/* =====================================
   VOLVER DESDE EDITAR
===================================== */
volverModalTemaXNegEditar(){

    $('#modalEditarTemaXNeg')
        .modal('hide');

    $('#modalTemaXNeg')
        .modal('show');

},

/* =====================================
   CREAR TEMA X NEG
===================================== */
crearTemaXNeg(){

    if(
        !this.nuevoTemaXNeg.tema_id
    ){
        apprise(
            'Selecciona un tema'
        );
        return;
    }

    if(
        !this.nuevoTemaXNeg.neg_id
    ){
        apprise(
            'Selecciona un negocio'
        );
        return;
    }

    this.bloquear(
        'Guardando relación...'
    );

    axios.post(

        this.apphost +

        '/U9bTtemaxnegCrear',

        this.nuevoTemaXNeg

    )

    .then(()=>{

        apprise(
            'Registro creado'
        );

        $('#modalCrearTemaXNeg')
            .modal('hide');

    })

    .catch(e=>{

        console.error(e);

        apprise(
            'Error al guardar'
        );

    })

    .finally(()=>{

        this.desbloquear();

        this.listarTemaXNeg();

        $('#modalTemaXNeg')
            .modal('show');

    });

},

/* =====================================
   GUARDAR TEMA X NEG
===================================== */
guardarTemaXNeg(){

    if(
        !this.formTemaXNeg.tema_id
    ){
        apprise(
            'Selecciona un tema'
        );
        return;
    }

    if(
        !this.formTemaXNeg.neg_id
    ){
        apprise(
            'Selecciona un negocio'
        );
        return;
    }

    this.bloquear(
        'Actualizando relación...'
    );

    const payload = {

            temaxneg_id:

                this.formTemaXNeg.temaxneg_id,

            tema_id:

                this.formTemaXNeg.tema.tema_id,

            neg_id:

                this.formTemaXNeg.negocio.neg_id

        };

        axios.post(

            this.apphost +

            '/U9bTtemaxnegEditar',

            payload

        )

    .then(()=>{

        apprise(
            'Registro actualizado'
        );

        $('#modalEditarTemaXNeg')
            .modal('hide');

    })

    .catch(e=>{

        console.error(e);

        apprise(
            'Error al actualizar'
        );

    })

    .finally(()=>{

        this.desbloquear();

        this.listarTemaXNeg();

        $('#modalTemaXNeg')
            .modal('show');

    });

},

/* =====================================
   LISTAR TEMA X NEG
===================================== */
listarTemaXNeg() {

    this.bloquear(
        'Cargando relaciones...'
    );

    axios.get(

        this.apphost +

        '/U9bTtemaxnegListar'

    )

    .then(r=>{

        this.temasXNeg =

            r.data.data || [];

        this.$nextTick(()=>{

            if(
                !this.dtTemaXNeg
            ){

                this.dtTemaXNeg =

                    $('#tablaTemaXNeg')

                    .DataTable({

                        language:

                            typeof dt_language
                            !== 'undefined'

                            ? dt_language

                            : undefined,

                        scrollX:true,

                        dom:'frtip',

                        order:[
                            [0,'desc']
                        ]

                    });

                const self = this;

                $('#tablaTemaXNeg tbody')

                .on(
                    'click',
                    'a.editar-temaxneg',
                    function(e){

                        e.preventDefault();

                        const id =

                            $(this)
                            .data('id');

                        const row =

                            self.temasXNeg.find(

                                x =>

                                parseInt(
                                    x.temaxneg_id
                                )

                                ===

                                parseInt(
                                    id
                                )

                            );

                        if(row){

                            self.bloquear(
                                'Cargando...'
                            );

                            Promise.all([

                                self.cargarTemas(),

                                self.cargarNegocios()

                            ])

                            .then(()=>{

                                self.formTemaXNeg = {

                                    temaxneg_id:
                                        parseInt(
                                            row.temaxneg_id
                                        ),

                                    tema:

                                        self.temasOptions.find(

                                            x =>

                                            Number(
                                                x.tema_id
                                            ) ===

                                            Number(
                                                row.tema_id
                                            )

                                        ),

                                    negocio:

                                        self.negociosOptions.find(

                                            x =>

                                            Number(
                                                x.neg_id
                                            ) ===

                                            Number(
                                                row.neg_id
                                            )

                                        )

                                };

                                self.$nextTick(()=>{

                                    self.formTemaXNeg.tema_id =
                                        parseInt(
                                            row.tema_id
                                        );

                                    self.formTemaXNeg.neg_id =
                                        parseInt(
                                            row.neg_id
                                        );

                                    $('#modalTemaXNeg')
                                        .modal('hide');

                                    $('#modalEditarTemaXNeg')
                                        .modal('show');

                                });

                            })

                            .finally(()=>{

                                self.desbloquear();

                            });

                        }

                    }
                )

                .on(
                    'click',
                    'a.eliminar-temaxneg',
                    function(e){

                        e.preventDefault();

                        const id =

                            $(this)
                            .data('id');

                        self.eliminarTemaXNeg(
                            id
                        );

                    }
                );

            }

            this.dtTemaXNeg.clear();

            this.temasXNeg.forEach(r=>{

                const acciones = `

                <div class="btn-group">

                    <button
                        class="btn btn-mini dropdown-toggle"
                        data-toggle="dropdown">

                        ⚙

                        <span class="caret"></span>

                    </button>

                    <ul class="dropdown-menu">

                        <li>

                            <a
                                href="#"
                                class="editar-temaxneg"
                                data-id="${r.temaxneg_id}">

                                Editar

                            </a>

                        </li>

                        <li>

                            <a
                                href="#"
                                class="eliminar-temaxneg"
                                data-id="${r.temaxneg_id}">

                                Eliminar

                            </a>

                        </li>

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

            this.dtTemaXNeg.draw(
                false
            );

        });

    })

    .finally(()=>{

        this.desbloquear();

    });

},

cssToObject(cssText){

    if(!cssText){

        return {};
    }

    const obj = {};

    cssText
        .split(';')
        .forEach(row=>{

            const parts =
                row.split(':');

            if(
                parts.length < 2
            ){
                return;
            }

            const key =
                parts[0]
                .trim()
                .replace(
                    /-([a-z])/g,
                    (_,c)=>
                        c.toUpperCase()
                );

            const value =
                parts
                .slice(1)
                .join(':')
                .trim();

            if(key){

                obj[key] = value;

            }

        });

    return obj;

},

eliminarTemaXNeg(id){

    apprise(

        '¿Eliminar registro?',

        {
            confirm:true
        },

        ok=>{

            if(!ok){

                return;

            }

            this.bloquear(
                'Eliminando...'
            );

            axios.post(

                this.apphost +

                '/U9bTtemaxnegEliminar',

                {

                    temaxneg_id:id

                }

            )

            .then(()=>{

                apprise(
                    'Eliminado'
                );

            })

            .finally(()=>{

                this.desbloquear();

                this.listarTemaXNeg();

            });

        }

    );

},

        desbloquear(){

            $.unblockUI();

        },

        /* =====================================
           MODALES
        ===================================== */

        abrirModalCrearTema(){

            this.nuevoTema = {

                topnavbar:'',

                fondo:'',

                boton:'',

                fondo_card:''

            };

            $('#modalCrearTema')
                .modal('show');

        },

        abrirModalEditarTema(t){

            this.formTema = {

                tema_id:
                    parseInt(
                        t.tema_id
                    ),

                nombre_tema:
                        t.nombre_tema || '',                    

                topnavbar:
                    t.topnavbar
                    || '',

                fondo:
                    t.fondo
                    || '',

                boton:
                    t.boton
                    || '',

                fondo_card:
                    t.fondo_card
                    || ''

            };

            $('#modalEditarTema')
                .modal('show');

        },

        cargarTemas(){

    return axios.get(

        this.apphost +

        '/U9bTtemaListar'

    )

    .then(r=>{

        const rows =

            r.data.data || [];

        this.temasOptions =

            rows.map(x => ({

                tema_id:
                    parseInt(
                        x.tema_id
                    ),

                nombre_tema:
                    x.nombre_tema || ''

            }));

    });

},

cargarNegocios(){

    return axios.get(

        this.apphost +

        '/U9bTnegListarCombo'

    )

    .then(r=>{

        this.negociosOptions =

            r.data.data || [];

    });

},

        /* =====================================
           LISTAR TEMAS
        ===================================== */

        listarTemas(){

            this.bloquear(

                'Cargando temas...'

            );

            axios.get(

                this.apphost +

                '/U9bTtemaListar'

            )

            .then(r=>{

                this.temas =

                    r.data.data

                    || [];

                this.temasOptions =

    this.temas.map(

        t => ({

            tema_id:
                parseInt(
                    t.tema_id
                ),

            nombre_tema:
                t.nombre_tema || ''

        })

    );

                this.$nextTick(()=>{

                    if(
                        !this.dtTema
                    ){

                        this.dtTema =

                            $('#tablaTema')

                            .DataTable({

                                language:

                                    typeof dt_language
                                    !== 'undefined'

                                    ? dt_language

                                    : undefined,

                                scrollX:true,

                                dom:'frtip',

                                order:[
                                    [0,'desc']
                                ]

                            });

                        const self=this;

                        $('#tablaTema tbody')

                        .on(

                            'click',

                            'a.editar-tema',

                            function(e){

                                e.preventDefault();

                                const id =

                                    $(this)

                                    .data('id');

                                const row =

                                    self.temas.find(

                                        x =>

                                        parseInt(
                                            x.tema_id
                                        )

                                        ===

                                        parseInt(
                                            id
                                        )

                                    );

                                if(row){

                                    self
                                    .abrirModalEditarTema(
                                        row
                                    );

                                }

                            }

                        )

                        .on(

                            'click',

                            'a.eliminar-tema',

                            function(e){

                                e.preventDefault();

                                const id =

                                    $(this)

                                    .data('id');

                                const row =

                                    self.temas.find(

                                        x =>

                                        parseInt(
                                            x.tema_id
                                        )

                                        ===

                                        parseInt(
                                            id
                                        )

                                    );

                                if(row){

                                    self
                                    .eliminarTema(
                                        row
                                    );

                                }

                            }

                        );

                    }

                    this.dtTema.clear();

                    this.temas.forEach(

                        t => {

                            const acciones = `

                            <div class="btn-group">

                                <button
                                    class="btn btn-mini dropdown-toggle"
                                    data-toggle="dropdown">

                                    ⚙

                                    <span class="caret"></span>

                                </button>

                                <ul class="dropdown-menu">

                                    <li>

                                        <a
                                            href="#"
                                            class="editar-tema"
                                            data-id="${t.tema_id}">

                                            Editar

                                        </a>

                                    </li>

                                    <li>

                                        <a
                                            href="#"
                                            class="eliminar-tema"
                                            data-id="${t.tema_id}">

                                            Eliminar

                                        </a>

                                    </li>

                                </ul>

                            </div>

                            `;

                            this.dtTema.row.add([

                                        t.tema_id,

                                        t.nombre_tema || '',

                                        this.cssPreview(

                                            t.fondo

                                        ),

                                        acciones

                                    ]);

                        }

                    );

                    this.dtTema.draw(
                        false
                    );

                });

            })

            .finally(()=>{

                this.desbloquear();

            });

        },

        /* =====================================
           CREAR TEMA
        ===================================== */

        crearTema(){

            this.bloquear(

                'Guardando tema...'

            );

            axios.post(

                this.apphost +

                '/U9bTtemaCrear',

                this.nuevoTema

            )

            .then(()=>{

                $('#modalCrearTema')
                .modal('hide');

                apprise(

                    'Tema creado'

                );

            })

            .finally(()=>{

                this.desbloquear();

                this.listarTemas();

            });

        },

        /* =====================================
           EDITAR TEMA
        ===================================== */

        guardarTema(){

            this.bloquear(

                'Actualizando tema...'

            );

            axios.post(

                this.apphost +

                '/U9bTtemaEditar',

                this.formTema

            )

            .then(()=>{

                $('#modalEditarTema')
                .modal('hide');

                apprise(

                    'Tema actualizado'

                );

            })

            .finally(()=>{

                this.desbloquear();

                this.listarTemas();

            });

        },

        cssPreview(css){

    if(

        !css

        ||

        css.trim() === ''

    ){

        return '—';

    }

    return `

        <div

            style="

                width:55px;

                height:55px;

                border-radius:10px;

                border:1px solid #dcdcdc;

                ${css}

            ">

        </div>

    `;

},

        /* =====================================
           ELIMINAR TEMA
        ===================================== */

        eliminarTema(t){

            apprise(

                '¿Eliminar tema #' +

                t.tema_id +

                '?',

                {

                    confirm:true

                },

                ok=>{

                    if(!ok){

                        return;

                    }

                    this.bloquear(

                        'Eliminando tema...'

                    );

                    axios.post(

                        this.apphost +

                        '/U9bTtemaEliminar',

                        {

                            tema_id:

                                t.tema_id

                        }

                    )

                    .then(()=>{

                        apprise(

                            'Tema eliminado'

                        );

                    })

                    .finally(()=>{

                        this.desbloquear();

                        this.listarTemas();

                    });

                }

            );

        }

    },

    mounted(){

        this.listarTemas();

    }

});

</script>