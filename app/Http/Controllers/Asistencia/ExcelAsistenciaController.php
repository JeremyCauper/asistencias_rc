<?php

namespace App\Http\Controllers\Asistencia;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\In;

class ExcelAsistenciaController extends Controller
{
    public function listarAsistenciasMensual(Request $request)
    {
        $mes = $request->query('mes');
        $fechaIni = $request->query('fechaIni');
        $fechaFin = $request->query('fechaFin');
        $tipoArea = $request->query('tipoArea', null)
            ? explode(',', $request->query('tipoArea'))
            : null;

        if ($mes && !$fechaIni && !$fechaFin) {
            $fechaIni = date('Y-m-01', strtotime($mes));
            $fechaFin = date('Y-m-t', strtotime($fechaIni));
        }

        if ($fechaIni && $fechaFin && !$mes) {
            $fechaIni = date('Y-m-d', strtotime($fechaIni));
            $fechaFin = date('Y-m-d', strtotime($fechaFin));
        }

        if (!$fechaIni || !$fechaFin) {
            return response()->json([
                'error' => 'Debe enviar un parámetro',
            ], 400);
        }

        try {
            // Obtener todas las fechas del mes
            $fechasMes = [];
            $fechaTmp = strtotime($fechaIni);
            while ($fechaTmp <= strtotime($fechaFin)) {
                $diaSemana = strtolower(date('l', $fechaTmp));
                if ($diaSemana !== 'sunday') { // Excluir domingos
                    $fechasMes[] = date('Y-m-d', $fechaTmp);
                }
                $fechaTmp = strtotime('+1 day', $fechaTmp);
            }

            // Cargar feriados del mes (sector privado)
            $feriados = DB::table('feriados_privado_peru')
                ->select('mes', 'dia')
                ->get()
                ->map(function ($f) {
                    return ['feriado' => str_pad($f->mes, 2, '0', STR_PAD_LEFT) . '-' . str_pad($f->dia, 2, '0', STR_PAD_LEFT)];
                })
                ->keyBy("feriado");

            // Cargar datos base
            $personal = DB::table('personal')
                ->select('id', 'user_id', 'dni', 'nombre', 'apellido')
                ->where('estatus', 1)
                ->whereIn('estado_sync', [1, 2, 3])
                ->whereIn('area_id', $tipoArea)
                ->orderBy('apellido', 'asc')
                ->get()
                ->keyBy('user_id');

            $asistencias = DB::table('asistencias')
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()
                ->groupBy('user_id');

            $justificaciones = DB::table('justificaciones')
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()
                ->groupBy('user_id');

            $descuentos = DB::table('descuentos_asistencia')
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()
                ->groupBy('user_id');

            // Configuración de trabajo (lunes a sábado)
            $config = DB::table('config_trabajo_personal')
                ->select('user_id', 'lunes', 'martes', 'miercoles', 'jueves', 'viernes', 'sabado')
                ->get()
                ->keyBy('user_id');

            $horaLimiteTardanza = strtotime('08:30:59');
            $resultado = [];

            foreach ($personal as $user_id => $p) {
                $registro = [
                    // 'user_id' => $user_id,
                    'dni' => $p->dni,
                    'personal' => "{$p->apellido}, {$p->nombre}",
                ];

                $asistenciasUsuario = $asistencias->get($user_id, collect());
                $descuentosUsuario = $descuentos->get($user_id, collect());
                $justificacionUsuario = $justificaciones->get($user_id, collect());
                $configUsuario = $config->get($user_id);

                $totalAsistencias = 0;
                $totalPendientes = 0;
                $totalFaltas = 0;
                $totalPuntuales = 0;
                $totalJustificados = 0;
                $totalDerivados = 0;
                $totalTardanzas = 0;
                $totalDescuento = 0.0;

                foreach ($fechasMes as $fecha) {
                    $strAFecha = strtotime($fecha);
                    $asistenciaDia = $asistenciasUsuario->firstWhere('fecha', $fecha);
                    $descuentoDia = $descuentosUsuario->firstWhere('fecha', $fecha);
                    $justificacionDia = $justificacionUsuario->firstWhere('fecha', $fecha);
                    $campoDia = self::getDay($fecha);
                    $feriado = $feriados->get(date('m-d', $strAFecha));

                    // Si la fecha es feriado (y no domingo), marcamos tipo_asistencia = 6 (feriado)
                    if ($feriado && !$asistenciaDia) {
                        $registro[$fecha] = [
                            'entrada' => null,
                            'tipo_modalidad' => 5,
                            'tipo_asistencia' => 6, // Feriado
                        ];
                        continue;
                    }

                    if (!$asistenciaDia) {
                        $registro[$fecha] = [
                            'entrada' => null,
                            'tipo_modalidad' => 1,
                            'tipo_asistencia' => 5, // Feriado
                        ];
                        continue;
                    }

                    // Ver si el día es laborable según config
                    $tipo_modalidad = 5; // No aplica por defecto
                    if ($configUsuario && $campoDia && isset($configUsuario->$campoDia)) {
                        $tipo_modalidad = $configUsuario->$campoDia ?? 5;
                    }

                    $entrada = null;
                    $tipo_asistencia = 0; // No aplica por defecto

                    if ($asistenciaDia) {
                        $entrada = $asistenciaDia->entrada;
                        $tipo_modalidad = $asistenciaDia->tipo_modalidad;
                        $tipo_asistencia = match(true) {
                            $justificacionDia && $justificacionDia->estatus == 0 => 0,
                            default => $asistenciaDia->tipo_asistencia,
                        };
                    } else {
                        // Si no tiene registro pero debía asistir
                        if ($tipo_modalidad == 1) {
                            $tipo_asistencia = 1; // Falta
                        }
                    }

                    // Calcular totales
                    switch ($tipo_asistencia) {
                        case 0:
                            $totalPendientes++;
                            break;
                        case 1:
                            $totalFaltas++;
                            break;
                        case 2:
                            $totalPuntuales++;
                            break;
                        case 3:
                            $totalJustificados++;
                            break;
                        case 4:
                            $totalTardanzas++;
                            break;
                        case 7:
                            $totalDerivados++;
                            break;
                    }
                    
                    if ($descuentoDia && $tipo_asistencia != 0)
                        $totalDescuento += floatval($descuentoDia->monto_descuento);

                    $totalAsistencias++;

                    $registro[$fecha] = [
                        'entrada' => $entrada,
                        'tipo_modalidad' => $tipo_modalidad,
                        'tipo_asistencia' => $tipo_asistencia,
                    ];
                }

                $registro['total'] = $totalAsistencias;
                $registro['pendientes'] = $totalPendientes;
                $registro['faltas'] = $totalFaltas;
                $registro['puntuales'] = $totalPuntuales;
                $registro['justificados'] = $totalJustificados;
                $registro['derivados'] = $totalDerivados;
                $registro['tardanzas'] = $totalTardanzas;
                $registro['dscto_tardanza'] = round($totalDescuento, 2);

                $resultado[] = $registro;
            }

            return response()->json($resultado, 200);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getLine() . ' ' . $e->getMessage()
            ], 500);
        }
    }
}
