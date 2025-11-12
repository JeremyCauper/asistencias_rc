(function($) {
  'use strict';
  $(function() {
    $('[data-bs-toggle="offcanvas"], .sidebar-content').on("click", function() {
      $('.sidebar-offcanvas').toggleClass('active')
      $('.sidebar-content').toggleClass('active')
    });
  });
})(jQuery);