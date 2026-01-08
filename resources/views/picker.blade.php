<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Material Date Time Picker - Input Integration</title>
    <style>
        /* ==================== CSS VARIABLES ==================== */
        :root {
            --mdtp-primary: #6750A4;
            --mdtp-on-primary: #FFFFFF;
            --mdtp-surface: #FFFFFF;
            --mdtp-on-surface: #1C1B1F;
            --mdtp-surface-variant: #E7E0EC;
            --mdtp-on-surface-variant: #49454F;
            --mdtp-outline: #79747E;
            --mdtp-overlay: rgba(0, 0, 0, 0.5);
            --mdtp-elevation: 0 8px 32px rgba(0, 0, 0, 0.12);
            --mdtp-radius: 28px;
            --mdtp-transition: cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] {
            --mdtp-primary: #D0BCFF;
            --mdtp-on-primary: #381E72;
            --mdtp-surface: #1C1B1F;
            --mdtp-on-surface: #E6E1E5;
            --mdtp-surface-variant: #49454F;
            --mdtp-on-surface-variant: #CAC4D0;
            --mdtp-outline: #938F99;
            --mdtp-overlay: rgba(0, 0, 0, 0.7);
        }

        /* ==================== RESET & BASE ==================== */
        .mdtp-picker * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* ==================== INPUT STYLES ==================== */
        .mdtp-input {
            width: 100%;
            padding: 12px 16px;
            font-size: 16px;
            font-family: 'Roboto', sans-serif;
            border: 2px solid var(--mdtp-outline);
            border-radius: 8px;
            background: var(--mdtp-surface);
            color: var(--mdtp-on-surface);
            cursor: pointer;
            transition: all 0.2s;
            outline: none;
        }

        .mdtp-input:focus {
            border-color: var(--mdtp-primary);
            box-shadow: 0 0 0 3px rgba(103, 80, 164, 0.1);
        }

        .mdtp-input:hover {
            border-color: var(--mdtp-primary);
        }

        .mdtp-input::placeholder {
            color: var(--mdtp-on-surface-variant);
            opacity: 0.6;
        }

        /* ==================== OVERLAY ==================== */
        .mdtp-overlay {
            position: fixed;
            inset: 0;
            background: var(--mdtp-overlay);
            z-index: 9998;
            opacity: 0;
            transition: opacity 0.3s var(--mdtp-transition);
        }

        .mdtp-overlay.active {
            opacity: 1;
        }

        /* ==================== MODAL CONTAINER ==================== */
        .mdtp-picker {
            position: fixed;
            z-index: 9999;
            font-family: 'Roboto', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 16px;
            color: var(--mdtp-on-surface);
        }

        /* Desktop: centered modal */
        @media (min-width: 600px) {
            .mdtp-picker {
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) scale(0.9);
                opacity: 0;
                transition: all 0.3s var(--mdtp-transition);
            }

            .mdtp-picker.active {
                transform: translate(-50%, -50%) scale(1);
                opacity: 1;
            }
        }

        /* Mobile: bottom sheet */
        @media (max-width: 599px) {
            .mdtp-picker {
                bottom: 0;
                left: 0;
                right: 0;
                transform: translateY(100%);
                transition: transform 0.3s var(--mdtp-transition);
                border-radius: var(--mdtp-radius) var(--mdtp-radius) 0 0;
            }

            .mdtp-picker.active {
                transform: translateY(0);
            }
        }

        /* ==================== MODAL CONTENT ==================== */
        .mdtp-container {
            background: var(--mdtp-surface);
            border-radius: var(--mdtp-radius);
            box-shadow: var(--mdtp-elevation);
            overflow: hidden;
            width: 360px;
            max-width: 90vw;
        }

        @media (max-width: 599px) {
            .mdtp-container {
                width: 100%;
                max-width: 100%;
                border-radius: var(--mdtp-radius) var(--mdtp-radius) 0 0;
            }
        }

        /* ==================== HEADER ==================== */
        .mdtp-header {
            background: var(--mdtp-surface-variant);
            padding: 24px;
            text-align: left;
        }

        .mdtp-header-label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--mdtp-on-surface-variant);
            margin-bottom: 8px;
        }

        .mdtp-header-value {
            font-size: 32px;
            font-weight: 400;
            color: var(--mdtp-on-surface);
            user-select: none;
            line-height: 1.2;
        }

        /* ==================== BODY ==================== */
        .mdtp-body {
            padding: 20px;
            min-height: 280px;
            max-height: 400px;
            overflow-y: auto;
            position: relative;
        }

        /* ==================== MONTH/YEAR PICKER ==================== */
        .mdtp-month-year-container {
            display: flex;
            gap: 20px;
            justify-content: center;
            align-items: center;
            height: 280px;
            position: relative;
        }

        .mdtp-close-month-year {
            position: absolute;
            top: -10px;
            right: -10px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: none;
            background: var(--mdtp-surface-variant);
            color: var(--mdtp-on-surface);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
            z-index: 10;
        }

        .mdtp-close-month-year:hover {
            background: var(--mdtp-primary);
            color: var(--mdtp-on-primary);
        }

        .mdtp-picker-column {
            position: relative;
            height: 280px;
            width: 120px;
            overflow: hidden;
        }

        .mdtp-picker-list {
            position: absolute;
            width: 100%;
            transition: transform 0.3s var(--mdtp-transition);
        }

        .mdtp-picker-item {
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--mdtp-on-surface);
            opacity: 0.4;
            transition: all 0.2s;
            user-select: none;
            cursor: pointer;
        }

        .mdtp-picker-item.active {
            opacity: 1;
            font-weight: 500;
            font-size: 20px;
        }

        .mdtp-picker-column::before,
        .mdtp-picker-column::after {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            height: 112px;
            pointer-events: none;
            z-index: 1;
        }

        .mdtp-picker-column::before {
            top: 0;
            background: linear-gradient(to bottom, var(--mdtp-surface), transparent);
        }

        .mdtp-picker-column::after {
            bottom: 0;
            background: linear-gradient(to top, var(--mdtp-surface), transparent);
        }

        .mdtp-picker-selector {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 56px;
            transform: translateY(-50%);
            border-top: 2px solid var(--mdtp-primary);
            border-bottom: 2px solid var(--mdtp-primary);
            pointer-events: none;
            z-index: 2;
        }

        /* ==================== DATE PICKER NAV ==================== */
        .mdtp-date-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .mdtp-date-nav-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--mdtp-on-surface);
            padding: 8px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
            user-select: none;
        }

        .mdtp-date-nav-title:hover {
            background: var(--mdtp-surface-variant);
        }

        .mdtp-date-nav-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: transparent;
            color: var(--mdtp-on-surface);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
            font-size: 18px;
        }

        .mdtp-date-nav-btn:hover {
            background: var(--mdtp-surface-variant);
        }

        .mdtp-date-nav-btn:focus {
            outline: 2px solid var(--mdtp-primary);
            outline-offset: 2px;
        }

        /* ==================== YEAR SELECTOR ==================== */
        .mdtp-year-grid {
            display: none;
        }

        /* ==================== MONTH SELECTOR ==================== */
        .mdtp-month-grid {
            display: none;
        }

        /* ==================== CALENDAR ==================== */
        .mdtp-calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }

        .mdtp-calendar-day {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 500;
            color: var(--mdtp-on-surface-variant);
            text-transform: uppercase;
        }

        .mdtp-calendar-date {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            border: none;
            background: transparent;
            color: var(--mdtp-on-surface);
            transition: all 0.2s;
            position: relative;
        }

        .mdtp-calendar-date:hover {
            background: var(--mdtp-surface-variant);
        }

        .mdtp-calendar-date.other-month {
            color: var(--mdtp-on-surface-variant);
            opacity: 0.4;
        }

        .mdtp-calendar-date.selected {
            background: var(--mdtp-primary);
            color: var(--mdtp-on-primary);
        }

        .mdtp-calendar-date.today::after {
            content: '';
            position: absolute;
            bottom: 2px;
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: var(--mdtp-primary);
        }

        .mdtp-calendar-date.selected.today::after {
            background: var(--mdtp-on-primary);
        }

        .mdtp-calendar-date:focus {
            outline: 2px solid var(--mdtp-primary);
            outline-offset: 2px;
        }

        /* ==================== TIME PICKER - ANALOG CLOCK STYLE ==================== */
        .mdtp-time-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            padding: 10px 0;
        }

        .mdtp-time-header {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 56px;
            font-weight: 300;
            user-select: none;
        }

        .mdtp-time-segment {
            min-width: 90px;
            padding: 8px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .mdtp-time-segment:hover {
            background: rgba(103, 80, 164, 0.08);
        }

        .mdtp-time-segment.active {
            background: rgba(103, 80, 164, 0.12);
        }

        .mdtp-time-colon {
            opacity: 0.6;
        }

        .mdtp-time-period-inline {
            display: flex;
            flex-direction: column;
            gap: 4px;
            margin-left: 8px;
        }

        .mdtp-time-period-inline-btn {
            padding: 6px 14px;
            border-radius: 16px;
            border: none;
            background: transparent;
            color: var(--mdtp-on-surface);
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.2s;
            opacity: 0.5;
        }

        .mdtp-time-period-inline-btn.active {
            background: rgba(103, 80, 164, 0.12);
            opacity: 1;
        }

        .mdtp-time-period-inline-btn:hover:not(.active) {
            background: rgba(103, 80, 164, 0.05);
        }

        .mdtp-analog-clock-container {
            position: relative;
            display: flex;
            gap: 24px;
            align-items: center;
            justify-content: center;
        }

        .mdtp-analog-clock {
            position: relative;
            width: 260px;
            height: 260px;
            background: var(--mdtp-surface-variant);
            border-radius: 50%;
            user-select: none;
            touch-action: none;
        }

        .mdtp-clock-center {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 8px;
            height: 8px;
            background: var(--mdtp-primary);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
        }

        .mdtp-clock-hand {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 2px;
            background: var(--mdtp-primary);
            transform-origin: 50% 100%;
            transition: none;
            z-index: 5;
            pointer-events: none;
        }

        .mdtp-clock-hand::before {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 50%;
            width: 8px;
            height: 8px;
            background: var(--mdtp-primary);
            border-radius: 50%;
            transform: translate(-50%, 0);
        }

        .mdtp-clock-hand::after {
            content: '';
            position: absolute;
            top: -20px;
            left: 50%;
            width: 40px;
            height: 40px;
            background: var(--mdtp-primary);
            border-radius: 50%;
            transform: translate(-50%, 0);
        }

        .mdtp-clock-number {
            position: absolute;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 400;
            color: var(--mdtp-on-surface);
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.2s;
            user-select: none;
        }

        .mdtp-clock-number:hover {
            background: rgba(103, 80, 164, 0.1);
        }

        .mdtp-clock-number.selected {
            background: transparent;
            color: var(--mdtp-on-primary);
            font-weight: 500;
        }

        .mdtp-clock-number.inner {
            font-size: 14px;
        }

        .mdtp-time-period {
            display: none;
        }

        /* ==================== ACTIONS ==================== */
        .mdtp-actions {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            padding: 16px 24px;
            border-top: 1px solid var(--mdtp-outline);
        }

        .mdtp-btn {
            padding: 10px 24px;
            border-radius: 20px;
            border: none;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .mdtp-btn-text {
            background: transparent;
            color: var(--mdtp-primary);
        }

        .mdtp-btn-text:hover {
            background: rgba(103, 80, 164, 0.08);
        }

        .mdtp-btn-filled {
            background: var(--mdtp-primary);
            color: var(--mdtp-on-primary);
        }

        .mdtp-btn-filled:hover {
            box-shadow: 0 2px 8px rgba(103, 80, 164, 0.3);
        }

        .mdtp-btn:focus {
            outline: 2px solid var(--mdtp-primary);
            outline-offset: 2px;
        }

        /* ==================== UTILITY ==================== */
        .mdtp-hidden {
            display: none !important;
        }

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
            <button class="theme-btn active" onclick="changeTheme('light')">☀️ Light</button>
            <button class="theme-btn" onclick="changeTheme('dark')">🌙 Dark</button>
        </div>

        <div class="demo-output">
            <h3>📊 Valores seleccionados</h3>
            <pre id="output">Los valores aparecerán aquí cuando realices una selección...</pre>
        </div>
    </div>

    <script>
        /* ==================== LOCALES ==================== */
        const LOCALES = {
            es: {
                months: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre',
                    'Octubre', 'Noviembre', 'Diciembre'
                ],
                monthsShort: ['ene.', 'feb.', 'mar.', 'abr.', 'may.', 'jun.', 'jul.', 'ago.', 'sep.', 'oct.', 'nov.',
                    'dic.'
                ],
                days: ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'],
                daysShort: ['Do', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sa'],
                daysAbbr: ['Dom.', 'Lun.', 'Mar.', 'Mié.', 'Jue.', 'Vie.', 'Sáb.'],
                cancel: 'Cancelar',
                confirm: 'Aceptar',
                selectDate: 'Seleccionar fecha',
                selectTime: 'Seleccionar hora',
                hour: 'Hora',
                minute: 'Minuto'
            },
            en: {
                months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
                    'October', 'November', 'December'
                ],
                monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
                daysShort: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                daysAbbr: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                cancel: 'Cancel',
                confirm: 'OK',
                selectDate: 'Select date',
                selectTime: 'Select time',
                hour: 'Hour',
                minute: 'Minute'
            }
        };

        /* ==================== DATE TIME FORMATTER ==================== */
        class DateTimeFormatter {
            static format(date, pattern, format24h = false, period = 'AM') {
                const d = date.getDate();
                const m = date.getMonth() + 1;
                const y = date.getFullYear();
                let h = date.getHours();
                const min = date.getMinutes();

                const displayHour = format24h ? h : (h % 12 || 12);

                const tokens = {
                    'DD': String(d).padStart(2, '0'),
                    'D': String(d),
                    'MM': String(m).padStart(2, '0'),
                    'M': String(m),
                    'YYYY': String(y),
                    'YY': String(y).slice(-2),
                    'HH': String(displayHour).padStart(2, '0'),
                    'H': String(displayHour),
                    'mm': String(min).padStart(2, '0'),
                    'm': String(min),
                    'A': format24h ? '' : period
                };

                return pattern.replace(/DD|D|MM|M|YYYY|YY|HH|H|mm|m|A/g, match => tokens[match] || match);
            }
        }

        /* ==================== MAIN CLASS ==================== */
        class MaterialDateTimePicker {
            static instances = new Map();

            constructor(options = {}) {
                this.options = {
                    inputId: options.inputId,
                    mode: options.mode || 'date',
                    locale: options.locale || 'es',
                    format: options.format || 'DD/MM/YYYY',
                    theme: options.theme || 'light',
                    closeOnConfirm: options.closeOnConfirm !== false,
                    format24h: options.format24h !== undefined ? options.format24h : false,
                    onConfirm: options.onConfirm || (() => {}),
                    onCancel: options.onCancel || (() => {})
                };

                if (!this.options.inputId) {
                    throw new Error('inputId is required');
                }

                this.input = document.getElementById(this.options.inputId);
                if (!this.input) {
                    throw new Error(`Input with id "${this.options.inputId}" not found`);
                }

                this.input.setAttribute('readonly', 'readonly');
                this.input.style.cursor = 'pointer';

                this.locale = LOCALES[this.options.locale] || LOCALES.es;

                const now = new Date();
                this.state = {
                    currentDate: new Date(),
                    selectedDate: new Date(),
                    viewYear: now.getFullYear(),
                    viewMonth: now.getMonth(),
                    hour: now.getHours(),
                    minute: now.getMinutes(),
                    period: now.getHours() >= 12 ? 'PM' : 'AM',
                    datePickerView: 'date', // 'year' | 'month' | 'date'
                    timeView: 'hour', // 'hour' | 'minute'
                    clockHandAngle: 0, // Track cumulative rotation
                    lastClockValue: 0 // Track last value for continuity
                };

                this.elements = {};
                this.isOpen = false;

                this.init();
                MaterialDateTimePicker.instances.set(this.options.inputId, this);
            }

            init() {
                this.input.addEventListener('click', () => this.show());
                this.input.addEventListener('focus', () => this.show());
                this.input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.show();
                    }
                });
            }

            show() {
                if (this.isOpen) return;

                // Reset to date view always when opening
                this.state.datePickerView = 'date';
                // Reset to hour view for time picker
                this.state.timeView = 'hour';

                if (this.input.value) {
                    // Parse existing value from input
                    this.parseInputValue();
                } else {
                    // If no value, initialize with today but don't set as selected yet
                    const today = new Date();
                    this.state.selectedDate = new Date(today);
                    this.state.viewYear = today.getFullYear();
                    this.state.viewMonth = today.getMonth();
                }

                this.render();
                this.isOpen = true;

                requestAnimationFrame(() => {
                    this.elements.overlay.classList.add('active');
                    this.elements.picker.classList.add('active');
                    this.trapFocus();
                });
            }

            parseInputValue() {
                const value = this.input.value;
                if (value) {
                    const dateMatch = value.match(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
                    if (dateMatch) {
                        const [, day, month, year] = dateMatch;
                        const parsedDate = new Date(year, month - 1, day);

                        // Validate the parsed date
                        if (!isNaN(parsedDate.getTime())) {
                            this.state.selectedDate = parsedDate;
                            this.state.viewYear = parsedDate.getFullYear();
                            this.state.viewMonth = parsedDate.getMonth();
                        }
                    }
                }
            }

            hide() {
                if (!this.isOpen) return;

                this.elements.overlay.classList.remove('active');
                this.elements.picker.classList.remove('active');

                setTimeout(() => {
                    if (this.elements.overlay) this.elements.overlay.remove();
                    if (this.elements.picker) this.elements.picker.remove();
                    this.isOpen = false;
                }, 300);
            }

            render() {
                this.elements.overlay = this.createOverlay();
                this.elements.picker = this.createPicker();

                document.body.appendChild(this.elements.overlay);
                document.body.appendChild(this.elements.picker);
            }

            createOverlay() {
                const overlay = document.createElement('div');
                overlay.className = 'mdtp-overlay';
                overlay.addEventListener('click', () => this.cancel());
                return overlay;
            }

            createPicker() {
                const picker = document.createElement('div');
                picker.className = 'mdtp-picker';
                picker.setAttribute('role', 'dialog');
                picker.setAttribute('aria-modal', 'true');
                picker.setAttribute('data-theme', this.options.theme);

                const container = document.createElement('div');
                container.className = 'mdtp-container';

                container.appendChild(this.createHeader());
                container.appendChild(this.createBody());
                container.appendChild(this.createActions());

                picker.appendChild(container);
                return picker;
            }

            createHeader() {
                const header = document.createElement('div');
                header.className = 'mdtp-header';

                // For time picker, replace header content entirely with time display
                if (this.options.mode === 'time') {
                    header.innerHTML = '';
                    const timeHeader = this.createTimeHeader();
                    header.appendChild(timeHeader);
                    return header;
                }

                const label = document.createElement('div');
                label.className = 'mdtp-header-label';

                if (this.options.mode === 'date' || this.options.mode === 'datetime') {
                    label.textContent = this.state.viewYear;
                    label.style.cursor = 'pointer';
                    label.addEventListener('click', () => {
                        this.state.datePickerView = 'month-year';
                        this.updateBody();
                    });
                }

                const value = document.createElement('div');
                value.className = 'mdtp-header-value';
                this.elements.headerValue = value;
                this.elements.headerLabel = label;
                this.updateHeaderValue();

                header.appendChild(label);
                header.appendChild(value);
                return header;
            }

            createTimeHeader() {
                const header = document.createElement('div');
                header.className = 'mdtp-time-header';

                const hourSegment = document.createElement('div');
                hourSegment.className = 'mdtp-time-segment' + (this.state.timeView === 'hour' ? ' active' : '');
                const displayHour = this.options.format24h ? this.state.hour : (this.state.hour % 12 || 12);
                hourSegment.textContent = String(displayHour).padStart(2, '0');
                hourSegment.addEventListener('click', () => this.switchTimeView('hour'));
                this.elements.hourSegment = hourSegment;

                const colon = document.createElement('div');
                colon.className = 'mdtp-time-colon';
                colon.textContent = ':';

                const minuteSegment = document.createElement('div');
                minuteSegment.className = 'mdtp-time-segment' + (this.state.timeView === 'minute' ? ' active' : '');
                minuteSegment.textContent = String(this.state.minute).padStart(2, '0');
                minuteSegment.addEventListener('click', () => this.switchTimeView('minute'));
                this.elements.minuteSegment = minuteSegment;

                header.appendChild(hourSegment);
                header.appendChild(colon);
                header.appendChild(minuteSegment);

                if (!this.options.format24h) {
                    const periodContainer = document.createElement('div');
                    periodContainer.className = 'mdtp-time-period-inline';

                    const amBtn = document.createElement('button');
                    amBtn.className = 'mdtp-time-period-inline-btn' + (this.state.period === 'AM' ? ' active' : '');
                    amBtn.textContent = 'AM';
                    amBtn.addEventListener('click', () => this.setPeriod('AM'));
                    this.elements.amBtn = amBtn;

                    const pmBtn = document.createElement('button');
                    pmBtn.className = 'mdtp-time-period-inline-btn' + (this.state.period === 'PM' ? ' active' : '');
                    pmBtn.textContent = 'PM';
                    pmBtn.addEventListener('click', () => this.setPeriod('PM'));
                    this.elements.pmBtn = pmBtn;

                    periodContainer.appendChild(amBtn);
                    periodContainer.appendChild(pmBtn);
                    header.appendChild(periodContainer);
                }

                return header;
            }

            createBody() {
                const body = document.createElement('div');
                body.className = 'mdtp-body';
                this.elements.body = body;

                if (this.options.mode === 'date' || this.options.mode === 'datetime') {
                    this.renderDatePickerView();
                } else if (this.options.mode === 'time') {
                    body.appendChild(this.createTimePicker());
                }

                return body;
            }

            renderDatePickerView() {
                this.elements.body.innerHTML = '';

                if (this.state.datePickerView === 'month-year') {
                    this.elements.body.appendChild(this.createMonthYearPicker());
                } else {
                    this.elements.body.appendChild(this.createDatePicker());
                }
            }

            createMonthYearPicker() {
                const container = document.createElement('div');
                container.className = 'mdtp-month-year-container';

                // Close button
                const closeBtn = document.createElement('button');
                closeBtn.className = 'mdtp-close-month-year';
                closeBtn.innerHTML = '❌';
                closeBtn.setAttribute('aria-label', 'Cerrar');
                closeBtn.addEventListener('click', () => {
                    // The state is already updated by the pickers, just close
                    this.state.datePickerView = 'date';
                    this.renderDatePickerView();
                    this.updateHeaderValue();
                });
                container.appendChild(closeBtn);

                // Year picker
                const yearColumn = this.createPickerColumn('year');
                container.appendChild(yearColumn);

                // Month picker
                const monthColumn = this.createPickerColumn('month');
                container.appendChild(monthColumn);

                return container;
            }

            createPickerColumn(type) {
                const column = document.createElement('div');
                column.className = 'mdtp-picker-column';

                const selector = document.createElement('div');
                selector.className = 'mdtp-picker-selector';
                column.appendChild(selector);

                const list = document.createElement('div');
                list.className = 'mdtp-picker-list';

                const itemHeight = 56;

                // Base values (no infinite scroll, finite list)
                const baseValues = type === 'year' ?
                    Array.from({
                        length: 3027
                    }, (_, i) => i + 1) :
                    this.locale.months;

                list.dataset.type = type;
                list.dataset.baseLength = baseValues.length;

                // Add padding at top and bottom for centering
                const padding = 4;

                // Top padding
                for (let i = 0; i < padding; i++) {
                    const item = document.createElement('div');
                    item.className = 'mdtp-picker-item';
                    item.innerHTML = '&nbsp;';
                    list.appendChild(item);
                }

                // Actual values
                baseValues.forEach((val, index) => {
                    const item = document.createElement('div');
                    item.className = 'mdtp-picker-item';
                    item.textContent = val;
                    item.dataset.value = type === 'year' ? val : index;
                    item.dataset.index = index;
                    list.appendChild(item);
                });

                // Bottom padding
                for (let i = 0; i < padding; i++) {
                    const item = document.createElement('div');
                    item.className = 'mdtp-picker-item';
                    item.innerHTML = '&nbsp;';
                    list.appendChild(item);
                }

                column.appendChild(list);

                // Initialize position
                const initialValue = type === 'year' ? this.state.viewYear : this.state.viewMonth;
                const initialIndex = type === 'year' ?
                    baseValues.indexOf(initialValue) :
                    initialValue;

                // Store state
                const state = {
                    type,
                    baseValues,
                    totalItems: baseValues.length,
                    itemHeight,
                    padding,
                    currentIndex: initialIndex,
                    offsetY: 0,
                    isDragging: false,
                    startY: 0,
                    startTranslate: 0,
                    velocity: 0,
                    lastY: 0,
                    lastTime: Date.now(),
                    lastAngle: 0 // For time picker hand rotation
                };

                list.pickerState = state;

                // Initial render and position
                this.scrollPickerToIndex(list, state, initialIndex);

                // Initialize scroll interaction (non-infinite)
                this.initPickerScrollFinite(column, list, state);

                return column;
            }

            scrollPickerToIndex(list, state, targetIndex) {
                // Clamp index to valid range
                targetIndex = Math.max(0, Math.min(targetIndex, state.totalItems - 1));

                state.currentIndex = targetIndex;
                // Calculate offset: center the selected item
                state.offsetY = -(state.padding + targetIndex) * state.itemHeight;

                list.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                list.style.transform = `translateY(${state.offsetY}px)`;

                this.updatePickerActiveItem(list, state);

                // Update value
                requestAnimationFrame(() => {
                    this.updatePickerValueFinite(state);
                });
            }

            updatePickerActiveItem(list, state) {
                const items = list.children;
                const centerIndex = state.padding + state.currentIndex;

                for (let i = 0; i < items.length; i++) {
                    items[i].classList.toggle('active', i === centerIndex);
                }
            }

            updatePickerValueFinite(state) {
                const {
                    type,
                    baseValues,
                    currentIndex
                } = state;

                // Clamp to valid range
                const clampedIndex = Math.max(0, Math.min(currentIndex, state.totalItems - 1));
                const value = type === 'year' ? baseValues[clampedIndex] : clampedIndex;

                if (type === 'year') {
                    this.state.viewYear = value;
                    this.updateHeaderLabel();
                } else {
                    this.state.viewMonth = value;
                }

                // Update selected date immediately
                const currentDay = this.state.selectedDate.getDate();
                const daysInNewMonth = new Date(this.state.viewYear, this.state.viewMonth + 1, 0).getDate();
                const newDay = Math.min(currentDay, daysInNewMonth);

                this.state.selectedDate = new Date(this.state.viewYear, this.state.viewMonth, newDay);
                this.updateHeaderValue();
            }

            initPickerScrollFinite(column, list, state) {
                const {
                    itemHeight,
                    padding,
                    totalItems
                } = state;
                let animationFrame = null;
                const scrollSensitivity = 0.3;

                const updatePosition = () => {
                    // Calculate current index from offset
                    const rawIndex = Math.round(-state.offsetY / itemHeight) - padding;
                    state.currentIndex = Math.max(0, Math.min(rawIndex, totalItems - 1));

                    this.updatePickerActiveItem(list, state);
                    this.updatePickerValueFinite(state);
                };

                const snap = () => {
                    // Snap to nearest valid item
                    const targetIndex = state.currentIndex;
                    const targetOffset = -(padding + targetIndex) * itemHeight;

                    state.offsetY = targetOffset;
                    list.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
                    list.style.transform = `translateY(${state.offsetY}px)`;

                    setTimeout(() => {
                        updatePosition();
                    }, 300);
                };

                const momentumScroll = () => {
                    if (Math.abs(state.velocity) > 0.5) {
                        state.offsetY += state.velocity;
                        state.velocity *= 0.92;

                        // Clamp to bounds
                        const minOffset = -(padding + totalItems - 1) * itemHeight;
                        const maxOffset = -padding * itemHeight;
                        state.offsetY = Math.max(minOffset, Math.min(maxOffset, state.offsetY));

                        list.style.transition = 'none';
                        list.style.transform = `translateY(${state.offsetY}px)`;

                        updatePosition();
                        animationFrame = requestAnimationFrame(momentumScroll);
                    } else {
                        snap();
                    }
                };

                const handleStart = (e) => {
                    state.isDragging = true;
                    state.startY = e.type.includes('mouse') ? e.clientY : e.touches[0].clientY;
                    state.startTranslate = state.offsetY;
                    state.velocity = 0;
                    state.lastY = state.startY;
                    state.lastTime = Date.now();

                    if (animationFrame) {
                        cancelAnimationFrame(animationFrame);
                        animationFrame = null;
                    }

                    list.style.transition = 'none';
                };

                const handleMove = (e) => {
                    if (!state.isDragging) return;

                    e.preventDefault();

                    const currentY = e.type.includes('mouse') ? e.clientY : e.touches[0].clientY;
                    const deltaY = (currentY - state.startY) * scrollSensitivity;
                    const currentTime = Date.now();
                    const deltaTime = currentTime - state.lastTime;

                    state.offsetY = state.startTranslate + deltaY;

                    // Clamp to bounds
                    const minOffset = -(padding + totalItems - 1) * itemHeight;
                    const maxOffset = -padding * itemHeight;
                    state.offsetY = Math.max(minOffset, Math.min(maxOffset, state.offsetY));

                    if (deltaTime > 0) {
                        state.velocity = ((currentY - state.lastY) / deltaTime * 16) * scrollSensitivity;
                    }

                    state.lastY = currentY;
                    state.lastTime = currentTime;

                    list.style.transform = `translateY(${state.offsetY}px)`;
                    updatePosition();
                };

                const handleEnd = () => {
                    if (!state.isDragging) return;
                    state.isDragging = false;

                    if (Math.abs(state.velocity) > 1) {
                        momentumScroll();
                    } else {
                        snap();
                    }
                };

                const handleWheel = (e) => {
                    e.preventDefault();

                    const delta = e.deltaY * scrollSensitivity;
                    state.offsetY -= delta;

                    // Clamp to bounds
                    const minOffset = -(padding + totalItems - 1) * itemHeight;
                    const maxOffset = -padding * itemHeight;
                    state.offsetY = Math.max(minOffset, Math.min(maxOffset, state.offsetY));

                    if (animationFrame) {
                        cancelAnimationFrame(animationFrame);
                    }

                    list.style.transition = 'none';
                    list.style.transform = `translateY(${state.offsetY}px)`;

                    updatePosition();

                    clearTimeout(list.snapTimeout);
                    list.snapTimeout = setTimeout(() => snap(), 150);
                };

                column.addEventListener('mousedown', handleStart);
                column.addEventListener('touchstart', handleStart, {
                    passive: false
                });

                document.addEventListener('mousemove', handleMove);
                document.addEventListener('touchmove', handleMove, {
                    passive: false
                });

                document.addEventListener('mouseup', handleEnd);
                document.addEventListener('touchend', handleEnd);

                column.addEventListener('wheel', handleWheel, {
                    passive: false
                });

                column.addEventListener('remove', () => {
                    document.removeEventListener('mousemove', handleMove);
                    document.removeEventListener('touchmove', handleMove);
                    document.removeEventListener('mouseup', handleEnd);
                    document.removeEventListener('touchend', handleEnd);
                    if (animationFrame) cancelAnimationFrame(animationFrame);
                    clearTimeout(list.snapTimeout);
                });

                updatePosition();
            }

            createDatePicker() {
                const container = document.createElement('div');

                const nav = document.createElement('div');
                nav.className = 'mdtp-date-nav';

                const prevBtn = document.createElement('button');
                prevBtn.className = 'mdtp-date-nav-btn';
                prevBtn.innerHTML = '❮';
                prevBtn.setAttribute('aria-label', 'Mes anterior');
                prevBtn.addEventListener('click', () => this.prevMonth());

                const title = document.createElement('div');
                title.className = 'mdtp-date-nav-title';
                title.textContent = `${this.locale.months[this.state.viewMonth]} ${this.state.viewYear}`;
                title.addEventListener('click', () => {
                    this.state.datePickerView = 'month-year';
                    this.renderDatePickerView();
                });

                const nextBtn = document.createElement('button');
                nextBtn.className = 'mdtp-date-nav-btn';
                nextBtn.innerHTML = '❯';
                nextBtn.setAttribute('aria-label', 'Mes siguiente');
                nextBtn.addEventListener('click', () => this.nextMonth());

                nav.appendChild(prevBtn);
                nav.appendChild(title);
                nav.appendChild(nextBtn);

                const calendar = document.createElement('div');
                calendar.className = 'mdtp-calendar';

                this.locale.daysShort.forEach(day => {
                    const dayEl = document.createElement('div');
                    dayEl.className = 'mdtp-calendar-day';
                    dayEl.textContent = day;
                    calendar.appendChild(dayEl);
                });

                const year = this.state.viewYear;
                const month = this.state.viewMonth;
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const daysInPrevMonth = new Date(year, month, 0).getDate();

                for (let i = firstDay - 1; i >= 0; i--) {
                    const btn = this.createDateButton(daysInPrevMonth - i, month - 1, year, true);
                    calendar.appendChild(btn);
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const btn = this.createDateButton(day, month, year, false);
                    calendar.appendChild(btn);
                }

                const totalCells = calendar.children.length - 7;
                const remainingCells = 42 - totalCells;

                for (let day = 1; day <= remainingCells; day++) {
                    const btn = this.createDateButton(day, month + 1, year, true);
                    calendar.appendChild(btn);
                }

                container.appendChild(nav);
                container.appendChild(calendar);
                return container;
            }

            createDateButton(day, month, year, isOtherMonth) {
                const btn = document.createElement('button');
                btn.className = 'mdtp-calendar-date';
                btn.textContent = day;

                if (isOtherMonth) {
                    btn.classList.add('other-month');
                }

                const btnDate = new Date(year, month, day);
                const today = new Date();
                today.setHours(0, 0, 0, 0);

                if (btnDate.getTime() === today.getTime()) {
                    btn.classList.add('today');
                }

                if (!isOtherMonth &&
                    day === this.state.selectedDate.getDate() &&
                    month === this.state.selectedDate.getMonth() &&
                    year === this.state.selectedDate.getFullYear()) {
                    btn.classList.add('selected');
                }

                btn.addEventListener('click', () => {
                    this.selectDate(new Date(year, month, day));
                });

                return btn;
            }

            createTimePicker() {
                const container = document.createElement('div');
                container.className = 'mdtp-time-container';

                // Analog clock container
                const clockContainer = document.createElement('div');
                clockContainer.className = 'mdtp-analog-clock-container';

                const clock = this.createAnalogClock();
                clockContainer.appendChild(clock);

                container.appendChild(clockContainer);

                return container;
            }

            createAnalogClock() {
                const clock = document.createElement('div');
                clock.className = 'mdtp-analog-clock';

                // Center dot
                const center = document.createElement('div');
                center.className = 'mdtp-clock-center';
                clock.appendChild(center);

                // Clock hand
                const hand = document.createElement('div');
                hand.className = 'mdtp-clock-hand';
                this.elements.clockHand = hand;
                clock.appendChild(hand);

                // Numbers container
                this.elements.clockNumbers = clock;
                this.renderClockNumbers();

                // Touch/mouse handlers
                this.initClockInteraction(clock);

                return clock;
            }

            // Add cleanup when switching views
            destroyClockInteraction() {
                if (this.elements.clockNumbers && this.elements.clockNumbers._cleanupEvents) {
                    this.elements.clockNumbers._cleanupEvents();
                }
            }

            renderClockNumbers() {
                // Remove existing numbers
                const existingNumbers = this.elements.clockNumbers.querySelectorAll('.mdtp-clock-number');
                existingNumbers.forEach(num => num.remove());

                const isHourView = this.state.timeView === 'hour';
                const radius = 110; // Distance from center
                const centerX = 130;
                const centerY = 130;

                if (isHourView) {
                    // Hour numbers (1-12 or 0-23 for 24h format)
                    const hours = this.options.format24h ?
                        Array.from({
                            length: 24
                        }, (_, i) => i) :
                        Array.from({
                            length: 12
                        }, (_, i) => i === 0 ? 12 : i);

                    hours.forEach((hour, index) => {
                        const angle = (index * 30 - 90) * (Math.PI / 180); // Start from top
                        const isInner = this.options.format24h && hour >= 1 && hour <= 12;
                        const r = isInner ? radius * 0.6 : radius;

                        const x = centerX + r * Math.cos(angle);
                        const y = centerY + r * Math.sin(angle);

                        const number = document.createElement('div');
                        number.className = 'mdtp-clock-number' + (isInner ? ' inner' : '');
                        number.textContent = hour === 0 ? '00' : hour;
                        number.style.left = `${x}px`;
                        number.style.top = `${y}px`;
                        number.style.transform = 'translate(-50%, -50%)';
                        number.dataset.value = hour;

                        const currentHour = this.options.format24h ? this.state.hour : (this.state.hour % 12 ||
                            12);
                        if (hour === currentHour) {
                            number.classList.add('selected');
                        }

                        number.addEventListener('click', () => {
                            this.selectClockValue(hour, 'hour');
                        });

                        this.elements.clockNumbers.appendChild(number);
                    });
                } else {
                    // Minute numbers (0, 5, 10, ..., 55)
                    for (let minute = 0; minute < 60; minute += 5) {
                        const angle = ((minute / 5) * 30 - 90) * (Math.PI / 180);
                        const x = centerX + radius * Math.cos(angle);
                        const y = centerY + radius * Math.sin(angle);

                        const number = document.createElement('div');
                        number.className = 'mdtp-clock-number';
                        number.textContent = String(minute).padStart(2, '0');
                        number.style.left = `${x}px`;
                        number.style.top = `${y}px`;
                        number.style.transform = 'translate(-50%, -50%)';
                        number.dataset.value = minute;

                        if (minute === this.state.minute) {
                            number.classList.add('selected');
                        }

                        number.addEventListener('click', () => {
                            this.selectClockValue(minute, 'minute');
                        });

                        this.elements.clockNumbers.appendChild(number);
                    }
                }

                this.updateClockHand();
            }

            initClockInteraction(clock) {
                let isDragging = false;
                let lastAngle = null;

                const handleInteraction = (e) => {
                    const rect = clock.getBoundingClientRect();
                    const centerX = rect.width / 2;
                    const centerY = rect.height / 2;

                    const clientX = e.type.includes('touch') ? e.touches[0].clientX : e.clientX;
                    const clientY = e.type.includes('touch') ? e.touches[0].clientY : e.clientY;

                    const x = clientX - rect.left - centerX;
                    const y = clientY - rect.top - centerY;

                    // Calculate angle from center (0° = 12 o'clock)
                    let angle = Math.atan2(y, x) * (180 / Math.PI) + 90;
                    if (angle < 0) angle += 360;

                    // Handle continuous rotation
                    if (lastAngle !== null) {
                        let angleDelta = angle - lastAngle;

                        // Detect crossing 0°/360° boundary
                        if (angleDelta > 180) {
                            angleDelta -= 360;
                        } else if (angleDelta < -180) {
                            angleDelta += 360;
                        }

                        // Update cumulative angle
                        this.state.clockHandAngle += angleDelta;
                    } else {
                        // First interaction - initialize angle
                        this.state.clockHandAngle = angle;
                    }

                    lastAngle = angle;

                    // Calculate value and update immediately
                    if (this.state.timeView === 'hour') {
                        const distance = Math.sqrt(x * x + y * y);
                        const isInner = this.options.format24h && distance < 70;

                        let normalizedAngle = ((this.state.clockHandAngle % 360) + 360) % 360;
                        let hour = Math.round(normalizedAngle / 30);
                        if (hour === 0) hour = 12;

                        if (this.options.format24h) {
                            if (isInner && hour === 12) hour = 0;
                            if (!isInner && hour !== 12) hour = hour === 12 ? 12 : hour + 12;
                            if (hour === 24) hour = 12;
                        }

                        this.selectClockValue(hour, 'hour', true);
                    } else {
                        // Minutes - direct following without snapping during drag
                        let normalizedAngle = ((this.state.clockHandAngle % 360) + 360) % 360;
                        let minute = Math.round(normalizedAngle / 6);
                        if (minute === 60) minute = 0;

                        this.selectClockValue(minute, 'minute', true);
                    }

                    // Update hand position immediately
                    this.updateClockHand();
                };

                const startDrag = (e) => {
                    if (e.type === 'touchstart') {
                        e.preventDefault();
                    }
                    isDragging = true;
                    lastAngle = null;

                    // Initialize cumulative angle from current value
                    if (this.state.timeView === 'hour') {
                        const hour = this.options.format24h ? this.state.hour : (this.state.hour % 12 || 12);
                        this.state.clockHandAngle = hour * 30;
                    } else {
                        this.state.clockHandAngle = this.state.minute * 6;
                    }

                    handleInteraction(e);
                };

                const moveDrag = (e) => {
                    if (isDragging) {
                        if (e.type === 'touchmove') {
                            e.preventDefault();
                        }
                        handleInteraction(e);
                    }
                };

                const endDrag = () => {
                    if (isDragging) {
                        isDragging = false;
                        lastAngle = null;

                        // Auto-switch to minutes after selecting hour
                        if (this.state.timeView === 'hour') {
                            setTimeout(() => {
                                this.switchTimeView('minute');
                            }, 300);
                        }
                    }
                };

                // Attach events to clock for start
                clock.addEventListener('mousedown', startDrag);
                clock.addEventListener('touchstart', startDrag, {
                    passive: false
                });

                // Attach move and end events to document for global tracking
                document.addEventListener('mousemove', moveDrag);
                document.addEventListener('touchmove', moveDrag, {
                    passive: false
                });
                document.addEventListener('mouseup', endDrag);
                document.addEventListener('touchend', endDrag);

                // Store cleanup function
                clock._cleanupEvents = () => {
                    document.removeEventListener('mousemove', moveDrag);
                    document.removeEventListener('touchmove', moveDrag);
                    document.removeEventListener('mouseup', endDrag);
                    document.removeEventListener('touchend', endDrag);
                };
            }

            selectClockValue(value, type, fromDrag = false) {
                if (type === 'hour') {
                    if (this.options.format24h) {
                        this.state.hour = value;
                    } else {
                        const hour12 = value === 0 ? 12 : value;
                        this.state.hour = this.state.period === 'PM' ?
                            (hour12 === 12 ? 12 : hour12 + 12) :
                            (hour12 === 12 ? 0 : hour12);
                    }

                    const displayHour = this.options.format24h ? this.state.hour : (this.state.hour % 12 || 12);
                    if (this.elements.hourSegment) {
                        this.elements.hourSegment.textContent = String(displayHour).padStart(2, '0');
                    }
                } else {
                    this.state.minute = value;
                    if (this.elements.minuteSegment) {
                        this.elements.minuteSegment.textContent = String(this.state.minute).padStart(2, '0');
                    }
                }

                this.renderClockNumbers();
                this.updateHeaderValue();
            }

            updateClockHand() {
                if (!this.elements.clockHand) return;

                const isHourView = this.state.timeView === 'hour';
                let length;

                if (isHourView) {
                    const isInner = this.options.format24h && this.state.hour >= 1 && this.state.hour <= 12;
                    length = isInner ? 60 : 100;
                } else {
                    length = 100;
                }

                // Use cumulative angle for continuous rotation
                const angle = this.state.clockHandAngle;

                this.elements.clockHand.style.height = `${length}px`;
                this.elements.clockHand.style.transform = `translate(-50%, -100%) rotate(${angle}deg)`;
            }

            switchTimeView(view) {
                this.state.timeView = view;

                if (this.elements.hourSegment && this.elements.minuteSegment) {
                    this.elements.hourSegment.classList.toggle('active', view === 'hour');
                    this.elements.minuteSegment.classList.toggle('active', view === 'minute');
                }

                // Initialize angle for new view
                if (view === 'hour') {
                    const hour = this.options.format24h ? this.state.hour : (this.state.hour % 12 || 12);
                    this.state.clockHandAngle = hour * 30;
                } else {
                    this.state.clockHandAngle = this.state.minute * 6;
                }

                this.renderClockNumbers();
            }

            setPeriod(period) {
                this.state.period = period;

                if (this.elements.amBtn && this.elements.pmBtn) {
                    this.elements.amBtn.classList.toggle('active', period === 'AM');
                    this.elements.pmBtn.classList.toggle('active', period === 'PM');
                }

                if (!this.options.format24h) {
                    const displayHour = this.state.hour % 12 || 12;
                    this.state.hour = period === 'PM' ?
                        (displayHour === 12 ? 12 : displayHour + 12) :
                        (displayHour === 12 ? 0 : displayHour);
                }

                if (this.elements.hourSegment) {
                    const displayHour = this.options.format24h ? this.state.hour : (this.state.hour % 12 || 12);
                    this.elements.hourSegment.textContent = String(displayHour).padStart(2, '0');
                }

                this.updateHeaderValue();
            }

            createPeriodSelector() {
                const period = document.createElement('div');
                period.className = 'mdtp-time-period';

                const amBtn = document.createElement('button');
                amBtn.className = 'mdtp-time-period-btn' + (this.state.period === 'AM' ? ' active' : '');
                amBtn.textContent = 'AM';
                amBtn.addEventListener('click', () => this.setPeriod('AM'));
                this.elements.amBtn = amBtn;

                const pmBtn = document.createElement('button');
                pmBtn.className = 'mdtp-time-period-btn' + (this.state.period === 'PM' ? ' active' : '');
                pmBtn.textContent = 'PM';
                pmBtn.addEventListener('click', () => this.setPeriod('PM'));
                this.elements.pmBtn = pmBtn;

                period.appendChild(amBtn);
                period.appendChild(pmBtn);

                return period;
            }

            createActions() {
                const actions = document.createElement('div');
                actions.className = 'mdtp-actions';

                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'mdtp-btn mdtp-btn-text';
                cancelBtn.textContent = this.locale.cancel;
                cancelBtn.addEventListener('click', () => this.cancel());

                const confirmBtn = document.createElement('button');
                confirmBtn.className = 'mdtp-btn mdtp-btn-filled';
                confirmBtn.textContent = this.locale.confirm;
                confirmBtn.addEventListener('click', () => this.confirm());

                actions.appendChild(cancelBtn);
                actions.appendChild(confirmBtn);

                return actions;
            }

            updateBody() {
                if (this.elements.body) {
                    this.renderDatePickerView();
                }
            }

            updateHeaderLabel() {
                if (this.elements.headerLabel) {
                    this.elements.headerLabel.textContent = this.state.viewYear;
                }
            }

            updateHeaderValue() {
                if (!this.elements.headerValue) return;

                if (this.options.mode === 'date' || this.options.mode === 'datetime') {
                    const date = this.state.selectedDate;
                    const dayName = this.locale.daysAbbr[date.getDay()];
                    const day = date.getDate();
                    const month = this.locale.monthsShort[date.getMonth()];

                    this.elements.headerValue.textContent = `${dayName}, ${day} de ${month}`;
                } else {
                    const hour = this.options.format24h ?
                        this.state.hour :
                        (this.state.hour % 12 || 12);
                    const minute = this.state.minute;
                    const period = this.options.format24h ? '' : ` ${this.state.period}`;
                    this.elements.headerValue.textContent =
                        `${String(hour).padStart(2, '0')}:${String(minute).padStart(2, '0')}${period}`;
                }
            }

            selectDate(date) {
                // Update the selected date with the exact date clicked
                this.state.selectedDate = new Date(date);
                // Sync view year and month with the selected date
                this.state.viewYear = date.getFullYear();
                this.state.viewMonth = date.getMonth();

                if (this.options.mode === 'datetime') {
                    this.switchToTimePicker();
                } else {
                    this.renderDatePickerView();
                    this.updateHeaderValue();
                }
            }

            switchToTimePicker() {
                this.elements.body.innerHTML = '';
                this.elements.body.appendChild(this.createTimePicker());

                // Update header to time display
                const header = this.elements.picker.querySelector('.mdtp-header');
                header.innerHTML = '';
                const timeHeader = this.createTimeHeader();
                header.appendChild(timeHeader);

                this.updateHeaderValue();
            }

            prevMonth() {
                if (this.state.viewMonth === 0) {
                    this.state.viewMonth = 11;
                    this.state.viewYear--;
                } else {
                    this.state.viewMonth--;
                }

                // Update selected date to maintain consistency
                const currentDay = this.state.selectedDate.getDate();
                const daysInMonth = new Date(this.state.viewYear, this.state.viewMonth + 1, 0).getDate();
                const newDay = Math.min(currentDay, daysInMonth);
                this.state.selectedDate = new Date(this.state.viewYear, this.state.viewMonth, newDay);

                this.updateHeaderLabel();
                this.updateHeaderValue();
                this.renderDatePickerView();
            }

            nextMonth() {
                if (this.state.viewMonth === 11) {
                    this.state.viewMonth = 0;
                    this.state.viewYear++;
                } else {
                    this.state.viewMonth++;
                }

                // Update selected date to maintain consistency
                const currentDay = this.state.selectedDate.getDate();
                const daysInMonth = new Date(this.state.viewYear, this.state.viewMonth + 1, 0).getDate();
                const newDay = Math.min(currentDay, daysInMonth);
                this.state.selectedDate = new Date(this.state.viewYear, this.state.viewMonth, newDay);

                this.updateHeaderLabel();
                this.updateHeaderValue();
                this.renderDatePickerView();
            }

            setPeriod(period) {
                this.state.period = period;

                if (this.elements.amBtn && this.elements.pmBtn) {
                    this.elements.amBtn.classList.toggle('active', period === 'AM');
                    this.elements.pmBtn.classList.toggle('active', period === 'PM');
                }

                if (!this.options.format24h) {
                    const displayHour = this.state.hour % 12 || 12;
                    this.state.hour = period === 'PM' ?
                        (displayHour === 12 ? 12 : displayHour + 12) :
                        (displayHour === 12 ? 0 : displayHour);
                }

                this.updateHeaderValue();
            }

            convert12to24(hour) {
                if (this.state.period === 'PM') {
                    return hour === 12 ? 12 : hour + 12;
                }
                return hour === 12 ? 0 : hour;
            }

            confirm() {
                let result, formattedValue;

                if (this.options.mode === 'date') {
                    // Use the currently selected date (not today's date)
                    result = new Date(this.state.selectedDate);
                    formattedValue = DateTimeFormatter.format(
                        result,
                        this.options.format,
                        this.options.format24h,
                        this.state.period
                    );
                } else if (this.options.mode === 'time') {
                    result = {
                        hour: this.state.hour,
                        minute: this.state.minute
                    };
                    const tempDate = new Date();
                    tempDate.setHours(this.state.hour, this.state.minute);
                    formattedValue = DateTimeFormatter.format(
                        tempDate,
                        this.options.format,
                        this.options.format24h,
                        this.state.period
                    );
                } else {
                    // datetime mode
                    const datetime = new Date(this.state.selectedDate);
                    datetime.setHours(this.state.hour, this.state.minute, 0, 0);
                    result = datetime;
                    formattedValue = DateTimeFormatter.format(
                        datetime,
                        this.options.format,
                        this.options.format24h,
                        this.state.period
                    );
                }

                this.input.value = formattedValue;
                this.options.onConfirm(result, formattedValue);

                if (this.options.closeOnConfirm) {
                    this.hide();
                }
            }

            cancel() {
                this.options.onCancel();
                this.hide();
            }

            trapFocus() {
                const focusableElements = this.elements.picker.querySelectorAll(
                    'button, [tabindex]:not([tabindex="-1"])'
                );

                const firstFocusable = focusableElements[0];
                const lastFocusable = focusableElements[focusableElements.length - 1];

                this.elements.picker.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape') {
                        this.cancel();
                        return;
                    }

                    if (e.key === 'Tab') {
                        if (e.shiftKey) {
                            if (document.activeElement === firstFocusable) {
                                e.preventDefault();
                                lastFocusable.focus();
                            }
                        } else {
                            if (document.activeElement === lastFocusable) {
                                e.preventDefault();
                                firstFocusable.focus();
                            }
                        }
                    }
                });

                firstFocusable.focus();
            }

            destroy() {
                this.hide();
                MaterialDateTimePicker.instances.delete(this.options.inputId);
            }
        }

        /* ==================== DEMO INITIALIZATION ==================== */
        let currentTheme = 'light';

        new MaterialDateTimePicker({
            inputId: 'fecha_cita',
            mode: 'date',
            locale: 'es',
            format: 'DD/MM/YYYY',
            theme: currentTheme,
            onConfirm: (date, formatted) => {
                updateOutput('Fecha de cita', formatted, date);
            }
        });

        new MaterialDateTimePicker({
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
