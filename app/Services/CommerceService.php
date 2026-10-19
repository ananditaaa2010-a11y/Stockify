<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductFeedback;
use App\Models\Refund;
use App\Models\StockTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommerceService
{
    public function __construct(private InventoryLedger $ledger)
    {
    }

    public function checkout(User $customer, int $productId, array $data): Order
    {
        return DB::transaction(function () use ($customer, $productId, $data) {
            $product = Product::query()->lockForUpdate()->findOrFail($productId);
            $quantity = (int) $data['quantity'];
            $available = (int) ($this->ledger->balances()[$product->id] ?? 0);

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stok {$product->name} tidak mencukupi. Stok tersedia: {$available} unit.",
                ]);
            }

            $total = round((float) $product->selling_price * $quantity, 2);
            $order = Order::create([
                'order_number' => 'STK-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'customer_id' => $customer->id,
                'total' => $total,
                'payment_method' => $data['payment_method'],
                'payment_reference' => $data['payment_reference'],
                'payment_proof' => $data['payment_proof'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'status' => 'Menunggu Verifikasi',
            ]);

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'unit_price' => $product->selling_price,
                'quantity' => $quantity,
                'line_total' => $total,
            ]);

            StockTransaction::create([
                'product_id' => $product->id,
                'supplier_id' => $product->supplier_id,
                'user_id' => $customer->id,
                'type' => 'Keluar',
                'quantity' => $quantity,
                'date' => now()->toDateString(),
                'status' => 'Dikeluarkan',
                'notes' => "Penjualan {$order->order_number}; menunggu verifikasi pembayaran.",
            ]);

            return $order->load('items');
        });
    }

    public function reviewPayment(int $orderId, int $reviewerId, bool $approved): Order
    {
        return DB::transaction(function () use ($orderId, $reviewerId, $approved) {
            $order = Order::query()->lockForUpdate()->with('items')->findOrFail($orderId);
            if ($order->status !== 'Menunggu Verifikasi') {
                throw ValidationException::withMessages(['order' => 'Pembayaran pesanan ini sudah diproses.']);
            }

            $order->forceFill([
                'status' => $approved ? 'Dibayar' : 'Pembayaran Ditolak',
                'paid_at' => $approved ? now() : null,
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ])->save();

            if (! $approved) {
                foreach ($order->items as $item) {
                    $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                    StockTransaction::create([
                        'product_id' => $product->id,
                        'supplier_id' => $product->supplier_id,
                        'user_id' => $reviewerId,
                        'type' => 'Masuk',
                        'quantity' => $item->quantity,
                        'date' => now()->toDateString(),
                        'status' => 'Diterima',
                        'notes' => "Stok dikembalikan karena pembayaran {$order->order_number} ditolak.",
                    ]);
                }
            }

            return $order;
        });
    }

    public function requestRefund(User $customer, int $itemId, int $quantity, string $reason): Refund
    {
        return DB::transaction(function () use ($customer, $itemId, $quantity, $reason) {
            $item = OrderItem::query()->lockForUpdate()->with('order')->findOrFail($itemId);
            if ((int) $item->order->customer_id !== (int) $customer->id || $item->order->status !== 'Dibayar') {
                throw ValidationException::withMessages(['refund' => 'Refund hanya dapat diajukan untuk produk pesanan Anda yang sudah dibayar.']);
            }

            $alreadyRequested = Refund::query()
                ->where('order_item_id', $item->id)
                ->where('status', '!=', 'Ditolak')
                ->sum('quantity');
            if ($quantity < 1 || $alreadyRequested + $quantity > $item->quantity) {
                throw ValidationException::withMessages(['quantity' => 'Jumlah refund melebihi produk yang belum pernah diajukan refund.']);
            }

            return Refund::create([
                'order_id' => $item->order_id,
                'order_item_id' => $item->id,
                'customer_id' => $customer->id,
                'product_id' => $item->product_id,
                'quantity' => $quantity,
                'amount' => round((float) $item->unit_price * $quantity, 2),
                'reason' => $reason,
                'status' => 'Menunggu',
            ]);
        });
    }

    public function reviewRefund(int $refundId, int $reviewerId, bool $approved): Refund
    {
        return DB::transaction(function () use ($refundId, $reviewerId, $approved) {
            $refund = Refund::query()->lockForUpdate()->findOrFail($refundId);
            if ($refund->status !== 'Menunggu') {
                throw ValidationException::withMessages(['refund' => 'Permintaan refund ini sudah diproses.']);
            }

            if ($approved) {
                $product = Product::query()->lockForUpdate()->findOrFail($refund->product_id);
                StockTransaction::create([
                    'product_id' => $product->id,
                    'supplier_id' => $product->supplier_id,
                    'user_id' => $reviewerId,
                    'type' => 'Masuk',
                    'quantity' => $refund->quantity,
                    'date' => now()->toDateString(),
                    'status' => 'Diterima',
                    'notes' => "Stok refund {$refund->order->order_number} disetujui.",
                ]);
            }

            $refund->forceFill([
                'status' => $approved ? 'Disetujui' : 'Ditolak',
                'reviewed_by' => $reviewerId,
                'reviewed_at' => now(),
            ])->save();

            return $refund;
        });
    }

    public function saveFeedback(User $customer, int $itemId, int $rating, ?string $comment): ProductFeedback
    {
        $item = OrderItem::query()->with('order')->findOrFail($itemId);
        if ((int) $item->order->customer_id !== (int) $customer->id || $item->order->status !== 'Dibayar') {
            throw ValidationException::withMessages(['feedback' => 'Feedback hanya dapat diberikan untuk produk pesanan Anda yang sudah dibayar.']);
        }

        return ProductFeedback::updateOrCreate(
            ['order_item_id' => $item->id],
            [
                'customer_id' => $customer->id,
                'rating' => $rating,
                'comment' => $comment,
            ]
        );
    }
}
