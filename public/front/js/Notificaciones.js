class Notificaciones {
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

    /* ============================================================
       CREACIÓN DE ITEM HTML
       ============================================================ */
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

    /* ============================================================
       HELPERS
       ============================================================ */

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
