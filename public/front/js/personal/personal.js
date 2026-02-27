$(document).ready(function () {
    configControls([
        // Formulario problemas
        {
            control: '#empresa',
            requested: true
        },
        {
            control: '#areas',
            requested: true
        },
        {
            control: '#dni',
            controlType: 'ndoc',
            requested: true
        },
        {
            control: '#nombre',
            mxl: 100,
            requested: true
        },
        {
            control: '#apellido',
            mxl: 100,
            requested: true
        },
        {
            control: '#rol_system',
            addLabel: 'Tipo Usuario',
            requested: true
        },
        {
            control: '#password_view',
            addLabel: 'Clave en Sistema',
            requested: true
        },
        {
            control: '#cardno',
            addLabel: 'Num. Tarjeta Sensor',
        },
        {
            control: '#rol_sensor',
            addLabel: 'Rol en Sensor',
            requested: true
        },
        {
            control: '#clave',
            addLabel: 'Clave en sensor',
            controlType: 'int'
        },
    ]);

    fObservador('.content-wrapper', () => {
        if (!esCelular()) {
            lista_personal.columns.adjust().draw();
        }
    });

    // Agregar botón de recargar
    $('.botones-accion').append(
        $('<button>', {
            class: 'btn btn-primary me-1',
            "data-mdb-ripple-init": '',
            "data-mdb-modal-init": '',
            "data-mdb-target": '#modalPersonal',
        }).html('<i class="fas fa-plus me-2"></i>Personal'),
        $('<button>', {
            class: 'btn btn-primary px-2',
            "data-mdb-ripple-init": '',
            "role": 'button'
        }).html('<i class="fas fa-rotate-right" style="min-width: 1.25rem;"></i>').on('click', updateTable),
    );

    $('.modal').on('hidden.bs.modal', function () {
        $('#tplunes1').click();
        $('#tpmartes1').click();
        $('#tpmiercoles1').click();
        $('#tpjueves1').click();
        $('#tpviernes1').click();
        $('#tpsabado1').click();
        calendarVacaciones.clear();
        calendarDescansos.clear();
        $('#archivoDescanso').val(''); // Limpiar file input
    });

    // 🧠 Evento: cuando el campo DNI cambia o se completa con 8 dígitos
    $('#dni').on('input', function () {
        const dni = $(this).val().trim();

        // Solo consulta si tiene exactamente 8 dígitos numéricos
        if (dni.length === 8 && /^[0-9]+$/.test(dni)) {
            consultarDNI(dni);
        }
    });

    // 🧍 Editar
    $(document).on('click', '.btnEditar', function () {
        $('#modalPersonalLabel').text('Editar Personal');
        $('#modalPersonal').modal('show');
        fMananger.formModalLoding('modalPersonal', 'show');
        let id = $(this).data('id');
        $.get(__url + `/personal/${id}`, function (p) {
            $('#userid').val(p.user_id);
            $('#empresa').val(p.empresa_ruc).trigger('change');
            $('#areas').val(p.area_id).trigger('change');
            $('#dni').val(p.dni);
            $('#nombre').val(p.nombre);
            $('#apellido').val(p.apellido);
            $('#rol_system').val(p.rol_system).trigger('change');
            $('#password_view').val(p.password_view);
            $('#cardno').val(p.cardno);
            $('#rol_sensor').val(p.role).trigger('change');
            $('#clave').val(p.password);
            $(`#tplunes${p.trabajo_personal?.lunes ?? 1}`).click();
            $(`#tpmartes${p.trabajo_personal?.martes ?? 1}`).click();
            $(`#tpmiercoles${p.trabajo_personal?.miercoles ?? 1}`).click();
            $(`#tpjueves${p.trabajo_personal?.jueves ?? 1}`).click();
            $(`#tpviernes${p.trabajo_personal?.viernes ?? 1}`).click();
            $(`#tpsabado${p.trabajo_personal?.sabado ?? 1}`).click();
            fMananger.formModalLoding('modalPersonal', 'hide');
        });
    });

    $(document).on('click', '.btnVacaciones', async function () {
        try {
            setTimeout(() => {
                calendarVacaciones.updateSize();
            }, 300);
            let id = $(this).data('id');
            $('#modalVacaciones').modal('show');

            fMananger.formModalLoding('modalVacaciones', 'show');

            const res = await $.getJSON(`${__url}/personal/cargar-vacaciones/${id}`);
            fMananger.formModalLoding('modalVacaciones', 'hide');

            if (!res?.data) {
                return boxAlert.box({
                    i: 'error',
                    t: 'No se pudo obtener la información',
                    h: res.message || 'No se encontraron datos de la asistencia seleccionada.'
                });
            }

            const data = res.data;
            window.currentUserIdVacaciones = id;
            window.currentFechasVacaciones = data;
            calendarVacaciones.loadDates(data);
        } catch (error) {
            fMananger.formModalLoding('modalVacaciones', 'hide');
            console.error(error);
            boxAlert.box({
                i: 'error',
                t: 'Error en la solicitud',
                h: 'No se pudo recuperar la información del servidor.'
            });
        }
    });

    // 🏥 Programar Descansos Médicos (Boton)
    $(document).on('click', '.btnDescansos', async function () {
        try {
            setTimeout(() => {
                calendarDescansos.updateSize();
            }, 300);
            let id = $(this).data('id');
            $('#modalDescansos').modal('show');

            fMananger.formModalLoding('modalDescansos', 'show');

            const res = await $.getJSON(`${__url}/personal/cargar-descansos/${id}`);
            fMananger.formModalLoding('modalDescansos', 'hide');

            if (!res?.data || !res?.success) {
                return boxAlert.box({
                    i: 'error',
                    t: 'No se pudo obtener la información',
                    h: res.message || 'No se encontraron datos.'
                });
            }

            const data = res.data;
            window.currentUserIdDescanso = id;
            window.currentFechasDescansos = data;
            calendarDescansos.loadDates(data);
        } catch (error) {
            fMananger.formModalLoding('modalDescansos', 'hide');
            console.error(error);
            boxAlert.box({ i: 'error', t: 'Aviso', h: 'No se pudo cargar la información previa o no existe historial.' });
        }
    });

    $("#btnGuardarVacaciones").on("click", async function () {
        try {
            let fechas = calendarVacaciones.diffWith(window.currentFechasVacaciones);
            if (fechas.added.length === 0 && fechas.removed.length === 0) {
                return boxAlert.box({
                    i: 'info',
                    t: 'Sin cambios',
                    h: 'No se han realizado modificaciones en las fechas de vacaciones.'
                });
            }

            if (!await boxAlert.confirm({
                t: '¿Estas seguro de guardar los cambios?',
                h: `Se van a agregar <strong>${fechas.added.length}</strong> y eliminar <strong>${fechas.removed.length}</strong> fechas.`
            })) return;

            fMananger.formModalLoding('modalVacaciones', 'show');
            const response = await fetch(`${__url}/personal/crear-vacaciones`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __token,
                },
                body: JSON.stringify({
                    user_id: window.currentUserIdVacaciones,
                    eliminadas: fechas.removed,
                    nuevas: fechas.added
                }),
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                const mensaje = data.message || 'No se pudo completar la operación.';
                return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
            }

            // Actualizar referencia local
            window.currentFechasVacaciones = fechas.added;
            boxAlert.box({ h: data.message });
        } catch (error) {
            console.error('Error en la solicitud:', error);
            boxAlert.box({ i: 'error', t: 'Error en la conexión', h: 'Ocurrió un problema.' });
        } finally {
            fMananger.formModalLoding('modalVacaciones', 'hide');
        }
    });

    // 💾 Guardar Descansos Médicos
    $("#btnGuardarDescansos").on("click", async function () {
        try {
            let fechas = calendarDescansos.diffWith(window.currentFechasDescansos);
            if (fechas.added.length === 0 && fechas.removed.length === 0) {
                return boxAlert.box({
                    i: 'info',
                    t: 'Sin cambios',
                    h: 'No se realizaron modificaciones.'
                });
            }

            if (!await boxAlert.confirm({
                t: '¿Guardar programación de descansos?',
                h: `Se agregan <strong>${fechas.added.length}</strong>, eliminan <strong>${fechas.removed.length}</strong>.`
            })) return;

            fMananger.formModalLoding('modalDescansos', 'show');

            /*let formData = new FormData();
            formData.append('user_id', window.currentUserIdDescanso);

            // Adjuntar arrays (Laravel los lee como arrays si se usa notación [])
            fechas.removed.forEach(f => formData.append('eliminadas[]', f));
            fechas.added.forEach(f => formData.append('nuevas[]', f));

            if (archivo) {
                formData.append('archivo', archivo);
            }

            // Fetch con FormData
            const response = await fetch(`${__url}/personal/crear-descansos`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': __token,
                    // 'Content-Type': 'multipart/form-data' // NO poner esto manual con fetch, el navegador lo pone con boundary
                },
                body: formData
            });

            window.currentFechasDescansos = fechas.added;
            $('#archivoDescanso').val(''); // Limpiar input
            boxAlert.box({ h: data.message });*/
            const response = await fetch(`${__url}/personal/crear-descansos`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': __token,
                },
                body: JSON.stringify({
                    user_id: window.currentUserIdDescanso,
                    eliminadas: fechas.removed,
                    nuevas: fechas.added
                }),
            });

            const data = await response.json();
            if (!response.ok || !data.success) {
                const mensaje = data.message || 'No se pudo completar la operación.';
                return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
            }

            // Actualizar referencia local
            window.currentFechasDescansos = fechas.added;
            boxAlert.box({ h: data.message });

        } catch (error) {
            console.error(error);
            boxAlert.box({ i: 'error', t: 'Error', h: 'Falló la conexión.' });
        } finally {
            fMananger.formModalLoding('modalDescansos', 'hide');
        }
    });

    // 🗑️ Eliminar
    $(document).on('click', '.btnEliminar', function () {
        if (!confirm('¿Eliminar este registro?')) return;
        let id = $(this).data('id');
        $.ajax({
            url: __url + `/personal/${id}`,
            type: 'DELETE',
            success: function () {
                updateTable();
            }
        });
    });

    $(document).on('click', '.btnEstado', async function () {
        if (!await boxAlert.confirm({ t: '¿Esta seguro de esta acción?' })) return;

        let user_id = $(this).data('id');
        let estatus = $(this).data('estatus');
        $.ajax({
            url: __url + `/personal/cambiarEstatus`,
            type: 'POST',
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': __token,
            },
            data: JSON.stringify({
                id: user_id,
                estado: estatus
            }),
            success: function (data) {
                if (data.success) {
                    boxAlert.box({ i: 'success', t: 'Cambio de Estado', h: data.message })
                }
                updateTable();
            }
        });
    });

    // 💾 Guardar (nuevo o edición)
    document.getElementById('formPersonal').addEventListener('submit', async function (event) {
        event.preventDefault();
        fMananger.formModalLoding('modalPersonal', 'show');

        var valid = validFrom(this);
        if (!valid.success)
            return fMananger.formModalLoding('modalPersonal', 'hide');

        if (!await boxAlert.confirm({
            t: '¿Estas de suguro de guardar los cambios?',
            // h: ''
        })) return fMananger.formModalLoding('modalPersonal', 'hide');

        const datos = valid.data.data;
        const id = $('#userid').val();
        const url = __url + '/personal' + (id ? '/' + id : '');
        const metodo = id ? 'PUT' : 'POST';

        $.ajax({
            url: url,
            type: metodo,
            headers: {
                'X-CSRF-TOKEN': __token,
            },
            data: datos,
            success: function (data) {
                console.log(data);
                $('#modalPersonal').modal('hide');
                updateTable();
                $('#formPersonal')[0].reset();
                $('#modalPersonalLabel').text('Registrar Personal');
            },
            error: function (xhr) {
                console.error('Error al guardar cambios del personal:', xhr);
                fMananger.formModalLoding('modalPersonal', 'hide');
            }
        });
    });

    // 🔍 Función para consultar API y llenar nombre/apellido
    function consultarDNI(dni) {
        const url = __url + `/api/ConsultaDoc?doc=${dni}`;

        // Muestra indicador de carga (opcional)
        $('[for="dni"]').html('Consultando...').addClass('text-info');

        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            success: function (response) {
                // Verifica que haya data válida
                if (response && response.data && response.data.Nombres) {
                    const personal = response.data;

                    // Divide nombre completo si es posible
                    const nombre = personal.Nombres.trim();
                    const apellido = personal.ApePaterno.trim() + ' ' + personal.ApeMaterno
                        .trim();

                    $('#nombre').val(nombre);
                    $('#apellido').val(apellido);
                } else {
                    alert('No se encontró información para este DNI.');
                }
                $('[for="dni"]').html('DNI').removeClass('text-info');
            },
            error: function (xhr) {
                console.error('Error en la consulta DNI:', xhr);
                $('#nombre').val('');
                $('#apellido').val('');
                alert('No se pudo consultar el documento, verifica la conexión.');
            }
        });
    }
});