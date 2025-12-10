$(document).ready(function () {
    configControls([
        { control: '#simbolo', mxl: 5, requested: true },
        { control: '#descripcion', mxl: 50, requested: true },
        { control: '#color', type: 'color', requested: true },
        { control: ['#estado'], requested: true },
    ]);

    formatSelect('modal_tipo_asistencia');

    $('.modal').on('hidden.bs.modal', function () {
        $('#modal_tipo_asistenciaLabel').html('REGISTRAR TIPO ASISTENCIA');
        $('#id').val('');
    });

    fObservador('.content-wrapper', () => {
        tb_tipo_asistencia.columns.adjust().draw();
    });
});

function updateTable() {
    tb_tipo_asistencia.ajax.reload();
}

const url_base = `${__url}/mantenimiento-dev/tipo-asistencia`;
mostrar_acciones(tb_tipo_asistencia);

// ======================================================
// GUARDAR / ACTUALIZAR
// ======================================================
$('#form-tipo-asistencia').on('submit', async function (event) {
    event.preventDefault();

    fMananger.formModalLoding('modal_tipo_asistencia', 'show');

    const valid = validFrom(this);
    if (!valid.success) {
        fMananger.formModalLoding('modal_tipo_asistencia', 'hide');
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
        fMananger.formModalLoding('modal_tipo_asistencia', 'hide');

        if (!response.ok || !data.success) {
            const mensaje = data.message || 'No se pudo completar la operación.';
            return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
        }

        $('#modal_tipo_asistencia').modal('hide');
        boxAlert.box({ h: data.message || 'Operación realizada con éxito.' });
        updateTable();
    } catch (error) {
        fMananger.formModalLoding('modal_tipo_asistencia', 'hide');
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
        $('#modal_tipo_asistenciaLabel').html('EDITAR TIPO ASISTENCIA');
        $('#modal_tipo_asistencia').modal('show');
        fMananger.formModalLoding('modal_tipo_asistencia', 'show');

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
        $('#simbolo').val(json.simbolo);
        $('#descripcion').val(json.descripcion);
        $('#color').val(json.color);
        $('#estado').val(json.estatus).trigger('change');

        fMananger.formModalLoding('modal_tipo_asistencia', 'hide');
    } catch (error) {
        console.error('Error al cargar registro:', error);

        boxAlert.box({
            i: 'error',
            t: 'No pudimos obtener la información',
            h: error.message || 'Ocurrió un error inesperado. Por favor, intenta nuevamente.'
        });

        fMananger.formModalLoding('modal_tipo_asistencia', 'hide');
    }
}

// ======================================================
// CAMBIAR ESTADO
// ======================================================
async function CambiarEstado(id, estado) {
    try {
        const confirm = await boxAlert.confirm({
            h: `¿Está seguro de ${estado ? 'desactivar' : 'activar'} este tipo de asistencia?`
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
// ELIMINAR
// ======================================================
async function Eliminar(id) {
    try {
        const confirm = await boxAlert.confirm({
            h: '¿Está seguro de eliminar este tipo de asistencia? Esta acción no se puede deshacer.'
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