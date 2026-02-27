$(document).ready(function () {
    const config = {
        "select": {
            placeholder: '-- Seleccione --',
            minimumResultsForSearch: Infinity,
        },
        "multiple": {
            minimumResultsForSearch: Infinity,
        },
        "search": {},
        "clear": {
            placeholder: '-- Seleccione --',
            allowClear: true,
        },
        "clear_nsearch": {
            placeholder: '-- Seleccione --',
            minimumResultsForSearch: Infinity,
            allowClear: true,
        },
        "tags": {
            placeholder: 'Buscar',
            allowClear: true,
            tags: true,
            createTag: function (params) {
                // Creamos un nuevo objeto tag con el value que queramos
                return {
                    id: 'nuevo:' + params.term,          // Este será el value
                    text: params.term,    // Este será el texto mostrado
                    newOption: true       // Lo marcamos como nuevo si queremos estilos
                };
            },
        },
        "icons": {
            placeholder: '-- Seleccione --',
            allowClear: true,
            // templateResult: iconFormat,
            // templateSelection: iconFormat,
        }
    };

    // Format icon
    function iconFormat(icon) {
        var originalOption = icon.element;
        if (!icon.id) { return icon.text; }
        var valor = icon.text.split('::');
        var $icon = `${valor[0]}<span>${valor[1]}</span>`;// '<i class="icon-home8"></i>' + icon.text;

        return $icon;
    }

    // Función para inicializar select2
    function initializeSelect2(selectElement, config, modal) {
        selectElement.select2({
            ...config,
            escapeMarkup: function (m) { return m; },
            dropdownParent: modal || null,
            matcher: matchCustom,
            templateResult: function (data) {
                // Comprueba si la opción tiene un atributo data-hidden en true
                if ($(data.element).data('hidden')) {
                    // Puedes devolver null o una cadena vacía para no mostrar nada
                    return null;
                }
                return data.text;
            },
            templateSelection: function (data) {
                return data.text;
            }
        });
    }

    function matchCustom(params, data) {
        // Si no hay término de búsqueda, retorna el dato para mostrarlo
        if ($.trim(params.term) === '') {
            return data;
        }

        // Excluir la opción si tiene data-nosearch="true"
        if ($(data.element).data('nosearch')) {
            return null;
        }

        // Búsqueda estándar: si el texto de la opción coincide, la retorna
        if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
            return data;
        }

        // No coincide, excluir la opción
        return null;
    }

    // Inicialización por defecto
    $('.select').each(function () {
        initializeSelect2($(this), config.select);
    });

    // Inicialización por defecto multiple
    $('.select-multiple').each(function () {
        initializeSelect2($(this), config.multiple);
    });

    // Inicialización con búsqueda
    $('.select-search').each(function () {
        initializeSelect2($(this), config.search);
    });

    // Allow clear selection
    $('.select-clear').each(function () {
        initializeSelect2($(this), config.clear);
    });

    // Allow clear selection
    $('.select-clear-nsearch').each(function () {
        initializeSelect2($(this), config.clear_nsearch);
    });

    // Allow tags selection
    $('.select-tags').each(function () {
        initializeSelect2($(this), config.tags);
    });

    // Allow icons selection
    $('.select-icons').each(function () {
        initializeSelect2($(this), config.icons);
    });

    // Aplica select2 a todos los selects dentro de los modales al mostrarse
    window.formatSelect = function (mod) {
        $(`#${mod}`).on('shown.bs.modal', function () {
            const modal = $(`#${mod} .modal-content .modal-body`);
            modal.find('select').each(function () {
                if ($(this).hasClass('select')) {
                    initializeSelect2($(this), config.select, modal);
                }
                if ($(this).hasClass('select-multiple')) {
                    initializeSelect2($(this), config.multiple, modal);
                }
                if ($(this).hasClass('select-search')) {
                    initializeSelect2($(this), config.search, modal);
                }
                if ($(this).hasClass('select-clear')) {
                    initializeSelect2($(this), config.clear, modal);
                }
                if ($(this).hasClass('select-tags')) {
                    initializeSelect2($(this), config.tags, modal);
                }
                if ($(this).hasClass('select-icons')) {
                    initializeSelect2($(this), config.icons, modal);
                }
            });
        });
    };

    $('.select-clear, .select-search, .select-tags, .select-icons').on('select2:open', async function () {
        let selector = $(this);
        let observador = new MutationObserver((mutations, obs) => {
            let searchField = document.querySelector('input.select2-search__field');
            if (searchField) {
                if ((selector.attr('class')).includes('select-tags')) {
                    $('.select2-search__field').on('keypress', function (e) {
                        // Permitir: teclas de navegación, backspace (8), delete (46)
                        // (se pueden agregar más códigos si se desea)
                        var allowedKeys = [8, 46];
                        if (allowedKeys.indexOf(e.keyCode) !== -1) {
                            return;
                        }
                        // Si ya se han ingresado 9 dígitos, no permitir más
                        // if (this.value.length >= 9) {
                        //     e.preventDefault();
                        //     return;
                        // }
                        // Validar que el carácter presionado sea un dígito
                        var char = String.fromCharCode(e.which);
                        if (!(/[0-9]/.test(char))) {
                            e.preventDefault();
                        }
                    });
                }
                searchField.setAttribute('minlength', selector.attr('minlength'));
                searchField.setAttribute('maxlength', selector.attr('maxlength'));
                searchField.focus();
            }
        });

        observador.observe(document.body, { childList: true, subtree: true });
        setTimeout(() => {
            observador.disconnect(); // Desconecta el observador
        }, 50);
    });
});