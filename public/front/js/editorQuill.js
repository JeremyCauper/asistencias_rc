class EditorJustificacion {
    constructor(selector, op = {}) {
        this.selector = selector;
        this.filesUp = [];
        const altura = op.altura || '400';
        $(selector).css({ 'height': altura });

        this.init();
    }

    /** ============================
     *  🔹 INICIALIZA QUILL
     * ============================ */
    init() {
        this.quill = new Quill(this.selector, {
            theme: 'snow',
            modules: {
                toolbar: {
                    container: [
                        ['bold', 'italic', 'underline'],
                        [{ 'header': [1, 2, false] }],
                        ['link', 'image', 'video', 'pdf'],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }]
                    ],
                    handlers: {
                        image: () => this.handleFileUpload('image', 'image/*', 3),
                        video: () => this.handleFileUpload('video', 'video/*', 10),
                        pdf: () => this.handleFileUpload('pdf', 'application/pdf', 5)
                    }
                }
            }
        });

        this.customizeToolbarIcons({
            link: 'link',
            image: 'file-image',
            video: 'file-video',
            pdf: 'file-pdf'
        });
    }

    /** ============================
     *  🔹 ICONOS PERSONALIZADOS
     * ============================ */
    customizeToolbarIcons(icons) {
        setTimeout(() => {
            for (const [key, icon] of Object.entries(icons)) {
                const customButton = document.querySelector('.ql-' + key);
                if (customButton) customButton.innerHTML = `<i class="far fa-${icon}"></i>`; // emoji o ícono custom
            }
        }, 100);
    }

    /** ============================
     *  🔹 INPUT DE ARCHIVOS
     * ============================ */
    handleFileUpload(tipo, accept, maxMB) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = accept;
        input.click();

        input.onchange = async () => {
            const file = input.files[0];
            if (!file) return;

            const limite = maxMB * 1024 * 1024;
            if (file.size > limite) {
                return alert(`Máximo ${maxMB}MB para ${tipo}s`);
            }

            await this.uploadFile(file, tipo);
        };
    }

    /** ============================
     *  🔹 SUBIR ARCHIVO AL BACKEND
     * ============================ */
    async uploadFile(file, tipo) {
        try {
            boxAlert.loading('Subiendo documento...');

            const formData = new FormData();
            formData.append('file', file);

            const res = await fetch(`${__url}/asistencias/uploadMedia`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': __token },
                body: formData
            });

            const data = await res.json();
            if (!data.data.url) throw new Error('Error al subir archivo');

            const range = this.quill.getSelection(true);
            const fullUrl = `${__url.replaceAll('public', '')}${data.data.url}`;
            this.filesUp.push(data.data.archivo_id);

            this.insertFile(tipo, fullUrl, file.name, range.index);
        } catch (e) {
            console.error(e);
            boxAlert.box({ i: 'error', h: 'No se pudo subir el archivo.' });
        } finally {
            Swal.close();
        }
    }

    /** ============================
     *  🔹 INSERTAR SEGÚN EL TIPO
     * ============================ */
    insertFile(tipo, url, fileName, index) {
        const acciones = {
            image: () => this.quill.insertEmbed(index, 'image', url),
            video: () => this.quill.insertEmbed(index, 'video', url),
            pdf: () =>
                this.quill.clipboard.dangerouslyPasteHTML(
                    index,
                    `<a href="${url}" target="_blank">📄 ${fileName}</a>`
                )
        };

        acciones[tipo]?.();
    }
}