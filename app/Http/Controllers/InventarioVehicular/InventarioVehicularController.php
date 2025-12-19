<?php

namespace App\Http\Controllers\InventarioVehicular;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class InventarioVehicularController extends Controller
{
    public function view()
    {
        try {
            $personal = DB::table('personal')->get();
            return view('inventario_vehicular.inventario_vehicular', [
                'personal' => $personal
            ]);
        } catch (Exception $e) {
            Log::error('[InventarioVehicularController@view] ' . $e->getMessage());
            return ApiResponse::error('Error al cargar la vista del módulo.', $e->getMessage());
        }
    }

    public function listar()
    {
        try {
            $personals = DB::table('personal')->get()->keyBy('user_id');
            $vehiculos = DB::table('inventario_vehicular')
                ->get()
                ->map(function ($val) use ($personals) {
                    $personal = $personals->get($val->user_id);
                    $propietario = 'Empresa';
                    if ($personal) {
                        $propietario = "$personal->nombre $personal->apellido";
                    }

                    return [
                        'propietario' => $propietario,
                        'placa' => $val->placa,
                        'tipo_registro' => $val->tipo_registro,
                        'modelo' => $val->modelo,
                        'marca' => $val->marca,
                        'soat' => $val->soat,
                        'r_tecnica' => $val->r_tecnica,
                        'v_chip' => $val->v_chip,
                        'v_cilindro' => $val->v_cilindro,
                        'updated_at' => $val->updated_at,
                        'created_at' => $val->created_at,
                        'acciones' => $this->DropdownAcciones([
                            'tittle' => 'Acciones',
                            'button' => [
                                ['funcion' => "Editar('{$val->id}')", 'texto' => '<i class="fas fa-pen me-2 text-info"></i>Editar'],
                                ['funcion' => "Asignar('{$val->id}')", 'texto' => '<i class="fas fa-user-plus me-2 text-secondary"></i>Asignar'],
                            ],
                        ])
                    ];
                });

            return ApiResponse::success('Listado obtenido correctamente.', $vehiculos);
        } catch (Exception $e) {
            Log::error('[InventarioVehicularController@listar] ' . $e->getMessage());
            return ApiResponse::error('No se pudo obtener el listado.');
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'placa' => 'required|string|max:20',
            'modelo' => 'required|string|max:50',
            'marca' => 'required|string|max:20',
            'tipo_registro' => 'required|string|max:20',
            'popietario' => 'nullable|string|max:20',
            'soat' => 'nullable|string',
            'r_tecnica' => 'nullable|string',
            'v_chip' => 'nullable|string',
            'v_cilindro' => 'nullable|string',
            'file_soat' => 'nullable|string',
            'file_inspeccion' => 'nullable|string',
            'file_chip' => 'nullable|string',
            'file_cilindro' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
        }

        return ApiResponse::error('Ya existe.', '', 500, $request->all());

        $existe = DB::table('inventario_vehicular')->where('placa', $request->placa)->exists();
        if ($existe) {
            return ApiResponse::error('Ya existe un registro con la placa proporcionada.');
        }

        try {
            DB::table('inventario_vehicular')->insert([
                'placa' => $request->placa,
                'tipo_registro' => $request->tipo_registro,
                'user_id' => $request->popietario,
                'modelo' => $request->modelo,
                'marca' => $request->marca,
                'soat' => $request->soat,
                'r_tecnica' => $request->r_tecnica,
                'v_chip' => $request->v_chip,
                'v_cilindro' => $request->v_cilindro,
                'soat_pdf' => $request->file_soat,
                'r_tecnica_pdf' => $request->file_inspeccion,
                'v_chip_pdf' => $request->file_chip,
                'v_cilindro_pdf' => $request->file_cilindro,
                'created_at' => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se creó correctamente.');
        } catch (Exception $e) {
            Log::error('[InventarioVehicularController@create] ' . $e->getMessage());
            return ApiResponse::error('No se pudo crear el registro.');
        }
    }

    public function show($id)
    {
        try {
            $registro = DB::table('inventario_vehicular')->where('id', $id)->first();

            if (!$registro) {
                return ApiResponse::notFound('No se encontró el registro solicitado.');
            }

            return ApiResponse::success('Registro obtenido correctamente.', $registro);
        } catch (Exception $e) {
            Log::error('[InventarioVehicularController@show] ' . $e->getMessage());
            return ApiResponse::error('Error al obtener el registro.');
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'descripcion' => 'required|string|max:50',
            'color' => 'required|string|max:20',
            'estado' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            DB::table('inventario_vehicular')->where('id', $request->id)->update([
                'descripcion' => $request->descripcion,
                'color' => $request->color,
                'estatus' => $request->estado,
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se actualizó correctamente.');
        } catch (Exception $e) {
            Log::error('[InventarioVehicularController@update] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }
}
