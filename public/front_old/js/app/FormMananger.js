class FormMananger {
    constructor(parameters) {

    }

    formModalLoding(id, accion, dnone = false) {
        const modalf = $(`#${id} .modal-dialog .modal-content`);
        switch (accion) {
            case 'show':
                modalf.append(`<div class="loader-of-modal"><div style="display:flex; justify-content:center;"><div class="loader"></div></div></div>`);
                if (dnone) {
                    modalf.find('.modal-body').addClass('d-none');
                }
                break;

            case 'hide':
                modalf.children('.loader-of-modal').remove();
                modalf.find('.modal-body').removeClass('d-none');
                break;

            default:
                break;
        }
    }
}

var fMananger = new FormMananger();