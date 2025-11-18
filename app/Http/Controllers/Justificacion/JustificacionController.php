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

        try {
            // Verifica si ya existe una justificación para esa fecha
            $yaJustificada = DB::table('justificaciones')->where('user_id', $user_id)
                ->where('fecha', $request->fecha)
                ->exists();

            if ($yaJustificada) {
                return ApiResponse::success('Ya existe una justificación para esa fecha.');
            }

            DB::beginTransaction();
            // Crea la justificación
            DB::table('justificaciones')->insert([
                'user_id' => $user_id,
                'fecha' => $request->fecha,
                'tipo_asistencia' => $request->tipo_asistencia,
                'asunto' => $request->asunto,
                'contenido_html' => $request->contenido,
                'created_by' => session('user_id'),
                'created_at' => $request->created,
                'estatus' => 0, // pendiente
            ]);
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

        if ($request->has('estatus')) {
            $validaciones['estatus'] = 'required|in:1,2'; // 1=aprobado, 2=rechazado
        }

        $validator = Validator::make($request->all(), $validaciones);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            // Verifica si ya existe una justificación para esa fecha
            $justificacion = DB::table('justificaciones')->where('id', $request->id_justificacion)->first();
            $estatus = $justificacion->estatus == 10 ? 0 : 1;
            $hora = date('Y-m-d H:i:s');

            $contenido = $this->createBodyMessage($justificacion->tipo_asistencia, $request->mensaje, $justificacion->contenido_html, $hora);

            $values = [
                'contenido_html' => $contenido,
                'estatus' => $estatus
            ];
            if ($request->has('asunto')) {
                $values['asunto'] = $request->asunto;
            }

            DB::beginTransaction();
            // Crea la justificación
            DB::table('justificaciones')->where('id', $request->id_justificacion)->update($values);

            if (in_array($estatus, [1, 2])) {
                $tipoAsistencia = $justificacion->tipo_asistencia == 7 ? 7 : ($estatus == 1 ? 3 : $justificacion->tipo_asistencia);
                DB::table('asistencias')
                    ->where([
                        'user_id' => $justificacion->user_id,
                        'fecha' => $justificacion->fecha,
                    ])
                    ->update([
                        'hora' => $tipoAsistencia == 7 ? date('H:i:s', strtotime($hora)) : null,
                        'tipo_asistencia' => $tipoAsistencia,
                    ]);
            }
            DB::commit();

            return ApiResponse::success('Justificación registrada correctamente.');
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
                    'created_at' => $justificacion->created_at,
                    'updated_at' => $justificacion->updated_at,
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

        setlocale(LC_TIME, 'es_PE.UTF-8');
        date_default_timezone_set('America/Lima');

        $fecha = strftime('%d de %B de %Y');
        $hora = date('h:i a', strtotime($timestamp));
        $fechaCompleta = "$fecha a las $hora";

        $body = '
            <div class="p-3">
                <div class="d-flex align-items-center mb-3">
                    <span class="img-xs rounded-circle text-white acronimo" style="background-color: ' . $config->siglaBg . ' !important;">' . $config->sigla . '</span>
                    <div class="ms-2">
                        <p class="fw-bold mb-1">' . $config->nombre_perfil . '</p>
                    </div>
                    <span class="badge rounded-pill ms-auto" style="background-color: ' . $config->accesoCl . ' !important;font-size: .7rem;">' . $config->acceso . '</span>
                </div>
                <p>📅 <small class="fw-bold">Fecha de creación:</small> ' . $fechaCompleta . '</p>
                <p class="mt-1">✉️ Justificación de <span class="fw-bold" style="color: ' . $tasistencia->color . ';">' . $tasistencia->descripcion . '</span></p>
                ' . $mensaje_decodificado . '
                <hr class="mb-0">
            </div>'
            . $contenido_decodificado;

        return $this->utf8ToBase64($body);
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
