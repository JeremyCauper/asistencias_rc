function fillSelect(selector, data, filterField, filterValue, optionValue, optionText, optionCondition) {
    $(selector.join()).html($('<option>').val('').html('-- Seleccione --')).attr('disabled', true);
    if (!filterValue) return false;

    if (Array.isArray(data)) {
        data.forEach(e => {
            if (e[filterField] == filterValue && e[optionCondition])
                $(selector[0]).append($('<option>').val(e[optionValue]).text(e[optionText]));
        });
    } else if (typeof data === 'object') {
        Object.entries(data).forEach(([key, e]) => {
            if (e[filterField] == filterValue && e[optionCondition])
                $(selector[0]).append($('<option>').val(e[optionValue]).text(e[optionText]));
        });
    }
    $(selector[0]).attr('disabled', false);
}

// Función para manejar controladores y configurar atributos
function configControls(controls) {
    controls.forEach(formControl => {
        const configuracion = (control, setting) => {
            const idString = control.replaceAll('#', '');
            const input = $(control);

            if (input.length === 0) {
                console.error(`El control "${idString}" no existe`);
                return;
            }

            let labelText = (setting.addLabel || idString).replaceAll('_', ' ');
            let label = $('<label>', {
                for: idString,
                text: labelText,
                class: 'form-label'
            }).insertBefore(control);

            let settings = {
                name: setting.name || idString,
                type: setting.type || false,
                value: setting.val || false,
                controlType: setting.controlType || false,
                requested: setting.requested || false,
                minLength: setting.mnl || false,
                maxLength: setting.mxl || false,
                disabled: setting.disabled || false,
                placeholder: setting.pholder || "",
                lengthMessage: setting.lengthMessage || `El campo '${labelText.toUpperCase()}' debe tener entre ${setting.mnl || 0} y ${setting.mxl || 0} dígitos.`,
                errorMessage: setting.errorMessage || false,
            };

            if (input.is('input')) {
                settings.type = settings.type || 'text';
                settings.controlType = settings.controlType || "string";
            } else if (input.is('button')) {
                settings.type = 'button';
            } else if (input.is('select')) {
                settings.lengthMessage = false;
            }

            if (settings.controlType == 'tel') {
                Inputmask("999999999", { placeholder: "0", greedy: true, casing: "upper", jitMasking: true }).mask(control);
                settings.minLength = 9;
                settings.maxLength = 9;
            }

            if (settings.controlType == 'dni') {
                Inputmask("99999999", { placeholder: "0", greedy: true, casing: "upper", jitMasking: true }).mask(control);
                settings.minLength = 8;
                settings.maxLength = 8;
            }

            if (settings.controlType == 'ndoc') {
                Inputmask("99999999999", { placeholder: "0", greedy: true, casing: "upper", jitMasking: true }).mask(control);
                settings.minLength = 8;
                settings.maxLength = 11;
            }

            for (let [key, value] of Object.entries(settings)) {
                if (key == 'errorMessage' && !value) {
                    value = {
                        'ruc': 'El numero de RUC es invalido.',
                        'dni': 'El numero de DNI es invalido.',
                        'ndoc': 'El numero de DOCUMENTO es invalido.',
                        'int': 'El dato ingresado debe ser un numero.',
                        'float': 'El dato ingresado debe ser un numero decimal.',
                        'date': 'El dato ingresado debe ser un numero.',
                        'email': 'El correo electrónico ingresado no es válido.',
                    }[settings.controlType] || '';
                }

                if (value) {
                    if (key == 'requested') {
                        value = labelText.toUpperCase();
                        label.addClass('requested');
                    }
                    input.attr(key, value);
                }
            }

            const { mask } = setting.mask || false;
            if (mask) {
                const { reg = "999999999", conf = { placeholder: "0", greedy: true, casing: "upper", jitMasking: true } } = mask;
                Inputmask(reg, conf).mask(control);
            }
        };

        if (typeof formControl.control === "string") {
            return configuracion(formControl.control, formControl);
        }
        formControl.control.forEach(control => { configuracion(control, formControl) });
    });
}

