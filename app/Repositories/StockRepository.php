<?php

namespace App\Repositories;

use App\Models\StockOpname;
use App\Models\StockTransaction;
use Illuminate\Support\Collection;

class StockRepository
{
    public function confirmedMovementTotals(?string $throughDate = null): Collection
    {
        return StockTransaction::query()
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN type = 'Masuk' THEN quantity ELSE -quantity END) AS quantity")
            ->when($throughDate, fn ($query) => $query->whereDate('date', '<=', $throughDate))
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('type', 'Masuk')->where('status', 'Diterima');
                })->orWhere(function ($query) {
                    $query->where('type', 'Keluar')->where('status', 'Dikeluarkan');
                });
            })
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id');
    }

    public function stockCountDifferences(?string $throughDate = null): Collection
    {
        return StockOpname::query()
            ->select('product_id')
            ->selectRaw('SUM(difference) AS quantity')
            ->when($throughDate, fn ($query) => $query->whereDate('date', '<=', $throughDate))
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id');
    }
}
