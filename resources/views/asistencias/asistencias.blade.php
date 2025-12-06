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
        const areas = @json($areas);
    </script>
    <style>
    </style>
@endsection

@section('navbar')
    <!-- Notifications -->
    <div class="dropdown me-2">
        <a data-mdb-dropdown-init
            class="dropdown-toggle hidden-arrow d-flex align-items-center justify-content-center img-xs rounded-circle"
            href="#" id="navbarDropdownMenuLink" role="button" aria-expanded="false" data-mdb-ripple-init>
            <span>
                <i class="fas fa-bell"></i>
                <span id="notiCount" class="badge rounded-pill badge-notification bg-danger"></span>
            </span>
        </a>
        <ul id="notiList" class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
            <li><span class="dropdown-item-text text-center pt-3 fw-bold">Notificaciones</span></li>
        </ul>
    </div>
@endsection

@section('content')
    <!-- Cards resumen -->
    <div class="row" id="list-estado"></div>
    <script>
        let bliColor = {
            info: '#54b4d3',
            warning: '#e4a11b',
            purple: '#7367f0',
            primary: '#3b71ca',
            success: '#14a44d',
            danger: '#dc4c64',
            light: '#fbfbfb',
            secondary: '#9fa6b2',
            dark: '#332d2d',
        };

        let incidencia_estados = [{
                name: "estado-total",
                text: "Total de Asistencias",
                color: "secondary",
                searchTable: 0,
                chart: false,
            },
            {
                name: "estado-asistencias",
                text: "PUNTUALES",
                color: "success",
                searchTable: 2,
                chart: true,
            },
            {
                name: "estado-faltas",
                text: "FALTAS",
                color: "danger",
                searchTable: 1,
                chart: true,
            },
            {
                name: "estado-tardanzas",
                text: "TARDANZAS",
                color: "warning",
                searchTable: 4,
                chart: true,
            },
            {
                name: "estado-justificados",
                text: "JUSTIFICADOS",
                color: "info",
                searchTable: 3,
                chart: true,
            },
            {
                name: "estado-derivados",
                text: "Derivados",
                color: "purple",
                searchTable: 7,
                chart: true,
            },
        ];

        let list_estado = $('#list-estado');
        incidencia_estados.forEach((e, i) => {
            list_estado.append(
                $('<div>', {
                    class: 'col-xxl-2 col-lg-4 col-6 mb-3'
                }).append(
                    $('<div>', {
                        class: 'card',
                        style: 'height: 100%;',
                        type: 'button',
                        "data-mdb-ripple-init": '',
                        onclick: `searchTable(${e.searchTable})`
                    }).append(
                        $('<div>', {
                            class: 'card-body row',
                            style: 'color: ' + bliColor[e.color],
                        }).append(
                            $('<div>', {
                                class: e.chart ? 'col-7' : ''
                            }).append(
                                $('<h6>', {
                                    class: 'card-title chart-estado-title text-nowrap mb-1'
                                }).text(e.text),
                                $('<h4>', {
                                    class: 'subtitle-count',
                                    id: 'count-' + e.name
                                }).text(0)
                            ),
                            e.chart ? $('<div>', {
                                class: 'col-5'
                            }).append($('<div>', {
                                id: 'chart-' + e.name
                            })) : null
                        )
                    )
                )
            );
            if (e.chart) {
                e.chart = new ChartMananger({
                    id: 'chart-' + e.name,
                    config: {
                        tipo: 'estado',
                        altura: 5,
                        bg: bliColor[e.color]
                    },
                    data: {
                        total: 100,
                        value: 0
                    }
                });
            }
        });

        function setEstados(obj_estado, total) {
            $('#count-estado-total').text(total);

            obj_estado.forEach((e, i) => {
                $('#count-' + e.name).text(e.value);
                let estado = incidencia_estados.find(ie => ie.name == e.name);
                if (estado.chart)
                    estado.chart.updateOption({
                        data: {
                            total: total,
                            value: e.value
                        }
                    });
            });
        }

        function cargarNotificaciones(data) {
            const notificaciones = data.filter(a => a.justificado === 0);
            const notiList = document.getElementById("notiList");
            const notiCount = document.getElementById("notiCount");

            // Limpia los anteriores (manteniendo el título)
            notiList.innerHTML = `<li><span class="dropdown-item-text text-center pt-3 fw-bold">Notificaciones</span></li>`;

            if (notificaciones.length === 0) {
                notiList.innerHTML += `
                    <li class="dropdown-item-text text-center text-muted py-2">
                        Sin notificaciones
                    </li>`;
                $(notiCount).fadeOut();
                return;
            }

            // Recorre y crea cada notificación
            notificaciones.forEach(noti => {
                const nombre = noti.personal.toUpperCase().replaceAll(' ', '').split(",");
                console.log(noti);

                const iniciales = nombre[1][0] + nombre[0][0];
                let tasistencia = tipoAsistencia.find(s => s.id == data) || {
                    descripcion: 'Pendiente',
                    color: '#9fa6b2'
                };

                const item = `
                    <li class="dropdown-item p-3" role="button" onclick="verJustificacion(${noti.user_id})">
                        <div class="d-flex align-items-center">
                        <span class="img-xs rounded-circle text-white acronimo" style="background-color: ${colores(iniciales)};">${iniciales}</span>
                        <div class="mx-3">
                            <p class="fw-bold mb-1">Justificación pendiente</p>
                            <p class="text-muted mb-0">${noti.personal}</p>
                        </div>
                        <span class="badge rounded-pill" style="background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>
                        </div>
                    </li>`;
                notiList.innerHTML += item;
                $(notiCount).fadeIn();
            });

            // Actualiza el contador
            notiCount.textContent = notificaciones.length;
        }
    </script>

    <!-- Tabla -->
    <div class="card">
        <div class="card-body">
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
                    @if (in_array(session('tipo_usuario'), [5, 6]))
                        <label class="form-label-filter" for="areas">Area</label>
                        <div class="form-control">{{ $areas[0]->descripcion }}</div>
                        <input type="hidden" id="areas" name="areas" value="{{ $areas[0]->id }}">
                    @else
                        <label class="form-label-filter" for="areas">Areas</label>
                        <select id="areas" name="areas" multiple="multiple" class="multiselect-select-all">
                            @foreach ($areas as $v)
                                <option
                                    {{ in_array(session('tipo_usuario'), [2, 4]) ? 'selected' : (Auth::user()->area_id == $v->id ? 'selected' : '') }}
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
            </div>
            <table id="tablaAsistencias" class="table align-center mb-0 table-hover text-nowrap w-100">
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
            <script>
                const tablaAsistencias = new DataTable('#tablaAsistencias', {
                    lengthChange: false,
                    paging: false,
                    scrollX: true,
                    scrollY: 400,
                    dom: `<"row"
                        <"col-lg-12 mb-2"B>>
                        <"row"
                            <"col-xsm-6 text-xsm-start text-center my-1 botones-table">
                            <"col-xsm-6 text-xsm-end text-center my-1"f>>
                        <"contenedor_tabla my-2"tr>
                        <"row"
                            <"col-md-5 text-md-start text-center my-1"i>
                            <"col-md-7 text-md-end text-center my-1"p>>`,
                    ajax: {
                        url: generateUrl(`${__url}/asistencias-diarias/listar`, {
                            fecha: $('#filtro_fecha').val(),
                            empresas: $('#empresas').val(),
                            tipoModalidad: $('#tipoModalidad').val(),
                            tipoPersonal: $('#tipoPersonal').val(),
                            tipoArea: $('#areas').val()
                        }),
                        dataSrc: function(json) {
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
                        },
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
                                let area = areas.find(tp => tp.id == data) || {
                                    descripcion: 'Sin Area',
                                    color: '#9fa6b2'
                                };
                                return `<label class="badge" style="font-size: 0.75rem;background-color: ${area.color};">${area.descripcion}</label>`;
                            }
                        },
                        {
                            data: 'tipo_personal',
                            render: function(data, type, row) {
                                let tpersonal = tipoPersonal.find(tp => tp.id == data) || {
                                    descripcion: 'Sin Tipo',
                                    color: '#9fa6b2'
                                };
                                return `<label style="font-size: 0.75rem;">${tpersonal.descripcion}</label>`;
                            }
                        },
                        {
                            data: 'tipo_modalidad',
                            render: function(data, type, row) {
                                let tmodalidad = tipoModalidad.find(tp => tp.id == data) || {
                                    descripcion: 'Sin Tipo',
                                    color: '#9fa6b2'
                                };
                                return `<label class="badge" style="font-size: 0.75rem; background-color: ${tmodalidad.color};"><i class="${tmodalidad.icono} fa-1x me-1"></i>${tmodalidad.descripcion}</label>`;
                            }
                        },
                        {
                            data: 'tipo_asistencia',
                            render: function(data, type, row) {
                                let tasistencia = tipoAsistencia.find(s => s.id == data) || {
                                    descripcion: 'Pendiente',
                                    color: '#9fa6b2'
                                };
                                return `<label class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</label>`;
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
                                let tasistencia = row.tipo_asistencia;
                                return data || (tasistencia == 1 ? 'Día Comp.' : '-');
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

                function updateTable() {
                    tablaAsistencias.ajax.reload();
                }
                mostrar_acciones(tablaAsistencias);

                function filtroBusqueda() {
                    const nuevoUrl = generateUrl(`${__url}/asistencias-diarias/listar`, {
                        fecha: $('#filtro_fecha').val(),
                        empresas: $('#empresas').val(),
                        tipoModalidad: $('#tipoModalidad').val(),
                        tipoPersonal: $('#tipoPersonal').val(),
                        tipoArea: $('#areas').val()
                    });

                    tablaAsistencias.ajax.url(nuevoUrl).load();
                    tablaAsistencias.column([4]).search('').draw();
                }

                function searchTable(search) {
                    let tasistencia = tipoAsistencia.find(s => s.id == search)?.descripcion || '';
                    tablaAsistencias.column([4]).search(tasistencia).draw();

                    const contenedor = document.querySelector('.content-wrapper');
                    contenedor.scrollTo({
                        top: contenedor.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            </script>
        </div>
    </div>

    <!-- Modal Descuento -->
    <button class="d-none" data-mdb-modal-init data-mdb-target="#modalDescuento"></button>
    <div class="modal fade" id="modalDescuento" tabindex="-1" aria-labelledby="modalDescuentoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <form class="modal-content" id="form-descuento">
                <div class="modal-header bg-primary text-white">
                    <h6 class="modal-title" id="modalDescuentoLabel">Aplicar / Modificar Descuento</h6>
                    <button type="button" class="btn-close btn-close-white" data-mdb-ripple-init
                        data-mdb-dismiss="modal" aria-label="Close"></button>
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
                                        {{ in_array(session('tipo_usuario'), [2, 4]) ? 'selected' : (Auth::user()->area_id == $v->id ? 'selected' : '') }}
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
    
    <script src="{{ secure_asset('front/js/asistencias/asistencias.js') }}?v=1"></script>
    @if (!in_array(session('tipo_usuario'), [1, 5, 6]) || session('tipo_sistema'))
        <script src="{{ secure_asset($ft_js->exceljs) }}"></script>
        <script src="{{ secure_asset($ft_js->FileSaver) }}"></script>
        <script src="{{ secure_asset('front/js/asistencias/export-excel-asistencias.js') }}?v=1"></script>
    @endif
    <script src="{{ secure_asset('front/js/asistencias/asistencias-justificaciones.js') }}?v=1.1"></script>
@endsection
