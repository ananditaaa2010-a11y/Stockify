<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockTransaction;
use App\Models\Supplier;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StockTransactionRepository
{
    public function paginateTransactions(): LengthAwarePaginator
    {
        return StockTransaction::with(['product.supplier', 'user', 'supplier'])
            ->latest('date')
            ->latest('id')
            ->paginate(15);
    }

    public function products(): Collection
    {
        return Product::orderBy('name')->get();
    }

    public function suppliers(): Collection
    {
        return Supplier::orderBy('name')->get();
    }

    public function paginateOpnames(): LengthAwarePaginator
    {
        return StockOpname::with(['product', 'user'])->latest('date')->paginate(10, ['*'], 'opnames_page');
    }

    public function findProduct(int $productId): Product
    {
        return Product::findOrFail($productId);
    }

    public function lockTransaction(int $transactionId): StockTransaction
    {
        return StockTransaction::query()->lockForUpdate()->findOrFail($transactionId);
    }

    public function lockProduct(int $productId): Product
    {
        return Product::query()->lockForUpdate()->findOrFail($productId);
    }

    public function create(array $data): StockTransaction
    {
        return StockTransaction::create($data);
    }

    public function updateStatus(StockTransaction $transaction, string $status): void
    {
        $transaction->update(['status' => $status]);
    }

    public function updateStaffAction(StockTransaction $transaction, string $action): void
    {
        $transaction->update([
            'staff_action' => $action,
            'staff_action_at' => now(),
        ]);
    }

    public function createOpname(array $data): StockOpname
    {
        return StockOpname::create($data);
    }
}
