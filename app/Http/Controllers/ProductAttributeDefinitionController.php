<?php

namespace App\Http\Controllers;

use App\Models\ProductAttributeDefinition;
use App\Services\ActivityLogger;
use App\Services\ProductAttributeDefinitionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductAttributeDefinitionController extends Controller
{
    public function index(ProductAttributeDefinitionService $definitions): View
    {
        return view('attribute-definitions.index', ['definitions' => $definitions->all()]);
    }

    public function store(Request $request, ProductAttributeDefinitionService $definitions, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:product_attribute_definitions,name']]);
        $definition = $definitions->create($data);
        $logger->log('product.attribute.created', "Menambahkan definisi atribut {$definition->name}.");

        return back()->with('status', 'Definisi atribut berhasil ditambahkan.');
    }

    public function update(Request $request, ProductAttributeDefinition $definition, ProductAttributeDefinitionService $definitions, ActivityLogger $logger): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('product_attribute_definitions', 'name')->ignore($definition->id)],
        ]);
        $definitions->update($definition, $data);
        $logger->log('product.attribute.updated', "Memperbarui definisi atribut {$definition->name}.");

        return back()->with('status', 'Definisi atribut berhasil diperbarui.');
    }

    public function destroy(ProductAttributeDefinition $definition, ProductAttributeDefinitionService $definitions, ActivityLogger $logger): RedirectResponse
    {
        $name = $definition->name;
        if (! $definitions->delete($definition)) {
            return back()->withErrors(['definition' => 'Definisi atribut yang sudah digunakan produk tidak dapat dihapus.']);
        }

        $logger->log('product.attribute.deleted', "Menghapus definisi atribut {$name}.");

        return back()->with('status', 'Definisi atribut berhasil dihapus.');
    }
}
