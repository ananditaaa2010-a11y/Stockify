<?php

namespace App\Services;

use App\Models\Supplier;
use App\Repositories\SupplierRepository;
use Illuminate\Database\Eloquent\Collection;

class SupplierService
{
    public function __construct(private SupplierRepository $suppliers)
    {
    }

    public function all(): Collection
    {
        return $this->suppliers->allWithProductCounts();
    }

    public function create(array $data): Supplier
    {
        return $this->suppliers->create($data);
    }

    public function update(Supplier $supplier, array $data): void
    {
        $this->suppliers->update($supplier, $data);
    }

    public function delete(Supplier $supplier): bool
    {
        if ($this->suppliers->hasProducts($supplier)) {
            return false;
        }

        $this->suppliers->delete($supplier);

        return true;
    }
}
