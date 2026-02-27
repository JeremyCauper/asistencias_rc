let lista_mis_asistencias;
let getUrlListar = () => generateUrl(`${__url}/asistencias/listar`, {
    fecha: filtro_fecha.val()
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
    lista_mis_asistencias = new CardTable('vista-movil', {
        ajax: {
            url: getUrlListar(),
            dataSrc: dataSet,
            error: function (xhr, error, thrown) {
                boxAlert.table();
                console.log('Respuesta del servidor:', xhr);
            }
        },
        columns: [{
            data: 'jornada',
            title: 'Jornada'
        },
        {
            data: 'fecha',
            title: 'Fecha'
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
                        <span>${obtenerFechaFormateada(new Date(data.fecha + ' 00:00:00'))}</span>
                    </div>
                    <div class="btn-acciones-movil">${data.acciones}</div>
                </div>
                <div class="d-flex justify-content-start align-items-center">
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
        perPage: 40,
        searchPlaceholder: 'Buscar...',
        order: ['fecha', 'desc'],
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
    lista_mis_asistencias = new DataTable('#lista_mis_asistencias', {
        scrollX: true,
        scrollY: 400,
        ajax: {
            url: getUrlListar(),
            dataSrc: dataSet
        },
        columns: [{
            data: 'jornada',
            render: function (data, type, row) {
                let dia = (data || 'domingo');
                return dia.charAt(0).toUpperCase() + dia.slice(1);
            }
        },
        {
            data: 'fecha'
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
            $(row).addClass('text-center');
            $(row).find('td:eq(0)').addClass('text-start');
            $(row).find('td:eq(7)').addClass(`td-acciones`);
        },
        order: [
            [1, 'desc']
        ],
        processing: true
    });
    mostrar_acciones(lista_mis_asistencias);
}

function updateTable() {
    if (esCelular()) {
        return lista_mis_asistencias.reload();
    }
    lista_mis_asistencias.ajax.reload();
}

function filtroBusqueda() {
    var nuevoUrl = getUrlListar();
    lista_mis_asistencias.ajax.url(nuevoUrl).load();

    if (!esCelular()) {
        lista_mis_asistencias.column([4]).search('').draw();
    }
}

function searchTable(search) {
    if (esCelular()) {
        lista_mis_asistencias.search('tipo_asistencia', search == 0 ? '' : search.toString()).draw();
    } else {
        let tasistencia = tipoAsistencia.find(s => s.id == search)?.descripcion || '';
        lista_mis_asistencias.column([4]).search(tasistencia).draw();
    }

    const contenedor = document.querySelector('.content-wrapper');
    contenedor.scrollTo({
        top: contenedor.scrollHeight,
        behavior: 'smooth'
    });
}