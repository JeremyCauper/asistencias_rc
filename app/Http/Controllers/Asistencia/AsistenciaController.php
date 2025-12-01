<?php

namespace App\Http\Controllers\Asistencia;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\JsonDB;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AsistenciaController extends Controller
{
    public function view()
    {
        try {
            $empresas = DB::table('empresa')->get();
            $areas = DB::table('areas_personal')->get();
            $tipoModalidad = JsonDB::table('tipo_modalidad')->get();
            $tipoAsistencia = JsonDB::table('tipo_asistencia')->get();
            $tipoPersonal = JsonDB::table('tipo_personal')->get();

            if (in_array(Auth::user()->rol_system, [5, 6])) {
                $areas = $areas->where('id', Auth::user()->area_id)->values();
            }

            return view('asistencias.asistencias', [
                'tipoModalidad' => $tipoModalidad,
                'tipoAsistencia' => $tipoAsistencia,
                'tipoPersonal' => $tipoPersonal,
                'empresas' => $empresas,
                'areas' => $areas
            ]); // la vista Blade (más abajo)
        } catch (Exception $e) {
            Log::error('[AsistenciaController@view] ' . $e->getMessage());
            return ApiResponse::error('Error al cargar la vista del módulo.', $e->getMessage());
        }
    }

    public function listar(Request $request)
    {
        try {
            $fecha = $request->query('fecha', date('Y-m-d'));
            $empresas = $request->query('empresas', null);
            $tipoModalidad = $request->query('tipoModalidad', null)
                ? explode(',', $request->query('tipoModalidad'))
                : null;
            $tipoPersonal = $request->query('tipoPersonal', null)
                ? explode(',', $request->query('tipoPersonal'))
                : null;
            $tipoArea = $request->query('tipoArea', null)
                ? explode(',', $request->query('tipoArea'))
                : null;
            $listado = [];

            $isAdmin = in_array(Auth::user()->rol_system, [2, 4, 7]);
            $isJefatura = in_array(Auth::user()->rol_system, [5]);
            $isSystem = Auth::user()->sistema == 1;

            $wherePersonal = ['estatus' => 1];
            if ($empresas) {
                $wherePersonal['empresa_ruc'] = $empresas;
            }

            if (!$isSystem && in_array(Auth::user()->rol_system, [5, 6])) {
                $wherePersonal['area_id'] = Auth::user()->area_id;
            }

            $strtoTime = strtotime($fecha);
            $campoDia = $this->getDay($fecha);

            // Cargar datos en memoria (una sola vez)
            $feriado = DB::table('feriados_privado_peru')
                ->select('nombre', 'tipo')
                ->where(['mes' => date('m', $strtoTime), 'dia' => date('d', $strtoTime)])
                ->first();

            if (!empty($tipoModalidad) && !empty($tipoPersonal)) {
                $asistencias = DB::table('asistencias')
                    ->where('fecha', $fecha)
                    ->get()
                    ->keyBy('user_id');

                $descuentos = DB::table('descuentos_asistencia')
                    ->where('fecha', $fecha)
                    ->get()
                    ->keyBy('user_id');

                $justificaciones = DB::table('justificaciones')
                    ->where('fecha', $fecha)
                    ->get()
                    ->keyBy('user_id');

                $modalidades = $campoDia
                    ? DB::table('config_trabajo_personal')
                        ->select('user_id', "$campoDia as modo")
                        ->get()
                        ->keyBy('user_id')
                    : collect();

                $personal = DB::table('personal')
                    ->select('id', 'user_id', 'nombre', 'apellido', 'area_id', 'rol_system')
                    ->where($wherePersonal)
                    ->whereIn('estado_sync', [1, 2, 3])
                    ->whereIn('rol_system', $tipoPersonal)
                    ->whereIn('area_id', $tipoArea)
                    ->get()->toArray();

                $tipoAsistencias = JsonDB::table('tipo_asistencia')->whereIn('id', [1, 4, 7])->get()->keyBy('id');

                $fechaActual = date('Y-m-d') == $fecha;
                $mesActual = date('Y-m') == date('Y-m', $strtoTime);

                foreach ($personal as $p) {
                    if (!$isAdmin && $p->user_id == Auth::user()->user_id && !$isSystem) {
                        continue;
                    }

                    $asistencia = $asistencias->get($p->user_id);
                    $modalidad = $modalidades->get($p->user_id);
                    $tipo_modalidad = $asistencia?->tipo_modalidad
                        ?? $modalidad?->modo
                        ?? 5;

                    if ($tipoModalidad && !in_array($tipo_modalidad, $tipoModalidad)) {
                        continue;
                    }

                    $descuento = $descuentos->get($p->user_id) ?? null;
                    $justificacion = $justificaciones->get($p->user_id) ?? null;

                    $notificacion = false;
                    $hora = $asistencia?->hora ?? null;
                    $tipo_asistencia = $asistencia?->tipo_asistencia ?? 0;
                    $asistencia_id = $asistencia?->id ?? null;

                    // Si aún no tiene registro pero debería asistir
                    if ((!$hora || $hora) && in_array($tipo_modalidad, [1, 2]) && $tipo_asistencia == 1 && $this->horaActual < $this->limitePuntual && $fechaActual) {
                        $tipo_asistencia = 0;
                    }

                    if ($justificacion && in_array($justificacion->estatus, [0, 10]) && $this->horaActual < $this->limiteDerivado && $fechaActual) {
                        $tipo_asistencia = 7;
                    }

                    // Acciones dinámicas
                    $acciones = [];
                    // Solo si tine id de asistencia y permisos adecuados por tipo de usuario o sistema y mes actual
                    if ($asistencia_id && $mesActual && ($isAdmin || $isSystem)) {
                        $acciones[] = [
                            'funcion' => "modificarDescuento($asistencia_id)",
                            'texto' => '<i class="fas fa-file-invoice-dollar me-2 text-secondary"></i> ' .
                                ($descuento ? 'Modificar' : 'Aplicar') . ' Descuento'
                        ];
                    }

                    // Derivar asistencia solo si es tipo asistencia 0: pendiente o 1: falta, antes de las 10:00 y es para la fecha actual
                    if ($asistencia_id && in_array($tipo_asistencia, [0, 1]) && !$justificacion && $this->horaActual < $this->limiteDerivado && $fechaActual) {
                        $acciones[] = [
                            'funcion' => "marcarDerivado($asistencia_id)",
                            'texto' => '<i class="fas fa-random me-2 text-info"></i> Derivar'
                        ];
                    }

                    // Permite ver justificación si existe y está pendiente, si se cumple la condición envia notificación
                    if ($asistencia_id && $justificacion && $justificacion->estatus != 10) {
                        $tJustificacion = [
                            ['color' => 'secondary', 'text' => 'Pendiente'],
                            ['color' => 'success', 'text' => 'Aprobada'],
                            ['color' => 'danger', 'text' => 'Rechazada'],
                        ][$justificacion->estatus];

                        $acciones[] = [
                            'funcion' => "verJustificacion($asistencia_id)",
                            'texto' => '<i class="fas fa-clock me-2 text-' . $tJustificacion['color'] . '"></i> Justificación ' . $tJustificacion['text']
                        ];
                        $notificacion = $justificacion->estatus == 0;
                    }

                    if (
                        $asistencia_id &&
                        in_array($tipo_asistencia, [1, 4]) &&
                        $mesActual &&
                        ($isAdmin || $isJefatura || $isSystem) ||
                        ($justificacion && $justificacion->estatus == 10 && $this->horaActual > $this->limiteDerivado)
                    ) {
                        $tipoAsistencia = $tipoAsistencias->get($tipo_asistencia);
                        $acciones[] = [
                            'funcion' => "justificarAsistencia({$asistencia_id}, {$p->user_id}, '{$fecha}', '{$hora}', {$tipo_asistencia})",
                            'texto' => '<i class="fas fa-scale-balanced me-2" style="color: ' . $tipoAsistencia->color . ';"></i>Justificar ' . $tipoAsistencia->descripcion
                        ];
                    }

                    $listado[] = [
                        'tipo_personal' => $p->rol_system,
                        'area' => $p->area_id,
                        'personal' => "{$p->apellido}, {$p->nombre}",
                        'fecha' => $fecha,
                        'hora' => $hora,
                        'tipo_modalidad' => $tipo_modalidad,
                        'tipo_asistencia' => $tipo_asistencia,
                        'justificacion' => $justificacion?->estatus ?? null,
                        'notificacion' => $notificacion,
                        'descuento' => $descuento?->monto_descuento ?? null,
                        'acciones' => $this->DropdownAcciones(['button' => $acciones], $notificacion)
                    ];
                }
            }

            return ApiResponse::success('Listado obtenido correctamente.', ['listado' => $listado, 'feriado' => $feriado]);
        } catch (Exception $e) {
            Log::error('[AsistenciaController@listarAsistencias] ' . $e->getMessage());
            return ApiResponse::error('No se pudo obtener el listado.');
        }
    }

