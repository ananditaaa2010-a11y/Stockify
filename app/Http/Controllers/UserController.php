<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(UserService $users): View
    {
        return view('users.index', ['users' => $users->all()]);
    }

    public function store(Request $request, UserService $users, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request);
        $user = $users->create($data);
        $logger->log('user.created', "Membuat akun {$user->name} dengan role {$user->role}.");

        return back()->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user, UserService $users, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request, $user);
        if (! $users->update($user, $data)) {
            return back()->withErrors(['user' => 'Sistem harus memiliki setidaknya satu akun Admin.']);
        }
        $logger->log('user.updated', "Memperbarui akun {$user->name}; role {$user->role}.");

        return back()->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user, UserService $users, ActivityLogger $logger): RedirectResponse
    {
        $error = $users->delete($user, auth()->user());
        if ($error !== null) {
            return back()->withErrors(['user' => $error]);
        }

        $name = $user->name;
        $logger->log('user.deleted', "Menghapus akun {$name}.");

        return back()->with('status', 'Pengguna berhasil dihapus.');
    }

    private function validatedData(Request $request, ?User $user = null): array
    {
        $allowedRoles = $user?->role === 'Customer'
            ? ['Customer']
            : ['Admin', 'Manajer Gudang', 'Staff Gudang'];

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in($allowedRoles)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
        ]);
    }
}
