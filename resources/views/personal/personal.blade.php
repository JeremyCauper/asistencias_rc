@extends('layout.app')
@section('title', 'Control del Personal')

@section('cabecera')
    <link rel="stylesheet" href="{{ secure_asset('front/css/app/personal/personal.css') }}?v={{ config('app.version') }}">
    <script>
        const empresa = @json($empresa);
        const tipoAreas = @json($areas);
        const tipoModalidad = @json($tipoModalidad);
        const tipoPersonal = @json($tipoPersonal);
    </script>
    <script src="{{ secure_asset($ft_js->full_calendar) }}"></script>
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
                                <i class="fa-solid fa-users fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Total</p>
                            <p class="fw-bold mb-0 fs-4" id="totalSync">0</p>
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
                                <i class="fa-solid fa-cloud-arrow-up fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Sincronizando</p>
                            <p class="fw-bold mb-0 fs-4" id="totalCreando">0</p>
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
                                <i class="fa-solid fa-pen-to-square fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Modificando</p>
                            <p class="fw-bold mb-0 fs-4" id="totalModificando">0</p>
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
                                <i class="fa-solid fa-exclamation-triangle fa-fw fs-4"></i>
                            </div>
                        </div>
                        <div class="content-text flex-grow-1 ms-2">
                            <p class="text-muted mb-1">Eliminando</p>
                            <p class="fw-bold mb-0 fs-4" id="totalEliminando">0</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 🔹 Tabla -->
    <div class="card">
        <div class="card-body">
            <h6 class="fw-bold mb-0">Listado de Personal</h6>
            <button hidden data-mdb-modal-init data-mdb-target="#modalPersonal"></button>

            <div id="vista-escritorio" style="display: none;">
                <table id="lista_personal" class="table align-center mb-0 table-hover text-nowrap w-100" >
                    <thead>
                        <tr class="text-center">
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
            </div>
        </div>
    </div>

    <div id="vista-movil" style="display: none;"></div>

    <script
        src="{{ secure_asset('front/js/personal/listado-personal.js') }}?v={{ config('app.version') }}"></script>


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
                                        style: 'min-width: 4rem; width: 8rem; font-size: small; position: sticky; left: 0; z-index: 5; background-color: var(--bg-modal-body);'
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

    <!-- 🔹 Modal Vacaciones -->
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
                    <div id="calendarVacaciones"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarVacaciones">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 🔹 Modal Descansos -->
    <div class="modal fade" id="modalDescansos" tabindex="-1" aria-labelledby="modalDescansosLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalDescansosLabel">Programar Descansos</h5>
                    <button type="button" class="btn-close btn-close-white" data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- <div class="mb-3">
                        <label for="archivoDescanso" class="form-label">Subir Archivo (Constancia/Certificado)</label>
                        <input class="form-control" type="file" id="archivoDescanso" accept=".pdf, .jpg, .jpeg, .png">
                    </div> -->
                    <div id="calendarDescansos"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardarDescansos">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ secure_asset('front/js/personal/config-full-calendar.js') }}?v={{ config('app.version') }}"></script>


    <!-- 🔹 Scripts -->
    <script src="{{ secure_asset($ft_js->jquery_inputmask_bundle) }}"></script>
    <script src="{{ secure_asset('front/js/personal/personal.js') }}?v={{ config('app.version') }}"></script>
@endsection
