
(function ($) {
  'use strict';
  $(function () {
    var sidebar = $('.sidebar');
    var current = location.pathname;

    //Add active class to nav-link based on url dynamically
    //Active class can be hard coded directly in html file also as required
    sidebar.find('.sidebar__body').find('a').filter(function () {
      let pathname = this.pathname;
      let enLink = pathname == current;
      let $this = $(this);

      if (enLink && $this.is('[data-ruta]')) {
        $(`[data-menu="${$this.data('ruta')}"]`).addClass('is-active');
      }

      return enLink;
    }).addClass('is-active');

    if ($("#datepicker-popup").length) {
      $('#datepicker-popup').datepicker({
        enableOnReadonly: true,
        todayHighlight: true,
      });
      $("#datepicker-popup").datepicker("setDate", "0");
    }
  });

  $('.modal').on('hidden.bs.modal', function () {
    $('html').removeClass('overflow-hidden');
    const form = $('.modal form');
    form.find('input').each(function () {
      if (!$(this).is('[data-noclear]')) {
        switch ($(this).attr('type')) {
          case "checkbox":
            $(this).prop('checked', false);
            break;

          case "number":
            $(this).val("0");
            break;

          default:
            $(this).val("");
            break;
        }
      }
    });
    form.find('textarea').each(function () {
      $(this).val("");
    });
    form.find('select').each(function () {
      var val = "";
      if ($(this).find('option[selected]').attr('value')) {
        val = $(this).find('option[selected]').attr('value');
        $(this).val(val).trigger('change.select2');
      } else {
        $(this).val(val).trigger('change.select2');
      }
    });
    form.find('button').each(function () {
      if ($(this).is('[button-reset]')) {
        $(this).click();
      }
    });
  });

  $('.modal').on('shown.bs.modal', function () {
    $('html').addClass('overflow-hidden');
  });
})(jQuery);