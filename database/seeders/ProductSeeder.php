<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure categories exist
        if (Category::count() === 0) {
            $this->call(CategorySeeder::class);
        }

        $categories = Category::all();

        $products = [
            ['name' => 'Smartphone X', 'price' => 5000000, 'stock' => 10],
            ['name' => 'Wireless Headphones', 'price' => 750000, 'stock' => 25],
            ['name' => 'Men T-Shirt', 'price' => 150000, 'stock' => 50],
            ['name' => 'Cooking Pan Set', 'price' => 350000, 'stock' => 20],
            ['name' => 'Yoga Mat', 'price' => 200000, 'stock' => 30],
            ['name' => 'Laptop Pro', 'price' => 12000000, 'stock' => 5],
            ['name' => 'Bluetooth Speaker', 'price' => 450000, 'stock' => 40],
            ['name' => 'Novel Book', 'price' => 90000, 'stock' => 100],
            ['name' => 'Office Chair', 'price' => 800000, 'stock' => 15],
            ['name' => 'Sneakers', 'price' => 600000, 'stock' => 35],
            ['name' => 'Backpack', 'price' => 300000, 'stock' => 40],
            ['name' => 'LED Monitor 24"', 'price' => 1500000, 'stock' => 12],
            ['name' => 'Gaming Mouse', 'price' => 250000, 'stock' => 30],
            ['name' => 'Sports Watch', 'price' => 550000, 'stock' => 18],
            ['name' => 'Blender', 'price' => 400000, 'stock' => 22],
            ['name' => 'Desk Lamp', 'price' => 120000, 'stock' => 45],
            ['name' => 'Jeans', 'price' => 280000, 'stock' => 60],
            ['name' => 'Football', 'price' => 180000, 'stock' => 25],
            ['name' => 'Water Bottle', 'price' => 80000, 'stock' => 70],
            ['name' => 'Wireless Keyboard', 'price' => 320000, 'stock' => 28],
        ];

        foreach ($products as $data) {
            $category = $categories->random();

            Product::create([
                'category_id' => $category->id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name'] . '-' . Str::random(4)),
                'description' => $data['name'] . ' description',
                'price' => $data['price'],
                'stock' => $data['stock'],
                'is_active' => true,
            ]);
        }
    }
}