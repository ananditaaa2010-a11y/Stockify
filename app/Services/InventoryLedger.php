<?php

namespace App\Services;

use App\Repositories\StockRepository;
use Illuminate\Support\Collection;

class InventoryLedger
{
    public function __construct(private StockRepository $stock)
    {
    }

    public function balances(?string $throughDate = null): Collection
    {
        $transactions = $this->stock->confirmedMovementTotals($throughDate);
        $opnames = $this->stock->stockCountDifferences($throughDate);

        return $transactions->keys()->merge($opnames->keys())->unique()->mapWithKeys(function ($productId) use ($transactions, $opnames) {
            return [$productId => (int) ($transactions[$productId] ?? 0) + (int) ($opnames[$productId] ?? 0)];
        });
    }
}
