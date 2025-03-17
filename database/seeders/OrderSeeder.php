<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = User::all();
        $products = Product::all();
        $statuses = ['pending', 'processing', 'completed', 'cancelled'];

        // Create orders for each user
        foreach ($users as $user) {
            $orderCount = rand(1, 5);

            for ($i = 0; $i < $orderCount; $i++) {
                $orderDate = Carbon::now()->subDays(rand(1, 30));
                $status = $statuses[array_rand($statuses)];

                $order = Order::create([
                    'user_id' => $user->id,
                    'description' => "Order #{$i} for {$user->name}",
                    'order_date' => $orderDate,
                    'status' => $status,
                    'total_amount' => 0 // Will be calculated based on items
                ]);

                // Add random items to the order
                $itemCount = rand(1, 5);
                $totalAmount = 0;

                // Use a subset of products to avoid depleting stock
                $orderProducts = $products->random($itemCount);

                foreach ($orderProducts as $product) {
                    $quantity = rand(1, 3);
                    $price = $product->price;
                    $subtotal = $quantity * $price;
                    $totalAmount += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'price' => $price
                    ]);

                    // Update product stock
                    $product->decrement('stock_quantity', $quantity);
                }

                // Update order total
                $order->update(['total_amount' => $totalAmount]);
            }
        }
    }
}
