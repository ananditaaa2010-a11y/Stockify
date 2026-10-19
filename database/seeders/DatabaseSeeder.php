<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Demo accounts and inventory must not be seeded in production.');
        }

        $accounts = [
            ['name' => 'Admin Stockify', 'email' => 'admin@stockify.test', 'role' => 'Admin', 'password' => 'Admin123'],
            ['name' => 'Manajer Gudang', 'email' => 'manager@stockify.test', 'role' => 'Manajer Gudang', 'password' => 'Manager1'],
            ['name' => 'Staff Gudang', 'email' => 'staff@stockify.test', 'role' => 'Staff Gudang', 'password' => 'Staff123'],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'role' => $account['role'],
                    'password' => Hash::make($account['password']),
                ]
            );
        }

        $category = Category::firstOrCreate(
            ['name' => 'Elektronik'],
            ['description' => 'Perangkat dan aksesori elektronik.']
        );
        $supplier = Supplier::firstOrCreate(
            ['name' => 'PT Sumber Elektronik'],
            [
                'address' => 'Jakarta',
                'phone' => '0215550100',
                'email' => 'supplier@stockify.test',
            ]
        );

        $manager = User::where('email', 'manager@stockify.test')->firstOrFail();
        $products = [
            ['name' => 'Laptop Lenovo IdeaPad Slim 3', 'sku' => 'ELK-LPT-001', 'purchase_price' => 7200000, 'selling_price' => 8499000, 'minimum_stock' => 5, 'image' => 'products/demo-laptop.png', 'quantity' => 18],
            ['name' => 'Mouse Logitech M170', 'sku' => 'ELK-MSE-002', 'purchase_price' => 95000, 'selling_price' => 149000, 'minimum_stock' => 10, 'image' => 'products/demo-mouse.png', 'quantity' => 32],
            ['name' => 'Keyboard Mechanical Royal Kludge', 'sku' => 'ELK-KBD-003', 'purchase_price' => 430000, 'selling_price' => 599000, 'minimum_stock' => 8, 'image' => 'products/demo-keyboard.png', 'quantity' => 14],
            ['name' => 'Monitor LG 24 inch', 'sku' => 'ELK-MON-004', 'purchase_price' => 1650000, 'selling_price' => 2099000, 'minimum_stock' => 4, 'image' => 'products/demo-monitor.png', 'quantity' => 11],
            ['name' => 'Headset JBL Tune 520BT', 'sku' => 'ELK-HDS-005', 'purchase_price' => 560000, 'selling_price' => 729000, 'minimum_stock' => 6, 'image' => 'products/demo-headset.png', 'quantity' => 20],
        ];

        foreach ($products as $productData) {
            $quantity = $productData['quantity'];
            unset($productData['quantity']);

            $product = Product::firstOrCreate(
                ['sku' => $productData['sku']],
                array_merge($productData, [
                    'category_id' => $category->id,
                    'supplier_id' => $supplier->id,
                    'description' => 'Produk contoh elektronik untuk demonstrasi Stockify.',
                ])
            );

            if ($product->image === null) {
                $product->update(['image' => $productData['image']]);
            }

            StockTransaction::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'user_id' => $manager->id,
                    'type' => 'Masuk',
                    'date' => now()->toDateString(),
                    'status' => 'Diterima',
                    'notes' => 'Stok awal demo',
                ],
                ['quantity' => $quantity]
            );
        }
    }
}
