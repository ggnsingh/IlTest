<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderItemResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Order $order): AnonymousResourceCollection
    {
        return OrderItemResource::collection($order->orderItems()->with('product')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Order $order)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($order, $validated) {
            $product = Product::findOrFail($validated['product_id']);

            // Check and reduce stock
            $this->stockService->decreaseStock($product, $validated['quantity']);

            // Create new order item
            $orderItem = $order->orderItems()->create([
                'product_id' => $validated['product_id'],
                'quantity' => $validated['quantity'],
                'price' => $validated['price']
            ]);

            // Update order total amount
            $order->update([
                'total_amount' => $order->orderItems()->sum(DB::raw('price * quantity'))
            ]);

            return new OrderItemResource($orderItem->load('product'));
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order, OrderItem $item): OrderItemResource|JsonResponse
    {
        // Ensure the item belongs to the order
        if ($item->order_id !== $order->id) {
            return response()->json(['message' => 'Order item not found'], 404);
        }

        return new OrderItemResource($item->load('product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order, OrderItem $item)
    {
        // Ensure the item belongs to the order
        if ($item->order_id !== $order->id) {
            return response()->json(['message' => 'Order item not found'], 404);
        }

        $validated = $request->validate([
            'quantity' => 'sometimes|required|integer|min:1',
            'price' => 'sometimes|required|numeric|min:0'
        ]);

        return DB::transaction(function () use ($item, $validated) {
            $oldQuantity = $item->quantity;
            $newQuantity = $validated['quantity'] ?? $oldQuantity;

            // Adjust stock if quantity changes
            if ($oldQuantity != $newQuantity) {
                $product = $item->product;

                if ($newQuantity > $oldQuantity) {
                    // Decrease stock by the difference
                    $this->stockService->decreaseStock($product, $newQuantity - $oldQuantity);
                } else {
                    // Increase stock by the difference
                    $this->stockService->increaseStock($product, $oldQuantity - $newQuantity);
                }
            }

            // Update the item
            $item->update($validated);

            // Update order total amount
            $order = $item->order;
            $order->update([
                'total_amount' => $order->orderItems()->sum(DB::raw('price * quantity'))
            ]);

            return new OrderItemResource($item->fresh('product'));
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order, OrderItem $item)
    {
        // Ensure the item belongs to the order
        if ($item->order_id !== $order->id) {
            return response()->json(['message' => 'Order item not found'], 404);
        }

        return DB::transaction(function () use ($order, $item) {
            // Restore stock
            $this->stockService->increaseStock($item->product, $item->quantity);

            // Delete the item
            $item->delete();

            // Update order total amount
            $order->update([
                'total_amount' => $order->orderItems()->sum(DB::raw('price * quantity'))
            ]);

            return response()->noContent();
        });
    }
}
