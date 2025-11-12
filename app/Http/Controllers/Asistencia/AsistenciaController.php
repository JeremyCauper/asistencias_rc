<?php

namespace App\Http\Controllers\Asistencia;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\JsonDB;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AsistenciaController extends Controller
{
    public function view()
    {
        $empresas = DB::table('empresa')->get();
        $areas = DB::table('areas_personal')->get();
        $tipoModalidad = JsonDB::table('tipo_modalidad')->get()->keyBy('id');
        $tipoAsistencia = JsonDB::table('tipo_asistencia')->get();
        $tipoPersonal = JsonDB::table('tipo_personal')->get()->keyBy('id');

        $path = public_path('front/images/excel/leyenda_asistencia.png');
        $imgData = base64_encode(file_get_contents($path));
        $mimeType = mime_content_type($path);
        $logoExcelLeyenda = "data:$mimeType;base64,$imgData";

        return view('asistencias.asistencias', [
            'tipoModalidad' => $tipoModalidad,
            'tipoAsistencia' => $tipoAsistencia,
            'tipoPersonal' => $tipoPersonal,
            'empresas' => $empresas,
            'areas' => $areas,
            'logoExcelLeyenda' => $logoExcelLeyenda
        ]); // la vista Blade (más abajo)
    }

    public function listarAsistencias(Request $request)
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

            $wherePersonal = ['estatus' => 1];
            if ($empresas) {
                $wherePersonal['empresa_ruc'] = $empresas;
            }

            if (Auth::user()->sistema === 0 && in_array(Auth::user()->rol_system, [5, 6])) {
                $wherePersonal['area_id'] = Auth::user()->area_id;
            }

            $strtoTime = strtotime($fecha);
            $diaSemana = strtolower(date('l', $strtoTime));

            $mapDias = [
                'monday' => 'lunes',
                'tuesday' => 'martes',
                'wednesday' => 'miercoles',
                'thursday' => 'jueves',
                'friday' => 'viernes',
                'saturday' => 'sabado',
                'sunday' => null,
            ];

            $campoDia = $mapDias[$diaSemana] ?? null;
            $limitePuntual = strtotime("$fecha 08:30:59");

            // Cargar datos en memoria (una sola vez)
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
                ->get();

            $horaActual = time();

            $resultado = $personal->map(function ($p) use (
                $fecha,
                $asistencias,
                $descuentos,
                $justificaciones,
                $modalidades,
                $limitePuntual,
                $horaActual
            ) {
                $asistencia = $asistencias->get($p->user_id);
                $modalidad = $modalidades->get($p->user_id);
                $descuentoData = $descuentos->get($p->user_id);
                $justificacionData = $justificaciones->get($p->user_id) ?? null;

                $tipo_modalidad = 1;
                $tipo_asistencia = 0;
                $hora = null;
                $asistencia_id = null;
                $descuento = null;
                $justificado = null;

                if ($asistencia) {
                    $asistencia_id = $asistencia->id;
                    $hora = $asistencia->hora;
                    $tipo_modalidad = $asistencia->tipo_modalidad;
                    $tipo_asistencia = $asistencia->tipo_asistencia;
                } elseif ($modalidad) {
                    $tipo_modalidad = $modalidad->modo ?? 5;
                }

                // Si aún no tiene registro pero debería asistir
                if (!$hora && $tipo_modalidad == 1 && $tipo_asistencia == 1 && $horaActual < $limitePuntual) {
                    $tipo_asistencia = 0;
                }

                if ($justificacionData) {
                    $justificado = $justificacionData->estatus;
                }

                if ($descuentoData) {
                    $descuento = $descuentoData->monto_descuento;
                }

                // Acciones dinámicas
                $acciones = [];
                if ($asistencia_id && in_array(Auth::user()->rol_system, [2, 7]) || Auth::user()->sistema == 1) {
                    $acciones[] = [
                        'funcion' => "modificarDescuento($asistencia_id)",
                        'texto' => '<i class="fas fa-file-invoice-dollar me-2 text-secondary"></i> ' .
                            ($descuento ? 'Modificar' : 'Aplicar') . ' Descuento'
                    ];
                }

                if ($justificado !== null) {
                    $acciones[] = [
                        'funcion' => "verJustificacion($asistencia_id)",
                        'texto' => '<i class="fas fa-clock text-warning me-2 text-secondary"></i> Ver Justificación'
                    ];
                }

                // if ($tipo_asistencia == 1 && $fecha == date('Y-m-d')) {
                if ($tipo_asistencia == 1) {
                    $acciones[] = [
                        'funcion' => "marcarDerivado($asistencia_id)",
                        'texto' => '<i class="fas fa-random me-2 text-info"></i> Derivar'
                    ];
                }

                return [
                    'tipo_personal' => $p->rol_system,
                    'area' => $p->area_id,
                    'personal' => "{$p->apellido}, {$p->nombre}",
                    'fecha' => $fecha,
                    'hora' => $hora,
                    'tipo_modalidad' => $tipo_modalidad,
                    'tipo_asistencia' => $tipo_asistencia,
                    'justificado' => $justificado,
                    'descuento' => $descuento,
                    'acciones' => $this->DropdownAcciones(['button' => $acciones], $justificado === 0 ? true : false)
                ];
            })
                ->whereIn('tipo_personal', $tipoPersonal)
                ->whereIn('tipo_modalidad', $tipoModalidad)->values();

