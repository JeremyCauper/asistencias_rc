<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Syncs\SyncAsistenciasController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CrearAsistencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asistencias:crearPorDia';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear asistencias para el dia actual';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Creando asistencias para el dia actual');
        $result = SyncAsistenciasController::crearAsistenciasPorDia();
        Log::info('Se insertaron ' . $result['insertados'] . ' registros para ' . $result['fecha']);
    }
}
