<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Syngenta',
            'BASF',
            'Bayer CropScience',
            'ACI Formulations',
            'Auto Crop Care',
            'Haychem',
            'McDonald Bangladesh',
            'Karnaphuli Fertilizer',
            'National Agri Care',
            'Globe Agro Vet',
        ];

        foreach ($brands as $i => $name) {
            Brand::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                    'sort_order' => $i,
                ]
            );
        }
    }
}
