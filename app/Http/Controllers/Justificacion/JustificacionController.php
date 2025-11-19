<?php

namespace App\Http\Controllers\Justificacion;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\JsonDB;
use DateTime;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use IntlDateFormatter;

class JustificacionController extends Controller
{
    public function storeJustificacion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'fecha' => 'required|date',
                'tipo_asistencia' => 'required|in:1,4',
                'asunto' => 'required|string|max:255',
                'contenido' => 'required|string',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray());
            }

            $user_id = $request->has('user_id') ? $request->user_id : session('user_id');
            $estatus = $request->has('estatus') ? $request->estatus : 0;

            // Verifica si ya existe una justificación para esa fecha
            $yaJustificada = DB::table('justificaciones')->where('user_id', $user_id)
                ->where('fecha', $request->fecha)
                ->exists();

            if ($yaJustificada) {
                return ApiResponse::success('Ya existe una justificación para esa fecha.');
            }

            DB::beginTransaction();
            // Crear contenido HTML
            $contenido = $this->createBodyMessage(
                $request->tipo_asistencia,
                $request->contenido,
                '',
                now()->format('Y-m-d H:i:s')
            );

            // Crea la justificación
            DB::table('justificaciones')->insert([
                'user_id' => $user_id,
                'fecha' => $request->fecha,
                'tipo_asistencia' => $request->tipo_asistencia,
                'asunto' => $request->asunto,
                'contenido_html' => $contenido,
                'created_by' => session('user_id'),
                'created_at' => now()->format('Y-m-d H:i:s'),
                'estatus' => $estatus, // pendiente
            ]);

            // Procesar asistencia solo cuando corresponde
            if ($estatus == 1) {
                DB::table('asistencias')
                    ->where([
                        'user_id' => $user_id,
                        'fecha' => $request->fecha,
                    ])
                    ->update([
                        'tipo_asistencia' => 3,
                    ]);
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
            'mensaje' => 'required|string',
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
            $limiteDerivado = strtotime(date("Y-m-d {$this->horaLimiteDerivado}"));
            $horaActual = time();

            if ($estatusOriginal == 10 && $horaActual < $limiteDerivado) {
                return ApiResponse::error('Solo se puede responder la drivación hasta las 10:20:00 am');
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

            // Procesar asistencia solo cuando corresponde
            if (in_array($estatus, [1, 2]) && $estatusOriginal != 10) {

                // Decidir tipo de asistencia de forma clara
                $tipoAsistencia = match (true) {
                    $estatus == 1 && $justificacion->tipo_asistencia == 7 => 7,
                    $estatus == 1 => 3,
                    default => $justificacion->tipo_asistencia
                };

                DB::table('asistencias')
                    ->where([
                        'user_id' => $justificacion->user_id,
                        'fecha' => $justificacion->fecha,
                    ])
                    ->update([
                        'hora' => $tipoAsistencia == 7 ? $now->format('H:i:s') : null,
                        'tipo_asistencia' => $tipoAsistencia,
                    ]);
            }

            DB::commit();
            return ApiResponse::success($message, [
                'estatus' => $estatus,
                'tipo_asistencia' => $tipoAsistencia,
                'contenido' => $contenido
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
            $limiteDerivado = strtotime(date("Y-m-d 09:30:00"));
            $horaActual = time();

            if ($horaActual < $limiteDerivado) {
                return ApiResponse::error('Solo se puede deribar hasta las 09:30:00 am');
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
            DB::commit();

            return ApiResponse::success('Se Derivó con exito, falta respuesta por parte del tecnico.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@marcarDerivado] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el estado.');
        }
    }

    private function createBodyMessage($id_tasistencia, $mensaje = '', $contenido = '', $timestamp)
    {
        $config = session()->get('config');
        $mensaje_decodificado = $mensaje ? "<hr><div>{$this->base64ToUtf8($mensaje)}</div>" : '';
        $contenido_decodificado = $contenido ? $this->base64ToUtf8($contenido) : '';
        $tasistencia = JsonDB::table('tipo_asistencia')->where('id', $id_tasistencia)->first();

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
                <p class="mt-1">✉️ Justificación de <span class="fw-bold" style="color: ' . $tasistencia->color . ';">' . $tasistencia->descripcion . '</span></p>
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
