<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Respuesta exitosa con datos opcionales.
     */
    public static function success(string $message = 'Operación realizada con éxito.', $data = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?: null,
        ], $status);
    }

    /**
     * Respuesta de error general.
     */
    public static function error(string $message = 'Ocurrió un error inesperado.', string $errorDetail = '', int $status = 500, $data = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'data' => $data,
        ];

        if ($errorDetail) {
            $response['error'] = $errorDetail;
        }

        return response()->json($response, $status);
    }
    
    /**
     * Respuesta para errores de solicitud incorrecta.
     */
    public static function badRequest(string $message = 'Solicitud incorrecta.'): JsonResponse
    {
        return self::error($message, '', 400);
    }

    /**
     * Respuesta cuando no se encuentra un recurso.
     */
    public static function notFound(string $message = 'Recurso no encontrado.'): JsonResponse
    {
        return self::error($message, '', 404);
    }


    /**
     * Respuesta para errores de validación.
     */
    public static function validation(array $errors, string $message = 'Errores de validación.'): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }
}