function validFrom(_this) {
    var dat = _this.querySelectorAll('[name]');
    var dataF = { success: true, data: { data: {}, requested: [] } };

    for (let i = 0; i < dat.length; i++) {
        const e = dat[i];
        const value = $(e).val();
        if (value && e.getAttribute("controlType")) {

        }
        switch (e.getAttribute("controlType")) {
            case 'ruc':
                var validRuc = /^(10|20)/.test(value);
                if (!validRuc) {
                    dataF.success = false;
                    boxAlert.box({ i: 'warning', t: 'Datos invalidos', h: e.getAttribute("errorMessage") });
                    return dataF;
                }
                break;

            case 'email':
                const emailValue = value;
                const emailRegex = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                if (emailValue && !emailRegex.test(emailValue)) {
                    dataF.success = false;
                    boxAlert.box({ i: 'warning', t: 'Datos invalidos', h: e.getAttribute("errorMessage") });
                    return dataF;
                }
                break;

            default:
                break;
        }

        if (e.getAttribute("requested") && !value) {
            dataF.data.requested.push(`<p class="mb-1" style="font-size:.85rem;"><b>${e.getAttribute("requested")}</b></p>`);
        }

        if (e.getAttribute("type") == 'radio') {
            if (dataF.data.data.hasOwnProperty(e.name)) {
                if (e.checked) dataF.data.data[e.name] = value;
            } else {
                dataF.data.data[e.name] = e.checked ? value : '';
            }
        } else {
            dataF.data.data[e.name] = value;
        }
    }

    if (dataF.data.requested.length > 0) {
        dataF.success = false;
        boxAlert.box({
            i: 'info',
            t: 'Faltan datos',
            h: `<h6 class="text-secondary">Los siguientes campos son requeridos</h6>${dataF.data.requested.join('')}`
        });
    }

    return dataF;
}

