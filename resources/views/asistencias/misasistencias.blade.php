@extends('layout.app')
@section('title', 'Mis asistencias')

@section('cabecera')
    <link rel="stylesheet" href="{{ secure_asset($ft_css->quill_show) }}">

    <link rel="stylesheet" href="{{ secure_asset($ft_css->mdtp) }}">
    <script src="{{ secure_asset($ft_js->mdtp) }}"></script>

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
        <div class="card-body">
            <h6 class="fw-bold">Mis Asistencias Diarias</h6>
            <div class="row mb-2">
                <div class="col-md-4 my-1">
                    <small class="form-label mb-0" for="fecha">Fecha</small>
                    <div class="input-group">
                        <button class="btn btn-primary px-2" type="button" id="btn-fecha-left" data-mdb-ripple-init>
                            <i class="fas fa-angle-left"></i>
                        </button>
                        <input type="text" id="filtro_fecha" class="form-control text-center">
                        <button class="btn btn-primary px-2" type="button" id="btn-fecha-right" data-mdb-ripple-init>
                            <i class="fas fa-angle-right"></i>
                        </button>
                    </div>
                    <script>
                        const filtro_fecha = new MaterialDateTimePicker({
                            inputId: 'filtro_fecha',
                            mode: 'month',
                            format: 'MMMM de YYYY'
                        });
                        filtro_fecha.val("{{ date('Y-m-d') }}");
                    </script>
                </div>
                <div class="col-md-1 my-1 text-end mt-auto"><button class="btn btn-primary" onclick="filtroBusqueda()" data-mdb-ripple-init>Filtrar</button></div>
            </div>

            <div id="vista-escritorio" style="display: none;">
                <table id="lista_mis_asistencias" class="table table-hover text-nowrap w-100">
                    <thead>
                        <tr class="text-center">
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
            </div>
        </div>
    </div>

    <div id="vista-movil" style="display: none;"></div>

    <script
        src="{{ secure_asset('front/js/misasistencias/listado-misasistencias.js') }}?v={{ config('app.version') }}"></script>

    <button class="d-none" data-mdb-modal-init data-mdb-target="#modalJustificarDerivado"></button>
    <!-- Modal de Justificación -->
    <div class="modal fade" id="modalJustificarDerivado" tabindex="-1" aria-labelledby="modalJustificarDerivadoLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
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
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-md-down">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalVerJustificacionLabel">
                        Detalle de Justificación
                        <span aria-item="ver_estatus">--</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
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
    <script src="{{ secure_asset('front/js/misasistencias/misasistencias.js') }}?v={{ config('app.version') }}"></script>
@endsection