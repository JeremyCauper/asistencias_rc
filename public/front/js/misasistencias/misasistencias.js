$(document).ready(function () {
    $('.modal').on('hidden.bs.modal', function () {
        llenarInfoModal('modalVerJustificacion');
        quillJustificarDerivado.clear(); // Limpia el editor
        quilleditorJustificar.clear(); // Limpia el editor
    });

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

    // función auxiliar: recibe "YYYY-MM" y devuelve objeto { year, month } (month 1-12)
    function parseYearMonth(str) {
        if (!str || typeof str !== 'string' || !str.includes('-')) {
            const d = new Date();
            return { year: d.getFullYear(), month: d.getMonth() + 1 };
        }
        const parts = str.split('-');
        const year = parseInt(parts[0], 10) || new Date().getFullYear();
        const month = parseInt(parts[1], 10) || (new Date().getMonth() + 1);
        return { year, month };
    }

    // función auxiliar: formatea {year, month} -> "YYYY-MM"
    function formatYearMonth(year, month) {
        return year + '-' + String(month).padStart(2, '0');
    }

    // ⬅️ Retroceder un mes
    $('#btn-fecha-left').on('click', function () {
        const { year, month } = parseYearMonth($inputFecha.val());
        let newYear = year;
        let newMonth = month - 1;
        if (newMonth < 1) {
            newMonth = 12;
            newYear -= 1;
        }
        const nuevaFecha = formatYearMonth(newYear, newMonth);
        $inputFecha.val(nuevaFecha);
        debounceFiltro();
    });

    // ➡️ Avanzar un mes
    $('#btn-fecha-right').on('click', function () {
        const { year, month } = parseYearMonth($inputFecha.val());
        let newYear = year;
        let newMonth = month + 1;
        if (newMonth > 12) {
            newMonth = 1;
            newYear += 1;
        }
        const nuevaFecha = formatYearMonth(newYear, newMonth);
        $inputFecha.val(nuevaFecha);
        debounceFiltro();
    });

    // Agregar botón de recargar
    $('#tablaMisAsistencias_length').css('display', 'flex').prepend(
        $('<button>', {
            class: 'btn btn-primary px-3 me-2',
            "data-mdb-ripple-init": ''
        }).html('<i class="fas fa-rotate"></i>').on('click', updateTable)
    );
});

const quillJustificarDerivado = new EditorJustificacion('#editor-justificarDerivado', {
    botones: ['camera']
});
const quilleditorJustificar = new EditorJustificacion('#editor-justificar');

function justificarDerivado(id, fecha, hora, tipo_asistencia) {
    try {
        if (!esCelular()) {
            boxAlert.box({
                i: 'info',
                h: 'Esta función está limitada solo a celulares.'
            });
        }
        $('#modalJustificarDerivado').modal('show');
        fMananger.formModalLoding('modalJustificarDerivado', 'show');

        let tasistencia = tipoAsistencia.find(s => s.id == tipo_asistencia)
            || { descripcion: 'Pendiente', color: '#9fa6b2' };
        window.tasistencia = tasistencia;

        llenarInfoModal('modalJustificarDerivado', {
            fecha: `${fecha} ${(hora || '')}`,
            estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
        });

        $('#id_justificacion').val(id);
        fMananger.formModalLoding('modalJustificarDerivado', 'hide');
    } catch (e) {
        console.log(e);
    }
}

