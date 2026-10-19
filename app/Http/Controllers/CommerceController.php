<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Refund;
use App\Services\ActivityLogger;
use App\Services\CommerceService;
use App\Services\InventoryLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommerceController extends Controller
{
    public function shop(InventoryLedger $ledger): View
    {
        return view('commerce.shop', [
            'products' => Product::with('category')->orderBy('name')->paginate(12),
            'stockBalances' => $ledger->balances(),
        ]);
    }

    public function product(Product $product, InventoryLedger $ledger): View
    {
        return view('commerce.product', [
            'product' => $product->load('category'),
            'available' => (int) ($ledger->balances()[$product->id] ?? 0),
        ]);
    }

    public function purchase(Request $request, Product $product, CommerceService $commerce, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['Transfer Bank', 'Tunai di Toko', 'COD'])],
            'payment_reference' => ['required_if:payment_method,Transfer Bank', 'nullable', 'string', 'max:255'],
            'delivery_address' => ['required_if:payment_method,COD', 'nullable', 'string', 'max:2000'],
            'payment_proof' => ['exclude_unless:payment_method,Transfer Bank', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $data['payment_reference'] = filled($data['payment_reference'] ?? null)
            ? trim($data['payment_reference'])
            : ($data['payment_method'] === 'COD'
                ? 'Bayar tunai saat pesanan diterima'
                : 'Bayar tunai saat mengambil pesanan');
        $proofPath = $data['payment_method'] === 'Transfer Bank'
            ? $request->file('payment_proof')?->store('payment-proofs', 'local')
            : null;
        $data['payment_proof'] = $proofPath;

        try {
            $order = $commerce->checkout($request->user(), (int) $product->id, $data);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if ($proofPath !== null) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        $logger->log('order.created', "Pesanan {$order->order_number} dicatat; menunggu verifikasi pembayaran.");

        return redirect()->route('purchases.index')->with('status', "Pesanan {$order->order_number} berhasil dikirim dan menunggu verifikasi pembayaran.");
    }

    public function purchases(Request $request): View
    {
        return view('commerce.purchases', [
            'orders' => Order::with(['items.feedback', 'items.product', 'items.refunds'])
                ->where('customer_id', $request->user()->id)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function paymentProof(Request $request, Order $order): StreamedResponse
    {
        abort_unless(
            $request->user()->role === 'Admin' || (int) $order->customer_id === (int) $request->user()->id,
            403
        );
        abort_if($order->payment_proof === null || ! Storage::disk('local')->exists($order->payment_proof), 404);

        return Storage::disk('local')->download($order->payment_proof);
    }

    public function refunds(Request $request): View
    {
        return view('commerce.refunds', [
            'refunds' => Refund::with(['order', 'product', 'reviewer'])
                ->where('customer_id', $request->user()->id)
                ->latest()
                ->paginate(10),
        ]);
    }

    public function feedback(Request $request): View
    {
        return view('commerce.feedback', [
            'items' => OrderItem::with(['order', 'product', 'feedback'])
                ->whereHas('order', fn ($query) => $query
                    ->where('customer_id', $request->user()->id)
                    ->where('status', 'Dibayar'))
                ->latest()
                ->paginate(12),
        ]);
    }

    public function saveFeedback(Request $request, OrderItem $item, CommerceService $commerce, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $commerce->saveFeedback($request->user(), (int) $item->id, (int) $data['rating'], $data['comment'] ?? null);
        $logger->log('product.feedback.created', "Memberikan feedback untuk item pesanan #{$item->id}.");

        return back()->with('status', 'Feedback produk berhasil disimpan.');
    }

    public function requestRefund(Request $request, OrderItem $item, CommerceService $commerce, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $refund = $commerce->requestRefund($request->user(), (int) $item->id, (int) $data['quantity'], $data['reason']);
        $logger->log('refund.requested', "Mengajukan refund untuk pesanan {$refund->order->order_number}.");

        return back()->with('status', 'Permintaan refund berhasil dikirim dan menunggu persetujuan Admin.');
    }

    public function adminOrders(): View
    {
        return view('commerce.admin-orders', [
            'orders' => Order::with(['customer', 'items'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function reviewPayment(Request $request, Order $order, CommerceService $commerce, ActivityLogger $logger, string $decision): RedirectResponse
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 404);
        $updated = $commerce->reviewPayment((int) $order->id, (int) $request->user()->id, $decision === 'approve');
        $logger->log(
            $decision === 'approve' ? 'order.payment.approved' : 'order.payment.rejected',
            "Pembayaran pesanan {$updated->order_number} ".($decision === 'approve' ? 'diverifikasi.' : 'ditolak dan stok dikembalikan.')
        );

        return back()->with('status', 'Status pembayaran berhasil diperbarui.');
    }

    public function adminRefunds(): View
    {
        return view('commerce.admin-refunds', [
            'refunds' => Refund::with(['customer', 'order', 'product', 'reviewer'])
                ->latest()
                ->paginate(15),
        ]);
    }

    public function reviewRefund(Request $request, Refund $refund, CommerceService $commerce, ActivityLogger $logger, string $decision): RedirectResponse
    {
        abort_unless(in_array($decision, ['approve', 'reject'], true), 404);
        $updated = $commerce->reviewRefund((int) $refund->id, (int) $request->user()->id, $decision === 'approve');
        $logger->log(
            $decision === 'approve' ? 'refund.approved' : 'refund.rejected',
            "Refund #{$updated->id} ".($decision === 'approve' ? 'disetujui dan stok dikembalikan.' : 'ditolak.')
        );

        return back()->with('status', 'Status refund berhasil diperbarui.');
    }
}
