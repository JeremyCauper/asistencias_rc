const RUTAS = {
    1: { url: '/asistencias-diarias' },
    2: { url: '/asistencias/misasistencias' }
};

const TITULOS = {
    1: 'Derivación pendiente',
    2: 'Justificación de falta',
    3: 'Justificación de tardanza',
    4: 'Justificación completada'
};

const ACCIONES = {
    1: (payload) => justificarDerivado(payload),
    2: (payload) => abrirModalJustificacion(payload),
    3: (payload) => cargarDetalleAsistencia(payload),
    4: (payload) => marcarComoVerificado(payload)
};

class Notificaciones {
    constructor({ url_base, endpointListar, endpointMarcar }) {
        this.url_base = url_base;
        this.endpointListar = endpointListar;
        this.endpointMarcar = endpointMarcar;
        this.container = document.querySelector('#contenedor-notificaciones');
        this.storageKey = 'notificacion_accion_pendiente';
    }

    async init() {
        await this.cargar();
        this.ejecutarAccionPendiente();
    }

    async cargar() {
        const res = await fetch(this.url_base + this.endpointListar, { credentials: 'same-origin' });
        if (!res.ok) return console.error('Error al listar notificaciones');
        const lista = await res.json();
        this.render(lista);
    }

    render(lista) {
        if (!this.container) return;
        this.container.innerHTML = '';

        lista.forEach(n => {
            const titulo = TITULOS[n.tipo_notificacion] ?? n.descripcion ?? 'Notificación';
            const desc = n.descripcion ?? '';
            const leidoClass = n.leido ? 'leido' : 'no-leido';

            const item = document.createElement('div');
            item.className = `noti-item ${leidoClass}`;
            item.dataset.id = n.id;
            item.innerHTML = `
        <div class="titulo">${titulo}</div>
        <div class="desc">${desc}</div>
        <small class="fecha">${n.created_at}</small>
      `;
            item.addEventListener('click', () => this.onClick(n));
            this.container.appendChild(item);
        });
    }

    async onClick(noti) {
        // marca leido en backend (no bloqueante)
        this.marcarLeido(noti.id).catch(e => console.warn(e));

        const rutaObj = RUTAS[noti.ruta_id];
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
            this.ejecutarAccion({ accion_id: noti.accion_id, payload: payloadAccion });
            return;
        }

        // Si no estás: guardar acción pendiente y redirigir
        localStorage.setItem(this.storageKey, JSON.stringify({
            accion_id: noti.accion_id,
            payload: payloadAccion,
            origen_notificacion_id: noti.id
        }));

        window.location.href = destino;
    }

    ejecutarAccion(p) {
        if (!p || !p.accion_id) return;
        const fn = ACCIONES[p.accion_id];
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

        // Ejecutar la acción
        this.ejecutarAccion(data);

        // opcional: notificar backend que acción pendiente ejecutada (marcar noti procesada)
        if (data.origen_notificacion_id) {
            fetch(`${this.url_base}${this.endpointMarcar}/${data.origen_notificacion_id}`, {
                method: 'POST',
                credentials: 'same-origin'
            }).catch(() => { });
        }
    }

    async marcarLeido(id) {
        const res = await fetch(`${this.url_base}${this.endpointMarcar}/${id}`);
        if (!res.ok) throw new Error('Error marcar leido');
        return res.json();
    }
}

const noti = new Notificaciones({
    url_base: __url,
    endpointListar: '/notificaciones/listar',
    endpointMarcar: '/notificaciones/marcar',
});
noti.init();


