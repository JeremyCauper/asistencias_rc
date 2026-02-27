document.getElementById('form-login').addEventListener('submit', function (event) {
    event.preventDefault();
    let changue = false;
    let dataForm = {};

    Array.from(this.querySelectorAll('[name]')).some(function (elemento) {
        const require = elemento.getAttribute("require");
        if (elemento.getAttribute("require") && elemento.value == "") {
            $(`[aria-cinput="${require}"]`).prepend(`<span info-message="${require}" style="position: absolute; bottom: -20px; left: 3px; font-size: 0.7rem; color: #e4a11b;"><i class="fas fa-circle-info" style="font-size: 0.75rem"></i> El campo ${require} es requerido</span>`);
            changue = true;
            return true;
        }
        dataForm[elemento.name] = elemento.value;
    });
    if (changue) return false;

    $.ajax({
        type: 'POST',
        url: `${__url}/iniciar`,
        contentType: 'application/json',
        headers: {
            'X-CSRF-TOKEN': __token,
        },
        data: JSON.stringify(dataForm),
        beforeSend: function () {
            $('#btn-ingresar').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Iniciando...').attr('disabled', true);
        },
        success: function (response) {            
            if (response.success) {
                $('#btn-ingresar').html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Ingresando...')
                let ruta = response.data;
                window.location.href = __url + ruta;
            } else {
                alertLogin(response.message);
                console.log(response.message);
                $('#btn-ingresar').html('Ingresar').attr('disabled', false);
            }
        },
        error: async function (xhr, status, error) {
            console.log(xhr);
            const statusCode = xhr.status;
            let errorMessage = 'Ha ocurrido un error al intentar iniciar sesión.';

            switch (statusCode) {
                case 400:
                    errorMessage = 'Solicitud incorrecta. Verifica los datos ingresados.';
                    break;
                case 401:
                    errorMessage = 'Credenciales inválidas. Verifica tu usuario y contraseña.';
                    break;
                case 404:
                    errorMessage = 'No se encontró el recurso solicitado.';
                    break;
                case 419:
                    Swal.fire({
                        title: "La pagina venció",
                        text: "Recargue nuevamente la pagina",
                        icon: "warning",
                        showCancelButton: false,
                        confirmButtonColor: "#3085d6",
                        confirmButtonText: "Recargar",
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) location.reload();;
                    });
                    return true;
                    break;
                case 500:
                    errorMessage = 'Error interno del servidor. Inténtalo más tarde.';
                    break;
                default:
                    errorMessage = 'Error desconocido. Por favor, intenta de nuevo.';
            }
            console.log('Código de estado:', statusCode, ' mensaje de error:', error);
            boxAlert.box({ i: 'error', t: 'Ocurrio un error al iniciar session', h: errorMessage });
            $('#btn-ingresar').html('Ingresar').attr('disabled', false);
        }
    });
});

function funKeyup() {
    $(`[info-message="${this.getAttribute("require")}"]`).remove();
}

document.getElementById('login_asist_usuario').addEventListener('keyup', funKeyup);
document.getElementById('login_asist_password').addEventListener('keyup', funKeyup);

document.querySelector('.icon-pass').addEventListener('click', function () {
    let login_asist_password = document.getElementById('login_asist_password');
    let icono = this.querySelector('i');
    const isPassword = login_asist_password.type === 'password';
    icono.className = isPassword ? 'fas fa-eye' : 'fas fa-eye-slash';
    login_asist_password.type = isPassword ? 'text' : 'password';
});


function alertLogin(message) {
    const alertElement = $('[role="alert"]');
    alertElement.html(`<i class="fas fa-triangle-exclamation"></i> ${message}`);

    alertElement.slideDown();
    setTimeout(() => {
        alertElement.slideUp();
    }, 3000);
}