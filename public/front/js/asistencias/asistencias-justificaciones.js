
$(document).ready(function () {
    const quillRespJustificacion = new EditorJustificacion('#respuesta-justificacion');

    /** ============================
     *  🔹 CONFIGURACIONES GLOBALES
     *  ============================ */
    const ESTADOS_JUSTIFICACION = [
        { descripcion: 'Pendiente', color: 'secondary' },
        { descripcion: 'Aprobado', color: 'success' },
        { descripcion: 'Rechazado', color: 'danger' },
    ];


    /** ============================
     *  🔹 VER JUSTIFICACIÓN
     *  ============================ */
    window.verJustificacion = async (id) => {
        try {
            $('#modalJustificacion').modal('show');
            fMananger.formModalLoding('modalJustificacion', 'show');

            const res = await $.getJSON(`${__url}/justificacion/mostrar/${id}`);
            fMananger.formModalLoding('modalJustificacion', 'hide');

            if (!res?.data) {
                return boxAlert.box({
                    i: 'error',
                    t: 'No se pudo obtener la información',
                    h: res.message || 'No se encontraron datos de la asistencia seleccionada.'
                });
            }

            const data = res.data;
            console.log(data);

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
                fecha: `${just.fecha} ${data.hora || ''}`,
                tipo_asistencia: badgeHtml(tasistencia.color, tasistencia.descripcion, true),
                asunto: just.asunto,
                contenido_html: contenidoHTML
            });

            window.currentJustificacionId = just.id;
            window.currentJustificacionStatus = just.estatus;
            if (just.estatus === 0) {
                $('#responderJustificacion').slideDown();
            } else {
                $('#responderJustificacion').slideUp();
            }
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
                $('#responderJustificacion').slideUp();
                return;
            }

            const estado = ESTADOS_JUSTIFICACION[estatus || 0];
            const textoEditor = quillRespJustificacion.quill.getText().trim();
            const contenidoHTMLResp = quillRespJustificacion.quill.root.innerHTML;

            if (!textoEditor && estatus === 2) {
                return boxAlert.box({ i: 'warning', h: 'Escribe una respuesta antes de enviar.' });
            }

            const mensaje = utf8ToBase64(contenidoHTMLResp);

            boxAlert.loading();
            const id = window.currentJustificacionId;
            const res = await fetch(`${__url}/asistencias/justificaciones/${id}/estatus`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": __token
                },
                body: JSON.stringify({ estatus, mensaje }),
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
            $('#responderJustificacion').slideUp();
            updateTable();
        } catch (err) {
            console.error(err);
            boxAlert.box({ i: 'error', h: err.message || "No se pudo actualizar el estado." });
        }
    };
});