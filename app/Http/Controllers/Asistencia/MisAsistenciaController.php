<?php

namespace App\Http\Controllers\Asistencia;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\JsonDB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class MisAsistenciaController extends Controller
{
    public function view()
    {
        // $config_system = session('config_system')->get('horaLimitePuntua')?->values;
        // dd($config_system);
        // $this->validarPermisos(6, 14);
        try {
            $empresas = DB::table('empresa')->get();
            $tipoModalidad = JsonDB::table('tipo_modalidad')->get()->keyBy('id');
            $tipoAsistencia = JsonDB::table('tipo_asistencia')->get();
            $tipoPersonal = JsonDB::table('tipo_personal')->get()->keyBy('id');

            return view('asistencias.misasistencias', [
                'tipoModalidad' => $tipoModalidad,
                'tipoAsistencia' => $tipoAsistencia,
                'tipoPersonal' => $tipoPersonal,
                'empresas' => $empresas,
            ]); // la vista Blade (más abajo)
        } catch (Exception $e) {
            Log::error('[MisAsistenciaController@view] ' . $e->getMessage());
            return ApiResponse::error('Error al cargar la vista del módulo.', $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function listar(Request $request)
    {
        try {
            $fecha = $request->query('fecha', date('Y-m'));
            $user_id = session('user_id');
            $fechaIni = date("Y-m-01", strtotime($fecha));
            $fechaFin = date("Y-m-t", strtotime($fecha));
            // $fechaIni = date("Y-m-d");
            // $fechaFin = date("Y-m-d");

            $descuentos = DB::table('descuentos_asistencia')
                ->where('user_id', $user_id)
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()
                ->keyBy('fecha');

            $justificaciones = DB::table('justificaciones')
                ->where('user_id', $user_id)
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()
                ->keyBy('fecha');

            $tipoAsistencias = JsonDB::table('tipo_asistencia')->whereIn('id', [1, 4, 7])->get()->keyBy('id');
            $tipoModalidades = JsonDB::table('tipo_modalidad')->whereIn('id', [2])->get()->keyBy('id');

            $listado = [];
            $asistencias = DB::table('asistencias')
                ->where('user_id', $user_id)
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()->toArray();

            $limitePuntual = strtotime(date("Y-m-d " . $this->horaLimitePuntual));
            $limiteDerivado = strtotime(date("Y-m-d " . $this->horaLimiteDerivado));
            $horaActual = time();

            foreach ($asistencias as $a) {
                $diaSemana = strtolower(date('l', strtotime($a->fecha)));
                $descuento = $descuentos->get($a->fecha) ?? null;
                $justificacion = $justificaciones->get($a->fecha) ?? null;
                $notificacion = false;
                $campoDia = [
                    'monday' => 'Lunes',
                    'tuesday' => 'Martes',
                    'wednesday' => 'Miercoles',
                    'thursday' => 'Jueves',
                    'friday' => 'Viernes',
                    'saturday' => 'Sabado',
                    'sunday' => 'Domingo',
                ][$diaSemana] ?? null;
                $tipo_asistencia = $a?->tipo_asistencia ?? 0;
                $tipo_modalidad = $a?->tipo_modalidad;
                $fechaActual = date('Y-m-d') == $a->fecha;

                // Si aún no tiene registro pero debería asistir
                if (!$a->hora && in_array($tipo_modalidad, [1, 2]) && $tipo_asistencia == 1 && $horaActual < $limitePuntual && $fechaActual) {
                    $tipo_asistencia = 0;
                }

                if ($justificacion && $justificacion->estatus == 10 && $horaActual < $limiteDerivado && $fechaActual) {
                    $tipo_asistencia = 7;
                }

                // Acciones dinámicas
                $acciones = [];
                // Si es un tipo de asistencia que puede ser justificado, no tiene justificación aún y es el día actual
                if ($justificacion && $justificacion?->estatus == 10 && $tipo_asistencia == 7 && $fechaActual) {
                    $acciones[] = [
                        'funcion' => "justificarDerivado({$a->id})",
                        'texto' => '<i class="fas fa-scale-balanced me-2" style="color: ' . $tipoAsistencias->get(7)->color . ';"></i>Justificar Derivado'
                    ];
                    $notificacion = $tipo_asistencia == 7; // notificar solo si es tipo 7 (derivado)
                }

                // Si es un tipo de asistencia que puede ser justificado, no tiene justificación aún y es el día actual
                if ((!$justificacion && in_array($tipo_asistencia, [1, 4]) || $justificacion && $justificacion?->estatus == 10) && $horaActual > $limitePuntual && $fechaActual) {
                    $tipoAsistencia = $tipoAsistencias->get($a->tipo_asistencia);
                    $acciones[] = [
                        'funcion' => "justificarAsistencia({$a->id})",
                        'texto' => '<i class="fas fa-scale-balanced me-2" style="color: ' . $tipoAsistencia->color . ';"></i>Justificar ' . $tipoAsistencia->descripcion
                    ];
                }

                // Si es un tipo de asistencia que puede ser justificado, no tiene justificación aún y es el día actual
                if (!$justificacion && $tipo_asistencia == 0 && $tipo_modalidad == 2 && $horaActual < $limitePuntual && $fechaActual) {
                    $tipoModalidad = $tipoModalidades->get($tipo_modalidad);
                    $acciones[] = [
                        'funcion' => "justificarAsistencia({$a->id})",
                        'texto' => '<i class="fas fa-scale-balanced me-2" style="color: ' . $tipoModalidad->color . ';"></i>Justificar ' . $tipoModalidad->descripcion
                    ];
                }

                // Si ya tiene justificación, se puede obtener la justificación
                if ($justificacion && $justificacion?->estatus != 10) {
                    $tJustificacion = [
                        ['color' => 'secondary', 'text' => 'Pendiente'],
                        ['color' => 'success', 'text' => 'Aprobada'],
                        ['color' => 'danger', 'text' => 'Rechazada'],
                    ][$justificacion->estatus];

                    $acciones[] = [
                        'funcion' => "showJustificacion({$a->id})",
                        'texto' => '<i class="fas fa-clock me-2 text-' . $tJustificacion['color'] . '"></i> Justificación ' . $tJustificacion['text']
                    ];
                }

                $listado[] = [
                    'jornada' => $campoDia,
                    'fecha' => $a->fecha,
                    'hora' => $a->hora,
                    'tipo_modalidad' => $tipo_modalidad,
                    'tipo_asistencia' => $tipo_asistencia,
                    'descuento' => $descuento?->monto_descuento ?? null,
                    'acciones' => $this->DropdownAcciones(['button' => $acciones], $notificacion)
                ];
            }

            return ApiResponse::success(data: ['listado' => $listado]);
        } catch (Exception $e) {
            Log::error('[MisAsistenciaController@listar] ' . $e->getMessage());
            return ApiResponse::error(message: 'No se pudo obtener el listado.');
        }
    }
}
