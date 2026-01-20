let fecha_inicio = null;
let fecha_final = null;

$(document).ready(function () {
    fecha_inicio = new MaterialDateTimePicker({
        inputId: 'fecha_inicio',
        mode: 'date',
        min: date('Y-m-01'),
        format: 'MMMM DD de YYYY',
        onConfirm: (time, formatted) => {
            $('#tiempo_contable').val(calcularDiferenciaMesesDias(fecha_inicio.val(), fecha_final.val()));
        }
    });

    fecha_final = new MaterialDateTimePicker({
        inputId: 'fecha_final',
        mode: 'date',
        min: date('Y-m-01'),
        format: 'MMMM DD de YYYY',
        onConfirm: (time, formatted) => {
            $('#tiempo_contable').val(calcularDiferenciaMesesDias(fecha_inicio.val(), fecha_final.val()));
        }
    });

    $('.modal').on('show.bs.modal', function () {
        fecha_inicio.val(date('Y-m-01'));
        fecha_final.val(date('Y-m-d'));
        $('#tiempo_contable').val(calcularDiferenciaMesesDias(fecha_inicio.val(), fecha_final.val()));
    });

    $('.botones-accion').append(
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
});

let nuevoContrato = false;

// 🧍 Para agregar un nuevo contrato
$(document).on('click', '.btnNuevoContrato', function () {
    nuevoContrato = true;
    $('#modalContratos').modal('show');
    $('#personal_id').val($(this).data('id'));
});

$(document).on('click', '.btnEditarContrato', function () {
    nuevoContrato = false;
    $('#modalContratos').modal('show');
    $('#contrato_id').val($(this).data('id'));
});

$('#btnGuardar').on('click', async function () {
    try {
        if (!await boxAlert.confirm({
            t: '¿Estas seguro de guardar los cambios?',
            h: `Se van a agregar <strong>1</strong> y eliminar <strong>1</strong> fechas.`
        })) return;

        fMananger.formModalLoding('modalContratos', 'show');
        const response = await fetch(`${__url}/contratos/registrar`, {
            method: nuevoContrato ? 'POST' : 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body: JSON.stringify({
                id: $('#contrato_id').val(),
                user_id: $('#personal_id').val(),
                fecha_inicio: fecha_inicio.val(),
                fecha_final: fecha_final.val(),
                tipo_contrato: $('#tipo_contrato').val(),
                __token: __token
            }),
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            const mensaje = data.message || 'No se pudo completar la operación.';
            return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
        }

        boxAlert.box({ h: data.message });
        updateTable();
        $('#modalContratos').modal('hide');
    } catch (error) {
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    } finally {
        fMananger.formModalLoding('modalContratos', 'hide');
    }
});