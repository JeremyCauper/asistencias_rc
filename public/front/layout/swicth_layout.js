function esCelularTema() {
    return /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
}

if (!localStorage.hasOwnProperty('data_mdb_theme') || !localStorage.data_mdb_theme) {
    localStorage.setItem('data_mdb_theme', 'light');
}
$('html').attr('data-mdb-theme', localStorage.data_mdb_theme);

$('#check').prop('checked', localStorage.data_mdb_theme == 'light' ? true : false);
if (!esCelularTema()) {
    $('.check-trail').append(`<span class="badge badge-secondary toltip-theme">
        <b class="fw-bold">Ctrl</b><i class="fas fa-plus"></i> <b class="fw-bold">Alt</b><i class="fas fa-plus"></i> <b class="fw-bold">D</b>
    </span>`);
}

$(document).ready(function (tema = null) {
    const toggleTheme = (checked = null) => {
        const checkbox = $('#check');
        if (checked) {
            checkbox.prop('checked', !checkbox.prop('checked'));
        }
        tema = checkbox.prop('checked') ? 'light' : 'dark';
        localStorage.data_mdb_theme = tema;
        $('html').attr('data-mdb-theme', tema);
    };

    $('#check').on('click', () => {
        toggleTheme();
    });

    $(window).on('keydown', ({
        key,
        shiftKey,
        ctrlKey,
        altKey
    }) => {
        if (ctrlKey && altKey && key.toLowerCase() === 'd') {
            toggleTheme(true);
        }
    });

});