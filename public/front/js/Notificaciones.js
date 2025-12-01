class Notificaciones {
    constructor({ url_base, endpointListar, endpointMarcar }) {
        this.url_base = url_base;
        this.endpointListar = endpointListar;
        this.endpointMarcar = endpointMarcar;
        this.container = document.querySelector('#contenedor-notificaciones');
        this.btnReload = document.querySelector('[noti-btn="reload"]');
        this.storageKey = 'notificacion_accion_pendiente';

        this.TITULOS = {
            1: 'Derivación pendiente',
            2: 'Justificación Pendiente',
            3: 'Justificación de falta',
            4: 'Justificación de tardanza'
        };

        this.DESCRIPCION = {
            1: ':personal debes registrar tu llegada y subir evidencia.',
            2: ':personal registró una justificación de falta y requiere revisión.',
            3: ':personal registró una justificación de tardanza y requiere revisión.',
            4: ':personal registró una justificación de derivación y requiere revisión.',
        };

        this.RUTAS = {
            1: { url: '/asistencias-diarias' },
            2: { url: '/asistencias/misasistencias' }
        };

        this.ACCIONES = {
            1: (payload) => justificarDerivado(payload),
            2: (payload) => verJustificacion(payload),
        };
    }

    async init() {
        await this.cargar();
        this.ejecutarAccionPendiente();

        if (this.btnReload) {
            this.btnReload.addEventListener('click', () => {
                this.cargar();
            });
        }

        setInterval(() => { this.cargar(); }, 1 * 60 * 1000);
    }

    async cargar() {
        let lista = [];
        try {
            const res = await fetch(this.url_base + this.endpointListar);
            if (!res.ok) throw new Error('Error al listar notificaciones'); // console.error('Error al listar notificaciones');
            lista = await res.json();
        } catch (error) {
            console.error(error);
        }
        this.render(lista || []);
    }

    render(lista) {
        if (!this.container) return;
        const containerBody = this.container.querySelector('.dropdown-body');
        const badgeNotification = this.container.querySelector('.badge-notification');
        containerBody.innerHTML = '';
        badgeNotification.innerHTML = lista.length || '';

        if (lista.length) {
            lista.forEach(n => {
                const titulo = this.TITULOS[n.tipo_notificacion] ?? 'Notificación';
                const sigla = (n.is_admin == 0) ? '<i class="fas fa-user-tie"></i>' : n.sigla ?? '??';
                const descripcion = this.DESCRIPCION[n.descripcion_id] ?? '';
                const desc = descripcion.replace(':personal', n.nombre);

                const item = document.createElement('div');
                item.className = `dropdown-item p-2 my-1 rounded`;
                item.dataset.id = n.id;
                item.dataset.role = "button";
                item.innerHTML = `
                <small class="noti-hora">${n.creado}</small>
                <div class="noti-contenido d-flex align-items-center">
                    <span class="img-xs rounded-circle text-white acronimo"
                        style="background-color:#${(n.is_admin == 0) ? '000000' : '7367F0'};">
                        ${sigla}
                    </span>

                    <div class="mx-2">
                        <p class="fw-bold mb-1 titulo">${titulo}</p>
                        <small class="text-muted mb-0 descripcion">${desc}</small>
                    </div>
                </div>
            `;
                item.addEventListener('click', () => this.onClick(n));
                containerBody.appendChild(item);
            });
        } else {
            containerBody.innerHTML = `
            <div class="dropdown-text text-center text-muted py-3">
                Sin notificaciones
            </div>`;
        }
    }

    async onClick(noti) {
        const rutaObj = this.RUTAS[noti.ruta_id];
        if (!rutaObj) {
            console.warn('ruta_id no definida', noti.ruta_id);
            // ejecutar acción aquí si aplica
            this.ejecutarAccion(noti);
            return;
        }

        const destino = this.url_base + rutaObj.url;
        const actual = window.location.href;

        // Construimos payload parseado desde DB (si viene string)
        let payloadAccion = null;
        try {
            payloadAccion = noti.payload_accion ? (typeof noti.payload_accion === 'string' ? JSON.parse(noti.payload_accion) : noti.payload_accion) : null;
        } catch (e) {
            console.warn('Error parseando payload JSON', e);
        }

        // Si ya estás en la ruta: solo ejecutar acción
        if (actual === destino) {
            // pasar payload_accion a la acción
            this.ejecutarAccion({ notificacion_id: noti.id, accion_id: noti.accion_id, payload: payloadAccion });
            return;
        }

        // Si no estás: guardar acción pendiente y redirigir
        localStorage.setItem(this.storageKey, JSON.stringify({
            notificacion_id: noti.id,
            accion_id: noti.accion_id,
            payload: payloadAccion
        }));

        window.location.href = destino;
    }

    ejecutarAccion(p) {
        window.currentNotificacionId = p.notificacion_id;
        if (!p || !p.accion_id) return;
        const fn = this.ACCIONES[p.accion_id];
        if (typeof fn !== 'function') {
            console.warn('Acción no registrada:', p.accion_id);
            return;
        }
        try {
            fn(p.payload ?? null);
        } catch (e) {
            console.error('Error ejecutando acción:', e);
        }
    }

    ejecutarAccionPendiente() {
        const raw = localStorage.getItem(this.storageKey);
        if (!raw) return;
        localStorage.removeItem(this.storageKey);

        let data = null;
        try { data = JSON.parse(raw); } catch (e) { console.warn(e); return; }
        console.log(data);

        // Ejecutar la acción
        this.ejecutarAccion(data);
    }

    async marcarLeido(id) {
        const res = await fetch(`${this.url_base}${this.endpointMarcar}/${id}`);
        if (!res.ok) throw new Error('Error marcar leido');
        return res.json();
    }
}

$(document).ready(function () {
    window.noti = new Notificaciones({
        url_base: __url,
        endpointListar: '/notificaciones/listar',
        endpointMarcar: '/notificaciones/marcar',
    });
    noti.init();
});