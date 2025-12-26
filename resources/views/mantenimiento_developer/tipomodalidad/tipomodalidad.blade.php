@extends('layout.app')
@section('title', 'Tipo Modalidad')

@section('cabecera')
    <style>
    </style>
@endsection
@section('content')


    <div class="col-12">
        <div class="card">
            <div class="card-body px-3">
                <h6 class="card-title col-form-label-sm text-primary mb-3">
                    <strong>Listado de Tipo Modalidades</strong>
                </h6>
                <div>
                    <button class="btn btn-primary" data-mdb-ripple-init data-mdb-modal-init
                        data-mdb-target="#modal_tipo_modalidad">
                        <i class="fas fa-plus"></i>
                        Nueva Modalidad
                    </button>
                    <button class="btn btn-primary px-2" onclick="updateTable()" data-mdb-ripple-init role="button">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-12">
                        <table id="tb_tipo_modalidad" class="table table-hover text-nowrap" style="width:100%">
                            <thead>
                                <tr class="text-bg-primary text-center">
                                    <th>#</th>
                                    <th>Descripcion</th>
                                    <th>Icono</th>
                                    <th>Color</th>
                                    <th>Registrado</th>
                                    <th>Actualizado</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                        <script>
                            const tb_tipo_modalidad = new DataTable('#tb_tipo_modalidad', {
                                autoWidth: true,
                                scrollX: true,
                                scrollY: 400,
                                fixedHeader: true, // Para fijar el encabezado al hacer scroll vertical
                                ajax: {
                                    url: `${__url}/mantenimiento-dev/tipo-modalidad/listar`,
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
                                        data: 'descripcion'
                                    },
                                    {
                                        data: 'icono',
                                        render: function(data, type, rows) {
                                            return `<i class="${data}"></i> ${data}`
                                        }
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
                                    $(row).addClass('text-center');
                                    $(row).find('td:eq(7)').addClass(`td-acciones`);
                                },
                                processing: true
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modal_tipo_modalidad" tabindex="-1" aria-labelledby="modal_tipo_modalidadLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="form-tipo-modalidad">
                <div class="modal-header  bg-primary text-white">
                    <h6 class="modal-title" id="modal_tipo_modalidadLabel">REGISTRAR TIPO MODALIDAD</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="id" id="id">
                        <div class="col-6 mb-2">
                            <input class="form-control" id="descripcion">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label mb-0" for="icono">Icono</label>
                            <div class="input-group">
                                <span class="input-group-text rounded me-1 px-2"><i class="fas fa-question"
                                        aria-label="icono"></i></span>
                                <input type="text" class="form-control rounded" id="icono" name="icono"
                                    requested="Icono">
                            </div>
                        </div>
                        <div class="col-6 mb-2">
                            <input class="form-control" id="color">
                        </div>
                        <div class="col-6 mb-2">
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
    <script src="{{ secure_asset('front/js/mantenimiento_dev/tipomodalidad/tipomodalidad.js') }}?v={{ env('APP_VERSION') }}"></script>
@endsection
