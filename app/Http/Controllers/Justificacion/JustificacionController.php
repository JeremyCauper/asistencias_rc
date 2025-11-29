<?php

namespace App\Http\Controllers\Justificacion;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\NotificacionController;
use App\Services\JsonDB;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Filesystem\Filesystem;

class JustificacionController extends Controller
{
    public function storeJustificacion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha' => 'required|date',
                'hora' => 'nullable|date_format:H:i:s',
                'tipo_asistencia' => 'required|in:0,1,4',
                'asunto' => 'required|string|max:255',
                'contenido' => 'required|string',
                'archivos' => 'nullable'
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray());
            }

            $user_id = $request->has('user_id') ? $request->user_id : session('user_id');
            $tipo_asistencia = $request->tipo_asistencia == 0 ? 2 : $request->tipo_asistencia;
            $estatus = $request->has('estatus') ? $request->estatus : ($tipo_asistencia == 2 ? 1 : 0);


            // Verifica si ya existe una justificación para esa fecha
            $justificacion = DB::table('justificaciones')->where('user_id', $user_id)
                ->where('fecha', $request->fecha)
                ->first();
            $justificacionEstatus = $justificacion?->estatus ?? null;

            if ($justificacion && in_array($justificacionEstatus, [0, 1, 2])) {
                return ApiResponse::success('Ya existe una justificación para esa fecha.');
            }
            $tipo_asistencia = $justificacionEstatus == 10 ? 1 : $tipo_asistencia;

            DB::beginTransaction();
            // Crear contenido HTML
            $contenido = $this->createBodyMessage(
                $tipo_asistencia,
                $request->contenido,
                $justificacion?->contenido_html ?? '',
                now()->format('Y-m-d H:i:s'),
                $justificacionEstatus == 10 ? 'Justificación de Fala por no justificar derivado a tiempo.' : ''
            );

            if ($justificacion) {
                DB::table('justificaciones')->where(['user_id' => $user_id, 'fecha' => $request->fecha])
                    ->update([
                        'tipo_asistencia' => $tipo_asistencia,
                        'asunto' => $request->asunto,
                        'contenido_html' => $contenido,
                        'estatus' => $tipo_asistencia == 2 ? 1 : $estatus, // pendiente
                    ]);
            } else {
                // Crea la justificación
                DB::table('justificaciones')->insert([
                    'user_id' => $user_id,
                    'fecha' => $request->fecha,
                    'tipo_asistencia' => $tipo_asistencia,
                    'asunto' => $request->asunto,
                    'contenido_html' => $contenido,
                    'created_by' => session('user_id'),
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'estatus' => $tipo_asistencia == 2 ? 1 : $estatus, // pendiente
                ]);
            }

            $asistencias = DB::table('asistencias')->where([
                'user_id' => $user_id,
                'fecha' => $request->fecha,
            ])->first();

            // Procesar asistencia solo cuando corresponde
            if ($asistencias) {
                if ($estatus == 1) {
                    $hora = $request->hora;
                    $limitePuntual = strtotime(date("Y-m-d " . $this->horaLimitePuntual));
                    DB::table('asistencias')->where('id', $asistencias->id)->update([
                        'tipo_asistencia' => $hora
                            ? ((strtotime(date("Y-m-d " . $hora)) > $limitePuntual) ? 4 : 2)
                            : 3,
                        'hora' => $hora ?? null
                    ]);
                }

                if (!empty($request->archivos)) {
                    $this->procesarArchivosJustificacion($request->archivos, $asistencias->id);
                }
            }
            DB::commit();

            return ApiResponse::success('Justificación registrada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[JustificacionController@storeJustificacion] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }

    public function responseJustificacion(Request $request)
    {
        $validaciones = [
            'id_justificacion' => 'required|integer',
            'hora' => 'nullable|date_format:H:i:s',
            'mensaje' => 'required|string',
            'archivos' => 'nullable',
        ];
        $message = 'Justificación registrada correctamente.';

        if ($request->has('estatus')) {
            $validaciones['estatus'] = 'required|in:1,2';
            $message = ($request->estatus == 1)
                ? 'Justificación aprobada correctamente.'
                : 'Justificación rechazada correctamente.';
        }

        $validator = Validator::make($request->all(), $validaciones);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            $justificacion = DB::table('justificaciones')->where('id', $request->id_justificacion)->first();
            $tipoAsistencia = null;

            if (!$justificacion) {
                return ApiResponse::error('La justificación no existe.');
            }

            // Manejo del estatus
            $estatusOriginal = $justificacion->estatus;
            $limiteDerivado = strtotime(date("Y-m-d " . $this->horaLimiteDerivado));
            $horaActual = time();

            if ($estatusOriginal == 10 && $horaActual > $limiteDerivado) {
                return ApiResponse::error('Solo se puede responder la drivación hasta las ' . $this->horaLimiteDerivado);
            }

            $estatus = ($estatusOriginal == 10)
                ? 0
                : ($request->estatus ?? $estatusOriginal);

            $now = now();
            // Crear contenido HTML
            $contenido = $this->createBodyMessage(
                $justificacion->tipo_asistencia,
                $request->mensaje,
                $justificacion->contenido_html,
                $now->format('Y-m-d H:i:s')
            );

            $values = [
                'contenido_html' => $contenido,
                'estatus' => $estatus
            ];

            if ($request->filled('asunto')) {
                $values['asunto'] = $request->asunto;
            }

            DB::beginTransaction();

            DB::table('justificaciones')
                ->where('id', $request->id_justificacion)
                ->update($values);

            $asistencias = DB::table('asistencias')->where([
                'user_id' => $justificacion->user_id,
                'fecha' => $justificacion->fecha,
            ])->first();

            // Procesar asistencia solo cuando corresponde
            if ($asistencias) {
                if ($estatusOriginal == 10) {
                    DB::table('asistencias')->where('id', $asistencias->id)->update([
                        'hora' => $request->hora ?? $now->format('H:i:s')
                    ]);
                }

                // Procesar asistencia solo cuando corresponde
                if (in_array($estatus, [1, 2]) && $estatusOriginal != 10) {
                    // Decidir tipo de asistencia de forma clara
                    $tipoAsistencia = match (true) {
                        $estatus == 1 && $justificacion->tipo_asistencia == 7 => 7,
                        $estatus == 1 => 3,
                        default => $justificacion->tipo_asistencia
                    };

                    DB::table('asistencias')->where('id', $asistencias->id)->update([
                        'tipo_asistencia' => $tipoAsistencia,
                    ]);
                }

                if (!empty($request->archivos)) {
                    $this->procesarArchivosJustificacion($request->archivos, $asistencias->id);
                }
                $archivos = DB::table('media_archivos')->where('asistencia_id', $asistencias->id)->get();
            }

            DB::commit();
            return ApiResponse::success($message, [
                'estatus' => $estatus,
                'tipo_asistencia' => $tipoAsistencia,
                'contenido' => $contenido,
                'archivos' => $archivos ?? []
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[JustificacionController@responseJustificacion] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }

    public function showJustificacion($id)
    {
        try {
            // ✅ Busca la justificación
            $justificacion = DB::table('justificaciones')->where('id', $id)->first();

            if (!$justificacion) {
                ApiResponse::notFound('No se encontró una justificación para el usuario en esa fecha.');
            }

            $personal = DB::table('personal')->select('dni', 'nombre', 'apellido')->where('user_id', $justificacion->user_id)->first();

            // 🧾 Formatea la respuesta
            return ApiResponse::success('Justificación encontrada.', [
                'personal' => $personal,
                'justificacion' => [
                    'id' => $justificacion->id,
                    'user_id' => $justificacion->user_id,
                    'fecha' => $justificacion->fecha,
                    'tipo_asistencia' => $justificacion->tipo_asistencia,
                    'asunto' => $justificacion->asunto,
                    'contenido_html' => $justificacion->contenido_html,
                    'estatus' => $justificacion->estatus,
                    'created_at' => $justificacion->created_at
                ]
            ]);
        } catch (Exception $e) {
            Log::error('[JustificacionController@showJustificacion] ' . $e->getMessage());
            return ApiResponse::error('Ocurrió un error interno al obtener la justificación. Intente nuevamente más tarde.');
        }
    }

    public function marcarDerivado(int $id)
    {
        try {
            $limiteDerivado = strtotime(date("Y-m-d " . $this->horaLimiteDerivado));
            $horaActual = time();

            if ($horaActual > $limiteDerivado) {
                return ApiResponse::error('Solo se puede deribar hasta las 10:00:00 am');
            }

            $asistencia = DB::table('asistencias')->where('id', $id)->first();
            if (!$asistencia) {
                return ApiResponse::error('Asistencia no encontrada.');
            }

            if ($asistencia->tipo_asistencia == 7) {
                return ApiResponse::error('Esta asistencia ya fue marcada como derivada.');
            }

            // Verifica si ya existe una justificación para esa fecha
            $yaJustificada = DB::table('justificaciones')->where('user_id', $asistencia->user_id)
                ->where('fecha', $asistencia->fecha)
                ->exists();

            if ($yaJustificada) {
                return ApiResponse::success('Ya existe una justificación para esa fecha.');
            }

            $fecha = now()->format('Y-m-d H:i:s');
            $mensaje = $this->utf8ToBase64('<p>✅ Se ha registrado la derivación del personal.</p><p>⏳ Ahora se encuentra pendiente la presentación de la justificación correspondiente por parte del trabajador.</p>');

            DB::beginTransaction();
            // Crea la justificación
            DB::table('justificaciones')->insert([
                'user_id' => $asistencia->user_id,
                'fecha' => $asistencia->fecha,
                'tipo_asistencia' => 7,
                'contenido_html' => $this->createBodyMessage(7, $mensaje, '', $fecha),
                'created_by' => session('user_id'),
                'created_at' => $fecha,
                'estatus' => 10, // pendiente
            ]);

            NotificacionController::crear([
                'user_id' => $asistencia->user_id,
                'tipo_notificacion' => 1,
                'descripcion_id' => 1,
                'ruta_id' => 2, // 1 -> mis asistencias (donde el técnico sube foto)
                'accion_id' => 1, // 1 -> justificarDerivado
                'payload_accion' => $asistencia->id,
            ]);
            DB::commit();

            return ApiResponse::success('Se Derivó con exito, falta respuesta por parte del tecnico.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@marcarDerivado] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el estado.');
        }
    }

    private function procesarArchivosJustificacion($nombresArchivos, int $asistenciaId)
    {
        // Obtener los registros desde la BD
        $archivos = DB::table('media_archivos')
            ->whereIn('nombre_archivo', $nombresArchivos)
            ->get();

        if ($archivos->isEmpty()) {
            throw new Exception("No se encontraron archivos en media_archivos.");
        }

        foreach ($archivos as $archivo) {
            try {
                $path_archivo = $archivo->path_archivo;
                $nombre_archivo = $archivo->nombre_archivo;
                $rutaLocal = public_path($path_archivo);

                $dirname = pathinfo($path_archivo, PATHINFO_DIRNAME); // solo la carpeta
                $filename = pathinfo($path_archivo, PATHINFO_BASENAME); // nombre + extensión

                if (!file_exists($rutaLocal)) {
                    Log::error("Archivo no encontrado: {$rutaLocal}");
                    throw new Exception("No se encontró el archivo local: {$nombre_archivo}");
                }

                $rutaS3 = Storage::disk('s3')->putFileAs(
                    $dirname,
                    new \Illuminate\Http\File($rutaLocal),
                    $filename
                );

                if (!$rutaS3) {
                    throw new Exception("Error al subir a S3: {$nombre_archivo}");
                }

                $urlS3 = Storage::disk('s3')->url($rutaS3);

                DB::table('media_archivos')->where('id', $archivo->id)
                    ->update([
                        'asistencia_id' => $asistenciaId,
                        'estatus' => 1,
                        'url_publica' => $urlS3
                    ]);

                // ELIMINAR ARCHIVO LOCAL SOLO SI TODO SALIÓ BIEN
                try {
                    unlink($rutaLocal);
                } catch (\Throwable $t) {
                    // Si falla la eliminación local, no debe romper todo el proceso
                    Log::warning("No se pudo eliminar archivo local: {$rutaLocal}. Error: {$t->getMessage()}");
                }
            } catch (Exception $e) {
                // Log detallado
                Log::error("[procesarArchivosJustificacion] {$e->getMessage()} Archivo: {$archivo->nombre_archivo}");
                // Lanzar nuevamente para que el método principal haga rollback
                throw $e;
            }
        }
    }

    private function createBodyMessage($id_tasistencia, $mensaje = '', $contenido = '', $timestamp, $title = '')
    {
        $config = session()->get('config');
        $mensaje_decodificado = $mensaje ? "<hr><div>{$this->base64ToUtf8($mensaje)}</div>" : '';
        $contenido_decodificado = $contenido ? $this->base64ToUtf8($contenido) : '';

        if ($title) {
            $titulo = $title;
        } else {
            $tasistencia = JsonDB::table('tipo_asistencia')->where('id', $id_tasistencia)->first();
            $titulo = 'Justificación de <span class="fw-bold" style="color: ' . $tasistencia->color . ';">' . ($id_tasistencia == 2 ? 'Asistencia' : $tasistencia->descripcion) . '</span>';
        }

        $fecha = $this->fecha_espanol();
        $hora = date('h:i a', strtotime($timestamp));
        $fechaCompleta = "$fecha a las $hora";

        $body = '
            <div class="p-3">
                <div class="d-flex align-items-center mb-3">
                    <span class="img-xs rounded-circle text-white acronimo" style="background-color: ' . $config->siglaBg . ' !important;">' . $config->sigla . '</span>
                    <div class="ms-2">
                        <p class="fw-bold mb-1">' . $config->nombre_perfil . '</p>
                    </div>
                    <span class="badge rounded-pill ms-auto" style="border: 2px solid var(--mdb-body-color) !important;color: var(--mdb-body-color) !important;font-size: .7rem;">' . $config->acceso . '</span>
                </div>
                <p>📅 <small class="fw-bold">Fecha de creación:</small> ' . $fechaCompleta . '</p>
                <p class="mt-1">✉️ ' . $titulo . '</p>
                ' . $mensaje_decodificado . '
                <hr class="mb-0">
            </div>'
            . $contenido_decodificado;

        return $this->utf8ToBase64($body);
    }

    private function fecha_espanol()
    {
        // $fecha debe ser formato: YYYY-MM-DD
        $timestamp = strtotime(date('Y-m-d'));

        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre'
        ];

        $dia = date('j', $timestamp);
        $mes = $meses[(int) date('n', $timestamp)];
        $anio = date('Y', $timestamp);

        return "$dia de $mes de $anio";
    }


    private function utf8ToBase64($str)
    {
        return base64_encode($str);
    }

    private function base64ToUtf8($base64)
    {
        return base64_decode($base64);
    }

    public function eliminarFile()
    {
        try {
            $ruta = 'asistencias_rc/justificaciones/2025/11/1763710725_59a36764f39fcba1.webp';

            $eliminado = Storage::disk('s3')->delete($ruta);

            if (!$eliminado) {
                return response()->json([
                    'ok' => false,
                    'mensaje' => 'El archivo no existe o no se pudo eliminar.'
                ]);
            }

            return response()->json([
                'ok' => true,
                'mensaje' => 'Archivo eliminado correctamente.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