/*class Notificaciones {
    constructor(endpointListar, endpointMarcar) {
        this.endpointListar = endpointListar;
        this.endpointMarcar = endpointMarcar;
        this.contenedor = document.querySelector('#contenedor-notificaciones');
    }

    async cargar() {
        const data = await fetch(this.endpointListar).then(r => r.json());
        this.renderizar(data);
        this.ejecutarAccionPendiente();
    }

    renderizar(lista) {
        this.contenedor.innerHTML = '';

        lista.forEach(n => {
            const item = document.createElement('div');
            item.className = 'noti-item ' + (n.leido ? 'leido' : '');

            const titulo = TITULOS_NOTIFICACIONES[n.tipo] ?? n.titulo;

            item.innerHTML = `
                <div class="titulo">${titulo}</div>
                <div class="mensaje">${n.mensaje ?? ''}</div>
                <div class="fecha">${n.created_at}</div>
            `;

            item.onclick = () => this.abrir(n);
            this.contenedor.appendChild(item);
        });
    }

    async abrir(noti) {
        await fetch(`${this.endpointMarcar}/${noti.id}`);

        const urlDestino = RUTAS_SISTEMA[noti.ruta];

        if (!urlDestino) {
            console.warn('Ruta no definida:', noti.ruta);
            return;
        }

        const urlActual = window.location.pathname;

        // Si ya estás en la ruta, ejecuta acción JS al toque
        if (urlActual === urlDestino) {
            this.ejecutarAccionJS(noti);
            return;
        }

        // Si no estás en la ruta, prepara la acción para después
        localStorage.setItem('accion_pendiente', noti.accion_js);

        // Redirige
        window.location.href = urlDestino;
    }

    ejecutarAccionPendiente() {
        const accion = localStorage.getItem('accion_pendiente');
        if (accion) {
            localStorage.removeItem('accion_pendiente');
            try {
                eval(accion);
            } catch (e) {
                console.error('Error ejecutando la acción pendiente:', e);
            }
        }
    }

    ejecutarAccionJS(noti) {
        if (!noti.accion_js) return;
        try {
            eval(noti.accion_js);
        } catch (e) {
            console.error('Error ejecutando acción JS:', e);
        }
    }
}*/

/*class Notificaciones {
    constructor(endpoint, contenedor) {
        this.endpoint = endpoint;
        this.contenedor = document.querySelector(contenedor);
    }

    async cargar() {
        try {
            const res = await fetch(this.endpoint);
            const data = await res.json();

            if (!data || !data.notificaciones || data.notificaciones.length === 0) {
                this.contenedor.innerHTML = `
                    <li class="dropdown-item text-center text-muted py-3">
                        Sin notificaciones
                    </li>`;
                return;
            }

            this.contenedor.innerHTML = data.notificaciones
                .map(n => this._crearItem(n))
                .join('');

        } catch (e) {
            console.error("Error cargando notificaciones:", e);
        }
    }

    // ============================================================
    //   CREACIÓN DE ITEM HTML
    // ============================================================
    _crearItem(n) {
        const sigla = this._crearSigla(n.nombre_usuario);
        const color = this._color(sigla);
        const tipoUsuario = this._tipo(n.tipo_usuario);

        return `
<li class="dropdown-item p-3" role="button" onclick="${n.accion_js}">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <span class="img-xs rounded-circle text-white acronimo"
                style="background-color:${color};">
                ${sigla}
            </span>

            <div class="mx-2">
                <p class="fw-bold mb-1 nombre_usuario">${n.nombre_usuario}</p>
                <p class="text-muted mb-0 descripcion">${n.descripcion}</p>
            </div>
        </div>

        <span class="badge rounded-pill"
            style="background-color:${color};">
            ${tipoUsuario}
        </span>
    </div>
</li>`;
    }

    // ============================================================
    //   HELPERS
    // ===========================================================

    _crearSigla(nombre) {
        if (!nombre) return "NA";
        const partes = nombre.trim().split(/\s+/);
        const letras = partes.map(p => p[0].toUpperCase());
        return letras.slice(0, 2).join('');
    }

    _color(sigla) {
        const mapa = {
            A: "#5A8DEE",
            B: "#39DA8A",
            C: "#FF5B5C",
            D: "#FDAC41",
            E: "#00CFDD"
        };

        const letra = sigla[0]?.toUpperCase() ?? 'Z';
        return mapa[letra] || "#7367F0";
    }

    _tipo(tipo) {
        return tipo == 0 ? "Admin" : "Técnico";
    }
}

const notificaciones = new Notificaciones(
    __url + "/notificaciones/listar",       // tu endpoint Laravel
    "#contenedor-notificaciones" // el UL o DIV donde van los <li>
);

notificaciones.cargar();
*/