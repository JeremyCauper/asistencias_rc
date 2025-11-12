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
        tablaAsistencias.columns.adjust().draw();

        incidencia_estados.forEach((e, i) => {
            if (e.chart) e.chart.resize();
        });
    });

    $('.botones-table').append(
        $('<button>', {
            class: 'btn btn-primary me-2',
            "data-mdb-ripple-init": '',
            id: 'btn-refrescar'
        }).text('Actualizar'),
        ([1, 6].includes(tipoUsuario) && tipoSistema == 0) ? null : $('<button>', {
            class: 'btn btn-warning',
            "data-mdb-ripple-init": '',
            id: 'btn-exportar-reporte'
        }).html('<i class="fas fa-file-excel me-2"></i>Exportar Reporte')
    );

    $('#btn-refrescar').on('click', updateTable);

    $('#btn-exportar-reporte').on('click', function () {
        $('#modalExport').modal('show');
        // exportar_Asistencias(["2025-10"])
    });

    let modo = 'mensual';
    let rangoSeleccionado = null;
    const $fechaExport = $('#fechaExport');
    const $titulo = $('#modalExportLabel');

    // --- FUNCIÓN PARA ACTIVAR RANGO ---
    function activarRango() {
        // Si ya existe un daterangepicker, lo destruimos antes
        if ($fechaExport.data('daterangepicker')) {
            $fechaExport.data('daterangepicker').remove();
        }

        $fechaExport.daterangepicker({
            autoUpdateInput: true, // <--- ACTIVADO para autocompletar
            showDropdowns: true,
            startDate: date('Y-m-01'),
            endDate: date('Y-m-d'),
            maxDate: date('Y-m-d'),
            autoUpdateInput: true,
            opens: "center",
            cancelClass: "btn-link",
            locale: {
                format: 'YYYY-MM-DD',
                separator: ' a ',
                applyLabel: 'Aplicar',
                cancelLabel: 'Cerrar',
                fromLabel: 'Desde',
                toLabel: 'Hasta',
                customRangeLabel: 'Rango personalizado',
                daysOfWeek: ["Do", "Lu", "Ma", "Mi", "Ju", "Vi", "Sa"],
                monthNames: [
                    "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
                    "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
                ],
                firstDay: 1
            },
            // Limita a 2 meses máximo
            isInvalidDate: function (date) {
                const start = this.startDate;
                if (!start) return false;
                const diffMonths = date.diff(start, 'months', true);
                return diffMonths > 2;
            }
        }).on('apply.daterangepicker', function (ev, picker) {
            const start = picker.startDate.format('YYYY-MM-DD');
            const end = picker.endDate.format('YYYY-MM-DD');

            rangoSeleccionado = [start, end];
            $(this).val(start + ' a ' + end); // <--- esto asegura que se vea el valor
        });
        rangoSeleccionado = ($fechaExport.val()).split(' a ');
    }

    // --- DESACTIVAR RANGO ---
    function desactivarRango() {
        if ($fechaExport.data('daterangepicker')) {
            $fechaExport.data('daterangepicker').remove();
        }
        rangoSeleccionado = null;
    }

    // --- MODO MENSUAL ---
    $('#btnMensual').on('click', function () {
        modo = 'mensual';
        $(this).addClass('active');
        $('#btnRango').removeClass('active');
        $titulo.text('EXPORTAR MENSUAL');

        desactivarRango();
        $fechaExport.attr('type', 'month');
        $fechaExport.removeAttr('readonly');
        $fechaExport.val(new Date().toISOString().slice(0, 7)); // YYYY-MM
    });

    // --- MODO RANGO ---
    $('#btnRango').on('click', function () {
        modo = 'rango';
        $(this).addClass('active');
        $('#btnMensual').removeClass('active');
        $titulo.text('EXPORTAR POR RANGO');

        $fechaExport.attr('type', 'button');
        $fechaExport.attr('readonly', '');
        $fechaExport.val('');
        activarRango();
    });

    // --- SHOW PICKER SOLO EN MENSUAL ---
    $fechaExport.on('click', function () {
        if (modo === 'mensual') {
            this.showPicker();
        }
    });

    // --- EXPORTAR ---
    $('#btnExportar').on('click', async function () {
        let resultado = [];

        if (modo === 'mensual') {
            const mes = $fechaExport.val();
            if (mes) resultado = [mes];
        } else if (modo === 'rango' && rangoSeleccionado) {
            resultado = rangoSeleccionado;
        }

        await exportar_Asistencias(resultado);
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

/**
 * Mostrar modal de descuento con datos del personal
 */
function modificarDescuento(id) {
    try {
        // Mostrar el modal
        $('#modalDescuento').modal('show');
        fMananger.formModalLoding('modalDescuento', 'show');

        $.ajax({
            type: 'GET',
            url: `${__url}/asistencias/asistencias/${id}`,
            dataType: 'json',
            success: function (response) {
                fMananger.formModalLoding('modalDescuento', 'hide');

                // Validar estructura esperada
                if (!response || !response.data) {
                    return boxAlert.box({
                        i: 'error',
                        t: 'Datos no disponibles',
                        h: response.message || 'No se pudo obtener la información del registro seleccionado.'
                    });
                }

                const data = response.data;

                // Validar que venga el objeto personal
                if (!data.personal) {
                    return boxAlert.box({
                        i: 'warning',
                        t: 'Datos incompletos',
                        h: 'El registro no tiene información de personal asociada.'
                    });
                }

                // Buscar descripción del tipo de asistencia
                const tasistencia = tipoAsistencia.find(s => s.id == data.tipo_asistencia) || {
                    descripcion: 'Pendiente',
                    color: '#9fa6b2'
                };

                // Llenar campos visibles
                llenarInfoModal('modalDescuento', {
                    personal: `${data.personal.dni} - ${data.personal.nombre} ${data.personal.apellido}`,
                    fecha: `${data.fecha} ${(data.hora || '')}`,
                    estado: `<span class="badge" style="font-size: 0.75rem; background-color: ${tasistencia.color};">${tasistencia.descripcion}</span>`
                });

                // Llenar campos ocultos
                $('#user_id').val(data.user_id);
                $('#fecha').val(data.fecha);

                // Rellenar datos del descuento si existen
                $('#monto_descuento').val(data.descuento?.monto_descuento ?? '');
                $('#comentario').val(data.descuento?.comentario ?? '');
            },
            error: function (jqXHR) {
                fMananger.formModalLoding('modalDescuento', 'hide');

                let mensaje = 'Error al recuperar la información.';
                if (jqXHR.status === 404) mensaje = 'El registro de asistencia no existe.';
                if (jqXHR.status === 500) mensaje = 'Error interno del servidor.';

                boxAlert.box({
                    i: 'error',
                    t: 'Error en la solicitud',
                    h: mensaje
                });
            }
        });
    } catch (error) {
        fMananger.formModalLoding('modalDescuento', 'hide');
        boxAlert.box({
            i: 'error',
            t: 'Error inesperado',
            h: 'Ocurrió un problema inesperado. Por favor, intenta nuevamente.'
        });
        console.error('Error JS:', error);
    }
}

/**
 * Guardar o actualizar descuento
 */
$('#form-descuento').on('submit', function (e) {
    e.preventDefault();

    fMananger.formModalLoding('modalDescuento', 'show');

    const payload = {
        user_id: $('#user_id').val(),
        fecha: $('#fecha').val(),
        monto_descuento: $('#monto_descuento').val(),
        comentario: $('#comentario').val() || null
    };

    $.ajax({
        type: 'POST',
        url: `${__url}/asistencias/descuento`,
        headers: { 'X-CSRF-TOKEN': __token },
        contentType: 'application/json',
        data: JSON.stringify(payload),
        success: function (res) {
            fMananger.formModalLoding('modalDescuento', 'hide');

            if (!res.success) {
                return boxAlert.box({
                    i: 'error',
                    t: 'No se pudo guardar el descuento',
                    h: res.message || 'Revisa los datos e intenta nuevamente.'
                });
            }

            $('#modalDescuento').modal('hide');
            boxAlert.minbox({ h: res.message });
            updateTable();
        },
        error: function (jqXHR) {
            fMananger.formModalLoding('modalDescuento', 'hide');
            let mensaje = 'No se pudo procesar la solicitud.';

            if (jqXHR.status === 400) mensaje = 'Los datos son inválidos. Revisa los campos.';
            if (jqXHR.status === 500) mensaje = 'Error interno del servidor.';

            boxAlert.box({
                i: 'error',
                t: 'Error en la solicitud',
                h: mensaje
            });
        }
    });
});

async function marcarDerivado(asistenciaId) {
    try {
        const confirm = await boxAlert.confirm({ h: `¿Deseas marcar esta asistencia como DERIVADA?` });
        if (!confirm) return;

        boxAlert.loading();
        const res = await fetch(__url + `/asistencias/${asistenciaId}/derivar`, {
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

// document.getElementById('form-descuento').addEventListener('submit', function (event) {
//     event.preventDefault();
//     fMananger.formModalLoding('modalDescuento', 'show');

//     var valid = validFrom(this);
//     if (!valid.success) {
//         return fMananger.formModalLoding('modalDescuento', 'hide');
//     }

//     $.ajax({
//         type: 'POST',
//         url: `${__url}/asistencias/descuento`,
//         contentType: 'application/json',
//         headers: {
//             'X-CSRF-TOKEN': __token,
//         },
//         data: JSON.stringify(valid.data.data),
//         success: function (data) {
//             fMananger.formModalLoding('modalDescuento', 'hide');

//             if (!data.success) {
//                 return boxAlert.box({
//                     i: 'error',
//                     t: 'Algo salió mal...',
//                     h: data.message || 'No se pudo completar la operación.'
//                 });
//             }

//             $('#modalDescuento').modal('hide');
//             boxAlert.minbox({
//                 h: data.message
//             });
//             updateTable();
//         },
//         error: function (jqXHR) {
//             console.log(jqXHR.responseJSON);

//             fMananger.formModalLoding('modalDescuento', 'hide');
//             let mensaje = 'Hubo un problema al procesar la solicitud. Intenta nuevamente.';

//             if (jqXHR.status === 400) {
//                 mensaje = 'Datos inválidos. Por favor, revisa los campos e intenta nuevamente.';
//             } else if (jqXHR.status === 409) {
//                 mensaje = jqXHR.responseJSON?.message || 'El código o la descripción ya existen en la base de datos.';
//             } else if (jqXHR.status === 500) {
//                 mensaje = 'Ocurrió un error interno en el servidor. Intenta más tarde.';
//             }

//             boxAlert.box({
//                 i: 'error',
//                 t: 'Error en la solicitud',
//                 h: mensaje
//             });
//             console.log("Error en AJAX:", jqXHR);
//         }
//     });
// });

// async function marcarComoFalta(id, nombrePersonal) {
//     try {
//         if (!await boxAlert.confirm({
//             t: `¿Está seguro de esta acción?`,
//             h: `<p class="mb-1">Está a punto de marcar como <b style="color: #dc3545;">FALTA</b></p><p><b>${nombrePersonal}</b></p>`
//         })) return true;

//         fMananger.formModalLoding('body', 'show'); // Puedes usar un loader general

//         $.ajax({
//             type: 'POST',
//             url: `${__url}/asistencias/marcar-falta`,
//             contentType: 'application/json',
//             headers: {
//                 'X-CSRF-TOKEN': __token,
//             },
//             data: JSON.stringify({ id }),
//             success: function (data) {
//                 fMananger.formModalLoding('body', 'hide');

//                 if (!data.success) {
//                     return boxAlert.box({
//                         i: 'error',
//                         t: 'No se pudo marcar la falta',
//                         h: data.message || 'Error desconocido.'
//                     });
//                 }

//                 boxAlert.minbox({
//                     h: data.message
//                 });
//                 updateTable(); // Refrescar tu tabla
//             },
//             error: function (jqXHR) {
//                 fMananger.formModalLoding('body', 'hide');

//                 let mensaje = 'Ocurrió un error al procesar la solicitud.';
//                 if (jqXHR.status === 404) mensaje = 'El registro de asistencia no existe.';
//                 if (jqXHR.status === 409) mensaje = jqXHR.responseJSON?.message || 'Conflicto al actualizar la asistencia.';

//                 boxAlert.box({
//                     i: 'error',
//                     t: 'Error en la solicitud',
//                     h: mensaje
//                 });
//                 console.error(jqXHR);
//             }
//         });

//     } catch (error) {
//         fMananger.formModalLoding('body', 'hide');
//         boxAlert.box({
//             i: 'error',
//             t: 'Error inesperado',
//             h: 'Ocurrió un problema inesperado. Intenta nuevamente.'
//         });
//         console.error(error);
//     }
// }

// async function revertirFalta(id, nombrePersonal) {
//     try {
//         if (!await boxAlert.confirm({
//             t: `¿Está seguro de esta acción?`,
//             h: `<p class="mb-1">Está a punto de <b>revertir</b> la <b style="color: #dc3545;">Falta</b> del personal</p><p><b>${nombrePersonal}</b></p>`
//         })) return true;

//         fMananger.formModalLoding('body', 'show');

//         $.ajax({
//             type: 'POST',
//             url: `${__url}/asistencias/revertir-falta`,
//             contentType: 'application/json',
//             headers: {
//                 'X-CSRF-TOKEN': __token,
//             },
//             data: JSON.stringify({ id }),
//             success: function (data) {
//                 fMananger.formModalLoding('body', 'hide');

//                 if (!data.success) {
//                     return boxAlert.box({
//                         i: 'error',
//                         t: 'No se pudo revertir la falta',
//                         h: data.message || 'Error desconocido.'
//                     });
//                 }

//                 boxAlert.minbox({
//                     h: data.message
//                 });
//                 updateTable();
//             },
//             error: function (jqXHR) {
//                 fMananger.formModalLoding('body', 'hide');

//                 let mensaje = 'Error al procesar la solicitud.';
//                 if (jqXHR.status === 404) mensaje = 'El registro no existe.';
//                 if (jqXHR.status === 409) mensaje = jqXHR.responseJSON?.message || 'Conflicto al revertir la asistencia.';

//                 boxAlert.box({
//                     i: 'error',
//                     t: 'Error en la solicitud',
//                     h: mensaje
//                 });
//                 console.error(jqXHR);
//             }
//         });

//     } catch (error) {
//         fMananger.formModalLoding('body', 'hide');
//         boxAlert.box({
//             i: 'error',
//             t: 'Error inesperado',
//             h: 'Ocurrió un problema inesperado. Intenta nuevamente.'
//         });
//         console.error(error);
//     }
// }

// async function marcarComoJustificado(id, nombrePersonal) {
//     try {
//         if (!await boxAlert.confirm({
//             t: `¿Está seguro de esta acción?`,
//             h: `<p class="mb-1">Está a punto de marcar como <b style="color: #17a2b8;">JUSTIFICADO</b></p><p><b>${nombrePersonal}</b></p>`
//         })) return true;

//         fMananger.formModalLoding('body', 'show'); // Puedes usar un loader general

//         $.ajax({
//             type: 'POST',
//             url: `${__url}/asistencias/marcar-justificado`,
//             contentType: 'application/json',
//             headers: {
//                 'X-CSRF-TOKEN': __token,
//             },
//             data: JSON.stringify({ id }),
//             success: function (data) {
//                 fMananger.formModalLoding('body', 'hide');

//                 if (!data.success) {
//                     return boxAlert.box({
//                         i: 'error',
//                         t: 'No se pudo marcar la justificación',
//                         h: data.message || 'Error desconocido.'
//                     });
//                 }

//                 boxAlert.minbox({
//                     h: data.message
//                 });
//                 updateTable(); // Refrescar tu tabla
//             },
//             error: function (jqXHR) {
//                 fMananger.formModalLoding('body', 'hide');

//                 let mensaje = 'Ocurrió un error al procesar la solicitud.';
//                 if (jqXHR.status === 404) mensaje = 'El registro de asistencia no existe.';
//                 if (jqXHR.status === 409) mensaje = jqXHR.responseJSON?.message || 'Conflicto al actualizar la asistencia.';

//                 boxAlert.box({
//                     i: 'error',
//                     t: 'Error en la solicitud',
//                     h: mensaje
//                 });
//                 console.error(jqXHR);
//             }
//         });

//     } catch (error) {
//         fMananger.formModalLoding('body', 'hide');
//         boxAlert.box({
//             i: 'error',
//             t: 'Error inesperado',
//             h: 'Ocurrió un problema inesperado. Intenta nuevamente.'
//         });
//         console.error(error);
//     }
// }

// async function marcarComoTardanza(id, nombrePersonal) {
//     try {
//         if (!await boxAlert.confirm({
//             t: `¿Está seguro de esta acción?`,
//             h: `<p class="mb-1">Está a punto de marcar como <b style="color: #e4a11b;">TARDANZA</b></p><p><b>${nombrePersonal}</b></p>`
//         })) return true;

//         fMananger.formModalLoding('body', 'show'); // Puedes usar un loader general

//         $.ajax({
//             type: 'POST',
//             url: `${__url}/asistencias/marcar-tardanza`,
//             contentType: 'application/json',
//             headers: {
//                 'X-CSRF-TOKEN': __token,
//             },
//             data: JSON.stringify({ id }),
//             success: function (data) {
//                 fMananger.formModalLoding('body', 'hide');

//                 if (!data.success) {
//                     return boxAlert.box({
//                         i: 'error',
//                         t: 'No se pudo marcar la tardanza',
//                         h: data.message || 'Error desconocido.'
//                     });
//                 }

//                 boxAlert.minbox({
//                     h: data.message
//                 });
//                 updateTable(); // Refrescar tu tabla
//             },
//             error: function (jqXHR) {
//                 fMananger.formModalLoding('body', 'hide');

//                 let mensaje = 'Ocurrió un error al procesar la solicitud.';
//                 if (jqXHR.status === 404) mensaje = 'El registro de asistencia no existe.';
//                 if (jqXHR.status === 409) mensaje = jqXHR.responseJSON?.message || 'Conflicto al actualizar la asistencia.';

//                 boxAlert.box({
//                     i: 'error',
//                     t: 'Error en la solicitud',
//                     h: mensaje
//                 });
//                 console.error(jqXHR);
//             }
//         });

//     } catch (error) {
//         fMananger.formModalLoding('body', 'hide');
//         boxAlert.box({
//             i: 'error',
//             t: 'Error inesperado',
//             h: 'Ocurrió un problema inesperado. Intenta nuevamente.'
//         });
//         console.error(error);
//     }
// }