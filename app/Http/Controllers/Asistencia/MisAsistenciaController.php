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

            $listado = [];
            $asistencias = DB::table('asistencias')
                ->where('user_id', $user_id)
                ->whereBetween('fecha', [$fechaIni, $fechaFin])
                ->get()->toArray();

            $limitePuntual = strtotime(date("Y-m-d {$this->horaLimitePuntual}"));
            $limiteDerivado = strtotime(date("Y-m-d {$this->horaLimiteDerivado}"));
            $horaActual = time();

            foreach ($asistencias as $a) {
                $diaSemana = strtolower(date('l', strtotime($a->fecha)));
                $descuento = $descuentos->get($a->fecha) ?? null;
                $justificacion = $justificaciones->get($a->fecha) ?? null;
                $tipoAsistencia = $tipoAsistencias->get($a->tipo_asistencia);
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
                $fechaActual = date('Y-m-d') == $a->fecha;

                // Si aún no tiene registro pero debería asistir
                if (!$a->hora && $a->tipo_modalidad == 1 && $tipo_asistencia == 1 && $horaActual < $limitePuntual && $fechaActual) {
                    $tipo_asistencia = 0;
                }

                if ($justificacion && $justificacion->estatus == 10) {
                    $tipo_asistencia = 7;
                }

                // Acciones dinámicas
                $acciones = [];
                // Si es un tipo de asistencia que puede ser justificado, no tiene justificación aún y es el día actual
                if ($tipo_asistencia == 7 && $justificacion && $justificacion?->estatus == 10 && $fechaActual) {
                    $acciones[] = [
                        'funcion' => "justificarAsistencia({$justificacion->id}, '{$a->fecha}', '{$a->hora}', {$tipo_asistencia})",
                        'texto' => '<i class="fas fa-scale-balanced me-2" style="color: ' . $tipoAsistencias->get(7)->color . ';"></i>Justificar Derivado'
                    ];
                    $notificacion = $tipo_asistencia == 7; // notificar solo si es tipo 7 (derivado)
                }

                // Si es un tipo de asistencia que puede ser justificado, no tiene justificación aún y es el día actual
                if (in_array($tipo_asistencia, [1, 4]) && !$justificacion && $fechaActual) {
                    $acciones[] = [
                        'funcion' => "justificarAsistencia({$a->id}, '{$a->fecha}', '{$a->hora}', {$tipo_asistencia})",
                        'texto' => '<i class="fas fa-scale-balanced me-2" style="color: ' . $tipoAsistencia->color . ';"></i>Justificar ' . $tipoAsistencia->descripcion
                    ];
                }

                // Si ya tiene justificación, se puede obtener la justificación
                if ($justificacion && $justificacion?->estatus != 10) {
                    $acciones[] = [
                        'funcion' => "showJustificacion({$justificacion->id}, '{$a->fecha}', '{$a->hora}', {$tipo_asistencia})",
                        'texto' => '<i class="fas fa-scale-balanced me-2 text-info"></i>Ver justificación'
                    ];
                }

                $listado[] = [
                    'jornada' => $campoDia,
                    'fecha' => $a->fecha,
                    'hora' => $a->hora,
                    'tipo_modalidad' => $a->tipo_modalidad,
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

    public function uploadMedia(Request $request)
    {
        try {
            if ($request->hasFile('file')) {
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
                    ApiResponse::error('Imagen mayor a 3MB');
                }
                if ($isVideo && $file->getSize() > $maxVideo) {
                    ApiResponse::error('Video mayor a 10MB');
                }
                if ($isPdf && $file->getSize() > $maxPdf) {
                    ApiResponse::error('Pdf mayor a 10MB');
                }

                // Guardar en public/media/año/mes
                $path = $file->store('media/' . date('Y/m'), 'public');
                $url = Storage::url("app/public/$path");

                DB::beginTransaction();
                $id = DB::table('media_archivos')->insertGetId([
                    'path_archivo' => $path,
                ]);
                DB::commit();

                return ApiResponse::success('Archivo subido correctamente.', ['url' => $url, 'archivo_id' => $id]);
            } else {
                return ApiResponse::error('No se ha subido ningún archivo.');
            }
        } catch (Exception $e) {
            Log::error('[MisAsistenciaController@uploadMedia] ' . $e->getMessage());
            return ApiResponse::error('Error al subir el archivo.');
        }
    }
}
