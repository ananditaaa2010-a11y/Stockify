<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLogger;
use App\Services\UserService;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function storeRegistration(Request $request, UserService $users, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'Customer',
        ]);

        Auth::login($user);
        $request->session()->regenerate();
        $logger->log('auth.customer_registered', 'Mendaftar akun Customer.');

        return redirect()->route('shop.index')->with('status', 'Pendaftaran berhasil. Selamat berbelanja!');
    }

    public function store(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi tidak sesuai.',
            ]);
        }

        $request->session()->regenerate();
        $logger->log('auth.login', 'Masuk ke aplikasi.');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $logger->log('auth.logout', 'Keluar dari aplikasi.');
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
