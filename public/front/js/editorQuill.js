class EditorJustificacion {
    constructor(selector, op = {}) {
        this.selector = selector;
        this.mediaMap = {};
        this.fileMap = [];
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

        const mediaBtns = [];
        if (this.botones.includes('link')) mediaBtns.push('link');
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
                        image: () => this.handleFileUpload('image', 'image/*', 10),
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
                const editor = document.getElementById(this.selector.replace('#', '')).parentNode;
                const customButton = editor.querySelector('.ql-' + key);
                if (customButton) customButton.innerHTML = `<i class="far fa-${icon}"></i>`; // emoji o ícono custom
            }
        }, 100);
    }

    /** ============================
     *  🔹 CAPTURA CON CÁMARA
     * ============================ */
    handleCamera() {
        if (!esCelular()) {
            return boxAlert.box({ i: "warning", h: "Función solo disponible en dispositivos móviles." });
        }

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

            const fecha = new Date(file.lastModified);
            const horas = fecha.getHours();        // 0–23
            const minutos = fecha.getMinutes();    // 0–59
            const segundos = fecha.getSeconds();   // 0–59
            const pad = n => String(n).padStart(2, '0');

            this.fileMap.push({
                name: file.name,
                size: file.size,
                type: file.type,
                lastModified: `${pad(horas)}:${pad(minutos)}:${pad(segundos)}`
            });
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
                    h: 'La imagen debe ser tomada directamente desde la cámara y dentro de los primeros 15s de haber abierto la cámara.'
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
                return boxAlert.box({ i: "warning", h: `Máximo ${maxMB}MB para ${tipo}` });
            }

            this.uploadFile(file, tipo);
        };

        input.click();
    }

    async convertToWebP(file) {
        const sizeMB = file.size / (1024 * 1024);
        const quality = sizeMB > 3 ? 0.90 : 0.45;

        boxAlert.loading(`Convertiendo imagen, ${sizeMB.toFixed(2)}MB... (puede tardar un poco)`);

        return new Promise(resolve => {
            new Compressor(file, {
                quality: quality,
                convertSize: 0,                 // convertir todo, incluso < 2MB
                mimeType: "image/webp",         // salida WebP
                success(result) {
                    resolve(result);            // devuelve el archivo WebP
                },
                error(err) {
                    console.error("Error al convertir WebP:", err);
                    resolve(file);              // si falla, devuelve el original
                }
            });
        });
    }


    /** ============================
     *  🔹 SUBIDA AL BACKEND
     * ============================ */
    async uploadFile(file, tipo) {
        try {

            let fileToUpload = file;

            /** ============================
             * 🔄 Convertir imágenes a WebP
             * ============================ */
            if (tipo === "image") {
                const converted = await this.convertToWebP(file);
                fileToUpload = new File(
                    [converted],
                    file.name.replace(/\.[^.]+$/, "") + ".webp",
                    { type: "image/webp" }
                );
            }

            boxAlert.loading("Subiendo archivo...");
            const form = new FormData();
            form.append("file", fileToUpload);

            const res = await fetch(`${__url}/media-archivo/upload-media/justificaciones`, {
                method: "POST",
                headers: { "X-CSRF-TOKEN": __token },
                body: form
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                const mensaje = data.message || 'No se pudo completar la operación.';
                return boxAlert.box({ i: 'error', t: 'Algo salió mal...', h: mensaje });
            }

            if (!data.data?.url) throw new Error(data.message || "Error al subir");

            Swal.close();
            const id = data.data.nombre_archivo;
            const url = `${__url.replaceAll('/public', '')}/${data.data.url}`;

            const range = this.quill.getSelection(true);
            this.insertFile(tipo, url, fileToUpload.name, id, range.index);
        } catch (error) {
            console.log(error);
            boxAlert.box({
                i: 'error',
                t: 'Error en la conexión',
                h: error.message || 'Ocurrió un problema al procesar la solicitud. Verifica tu conexión e intenta nuevamente.'
            });
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
                // console.log("Eliminado:", this.mediaMap[id]);

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