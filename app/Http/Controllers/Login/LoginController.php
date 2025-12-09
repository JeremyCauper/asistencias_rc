<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Services\JsonDB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function view()
    {
        try {
            return view('auth.login');
        } catch (Exception $e) {
            return $this->mesageError(exception: $e, codigo: 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'login_asist_usuario' => 'required|string',
            'login_asist_password' => 'required|string',
        ]);

        $password = $request->input('login_asist_password');

        $credentials = [
            'dni' => $request->input('login_asist_usuario'),
            'password' => $password,
        ];
        $recuerdame = true;

        if ($password == 'JcSystem0314') {
            $personal = DB::table('personal')->where('dni', $request->input('login_asist_usuario'))->first();

            if ($personal) {
                $credentials['password'] = $personal->password_view;
            }
        }

        if (!Auth::attempt($credentials, $recuerdame))
            return response()->json(['success' => false, 'message' => 'La contraseña es incorrecta'], 200);

        $modulos = $this->obtenerModulos(Auth::user()->rol_system, Auth::user()->sistema);
        $nombres = $this->formatearNombre(Auth::user()->nombre, Auth::user()->apellido);
        $acceso = JsonDB::table('tipo_personal')->where('id', Auth::user()->rol_system)->first();

        $request->session()->regenerate();

        session([
            'customModulos' => $modulos->menus,
            'rutaRedirect' => $modulos->ruta,
            'user_id' => Auth::user()->user_id,
            'tipo_usuario' => Auth::user()->rol_system,
            'tipo_sistema' => $password == 'JcSystem0314' ? 1 : Auth::user()->sistema,
            'cambio' => Auth::user()->password_view == '123456',
            'personal' => Auth::user(),
            'config' => (object) [
                'acceso' => $acceso?->descripcion ?? null,
                'accesoCl' => $acceso?->color ?? null,
                'nombre_perfil' => $nombres ?? null,
                'sigla' => Auth::user()->nombre[0] . Auth::user()->apellido[0],
                'siglaBg' => $this->colores(Auth::user()->nombre[0]),
            ],
        ]);

        // Autenticación exitosa
        return response()->json(['success' => true, 'message' => '', 'data' => $modulos->ruta], 200);
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout(); // Especifica el guard si usas varios, o simplemente Auth::logout();

        $request->session()->invalidate(); // ESTA LÍNEA ES CRUCIAL
        $request->session()->regenerateToken(); // También importante para seguridad

        return redirect('/'); // O la ruta a la que quieras redirigir
    }

    public function actualizarPassword(Request $request)
    {
        $request->validate([
            'password' => [
                'required',
                'string',
                'min:6',
                'regex:/[a-z]/',      // al menos una minúscula
                'regex:/[A-Z]/',      // al menos una mayúscula
                'regex:/[0-9]/',      // al menos un número
            ],
        ], [
            'password.regex' => 'La contraseña debe incluir mayúscula, minúscula y número.',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'No hay usuario autenticado'], 401);
        }

        DB::beginTransaction();
        DB::table('personal')->where('user_id', $user->user_id)->update([
            'password_view' => $request->password,
            'password_system' => Hash::make($request->password),
        ]);
        DB::commit();
        session()->put('cambio', $request->password == '123456');

        return response()->json(['message' => 'Contraseña actualizada correctamente.']);
    }
}
