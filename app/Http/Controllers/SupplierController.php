<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\SupplierService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(SupplierService $suppliers): View
    {
        return view('suppliers.index', [
            'suppliers' => $suppliers->all(),
            'canManage' => auth()->user()->role === 'Admin',
        ]);
    }

    public function store(Request $request, SupplierService $suppliers, ActivityLogger $logger): RedirectResponse
    {
        $supplier = $suppliers->create($this->validatedData($request));
        $logger->log('supplier.created', "Menambahkan supplier {$supplier->name}.");

        return back()->with('status', 'Supplier berhasil ditambahkan.');
    }

    public function update(Request $request, Supplier $supplier, SupplierService $suppliers, ActivityLogger $logger): RedirectResponse
    {
        $suppliers->update($supplier, $this->validatedData($request));
        $logger->log('supplier.updated', "Memperbarui supplier {$supplier->name}.");

        return back()->with('status', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier, SupplierService $suppliers, ActivityLogger $logger): RedirectResponse
    {
        if (! $suppliers->delete($supplier)) {
            return back()->withErrors(['supplier' => 'Supplier yang masih terhubung ke produk tidak dapat dihapus.']);
        }

        $name = $supplier->name;
        $logger->log('supplier.deleted', "Menghapus supplier {$name}.");

        return back()->with('status', 'Supplier berhasil dihapus.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);
    }
}
