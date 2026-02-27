class SelectableCalendar {
    constructor(elementId) {
        this.diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
        this.elemento = document.getElementById(elementId);
        this.fechaMinima = date('Y-m-d');
        this.calendar = null;

        this.init();
    }

    init() {
        this.calendar = new FullCalendar.Calendar(this.elemento, {
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

            dateClick: this.onDateClick.bind(this),
            dayCellDidMount: this.onDayCellDidMount.bind(this),
            eventDidMount: this.onEventDidMount.bind(this)
        });

        this.calendar.render();
    }

    onDateClick(info) {
        if (info.date < new Date(this.fechaMinima)) return;

        const existingEvent = this.calendar
            .getEvents()
            .find(e => e.startStr === info.dateStr);

        existingEvent ? existingEvent.remove() : this.addEvent(info.dateStr);
    }

    onDayCellDidMount(info) {
        if (new Date(info.date) < new Date(this.fechaMinima)) {
            info.el.classList.add("fc-day-disabled");
            info.el.style.cursor = "not-allowed";
        }
    }

    onEventDidMount(info) {
        info.el.style.cursor = "pointer";
        info.el.style.height = "28px";
        info.el.style.lineHeight = "28px";
    }

    addEvent(date) {
        this.calendar.addEvent({
            title: '✓✓',
            start: date,
            allDay: true,
            backgroundColor: '#3b71ca',
            borderColor: 'transparent',
            textColor: '#ffffff'
        });
    }

    loadDates(dates = []) {
        dates.forEach(d => this.addEvent(d));
    }

    clear() {
        this.calendar.getEvents().forEach(e => e.remove());
    }

    getSelectedDates() {
        return this.calendar.getEvents().map(e => e.startStr);
    }

    diffWith(sourceDates = []) {
        const events = this.calendar.getEvents();

        const removed = sourceDates.filter(
            d => !events.some(e => e.startStr === d)
        );

        const added = events
            .map(e => e.startStr)
            .filter(d => !sourceDates.includes(d));

        return { removed, added };
    }

    updateSize() {
        setTimeout(() => {
            this.calendar.updateSize();
        }, 300);
    }
}

const calendarVacaciones = new SelectableCalendar('calendarVacaciones');
const calendarDescansos = new SelectableCalendar('calendarDescansos');

/*let diasSemana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
let calendarioVacaciones = null;

let elementoCalendarioVacaciones = document.getElementById('calendarVacaciones');
let fechaMinima = date('Y-m-d');

calendarioVacaciones = new FullCalendar.Calendar(elementoCalendarioVacaciones, {
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

        let eventoExistente = calendarioVacaciones.getEvents().find(e => e.startStr === info.dateStr);

        if (eventoExistente) {
            eventoExistente.remove();
        } else {
            calendarioVacaciones.addEvent({
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

calendarioVacaciones.render();

function cargarFechas(fechasGuardadas) {
    fechasGuardadas.forEach(f => calendarioVacaciones.addEvent({
        title: '✓✓',
        start: f,
        allDay: true,
        backgroundColor: '#3b71ca',
        borderColor: 'transparent',
        textColor: '#ffffff'
    }));
}

function limpiarCalendario() {
    let eventos = calendarioVacaciones.getEvents();
    eventos.forEach(e => e.remove());
}

function getEvents() {
    return calendarioVacaciones.getEvents().map(e => e.startStr);
}

function extractDatesFromEvents() {
    let fechasOfEndpoint = window.currentFechasVacaciones || [];
    let eventos = calendario.getEvents();
    let fechasEliminadas = fechasOfEndpoint.filter(f => !eventos.some(e => e.startStr === f));
    let fechasNuevas = eventos.map(e => e.startStr).filter(f => !fechasOfEndpoint.includes(f));
    return { fechasEliminadas, fechasNuevas };
}*/