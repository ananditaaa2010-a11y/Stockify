<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function allWithProductCounts(): Collection
    {
        return Category::withCount('products')->orderBy('name')->get();
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): void
    {
        $category->update($data);
    }

    public function hasProducts(Category $category): bool
    {
        return $category->products()->exists();
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }
}
