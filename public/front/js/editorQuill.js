class EditorJustificacion {
    constructor(selector, op = {}) {
        this.selector = selector;
        this.mediaMap = {};
        this.botones = op.botones || ['link', 'image', 'video', 'pdf'];
        const altura = op.altura || '400';

        $(selector).css({ height: altura });

        this.init();
    }

    /** ============================
     *  🔹 INICIALIZA QUILL
     * ============================ */
    init() {
        const toolbarBtns = [];

        if (this.botones.includes('link')) toolbarBtns.push('link');
        const mediaBtns = [];
        if (this.botones.includes('image')) mediaBtns.push('image');
        if (this.botones.includes('video')) mediaBtns.push('video');
        if (this.botones.includes('pdf')) mediaBtns.push('pdf');
        if (this.botones.includes('camera')) mediaBtns.push('camera');

        const toolbar = [
            ['bold', 'italic', 'underline'],
            [{ header: [1, 2, false] }],
            mediaBtns,
            [{ list: 'ordered' }, { list: 'bullet' }]
        ];

        this.quill = new Quill(this.selector, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: toolbar,
                    handlers: {
                        image: () => this.handleFileUpload('image', 'image/*', 3),
                        video: () => this.handleFileUpload('video', 'video/*', 10),
                        pdf: () => this.handleFileUpload('pdf', 'application/pdf', 5),
                        camera: () => this.handleCamera()
                    }
                }
            }
        });


        this.customizeToolbarIcons({
            link: 'link',
            image: 'file-image',
            video: 'file-video',
            pdf: 'file-pdf',
            camera: 'camera'
        });

        this.quill.on('text-change', () => {
            this.detectDeletedMedia();
        });

    }

    /** ============================
     *  🔹 ICONOS PERSONALIZADOS
     * ============================ */
    customizeToolbarIcons(icons) {
        setTimeout(() => {
            for (const [key, icon] of Object.entries(icons)) {
                const editor = document.getElementById('editor-justificar').parentNode;
                const customButton = editor.querySelector('.ql-' + key);
                if (customButton) customButton.innerHTML = `<i class="far fa-${icon}"></i>`; // emoji o ícono custom
            }
        }, 100);
    }

    /** ============================
     *  🔹 CAPTURA CON CÁMARA
     * ============================ */
    handleCamera() {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = "image/*";
        input.capture = "camera"; // abre cámara (Android directo, iPhone por menú)

        const tiempoApertura = Date.now();  // Marca cuando abriste la cámara

        input.onchange = () => {
            const file = input.files[0];
            if (!file) return;

            const ahora = Date.now();

            // Calculamos cuánto tiempo pasó desde que se abrió la cámara
            const delta = ahora - file.lastModified;
            const deltaDesdeApertura = ahora - tiempoApertura;

            /*
                ✔ Condición real:
                - Foto tomada hace menos de 15 segundos
                - Y la selección ocurrió poco después de abrir la cámara
            */
            const desdeCamara = (delta < 15000) && (deltaDesdeApertura < 20000);

            if (!desdeCamara) {
                boxAlert.box({
                    i: 'warning',
                    t: 'Foto no permitida',
                    h: 'La imagen debe ser tomada directamente desde la cámara y dentro de los primeros 15s de haber sido tomada.'
                });
                return; // ❌ Cancela subida
            }

            // Si pasó la validación, ahora sí se sube
            this.uploadFile(file, "image");
        };

        input.click();
    }

    /** ============================
     *  🔹 INPUT DE ARCHIVOS
     * ============================ */
    handleFileUpload(tipo, accept, maxMB) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = accept;

        input.onchange = () => {
            const file = input.files[0];
            if (!file) return;

            const limit = maxMB * 1024 * 1024;
            if (file.size > limit) {
                return alert(`Máximo ${maxMB}MB para ${tipo}`);
            }

            this.uploadFile(file, tipo);
        };

        input.click();
    }

    /** ============================
     *  🔹 SUBIDA AL BACKEND
     * ============================ */
    async uploadFile(file, tipo) {
        try {
            boxAlert.loading("Subiendo archivo...");

            const form = new FormData();
            form.append("file", file);

            const res = await fetch(`${__url}/asistencias/uploadMedia`, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": __token },
                body: form
            });

            const data = await res.json();
            if (!data.data.url) throw new Error("Error al subir");

            const id = data.data.nombre_archivo;
            const url = `${__url.replaceAll('public', 'storage/app/public/')}${data.data.url}`;

            const range = this.quill.getSelection(true);
            this.insertFile(tipo, url, file.name, id, range.index);

        } catch (e) {
            console.error(e);
            boxAlert.box({ i: "error", h: "No se pudo subir el archivo." });
        } finally {
            Swal.close();
        }
    }

    /** ============================
     *  🔹 INSERTAR CON ID
     * ============================ */
    insertFile(tipo, url, filename, id, index) {
        this.mediaMap = this.mediaMap || {};
        this.mediaMap[id] = { tipo, id };

        const acc = {
            image: () =>
                this.quill.clipboard.dangerouslyPasteHTML(
                    index,
                    `<img src="${url}" data-id="${id}" style="max-width:100%;">`
                ),
            video: () =>
                this.quill.clipboard.dangerouslyPasteHTML(
                    index,
                    `<video src="${url}" controls data-id="${id}" style="max-width:100%"></video>`
                ),
            pdf: () =>
                this.quill.clipboard.dangerouslyPasteHTML(
                    index,
                    `<a href="${url}" data-id="${id}" target="_blank">📄 ${filename}</a>`
                )
        };

        acc[tipo]?.();
    }

    detectDeletedMedia() {
        const editor = this.quill.root; // contenido del editor

        // Obtener todos los elementos activos con data-id
        const currentIds = Array.from(
            editor.querySelectorAll("[data-id]")
        ).map(el => el.getAttribute("data-id"));

        // Detectar eliminados
        for (const id in this.mediaMap) {
            if (!currentIds.includes(id)) {
                console.log("Eliminado:", this.mediaMap[id]);

                // Aquí haces lo que quieras:
                // - eliminar de una lista
                // - mandar al backend
                // - mostrar alerta
                // - etc

                delete this.mediaMap[id]; // limpiar registro
            }
        }
    }



    /** ============================
     *  🔹 OBTENER HTML SIN URLS
     * ============================ */
    html() {
        const clone = this.quill.root.cloneNode(true);

        clone.querySelectorAll('img').forEach(el => {
            el.removeAttribute('src'); // quitar URL
        });

        clone.querySelectorAll('video').forEach(el => {
            el.removeAttribute('src');
        });

        clone.querySelectorAll('a').forEach(el => {
            el.removeAttribute('href');
        });

        return clone.innerHTML.trim();
    }

    isEmpty() {
        return this.quill.getText().trim().length === 0 &&
            !this.quill.root.innerHTML.includes('<img') &&
            !this.quill.root.innerHTML.includes('<video') &&
            !this.quill.root.innerHTML.includes('<a');
    }

    isEmptyImg() {
        return !this.quill.root.innerHTML.includes('<img');
    }

    clear() {
        this.quill.setContents([]);
    }
}