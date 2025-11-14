<?php

namespace App\Http\Controllers\Api\Syncs;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncAsistenciasController extends Controller
{

    // Método que registra asistencias para remoto/permiso a las 08:30
    public function crearAsistenciasPorDia(Request $request)
    {
        try {
            $fecha = $request->query('fecha', date('Y-m-d'));
            $diaSemana = strtolower(date('l', strtotime($fecha))); // monday, tuesday, ...

            $mapDias = [
                'monday' => 'lunes',
                'tuesday' => 'martes',
                'wednesday' => 'miercoles',
                'thursday' => 'jueves',
                'friday' => 'viernes',
                'saturday' => 'sabado',
            ];

            $campoDia = $mapDias[$diaSemana] ?? null;
            $insertados = 0;
            $modalidad_trabajo = null;

            $feriados = DB::table('feriados_privado_peru')->where([
                'mes' => date('m', strtotime($fecha)),
                'dia' => date('d', strtotime($fecha)),
            ])->exists();

            $asistencias = DB::table('asistencias')->where('fecha', $fecha)->get()->keyBy('user_id');
            if ($campoDia)
                $modalidad_trabajo = DB::table('config_trabajo_personal')->select('user_id', "{$campoDia} as modo")->get()->keyBy('user_id');
            $personal = DB::table('personal')->where('estatus', 1)->get()->toArray();

            foreach ($personal as $per) {
                $existe = $asistencias->get($per->user_id);
                if ($existe)
                    continue;
                $tipoAsistencia = 1;
                $tipoModalidad = $modalidad_trabajo ? ($modalidad_trabajo->get($per->user_id))->modo : 5;

                if (!$campoDia || $feriados) {
                    $tipoAsistencia = $feriados ? 6 : 5;
                } else if ($tipoModalidad == 2 || $tipoModalidad == 3) {
                    $tipoAsistencia = 2;
                } else if ($tipoModalidad == 4) {
                    $tipoAsistencia = 3;
                }

                DB::table('asistencias')->insert([
                    'user_id' => $per->user_id,
                    'fecha' => $fecha,
                    'hora' => null,
                    'tipo_modalidad' => $tipoModalidad,
                    'tipo_asistencia' => $tipoAsistencia,
                    'sincronizado' => 1,
                    'created_at' => now(),
                ]);

                $insertados++;
            }

            Log::info("crearAsistenciasPorDia: se insertaron $insertados registros para $fecha.");
            return response()->json("se insertaron $insertados registros para $fecha.");
        } catch (\Throwable $e) {
            Log::error("crearAsistenciasPorDia ERROR: {$e->getLine()} " . $e->getMessage());
        }
    }

    public function sincronizar(Request $request)
    {
        try {
            $asistencias = $request->input('asistencias', []);
            $sincronizadas = [];
            $limitePuntual = strtotime("08:30:59");

            if (!empty($asistencias)) {
                DB::beginTransaction();
                foreach ($asistencias as $a) {
                    $userId = $a['deviceUserId'];
                    $recordTime = $a['recordTime'];
                    $strtoTime = strtotime($recordTime);
                    $fecha = date('Y-m-d', $strtoTime);
                    $hora = date('H:i:s', $strtoTime);
                    $horaMarcada = strtotime($hora);
                    $tipo_asistencia = 4;
                    $derivado = false;
                    if ($horaMarcada <= $limitePuntual)
                        $tipo_asistencia = 2;

                    $asistencia = DB::table('asistencias')->where(['user_id' => $userId, 'fecha' => $fecha])->first();
                    if ($asistencia)
                        $derivado = $asistencia->tipo_asistencia == 7;

                    if (!$derivado) {
                        DB::table('asistencias')->updateOrInsert(
                            ['user_id' => $userId, 'fecha' => $fecha],
                            [
                                'hora' => $hora,
                                'tipo_modalidad' => 1,
                                'tipo_asistencia' => $tipo_asistencia,
                                'ip' => $a['ip'],
                                'sincronizado' => 1,
                            ]
                        );

                        if ($tipo_asistencia == 4) {
                            DB::table('descuentos_asistencia')->insert(['user_id' => $userId, 'fecha' => $fecha]);
                        }
                    }
                    $sincronizadas[] = $a;
                }
                DB::commit();
            }

            return response()->json(['success' => true, 'sincronizadas' => $sincronizadas]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
