<?php

namespace App\Http\Controllers\Contratos;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Services\JsonDB;
use Auth;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ContratosController extends Controller
{
    public function view()
    {
        try {
            $empresa = DB::table('empresa')->get();
            $areas = DB::table('areas_personal')->get();

            return view('contratos.contratos', [
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
        $empresas = DB::table('empresa')->get()->keyBy('ruc');
        $contratos = DB::table('contratos')->select('id', 'user_id', 'fecha_inicio', 'fecha_fin', 'tipo_contrato', 'estatus')
            ->whereNot('estatus', 0)->get()->keyBy('user_id');

        $personal = DB::table('personal')
            ->select('id', 'user_id', 'area_id', 'empresa_ruc', 'dni', 'nombre', 'apellido', 'estado_sync')
            ->where('estatus', 1)
            ->whereNot('estado_sync', 4)->get()
            ->map(function ($p) use ($empresas, $contratos) {
                $empresa = $empresas->get($p->empresa_ruc) ?? null;
                $contrato = $contratos->get($p->user_id) ?? null;

                return [
                    'area' => $p->area_id,
                    'empresa' => "{$empresa->ruc} - {$empresa->razon_social}",
                    'dni' => $p->dni,
                    'nombre' => $p->nombre,
                    'apellido' => $p->apellido,
                    'fecha_inicio' => $contrato?->fecha_inicio,
                    'fecha_fin' => $contrato?->fecha_fin,
                    'tipo_contrato' => $contrato?->tipo_contrato,
                    'estatus' => $contrato?->estatus,
                    'acciones' => $this->DropdownAcciones([
                        'button' => [
                            ['clase' => 'btnNuevoContrato', 'attr' => 'data-id="' . $p->user_id . '"', 'texto' => '<i class="fa fa-edit me-2 text-info"></i> Nuevo Contrato'],
                        ],
                    ])
                ];
            });

        return response()->json($personal);
    }

    /**
     * 📄 Obtener un registro por ID
     */
    public function show($id)
    {
        $contrato = DB::table('contratos')->where('user_id', $id)->first();
        $trabajo_personal = DB::table('config_trabajo_personal')->where('user_id', $id)->first();

        if (!$contrato) {
            return response()->json(['message' => 'Contrato no encontrado'], 404);
        }

        // if (!$trabajo_personal) {
        //     return response()->json(['message' => 'Personal no encontrado'], 404);
        // }
        $contrato->trabajo_personal = $trabajo_personal ?: [];

        return response()->json($contrato);
    }

    // ✏️ Crear personal (desde el panel web)
    public function create(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|integer',
                'fecha_inicio' => 'required|date',
                'fecha_final' => 'required|date',
                'tipo_contrato' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['required' => $validator->errors()], 422);
            }

            DB::table('contratos')->insert([
                'user_id' => $request->user_id,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_final,
                'tipo_contrato' => $request->tipo_contrato,
                'creado_by' => Auth::user()->user_id,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Contrato creado', 'data' => ['user_id' => $request->user_id]], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // ✏️ Editar personal (desde la web)
    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date',
                'tipo_contrato' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['required' => $validator->errors()], 422);
            }

            DB::beginTransaction();
            DB::table('contratos')->where('id', $request->id)->update([
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'tipo_contrato' => $request->tipo_contrato,
            ]);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Contrato actualizado', 'data' => ['user_id' => $request->user_id]], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // 🗑️ Eliminar personal (desde la web)
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            DB::table('contratos')->where('id', $id)->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Contrato eliminado', 'data' => ['id' => $id]], 200);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
