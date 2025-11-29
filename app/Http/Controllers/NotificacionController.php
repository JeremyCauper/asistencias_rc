<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificacionController extends Controller
{
    public function listar()
    {
        $descripcion = [
            1 => ':personal debes registrar tu llegada y subir evidencia.',
            2 => ':personal registró una justificación de falta y requiere revisión.',
            3 => ':personal registró una justificación de tardanza y requiere revisión.',
            4 => ':personal registró una justificación de derivación y requiere revisión.',
        ];

        $userId = auth()->id();
        $rows = DB::table('notificaciones')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return response()->json($rows);
    }

    public static function crear(array $data)
    {
        // ['user_id','tipo_notificacion','ruta_id','accion_id','payload_ruta','payload_accion','descripcion']

        // sanitiza y valida según convenga
        DB::table('notificaciones')->insert([
            'user_id' => $data['user_id'],
            'tipo_notificacion' => $data['tipo_notificacion'],
            'descripcion_id' => $data['descripcion_id'],
            'ruta_id' => $data['ruta_id'],
            'accion_id' => $data['accion_id'] ?? null,
            'payload_accion' => isset($data['payload_accion']) ? json_encode($data['payload_accion']) : null,
            'created_at' => now()->format('Y-m-d H:i:s')
        ]);
    }

    public function marcarLeido($id)
    {
        DB::table('notificaciones')->where('id', $id)->update(['leido' => 1]);
        return response()->json(['ok'=>true]);
    }

    public function borrar($id)
    {
        DB::table('notificaciones')->where('id', $id)->delete();
        return response()->json(['ok'=>true]);
    }

    public function contarNoLeidas()
    {
        $userId = auth()->id();
        $count = DB::table('notificaciones')->where('user_id', $userId)->where('leido',0)->count();
        return response()->json(['unread'=>$count]);
    }
}