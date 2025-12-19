@extends('layout.app')
@section('title', 'Tipo Asistencia')

@section('cabecera')
    <script src="{{ secure_asset('front/vendor/multiselect/bootstrap.bundle.min.js') }}?v=6.3.3.5"></script>
    <script src="{{ secure_asset('front/vendor/multiselect/bootstrap_multiselect.js') }}?v=6.3.3.5"></script>
    <script src="{{ secure_asset('front/vendor/multiselect/form_multiselect.js') }}?v=6.3.3.5"></script>
    <script>
        const tipoModalidad = @json($tipoModalidad);
    </script>
@endsection
@section('content')


    <div class="col-12">
        <div class="card">
            <div class="card-body px-3">
                <h6 class="card-title col-form-label-sm text-primary mb-3">
                    <strong>Listado de Tipo Asistencias</strong>
                </h6>
                <div>
                    <button class="btn btn-primary" data-mdb-ripple-init data-mdb-modal-init
                        data-mdb-target="#modal_tipo_asistencia">
                        <i class="fas fa-plus"></i>
                        Nueva Asistencia
                    </button>
                    <button class="btn btn-primary px-2" onclick="updateTable()" data-mdb-ripple-init role="button">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-12">
                        <table id="tb_tipo_asistencia" class="table table-hover text-nowrap" style="width:100%">
                            <thead>
                                <tr class="text-bg-primary text-center">
                                    <th>#</th>
                                    <th>Simbolo</th>
                                    <th>Descripcion</th>
                                    <th>Color</th>
                                    <th>Registrado</th>
                                    <th>Actualizado</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                        <script>
                            const tb_tipo_asistencia = new DataTable('#tb_tipo_asistencia', {
                                autoWidth: true,
                                scrollX: true,
                                scrollY: 400,
                                fixedHeader: true, // Para fijar el encabezado al hacer scroll vertical
                                ajax: {
                                    url: `${__url}/mantenimiento-dev/tipo-asistencia/listar`,
                                    dataSrc: function(json) {
                                        return json?.data;
                                    },
                                    error: function(xhr, error, thrown) {
                                        boxAlert.table();
                                        console.log('Respuesta del servidor:', xhr);
                                    }
                                },
                                columns: [{
                                        data: 'id'
                                    },
                                    {
                                        data: 'simbolo'
                                    },
                                    {
                                        data: 'descripcion'
                                    },
                                    {
                                        data: 'color',
                                        render: function(data, type, rows) {
                                            return `<span style="color: ${data};">${data}</span>`
                                        }
                                    },
                                    {
                                        data: 'created_at'
                                    },
                                    {
                                        data: 'updated_at'
                                    },
                                    {
                                        data: 'estado'
                                    },
                                    {
                                        data: 'acciones'
                                    }
                                ],
                                createdRow: function(row, data, dataIndex) {
                                    $(row).find('td:eq(6)').addClass(`td-acciones`);
                                    $(row).addClass('text-center');
                                },
                                // order: [["3", "asc"]],
                                processing: true
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modal_tipo_asistencia" tabindex="-1" aria-labelledby="modal_tipo_asistenciaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="form-tipo-asistencia">
                <div class="modal-header  bg-primary text-white">
                    <h6 class="modal-title" id="modal_tipo_asistenciaLabel">REGISTRAR TIPO ASISTENCIA</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="id" id="id">
                        <div class="col-6 mb-2">
                            <input class="form-control" id="simbolo">
                        </div>
                        <div class="col-6 mb-2">
                            <input class="form-control" id="descripcion">
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <input class="form-control" id="color">
                        </div>
                        <div class="col-6 col-md-3 mb-2">
                            <select class="select" id="estado">
                                <option selected value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>
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

@endsection

@section('scripts')
    <script></script>
    <script src="{{ secure_asset('front/js/mantenimiento_dev/tipoasistencia/tipoasistencia.js') }}?v=6.3.3.5"></script>
@endsection
