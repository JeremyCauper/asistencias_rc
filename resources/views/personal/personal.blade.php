@extends('layout.app')
@section('title', 'Control del Personal')

@section('cabecera')
    <link rel="stylesheet" href="{{ secure_asset('front/css/app/personal/personal.css') }}?v=1">
    <script>
        const empresa = @json($empresa);
        const areas = @json($areas);
        const tipoModalidad = @json($tipoModalidad);
        const tipoPersonal = @json($tipoPersonal);
    </script>
    <script src="{{ secure_asset($ft_js->full_calendar) }}"></script>
@endsection

@section('content')
    <!-- 🔹 Resumen contable -->
    <section class="card mb-3">
        <div class="card-body py-lg-2 px-md-3">
            <div class="row gx-lg-5">
                <div class="col-md-3 col-6 my-2 py-2">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="p-3 rounded-4" style="background-color: #e2eaf7">
                                <i class="fa-solid fa-users text-primary fa-fw"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <p class="fw-bold mb-1">Total</p>
                            <p class="text-muted mb-0 fs-4" id="totalSync">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6 my-2 py-2">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="p-3 rounded-4" style="background-color: #e2eaf7">
                                <i class="fa-solid fa-cloud-arrow-up text-primary fa-fw"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <p class="fw-bold mb-1">Sincronizando</p>
                            <p class="text-muted mb-0 fs-4" id="totalCreando">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6 my-2 py-2">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="p-3 rounded-4" style="background-color: #e2eaf7">
                                <i class="fa-solid fa-pen-to-square text-primary fa-fw"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <p class="fw-bold mb-1">Modificando</p>
                            <p class="text-muted mb-0 fs-4" id="totalModificando">0</p>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 col-6 my-2 py-2">
                    <div class="d-flex align-items-start">
                        <div class="flex-shrink-0">
                            <div class="p-3 rounded-4" style="background-color: #e2eaf7">
                                <i class="fa-solid fa-exclamation-triangle text-primary fa-fw"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <p class="fw-bold mb-1">Eliminando</p>
                            <p class="text-muted mb-0 fs-4" id="totalEliminando">0</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- 🔹 Tabla -->
    <div class="card">
        <div class="card-body px-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h6 class="fw-bold">Listado de Personal</h6>
                </div>

                <div class="d-flex align-items-center">
                    <button class="btn btn-primary me-2" data-mdb-ripple-init data-mdb-modal-init
                        data-mdb-target="#modalPersonal">
                        <i class="fa fa-plus me-2"></i>Nuevo Personal
                    </button>
                    <button class="btn btn-primary px-3" onclick="updateTable()" data-mdb-ripple-init role="button">
                        <i class="fas fa-rotate-right"></i>
                    </button>
                </div>
            </div>
            <table id="tablaPersonal" class="table align-center mb-0 table-hover text-nowrap w-100">
                <thead>
                    <tr class="text-bg-primary text-center">
                        <th>UserID</th>
                        <th>Empresa</th>
                        <th>Area</th>
                        <th>Personal</th>
                        <th>Contraseña</th>
                        <th>Tipo</th>
                        <th>Estado Sync</th>
                        <th>Estado</th>
                        <th>Registrado</th>
                        <th>Actualizado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
            </table>
            <script>
                const tablaPersonal = new DataTable('#tablaPersonal', {
                    scrollX: true,
                    scrollY: 400,
                    // buttons: ['excel'],
                    ajax: {
                        url: __url + '/personal/listar',
                        dataSrc: function(json) {
                            // let total = json.length;
                            let sync = json.filter(p => p.estado_sync === 1).length;
                            let cre = json.filter(p => p.estado_sync === 0).length;
                            let mod = json.filter(p => p.estado_sync === 2).length;
                            let eli = json.filter(p => p.estado_sync === 3).length;
                            // let pend = json.filter(p => [0, 3].includes(p.estado_sync)).length;

                            $('#totalSync').text(sync);
                            $('#totalCreando').text(cre);
                            $('#totalModificando').text(mod);
                            $('#totalEliminando').text(eli);
                            return json;
                        },
                        error: function(xhr, error, thrown) {
                            boxAlert.table();
                            console.log('Respuesta del servidor:', xhr);
                        }
                    },
                    columns: [{
                            data: 'user_id'
                        },
                        {
                            data: 'empresa'
                        },
                        {
                            data: 'area',
                            render: function(data, type, row) {
                                let area = areas.find(tp => tp.id == data) || {
                                    descripcion: 'Sin Area',
                                    color: '#9fa6b2'
                                };
                                return `<label class="badge" style="font-size: 0.75rem;background-color: ${area.color};">${area.descripcion}</label>`;
                            }
                        },
                        {
                            data: 'dni',
                            render: function(data, type, row) {
                                let dni = (data || '') + (data ? ' - ' : '');
                                return dni + `${row.nombre || ''} ${row.apellido || ''}`;
                            }
                        },
                        {
                            data: 'clave'
                        },
                        {
                            data: 'tipo',
                            render: function(data, type, row) {
                                let tPersonal = tipoPersonal.find(tp => tp.id == data);
                                return `<label class="badge" style="font-size: 0.75rem;background-color: ${tPersonal.color};">${tPersonal.descripcion}</label>`;
                            }
                        },
                        {
                            data: 'estado_sync',
                            render: function(data, type, row) {
                                let estadoTexto = [
                                    ['secondary', 'Creando'],
                                    ['success', 'Sincronizado'],
                                    ['warning', 'Modificando'],
                                    ['danger', 'Eliminando']
                                ][data] || 'Desconocido';

                                return `<label class="badge badge-${estadoTexto[0]}" style="font-size: 0.75rem;">${estadoTexto[1]}</label>`;
                            }
                        },
                        {
                            data: 'estado',
                            render: function(data, type, row) {
                                let estadoTexto = [
                                    ['warning', 'Desactivado'],
                                    ['success', 'Activo'],
                                ][data] || 'Desconocido';

                                return `<label class="badge badge-${estadoTexto[0]}" style="font-size: 0.75rem;">${estadoTexto[1]}</label>`;
                            }
                        },
                        {
                            data: 'registrado'
                        },
                        {
                            data: 'actualizado'
                        },
                        {
                            data: 'acciones'
                        }
                    ],
                    createdRow: function(row, data, dataIndex) {
                        $(row).addClass('text-center');
                        $(row).find('td:eq(1), td:eq(3)').addClass('text-start');
                        $(row).find('td:eq(10)').addClass(`td-acciones`);
                    },
                    processing: true
                });

                function updateTable() {
                    tablaPersonal.ajax.reload();
                }
                mostrar_acciones(tablaPersonal);
            </script>
        </div>
    </div>


    <!-- 🔹 Modal -->
    <div class="modal fade" id="modalPersonal" tabindex="-1" aria-labelledby="modalPersonalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog">
            <form id="formPersonal" class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalPersonalLabel">Registrar Personal</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" id="userid">
                        <div class="col-12 mb-2">
                            <select class="select-clear" id="empresa">
                                <option value="">-- Seleccione --</option>
                                @foreach ($empresa as $v)
                                    <option value="{{ $v->ruc }}"
                                        {{ $v->estatus != 1 || $v->eliminado == 1 ? 'data-hidden="true" data-nosearch="true"' : '' }}>
                                        {{ $v->ruc }} - {{ $v->razon_social }}
                                        {{ $v->eliminado == 1 ? '<label class="badge badge-danger ms-2">Elim.</label>' : ($v->estatus != 1 ? '<label class="badge badge-danger ms-2">Inac.</label>' : '') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <select id="areas" class="select">
                                <option value="">-- Seleccione --</option>
                                @foreach ($areas as $v)
                                    <option value="{{ $v->id }}"
                                        {{ $v->estatus != 1 ? 'data-hidden="true" data-nosearch="true"' : '' }}>
                                        {{ $v->descripcion }}
                                        {{ $v->estatus != 1 ? '<label class="badge badge-danger ms-2">Inac.</label>' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <input id="dni" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                            <input id="nombre" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                            <input id="apellido" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                            <select id="rol_system" class="select">
                                <option value="">-- Seleccione --</option>
                                @foreach ($tipoPersonal as $v)
                                    <option value="{{ $v->id }}"
                                        {{ $v->selected == 1 && $v->estatus == 1 ? 'selected' : '' }}
                                        {{ $v->estatus != 1 || $v->eliminado == 1 ? 'data-hidden="true" data-nosearch="true"' : '' }}>
                                        {{ $v->descripcion }}
                                        {{ $v->eliminado == 1 ? '<label class="badge badge-danger ms-2">Elim.</label>' : ($v->estatus != 1 ? '<label class="badge badge-danger ms-2">Inac.</label>' : '') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <input id="password_view" class="form-control">
                        </div>

                        <small class="fw-bold text-primary my-2">Configuración en sensor</small>
                        <div class="mb-2">
                            <input id="cardno" class="form-control">
                        </div>
                        <div class="col-6 mb-2">
                            <select id="rol_sensor" class="select">
                                <option value="0" selected>Usuario</option>
                                <option value="14">Administrador</option>
                            </select>
                        </div>
                        <div class="col-6 mb-2">
                            <input id="clave" class="form-control">
                        </div>
                        <div class="mb-2">
                            <label class="form-label requested">Modalidad por Día</label>
                            <div id="trabajo_personal" style="overflow-x: auto;"></div>
                        </div>
                        <script>
                            [
                                ['Lunes', 'tplunes'],
                                ['Martes', 'tpmartes'],
                                ['Miércoles', 'tpmiercoles'],
                                ['Jueves', 'tpjueves'],
                                ['Viernes', 'tpviernes'],
                                ['Sábado ', 'tpsabado']
                            ].forEach(dias => {
                                let val = null;
                                let span2 = $('<span>', {
                                    class: 'input-group-text border-0 justify-content-between justify-content-md-start',
                                    style: 'width: 100%;'
                                });
                                let estilos = $('<style>');
                                tipoModalidad.forEach(job => {
                                    let id = job.id;
                                    let color = job.color;
                                    let clase = `form-check-input-${id}`;
                                    if (id == 1) val = id;
                                    span2.append($('<div>', {
                                        class: 'form-check form-check-inline mb-0'
                                    }).append(
                                        $('<input>', {
                                            class: `form-check-input ${clase}`,
                                            type: 'radio',
                                            id: dias[1] + id,
                                            name: dias[1],
                                            value: id,
                                            'data-noclear': '',
                                            ...(id == 1 ? {
                                                checked: true
                                            } : {})
                                        }),
                                        $('<label>', {
                                            class: 'form-check-label ps-0',
                                            for: dias[1] + id
                                        }).text(job.descripcion)
                                    ))
                                    estilos.append(
                                        `.${clase}:checked{border-color: ${color};}.${clase}[type=radio]:checked:after{background-color: ${color};}`
                                    );
                                });
                                $('#trabajo_personal').append($('<div>', {
                                    class: 'input-group mb-1'
                                }).append(
                                    estilos,
                                    $('<span>', {
                                        class: 'input-group-text border-0 px-0',
                                        style: 'min-width: 4rem; width: 8rem; font-size: small; position: sticky; left: 0; z-index: 5; background-color: var(--mdb-modal-bg);'
                                    }).text(dias[0]),
                                    span2
                                ));
                            });
                        </script>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 🔹 Modal -->
    <div class="modal fade" id="modalVacaciones" tabindex="-1" aria-labelledby="modalVacacionesLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalVacacionesLabel">Programar Vacaciones</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="calendar"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnVerDatos">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ secure_asset('front/js/personal/config-full-calendar.js') }}?v=1"></script>


    <!-- 🔹 Scripts -->
    <script src="{{ secure_asset($ft_js->jquery_inputmask_bundle) }}"></script>
    <script src="{{ secure_asset('front/js/personal/personal.js') }}?v=1"></script>
@endsection
