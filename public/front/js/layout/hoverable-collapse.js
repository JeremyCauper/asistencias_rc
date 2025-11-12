(function ($) {
  'use strict';
  //Open submenu on hover in compact sidebar mode and horizontal menu mode
  $(document).on('mouseenter mouseleave', '.sidebar .menu-item', function (ev) {
    var body = $('body');
    var sidebarIconOnly = body.hasClass("sidebar-icon-only");
    var sidebarFixed = body.hasClass("sidebar-fixed");
    if (!('ontouchstart' in document.documentElement)) {
      if (window.innerWidth > 991) {
        if (sidebarIconOnly) {
          if (sidebarFixed) {
            if (ev.type === 'mouseenter') {
              body.removeClass('sidebar-icon-only');
            }
          } else {
            var $menuItem = $(this);

            if ($menuItem.attr('tittle-menu') == undefined) {
              if ($menuItem[0].childElementCount > 1) {
                var $menuLink = $menuItem.find('.menu-link');
                if (ev.type === 'mouseenter') {
                  $menuItem.addClass('hover-open')
                  $menuLink.removeAttr('data-mdb-collapse-init data-mdb-ripple-init');
                  $menuLink.attr('href', 'javascript:void(0)');
                } else {
                  $menuItem.removeClass('hover-open')
                  var new_href = '#' + $menuLink.attr('aria-controls');
                  $menuLink.attr({
                    'data-mdb-collapse-init': '',
                    'data-mdb-ripple-init': '',
                    'href': new_href
                  });
                }
              } else {
                if (ev.type === 'mouseenter') {
                  $menuItem.addClass('hover-open-only')
                } else {
                  $menuItem.removeClass('hover-open-only')
                }
              }
            }
          }
        }
      }
    }
  });
})(jQuery);