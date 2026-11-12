<?php
namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Electronics',
            'Clothing',
            'Food & Beverages',
            'Home & Furniture',
            'Beauty & Personal Care',
            'Sports & Outdoors',
            'Office Supplies',
            'Health & Medicine',
            'Automotive',
            'Accessories',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}