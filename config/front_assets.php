<?php
$version = 9;
return [
    'css' => (object) [
        'mdb_all_min6_0_0' => 'front/vendor/mdboostrap/css/all.min6.0.0.css?v=' . $version,
        'mdb_min7_2_0' => 'front/vendor/mdboostrap/css/mdb.min7.2.0.css?v=' . $version,
        'select2' => 'front/vendor/select/select2.min.css?v=' . $version,
        'sweet_animate' => 'front/vendor/sweetalert/animate.min.css?v=' . $version,
        'sweet_default' => 'front/vendor/sweetalert/default.css?v=' . $version,
        'fonts' => 'front/vendor/fontGoogle/fonts.css?v=' . $version,
        'layout' => 'front/layout/layout.css?v=' . $version,
        'app' => 'front/css/app.css?v=' . $version,
        'swicth_layout' => 'front/layout/swicth_layout.css?v=' . $version,
        'quill_show' => 'front/vendor/quill/quill.snow.css?v=' . $version,
        'daterangepicker' => 'front/vendor/daterangepicker/daterangepicker.css?v=' . $version,
    ],

    'js' => (object) [
        'actualizarPassword' => 'front/js/actualizarPassword.js?v=' . $version,

        'app' => 'front/js/app.js?v=' . $version,
        'AlertMananger' => 'front/js/app/AlertMananger.js?v=' . $version,
        'NotificacionesControl' => 'front/js/app/NotificacionesControl.js?v=' . $version,
        'FormMananger' => 'front/js/app/FormMananger.js?v=' . $version,
        'ChartMananger' => 'front/js/app/ChartMananger.js?v=' . $version,
        'MediaViewerControl' => 'front/js/app/MediaViewerControl.js?v=' . $version,
        'QuillControl' => 'front/js/app/QuillControl.js?v=' . $version,

        'swicth_layout' => 'front/layout/swicth_layout.js?v=' . $version,
        'toggle_template' => 'front/layout/toggle_template.js?v=' . $version,
        'template' => 'front/layout/template.js?v=' . $version,

        'jquery' => 'front/vendor/jquery/jquery.min.js?v=' . $version,
        'mdb_umd_min7_2_0' => 'front/vendor/mdboostrap/js/mdb.umd.min7.2.0.js?v=' . $version,
        'jquery_dataTables' => 'front/vendor/dataTable/jquery.dataTables.min.js?v=' . $version,
        'sweet_sweetalert2' => 'front/vendor/sweetalert/sweetalert2@11.js?v=' . $version,
        'select2' => 'front/vendor/select/select2.min.js?v=' . $version,
        'form_select2' => 'front/vendor/select/form_select2.js?v=' . $version,
        'daterangepicker_moment' => 'front/vendor/daterangepicker/moment.min.js?v=' . $version,
        'daterangepicker' => 'front/vendor/daterangepicker/daterangepicker.min.js?v=' . $version,
        'bootstrap_bundle' => 'front/vendor/multiselect/bootstrap.bundle.min.js?v=' . $version,
        'bootstrap_multiselect' => 'front/vendor/multiselect/bootstrap_multiselect.js?v=' . $version,
        'form_multiselect' => 'front/vendor/multiselect/form_multiselect.js?v=' . $version,
        'echarts' => 'front/vendor/echartjs/echarts.min.js?v=' . $version,
        'compressor' => 'front/vendor/compression/compressor.min.js?v=' . $version,
        'quill' => 'front/vendor/quill/quill.min.js?v=' . $version,
        'exceljs' => 'front/vendor/exceljs/exceljs.min.js?v=' . $version,
        'FileSaver' => 'front/vendor/exceljs/FileSaver.min.js?v=' . $version,
        'full_calendar' => 'front/vendor/full-calendar/full-calendar.min.js?v=' . $version,
        'jquery_inputmask_bundle' => 'front/vendor/inputmask/jquery.inputmask.bundle.min.js?v=' . $version,

        'service_worker' => 'sw.js?v=' . $version,
    ],

    'json' => (object) [
        'manifest' => 'manifest.json?v=' . $version,
    ],

    'img' => (object) [
        'icon' => 'front/images/app/icons/icon.png?v=' . $version,
    ]
];