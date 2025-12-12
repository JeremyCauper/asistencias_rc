<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="shortcut icon" href="{{ secure_asset($ft_img->icon) }}" />
    <title>Bienvenido...</title>

    <head>
        <meta charset="UTF-8">
        <title>Logo con Loading (Imagen)</title>
        <style>
            @keyframes l13 {
                100% {
                    transform: rotate(1turn)
                }
            }

            body {
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
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
                content: '';
                position: absolute;
                width: 205px;
                top: -23px;
                left: -23px;
                aspect-ratio: 1;
                border-radius: 50%;
                background: radial-gradient(farthest-side, var(--bg) 94%, #0000) top / 16px 16px no-repeat, conic-gradient(#0000 30%, var(--bg));
                -webkit-mask: radial-gradient(farthest-side, #0000 calc(100% - 16px), #000 0);
                animation: l13 1s infinite linear;
            }

            .logo-container {
                width: 120px;
                height: 120px;
                object-fit: cover;
                border-radius: 50%;
                box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
                padding: 20px;
                z-index: 2;
                position: relative;
                display: block;
            }

            .logo-img {
                width: 100%;
                height: 100%;
            }
        </style>
    </head>

<body>
    <div class="loader">
        <div class="logo-container">
            <img src="{{ secure_asset($ft_img->icon_512) }}" alt="Logo de la empresa" class="logo-img">
        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            // Una vez que toda la página (incluyendo imágenes) ha cargado,
            // redirigimos a la aplicación principal.
            // Podrías añadir un pequeño retraso aquí si quieres que el splash se vea un poco más.
            setTimeout(() => {
                window.location.href = "{{ Auth::check() ? secure_url($rutaRedirect) : secure_url('/inicio') }}"; // O la ruta que sea tu inicio real
            }, 500); // Pequeño retraso para que el usuario vea el splash
        });
    </script>
</body>

</html>
