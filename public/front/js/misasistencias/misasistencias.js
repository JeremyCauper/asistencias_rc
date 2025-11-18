$(document).ready(function () {
    $('.modal').on('hidden.bs.modal', function () {
        llenarInfoModal('modalVerJustificacion');
        quillJustificacion.quill.setContents([]); // Limpia el editor
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

const quillJustificacion = new EditorJustificacion('#editor-container');

function justificarAsistencia(id, fecha, hora, tipo_asistencia) {
    try {
        $('#modalJustificacion').modal('show');
        fMananger.formModalLoding('modalJustificacion', 'show');

        let tasistencia = tipoAsistencia[tipo_asistencia] || {
            descripcion: 'Pendiente',
            color: '#9fa6b2'
        };
        window.tasistencia = tasistencia;

        llenarInfoModal('modalJustificacion', {
            fecha: `${fecha} ${(hora || '')}`,
            estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
        });

        $('#id_justificacion').val(id);
        fMananger.formModalLoding('modalJustificacion', 'hide');
    } catch (e) {
        console.log(e);
    }
}

// Captura del formulario
document.getElementById('formJustificacion').addEventListener('submit', async function (e) {
    e.preventDefault();
    fMananger.formModalLoding('modalJustificacion', 'show');

    // Obtiene el contenido HTML del editor
    const contenidoHTML = quillJustificacion.quill.root.innerHTML;

    // Verifica si hay contenido vacío
    if (quillJustificacion.quill.getText().trim().length === 0) {
        boxAlert.box({ i: 'warning', h: 'Por favor, escribe una justificación antes de enviar.' });
        return;
    }

    var valid = validFrom(this);
    if (!valid.success) {
        return fMananger.formModalLoding('modalJustificacion', 'hide');
    }
    valid.data.data.mensaje = utf8ToBase64(contenidoHTML);

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
        quillJustificacion.quill.setContents([]); // Limpia el editor
        this.reset();
        updateTable();
        $('#modalJustificacion').modal('hide');
    } catch (error) {
        fMananger.formModalLoding('modal_tipo_personal', 'hide');
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    } finally {
        fMananger.formModalLoding('modalJustificacion', 'hide');
    }
});

async function showJustificacion(id, fecha, hora, tipo_asistencia) {
    try {
        $('#modalVerJustificacion').modal('show');
        fMananger.formModalLoding('modalVerJustificacion', 'show');

        const response = await fetch(__url + `/justificacion/mostrar/${id}`);
        const result = await response.json();

        if (!response.ok) throw new Error(result.message || 'Error desconocido');
        let data = result.data;

        let tasistencia = tipoAsistencia[tipo_asistencia] || {
            descripcion: 'Pendiente',
            color: '#9fa6b2'
        };

        let estado = [
            { descripcion: 'Pendiente', color: 'secondary' },
            { descripcion: 'Aprobada', color: 'success' },
            { descripcion: 'Rechazada', color: 'danger' },
        ][data.estatus || 0];

        llenarInfoModal('modalVerJustificacion', {
            ver_estatus: `<span class="badge badge-${estado.color} ms-2" style="font-size: 0.75rem;">${estado.descripcion}</span>`,
            ver_fecha_asistencia: `${fecha} ${hora || ''}`,
            ver_tipo_asistencia: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
            ver_creado: 'Creado el ' + data.created_at,
            ver_asunto: data.asunto,
            ver_contenido_html: base64ToUtf8(data.contenido_html)
        });

        fMananger.formModalLoding('modalVerJustificacion', 'hide');
    } catch (error) {
        console.error(error);
        alert(error.message);
    }
}


// Codificar HTML con emojis Binario y después a Base64
function utf8ToBase64(str) {
    // 1. Convertimos el string a bytes (UTF-8)
    const bytes = new TextEncoder().encode(str);
    // 2. Creamos un string binario desde esos bytes
    let binary = '';
    bytes.forEach(b => binary += String.fromCharCode(b));
    // 3. Codificamos el string binario a Base64
    return btoa(binary);
}


// Decodificar Base64 a Binario a HTML con emojis
function base64ToUtf8(base64) {
    // 1. Decodificamos Base64 a un string binario
    const binary = atob(base64);
    // 2. Lo convertimos de binario a bytes
    const bytes = Uint8Array.from(binary, c => c.charCodeAt(0));
    // 3. Lo decodificamos de bytes a string UTF-8
    return new TextDecoder().decode(bytes);
}