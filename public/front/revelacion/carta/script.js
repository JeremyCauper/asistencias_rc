let teamSeleccionado = null;
document.querySelectorAll('.team-box').forEach(box => {
    box.addEventListener('click', function () {
        document.querySelectorAll('.team-box').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        teamSeleccionado = this.getAttribute('data-team');
    });
});

document.querySelector(".btn-ubicacion").addEventListener("click", function () {
    // Opción 1: usando coordenadas
    // const lat = -12.091942299991093;
    // const lng = -77.00690883294467;
    // const url = `https://www.google.com/maps?q=${lat},${lng}`;

    // Opción 2: usando la dirección (menos exacta, depende de Google)
    const direccion = encodeURIComponent("Calle Sisley 158, San Borja");
    const url = `https://www.google.com/maps/search/?api=1&query=${direccion}`;

    window.open(url, "_blank"); // abre en una nueva pestaña
});

function asistenciaConfirmada() {
    let openModalBtn = document.getElementById("openModalBtn");
    openModalBtn.querySelector(".texto").innerHTML = "<span>Asistencia</span><small>confirmada</small>";
    openModalBtn.setAttribute("disabled", "true");
    openModalBtn.style.cursor = "not-allowed";
    openModalBtn.style.backgroundColor = "#a5e4a7ff";
    openModalBtn.style.pointerEvents = "none";
}

function getCurrentDateTime() {
    const now = new Date()

    const year = now.getFullYear()
    const month = String(now.getMonth() + 1).padStart(2, '0')
    const day = String(now.getDate()).padStart(2, '0')

    const hours = String(now.getHours()).padStart(2, '0')
    const minutes = String(now.getMinutes()).padStart(2, '0')
    const seconds = String(now.getSeconds()).padStart(2, '0')

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`
}

async function confirmarInvitacion() {
    let nombres = document.getElementById("nombres").value.trim();
    let apellidos = document.getElementById("apellidos").value.trim();
    if (!nombres || !apellidos) {
        mostrarAlerta("Por favor, ingresa tus nombres y apellidos.");
        return;
    }
    if (teamSeleccionado === null) {
        mostrarAlerta("Por favor, selecciona un team <b>(niño o niña)</b>.");
        return;
    }

    let datos = JSON.stringify({
        nombres: nombres,
        apellidos: apellidos,
        team: teamSeleccionado, // 0 para niño, 1 para niña
        fecha_confirmacion: getCurrentDateTime()
    });

    try {
        const response = await fetch(url_base + "/api/revelacion/confirmar", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json"
            },
            body: datos
        });

        const data = await response.json();
        console.log(data);

        if (response.ok) {
            mostrarAlerta(`<b style="font-size: 1rem;">${nombres} ${apellidos}</b><br>` + data.message); // Invitación confirmada
            localStorage.setItem("invitado", datos); // Guardamos los datos del invitado
            asistenciaConfirmada();
        } else {
            mostrarAlerta("Error: " + data.message);
        }

    } catch (error) {
        console.error("Error al conectar con la API:", error);
        mostrarAlerta("No se pudo conectar con el servidor");
    }
}

const modal = document.getElementById("myModal");
const openBtn = document.getElementById("openModalBtn");
const closeBtn = document.getElementById("closeBtn");
const confirmBtn = document.getElementById("confirmBtn");

openBtn.onclick = () => {
    // if (localStorage.getItem("invitado")) {
    //     let invitado = JSON.parse(localStorage.getItem("invitado"));
    //     console.log(invitado);
    //     asistenciaConfirmada();
    //     document.querySelector('[data-team="' + invitado.team + '"]').classList.add('active');
    //     document.querySelector('.team-row').style.pointerEvents = 'none'; // Deshabilitar selección de team
    //     mostrarAlerta(`<b style="font-size: 1rem;">${invitado.nombres} ${invitado.apellidos}</b><br>Ya has confirmado tu asistencia. ¡Gracias!`);
    //     return;
    // }
    modal.style.display = "flex";
}

closeBtn.onclick = () => {
    modal.style.display = "none";
}

confirmBtn.onclick = () => {
    confirmarInvitacion();
    modal.style.display = "none";
}

const modalAlerta = document.getElementById("myModalAlerta");
const closeBtnAlerta = document.getElementById("closeBtnAlerta");

closeBtnAlerta.onclick = () => {
    modalAlerta.style.display = "none";
}

function mostrarAlerta(mensaje) {
    document.getElementById("mensajeAlerta").innerHTML = mensaje;
    modalAlerta.style.display = "flex";
}

// Cerrar al hacer clic fuera del modal
window.onclick = (e) => {
    if (e.target === modal) {
        modal.style.display = "none";
    }
    if (e.target == modalAlerta) {
        modalAlerta.style.display = "none";
        const audio = document.getElementById("musica");
        if (audio.paused) {
            audio.play();
        }
    }
}