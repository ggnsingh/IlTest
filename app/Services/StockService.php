<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;

class StockService
{
    /**
     * Decrease product stock.
     *
     * @param Product $product
     * @param int $quantity
     * @return bool
     */
    public function decreaseStock(Product $product, int $quantity): bool
    {
        return DB::transaction(function () use ($product, $quantity) {
            // Lock the product row to avoid concurrency issues
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);

            // Check if there's enough stock
            if ($lockedProduct->stock_quantity < $quantity) {
                throw new \Exception("Not enough stock for product {$product->name}");
            }

            // Update stock
            $lockedProduct->stock_quantity -= $quantity;
            $lockedProduct->save();

            return true;
        });
    }

    /**
     * Increase product stock.
     *
     * @param Product $product
     * @param int $quantity
     * @return bool
     */
    public function increaseStock(Product $product, int $quantity)
    {
        return DB::transaction(function () use ($product, $quantity) {
            // Lock the product row to avoid concurrency issues
            $lockedProduct = Product::lockForUpdate()->findOrFail($product->id);

            // Update stock
            $lockedProduct->stock_quantity += $quantity;
            $lockedProduct->save();

            return true;
        });
    }
}
