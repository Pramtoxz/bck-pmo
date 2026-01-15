<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $parts = [
            ['part_number' => '11200K0JN00', 'image' => 'parts/oil-001.jpg'],
            ['part_number' => '11200K0JN01', 'image' => 'parts/oil-002.jpg'],
            ['part_number' => '06455K0JN00', 'image' => 'parts/brake-001.jpg'],
            ['part_number' => '06435K0JN00', 'image' => 'parts/brake-002.jpg'],
            ['part_number' => '13010K0JN00', 'image' => 'parts/filter-001.jpg'],
        ];

        foreach ($parts as $part) {
            Product::firstOrCreate(
                ['part_number' => $part['part_number']],
                ['image' => $part['image']]
            );
        }
    }
}
