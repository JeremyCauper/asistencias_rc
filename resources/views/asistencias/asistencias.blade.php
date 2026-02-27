@extends('layout.app')
@section('title', 'Asistencias del personal')

@section('cabecera')
    <link rel="stylesheet" href="{{ secure_asset($ft_css->quill_show) }}">
    <link rel="stylesheet" type="text/css" href="{{ secure_asset($ft_css->daterangepicker) }}">

    <link rel="stylesheet" href="{{ secure_asset($ft_css->mdtp) }}">
    <script src="{{ secure_asset($ft_js->mdtp) }}"></script>

    <script type="text/javascript" src="{{ secure_asset($ft_js->daterangepicker_moment) }}"></script>
    <script type="text/javascript" src="{{ secure_asset($ft_js->daterangepicker) }}"></script>

    <link rel="stylesheet" href="{{ secure_asset($ft_css->bootstrap_multiselect) }}">
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

    <!-- Tabla -->
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="fw-bold">Panel de Asistencias Diarias</h6>
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
                                <option {{ in_array(Auth::user()->rol_system, [2, 4]) ? 'selected' : (Auth::user()->area_id == $v->id ? 'selected' : '') }} value="{{ $v->id }}">
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
                        <input type="text" id="filtro_fecha" class="form-control" readonly role="button">
                        <button class="btn btn-primary px-2" type="button" id="btn-fecha-right" data-mdb-ripple-init>
                            <i class="fas fa-angle-right"></i>
                        </button>
                    </div>
                    <script>
                        const filtro_fecha = new MaterialDateTimePicker({
                            inputId: 'filtro_fecha',
                            mode: 'date',
                            format: 'MMMM DD de YYYY'
                        });
                        filtro_fecha.val("{{ date('Y-m-d') }}");
                    </script>
                </div>
                <div class="col-12 my-1 text-end">
                    <button class="btn btn-primary" onclick="filtroBusqueda()" data-mdb-ripple-init>Filtrar</button>
                </div>
            </div>

            <div id="vista-escritorio" style="display: none;">
                <table id="lista_asistencias" class="table align-center mb-0 table-hover text-nowrap w-100">
                    <thead>
                        <tr class="text-center">
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
    </div>

    <div id="vista-movil" style="display: none;"></div>

    <script src="{{ secure_asset('front/js/asistencias/listado-asistencias.js') }}?v={{ config('app.version') }}"></script>

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
                    <button type="button" class="btn btn-link" data-mdb-ripple-init data-mdb-dismiss="modal">Cerrar</button>
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
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <!-- CUERPO -->
                <div class="modal-body">
                    <div class="row text-center">
                        <!-- INPUT AREAS -->
                        <div class="col-12 py-2 text-start">
                            <label class="form-label-filter" for="tipoArea">Areas</label>
                            <select id="tipoArea" name="tipoArea" multiple="multiple" class="multiselect-select-all">
                                @foreach ($areas as $v)
                                    <option {{ in_array(Auth::user()->rol_system, [2, 4]) ? 'selected' : (Auth::user()->area_id == $v->id ? 'selected' : '') }} value="{{ $v->id }}">
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
                            <input type="text" class="form-control text-center" id="fechaExport" name="fechaExport"
                                role="button" readonly type="button">
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

    <script src="{{ secure_asset('front/js/asistencias/asistencias.js') }}?v={{ config('app.version') }}"></script>
    @if (!in_array(Auth::user()->rol_system, [1, 5, 6]) || $tipo_sistema)
        <script src="{{ secure_asset($ft_js->exceljs) }}"></script>
        <script src="{{ secure_asset($ft_js->FileSaver) }}"></script>
        <script
            src="{{ secure_asset('front/js/asistencias/export-excel-asistencias.js') }}?v={{ config('app.version') }}"></script>
    @endif
    <script
        src="{{ secure_asset('front/js/asistencias/asistencias-justificaciones.js') }}?v={{ config('app.version') }}"></script>
@endsection