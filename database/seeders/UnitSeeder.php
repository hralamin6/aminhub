<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            // Weight
            ['name' => 'Kilogram', 'short_name' => 'kg', 'unit_type' => 'weight'],
            ['name' => 'Gram', 'short_name' => 'g', 'unit_type' => 'weight'],
            ['name' => 'Ton', 'short_name' => 'ton', 'unit_type' => 'weight'],

            // Volume
            ['name' => 'Liter', 'short_name' => 'ltr', 'unit_type' => 'volume'],
            ['name' => 'Milliliter', 'short_name' => 'ml', 'unit_type' => 'volume'],

            // Length
            ['name' => 'Meter', 'short_name' => 'm', 'unit_type' => 'length'],
            ['name' => 'Feet', 'short_name' => 'ft', 'unit_type' => 'length'],

            // Piece
            ['name' => 'Piece', 'short_name' => 'pcs', 'unit_type' => 'piece'],
            ['name' => 'Dozen', 'short_name' => 'dz', 'unit_type' => 'piece'],

            // Pack
            ['name' => 'Bag', 'short_name' => 'bag', 'unit_type' => 'pack'],
            ['name' => 'Bottle', 'short_name' => 'btl', 'unit_type' => 'pack'],
            ['name' => 'Packet', 'short_name' => 'pkt', 'unit_type' => 'pack'],
            ['name' => 'Roll', 'short_name' => 'roll', 'unit_type' => 'pack'],
            ['name' => 'Box', 'short_name' => 'box', 'unit_type' => 'pack'],
            ['name' => 'Carton', 'short_name' => 'ctn', 'unit_type' => 'pack'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(
                ['short_name' => $unit['short_name']],
                $unit
            );
        }
    }
}
