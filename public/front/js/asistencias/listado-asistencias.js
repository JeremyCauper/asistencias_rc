let lista_asistencias;
let getUrlListar = () => generateUrl(`${__url}/asistencias-diarias/listar`, {
    fecha: filtro_fecha.val(),
    empresas: $('#empresas').val(),
    tipoModalidad: $('#tipoModalidad').val(),
    tipoPersonal: $('#tipoPersonal').val(),
    tipoArea: $('#areas').val()
});
let dataSet = (json) => {
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
}

if (esCelular()) {
    $('#vista-movil').show();
    lista_asistencias = new CardTable('vista-movil', {
        ajax: {
            url: getUrlListar(),
            dataSrc: dataSet,
            error: function (xhr, error, thrown) {
                boxAlert.table();
                console.log('Respuesta del servidor:', xhr);
            }
        },
        columns: [{
            data: 'personal',
            title: 'Nombre'
        },
        {
            data: 'area',
            title: 'Área'
        },
        {
            data: 'tipo_modalidad',
            title: 'Modalidad'
        },
        {
            data: 'tipo_asistencia',
            title: 'Estado'
        },
        {
            data: 'entrada',
            title: 'Entrada'
        },
        {
            data: 'salida',
            title: 'Salida'
        }
        ],
        cardTemplate: (data, index) => {
            return `
                <div class="d-flex align-items-center justify-content-between pb-1">
                    <div class="fw-medium mb-0" style="overflow: hidden;font-size: 3.25vw;">
                        <span>${data.personal}</span>
                    </div>
                    <div class="btn-acciones-movil">${data.acciones}</div>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                        ${getBadgeAreas(data.area, '.95', false)}
                    <span>
                        ${getBadgeTipoModalidad(data.tipo_modalidad, '.85')}
                        ${getBadgeTipoAsistencia(data.tipo_asistencia, '.85')}
                    </span>
                </div>
                <hr class="mx-1 my-2">
                <div class="d-flex align-items-center justify-content-between pt-1" style="font-size: 2.85vw;color: #909090;">
                    ${getFormatJornada(data)}
                    ${getBadgeDescuento(data)}
                </div>`;
        },
        scrollY: '600px',
        perPage: 100,
        searchPlaceholder: 'Buscar por nombre...',
        order: ['personal', 'asc'],
        drawCallback: function () {
            if (typeof mdb !== 'undefined') {
                document.querySelectorAll('[data-mdb-dropdown-init]').forEach(el => {
                    new mdb.Dropdown(el);
                });
            }
        }
    });
} else {
    $('#vista-escritorio').show();
    lista_asistencias = new DataTable('#lista_asistencias', {
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
            data: 'personal'
        },
        {
            data: 'area',
            render: function (data, type, row) {
                return getBadgeAreas(data);
            }
        },
        {
            data: 'tipo_personal',
            render: function (data, type, row) {
                return getBadgeTipoPersonal(data);
            }
        },
        {
            data: 'tipo_modalidad',
            render: function (data, type, row) {
                return getBadgeTipoModalidad(data);
            }
        },
        {
            data: 'tipo_asistencia',
            render: function (data, type, row) {
                return getBadgeTipoAsistencia(data);
            }
        },
        {
            data: 'entrada',
            render: function (data, type, row) {
                return data || '-';
            }
        },
        {
            data: 'salida',
            render: function (data, type, row) {
                return data || '-';
            }
        },
        {
            data: 'descuento',
            render: function (data, type, row) {
                return getBadgeDescuento(row);
            }
        },
        {
            data: 'acciones'
        }
        ],
        createdRow: function (row, data, dataIndex) {
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

    mostrar_acciones(lista_asistencias);
}

$('.botones-accion').append(
    $('<button>', {
        class: 'btn btn-primary px-3 me-2',
        "data-mdb-ripple-init": ''
    }).html('<i class="fas fa-rotate"></i>').on('click', updateTable)
);

function updateTable() {
    if (esCelular()) {
        return lista_asistencias.reload();
    }
    lista_asistencias.ajax.reload();
}

function filtroBusqueda() {
    const nuevoUrl = getUrlListar();
    lista_asistencias.ajax.url(nuevoUrl).load();
    if (!esCelular()) {
        lista_asistencias.column([4]).search('').draw();
    }
}

function searchTable(search) {
    if (esCelular()) {
        lista_asistencias.search('tipo_asistencia', search == 0 ? '' : search.toString()).draw();
    } else {
        let tasistencia = tipoAsistencia.find(s => s.id == search)?.descripcion || '';
        lista_asistencias.column([4]).search(tasistencia).draw();
    }

    const contenedor = document.querySelector('.content-wrapper');
    contenedor.scrollTo({
        top: contenedor.scrollHeight,
        behavior: 'smooth'
    });
}