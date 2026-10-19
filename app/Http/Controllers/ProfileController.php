<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', ['user' => auth()->user()]);
    }

    public function update(Request $request, UserService $users, ActivityLogger $logger): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'current_password' => ['required', 'current_password'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $users->updateProfile($user, $data);

        $logger->log('profile.updated', 'Memperbarui profil dan/atau kata sandi.');

        return back()->with('status', 'Profil berhasil diperbarui.');
    }
}
