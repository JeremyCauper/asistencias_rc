<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

class ConsultasController extends Controller
{
    public function ConsultaDoc(Request $request)
    {
        try {
            $doc = $request->query('doc');

            if (empty($doc) || !ctype_digit($doc)) {
                return response()->json(['error' => 'Número de documento inválido'], 400);
            }

            $ip = env('APP_ENV') == "local" ? "192.168.1.113" : "38.250.184.10";

            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "http://$ip:8282/apugescom.api/api/ConsultaExterna/Consulta?numDoc=$doc",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CUSTOMREQUEST => 'GET',
            ]);

            $responseRaw = curl_exec($curl);
            if (curl_errno($curl)) {
                $error_msg = curl_error($curl);
                curl_close($curl);
                return response()->json(['error' => "Error al consultar el servicio: $error_msg"], 502);
            }
            curl_close($curl);

            $response = json_decode($responseRaw);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Respuesta JSON inválida del servicio externo'], 500);
            }

            if (!empty($response) && strlen($doc) == 8 && !empty($response->EstadoErrorConsulta) && isset($response->RazonSocialCliente)) {
                $rsCliente = explode(" ", $response->RazonSocialCliente);
                $apellidos = array_splice($rsCliente, -2);

                $response->Nombres = implode(" ", $rsCliente);
                $response->ApePaterno = $apellidos[0] ?? '';
                $response->ApeMaterno = $apellidos[1] ?? '';
                $EstadoErrorConsulta = $response->EstadoErrorConsulta;
            } else {
                $EstadoErrorConsulta = false;
            }

            return response()->json(
                ['message' => $EstadoErrorConsulta ? 'Consulta exitosa' : 'Documento consultado invalido', 'data' => $response],
                $EstadoErrorConsulta ? 200 : 400
            );
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'status' => $e->getCode() ?: 500
            ], 500);
        }
    }
}
