<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(private UserRepository $users)
    {
    }

    public function all(): Collection
    {
        return $this->users->all();
    }

    public function create(array $data): User
    {
        $data['password'] = Hash::make($data['password']);

        return $this->users->create($data);
    }

    public function update(User $user, array $data): bool
    {
        if ($this->users->isLastAdmin($user) && ($data['role'] ?? $user->role) !== 'Admin') {
            return false;
        }

        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $this->users->update($user, $data);

        return true;
    }

    public function updateProfile(User $user, array $data): void
    {
        if (filled($data['password'] ?? null)) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['current_password']);
        $this->users->updateProfile($user, $data);
    }

    public function delete(User $user, User $actor): ?string
    {
        if ($user->is($actor)) {
            return 'Akun yang sedang digunakan tidak dapat dihapus.';
        }
        if ($this->users->isLastAdmin($user)) {
            return 'Sistem harus memiliki setidaknya satu akun Admin.';
        }
        if ($this->users->hasStockHistory($user)) {
            return 'Pengguna yang memiliki riwayat stok tidak dapat dihapus.';
        }

        $this->users->delete($user);

        return null;
    }
}
