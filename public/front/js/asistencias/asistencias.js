$(document).ready(function () {
    configControls([
        // Formulario problemas
        {
            control: '#monto_descuento',
            addLabel: 'Monto del Descuento (S/)',
            type: 'number',
            requested: true
        },
        {
            control: '#comentario',
            addLabel: 'Comentario (opcional)'
        }
    ]);

    fObservador('.content-wrapper', () => {
        if (!esCelular()) {
            tablaAsistencias.columns.adjust().draw();
        }

        incidencia_estados.forEach((e, i) => {
            if (e.chart) e.chart.resize();
        });
    });

    $('.botones-accion').append(
        $('<button>', {
            class: 'btn btn-primary px-3 me-2',
            "data-mdb-ripple-init": ''
        }).html('<i class="fas fa-rotate"></i>').on('click', updateTable)
    );

    // eventos
    var $inputFecha = $('#filtro_fecha');
    var debounceTimer = null;

    // ⏳ Esperar 500 ms antes de recargar (debounce)
    function debounceFiltro() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(filtroBusqueda, 500);
    }

    // 📅 Detectar cambio manual de fecha
    $inputFecha.on('change', function () {
        debounceFiltro();
    });

    // ⬅️ Retroceder un día
    $('#btn-fecha-left').on('click', function () {
        var fecha = new Date($inputFecha.val());
        fecha.setDate(fecha.getDate() - 1);
        var nuevaFecha = fecha.toISOString().split('T')[0];
        $inputFecha.val(nuevaFecha);
        debounceFiltro();
    });

    // ➡️ Avanzar un día
    $('#btn-fecha-right').on('click', function () {
        var fecha = new Date($inputFecha.val());
        fecha.setDate(fecha.getDate() + 1);
        var nuevaFecha = fecha.toISOString().split('T')[0];
        $inputFecha.val(nuevaFecha);
        debounceFiltro();
    });
});

const url_base = `${__url}/asistencias-diarias`;

/**
 * Mostrar modal de descuento con datos del personal
 */
async function modificarDescuento(id) {
    try {
        $('#modalDescuento').modal('show');
        fMananger.formModalLoding('modalDescuento', 'show');

        const response = await fetch(`${url_base}/mostrar/${id}`, { method: 'GET' });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo obtener la información solicitada.');
        }

        if (!data.data) {
            throw new Error('No se encontró el registro solicitado. Puede que haya sido eliminado.');
        }

        const json = data.data;
        // Buscar descripción del tipo de asistencia
        const tasistencia = tipoAsistencia.find(s => s.id == json.tipo_asistencia) || {
            descripcion: 'Pendiente',
            color: '#9fa6b2'
        };

        // Llenar campos visibles
        llenarInfoModal('modalDescuento', {
            personal: `${json.personal.dni} - ${json.personal.nombre} ${json.personal.apellido}`,
            fecha: `${json.fecha} ${(json.entrada || '')}`,
            estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`
        });

        // Llenar campos ocultos
        $('#user_id').val(json.user_id);
        $('#fecha').val(json.fecha);

        // Rellenar datos del descuento si existen
        $('#monto_descuento').val(json.descuento?.monto_descuento ?? '');
        $('#comentario').val(json.descuento?.comentario ?? '');

        fMananger.formModalLoding('modalDescuento', 'hide');
    } catch (error) {
        console.error('Error al cargar registro:', error);

        boxAlert.box({
            i: 'error',
            t: 'No pudimos obtener la información',
            h: error.message || 'Ocurrió un error inesperado. Por favor, intenta nuevamente.'
        });

        fMananger.formModalLoding('modalDescuento', 'hide');
    }
}

/**
 * Guardar o actualizar descuento
 */
$('#form-descuento').on('submit', async function (e) {
    e.preventDefault();

    fMananger.formModalLoding('modalDescuento', 'show');

    try {
        const body = JSON.stringify({
            user_id: $('#user_id').val(),
            fecha: $('#fecha').val(),
            monto_descuento: $('#monto_descuento').val(),
            comentario: $('#comentario').val() || null
        });
        const response = await fetch(`${url_base}/ingresar-descuento`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body,
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            const mensaje = data.message || 'No se pudo completar la operación.';
            return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
        }

        boxAlert.box({ h: data.message });
        updateTable();
    } catch (error) {
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    } finally {
        fMananger.formModalLoding('modalDescuento', 'hide');
    }
});

async function marcarDerivado(id) {
    try {
        const confirm = await boxAlert.confirm({ h: `¿Deseas marcar esta asistencia como DERIVADA?` });
        if (!confirm) return;

        boxAlert.loading();
        const res = await fetch(`${__url}/justificacion/marcar-derivado/${id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": __token
            },
            body: JSON.stringify({ derivado: true }) // opcional, si deseas enviar algún valor
        });

        const data = await res.json();

        if (!res.ok || !data.success) {
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