function extractDataRow($this, $table = null) {
    const ths = document.querySelectorAll(`${$table ? `#${$table}_wrapper` : ""} .dataTables_scrollHeadInner table thead tr th`);
    const tr = $this.parentNode.parentNode.parentNode.parentNode;
    const tds = tr.querySelectorAll('td');
    var obj_return = {};

    ths.forEach(function (e, i) {
        if (i != 9) obj_return[(e.innerHTML).toLowerCase()] = tds[i].innerHTML;
    });

    return obj_return;
}

function formatRequired(data) {
    let $mensaje = "EL campo :atributo es requerido.";
    var result = "";
    for (const key in data) {
        const text = $(`[for="${key}"]`).html() ?? key.toUpperCase();
        result = `<li><b>${$mensaje.replace(':atributo', text)}</b></li>`;
    }
    return `<ul style="font-size:.75rem;">${result}</ul>`;
}

function formatUnique(data) {
    let $mensaje = "El dato ingresado en el campo :atributo, ya está en uso.";
    var result = "";
    data.forEach(function (e) {
        if (e == "cod_inc") {
            $mensaje = "El codigo de incidencia ya está en uso.";
        }
        const text = $(`[for="${e}"]`).html() ?? e.toUpperCase();
        result = `<li><b>${$mensaje.replace(':atributo', text)}</b></li>`;
    });
    return `<ul style="font-size:.75rem;">${result}</ul>`;
}

function date(format, strtime = null) {
    const now = strtime ? new Date(strtime) : new Date();

    const map = {
        'Y': now.getFullYear(),                // Año completo (2024)
        'm': String(now.getMonth() + 1).padStart(2, '0'),  // Mes (01-12)
        'd': String(now.getDate()).padStart(2, '0'),       // Día del mes (01-31)
        'H': String(now.getHours()).padStart(2, '0'),      // Horas (00-23)
        'i': String(now.getMinutes()).padStart(2, '0'),    // Minutos (00-59)
        's': String(now.getSeconds()).padStart(2, '0'),    // Segundos (00-59)
        'j': now.getDate(),                                // Día del mes sin ceros iniciales (1-31)
        'n': now.getMonth() + 1,                           // Mes sin ceros iniciales (1-12)
        'w': now.getDay(),                                 // Día de la semana (0 = domingo, 6 = sábado)
        'G': now.getHours(),                               // Horas sin ceros iniciales (0-23)
        'a': now.getHours() >= 12 ? 'pm' : 'am',           // am o pm
        'A': now.getHours() >= 12 ? 'PM' : 'AM'            // AM o PM en mayúsculas
    };

    return format.replace(/[YmdHisjwnGaA]/g, (match) => map[match]);
}

let xhrConsultaDni = null;
async function consultarDoc(dni) {
    if (xhrConsultaDni) {
        xhrConsultaDni.abort();
    }

    try {
        xhrConsultaDni = $.ajax({
            url: `${__url}/api/ConsultaDoc/Consulta?doc=${dni}`,
            method: "GET",
            dataType: "json",
            contentType: 'application/json',
        });

        const response = await xhrConsultaDni;
        return response;

    } catch (error) {
        if (error.statusText === "abort") {
            console.log("Petición cancelada");
            return { status: 0, message: "Petición cancelada" };
        }
        return error.responseJSON || { status: 500, message: "Error desconocido" };
    } finally {
        xhrConsultaDni = null;
    }
}

async function cancelarConsultarDoc() {
    consulta = $('span[data-con="consulta"]');
    if (consulta.length) {
        consulta.parent().addClass('d-flex justify-content-between mt-1');
        consulta.remove();
    }
    if (xhrConsultaDni) {
        xhrConsultaDni.abort();
    }
}

async function consultarDniInput($this) {
    const label = $(`[for="${$this.attr('id')}"`);
    let doc = $this.val();

    if (!doc || doc.length > 8) return label.find('span[data-con="consulta"]').remove();
    if (label.find('span.text-info').length) {
        return true;
    }
    try {
        let cargar = '<span class="spinner-border" role="status" style="width: .8rem; height: .8rem;"></span> Consultando';
        if (label.find('span[data-con="consulta"]').length) {
            label.find('span[data-con="consulta"]').html(cargar).attr('class', 'text-info');
        } else {
            label.addClass('d-flex justify-content-between mt-1').append(`<span data-con="consulta" class="text-info" style="font-size: .68rem;">${cargar}</span>`);
        }
        let consultar = label.find('span[data-con="consulta"]');
        const datos = await consultarDoc(doc);
        switch (datos.status) {
            case 200:
                label.removeClass('d-flex justify-content-between mt-1');
                consultar.remove();
                break;

            case 400:
                consultar.html('Doc. Invalido').attr('class', 'text-danger');
                break;

            case 502:
                consultar.html('Doc. Invalido').attr('class', 'text-warning');
                break;

            default:
                consultar.html('Servicio Inhabilitado').attr('class', 'text-danger');
                break;
        }
        return datos;
    } catch (error) {
        console.log("No se pudo consultar el DNI.:", error);
    }
}

function llenarInfoModal(id_modal, data = {}) {
    if (Object.entries(data).length) {
        Object.entries(data).forEach(([key, e]) => {
            $(`#${id_modal} [aria-item="${key}"`).html(e);
        });
    } else {
        $(`#${id_modal} [aria-item`).each((i, e) => {
            $(e).html('');
        });
    }
}

function setMediaUrls(contenedorId, archivos, baseUrl = '') {
    const $contenedor = $(contenedorId);

    if ($contenedor.length === 0) {
        alert("Contenedor no encontrado:", contenedorId);
        return;
    }

    // Seleccionar todos los elementos con data-id dentro del contenedor
    $contenedor.find("[data-id]").each(function () {
        const $el = $(this);
        const id = $el.data("id");

        // Buscar coincidencia en el array de archivos
        const file = archivos.find(x => x.nombre_archivo == id);

        if (!file) {
            console.warn("Archivo no encontrado para id:", id);
            return;
        }

        // Concatenar URL final
        const fullUrl = file.url_publica;

        // Asignar según tipo de elemento
        if ($el.is("img")) {
            $el.attr("src", fullUrl);
        } else if ($el.is("iframe")) {
            $el.attr("src", fullUrl);
        } else if ($el.is("a")) {
            $el.attr("href", fullUrl);
        }

        // Opcional: manejar estatus (ejemplo)
        if (file.estatus === 2) {
            $el.css("opacity", 0.6);
        }
    });

    $contenedor[0].addEventListener("click", e => {
        const el = e.target;

        if (el.tagName === "IMG") {
            mediaViewer.openImage(el.src);
        }
    });
}

function llenarInfoTipoInc(id_modal, data) {
    let tipoi = data.incidencia.tipo_incidencia;
    let estado = data.incidencia.estado_informe;
    let seguimiento = data.seguimiento?.final?.date ?? "En Proceso ...";

    let resultado = Object.entries(tipoi).map(([key, e]) => {
        let tipoInc = tipo_incidencia[e.id_tipo_inc];
        let dnone = "", fecha_ini = "", fecha_fin = "";

        if (estado == 0 || estado == 1) {
            fecha_ini = "Sin Iniciar";
            dnone = "d-none";
        } else {
            fecha_ini = `${e.fecha} ${e.hora}`;
            if (key == (tipoi.length - 1)) {
                fecha_fin = seguimiento;
            } else {
                let ffin = tipoi[eval(key) + 1];
                fecha_fin = `${ffin.fecha} ${ffin.hora}`;
            }
        }
        return `
            <div class="col-lg-4 col-md-6 mt-2">
                <div class="d-flex align-items-center">
                    <label class="badge badge-${tipoInc.color}">${tipoInc.tipo}</label>
                    <div class="ms-2 w-100">
                        <p class="d-flex justify-content-between mb-0 pe-lg-5 col-5 col-md-12" style="font-weight: 500;font-size: small;">${tipoInc.descripcion}<span>${calcularDuracion(fecha_ini, fecha_fin)}</span></p>
                        <p class="text-muted mb-0" style="font-size: .675rem;"><b style="letter-spacing: 0.1em;">I: </b>${fecha_ini}</p>
                        <p class="text-muted mb-0 ${dnone}" style="font-size: .675rem;"><b>F: </b>${fecha_fin}</p>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    $(`#${id_modal} [aria-item="incidencia"]`).html(`
        <div class="row">
            ${resultado}
        </div>
    `);
}

/**
 * Calcula la diferencia entre dos fechas (format "YYYY-MM-DD HH:mm:ss")
 * y devuelve un string "Xh Ym Zs".
 *
 * @param {string} fechaIni - Fecha de inicio, p. ej. "2025-04-21 10:49:44"
 * @param {string} fechaFin - Fecha de fin,    p. ej. "2025-04-21 13:55:44"
 * @returns {string} Diferencia en "##h ##m ##s"
 */
function calcularDuracion(fechaIni, fechaFin) {
    // Convertir " " en "T" para que Date lo interprete como ISO
    const inicio = new Date(fechaIni.replace(' ', 'T'));
    const fin = new Date(fechaFin.replace(' ', 'T'));

    // Diferencia en milisegundos
    let diffMs = fin - inicio;
    if (isNaN(diffMs)) {
        return '';
    }
    if (diffMs < 0) {
        // Si la fecha fin es anterior, invertimos o lanzamos error
        diffMs = Math.abs(diffMs);
    }

    const totalSegundos = Math.floor(diffMs / 1000);
    const segundos = totalSegundos % 60;
    const totalMinutos = Math.floor(totalSegundos / 60);
    const minutos = totalMinutos % 60;
    const horas = Math.floor(totalMinutos / 60);

    return `${horas}h ${minutos}m ${segundos}s`;
}

//   // Ejemplo de uso:
//   const inicio = "2025-04-21 10:49:44";
//   const fin    = "2025-04-21 13:55:44";
//   console.log(calcularDuracion(inicio, fin)); // "3h 6m 0s"

function getBadgeAreas(estado, size = '.75', fill = true) {
    let area = tipoAreas.find(tp => tp.id == estado) || {
        descripcion: 'Sin Area',
        color: '#7e7979'
    };

    return `<label ${fill
        ? `class="badge" style="font-size: ${size}rem;background-color: ${area.color};"`
        : `style="font-size: ${size}rem;color: ${area.color};"`
        }>${area.descripcion}</label>`;
}


function getBadgeTipoPersonal(estado, size = '.75', muted = false) {
    let tipo = tipoPersonal.find(tp => tp.id == estado) || {
        descripcion: 'Sin Tipo',
        color: '#7e7979'
    };

    return `<label ${muted
        ? `class="text-muted" style="font-size: ${size}rem;"`
        : `style="font-size: ${size}rem;color: ${tipo.color};"`
        }>${tipo.descripcion}</label>`;
}

function getBadgeTipoModalidad(estado, size = '.75') {
    let modalidad = tipoModalidad.find(tp => tp.id == estado) || {
        descripcion: 'Sin Modalidad',
        color: '#7e7979'
    };
    let color = modalidad?.color;
    return `<label class="badge" style="font-size: ${size}rem; background-color: ${color}20;color: ${color};border: 1px solid ${color};">
            <i class="${modalidad?.icono} fa-1x me-1"></i>${modalidad?.descripcion}
        </label>`;
}

function getBadgeTipoAsistencia(estado, size = '.75') {
    let tipo = tipoAsistencia.find(tp => tp.id == estado) || {
        descripcion: 'Pendiente',
        color: '#7e7979'
    };
    return `<label class="badge" style="font-size: ${size}rem;background-color: ${tipo.color}25;color: ${tipo.color};">${tipo.descripcion}</label>`;
}

function getFormatJornada(data) {
    return `<label>
        <i class="far fa-clock"></i>
        <span style="vertical-align: middle;">${data.entrada || 'No Check-in'}${data.salida ? ` - ${data.salida}` : ''}</span>
    </label>`;
}

function getBadgeDescuento(data) {
    if (data.descuento || data.tipo_asistencia == 1) {
        return `
        <label class="badge" style="border: 1px solid #f87171;color: #f87171;">
            <i class="fas fa-triangle-exclamation me-2" style="font-size: .75rem;"></i>
            <span> ${data.descuento ? `S/ -${data.descuento}` : (data.tipo_asistencia == 1 ? 'Día Comp.' : '')}</span>
        </label>`;
    }
    return ''
}

function getBadgeEstadoSync(estado, size = '.75', fill = true) {
    let estadoTexto = [
        ['secondary', 'Creando'],
        ['success', 'Sincronizado'],
        ['warning', 'Modificando'],
        ['danger', 'Eliminando']
    ][estado] || ['dark', 'Desconocido'];

    return `<label class="badge ${fill
        ? `badge-${estadoTexto[0]}`
        : `text-${estadoTexto[0]} border border-${estadoTexto[0]}`
        }"  style="font-size: ${size}rem;">${estadoTexto[1]} en Sensor</label>`;
}

function getBadgeEstado(estado, size = '.75') {
    let estadoTexto = [
        ['danger', 'Inactivo'],
        ['success', 'Activo']
    ][estado] || ['dark', 'Desconocido'];
    return `<label class="badge badge-${estadoTexto[0]}" style="font-size: ${size}rem;">${estadoTexto[1]}</label>`;
}

function mostrar_acciones(table = null) {
    if (!table) return;
    const idTabla = table.table().node().id;

    const tableSelector = idTabla ? `#${idTabla}` : '';
    const dataTables_scrollBody = $(`${idTabla ? `#${idTabla}_wrapper` : '.dataTables_wrapper'} .dataTables_scrollBody`);
    let filaAccionActivo = null;
    let filaAccionOld = null;
    // let openOnCkick = false;

    const animateProperty = (element, property, start, end, duration, fps, callback = null) => {
        let current = start;
        element.css((property == 'left' ? 'right' : 'left'), '');
        const totalFrames = duration / (1000 / fps);
        const delta = (end - start) / totalFrames;
        const interval = setInterval(() => {
            current += delta;
            element.css(property, `${current}px`);
            // Condición de finalización según dirección de la animación
            if ((delta > 0 && current >= end) || (delta < 0 && current <= end)) {
                clearInterval(interval);
                if (typeof callback === "function") callback();
            }
        }, 1000 / fps);
    }

    const getWidthTdAccion = (td_accion) => {
        const width_tabla = $(tableSelector)[0].clientWidth + 11;
        const width_scroll = dataTables_scrollBody[0].clientWidth;
        const width_tdAccion = td_accion[0].clientWidth;
        const width_btnGroup = td_accion.find('.dropdown i')[0].clientWidth;

        return (width_tabla - ((width_tdAccion / 2) + (width_btnGroup / 2))) < width_scroll;
    }

    const getScrollTdAccion = (td_accion) => {
        const distanciaAlFinal = dataTables_scrollBody.get(0).scrollWidth - dataTables_scrollBody.get(0).scrollLeft - dataTables_scrollBody.get(0).clientWidth;
        const anchoField = ((td_accion[0].clientWidth / 2) + (td_accion.find('.dropdown i')[0].clientWidth / 2));
        return distanciaAlFinal < anchoField;
    }

    const getBgColorRow = fila_activa => { // Extraemos los valores RGB de la fila, para eliminar cualquier opacidad
        const [r, g, b] = fila_activa.css('background-color').match(/\d+/g) || [255, 255, 255];
        return `rgb(${r}, ${g}, ${b})`;
    };

    let paginaActual = null;
    // Cuando se dibuja la tabla (draw.dt) se asocian los eventos a cada fila
    table.off("draw.dt").on('draw.dt', function () {
        let nuevaPagina = table.page();

        $("tr:has(.td-acciones)").off();
        $("tr:has(.td-acciones)").each(function () {
            const tdAcciones = $(this).find(".td-acciones");
            if (!tdAcciones.length) return;

            if (paginaActual != nuevaPagina) {
                tdAcciones.removeClass('active-acciones sticky-activo').removeAttr('style');
                filaAccionActivo = null;
                filaAccionOld = null;
            }

            let evento = esCelular() ? 'click' : 'mouseenter';
            $(this).off(evento).on(evento, function () { // Fila a la que se le dió click
                // if (openOnCkick) return;

                filaAccionActivo = $(this);
                if (!esCelular()) {
                    filaAccionOld = ($("tr:has(.active-acciones)")?.length) ? $("tr:has(.active-acciones)") : $("tr:has(.dropdown-menu.show)");
                } else {
                    filaAccionOld = $("tr:has(.active-acciones)");
                }

                if (filaAccionActivo.is(filaAccionOld)) return;
                const newTdAccion = filaAccionActivo.find(".td-acciones");
                if (filaAccionOld?.length) {
                    const oldTdAccion = filaAccionOld.find(".td-acciones");
                    if (!newTdAccion.hasClass('active-acciones') && oldTdAccion.hasClass('active-acciones')) {
                        if (!esCelular() && oldTdAccion.find('.dropdown-menu').hasClass('show')) return;
                        animateProperty(oldTdAccion, 'right', -43, -75, 150, 60, () => {
                            oldTdAccion.removeClass('active-acciones sticky-activo').removeAttr('style');
                        });
                        filaAccionOld = null;
                    }
                }
                if (getScrollTdAccion(newTdAccion)) return;

                if (getWidthTdAccion(newTdAccion)) {
                    if (!newTdAccion.find('.dropdown-menu').hasClass('show')) {
                        newTdAccion.removeClass('active-acciones').removeAttr('style');
                    }
                    return newTdAccion.removeClass('sticky-activo');
                }

                if (newTdAccion.hasClass('active-acciones')) {
                    if (newTdAccion.find('.dropdown-menu').hasClass('show')) return false;
                    return animateProperty(newTdAccion, 'right', -43, -75, 150, 60, () => {
                        newTdAccion.removeClass('active-acciones sticky-activo').removeAttr('style');
                    });
                }
                // Se añaden cuando el scroll esta a unos pixeles menos del final 
                if (getScrollTdAccion(newTdAccion)) return;
                newTdAccion.addClass('active-acciones sticky-activo'); //.css('background-color', getBgColorRow(filaAccionActivo));
                animateProperty(newTdAccion, 'right', -75, -43, 150, 60);
            });

            if (!esCelular()) {
                $(this).off('mouseleave').on('mouseleave', function () {
                    const newTdAccion = filaAccionActivo.find(".td-acciones");
                    if (!newTdAccion.find('.dropdown-menu').hasClass('show')) { //  && !openOnCkick
                        animateProperty(newTdAccion, 'right', -43, -75, 150, 60, () => {
                            newTdAccion.removeClass('active-acciones sticky-activo').removeAttr('style');
                        });
                    }
                });

                $(this).off('click').on('click', function () {
                    filaAccionActivo = $(this);
                    const newTdAccion = filaAccionActivo.find(".td-acciones");

                    if (filaAccionActivo.is(filaAccionOld)) return;

                    if (!getScrollTdAccion(newTdAccion) && !newTdAccion.hasClass('active-acciones')) {
                        newTdAccion.addClass('active-acciones sticky-activo'); //.css('background-color', getBgColorRow(filaAccionActivo));
                        animateProperty(newTdAccion, 'right', -75, -43, 150, 60);
                        // openOnCkick = true;
                    }

                    if (filaAccionOld?.length) {
                        const oldTdAccion = filaAccionOld.find(".td-acciones");
                        animateProperty(oldTdAccion, 'right', -43, -75, 150, 60, () => {
                            oldTdAccion.removeClass('active-acciones sticky-activo').removeAttr('style');
                        });
                        filaAccionOld = null;
                    }
                    // setTimeout(() => openOnCkick = false, 165);
                });
            }
        });
        paginaActual = nuevaPagina; // Actualizar la página actual
    });

    // Evento de scroll para actualizar la clase sticky-activo
    dataTables_scrollBody.on('scroll', function () {
        try {
            if (!filaAccionActivo?.length) return;

            let filaActiva = filaAccionOld?.length ? filaAccionOld : filaAccionActivo;
            const accionTd = filaActiva.find('.td-acciones');
            if (getScrollTdAccion(accionTd)) {
                return accionTd.removeClass('active-acciones sticky-activo').removeAttr('style');
            }
            accionTd.addClass("active-acciones sticky-activo").css({ 'right': '-43px' }); // , 'background-color': getBgColorRow(filaActiva)
        } catch (error) {
            console.log(error);
        }
    });

    dataTables_scrollBody.on('blur', function () {
        console.log('salió');
    });
}

function parseColor(color) {
    if (!color) return;
    let colores = {
        primary: '#3b71ca',
        secondary: '#7e7979',
        success: '#14a44d',
        danger: '#dc4c64',
        warning: '#e4a11b',
        info: '#54b4d3',
        light: '#fbfbfb',
        dark: '#332d2d',
    }

    if (colores.hasOwnProperty(color)) {
        color = colores[color];
    }
    const rgbRegex = /^rgb\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})\s*\)$/i;
    const hexRegex = /^#([a-f\d]{3}|[a-f\d]{6})$/i;

    // Si es RGB
    const rgbMatch = color.match(rgbRegex);
    if (rgbMatch) {
        return color;
    }

    // Si es HEX
    const hexMatch = color.match(hexRegex);
    if (hexMatch) {
        let hex = hexMatch[1];
        if (hex.length === 3) { hex = hex.split('').map(c => c + c).join(''); }
        const r = parseInt(hex.slice(0, 2), 16);
        const g = parseInt(hex.slice(2, 4), 16);
        const b = parseInt(hex.slice(4, 6), 16);
        return `rgb(${r}, ${g}, ${b})`;
    }

    throw new Error('Formato de color no reconocido');
}