            return response()->json($resultado, 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getLine() . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene la información detallada de una asistencia.
     */
    public function showAsistencia($id)
    {
        try {
            // Validar que el ID sea numérico
            if (!is_numeric($id)) {
                return response()->json([
                    'message' => 'El ID proporcionado no es válido.'
                ], 400);
            }

            // Buscar la asistencia
            $asistencia = DB::table('asistencias')->where('id', $id)->first();

            if (!$asistencia) {
                return response()->json([
                    'message' => 'Asistencia no encontrada.'
                ], 404);
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

            // Agregar los datos adicionales a la respuesta
            $detalle = [
                'id' => $asistencia->id,
                'user_id' => $asistencia->user_id,
                'fecha' => $asistencia->fecha,
                'hora' => $asistencia->hora,
                'tipo_modalidad' => $asistencia->tipo_modalidad,
                'tipo_asistencia' => $asistencia->tipo_asistencia,
                'personal' => $personal,
                'descuento' => $descuento,
                'justificacion' => $justificacion
            ];

            return response()->json([
                'message' => 'Consulta exitosa.',
                'data' => $detalle
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'No se encontró el registro solicitado.'
            ], 404);
        } catch (Exception $e) {
            // Registrar el error para debug
            Log::error('Error al obtener asistencia: ' . $e->getMessage(), [
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'message' => 'Ocurrió un error al procesar la solicitud.',
                'error' => $e->getMessage() // opcional: puedes quitarlo en producción
            ], 500);
        }
    }

    public function actualizarDescuento(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validación de los datos de entrada
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'fecha'      => 'required|string',
                'monto_descuento'      => 'required|numeric',
                'comentario'      => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Por favor, revisa los campos e intenta nuevamente.',
                    'errors'  => $validator->errors()
                ], 400);
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

            return response()->json([
                'success' => true,
                'message' => 'Descuento actualizado correctamente.'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el descuento.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function actualizarEstatus(Request $request, int $id)
    {
        try {
            $validated = $request->validate([
                'estatus'       => 'required|in:1,2', // 1=aprobado, 2=rechazado
                'contenidoHTML' => 'required|string',
            ]);

            $justificacion = DB::table('justificaciones')->where('id', $id)->first();
            if (!$justificacion) {
                return response()->json(['message' => 'Justificación no encontrada'], 404);
            }

            if ($justificacion->estatus != 0) {
                return response()->json(['message' => 'Esta justificación ya fue procesada.'], 400);
            }

            DB::beginTransaction();

            $isAprobado = $validated['estatus'] == 1;
            $tipoAsistencia = $justificacion->tipo_asistencia == 7
                ? 7
                : ($isAprobado ? 3 : $justificacion->tipo_asistencia);

            if ($isAprobado && in_array($justificacion->tipo_asistencia, [1, 4])) {
                DB::table('descuentos_asistencia')
                    ->where([
                        'user_id' => $justificacion->user_id,
                        'fecha'   => $justificacion->fecha,
                    ])
                    ->delete();
            }

            DB::table('asistencias')
                ->where([
                    'user_id' => $justificacion->user_id,
                    'fecha'   => $justificacion->fecha,
                ])
                ->update([
                    'hora'            => $tipoAsistencia == 7 ? date('H:i:s', strtotime($justificacion->created_at)) : null,
                    'tipo_asistencia' => $tipoAsistencia,
                ]);

            DB::table('justificaciones')->where('id', $id)
                ->update([
                    'estatus'        => $validated['estatus'],
                    'contenido_html' => $validated['contenidoHTML'],
                ]);

            DB::commit();

            $mensaje = $isAprobado
                ? 'Justificación aprobada correctamente.'
                : 'Justificación rechazada correctamente.';

            return response()->json([
                'message' => $mensaje,
                'data'    => [
                    'estatus'         => $validated['estatus'],
                    'tipo_asistencia' => $tipoAsistencia,
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Datos inválidos',
                'errors'  => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar estatus de justificación: ' . $e->getMessage());
            return response()->json(['message' => 'Error interno del servidor.'], 500);
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
