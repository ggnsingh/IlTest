<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\OrderCollection;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of the orders with filters.
     *
     * @param Request $request
     * @return OrderCollection
     */
    public function index(Request $request): OrderCollection
    {
        $filters = $request->only(['start_date', 'end_date', 'search', 'status']);
        $orders = $this->orderService->getFilteredOrders($filters);

        return new OrderCollection($orders);
    }

    /**
     * Store a newly created order in storage.
     *
     * @param StoreOrderRequest $request
     * @return OrderResource
     */
    public function store(StoreOrderRequest $request): OrderResource
    {
        $order = $this->orderService->createOrder($request->validated());

        return new OrderResource($order);
    }

    /**
     * Display the specified order.
     *
     * @param Order $order
     * @return OrderResource
     */
    public function show(Order $order): OrderResource
    {
        return new OrderResource($order->load(['user', 'orderItems.product']));
    }

    /**
     * Update the specified order in storage.
     *
     * @param UpdateOrderRequest $request
     * @param Order $order
     * @return OrderResource
     */
    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        $order = $this->orderService->updateOrder($order, $request->validated());

        return new OrderResource($order);
    }

    /**
     * Remove the specified order from storage.
     *
     * @param Order $order
     * @return Response
     */
    public function destroy(Order $order): Response
    {
        $this->orderService->deleteOrder($order);

        return response()->noContent();
    }
}
