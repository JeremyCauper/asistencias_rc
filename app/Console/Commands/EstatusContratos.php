<?php

namespace App\Console\Commands;

use App\Http\Controllers\NotificacionController;
use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;

class EstatusContratos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contratos:estatus';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualiza el estatus de los contratos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Actualizando estatus de contratos...');
        $hoy = Carbon::today();
        $limite = $hoy->copy()->addDays(10);

        /*// VENCIDOS
        DB::table('contratos')
            ->where('estatus', '!=', 0)
            ->where('estatus', '!=', 3)
            ->whereDate('fecha_fin', '<', $hoy)
            ->update(['estatus' => 3]);

        // POR VENCER
        DB::table('contratos')
            ->where('estatus', '!=', 0)
            ->where('estatus', '!=', 3)
            ->whereBetween('fecha_fin', [$hoy, $limite])
            ->update(['estatus' => 2]);*/

        $contratos = DB::table('contratos')
            ->where('estatus', '!=', 0)
            ->where('estatus', '!=', 3)
            ->get();

        foreach ($contratos as $contrato) {
            $estatus = 3;
            if ($limite->isBefore($contrato->fecha_fin)) {
                $estatus = 2;
                NotificacionController::crear([
                    'tipo_notificacion' => 1,
                    'asignado_id' => $contrato->contrato_id,
                    'user_id' => $contrato->user_id,
                    'is_admin' => 1,
                    'titulo_id' => $contrato->titulo_id,
                    'descripcion_id' => $contrato->descripcion_id,
                    'ruta_id' => 1,
                    'accion_id' => 2,
                    'payload_accion' => $contrato->contrato_id,
                ]);
            }

            DB::table('contratos')
                ->where('contrato_id', $contrato->contrato_id)
                ->update(['estatus' => $estatus]);
        }

        $this->info('Estatus de contratos actualizado correctamente.');
    }
}
