class CTable {
    _contenedor = null;
    _idContenedor = null;
    _selector = null;
    _btnCreate = null;
    _counter = null;
    _table = null;
    _structure = {
        count: null,
        repit: false,
        table: {},
        extract: [],
        select: {}
    };
    _dom = '<"row"<"col-lg-8 col-10"s><"col-2"B>>';
    _dataCount = 1;
    acciones = true;
    newRow = 1;
    data = {};
    _obj = null;

    $operations = {
        '===': (a, b) => a === b,
        '!==': (a, b) => a !== b,
        '>': (a, b) => a > b,
        '<': (a, b) => a < b,
        '>=': (a, b) => a >= b,
        '<=': (a, b) => a <= b,
    }

    constructor(contenedor, options = {}) {
        this._structure = {
            ...this._structure,
            ...options
        };
        this._idContenedor = contenedor;
        this._contenedor = $('#' + contenedor);
        this._dom = options.dom || this._dom;
        this.dataSet = options.dataSet || [];

        if (!this._contenedor.length) return alert('Contenedor no existe.');
        if (!this._structure.table?.thead?.length) return alert('thead no configurado.');
        if (!this._structure.table?.tbody?.length) return alert('tbody no configurado.');

        this._selector = $('<select>', {
            class: 'select-clear',
            'ctable-select': contenedor
        });

        this._btnCreate = $('<button>', {
            type: "button",
            class: 'btn btn-primary px-2 ms-1 rounded',
            'ctable-create': contenedor,
            'data-mdb-ripple-init': ''
        }).append($('<i>', { class: 'fas fa-plus', style: 'pointer-events: none;' }));

        if (this._structure?.count) {
            this._counter = $('<div>', { class: 'input-group disabled', style: 'max-width: 300px;', 'ctable-count-content': contenedor }).append(
                $('<button>', { class: 'btn btn-secondary px-2', type: 'button', 'ctable-count-minus': contenedor }).append(
                    $('<i>', { class: 'fas fa-minus', style: 'font-size: .75rem;' })
                ),
                $('<input>', { type: 'number', class: 'form-control', min: '1', value: '1', 'ctable-count-cant': contenedor }),
                $('<button>', { class: 'btn btn-secondary px-2', type: 'button', 'ctable-count-plus': contenedor }).append(
                    $('<i>', { class: 'fas fa-plus', style: 'font-size: .75rem;' })
                )
            );
        }

        this._contenedor.empty().attr({ 'ctable-content': contenedor });
        const layout = this._parseDOMString(this._dom);
        this._contenedor.append(layout);

        this.fillSelect();
        this.createCount();

        $(`button[ctable-create="${contenedor}"]`).on('click', () => {
            this.createRow();
        });

        this._selector.on('change', () => {
            const count = this._structure?.count;
            const val = this._selector.val();
            this.fillObj(val);

            if (count) {
                this.clearCount();
                if (val) $(`input[ctable-count-cant="${contenedor}"]`).attr({ max: this._obj[count] });
            }
        });
    }

    _parseDOMString(domString) {
        const mapping = {
            's': () => this._selector,
            'B': () => this._btnCreate,
            'C': () => this._counter,
            't': () => this._createTableWrapper()
        };

        const stack = [];
        let current = $('<div>');
        let inQuotes = false;
        let className = '';

        for (let i = 0; i < domString.length; i++) {
            const char = domString[i];

            if (char === '"') {
                inQuotes = !inQuotes;
                if (!inQuotes && className) {
                    current.addClass('my-1');
                    current.addClass(className);
                    className = '';
                }
            } else if (inQuotes) {
                className += char;
            } else if (char === '<') {
                const newDiv = $('<div>');
                stack.push(current);
                current.append(newDiv);
                current = newDiv;
            } else if (char === '>') {
                current = stack.pop() || $('<div>');
            } else if (/[a-zA-Z]/.test(char)) {
                const el = mapping[char]?.();
                if (el) current.append(el);
            }
        }

        // Siempre agregar el contenedor de la tabla al final
        current.append(this._createTableWrapper());
        return current.children();
    }

    _createTableWrapper() {
        return $('<div>', { class: 'col-12 pt-3', style: 'overflow: auto;' }).append(
            $('<div>', { 'ctable-contentTable': this._idContenedor })
        );
    }

    createRow(value = this._selector.val()) {
        if (!value) return false;
        const extract = this._structure.extract;
        this.fillObj(value);
        if (!this._obj) return false;
        if ($(`tr[ctable-table-tr-id="${value}"]`).length) return boxAlert.minbox({ i: 'info', h: 'El registro ya existe.' });

        const $id = this._obj[extract[0]];
        if ($id in this.data) {
            this.data[$id].eliminado = 0;
        } else {
            const obj_td = {};
            if (this._structure?.count && this._obj[this._structure?.count] < this._dataCount) return boxAlert.box({ i: 'warning', h: 'La cantidad ingresada es menor a lo que se tiene registrado.' });
            extract.forEach(e => {
                let valor = this._obj[e];
                if (this._structure?.count && this._structure?.count == e) valor = this._dataCount;
                obj_td[e] = valor;
            });
            obj_td['eliminado'] = 0;
            obj_td['registro'] = this.newRow;
            this.data[$id] = obj_td;
        }

        const $tr = $('<tr>', { 'ctable-table-tr-id': $id, 'ctable-table-tr': this.acciones, 'ctable-table-rnew': this.newRow });
        this._structure.table.tbody.forEach(col => {
            const $td = $('<td>', { 'ctable-table-col': col.data });
            let valor = (col.render && typeof col.render === 'function') ? col.render(this._obj[col.data], typeof col.data, this._obj) : this._obj[col.data];
            if (this._structure?.count == col.data) valor = this._dataCount;
            $td.html(valor);
            $tr.append($td);
        });

        const actionDelete = $('<td>', { class: 'text-center' }).append($('<i>', { class: "far fa-trash-can text-danger", type: "button", 'ctable-delete': this._idContenedor, value: $id }));
        const dontDelete = $('<td>', { class: 'text-center' }).append($('<i>', { class: "fas fa-ban text-primary" }));
        $tr.append(this.acciones ? actionDelete : dontDelete);

        this.createTable();
        this._table.children('tbody').append($tr);

        $(`i[ctable-delete="${this._idContenedor}"]`).off('click').on('click', (event) => {
            const val = event.currentTarget.getAttribute('value');
            this.deleteRow(val);
        });

        this.fillSelect();
        this.clearCount();
        this.acciones = true;
        this.newRow = 1;
    }

