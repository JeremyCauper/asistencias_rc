$(document).ready(function () {
    const quillRespJustificacion = new EditorJustificacion('#respuesta-justificacion');
    const quilleditorJustificar = new EditorJustificacion('#editor-justificar');

    $('.modal').on('hidden.bs.modal', function () {
        llenarInfoModal('modalJustificacion');
        quillRespJustificacion.clear(); // Limpia el editor
        quilleditorJustificar.clear(); // Limpia el editor
    });

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
            $('#responderJustificacion').fadeOut();
            $('#modalJustificacion').modal('show');
            fMananger.formModalLoding('modalJustificacion', 'show');

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
            const personal = data.personal;
            const archivos = data.archivos;

            if (just.estatus === 0) {
                $('#responderJustificacion').slideDown();
            }

            if (!just) {
                return boxAlert.box({
                    i: 'warning',
                    t: 'Sin justificación',
                    h: 'El personal no tiene una justificación pendiente.'
                });
            }

            const tasistencia = tipoAsistencia.find(s => s.id == data.tipo_asistencia)
                || { descripcion: 'Pendiente', color: '#717883' };

            const estado = ESTADOS_JUSTIFICACION[just.estatus || 0];
            const contenidoHTML = base64ToUtf8(just.contenido_html);

            llenarInfoModal('modalJustificacion', {
                estado: badgeHtml(estado.color, estado.descripcion),
                personal: `${personal?.dni ?? ''} - ${personal?.nombre ?? ''} ${personal?.apellido ?? ''}`,
                fecha: `${just.fecha} ${data.entrada || ''}`,
                tipo_asistencia: badgeHtml(tasistencia.color, tasistencia.descripcion, true),
                asunto: just.asunto,
                contenido_html: contenidoHTML
            });
            setMediaUrls('#modalJustificacion [aria-item="contenido_html"]', archivos);

            window.currentAsistenciaId = id;
            window.currentJustificacionStatus = just.estatus;
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
            let contenidoHTMLResp = quillRespJustificacion.html();

            if (quillRespJustificacion.isEmpty() && estatus === 2) {
                return boxAlert.box({ i: 'warning', h: 'Escribe una respuesta antes de enviar.' });
            }

            if (quillRespJustificacion.isEmpty() && estatus === 1) {
                contenidoHTMLResp = `<p>✅ La justificación ha sido <b>aprobada</b>.</p>`;
            }

            const mensaje = utf8ToBase64(contenidoHTMLResp);

            boxAlert.loading();
            const archivos_data = Object.keys(quillRespJustificacion.mediaMap || {});
            const res = await fetch(__url + '/justificacion/responder-justificacion/admin', {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": __token
                },
                body: JSON.stringify({
                    id_asistencia: window.currentAsistenciaId,
                    estatus,
                    mensaje,
                    archivos: archivos_data
                }),
            });

            const data = await res.json();
            if (!res.ok) throw new Error(data.message || "Error al actualizar el estado");

            const resp = data.data;
            const tasistencia = tipoAsistencia.find(s => s.id == resp.tipo_asistencia)
                || { descripcion: 'Pendiente', color: '#717883' };

            llenarInfoModal('modalJustificacion', {
                estado: badgeHtml(estado.color, estado.descripcion),
                tipo_asistencia: badgeHtml(tasistencia.color, tasistencia.descripcion, true),
                contenido_html: base64ToUtf8(resp.contenido)
            });
            setMediaUrls('#modalJustificacion [aria-item="contenido_html"]', resp.archivos);

            boxAlert.box({ h: data.message });
            window.noti.cargar();
            $('#responderJustificacion').slideUp();
            updateTable();
        } catch (err) {
            console.error(err);
            boxAlert.box({ i: 'error', h: err.message || "No se pudo actualizar el estado." });
        }
    };

    window.justificarAsistencia = async (id) => {
        try {
            boxAlert.loading();
            const endpoint = await fetch(`${__url}/asistencias-diarias/mostrar/${id}`);
            const response = await endpoint.json();
            const data = response.data;
            const just = data.justificacion;

            if (!response?.data) {
                return boxAlert.box({
                    i: 'error',
                    t: 'No se pudo obtener la información',
                    h: response.message || 'No se encontraron datos de la asistencia seleccionada.'
                });
            }

            if (just && [10].includes(just?.estatus) && data.tipo_asistencia == 0) {
                updateTable();
                return boxAlert.box({
                    i: 'info',
                    h: 'Ya existe una justificación pendiente de derivación directa.'
                });
            }

            Swal.close();
            $('#modalJustificar').modal('show');
            fMananger.formModalLoding('modalJustificar', 'show');

            let tasistencia = tipoAsistencia.find(s => s.id == data.tipo_asistencia)
                || { descripcion: 'Pendiente', color: '#717883' };
            window.tasistencia = tasistencia;

            llenarInfoModal('modalJustificar', {
                fecha: `${data.fecha} ${(data.entrada || '')}`,
                estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
            });
            window.tasistencia = tasistencia;

            window.user_id = data.user_id;
            window.tipo_asistencia = data.tipo_asistencia;
            window.currentAsistenciaId = id;
            fMananger.formModalLoding('modalJustificar', 'hide');
        } catch (e) {
            Swal.close();
            console.error(e);
        }
    }

    // Captura del formulario
    document.getElementById('formJustificar').addEventListener('submit', async function (e) {
        e.preventDefault();
        // Verifica si hay contenido vacío
        if (quilleditorJustificar.isEmpty()) {
            boxAlert.box({ i: 'warning', h: 'Por favor, escribe una justificación antes de enviar.' });
            return;
        }

        const msg = `¿Estás de enviar la justificación?`;
        if (!await boxAlert.confirm({ h: msg })) return;

        // Obtiene el contenido HTML del editor
        const contenidoHTML = quilleditorJustificar.html();

        fMananger.formModalLoding('modalJustificar', 'show');

        var valid = validFrom(this);
        if (!valid.success) {
            return fMananger.formModalLoding('modalJustificar', 'hide');
        }
        let mensaje = utf8ToBase64(contenidoHTML);
        const archivos_data = Object.keys(quilleditorJustificar.mediaMap || {});

        try {
            const body = JSON.stringify({
                id_asistencia: window.currentAsistenciaId,
                user_id: window.user_id,
                tipo_asistencia: window.tipo_asistencia,
                asunto: $('#asunto').val(),
                contenido: mensaje,
                archivos: archivos_data,
                estatus: 1
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
});