function obtenerFechaFormateada(fecha = new Date(), short = false) {
    const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    const mesesAbreviados = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];


    // Convertir a zona horaria de Lima (Perú)
    // Si la fecha original no tiene hora, usamos mediodía para evitar problemas de zona horaria
    const fechaConHora = new Date(fecha);
    if (fechaConHora.getHours() === 0 && fechaConHora.getMinutes() === 0 && fechaConHora.getSeconds() === 0) {
        fechaConHora.setHours(12);
    }

    const fechaLima = new Date(fechaConHora.toLocaleString('sv-SE', { timeZone: 'America/Lima' }));

    const nombreDia = dias[fechaLima.getDay()];
    const numeroDia = fechaLima.getDate();
    const nombreMes = short ? mesesAbreviados[fechaLima.getMonth()] : meses[fechaLima.getMonth()];
    const año = fechaLima.getFullYear();

    return short ? `${nombreMes}. ${numeroDia}, ${año}` : `${nombreDia} ${numeroDia} de ${nombreMes} del ${año}`;
}

function fObservador(selector, callback) {
    if (typeof selector !== 'string') return null;

    let contenedor = null;
    if (selector.startsWith('.')) {
        contenedor = document.querySelector(selector);
    } else if (selector.startsWith('#')) {
        contenedor = document.getElementById(selector);
    } else {
        return null;
    }

    const observer = new ResizeObserver(entries => {
        if (typeof callback === "function") callback();
    });
    observer.observe(contenedor);
}

