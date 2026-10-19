<?php

namespace App\Services;

use App\Models\ProductAttributeDefinition;
use App\Repositories\ProductAttributeDefinitionRepository;
use Illuminate\Database\Eloquent\Collection;

class ProductAttributeDefinitionService
{
    public function __construct(private ProductAttributeDefinitionRepository $definitions)
    {
    }

    public function all(): Collection
    {
        return $this->definitions->allWithUsageCounts();
    }

    public function create(array $data): ProductAttributeDefinition
    {
        return $this->definitions->create($data);
    }

    public function update(ProductAttributeDefinition $definition, array $data): void
    {
        $this->definitions->update($definition, $data);
    }

    public function delete(ProductAttributeDefinition $definition): bool
    {
        if ($definition->attributes()->exists()) {
            return false;
        }

        $this->definitions->delete($definition);

        return true;
    }
}
