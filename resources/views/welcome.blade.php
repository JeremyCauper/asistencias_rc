<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foto</title>
</head>
<body>
    <input type="file" id="fileInput" accept="image/*" capture="camera">
</body>
</html>

{{-- <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <title>Convertir Imagen a WebP</title>

    <!-- Librería Compressor.js -->
    <script src="https://cdn.jsdelivr.net/npm/compressorjs@1.2.1/dist/compressor.min.js"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }
        img {
            max-width: 300px;
            margin-top: 20px;
            border-radius: 10px;
        }
        .msg {
            margin-top: 15px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h2>Convertir Imagen a WebP</h2>

    <input type="file" id="fileInput" accept="image/*">

    <div class="msg" id="msg"></div>
    <img id="preview">

    <script>
        const input = document.getElementById('fileInput');
        const msg = document.getElementById('msg');
        const preview = document.getElementById('preview');

        input.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            msg.innerText = "Convirtiendo imagen, espera...";
            preview.src = URL.createObjectURL(file);

            new Compressor(file, {
                quality: 0.35,               // Calidad WebP
                convertTypes: ['image/webp'], // Convertir SIEMPRE a WebP
                success(result) {
                    msg.innerText = "Conversión completada ✔";

                    // Descargar el WebP
                    const a = document.createElement('a');
                    a.href = URL.createObjectURL(result);
                    a.download = file.name.replace(/\.[^.]+$/, '') + '.webp';
                    a.click();

                    // Vista previa del WebP
                    preview.src = a.href;
                },
                error(err) {
                    msg.innerText = "Ocurrió un error ❌";
                    console.error(err);
                },
            });
        });
    </script>

</body>
</html> --}}
