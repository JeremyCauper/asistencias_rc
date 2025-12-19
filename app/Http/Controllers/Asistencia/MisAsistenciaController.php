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
            $tipoModalidad = JsonDB::table('tipo_modalidad')->get();
            $tipoAsistencia = JsonDB::table('tipo_asistencia')->get();

            return view('asistencias.misasistencias', [
                'tipoModalidad' => $tipoModalidad,
                'tipoAsistencia' => $tipoAsistencia
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
            $user_id = Auth::user()->user_id;
            $fechaIni = date("Y-m-01", strtotime($fecha));
            $fechaFin = date("Y-m-t", strtotime($fecha));

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

            $tipoAsistencias = JsonDB::table('tipo_asistencia')->select('id', 'descripcion', 'color')->get()->keyBy('id');

            $listado = [];
            $asistencias = DB::table('asistencias')
                ->where('user_id', $user_id)
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()->toArray();


            foreach ($asistencias as $a) {
                $descuento = $descuentos->get($a->fecha) ?? null;
                $justificacion = $justificaciones->get($a->fecha) ?? null;
                $notificacion = false;
                $campoDia = $this->getDay($a->fecha);
                $tipo_asistencia = $a?->tipo_asistencia ?? 0;
                $tipo_modalidad = $a?->tipo_modalidad;
                $fechaActual = date($this->strFecha) == $a->fecha;
                $entrada = $a?->entrada;

                // Si aún no tiene registro pero debería asistir
                $previo = $a?->tipo_asistencia ?? 0;
                $just = $justificacion?->estatus ?? null;

                $tipo_asistencia = match (true) {
                    empty($entrada) && in_array($tipo_modalidad, [1]) && $previo == 1 && $this->horaActual < $this->limitePuntual && $fechaActual => 0,
                    empty($entrada) && in_array($tipo_modalidad, [2]) && $previo == 1 && $this->horaActual < $this->limiteRemoto && $fechaActual => 0,
                    !empty($justificacion) && $just === 10 && $this->horaActual < $this->limiteDerivado && $fechaActual => 0,
                    !empty($justificacion) && $just === 0 => 0,
                    default => $previo,
                };

                // Acciones dinámicas
                $acciones = [];

                // Si es un tipo de asistencia que puede ser justificado, no tiene justificación aún y es el día actual
                if (!empty($a) && !empty($justificacion) && $justificacion?->estatus == 10 && $this->horaActual < $this->limiteDerivado && $fechaActual) {
                    $acciones[] = [
                        'funcion' => "justificarDerivado({$a->id})",
                        'texto' => '<i class="fas fa-comments me-2" style="color: ' . $tipoAsistencias->get(7)->color . ';"></i>Justificar Derivado'
                    ];
                    $notificacion = true; // notificar solo si es tipo 7 (derivado)
                }

                // Si es un tipo de asistencia que puede ser justificado, no tiene justificación aún y es el día actual
                if (
                    !empty($a) && 
                    empty($justificacion) &&
                    in_array($tipo_asistencia, [0]) &&
                    $tipo_modalidad == 2 &&
                    $fechaActual
                ) {
                    $acciones[] = [
                        'funcion' => "justificarAsistencia({$a->id})",
                        'texto' => '<i class="fas fa-comment-dots me-2 text-success"></i>Justificar Remoto'
                    ];
                }

                if (
                    !empty($a) && 
                    in_array($tipo_asistencia, [1, 4]) &&
                    (
                        !$justificacion && $this->horaActual > $this->limitePuntual ||
                        $justificacion && $justificacion?->estatus == 10 && $this->horaActual > $this->limiteDerivado
                    ) &&
                    $fechaActual
                ) {
                    $tipoAsistencia = $tipoAsistencias->get($a->tipo_asistencia);
                    $acciones[] = [
                        'funcion' => "justificarAsistencia({$a->id})",
                        'texto' => '<i class="fas fa-comment-dots me-2" style="color: ' . $tipoAsistencia->color . ';"></i>Justificar ' . $tipoAsistencia->descripcion
                    ];
                }

                if (!empty($a) && in_array($tipo_asistencia, [0]) && !$justificacion && $this->horaActual < $this->limitePuntual && $fechaActual) {
                    $acciones[] = [
                        'funcion' => "justificarAsistencia({$a->id})",
                        'texto' => '<i class="fas fa-comment-dots me-2"></i>Justificar'
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
                        'funcion' => "verJustificacion({$a->id})",
                        'texto' => '<i class="fas fa-comment-dots me-2 text-' . $tJustificacion['color'] . '"></i> Justificación ' . $tJustificacion['text']
                    ];
                }

                $badgeTitle = $tipoAsistencias->get($tipo_asistencia) ?? (object) ['color' => '#717883', 'descripcion' => 'Pendiente'];

                $listado[] = [
                    'jornada' => $campoDia,
                    'fecha' => $a->fecha,
                    'entrada' => $entrada,
                    'salida' => $a?->salida ?? null,
                    'tipo_modalidad' => $tipo_modalidad,
                    'tipo_asistencia' => $tipo_asistencia,
                    'descuento' => $descuento?->monto_descuento ?? null,
                    'acciones' => $this->DropdownAcciones([
                        'tittle' => '<label class="badge" style="background-color: ' . $badgeTitle->color . '">' . $badgeTitle->descripcion . '</label>',
                        'button' => $acciones
                    ], $notificacion)
                ];
            }

            return ApiResponse::success(data: ['listado' => $listado]);
        } catch (Exception $e) {
            Log::error('[MisAsistenciaController@listar] ' . $e->getMessage());
            return ApiResponse::error(message: 'No se pudo obtener el listado.');
        }
    }
}
