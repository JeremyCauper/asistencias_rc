<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Convertir Imagen a WebP</title>

    <!-- Librería Browser Image Compression -->
    <script
        src="{{secure_asset('front/vendor/browser-image-compression/browser-image-compression.js')}}"></script>

    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        img {
            max-width: 320px;
            margin-top: 20px;
            border-radius: 10px;
        }

        .info {
            margin-top: 15px;
            font-size: 16px;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>

<body>

    <h2>Convertir Imagen a WebP</h2>

    <input type="file" id="fileInput" accept="image/*">

    <div class="info" id="info"></div>
    <img id="preview">

    <script>
        const input = document.getElementById("fileInput");
        const info = document.getElementById("info");
        const preview = document.getElementById("preview");

        input.addEventListener("change", async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            info.innerHTML = "Convirtiendo imagen, espera...";
            preview.src = URL.createObjectURL(file);

            try {

                const options = {
                    maxSizeMB: 10,
                    initialQuality: 0.25,  // calidad (0.3–0.9 ideal)
                    fileType: "image/webp" // convertir a WebP
                };
                const output = await imageCompression(file, options);

                // Mostrar información de tamaños
                const originalKB = (file.size / 1024).toFixed(2);
                const newKB = (output.size / 1024).toFixed(2);
                const reduction = (100 - (output.size / file.size * 100)).toFixed(1);

                info.innerHTML = `
                    <div class="bold">Conversión completada ✔</div>
                    Tamaño original: <b>${originalKB} KB</b><br>
                    Tamaño WebP: <b>${newKB} KB</b><br>
                    Reducción: <b>${reduction}%</b>
                `;

                // Descargar WebP automáticamente
                const a = document.createElement("a");
                a.href = URL.createObjectURL(output);
                a.download = file.name.replace(/\.[^.]+$/, "") + ".webp";
                a.click();

                // Previsualizar WebP
                preview.src = a.href;

            } catch (err) {
                console.error(err);
                info.innerHTML = "Ocurrió un error ❌";
            }
        });
    </script>

</body>

</html>