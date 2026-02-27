let lista_inventario_vehicular;
let getUrlListar = () => `${__url}/inventario-vehicular/listar`;
let dataSet = (json) => {
    return json?.data;
};

let btn_acciones = $('<div>');
btn_acciones.append(
    $('<button>', {
        class: 'btn btn-primary me-1',
        "data-mdb-ripple-init": '',
        "data-mdb-modal-init": '',
        "data-mdb-target": '#modal_inventario_vehicular',
    }).html('<i class="fas fa-plus"></i> Vehiculo'),
    $('<button>', {
        class: 'btn btn-primary px-2',
        "data-mdb-ripple-init": '',
        "role": 'button'
    }).html('<i class="fas fa-rotate-right" style="min-width: 1.25rem;"></i>').on('click', updateTable),
);

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
            `<span class="ms-auto badge badge-${informacion.color} rounded-pill">${informacion.estado}</span>`;
    } else {
        informacion = {
            estado: null,
            color: 'secondary'
        };
        fechaExpiracionFormateada = 'Sin Registro';
    }

    let color = informacion.color || 'secondary';

    return esCelular() ? `
        <div class="d-flex align-items-center rounded-4 p-2 w-100" style="font-size: .65rem;background-color: rgb(var(--bg-informacion-fechas-${color}) / 15%);color: rgb(var(--bg-informacion-fechas-${color}));border: 1px solid rgb(var(--bg-informacion-fechas-${color}));" type="button" ${abrirPdf}>
            <div class="text-start">
                <p class="mb-1 fw-bold" style="font-size: .65rem;"><i class="${iconos.icon} me-2" style="font-size: .75rem;"></i>${iconos.titulo}</p>
                <p class="mb-0 text-nowrap" style="font-size: .7rem;">${fechaExpiracionFormateada}</p>
            </div>
            ${estadoBadge}
        </div>
        ` : `<div class="d-flex justify-content-between"><span style="font-size: .7rem;color: rgb(var(--bg-informacion-fechas-${color}));">${fechaExpiracionFormateada}</span>${estadoBadge}</div>`;
}

const ver_tarjeta_propiedad = (urlPdf, movil = false) => {
    if (!urlPdf && movil) return '';

    let abrirPdf = urlPdf ? `onclick="abrirPdf('${urlPdf}')" data-mdb-ripple-init type="button" style="font-size: .75rem;background-color: var(--bg-ver-tarjeta-propiedad);"` : 'style="cursor: default;"';

    return esCelular() ? `
        <div class="text-center rounded-bottom-4 p-3" ${abrirPdf}>
            ${urlPdf ? `<i class="fas fa-eye me-2"></i>Ver Tarjeta de Propiedad` : 'Sin Registro de Tarjeta'}
        </div>` : `<span class="badge rounded-pill" ${abrirPdf}>
            ${urlPdf ? `<i class="fas fa-eye me-2"></i>Ver` : 'Sin Registro de Tarjeta'}
        </span>`;
};

if (esCelular()) {
    $('#vista-movil').show().find('.acciones').append(btn_acciones);

    lista_inventario_vehicular = new CardTable('lista_inventario_vehicular', {
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
        cardWrapper: '<div class="card rounded-7" style="margin: .5rem .1rem;"><div class="card-body p-0">:content</div></div>',
        cardTemplate: (data, index) => {
            return `
                <div class="p-3">
                    <div class="d-flex align-items-center">
                        <div class="align-content-center d-grid rounded-6 text-white" style="width: 48px;height: 47px;background-color: #0f1117;">
                            <i class="fas fa-${data.tipo_registro.toLocaleLowerCase() != 'motorizado' ? 'car' : 'motorcycle'}"></i>
                        </div>
                        <div class="ms-2">
                            <p class="fw-bold mb-1" style="font-size: 1.25rem;">
                                ${data.placa}
                                <span class="mb-0 ms-2 px-2 py-1 rounded-pill text-muted" style="font-size: .65rem;background-color: var(--bg-tipo-registro);">${data.tipo_registro}</span>
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
    $('#vista-escritorio').show();
    lista_inventario_vehicular = new DataTable('#tb_inventario_vehicular', {
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
    mostrar_acciones(lista_inventario_vehicular);
    $('.botones-accion').append(btn_acciones);
}

function updateTable() {
    if (esCelular()) {
        return lista_inventario_vehicular.reload();
    }
    lista_inventario_vehicular.ajax.reload();
}