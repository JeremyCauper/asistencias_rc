class MediaViewer {
    /* ============================
     *  🔹 MEDIA VIEWER
     * ============================ */
    constructor() {
        // Crear contenedor si no existe
        if (!document.querySelector("#media-viewer")) {
            $("body").append(`<div id="media-viewer" class="media-viewer" style="display: none;"><div class="media-content" style="text-align: center;"></div><button class="media-close">✕</button></div>`);
            $("head").append(`<style id="media-viewer-style">.media-viewer {position: fixed;inset: 0;background: rgba(0,0,0,0.85);display: flex;justify-content: center;align-items: center;z-index: 999999;padding: 20px;}.media-content img,.media-content video {max-width: 100%;max-height: 80vh;border-radius: 8px;transition: transform 0.15s ease;cursor: grab;}.media-close {position: absolute;top: 25px;right: 25px;font-size: 28px;background: hsla(0, 0%, 98%, 0);border: none;color: white;padding: 6px 12px;cursor: pointer;border-radius: 5px;transition: background .3s ease-in-out;}.media-close:hover {background: hsla(0, 0%, 98%, 0.2);}</style>`);
        }
        this.viewer = document.querySelector("#media-viewer");
        const style_viewer = document.querySelector("#media-viewer-style");
        this.content = this.viewer.querySelector(".media-content");
        const closeBtn = this.viewer.querySelector(".media-close");

        this.zoomScale = 1;

        const btn_close = () => {
            $(this.viewer).fadeOut(200);
            this.zoomScale = 1;
            this.content.innerHTML = "";
        }

        closeBtn.addEventListener("click", () => btn_close());
        this.viewer.addEventListener("click", (e) => {
            if (e.target !== this.viewer) return;
            btn_close();
        });
        // document.addEventListener("keydown", e => {
        //     if (e.key === "Escape") btn_close();
        // });
    }

    openImage(src) {
        this.zoomScale = 1;
        this.content.innerHTML = `<img src="${src}" style="transform-origin: center center;">`;

        const img = this.content.querySelector("img");

        /* ============================================================
         * 🔹 SISTEMA DE ARRASTRE + LIMITES (AÑADIDO)
         * ============================================================ */
        img.addEventListener("dragstart", e => e.preventDefault());
        img.style.userSelect = "none";
        img.style.pointerEvents = "auto";

        let isDragging = false;
        let startX = 0;
        let startY = 0;
        let translateX = 0;
        let translateY = 0;

        const applyTransform = () => {
            img.style.transform = this.zoomScale == 1 ? "" : `translate(${translateX}px, ${translateY}px) scale(${this.zoomScale})`;
            if (this.zoomScale == 1) {
                isDragging = false;
                startX = 0;
                startY = 0;
                translateX = 0;
                translateY = 0;
            }
        };

        const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

        img.addEventListener("mousedown", e => {
            if (this.zoomScale <= 1) return;

            isDragging = true;
            img.style.cursor = "grabbing";

            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
        });

        window.addEventListener("mousemove", e => {
            if (!isDragging) return;

            translateX = e.clientX - startX;
            translateY = e.clientY - startY;

            // Limites:
            const maxX = (img.clientWidth * this.zoomScale - img.clientWidth) / 2;
            const maxY = (img.clientHeight * this.zoomScale - img.clientHeight) / 2;

            translateX = clamp(translateX, -maxX, maxX);
            translateY = clamp(translateY, -maxY, maxY);

            applyTransform();
        });

        window.addEventListener("mouseup", () => {
            isDragging = false;
            img.style.cursor = this.zoomScale > 1 ? "grab" : "zoom-in";
        });

        /* ============================================================ */
        // Zoom con rueda (tu código intacto)
        img.addEventListener("wheel", e => {
            e.preventDefault();
            const delta = e.deltaY > 0 ? -0.1 : 0.1;
            this.zoomScale = Math.min(Math.max(1, this.zoomScale + delta), 5);
            applyTransform();
        });

        // Zoom doble click (tu código intacto)
        img.addEventListener("dblclick", () => {
            this.zoomScale = this.zoomScale === 1 ? 3 : 1;
            translateX = 0;
            translateY = 0;
            applyTransform();
        });

        $(this.viewer).fadeIn(200);
    }
}

const mediaViewer = new MediaViewer();