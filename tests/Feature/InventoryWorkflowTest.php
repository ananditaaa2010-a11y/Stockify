<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryLedger;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_access_matches_each_workspace(): void
    {
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $staff = User::factory()->create(['role' => 'Staff Gudang']);

        $this->actingAs($manager)->get(route('products.index'))->assertOk();
        $this->actingAs($manager)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($manager)->get(route('products.create'))->assertOk();
        $this->actingAs($manager)->get(route('attribute-definitions.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('users.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('transactions.index'))->assertOk();
        $this->actingAs($staff)->get(route('products.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('products.create'))->assertOk();
        $this->actingAs($staff)->get(route('transactions.index'))
            ->assertSee('Catat barang masuk / keluar')
            ->assertSee('Stock opname')
            ->assertSee('Produk belum terdaftar?');
    }

    public function test_staff_can_confirm_stock_but_cannot_confirm_more_than_available(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $staff = User::factory()->create(['role' => 'Staff Gudang']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Mouse',
            'sku' => 'MOUSE-01',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 1,
        ]);

        $this->actingAs($admin)->post(route('transactions.store'), [
            'product_id' => $product->id,
            'type' => 'Masuk',
            'quantity' => 5,
            'date' => now()->toDateString(),
        ])->assertRedirect();
        $incoming = StockTransaction::latest('id')->firstOrFail();

        $this->actingAs($manager)->post(route('transactions.prepare', $incoming))->assertForbidden();
        $this->actingAs($staff)->post(route('transactions.confirm', $incoming))
            ->assertRedirect()
            ->assertSessionHasErrors('transaction');
        $this->assertSame(0, app(InventoryLedger::class)->balances()[$product->id] ?? 0);
        $this->actingAs($staff)->post(route('transactions.prepare', $incoming))->assertRedirect();
        $this->assertSame('Diperiksa', $incoming->fresh()->staff_action);
        $this->actingAs($staff)->post(route('transactions.prepare', $incoming))
            ->assertRedirect()
            ->assertSessionHasErrors('transaction');
        $this->actingAs($staff)->post(route('transactions.confirm', $incoming))->assertRedirect();
        $this->assertSame(5, app(InventoryLedger::class)->balances()[$product->id]);

        $this->actingAs($admin)->post(route('opnames.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 3,
            'date' => now()->toDateString(),
            'notes' => 'Hitung fisik',
        ])->assertRedirect();
        $this->assertSame(5, StockOpname::latest('id')->firstOrFail()->system_quantity);
        $this->assertSame(3, app(InventoryLedger::class)->balances()[$product->id]);

        $this->actingAs($admin)->post(route('transactions.store'), [
            'product_id' => $product->id,
            'type' => 'Keluar',
            'quantity' => 4,
            'date' => now()->toDateString(),
        ])->assertRedirect();
        $outgoing = StockTransaction::latest('id')->firstOrFail();

        $this->actingAs($staff)->post(route('transactions.prepare', $outgoing))->assertRedirect();
        $this->actingAs($staff)
            ->from(route('transactions.index'))
            ->post(route('transactions.confirm', $outgoing))
            ->assertRedirect(route('transactions.index'))
            ->assertSessionHasErrors('transaction');

        $this->assertSame('Pending', $outgoing->fresh()->status);
        $this->assertSame(3, app(InventoryLedger::class)->balances()[$product->id]);
    }

    public function test_staff_can_record_incoming_transactions_and_stock_opname(): void
    {
        $staff = User::factory()->create(['role' => 'Staff Gudang']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Keyboard',
            'sku' => 'KEYBOARD-01',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 1,
        ]);

        $this->actingAs($staff)->post(route('transactions.store'), [
            'product_id' => $product->id,
            'type' => 'Masuk',
            'quantity' => 8,
            'date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $staff->id,
            'type' => 'Masuk',
            'quantity' => 8,
            'status' => 'Pending',
        ]);
        $incoming = StockTransaction::latest('id')->firstOrFail();
        $this->actingAs($staff)->post(route('transactions.prepare', $incoming))->assertRedirect();
        $this->actingAs($staff)->post(route('transactions.confirm', $incoming))->assertRedirect();

        $this->actingAs($staff)->post(route('opnames.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 6,
            'date' => now()->toDateString(),
            'notes' => 'Dihitung oleh Staff Gudang',
        ])->assertRedirect()->assertSessionHas('status');

        $opname = StockOpname::latest('id')->firstOrFail();
        $this->assertSame($staff->id, $opname->user_id);
        $this->assertSame(8, $opname->system_quantity);
        $this->assertSame(-2, $opname->difference);
        $this->assertSame(6, app(InventoryLedger::class)->balances()[$product->id]);
    }

    public function test_admin_manages_reusable_product_attribute_definitions(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);

        $this->actingAs($admin)->post(route('attribute-definitions.store'), ['name' => 'Warna'])
            ->assertRedirect()
            ->assertSessionHas('status');
        $definition = \App\Models\ProductAttributeDefinition::where('name', 'Warna')->firstOrFail();

        $this->actingAs($manager)->get(route('attribute-definitions.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('products.create'))->assertOk()->assertSee('Warna');

        $category = Category::create(['name' => 'Aksesori']);
        $supplier = Supplier::create(['name' => 'Pemasok']);
        $this->actingAs($manager)->post(route('products.store'), [
            'name' => 'Tas',
            'sku' => 'TAS-001',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 10,
            'selling_price' => 15,
            'minimum_stock' => 1,
            'initial_stock' => 0,
            'attributes' => [['definition_id' => $definition->id, 'value' => 'Biru']],
        ])->assertRedirect();

        $attribute = \App\Models\ProductAttribute::firstOrFail();
        $this->assertSame($definition->id, $attribute->definition_id);

        $this->actingAs($admin)->put(route('attribute-definitions.update', $definition), ['name' => 'Warna produk'])
            ->assertRedirect();
        $this->assertSame('Warna produk', $attribute->fresh()->name);
        $this->actingAs($admin)->delete(route('attribute-definitions.destroy', $definition))
            ->assertRedirect()
            ->assertSessionHasErrors('definition');
    }

    public function test_staff_can_record_stock_opname_and_adjust_the_stock_balance(): void
    {
        $staff = User::factory()->create(['role' => 'Staff Gudang']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Keyboard',
            'sku' => 'KEYBOARD-01',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 1,
        ]);
        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $staff->id,
            'type' => 'Masuk',
            'quantity' => 8,
            'date' => now()->toDateString(),
            'status' => 'Diterima',
        ]);

        $this->actingAs($staff)->post(route('opnames.store'), [
            'product_id' => $product->id,
            'counted_quantity' => 6,
            'date' => now()->toDateString(),
            'notes' => 'Dihitung oleh Staff Gudang',
        ])->assertRedirect()->assertSessionHas('status');

        $opname = StockOpname::latest('id')->firstOrFail();
        $this->assertSame($staff->id, $opname->user_id);
        $this->assertSame(8, $opname->system_quantity);
        $this->assertSame(-2, $opname->difference);
        $this->assertSame(6, app(InventoryLedger::class)->balances()[$product->id]);
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_login_records_activity_and_opens_the_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'Admin',
            'password' => bcrypt('correct-horse'),
        ]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-horse',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'auth.login',
        ]);
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        foreach ([
            'dashboard',
            'categories.index',
            'suppliers.index',
            'transactions.index',
            'reports.index',
            'users.index',
            'settings.index',
            'profile.edit',
        ] as $routeName) {
            $this->actingAs($admin)->get(route($routeName))->assertOk();
        }

        $this->actingAs($admin)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('name="initial_stock"', false)
            ->assertSee('name="image"', false);
    }

    public function test_dashboard_renders_when_products_exist(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Mouse',
            'sku' => 'MOUSE-01',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 1,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_dashboards_show_the_summary_for_each_role(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $staff = User::factory()->create(['role' => 'Staff Gudang']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Jumlah produk')
            ->assertSee('Barang masuk hari ini')
            ->assertSee('Barang keluar hari ini')
            ->assertSee('Grafik stok barang')
            ->assertSee('Aktivitas pengguna terbaru');

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Stok menipis')
            ->assertSee('Barang masuk hari ini')
            ->assertSee('Barang keluar hari ini')
            ->assertDontSee('Grafik stok barang')
            ->assertDontSee('Aktivitas pengguna terbaru');

        $this->actingAs($staff)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Tugas gudang yang perlu diselesaikan')
            ->assertDontSee('Jumlah produk')
            ->assertDontSee('Grafik stok barang')
            ->assertDontSee('Aktivitas transaksi terbaru');
    }

    public function test_admin_can_import_products_and_opening_stock_from_csv(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        $csv = implode("\n", [
            'name,sku,category,supplier,description,purchase_price,selling_price,minimum_stock,initial_stock,attributes',
            '"Keyboard, mekanik",CSV-001,elektronik,supplier demo,"Keyboard, layout 75%",100,150,2,8,"Warna:Hitam;Ukuran:75%"',
            'Mouse,CSV-002,Kategori Tidak Ada,Supplier Demo,,20,30,1,0,',
        ]);

        $response = $this->actingAs($admin)->post(route('products.import'), [
            'file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ]);

        $response->assertRedirect()
            ->assertSessionHas('import_result.imported', 1)
            ->assertSessionHas('import_result.failed', 1)
            ->assertSessionHas('import_result.errors.0.line', 3);

        $product = Product::where('sku', 'CSV-001')->firstOrFail();
        $this->assertSame('Keyboard, mekanik', $product->name);
        $this->assertSame(2, $product->attributes()->count());
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'type' => 'Masuk',
            'quantity' => 8,
            'status' => 'Diterima',
        ]);
        $this->assertSame(8, app(InventoryLedger::class)->balances()[$product->id]);
    }

    public function test_admin_can_upload_a_product_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'Admin']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);

        $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Produk Bergambar',
            'sku' => 'IMAGE-001',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 2,
            'initial_stock' => 0,
            'image' => UploadedFile::fake()->createWithContent(
                'produk.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/nWQAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'IMAGE-001')->firstOrFail();
        $this->assertNotNull($product->image);
        Storage::disk('public')->assertExists($product->image);
    }

    public function test_product_image_urls_are_relative_to_the_current_application_host(): void
    {
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Laptop Demo',
            'sku' => 'LAPTOP-DEMO',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 1,
            'image' => 'products/demo-laptop.png',
        ]);

        $this->actingAs($manager)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('src="/storage/products/demo-laptop.png"', false)
            ->assertDontSee('src="http://localhost/storage/', false);
    }

    public function test_product_actions_fit_inside_the_table_and_respect_roles(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Produk Aksi',
            'sku' => 'ACTION-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('table-fixed', false)
            ->assertSee('overflow-x-hidden', false)
            ->assertSee(route('products.show', $product), false)
            ->assertSee(route('products.edit', $product), false)
            ->assertSee(route('products.destroy', $product), false)
            ->assertSee('Detail')
            ->assertSee('Ubah')
            ->assertSee('Hapus');

        $this->actingAs($manager)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('table-fixed', false)
            ->assertSee('overflow-x-hidden', false)
            ->assertSee(route('products.show', $product), false)
            ->assertSee(route('products.create'), false)
            ->assertDontSee('href="'.route('products.edit', $product).'"', false)
            ->assertDontSee('value="DELETE"', false);

        $this->actingAs($manager)->get(route('products.edit', $product))->assertForbidden();
        $this->actingAs($manager)->put(route('products.update', $product), [])->assertForbidden();
        $this->actingAs($manager)->delete(route('products.destroy', $product))->assertForbidden();
    }

    public function test_manager_can_create_products_but_cannot_edit_or_delete_them(): void
    {
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $category = Category::create(['name' => 'Perlengkapan']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);

        $this->actingAs($manager)->post(route('products.store'), [
            'name' => 'Rak Gudang',
            'sku' => 'RAK-001',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 2,
            'initial_stock' => 4,
        ])->assertRedirect(route('products.index'));

        $product = Product::where('sku', 'RAK-001')->firstOrFail();
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $manager->id,
            'type' => 'Masuk',
            'quantity' => 4,
            'status' => 'Diterima',
        ]);
    }

    public function test_staff_can_create_an_unregistered_product_during_receipt(): void
    {
        $staff = User::factory()->create(['role' => 'Staff Gudang']);
        $category = Category::create(['name' => 'Perlengkapan']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);

        $this->actingAs($staff)->post(route('products.store'), [
            'name' => 'Produk Baru Saat Penerimaan',
            'sku' => 'RECEIPT-NEW-01',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 2,
            'initial_stock' => 5,
        ])->assertRedirect(route('transactions.index'));

        $product = Product::where('sku', 'RECEIPT-NEW-01')->firstOrFail();
        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'user_id' => $staff->id,
            'type' => 'Masuk',
            'quantity' => 5,
            'status' => 'Diterima',
        ]);
    }

    public function test_manager_can_select_supplier_for_incoming_stock_transaction(): void
    {
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $category = Category::create(['name' => 'Perlengkapan']);
        $productSupplier = Supplier::create(['name' => 'Supplier Produk']);
        $receiptSupplier = Supplier::create(['name' => 'Supplier Penerimaan']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $productSupplier->id,
            'name' => 'Produk Supplier',
            'sku' => 'SUPPLIER-001',
            'purchase_price' => 10,
            'selling_price' => 15,
            'minimum_stock' => 1,
        ]);

        $this->actingAs($manager)->post(route('transactions.store'), [
            'product_id' => $product->id,
            'supplier_id' => $receiptSupplier->id,
            'type' => 'Masuk',
            'quantity' => 3,
            'date' => now('Asia/Jakarta')->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('stock_transactions', [
            'product_id' => $product->id,
            'supplier_id' => $receiptSupplier->id,
            'status' => 'Pending',
        ]);
    }

    public function test_admin_can_export_products_as_csv_and_other_roles_cannot_import_or_export(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $staff = User::factory()->create(['role' => 'Staff Gudang']);
        $category = Category::create(['name' => 'Elektronik']);
        $supplier = Supplier::create(['name' => 'Supplier Demo']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Monitor Demo',
            'sku' => 'EXPORT-01',
            'purchase_price' => 100,
            'selling_price' => 150,
            'minimum_stock' => 2,
        ]);

        $export = $this->actingAs($admin)
            ->get(route('products.export'))
            ->assertOk()
            ->assertDownload('stockify-products-'.now('Asia/Jakarta')->format('Y-m-d').'.csv');
        $this->assertStringContainsString('EXPORT-01', $export->streamedContent());

        $template = $this->actingAs($admin)
            ->get(route('products.import-template'))
            ->assertOk()
            ->assertDownload('stockify-product-import-template.csv');
        $this->assertStringContainsString('name,sku,category,supplier', $template->streamedContent());

        foreach ([$manager, $staff] as $user) {
            $this->actingAs($user)->get(route('products.export'))->assertForbidden();
            $this->actingAs($user)->get(route('products.import-template'))->assertForbidden();
            $this->actingAs($user)->post(route('products.import'), [
                'file' => UploadedFile::fake()->createWithContent('products.csv', "name,sku\n"),
            ])->assertForbidden();
        }

        $this->assertDatabaseHas('products', ['id' => $product->id, 'sku' => 'EXPORT-01']);
    }

    public function test_admin_dashboard_shows_recent_database_activities_in_western_indonesian_time(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        ActivityLog::forceCreate([
            'user_id' => $admin->id,
            'action' => 'stock.test',
            'description' => 'Aktivitas dashboard tersimpan.',
            'created_at' => Carbon::parse('2026-10-16 02:30:00', 'UTC'),
        ]);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas pengguna terbaru')
            ->assertSee('Aktivitas dashboard tersimpan.')
            ->assertSee('16 Okt 2026, 09:30');
    }

    public function test_internal_navigation_has_role_icons_without_a_page_loader(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-sidebar-toggle', false)
            ->assertSee('Ruang kerja')
            ->assertSee('Pengguna & hak akses', false)
            ->assertDontSee('Menyiapkan Stockify');
    }

    public function test_sidebar_reopen_control_is_available_on_each_role_workspace(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $staff = User::factory()->create(['role' => 'Staff Gudang']);

        $this->actingAs($admin)->get(route('reports.index'))->assertSee('data-sidebar-reopen', false);
        $this->actingAs($manager)->get(route('reports.index'))->assertSee('data-sidebar-reopen', false);
        $this->actingAs($staff)->get(route('transactions.index'))->assertSee('data-sidebar-reopen', false);
    }

    public function test_admin_can_update_application_name_information_and_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'Admin']);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('Apa itu Stockify?')
            ->assertSee('Tujuan aplikasi')
            ->assertSee('Nama aplikasi')
            ->assertSee('Simpan pengaturan');

        $this->actingAs($admin)->post(route('settings.update'), [
            'app_name' => 'Stockify Gudang',
            'app_info' => 'Informasi aplikasi yang diperbarui.',
            'logo' => UploadedFile::fake()->createWithContent(
                'logo.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/nWQAAAAASUVORK5CYII=')
            ),
        ])->assertRedirect(route('settings.index'));

        $logo = \App\Models\Setting::where('key', 'logo')->value('value');
        $this->assertSame('Stockify Gudang', \App\Models\Setting::valueFor('app_name'));
        $this->assertSame('Informasi aplikasi yang diperbarui.', \App\Models\Setting::valueFor('app_info'));
        $this->assertNotNull($logo);
        Storage::disk('public')->assertExists($logo);
        $this->assertDatabaseHas('activity_logs', ['action' => 'settings.updated']);
    }

    public function test_manager_cannot_change_application_settings(): void
    {
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);

        $this->actingAs($manager)
            ->post(route('settings.update'), ['app_name' => 'Nama ilegal'])
            ->assertForbidden();

        $this->assertSame('Stockify', \App\Models\Setting::valueFor('app_name', 'Stockify'));
    }

    public function test_admin_and_manager_see_user_activity_in_reports(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        ActivityLog::forceCreate([
            'user_id' => $manager->id,
            'action' => 'test.activity',
            'description' => 'Aktivitas internal khusus Admin.',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Aktivitas pengguna')
            ->assertSee('Aktivitas internal khusus Admin.');

        $this->actingAs($manager)
            ->get(route('reports.index'))
            ->assertOk()
            ->assertSee('Aktivitas pengguna')
            ->assertSee('Aktivitas internal khusus Admin.');

        $staff = User::factory()->create(['role' => 'Staff Gudang']);
        $this->actingAs($staff)->get(route('reports.index'))->assertForbidden();
    }

    public function test_dashboard_stock_chart_labels_months_with_their_year(): void
    {
        $admin = User::factory()->create(['role' => 'Admin']);
        Carbon::setTestNow(Carbon::parse('2026-10-16 03:00:00', 'UTC'));

        try {
            $this->actingAs($admin)
                ->get(route('dashboard'))
                ->assertOk()
                ->assertViewHas('chartLabels', function ($labels) {
                    return $labels[0] === Carbon::parse('2025-11-01')->locale('id')->translatedFormat('M Y')
                        && $labels[11] === Carbon::parse('2026-10-01')->locale('id')->translatedFormat('M Y');
                })
                ->assertSee('November 2025')
                ->assertSee('Oktober 2026');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_manager_report_uses_persisted_confirmed_records_in_the_selected_date_range(): void
    {
        $manager = User::factory()->create(['role' => 'Manajer Gudang']);
        $category = Category::create(['name' => 'Perlengkapan']);
        $supplier = Supplier::create(['name' => 'Pemasok Demo']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Barang Laporan',
            'sku' => 'REPORT-01',
            'purchase_price' => 10,
            'selling_price' => 15,
            'minimum_stock' => 1,
        ]);
        $from = now()->subDay()->toDateString();
        $to = now()->toDateString();
        $confirmed = StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $manager->id,
            'type' => 'Masuk',
            'quantity' => 7,
            'date' => $from,
            'status' => 'Diterima',
        ]);
        StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $manager->id,
            'type' => 'Masuk',
            'quantity' => 50,
            'date' => $to,
            'status' => 'Pending',
        ]);
        $outsidePeriod = StockTransaction::create([
            'product_id' => $product->id,
            'user_id' => $manager->id,
            'type' => 'Masuk',
            'quantity' => 90,
            'date' => now()->addDay()->toDateString(),
            'status' => 'Diterima',
        ]);
        $opname = StockOpname::create([
            'product_id' => $product->id,
            'user_id' => $manager->id,
            'system_quantity' => 7,
            'counted_quantity' => 6,
            'difference' => -1,
            'date' => $to,
            'notes' => 'Penyesuaian uji',
        ]);
        $localMorningActivity = ActivityLog::forceCreate([
            'user_id' => $manager->id,
            'action' => 'report.test',
            'description' => 'Aktivitas tersimpan di database.',
            'created_at' => Carbon::parse($from, 'Asia/Jakarta')->startOfDay()->utc()->addMinutes(30),
        ]);

        $this->actingAs($manager)
            ->get(route('reports.index', ['from' => $from, 'to' => $to]))
            ->assertOk()
            ->assertViewHas('transactions', fn ($transactions) => $transactions->pluck('id')->all() === [$confirmed->id])
            ->assertViewHas('opnames', fn ($opnames) => $opnames->pluck('id')->contains($opname->id))
            ->assertViewHas('balances', fn ($balances) => $balances[$product->id] === 6)
            ->assertViewHas('activities', fn ($activities) => $activities->pluck('id')->contains($localMorningActivity->id))
            ->assertSee('Aktivitas tersimpan di database.');

        $this->assertDatabaseHas('stock_transactions', ['id' => $confirmed->id, 'quantity' => 7]);
        $this->assertDatabaseHas('stock_transactions', ['id' => $outsidePeriod->id, 'quantity' => 90]);
    }
}
