<?php

namespace Tests\Feature;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User|Collection|Model $user;
    protected array $products = [];

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a user
        $this->user = User::factory()->create();

        // Create some products
        for ($i = 0; $i < 3; $i++) {
            $this->products[] = Product::create([
                'name' => "Product $i",
                'description' => "Description of product $i",
                'price' => rand(10, 100),
                'stock_quantity' => 50,
            ]);
        }
    }

    /**
     * Test creating a new order.
     */
    public function testCreateOrder()
    {
        $orderData = [
            'user_id' => $this->user->id,
            'description' => 'Test order',
            'total_amount' => 150.00,
            'order_date' => now()->format('Y-m-d H:i:s'),
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

        $response = $this->postJson('/api/orders', $orderData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user',
                    'status',
                    'description',
                    'total_amount',
                    'order_date',
                    'items'
                ]
            ]);

        // Check that stock was reduced
        $this->assertEquals(
            48,
            Product::find($this->products[0]->id)->stock_quantity,
            'Stock quantity should be reduced by 2'
        );

        $this->assertEquals(
            49,
            Product::find($this->products[1]->id)->stock_quantity,
            'Stock quantity should be reduced by 1'
        );
    }

    /**
     * Test getting order list with filters.
     */
    public function testGetOrdersWithFilters()
    {
        // Create some orders
        Order::factory()
            ->for($this->user)
            ->count(5)
            ->create(['order_date' => now()->subDays(10)]);

        Order::factory()
            ->for($this->user)
            ->count(3)
            ->create(['order_date' => now(), 'description' => 'Special order']);

        // Test date filtering
        $response = $this->getJson('/api/orders?start_date=' . now()->format('Y-m-d'));
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        // Test search filtering
        $response = $this->getJson('/api/orders?search=Special');
        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test updating an order.
     */
    public function testUpdateOrder()
    {
        // Create an order first
        $order = Order::factory()
            ->for($this->user)
            ->create(['total_amount' => 100.00]);

        // Add items to the order
        $orderItem = $order->orderItems()->create([
            'product_id' => $this->products[0]->id,
            'quantity' => 1,
            'price' => $this->products[0]->price
        ]);

        // Update product stock to reflect order
        $this->products[0]->decrement('stock_quantity', 1);

        // Prepare update data
        $updateData = [
            'description' => 'Updated order description',
            'status' => 'processing',
            'items' => [
                [
                    'product_id' => $this->products[0]->id,
                    'quantity' => 2,  // Increase quantity
                    'price' => $this->products[0]->price
                ],
                [
                    'product_id' => $this->products[1]->id,
                    'quantity' => 1,  // Add new product
                    'price' => $this->products[1]->price
                ]
            ]
        ];

        $response = $this->putJson("/api/orders/{$order->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'description' => 'Updated order description',
                    'status' => 'processing'
                ]
            ]);

        // Check stock levels - product[0] should now be -2 (original -1 restored, then -2 again)
        $this->assertEquals(
            48,
            Product::find($this->products[0]->id)->stock_quantity,
            'Stock quantity should be reduced by 2 after update'
        );

        // Check product[1] was reduced by 1
        $this->assertEquals(
            49,
            Product::find($this->products[1]->id)->stock_quantity,
            'Stock quantity should be reduced by 1 for the new product'
        );
    }

    /**
     * Test deleting an order.
     */
    public function testDeleteOrder()
    {
        // Create an order
        $order = Order::factory()
            ->for($this->user)
            ->create();

        // Add items to the order
        $orderItem = $order->orderItems()->create([
            'product_id' => $this->products[0]->id,
            'quantity' => 3,
            'price' => $this->products[0]->price
        ]);

        // Update product stock to reflect order
        $this->products[0]->decrement('stock_quantity', 3);
        $initialStock = $this->products[0]->fresh()->stock_quantity;

        // Delete the order
        $response = $this->deleteJson("/api/orders/{$order->id}");
        $response->assertStatus(204);

        // Check that the order was deleted
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);

        // Check that stock was restored
        $this->assertEquals(
            $initialStock + 3,
            Product::find($this->products[0]->id)->stock_quantity,
            'Stock quantity should be restored after order deletion'
        );
    }

    /**
     * Test insufficient stock handling.
     */
    public function testInsufficientStockHandling()
    {
        // Create a product with low stock
        $lowStockProduct = Product::create([
            'name' => 'Low Stock Product',
            'price' => 25.00,
            'stock_quantity' => 2
        ]);

        // Try to create an order with quantity higher than available stock
        $orderData = [
            'user_id' => $this->user->id,
            'total_amount' => 75.00,
            'items' => [
                [
                    'product_id' => $lowStockProduct->id,
                    'quantity' => 3, // More than available (2)
                    'price' => $lowStockProduct->price
                ]
            ]
        ];

        $response = $this->postJson('/api/orders', $orderData);

        // Stock should remain unchanged
        $this->assertEquals(
            2,
            Product::find($lowStockProduct->id)->stock_quantity,
            'Stock should not change when order fails due to insufficient stock'
        );
    }
}
