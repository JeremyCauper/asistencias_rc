<?php

use App\Http\Controllers\Api\Syncs\SyncAsistenciasController;
use App\Http\Controllers\Api\Syncs\SyncPersonalController;
use App\Http\Controllers\Asistencia\AsistenciaController;
use App\Http\Controllers\Asistencia\ExcelAsistenciaController;
use App\Http\Controllers\Asistencia\MisAsistenciaController;
use App\Http\Controllers\InventarioVehicular\InventarioVehicularController;
use App\Http\Controllers\Justificacion\JustificacionController;
use App\Http\Controllers\Mantenimientos\AreaPersonal\AreaPersonalController;
use App\Http\Controllers\MantenimientosDeveloper\Menu\MenuController;
use App\Http\Controllers\MantenimientosDeveloper\TipoPersonal\TipoPersonalController;
use App\Http\Controllers\PushController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Personal\PersonalController;
use App\Http\Controllers\MantenimientosDeveloper\Menu\SubMenuController;
use App\Http\Controllers\MantenimientosDeveloper\TipoAsistencia\TipoAsistenciaController;
use App\Http\Controllers\MantenimientosDeveloper\TipoModalidad\TipoModalidadController;
use App\Http\Controllers\MediaArchivo\MediaArchivoController;
use App\Http\Controllers\NotificacionController;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/offline', function () {
    return view('offline');
});

Route::get('/inicio', [LoginController::class, 'view'])->name('login')->middleware('guest:web');
Route::post('/iniciar', [LoginController::class, 'login'])->middleware('web');
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/offline', function () {
    return view('offline');
});

