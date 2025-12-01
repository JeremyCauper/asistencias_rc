<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use function PHPUnit\Framework\matches;

class NotificacionController extends Controller
{
    public function listar()
    {
        $user = Auth::user();
        $userId = $user->id;

        // Personal
        $personalQuery = DB::table('personal')->select('user_id', 'dni', 'nombre', 'apellido');
        $whereAsistencia = ['leido' => 0];

        if ($user->rol_system == 1) {
            $personal = $personalQuery->where('user_id', $userId)->get()->keyBy('user_id');
            $whereAsistencia['user_id'] = $userId;
            $whereAsistencia['is_admin'] = 0;
        } else {
            if (!in_array($user->rol_system, [2, 7])) {
                $personalQuery = $personalQuery->where('area_id', $user->area_id);
            }
            $personal = $personalQuery->get()->keyBy('user_id');
            $whereAsistencia['is_admin'] = 1;
        }

        $notificaciones = DB::table('notificaciones')
            ->where($whereAsistencia)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        // Armado de respuesta
        $result = [];

        foreach ($notificaciones as $noti) {
            if (!isset($personal[$noti->user_id])) {
                continue; // por seguridad si no existiera user en tabla personal
            }

            $limiteShow = match($noti->limite_show) {
                'derivado' => $this->limiteDerivado,
                default => null
            };
            
            if ($limiteShow && $this->horaActual > $limiteShow) {
                continue;
            }

            $per = $personal[$noti->user_id];

            $result[] = [
                'id' => $noti->id,
                'user_id' => $noti->user_id,
                'tipo_notificacion' => $noti->tipo_notificacion,
                'is_admin' => $noti->is_admin,
                'descripcion_id' => $noti->descripcion_id,
                'sigla' => $per->nombre[0] . $per->apellido[0],
                'nombre' => $this->formatearNombre($per->nombre, $per->apellido),
                'creado' => $noti->created_at,
                'leido' => $noti->leido,
                'ruta_id' => $noti->ruta_id,
                'accion_id' => $noti->accion_id,
                'payload_accion' => $noti->payload_accion
            ];
        }

        return response()->json($result);
    }


    public static function crear(array $data)
    {
        // sanitiza y valida según convenga
        DB::table('notificaciones')->insert([
            'user_id' => $data['user_id'],
            'is_admin' => $data['is_admin'] ?? 0,
            'tipo_notificacion' => $data['tipo_notificacion'],
            'descripcion_id' => $data['descripcion_id'],
            'ruta_id' => $data['ruta_id'],
            'accion_id' => $data['accion_id'] ?? null,
            'payload_accion' => isset($data['payload_accion']) ? json_encode($data['payload_accion']) : null,
            'limite_show' => $data['limite_show'] ?? null,
            'created_at' => now()->format('Y-m-d H:i:s')
        ]);
    }

    public static function marcarLeido($id)
    {
        DB::table('notificaciones')->where('id', $id)->update(['leido' => 1]);
    }

    public function borrar($id)
    {
        DB::table('notificaciones')->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function contarNoLeidas()
    {
        $userId = auth()->id();
        $count = DB::table('notificaciones')->where('user_id', $userId)->where('leido', 0)->count();
        return response()->json(['unread' => $count]);
    }
}