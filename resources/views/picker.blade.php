<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Date Time Picker - Input Integration</title>
    <link rel="stylesheet" href="{{ asset('front/vendor/mdtp/mdtp.min.css') }}">
    <script src="{{ asset('front/vendor/mdtp/mdtp.min.js') }}"></script>
    <style>
        /* ==================== DEMO STYLES ==================== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            padding: 40px 20px;
            max-width: 800px;
            margin: 0 auto;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .demo-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .demo-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .demo-header h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }

        .demo-header p {
            color: #666;
            font-size: 16px;
        }

        .demo-form {
            display: grid;
            gap: 24px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-label {
            font-size: 14px;
            font-weight: 500;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label-icon {
            font-size: 20px;
        }

        .theme-toggle {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px 0;
            border-top: 1px solid #e0e0e0;
            margin-top: 20px;
        }

        .theme-btn {
            padding: 8px 20px;
            border-radius: 20px;
            border: 2px solid #6750A4;
            background: white;
            color: #6750A4;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .theme-btn.active {
            background: #6750A4;
            color: white;
        }

        .theme-btn:hover:not(.active) {
            background: #f5f5f5;
        }

        .demo-output {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 12px;
            border-left: 4px solid #6750A4;
        }

        .demo-output h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .demo-output pre {
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>

<body>
    <div class="demo-container">
        <div class="demo-header">
            <h1>📅 Material Date Time Picker</h1>
            <p>Navegación mejorada: Año → Mes → Fecha</p>
        </div>

        <form class="demo-form">
            <div class="form-group">
                <label class="form-label" for="fecha_cita">
                    <span class="form-label-icon">📅</span>
                    Fecha de la cita
                </label>
                <input type="text" id="fecha_cita" class="mdtp-input" placeholder="Selecciona una fecha" readonly />
            </div>

            <div class="form-group">
                <label class="form-label" for="hora_cita">
                    <span class="form-label-icon">🕐</span>
                    Hora de la cita (12h)
                </label>
                <input type="text" id="hora_cita" class="mdtp-input" placeholder="Selecciona una hora" readonly />
            </div>

            <div class="form-group">
                <label class="form-label" for="hora_reunion">
                    <span class="form-label-icon">⏰</span>
                    Hora de reunión (24h)
                </label>
                <input type="text" id="hora_reunion" class="mdtp-input" placeholder="Formato 24 horas" readonly />
            </div>

            <div class="form-group">
                <label class="form-label" for="fecha_hora_evento">
                    <span class="form-label-icon">📆</span>
                    Fecha y hora del evento
                </label>
                <input type="text" id="fecha_hora_evento" class="mdtp-input" placeholder="Fecha y hora completa"
                    readonly />
            </div>
        </form>

        <div class="theme-toggle">
            <button class="theme-btn" onclick="changeTheme('light')">☀️ Light</button>
            <button class="theme-btn active" onclick="changeTheme('dark')">🌙 Dark</button>
        </div>

        <div class="demo-output">
            <h3>📊 Valores seleccionados</h3>
            <pre id="output">Los valores aparecerán aquí cuando realices una selección...</pre>
        </div>
    </div>

    <script>
        /* ==================== DEMO INITIALIZATION ==================== */
        let currentTheme = 'dark';

        const fecha_cita = new MaterialDateTimePicker({
            inputId: 'fecha_cita',
            mode: 'month',
            locale: 'es',
            format: 'MMMM de YYYY',
            max: '2026-02-15',
            theme: currentTheme,
            onConfirm: (date, formatted) => {
                updateOutput('Fecha de cita', formatted, date);
            }
        });

        const hora_cita = new MaterialDateTimePicker({
            inputId: 'hora_cita',
            mode: 'time',
            locale: 'es',
            format: 'HH:mm A',
            format24h: false,
            theme: currentTheme,
            onConfirm: (time, formatted) => {
                updateOutput('Hora de cita', formatted, time);
            }
        });

        new MaterialDateTimePicker({
            inputId: 'hora_reunion',
            mode: 'time',
            locale: 'es',
            format: 'HH:mm',
            format24h: true,
            theme: currentTheme,
            onConfirm: (time, formatted) => {
                updateOutput('Hora de reunión', formatted, time);
            }
        });

        new MaterialDateTimePicker({
            inputId: 'fecha_hora_evento',
            mode: 'datetime',
            locale: 'es',
            format: 'DD/MM/YYYY HH:mm A',
            format24h: false,
            theme: currentTheme,
            onConfirm: (datetime, formatted) => {
                updateOutput('Fecha y hora del evento', formatted, datetime);
            }
        });

        function updateOutput(label, formatted, raw) {
            const output = document.getElementById('output');
            const timestamp = raw instanceof Date ? raw.toISOString() : JSON.stringify(raw);
            output.textContent = `${label}:\n  Formateado: ${formatted}\n  Raw: ${timestamp}`;
        }

        function changeTheme(theme) {
            currentTheme = theme;
            document.querySelectorAll('.theme-btn').forEach(btn => {
                btn.classList.toggle('active', btn.textContent.includes(theme === 'light' ? '☀️' : '🌙'));
            });

            MaterialDateTimePicker.instances.forEach(instance => {
                instance.options.theme = theme;
            });
        }
    </script>
</body>

</html>