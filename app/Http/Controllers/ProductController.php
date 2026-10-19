<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\ActivityLogger;
use App\Services\InventoryLedger;
use App\Services\ProductImportService;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(InventoryLedger $ledger, ProductRepository $products): View
    {
        return view('products.index', [
            'products' => $products->allWithRelations(),
            'stockBalances' => $ledger->balances(),
            'canManage' => auth()->user()->role === 'Admin',
            'canCreate' => in_array(auth()->user()->role, ['Admin', 'Manajer Gudang', 'Staff Gudang'], true),
        ]);
    }

    public function create(ProductRepository $products): View
    {
        return view('products.form', $this->formOptions($products));
    }

    public function show(Product $product, InventoryLedger $ledger, ProductRepository $products): View
    {
        return view('products.show', [
            'product' => $products->findWithRelations($product),
            'stock' => (int) ($ledger->balances()[$product->id] ?? 0),
        ]);
    }

    public function store(Request $request, ProductService $products, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request);
        $product = $products->create($data, (int) $request->user()->id);

        $logger->log('product.created', "Menambahkan produk {$product->name}.");

        $destination = $request->user()->role === 'Staff Gudang' ? 'transactions.index' : 'products.index';

        return redirect()->route($destination)->with('status', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product, ProductRepository $products): View
    {
        return view('products.form', $this->formOptions($products, $products->findWithRelations($product)));
    }

    public function update(Request $request, Product $product, ProductService $products, ActivityLogger $logger): RedirectResponse
    {
        $data = $this->validatedData($request, $product);
        $products->update($product, $data);

        $logger->log('product.updated', "Memperbarui produk {$product->name}.");

        return redirect()->route('products.index')->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product, ProductService $products, ActivityLogger $logger): RedirectResponse
    {
        if (! $products->delete($product)) {
            return back()->withErrors(['product' => 'Produk yang memiliki riwayat stok tidak dapat dihapus.']);
        }

        $logger->log('product.deleted', "Menghapus produk {$product->name}.");

        return redirect()->route('products.index')->with('status', 'Produk berhasil dihapus.');
    }

    public function import(Request $request, ProductImportService $importer, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $result = $importer->import($request->file('file'), (int) $request->user()->id);
        if ($result['imported'] > 0) {
            $logger->log('product.imported', "Mengimpor {$result['imported']} produk dari file CSV.");
        }

        return back()
            ->with('import_result', $result)
            ->with('status', "Import selesai: {$result['imported']} berhasil, {$result['failed']} gagal.");
    }

    public function importTemplate(): StreamedResponse
    {
        return Response::streamDownload(function () {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka stream template import.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'name',
                'sku',
                'category',
                'supplier',
                'description',
                'purchase_price',
                'selling_price',
                'minimum_stock',
                'initial_stock',
                'attributes',
            ]);
            fclose($output);
        }, 'stockify-product-import-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function export(ProductRepository $products, InventoryLedger $ledger): StreamedResponse
    {
        $items = $products->allWithRelations();
        $balances = $ledger->balances();

        return Response::streamDownload(function () use ($items, $balances) {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Tidak dapat membuka stream ekspor produk.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'name',
                'sku',
                'category',
                'supplier',
                'description',
                'purchase_price',
                'selling_price',
                'minimum_stock',
                'initial_stock',
                'attributes',
            ]);

            foreach ($items as $product) {
                $attributes = $product->attributes
                    ->map(fn ($attribute) => $attribute->name.':'.$attribute->value)
                    ->implode(';');

                fputcsv($output, [
                    $product->name,
                    $product->sku,
                    $product->category->name ?? '',
                    $product->supplier->name ?? '',
                    $product->description ?? '',
                    $product->purchase_price,
                    $product->selling_price,
                    $product->minimum_stock,
                    (int) ($balances[$product->id] ?? 0),
                    $attributes,
                ]);
            }

            fclose($output);
        }, 'stockify-products-'.now('Asia/Jakarta')->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function formOptions(ProductRepository $products, ?Product $product = null): array
    {
        return [
            'product' => $product?->load('attributes.definition'),
            'categories' => $products->formCategories(),
            'suppliers' => $products->formSuppliers(),
            'attributeDefinitions' => $products->formAttributeDefinitions(),
        ];
    }

    private function validatedData(Request $request, ?Product $product = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product?->id)],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'description' => ['nullable', 'string'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'initial_stock' => [$product ? 'nullable' : 'required', 'integer', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'attributes.*.definition_id' => ['nullable', 'integer', 'distinct', 'required_with:attributes.*.value', 'exists:product_attribute_definitions,id'],
            'attributes.*.value' => ['nullable', 'string', 'max:255', 'required_with:attributes.*.definition_id'],
        ]);
    }

}
