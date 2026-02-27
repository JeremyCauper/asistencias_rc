let lista_contrato;
let getUrlListar = () => generateUrl(__url + '/contratos/listar', {
    empresa: $('#empresa').val()
});
let dataSet = (json) => {
    let total = json.length;
    let activos = json.filter(p => p.estatus === 1).length;
    let prontoExpirar = json.filter(p => p.estatus === 2).length;
    let expirados = json.filter(p => p.estatus === 3).length;

    $('#totalContratos').text(total);
    $('#totalActivos').text(activos);
    $('#totalProntoExpirar').text(prontoExpirar);
    $('#totalExpirados').text(expirados);
    return json;
}
let getDiasRestantes = (data) => {
    if (data) {
        const fechaExp = new Date(data + 'T00:00:00');
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
            color: 'success'
        };
    } else {
        informacion = {
            estado: 'Sin Registro',
            color: 'secondary'
        };
    }

    return `<span class="ms-auto badge badge-${informacion.color} rounded-1" style="font-size: .75rem">${informacion.estado}</span>`;
}


if (esCelular()) {
    $('#vista-movil').show();
} else {
    $('#vista-escritorio').show();
    lista_contrato = new DataTable('#lista_contrato', {
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
        columns: [
            {
                data: 'dni',
                render: function (data, type, row) {
                    let dni = (data || '') + (data ? ' - ' : '');
                    return dni + `${row.nombre || ''} ${row.apellido || ''}`;
                }
            },
            {
                data: 'empresa'
            },
            {
                data: 'area',
                render: function (data, type, row) {
                    return getBadgeAreas(data, '.75', false);
                }
            },
            {
                data: 'tipo_contrato',
                render: function (data, type, row) {
                    let tipo = [
                        { id: 0, descripcion: 'Contrato' },
                        { id: 1, descripcion: 'Permanente' },
                        { id: 2, descripcion: 'Por Proyecto' }
                    ]
                    return (tipo.find(t => t.id == data)?.descripcion || 'Contrato');
                }
            },
            {
                data: 'fecha_fin', render: function (data, type, row) {
                    return getDiasRestantes(data);
                }
            },
            {
                data: 'fecha_inicio', render: function (data, type, row) {
                    if (data) {
                        const fechaExp = new Date(data + 'T00:00:00');
                        return obtenerFechaFormateada(fechaExp, true);
                    } else return '-';
                }
            },
            {
                data: 'fecha_fin', render: function (data, type, row) {
                    if (data) {
                        const fechaExp = new Date(data + 'T00:00:00');
                        return obtenerFechaFormateada(fechaExp, true);
                    } else return '-';
                }
            },
            {
                data: 'acciones'
            }
        ],
        createdRow: function (row, data, dataIndex) {
            $(row).addClass('text-center');
            $(row).find('td:eq(0), td:eq(1)').addClass('text-start');
            $(row).find('td:eq(7)').addClass(`td-acciones`);
        },
        processing: true
    });
    mostrar_acciones(lista_contrato);
}

function updateTable() {
    if (esCelular()) {
        return lista_contrato.reload();
    }
    lista_contrato.ajax.reload();
}