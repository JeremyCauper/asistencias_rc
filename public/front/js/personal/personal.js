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
            control: '#usuario',
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

    formatSelect('modalPersonal');

    fObservador('.content-wrapper', () => {
        tablaPersonal.columns.adjust().draw();
    });

    $('.modal').on('hidden.bs.modal', function () {
        $('#tplunes1').click();
        $('#tpmartes1').click();
        $('#tpmiercoles1').click();
        $('#tpjueves1').click();
        $('#tpviernes1').click();
        $('#tpsabado1').click();
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
            $('#usuario').val(p.usuario);
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