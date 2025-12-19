<?php
if (isset($_GET['view'])) {
    $archivo = $_GET['view'];
} else {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor PDF</title>
    <link rel="shortcut icon" href="../img/logo.png" type="image/x-icon">
    <script src="pdf-js/pdf.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            background-color: #171725;
        }

        .top-bar {
            background: rgba(23, 23, 37, 0.1);
            /* Fondo inicial transparente */
            color: #fff;
            padding: 3px;
            position: fixed;
            /* Cambiado a block para que se muestre siempre */
            transition: background 0.3s, opacity 0.3s;
            /* Añade una transición suave */
            opacity: 0.7;
            /* Opacidad inicial */
        }

        .top-bar:hover {
            background: rgba(23, 23, 37, 0.9);
            /* Fondo más opaco al hacer hover */
            opacity: 1;
            /* Totalmente visible al hacer hover */
        }

        .btn {
            background: rgba(255, 127, 80, 0.1);
            /* Fondo inicial semi-transparente */
            color: #fff;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 0.5rem 2rem;
            transition: background 0.3s, opacity 0.3s;
            /* Transición suave */
        }

        .btn:hover {
            background: rgba(255, 127, 80, 1);
            /* Fondo opaco al hacer hover */
            opacity: 1;
            /* Totalmente visible al hacer hover */
        }

        .error {
            background: orangered;
            color: #fff;
            padding: 1rem;
            margin-top: 50vh;
        }

        #pdf-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 100%;
        }

        #canvas-container {
            width: 100%;
            overflow: hidden;
            display: flex;
            justify-content: center;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        #pdf_canvas {
            max-width: 100%;
            height: auto;
        }

        .page-info {
            text-align: center;
            margin-top: 5px;
        }

        .loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #429867;
            color: #fff;
            padding: 1rem;
            border-radius: 5px;
            display: none;
        }


        #page_num_input{
            width: 40px; 
            text-align: center;
            outline: none;
            border: none;
            color: rgba(255, 255, 255, 1); /* Negro con 20% de opacidad */
            background: rgba(255, 255, 255, 0.1);
        }

        /* Ocultar las flechas en navegadores WebKit (Chrome, Safari) */
        #page_num_input::-webkit-inner-spin-button,
        #page_num_input::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        /* Ocultar las flechas en Firefox y otros navegadores */
        #page_num_input {
            appearance: none; /* Propiedad estándar */
            -moz-appearance: textfield; /* Propiedad para Firefox */
        }



        @media (max-width: 768px) {
            #pdf-container {
                height: 100vh;
                justify-content: space-evenly;
            }

            .top-bar {
                position: relative;
                /* Mantenerlo fijo */
                bottom: 0;
                /* Posicionar en la parte inferior */
                opacity: 0.7;
                /* Opacidad inicial */
            }

            .btn {
                background: rgba(255, 127, 80, 0.9);
            }

            #canvas-container {
                align-items: center;
                margin-bottom: 0px;
            }

            #zoomIn,
            #zoomOut {
                display: none;
            }

        }
    </style>

    <style>
        /* From Uiverse.io by zanina-yassine */
        .container {
            width: 100px;
            height: 125px;
            margin: auto auto;
            position: absolute;
            top: 50%;
        }

        .loading-title {
            display: block;
            text-align: center;
            font-size: 20;
            font-family: 'Inter', sans-serif;
            font-weight: bold;
            padding-bottom: 15px;
            color: #888;
        }

        .loading-circle {
            display: block;
            border-left: 5px solid;
            border-top-left-radius: 100%;
            border-top: 5px solid;
            margin: 5px;
            animation-name: Loader_611;
            animation-duration: 1500ms;
            animation-timing-function: linear;
            animation-delay: 0s;
            animation-iteration-count: infinite;
            animation-direction: normal;
            animation-fill-mode: forwards;
        }

        .sp1 {
            border-left-color: #F44336;
            border-top-color: #F44336;
            width: 40px;
            height: 40px;
        }

        .sp2 {
            border-left-color: #FFC107;
            border-top-color: #FFC107;
            width: 30px;
            height: 30px;
        }

        .sp3 {
            width: 20px;
            height: 20px;
            border-left-color: #8bc34a;
            border-top-color: #8bc34a;
        }

        @keyframes Loader_611 {
            0% {
                transform: rotate(0deg);
                transform-origin: right bottom;
            }

            25% {
                transform: rotate(90deg);
                transform-origin: right bottom;
            }

            50% {
                transform: rotate(180deg);
                transform-origin: right bottom;
            }

            75% {
                transform: rotate(270deg);
                transform-origin: right bottom;
            }

            100% {
                transform: rotate(360deg);
                transform-origin: right bottom;
            }
        }
    </style>

    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css"
        integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous" />
