@extends('layout.app')
@section('title', 'Mis asistencias')

@section('cabecera')
    <link rel="stylesheet" href="{{ secure_asset($ft_css->quill_show) }}">
    <script src="{{ secure_asset($ft_js->bootstrap_bundle) }}"></script>
    <script src="{{ secure_asset($ft_js->bootstrap_multiselect) }}"></script>
    <script src="{{ secure_asset($ft_js->form_multiselect) }}"></script>
    <script src="{{ secure_asset($ft_js->echarts) }}"></script>
    <script src="{{ secure_asset($ft_js->ChartMananger) }}"></script>
    <script>
        const tipoModalidad = @json($tipoModalidad);
        const tipoAsistencia = @json($tipoAsistencia);
    </script>
    <style>
    </style>
@endsection

@section('content')
    <!-- Cards resumen -->
    @include('asistencias.partials.estados_card')

    <!-- Tabla -->
    <div class="card">
        <div class="card-body px-0">
            <div class="mx-3">
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
            </div>

            <div id="cardsMisAsistencias" style="display: none;"></div>

            <table id="tablaMisAsistencias" class="table table-hover text-nowrap w-100" style="display: none;">
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
                let tablaMisAsistencias;
                let getUrlListar = () => generateUrl(`${__url}/asistencias/listar`, {
                    fecha: $('#filtro_fecha').val()
                });
                let dataSet = (json) => {
                    let feriado = json.data?.feriado || {};
                    $('#feriado-text').html(Object.keys(feriado).length ?
                        `<b>${feriado.tipo}:</b> ${feriado.nombre}` : '');

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
                            name: "estado-derivados",
                            value: lista.filter(a => a.tipo_asistencia === 7).length
                        },
                    ];
                    setEstados(estadosAsistencias, lista.length);
                    return lista;
                }

                if (esCelular()) {
                    $('#cardsMisAsistencias').removeAttr('style');
                    tablaMisAsistencias = new CardTable('cardsMisAsistencias', {
                        ajax: {
                            url: getUrlListar(),
                            dataSrc: dataSet,
                            error: function(xhr, error, thrown) {
                                boxAlert.table();
                                console.log('Respuesta del servidor:', xhr);
                            }
                        },
                        columns: [{
                                data: 'jornada',
                                title: 'Jornada'
                            },
                            {
                                data: 'fecha',
                                title: 'Fecha'
                            },
                            {
                                data: 'tipo_modalidad',
                                title: 'Modalidad'
                            },
                            {
                                data: 'tipo_asistencia',
                                title: 'Estado'
                            },
                            {
                                data: 'entrada',
                                title: 'Entrada'
                            },
                            {
                                data: 'salida',
                                title: 'Salida'
                            }
                        ],
                        cardTemplate: (data, index) => {
                            return `
                                <div class="d-flex align-items-center justify-content-between pb-1">
                                    <div class="fw-medium mb-0" style="overflow: hidden;font-size: 3.25vw;">
                                        <span>${obtenerFechaFormateada(new Date(data.fecha + ' 00:00:00'))}</span>
                                    </div>
                                    <div class="btn-acciones-movil">${data.acciones}</div>
                                </div>
                                <div class="d-flex justify-content-start align-items-center">
                                    <span>
                                        ${getBadgeTipoModalidad(data.tipo_modalidad, '.85')}
                                        ${getBadgeTipoAsistencia(data.tipo_asistencia, '.85')}
                                    </span>
                                </div>
                                <hr class="mx-1 my-2">
                                <div class="d-flex align-items-center justify-content-between pt-1" style="font-size: 2.85vw;color: #909090;">
                                    ${getFormatJornada(data)}
                                    ${getBadgeDescuento(data)}
                                </div>`;
                        },
                        scrollY: '600px',
                        perPage: 40,
                        searchPlaceholder: 'Buscar por nombre...',
                        order: ['fecha', 'desc'],
                        drawCallback: function() {
                            if (typeof mdb !== 'undefined') {
                                document.querySelectorAll('[data-mdb-dropdown-init]').forEach(el => {
                                    new mdb.Dropdown(el);
                                });
                            }
                        }
                    });
                } else {
                    $('#tablaMisAsistencias').removeAttr('style');
                    tablaMisAsistencias = new DataTable('#tablaMisAsistencias', {
                        scrollX: true,
                        scrollY: 400,
                        ajax: {
                            url: getUrlListar(),
                            dataSrc: dataSet
                        },
                        columns: [{
                                data: 'jornada',
                                render: function(data, type, row) {
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
                                    return getBadgeTipoModalidad(data);
                                }
                            },
                            {
                                data: 'tipo_asistencia',
                                render: function(data, type, row) {
                                    return getBadgeTipoAsistencia(data);
                                }
                            },
                            {
                                data: 'descuento',
                                render: function(data, type, row) {
                                    return getBadgeDescuento(row);
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
                    mostrar_acciones(tablaMisAsistencias);
                }

                function updateTable() {
                    if (esCelular()) {
                        return tablaMisAsistencias.reload();
                    }
                    tablaMisAsistencias.ajax.reload();
                }

                function filtroBusqueda() {
                    var nuevoUrl = getUrlListar();
                    tablaMisAsistencias.ajax.url(nuevoUrl).load();

                    if (!esCelular()) {
                        tablaMisAsistencias.column([4]).search('').draw();
                    }
                }

                function searchTable(search) {
                    if (esCelular()) {
                        tablaMisAsistencias.search('tipo_asistencia', search == 0 ? '' : search.toString()).draw();
                    } else {
                        let tasistencia = tipoAsistencia.find(s => s.id == search)?.descripcion || '';
                        tablaMisAsistencias.column([4]).search(tasistencia).draw();
                    }

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
    <script src="{{ secure_asset($ft_js->MediaViewerControl) }}"></script>
    <script src="{{ secure_asset($ft_js->compressor) }}"></script>
    <script src="{{ secure_asset($ft_js->quill) }}"></script>
    <script src="{{ secure_asset($ft_js->QuillControl) }}"></script>
    <script src="{{ secure_asset('front/js/misasistencias/misasistencias.js') }}?v=6.3.3.5"></script>
@endsection
