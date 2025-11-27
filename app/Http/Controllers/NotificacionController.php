<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;

class NotificacionController extends Controller
{
    public function listar()
    {
        $user = auth()->user();
        $tipo = (int) $user->tipo;   // 0 admin, 1 técnico
        $userId = $user->user_id;    // código tipo 000123

        $notificaciones = DB::table('notificaciones')
            ->join('users', 'users.user_id', '=', 'notificaciones.user_id')
            ->where('notificaciones.estado', 0)
            ->where(function ($q) use ($tipo) {
                $q->where('notificaciones.tipo_destinatario', $tipo)
                    ->orWhere('notificaciones.tipo_destinatario', 2); // ambos
            })
            ->where(function ($q) use ($tipo, $userId) {
                // Si es técnico, puede recibir notificaciones dirigidas a él
                if ($tipo == 1) {
                    $q->whereNull('notificaciones.user_id_destino')
                        ->orWhere('notificaciones.user_id_destino', $userId);
                }
            })
            ->select(
                'notificaciones.id',
                'notificaciones.descripcion',
                'notificaciones.accion_js',
                'users.nombre as nombre_usuario',
                'users.tipo as tipo_usuario',
                'notificaciones.created_at'
            )
            ->orderBy('notificaciones.created_at', 'desc')
            ->get();

        return response()->json([
            'notificaciones' => $notificaciones
        ]);
    }

    public static function store(object $noti)
    {
        DB::table('notificaciones')->insert([
            'user_id' => $noti->user_id,
            'descripcion' => $noti->descripcion,
            'accion_js' => $noti->accion,
            'tipo_destinatario' => $noti->destinatario ?? 0,
            'user_id_destino' => $noti->user_destino ?? null,
            'limite_show' => $noti->limite_show,
            'user_id_origen' => Auth::user()->id,
        ]);
    }
}
