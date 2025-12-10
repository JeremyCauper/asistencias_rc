<?php

namespace App\Http\Controllers\MediaArchivo;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class MediaArchivoController extends Controller
{
    public function uploadMedia(Request $request, $carpeta)
    {
        try {
            if (!$request->hasFile('file')) {
                return ApiResponse::error('No se ha subido ningún archivo.');
            }

            $file = $request->file('file');
            $mime = $file->getClientMimeType();
            $isImage = str_starts_with($mime, 'image/');
            $isVideo = str_starts_with($mime, 'video/');
            $isPdf = str_starts_with($mime, 'application/pdf');

            if (!($isImage || $isVideo || $isPdf)) {
                return ApiResponse::error('El archivo debe ser una imagen, video o PDF.');
            }

            // límites
            $maxImage = 10 * 1024 * 1024;
            $maxVideo = 10 * 1024 * 1024;
            $maxPdf = 5 * 1024 * 1024;

            if ($isImage && $file->getSize() > $maxImage) {
                return ApiResponse::error('La imagen es demasiado grande, debe ser menor a 10MB');
            }
            if ($isVideo && $file->getSize() > $maxVideo) {
                return ApiResponse::error('El video es demasiado grande, debe ser menor a 10MB');
            }
            if ($isPdf && $file->getSize() > $maxPdf) {
                return ApiResponse::error('El PDF es demasiado grande, debe ser menor a 5MB');
            }

            // NOMBRE ÚNICO
            $extension = $file->getClientOriginalExtension();
            $nombre_archivo = time() . '_' . bin2hex(random_bytes(8));
            $nombre = $nombre_archivo . '.' . $extension;

            $folder = 'asistencias_rc/' . ($carpeta ?? 'media') . '/' . date('Y/m');
            $path = $folder . '/' . $nombre;

            // Crear carpeta dentro de public/
            $fullFolderPath = public_path($folder);

            if (!file_exists($fullFolderPath)) {
                mkdir($fullFolderPath, 0755, true);
            }

            // Guardar archivo correctamente
            if (!file_put_contents($fullFolderPath . '/' . $nombre, file_get_contents($file))) {
                return ApiResponse::error('No se pudo guardar el archivo.');
            }

            DB::beginTransaction();
            DB::table('media_archivos')->insert([
                'nombre_archivo' => $nombre_archivo,
                'path_archivo' => $path,
            ]);
            DB::commit();

            return ApiResponse::success('Archivo subido correctamente.', [
                'url' => $path,
                'nombre_archivo' => $nombre_archivo,
            ]);
        } catch (Exception $e) {
            Log::error('[MediaArchivoController@uploadMedia] ' . $e->getMessage());
            return ApiResponse::error('No se pudo subir el archivo.');
        }
    }

    public static function uploadFileS3($nombresArchivos, int $asistenciaId)
    {
        // Obtener los registros desde la BD
        $archivos = DB::table('media_archivos')
            ->whereIn('nombre_archivo', $nombresArchivos)
            ->get();

        if ($archivos->isEmpty()) {
            throw new Exception("No se encontraron archivos en media_archivos.");
        }

        foreach ($archivos as $archivo) {
            try {
                $path_archivo = $archivo->path_archivo;
                $nombre_archivo = $archivo->nombre_archivo;
                $rutaLocal = public_path($path_archivo);

                $dirname = pathinfo($path_archivo, PATHINFO_DIRNAME); // solo la carpeta
                $filename = pathinfo($path_archivo, PATHINFO_BASENAME); // nombre + extensión

                if (!file_exists($rutaLocal)) {
                    Log::error("Archivo no encontrado: {$rutaLocal}");
                    throw new Exception("No se encontró el archivo local: {$nombre_archivo}");
                }

                $rutaS3 = Storage::disk('s3')->putFileAs(
                    $dirname,
                    new \Illuminate\Http\File($rutaLocal),
                    $filename
                );

                if (!$rutaS3) {
                    throw new Exception("Error al subir a S3: {$nombre_archivo}");
                }

                $urlS3 = Storage::disk('s3')->url($rutaS3);

                DB::table('media_archivos')->where('id', $archivo->id)
                    ->update([
                        'asistencia_id' => $asistenciaId,
                        'estatus' => 1,
                        'url_publica' => $urlS3
                    ]);

                // ELIMINAR ARCHIVO LOCAL SOLO SI TODO SALIÓ BIEN
                try {
                    unlink($rutaLocal);
                } catch (\Throwable $t) {
                    // Si falla la eliminación local, no debe romper todo el proceso
                    Log::warning("No se pudo eliminar archivo local: {$rutaLocal}. Error: {$t->getMessage()}");
                }
            } catch (Exception $e) {
                Log::error('[MediaArchivoController@uploadFileS3] Archivo: {$archivo->nombre_archivo}: ' . $e->getMessage());
                // Lanzar nuevamente para que el método principal haga rollback
                throw $e;
            }
        }
    }

    public function deleteFile()
    {
        try {
            $ruta = 'asistencias_rc/justificaciones/2025/11/1763710725_59a36764f39fcba1.webp';

            $eliminado = Storage::disk('s3')->delete($ruta);

            if (!$eliminado) {
                return response()->json([
                    'ok' => false,
                    'mensaje' => 'El archivo no existe o no se pudo eliminar.'
                ]);
            }

            return response()->json([
                'ok' => true,
                'mensaje' => 'Archivo eliminado correctamente.'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}