    deleteRow(value) {
        if (!value) return false;
        if (!this.data[value]) return false;
        if (this.data[value].registro) {
            delete this.data[value];
        } else {
            this.data[value].eliminado = 1;
        }
        $(`tr[ctable-table-tr-id="${value}"]`).remove();
        this.fillSelect();
        if (!this._table.children('tbody').children('tr').length) this.deleteTable();
    }

    createTable() {
        if (this._table === null) {
            var style = "padding-top: 4px; padding-bottom: 4px;";
            this._table = $('<table>', { class: 'table w-100 text-nowrap', 'ctable-table': this._idContenedor }).append(
                $('<thead>').append($('<tr>', { style: 'border-bottom: 1px solid var(--mdb-primary);' }).html(`<th style="${style}">${(this._structure.table.thead).join(`</th><th style="${style}">`)}</th><th class="text-center" style="min-width: 50px;max-width: 120px;width: 120px;${style}"></th>`)),
                $('<tbody>')
            );
            $(`div[ctable-contentTable="${this._idContenedor}"]`).append(this._table);
        }
    }

    updateTable({ data = this.data, del = true, newR = 0 } = {}) {
        this.deleteTable();
        this.fillTable(Object.keys(data), { del: del, newR: newR });
    }

    deleteTable() {
        this.data = {};
        if (this._table !== null) this._table.remove();
        this.fillSelect();
        this._table = null;
    }

    fillTable(value, { del = true, newR = 0 } = {}) {
        const valores = Array.isArray(value) ? value : [value];
        valores.forEach(v => {
            this.acciones = del;
            this.newRow = newR;
            this.createRow(v);
        });
    }

    fillSelect(dataSet = this.dataSet) {
        const items = Array.isArray(dataSet) ? dataSet : Object.values(dataSet);
        const options = this._structure.select;
        this._selector.html($('<option>', { value: '', text: '-- Seleccione --' }));

        items.forEach(item => {
            const text = typeof options.text === 'function' ? options.text(item) : (typeof item === 'string' ? item : item[options.text]);
            const value = typeof item === 'string' ? item : item[options.value];

            const { hidden, badge } = this._checkValidation(item);
            const atributos = {};
            if (hidden) {
                atributos['data-hidden'] = true;
                atributos['data-nosearch'] = true;
            }
            if (this.data[value]) atributos.disabled = true;

            this._selector.append(
                $('<option>')
                    .val(value)
                    .text(text +
                        (hidden && badge ? ` <label class="badge badge-danger ms-2">${badge}</label>` : '') +
                        (this.data[value] ? ' <label class="badge badge-info ms-2">En uso</label>' : '')
                    )
                    .attr(atributos)
            );
        });
    }

    _checkValidation(item) {
        for (const { clave, operation, value, badge = '' } of this._structure.select.validation) {
            if (clave in item && this.$operations[operation](item[clave], value)) {
                return { hidden: true, badge };
            }
        }
        return { hidden: false, badge: '' };
    }

    createCount() {
        if (!this._structure?.count) return false;
        const $btnMas = $(`button[ctable-count-plus="${this._idContenedor}"]`);
        const $input = $(`input[ctable-count-cant="${this._idContenedor}"]`);
        const $btnMenos = $(`button[ctable-count-minus="${this._idContenedor}"]`);

        $btnMas.off('click').on('click', () => this.updateValueCount(1));
        $btnMenos.off('click').on('click', () => this.updateValueCount(-1));
        $input.off('paste').on('paste', (e) => {
            const pasted = e.originalEvent.clipboardData.getData('text');
            if (!/^\d+$/.test(pasted)) e.preventDefault();
        });
        $input.off('blur').on('blur', () => {
            let valor = parseInt($input.val(), 10);
            if (isNaN(valor) || valor < 1) this.updateValueCount();
        });
    }

    updateValueCount(delta = null) {
        const $input = $(`input[ctable-count-cant="${this._idContenedor}"]`);
        if (!delta) return $input.val(1);
        let valor = parseInt($input.val(), 10);
        if (isNaN(valor)) valor = 1;
        valor = Math.max(1, valor + delta);
        
        if (valor > this._obj[this._structure?.count]) return;
        $input.val(valor);
        this._dataCount = valor;
    }

    clearCount() {
        const countContent = $(`div[ctable-count-content="${this._idContenedor}"]`);
        if (this._selector.val()) {
            countContent.removeClass('disabled');
        } else {
            countContent.addClass('disabled');
        }
        this._dataCount = 1;
        this.updateValueCount();
    }

    extract() {
        if (this._selector.val()) {
            boxAlert.box({ i: 'warning', t: 'Advertencia', h: 'El selector aún tiene un valor por añadir.' });
            return false;
        }
        return this.data;
    }

    fillObj(val) {
        this._obj = val ? ((Array.isArray(this.dataSet) ? this.dataSet : Object.values(this.dataSet)).find(v => v[this._structure.extract[0]] == val)) : null;
    }
}