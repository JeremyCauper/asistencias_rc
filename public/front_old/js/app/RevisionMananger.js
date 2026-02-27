class RevisionMananger {
    _config = [
        {
            text: "ISLA",
            classText: "input-group-text border-0 ps-0",
            styleText: "font-size: small; color: #757575",
            dataInput: "isla",
            child: false,
        },
        {
            text: "POS",
            classText: "input-group-text border-0 ps-0",
            styleText: "font-size: small; color: #757575",
            dataInput: "pos",
            child: false,
        },
        {
            text: "IMPRESORAS",
            classText: "col-lg-3 d-flex align-items-center",
            styleText: "font-size: 11px; color: #757575",
            dataInput: "impresoras",
            child: false,
        },
        {
            text: "RED DE LECTORES",
            classText: "col-lg-3 d-flex align-items-center",
            styleText: "font-size: 11px; color: #757575",
            dataInput: "red_lectores",
            child: false,
        },
        {
            text: "JACK TOOLS",
            classText: "col-lg-3 d-flex align-items-center",
            styleText: "font-size: 11px; color: #757575",
            dataInput: "jack_tools",
            child: false,
        },
        {
            text: "VOLTAJE DE MANGUERAS",
            classText: "col-lg-3",
            styleText: "font-size: 11px; color: #757575",
            dataInput: "voltaje",
            child: true,
        },
        {
            text: "CAUCHO PROTECTOR DE LECTORES",
            classText: "col-lg-3 d-flex align-items-center",
            styleText: "font-size: 11px; color: #757575",
            dataInput: "caucho_protector",
            child: false,
        },
        {
            text: "MUEBLE DE POS",
            classText: "col-lg-3 d-flex align-items-center",
            styleText: "font-size: 11px; color: #757575",
            dataInput: "mueble_pos",
            child: false,
        },
        {
            text: "MR 350 / DTI / TERMINAL",
            classText: "col-lg-3 d-flex align-items-center",
            styleText: "font-size: 11px; color: #757575",
            dataInput: "terminales",
            child: false,
        }
    ];

    static _data = {};

    get data() {
        return RevisionMananger._data;
    }

    set data(dat) {
        RevisionMananger._data = dat;
    }

    create() {
        let _content_islas = $('#content-islas');
        let items_islas = $('<div>', { class: "islas-item py-2" });
        let contenedor; // Variable para almacenar la fila actual

        this._config.forEach((e, i) => {
            // Para los dos primeros elementos (índices 0 y 1)
            if (i < 2) {
                // Si es el primer elemento, creamos una nueva fila
                if (!contenedor) {
                    contenedor = $('<div>', { class: "row my-2" });
                }
                let contentGroup = $('<div>', { class: "col-lg-3 col-sm-4 col-5" });
                let inputGroup = $('<div>', { class: "input-group" })
                    .append($('<span>', { class: e.classText, style: e.styleText }).text(e.text))
                    .append($('<input>', { class: "form-control rounded" }).attr("data-isla", e.dataInput));
                contentGroup.append(inputGroup);
                contenedor.append(contentGroup);
                // Si es el segundo elemento (índice 1), agregamos el botón de eliminar a la misma fila
                if (i === 1) {
                    let conteo = $('<div>', { class: "align-items-center col-2 col-lg-6 col-sm-4 d-flex justify-content-end" })
                        .append(
                            $('<strong>', { class: "me-2 text-nowrap conteo-islas-tittle" })
                        );
                    contenedor.append(conteo);
                    items_islas.append(contenedor);
                    contenedor = null; // Reiniciamos para que en próximas iteraciones se cree una nueva fila si es necesario
                }
            } else {
                // Para el resto de elementos, cada uno en su propia fila
                // <div class="input-group mb-3">
                //     <span class="input-group-text" id="basic-addon1">@</span>
                //     <input type="text" class="form-control"/>
                // </div>
                contenedor = $('<div>', { class: "row my-2" });
                let titulo = $('<div>', { class: e.classText, style: e.styleText })
                    .html($('<strong>', { class: `${ e.child ? 'item-isla' : '' }`}).text(e.text));
                let cuerpo = $('<div>', { class: "col-lg-9" })
                    .append($('<div>', { class: "input-group" })
                        .append($('<span>', { class: "input-group-text border-0 ps-0" }).html('<i class="fas fa-circle-check"></i>'))
                        .append($('<input>', { class: "form-control rounded", "onchange": "changeCheck(this)" }).attr("data-isla", e.dataInput)));

                contenedor.append(titulo, cuerpo);
                items_islas.append(contenedor);
                contenedor = null;
            }

        });
        let btnAcciones = $('<div>', { class: "text-end" })
            .append(
                $('<button>', { class: "btn btn-secondary btn-sm px-1", type: "button", "onclick": "(new RevisionMananger()).create()" })
                    .append('<i class="far fa-square-plus"></i>')
            )
            .append(
                $('<button>', { class: "btn btn-danger btn-sm px-1 ms-2", type: "button", "onclick": "(new RevisionMananger()).delete(this)" })
                    .append('<i class="far fa-trash-can"></i>')
            );
        items_islas.append(btnAcciones);
        _content_islas.append(items_islas);

        $('#conteo-islas').html(`Cant. ${_content_islas.find('.islas-item').length}`);

        $('.conteo-islas-tittle').each(function (i, e) {
            $(e).html('N° ' + (i + 1));
        });

        $("#modal_orden").animate({
            scrollTop: $("#modal_orden")[0].scrollHeight
        }, 800); // La duración de la animación en milisegundos (800ms = 0.8 segundos)
    }

    delete($this) {
        let item_islas = $($this).parent().parent();
        let conteos = $('.conteo-islas-tittle');
        if (conteos.length == 1) {
            return boxAlert.minbox({ i: "warning", b: '#e4a11b', h: "Tiene que tener almenos un formulario de revision." });
        }
        item_islas.remove();
        $('.conteo-islas-tittle').each(function (i, e) {
            $(e).html('N° ' + (i + 1));
        });
        $('#conteo-islas').html(`Cant. ${conteos.length - 1}`);
    }

    deleteAll() {
        $('#content-islas').html('');
        this.create();
    }

    extract() {
        let items_islas = $('#content-islas').find('.islas-item');
        let data = [];
        items_islas.each(function (i, e) {
            let obj = {};
            let inputs = $(e).find('[data-isla]');
            inputs.each(function (ii, ei) {
                let input = $(ei);
                obj[input.attr('data-isla')] = input.val();
            });
            data.push(obj);
        });
        return data;
    }
}

const MRevision = new RevisionMananger();
MRevision.create()