$(document).ready(function () {
    const config = {
        "select": {
            placeholder: 'Seleccione...',
            allowClear: false,
        },
        "search": {
            search: true,
            placeholder: 'Seleccione...',
            searchPlaceholder: 'Buscar',
            allowClear: false,
            backdrop: true
        },
        "clear": {
            search: true,
            placeholder: 'Seleccione...',
            searchPlaceholder: 'Buscar',
            allowClear: true,
            backdrop: true
        },
        "clear_simple": {
            placeholder: 'Seleccione...',
            allowClear: true,
        },
        "tags": {
            search: true,
            searchPlaceholder: 'Ingresar... 999999999',
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
            placeholder: 'Seleccione...',
            allowClear: true,
        }
    };

    // Función para inicializar select2
    function initializeSelect2(selectElement, config, modal) {
        selectElement.customSelect2({
            ...config,
            // matcher: matchCustom,
            templateResult: function (data) {
                if ($(data.element).data('hidden')) {
                    return null;
                }
                return data.text;
            },
            templateSelection: function (data) {
                return data.text;
            }
        });
    }

    // Inicialización por defecto
    $('.select').each(function () {
        initializeSelect2($(this), config.select);
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
    $('.select-clear-simple').each(function () {
        initializeSelect2($(this), config.clear_simple);
    });

    // Allow tags selection
    $('.select-tags').each(function () {
        initializeSelect2($(this), config.tags);
    });

    // Allow icons selection
    $('.select-icons').each(function () {
        initializeSelect2($(this), config.icons);
    });

    $('.select-tags').on('customSelect2:open', async function () {
        let searchField = document.querySelector('.custom-select2-search__field');
        if (searchField) {
            searchField.setAttribute('maxlength', 9);
            searchField.setAttribute('minlength', 9);
            searchField.setAttribute('pattern', '^9[0-9]{8}$');

            if (!searchField.value) {
                searchField.value = '9';
            }

            searchField.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
                if (this.value.length === 0) {
                    this.value = '9';
                    return;
                }

                if (this.value[0] !== '9') {
                    this.value = '9' + this.value.substring(1);
                }

                if (this.value.length > 9) {
                    this.value = this.value.substring(0, 9);
                }
            });

            searchField.focus();
        }
    });
});