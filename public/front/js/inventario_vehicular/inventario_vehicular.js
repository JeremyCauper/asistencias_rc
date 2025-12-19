$(document).ready(function () {
    configControls([
        {
            control: '#placa',
            requested: true
        },
        {
            control: '#modelo',
            mxl: 100,
            requested: true
        },
        {
            control: '#marca',
            mxl: 100,
            requested: true
        },
        {
            control: '#tipo_registro',
            requested: true
        },
        {
            control: '#popietario'
        }
    ]);

    fObservador('.content-wrapper', () => {
        if (!esCelular()) {
            tb_inventario_vehicular.columns.adjust().draw();
        }
    });

    $('.botones-accion').append(
        $('<button>', {
            class: 'btn btn-primary me-1',
            "data-mdb-ripple-init": '',
            "data-mdb-modal-init": '',
            "data-mdb-target": '#modal_inventario_vehicular',
        }).html('<i class="fas fa-plus"></i> Vehiculo'),
        $('<button>', {
            class: 'btn btn-primary px-2',
            "data-mdb-ripple-init": '',
            "role": 'button'
        }).html('<i class="fas fa-rotate-right" style="min-width: 1.25rem;"></i>').on('click', updateTable),
    );

    // Array con los IDs de los inputs file y sus correspondientes inputs date
    const campos = [
        { file: '#fileSoat', date: '#soat', label: 'label[for="fileSoat"]' },
        { file: '#fileInspeccion', date: '#inspeccion', label: 'label[for="fileInspeccion"]' },
        { file: '#fileChip', date: '#chip', label: 'label[for="fileChip"]' },
        { file: '#fileCilindro', date: '#cilindro', label: 'label[for="fileCilindro"]' }
    ];

    // Deshabilitar todos los inputs date al cargar la página
    campos.forEach(campo => {
        $(campo.date).prop('disabled', true);
    });

    // Función para manejar el cambio de archivo
    function manejarCambioArchivo(campo) {
        $(campo.file).on('change', function () {
            const archivo = this.files[0];
            const label = $(campo.label);
            const inputDate = $(campo.date);

            if (archivo) {
                // Si se seleccionó un archivo
                // Cambiar por el icono de check
                label.html('<i class="fas fa-check text-success"></i>');

                // Habilitar el input date
                inputDate.prop('disabled', false);
            } else {
                // Si no hay archivo (se canceló la selección)
                // Restaurar el texto "Subir"
                label.html('<i class="far fa-file-pdf text-danger"></i> Subir');

                // Deshabilitar el input date
                inputDate.prop('disabled', true);
                inputDate.val(date('Y-m-d'));
            }
        });
    }

    // Aplicar el evento a cada campo
    campos.forEach(campo => {
        manejarCambioArchivo(campo);
    });
});

const url_base = `${__url}/inventario-vehicular`;

const getFormData = (input) => {
    const file = $('#' + input)[0].files[0];
    if (file) {
        const form = new FormData();
        form.append('file', file);
        return form;
    }
    return null;
}

// 💾 Guardar (nuevo o edición)
$('#form-inventario-vehicular').on('submit', async function (event) {
    event.preventDefault();
    console.log('Hola');
    

    fMananger.formModalLoding('modal_inventario_vehicular', 'show');

    const valid = validFrom(this);
    if (!valid.success) {
        fMananger.formModalLoding('modal_inventario_vehicular', 'hide');
        return;
    }

    valid.data.data.file_soat = getFormData('fileSoat');
    valid.data.data.file_inspeccion = getFormData('fileInspeccion');
    valid.data.data.file_chip = getFormData('fileChip');
    valid.data.data.file_cilindro = getFormData('fileCilindro');

    try {
        const body = JSON.stringify(valid.data.data);
        const response = await fetch(`${url_base}/${$('#id').val() ? 'actualizar' : 'registrar'}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body,
        });

        const data = await response.json();
        fMananger.formModalLoding('modal_inventario_vehicular', 'hide');

        if (!response.ok || !data.success) {
            const mensaje = data.message || 'No se pudo completar la operación.';
            return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
        }

        $('#modal_inventario_vehicular').modal('hide');
        boxAlert.box({ h: data.message || 'Operación realizada con éxito.' });
        updateTable();
    } catch (error) {
        fMananger.formModalLoding('modal_inventario_vehicular', 'hide');
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    }
});