<?php

namespace App\Http\Controllers;

use App\Models\StockTransaction;
use App\Services\ActivityLogger;
use App\Services\StockTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StockTransactionController extends Controller
{
    public function index(Request $request, StockTransactionService $transactions): View
    {
        return view('transactions.index', $transactions->indexData($request->user()->role));
    }

    public function store(Request $request, StockTransactionService $transactions, ActivityLogger $logger): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Manajer Gudang', 'Staff Gudang'], true), 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'type' => ['required', Rule::in(['Masuk', 'Keluar'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $transaction = $transactions->create($data, (int) $request->user()->id);
        $logger->log('stock.transaction.created', "Mencatat {$transaction->type} {$transaction->quantity} unit untuk {$transaction->product->name}; menunggu konfirmasi staff.");

        return back()->with('status', 'Transaksi stok dicatat dan menunggu konfirmasi Staff Gudang.');
    }

    public function confirm(Request $request, StockTransaction $transaction, StockTransactionService $transactions, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()->role === 'Staff Gudang', 403);

        $transaction = $transactions->confirm($transaction->id);

        $logger->log('stock.transaction.confirmed', "Mengonfirmasi {$transaction->type} {$transaction->quantity} unit untuk {$transaction->product->name}.");

        return back()->with('status', 'Transaksi berhasil dikonfirmasi.');
    }

    public function markPrepared(Request $request, StockTransaction $transaction, StockTransactionService $transactions, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()->role === 'Staff Gudang', 403);

        $transaction = $transactions->markPrepared($transaction->id);
        $action = strtolower($transaction->staff_action);
        $logger->log('stock.transaction.staff_action', "Staff {$action} transaksi {$transaction->type} untuk {$transaction->product->name}.");

        return back()->with('status', "Barang berhasil ditandai {$action}. Lanjutkan dengan konfirmasi.");
    }

    public function reject(Request $request, StockTransaction $transaction, StockTransactionService $transactions, ActivityLogger $logger): RedirectResponse
    {
        abort_unless($request->user()->role === 'Admin', 403);

        $transaction = $transactions->reject($transaction->id);
        if ($transaction === null) {
            return back()->withErrors(['transaction' => 'Hanya transaksi yang menunggu konfirmasi yang dapat ditolak.']);
        }

        $logger->log('stock.transaction.rejected', "Menolak transaksi {$transaction->type} {$transaction->quantity} unit untuk {$transaction->product->name}.");

        return back()->with('status', 'Transaksi pending berhasil ditolak.');
    }

    public function storeOpname(Request $request, StockTransactionService $transactions, ActivityLogger $logger): RedirectResponse
    {
        abort_unless(in_array($request->user()->role, ['Admin', 'Manajer Gudang', 'Staff Gudang'], true), 403);

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'counted_quantity' => ['required', 'integer', 'min:0'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $opname = $transactions->recordOpname($data, (int) $request->user()->id);

        $logger->log('stock.opname.created', "Stock opname {$opname->product->name}: sistem {$opname->system_quantity}, fisik {$opname->counted_quantity} unit.");

        return back()->with('status', 'Stock opname tersimpan dan selisih telah disesuaikan ke stok.');
    }
}
