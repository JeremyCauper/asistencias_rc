class CSelect {
    $select = null;

    // Mapa de operaciones
    $operations = {
        '===': (a, b) => a === b,
        '!==': (a, b) => a !== b,
        '>': (a, b) => a > b,
        '<': (a, b) => a < b,
        '>=': (a, b) => a >= b,
        '<=': (a, b) => a <= b,
    }

    /**
     * @param {Array|Object} dataSet      Dataset a iterar
     * @param {String}        filterField Campo por el que filtrar
     * @param {String}        optionValue Valor de cada <option>
     * @param {String|Function} optionText  Texto de cada <option>
     * @param {Array}         optionValidation
     *     [
     *       { 
     *         clave: 'estatus', 
     *         operation: '===', 
     *         value: 0,
     *         badge: 'Inac.'            // badge opcional para esta regla
     *       },
     *       { 
     *         clave: 'eliminado', 
     *         operation: '===', 
     *         value: 1,
     *         badge: 'Elim.' 
     *       }
     *     ]
     * @param {String|null}   optionSelected  Campo que marca selected (1/0)
     */
    constructor(selector, { dataSet, filterField = null, optionValue = 'id', optionText, optionValidation = [], optionSelected = null, } = {}) {
        this.$select = $(selector[0]);
        this.selector = selector;
        this.dataSet = dataSet;
        this.filterField = filterField;
        this.optionValue = optionValue;
        this.optionText = optionText;
        this.optionValidation = optionValidation;
        this.optionSelected = optionSelected;
    }

    /**
     * Evalúa si un item cumple alguna de las reglas de validación
     * (OR lógico). Devuelve { hidden: Boolean, badge: String }.
     */
    _checkValidation(item) {
        for (const { clave, operation, value, badge = '' } of this.optionValidation) {
            if (clave in item && this.$operations[operation](item[clave], value)) {
                return { hidden: true, badge };
            }
        }
        return { hidden: false, badge: '' };
    }

    selecionar(filterValue = null) {
        const val = typeof filterValue === 'function' ? filterValue() : filterValue;
        this.$select
            .html($('<option>', { value: '', text: '-- Seleccione --' }))
            .attr('disabled', true);

        if (!val) return false;

        const items = Array.isArray(this.dataSet) ? this.dataSet : Object.values(this.dataSet);

        items.forEach(item => {
            if (this.filterField)
                if (item[this.filterField] != val) return;
            const text = typeof this.optionText === 'function' ? this.optionText(item) : (typeof item === 'string' ? item : item[this.optionText]);
            const value = typeof item === 'string' ? item : item[this.optionValue];

            const { hidden, badge } = this._checkValidation(item);
            const atributos = {};

            if (hidden) {
                atributos['data-hidden'] = true;
                atributos['data-nosearch'] = true;
            }

            if (item[this.optionSelected] == 1) {
                atributos['selected'] = '';
            }

            this.$select.append(
                $('<option>')
                    .val(value)
                    .text(text + (hidden && badge ? ` <label class="badge badge-danger ms-2">${badge}</label>` : ''))
                    .attr(atributos)
            );
        });

        this.$select.attr('disabled', false);
    }

    llenar(dataSet = this.dataSet) {
        // Permite iterar sobre dataSet tanto si es arreglo como si es objeto
        const items = Array.isArray(dataSet) ? dataSet : Object.values(dataSet);

        this.$select.html($('<option>', { value: '', text: '-- Seleccione --' }));
        items.forEach(item => {
            // Se obtiene el texto, ya sea mediante función o propiedad directa
            const text = typeof this.optionText === 'function' ? this.optionText(item) : (typeof item === 'string' ? item : item[this.optionText]);
            const value = typeof item === 'string' ? item : item[this.optionValue];

            const { hidden, badge } = this._checkValidation(item);
            const atributos = {};

            if (hidden) {
                atributos['data-hidden'] = true;
                atributos['data-nosearch'] = true;
            }

            if (item[this.optionSelected] == 1) {
                atributos['selected'] = '';
            }

            this.$select.append(
                $('<option>')
                    .val(value)
                    .text(text + (hidden && badge ? ` <label class="badge badge-danger ms-2">${badge}</label>` : ''))
                    .attr(atributos)
            );
        });
    }
}