<?php

namespace App\Repositories;

use App\Models\ProductAttributeDefinition;
use Illuminate\Database\Eloquent\Collection;

class ProductAttributeDefinitionRepository
{
    public function allWithUsageCounts(): Collection
    {
        return ProductAttributeDefinition::withCount('attributes')->orderBy('name')->get();
    }

    public function create(array $data): ProductAttributeDefinition
    {
        return ProductAttributeDefinition::create($data);
    }

    public function update(ProductAttributeDefinition $definition, array $data): void
    {
        $definition->update($data);
        $definition->attributes()->update(['name' => $definition->name]);
    }

    public function delete(ProductAttributeDefinition $definition): void
    {
        $definition->delete();
    }
}
