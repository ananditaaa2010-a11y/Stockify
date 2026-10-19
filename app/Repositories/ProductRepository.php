<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeDefinition;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Collection;

class ProductRepository
{
    private array $relatedIds = [];

    public function allWithRelations(): Collection
    {
        return Product::with(['category', 'supplier', 'attributes'])->orderBy('name')->get();
    }

    public function formCategories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function formSuppliers(): Collection
    {
        return Supplier::orderBy('name')->get();
    }

    public function formAttributeDefinitions(): Collection
    {
        return ProductAttributeDefinition::orderBy('name')->get();
    }

    public function findWithRelations(Product $product): Product
    {
        return $product->load(['category', 'supplier', 'attributes.definition']);
    }

    public function findIdByCategoryName(string $name): ?int
    {
        return $this->findRelatedId('categories', $name);
    }

    public function findIdBySupplierName(string $name): ?int
    {
        return $this->findRelatedId('suppliers', $name);
    }

    public function skuExists(string $sku): bool
    {
        return Product::where('sku', $sku)->exists();
    }

    public function create(array $data, array $attributes): Product
    {
        $product = Product::create($data);
        $this->syncAttributes($product, $attributes);

        return $product;
    }

    public function update(Product $product, array $data, array $attributes): void
    {
        $product->update($data);
        $this->syncAttributes($product, $attributes);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function recordOpeningStock(Product $product, int $quantity, int $userId, string $date): void
    {
        if ($quantity < 1) {
            return;
        }

        StockTransaction::create([
            'product_id' => $product->id,
            'supplier_id' => $product->supplier_id,
            'user_id' => $userId,
            'type' => 'Masuk',
            'quantity' => $quantity,
            'date' => $date,
            'status' => 'Diterima',
            'notes' => 'Pencatatan stok awal produk.',
        ]);
    }

    private function findRelatedId(string $table, string $name): ?int
    {
        if (! array_key_exists($table, $this->relatedIds)) {
            $this->relatedIds[$table] = \Illuminate\Support\Facades\DB::table($table)
                ->get(['id', 'name'])
                ->mapWithKeys(fn ($record) => [mb_strtolower(trim($record->name)) => (int) $record->id])
                ->all();
        }

        return $this->relatedIds[$table][mb_strtolower(trim($name))] ?? null;
    }

    private function syncAttributes(Product $product, array $attributes): void
    {
        $product->attributes()->delete();

        foreach ($attributes as $attribute) {
            if (filled($attribute['definition_id'] ?? null) && filled($attribute['value'] ?? null)) {
                $definition = ProductAttributeDefinition::findOrFail($attribute['definition_id']);
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'definition_id' => $definition->id,
                    'name' => $definition->name,
                    'value' => $attribute['value'],
                ]);
            } elseif (filled($attribute['name'] ?? null) && filled($attribute['value'] ?? null)) {
                $definition = ProductAttributeDefinition::firstOrCreate(['name' => $attribute['name']]);
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'definition_id' => $definition->id,
                    'name' => $definition->name,
                    'value' => $attribute['value'],
                ]);
            }
        }
    }
}
