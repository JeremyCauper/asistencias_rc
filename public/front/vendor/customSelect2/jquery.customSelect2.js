/**
 * CustomSelect2 - Réplica funcional de Select2 en jQuery
 * @version 1.0.1
 * @author Claude
 * @license MIT
 */
; (function ($, window, document, undefined) {
    'use strict';

    // Configuración por defecto
    var defaults = {
        search: false,
        placeholder: 'Seleccionar...',
        searchPlaceholder: 'Buscar...',
        allowClear: false,
        minimumResultsForSearch: 10,
        openDelay: 0,
        closeDelay: 0,
        debounceDelay: 10,
        width: '100%',
        dropdownParent: null,
        ajax: null,
        data: null,
        templateResult: null,
        templateSelection: null,
        matcher: null,
        language: {
            noResults: 'No se encontraron resultados',
            searching: 'Buscando...',
            loadingMore: 'Cargando más resultados...',
            errorLoading: 'Error al cargar resultados',
            inputTooShort: 'Escribe para buscar',
            createTag: 'Presiona Enter para crear: '
        },
        theme: 'default',
        disabled: false,
        multiple: false,
        tags: false,
        createTag: null,
        maximumSelectionLength: 0,
        closeOnSelect: true,
        backdrop: true,
        fullscreenOnMobile: true,
        positionStrategy: 'auto'
    };

    // Constructor del plugin
    function CustomSelect2(element, options) {
        this.$element = $(element);
        this.options = $.extend(true, {}, defaults, options);
        this.id = this._generateId();
        this.isOpen = false;
        this.isSearchMode = false;
        this.searchTimeout = null;
        this.ajaxRequest = null;
        this.$container = null;
        this.$selection = null;
        this.$dropdown = null;
        this.$search = null;
        this.$results = null;
        this.$backdrop = null;
        this.results = [];
        this.filteredResults = [];
        this.selectedValue = null;
        this.focusedIndex = -1;
        this.isMobile = this._isMobileDevice();

        this._init();
    }

    CustomSelect2.prototype = {
        constructor: CustomSelect2,

        _init: function () {
            if (this.$element.data('customSelect2')) {
                return;
            }

            // Verificar si está oculto por data-hidden
            var dataHidden = this.$element.data('hidden');
            if (dataHidden === true || dataHidden === 'true') {
                return; // No inicializar si está oculto
            }

            this.isSearchMode = this._shouldUseSearchMode();

            // Sincronizar estado disabled inicial
            if (this.$element.prop('disabled')) {
                this.options.disabled = true;
            }

            this.$element.hide();
            this._createContainer();
            this._createSelection();
            this._loadInitialData();
            this._bindEvents();
            this.$element.data('customSelect2', this);
            this._trigger('init');
        },

        _shouldUseSearchMode: function () {
            // Verificar data-nosearch (desactivar búsqueda forzosamente)
            var dataNoSearch = this.$element.data('nosearch');
            if (dataNoSearch === true || dataNoSearch === 'true') {
                return false;
            }

            // Verificar atributo data
            var dataSearch = this.$element.data('search');
            if (dataSearch !== undefined) {
                return dataSearch === true || dataSearch === 'true';
            }

            // Verificar opción
            if (this.options.search === true) {
                return true;
            }

            // Auto-detectar por cantidad de opciones
            var optionCount = this.$element.find('option').length;
            return optionCount >= this.options.minimumResultsForSearch;
        },

        _createContainer: function () {
            var containerClass = 'custom-select2-container' +
                (this.isSearchMode ? ' custom-select2-container--search' : '') +
                (this.options.theme !== 'default' ? ' custom-select2-container--' + this.options.theme : '');

            this.$container = $('<div>', {
                'class': containerClass,
                'id': this.id
            });

            if (this.options.width) {
                this.$container.css('width', this.options.width);
            }

            this.$element.after(this.$container);
        },

        _createSelection: function () {
            this.$selection = $('<div>', {
                'class': 'custom-select2-selection' + (this.options.disabled ? ' custom-select2-selection--disabled' : ''),
                'role': 'combobox',
                'aria-haspopup': 'listbox',
                'aria-expanded': 'false',
                'tabindex': '0'
            });

            var $rendered = $('<span>', {
                'class': 'custom-select2-selection__rendered'
            });

            var selectedOption = this.$element.find('option:selected');
            var text = selectedOption.length ? selectedOption.text() : this.options.placeholder;

            $rendered.append(text);

            if (!selectedOption.length || !selectedOption.val()) {
                $rendered.addClass('custom-select2-selection__placeholder');
            }

            this.$selection.append($rendered);

            var $arrow = $('<span>', {
                'class': 'custom-select2-selection__arrow',
                'role': 'presentation'
            }).html('<b role="presentation"></b>');

            this.$selection.append($arrow);

            if (this.options.allowClear) {
                var $clear = $('<span>', {
                    'class': 'custom-select2-selection__clear',
                    'role': 'button',
                    'aria-label': 'Limpiar selección'
                }).html('×');
                this.$selection.append($clear);
            }

            this.$container.append(this.$selection);
        },

        _createDropdown: function () {
            if (this.$dropdown) {
                return;
            }

            var dropdownClass = 'custom-select2-dropdown' +
                (this.isSearchMode ? ' custom-select2-dropdown--search' : '');

            this.$dropdown = $('<div>', {
                'class': dropdownClass,
                'role': 'listbox'
            });

            if (this.isSearchMode) {
                this._createSearchInput();
            }

            this.$results = $('<div>', {
                'class': 'custom-select2-results'
            });

            this.$dropdown.append(this.$results);

            var $parent = this._getDropdownParent();
            $parent.append(this.$dropdown);

            if (this.isSearchMode && this.options.backdrop) {
                this._createBackdrop();
            }
        },

        _createSearchInput: function () {
            var $searchContainer = $('<div>', {
                'class': 'custom-select2-search'
            });

            this.$search = $('<input>', {
                'type': 'text',
                'class': 'custom-select2-search__field',
                'placeholder': this.options.searchPlaceholder,
                'autocomplete': 'off',
                'autocorrect': 'off',
                'autocapitalize': 'off',
                'spellcheck': 'false',
                'role': 'searchbox'
            });

            $searchContainer.append(this.$search);
            this.$dropdown.prepend($searchContainer);
        },

        _createBackdrop: function () {
            this.$backdrop = $('<div>', {
                'class': 'custom-select2-backdrop'
            });

            var $parent = this._getDropdownParent();
            $parent.append(this.$backdrop);
        },

        _getDropdownParent: function () {
            if (this.options.dropdownParent) {
                return $(this.options.dropdownParent);
            }

            var $modal = this.$element.closest('.modal, [role="dialog"]');
            if ($modal.length) {
                return $modal;
            }

            return $('body');
        },

        _loadInitialData: function () {
            if (this.options.data && Array.isArray(this.options.data)) {
                this.results = this.options.data;
                this.filteredResults = this.results.slice();
            } else {
                this._loadFromSelect();
            }
        },

        _loadFromSelect: function () {
            var self = this;
            this.results = [];

            this.$element.find('option').each(function () {
                var $option = $(this);

                // Verificar si la opción está oculta
                var isHidden = $option.data('hidden');
                if (isHidden === true || isHidden === 'true') {
                    return; // Skip esta opción
                }

                // Verificar si la opción no debe aparecer en búsqueda
                var noSearch = $option.data('nosearch');

                self.results.push({
                    id: $option.val(),
                    text: $option.text(),
                    html: $option.html(), // Guardar HTML completo
                    disabled: $option.prop('disabled'),
                    nosearch: noSearch === true || noSearch === 'true',
                    element: this
                });
            });

            this.filteredResults = this.results.slice();
        },

        _bindEvents: function () {
            var self = this;

            // Observer para cambios en atributos (disabled)
            if (window.MutationObserver) {
                this.observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        if (mutation.attributeName === 'disabled') {
                            self.enable(!self.$element.prop('disabled'));
                        }
                    });
                });

                this.observer.observe(this.$element[0], {
                    attributes: true,
                    attributeFilter: ['disabled']
                });
            }

            this.$selection.on('click.customSelect2', function (e) {
                if ($(e.target).hasClass('custom-select2-selection__clear')) {
                    self._handleClear(e);
                } else {
                    self.toggle();
                }
            });

            this.$selection.on('keydown.customSelect2', function (e) {
                self._handleSelectionKeydown(e);
            });

            $(document).on('click.customSelect2.' + this.id, function (e) {
                if (!self.$container.is(e.target) &&
                    self.$container.has(e.target).length === 0 &&
                    (!self.$dropdown || !self.$dropdown.is(e.target)) &&
                    (!self.$dropdown || self.$dropdown.has(e.target).length === 0) &&
                    (!self.$backdrop || !self.$backdrop.is(e.target))) {
                    self.close();
                }
            });

            this.$element.on('change.customSelect2', function () {
                self._updateSelection();
            });
        },

        _bindDropdownEvents: function () {
            var self = this;

            if (this.$search) {
                this.$search.on('input.customSelect2', function () {
                    self._handleSearch();
                });

                this.$search.on('keydown.customSelect2', function (e) {
                    self._handleSearchKeydown(e);
                });

                this.$search.on('keydown.customSelect2 keyup.customSelect2', function (e) {
                    e.stopPropagation();
                });
            }

            this.$results.on('click.customSelect2', '.custom-select2-results__option', function (e) {
                e.preventDefault();
                e.stopPropagation();

                if (!$(this).hasClass('custom-select2-results__option--disabled')) {
                    var index = $(this).data('index');
                    self._selectResult(index);
                }
            });

            this.$results.on('mouseenter.customSelect2', '.custom-select2-results__option', function () {
                if (!$(this).hasClass('custom-select2-results__option--disabled')) {
                    self._setFocusedResult($(this).data('index'));
                }
            });

            if (this.$backdrop) {
                this.$backdrop.on('click.customSelect2', function () {
                    self.close();
                });
            }

            // Agregar listener de scroll para dropdowns normales (no de búsqueda)
            if (!this.isSearchMode) {
                this._bindScrollEvents();
            }
        },

        _bindScrollEvents: function () {
            var self = this;

            // Listener para scroll en window
            $(window).on('scroll.customSelect2.' + this.id, function () {
                if (self.isOpen && !self.isSearchMode) {
                    self._updateDropdownPosition();
                }
            });

            // Listener para scroll en contenedores scrolleables
            // Buscar todos los padres scrolleables
            this.$element.parents().each(function () {
                var $parent = $(this);
                // Verificar si el elemento tiene scroll
                if ($parent.css('overflow') === 'auto' ||
                    $parent.css('overflow') === 'scroll' ||
                    $parent.css('overflow-y') === 'auto' ||
                    $parent.css('overflow-y') === 'scroll') {

                    $parent.on('scroll.customSelect2.' + self.id, function () {
                        if (self.isOpen && !self.isSearchMode) {
                            self._updateDropdownPosition();
                        }
                    });
                }
            });

            // Listener para resize de window
            $(window).on('resize.customSelect2.' + this.id, function () {
                if (self.isOpen && !self.isSearchMode) {
                    self._updateDropdownPosition();
                }
            });
        },

        _unbindScrollEvents: function () {
            // Limpiar todos los listeners de scroll y resize
            $(window).off('scroll.customSelect2.' + this.id);
            $(window).off('resize.customSelect2.' + this.id);
            this.$element.parents().off('scroll.customSelect2.' + this.id);
        },

        open: function () {
            var self = this;

            if (this.isOpen || this.options.disabled || this.$element.prop('disabled')) {
                return;
            }

            this._trigger('beforeOpen');

            setTimeout(function () {
                self._createDropdown();

                // Posicionar ANTES de mostrar para evitar saltos
                self._positionDropdown();

                // Renderizar contenido
                self._renderResults();
                self._bindDropdownEvents();

                // Forzar reflow para asegurar que el posicionamiento se aplique
                self.$dropdown[0].offsetHeight;

                // Ahora sí, mostrar con animación
                self.$dropdown.addClass('custom-select2-dropdown--open');

                if (self.$backdrop) {
                    self.$backdrop.addClass('custom-select2-backdrop--open');
                }

                self.$selection.attr('aria-expanded', 'true');
                self.isOpen = true;

                if (self.$search) {
                    setTimeout(function () {
                        self.$search.focus();
                    }, 50);
                }

                self._trigger('open');
            }, this.options.openDelay);
        },

        _updateDropdownPosition: function () {
            if (!this.$dropdown || this.isSearchMode) {
                return;
            }

            // Verificar si el select sigue visible en el viewport
            var selectRect = this.$selection[0].getBoundingClientRect();
            var windowHeight = $(window).height();

            // Si el select se salió completamente del viewport, cerrar el dropdown
            if (selectRect.bottom < 0 || selectRect.top > windowHeight) {
                this.close();
                return;
            }

            // Recalcular posición
            this._positionNormalDropdown();
        },

        close: function () {
            var self = this;

            if (!this.isOpen) {
                return;
            }

            this._trigger('beforeClose');

            // Limpiar listeners de scroll
            this._unbindScrollEvents();

            setTimeout(function () {
                if (self.$dropdown) {
                    self.$dropdown.removeClass('custom-select2-dropdown--open');
                }

                if (self.$backdrop) {
                    self.$backdrop.removeClass('custom-select2-backdrop--open');
                }

                self.$selection.attr('aria-expanded', 'false');
                self.$selection.focus();
                self.isOpen = false;

                if (self.$search) {
                    self.$search.val('');
                    self.filteredResults = self.results.slice();
                }

                self.focusedIndex = -1;

                self._trigger('close');

                setTimeout(function () {
                    if (self.$dropdown) {
                        self.$dropdown.remove();
                        self.$dropdown = null;
                    }
                    if (self.$backdrop) {
                        self.$backdrop.remove();
                        self.$backdrop = null;
                    }
                }, 300);
            }, this.options.closeDelay);
        },

        toggle: function () {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        },

        _positionDropdown: function () {
            if (this.isSearchMode) {
                this._positionSearchDropdown();
            } else {
                this._positionNormalDropdown();
            }
        },

        _positionNormalDropdown: function () {
            var $parent = this._getDropdownParent();
            var isInModal = $parent.hasClass('modal') || $parent.closest('.modal').length > 0;

            if (isInModal) {
                // Posicionamiento relativo al modal
                var $actualModal = $parent.hasClass('modal') ? $parent : $parent.closest('.modal');
                var modalOffset = $actualModal.offset();
                var selectionOffset = this.$selection.offset();

                var height = this.$selection.outerHeight();
                var width = this.$selection.outerWidth();

                // Calcular posición relativa al modal
                var topPosition = selectionOffset.top - modalOffset.top + height;
                var leftPosition = selectionOffset.left - modalOffset.left;

                this.$dropdown.css({
                    position: 'absolute',
                    top: topPosition + 'px',
                    left: leftPosition + 'px',
                    width: width + 'px'
                });

                // Verificar si cabe abajo o debe ir arriba
                var dropdownHeight = this.$dropdown.outerHeight();
                var modalBodyHeight = $actualModal.find('.modal-body').height() || $actualModal.height();
                var spaceBelow = modalBodyHeight - topPosition;

                if (spaceBelow < dropdownHeight && topPosition > dropdownHeight) {
                    // Mostrar arriba
                    this.$dropdown.css('top', (topPosition - height - dropdownHeight) + 'px');
                    this.$dropdown.addClass('custom-select2-dropdown--above');
                }
            } else {
                // Posicionamiento normal (fuera de modal)
                var offset = this.$selection.offset();
                var height = this.$selection.outerHeight();
                var width = this.$selection.outerWidth();

                this.$dropdown.css({
                    position: 'absolute',
                    top: offset.top + height,
                    left: offset.left,
                    width: width
                });

                var dropdownHeight = this.$dropdown.outerHeight();
                var windowHeight = $(window).height();
                var scrollTop = $(window).scrollTop();

                if (offset.top + height + dropdownHeight > windowHeight + scrollTop) {
                    this.$dropdown.css('top', offset.top - dropdownHeight);
                    this.$dropdown.addClass('custom-select2-dropdown--above');
                }
            }
        },

        _positionSearchDropdown: function () {
            if (this.isMobile && this.options.fullscreenOnMobile) {
                this.$dropdown.addClass('custom-select2-dropdown--fullscreen');
            }
            // El CSS ya posiciona el dropdown centrado, no necesitamos JS adicional
        },

        _renderResults: function () {
            var self = this;
            var $list = $('<ul>', {
                'class': 'custom-select2-results__options',
                'role': 'listbox'
            });

            if (this.filteredResults.length === 0) {
                var $noResults = $('<li>', {
                    'class': 'custom-select2-results__option custom-select2-results__message'
                }).text(this.options.language.noResults);
                $list.append($noResults);
            } else {
                $.each(this.filteredResults, function (index, item) {
                    var $option = self._renderResult(item, index);
                    $list.append($option);
                });
            }

            this.$results.empty().append($list);

            var selectedValue = this.$element.val();
            if (selectedValue) {
                var selectedIndex = this._findResultIndex(selectedValue);
                if (selectedIndex >= 0) {
                    this._setFocusedResult(selectedIndex);
                }
            }
        },

        _renderResult: function (item, index) {
            var isSelected = this.$element.val() == item.id;
            var isDisabled = item.disabled;
            var isNewTag = item.newTag || item.newOption;

            var $option = $('<li>', {
                'class': 'custom-select2-results__option' +
                    (isSelected ? ' custom-select2-results__option--selected' : '') +
                    (isDisabled ? ' custom-select2-results__option--disabled' : '') +
                    (isNewTag ? ' custom-select2-results__option--new-tag' : ''),
                'role': 'option',
                'data-index': index,
                'aria-selected': isSelected
            });

            var content = item.html || item.text;

            // Decodificar HTML si viene escapado de PHP
            if (content && content.indexOf('&lt;') !== -1) {
                content = this._decodeHTML(content);
            }

            // Si es un nuevo tag, agregar indicador visual
            if (isNewTag && this.options.language.createTag) {
                content = this.options.language.createTag + '"' + item.text + '"';
            }

            // Aplicar highlight solo si no es un nuevo tag y hay búsqueda
            if (this.$search && this.$search.val() && !isNewTag) {
                // Si tiene HTML, hacer highlight solo en el texto
                if (content && content !== item.text && content.indexOf('<') !== -1) {
                    // Mantener el HTML original con highlight
                    content = this._highlightSearchInHTML(content, this.$search.val());
                } else {
                    content = this._highlightSearch(item.text, this.$search.val());
                }
            }

            if (this.options.templateResult && typeof this.options.templateResult === 'function') {
                var rendered = this.options.templateResult(item);
                $option.html(rendered);
            } else {
                $option.html(content);
            }

            return $option;
        },

        _highlightSearch: function (text, search) {
            var regex = new RegExp('(' + this._escapeRegex(search) + ')', 'gi');
            return text.replace(regex, '<mark>$1</mark>');
        },

        _highlightSearchInHTML: function (html, search) {
            // Crear un elemento temporal para manipular el HTML
            var $temp = $('<div>').html(html);
            var regex = new RegExp('(' + this._escapeRegex(search) + ')', 'gi');

            // Función recursiva para hacer highlight solo en nodos de texto
            var highlightTextNodes = function ($element) {
                $element.contents().each(function () {
                    if (this.nodeType === 3) { // Nodo de texto
                        var text = $(this).text();
                        if (regex.test(text)) {
                            var highlighted = text.replace(regex, '<mark>$1</mark>');
                            $(this).replaceWith(highlighted);
                        }
                    } else if (this.nodeType === 1) { // Nodo elemento
                        highlightTextNodes($(this));
                    }
                });
            };

            highlightTextNodes($temp);
            return $temp.html();
        },

        _escapeRegex: function (text) {
            return text.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
        },

        _decodeHTML: function (html) {
            // Crear un textarea temporal para decodificar entidades HTML
            var txt = document.createElement('textarea');
            txt.innerHTML = html;
            return txt.value;
        },

        _handleSearch: function () {
            var self = this;
            var query = this.$search.val();

            clearTimeout(this.searchTimeout);

            this.searchTimeout = setTimeout(function () {
                self._performSearch(query);
            }, this.options.debounceDelay);
        },

        _performSearch: function (query) {
            if (this.options.ajax) {
                this._performAjaxSearch(query);
            } else {
                this._performLocalSearch(query);
            }
        },

        _performLocalSearch: function (query) {
            var self = this;

            if (!query) {
                this.filteredResults = this.results.slice();
            } else {
                var matcher = this.options.matcher || this._defaultMatcher;
                this.filteredResults = this.results.filter(function (item) {
                    return matcher.call(self, query, item);
                });

                // Si tags está habilitado, permitir crear
                if (this.options.tags && query.trim()) {
                    // Crear tag usando createTag si existe, o crear uno por defecto
                    var newTag;
                    if (this.options.createTag && typeof this.options.createTag === 'function') {
                        newTag = this.options.createTag({ term: query });
                    } else {
                        // Tag por defecto
                        newTag = {
                            id: query,
                            text: query,
                            newTag: true
                        };
                    }

                    // Agregar al inicio de los resultados filtrados
                    if (newTag) {
                        this.filteredResults.unshift(newTag);
                    }
                }
            }

            this._renderResults();
        },

        _defaultMatcher: function (query, item) {
            // Si la opción tiene nosearch=true, no debe aparecer en búsqueda
            if (item.nosearch) {
                return false;
            }

            var text = item.text.toLowerCase();
            var search = query.toLowerCase();
            return text.indexOf(search) >= 0;
        },

        _performAjaxSearch: function (query) {
            var self = this;

            if (this.ajaxRequest) {
                this.ajaxRequest.abort();
            }

            this._showLoading();

            var ajaxOptions = typeof this.options.ajax === 'function' ?
                this.options.ajax(query) : this.options.ajax;

            // Procesar datos si hay función
            if (ajaxOptions.data && typeof ajaxOptions.data === 'function') {
                ajaxOptions.data = ajaxOptions.data({ term: query });
            }

            // Guardar processResults si existe
            var processResults = ajaxOptions.processResults;
            delete ajaxOptions.processResults;

            this.ajaxRequest = $.ajax(ajaxOptions)
                .done(function (data) {
                    if (processResults && typeof processResults === 'function') {
                        var processed = processResults(data);
                        self.filteredResults = processed.results || [];
                    } else {
                        self.filteredResults = data.results || data;
                    }
                    self._renderResults();
                })
                .fail(function (xhr) {
                    if (xhr.statusText !== 'abort') {
                        self._showError();
                    }
                })
                .always(function () {
                    self.ajaxRequest = null;
                });
        },

        _showLoading: function () {
            var $loading = $('<li>', {
                'class': 'custom-select2-results__option custom-select2-results__message'
            }).text(this.options.language.searching);

            this.$results.empty().append($('<ul>').append($loading));
        },

        _showError: function () {
            var $error = $('<li>', {
                'class': 'custom-select2-results__option custom-select2-results__message custom-select2-results__message--error'
            }).text(this.options.language.errorLoading);

            this.$results.empty().append($('<ul>').append($error));
        },

        _selectResult: function (index) {
            if (index < 0 || index >= this.filteredResults.length) {
                return;
            }

            var item = this.filteredResults[index];

            this._trigger('selecting', { item: item });

            // Si es un nuevo tag, crear la opción en el select
            if (item.newTag || item.newOption) {
                var $existingOption = this.$element.find('option[value="' + item.id + '"]');

                if ($existingOption.length === 0) {
                    var $newOption = $('<option>', {
                        value: item.id,
                        selected: true
                    });

                    // Usar HTML si existe, sino usar text
                    if (item.html && item.html !== item.text) {
                        $newOption.html(item.html);
                    } else {
                        $newOption.text(item.text);
                    }

                    // Agregar data attributes si existen
                    if (item.data) {
                        $.each(item.data, function (key, value) {
                            $newOption.attr('data-' + key, value);
                        });
                    }

                    this.$element.append($newOption);

                    // Agregar a la lista de resultados permanentes
                    this.results.push({
                        id: item.id,
                        text: item.text,
                        html: item.html || item.text,
                        element: $newOption[0]
                    });
                }
            }

            this.$element.val(item.id).trigger('change');

            this._updateSelection();

            this._trigger('select', { item: item });

            if (this.options.closeOnSelect) {
                this.close();
            }
        },

        _updateSelection: function () {
            var selectedOption = this.$element.find('option:selected');
            var $rendered = this.$selection.find('.custom-select2-selection__rendered');

            if (selectedOption.length && selectedOption.val()) {
                var text = selectedOption.text();
                var html = selectedOption.html();

                if (this.options.templateSelection && typeof this.options.templateSelection === 'function') {
                    var item = {
                        id: selectedOption.val(),
                        text: text,
                        html: html,
                        element: selectedOption[0]
                    };
                    var rendered = this.options.templateSelection(item);
                    $rendered.html(rendered).removeClass('custom-select2-selection__placeholder');
                } else {
                    // Decodificar entidades HTML si es necesario (viene de PHP como texto)
                    var decodedHTML = this._decodeHTML(html);

                    // Verificar si realmente contiene HTML
                    if (decodedHTML !== text && (decodedHTML.indexOf('<') !== -1 || html.indexOf('&lt;') !== -1)) {
                        $rendered.html(decodedHTML).removeClass('custom-select2-selection__placeholder');
                    } else {
                        $rendered.text(text).removeClass('custom-select2-selection__placeholder');
                    }
                }
            } else {
                $rendered.text(this.options.placeholder).addClass('custom-select2-selection__placeholder');
            }
        },

        _handleClear: function (e) {
            e.preventDefault();
            e.stopPropagation();

            this.$element.val('').trigger('change');
            this._updateSelection();
            this._trigger('clear');
        },

        _handleSelectionKeydown: function (e) {
            switch (e.which) {
                case 13: // Enter
                case 32: // Space
                    e.preventDefault();
                    this.open();
                    break;
                case 27: // Escape
                    this.close();
                    break;
                case 38: // Up
                    e.preventDefault();
                    if (this.isOpen) {
                        this._moveFocus(-1);
                    }
                    break;
                case 40: // Down
                    e.preventDefault();
                    if (this.isOpen) {
                        this._moveFocus(1);
                    } else {
                        this.open();
                    }
                    break;
            }
        },

        _handleSearchKeydown: function (e) {
            switch (e.which) {
                case 13: // Enter
                    e.preventDefault();
                    if (this.focusedIndex >= 0) {
                        this._selectResult(this.focusedIndex);
                    }
                    break;
                case 27: // Escape
                    e.preventDefault();
                    this.close();
                    break;
                case 38: // Up
                    e.preventDefault();
                    this._moveFocus(-1);
                    break;
                case 40: // Down
                    e.preventDefault();
                    this._moveFocus(1);
                    break;
            }
        },

        _moveFocus: function (direction) {
            var newIndex = this.focusedIndex + direction;

            if (newIndex < 0 || newIndex >= this.filteredResults.length) {
                return;
            }

            this._setFocusedResult(newIndex);
        },

        _setFocusedResult: function (index) {
            this.focusedIndex = index;

            this.$results.find('.custom-select2-results__option')
                .removeClass('custom-select2-results__option--highlighted');

            var $focused = this.$results.find('.custom-select2-results__option[data-index="' + index + '"]');
            $focused.addClass('custom-select2-results__option--highlighted');

            this._scrollToResult($focused);
        },

        _scrollToResult: function ($result) {
            if (!$result.length) return;

            var container = this.$results[0];
            var option = $result[0];

            var containerTop = container.scrollTop;
            var containerBottom = containerTop + container.clientHeight;

            var optionTop = option.offsetTop;
            var optionBottom = optionTop + option.offsetHeight;

            if (optionTop < containerTop) {
                container.scrollTop = optionTop;
            } else if (optionBottom > containerBottom) {
                container.scrollTop = optionBottom - container.clientHeight;
            }
        },

        _findResultIndex: function (id) {
            for (var i = 0; i < this.filteredResults.length; i++) {
                if (this.filteredResults[i].id == id) {
                    return i;
                }
            }
            return -1;
        },

        _isMobileDevice: function () {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
        },

        _generateId: function () {
            return 'custom-select2-' + Math.random().toString(36).substr(2, 9);
        },

        _trigger: function (eventName, data) {
            var event = $.Event('customSelect2:' + eventName);
            this.$element.trigger(event, data);
            return event;
        },

        destroy: function () {
            this.close();

            if (this.observer) {
                this.observer.disconnect();
                this.observer = null;
            }

            $(document).off('.customSelect2.' + this.id);

            // Limpiar listeners de scroll
            this._unbindScrollEvents();

            if (this.$selection) {
                this.$selection.off('.customSelect2');
            }

            if (this.$container) {
                this.$container.remove();
            }

            this.$element.show().removeData('customSelect2');
        },

        update: function (data) {
            if (data && Array.isArray(data)) {
                this.results = data;
                this.filteredResults = data.slice();
            } else {
                this._loadFromSelect();
            }

            if (this.isOpen) {
                this._renderResults();
            }
        },

        enable: function (enabled) {
            this.options.disabled = !enabled;
            this.$selection.toggleClass('custom-select2-selection--disabled', !enabled);
        },

        data: function () {
            var value = this.$element.val();
            var text = this.$element.find('option:selected').text();

            return {
                id: value,
                text: text
            };
        }
    };

    // Plugin jQuery
    $.fn.customSelect2 = function (options) {
        var args = Array.prototype.slice.call(arguments, 1);

        return this.each(function () {
            var $element = $(this);
            var instance = $element.data('customSelect2');

            if (typeof options === 'string') {
                if (instance && typeof instance[options] === 'function') {
                    instance[options].apply(instance, args);
                }
            } else {
                if (instance) {
                    instance.destroy();
                }
                new CustomSelect2(this, options);
            }
        });
    };

    // Exponer constructor
    $.fn.customSelect2.Constructor = CustomSelect2;

})(jQuery, window, document);