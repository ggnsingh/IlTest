<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderService
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get filtered orders based on parameters.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getFilteredOrders(array $filters): LengthAwarePaginator
    {
        $query = Order::with('user');

        // Apply date filters
        if (isset($filters['start_date'])) {
            $query->where('order_date', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->where('order_date', '<=', $filters['end_date']);
        }

        // Apply search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $query) use ($search) {
                $query->whereHas('user', function (Builder $query) use ($search) {
                    $query->where('name', 'like', "%{$search}%");
                })
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('order_date', 'desc')->paginate(15);
    }

    /**
     * Create a new order with items.
     *
     * @param array $data
     * @return Order
     */
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Create order
            $order = Order::create([
                'user_id' => $data['user_id'],
                'description' => $data['description'] ?? null,
                'total_amount' => $data['total_amount'],
                'order_date' => $data['order_date'] ?? now(),
                'status' => $data['status'] ?? 'pending'
            ]);

            // Create order items and update stock
            foreach ($data['items'] as $item) {
                // Check if enough stock is available
                $product = Product::findOrFail($item['product_id']);
                $this->stockService->decreaseStock($product, $item['quantity']);

                // Create order item
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price']
                ]);
            }

            return $order->fresh(['orderItems.product', 'user']);
        });
    }

    /**
     * Update an existing order.
     *
     * @param Order $order
     * @param array $data
     * @return Order
     */
    public function updateOrder(Order $order, array $data)
    {
        return DB::transaction(function () use ($order, $data) {
            // Update order details
            $order->update([
                'description' => $data['description'] ?? $order->description,
                'total_amount' => $data['total_amount'] ?? $order->total_amount,
                'status' => $data['status'] ?? $order->status,
                'order_date' => $data['order_date'] ?? $order->order_date
            ]);

            // Handle order items if they are provided
            if (isset($data['items'])) {
                // First, restore stock for all existing items
                foreach ($order->orderItems as $existingItem) {
                    $this->stockService->increaseStock($existingItem->product, $existingItem->quantity);
                }

                // Delete all existing items
                $order->orderItems()->delete();

                // Create new order items and update stock
                foreach ($data['items'] as $item) {
                    $product = Product::findOrFail($item['product_id']);
                    $this->stockService->decreaseStock($product, $item['quantity']);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ]);
                }
            }

            return $order->fresh(['orderItems.product', 'user']);
        });
    }

    /**
     * Delete an order and restore stock.
     *
     * @param Order $order
     * @return bool
     */
    public function deleteOrder(Order $order)
    {
        return DB::transaction(function () use ($order) {
            // Restore stock for all items
            foreach ($order->orderItems as $item) {
                $this->stockService->increaseStock($item->product, $item->quantity);
            }

            // Delete the order (and its items due to cascade)
            return $order->delete();
        });
    }
}
