@extends('layout.app')
@section('title', 'Inventario Vehicular')

@section('cabecera')
    <style>
        :root,
        [data-mdb-theme="light"] {
            --bg-tipo-registro: var(--color-gray-200);
            --bg-informacion-fechas-danger: 220 76 100;
            --bg-informacion-fechas-warning: 228 161 27;
            --bg-informacion-fechas-success: 20 164 77;
            --bg-informacion-fechas-info: 84 180 211;
            --bg-informacion-fechas-secondary: 159 166 178;
            --bg-ver-tarjeta-propiedad: var(--color-gray-200);
        }

        [data-mdb-theme="dark"] {
            --bg-tipo-registro: var(--color-slate-700);
            --bg-informacion-fechas-danger: 176 61 80;
            --bg-informacion-fechas-warning: 137 97 16;
            --bg-informacion-fechas-success: 12 98 46;
            --bg-informacion-fechas-info: 59 126 148;
            --bg-informacion-fechas-secondary: 113 120 131;
            --bg-ver-tarjeta-propiedad: var(--color-slate-700);
        }

    </style>
    <script></script>
@endsection
@section('content')

    <div class="col-12" id="vista-escritorio" style="display: none">
        <div class="card">
            <div class="card-body">
                <h6 class="fw-bold mb-0">Inventario Vehicular</h6>

                <table id="tb_inventario_vehicular" class="table table-hover text-nowrap w-100">
                    <thead>
                        <tr class="text-center">
                            <th>Placa</th>
                            <th>Tipo Registro</th>
                            <th>Propietario</th>
                            <th>Modelo</th>
                            <th>Marca</th>
                            <th>Tarjeta de Propiedad</th>
                            <th>Soat</th>
                            <th>Revisión Técnica</th>
                            <th>Chip</th>
                            <th>Cilindro</th>
                            <th>Registrado</th>
                            <th>Actualizado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div id="vista-movil" class="mt-2" style="display: none;">
        <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Inventario Vehicular</h6>
            <div class="acciones"></div>
        </div>
        <div id="lista_inventario_vehicular"></div>
    </div>

    <script
        src="{{ secure_asset('front/js/inventario_vehicular/listado-inventario-vehicular.js') }}?v={{ config('app.version') }}"></script>

    <div class="modal fade" id="modal_inventario_vehicular_asignar" tabindex="-1"
        aria-labelledby="modal_inventario_vehicularLabel" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen-md-down modal-lg">
            <div class="modal-content">
                <div class="modal-header  bg-primary text-white">
                    <h6 class="modal-title" id="modal_inventario_vehicularLabel">ASIGNAR VEHICULO</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 rounded-7 p-3" style="background-color: var(--bg-surface-3);">
                        <span hidden aria-item="id"></span>
                        <div class="d-flex align-items-center pb-1">
                            <div class="align-content-center d-grid rounded-6 text-bg-dark"
                                style="width: 48px;height: 47px;" aria-item="tipo_registro_icon"></div>
                            <div class="ms-2">
                                <p class="fw-bold mb-1" style="font-size: 1.25rem;">
                                    <span aria-item="placa"></span>
                                    <span class="text-muted mb-0 ms-2" style="font-size: .65rem;"
                                        aria-item="tipo_registro"></span>
                                </p>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center my-2">
                            <div class="col-6">
                                <p class="text-muted mb-0" style="font-size: .65rem;">Modelo</p>
                                <p class="fw-bold mb-0" style="font-size: .8rem;" aria-item="modelo"></p>
                            </div>
                            <div class="col-6">
                                <p class="text-muted mb-0" style="font-size: .65rem;">Marca</p>
                                <p class="fw-bold mb-0" style="font-size: .8rem;" aria-item="marca"></p>
                            </div>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="my-1">
                            <input type="search" id="searchPersonal" class="form-control" placeholder="Buscar">
                        </div>
                        <ul id="personal_asignados" class="list-group list-group-light"
                            style="max-height: 400px;overflow: hidden auto;">
                            @foreach ($personal as $v)
                                @if (!in_array($v->rol_system, [2, 4, 7]))
                                    <li class="list-group-item py-3">
                                        <div class="d-flex align-items-center" data-user-id="{{ $v->user_id }}">
                                            <input class="form-check-input me-1" type="checkbox" />
                                            <div class="ms-2">
                                                <p class="mb-1">{{ $v->nombre }} {{ $v->apellido }}</p>
                                                <p class="mb-0">{{ $v->dni }}</p>
                                            </div>
                                        </div>
                                    </li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                    <script>
                        document.getElementById('searchPersonal').addEventListener('input', function () {
                            const search = this.value.toLowerCase().trim();
                            const items = document.querySelectorAll('#personal_asignados li');

                            items.forEach(item => {
                                const nombre = item.querySelector('p.mb-1')?.textContent.toLowerCase() || '';
                                const dni = item.querySelector('p.mb-0')?.textContent.toLowerCase() || '';

                                const coincide = nombre.includes(search) || dni.includes(search);
                                item.style.display = coincide ? '' : 'none';
                            });
                        });
                    </script>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-ripple-init data-mdb-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnAsignar" data-mdb-ripple-init>Asignar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modal_inventario_vehicular" tabindex="-1" aria-labelledby="modal_inventario_vehicularLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="form-inventario-vehicular">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title" id="modal_inventario_vehicularLabel">REGISTRAR VEHICULO</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <input type="hidden" name="id" id="id">
                        <div class="col-md-4 col-6 mb-2">
                            <input class="form-control" id="placa">
                        </div>
                        <div class="col-md-4 col-6 mb-2">
                            <input class="form-control" id="modelo">
                        </div>
                        <div class="col-md-4 col-6 mb-2">
                            <input class="form-control" id="marca">
                        </div>
                        <div class="col-md-4 col-6 mb-2">
                            <select class="select" id="tipo_registro">
                                <option value="VEHICULO">VEHICULO</option>
                                <option value="MOTORIZADO">MOTORIZADO</option>
                                <option value="GERENCIA">GERENCIA</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-2">
                            <select class="select-clear" id="popietario">
                                <option value=""></option>
                                @foreach ($personal as $v)
                                    <option value="{{ $v->user_id }}">
                                        {{ $v->dni }} - {{ $v->nombre }} {{ $v->apellido }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 mb-2">
                            <label class="form-label mb-1" for="fileTarjetaPropiedad">Tarjeta de Propiedad</label>
                            <input type="file" class="form-control" id="fileTarjetaPropiedad" accept=".pdf" />
                        </div>

                        <div class="col-6 mb-2">
                            <label class="form-label mb-1" for="soat">Soat</label>
                            <div class="input-group">
                                <label class="input-group-text px-2" style="font-size: .7rem" for="fileSoat" type="button"
                                    data-mdb-ripple-init>
                                    <i class="far fa-file-pdf text-danger"></i>
                                </label>
                                <input type="file" hidden id="fileSoat" accept=".pdf" />
                                <input type="date" class="form-control" id="soat" name="soat" value="{{ date('Y-m-d') }}">
                                <label class="input-group-text px-2" style="font-size: .7rem" data-ver-pdf="soat"
                                    type="button" data-mdb-ripple-init>
                                    <i class="fas fa-upload"></i>
                                </label>
                            </div>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label mb-1" for="inspeccion">Inspeccion</label>
                            <div class="input-group">
                                <label class="input-group-text px-2" style="font-size: .7rem" for="fileInspeccion"
                                    type="button" data-mdb-ripple-init>
                                    <i class="far fa-file-pdf text-danger"></i>
                                </label>
                                <input type="file" hidden id="fileInspeccion" accept=".pdf" />
                                <input type="date" class="form-control" id="inspeccion" name="inspeccion"
                                    value="{{ date('Y-m-d') }}">
                                <label class="input-group-text px-2" style="font-size: .7rem" data-ver-pdf="inspeccion"
                                    type="button" data-mdb-ripple-init>
                                    <i class="fas fa-upload"></i>
                                </label>
                            </div>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label mb-1" for="chip">Chip</label>
                            <div class="input-group">
                                <label class="input-group-text px-2" style="font-size: .7rem" for="fileChip" type="button"
                                    data-mdb-ripple-init>
                                    <i class="far fa-file-pdf text-danger"></i>
                                </label>
                                <input type="file" hidden id="fileChip" accept=".pdf" />
                                <input type="date" class="form-control" id="chip" name="chip" value="{{ date('Y-m-d') }}">
                                <label class="input-group-text px-2" style="font-size: .7rem" data-ver-pdf="chip"
                                    type="button" data-mdb-ripple-init>
                                    <i class="fas fa-upload"></i>
                                </label>
                            </div>
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label mb-1" for="cilindro">Cilindro</label>
                            <div class="input-group">
                                <label class="input-group-text px-2" style="font-size: .7rem" for="fileCilindro"
                                    type="button" data-mdb-ripple-init>
                                    <i class="far fa-file-pdf text-danger"></i>
                                </label>
                                <input type="file" hidden id="fileCilindro" accept=".pdf" />
                                <input type="date" class="form-control" id="cilindro" name="cilindro"
                                    value="{{ date('Y-m-d') }}">
                                <label class="input-group-text px-2" style="font-size: .7rem" data-ver-pdf="cilindro"
                                    type="button" data-mdb-ripple-init>
                                    <i class="fas fa-upload"></i>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-mdb-ripple-init data-mdb-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary" data-mdb-ripple-init>Guardar</button>
                </div>
            </form>
        </div>
    </div>

@endsection

@section('scripts')
    <script
        src="{{ secure_asset('front/js/inventario_vehicular/inventario_vehicular.js') }}?v={{ config('app.version') }}"></script>
@endsection