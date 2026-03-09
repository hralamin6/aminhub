<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'সার (Fertilizer)', 'icon' => 'o-beaker', 'children' => [
                ['name' => 'জৈব সার (Organic)'],
                ['name' => 'রাসায়নিক সার (Chemical)'],
            ]],
            ['name' => 'কীটনাশক (Pesticide)', 'icon' => 'o-bug-ant', 'children' => [
                ['name' => 'Insecticide'],
                ['name' => 'Fungicide'],
                ['name' => 'Herbicide'],
            ]],
            ['name' => 'বীজ (Seeds)', 'icon' => 'o-sun'],
            ['name' => 'পলিথিন (Polythene)', 'icon' => 'o-archive-box'],
            ['name' => 'রশি (Rope)', 'icon' => 'o-link'],
            ['name' => 'অন্যান্য (Others)', 'icon' => 'o-squares-plus'],
        ];

        foreach ($categories as $i => $cat) {
            $parent = Category::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'icon' => $cat['icon'] ?? null,
                    'sort_order' => $i,
                    'is_active' => true,
                ]
            );

            if (isset($cat['children'])) {
                foreach ($cat['children'] as $j => $child) {
                    Category::firstOrCreate(
                        ['slug' => Str::slug($child['name'])],
                        [
                            'name' => $child['name'],
                            'parent_id' => $parent->id,
                            'sort_order' => $j,
                            'is_active' => true,
                        ]
                    );
                }
            }
        }
    }
}
