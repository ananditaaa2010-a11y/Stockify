<?php

namespace App\Services;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    public function all(): Collection
    {
        return $this->categories->allWithProductCounts();
    }

    public function create(array $data): Category
    {
        return $this->categories->create($data);
    }

    public function update(Category $category, array $data): void
    {
        $this->categories->update($category, $data);
    }

    public function delete(Category $category): bool
    {
        if ($this->categories->hasProducts($category)) {
            return false;
        }

        $this->categories->delete($category);

        return true;
    }
}
