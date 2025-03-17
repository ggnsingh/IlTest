<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Create some products
        $products = [
            [
                'name' => 'Laptop',
                'description' => 'High-performance laptop with 16GB RAM',
                'price' => 999.99,
                'stock_quantity' => 50,
            ],
            [
                'name' => 'Smartphone',
                'description' => 'Latest smartphone with 128GB storage',
                'price' => 699.99,
                'stock_quantity' => 100,
            ],
            [
                'name' => 'Headphones',
                'description' => 'Noise-cancelling wireless headphones',
                'price' => 199.99,
                'stock_quantity' => 75,
            ],
            [
                'name' => 'Keyboard',
                'description' => 'Mechanical keyboard with RGB lighting',
                'price' => 129.99,
                'stock_quantity' => 60,
            ],
            [
                'name' => 'Monitor',
                'description' => '27-inch 4K display with HDR',
                'price' => 349.99,
                'stock_quantity' => 40,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }

        // Create additional random products
        Product::factory()->count(15)->create();
    }
}
