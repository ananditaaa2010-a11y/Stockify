<?php

namespace App\Services;

use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class ProductImportService
{
    private const REQUIRED_HEADERS = [
        'name',
        'sku',
        'category',
        'supplier',
        'purchase_price',
        'selling_price',
        'minimum_stock',
    ];

    public function __construct(
        private ProductRepository $products,
        private ProductService $productService
    ) {
    }

    public function import(UploadedFile $file, int $userId): array
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak dapat dibaca.']);
        }

        try {
            return $this->readRows($handle, $userId);
        } finally {
            fclose($handle);
        }
    }

    private function readRows($handle, int $userId): array
    {
        $headers = fgetcsv($handle, 0, ',', '"', '\\');
        if ($headers === false) {
            throw ValidationException::withMessages(['file' => 'File CSV kosong atau tidak memiliki header.']);
        }

        $headers = array_map(fn ($header) => trim((string) $header), $headers);
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        $normalizedHeaders = array_map(fn ($header) => strtolower($header), $headers);
        $missingHeaders = array_diff(self::REQUIRED_HEADERS, $normalizedHeaders);
        if ($missingHeaders !== []) {
            throw ValidationException::withMessages([
                'file' => 'Header CSV wajib mencakup: '.implode(', ', self::REQUIRED_HEADERS).'.',
            ]);
        }
        if (count($normalizedHeaders) !== count(array_unique($normalizedHeaders))) {
            throw ValidationException::withMessages(['file' => 'Header kolom CSV tidak boleh duplikat.']);
        }

        $dataRows = [];
        while (($values = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
            if ($values === [null] || count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $dataRows[] = $values;
            if (count($dataRows) > 1000) {
                throw ValidationException::withMessages(['file' => 'File CSV maksimal berisi 1.000 baris produk.']);
            }
        }

        if ($dataRows === []) {
            throw ValidationException::withMessages(['file' => 'File CSV tidak memiliki baris produk.']);
        }

        $imported = 0;
        $failed = 0;
        $errors = [];

        foreach ($dataRows as $index => $values) {
            $line = $index + 2;
            if (count($values) !== count($headers)) {
                $this->addError($errors, $failed, $line, ['Jumlah kolom tidak sesuai dengan header CSV.']);
                continue;
            }

            $row = array_combine($normalizedHeaders, array_map(fn ($value) => trim((string) $value), $values));
            $categoryId = $this->products->findIdByCategoryName($row['category'] ?? '');
            $supplierId = $this->products->findIdBySupplierName($row['supplier'] ?? '');
            $attributes = $this->parseAttributes($row['attributes'] ?? '');

            $data = [
                'name' => $row['name'] ?? '',
                'sku' => $row['sku'] ?? '',
                'category_id' => $categoryId,
                'supplier_id' => $supplierId,
                'description' => $row['description'] ?? null,
                'purchase_price' => $row['purchase_price'] ?? null,
                'selling_price' => $row['selling_price'] ?? null,
                'minimum_stock' => $row['minimum_stock'] ?? null,
                'initial_stock' => filled($row['initial_stock'] ?? null) ? $row['initial_stock'] : 0,
                'attributes' => $attributes['values'],
            ];

            $validator = Validator::make($data, [
                'name' => ['required', 'string', 'max:255'],
                'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')],
                'category_id' => ['required', 'exists:categories,id'],
                'supplier_id' => ['required', 'exists:suppliers,id'],
                'description' => ['nullable', 'string'],
                'purchase_price' => ['required', 'numeric', 'min:0'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'minimum_stock' => ['required', 'integer', 'min:0'],
                'initial_stock' => ['nullable', 'integer', 'min:0'],
            ]);

            $messages = [];
            if ($categoryId === null) {
                $messages[] = "Kategori \"{$row['category']}\" tidak ditemukan.";
            }
            if ($supplierId === null) {
                $messages[] = "Supplier \"{$row['supplier']}\" tidak ditemukan.";
            }
            $messages = array_merge($messages, $validator->errors()->all(), $attributes['errors']);
            if ($messages !== []) {
                $this->addError($errors, $failed, $line, $messages);
                continue;
            }

            try {
                $this->productService->create($validator->validated() + [
                    'initial_stock' => $data['initial_stock'],
                    'attributes' => $attributes['values'],
                ], $userId);
                $imported++;
            } catch (QueryException $exception) {
                if (! $this->products->skuExists((string) $data['sku'])) {
                    throw $exception;
                }

                $this->addError($errors, $failed, $line, ['SKU sudah digunakan oleh produk lain.']);
            }
        }

        return ['imported' => $imported, 'failed' => $failed, 'errors' => $errors];
    }

    private function parseAttributes(string $raw): array
    {
        $values = [];
        $errors = [];

        foreach (array_filter(array_map('trim', explode(';', $raw))) as $attribute) {
            $parts = array_map('trim', explode(':', $attribute, 2));
            if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
                $errors[] = 'Format atribut harus Nama:Nilai dan dipisahkan dengan titik koma.';
                continue;
            }
            if (mb_strlen($parts[0]) > 100 || mb_strlen($parts[1]) > 255) {
                $errors[] = 'Nama atribut maksimal 100 karakter dan nilainya maksimal 255 karakter.';
                continue;
            }
            $values[] = ['name' => $parts[0], 'value' => $parts[1]];
        }

        return ['values' => $values, 'errors' => $errors];
    }

    private function addError(array &$errors, int &$failed, int $line, array $messages): void
    {
        $failed++;
        if (count($errors) < 25) {
            $errors[] = ['line' => $line, 'messages' => array_values(array_unique($messages))];
        }
    }
}
