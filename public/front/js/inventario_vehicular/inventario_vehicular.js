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

    formatSelect('modal_inventario_vehicular');

    fObservador('.content-wrapper', () => {
        if (!esCelular()) {
            tb_inventario_vehicular.columns.adjust().draw();
        }
    });

    $('.modal').on('hidden.bs.modal', function () {
        ['fileSoat', 'fileInspeccion', 'fileChip', 'fileCilindro'].forEach(id => {
            $('#' + id).val('').trigger('change');
        });

        desmarcarCheckboxes();
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

    // Función para obtener la fecha mínima permitida (día siguiente)
    function getFechaMinima() {
        const hoy = new Date();
        hoy.setDate(hoy.getDate() + 1);
        return hoy.toISOString().split('T')[0];
    }

    // Configurar fecha mínima en todos los inputs de tipo date
    document.querySelectorAll('input[type="date"]').forEach(input => {
        input.setAttribute('min', getFechaMinima());
    });

    // Función para manejar la subida de archivos
    function configurarInputFile(fileInputId, iconoUploadSelector, dateInputId) {
        const fileInput = document.getElementById(fileInputId);
        const iconoUpload = document.querySelector(iconoUploadSelector);
        const dateInput = document.getElementById(dateInputId);

        if (!fileInput || !iconoUpload || !dateInput) return;

        // Evento cuando se selecciona un archivo
        fileInput.addEventListener('change', function (e) {
            if (this.files && this.files.length > 0) {
                // Cambiar icono a check
                iconoUpload.className = 'fas fa-check text-success';

                // Añadir atributo requested al input date
                const idMayuscula = dateInputId.toUpperCase();
                dateInput.setAttribute('requested', idMayuscula);
                document.querySelector(`[for="${dateInputId}"]`).classList.add('requested');
            }
        });

        // Evento cuando se hace click en el icono
        iconoUpload.parentElement.addEventListener('click', function (e) {
            e.preventDefault();

            // Si el icono es check (ya hay archivo subido)
            if (iconoUpload.classList.contains('fa-check')) {
                // Limpiar el input file
                fileInput.value = '';

                // Cambiar icono a upload
                iconoUpload.className = 'fas fa-upload';

                // Remover atributo requested del input date
                dateInput.removeAttribute('requested');
                document.querySelector(`[for="${dateInputId}"]`).classList.remove('requested');
            }
        });
    }

    // Soat
    configurarInputFile(
        'fileSoat',
        '[data-ver-pdf="soat"] i',
        'soat'
    );

    // Inspección
    configurarInputFile(
        'fileInspeccion',
        '[data-ver-pdf="inspeccion"] i',
        'inspeccion'
    );

    // Chip
    configurarInputFile(
        'fileChip',
        '[data-ver-pdf="chip"] i',
        'chip'
    );

    // Cilindro
    configurarInputFile(
        'fileCilindro',
        '[data-ver-pdf="cilindro"] i',
        'cilindro'
    );

    // Función adicional para validar fechas al enviar el formulario (opcional)
    function validarFechas() {
        const fechaMinima = getFechaMinima();
        let valido = true;

        document.querySelectorAll('input[type="date"]').forEach(input => {
            if (input.value && input.value <= fechaMinima) {
                alert(`La fecha de ${input.name} debe ser posterior a hoy`);
                valido = false;
            }
        });

        return valido;
    }

    // Array con los IDs de los inputs file y sus correspondientes inputs date
    /*const campos = [
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
    });*/
});

const url_base = `${__url}/inventario-vehicular`;

