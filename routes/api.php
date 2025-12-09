<?php

use App\Http\Controllers\Api\Syncs\SyncAsistenciasController;
use App\Http\Controllers\Api\Syncs\SyncPersonalController;
use App\Http\Controllers\Asistencia\AsistenciaController;
use App\Http\Controllers\Asistencia\ExcelAsistenciaController;
use App\Http\Controllers\Asistencia\MisAsistenciaController;
use App\Http\Controllers\ConsultasController;
use App\Http\Controllers\MantenimientosDeveloper\TipoPersonal\TipoPersonalController;
use App\Http\Controllers\Personal\PersonalController;
use App\Http\Controllers\MantenimientosDeveloper\Menu\MenuController;
use App\Http\Controllers\MantenimientosDeveloper\Menu\SubMenuController;
use App\Http\Controllers\MantenimientosDeveloper\TipoAsistencia\TipoAsistenciaController;
use App\Http\Controllers\MantenimientosDeveloper\TipoModalidad\TipoModalidadController;
use App\Http\Controllers\PushController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/ConsultaDoc', [ConsultasController::class, 'ConsultaDoc']);

Route::get('/personal/pendientes', [SyncPersonalController::class, 'pendientes']);
Route::put('/personal/{id}/estado', [SyncPersonalController::class, 'actualizarEstado']);

Route::post('/asistencias/sincronizar', [SyncAsistenciasController::class, 'sincronizar']);
Route::get('/asistencias/crearAsistenciasPorDia', [SyncAsistenciasController::class, 'crearAsistenciasPorDia']);

Route::get('/asistencias/exportar-mensual', [ExcelAsistenciaController::class, 'listarAsistenciasMensual']);