<?php

namespace App\Http\Controllers\Justificacion;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class JustificacionController extends Controller
{
    public function storeJustificacion(Request $request)
    {
        $validaciones = [
            'fecha' => 'required|date',
            'tipo_asistencia' => 'required|in:1,4',
            'asunto' => 'required|string|max:255',
            'contenido' => 'required|string',
        ];

        $isAdmin = in_array(session('tipo_usuario'), [2, 7]);
        if ($isAdmin) {
            $validaciones['user_id'] = 'required|integer';
        }

        $validator = Validator::make($request->all(), $validaciones);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        $user_id = $isAdmin ? $request->user_id : session('user_id');

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
            Log::error('[storeJustificacionTecnico@update] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
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

            DB::beginTransaction();
            // Crea la justificación
            DB::table('justificaciones')->insert([
                'user_id' => $asistencia->user_id,
                'fecha' => $asistencia->fecha,
                'tipo_asistencia' => 7,
                'created_by' => session('user_id'),
                'estatus' => 10, // pendiente
            ]);
            DB::commit();

            return ApiResponse::success('Se Derivó con exito, falta respuesta por parte del tecnico.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@marcarDerivado] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el estado.');
        }
    }
}
