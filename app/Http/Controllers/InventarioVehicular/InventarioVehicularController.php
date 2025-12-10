<?php

namespace App\Http\Controllers\InventarioVehicular;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventarioVehicularController extends Controller
{
    public function view()
    {
        try {
            return view('inventario_vehicular.inventario_vehicular');
        } catch (Exception $e) {
            Log::error('[InventarioVehicularController@view] ' . $e->getMessage());
            return ApiResponse::error('Error al cargar la vista del módulo.', $e->getMessage());
        }
    }

    public function listar()
    {
        try {
            $vehiculos = DB::table('inventario_vehicular')
                ->get()
                ->map(function ($val) {
                    return [
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
                                ['funcion' => "Editar('{$val->placa}')", 'texto' => '<i class="fas fa-pen me-2 text-info"></i>Editar'],
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
}
