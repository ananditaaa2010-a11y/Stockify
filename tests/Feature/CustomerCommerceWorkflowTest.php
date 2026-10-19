<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Refund;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerCommerceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_purchase_payment_feedback_and_approved_refund_update_database_and_stock(): void
    {
        [$admin, $customer, $product] = $this->createShopData();

        $this->actingAs($customer)->get('http://localhost/dashboard')->assertRedirect(route('shop.index'));
        $shopPage = $this->actingAs($customer)->get('http://localhost/shop');
        $shopPage->assertOk()
            ->assertSee($product->name)
            ->assertSee('Lihat detail produk')
            ->assertSee(route('shop.product', $product));
        $productPage = $this->actingAs($customer)->get(route('shop.product', $product));
        $productPage->assertOk()
            ->assertSee('12345678901234567890')
            ->assertSee('bukan rekening nyata')
            ->assertSee('screenshot atau foto transaksi berhasil')
            ->assertSee('COD (Bayar di Tempat)')
            ->assertSee('Alamat pengantaran')
            ->assertSee('Jangan unggah PIN, OTP, atau saldo');
        $this->assertSame(4, substr_count($shopPage->getContent(), '<svg class="h-5 w-5 shrink-0"'));
        $this->actingAs($customer)->post('http://localhost/shop/'.$product->id.'/purchase', [
            'quantity' => 2,
            'payment_method' => 'Transfer Bank',
        ])->assertSessionHasErrors('payment_reference');
        $this->assertDatabaseCount('orders', 0);
        $this->actingAs($customer)->post('http://localhost/shop/'.$product->id.'/purchase', [
            'quantity' => 2,
            'payment_method' => 'Transfer Bank',
            'payment_reference' => 'TRF-2026-001',
        ])->assertRedirect(route('purchases.index'));

        $order = Order::firstOrFail();
        $this->assertSame('TRF-2026-001', $order->payment_reference);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'payment_reference' => 'TRF-2026-001']);
        $this->assertSame('Menunggu Verifikasi', $order->status);
        $this->assertSame(2, app(InventoryLedger::class)->balances()[$product->id]);
        Storage::fake('local');
        $proofPath = 'payment-proofs/'.$order->id.'.png';
        Storage::disk('local')->put($proofPath, 'receipt');
        $order->update(['payment_proof' => $proofPath]);
        $proofUrl = 'http://localhost/orders/'.$order->id.'/payment-proof';
        $this->actingAs($customer)->get($proofUrl)->assertOk();
        $otherCustomer = User::factory()->create(['role' => 'Customer']);
        $this->actingAs($otherCustomer)->get($proofUrl)->assertForbidden();
        $this->get('http://localhost/sales')->assertForbidden();
        $this->get('http://localhost/refunds')->assertForbidden();
        $this->actingAs($admin)->get($proofUrl)->assertOk();
        $adminPage = $this->actingAs($admin)->get('http://localhost/sales');
        $adminPage->assertOk()->assertSee($order->order_number);
        $this->assertSame(11, substr_count($adminPage->getContent(), '<svg class="h-5 w-5 shrink-0"'));

        $this->actingAs($admin)->post('http://localhost/sales/'.$order->id.'/approve')->assertRedirect();
        $this->assertSame('Dibayar', $order->fresh()->status);

        $item = $order->items()->firstOrFail();
        $this->actingAs($customer)->post('http://localhost/order-items/'.$item->id.'/feedback', [
            'rating' => 5,
            'comment' => 'Produk sesuai deskripsi.',
        ])->assertRedirect()->assertSessionHas('status');
        $this->assertDatabaseHas('product_feedback', [
            'customer_id' => $customer->id,
            'order_item_id' => $item->id,
            'rating' => 5,
        ]);

        $this->actingAs($customer)->post('http://localhost/order-items/'.$item->id.'/refund', [
            'quantity' => 1,
            'reason' => 'Produk tidak sesuai kebutuhan.',
        ])->assertRedirect();
        $refund = Refund::firstOrFail();
        $this->assertSame('Menunggu', $refund->status);
        $this->assertSame(2, app(InventoryLedger::class)->balances()[$product->id]);
        $this->actingAs($admin)->get('http://localhost/refunds')->assertOk()->assertSee($refund->customer->name);

        $this->actingAs($admin)->post('http://localhost/refunds/'.$refund->id.'/approve')->assertRedirect();
        $this->assertSame('Disetujui', $refund->fresh()->status);
        $this->assertSame(3, app(InventoryLedger::class)->balances()[$product->id]);

        $this->actingAs($customer)->get('http://localhost/purchases')->assertOk()->assertSee($order->order_number);
        $this->get('http://localhost/refund-history')->assertOk()->assertSee('Disetujui');
        $this->get('http://localhost/feedback')->assertOk()->assertSee('Produk sesuai deskripsi.');
    }

    public function test_rejected_payment_restores_reserved_stock_and_customer_cannot_open_staff_pages(): void
    {
        [$admin, $customer, $product] = $this->createShopData();
        $this->actingAs($customer)->get('http://localhost/transactions')->assertForbidden();
        $this->actingAs($customer)->post('http://localhost/shop/'.$product->id.'/purchase', [
            'quantity' => 3,
            'payment_method' => 'Tunai di Toko',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('Bayar tunai saat mengambil pesanan', $order->payment_reference);
        $this->assertSame(1, app(InventoryLedger::class)->balances()[$product->id]);
        $this->actingAs($admin)->post('http://localhost/sales/'.$order->id.'/reject')->assertRedirect();

        $this->assertSame('Pembayaran Ditolak', $order->fresh()->status);
        $this->assertSame(4, app(InventoryLedger::class)->balances()[$product->id]);
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'type' => 'Masuk',
            'quantity' => 3,
            'status' => 'Diterima',
        ]);

        $this->actingAs($customer)->post(route('shop.purchase', $product), [
            'quantity' => 1,
            'payment_method' => 'COD',
        ])->assertSessionHasErrors('delivery_address');
        $this->assertDatabaseCount('orders', 1);

        $this->actingAs($customer)->post(route('shop.purchase', $product), [
            'quantity' => 1,
            'payment_method' => 'COD',
            'delivery_address' => 'Jalan Merdeka No. 12, Bandung',
        ])->assertRedirect(route('purchases.index'));

        $codOrder = Order::where('payment_method', 'COD')->firstOrFail();
        $this->assertSame('Jalan Merdeka No. 12, Bandung', $codOrder->delivery_address);
        $this->assertSame('Bayar tunai saat pesanan diterima', $codOrder->payment_reference);
        $adminPage = $this->actingAs($admin)->get('http://localhost/sales');
        $adminPage->assertOk()
            ->assertSee('Jalan Merdeka No. 12, Bandung')
            ->assertSee('Konfirmasi COD diterima');
    }

    public function test_customer_can_register_but_admin_cannot_assign_customer_role(): void
    {
        [$admin, , $product] = $this->createShopData();
        $this->get('http://localhost/register')
            ->assertOk()
            ->assertSee('Daftar sebagai Customer')
            ->assertSee('Email harus valid dan belum pernah terdaftar.')
            ->assertSee('Kata sandi minimal 8 karakter.')
            ->assertSee('Konfirmasi kata sandi harus sama persis.');

        $this->actingAs($admin)->post('http://localhost/users', [
            'name' => 'Customer dari Admin',
            'email' => 'admin-customer@example.test',
            'role' => 'Customer',
            'password' => 'CustomerPass123',
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'admin-customer@example.test']);
        $this->post('http://localhost/logout')->assertRedirect();

        $this->post('http://localhost/register', [
            'name' => 'Customer Baru',
            'email' => 'customer@example.test',
            'password' => 'CustomerPass123',
            'password_confirmation' => 'CustomerPass123',
            'role' => 'Admin',
        ])->assertRedirect(route('shop.index'));

        $customer = User::where('email', 'customer@example.test')->firstOrFail();
        $this->assertSame('Customer', $customer->role);
        $this->assertAuthenticatedAs($customer);
        $this->assertNotSame('CustomerPass123', $customer->password);

        $this->actingAs($admin)->put('http://localhost/users/'.$customer->id, [
            'name' => 'Customer Baru',
            'email' => 'customer@example.test',
            'role' => 'Staff Gudang',
            'password' => '',
        ])->assertSessionHasErrors('role');
        $this->assertSame('Customer', $customer->fresh()->role);

        $this->post('http://localhost/logout')->assertRedirect();
        $this->post('http://localhost/register', [
            'name' => 'Password Pendek',
            'email' => 'short-password@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');
        $this->post('http://localhost/register', [
            'name' => 'Konfirmasi Salah',
            'email' => 'confirmation@example.test',
            'password' => 'CustomerPass123',
            'password_confirmation' => 'CustomerPass456',
        ])->assertSessionHasErrors('password');
        $this->post('http://localhost/register', [
            'name' => 'Email Duplikat',
            'email' => 'customer@example.test',
            'password' => 'CustomerPass123',
            'password_confirmation' => 'CustomerPass123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($customer)->post('http://localhost/shop/'.$product->id.'/purchase', [
            'quantity' => 5,
            'payment_method' => 'Transfer Bank',
            'payment_reference' => 'TRF-OVERSTOCK',
        ])->assertRedirect()->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(4, app(InventoryLedger::class)->balances()[$product->id]);
    }

    private function createShopData(): array
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $customer = User::factory()->create(['role' => 'Customer']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Pemasok Demo']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Mouse Wireless',
            'sku' => 'MOUSE-WIRELESS-01',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 1,
        ]);

        StockTransaction::create([
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'user_id' => $admin->id,
            'type' => 'Masuk',
            'quantity' => 4,
            'date' => now()->toDateString(),
            'status' => 'Diterima',
            'notes' => 'Stok awal test',
        ]);

        return [$admin, $customer, $product];
    }
}
