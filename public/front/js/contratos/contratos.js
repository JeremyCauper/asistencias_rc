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
        let mesActual = parseInt(date('m')) + 2;
        fecha_inicio.val(date('Y-m-01'));
        fecha_final.val(date('Y-' + String(mesActual).padStart(2, '0') + '-t'));
        $('#tiempo_contable').val(calcularDiferenciaMesesDias(fecha_inicio.val(), fecha_final.val()));
    });

    $('.botones-accion').append(
        $('<button>', {
            class: 'btn btn-primary px-2',
            "data-mdb-ripple-init": '',
            "role": 'button'
        }).html('<i class="fas fa-rotate-right" style="min-width: 1.25rem;"></i>').on('click', updateTable),
    );
});

let nuevoContrato = false;

// 🧍 Para agregar un nuevo contrato
$(document).on('click', '.btnContratos', async function () {
    nuevoContrato = true;
    const userId = $(this).data('id');
    $('#personal_id').val(userId);
    $('#modalContratos').modal('show');

    // Limpiar y cargar historial
    const tbody = $('#tablaHistorialContratos');
    tbody.html('<tr><td colspan="5" class="text-center"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>');

    try {
        const response = await fetch(`${__url}/contratos/listar-usuario/${userId}`);
        const contratos = await response.json();

        tbody.empty();

        if (contratos.length === 0) {
            tbody.html('<tr><td colspan="5" class="text-center text-muted">Sin historial de contratos</td></tr>');
            return;
        }

        if (!contratos.length) return;
        contratos.forEach(c => {
            const tipo = ['Contrato', 'Permanente', 'Por Proyecto'][c.tipo_contrato] || 'Desconocido';

            tbody.append(`
                <tr>
                    <td>${tipo}</td>
                    <td>${c.fecha_inicio || '-'}</td>
                    <td>${c.fecha_fin || '-'}</td>
                    <td>${getDiasRestantes(c.fecha_fin)}</td>
                    <td class="small text-muted">${c.created_at || '-'}</td>
                </tr>
            `);
        });

        let ultimoContrato = contratos.at(-1);
        let fecha_base = ultimoContrato?.fecha_fin || date('Y-m-01');
        let sumarDate = (fechaStr, op = {}) => {
            const d = new Date(fechaStr + 'T00:00:00');
            if (op.days) d.setDate(d.getDate() + op.days);
            if (op.months) d.setMonth(d.getMonth() + op.months);
            if (op.years) d.setFullYear(d.getFullYear() + op.years);
            return d;
        }

        fecha_inicio.val(date('Y-m-d', sumarDate(fecha_base, { days: 1 })));

        fecha_final.val(date('Y-m-t', sumarDate(fecha_inicio.val(), { months: 2 })));

        $('#tiempo_contable').val(calcularDiferenciaMesesDias(fecha_inicio.val(), fecha_final.val()));
    } catch (error) {
        console.error(error);
        tbody.html('<tr><td colspan="5" class="text-center text-danger">Error al cargar historial</td></tr>');
    }
});

$('#btnGuardar').on('click', async function () {
    try {
        if (!await boxAlert.confirm({
            t: '¿Estas seguro de guardar los cambios?',
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