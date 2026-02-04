@extends('layout.app')
@section('title', 'Inventario Vehicular')

@section('cabecera')
    <style>
    </style>
    <script></script>
@endsection
@section('content')


    <div class="col-12">
        <div class="card">
            <div class="card-body px-0">
                <div class="d-flex align-items-center justify-content-between mx-3">
                    <h6 class="fw-bold mb-0">Inventario Vehicular</h6>
                </div>

                <div id="cardsInventarioVehicular" style="display: none"></div>

                <table id="tb_inventario_vehicular" class="table table-hover text-nowrap w-100" style="display: none">
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
                <script>
                    let tb_inventario_vehicular;
                    let getUrlListar = () => `${__url}/inventario-vehicular/listar`;
                    let dataSet = (json) => {
                        return json?.data;
                    };

                    function evaluarExpiracion(fechaExpiracion, tipo, urlPdf) {
                        const iconos = {
                            'soat': {
                                titulo: 'SOAT',
                                icon: 'fas fa-shield'
                            },
                            'r_tecnica': {
                                titulo: 'INSPECCIÓN',
                                icon: 'fas fa-wrench'
                            },
                            'v_chip': {
                                titulo: 'CHIP',
                                icon: 'fas fa-microchip'
                            },
                            'v_cilindro': {
                                titulo: 'CILINDRO',
                                icon: 'fas fa-gas-pump'
                            }
                        }[tipo];
                        let informacion = null;
                        let fechaExpiracionFormateada = '';
                        let estadoBadge = '';
                        let abrirPdf = urlPdf ? `onclick="abrirPdf('${urlPdf}')" data-mdb-ripple-init` : 'style="cursor: default;"';

                        if (fechaExpiracion) {
                            const fechaExp = new Date(fechaExpiracion + 'T00:00:00');
                            const hoy = new Date();
                            hoy.setHours(0, 0, 0, 0);
                            const diferenciaTiempo = fechaExp - hoy;
                            const diferenciaDias = Math.ceil(diferenciaTiempo / (1000 * 60 * 60 * 24));

                            if (diferenciaDias < 0) {
                                informacion = {
                                    estado: 'Expirado',
                                    color: 'danger'
                                };
                            } else if (diferenciaDias >= 0 && diferenciaDias <= 10) {
                                informacion = {
                                    estado: `${diferenciaDias} dia${diferenciaDias !== 1 ? 's' : ''}`,
                                    color: 'warning'
                                };
                            } else informacion = {
                                estado: `Vigente`,
                                color: urlPdf ? 'success' : 'info'
                            };

                            fechaExpiracionFormateada = obtenerFechaFormateada(fechaExp, true);
                            estadoBadge =
                                `<span class="ms-auto badge badge-${informacion.color} rounded-1">${informacion.estado}</span>`;
                        } else {
                            informacion = {
                                estado: null,
                                color: 'secondary'
                            };
                            fechaExpiracionFormateada = 'Sin Registro';
                        }

                        let color = {
                            'danger': { cl: 'danger', bg: '#52000e' },
                            'warning': { cl: 'warning', bg: '#5f3d00' },
                            'success': { cl: 'success', bg: '#03471d' },
                            'info': { cl: 'info', bg: '#07495f' },
                            'secondary': { cl: 'secondary', bg: '#343f4f' }
                        }[informacion.color] || { cl: 'secondary', bg: '#343f4f' };

                        return `
                                <div class="d-flex align-items-center border border-${color.cl} rounded-4 p-1 w-100" style="font-size: .65rem;background-color: ${color.bg};" type="button" ${abrirPdf}>
                                    <div class="d-flex align-items-center me-1">
                                        <span class="text-${color.cl}"><i class="${iconos.icon}" style="font-size: .75rem;"></i></span>
                                        <span class="text-start">
                                            <p class="mb-0 fw-bold text-white" style="font-size: .5rem;">${iconos.titulo}</p>
                                            <p class="mb-0 text-${color.cl} text-nowrap" style="font-size: .6rem;">${fechaExpiracionFormateada}</p>
                                        </span>
                                    </div>
                                    ${estadoBadge}
                                </div>
                                `;
                    }

                    const ver_tarjeta_propiedad = (urlPdf, movil = false) => {
                        if (!urlPdf && movil) return '';

                        let abrirPdf = urlPdf ? `onclick="abrirPdf('${urlPdf}')" data-mdb-ripple-init type="button"` : 'style="cursor: default;"';

                        return `
                            <div class="text-center mt-1">
                                <span class="badge bg-black p-2" style="font-size: .65rem;" ${abrirPdf}>
                                    ${urlPdf ? `<i class="fas fa-eye me-1"></i>Tarjeta de Propiedad` : 'Sin Registro de Tarjeta'}
                                </span>
                            </div>`;
                    };

                    if (esCelular()) {
                        $('#cardsInventarioVehicular').removeAttr('style');
                        tb_inventario_vehicular = new CardTable('cardsInventarioVehicular', {
                            ajax: {
                                url: getUrlListar(),
                                dataSrc: dataSet,
                                error: function (xhr, error, thrown) {
                                    boxAlert.table();
                                    console.log('Respuesta del servidor:', xhr);
                                }
                            },
                            columns: [{
                                data: 'placa'
                            },
                            {
                                data: 'tipo_registro'
                            },
                            {
                                data: 'propietario'
                            },
                            {
                                data: 'modelo'
                            },
                            {
                                data: 'marca'
                            },
                            {
                                data: 'soat'
                            },
                            {
                                data: 'r_tecnica'
                            },
                            {
                                data: 'v_chip'
                            },
                            {
                                data: 'v_cilindro'
                            },
                            ],
                            cardTemplate: (data, index) => {
                                return `
                                                    <div class="d-flex align-items-center pb-1">
                                                        <div class="align-content-center d-grid rounded-6 text-white" style="width: 48px;height: 47px;background-color: #0f1117;">
                                                            <i class="fas fa-${data.tipo_registro.toLocaleLowerCase() != 'motorizado' ? 'car' : 'motorcycle'}"></i>
                                                        </div>
                                                        <div class="ms-2">
                                                            <p class="fw-bold mb-1" style="font-size: 1.25rem;">
                                                                ${data.placa}
                                                                <span class="text-muted mb-0 ms-2" style="font-size: .65rem;">${data.tipo_registro}</span>
                                                            </p>
                                                            <p class="text-muted mb-0" style="font-size: .65rem;">${data.propietario}</p>
                                                        </div>
                                                        <div class="btn-acciones-movil ms-auto">${data.acciones}</div>
                                                    </div>
                                                    <div class="d-flex justify-content-between align-items-center my-2">
                                                        <div class="col-6">
                                                            <p class="text-muted mb-0" style="font-size: .65rem;">Modelo</p>
                                                            <p class="fw-bold mb-0" style="font-size: .8rem;">${data.modelo}</p>
                                                        </div>
                                                        <div class="col-6">
                                                            <p class="text-muted mb-0" style="font-size: .65rem;">Marca</p>
                                                            <p class="fw-bold mb-0" style="font-size: .8rem;">${data.marca}</p>
                                                        </div>
                                                    </div>
                                                    <hr class="m-1">
                                                    <div class="col-12 text-muted my-1" style="font-size: .65rem;">Estado de Mantenimientos</div>
                                                    <div class="col-12">
                                                        <div class="row">
                                                            <div class="col-6 p-0 pb-1" style="padding-right: .1rem !important;">${evaluarExpiracion(data.soat, 'soat', data.soat_pdf)}</div>
                                                            <div class="col-6 p-0 pb-1" style="padding-left: .1rem !important;">${evaluarExpiracion(data.r_tecnica, 'r_tecnica', data.r_tecnica_pdf)}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-6 p-0 pb-1" style="padding-right: .1rem !important;">${evaluarExpiracion(data.v_chip, 'v_chip', data.v_chip_pdf)}</div>
                                                            <div class="col-6 p-0 pb-1" style="padding-left: .1rem !important;">${evaluarExpiracion(data.v_cilindro, 'v_cilindro', data.v_cilindro_pdf)}</div>
                                                        </div>
                                                    </div>
                                                    ${ver_tarjeta_propiedad(data.tarjeta_propiedad_pdf, true)}
                                                    `;
                            },
                            scrollY: '600px',
                            perPage: 50,
                            searchPlaceholder: 'Buscar',
                            order: ['apellido', 'asc'],
                            drawCallback: function () {
                                if (typeof mdb !== 'undefined') {
                                    document.querySelectorAll('[data-mdb-dropdown-init]').forEach(el => {
                                        new mdb.Dropdown(el);
                                    });
                                }
                            }
                        });
                    } else {
                        $('#tb_inventario_vehicular').removeAttr('style');
                        tb_inventario_vehicular = new DataTable('#tb_inventario_vehicular', {
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
                                error: function (xhr, error, thrown) {
                                    boxAlert.table();
                                    console.log('Respuesta del servidor:', xhr);
                                }
                            },
                            columns: [{
                                data: 'placa'
                            },
                            {
                                data: 'tipo_registro'
                            },
                            {
                                data: 'propietario'
                            },
                            {
                                data: 'modelo'
                            },
                            {
                                data: 'marca'
                            },
                            {
                                data: 'tarjeta_propiedad_pdf',
                                render: function (data, type, row) {
                                    return ver_tarjeta_propiedad(data);
                                }
                            },
                            {
                                data: 'soat',
                                render: function (data, type, row) {
                                    return evaluarExpiracion(data, 'soat', row.soat_pdf);
                                }
                            },
                            {
                                data: 'r_tecnica',
                                render: function (data, type, row) {
                                    return evaluarExpiracion(data, 'r_tecnica', row.r_tecnica_pdf);
                                }
                            },
                            {
                                data: 'v_chip',
                                render: function (data, type, row) {
                                    return evaluarExpiracion(data, 'v_chip', row.v_chip_pdf);
                                }
                            },
                            {
                                data: 'v_cilindro',
                                render: function (data, type, row) {
                                    return evaluarExpiracion(data, 'v_cilindro', row.v_cilindro_pdf);
                                }
                            },
                            {
                                data: 'created_at'
                            },
                            {
                                data: 'updated_at'
                            },
                            {
                                data: 'acciones'
                            }
                            ],
                            createdRow: function (row, data, dataIndex) {
                                $(row).addClass('text-center');
                                $(row).find('td:eq(12)').addClass(`td-acciones`);
                            },
                            processing: true
                        });
                        mostrar_acciones(tb_inventario_vehicular);
                    }

                    function updateTable() {
                        if (esCelular()) {
                            return tb_inventario_vehicular.reload();
                        }
                        tb_inventario_vehicular.ajax.reload();
                    }
                </script>


            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_inventario_vehicular_asignar" tabindex="-1"
        aria-labelledby="modal_inventario_vehicularLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header  bg-primary text-white">
                    <h6 class="modal-title" id="modal_inventario_vehicularLabel">ASIGNAR VEHICULO</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init data-mdb-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2 border border-secondary rounded-4 p-3">
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
                            <select class="select-clear-nsearch" id="tipo_registro">
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