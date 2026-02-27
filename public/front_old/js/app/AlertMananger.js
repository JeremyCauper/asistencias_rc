class AlertMananger {
    constructor() {
        //this.Swal = new Swal();
    }

    loading(msg = false) {
        const objeto = {
            title: '<div class="my-3"><div style="display:flex; justify-content:center;"><div class="loader loader-lg"></div></div></div>',
            allowOutsideClick: false,
            showConfirmButton: false,
            width: 'auto',         // o un valor fijo como '400px'
            showClass: {
                popup: `
                    animate__animated
                    animate__fadeIn
                    animate__faster
                `
            }
        };
        if (msg === false) {
            objeto.background = 'transparent';
        } else {
            objeto.html = `<span class="text-primary"><b>${msg || 'Por favor espere...'}</b></span>`;
        }

        Swal.fire(objeto);
    }

    async confirm(op = {}) {
        const titulo = op.t || '¿Estás de suguro de esta acción?';
        const thtml = op.h || false;
        if (!(await Swal.fire({
            title: `<h5 class="card-title text-secondary">${titulo}</h5>`,
            html: thtml,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "¡Sí!",
            cancelButtonText: "Cancelar",
            showClass: {
                popup: `
                  animate__animated
                  animate__fadeIn
                  animate__faster
                `
            },
        })).isConfirmed)
            return false;
        return true;
    }

    box(op = {}) {
        const icono = op.i || 'success';
        const titulo = op.t || '';
        const thtml = op.h || false;
        delete op.i;
        delete op.t;
        delete op.h;
        Swal.fire({
            icon: icono,
            title: `<h5 class="card-title text-secondary">${titulo}</h5>`,
            html: thtml,
            ...op,
            confirmButtonColor: "#3085d6",
            confirmButtonText: "Aceptar",
        });
    }

    minbox(op = {}) {
        const icono = op.i || 'success';
        const thtml = op.h || false;
        const bground = op.b || "#5e87ca";
        const color = op.c || "#ffffff";
        const position = op.p || "top";
        delete op.i;
        delete op.h;
        delete op.b;
        delete op.c;
        delete op.p;

        const Toast = Swal.mixin({
            toast: true,
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            },
            showClass: {
                popup: `
                  animate__animated
                  animate__fadeInDownBig
                  animate__faster
                `
            },
            hideClass: {
                popup: `
                  animate__animated
                  animate__fadeOutUp
                  animate__faster
                `
            }
        });
        Toast.fire({
            icon: icono,
            html: thtml,
            position: position || "top-end",
            background: bground,
            color: color,
            ...op
        });
    }

    toast(op = {}) {
        const icono = op.i || 'success';
        const thtml = op.h || false;
        const bground = op.b || "#3b71ca";
        const color = op.c || "#ffffff";
        const position = op.p || "top-end";
        const timer = op.tr || 5000;
        const timerProBar = timer ? true : false;
        delete op.i;
        delete op.h;
        delete op.b;
        delete op.c;
        delete op.p;
        delete op.tr;

        Swal.fire({
            toast: true,
            icon: icono,
            html: thtml,
            position: position,
            background: bground,
            color: color,
            showConfirmButton: false,
            showCloseButton: true, // Agrega la "X" para cerrar
            timer: timer,
            timerProgressBar: timerProBar,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            },
            showClass: {
                popup: `
                animate__animated
                animate__fadeInRight
                animate__faster
                `
            },
            hideClass: {
                popup: `
                animate__animated
                animate__fadeOutRight
                animate__faster
                `
            }
        });
    }

    table(funcion = updateTable) {
        Swal.fire({
            icon: 'error',
            title: `<h6 class="card-title text-secondary"><b>Error al intentar extraer datos de la tabla</b></h6>`,
            html: `<div class="text-center" style="font-size:small;">
                    <p class="mb-0">intente recargar la tabla o comuniquese con su administrador</p>
                   </div>`,
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: '<i class="fas fa-rotate-right"></i>',
            cancelButtonText: "Cerrar",
            showClass: {
                popup: `
                  animate__animated
                  animate__fadeIn
                  animate__faster
                `
            },
        }).then((result) => {
            if (result.isConfirmed && typeof funcion === "function") {
                funcion();
            }
        });
    }
}

var boxAlert = new AlertMananger();