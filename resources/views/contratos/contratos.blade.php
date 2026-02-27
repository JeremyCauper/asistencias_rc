@extends('layout.app')
@section('title', 'Control del Contratos')

@section('cabecera')
    <link rel="stylesheet" href="{{ secure_asset('front/css/app/contratos/contratos.css') }}?v={{ config('app.version') }}">

    <link rel="stylesheet" href="{{ secure_asset($ft_css->mdtp) }}">
    <script src="{{ secure_asset($ft_js->mdtp) }}"></script>

    <script>
        const empresa = @json($empresa);
        const tipoAreas = @json($areas);
    </script>
@endsection

@section('content')
    <!-- 🔹 Resumen contable -->
    <section class="row">
        <div class="col-md-3 col-6 mb-2">
            <div class="card" style="background-color: #549cea50; border: 1px solid #3b71ca20;">
                <div class="card-body px-3">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="card-icon rounded-7 text-bg-primary">
                                <i class="far fa-clipboard fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Total Personal</p>
                            <p class="fw-bold mb-0 fs-4" id="totalContratos">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-2">
            <div class="card" style="background-color: #14a44d40; border: 1px solid #14a44d20;">
                <div class="card-body px-3">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="card-icon rounded-7 text-bg-success">
                                <i class="fas fa-circle-check fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Activos</p>
                            <p class="fw-bold mb-0 fs-4" id="totalActivos">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-2">
            <div class="card" style="background-color: #e4a11b40; border: 1px solid #e4a11b20;">
                <div class="card-body px-3">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="card-icon rounded-7 text-bg-warning">
                                <i class="fas fa-triangle-exclamation fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Pronto a Expirar</p>
                            <p class="fw-bold mb-0 fs-4" id="totalProntoExpirar">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-6 mb-2">
            <div class="card" style="background-color: #dc4c6440; border: 1px solid #dc4c6420;">
                <div class="card-body px-3">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="card-icon rounded-7 text-bg-danger">
                                <i class="fas fa-xmark fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Expirados</p>
                            <p class="fw-bold mb-0 fs-4" id="totalExpirados">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 🔹 Tabla -->
    <div class="card">
        <div class="card-body">
            <h6 class="fw-bold mb-0">Listado de Contratos</h6>
            <button hidden data-mdb-modal-init data-mdb-target="#modalContratos"></button>

            <div id="vista-escritorio" style="display: none;">
                <table id="lista_contrato" class="table align-center mb-0 table-hover text-nowrap w-100">
                    <thead>
                        <tr class="text-center">
                            <th>Personal</th>
                            <th>Empresa</th>
                            <th>Area</th>
                            <th>Tipo Contrato</th>
                            <th>Dias Restantes</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Expiracion</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div id="vista-movil" style="display: none;"></div>

    <script
        src="{{ secure_asset('front/js/contratos/listado-contratos.js') }}?v={{ config('app.version') }}"></script>


    <!-- 🔹 Modal -->
    <div class="modal fade" id="modalContratos" tabindex="-1" aria-labelledby="modalContratosLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalContratosLabel">Registrar Contratos</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" id="contrato_id">
                        <input type="hidden" id="personal_id">
                        <div class="col-md-6 mb-2">
                            <label class="form-label" for="fecha_inicio">Fecha Inicio</label>
                            <input type="text" class="form-control" id="fecha_inicio" name="fecha_inicio">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label" for="fecha_final">Fecha Fin</label>
                            <input type="text" class="form-control" id="fecha_final" name="fecha_final">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label" for="tiempo_contable">Tiempo Contable</label>
                            <input type="button" class="form-control" id="tiempo_contable" readonly="readonly"
                                role="button">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label" for="tipo_contrato">Tipo Contrato</label>
                            <select class="select" id="tipo_contrato" name="tipo_contrato">
                                <option value="0" selected>Contrato</option>
                                <option value="1">Permanente</option>
                                <option value="2">Por Proyecto</option>
                            </select>
                        </div>
                        <div class="col-12 text-end mb-2">
                            <button type="button" class="btn btn-primary" id="btnGuardar">Guardar</button>
                        </div>
                    </div>

                    <div class="col-12">
                        <h6 class="fw-bold" style="font-size: small;">Historial de Contratos</h6>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover text-center align-middle text-nowrap">
                                <thead class="border-bottom text-primary-emphasis">
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Estado</th>
                                        <th>Registro</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaHistorialContratos">
                                    <!-- JS will populate this -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </div>
    </div>


    <!-- 🔹 Scripts -->
    <script src="{{ secure_asset($ft_js->jquery_inputmask_bundle) }}"></script>
    <script src="{{ secure_asset('front/js/contratos/contratos.js') }}?v={{ config('app.version') }}"></script>
@endsection