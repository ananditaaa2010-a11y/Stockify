<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockOpname;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\Collection;

class ReportRepository
{
    public function categories(): Collection
    {
        return Category::orderBy('name')->get();
    }

    public function products(?int $categoryId): Collection
    {
        return Product::with('category')
            ->when($categoryId, fn ($query) => $query->where('category_id', $categoryId))
            ->orderBy('name')
            ->get();
    }

    public function transactions(string $from, string $to, ?int $categoryId): Collection
    {
        return StockTransaction::with(['product.category', 'product.supplier', 'user', 'supplier'])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->where(fn ($query) => $query
                ->where(fn ($query) => $query->where('type', 'Masuk')->where('status', 'Diterima'))
                ->orWhere(fn ($query) => $query->where('type', 'Keluar')->where('status', 'Dikeluarkan')))
            ->when($categoryId, fn ($query) => $query->whereHas('product', fn ($products) => $products->where('category_id', $categoryId)))
            ->latest('date')
            ->latest('id')
            ->get();
    }

    public function opnames(string $from, string $to, ?int $categoryId): Collection
    {
        return StockOpname::with(['product.category', 'user'])
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->when($categoryId, fn ($query) => $query->whereHas('product', fn ($products) => $products->where('category_id', $categoryId)))
            ->latest('date')
            ->get();
    }

    public function activities(string $from, string $to): Collection
    {
        return ActivityLog::with('user')->whereBetween('created_at', [$from, $to])->latest()->limit(100)->get();
    }
}
