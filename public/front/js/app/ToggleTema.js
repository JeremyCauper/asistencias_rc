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

    $(window).on('keydown', ({ key, shiftKey }) => {
        if (shiftKey && key.toLowerCase() === 'd') {
            toggleTheme(true);
        }
    });
});

function esCelularTema() {
    return /Android|iPhone|iPad|iPod|Windows Phone/i.test(navigator.userAgent);
}