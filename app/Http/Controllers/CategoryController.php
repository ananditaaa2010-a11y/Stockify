<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(CategoryService $categories): View
    {
        return view('categories.index', ['categories' => $categories->all()]);
    }

    public function store(Request $request, CategoryService $categories, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
        ]);
        $category = $categories->create($data);
        $logger->log('category.created', "Menambahkan kategori {$category->name}.");

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category, CategoryService $categories, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string'],
        ]);
        $categories->update($category, $data);
        $logger->log('category.updated', "Memperbarui kategori {$category->name}.");

        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category, CategoryService $categories, ActivityLogger $logger): RedirectResponse
    {
        if (! $categories->delete($category)) {
            return back()->withErrors(['category' => 'Kategori yang masih dipakai produk tidak dapat dihapus.']);
        }

        $name = $category->name;
        $logger->log('category.deleted', "Menghapus kategori {$name}.");

        return back()->with('status', 'Kategori berhasil dihapus.');
    }
}
