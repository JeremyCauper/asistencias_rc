class EditorJustificacion {
    constructor(selector, op = {}) {
        this.selector = selector;
        this.mediaMap = {};
        this.fileMap = [];
        this.botones = op.botones || ['link', 'image', 'video', 'pdf'];
        this.noPasteImg = op.noPasteImg || false;
        this.altura = op.altura || '400';

        $(selector).css({ height: this.altura });

        this._events = [];              // aquí guardamos listeners para limpiarlos después
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
                        camera: async () => await this.handleCamera(10)
                    }
                }
            }
        });

        this.customizeToolbarIcons({
            link: 'link',
            image: 'image',
            video: 'film',
            pdf: 'file-pdf',
            camera: 'camera'
        });

        const handler = () => {
            this.detectDeletedMedia();

            ($(this.selector)[0]).querySelectorAll('p[date-file]').forEach(p => {
                const hijos = Array.from(p.childNodes).filter(n => n.nodeType !== 3 || n.textContent.trim() !== '');
                if (hijos.length === 1 && hijos[0].nodeName === 'BR') {
                    p.removeAttribute('date-file');
                }
            });

            ($(this.selector)[0]).querySelectorAll('li[date-file]').forEach(p => {
                const hijos = Array.from(p.childNodes).filter(n => n.nodeType !== 3 || n.textContent.trim() !== '');
                if (hijos.length === 1 && hijos[0].nodeName === 'BR') {
                    p.removeAttribute('date-file');
                }
            });
        };
        this.quill.on('text-change', handler);

        // Guardamos el listener para futuro "destroy"
        this._events.push({ type: 'text-change', handler });

        /* CONTROL DE PEGADO IMÁGENES */
        const handler_paste = (e) => {
            const dt = e.clipboardData || e.originalEvent?.clipboardData;
            if (!dt) return;

            for (const item of dt.items) {
                if (item.type.startsWith("image/")) {

                    if (!this.noPasteImg) {
                        const file = item.getAsFile(); // ← Aquí recibes la imagen como File

                        const reader = new FileReader();
                        reader.onload = async () => {
                            const base64 = reader.result; // ← Aquí está el base64 completo

                            // Si no es base64, dejarlo tal cual
                            if (!base64.startsWith("data:image/")) return delta;

                            // Convertir base64 → File
                            const file = this.base64ToFile(base64);

                            // Subir mediante tu método de la clase
                            await this.uploadFile(file, "image");
                        };

                        reader.readAsDataURL(file);
                    }

                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        };

        this.quill.root.addEventListener("paste", handler_paste);
        this._events.push({ type: "paste", handler_paste });

        this.quill.root.addEventListener("click", e => {
            const el = e.target;

            if (el.tagName === "IMG") {
                mediaViewer.openImage(el.src);
            }
        });
    }

    /** ============================
     *  🔻 MÉTODO DESTROY
     * ============================ */
    destroy() {
        if (!this.quill) return;

        // 1. Remover eventos registrados
        this._events.forEach(ev => {
            this.quill.off(ev.type, ev.handler);
        });
        this._events = [];

        // 2. Destruir Quill
        this.quill = null;

        // 3. Remover toolbar
        const quill_toolbar = document.querySelector(`[data-quill-toolbar="${this.selector.replace('#', '')}"]`);
        if (quill_toolbar) quill_toolbar.remove();

        // 4. Limpieza del DOM (dejar vacío o restaurar)
        $(this.selector).html("");

        // 5. Limpiar referencias
        this.mediaMap = {};
        this.fileMap = [];
        this.botones = ['link', 'image', 'video', 'pdf'];
        this.nopasteImg = false;
        $(this.selector).css({ height: 0 });
        this._events = [];
    }

    /** ============================
     *  🔄 ACTUALIZAR OPCIONES
     * ============================
     *  Permite hacer:
     *      editor.updateOptions({ altura: 500, noPasteImg: true })
     *
     *  Reinicia Quill automáticamente.
     */
    updateOptions(newOptions = {}) {
        // Destruir Quill
        this.destroy();

        // Actualizar propiedades relevantes
        this.botones = newOptions.botones || this.botones;
        this.noPasteImg = newOptions.noPasteImg || false;
        this.altura = newOptions.altura || this.altura;

        // Actualizar altura si vino en opciones
        $(this.selector).css('height', this.altura || '400');

        // Reiniciar editor completamente
        this.init();
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

    async solicitarPermisoCamara() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(t => t.stop());
            return true;
        } catch (err) {
            return false;
        }
    }

    /** ============================
     *  🔹 CAPTURA CON CÁMARA
     * ============================ */
    async handleCamera(maxMB) {
        if (!esCelular()) {
            return boxAlert.box({ i: "warning", h: "Acción disponible solo en dispositivos móviles." });
        }

        const permiso = await navigator.permissions.query({
            name: "camera"
        });

        if (permiso.state === "prompt") {
            const ok = await solicitarPermisoCamara();

            // Revisar nuevamente el estado después de pedir permiso  
            const post = await navigator.permissions.query({
                name: "camera"
            });

            if (!ok || post.state === "denied") {
                return boxAlert.box({
                    i: "warning",
                    h: "Se denegó el acceso a la cámara, debe desbloquearlo desde los ajustes del navegador."
                });
            }
        }

        if (permiso.state === "denied") {
            return boxAlert.box({
                i: "warning",
                h: "Acceso a la cámara denegado, debe desbloquearlo desde los ajustes del navegador."
            });
        }

        const input = document.createElement('input');
        input.type = 'file';
        input.accept = "image/*";
        input.capture = "environment";

        const tiempoApertura = Date.now();  // Marca cuando abriste la cámara

        input.onchange = () => {
            const file = input.files[0];
            if (!file) return;

            const ahora = Date.now();

            // Calculamos cuánto tiempo pasó desde que se abrió la cámara
            const delta = ahora - file.lastModified;
            const deltaDesdeApertura = ahora - tiempoApertura;

            let fechaStr = date('H:i:s', file.lastModified);

            this.fileMap.push({
                name: file.name,
                size: file.size,
                type: file.type,
                lastModified: fechaStr
            });

            const desdeCamara = (delta < 15000) && (deltaDesdeApertura < 20000);

            const limit = maxMB * 1024 * 1024;
            if (file.size > limit) {
                return boxAlert.box({ i: "warning", h: `El achivo debe tener un tamaño menor de ${maxMB}MB.` });
            }

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
                return boxAlert.box({ i: "warning", h: `El achivo debe tener un tamaño menor de ${maxMB}MB.` });
            }

            this.uploadFile(file, tipo);
        };

        input.click();
    }

    async uploadFile(file, tipo) {
        try {
            let fileToUpload = file;
            let last_Modified = file.lastModified;

            /** ============================
             * 🔄 Convertir imágenes a WebP
             * ============================ */
            if (tipo === "image") {
                const converted = await this.convertToWebP(file);

                // ✅ Validar que la conversión fue exitosa
                if (!converted || converted.size === 0) {
                    throw new Error("No se pudo convertir la imagen. Inténtalo nuevamente.");
                }

                // ✅ Si la conversión devolvió el archivo original, no cambiar extensión
                const isConverted = converted.type === "image/webp";
                const newName = isConverted
                    ? file.name.replace(/\.[^.]+$/, "") + ".webp"
                    : file.name;

                fileToUpload = new File(
                    [converted],
                    newName,
                    { type: converted.type, lastModified: last_Modified }
                );
            }

            boxAlert.loading("Subiendo archivo...");

            const form = new FormData();
            form.append("file", fileToUpload);

            const res = await fetch(`${__url}/media-archivo/upload-media/justificaciones`, {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": __token
                },
                body: form
            });

            const data = await this.parseJsonSafe(res);

            if (!res.ok || !data.success) {
                throw new Error(data.message || "No se pudo completar la operación.");
            }

            Swal.close();
            const id = data.data.nombre_archivo;
            const url = (__asset.replaceAll('front', '')) + data.data.url;

            const range = this.quill.getSelection(true);
            this.insertFile(tipo, url, last_Modified, id, range.index);

        } catch (error) {
            console.log(error);
            boxAlert.box({
                i: "error",
                t: "Parece que hubo un problema",
                h: error.message || error || "Verifica tu conexión e inténtalo nuevamente."
            });
        }
    }

    async convertToWebP(file) {
        const sizeMB = file.size / (1024 * 1024);
        const quality = sizeMB > 3 ? 0.90 : 0.55;

        boxAlert.loading(`Convertiendo imagen, ${sizeMB.toFixed(2)}MB... (puede tardar un poco)`);

        return new Promise(resolve => {
            new Compressor(file, {
                quality: quality,
                convertSize: 0,
                mimeType: "image/webp",
                success(result) {
                    resolve(result);  // ✅ Devuelve el WebP convertido
                },
                error(err) {
                    console.error("Error al convertir WebP:", err);
                    resolve(null);  // ✅ Devuelve null para detectar el error
                }
            });
        });
    }

    async parseJsonSafe(response) {
        const contentType = response.headers.get("content-type");

        if (!contentType || !contentType.includes("application/json")) {
            throw new Error("Respuesta inesperada del servidor");
        }

        return response.json();
    }

    /** ============================
     *  🔹 INSERTAR CON ID
     * ============================ */
    insertFile(tipo, url, lastModified, id, index) {
        this.mediaMap = this.mediaMap || {};
        this.mediaMap[id] = { tipo, id };

        const acc = {
            image: () => this.quill.insertEmbed(index, 'image', url),
            video: () => this.quill.insertEmbed(index, 'video', url),
            pdf: () => {
                this.quill.insertEmbed(index, 'text', '')
                this.quill.clipboard.dangerouslyPasteHTML(index,
                    `<a href="${url}" target="_blank" style="color:blue; text-decoration:underline;">
                    📄 Ver PDF
                </a>`)
            }
        };

        acc[tipo]?.();

        setTimeout(() => {
            let etiqueta = {
                image: 'img',
                video: 'iframe'
            }[tipo] || false;

            if (!etiqueta) return;

            let archivo = $(this.selector).find(`${etiqueta}[data-id="${id}"]`)[0];
            const parent = tipo == 'image' ? archivo.parentElement : archivo;
            parent.setAttribute('date-file', date('Y-m-d H:i:s', lastModified));
        }, 300);
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
                delete this.mediaMap[id]; // limpiar registro
            }
        }
    }

    base64ToFile(base64) {
        const arr = base64.split(",");
        const mime = arr[0].match(/:(.*?);/)[1];
        const bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);

        while (n--) u8arr[n] = bstr.charCodeAt(n);

        return new File([u8arr], "pasted-image.webp", { type: mime });
    }

    /** ============================
     *  🔹 OBTENER HTML SIN URLS
     * ============================ */
    html() {
        const clone = this.quill.root.cloneNode(true);

        clone.querySelectorAll('img').forEach(el => {
            el.removeAttribute('src'); // quitar URL
        });

        clone.querySelectorAll('iframe').forEach(el => {
            el.removeAttribute('src');
            el.innerHTML = '';
        });

        clone.querySelectorAll('a').forEach(el => {
            el.removeAttribute('href');
        });

        return clone.innerHTML.trim();
    }

    isEmpty() {
        return this.quill.getText().trim().length === 0 &&
            !this.quill.root.innerHTML.includes('<img') &&
            !this.quill.root.innerHTML.includes('<iframe') &&
            !this.quill.root.innerHTML.includes('<a');
    }

    isEmptyImg() {
        return !this.quill.root.innerHTML.includes('<img');
    }

    clear() {
        this.quill.setContents([]);
    }
}