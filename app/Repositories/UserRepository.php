<?php

namespace App\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function all(): Collection
    {
        return User::orderBy('name')->get();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): void
    {
        $user->update($data);
    }

    public function updateProfile(User $user, array $data): void
    {
        $user->fill($data)->save();
    }

    public function isLastAdmin(User $user): bool
    {
        return $user->role === 'Admin' && User::where('role', 'Admin')->count() <= 1;
    }

    public function hasStockHistory(User $user): bool
    {
        return $user->stockTransactions()->exists()
            || $user->stockOpnames()->exists()
            || $user->orders()->exists()
            || $user->refunds()->exists()
            || $user->productFeedback()->exists();
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
