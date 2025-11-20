<?php

namespace App\Http\Controllers\Login;

use App\Http\Controllers\Controller;
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
            'usuario' => 'required|string',
            'clave' => 'required|string',
        ]);

        $credentials = [
            'usuario' => $request->input('usuario'),
            'password' => $request->input('clave'),
        ];

        if (!Auth::attempt($credentials))
            return response()->json(['success' => false, 'message' => 'La contraseña es incorrecta'], 200);

        $modulos = $this->obtenerModulos(Auth::user()->rol_system, Auth::user()->sistema);
        $nombres = $this->formatearNombre(Auth::user()->nombre, Auth::user()->apellido);
        $acceso = JsonDB::table('tipo_personal')->where('id', Auth::user()->rol_system)->first();

        session([
            'customModulos'       => $modulos->menus,
            'rutaRedirect'        => $modulos->ruta,
            'user_id'             => Auth::user()->user_id,
            'tipo_usuario'        => Auth::user()->rol_system,
            'tipo_sistema'        => Auth::user()->sistema,
            'cambio'              => Auth::user()->password_view == '123456',
            'personal'            => Auth::user(),
            'config' => (object) [
                'acceso'        => $acceso?->descripcion ?? null,
                'accesoCl'      => $acceso?->color ?? null,
                'nombre_perfil' => $nombres ?? null,
                'sigla'         => Auth::user()->nombre[0] . Auth::user()->apellido[0],
                'siglaBg'       => $this->colores(Auth::user()->nombre[0]),
            ],
        ]);

        $request->session()->regenerate();

        // Autenticación exitosa
        return response()->json(['success' => true, 'message' => '', 'data' => $modulos->ruta], 200);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        session()->forget(['customModulos', 'rutaRedirect', 'personal', 'config']);
        return redirect('/');
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
