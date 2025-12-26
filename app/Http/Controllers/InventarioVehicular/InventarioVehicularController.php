<?php

namespace App\Http\Controllers\InventarioVehicular;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class InventarioVehicularController extends Controller
{
    public function view()
    {
        try {
            $personal = DB::table('personal')->where('estatus', 1)->get();
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
            $isAdmin = in_array(Auth::user()->rol_system, [2, 4, 7]);
            $personals = DB::table('personal')->get()->keyBy('user_id');
            $vehiculos = DB::table('inventario_vehicular');
            if (!$isAdmin) {
                $vehiculo_ids = DB::table('inventario_vehicular_asignado')->where('user_id', Auth::user()->user_id)
                    ->pluck('vehiculo_id')
                    ->toArray();

                $vehiculos = $vehiculos->whereIn('id', $vehiculo_ids);
            }
            $vehiculos = $vehiculos
                ->get()
                ->map(function ($val) use ($personals, $isAdmin) {
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
                        'tarjeta_propiedad_pdf' => $val->tarjeta_propiedad_pdf,
                        'soat_pdf' => $val->soat_pdf,
                        'r_tecnica_pdf' => $val->r_tecnica_pdf,
                        'v_chip_pdf' => $val->v_chip_pdf,
                        'v_cilindro_pdf' => $val->v_cilindro_pdf,
                        'updated_at' => $val->updated_at,
                        'created_at' => $val->created_at,
                        'acciones' => $isAdmin ? $this->DropdownAcciones([
                            'tittle' => 'Acciones',
                            'button' => [
                                ['funcion' => "Editar('{$val->id}')", 'texto' => '<i class="fas fa-pen me-2 text-info"></i>Editar'],
                                ['funcion' => "Asignar('{$val->id}')", 'texto' => '<i class="fas fa-user-plus me-2 text-secondary"></i>Asignar'],
                            ],
                        ]) : ''
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

            'soat' => 'nullable|date',
            'r_tecnica' => 'nullable|date',
            'v_chip' => 'nullable|date',
            'v_cilindro' => 'nullable|date',
            
            'file_tarjeta_propiedad' => 'nullable|file|mimes:pdf|max:5120',
            'file_soat' => 'nullable|file|mimes:pdf|max:5120',
            'file_inspeccion' => 'nullable|file|mimes:pdf|max:5120',
            'file_chip' => 'nullable|file|mimes:pdf|max:5120',
            'file_cilindro' => 'nullable|file|mimes:pdf|max:5120',

        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
        }

        $existe = DB::table('inventario_vehicular')->where('placa', $request->placa)->exists();
        if ($existe) {
            return ApiResponse::error('Ya existe un registro con la placa proporcionada.');
        }

        try {

            $soatPdf = null;
            $rTecnicaPdf = null;
            $vChipPdf = null;
            $vCilindroPdf = null;

            if ($request->hasFile('file_tarjeta_propiedad')) {
                $tarjetaPropiedadPdf = $this->uploadFileToS3($request->file('file_tarjeta_propiedad'), $request->placa, 'tarjeta_propiedad');
            }

            if ($request->hasFile('file_soat')) {
                $soatPdf = $this->uploadFileToS3($request->file('file_soat'), $request->placa, 'soat');
            }

            if ($request->hasFile('file_inspeccion')) {
                $rTecnicaPdf = $this->uploadFileToS3($request->file('file_inspeccion'), $request->placa, 'r_tecnica');
            }

            if ($request->hasFile('file_chip')) {
                $vChipPdf = $this->uploadFileToS3($request->file('file_chip'), $request->placa, 'chip');
            }

            if ($request->hasFile('file_cilindro')) {
                $vCilindroPdf = $this->uploadFileToS3($request->file('file_cilindro'), $request->placa, 'cilindro');
            }

            DB::beginTransaction();
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
                'tarjeta_propiedad_pdf' => $tarjetaPropiedadPdf,
                'soat_pdf' => $soatPdf,
                'r_tecnica_pdf' => $rTecnicaPdf,
                'v_chip_pdf' => $vChipPdf,
                'v_cilindro_pdf' => $vCilindroPdf,
                'created_at' => now()->format('Y-m-d H:i:s')
            ]);
            DB::commit();

            return ApiResponse::success('El registro se creó correctamente.');
        } catch (Exception $e) {
            DB::rollBack();
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
            $registro->personal_asignados = DB::table('inventario_vehicular_asignado')->where('vehiculo_id', $id)
                ->pluck('user_id')
                ->toArray();

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
            'placa' => 'required|string|max:20',
            'modelo' => 'required|string|max:50',
            'marca' => 'required|string|max:20',
            'tipo_registro' => 'required|string|max:20',
            'popietario' => 'nullable|string|max:20',

            'soat' => 'nullable|date',
            'r_tecnica' => 'nullable|date',
            'v_chip' => 'nullable|date',
            'v_cilindro' => 'nullable|date',

            'file_tarjeta_propiedad' => 'nullable|file|mimes:pdf|max:5120',
            'file_soat' => 'nullable|file|mimes:pdf|max:5120',
            'file_inspeccion' => 'nullable|file|mimes:pdf|max:5120',
            'file_chip' => 'nullable|file|mimes:pdf|max:5120',
            'file_cilindro' => 'nullable|file|mimes:pdf|max:5120',

        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
        }

        $existe = DB::table('inventario_vehicular')->whereNot('id', $request->id)->where('placa', $request->placa)->exists();
        if ($existe) {
            return ApiResponse::error('Ya existe un registro con la placa proporcionada.');
        }

        try {

            $soatPdf = null;
            $rTecnicaPdf = null;
            $vChipPdf = null;
            $vCilindroPdf = null;

            if ($request->hasFile('file_tarjeta_propiedad')) {
                $tarjetaPropiedadPdf = $this->uploadFileToS3($request->file('file_tarjeta_propiedad'), $request->placa, 'tarjeta_propiedad');
            }

            if ($request->hasFile('file_soat')) {
                $soatPdf = $this->uploadFileToS3($request->file('file_soat'), $request->placa, 'soat');
            }

            if ($request->hasFile('file_inspeccion')) {
                $rTecnicaPdf = $this->uploadFileToS3($request->file('file_inspeccion'), $request->placa, 'r_tecnica');
            }

            if ($request->hasFile('file_chip')) {
                $vChipPdf = $this->uploadFileToS3($request->file('file_chip'), $request->placa, 'chip');
            }

            if ($request->hasFile('file_cilindro')) {
                $vCilindroPdf = $this->uploadFileToS3($request->file('file_cilindro'), $request->placa, 'cilindro');
            }

            DB::beginTransaction();
            DB::table('inventario_vehicular')->where('id', $request->id)
                ->update([
                    'placa' => $request->placa,
                    'tipo_registro' => $request->tipo_registro,
                    'user_id' => $request->popietario,
                    'modelo' => $request->modelo,
                    'marca' => $request->marca,
                    'soat' => $request->soat,
                    'r_tecnica' => $request->r_tecnica,
                    'v_chip' => $request->v_chip,
                    'v_cilindro' => $request->v_cilindro,
                    'tarjeta_propiedad_pdf' => $tarjetaPropiedadPdf,
                    'soat_pdf' => $soatPdf,
                    'r_tecnica_pdf' => $rTecnicaPdf,
                    'v_chip_pdf' => $vChipPdf,
                    'v_cilindro_pdf' => $vCilindroPdf,
                    'updated_at' => now()->format('Y-m-d H:i:s')
                ]);
            DB::commit();

            return ApiResponse::success('El registro se actualizó correctamente.');
        } catch (Exception $e) {
            Log::error('[InventarioVehicularController@update] ' . $e->getMessage());
            return ApiResponse::error('Error al actualizar el registro.');
        }
    }

