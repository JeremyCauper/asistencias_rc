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

            $campoDia = $this->getDay($fecha);
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
                } else if ($tipoModalidad == 3) {
                    $tipoAsistencia = 2;
                } else if ($tipoModalidad == 4) {
                    $tipoAsistencia = 3;
                }

                DB::table('asistencias')->insert([
                    'user_id' => $per->user_id,
                    'fecha' => $fecha,
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
                    $fecha = $a['fecha'];
                    $hora = $a['hora'];
                    $horaMarcada = strtotime($hora);
                    $puntual = $horaMarcada <= $limitePuntual;
                    $descuento = false;

                    $asistencia = DB::table('asistencias')->where(['user_id' => $userId, 'fecha' => $fecha])->first();
                    $id_asistencia = $asistencia?->id ?? null;

                    $payloadBase = [
                        'tipo_modalidad' => 1,
                        'ip' => $a['ip'],
                        'sincronizado' => 1,
                    ];
                    /*---------------------------------------------------------
                    | CASO A: No existe asistencia previa (primera marca)
                    ---------------------------------------------------------*/
                    if (!$asistencia) {
                        $id_asistencia = DB::table('asistencias')->insertGetId(array_merge($payloadBase, [
                            'user_id' => $userId,
                            'fecha' => $fecha,
                            'entrada' => $hora,
                            'tipo_asistencia' => $puntual ? 2 : 4,
                        ]));

                        $descuento = !$puntual;
                    } else {
                        if ($asistencia->tipo_asistencia != 8) {
                            /*---------------------------------------------------------
                            | CASO B: Ya existe asistencia, evaluar salida/entrada
                            ---------------------------------------------------------*/
                            $entradaMarcada = strtotime($asistencia->entrada);
                            $transcurrido = $horaMarcada - $entradaMarcada;

                            // Solo registra salida/entrada si pasaron más de 10 minutos
                            if ($transcurrido > 600) {
                                $jornada = 'salida';
                                // Si por alguna razón entrada está vacía, la registra
                                if (empty($asistencia->entrada)) {
                                    $jornada = 'entrada';

                                    if ($asistencia->tipo_asistencia != 7) {
                                        $tipo = $puntual ? 2 : 4;
                                        $payloadBase['tipo_asistencia'] = $tipo;

                                        $descuento = !$puntual;
                                    }
                                }

                                DB::table('asistencias')->where('id', $id_asistencia)
                                    ->update(array_merge($payloadBase, [
                                        $jornada => $hora,
                                    ]));
                            }
                        }
                    }

                    /*---------------------------------------------------------
                    | Registrar descuento si corresponde
                    ---------------------------------------------------------*/
                    if ($descuento) {
                        DB::table('descuentos_asistencia')->insert([
                            'asistencia_id' => $id_asistencia,
                            'user_id' => $userId,
                            'fecha' => $fecha,
                        ]);
                    }

                    $a['sincronizado'] = 1;
                    $sincronizadas[] = $a;
                }

                DB::commit();
            }

            return response()->json([
                'success' => true,
                'sincronizadas' => $sincronizadas
            ]);
        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
