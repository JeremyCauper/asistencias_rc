$(document).ready(function () {

    /** ============================
     *  🔹 CONFIGURACIONES GLOBALES
     *  ============================ */
    const ESTADOS_JUSTIFICACION = [
        { descripcion: 'Pendiente', color: 'secondary' },
        { descripcion: 'Aprobado', color: 'success' },
        { descripcion: 'Rechazado', color: 'danger' },
    ];

    /** ============================
     *  🔹 INICIALIZAR QUILL
     *  ============================ */
    const quill = new Quill('#editor-container', {
        theme: 'snow',
        modules: {
            toolbar: {
                container: [
                    ['bold', 'italic', 'underline'],
                    [{ 'header': [1, 2, false] }],
                    ['link', 'image', 'video', 'pdf'],
                    [{ 'list': 'ordered' }, { 'list': 'bullet' }]
                ],
                handlers: {
                    image: () => handleFileUpload('image', 'image/*', 3),
                    video: () => handleFileUpload('video', 'video/*', 10),
                    pdf:   () => handleFileUpload('pdf', 'application/pdf', 5),
                }
            }
        }
    });

    customizeToolbarIcons({
        link: 'link',
        image: 'file-image',
        video: 'file-video',
        pdf: 'file-pdf'
    });

    /** ============================
     *  🔹 FUNCIONES AUXILIARES
     *  ============================ */

    // ✅ Personaliza íconos del toolbar
    function customizeToolbarIcons(icons) {
        for (const [key, icon] of Object.entries(icons)) {
            const button = document.querySelector(`.ql-${key}`);
            if (button) button.innerHTML = `<i class="far fa-${icon}"></i>`;
        }
    }

    // ✅ Maneja subida de archivos de cualquier tipo
    async function handleFileUpload(tipo, accept, maxMB) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = accept;
        input.click();

        input.onchange = async () => {
            const file = input.files[0];
            if (!file) return;

            const limite = maxMB * 1024 * 1024;
            if (file.size > limite) {
                return alert(`Máximo ${maxMB}MB para ${tipo}s`);
            }

            await uploadFile(file, tipo);
        };
    }

    // ✅ Subida de archivo al backend
    async function uploadFile(file, tipo) {
        try {
            boxAlert.loading('Subiendo documento...');
            const formData = new FormData();
            formData.append('file', file);

            const res = await fetch(`${__url}/asistencias/uploadMedia`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': __token },
                body: formData
            });

            const data = await res.json();
            if (!data.url) throw new Error('Error subiendo archivo');

            const range = quill.getSelection(true);
            const fullUrl = location.origin + data.url;

            insertFileInEditor(tipo, fullUrl, file.name, range.index);
        } catch (error) {
            console.error(error);
            boxAlert.box({ i: 'error', h: 'No se pudo subir el archivo.' });
        } finally {
            Swal.close();
        }
    }

    // ✅ Inserta contenido en el editor según tipo
    function insertFileInEditor(tipo, url, fileName, index) {
        const inserciones = {
            image: () => quill.insertEmbed(index, 'image', url),
            video: () => quill.insertEmbed(index, 'video', url),
            pdf:   () => quill.clipboard.dangerouslyPasteHTML(index, `<a href="${url}" target="_blank">📄${fileName}</a>`)
        };
        inserciones[tipo]?.();
    }

    /** ============================
     *  🔹 VER JUSTIFICACIÓN
     *  ============================ */
    window.verJustificacion = async (id) => {
        try {
            $('#modalJustificacion').modal('show');
            fMananger.formModalLoding('modalJustificacion', 'show');

            const res = await $.getJSON(`${__url}/asistencias/asistencias/${id}`);
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
            const personal = data.personal;

            if (!just) {
                return boxAlert.box({
                    i: 'warning',
                    t: 'Sin justificación',
                    h: 'El personal no tiene una justificación pendiente.'
                });
            }

            const tasistencia = tipoAsistencia.find(s => s.id == just.tipo_asistencia)
                || { descripcion: 'Pendiente', color: '#9fa6b2' };

            const estado = ESTADOS_JUSTIFICACION[just.estatus || 0];
            const contenidoHTML = decodeHtmlContent(just.contenido_html);

            llenarInfoModal('modalJustificacion', {
                estado: badgeHtml(estado.color, estado.descripcion),
                personal: `${personal?.dni ?? ''} - ${personal?.nombre ?? ''} ${personal?.apellido ?? ''}`,
                fecha: `${data.fecha} ${data.hora || ''}`,
                tipo_asistencia: badgeHtml(tasistencia.color, tasistencia.descripcion, true),
                asunto: just.asunto,
                contenido_html: contenidoHTML
            });

            window.currentJustificacionId = just.id;
            window.currentJustificacionStatus = just.estatus;
            toggleResponder(just.estatus === 0);
        } catch (error) {
            fMananger.formModalLoding('modalJustificacion', 'hide');
            console.error(error);
            boxAlert.box({
                i: 'error',
                t: 'Error en la solicitud',
                h: 'No se pudo recuperar la información del servidor.'
            });
        }
    };

    function decodeHtmlContent(content) {
        try {
            const decoded = base64ToUtf8(content);
            window.contenido_HTML = decoded;
            return decoded;
        } catch {
            return '<em class="text-danger">Error al decodificar el contenido.</em>';
        }
    }

    function badgeHtml(color, text, customColor = false) {
        return customColor
            ? `<span class="badge" style="font-size: 0.75rem; background-color: ${color};">${text}</span>`
            : `<span class="badge badge-${color} ms-2" style="font-size: 0.75rem;">${text}</span>`;
    }

    function toggleResponder(show) {
        $('#responderJustificacion').slideToggle(show);
    }

    /** ============================
     *  🔹 MANEJO DE ESTADO (Aprob/Rechaz)
     *  ============================ */
    $('#btnAprobar').on('click', () => procesarJustificacion(1, 'aprobar'));
    $('#btnRechazar').on('click', () => procesarJustificacion(2, 'rechazar'));

    async function procesarJustificacion(estatus, accion) {
        const msg = `Estás a punto de ${accion} esta justificación`;
        if (!await boxAlert.confirm({ h: msg })) return;
        await actualizarEstatusJustificacion(estatus);
    }

    /** ============================
     *  🔹 ACTUALIZAR ESTADO
     *  ============================ */
    window.actualizarEstatusJustificacion = async (estatus) => {
        try {
            if (window.currentJustificacionStatus !== 0) {
                boxAlert.box({ i: 'info', h: 'No se puede continuar, la justificación no está pendiente.' });
                toggleResponder(false);
                return;
            }

            const estado = ESTADOS_JUSTIFICACION[estatus || 0];
            const textoEditor = quill.getText().trim();
            const contenidoHTMLResp = quill.root.innerHTML;

            if (!textoEditor && estatus === 2) {
                return boxAlert.box({ i: 'warning', h: 'Escribe una respuesta antes de enviar.' });
            }

            const htmlCorreo = generarPlantillaCorreo(estado, contenidoHTMLResp);
            const contenidoHTML = utf8ToBase64(htmlCorreo);

            boxAlert.loading();
            const id = window.currentJustificacionId;
            const res = await fetch(`${__url}/asistencias/justificaciones/${id}/estatus`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": __token
                },
                body: JSON.stringify({ estatus, contenidoHTML }),
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || "Error al actualizar el estado");

            const resp = data.data;
            const tasistencia = tipoAsistencia.find(s => s.id == resp.tipo_asistencia)
                || { descripcion: 'Pendiente', color: '#9fa6b2' };

            llenarInfoModal('modalJustificacion', {
                estado: badgeHtml(estado.color, estado.descripcion),
                tipo_asistencia: badgeHtml(tasistencia.color, tasistencia.descripcion, true),
                contenido_html: htmlCorreo
            });

            boxAlert.box({ h: data.message });
            toggleResponder(false);
            updateTable();
        } catch (err) {
            console.error(err);
            boxAlert.box({ i: 'error', h: err.message || "No se pudo actualizar el estado." });
        }
    };

    /** ============================
     *  🔹 GENERAR PLANTILLA CORREO
     *  ============================ */
    function generarPlantillaCorreo(estado, contenidoHTMLResp) {
        const fecha = new Date().toLocaleDateString('es-PE', { year: 'numeric', month: 'long', day: 'numeric' });
        const hora = new Date().toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const tpersonal = tipoPersonal[tipoUsuario] || { descripcion: 'Técnico', color: '#9fa6b2' };

        return `
            <div class="p-3">
                <div class="d-flex align-items-center mb-3">
                    <span class="img-xs rounded-circle text-white acronimo" style="background-color: ${acronimo_bg} !important;">${acronimo}</span>
                    <div class="ms-2"><p class="fw-bold mb-1">${nomUsuario}</p></div>
                    <span class="badge rounded-pill ms-auto" style="background-color: ${tpersonal.color} !important;font-size: .7rem;">${tpersonal.descripcion}</span>
                </div>
                <p>📅 <small class="fw-bold">Fecha de creación:</small> ${fecha} a las ${hora}</p>
                <p class="mt-1">✉️ Justificación <span class="fw-bold text-${estado.color}">${estado.descripcion}</span></p>
                ${quill.getText().trim().length ? '<hr>' : ''}
                <div>${contenidoHTMLResp}</div>
                <hr class="mb-0">
            </div>
            ${window.contenido_HTML}
        `;
    }
});