</head>

<body>
    <div id="pdf-container">
        <div class="container" id="loading-message">
            <label class="loading-title">Cargando ...</label>
            <span class="loading-circle sp1">
                <span class="loading-circle sp2">
                    <span class="loading-circle sp3"></span>
                </span>
            </span>
        </div>


        <div id="canvas-container">
            <canvas id="pdf_canvas"></canvas>
        </div>
        <div class="top-bar" id="top-bar" style="display: none;"> 
            <div class="container-btn">
                <button class="btn" id="prev">
                    <i class="fas fa-arrow-circle-left"></i> Anterior
                </button>
                <button class="btn" id="next">
                    Siguiente <i class="fas fa-arrow-circle-right"></i>
                </button>
                <button id="zoomOut" class="btn"><i class="fas fa-search-minus"></i></button>
                <button id="zoomIn" class="btn"><i class="fas fa-search-plus"></i></button>
            </div>
            <div class="page-info">
                <div class="page-info">
                    Página <input type="number" id="page_num_input" value="1" min="1"/> de <span id="page_count"></span>
                </div>

            </div>
        </div>


    </div>

    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'pdf-js/pdf.worker.min.js';

        var archivoPDF = "<?php echo 'docs/' . $archivo . '.pdf'; ?>";
        var pdfDoc = null,
            pageNum = 1,
            pageRendering = false,
            pageNumPending = null,
            scale = window.innerWidth < 768 ? 1.5 : 0.9;
        canvas = document.getElementById("pdf_canvas"),
            ctx = canvas.getContext('2d');

        // Cambia esta línea en la función renderPage
        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then((page) => {
                var viewport = page.getViewport({
                    scale: scale
                });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                var renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                var renderTask = page.render(renderContext);
                renderTask.promise.then(() => {
                    pageRendering = false;
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });
            document.getElementById('page_num_input').value = num; 
        }

        // Agrega el evento para el input
        document.getElementById('page_num_input').addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                const inputPageNum = parseInt(this.value, 10);
                if (inputPageNum >= 1 && inputPageNum <= pdfDoc.numPages) {
                    pageNum = inputPageNum; // Actualiza el número de página
                    queueRenderPage(pageNum);
                } else {
                    // Muestra una alerta si el número de página es inválido
                    alert(`Por favor, ingrese un número de página válido (1 - ${pdfDoc.numPages}).`);
                }
            }
        });


        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }

        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }
        document.getElementById('prev').addEventListener('click', onPrevPage);

        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }
        document.getElementById('next').addEventListener('click', onNextPage);

        function zoomOut() {
            scale -= 0.1;
            renderPage(pageNum);
        }
        document.getElementById("zoomOut").addEventListener('click', zoomOut);

        function zoomIn() {
            scale += 0.1;
            renderPage(pageNum);
        }
        document.getElementById('zoomIn').addEventListener('click', zoomIn);

        function loadPDF() {
            document.getElementById('loading-message').style.display = 'block';
            pdfjsLib.getDocument(archivoPDF).promise.then((pdfDoc_) => {
                pdfDoc = pdfDoc_;
                document.getElementById('loading-message').style.display = 'none';
                document.getElementById('top-bar').style.display = 'block';
                document.getElementById('page_count').textContent = pdfDoc.numPages;
                renderPage(pageNum);
            }).catch((error) => {
                document.getElementById('loading-message').style.display = 'none';
                document.getElementById('pdf-container').innerHTML = '<div class="error">No se pudo cargar el PDF.</div>';
            });
        }


        loadPDF();
    </script>
    <script>
        document.addEventListener('contextmenu', function(event) {
            event.preventDefault();
        });
    </script>
</body>

</html>