<?php

namespace App\Repositories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Collection;

class SupplierRepository
{
    public function allWithProductCounts(): Collection
    {
        return Supplier::withCount('products')->orderBy('name')->get();
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): void
    {
        $supplier->update($data);
    }

    public function hasProducts(Supplier $supplier): bool
    {
        return $supplier->products()->exists();
    }

    public function delete(Supplier $supplier): void
    {
        $supplier->delete();
    }
}