// 💾 Guardar (nuevo o edición)
$('#form-inventario-vehicular').on('submit', async function (event) {
    event.preventDefault();

    fMananger.formModalLoding('modal_inventario_vehicular', 'show');

    const valid = validFrom(this);
    if (!valid.success) {
        fMananger.formModalLoding('modal_inventario_vehicular', 'hide');
        return;
    }

    const formData = new FormData();

    if (valid.data.data) {
        let data = valid.data.data;
        Object.keys(data).forEach(key => {
            formData.append(key, data[key]);
        });
    }

    const appendFile = (inputId, fieldName) => {
        const file = $('#' + inputId)[0].files[0];
        if (file) {
            formData.append(fieldName, file);
        }
    };

    appendFile('fileSoat', 'file_soat');
    appendFile('fileInspeccion', 'file_inspeccion');
    appendFile('fileChip', 'file_chip');
    appendFile('fileCilindro', 'file_cilindro');

    try {
        const body = JSON.stringify(valid.data.data);
        const response = await fetch(`${url_base}/${$('#id').val() ? 'actualizar' : 'registrar'}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': __token,
            },
            body: formData, // 👈 AQUÍ
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

// ======================================================
// EDITAR
// ======================================================
async function Editar(id) {
    try {
        $('#modal_inventario_vehicularLabel').html('EDITAR VEHICULO');
        $('#modal_inventario_vehicular').modal('show');
        fMananger.formModalLoding('modal_inventario_vehicular', 'show');

        const response = await fetch(`${url_base}/mostrar/${id}`, { method: 'GET' });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo obtener la información solicitada.');
        }

        if (!data.data) {
            throw new Error('No se encontró el registro solicitado. Puede que haya sido eliminado.');
        }

        const json = data.data;
        $('#id').val(json.id);
        $('#placa').val(json.placa);
        $('#modelo').val(json.modelo);
        $('#marca').val(json.marca);
        $('#tipo_registro').val(json.tipo_registro).trigger('change');
        $('#propietario').val(json.user_id).trigger('change');

        $('#soat').val(json.soat);
        $('#inspeccion').val(json.r_tecnica);
        $('#chip').val(json.v_chip);
        $('#cilindro').val(json.v_cilindro);

        fMananger.formModalLoding('modal_inventario_vehicular', 'hide');
    } catch (error) {
        console.error('Error al cargar registro:', error);

        boxAlert.box({
            i: 'error',
            t: 'No pudimos obtener la información',
            h: error.message || 'Ocurrió un error inesperado. Por favor, intenta nuevamente.'
        });

        fMananger.formModalLoding('modal_inventario_vehicular', 'hide');
    }
}

// ======================================================
// ASIGNAR
// ======================================================
async function Asignar(id) {
    try {
        $('#modal_inventario_vehicular_asignar').modal('show');
        fMananger.formModalLoding('modal_inventario_vehicular_asignar', 'show');

        const response = await fetch(`${url_base}/mostrar/${id}`, { method: 'GET' });
        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'No se pudo obtener la información solicitada.');
        }

        if (!data.data) {
            throw new Error('No se encontró el registro solicitado. Puede que haya sido eliminado.');
        }

        const json = data.data;
        llenarInfoModal('modal_inventario_vehicular_asignar', {
            id: json.id,
            placa: json.placa,
            modelo: json.modelo,
            marca: json.marca,
            tipo_registro_icon: `<i class="fas fa-${json.tipo_registro.toLocaleLowerCase() != 'motorizado' ? 'car' : 'motorcycle'}"></i>`,
            tipo_registro: json.tipo_registro,
            propietario: json.user_id
        });
        window.arrayAsignados = json.personal_asignados || [];
        marcarCheckboxes(window.arrayAsignados);
        window.currentVehiculoId = json.id;

        fMananger.formModalLoding('modal_inventario_vehicular_asignar', 'hide');
    } catch (error) {
        console.error('Error al cargar registro:', error);

        boxAlert.box({
            i: 'error',
            t: 'No pudimos obtener la información',
            h: error.message || 'Ocurrió un error inesperado. Por favor, intenta nuevamente.'
        });

        fMananger.formModalLoding('modal_inventario_vehicular_asignar', 'hide');
    }
}

document.querySelector('#btnAsignar').addEventListener('click', async function () {
    try {
        const cambios = compararArrays();
        if (cambios.nuevos.length === 0 && cambios.eliminados.length === 0) {
            return boxAlert.box({
                i: 'info',
                t: 'Sin cambios',
                h: 'No se han realizado modificaciones en las fechas de vacaciones.'
            });
        }

        if (!await boxAlert.confirm({
            t: '¿Estas seguro de guardar los cambios?',
            h: `Se van a agregar <strong>${cambios.nuevos.length}</strong> y eliminar <strong>${cambios.eliminados.length}</strong> fechas.`
        })) return;

        fMananger.formModalLoding('modal_inventario_vehicular_asignar', 'show');
        const response = await fetch(`${url_base}/asignar`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': __token,
            },
            body: JSON.stringify({
                vehiculo_id: window.currentVehiculoId,
                eliminadas: cambios.eliminados,
                nuevas: cambios.nuevos
            }),
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
            const mensaje = data.message || 'No se pudo completar la operación.';
            return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
        }

        boxAlert.box({ h: data.message });
    } catch (error) {
        console.error('Error en la solicitud:', error);

        boxAlert.box({
            i: 'error',
            t: 'Error en la conexión',
            h: 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
        });
    } finally {
        fMananger.formModalLoding('modal_inventario_vehicular_asignar', 'hide');
    }
});

function marcarCheckboxes(userIds) {
    const todosLosCheckboxes = document.querySelectorAll('#personal_asignados input[type="checkbox"]');
    todosLosCheckboxes.forEach(checkbox => checkbox.checked = false);

    userIds.forEach(userId => {
        const elemento = document.querySelector(`#personal_asignados [data-user-id="${userId}"]`);
        if (elemento) {
            const checkbox = elemento.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.checked = true;
            }
        }
    });
}

function desmarcarCheckboxes() {
    const todosLosCheckboxes = document.querySelectorAll('#personal_asignados input[type="checkbox"]');
    todosLosCheckboxes.forEach(checkbox => checkbox.checked = false);
}

function compararArraysAsignados() {
    const marcados = [];
    const checkboxes = document.querySelectorAll('#personal_asignados input[type="checkbox"]:checked');

    checkboxes.forEach(checkbox => {
        const contenedor = checkbox.closest('[data-user-id]');
        if (contenedor) {
            marcados.push(parseInt(contenedor.getAttribute('data-user-id')));
        }
    });

    const nuevos = marcados.filter(id => !window.arrayAsignados.includes(id));
    const eliminados = window.arrayAsignados.filter(id => !marcados.includes(id));

    return { nuevos, eliminados };
}