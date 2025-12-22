<?php

namespace App\Http\Controllers\Justificacion;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\MediaArchivo\MediaArchivoController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\PushController;
use App\Services\JsonDB;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\In;

class JustificacionController extends Controller
{
    public function storeJustificacionByAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_asistencia' => 'required|integer',
                'user_id' => 'required|integer',
                'tipo_asistencia' => 'required|in:1,4',
                'asunto' => 'required|string|max:255',
                'contenido' => 'required|string',
                'archivos' => 'nullable'
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray());
            }

            $id_asistencia = $request->id_asistencia;
            $tipo_asistencia = $request->tipo_asistencia;
            $user_id = $request->user_id;
            $asunto = $request->asunto;

            // Verifica si ya existe una justificación para este id
            $justificacion = DB::table('justificaciones')->select('estatus', 'contenido_html')->where('asistencia_id', $id_asistencia)->first();
            $justificacionEstatus = $justificacion?->estatus ?? null;
            if (!empty($justificacion) && in_array($justificacionEstatus, [0, 1, 2])) {
                $estadoJust = ['0' => 'Pendiente', '1' => 'Aprobada', '2' => 'Rechazada'][$justificacionEstatus] ?? 'Desconocido';
                return ApiResponse::success("Ya existe una justificación para esa asistencia y está {$estadoJust}");
            }

            $asistencia = DB::table('asistencias')->select('user_id', 'fecha', 'tipo_modalidad')->where('id', $id_asistencia)->first();
            // Verificar si la asistencia existe
            if (!$asistencia) {
                return ApiResponse::error('No se encontró la asistencia.');
            }

            // Crear contenido HTML
            $contenido = $this->createBodyMessage(
                $tipo_asistencia,
                $request->contenido,
                $justificacion?->contenido_html ?? '',
                now()->format('Y-m-d H:i:s'),
                $justificacionEstatus == 10 ? 'Justificación de Fala por no justificar derivado a tiempo.' : ''
            );

            DB::beginTransaction();
            if ($justificacion) {
                DB::table('justificaciones')->where('asistencia_id', $id_asistencia)
                    ->update([
                        'tipo_asistencia' => $tipo_asistencia,
                        'asunto' => $asunto,
                        'contenido_html' => $contenido,
                        'estatus' => 1,
                    ]);
            } else {
                // Crea la justificación
                DB::table('justificaciones')->insert([
                    'asistencia_id' => $id_asistencia,
                    'user_id' => $user_id,
                    'fecha' => $asistencia->fecha,
                    'tipo_asistencia' => $tipo_asistencia,
                    'asunto' => $asunto,
                    'contenido_html' => $contenido,
                    'created_by' => Auth::user()->user_id,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'estatus' => 1,
                ]);
            }

            // Actualiza la asistencia como justificada
            DB::table('asistencias')->where('id', $id_asistencia)->update([
                'tipo_asistencia' => 3,
            ]);

            // Elimina los descuentos de la asistencia
            DB::table('descuentos_asistencia')->where('asistencia_id', $id_asistencia)->delete();

            DB::commit();

            if (!empty($request->archivos)) {
                MediaArchivoController::uploadFileS3($request->archivos, $id_asistencia);
            }

            return ApiResponse::success('Justificación creada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[JustificacionController@storeJustificacionByAdmin] ' . $e->getMessage());
            return ApiResponse::error('Error al guarsdar la justificación.');
        }
    }

    public function storeJustificacionByUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_asistencia' => 'required|integer',
                'entrada' => 'nullable|date_format:H:i:s',
                'asunto' => 'required|string|max:255',
                'contenido' => 'required|string',
                'archivos' => 'nullable'
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray());
            }

            $id_asistencia = $request->id_asistencia;
            $user_id = Auth::user()->user_id;
            $asunto = $request->asunto;

            // Verifica si ya existe una justificación para este id
            $justificacion = DB::table('justificaciones')->select('estatus', 'contenido_html')->where('asistencia_id', $id_asistencia)->first();
            $justificacionEstatus = $justificacion?->estatus ?? null;
            if (!empty($justificacion) && in_array($justificacionEstatus, [0, 1, 2])) {
                $estadoJust = ['0' => 'Pendiente', '1' => 'Aprobada', '2' => 'Rechazada'][$justificacionEstatus] ?? null;
                $estadoJust = $estadoJust ? ' y está ' . $estadoJust : '';
                return ApiResponse::success("Ya existe una justificación para esa asistencia{$estadoJust}.");
            }

            $asistencia = DB::table('asistencias')->select('user_id', 'fecha', 'tipo_modalidad', 'tipo_asistencia')->where('id', $id_asistencia)->first();
            // Verificar si la asistencia existe
            if (!$asistencia) {
                return ApiResponse::error('No se encontró la asistencia.');
            }

            $tipo_modalidad = $asistencia->tipo_modalidad;

            $tipo_asistencia = match (true) {
                $tipo_modalidad == 1 && $asistencia->tipo_asistencia == 1 => 1,
                default => $asistencia->tipo_asistencia
            };

            if ($tipo_modalidad == 2 && $tipo_asistencia == 1) {
                $entrada = $request->entrada;
                if (!$entrada) {
                    return ApiResponse::error('Debe ingresar la hora de entrada.');
                }
                $puntualidad = strtotime(date("{$this->strFecha} {$entrada}")) < $this->limitePuntual();
                $tipo_asistencia = $puntualidad ? 2 : 4;
            }

            $estatus = match (true) {
                $tipo_asistencia == 2 => 1,
                default => 0
            };

            // Crear contenido HTML
            $contenido = $this->createBodyMessage(
                $tipo_asistencia,
                $request->contenido,
                $justificacion?->contenido_html ?? '',
                now()->format('Y-m-d H:i:s'),
            );

            DB::beginTransaction();

            if ($justificacion) {
                DB::table('justificaciones')->where('asistencia_id', $id_asistencia)
                    ->update([
                        'tipo_asistencia' => $tipo_asistencia,
                        'asunto' => $asunto,
                        'contenido_html' => $contenido,
                        'estatus' => $estatus,
                    ]);
            } else {
                DB::table('justificaciones')->insert([
                    'asistencia_id' => $id_asistencia,
                    'user_id' => $user_id,
                    'fecha' => $asistencia->fecha,
                    'tipo_asistencia' => $tipo_asistencia,
                    'asunto' => $asunto,
                    'contenido_html' => $contenido,
                    'created_by' => $user_id,
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'estatus' => $estatus,
                ]);
            }

            if ($tipo_modalidad == 2 && $asistencia->tipo_asistencia == 1) {
                DB::table('asistencias')->where('id', $id_asistencia)->update([
                    'tipo_asistencia' => $tipo_asistencia,
                    'entrada' => $entrada
                ]);

                if ($tipo_asistencia == 4) {
                    DB::table('descuentos_asistencia')->insert([
                        'asistencia_id' => $id_asistencia,
                        'user_id' => $user_id,
                        'fecha' => $asistencia->fecha,
                    ]);
                    $mensaje = 'Justificación registrada. pero se le ha otorgado un descuento por tardanza.';
                }
            }

            if (!in_array($tipo_asistencia, [2])) {
                $configuraciones = match ($tipo_asistencia) {
                    1 => [3, 2],
                    4 => [4, 3]
                };

                NotificacionController::crear([
                    'tipo_notificacion' => 0,
                    'asignado_id' => $id_asistencia,
                    'user_id' => $user_id,
                    'is_admin' => 1,
                    'titulo_id' => $configuraciones[0],
                    'descripcion_id' => $configuraciones[1],
                    'ruta_id' => 1,
                    'accion_id' => 2,
                    'payload_accion' => $id_asistencia,
                ]);
            }

            DB::commit();

            if (!empty($request->archivos)) {
                MediaArchivoController::uploadFileS3($request->archivos, $id_asistencia);
            }

            if (!in_array($tipo_asistencia, [2]))
                PushController::sendForAdmin();

            return ApiResponse::success($mensaje ?? 'Justificación creada correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[JustificacionController@storeJustificacionByUser] ' . $e->getMessage());
            return ApiResponse::error('Error al guarsdar la justificación.');
        }
    }

    public function responseJustificacionByAdmin(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_asistencia' => 'required|integer',
                'estatus' => 'required|in:1,2',
                'mensaje' => 'required|string',
                'archivos' => 'nullable'
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray());
            }

            $id_asistencia = $request->id_asistencia;

            $justificacion = DB::table('justificaciones')->where('asistencia_id', $id_asistencia)->first();
            // Verificar si la justificación existe
            if (!$justificacion) {
                return ApiResponse::error('No se encontró la justificación.');
            }

            // Declarar variables
            $tipoAsistencia = $justificacion->tipo_asistencia;
            $estatus = $request->estatus;
            $now = now();

            $asistencia = DB::table('asistencias')->where('id', $id_asistencia)->exists();
            // Verificar si la asistencia existe
            if (!$asistencia) {
                return ApiResponse::error('No se encontró la asistencia.');
            }

            DB::beginTransaction();

            // Crear contenido HTML
            $contenido = $this->createBodyMessage(
                $tipoAsistencia,
                $request->mensaje,
                $justificacion->contenido_html,
                $now->format('Y-m-d H:i:s')
            );

            DB::table('justificaciones')->where('asistencia_id', $id_asistencia)
                ->update([
                    'contenido_html' => $contenido,
                    'estatus' => $estatus
                ]);

            if (in_array($estatus, [1, 2])) {
                $tipoAsistencia = match (true) {
                    $estatus == 1 && $tipoAsistencia == 7 => 7,
                    $estatus == 1 => 3,
                    default => $tipoAsistencia
                };

                DB::table('asistencias')->where('id', $id_asistencia)->update([
                    'tipo_asistencia' => $tipoAsistencia,
                ]);
            }

            if ($justificacion->tipo_asistencia == 4 && $estatus == 1) {
                DB::table('descuentos_asistencia')->where('asistencia_id', $id_asistencia)->delete();
            }

            if ($id_asistencia)
                NotificacionController::marcarLeido(0, $id_asistencia);

            DB::commit();

            if (!empty($request->archivos)) {
                MediaArchivoController::uploadFileS3($request->archivos, $id_asistencia);
            }
            $archivos = DB::table('media_archivos')->where('asistencia_id', $id_asistencia)->get();

            return ApiResponse::success(
                str_replace(':?', $estatus == 1 ? 'aprobada' : 'rechazada', 'Justificación :? correctamente.'),
                [
                    'estatus' => $estatus,
                    'tipo_asistencia' => $tipoAsistencia,
                    'contenido' => $contenido,
                    'archivos' => $archivos ?? []
                ]
            );
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[JustificacionController@responseJustificacionByAdmin] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }

    public function responseJustificacionByUser(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id_asistencia' => 'required|integer',
                'entrada' => 'nullable|date_format:H:i:s',
                'asunto' => 'required|string|max:255',
                'mensaje' => 'required|string',
                'archivos' => 'nullable'
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray());
            }

            $id_asistencia = $request->id_asistencia;

            $justificacion = DB::table('justificaciones')->where('asistencia_id', $id_asistencia)->first();
            // Verificar si la justificación existe
            if (!$justificacion) {
                return ApiResponse::error('No se encontró la justificación.');
            }

            // Declarar variables
            $tipoAsistencia = $justificacion->tipo_asistencia;
            $estatusOriginal = $justificacion->estatus;

            // Verificar si es derivado y dentro del tiempo limite
            if ($estatusOriginal == 10 && $this->horaActual > $this->limiteDerivado()) {
                return ApiResponse::error('Solo se puede responder la drivación hasta las ' . $this->horaLimiteDerivado);
            }

            $estatus = $estatusOriginal == 10 ? 0 : $estatusOriginal;
            $now = now();

            $asistencia = DB::table('asistencias')->where('id', $id_asistencia)->exists();
            // Verificar si la asistencia existe
            if (!$asistencia) {
                return ApiResponse::error('No se entrontró la asistencia.');
            }

            DB::beginTransaction();

            // Crear contenido HTML
            $contenido = $this->createBodyMessage(
                $justificacion->tipo_asistencia,
                $request->mensaje,
                $justificacion->contenido_html,
                $now->format('Y-m-d H:i:s')
            );

            DB::table('justificaciones')
                ->where('id', $justificacion->id)
                ->update([
                    'contenido_html' => $contenido,
                    'asunto' => $request->asunto,
                    'estatus' => $estatus
                ]);

            $actualizarAsistencia = [];
            if ($estatusOriginal == 10)
                $actualizarAsistencia['entrada'] = $request->entrada ?? $now->format('H:i:s');

            if (in_array($estatus, [1, 2]) && $estatusOriginal != 10) {
                $tipoAsistencia = match (true) {
                    $estatus == 1 && $tipoAsistencia == 7 => 7,
                    $estatus == 1 => 3,
                    default => $tipoAsistencia
                };
                $actualizarAsistencia['tipo_asistencia'] = $tipoAsistencia;
            }

            if (!empty($actualizarAsistencia))
                DB::table('asistencias')->where('id', $id_asistencia)->update($actualizarAsistencia);

            // Enviar notificaciones
            if ($id_asistencia)
                NotificacionController::marcarLeido(0, $id_asistencia);

            NotificacionController::crear([
                'tipo_notificacion' => 0,
                'asignado_id' => $id_asistencia,
                'user_id' => $justificacion->user_id,
                'is_admin' => 1,
                'titulo_id' => 2,
                'descripcion_id' => 4,
                'ruta_id' => 1,
                'accion_id' => 2,
                'payload_accion' => $id_asistencia,
            ]);

            DB::commit();

            if (!empty($request->archivos)) {
                MediaArchivoController::uploadFileS3($request->archivos, $id_asistencia);
            }
            $archivos = DB::table('media_archivos')->where('asistencia_id', $id_asistencia)->get();

            PushController::sendForAdmin();

            return ApiResponse::success(
                'Justificación respondida correctamente.',
                [
                    'estatus' => $estatus,
                    'tipo_asistencia' => $tipoAsistencia,
                    'contenido' => $contenido,
                    'archivos' => $archivos ?? []
                ]
            );

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[JustificacionController@responseJustificacionByUser] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }

    public function marcarDerivado(int $id)
    {
        try {
            if ($this->horaActual > $this->limiteDerivado()) {
                return ApiResponse::error('Solo se puede deribar hasta las 10:30:00 am');
            }

            $asistencia = DB::table('asistencias')->where('id', $id)->first();
            if (!$asistencia) {
                return ApiResponse::error('Asistencia no encontrada.');
            }

            if ($asistencia->tipo_asistencia == 7) {
                return ApiResponse::error('Esta asistencia ya fue marcada como derivada.');
            }

            // Verifica si ya existe una justificación para esa fecha
            $yaJustificada = DB::table('justificaciones')->where('asistencia_id', $id)->exists();

            if ($yaJustificada) {
                return ApiResponse::success('Ya existe una justificación para esa fecha.');
            }

            $fecha = now()->format('Y-m-d H:i:s');
            $mensaje = $this->utf8ToBase64('<p>✅ Se ha registrado la derivación del personal.</p><p>⏳ Ahora se encuentra pendiente la presentación de la justificación correspondiente por parte del trabajador.</p>');

            DB::beginTransaction();
            // Crea la justificación
            DB::table('justificaciones')->insert([
                'asistencia_id' => $id,
                'user_id' => $asistencia->user_id,
                'fecha' => $asistencia->fecha,
                'tipo_asistencia' => 7,
                'contenido_html' => $this->createBodyMessage(7, $mensaje, '', $fecha),
                'created_by' => Auth::user()->user_id,
                'created_at' => $fecha,
                'estatus' => 10, // pendiente
            ]);

            NotificacionController::crear([
                'tipo_notificacion' => 0,
                'asignado_id' => $id,
                'user_id' => $asistencia->user_id,
                'fecha' => $asistencia->fecha,
                'titulo_id' => 1,
                'descripcion_id' => 1,
                'ruta_id' => 2,
                'accion_id' => 1,
                'payload_accion' => $id,
                'limite_show' => 'derivado'
            ]);

            DB::commit();

            PushController::sendDerivado($asistencia->user_id);

            return ApiResponse::success('Se Derivó con exito, falta respuesta por parte del tecnico.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@marcarDerivado] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el estado.', $e->getMessage());
        }
    }

    private function createBodyMessage($id_tasistencia, $mensaje = '', $contenido = '', $timestamp, $title = '')
    {
        $config = config('ajustes.config');
        $mensaje_decodificado = $mensaje ? "<hr><div>{$this->base64ToUtf8($mensaje)}</div>" : '';
        $contenido_decodificado = $contenido ? $this->base64ToUtf8($contenido) : '';

        if ($title) {
            $titulo = $title;
        } else {
            $tasistencia = JsonDB::table('tipo_asistencia')->where('id', $id_tasistencia)->first();
            $color = $tasistencia?->color ?? '#959595';
            $descripcion = !empty($tasistencia) ? ($id_tasistencia == 2 ? 'Asistencia' : $tasistencia->descripcion) : 'Asistencia';
            $titulo = 'Justificación de <span class="fw-bold" style="color: ' . $color . ';">' . $descripcion . '</span>';
        }

        $fecha = $this->fecha_espanol();
        $hora = date('h:i a', timestamp: strtotime($timestamp));
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
}