function cargarIframeDocumento(url) {
    $('#modal_pdf').modal('show');
    let contenedor = $('#modal_pdf .modal-body');
    contenedor.prepend('<div class="loader-of-modal"><div style="display:flex; justify-content:center;"><div class="loader"></div></div></div>');
    $('#contenedor_doc').addClass('d-none').attr('src', url).off('load').on('load', function () {
        $(this).removeClass('d-none');
        contenedor.find('.loader-of-modal').remove();
    });
    const observer = new ResizeObserver(entries => {
        for (let entry of entries) {
            $('#contenedor_doc').height(entry.contentRect.height - 10);
        }
    });
    observer.observe(contenedor.get(0));
    $('#modal_pdf').on('hidden.bs.modal', function () {
        observer.unobserve(contenedor.get(0));
    });
}

function generateUrl(baseUrl, params) {
    const url = new URL(baseUrl);
    Object.keys(params).forEach(key => params[key] ? url.searchParams.set(key, params[key]) : null);
    return url.toString();
}

function esCelular() {
    return (
        /android|iphone|ipod|ipad|mobile/i.test(navigator.userAgent.toLowerCase()) ||
        navigator.maxTouchPoints > 1
    );
}

// Codificar HTML con emojis Binario y después a Base64
function utf8ToBase64(str) {
    try {
        // 1. Convertimos el string a bytes (UTF-8)
        const bytes = new TextEncoder().encode(str);
        // 2. Creamos un string binario desde esos bytes
        let binary = '';
        bytes.forEach(b => binary += String.fromCharCode(b));
        // 3. Codificamos el string binario a Base64
        return btoa(binary);
    } catch {
        alert('Error al codificar el contenido.');
        return false;
    }
}


