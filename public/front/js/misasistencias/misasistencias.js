$(document).ready(function () {
    $('.modal').on('hidden.bs.modal', function () {
        quill.setContents([]); // Limpia el editor
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

const quill = new Quill('#editor-container', {
    theme: 'snow',
    modules: {
        toolbar: {
            container: [
                ['bold', 'italic', 'underline'],
                [{
                    'header': [1, 2, false]
                }],
                ['link', 'image', 'video', 'pdf'],
                [{
                    'list': 'ordered'
                }, {
                    'list': 'bullet'
                }]
            ],
            handlers: {
                'image': subirImagen,
                'video': subirVideo,
                'pdf': subirPDF
            }
        }
    }
});

// const toolbar = quill.getModule('toolbar');
for (const [key, value] of Object.entries({
    link: 'link',
    image: 'file-image',
    video: 'file-video',
    pdf: 'file-pdf'
})) {
    const customButton = document.querySelector('.ql-' + key);
    if (customButton) customButton.innerHTML = `<i class="far fa-${value}"></i>`; // emoji o ícono custom
}

// Subir imagen
function subirImagen() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'image/*';
    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        if (file.size > 3 * 1024 * 1024) {
            alert('Máximo 3MB para imágenes');
            return;
        }
        await uploadFile(file, 'image');
    };
}

// Subir video
function subirVideo() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'video/*';
    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        if (file.size > 10 * 1024 * 1024) {
            alert('Máximo 10MB para videos');
            return;
        }
        await uploadFile(file, 'video');
    };
}

// Subir PDF
function subirPDF() {
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'application/pdf';
    input.click();

    input.onchange = async () => {
        const file = input.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('Máximo 5MB para PDF');
            return;
        }
        await uploadFile(file, 'pdf');
    };
}

// Subir al backend
async function uploadFile(file, tipo) {
    try {
        boxAlert.loading('Subiendo documento...')
        const formData = new FormData();
        formData.append('file', file);

        const res = await fetch(__url + "/asistencias/uploadMedia", {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': __token
            },
            body: formData
        });

        const data = await res.json();
        if (data.url) {
            const range = quill.getSelection(true);

            if (tipo === 'image') {
                quill.insertEmbed(range.index, 'image', location.origin + data.url);
            } else if (tipo === 'video') {
                quill.insertEmbed(range.index, 'video', location.origin + data.url);
            } else if (tipo === 'pdf') {
                // Insertar como enlace o iframe pequeño
                quill.insertEmbed(range.index, 'text', '');
                quill.clipboard.dangerouslyPasteHTML(range.index, `<a href="${location.origin + data.url}" target="_blank">📄${file.name}</a>`);
            }
        } else {
            alert('Error subiendo archivo');
        }
    } catch (error) {
        console.log(error);
    } finally {
        Swal.close()
    }
}

function solicitarJustificacion(id, fecha, hora, tipo_asistencia) {
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

        $('#fecha_justi').val(fecha);
        $('#tipo_asistencia_justi').val(tipo_asistencia);
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
    const contenidoHTML = quill.root.innerHTML;

    // Verifica si hay contenido vacío
    if (quill.getText().trim().length === 0) {
        boxAlert.box({ i: 'warning', h: 'Por favor, escribe una justificación antes de enviar.' });
        return;
    }

    var valid = validFrom(this);
    if (!valid.success) {
        return fMananger.formModalLoding('modalJustificacion', 'hide');
    }

    const fechaActual = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
    const horaActual = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const horaCreated = date('Y-m-d H:i:s');

    let tpersonal = tipoPersonal[tipoUsuario] || { descripcion: 'Tecnico', color: '#9fa6b2' };

    // Plantilla HTML estilo correo
    const htmlCorreo = `
        <div class="p-3">
            <div class="d-flex align-items-center mb-3">
                <span class="img-xs rounded-circle text-white acronimo" style="background-color: ${acronimo_bg} !important;">${acronimo}</span>
                <div class="ms-2">
                    <p class="fw-bold mb-1">${nomUsuario}</p>
                </div>
                <span class="badge rounded-pill ms-auto" style="background-color: ${tpersonal.color} !important;font-size: .7rem;">${tpersonal.descripcion}</span>
            </div>
            <p>📅 <small class="fw-bold">Fecha de creación:</small> ${fechaActual} a las ${horaActual}</p>
            <p class="mt-1">✉️ Justificación de <span class="fw-bold" style="color: ${window.tasistencia.color};">${window.tasistencia.descripcion}</span></p>
            <hr>
            <div>${contenidoHTML}</div>
            <hr class="mb-0">
        </div>
        <div class="px-3 py-2 text-end">Sistema de Control de Asistencia del Personal</div>
    `;
    valid.data.data.contenidoHTML = utf8ToBase64(htmlCorreo);
    valid.data.data.created = horaCreated;
    console.log(valid.data.data);

    try {
        const body = JSON.stringify(valid.data.data);
        const response = await fetch(__url + '/asistencias/justificaciones', {
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
        quill.setContents([]); // Limpia el editor
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

async function obtenerJustificacion(fecha, hora, tipo_asistencia) {
    try {
        $('#modalVerJustificacion').modal('show');
        fMananger.formModalLoding('modalVerJustificacion', 'show');

        const response = await fetch(__url + `/asistencias/justificaciones/${fecha}`);
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