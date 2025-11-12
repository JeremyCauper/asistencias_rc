document.addEventListener("DOMContentLoaded", function () {
    if (localStorage.getItem("invitado")) {
        let invitado = JSON.parse(localStorage.getItem("invitado"));
        console.log(invitado);
        asistenciaConfirmada();
        document.querySelector('[data-team="' + invitado.team + '"]').classList.add('active');
        document.querySelector('.team-row').style.pointerEvents = 'none'; // Deshabilitar selección de team
        mostrarAlerta(`<b style="font-size: 1rem;">${invitado.nombres} ${invitado.apellidos}</b><br>¡Gracias por confirmar tu asistencia!, Nos vemos pronto.`);

        document.getElementById('closeBtnAlerta').addEventListener("click", () => {
            const audio = document.getElementById("musica");
            audio.play();
        }, { once: true });

        asistenciaConfirmada();
        return;
    }
});
