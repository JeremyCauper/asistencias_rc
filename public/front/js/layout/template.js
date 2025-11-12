
(function ($) {
  'use strict';
  $(function () {
    var body = $('body');
    var contentWrapper = $('.content-wrapper');
    var scroller = $('.container-scroller');
    var footer = $('.footer');
    var sidebar = $('.sidebar');
    var current = location.pathname; //.split("/").slice(-1)[0].replace(/^\/|\/$/g, '');

    //Add active class to nav-link based on url dynamically
    //Active class can be hard coded directly in html file also as required

    function addActiveClass(element) {
      if (current === "") {
        //for root url
        if (element.attr('href').indexOf("index.html") !== -1) {
          element.parents('.nav-item').last().addClass('active');
          if (element.parents('.sub-menu').length) {
            element.closest('.collapse').addClass('show');
            element.addClass('active');
          }
        }
      } else {
        //for other url
        if (element.attr('href').indexOf(current) !== -1) {
          element.parents('.nav-item').last().addClass('active');
          const expand = element.parents('.nav-item').length == 1 ? false : true;
          element.parents('.nav-item').last().children('.nav-link').attr('aria-expanded', expand);
          if (element.parents('.sub-menu').length) {
            element.closest('.collapse').addClass('show');
            element.addClass('active');
          }
          if (element.parents('.submenu-item').length) {
            element.addClass('active');
          }
        }
      }
    }

    $('.nav li a', sidebar).each(function () {
      var $this = $(this);
      addActiveClass($this);
    })

    $('.horizontal-menu .nav li a').each(function () {
      var $this = $(this);
      addActiveClass($this);
    })

    //Close other submenu in sidebar on opening any

    sidebar.on('show.bs.collapse', '.collapse', function () {
      sidebar.find('.collapse.show').collapse('hide');
    });


    //Change sidebar and content-wrapper height
    applyStyles();

    function applyStyles() {
      //Applying perfect scrollbar
      if (!body.hasClass("rtl")) {
        if ($('.settings-panel .tab-content .tab-pane.scroll-wrapper').length) {
          const settingsPanelScroll = new PerfectScrollbar('.settings-panel .tab-content .tab-pane.scroll-wrapper');
        }
        if ($('.chats').length) {
          const chatsScroll = new PerfectScrollbar('.chats');
        }
        if (body.hasClass("sidebar-fixed")) {
          if ($('#sidebar').length) {
            var fixedSidebarScroll = new PerfectScrollbar('#sidebar .nav');
          }
        }
      }
    }

    //checkbox and radios
    $(".form-check label,.form-radio label").append('<i class="input-helper"></i>');

    //Horizontal menu in mobile
    $('[data-toggle="horizontal-menu-toggle"]').on("click", function () {
      $(".horizontal-menu .bottom-navbar").toggleClass("header-toggled");
    });
    // Horizontal menu navigation in mobile menu on click
    var navItemClicked = $('.horizontal-menu .page-navigation >.nav-item');
    navItemClicked.on("click", function (event) {
      if (window.matchMedia('(max-width: 991px)').matches) {
        if (!($(this).hasClass('show-submenu'))) {
          navItemClicked.removeClass('show-submenu');
        }
        $(this).toggleClass('show-submenu');
      }
    })

    $(window).scroll(function () {
      if (window.matchMedia('(min-width: 992px)').matches) {
        var header = $('.horizontal-menu');
        if ($(window).scrollTop() >= 70) {
          $(header).addClass('fixed-on-scroll');
        } else {
          $(header).removeClass('fixed-on-scroll');
        }
      }
    });
    if ($("#datepicker-popup").length) {
      $('#datepicker-popup').datepicker({
        enableOnReadonly: true,
        todayHighlight: true,
      });
      $("#datepicker-popup").datepicker("setDate", "0");
    }

    $(window).on('resize', function () {
      var sidebarIconOnly = body.hasClass("sidebar-icon-only");
      if (window.innerWidth <= 991) {
        if (sidebarIconOnly) {
          body.removeClass("sidebar-icon-only");
        }
      } else {
        $('.sidebar, .sidebar-content').removeClass('active');
        if (localStorage.sidebarIconOnly) {
          body.addClass("sidebar-icon-only");
        }
      }
    });

  });

  //check all boxes in order status 
  $("#check-all").click(function () {
    $(".form-check-input").prop('checked', $(this).prop('checked'));
  });

  // focus input when clicking on search icon
  $('#navbar-search-icon').click(function () {
    $("#navbar-search-input").focus();
  });

  $(window).scroll(function () {
    var scroll = $(window).scrollTop();

    //>=, not <=
    if (scroll >= 6) {
      //clearHeader, not clearheader - caps H
      $(".fixed-top").addClass("headerLight");
    }
    else {
      $(".fixed-top").removeClass("headerLight");
    }
  }); //missing );

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