// Decodificar Base64 a Binario a HTML con emojis
function base64ToUtf8(base64) {
    try {
        // 1. Decodificamos Base64 a un string binario
        const binary = atob(base64);
        // 2. Lo convertimos de binario a bytes
        const bytes = Uint8Array.from(binary, c => c.charCodeAt(0));
        // 3. Lo decodificamos de bytes a string UTF-8
        return new TextDecoder().decode(bytes);
    } catch {
        alert('Error al decodificar el contenido.');
        return '<em class="text-danger">Error al decodificar el contenido.</em>';
    }
}

function colores(c) {
    const coloresPorLetra = {
        A: "#E74C3C", // rojo
        B: "#8E44AD", // púrpura
        C: "#3498DB", // azul
        D: "#1ABC9C", // turquesa
        E: "#27AE60", // verde
        F: "#F1C40F", // amarillo
        G: "#E67E22", // naranja
        H: "#E84393", // rosado
        I: "#2ECC71", // verde claro
        J: "#16A085", // verde azulado
        K: "#2980B9", // azul intenso
        L: "#9B59B6", // violeta
        M: "#34495E", // gris azulado
        N: "#D35400", // naranja oscuro
        Ñ: "#BB8FCE", // violeta suave
        O: "#C0392B", // rojo oscuro
        P: "#7D3C98", // violeta profundo
        Q: "#2471A3", // azul medio
        R: "#1F618D", // azul marino
        S: "#17A589", // verde marino
        T: "#229954", // verde selva
        U: "#F39C12", // ámbar
        V: "#BA4A00", // marrón
        W: "#7E5109", // marrón oscuro
        X: "#616A6B", // gris medio
        Y: "#2E4053", // gris azulado oscuro
        Z: "#3f7175ff"  // gris claro
    };
    return coloresPorLetra[c[0].toUpperCase()] || '#3b71ca';
}

/*async function pedirPermisoNotificaciones() {
    const permiso = await Notification.requestPermission();
    if (permiso === "granted") {
        console.log("Permiso otorgado");
    } else {
        console.log("Permiso denegado");
    }
}

// Llama a esta función, por ejemplo, cuando el usuario hace login
pedirPermisoNotificaciones();*/