<?php

namespace App\Http\Controllers;

use FFI\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MensajeController extends Controller
{
    public function index()
    {
        // Obtener últimos mensajes
        $mensajes = DB::table('mensajes')->orderBy('created_at', 'desc')->get();

        return view('editor', compact('mensajes'));
    }

    public function guardar(Request $request)   
    {
        try {
            $validator = Validator::make($request->all(), [
                'contenido' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['required' => $validator->errors()], 422);
            }

            DB::table('mensajes')->insert([
                'contenido_html' => $request->contenido,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ]);

            return response()->json(['ok' => true]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function uploadMedia(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['error' => 'No se recibió archivo'], 400);
        }

        $file = $request->file('file');
        $mime = $file->getClientMimeType();
        $isImage = str_starts_with($mime, 'image/');
        $isVideo = str_starts_with($mime, 'video/');
        $isPdf = str_starts_with($mime, 'application/pdf');

        if (!($isImage || $isVideo || $isPdf)) {
            return response()->json(['error' => 'Tipo de archivo no permitido'], 415);
        }

        // Límites
        $maxImage = 3 * 1024 * 1024; // 3MB
        $maxVideo = 10 * 1024 * 1024; // 10MB
        $maxPdf = 10 * 1024 * 1024; // 10MB

        if ($isImage && $file->getSize() > $maxImage) {
            return response()->json(['error' => 'Imagen mayor a 3MB'], 413);
        }
        if ($isVideo && $file->getSize() > $maxVideo) {
            return response()->json(['error' => 'Video mayor a 10MB'], 413);
        }
        if ($isPdf && $file->getSize() > $maxPdf) {
            return response()->json(['error' => 'Pdf mayor a 10MB'], 413);
        }

        // Guardar en public/media/año/mes
        $path = $file->store('media/' . date('Y/m'), 'public');
        $url = Storage::url("app/public/$path");

        return response()->json(['url' => "/asistencias_rc{$url}"]);
    }
}