// Captura del formulario
document.getElementById('formJustificarDerivado').addEventListener('submit', async function (e) {
    e.preventDefault();
    const msg = `¿Estás de enviar la justificación?`;
    if (!await boxAlert.confirm({ h: msg })) return;
    fMananger.formModalLoding('modalJustificarDerivado', 'show');

    // Verifica si hay contenido vacío
    if (quillJustificarDerivado.isEmpty()) {
        boxAlert.box({ i: 'warning', h: 'Por favor, el contenido no puede estar vacio.' });
        return;
    }

    if (quillJustificarDerivado.isEmptyImg()) {
        boxAlert.box({ i: 'warning', h: 'Tiene que subir minimo una foto.' });
        return;
    }

    var valid = validFrom(this);
    if (!valid.success) {
        return fMananger.formModalLoding('modalJustificarDerivado', 'hide');
    }

    const archivos_data = Object.keys(quillJustificarDerivado.mediaMap || {});
    // Obtiene el contenido HTML del editor
    valid.data.data.mensaje = utf8ToBase64(quillJustificarDerivado.html());
    valid.data.data.archivos = archivos_data;

    try {
        const body = JSON.stringify(valid.data.data);
        const response = await fetch(__url + '/justificacion/responder-justificacion', {
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

        boxAlert.box({ h: data.message || 'Justificación enviada' });
        quillJustificarDerivado.clear(); // Limpia el editor
        this.reset();
        updateTable();
        $('#modalJustificarDerivado').modal('hide');
    } catch (error) {
        fMananger.formModalLoding('modalJustificarDerivado', 'hide');
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    } finally {
        fMananger.formModalLoding('modalJustificarDerivado', 'hide');
    }
});

async function justificarAsistencia(fecha, hora, tipo_asistencia) {
    try {
        $('#modalJustificar').modal('show');
        fMananger.formModalLoding('modalJustificar', 'show');

        let tasistencia = tipoAsistencia.find(s => s.id == tipo_asistencia)
            || { descripcion: 'Pendiente', color: '#9fa6b2' };
        window.tasistencia = tasistencia;

        llenarInfoModal('modalJustificar', {
            fecha: `${fecha} ${(hora || '')}`,
            estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
        });
        window.tasistencia = tasistencia;

        window.fecha = fecha;
        window.tipo_asistencia = tipo_asistencia;
        fMananger.formModalLoding('modalJustificar', 'hide');
    } catch (e) {
        console.log(e);
    }
}

// Captura del formulario
document.getElementById('formJustificar').addEventListener('submit', async function (e) {
    e.preventDefault();
    const msg = `¿Estás de enviar la justificación?`;
    if (!await boxAlert.confirm({ h: msg })) return;
    fMananger.formModalLoding('modalJustificar', 'show');

    // Obtiene el contenido HTML del editor
    const contenidoHTML = quilleditorJustificar.html();

    // Verifica si hay contenido vacío
    if (quilleditorJustificar.isEmpty()) {
        boxAlert.box({ i: 'warning', h: 'Por favor, escribe una justificación antes de enviar.' });
        return;
    }

    var valid = validFrom(this);
    if (!valid.success) {
        return fMananger.formModalLoding('modalJustificar', 'hide');
    }
    let mensaje = utf8ToBase64(contenidoHTML);
    const archivos_data = Object.keys(quilleditorJustificar.mediaMap || {});

    try {
        const body = JSON.stringify({
            fecha: window.fecha,
            tipo_asistencia: window.tipo_asistencia,
            asunto: $('#asunto_justificar').val(),
            contenido: mensaje,
            archivos: archivos_data
        });

        const response = await fetch(__url + '/justificacion/justificar', {
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

        boxAlert.box({ h: data.message || 'Justificación enviada' });
        quilleditorJustificar.clear(); // Limpia el editor
        this.reset();
        updateTable();
        $('#modalJustificar').modal('hide');
    } catch (error) {
        fMananger.formModalLoding('modalJustificar', 'hide');
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    } finally {
        fMananger.formModalLoding('modalJustificar', 'hide');
    }
});

async function showJustificacion(id, fecha, hora, tipo_asistencia) {
    try {
        $('#modalVerJustificacion').modal('show');
        fMananger.formModalLoding('modalVerJustificacion', 'show');

        const res = await $.getJSON(`${__url}/asistencias-diarias/mostrar/${id}`);
        fMananger.formModalLoding('modalJustificacion', 'hide');

        if (!res?.data) {
            return boxAlert.box({
                i: 'error',
                t: 'No se pudo obtener la información',
                h: res.message || 'No se encontraron datos de la asistencia seleccionada.'
            });
        }

        const data = res.data;

        const just = data.justificacion;
        const archivos = data.archivos;

        let tasistencia = tipoAsistencia.find(s => s.id == data.tipo_asistencia)
            || { descripcion: 'Pendiente', color: '#9fa6b2' };

        let estado = [
            { descripcion: 'Pendiente', color: 'secondary' },
            { descripcion: 'Aprobada', color: 'success' },
            { descripcion: 'Rechazada', color: 'danger' },
        ][just.estatus || 0];

        llenarInfoModal('modalVerJustificacion', {
            ver_estatus: `<span class="badge badge-${estado.color} ms-2" style="font-size: 0.75rem;">${estado.descripcion}</span>`,
            ver_fecha_asistencia: `${data.fecha} ${data.hora || ''}`,
            ver_tipo_asistencia: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
            ver_asunto: just.asunto,
            ver_contenido_html: base64ToUtf8(just.contenido_html)
        });
        setMediaUrls('#modalVerJustificacion [aria-item="ver_contenido_html"]', archivos);

        fMananger.formModalLoding('modalVerJustificacion', 'hide');
    } catch (error) {
        fMananger.formModalLoding('modalVerJustificacion', 'hide');
        console.error(error);
        boxAlert.box({
            i: 'error',
            t: 'Error en la solicitud',
            h: 'No se pudo recuperar la información del servidor.'
        });
    }
}