<?php

namespace App\Services;

use App\Repositories\ActivityLogRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\ProductRepository;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private DashboardRepository $dashboard,
        private ActivityLogRepository $activityLogs,
        private ProductRepository $products,
        private InventoryLedger $ledger
    ) {
    }

    public function data(string $role): array
    {
        $today = Carbon::now('Asia/Jakarta')->startOfDay()->locale('id');
        $firstMonth = $today->copy()->startOfMonth()->subMonths(11);
        $lastMonth = $today->copy()->endOfMonth();
        $stockBalances = $this->ledger->balances();
        $products = $this->products->allWithRelations();
        $lowStockProducts = $products->filter(fn ($product) => (int) ($stockBalances[$product->id] ?? 0) <= $product->minimum_stock);
        $monthlyMovements = $this->dashboard->monthlyMovements($firstMonth->toDateString(), $lastMonth->toDateString());
        $chartLabels = [];
        $incomingData = array_fill(0, 12, 0);
        $outgoingData = array_fill(0, 12, 0);
        $monthIndexes = [];

        for ($month = $firstMonth->copy(); $month <= $lastMonth; $month->addMonth()) {
            $monthIndexes[$month->format('Y-m')] = count($chartLabels);
            $chartLabels[] = $month->translatedFormat('M Y');
        }

        foreach ($monthlyMovements as $movement) {
            $index = $monthIndexes[Carbon::parse($movement->date)->format('Y-m')] ?? null;
            if ($index === null) {
                continue;
            }

            if ($movement->type === 'Masuk') {
                $incomingData[$index] += (int) $movement->quantity;
            } else {
                $outgoingData[$index] += (int) $movement->quantity;
            }
        }

        return [
            'productsCount' => $products->count(),
            'incomingToday' => $this->dashboard->movementCount('Masuk', 'Diterima', $today->toDateString()),
            'outgoingToday' => $this->dashboard->movementCount('Keluar', 'Dikeluarkan', $today->toDateString()),
            'lowStockCount' => $lowStockProducts->count(),
            'pendingTasks' => $this->dashboard->pendingTaskCount(),
            'totalStock' => $stockBalances->sum(),
            'stockBalances' => $stockBalances,
            'lowStockProducts' => $lowStockProducts->take(5),
            'recentTransactions' => $this->dashboard->recentTransactions(),
            'recentUsers' => $this->dashboard->recentUsers(),
            'recentActivities' => $role === 'Admin' ? $this->activityLogs->latest(8) : collect(),
            'pendingTransactions' => $this->dashboard->pendingTransactions(),
            'recentOpnames' => $this->dashboard->recentOpnames(),
            'chartLabels' => $chartLabels,
            'chartPeriod' => $firstMonth->translatedFormat('F Y').' – '.$lastMonth->translatedFormat('F Y'),
            'incomingData' => $incomingData,
            'outgoingData' => $outgoingData,
            'role' => $role,
        ];
    }
}
