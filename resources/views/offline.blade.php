<!-- resources/views/offline.blade.php -->
<!DOCTYPE html>
<html lang="es">

<head style="height: 100vh; overflow: hidden;">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Sin Conexión</title>
    <link rel="shortcut icon" href="{{ secure_asset('front/images/app/icons/icon.png') }}?v=1.0.0" />
    <!-- Font Awesome -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/all.min6.0.0.css') }}?v=1.0.0" rel="stylesheet">
    <!-- MDB -->
    <link href="{{ secure_asset('front/vendor/mdboostrap/css/mdb.min7.2.0.css') }}?v=1.0.0" rel="stylesheet">
</head>

<body class="d-flex align-items-center justify-content-center"
    style="height: 100vh; background-color: #171717;overflow: hidden;">
    <div class="text-center">
        <img src="{{ secure_asset('front/images/app/icons/icon.png') }}?v=1.0.0" alt="" style="width: 9rem">
        <p class="mb-2 mt-4 text-white">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 512 512" class="me-2">
                <path d="M93.72,183.25C49.49,198.05,16,233.1,16,288c0,66,54,112,120,112H320.37"
                    style="fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" />
                <path
                    d="M467.82,377.74C485.24,363.3,496,341.61,496,312c0-59.82-53-85.76-96-88-8.89-89.54-71-144-144-144-26.16,0-48.79,6.93-67.6,18.14"
                    style="fill:none;stroke:#fff;stroke-linecap:round;stroke-linejoin:round;stroke-width:32px" />
                <line x1="448" y1="448" x2="64" y2="64"
                    style="fill:none;stroke:#fff;stroke-linecap:round;stroke-miterlimit:10;stroke-width:32px" />
            </svg>
            Conexion no disponible
        </p>
        <button class="btn btn-secondary" onclick="window.location.reload()">Reintentar</button>
    </div>
</body>

</html>
