/**
 * CardTable - Sistema de cards configurable similar a DataTables
 * @version 15.0.0
 */
class CardTable {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Contenedor con ID "${containerId}" no encontrado`);
        }

        // Configuración por defecto
        this.options = {
            data: options.data || [],
            ajax: options.ajax || null,
            columns: options.columns || [],
            cardTemplate: options.cardTemplate || null,
            cardWrapper: options.cardWrapper || '<div class="card rounded-7 my-2"><div class="card-body p-3">:content</div></div>',
            perPage: options.perPage || 12,
            scrollY: options.scrollY || null,
            searchable: options.searchable !== false,
            sortable: options.sortable !== false,
            pagination: options.pagination !== false,
            searchPlaceholder: options.searchPlaceholder || 'Buscar...',
            noDataMessage: options.noDataMessage || 'No hay datos disponibles',
            language: {
                search: options.language?.search || '',
                showing: options.language?.showing || 'Mostrando',
                to: options.language?.to || 'a',
                of: options.language?.of || 'de',
                entries: options.language?.entries || 'registros',
                noResults: options.language?.noResults || 'No se encontraron resultados',
                loading: options.language?.loading || ''
            },
            onCardClick: options.onCardClick || null,
            filters: options.filters || [],
            initComplete: options.initComplete || null,
            drawCallback: options.drawCallback || null
        };

        this.filteredData = [];
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.searchTerm = '';
        this.columnSearches = {}; // Búsquedas por campo específico
        this.activeFilters = {};
        this.isLoading = false;
        this.initialOrder = options.order || null; // Orden inicial

        this.init();
    }

    async init() {
        this.container.innerHTML = '';
        this.container.className = 'cardtable-container';

        if (this.options.searchable || this.options.filters.length > 0) {
            this.renderControls();
        }

        // Crear contenedor de cards
        this.cardsContainer = document.createElement('div');
        this.cardsContainer.className = 'cardtable-wrapper px-3';

        if (this.options.scrollY) {
            this.cardsContainer.style.minHeight = this.options.scrollY;
            this.cardsContainer.style.maxHeight = this.options.scrollY;
            this.cardsContainer.style.overflowY = 'auto';
            this.cardsContainer.style.overflowX = 'hidden';
        }

        this.container.appendChild(this.cardsContainer);

        // Crear contenedor de info y paginación
        this.infoContainer = document.createElement('div');
        this.infoContainer.className = 'cardtable-info mx-3';
        this.container.appendChild(this.infoContainer);

        // Cargar datos
        if (this.options.ajax) {
            await this.loadAjaxData();
        } else {
            this.filteredData = [...this.options.data];

            // Aplicar orden inicial si existe
            if (this.initialOrder) {
                const [column, direction] = this.initialOrder;
                this.sortColumn = column;
                this.sortDirection = direction;
                this.filteredData.sort((a, b) => {
                    const aVal = this.getNestedValue(a, column);
                    const bVal = this.getNestedValue(b, column);

                    if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                    if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                    return 0;
                });
            }

            this.renderCards();
        }

        if (this.options.pagination) {
            this.renderPagination();
        }

        if (this.options.initComplete) {
            this.options.initComplete.call(this);
        }
    }

    async loadAjaxData() {
        this.showLoading();
        this.isLoading = true;

        try {
            const ajaxConfig = this.options.ajax;
            const url = typeof ajaxConfig === 'string' ? ajaxConfig : ajaxConfig.url;
            const method = ajaxConfig.type || ajaxConfig.method || 'GET';
            const data = ajaxConfig.data || {};

            const fetchOptions = {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    ...(ajaxConfig.headers || {})
                }
            };

            if (method !== 'GET' && Object.keys(data).length > 0) {
                fetchOptions.body = JSON.stringify(data);
            }

            const response = await fetch(url, fetchOptions);
            const json = await response.json();

            // Procesar dataSrc si existe
            let processedData = json;
            if (ajaxConfig.dataSrc) {
                if (typeof ajaxConfig.dataSrc === 'function') {
                    processedData = ajaxConfig.dataSrc(json);
                } else if (typeof ajaxConfig.dataSrc === 'string') {
                    processedData = this.getNestedValue(json, ajaxConfig.dataSrc);
                }
            }

            this.options.data = Array.isArray(processedData) ? processedData : [];
            this.filteredData = [...this.options.data];

            // Aplicar orden si existe uno configurado
            if (this.sortColumn) {
                // Si ya hay un sortColumn configurado, mantenerlo
                this.filteredData.sort((a, b) => {
                    const aVal = this.getNestedValue(a, this.sortColumn);
                    const bVal = this.getNestedValue(b, this.sortColumn);

                    if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                    if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });
            } else if (this.initialOrder) {
                // Si no hay sortColumn pero hay orden inicial, aplicarlo
                const [column, direction] = this.initialOrder;
                this.sortColumn = column;
                this.sortDirection = direction;
                this.filteredData.sort((a, b) => {
                    const aVal = this.getNestedValue(a, column);
                    const bVal = this.getNestedValue(b, column);

                    if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                    if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                    return 0;
                });
            }

            this.hideLoading();
            this.renderCards();

            if (this.options.pagination) {
                this.renderPagination();
            }

        } catch (error) {
            this.hideLoading();
            console.error('Error al cargar datos:', error);

            if (this.options.ajax.error) {
                this.options.ajax.error(error, 'error', error.message);
            }

            this.showError('Error al cargar los datos');
        } finally {
            this.isLoading = false;
        }
    }

    showLoading() {
        this.cardsContainer.innerHTML = `
      <div class="cardtable-loading text-center py-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">${this.options.language.loading}</span>
        </div>
        <p class="mt-2">${this.options.language.loading}</p>
      </div>
    `;
    }

    hideLoading() {
        const loading = this.cardsContainer.querySelector('.cardtable-loading');
        if (loading) loading.remove();
    }

    showError(message) {
        this.cardsContainer.innerHTML = `
      <div class="alert alert-danger" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>${message}
      </div>
    `;
    }

    renderControls() {
        const controls = document.createElement('div');
        controls.className = 'cardtable-controls mb-2 m-3 row';

        const btnAccion = document.createElement('div');
        btnAccion.className = 'botones-accion text-center';

        const colAccion = document.createElement('div');
        colAccion.className = 'col-12 my-1';

        colAccion.appendChild(btnAccion);
        controls.appendChild(colAccion);

        if (this.options.searchable) {
            const searchCol = document.createElement('div');
            searchCol.className = 'col-12 my-1';

            const searchWrapper = document.createElement('div');
            searchWrapper.className = 'cardtable-search d-flex align-items-center';

            const searchLabel = document.createElement('label');
            searchLabel.className = 'form-label mb-1';
            searchLabel.textContent = this.options.language.search;

            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.className = 'form-control';
            searchInput.placeholder = this.options.searchPlaceholder;
            searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));

            searchWrapper.appendChild(searchLabel);
            searchWrapper.appendChild(searchInput);
            searchCol.appendChild(searchWrapper);
            controls.appendChild(searchCol);
        }

        if (this.options.filters.length > 0) {
            this.options.filters.forEach(filter => {
                const filterCol = document.createElement('div');
                filterCol.className = filter.colClass || 'col-md-3';

                const filterDiv = document.createElement('div');
                filterDiv.className = 'cardtable-filter';

                const label = document.createElement('label');
                label.className = 'form-label mb-1';
                label.textContent = filter.label;

                const select = document.createElement('select');
                select.className = 'form-select';
                select.innerHTML = '<option value="">Todos</option>';

                filter.options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt.value;
                    option.textContent = opt.label;
                    select.appendChild(option);
                });

                select.addEventListener('change', (e) => {
                    this.handleFilter(filter.column, e.target.value);
                });

                filterDiv.appendChild(label);
                filterDiv.appendChild(select);
                filterCol.appendChild(filterDiv);
                controls.appendChild(filterCol);
            });
        }

        this.container.appendChild(controls);
    }

    renderCards() {
        this.cardsContainer.innerHTML = '';

        const start = (this.currentPage - 1) * this.options.perPage;
        const end = start + this.options.perPage;
        const pageData = this.filteredData.slice(start, end);

        if (pageData.length === 0) {
            const noData = document.createElement('div');
            noData.className = 'cardtable-no-data alert alert-info text-center';
            noData.innerHTML = `<i class="fas fa-info-circle me-2"></i>${this.searchTerm || Object.keys(this.columnSearches).length > 0 || Object.keys(this.activeFilters).length > 0
                ? this.options.language.noResults
                : this.options.noDataMessage
                }`;
            this.cardsContainer.appendChild(noData);
            this.updateInfo();
            return;
        }

        pageData.forEach((item, index) => {
            const card = this.createCard(item, start + index);
            this.cardsContainer.appendChild(card);
        });

        this.updateInfo();

        if (this.options.drawCallback) {
            this.options.drawCallback.call(this);
        }
    }

    updateInfo() {
        // Limpiar info container
        this.infoContainer.innerHTML = '';

        // Info de registros
        if (this.filteredData.length > 0) {
            const start = (this.currentPage - 1) * this.options.perPage;
            const end = start + this.options.perPage;
            const showing = Math.min(end, this.filteredData.length);

            const info = document.createElement('div');
            info.className = 'text-muted small mt-3';
            info.textContent = `${this.options.language.showing} ${start + 1} ${this.options.language.to} ${showing} ${this.options.language.of} ${this.filteredData.length} ${this.options.language.entries}`;
            this.infoContainer.appendChild(info);
        }
    }

    createCard(data, index) {
        const cardContainer = document.createElement('div');

        let cardContent = '';
        if (this.options.cardTemplate) {
            cardContent = this.options.cardTemplate(data, index);
        } else {
            cardContent = this.defaultTemplate(data);
        }

        // Insertar contenido en el wrapper
        const finalHtml = this.options.cardWrapper.replace(':content', cardContent);
        cardContainer.innerHTML = finalHtml;

        const cardElement = cardContainer.firstElementChild;
        cardElement.dataset.index = index;

        if (this.options.onCardClick) {
            cardElement.style.cursor = 'pointer';
            cardElement.addEventListener('click', (e) => {
                // Evitar click si se hace click en botones/dropdowns
                if (!e.target.closest('button, a, .dropdown')) {
                    this.options.onCardClick(data, index);
                }
            });
        }

        return cardElement;
    }

    defaultTemplate(data) {
        let html = '<div class="card-content">';
        this.options.columns.forEach(col => {
            const value = this.getNestedValue(data, col.data);
            html += `
        <div class="mb-2">
          <strong>${col.title}:</strong>
          <span>${col.render ? col.render(value, 'display', data) : value}</span>
        </div>
      `;
        });
        html += '</div>';
        return html;
    }

    getNestedValue(obj, path) {
        if (!path) return obj;
        return path.split('.').reduce((acc, part) => acc && acc[part], obj);
    }

    renderPagination() {
        // Buscar o crear contenedor de paginación
        let paginationWrapper = this.infoContainer.querySelector('.cardtable-pagination');
        if (!paginationWrapper) {
            paginationWrapper = document.createElement('div');
            paginationWrapper.className = 'cardtable-pagination mt-3';
            this.infoContainer.appendChild(paginationWrapper);
        }

        paginationWrapper.innerHTML = '';

        const totalPages = Math.ceil(this.filteredData.length / this.options.perPage);

        if (totalPages <= 1) return;

        const pagination = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-center mb-0';

        // Botón anterior
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${this.currentPage === 1 ? 'disabled' : ''}`;
        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.innerHTML = '&laquo;';
        prevLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.currentPage > 1) {
                this.currentPage--;
                this.renderCards();
                this.renderPagination();
            }
        });
        prevLi.appendChild(prevLink);
        ul.appendChild(prevLi);

        // Números de página
        const maxButtons = 5;
        let startPage = Math.max(1, this.currentPage - Math.floor(maxButtons / 2));
        let endPage = Math.min(totalPages, startPage + maxButtons - 1);

        if (endPage - startPage < maxButtons - 1) {
            startPage = Math.max(1, endPage - maxButtons + 1);
        }

        if (startPage > 1) {
            ul.appendChild(this.createPageItem(1));
            if (startPage > 2) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                const ellipsisSpan = document.createElement('span');
                ellipsisSpan.className = 'page-link';
                ellipsisSpan.textContent = '...';
                ellipsisLi.appendChild(ellipsisSpan);
                ul.appendChild(ellipsisLi);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            ul.appendChild(this.createPageItem(i));
        }

        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsisLi = document.createElement('li');
                ellipsisLi.className = 'page-item disabled';
                const ellipsisSpan = document.createElement('span');
                ellipsisSpan.className = 'page-link';
                ellipsisSpan.textContent = '...';
                ellipsisLi.appendChild(ellipsisSpan);
                ul.appendChild(ellipsisLi);
            }
            ul.appendChild(this.createPageItem(totalPages));
        }

        // Botón siguiente
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${this.currentPage === totalPages ? 'disabled' : ''}`;
        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.innerHTML = '&raquo;';
        nextLink.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.currentPage < totalPages) {
                this.currentPage++;
                this.renderCards();
                this.renderPagination();
            }
        });
        nextLi.appendChild(nextLink);
        ul.appendChild(nextLi);

        pagination.appendChild(ul);
        paginationWrapper.appendChild(pagination);
    }

    createPageItem(page) {
        const li = document.createElement('li');
        li.className = `page-item ${page === this.currentPage ? 'active' : ''}`;
        const link = document.createElement('a');
        link.className = 'page-link';
        link.href = '#';
        link.textContent = page;
        link.addEventListener('click', (e) => {
            e.preventDefault();
            this.goToPage(page);
        });
        li.appendChild(link);
        return li;
    }

    goToPage(page) {
        this.currentPage = page;
        this.renderCards();
        this.renderPagination();

        // Scroll to top del contenedor
        if (this.options.scrollY) {
            this.cardsContainer.scrollTop = 0;
        }
    }

    handleSearch(term) {
        this.searchTerm = term.toLowerCase();
        this.applyFilters();
    }

    handleFilter(column, value) {
        if (value === '') {
            delete this.activeFilters[column];
        } else {
            this.activeFilters[column] = value;
        }
        this.applyFilters();
    }

    /**
     * Buscar en campos específicos (similar a column().search() de DataTables)
     * @param {string|array} fields - Campo(s) en los que buscar
     * @param {string} value - Valor a buscar
     * @returns {object} - Retorna el objeto CardTable para encadenar métodos
     */
    search(fields, value = '') {
        // Si no se especifica valor, limpiar búsquedas en esos campos
        if (!value) {
            if (Array.isArray(fields)) {
                fields.forEach(field => delete this.columnSearches[field]);
            } else {
                delete this.columnSearches[fields];
            }
        } else {
            // Agregar búsquedas por campo
            if (Array.isArray(fields)) {
                fields.forEach(field => {
                    this.columnSearches[field] = value.toLowerCase();
                });
            } else {
                this.columnSearches[fields] = value.toLowerCase();
            }
        }

        return this; // Para encadenar con .draw()
    }

    /**
     * Redibujar la tabla (similar a draw() de DataTables)
     */
    draw() {
        this.applyFilters();
    }

    applyFilters() {
        this.filteredData = this.options.data.filter(item => {
            // Búsqueda general de texto en todos los campos
            if (this.searchTerm) {
                const searchTerms = this.searchTerm.split(' ').filter(term => term.length > 0);

                const searchMatch = searchTerms.every(term => {
                    return this.options.columns.some(col => {
                        const value = this.getNestedValue(item, col.data);
                        return String(value).toLowerCase().includes(term);
                    });
                });

                if (!searchMatch) return false;
            }

            // Búsquedas específicas por campo
            for (const [field, searchValue] of Object.entries(this.columnSearches)) {
                const fieldValue = this.getNestedValue(item, field);
                if (!String(fieldValue).toLowerCase().includes(searchValue)) {
                    return false;
                }
            }

            // Filtros específicos
            for (const [column, value] of Object.entries(this.activeFilters)) {
                if (this.getNestedValue(item, column) != value) {
                    return false;
                }
            }

            return true;
        });

        // Aplicar orden si existe
        if (this.sortColumn) {
            this.filteredData.sort((a, b) => {
                const aVal = this.getNestedValue(a, this.sortColumn);
                const bVal = this.getNestedValue(b, this.sortColumn);

                if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                return 0;
            });
        }

        this.currentPage = 1;
        this.renderCards();
        if (this.options.pagination) {
            this.renderPagination();
        }
    }

    sort(column, direction = null) {
        this.sortColumn = column;
        this.sortDirection = direction || (this.sortDirection === 'asc' ? 'desc' : 'asc');

        this.filteredData.sort((a, b) => {
            const aVal = this.getNestedValue(a, column);
            const bVal = this.getNestedValue(b, column);

            if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });

        this.renderCards();
    }

    async reload(resetPaging = true) {
        if (resetPaging) {
            this.currentPage = 1;
        }

        if (this.options.ajax) {
            await this.loadAjaxData();
        } else {
            this.filteredData = [...this.options.data];

            // Aplicar orden si existe
            if (this.sortColumn) {
                this.filteredData.sort((a, b) => {
                    const aVal = this.getNestedValue(a, this.sortColumn);
                    const bVal = this.getNestedValue(b, this.sortColumn);

                    if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                    if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                    return 0;
                });
            } else if (this.initialOrder) {
                const [column, direction] = this.initialOrder;
                this.sortColumn = column;
                this.sortDirection = direction;
                this.filteredData.sort((a, b) => {
                    const aVal = this.getNestedValue(a, column);
                    const bVal = this.getNestedValue(b, column);

                    if (aVal < bVal) return direction === 'asc' ? -1 : 1;
                    if (aVal > bVal) return direction === 'asc' ? 1 : -1;
                    return 0;
                });
            }

            this.renderCards();
            if (this.options.pagination) {
                this.renderPagination();
            }
        }
    }

    /**
     * Objeto ajax para manejar la URL y recargar datos
     */
    get ajax() {
        const self = this;

        const ajaxObject = {
            url: function (newUrl) {
                if (newUrl !== undefined) {
                    // Si ajax es string, convertirlo a objeto
                    if (typeof self.options.ajax === 'string') {
                        self.options.ajax = { url: self.options.ajax };
                    }
                    // Actualizar la URL
                    self.options.ajax.url = newUrl;

                    // Retornar el mismo objeto ajax para encadenar
                    return ajaxObject;
                }
                // Getter: retornar la URL actual
                return typeof self.options.ajax === 'string'
                    ? self.options.ajax
                    : self.options.ajax?.url;
            },
            load: async function () {
                await self.reload(true);
                return self;
            }
        };

        return ajaxObject;
    }

    /**
     * Ordenar por columna y dirección
     * @param {string} column - Campo por el cual ordenar
     * @param {string} direction - 'asc' o 'desc'
     */
    order(column, direction = 'asc') {
        this.sort(column, direction);
        return this;
    }

    destroy() {
        this.container.innerHTML = '';
    }

    // Métodos auxiliares tipo DataTables
    data() {
        return this.filteredData;
    }

    row(index) {
        return {
            data: () => this.filteredData[index],
            node: () => this.cardsContainer.querySelector(`[data-index="${index}"]`)
        };
    }

    rows() {
        return {
            data: () => this.filteredData,
            count: () => this.filteredData.length
        };
    }
}