    public function asignar(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'vehiculo_id' => 'required|integer',
                'nuevas' => 'nullable|array',
                'nuevas.*' => 'nullable|integer',
                'eliminadas' => 'nullable|array',
                'eliminadas.*' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator->errors()->toArray(), 'Los datos proporcionados no son válidos.');
            }

            DB::beginTransaction();
            if (!empty($request->eliminadas)) {
                DB::table('inventario_vehicular_asignado')->where('vehiculo_id', $request->vehiculo_id)
                    ->whereIn('user_id', $request->eliminadas)
                    ->delete();
            }
            foreach ($request->nuevas as $nueva) {
                DB::table('inventario_vehicular_asignado')->insert(['vehiculo_id' => $request->vehiculo_id, 'user_id' => $nueva, 'accion' => 0]);
            }
            DB::commit();
            return ApiResponse::success('Exito al guardar las asignaciones.');
        } catch (Exception $e) {
            Log::error('[AsistenciaController@crearVacaciones] ' . $e->getMessage());
            return ApiResponse::error('Error al guardar las asignaciones.');
        }
    }

    private function uploadFileToS3($file, $placa, $suffix)
    {
        $ext = $file->getClientOriginalExtension();
        $app_env = env('APP_ENV', 'development');
        $hash = uniqid();
        $filename = "{$placa}_{$suffix}_{$hash}.{$ext}";
        $dirname = "asistencias_rc/{$app_env}/vehiculos/{$placa}";

        $path = Storage::disk('s3')->putFileAs(
            $dirname,
            $file,
            $filename
        );

        if (!$path) {
            throw new Exception("Error al subir archivo {$filename}");
        }

        return Storage::disk('s3')->url($path);
    }

}
