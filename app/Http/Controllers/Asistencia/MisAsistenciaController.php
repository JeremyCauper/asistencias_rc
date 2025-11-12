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
        // $this->validarPermisos(6, 14);
        try {
            $empresas = DB::table('empresa')->get();
            $tipoModalidad = JsonDB::table('tipo_modalidad')->get()->keyBy('id');
            $tipoAsistencia = JsonDB::table('tipo_asistencia')->get()->keyBy('id');
            $tipoPersonal = JsonDB::table('tipo_personal')->get()->keyBy('id');

            return view('asistencias.misasistencias', [
                'tipoModalidad' => $tipoModalidad,
                'tipoAsistencia' => $tipoAsistencia,
                'tipoPersonal' => $tipoPersonal,
                'empresas' => $empresas,
            ]); // la vista Blade (más abajo)
        } catch (Exception $e) {
            Log::error('Vista: ' . $e->getMessage());
            return $this->message(data: ['error' => 'Vista: ' . $e->getMessage()], status: 500);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function listarMisAsistencias(Request $request)
    {
        try {
            $fecha = $request->query('fecha', date('Y-m'));
            $user_id = session('user_id');
            // $fechaIni = date("Y-m-01", strtotime($fecha));
            // $fechaFin = date("Y-m-t", strtotime($fecha));
            $fechaIni = date("Y-m-d");
            $fechaFin = date("Y-m-d");

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

            $tipoAsistencia = JsonDB::table('tipo_asistencia')->whereIn('id', [1, 4, 7])->get()->keyBy('id');

            $mis_asistencias = DB::table('asistencias')
                ->where('user_id', $user_id)
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()
                ->map(function ($val) use ($descuentos, $justificaciones, $tipoAsistencia) {
                    $diaSemana = strtolower(date('l', strtotime($val->fecha)));
                    $descuentoData = $descuentos->get($val->fecha);
                    $justificacionData = $justificaciones->get($val->fecha);
                    $tipoAsistenciasData = $tipoAsistencia->get($val->tipo_asistencia);
                    $descuento = null;

                    $campoDia = [
                        'monday' => 'Lunes',
                        'tuesday' => 'Martes',
                        'wednesday' => 'Miercoles',
                        'thursday' => 'Jueves',
                        'friday' => 'Viernes',
                        'saturday' => 'Sabado',
                        'sunday' => 'Domingo',
                    ][$diaSemana] ?? null;

                    if ($descuentoData) {
                        $descuento = $descuentoData->monto_descuento;
                    }

                    $acciones = [];
                    if (in_array($val->tipo_asistencia, [1, 4, 7]) && !$justificacionData) {
                        $acciones[] = ['funcion' => "solicitarJustificacion({$val->id}, '{$val->fecha}', '{$val->hora}', {$val->tipo_asistencia})", 'texto' => '<i class="fas fa-scale-balanced me-2" style="color: ' . $tipoAsistenciasData->color . ';"></i>Justificar ' . $tipoAsistenciasData->descripcion];
                    }

                    if ($justificacionData) {
                        $acciones[] = ['funcion' => "obtenerJustificacion('{$val->fecha}', '{$val->hora}', {$val->tipo_asistencia})", 'texto' => '<i class="fas fa-scale-balanced me-2 text-info"></i>Ver justificación'];
                    }


                    return [
                        'dia' => $campoDia,
                        'fecha' => $val->fecha,
                        'hora' => $val->hora,
                        'tipo_modalidad' => $val->tipo_modalidad,
                        'tipo_asistencia' => $val->tipo_asistencia,
                        'descuento' => $descuento,
                        'acciones' => $this->DropdownAcciones([
                            'tittle' => 'Acciones',
                            'button' => $acciones,
                        ])
                    ];
                });
            return $mis_asistencias;
        } catch (Exception $e) {
            Log::error($e->getLine() . ' Listado: ' . $e->getMessage());
            return $this->message(data: ['error' => 'Listado: ' . $e->getMessage()], status: 500);
        }
    }

    public function uploadMedia(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No se recibió archivo'], 400);
        }

        $file = $request->file('file');
        $mime = $file->getClientMimeType();
        $isImage = str_starts_with($mime, 'image/');
        $isVideo = str_starts_with($mime, 'video/');
        $isPdf = str_starts_with($mime, 'application/pdf');

        if (!($isImage || $isVideo || $isPdf)) {
            return response()->json(['error' => 'Tipo de archivo no permitido'], 415);
        }

        // Límites
        $maxImage = 3 * 1024 * 1024; // 3MB
        $maxVideo = 10 * 1024 * 1024; // 10MB
        $maxPdf = 10 * 1024 * 1024; // 10MB

        if ($isImage && $file->getSize() > $maxImage) {
            return response()->json(['error' => 'Imagen mayor a 3MB'], 413);
        }
        if ($isVideo && $file->getSize() > $maxVideo) {
            return response()->json(['error' => 'Video mayor a 10MB'], 413);
        }
        if ($isPdf && $file->getSize() > $maxPdf) {
            return response()->json(['error' => 'Pdf mayor a 10MB'], 413);
        }

        // Guardar en public/media/año/mes
        $path = $file->store('media/' . date('Y/m'), 'public');
        $url = Storage::url("app/public/$path");

        return response()->json(['url' => "/asistencias_rc{$url}"]);
    }

    public function storeJustificaciones(Request $request)
    {
        $user_id = session('user_id');
        $validator = Validator::make($request->all(), [
            'fecha_justi'            => 'required|date',
            'tipo_asistencia_justi'  => 'required|in:1,4,7',
            'asunto_justi'           => 'required|string|max:255',
            'contenidoHTML'          => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            // Verifica si ya existe una justificación para esa fecha
            $yaJustificada = DB::table('justificaciones')->where('user_id', $user_id)
                ->where('fecha', $request->fecha_justi)
                ->exists();

            if ($yaJustificada) {
                return ApiResponse::success('Ya existe una justificación para esa fecha.');
            }

            DB::beginTransaction();
            // Crea la justificación
            $justificacion = DB::table('justificaciones')->insert([
                'user_id' => $user_id,
                'fecha' => $request->fecha_justi,
                'tipo_asistencia' => $request->tipo_asistencia_justi,
                'asunto' => $request->asunto_justi,
                'contenido_html' => $request->contenidoHTML,
                'created_at' => $request->created,
                'estatus' => 0, // pendiente
            ]);
            DB::commit();

            return ApiResponse::success('Justificación registrada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[TipoAsistenciaController@update] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }

    /**
     * Muestra la justificación de un usuario en una fecha específica.
     */
    public function showJustificacion($fecha)
    {
        try {
            $user_id = session('user_id');
            if (!is_numeric($user_id)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'El ID de usuario debe ser numérico.'
                ], 400);
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
                return response()->json([
                    'ok' => false,
                    'message' => 'La fecha debe tener el formato YYYY-MM-DD.'
                ], 400);
            }

            // ✅ Busca la justificación
            $justificacion = DB::table('justificaciones')->where('user_id', $user_id)
                ->where('fecha', $fecha)
                ->first();

            if (!$justificacion) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se encontró una justificación para el usuario en esa fecha.'
                ], 404);
            }

            // 🧾 Formatea la respuesta
            return response()->json([
                'ok' => true,
                'data' => [
                    'id' => $justificacion->id,
                    'user_id' => $justificacion->user_id,
                    'fecha' => $justificacion->fecha,
                    'tipo_asistencia' => $justificacion->tipo_asistencia,
                    'asunto' => $justificacion->asunto,
                    'contenido_html' => $justificacion->contenido_html,
                    'estatus' => $justificacion->estatus,
                    'created_at' => $justificacion->created_at,
                    'updated_at' => $justificacion->updated_at,
                ]
            ], 200);
        } catch (Exception $e) {
            // 🚨 Captura cualquier error inesperado
            Log::error('Error al obtener la justificación', [
                'user_id' => $user_id,
                'fecha' => $fecha,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'ok' => false,
                'message' => 'Ocurrió un error interno al obtener la justificación. Intente nuevamente más tarde.'
            ], 500);
        }
    }
}
