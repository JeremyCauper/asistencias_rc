<?php

use App\Http\Controllers\Api\Syncs\SyncAsistenciasController;
use App\Http\Controllers\Api\Syncs\SyncPersonalController;
use App\Http\Controllers\Asistencia\AsistenciaController;
use App\Http\Controllers\Asistencia\ExcelAsistenciaController;
use App\Http\Controllers\Asistencia\MisAsistenciaController;
use App\Http\Controllers\Justificacion\JustificacionController;
use App\Http\Controllers\Mantenimientos\AreaPersonal\AreaPersonalController;
use App\Http\Controllers\MantenimientosDeveloper\Menu\MenuController;
use App\Http\Controllers\MantenimientosDeveloper\TipoPersonal\TipoPersonalController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Login\LoginController;
use App\Http\Controllers\Personal\PersonalController;
use App\Http\Controllers\MantenimientosDeveloper\Menu\SubMenuController;
use App\Http\Controllers\MantenimientosDeveloper\TipoAsistencia\TipoAsistenciaController;
use App\Http\Controllers\MantenimientosDeveloper\TipoModalidad\TipoModalidadController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/inicio');
});

Route::get('/inicio', [LoginController::class, 'view'])->name('login');
Route::post('/iniciar', [LoginController::class, 'login']);
Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::post('/personal/actualizar-password', [LoginController::class, 'actualizarPassword']);

    Route::get('/personal/personal', [SyncPersonalController::class, 'view']);
    Route::get('/personal/listar', [SyncPersonalController::class, 'listar']);
    Route::get('/personal/{id}', [SyncPersonalController::class, 'show']);
    Route::post('/personal', [SyncPersonalController::class, 'store']);
    Route::put('/personal/{id}', [SyncPersonalController::class, 'update']);
    Route::post('/personal/cambiarEstatus', [SyncPersonalController::class, 'cambiarEstatus']);
    Route::delete('/personal/{id}', [SyncPersonalController::class, 'marcarEliminar']);

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
    Route::post('/asistencias/uploadMedia', [MisAsistenciaController::class, 'uploadMedia']);

    Route::controller(JustificacionController::class)
        ->prefix('justificacion')
        ->as('justificacion.')
        ->group(function () {
            Route::get('/mostrar/{id}', 'showJustificacion')->name('showJustificacion');
            Route::post('/justificar', 'storeJustificacion')->name('justificar');
            Route::post('/responder-justificacion', 'responseJustificacion')->name('responseJustificacion');
            Route::put('/marcar-derivado/{id}', 'marcarDerivado')->name('marcarDerivado');
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
});

Route::get('/obtener_modulos/{tipo}/{accesso}', [Controller::class, 'obtenerModulos']);
Route::get('/obtener_modulos2/{tipo}/{accesso}', [Controller::class, 'obtenerModulos2']);
