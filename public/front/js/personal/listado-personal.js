let lista_personal;
let getUrlListar = () => generateUrl(__url + '/personal/listar', {
    empresa: $('#empresa').val()
});
let dataSet = (json) => {
    let sync = json.filter(p => p.estado_sync === 1).length;
    let cre = json.filter(p => p.estado_sync === 0).length;
    let mod = json.filter(p => p.estado_sync === 2).length;
    let eli = json.filter(p => p.estado_sync === 3).length;

    $('#totalSync').text(sync);
    $('#totalCreando').text(cre);
    $('#totalModificando').text(mod);
    $('#totalEliminando').text(eli);
    return json;
}

if (esCelular()) {
    $('#vista-movil').show();
    lista_personal = new CardTable('vista-movil', {
        ajax: {
            url: getUrlListar(),
            dataSrc: dataSet,
            error: function (xhr, error, thrown) {
                boxAlert.table();
                console.log('Respuesta del servidor:', xhr);
            }
        },
        columns: [{
            data: 'user_id',
            title: 'USer Id'
        },
        {
            data: 'empresa',
            title: 'Empresa'
        },
        {
            data: 'area',
            title: 'Area'
        },
        {
            data: 'dni',
            title: 'Dni'
        },
        {
            data: 'nombre',
            title: 'Nombre'
        },
        {
            data: 'apellido',
            title: 'Apellido'
        },
        {
            data: 'clave',
            title: 'Clave'
        },
        {
            data: 'tipo',
            title: 'Tipo'
        },
        {
            data: 'estado_sync',
            title: 'Estado Sync'
        },
        {
            data: 'estado',
            title: 'Estado'
        }
        ],
        cardTemplate: (data, index) => {
            return `
                <div class="d-flex align-items-center justify-content-between pb-1">
                    <div class="fw-medium mb-0" style="overflow: hidden;font-size: 3vw;">
                        <span class="badge badge-dark">${data.user_id}</span>
                        <span>${data.apellido}, ${data.nombre}</span>
                    </div>
                    <div class="btn-acciones-movil">${data.acciones}</div>
                </div>
                <div class="d-flex justify-content-between align-items-center pb-2">
                    <span style="font-size: 2.85vw;"><b class="text-muted">Documento:</b> ${data.dni}</span>
                    <span>
                        ${getBadgeAreas(data.area, '.8', false)} / ${getBadgeTipoPersonal(data.tipo, '.8', true)}
                    </span>
                </div>
                <div class="d-flex justify-content-between align-items-center pb-2">
                    <span style="font-size: 2.85vw;"><b class="text-muted">Contraseña:</b> ${data.clave}</span>
                </div>
                <div class="d-flex justify-content-start align-items-center" style="gap: 4px;">
                    ${getBadgeEstadoSync(data.estado_sync, '.75', false)} ${getBadgeEstado(data.estado, '.75')}
                </div>
                <hr class="mx-1 my-2">
                <div class="d-flex align-items-center justify-content-between pt-1" style="font-size: 2.85vw;color: #909090;">
                    <label>
                        <span style="vertical-align: middle;">${data.empresa}</span>
                    </label>
                </div>`;
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
    lista_personal = new DataTable('#lista_personal', {
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
            url: __url + '/personal/listar',
            dataSrc: dataSet,
            error: function (xhr, error, thrown) {
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
            render: function (data, type, row) {
                return getBadgeAreas(data, '.75', false);
            }
        },
        {
            data: 'dni',
            render: function (data, type, row) {
                let dni = (data || '') + (data ? ' - ' : '');
                return dni + `${row.nombre || ''} ${row.apellido || ''}`;
            }
        },
        {
            data: 'clave'
        },
        {
            data: 'tipo',
            render: function (data, type, row) {
                return getBadgeTipoPersonal(data);
            }
        },
        {
            data: 'estado_sync',
            render: function (data, type, row) {
                return getBadgeEstadoSync(data);
            }
        },
        {
            data: 'estado',
            render: function (data, type, row) {
                return getBadgeEstado(data);
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
        createdRow: function (row, data, dataIndex) {
            $(row).addClass('text-center');
            $(row).find('td:eq(1), td:eq(3)').addClass('text-start');
            $(row).find('td:eq(10)').addClass(`td-acciones`);
        },
        processing: true
    });
    mostrar_acciones(lista_personal);
}

function updateTable() {
    if (esCelular()) {
        return lista_personal.reload();
    }
    lista_personal.ajax.reload();
}