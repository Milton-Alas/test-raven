<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CandidateAuthController extends Controller
{
    /**
     * Muestra la vista de login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Maneja el intento de autenticación.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Determina si el login es un email o DUI/NIT
        $isEmail = filter_var($request->login, FILTER_VALIDATE_EMAIL);

        // RNF-09.01: el DUI/NIT se guarda cifrado, así que no se puede comparar
        // contra la columna. Se resuelve el candidato por su índice seguro
        // (HMAC) y la contraseña se verifica contra el hash almacenado.
        if (! $isEmail) {
            $candidate = Candidate::findByDuiNit($request->login);

            if (! $candidate
                || ! $candidate->is_active
                || ! Hash::check($request->password, $candidate->password)) {
                throw ValidationException::withMessages([
                    'login' => [trans('auth.failed')],
                ]);
            }

            Auth::guard('candidate')->login($candidate, $request->boolean('remember'));
            $request->session()->regenerate();

            return redirect()->intended('/instrucciones');
        }

        $credentials = [
            'email' => $request->login,
            'password' => $request->password,
            'is_active' => true, // Solo permitir login a candidatos activos
        ];

        if (Auth::guard('candidate')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Redirigir al dashboard o a la página de instrucciones del test
            return redirect()->intended('/instrucciones');
        }

        throw ValidationException::withMessages([
            'login' => [trans('auth.failed')],
        ]);
    }

    /**
     * Cierra la sesión del candidato.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('candidate')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    /**
     * Muestra la vista de registro.
     */
    public function createRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Maneja el registro de un nuevo candidato.
     */
    public function storeRegister(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:candidates'],
            'dui_nit' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'age' => ['required', 'integer', 'min:1'],
            'occupation' => ['required', 'string', 'max:255'],
            'education_level' => ['required', 'string', 'max:255'],
        ]);

        // RNF-09.01: el DUI/NIT se guarda cifrado, así que la unicidad no puede
        // validarse contra la columna (dos cifrados del mismo valor difieren).
        // Se comprueba contra el índice seguro, que es determinista y permite
        // detectar el duplicado sin descifrar nada.
        if (Candidate::findByDuiNit($request->input('dui_nit'))) {
            throw ValidationException::withMessages([
                'dui_nit' => ['El campo dui nit ya ha sido registrado.'],
            ]);
        }

        $candidate = Candidate::create([
            'name' => $request->name,
            'email' => $request->email,
            'dui_nit' => $request->dui_nit,
            'password' => Hash::make($request->password),
            'age' => $request->age,
            'occupation' => $request->occupation,
            'education_level' => $request->education_level,
            'is_active' => true, // Activo por defecto al registrarse
        ]);

        event(new Registered($candidate));

        Auth::guard('candidate')->login($candidate);

        return redirect('/instrucciones');
    }
}
