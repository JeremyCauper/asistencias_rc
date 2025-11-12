<?php

namespace App\Http\Controllers\MantenimientosDeveloper\TipoModalidad;

use App\Http\Controllers\Controller;
use App\Services\JsonDB;
use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TipoModalidadController extends Controller
{
    public function __construct()
    {
        JsonDB::schema('tipo_modalidad', [
            'id'          => 'int|primary_key|auto_increment',
            'descripcion' => 'string|unique:"Descripcion"',
            'icono' => 'string',
            'color'       => 'string|unique:"Color"',
            'estatus'     => 'int|default:1',
            'selected'    => 'int|default:0',
            'eliminado'   => 'int|default:0',
            'updated_at'  => 'string|default:""',
            'created_at'  => 'string|default:""'
        ]);
    }

    public function view()
    {
        try {
            return view('mantenimiento_developer.tipomodalidad.tipomodalidad');
        } catch (Exception $e) {
            Log::error('[TipoModalidadController@view] ' . $e->getMessage());
            return ApiResponse::error('Error al cargar la vista del módulo.', $e->getMessage());
        }
    }

    public function listar()
    {
        try {
            $tipoModalidad = JsonDB::table('tipo_modalidad')
                ->where('eliminado', 0)
                ->get()
                ->map(function ($val) {
                    return [
                        'id'          => $val->id,
                        'descripcion' => $val->descripcion,
                        'icono'       => $val->icono,
                        'color'       => $val->color,
                        'estado'      => $this->formatEstado($val->estatus),
                        'updated_at'  => $val->updated_at,
                        'created_at'  => $val->created_at,
                        'acciones'    => $this->DropdownAcciones([
                            'tittle' => 'Acciones',
                            'button' => [
                                ['funcion' => "Editar({$val->id})", 'texto' => '<i class="fas fa-pen me-2 text-info"></i>Editar'],
                                ['funcion' => "CambiarEstado({$val->id}, {$val->estatus})", 'texto' => $this->formatEstado($val->estatus, 'change')],
                                ['funcion' => "Eliminar({$val->id})", 'texto' => '<i class="far fa-trash-can me-2 text-danger"></i>Eliminar'],
                            ],
                        ])
                    ];
                });

            return ApiResponse::success('Listado obtenido correctamente.', $tipoModalidad);
        } catch (Exception $e) {
            Log::error('[TipoModalidadController@listar] ' . $e->getMessage());
            return ApiResponse::error('No se pudo obtener el listado.');
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:50',
            'icono'       => 'required|string',
            'color'       => 'required|string|max:20',
            'estado'      => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
        }

        try {
            JsonDB::table('tipo_modalidad')->insert([
                'descripcion' => $request->descripcion,
                'icono'       => $request->icono,
                'color'       => $request->color,
                'estatus'     => $request->estado,
                'created_at'  => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se creó correctamente.');
        } catch (Exception $e) {
            Log::error('[TipoModalidadController@create] ' . $e->getMessage());
            return ApiResponse::error('No se pudo crear el registro.');
        }
    }

    public function show($id)
    {
        try {
            $registro = JsonDB::table('tipo_modalidad')->where('id', $id)->first();

            if (!$registro) {
                return ApiResponse::notFound('No se encontró el tipo de asistencia solicitado.');
            }

            return ApiResponse::success('Registro obtenido correctamente.', $registro);
        } catch (Exception $e) {
            Log::error('[TipoModalidadController@show] ' . $e->getMessage());
            return ApiResponse::error('Error al obtener el registro.');
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer',
            'descripcion' => 'required|string|max:50',
            'icono'       => 'required|string',
            'color'       => 'required|string|max:20',
            'estado'      => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            JsonDB::table('tipo_modalidad')->where('id', $request->id)->update([
                'descripcion' => $request->descripcion,
                'icono'       => $request->icono,
                'color'       => $request->color,
                'estatus'     => $request->estado,
                'updated_at'  => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se actualizó correctamente.');
        } catch (Exception $e) {
            Log::error('[TipoModalidadController@update] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }

    public function changeStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|integer',
            'estado' => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            JsonDB::table('tipo_modalidad')->where('id', $request->id)->update([
                'estatus'    => $request->estado,
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El estado se cambió correctamente.');
        } catch (Exception $e) {
            Log::error('[TipoModalidadController@changeStatus] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el estado.');
        }
    }

    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            JsonDB::table('tipo_modalidad')->where('id', $request->id)->update([
                'eliminado'  => 1
            ]);

            return ApiResponse::success('El registro se eliminó correctamente.');
        } catch (Exception $e) {
            Log::error('[TipoModalidadController@delete] ' . $e->getMessage());
            return ApiResponse::error('Error al eliminar el registro.');
        }
    }
}