Route::middleware('auth')->group(function () {
    Route::post('/personal/actualizar-password', [LoginController::class, 'actualizarPassword']);

    Route::controller(NotificacionController::class)
        ->prefix('notificaciones')
        ->as('notificaciones.')
        ->group(function () {
            Route::get('/listar', 'listar')->name('listar');
            Route::get('/marcar/{id}', 'marcarLeido')->name('marcarLeido');
            Route::get('/borrar/{id}', 'borrar')->name('borrar');
        });


    Route::get('/personal/personal', [SyncPersonalController::class, 'view']);
    Route::get('/personal/listar', [SyncPersonalController::class, 'listar']);
    Route::get('/personal/{id}', [SyncPersonalController::class, 'show']);
    Route::post('/personal', [SyncPersonalController::class, 'store']);
    Route::put('/personal/{id}', [SyncPersonalController::class, 'update']);
    Route::post('/personal/cambiarEstatus', [SyncPersonalController::class, 'cambiarEstatus']);
    Route::delete('/personal/{id}', [SyncPersonalController::class, 'marcarEliminar']);
    Route::get('/personal/cargar-vacaciones/{id}', [SyncPersonalController::class, 'cargarVacaciones']);
    Route::post('/personal/crear-vacaciones', [SyncPersonalController::class, 'crearVacaciones']);

    Route::controller(AsistenciaController::class)
        ->prefix('asistencias-diarias')
        ->as('asistenciasDiarias.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/ingresar-descuento', 'ingresarDescuento')->name('ingresarDescuento');
        });

    Route::get('/asistencias/misasistencias', [MisAsistenciaController::class, 'view']);
    Route::get('/asistencias/listar', [MisAsistenciaController::class, 'listar']);

    Route::controller(MediaArchivoController::class)
        ->prefix('media-archivo')
        ->as('mediaArchivo.')
        ->group(function () {
            Route::post('/upload-media/{carpeta}', 'uploadMedia')->name('uploadMedia');
        });

    Route::controller(JustificacionController::class)
        ->prefix('justificacion')
        ->as('justificacion.')
        ->group(function () {
            Route::post('/justificar-usuario', 'storeJustificacionByUser')->name('storeJustificacionByUser');
            Route::post('/justificar-admin', 'storeJustificacionByAdmin')->name('storeJustificacionByAdmin');
            Route::post('/responder-justificacion/usuario', 'responseJustificacionByUser')->name('responseJustificacionByUser');
            Route::post('/responder-justificacion/admin', 'responseJustificacionByAdmin')->name('responseJustificacionByAdmin');
            Route::put('/marcar-derivado/{id}', 'marcarDerivado')->name('marcarDerivado');
            Route::get('/eliminarFile', 'eliminarFile')->name('eliminarFile');
        });

    Route::controller(AreaPersonalController::class)
        ->prefix('mantenimiento/area-personal')
        ->as('areapersonal.areapersonal.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::post('/registrar', 'create')->name('create');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/actualizar', 'update')->name('update');
            Route::post('/cambiar-estado', 'changeStatus')->name('changeStatus');
            Route::post('/eliminar', 'delete')->name('delete');
        });

    Route::controller(MenuController::class)
        ->prefix('mantenimiento-dev/menu/menu')
        ->as('menu.menu.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::post('/registrar', 'create')->name('create');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/actualizar', 'update')->name('update');
            Route::post('/cambiar-estado', 'changeStatus')->name('changeStatus');
            Route::post('/cambiar-orden-menu', 'changeOrdenMenu')->name('changeOrdenMenu');
            Route::post('/eliminar', 'delete')->name('delete');
        });

    Route::controller(SubMenuController::class)
        ->prefix('mantenimiento-dev/menu/sub-menu')
        ->as('menu.submenu.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::post('/registrar', 'create')->name('create');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/actualizar', 'update')->name('update');
            Route::post('/cambiar-estado', 'changeStatus')->name('changeStatus');
            Route::post('/eliminar', 'delete')->name('delete');
        });

    Route::controller(TipoModalidadController::class)
        ->prefix('mantenimiento-dev/tipo-modalidad')
        ->as('tipomodalidad.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::post('/registrar', 'create')->name('create');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/actualizar', 'update')->name('update');
            Route::post('/cambiar-estado', 'changeStatus')->name('changeStatus');
            Route::post('/eliminar', 'delete')->name('delete');
        });

    Route::controller(TipoAsistenciaController::class)
        ->prefix('mantenimiento-dev/tipo-asistencia')
        ->as('tipoasistencia.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::post('/registrar', 'create')->name('create');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/actualizar', 'update')->name('update');
            Route::post('/cambiar-estado', 'changeStatus')->name('changeStatus');
            Route::post('/eliminar', 'delete')->name('delete');
        });

    Route::controller(TipoPersonalController::class)
        ->prefix('mantenimiento-dev/tipo-personal')
        ->as('tipopersonal.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::post('/registrar', 'create')->name('create');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/actualizar', 'update')->name('update');
            Route::post('/cambiar-estado', 'changeStatus')->name('changeStatus');
            Route::post('/eliminar', 'delete')->name('delete');
        });

    Route::controller(InventarioVehicularController::class)
        ->prefix('inventario-vehicular')
        ->as('inventariovehicular.')
        ->group(function () {
            Route::get('/', 'view')->name('view');
            Route::get('/listar', 'listar')->name('listar');
            Route::post('/registrar', 'create')->name('create');
            Route::get('/mostrar/{id}', 'show')->name('show');
            Route::post('/actualizar', 'update')->name('update');
            Route::post('/asignar', 'asignar')->name('asignar');
        });

    Route::get('/notificaciones/listar', [NotificacionController::class, 'listar']);

    Route::post('/push/subscribe', [PushController::class, 'subscribe']);
});

Route::get('/previsualizar-pdf/movil', [MediaArchivoController::class, 'previewPdfMovil']);

Route::get('/push/test/{id}', [PushController::class, 'test']);
Route::get('/delete-s3', [MediaArchivoController::class, 'deleteFile']);

Route::get('/picker', function () {
    return view('picker');
});