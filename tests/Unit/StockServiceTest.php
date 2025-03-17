<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $orderService;
    private User|Collection|Model $user;
    private array $products = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Create real StockService instance
        $stockService = new StockService();

        // Create OrderService with real StockService dependency
        $this->orderService = new OrderService($stockService);

        // Create a test user
        $this->user = User::factory()->create();

        // Create some test products
        for ($i = 0; $i < 3; $i++) {
            $this->products[] = Product::create([
                'name' => "Test Product $i",
                'description' => "Description $i",
                'price' => 10.00 + $i,
                'stock_quantity' => 20
            ]);
        }
    }

    /** @test */
    public function it_creates_order_with_items()
    {
        // Prepare order data
        $orderData = [
            'user_id' => $this->user->id,
            'description' => 'Test order',
            'total_amount' => 30.00,
            'status' => 'pending',
            'items' => [
                [
                    'product_id' => $this->products[0]->id,
                    'quantity' => 2,
                    'price' => $this->products[0]->price
                ],
                [
                    'product_id' => $this->products[1]->id,
                    'quantity' => 1,
                    'price' => $this->products[1]->price
                ]
            ]
        ];

        // Create order
        $order = $this->orderService->createOrder($orderData);

        // Check order was created
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('Test order', $order->description);
        $this->assertEquals(30.00, $order->total_amount);
        $this->assertEquals('pending', $order->status);

        // Check order items were created
        $this->assertCount(2, $order->orderItems);

        // Check stock was updated
        $this->products[0]->refresh();
        $this->products[1]->refresh();
        $this->assertEquals(18, $this->products[0]->stock_quantity);
        $this->assertEquals(19, $this->products[1]->stock_quantity);
    }

    /** @test */
    public function it_updates_order_with_new_items()
    {
        // Create an initial order
        $order = Order::factory()->for($this->user)->create([
            'description' => 'Original order',
            'total_amount' => 20.00,
            'status' => 'pending'
        ]);

        // Add an initial item
        $order->orderItems()->create([
            'product_id' => $this->products[0]->id,
            'quantity' => 1,
            'price' => $this->products[0]->price
        ]);

        // Update product stock
        $this->products[0]->decrement('stock_quantity', 1);

        // Prepare update data
        $updateData = [
            'description' => 'Updated order',
            'status' => 'processing',
            'items' => [
                [
                    'product_id' => $this->products[1]->id,
                    'quantity' => 2,
                    'price' => $this->products[1]->price
                ],
                [
                    'product_id' => $this->products[2]->id,
                    'quantity' => 1,
                    'price' => $this->products[2]->price
                ]
            ]
        ];

        // Update order
        $updatedOrder = $this->orderService->updateOrder($order, $updateData);

        // Check order was updated
        $this->assertEquals('Updated order', $updatedOrder->description);
        $this->assertEquals('processing', $updatedOrder->status);

        // Check order items were updated (old ones deleted, new ones created)
        $this->assertCount(2, $updatedOrder->orderItems);

        // Check original item's product stock was restored
        $this->products[0]->refresh();
        $this->assertEquals(20, $this->products[0]->stock_quantity, 'Original product stock should be restored');

        // Check new items' stock was updated
        $this->products[1]->refresh();
        $this->products[2]->refresh();
        $this->assertEquals(18, $this->products[1]->stock_quantity, 'New product 1 stock should be reduced by 2');
        $this->assertEquals(19, $this->products[2]->stock_quantity, 'New product 2 stock should be reduced by 1');
    }

    /** @test */
    public function it_deletes_order_and_restores_stock()
    {
        // Create an order
        $order = Order::factory()->for($this->user)->create();

        // Add items to the order
        $order->orderItems()->create([
            'product_id' => $this->products[0]->id,
            'quantity' => 3,
            'price' => $this->products[0]->price
        ]);

        $order->orderItems()->create([
            'product_id' => $this->products[1]->id,
            'quantity' => 2,
            'price' => $this->products[1]->price
        ]);

        // Update product stock
        $this->products[0]->decrement('stock_quantity', 3);
        $this->products[1]->decrement('stock_quantity', 2);

        // Initial stock checks
        $this->products[0]->refresh();
        $this->products[1]->refresh();
        $this->assertEquals(17, $this->products[0]->stock_quantity);
        $this->assertEquals(18, $this->products[1]->stock_quantity);

        // Delete the order
        $result = $this->orderService->deleteOrder($order);

        // Check deletion was successful
        $this->assertTrue($result);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);

        // Check product stock was restored
        $this->products[0]->refresh();
        $this->products[1]->refresh();
        $this->assertEquals(20, $this->products[0]->stock_quantity, 'Product 1 stock should be fully restored');
        $this->assertEquals(20, $this->products[1]->stock_quantity, 'Product 2 stock should be fully restored');
    }

    /** @test */
    public function it_filters_orders_correctly()
    {
        // Create multiple orders with different dates and descriptions
        Order::factory()->for($this->user)->create([
            'description' => 'Regular order',
            'order_date' => now()->subDays(10)
        ]);

        Order::factory()->for($this->user)->create([
            'description' => 'Special order',
            'order_date' => now()->subDays(5)
        ]);

        Order::factory()->for($this->user)->create([
            'description' => 'Recent regular order',
            'order_date' => now()->subDays(2)
        ]);

        Order::factory()->for($this->user)->create([
            'description' => 'Recent special order',
            'order_date' => now()->subDay()
        ]);

        // Test date filter
        $dateFilters = [
            'start_date' => now()->subDays(3)->format('Y-m-d')
        ];

        $dateFilteredOrders = $this->orderService->getFilteredOrders($dateFilters);
        $this->assertCount(2, $dateFilteredOrders);

        // Test search filter
        $searchFilters = [
            'search' => 'Special'
        ];

        $searchFilteredOrders = $this->orderService->getFilteredOrders($searchFilters);
        $this->assertCount(2, $searchFilteredOrders);

        // Test combined filters
        $combinedFilters = [
            'start_date' => now()->subDays(3)->format('Y-m-d'),
            'search' => 'Special'
        ];

        $combinedFilteredOrders = $this->orderService->getFilteredOrders($combinedFilters);
        $this->assertCount(1, $combinedFilteredOrders);
        $this->assertEquals('Recent special order', $combinedFilteredOrders->first()->description);
    }
}
