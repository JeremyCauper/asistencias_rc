$(document).ready(function () {
    configControls([
        { control: '#descripcion', mxl: 50, requested: true },
        { control: '#ruta', mxl: 255, requested: true },
        { control: '#submenu', addLabel: 'Sub menu', requested: true },
        { control: ['#desarrollo', '#estado'], requested: true },
    ]);

    $('.modal').on('hidden.bs.modal', function () {
        $('#modal_menuLabel').html('REGISTRAR MENU');
        $('#id').val('');
    });

    $('#tb_menu').off("draw.dt").on('draw.dt', function () {
        iniciarTbOrden();
    });

    $('#icono').on('change blur', function () {
        $('[aria-label="icono"]').attr('class', $(this).val() || 'fas fa-question');
    });

    fObservador('.content-wrapper', () => {
        tb_menu.columns.adjust().draw();
    });
});

function updateTable() {
    tb_menu.ajax.reload();
}

const url_base = `${__url}/mantenimiento-dev/menu/menu`;
mostrar_acciones(tb_menu);

// ======================================================
// GUARDAR / ACTUALIZAR
// ======================================================
$('#form-menu').on('submit', async function (event) {
    event.preventDefault();

    fMananger.formModalLoding('modal_menu', 'show');

    const valid = validFrom(this);
    if (!valid.success) {
        fMananger.formModalLoding('modal_menu', 'hide');
        return;
    }

    try {
        const body = JSON.stringify(valid.data.data);
        const response = await fetch(`${url_base}/${$('#id').val() ? 'actualizar' : 'registrar'}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body,
        });

        const data = await response.json();
        fMananger.formModalLoding('modal_menu', 'hide');

        if (!response.ok || !data.success) {
            const mensaje = data.message || 'No se pudo completar la operación.';
            return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
        }

        $('#modal_menu').modal('hide');
        boxAlert.box({ h: data.message || 'Operación realizada con éxito.' });
        updateTable();
    } catch (error) {
        fMananger.formModalLoding('modal_menu', 'hide');
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    }
});

// ======================================================
// EDITAR
// ======================================================
async function Editar(id) {
    try {
        $('#modal_menuLabel').html('EDITAR MENU');
        $('#modal_menu').modal('show');
        fMananger.formModalLoding('modal_menu', 'show');

        const response = await fetch(`${url_base}/mostrar/${id}`, { method: 'GET' });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo obtener la información solicitada.');
        }

        if (!data.data) {
            throw new Error('No se encontró el registro solicitado. Puede que haya sido eliminado.');
        }

        const json = data.data;

        $('#id').val(json.id);
        $('#descripcion').val(json.descripcion);
        $('#icono').val(json.icon).trigger('change');
        $('#ruta').val(json.ruta);
        $('#submenu').val(json.submenu).trigger('change');
        $('#desarrollo').val(json.sistema).trigger('change');
        $('#estado').val(json.estatus).trigger('change');

        fMananger.formModalLoding('modal_menu', 'hide');
    } catch (error) {
        console.error('Error al cargar registro:', error);

        boxAlert.box({
            i: 'error',
            t: 'No pudimos obtener la información',
            h: error.message || 'Ocurrió un error inesperado. Por favor, intenta nuevamente.'
        });

        fMananger.formModalLoding('modal_menu', 'hide');
    }
}

// ======================================================
// CAMBIAR ESTADO
// ======================================================
async function CambiarEstado(id, estado) {
    try {
        const confirm = await boxAlert.confirm({
            h: `¿Está seguro de ${estado ? 'desactivar' : 'activar'} este menu?`
        });
        if (!confirm) return;

        boxAlert.loading();

        const body = JSON.stringify({ id, estado: estado ? 0 : 1 });
        const response = await fetch(`${url_base}/cambiar-estado`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body,
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo cambiar el estado.');
        }

        boxAlert.box({ h: data.message || 'Estado actualizado correctamente.' });
        updateTable();
    } catch (error) {
        console.error('Error al cambiar estado:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error al actualizar el estado',
            h: error.message || 'Ocurrió un error interno. Intenta nuevamente más tarde.'
        });
    }
}

// ======================================================
// CAMBIAR ORDEN
// ======================================================
async function cambiarOrden() {
    try {
        const confirm = await boxAlert.confirm();
        if (!confirm) return;

        boxAlert.loading();

        const body = JSON.stringify({ data: extraerIdsYOrdenes() });
        const response = await fetch(`${url_base}/cambiar-orden-menu`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body,
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo cambiar el estado.');
        }

        boxAlert.box({ h: data.message || 'Estado actualizado correctamente.' });
        fMananger.formModalLoding('modal_ordenm', 'hide');
        updateTable();
    } catch (error) {
        console.error('Error al cambiar estado:', error);

        fMananger.formModalLoding('modal_ordenm', 'hide');

        boxAlert.box({
            i: 'error',
            t: 'Error al actualizar el estado',
            h: error.message || 'Ocurrió un error interno. Intenta nuevamente más tarde.'
        });
    }
}

// ======================================================
// ELIMINAR
// ======================================================
async function Eliminar(id) {
    try {
        const confirm = await boxAlert.confirm({
            h: '¿Está seguro de eliminar este menu? Esta acción no se puede deshacer.'
        });
        if (!confirm) return;

        boxAlert.loading();

        const body = JSON.stringify({ id });
        const response = await fetch(`${url_base}/eliminar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body,
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo eliminar el registro.');
        }

        boxAlert.box({ h: data.message || 'Registro eliminado correctamente.' });
        updateTable();
    } catch (error) {
        console.error('Error al eliminar registro:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error al eliminar',
            h: error.message || 'Ocurrió un error inesperado. Por favor, intenta nuevamente.'
        });
    }
}