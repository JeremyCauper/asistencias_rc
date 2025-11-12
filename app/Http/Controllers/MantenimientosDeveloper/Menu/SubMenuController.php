<?php

namespace App\Http\Controllers\MantenimientosDeveloper\Menu;

use App\Http\Controllers\Controller;
use App\Services\JsonDB;
use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SubMenuController extends Controller
{
    public function __construct()
    {
        JsonDB::schema('sub_menu', [
            'id' => 'int|primary_key|auto_increment',
            'id_menu' => 'int',
            'descripcion' => 'string|unique:"Descripcion"',
            'categoria' => 'string|default:""',
            'ruta' => 'string|unique:"Ruta"',
            'estatus' => 'int|default:1',
            'selected' => 'int|default:0',
            'eliminado' => 'int|default:0',
            'updated_at' => 'string|default:""',
            'created_at' => 'string|default:""'
        ]);
    }

    public function view()
    {
        try {
            $menus = JsonDB::table('menu')->select('id', 'descripcion', 'icon', 'estatus', 'eliminado')->get();
            return view('mantenimiento_developer.menu.submenu', [
                'menus' => $menus
            ]);
        } catch (Exception $e) {
            Log::error('[SubMenuController@view] ' . $e->getMessage());
            return ApiResponse::error('Error al cargar la vista del módulo.', $e->getMessage());
        }
    }

    public function listar()
    {
        try {
            $sub_menus = JsonDB::table('sub_menu')
                ->where('eliminado', 0)
                ->get()
                ->map(function ($val) {
                    return [
                        'id'          => $val->id,
                        'menu'        => $val->id_menu,
                        'categoria'   => $val->categoria,
                        'descripcion' => $val->descripcion,
                        'ruta'        => $val->ruta,
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

            return ApiResponse::success('Listado obtenido correctamente.', $sub_menus);
        } catch (Exception $e) {
            Log::error('[SubMenuController@listar] ' . $e->getMessage());
            return ApiResponse::error('No se pudo obtener el listado.');
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'menu'        => 'required|integer',
            'categoria'   => 'nullable|string',
            'descripcion' => 'required|string|max:50',
            'ruta'        => 'required|string|max:255',
            'estado'      => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
        }

        try {
            JsonDB::table('sub_menu')->insert([
                'id_menu'     => $request->menu,
                'categoria'   => $request->categoria ?? '',
                'descripcion' => $request->descripcion,
                'ruta'        => $request->ruta,
                'estatus'     => $request->estado,
                'created_at'  => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se creó correctamente.');
        } catch (Exception $e) {
            Log::error('[SubMenuController@create] ' . $e->getMessage());
            return ApiResponse::error('No se pudo crear el registro.');
        }
    }

    public function show($id)
    {
        try {
            $registro = JsonDB::table('sub_menu')->where('id', $id)->first();

            if (!$registro) {
                return ApiResponse::notFound('No se encontró el tipo de asistencia solicitado.');
            }

            return ApiResponse::success('Registro obtenido correctamente.', $registro);
        } catch (Exception $e) {
            Log::error('[SubMenuController@show] ' . $e->getMessage());
            return ApiResponse::error('Error al obtener el registro.');
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer',
            'menu'        => 'required|integer',
            'categoria'   => 'nullable|string',
            'descripcion' => 'required|string|max:50',
            'ruta'        => 'required|string|max:255',
            'estado'      => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            JsonDB::table('sub_menu')->where('id', $request->id)->update([
                'id_menu'     => $request->menu,
                'categoria'   => $request->categoria ?? '',
                'descripcion' => $request->descripcion,
                'ruta'        => $request->ruta,
                'estatus'     => $request->estado,
                'updated_at'  => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se actualizó correctamente.');
        } catch (Exception $e) {
            Log::error('[SubMenuController@update] ' . $e->getMessage());
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
            JsonDB::table('sub_menu')->where('id', $request->id)->update([
                'estatus'    => $request->estado,
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El estado se cambió correctamente.');
        } catch (Exception $e) {
            Log::error('[SubMenuController@changeStatus] ' . $e->getMessage());
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
            JsonDB::table('sub_menu')->where('id', $request->id)->update([
                'eliminado'  => 1
            ]);

            return ApiResponse::success('El registro se eliminó correctamente.');
        } catch (Exception $e) {
            Log::error('[SubMenuController@delete] ' . $e->getMessage());
            return ApiResponse::error('Error al eliminar el registro.');
        }
    }
}
