<?php

namespace App\Http\Controllers\MantenimientosDeveloper\Menu;

use App\Http\Controllers\Controller;
use App\Services\JsonDB;
use App\Helpers\ApiResponse;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MenuController extends Controller
{
    public function __construct()
    {
        JsonDB::schema('menu', [
            'id' => 'int|primary_key|auto_increment',
            'descripcion' => 'string|unique:"Descripcion"',
            'icon' => 'string|unique:"Icono"',
            'ruta' => 'string|unique:"Ruta"',
            'submenu' => 'int|default:0',
            'sistema' => 'int|default:0',
            'orden' => 'int',
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
            return view('mantenimiento_developer.menu.menu');
        } catch (Exception $e) {
            Log::error('[MenuController@view] ' . $e->getMessage());
            return ApiResponse::error('Error al cargar la vista del módulo.', $e->getMessage());
        }
    }

    public function listar()
    {
        try {
            $menus = JsonDB::table('menu')
                ->where('eliminado', 0)
                ->get()
                ->map(function ($val) {
                    return [
                        'id'          => $val->id,
                        'orden'       => $val->orden,
                        'descripcion' => $val->descripcion,
                        'icono'       => $val->icon,
                        'iconText'    => '<i class="' . ($val->icon ?? '') . '"></i> ' . ($val->icon ?? ''),
                        'ruta'        => $val->ruta,
                        'submenu'     => ($val->submenu ?? 0) ? 'Sí' : 'No',
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

            return ApiResponse::success('Listado obtenido correctamente.', $menus);
        } catch (Exception $e) {
            Log::error('[MenuController@listar] ' . $e->getMessage());
            return ApiResponse::error('No se pudo obtener el listado.');
        }
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'descripcion' => 'required|string|max:50',
            'icono'       => 'required|string',
            'ruta'        => 'required|string|max:255',
            'submenu'     => 'required|integer',
            'desarrollo'  => 'required|integer',
            'estado'      => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
        }

        try {
            $nuevoOrden = count(JsonDB::table('menu')->get()) + 1;

            JsonDB::table('menu')->insert([
                'descripcion' => $request->descripcion,
                'icon'        => $request->icono,
                'ruta'        => $request->ruta,
                'submenu'     => $request->submenu,
                'sistema'     => $request->desarrollo,
                'orden'       => $nuevoOrden,
                'estatus'     => $request->estado,
                'created_at'  => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se creó correctamente.');
        } catch (Exception $e) {
            Log::error('[MenuController@create] ' . $e->getMessage());
            return ApiResponse::error('No se pudo crear el registro.');
        }
    }

    public function show($id)
    {
        try {
            $registro = JsonDB::table('menu')->where('id', $id)->first();

            if (!$registro) {
                return ApiResponse::notFound('No se encontró el tipo de asistencia solicitado.');
            }

            return ApiResponse::success('Registro obtenido correctamente.', $registro);
        } catch (Exception $e) {
            Log::error('[MenuController@show] ' . $e->getMessage());
            return ApiResponse::error('Error al obtener el registro.');
        }
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'          => 'required|integer',
            'descripcion' => 'required|string|max:50',
            'icono'       => 'required|string',
            'ruta'        => 'required|string|max:255',
            'submenu'     => 'required|integer',
            'desarrollo'  => 'required|integer',
            'estado'      => 'required|integer|in:0,1',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            JsonDB::table('menu')->where('id', $request->id)->update([
                'descripcion' => $request->descripcion,
                'icon'        => $request->icono,
                'ruta'        => $request->ruta,
                'submenu'     => $request->submenu,
                'sistema'     => $request->desarrollo,
                'estatus'     => $request->estado,
                'updated_at'  => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El registro se actualizó correctamente.');
        } catch (Exception $e) {
            Log::error('[MenuController@update] ' . $e->getMessage());
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
            JsonDB::table('menu')->where('id', $request->id)->update([
                'estatus'    => $request->estado,
                'updated_at' => now()->format('Y-m-d H:i:s')
            ]);

            return ApiResponse::success('El estado se cambió correctamente.');
        } catch (Exception $e) {
            Log::error('[MenuController@changeStatus] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el estado.');
        }
    }

    /**
     * Cambia el orden de los menús.
     */
    public function changeOrdenMenu(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray());
        }

        try {
            // Se espera que $request->data sea un arreglo con 'id' y 'orden'
            foreach ($request->data as $item) {
                JsonDB::table('menu')->where('id', $item['id'])->update([
                    'orden' => $item['orden'],
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ]);
            }

            return ApiResponse::success('Orden actualizado con éxito.');
        } catch (Exception $e) {
            Log::error('[MenuController@changeStatus] ' . $e->getMessage());
            return ApiResponse::error('Error al cambiar el orden.');
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
            JsonDB::table('menu')->where('id', $request->id)->update([
                'eliminado'  => 1
            ]);

            return ApiResponse::success('El registro se eliminó correctamente.');
        } catch (Exception $e) {
            Log::error('[MenuController@delete] ' . $e->getMessage());
            return ApiResponse::error('Error al eliminar el registro.');
        }
    }
}