    /**
     * Obtiene la información detallada de una asistencia.
     */
    public function show($id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return ApiResponse::badRequest('El ID proporcionado no es válido.');
            }

            // Buscar la asistencia
            $asistencia = DB::table('asistencias')->where('id', $id)->first();
            if (!$asistencia) {
                return ApiResponse::notFound('No se encontró la asistencia solicitada.');
            }

            // Obtener datos relacionados
            $personal = DB::table('personal')
                ->where('user_id', $asistencia->user_id)
                ->select('dni', 'nombre', 'apellido')
                ->first();

            $descuento = DB::table('descuentos_asistencia')
                ->where([
                    'user_id' => $asistencia->user_id,
                    'fecha' => $asistencia->fecha
                ])
                ->first();

            $justificacion = DB::table('justificaciones')
                ->where([
                    'user_id' => $asistencia->user_id,
                    'fecha' => $asistencia->fecha
                ])
                ->first();

            $archivos = DB::table('media_archivos')
                ->select('nombre_archivo', 'path_archivo', 'url_publica', 'estatus')
                ->where('asistencia_id', $asistencia->id)
                ->get();

            $tipo_asistencia = $asistencia?->tipo_asistencia;
            $fechaActual = date('Y-m-d') == $asistencia->fecha;

            if (
                !$asistencia->hora &&
                in_array($asistencia->tipo_modalidad, [1, 2]) &&
                $tipo_asistencia == 1 &&
                $this->horaActual < $this->limitePuntual &&
                $fechaActual
            ) {
                $tipo_asistencia = 0;
            }

