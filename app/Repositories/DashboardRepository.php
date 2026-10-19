<?php

namespace App\Repositories;

use App\Models\StockOpname;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository
{
    public function monthlyMovements(string $from, string $through): Collection
    {
        return StockTransaction::query()
            ->select('date', 'type')
            ->selectRaw('SUM(quantity) AS quantity')
            ->whereBetween('date', [$from, $through])
            ->where(function ($query) {
                $query->where(fn ($query) => $query->where('type', 'Masuk')->where('status', 'Diterima'))
                    ->orWhere(fn ($query) => $query->where('type', 'Keluar')->where('status', 'Dikeluarkan'));
            })
            ->groupBy('date', 'type')
            ->get();
    }

    public function recentTransactions(): Collection
    {
        return StockTransaction::with(['product', 'user'])->latest('date')->latest('id')->limit(6)->get();
    }

    public function pendingTaskCount(): int
    {
        return StockTransaction::where('status', 'Pending')->whereIn('type', ['Masuk', 'Keluar'])->count();
    }

    public function movementCount(string $type, string $status, string $date): int
    {
        return StockTransaction::where('type', $type)->where('status', $status)->whereDate('date', $date)->count();
    }

    public function recentUsers(): Collection
    {
        return User::latest()->limit(5)->get();
    }

    public function pendingTransactions(): Collection
    {
        return StockTransaction::with('product')
            ->where('status', 'Pending')
            ->latest('date')
            ->latest('id')
            ->limit(8)
            ->get();
    }

    public function recentOpnames(): Collection
    {
        return StockOpname::with(['product', 'user'])->latest('date')->limit(5)->get();
    }
}
