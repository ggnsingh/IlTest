<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * Get all products.
     *
     * @return LengthAwarePaginator
     */
    public function getAllProducts(): LengthAwarePaginator
    {
        return Product::paginate(15);
    }

    /**
     * Create a new product.
     *
     * @param array $data
     * @return Product
     */
    public function createProduct(array $data)
    {
        return Product::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'stock_quantity' => $data['stock_quantity'] ?? 0,
        ]);
    }

    /**
     * Update an existing product.
     *
     * @param Product $product
     * @param array $data
     * @return Product
     */
    public function updateProduct(Product $product, array $data)
    {
        $product->update([
            'name' => $data['name'] ?? $product->name,
            'description' => $data['description'] ?? $product->description,
            'price' => $data['price'] ?? $product->price,
            'stock_quantity' => $data['stock_quantity'] ?? $product->stock_quantity,
        ]);

        return $product->fresh();
    }

    /**
     * Delete a product.
     *
     * @param Product $product
     * @return bool|string
     */
    public function deleteProduct(Product $product)
    {
        // Check if product is used in any order
        if ($product->orderItems()->exists()) {
            throw new \Exception('Cannot delete product as it is used in one or more orders');
        }

        return $product->delete();
    }
}

