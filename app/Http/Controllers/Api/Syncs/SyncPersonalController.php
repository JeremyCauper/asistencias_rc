<?php

namespace App\Http\Controllers\Api\Syncs;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Personal;
use App\Services\JsonDB;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SyncPersonalController extends Controller
{
    public function view()
    {
        try {
            $tipoModalidad = JsonDB::table('tipo_modalidad')->get();
            $tipoPersonal = JsonDB::table('tipo_personal')->get();
            $empresa = DB::table('empresa')->get();
            $areas = DB::table('areas_personal')->get();

            return view('personal.personal', [
                'tipoModalidad' => $tipoModalidad,
                'tipoPersonal' => $tipoPersonal,
                'empresa' => $empresa,
                'areas' => $areas
            ]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // 📄 Listar todos o solo los pendientes
    public function listar(Request $request)
    {
        $empresas = DB::table('empresa')->get();
        $personal = DB::table('personal')
            ->whereNot('estado_sync', 4)->get()
            ->map(function ($p) use ($empresas) {
                $empresa = $empresas->where('ruc', $p->empresa_ruc)->first();
                $colorEstado = $p->estatus ? 'warning' : 'success';

                return [
                    'user_id' => $p->user_id,
                    'area' => $p->area_id,
                    'empresa' => "{$empresa->ruc} - {$empresa->razon_social}",
                    'dni' => $p->dni,
                    'nombre' => $p->nombre,
                    'apellido' => $p->apellido,
                    'clave' => $p->password_view,
                    'tipo' => $p->rol_system,
                    'estado_sync' => $p->estado_sync,
                    'registrado' => $p->created_at,
                    'actualizado' => $p->updated_at,
                    'estado' => $p->estatus,
                    'acciones' => $this->DropdownAcciones([
                        'button' => [
                            ['clase' => 'btnEditar', 'attr' => 'data-id="' . $p->user_id . '"', 'texto' => '<i class="fa fa-edit me-2 text-info"></i> Editar'],
                            ['clase' => 'btnVacaciones', 'attr' => 'data-id="' . $p->user_id . '"', 'texto' => '<i class="fa fa-house-chimney-user me-2 text-primary"></i> Programar Vacaciones'],
                            ['clase' => 'btnDescansos', 'attr' => 'data-id="' . $p->user_id . '"', 'texto' => '<i class="fas fa-house-chimney-medical me-2 text-info"></i> Programar Desc. Medico'],
                            ['clase' => 'btnEliminar', 'attr' => 'data-id="' . $p->user_id . '"', 'texto' => '<i class="fa fa-trash me-2 text-danger"></i> Eliminar'],
                            ['clase' => 'btnEstado', 'attr' => 'data-id="' . $p->user_id . '" data-estatus="' . $p->estatus . '"', 'texto' => '<i class="fas fa-rotate-right me-2 text-' . $colorEstado . '"></i> ' . ($p->estatus ? 'Desactivar' : 'Activar')],
                        ],
                    ])
                ];
            });

        return response()->json($personal);
    }

    // 📄 Listar solo los pendientes
    public function pendientes()
    {
        $personal = DB::table('personal')
            ->select('id', 'user_id', 'nombre', 'apellido', 'password', 'role', 'cardno', 'estado_sync')
            ->whereIn('estado_sync', [0, 2, 3])->get();

        return response()->json($personal);
    }

    /**
     * 📄 Obtener un registro por ID
     */
    public function show($id)
    {
        $personal = DB::table('personal')->where('user_id', $id)->first();
        $trabajo_personal = DB::table('config_trabajo_personal')->where('user_id', $id)->first();

        if (!$personal) {
            return response()->json(['message' => 'Personal no encontrado'], 404);
        }

        $personal->trabajo_personal = $trabajo_personal ?: [];

        return response()->json($personal);
    }

    // ✏️ Crear personal (desde el panel web)
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'dni' => 'required|string|max:15',
                'areas' => 'required|integer',
                'empresa' => 'required|string|size:11',
                'nombre' => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'rol_sensor' => 'nullable|integer',
                'clave' => 'nullable|string|max:50',
                'cardno' => 'nullable|string|max:50',
                'rol_system' => 'nullable|integer',
                'password_view' => 'nullable|string|max:50',
                'tplunes' => 'required|integer',
                'tpmartes' => 'required|integer',
                'tpmiercoles' => 'required|integer',
                'tpjueves' => 'required|integer',
                'tpviernes' => 'required|integer',
                'tpsabado' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['required' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            $id = DB::table('personal')->insertGetId([
                'area_id' => $request->areas,
                'empresa_ruc' => $request->empresa,
                'dni' => $request->dni,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'role' => $request->rol_sensor,
                'password' => $request->clave,
                'cardno' => $request->cardno,
                'rol_system' => $request->rol_system,
                'password_view' => $request->password_view,
                'password_system' => Hash::make($request->password_view),
                'estado_sync' => 0,
            ]);

            // Aquí actualizamos el campo user_id con el valor del id recién insertado
            DB::table('personal')->where('id', $id)->update(['user_id' => $id]);

            DB::table('config_trabajo_personal')->insert([
                'user_id' => $id,
                'lunes' => $request->tplunes,
                'martes' => $request->tpmartes,
                'miercoles' => $request->tpmiercoles,
                'jueves' => $request->tpjueves,
                'viernes' => $request->tpviernes,
                'sabado' => $request->tpsabado,
            ]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Personal actualizado', 'data' => ['user_id' => $id]], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ✏️ Editar personal (desde la web)
    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'areas' => 'required|integer',
                'dni' => 'required|string|max:15',
                'empresa' => 'required|string|size:11',
                'nombre' => 'required|string|max:100',
                'apellido' => 'required|string|max:100',
                'rol_sensor' => 'nullable|integer',
                'clave' => 'nullable|string|max:50',
                'cardno' => 'nullable|string|max:50',
                'rol_system' => 'nullable|integer',
                'password_view' => 'nullable|string|max:50',
                'tplunes' => 'required|integer',
                'tpmartes' => 'required|integer',
                'tpmiercoles' => 'required|integer',
                'tpjueves' => 'required|integer',
                'tpviernes' => 'required|integer',
                'tpsabado' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['required' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            DB::table('personal')->where('user_id', $id)->update([
                'area_id' => $request->areas,
                'empresa_ruc' => $request->empresa,
                'dni' => $request->dni,
                'nombre' => $request->nombre,
                'apellido' => $request->apellido,
                'role' => $request->rol_sensor,
                'password' => $request->clave,
                'cardno' => $request->cardno,
                'rol_system' => $request->rol_system,
                'password_view' => $request->password_view,
                'password_system' => Hash::make($request->password_view),
                'estado_sync' => 2,
            ]);

            DB::table('config_trabajo_personal')->updateOrInsert(
                ['user_id' => $id],
                [
                    'lunes' => $request->tplunes,
                    'martes' => $request->tpmartes,
                    'miercoles' => $request->tpmiercoles,
                    'jueves' => $request->tpjueves,
                    'viernes' => $request->tpviernes,
                    'sabado' => $request->tpsabado,
                ]
            );

            $fecha = date('Y-m-d');
            $campoDia = $this->getDay($fecha);

            if (!empty($campoDia)) {
                $dia_modalidad = "tp$campoDia";
                $tipo_modalidad = $request->$dia_modalidad;
                $asistencia = DB::table('asistencias')->where(['user_id' => $id, 'fecha' => $fecha])->first();
                if ($asistencia && empty($asistencia->entrada)) {
                    DB::table('asistencias')->where(['user_id' => $id, 'fecha' => $fecha])->update([
                        'tipo_modalidad' => $tipo_modalidad,
                        'tipo_asistencia' => $tipo_modalidad == 4 ? 3 : 1,
                    ]);
                }
            }
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Personal actualizado', 'data' => ['user_id' => $id]]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // 🚦 Actualizar solo el estado de sincronización (usado por Node.js)
    public function actualizarEstado(Request $request, $id)
    {
        try {
            $estado_sync = $request->input('estado_sync');
            $user_id = $request->input('user_id');

            DB::beginTransaction();
            DB::table('personal')->where(['id' => $id, 'user_id' => $user_id])->update([
                'estado_sync' => $estado_sync,
            ]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Estado actualizado']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // 🗑️ Eliminar personal (marca como pendiente de eliminación)
    public function marcarEliminar($id)
    {
        try {
            DB::beginTransaction();
            DB::table('personal')->where('user_id', $id)->update([
                'estado_sync' => 3,
            ]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Personal marcado para eliminación']);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function cambiarEstatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'estado' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['required' => $validator->errors()], 422);
            }

            $id = $request->id;
            $estatus = $request->estado;

            DB::beginTransaction();
            DB::table('personal')->where('user_id', $id)->update([
                'estatus' => $estatus ? 0 : 1,
            ]);
            DB::commit();
            $estadoTexto = $estatus ? 'des' : '';

            return response()->json(['success' => true, 'message' => "Personal {$estadoTexto}activado correctamente."]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function cargarVacaciones($id)
    {
        try {
            $asistencias = DB::table('asistencias')->select('fecha')->where(['user_id' => $id, 'tipo_asistencia' => 8])->pluck('fecha');
            return ApiResponse::success('Consulta exitosa.', $asistencias);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('No se encontró el registro solicitado.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@showAsistencia] ' . $e->getMessage());
            return ApiResponse::error('Error al obtener el registro.');
        }
    }

    public function crearVacaciones(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'nuevas' => 'nullable|array',
                'nuevas.*' => 'nullable|date_format:Y-m-d',
                'eliminadas' => 'nullable|array',
                'eliminadas.*' => 'nullable|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
            }

            DB::beginTransaction();
            if (!empty($request->eliminadas)) {
                DB::table('asistencias')->where('user_id', $request->user_id)
                    ->whereIn('fecha', $request->eliminadas)
                    ->where('tipo_asistencia', 8)
                    ->delete();
            }
            foreach ($request->nuevas as $nueva) {
                DB::table('asistencias')->updateOrInsert(
                    ['user_id' => $request->user_id, 'fecha' => $nueva],
                    [
                        'tipo_asistencia' => 8,
                        'tipo_modalidad' => 5,
                    ]
                );
            }
            DB::commit();
            return ApiResponse::success('Exito al guardar las vacaciones.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@crearVacaciones] ' . $e->getMessage());
            return ApiResponse::error('Error al guardar las vacaciones.');
        }
    }

    public function cargarDescansos($id)
    {
        try {
            // Se usa tipo_asistencia = 9 para descansos
            $asistencias = DB::table('asistencias')
                ->select('fecha')
                ->where(['user_id' => $id, 'tipo_asistencia' => 9])
                ->pluck('fecha');

            return ApiResponse::success('Consulta exitosa.', $asistencias);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::notFound('No se encontró el registro solicitado.');
        } catch (Exception $e) {
            Log::error('[SyncPersonalController@cargarDescansos] ' . $e->getMessage());
            return ApiResponse::error('Error al obtener el registro.');
        }
    }

    public function crearDescansos(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'nuevas' => 'nullable|array',
                'nuevas.*' => 'nullable|date_format:Y-m-d',
                'eliminadas' => 'nullable|array',
                'eliminadas.*' => 'nullable|date_format:Y-m-d',
                // 'archivo' => 'required|file|mimes:pdf|max:10240',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
            }

            DB::beginTransaction();

            if (!empty($request->eliminadas)) {
                DB::table('asistencias')->where('user_id', $request->user_id)
                    ->whereIn('fecha', $request->eliminadas)
                    ->where('tipo_asistencia', 9)
                    ->delete();
            }

            if (!empty($request->nuevas)) {
                foreach ($request->nuevas as $nueva) {
                    DB::table('asistencias')->updateOrInsert(
                        ['user_id' => $request->user_id, 'fecha' => $nueva],
                        [
                            'tipo_asistencia' => 9,
                            'tipo_modalidad' => 5,
                        ]
                    );
                }
            }

            // $archivoPath = null;
            // if ($request->hasFile('archivo')) {
            //     $file = $request->file('archivo');
            //     $archivoPath = $file->store('descansos', 'public');
            // }

            /*if (!empty($request->eliminadas)) {
                $eliminadas = $request->eliminadas;
                DB::table('asistencias')->where('user_id', $request->user_id)
                    ->whereIn('fecha', $eliminadas)
                    ->where('tipo_asistencia', 9)
                    ->delete();

                DB::table('descansos_medicos')->where('user_id', $request->user_id)
                    ->whereIn('fecha', $eliminadas)
                    ->delete();
            }

            if (!empty($request->nuevas)) {
                $nuevas = $request->nuevas;

                foreach ($nuevas as $fecha) {
                    DB::table('asistencias')->updateOrInsert(
                        ['user_id' => $request->user_id, 'fecha' => $fecha],
                        [
                            'tipo_asistencia' => 9,
                            'tipo_modalidad' => 5,
                        ]
                    );

                    $existeDescanso = DB::table('descansos_medicos')
                        ->where('user_id', $request->user_id)
                        ->where('fecha', $fecha)
                        ->first();

                    if ($existeDescanso) {
                        if ($archivoPath) {
                            DB::table('descansos_medicos')
                                ->where('id', $existeDescanso->id)
                                ->update([
                                    'archivo' => $archivoPath,
                                ]);
                        }
                    } else {
                        DB::table('descansos_medicos')->insert([
                            'user_id' => $request->user_id,
                            'fecha' => $fecha,
                            'archivo' => $archivoPath,
                            'created_at' => now()->format('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }*/

            DB::commit();
            return ApiResponse::success('Descanso médico guardado correctamente.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('[SyncPersonalController@crearDescansos] ' . $e->getMessage());
            return ApiResponse::error('Error al guardar el descanso médico: ' . $e->getMessage());
        }
    }
}
