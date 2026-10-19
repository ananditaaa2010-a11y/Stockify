<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductService
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function create(array $data, int $userId): Product
    {
        $image = $data['image'] ?? null;
        unset($data['image']);
        $imagePath = $this->storeImage($image);

        try {
            return DB::transaction(function () use ($data, $userId, $imagePath) {
                $attributes = $data['attributes'] ?? [];
                $openingStock = (int) ($data['initial_stock'] ?? 0);
                unset($data['attributes'], $data['initial_stock']);
                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                $product = $this->products->create($data, $attributes);
                $this->products->recordOpeningStock(
                    $product,
                    $openingStock,
                    $userId,
                    now('Asia/Jakarta')->toDateString()
                );

                return $product;
            });
        } catch (Throwable $exception) {
            $this->deleteImage($imagePath);
            throw $exception;
        }
    }

    public function update(Product $product, array $data): void
    {
        $image = $data['image'] ?? null;
        unset($data['image']);
        $imagePath = $this->storeImage($image);
        $oldImagePath = $product->image;

        try {
            DB::transaction(function () use ($product, $data, $imagePath) {
                $attributes = $data['attributes'] ?? [];
                unset($data['attributes'], $data['initial_stock']);
                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                $this->products->update($product, $data, $attributes);
            });
        } catch (Throwable $exception) {
            $this->deleteImage($imagePath);
            throw $exception;
        }

        if ($imagePath !== null && $oldImagePath !== $imagePath) {
            $this->deleteImage($oldImagePath);
        }
    }

    public function delete(Product $product): bool
    {
        if ($product->stockTransactions()->exists() || $product->stockOpnames()->exists()) {
            return false;
        }

        $this->products->delete($product);
        $this->deleteImage($product->image);

        return true;
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if ($image === null) {
            return null;
        }

        $path = $image->store('products', 'public');
        if ($path === false) {
            throw new \RuntimeException('Gambar produk gagal disimpan.');
        }

        return $path;
    }

    private function deleteImage(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
