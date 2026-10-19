<?php

namespace App\Services;

use App\Repositories\ReportRepository;
use Carbon\Carbon;

class ReportService
{
    public function __construct(
        private ReportRepository $reports,
        private InventoryLedger $ledger
    ) {
    }

    public function data(array $filters, string $role): array
    {
        $from = $filters['from'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $filters['to'] ?? now('Asia/Jakarta')->toDateString();
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : null;
        $activityFrom = Carbon::parse($from, 'Asia/Jakarta')->startOfDay()->utc();
        $activityTo = Carbon::parse($to, 'Asia/Jakarta')->endOfDay()->utc();

        return [
            'categories' => $this->reports->categories(),
            'products' => $this->reports->products($categoryId),
            'balances' => $this->ledger->balances($to),
            'transactions' => $this->reports->transactions($from, $to, $categoryId),
            'opnames' => $this->reports->opnames($from, $to, $categoryId),
            'activities' => in_array($role, ['Admin', 'Manajer Gudang'], true)
                ? $this->reports->activities($activityFrom, $activityTo)
                : collect(),
            'filters' => ['from' => $from, 'to' => $to, 'category_id' => $categoryId ?? ''],
        ];
    }
}
