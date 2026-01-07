@extends('layout.app')
@section('title', 'Asistencias del personal')

@section('cabecera')
    <link rel="stylesheet" href="{{ secure_asset($ft_css->quill_show) }}">
    <link rel="stylesheet" type="text/css" href="{{ secure_asset($ft_css->daterangepicker) }}">

    <script type="text/javascript" src="{{ secure_asset($ft_js->daterangepicker_moment) }}"></script>
    <script type="text/javascript" src="{{ secure_asset($ft_js->daterangepicker) }}"></script>

    <script src="{{ secure_asset($ft_js->bootstrap_bundle) }}"></script>
    <script src="{{ secure_asset($ft_js->bootstrap_multiselect) }}"></script>
    <script src="{{ secure_asset($ft_js->form_multiselect) }}"></script>

    <script src="{{ secure_asset($ft_js->echarts) }}"></script>
    <script src="{{ secure_asset($ft_js->ChartMananger) }}"></script>
    <script>
        const tipoAsistencia = @json($tipoAsistencia);
        const tipoPersonal = @json($tipoPersonal);
        const tipoModalidad = @json($tipoModalidad);
        const tipoAreas = @json($areas);
    </script>
    <style>
    </style>
@endsection

@section('content')
    <!-- Cards resumen -->
    @include('asistencias.partials.estados_card')

    {{-- <div class="card my-3">
        <div class="card-body p-3">
            <div class="d-flex align-items-center justify-content-between my-1">
                <div class="fw-bold mb-0" style="overflow: hidden;font-size: 2.5vw;">
                    <span>ALBORNOZ MEZA, KATHERINE ANDREA</span>
                </div>
                <div class="btn-group dropdown shadow-0">
                    <button type="button" class="dropdown-toggle btn btn-tertiary hover-btn btn-sm p-1 shadow-0"
                        data-mdb-ripple-init="" aria-expanded="false" data-mdb-dropdown-init="" data-mdb-ripple-color="dark"
                        data-mdb-parent=".dataTables_scrollBody" data-mdb-dropdown-animation="off"
                        data-mdb-dropdown-initialized="true">
                        <i class="fas fa-bars" style="font-size: 1.125em;"></i>
                    </button>
                    <div class="dropdown-menu">
                        <h6 class="dropdown-header text-secondary d-flex justify-content-between align-items-center"><label
                                class="badge" style="background-color: #dc3545">Falta</label> <i class="fas fa-gear"></i>
                        </h6><button class="dropdown-item py-2 " onclick="modificarDescuento(19510)"><i
                                class="fas fa-file-invoice-dollar me-2 text-secondary"></i> Aplicar
                            Descuento</button><button class="dropdown-item py-2 " onclick="justificarAsistencia(19510)"><i
                                class="fas fa-scale-balanced me-2" style="color: #dc3545;"></i>Justificar Falta</button>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center my-2">
                <label class="badge" style="font-size: 0.75rem;background-color: #54b4d3;">Sistemas</label>
                <span>
                    <label class="badge" style="font-size: 0.75rem; background-color: #28a745;"><i
                            class="fas fa-house-laptop fa-1x me-1"></i>Remoto</label>
                    <label class="badge" style="font-size: 0.75rem; background-color: #dc3545;">Falta</label>
                    <label class="ms-auto"><span style="font-size: 2.5vw;"> S/ -50.00</span></label>
                </span>
            </div>
            <div class="text-center">
                <label>
                    <i class="far fa-clock"></i>
                    <span style="font-size: 2.5vw;">08:25:08 - 18:05:00</span>
                </label>
            </div>
        </div>
    </div> --}}

    <!-- Tabla -->
    <div class="card">
        <div class="card-body px-0">
            <div class="mx-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold">📅 Panel de Asistencias Diarias</h6>
                    <span id="feriado-text"></span>
                </div>
                <div class="row mb-2">
                    <div class="col-md-7 my-1">
                        <label class="form-label-filter" for="empresas">Empresa</label>
                        <select id="empresas" name="empresas" class="select-clear">
                            <option value="">-- Seleccione --</option>
                            @foreach ($empresas as $v)
                                <option value="{{ $v->ruc }}">
                                    {{ $v->ruc }} - {{ $v->razon_social }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5 col-6 my-1">
                        @if (in_array(Auth::user()->rol_system, [5, 6]))
                            <label class="form-label-filter" for="areas">Area</label>
                            <div class="form-control">{{ $areas[0]->descripcion }}</div>
                            <input type="hidden" id="areas" name="areas" value="{{ $areas[0]->id }}">
                        @else
                            <label class="form-label-filter" for="areas">Areas</label>
                            <select id="areas" name="areas" multiple="multiple" class="multiselect-select-all">
                                @foreach ($areas as $v)
                                    <option
                                        {{ in_array(Auth::user()->rol_system, [2, 4]) ? 'selected' : (Auth::user()->area_id == $v->id ? 'selected' : '') }}
                                        value="{{ $v->id }}">
                                        {{ $v->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                    <div class="col-md-4 col-6 my-1">
                        <label class="form-label-filter" for="tipoModalidad">Modalidad</label>
                        <select id="tipoModalidad" name="tipoModalidad" multiple="multiple" class="multiselect-select-all">
                            @foreach ($tipoModalidad as $v)
                                <option selected value="{{ $v->id }}">
                                    {{ $v->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-6 my-1">
                        <label class="form-label-filter" for="tipoPersonal">Tipo Personal</label>
                        <select id="tipoPersonal" name="tipoPersonal" multiple="multiple" class="multiselect-select-all">
                            @foreach ($tipoPersonal as $v)
                                <option selected value="{{ $v->id }}">
                                    {{ $v->descripcion }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 col-6 my-1">
                        <label class="form-label-filter" for="fecha">Fecha</label>
                        <div class="input-group">
                            <button class="btn btn-primary px-2" type="button" id="btn-fecha-left" data-mdb-ripple-init>
                                <i class="fas fa-angle-left"></i>
                            </button>
                            <input type="date" id="filtro_fecha" class="form-control" value="{{ date('Y-m-d') }}">
                            <button class="btn btn-primary px-2" type="button" id="btn-fecha-right" data-mdb-ripple-init>
                                <i class="fas fa-angle-right"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-12 my-1 text-end">
                        <button class="btn btn-primary" onclick="filtroBusqueda()" data-mdb-ripple-init>Filtrar</button>
                    </div>
                    <div class="col-md-4 col-6 my-1">
                        <label class="form-label-filter" for="">Fecha</label>
                        <input type="date" class="form-control">
                        <input type="time" class="form-control" value="12:21:21">
                        <input type="datetime" class="form-control">
                    </div>
                </div>
            </div>

            <div id="cardsAsistencias" style="display: none;"></div>

            <table id="tablaAsistencias" class="table align-center mb-0 table-hover text-nowrap w-100"
                style="display: none;">
                <thead>
                    <tr class="text-bg-primary text-center">
                        <th>Personal</th>
                        <th>Area</th>
                        <th>Tipo Personal</th>
                        <th>Modalidad</th>
                        <th>Estado</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Descuento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <script>
        let tablaAsistencias;
        let getUrlListar = () => generateUrl(`${__url}/asistencias-diarias/listar`, {
            fecha: $('#filtro_fecha').val(),
            empresas: $('#empresas').val(),
            tipoModalidad: $('#tipoModalidad').val(),
            tipoPersonal: $('#tipoPersonal').val(),
            tipoArea: $('#areas').val()
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
            $('#cardsAsistencias').removeAttr('style');
            tablaAsistencias = new CardTable('cardsAsistencias', {
                ajax: {
                    url: getUrlListar(),
                    dataSrc: dataSet,
                    error: function(xhr, error, thrown) {
                        boxAlert.table();
                        console.log('Respuesta del servidor:', xhr);
                    }
                },
                columns: [{
                        data: 'personal',
                        title: 'Nombre'
                    },
                    {
                        data: 'area',
                        title: 'Área'
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
                                <span>${data.personal}</span>
                            </div>
                            <div class="btn-acciones-movil">${data.acciones}</div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                                ${getBadgeAreas(data.area, '.95', false)}
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
                perPage: 100,
                searchPlaceholder: 'Buscar por nombre...',
                order: ['personal', 'asc'],
                drawCallback: function() {
                    if (typeof mdb !== 'undefined') {
                        document.querySelectorAll('[data-mdb-dropdown-init]').forEach(el => {
                            new mdb.Dropdown(el);
                        });
                    }
                }
            });
        } else {
            $('#tablaAsistencias').removeAttr('style');
            tablaAsistencias = new DataTable('#tablaAsistencias', {
                lengthChange: false,
                paging: false,
                scrollX: true,
                scrollY: 400,
                dom: `<"row"
                    <"col-lg-12 mb-2"B>>
                    <"row"
                        <"col-sm-6 text-sm-start text-center my-1 botones-accion">
                        <"col-sm-6 text-sm-end text-center my-1"f>>
                    <"contenedor_tabla my-2"tr>
                    <"row"
                        <"col-md-5 text-md-start text-center my-1"i>
                        <"col-md-7 text-md-end text-center my-1"p>>`,
                ajax: {
                    url: getUrlListar(),
                    dataSrc: dataSet,
                    error: function(xhr, error, thrown) {
                        boxAlert.table();
                        console.log('Respuesta del servidor:', xhr);
                    }
                },
                columns: [{
                        data: 'personal'
                    },
                    {
                        data: 'area',
                        render: function(data, type, row) {
                            return getBadgeAreas(data);
                        }
                    },
                    {
                        data: 'tipo_personal',
                        render: function(data, type, row) {
                            return getBadgeTipoPersonal(data);
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
                    if (data.justificado == 0) {
                        $(row).attr({
                            'title': 'Tiene una Justificacion pendiente.'
                        });
                    }
                    $(row).addClass('text-center');
                    $(row).find('td:eq(0)').addClass('text-start');
                    $(row).find('td:eq(8)').addClass(`td-acciones`);
                },
                order: [
                    [0, 'asc']
                ],
                processing: true
            });

            mostrar_acciones(tablaAsistencias);
        }

        function updateTable() {
            if (esCelular()) {
                return tablaAsistencias.reload();
            }
            tablaAsistencias.ajax.reload();
        }

        function filtroBusqueda() {
            const nuevoUrl = getUrlListar();
            tablaAsistencias.ajax.url(nuevoUrl).load();
            if (!esCelular()) {
                tablaAsistencias.column([4]).search('').draw();
            }
        }

        function searchTable(search) {
            if (esCelular()) {
                tablaAsistencias.search('tipo_asistencia', search == 0 ? '' : search.toString()).draw();
            } else {
                let tasistencia = tipoAsistencia.find(s => s.id == search)?.descripcion || '';
                tablaAsistencias.column([4]).search(tasistencia).draw();
            }

            const contenedor = document.querySelector('.content-wrapper');
            contenedor.scrollTo({
                top: contenedor.scrollHeight,
                behavior: 'smooth'
            });
        }
    </script>

    <!-- Modal Descuento -->
    <button class="d-none" data-mdb-modal-init data-mdb-target="#modalDescuento"></button>
    <div class="modal fade" id="modalDescuento" tabindex="-1" aria-labelledby="modalDescuentoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="form-descuento">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title" id="modalDescuentoLabel">Aplicar / Modificar Descuento</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Información del personal -->
                    <div class="col-12">
                        <div class="list-group list-group-light">
                            <div class="list-group-item">
                                <p aria-item="personal" class="fw-semibold mb-2" style="font-size: .92rem;">...</p>
                            </div>
                            <div class="list-group-item">
                                <label class="form-label me-2">Fecha Asistencia:</label>
                                <span style="font-size: .75rem;" aria-item="fecha">...</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center my-2">
                        <h6 class="fw-semibold text-primary text-uppercase m-0" style="font-size: smaller;">Ingresar
                            Descuento</h6>
                        <span aria-item="estado">...</span>
                    </div>

                    <input type="hidden" id="user_id" name="user_id">
                    <input type="hidden" id="fecha" name="fecha">

                    <!-- Monto del descuento -->
                    <div class="mb-3">
                        <input type="number" step="0.01" min="0" class="form-control" id="monto_descuento"
                            name="monto_descuento" placeholder="Monto del descuento">
                    </div>

                    <!-- Comentario -->
                    <div class="mb-3">
                        <textarea class="form-control" id="comentario" name="comentario" rows="4"
                            placeholder="Comentario (opcional)..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-ripple-init
                        data-mdb-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" data-mdb-ripple-init>Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Justificación -->
    <button class="d-none" data-mdb-modal-init data-mdb-target="#modalJustificacion"></button>
    <div class="modal fade" id="modalJustificacion" tabindex="-1" aria-labelledby="modalJustificacionLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content" id="form-justificacion">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title" id="modalJustificacionLabel">
                        Justificación
                        <span aria-item="estado">--</span>
                    </h6>
                    <button type="button" class="btn-close text-white" data-mdb-dismiss="modal"
                        aria-label="Cerrar"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-1">
                        <span style="font-size: .85rem;" aria-item="personal">...</span>
                    </div>

                    <!-- Asunto -->
                    <div class="d-flex justify-content-between align-items-center mt-2 mb-2">
                        <label class="form-label me-2">Asistencia:
                            <span style="font-size: .75rem;" aria-item="fecha">...</span>
                        </label>
                        <span aria-item="tipo_asistencia">...</span>
                    </div>

                    <!-- Contenido HTML -->
                    <div class="border rounded ql-editor-html">
                        <h4 class="p-3" aria-item="asunto">Retraso por tráfico</h4>
                        <div aria-item="contenido_html"></div>
                    </div>

                    <div class="my-2" id="responderJustificacion">
                        <label class="form-label mb-1"><i class="fas fa-reply me-2"></i>Responder</label>
                        <div id="respuesta-justificacion" class="mb-2"></div>
                        <div class="text-end">
                            <button class="btn btn-sm btn-success me-2" id="btnAprobar"><i class="fas fa-check"></i>
                                Aprobar</button>
                            <button class="btn btn-sm btn-danger" id="btnRechazar"><i class="fas fa-times"></i>
                                Rechazar</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Justificación -->
    <div class="modal fade" id="modalJustificar" tabindex="-1" aria-labelledby="modalJustificarLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <form id="formJustificar" class="modal-content">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalJustificarLabel">Justificar asistencia</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init
                        data-mdb-dismiss="modal" aria-label="Close"></button>
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

    <!-- Modal -->
    <div class="modal fade" id="modalExport" tabindex="-1" aria-labelledby="modalExportLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <!-- CABECERA -->
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title" id="modalExportLabel">EXPORTAR MENSUAL</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init
                        data-mdb-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- CUERPO -->
                <div class="modal-body">
                    <div class="row text-center">
                        <!-- INPUT AREAS -->
                        <div class="col-12 py-2 text-start">
                            <label class="form-label-filter" for="tipoArea">Areas</label>
                            <select id="tipoArea" name="tipoArea" multiple="multiple" class="multiselect-select-all">
                                @foreach ($areas as $v)
                                    <option
                                        {{ in_array(Auth::user()->rol_system, [2, 4]) ? 'selected' : (Auth::user()->area_id == $v->id ? 'selected' : '') }}
                                        value="{{ $v->id }}">
                                        {{ $v->descripcion }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- BOTONES DE MODO -->
                        <div class="col-12 py-1">
                            <div class="btn-group" role="group">
                                <button id="btnMensual" class="btn btn-outline-primary active"
                                    data-mdb-ripple-init>Mensual</button>
                                <button id="btnRango" class="btn btn-outline-primary" data-mdb-ripple-init>Rango</button>
                            </div>
                        </div>

                        <!-- INPUT FECHA -->
                        <div class="col-12 py-2">
                            <input type="month" class="form-control text-center" id="fechaExport" name="fechaExport"
                                role="button" value="<?= date('Y-m') ?>" max="<?= date('Y-m') ?>">
                        </div>

                        <!-- EXPORTAR -->
                        <div class="col-12 py-1">
                            <button class="btn btn-primary w-100" id="btnExportar">Exportar</button>
                        </div>
                    </div>
                </div>

                <!-- PIE -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ secure_asset($ft_js->MediaViewerControl) }}"></script>
    <script src="{{ secure_asset($ft_js->compressor) }}"></script>
    <script src="{{ secure_asset($ft_js->quill) }}"></script>
    <script src="{{ secure_asset($ft_js->QuillControl) }}"></script>

    <script src="{{ secure_asset('front/js/asistencias/asistencias.js') }}?v={{ env('APP_VERSION') }}"></script>
    @if (!in_array(Auth::user()->rol_system, [1, 5, 6]) || $tipo_sistema)
        <script src="{{ secure_asset($ft_js->exceljs) }}"></script>
        <script src="{{ secure_asset($ft_js->FileSaver) }}"></script>
        <script src="{{ secure_asset('front/js/asistencias/export-excel-asistencias.js') }}?v={{ env('APP_VERSION') }}"></script>
    @endif
    <script src="{{ secure_asset('front/js/asistencias/asistencias-justificaciones.js') }}?v={{ env('APP_VERSION') }}"></script>
@endsection
