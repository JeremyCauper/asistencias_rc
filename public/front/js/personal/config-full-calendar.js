let diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
let calendario = null;
let eventoTemporal = null;
let anioMemoria = null;
let añosCargados = {}; // Registro de años ya cargados para evitar duplicados

let elementoCalendario = document.getElementById('calendar');

let fechaMinima = '2025-12-01';

calendario = new FullCalendar.Calendar(elementoCalendario, {
    initialView: 'dayGridMonth',
    selectable: true,
    locale: 'es',

    headerToolbar: {
        left: 'title',
        center: '',
        right: 'prev,next today'
    },

    buttonText: { today: 'Hoy' },
    titleFormat: { year: 'numeric', month: 'long' },
    eventOrder: "start,-duration",

    dateClick: function (info) {
        if (info.date < new Date(fechaMinima)) return;  // bloqueo por seguridad

        let eventoExistente = calendario.getEvents().find(e => e.startStr === info.dateStr);

        if (eventoExistente) {
            eventoExistente.remove();
        } else {
            calendario.addEvent({
                title: '✓✓',
                start: info.dateStr,
                allDay: true,
                backgroundColor: '#3b71ca',
                borderColor: 'transparent',
                textColor: '#ffffff'
            });
        }
    },

    dayCellDidMount: function (info) {
        if (new Date(info.date) < new Date(fechaMinima)) {
            info.el.classList.add("fc-day-disabled");
            info.el.style.cursor = "not-allowed";
            // info.el.style.pointerEvents = "none"; // opcional si quieres bloquear clic visualmente
        }
    },

    eventDidMount: function (info) {
        info.el.style.cursor = "pointer";
        info.el.style.height = "28px";
        info.el.style.lineHeight = "28px";
    }
});

calendario.render();

function cargarFechas(fechasGuardadas) {
    fechasGuardadas.forEach(f => calendario.addEvent({
        title: '✓✓',
        start: f,
        allDay: true,
        backgroundColor: '#3b71ca',
        borderColor: 'transparent',
        textColor: '#ffffff'
    }));
}

function limpiarCalendario() {
    let eventos = calendario.getEvents();
    eventos.forEach(e => e.remove());
}

function getEvents() {
    return calendario.getEvents().map(e => e.startStr);
}

function extractDatesFromEvents() {
    let fechasOfEndpoint = window.currentFechasVacaciones || [];
    let eventos = calendario.getEvents();
    let fechasEliminadas = fechasOfEndpoint.filter(f => !eventos.some(e => e.startStr === f));
    let fechasNuevas = eventos.map(e => e.startStr).filter(f => !fechasOfEndpoint.includes(f));
    return { fechasEliminadas, fechasNuevas };
}