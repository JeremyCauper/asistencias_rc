<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="shortcut icon" href="./front/images/app/icons/icon-192.webp?v=5.6.5">

    <!-- PWA Meta Tags -->
    <link rel="manifest" href="./manifest.json?v=5.6.5">
    <meta name="theme-color" content="#000000">

    <title>Cargando...</title>

    <!-- Para iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Asistencias">
    <link rel="apple-touch-icon" href="./front/images/app/icons/icon-192.webp?v=5.6.5">

    <!-- Para Windows -->
    <meta name="msapplication-TileImage" content="./front/images/app/icons/icon-192.webp?v=5.6.5">
    <meta name="msapplication-TileColor" content="#000000">

    <style>
        @keyframes l13 {
            100% {
                transform: rotate(1turn)
            }
        }

        html {
            height: 100dvh;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100dvh;
            margin: 0;
            background-color: #3b71ca;
            font-family: Arial, sans-serif;
        }

        .loader {
            position: relative;
        }

        /* HTML: <div class="loader"></div> */
        .loader::after {
            --bg: #f4f4f4;
            --ancho: 10px;
            content: '';
            position: absolute;
            width: 171px;
            top: -18px;
            left: -18px;
            aspect-ratio: 1;
            border-radius: 50%;
            background: radial-gradient(farthest-side, var(--bg) 94%, #0000) top / var(--ancho) var(--ancho) no-repeat, conic-gradient(#0000 30%, var(--bg));
            -webkit-mask: radial-gradient(farthest-side, #0000 calc(100% - var(--ancho)), #000 0);
            animation: l13 .6s infinite linear;
        }

        .logo-container {
            width: 96px;
            height: 96px;
            object-fit: cover;
            border-radius: 50%;
            /* box-shadow: 0 0 5px rgba(0, 0, 0, 0.2); */
            padding: 20px;
            z-index: 2;
            position: relative;
            display: block;
        }

        .logo-img {
            width: 100%;
            height: 100%;
        }

        .info-container {
            position: absolute;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            color: white;
            font-size: 0.9rem;
            font-family: Arial, sans-serif;
            z-index: 3;
            text-align: center;
        }

        .info-container .version {
            margin-top: 0.2rem;
            font-size: 0.8rem;
            opacity: 0.8;
        }
    </style>
</head>

<body>
    <div class="loader">
        <div class="logo-container">
            <img src="./front/images/app/icons/icon-192.webp?v=5.6.5" class="logo-img" alt="Logo">
        </div>
    </div>
    <div class="info-container">
        <p class="version">v5.6.5</p>
    </div>
    <script>
        window.addEventListener('load', () => {
            const redirectToHome = () => {
                window.location.href = location.href + 'inicio';
            };

            if ("serviceWorker" in navigator) {
                navigator.serviceWorker.register("./sw.js?v=5.6.5")
                    .then(async reg => {
                        console.log("[SW] Registrado correctamente");
                        redirectToHome();
                    });
            }
        });
    </script>
</body>

</html>