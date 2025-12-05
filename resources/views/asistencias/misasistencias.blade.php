@extends('layout.app')
@section('title', 'Mis asistencias')

@section('cabecera')
    <link href="{{ secure_asset('front/vendor/quill/quill.snow.css') }}?v=1.0.0" rel="stylesheet">
    <script src="{{ secure_asset('front/vendor/multiselect/bootstrap.bundle.min.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/vendor/multiselect/bootstrap_multiselect.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/vendor/multiselect/form_multiselect.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/vendor/echartjs/echarts.min.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/js/app/ChartMananger.js') }}?v=1.0.0"></script>
    <script>
        const empresas = @json($empresas);
        const tipoModalidad = @json($tipoModalidad);
        const tipoAsistencia = @json($tipoAsistencia);
        const tipoPersonal = @json($tipoPersonal);
    </script>
    <style>
    </style>
@endsection

@section('content')
    <!-- Cards resumen -->
    <div class="row" id="list-estado"></div>
    <script>
        let bliColor = {
            info: '#54b4d3',
            warning: '#e4a11b',
            purple: '#7367f0',
            primary: '#3b71ca',
            success: '#14a44d',
            danger: '#dc4c64',
            light: '#fbfbfb',
            secondary: '#9fa6b2',
            dark: '#332d2d',
        };

        let incidencia_estados = [{
                name: "estado-total",
                text: "Total",
                color: "purple",
                searchTable: 0,
                chart: false,
            },
            {
                name: "estado-asistencias",
                text: "PUNTUALES",
                color: "success",
                searchTable: 2,
                chart: true,
            },
            {
                name: "estado-faltas",
                text: "FALTAS",
                color: "danger",
                searchTable: 1,
                chart: true,
            },
            {
                name: "estado-tardanzas",
                text: "TARDANZAS",
                color: "warning",
                searchTable: 4,
                chart: true,
            },
            {
                name: "estado-justificados",
                text: "JUSTIFICADOS",
                color: "info",
                searchTable: 3,
                chart: true,
            },
            {
                name: "estado-noaplica",
                text: "NO APLICA",
                color: "dark",
                searchTable: 6,
                chart: true,
            },
        ];

        let list_estado = $('#list-estado');
        incidencia_estados.forEach((e, i) => {
            list_estado.append(
                $('<div>', {
                    class: 'col-xxl-2 col-lg-4 col-6 mb-3'
                }).append(
                    $('<div>', {
                        class: 'card',
                        style: 'height: 100%;',
                        type: 'button',
                        "data-mdb-ripple-init": '',
                        onclick: `searchTable(${e.searchTable})`
                    }).append(
                        $('<div>', {
                            class: 'card-body row',
                            style: 'color: ' + bliColor[e.color],
                        }).append(
                            $('<div>', {
                                class: e.chart ? 'col-7' : ''
                            }).append(
                                $('<h6>', {
                                    class: 'card-title chart-estado-title text-nowrap mb-1'
                                }).text(e.text),
                                $('<h4>', {
                                    class: 'subtitle-count',
                                    id: 'count-' + e.name
                                }).text(0)
                            ),
                            e.chart ? $('<div>', {
                                class: 'col-5'
                            }).append($('<div>', {
                                id: 'chart-' + e.name
                            })) : null
                        )
                    )
                )
            );
            if (e.chart) {
                e.chart = new ChartMananger({
                    id: 'chart-' + e.name,
                    config: {
                        tipo: 'estado',
                        altura: 5,
                        bg: bliColor[e.color]
                    },
                    data: {
                        total: 100,
                        value: 0
                    }
                });
            }
        });

        function setEstados(obj_estado) {
            let total = obj_estado.reduce((acc, item) => acc + item.value, 0);
            $('#count-estado-total').text(total);

            obj_estado.forEach((e, i) => {
                $('#count-' + e.name).text(e.value);
                let estado = incidencia_estados.find(ie => ie.name == e.name);
                if (estado.chart)
                    estado.chart.updateOption({
                        data: {
                            total: total,
                            value: e.value
                        }
                    });
            });
        }
    </script>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body">
            <h6 class="fw-bold">📅 Mis asistencias diarias</h6>
            <div class="row mb-2">
                <div class="col-md-4 my-1">
                    <small class="form-label mb-0" for="fecha">Fecha</small>
                    <div class="input-group">
                        <button class="btn btn-primary px-2" type="button" id="btn-fecha-left" data-mdb-ripple-init>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <input type="month" id="filtro_fecha" class="form-control" value="{{ date('Y-m') }}">
                        <button class="btn btn-primary px-2" type="button" id="btn-fecha-right" data-mdb-ripple-init>
                            <i class="fas fa-angle-right"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-1 my-1 text-end mt-auto"><button class="btn btn-primary" onclick="filtroBusqueda()"
                        data-mdb-ripple-init>Filtrar</button></div>
            </div>
            <table id="tablaMisAsistencias" class="table table-hover text-nowrap w-100">
                <thead>
                    <tr class="text-bg-primary text-center">
                        <th>Jornada</th>
                        <th>Fecha</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Modalidad</th>
                        <th>Estado</th>
                        <th>Descuento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
            <script>
                const tablaMisAsistencias = new DataTable('#tablaMisAsistencias', {
                    scrollX: true,
                    scrollY: 400,
                    ajax: {
                        url: __url + `/asistencias/listar`,
                        dataSrc: function(json) {
                            let lista = json.data?.listado || [];
                            let estadosAsistencias = [{
                                    name: "estado-faltas",
                                    value: lista.filter(a => a.tipo_asistencia === 1).length
                                },
                                {
                                    name: "estado-asistencias",
                                    value: lista.filter(a => a.tipo_asistencia === 2).length
                                },
                                {
                                    name: "estado-justificados",
                                    value: lista.filter(a => a.tipo_asistencia === 3).length
                                },
                                {
                                    name: "estado-tardanzas",
                                    value: lista.filter(a => a.tipo_asistencia === 4).length
                                },
                                {
                                    name: "estado-noaplica",
                                    value: lista.filter(a => a.tipo_asistencia === 6).length
                                },
                            ];
                            setEstados(estadosAsistencias);
                            return lista;
                        },
                        error: function(xhr, error, thrown) {
                            boxAlert.table();
                            console.log('Respuesta del servidor:', xhr);
                        }
                    },
                    columns: [{
                            data: 'jornada', render: function(data, type, row) {
                                let dia = (data || 'domingo');
                                return dia.charAt(0).toUpperCase() + dia.slice(1);
                            }
                        },
                        {
                            data: 'fecha'
                        },
                        {
                            data: 'entrada',
                            render: function(data, type, row) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'salida',
                            render: function(data, type, row) {
                                return data || '-';
                            }
                        },
                        {
                            data: 'tipo_modalidad',
                            render: function(data, type, row) {
                                let tmodalidad = tipoModalidad[data];
                                return `<label class="badge" style="font-size: 0.75rem; background-color: ${tmodalidad?.color};"><i class="${tmodalidad?.icono} fa-1x me-1"></i>${tmodalidad?.descripcion}</label>`;
                            }
                        },
                        {
                            data: 'tipo_asistencia',
                            render: function(data, type, row) {
                                let tasistencia = tipoAsistencia.find(s => s.id == data) || {
                                    descripcion: 'Pendiente',
                                    color: '#9fa6b2'
                                };
                                return `<label class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</label>`;
                            }
                        },
                        {
                            data: 'descuento',
                            render: function(data, type, row) {
                                let tasistencia = row.tipo_asistencia;
                                return data || (tasistencia == 1 ? 'Día Comp.' : '-');
                            }
                        },
                        {
                            data: 'acciones'
                        }
                    ],
                    createdRow: function(row, data, dataIndex) {
                        $(row).addClass('text-center');
                        $(row).find('td:eq(0)').addClass('text-start');
                        $(row).find('td:eq(7)').addClass(`td-acciones`);
                    },
                    order: [
                        [1, 'desc']
                    ],
                    processing: true
                });

                function updateTable() {
                    tablaMisAsistencias.ajax.reload();
                }
                mostrar_acciones(tablaMisAsistencias);

                function filtroBusqueda() {
                    var filtroFecha = $('#filtro_fecha').val();

                    var nuevoUrl = __url + `/asistencias/listar?fecha=${filtroFecha}`;
                    tablaMisAsistencias.ajax.url(nuevoUrl).load();
                    tablaMisAsistencias.column([4]).search('').draw();
                }

                function searchTable(search) {
                    let tasistencia = tipoAsistencia.find(s => s.id == search)?.descripcion || '';
                    tablaMisAsistencias.column([4]).search(tasistencia).draw();

                    const contenedor = document.querySelector('.content-wrapper');
                    contenedor.scrollTo({
                        top: contenedor.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            </script>
        </div>
    </div>

    <button class="d-none" data-mdb-modal-init data-mdb-target="#modalJustificarDerivado"></button>
    <!-- Modal de Justificación -->
    <div class="modal fade" id="modalJustificarDerivado" tabindex="-1" aria-labelledby="modalJustificarDerivadoLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formJustificarDerivado" class="modal-content">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalJustificarDerivadoLabel">Justificar asistencia</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="list-group list-group-light">
                            <div class="list-group-item">
                                <label class="form-label me-2">Fecha Asistencia: </label><span style="font-size: .75rem;"
                                    aria-item="fecha">...</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center my-2">
                        <h6 class="font-weight-semibold text-primary tt-upper m-0" style="font-size: smaller;">Ingresar
                            Justificación</h6>
                        <span aria-item="estado">...</span>
                    </div>

                    <!-- Asunto -->
                    <div class="mb-3">
                        <label for="asunto" class="form-label requested">Asunto</label>
                        <input type="text" class="form-control" id="asunto" name="asunto"
                            placeholder="Motivo de la justificación" requested="Asunto">
                    </div>

                    <!-- Editor Quill -->
                    <div class="mb-3">
                        <label class="form-label requested">Contenido</label>
                        <div id="editor-justificarDerivado"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-ripple-init
                        data-mdb-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar <i class="far fa-paper-plane"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Justificación -->
    <div class="modal fade" id="modalJustificar" tabindex="-1" aria-labelledby="modalJustificarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formJustificar" class="modal-content">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalJustificarLabel">Justificar asistencia</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="list-group list-group-light">
                            <div class="list-group-item">
                                <label class="form-label me-2">Fecha Asistencia: </label><span style="font-size: .75rem;"
                                    aria-item="fecha">...</span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center my-2">
                        <h6 class="font-weight-semibold text-primary tt-upper m-0" style="font-size: smaller;">Ingresar
                            Justificación</h6>
                        <span aria-item="estado">...</span>
                    </div>

                    <!-- Asunto -->
                    <div class="mb-3">
                        <label for="asunto_justificar" class="form-label requested">Asunto</label>
                        <input type="text" class="form-control" id="asunto_justificar" name="asunto_justificar"
                            placeholder="Motivo de la justificación" requested="Asunto">
                    </div>

                    <!-- Editor Quill -->
                    <div class="mb-3">
                        <label class="form-label requested">Contenido</label>
                        <div id="editor-justificar"></div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-ripple-init
                        data-mdb-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar <i class="far fa-paper-plane"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Justificación -->
    <div class="modal fade" id="modalVerJustificacion" tabindex="-1" aria-labelledby="modalVerJustificacionLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalVerJustificacionLabel">
                        Detalle de Justificación
                        <span aria-item="ver_estatus">--</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init
                        data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Asunto -->
                    <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                        <label class="form-label me-2">Asistencia:
                            <span style="font-size: .75rem;" aria-item="ver_fecha_asistencia">...</span>
                        </label>
                        <span aria-item="ver_tipo_asistencia">...</span>
                    </div>

                    <!-- Contenido HTML -->
                    <div class="border rounded ql-editor-html">
                        <h4 class="p-3" aria-item="ver_asunto">...</h4>
                        <div aria-item="ver_contenido_html"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('scripts')
    <!-- Librería Browser Image Compression -->
    <script src="{{ secure_asset('front/js/app/MediaViewerControl.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/vendor/compression/compressor.min.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/vendor/quill/quill.min.js') }}?v=1.0.0"></script>
    <script src="{{ secure_asset('front/js/app/QuillControl.js') }}?v=1.0.1"></script>
    <script src="{{ secure_asset('front/js/misasistencias/misasistencias.js') }}?v=1.0.0"></script>
@endsection
