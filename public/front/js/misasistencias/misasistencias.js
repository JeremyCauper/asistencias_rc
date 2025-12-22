$(document).ready(function () {
    const quillJustificarDerivado = new EditorJustificacion('#editor-justificarDerivado', {
        noPasteImg: true,
        botones: ['link', 'camera']
    });
    const quilleditorJustificar = new EditorJustificacion('#editor-justificar');

    fObservador('.content-wrapper', () => {
        if (!esCelular()) {
            tablaMisAsistencias.columns.adjust().draw();
        }

        incidencia_estados.forEach((e, i) => {
            if (e.chart) e.chart.resize();
        });
    });


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

    window.justificarDerivado = async (id) => {
        try {
            if (!esCelular()) {
                return boxAlert.box({
                    i: 'info',
                    h: 'Acción disponible solo en dispositivos móviles.'
                });
            }

            $('#modalJustificarDerivado').modal('show');
            fMananger.formModalLoding('modalJustificarDerivado', 'show');

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

            if (!data.is_derivado) {
                return justificarAsistencia(id);
            }

            let tasistencia = tipoAsistencia.find(s => s.id == data.tipo_asistencia)
                || { descripcion: 'Pendiente', color: '#959595' };

            llenarInfoModal('modalJustificarDerivado', {
                fecha: `${data.fecha} ${(data.entrada || '')}`,
                estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
            });
            window.currentAsistenciaId = id;
            $('#asunto').val('Justificación de Asistencia Derivada');
            fMananger.formModalLoding('modalJustificarDerivado', 'hide');
        } catch (error) {
            fMananger.formModalLoding('modalJustificarDerivado', 'hide');
            console.error(error);
            boxAlert.box({
                i: 'error',
                t: 'Error en la solicitud',
                h: 'No se pudo recuperar la información del servidor.'
            });
        }
    }

    // Captura del formulario
    document.getElementById('formJustificarDerivado').addEventListener('submit', async function (e) {
        e.preventDefault();
        // Verifica si hay contenido vacío
        if (quillJustificarDerivado.isEmpty()) {
            boxAlert.box({ i: 'warning', h: 'Por favor, el contenido no puede estar vacio.' });
            return;
        }

        if (quillJustificarDerivado.isEmptyImg()) {
            boxAlert.box({ i: 'warning', h: 'Tiene que subir minimo una foto.' });
            return;
        }

        const msg = `¿Estás de enviar la justificación?`;
        if (!await boxAlert.confirm({ h: msg })) return;

        fMananger.formModalLoding('modalJustificarDerivado', 'show');

        var valid = validFrom(this);
        if (!valid.success) {
            return fMananger.formModalLoding('modalJustificarDerivado', 'hide');
        }

        const archivos_data = Object.keys(quillJustificarDerivado.mediaMap || {});
        // Obtiene el contenido HTML del editor
        valid.data.data.mensaje = utf8ToBase64(quillJustificarDerivado.html());
        valid.data.data.archivos = archivos_data;
        valid.data.data.id_asistencia = window.currentAsistenciaId;

        try {
            const body = JSON.stringify(valid.data.data);
            const response = await fetch(__url + '/justificacion/responder-justificacion/usuario', {
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
                console.error(data);
                return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
            }

            boxAlert.box({ h: data.message || 'Justificación enviada' });
            quillJustificarDerivado.clear(); // Limpia el editor
            this.reset();
            window.noti.cargar();
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

            if (!esCelular() && [0, 1].includes(data.tipo_asistencia) && ([2].includes(data.tipo_modalidad) || [10].includes(just?.estatus))) {
                return boxAlert.box({
                    i: 'info',
                    h: 'Acción disponible solo en dispositivos móviles.'
                });
            }

            Swal.close();
            $('#modalJustificar').modal('show');
            fMananger.formModalLoding('modalJustificar', 'show');

            let tasistencia = tipoAsistencia.find(s => s.id == data.tipo_asistencia)
                || { descripcion: 'Pendiente', color: '#959595' };

            llenarInfoModal('modalJustificar', {
                fecha: `${data.fecha} ${(data.entrada || '')}`,
                estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`,
            });
            window.tasistencia = tasistencia;

            window.currentAsistenciaId = id;

            if ([0, 1].includes(data.tipo_asistencia) && ([2].includes(data.tipo_modalidad) || [10].includes(just?.estatus))) {
                quilleditorJustificar.updateOptions({
                    noPasteImg: true,
                    botones: ['link', 'camera']
                });
                $('#asunto_justificar').val(data.tipo_modalidad == 2 && data.tipo_asistencia == 0 ? 'Justificación de Asistencia Remota' : '');
            } else {
                quilleditorJustificar.updateOptions();
            }

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

        if (quilleditorJustificar.isEmptyImg()) {
            boxAlert.box({ i: 'warning', h: 'Tiene que subir minimo una foto.' });
            return;
        }

        const msg = `¿Estás de enviar la justificación?`;
        if (!await boxAlert.confirm({ h: msg })) return;

        fMananger.formModalLoding('modalJustificar', 'show');

        // Obtiene el contenido HTML del editor
        const contenidoHTML = quilleditorJustificar.html();

        var valid = validFrom(this);
        if (!valid.success) {
            return fMananger.formModalLoding('modalJustificar', 'hide');
        }
        let mensaje = utf8ToBase64(contenidoHTML);
        const archivos_data = Object.keys(quilleditorJustificar.mediaMap || {});
        const hora_justificacion = quilleditorJustificar.fileMap[0]?.lastModified || null;
        try {
            const body = JSON.stringify({
                id_asistencia: window.currentAsistenciaId,
                entrada: hora_justificacion,
                asunto: $('#asunto_justificar').val(),
                contenido: mensaje,
                archivos: archivos_data
            });

            const response = await fetch(__url + '/justificacion/justificar-usuario', {
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

    window.verJustificacion = async (id) => {
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
                || { descripcion: 'Pendiente', color: '#959595' };

            let estado = [
                { descripcion: 'Pendiente', color: 'secondary' },
                { descripcion: 'Aprobada', color: 'success' },
                { descripcion: 'Rechazada', color: 'danger' },
            ][just.estatus || 0];

            llenarInfoModal('modalVerJustificacion', {
                ver_estatus: `<span class="badge badge-${estado.color} ms-2" style="font-size: 0.75rem;">${estado.descripcion}</span>`,
                ver_fecha_asistencia: `${data.fecha} ${data.entrada || ''}`,
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
});