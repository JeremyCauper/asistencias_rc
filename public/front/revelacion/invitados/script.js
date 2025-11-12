let invitados = [];
const tbody = document.querySelector("#tabla-invitados tbody");

// Consumir API
async function cargarInvitados() {
    try {
        tbody.innerHTML = "";
        const filaVacia = document.createElement("tr");
        filaVacia.innerHTML = `<td colspan="5">Cargando Datos...</td>`;
        tbody.appendChild(filaVacia);

        const response = await fetch(url_base + "/api/revelacion/listarInvitados");
        const data = await response.json();

        if (response.ok) {
            invitados = data.data;
            mostrarInvitados(invitados);
        } else {
            alert("Error: " + data.message);
        }
    } catch (error) {
        console.error("Error al conectar con la API:", error);
        alert("No se pudo conectar con el servidor");
    }
}

// Mostrar invitados en la tabla
function mostrarInvitados(lista) {
    tbody.innerHTML = "";
    if (lista.length === 0) {
        const filaVacia = document.createElement("tr");
        filaVacia.innerHTML = `<td colspan="5">No se encontraron invitados.</td>`;
        tbody.appendChild(filaVacia);
        return;
    }
    indice = 1;
    lista.forEach(inv => {
        const fila = document.createElement("tr");

        const team = [
            ['o', 'Niño'],
            ['a', 'Niña']
        ][inv.team];

        fila.innerHTML = `
                    <td>${indice}</td>
                    <td>${inv.nombres}</td>
                    <td>${inv.apellidos}</td>
                    <td><span class="badge nini${team[0]}">${team[1]}</span></td>
                    <td>${inv.fecha_confirmacion}</td>
                `;

        indice++;
        tbody.appendChild(fila);
    });
}

// Filtro de búsqueda
document.getElementById("buscar").addEventListener("keyup", function () {
    const valor = this.value.toLowerCase();
    const filtrados = invitados.filter(inv =>
        inv.nombres.toLowerCase().includes(valor) ||
        inv.apellidos.toLowerCase().includes(valor)
    );
    mostrarInvitados(filtrados);
});

// Cargar al iniciar
document.addEventListener("DOMContentLoaded", cargarInvitados);


// Variables globales
let url_sobre = url_base + "/revelacion/sobre";
// Mensaje con emojis DIRECTOS
const mensaje = `✨👶 ¡Hola familia y amigos! 👶✨
Con mucha alegría queremos invitarte a compartir con nosotros un momento muy especial: la revelación del sexo de nuestro bebé 💙💖

Haz clic en este enlace para abrir tu invitación digital 📩👇
👉 ${url_sobre}

¡No olvides confirmar tu asistencia en la misma invitación! Te esperamos con mucho cariño 💕`;

// Abrir vínculo
document.getElementById("btnAbrirVinculo").addEventListener("click", function () {
    window.open(url_sobre, "_blank");
});

// Copiar link
document.getElementById("copiar-link").addEventListener("click", async function () {
    if (navigator.share) {
        // Si el navegador soporta Web Share API
        try {
            await navigator.share({
                title: "Invitación especial 🎉",
                url: url_sobre, // opcional, lo agrega como link aparte
            });
            console.log("Compartido con éxito");
        } catch (err) {
            console.error("Error al compartir:", err);
        }
    } else {
        // fallback antiguo
        const tempInput = document.createElement("input");
        tempInput.value = url_sobre;
        document.body.appendChild(tempInput);
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
        alert("Enlace copiado");
    }
});

// Botón WhatsApp
const btn_ws = document.getElementById('btnWhatsapp');

btn_ws.addEventListener('click', () => {
    // Usamos la API oficial de WhatsApp (mejor soporte en PC y móvil)
    const url = "https://api.whatsapp.com/send?text=" + encodeURIComponent(mensaje);
    window.open(url, "_blank");
});