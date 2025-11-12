document.addEventListener("DOMContentLoaded", async () => {
    mostrarAlertaCambioPassword();
});

function mostrarAlertaCambioPassword() {
    Swal.fire({
        title: "<h5>Cambia tu contraseña</h5>",
        html: `
            <div style="text-align: left; font-size: 0.75rem; margin-bottom: 10px; color: #8e8e8e;">
                <p><strong class="text-danger">Nota:</strong> Por motivos de seguridad, es necesario actualizar tu contraseña predeterminada.</p>
                <p>Esta medida garantiza la protección de tu cuenta y tus datos personales. 
                Por favor, crea una nueva contraseña que cumpla con los siguientes requisitos:</p>
                <ul style="margin-left: 18px;">
                    <li>Al menos 6 caracteres</li>
                    <li>Al menos una letra mayúscula</li>
                    <li>Al menos un número</li>
                </ul>
            </div>
            <input class="form-control my-2" type="password" id="newPassword" placeholder="Nueva contraseña">
            <input class="form-control my-2" type="password" id="confirmPassword" placeholder="Confirmar contraseña">
        `,
        confirmButtonText: "Guardar",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didClose: () => setTimeout(mostrarAlertaCambioPassword, 100),
        preConfirm: async () => {
            const newPassword = document.getElementById("newPassword").value.trim();
            const confirmPassword = document.getElementById("confirmPassword").value.trim();

            // Validaciones frontend
            if (!validarPassword(newPassword)) {
                Swal.showValidationMessage("La contraseña no cumple los requisitos.");
                return false;
            }
            if (newPassword !== confirmPassword) {
                Swal.showValidationMessage("Las contraseñas no coinciden.");
                return false;
            }

            try {
                const res = await fetch(__url + "/personal/actualizar-password", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": __token
                    },
                    body: JSON.stringify({ password: newPassword })
                });

                const data = await res.json();
                if (!res.ok) throw new Error(data.message || "Error al actualizar");

                Swal.fire({
                    icon: "success",
                    title: "Contraseña actualizada",
                    text: "Tu nueva contraseña se guardó correctamente.",
                    confirmButtonText: "Aceptar"
                });

                $('#cambioPass').remove();

                return true;
            } catch (err) {
                Swal.showValidationMessage(err.message);
                return false;
            }
        }
    });
}

function validarPassword(pass) {
    // Al menos 6 caracteres, una mayúscula, una minúscula y un número
    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}$/;
    return regex.test(pass);
}

// Protección contra cierre manual o por consola
const originalClose = Swal.close;
Swal.close = function () {
    originalClose.apply(this, arguments);
    setTimeout(() => {
        if (!Swal.isVisible()) mostrarAlertaCambioPassword();
    }, 150);
};
