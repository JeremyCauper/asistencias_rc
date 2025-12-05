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
            Log::error('[MisAsistenciaController@uploadMedia] ' . $e->getMessage());
            return ApiResponse::error('No se pudo subir el archivo.');
        }
    }
}
