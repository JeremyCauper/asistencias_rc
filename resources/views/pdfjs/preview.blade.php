<!DOCTYPE html>
<html lang="es" data-mdb-theme="dark" class="h-100 overflow-hidden">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Font Awesome -->
    <link href="{{ secure_asset($ft_css->mdb_all_min6_0_0) }}" rel="stylesheet">
    <!-- MDB -->
    <link href="{{ secure_asset($ft_css->mdb_min7_2_0) }}" rel="stylesheet">
    <!-- JQuery -->
    <script src="{{ secure_asset($ft_js->jquery) }}"></script>
    <title>Visor PDF</title>

    <style>
        header nav {
            height: 54px !important;
        }

        header * {
            color: #ffffff;
        }

        main {
            height: calc(100vh - 115px);
        }

        footer nav {
            height: 60px !important;
        }

        #page_num_input,
        #page_count {
            font-size: .9rem;
        }

        #page_num_input {
            text-align: center;
            width: 40px;
            display: inline;
        }

        #porcentaje_zoom {
            font-size: .75rem;
            background-color: rgb(30, 30, 30);
        }

        /* @media (max-width: 768px) {
        } */
    </style>
</head>

<body class="overflow-hidden h-100">
    <header>
        <!-- Navbar -->
        <nav class="navbar bg-body py-1"> <!--  fixed-top -->
            <div class="container-fluid">
                <div class="navbar-brand">
                    <button type="button" class="btn btn-link btn-floating" id="zoomOut" data-mdb-ripple-init>
                        <i class="fas fa-search-minus"></i>
                    </button>
                    <span id="porcentaje_zoom" class="mx-1 py-1 px-2 rounded"></span>
                    <button type="button" class="btn btn-link btn-floating" id="zoomIn" data-mdb-ripple-init>
                        <i class="fas fa-search-plus"></i>
                    </button>
                    <button type="button" class="btn btn-link btn-floating d-none" id="reload_zoom"
                        data-mdb-ripple-init>
                        <i class="fas fa-arrow-rotate-right"></i>
                    </button>
                </div>
                <button type="button" id="descargar" class="btn btn-link btn-floating" data-mdb-ripple-init>
                    <i class="fas fa-download"></i>
                </button>
            </div>
        </nav>
        <!-- Navbar -->
    </header>
    <main id="pdf-container" class="overflow-auto p-2">
        <div id="canvas-container" class="text-center">
            <canvas id="pdf_canvas" class="w-100"></canvas>
        </div>
    </main>
    <footer>
        <!-- Navbar -->
        <nav class="navbar bg-body"> <!--  fixed-bottom -->
            <div class="d-flex align-items-center m-auto py-1">
                <i type="button" class="fas fa-arrow-circle-left" style="font-size: 1.5em;" id="prev"></i>
                <div class="px-3">
                    <span>
                        <input type="text" class="form-control" id="page_num_input" value="1" onfocus="this.select()">
                    </span> / <span id="page_count"></span>
                </div>
                <i type="button" class="fas fa-arrow-circle-right" style="font-size: 1.5em;" id="next"></i>
            </div>
        </nav>
        <!-- Navbar -->
    </footer>

    <!-- MDB -->
    <script type="text/javascript" src="{{secure_asset($ft_js->mdb_umd_min7_2_0)}}"></script>
    <<script src="{{ secure_asset($ft_js->pdf_js) }}"></script>
    <script src="{{ secure_asset($ft_js->pdf_worker_js) }}"></script>
    <script>
        // 1) Cadena Base64 del PDF (asegúrate de inyectarlo correctamente)
        const base64pdf = "{{ $base64_pdf }}";  // Asegúrate de que esta variable esté correctamente configurada.

        document.addEventListener("DOMContentLoaded", function () {
            try {
                if (base64pdf) {
                    // 2) Convierte Base64 a Uint8Array
                    function base64ToUint8Array(b64) {
                        const raw = atob(b64);                      // decodifica Base64
                        const arr = new Uint8Array(raw.length);
                        for (let i = 0; i < raw.length; i++) {
                            arr[i] = raw.charCodeAt(i);             // cada carácter a su byte correspondiente
                        }
                        return arr;
                    }

                    // 4) Carga y renderiza la primera página
                    const pdfData = base64ToUint8Array(base64pdf);
                    let pdfDoc = null;
                    let pageNum = 1;
                    let pageRendering = false;
                    let pageNumPending = null;
                    let scale = 1;  // Define el zoom inicial

                    pdfjsLib.getDocument({ data: pdfData }).promise.then((pdfDoc_) => {
                        pdfDoc = pdfDoc_;
                        // document.getElementById('loading-message').style.display = 'none';
                        document.getElementById('page_count').textContent = pdfDoc.numPages;
                        renderPage();
                    })
                        .catch((err) => {
                            console.error('Error al cargar el PDF:', err);
                            // document.getElementById('loading-message').style.display = 'none';
                            document.getElementById('pdf-container').innerHTML = '<div class="error">No se pudo cargar el PDF.</div>';
                        });

                    let page_num_input = document.getElementById('page_num_input');

                    const canvas = document.getElementById('pdf_canvas');
                    const container = document.getElementById('pdf-container');
                    const context = canvas.getContext('2d');

                    function renderPage() {
                        pdfDoc.getPage(pageNum).then(function (page) {
                            scale = parseFloat(scale.toFixed(1));
                            let viewport = page.getViewport({ scale });

                            canvas.width = viewport.width;
                            canvas.height = viewport.height;
                            const classesToAdd = 'd-flex justify-content-center'.split(' ');
                            if (scale == 1) {
                                canvas.classList.add('w-100');
                                container.classList.add(...classesToAdd);
                            } else {
                                canvas.classList.remove('w-100');
                                container.classList.remove(...classesToAdd);
                            }

                            const renderContext = {
                                canvasContext: context,
                                viewport: viewport
                            };
                            page.render(renderContext);
                        });
                        page_num_input.value = pageNum;
                        hidden_reload_zoom();
                    }

                    page_num_input.addEventListener('input', function () {
                        this.value = this.value.replace(/\D/g, '');
                    });

                    // Agrega el evento para el input
                    page_num_input.addEventListener('change', async function (event) {
                        const inputPageNum = parseInt(this.value, 10);
                        if (inputPageNum >= 1 && inputPageNum <= pdfDoc.numPages) {
                            pageNum = inputPageNum;
                            if (pageRendering) {
                                pageNumPending = pageNum;
                            } else {
                                renderPage();
                            }
                        } else {
                            // alert(`Por favor, ingrese un número de página válido (1 - ${pdfDoc.numPages}).`);
                            this.value = pageNum;
                        }
                        this.select();
                    });

                    document.getElementById('prev').addEventListener('click', function () {
                        if (pageNum > 1) {
                            pageNum--;
                            renderPage();
                        }
                    });

                    // Función para renderizar la página anterior
                    document.getElementById('next').addEventListener('click', function () {
                        if (pageNum < pdfDoc.numPages) {
                            pageNum++;
                            renderPage();
                        }
                    });

                    let reload_zoom = document.getElementById('reload_zoom');
                    let zoomOut = document.getElementById("zoomOut");
                    let zoomIn = document.getElementById('zoomIn');
                    let descargar = document.getElementById('descargar');

                    function hidden_reload_zoom() {
                        if (scale == 1) {
                            zoomOut.disabled = true;
                            reload_zoom.classList.add('d-none');
                        } else {
                            zoomOut.disabled = false;
                            reload_zoom.classList.remove('d-none');
                        }
                        document.getElementById('porcentaje_zoom').innerHTML = (((scale.toFixed(1)).toString()).replace('.', '')).padEnd(3, '0') + '%';
                    }

                    // Zoom en y zoom out
                    zoomOut.addEventListener('click', function () {
                        scale -= 0.1;
                        renderPage();
                    });

                    zoomIn.addEventListener('click', function zoomIn() {
                        scale += 0.1;
                        renderPage();
                    });

                    reload_zoom.addEventListener('click', function () {
                        scale = 1;
                        renderPage();
                    });


                    descargar.addEventListener('click', function () {
                        const nombreArchivo = "Documento.pdf";

                        const byteCharacters = atob(base64pdf);
                        const byteArray = new Uint8Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteArray[i] = byteCharacters.charCodeAt(i);
                        }
                        const blob = new Blob([byteArray], { type: 'application/pdf' });
                        const blobUrl = URL.createObjectURL(blob);

                        const link = document.createElement('a');
                        link.href = blobUrl;
                        link.download = nombreArchivo;
                        link.click();
                        URL.revokeObjectURL(blobUrl);
                    });
                } else {
                    document.getElementById('pdf-container').innerHTML = '<div class="error">El PDF no está configurado correctamente.</div>';
                }
            } catch (error) {
                document.getElementById('pdf-container').innerHTML = '<div class="error">' + error + '</div>';
            }
        });
    </script>
</body>

</html>