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

        $contratos = DB::table('contratos')
            ->where('estatus', '!=', 0)
            ->where('estatus', '!=', 3)
            ->get();

        foreach ($contratos as $contrato) {
            $fechaFin = Carbon::parse($contrato->fecha_fin);
            $estatus = 1; // VIGENTE
            $enviadaNotificacion = false;

            if ($fechaFin->isBefore($hoy)) {
                $estatus = 3; // VENCIDO
                $enviadaNotificacion = true;
            } elseif ($fechaFin->between($hoy, $limite)) {
                $estatus = 2; // POR VENCER
                $enviadaNotificacion = $estatus !== $contrato->estatus;
            }

            if ($enviadaNotificacion) {
                $userId = $contrato->user_id;
                $tipoNotificacion = ($estatus == 2) ? 5 : 6; // 5: POR VENCER, 6: VENCIDO

                NotificacionController::crear([
                    'tipo_notificacion' => 1,
                    'asignado_id' => $contrato->id,
                    'user_id' => $userId,
                    'is_admin' => 1,
                    'titulo_id' => $tipoNotificacion,
                    'descripcion_id' => $tipoNotificacion,
                    'ruta_id' => 3,
                    'accion_id' => 3,
                    'payload_accion' => $userId,
                ]);
            }

            if ($estatus !== 1) {
                $user_id = $contrato->user_id;
                DB::table('contratos')
                    ->where('id', $contrato->id)
                    ->update(['estatus' => $estatus]);
            }
        }

        $this->info('Estatus de contratos actualizado correctamente.');
    }
}