            if ($justificacion && $justificacion->estatus == 10 && $this->horaActual < $this->limiteDerivado) {
                $tipo_asistencia = 7;
            }

            // Agregar los datos adicionales a la respuesta
            $detalle = [
                'id' => $asistencia->id,
                'user_id' => $asistencia->user_id,
                'fecha' => $asistencia->fecha,
                'hora' => $asistencia->hora,
                'tipo_modalidad' => $asistencia->tipo_modalidad,
                'tipo_asistencia' => $tipo_asistencia,
                'personal' => $personal,
                'descuento' => $descuento,
                'justificacion' => $justificacion,
                'archivos' => $archivos,
                'is_derivado' => $this->horaActual < $this->limiteDerivado
            ];

            return ApiResponse::success('Consulta exitosa.', $detalle);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('No se encontró el registro solicitado.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@showAsistencia] ' . $e->getMessage());
            return ApiResponse::error('Error al obtener el registro.');
        }
    }

    public function ingresarDescuento(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validación de los datos de entrada
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'fecha' => 'required|string',
                'monto_descuento' => 'required|numeric',
                'comentario' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray());
            }

            // Insertar o actualizar si ya existe un descuento para ese usuario y fecha
            DB::table('descuentos_asistencia')->updateOrInsert(
                ['user_id' => $request->user_id, 'fecha' => $request->fecha],
                [
                    'monto_descuento' => $request->monto_descuento,
                    'comentario' => $request->comentario,
                ]
            );

            DB::commit();

            return ApiResponse::success('Descuento actualizado correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[AsistenciaController@ingresarDescuento] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el descuento.');
        }
    }

    public function marcarDerivado(int $id)
    {
        try {
            $asistencia = DB::table('asistencias')->where('id', $id)->first();
            if (!$asistencia) {
                return ApiResponse::error('Asistencia no encontrada.');
            }

            if ($asistencia->tipo_asistencia == 7) {
                return ApiResponse::error('Esta asistencia ya fue marcada como derivada.');
            }

            DB::table('asistencias')
                ->where('id', $id)
                ->update(['tipo_asistencia' => 7]);

            return ApiResponse::success('El estado se cambió correctamente.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@marcarDerivado] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el estado.');
        }
    }
}
