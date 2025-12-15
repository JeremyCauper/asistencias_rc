<?php
$version = '5.6.3';
return [
    'version' => $version,
    'css' => (object) [
        'app' => 'front/css/app.css?v=' . $version,
        'mdb_all_min6_0_0' => 'front/vendor/mdboostrap/css/all.min6.0.0.css',
        'mdb_min7_2_0' => 'front/vendor/mdboostrap/css/mdb.min7.2.0.css',
        'select2' => 'front/vendor/select/select2.min.css',
        'sweet_animate' => 'front/vendor/sweetalert/animate.min.css',
        'sweet_default' => 'front/vendor/sweetalert/default.css',
        'fonts' => 'front/vendor/fontGoogle/fonts.css',
        'quill_show' => 'front/vendor/quill/quill.snow.css?v=' . $version,
        'daterangepicker' => 'front/vendor/daterangepicker/daterangepicker.css?v=' . $version,
        'layout' => 'front/layout/layout.css?v=' . $version,
        'swicth_layout' => 'front/layout/swicth_layout.css?v=' . $version,
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

        'jquery' => 'front/vendor/jquery/jquery.min.js',
        'mdb_umd_min7_2_0' => 'front/vendor/mdboostrap/js/mdb.umd.min7.2.0.js',
        'jquery_dataTables' => 'front/vendor/dataTable/jquery.dataTables.min.js',
        'sweet_sweetalert2' => 'front/vendor/sweetalert/sweetalert2@11.js',
        'select2' => 'front/vendor/select/select2.min.js',
        'form_select2' => 'front/vendor/select/form_select2.js',
        'daterangepicker_moment' => 'front/vendor/daterangepicker/moment.min.js',
        'daterangepicker' => 'front/vendor/daterangepicker/daterangepicker.min.js',
        'bootstrap_bundle' => 'front/vendor/multiselect/bootstrap.bundle.min.js',
        'bootstrap_multiselect' => 'front/vendor/multiselect/bootstrap_multiselect.js',
        'form_multiselect' => 'front/vendor/multiselect/form_multiselect.js',
        'echarts' => 'front/vendor/echartjs/echarts.min.js',
        'compressor' => 'front/vendor/compression/compressor.min.js',
        'quill' => 'front/vendor/quill/quill.min.js',
        'exceljs' => 'front/vendor/exceljs/exceljs.min.js',
        'FileSaver' => 'front/vendor/exceljs/FileSaver.min.js',
        'full_calendar' => 'front/vendor/full-calendar/full-calendar.min.js',
        'jquery_inputmask_bundle' => 'front/vendor/inputmask/jquery.inputmask.bundle.min.js',

        'service_worker' => 'sw.js?v=' . $version,
    ],

    'json' => (object) [
        'manifest' => 'manifest.json?v=' . $version,
    ],

    'img' => (object) [
        'icon' => 'front/images/app/icons/icon.webp?v=' . $version,
        'icon_badge' => 'front/images/app/icons/icon-badge.webp?v=' . $version,
        'icon_96' => 'front/images/app/icons/icon-96.webp?v=' . $version,
        'icon_192' => 'front/images/app/icons/icon-192.webp?v=' . $version,
        'icon_512' => 'front/images/app/icons/icon-512.webp?v=' . $version,
    ]
];