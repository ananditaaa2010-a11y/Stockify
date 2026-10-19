<?php

namespace App\Services;

use App\Models\StockOpname;
use App\Models\StockTransaction;
use App\Repositories\StockTransactionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockTransactionService
{
    public function __construct(
        private StockTransactionRepository $transactions,
        private InventoryLedger $ledger
    ) {
    }

    public function indexData(string $role): array
    {
        return [
            'transactions' => $this->transactions->paginateTransactions(),
            'canCreate' => in_array($role, ['Admin', 'Manajer Gudang', 'Staff Gudang'], true),
            'canConfirm' => $role === 'Staff Gudang',
            'canOpname' => in_array($role, ['Admin', 'Manajer Gudang', 'Staff Gudang'], true),
            'products' => $this->transactions->products(),
            'suppliers' => $this->transactions->suppliers(),
            'stockBalances' => $this->ledger->balances(),
            'opnames' => $this->transactions->paginateOpnames(),
        ];
    }

    public function create(array $data, int $userId): StockTransaction
    {
        if ($data['type'] === 'Masuk' && empty($data['supplier_id'])) {
            $data['supplier_id'] = $this->transactions->findProduct((int) $data['product_id'])->supplier_id;
        }

        $data['user_id'] = $userId;
        $data['status'] = 'Pending';

        return $this->transactions->create($data);
    }

    public function confirm(int $transactionId): StockTransaction
    {
        return DB::transaction(function () use ($transactionId) {
            $transaction = $this->transactions->lockTransaction($transactionId);
            if ($transaction->status !== 'Pending') {
                throw ValidationException::withMessages(['transaction' => 'Transaksi ini sudah diproses.']);
            }

            $requiredAction = $transaction->type === 'Masuk' ? 'Diperiksa' : 'Disiapkan';
            if ($transaction->staff_action !== $requiredAction) {
                throw ValidationException::withMessages([
                    'transaction' => 'Tandai barang sebagai '.mb_strtolower($requiredAction).' sebelum mengonfirmasi.',
                ]);
            }

            $product = $this->transactions->lockProduct($transaction->product_id);
            if ($transaction->type === 'Keluar' && ($this->ledger->balances()[$product->id] ?? 0) < $transaction->quantity) {
                throw ValidationException::withMessages(['transaction' => 'Stok tidak mencukupi untuk mengonfirmasi barang keluar.']);
            }

            $this->transactions->updateStatus(
                $transaction,
                $transaction->type === 'Masuk' ? 'Diterima' : 'Dikeluarkan'
            );

            return $transaction->load('product');
        });
    }

    public function markPrepared(int $transactionId): StockTransaction
    {
        return DB::transaction(function () use ($transactionId) {
            $transaction = $this->transactions->lockTransaction($transactionId);
            if ($transaction->status !== 'Pending') {
                throw ValidationException::withMessages(['transaction' => 'Hanya transaksi yang menunggu yang dapat diperiksa atau disiapkan.']);
            }
            if ($transaction->staff_action !== null) {
                throw ValidationException::withMessages(['transaction' => 'Tahap pemeriksaan atau persiapan sudah dicatat.']);
            }

            $action = $transaction->type === 'Masuk' ? 'Diperiksa' : 'Disiapkan';
            $this->transactions->updateStaffAction($transaction, $action);

            return $transaction->load('product');
        });
    }

    public function reject(int $transactionId): ?StockTransaction
    {
        return DB::transaction(function () use ($transactionId) {
            $transaction = $this->transactions->lockTransaction($transactionId);
            if ($transaction->status !== 'Pending') {
                return null;
            }

            $this->transactions->updateStatus($transaction, 'Ditolak');

            return $transaction->load('product');
        });
    }

    public function recordOpname(array $data, int $userId): StockOpname
    {
        return DB::transaction(function () use ($data, $userId) {
            $product = $this->transactions->lockProduct((int) $data['product_id']);
            $systemQuantity = max(0, (int) ($this->ledger->balances($data['date'])[$product->id] ?? 0));

            return $this->transactions->createOpname(array_merge($data, [
                'user_id' => $userId,
                'system_quantity' => $systemQuantity,
                'difference' => (int) $data['counted_quantity'] - $systemQuantity,
            ]));
        });
    }
}
